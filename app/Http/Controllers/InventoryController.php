<?php

namespace App\Http\Controllers;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Services\AuditLogger;
use App\Services\InventoryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InventoryController extends Controller
{
    public function __construct(private InventoryService $inventory)
    {
    }

    public function summary()
    {
        $items = InventoryItem::query()->where('is_active', true);
        $today = now()->startOfDay();

        return [
            'total_items' => (clone $items)->count(),
            'low_stock' => (clone $items)->whereColumn('quantity', '<=', 'min_stock')->where('quantity', '>', 0)->count(),
            'out_of_stock' => (clone $items)->where('quantity', '<=', 0)->count(),
            'inventory_value' => (float) (clone $items)->selectRaw('COALESCE(SUM(quantity * purchase_price), 0) as value')->value('value'),
            'expiring' => (clone $items)->whereNotNull('expires_at')->whereBetween('expires_at', [$today->toDateString(), $today->copy()->addDays(30)->toDateString()])->count(),
            'expired' => (clone $items)->whereNotNull('expires_at')->whereDate('expires_at', '<', $today)->count(),
        ];
    }

    public function index(Request $request)
    {
        $query = InventoryItem::query()->with(['category:id,name', 'supplier:id,name']);

        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(function ($inner) use ($search) {
                $inner->where('name', 'like', '%'.$search.'%')
                    ->orWhere('sku', 'like', '%'.$search.'%')
                    ->orWhere('barcode', 'like', '%'.$search.'%')
                    ->orWhere('batch_number', 'like', '%'.$search.'%');
            });
        }

        if ($request->filled('category_id')) {
            $query->where('inventory_category_id', $request->integer('category_id'));
        }

        $status = $request->query('status');
        if ($status === 'out') {
            $query->where('quantity', '<=', 0);
        } elseif ($status === 'low') {
            $query->whereColumn('quantity', '<=', 'min_stock')->where('quantity', '>', 0);
        } elseif ($status === 'in') {
            $query->whereColumn('quantity', '>', 'min_stock');
        } elseif ($status === 'expiring') {
            $query->whereNotNull('expires_at')->whereBetween('expires_at', [now()->toDateString(), now()->addDays(30)->toDateString()]);
        } elseif ($status === 'expired') {
            $query->whereNotNull('expires_at')->whereDate('expires_at', '<', now()->toDateString());
        }

        $sort = in_array($request->query('sort'), ['name', 'quantity', 'expires_at', 'purchase_price', 'updated_at'], true)
            ? $request->query('sort')
            : 'name';
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';

        $paginator = $query->orderBy($sort, $direction)->paginate($request->integer('per_page', 10));
        $paginator->getCollection()->transform(function (InventoryItem $item) {
            $item->status = $item->stockStatus();
            $item->value = round($item->quantity * $item->purchase_price, 2);

            return $item;
        });

        return $paginator;
    }

    public function store(Request $request)
    {
        $data = $this->validateItem($request);
        $opening = (float) ($data['quantity'] ?? 0);
        $data['quantity'] = 0;
        $item = InventoryItem::query()->create($data);

        if ($opening > 0) {
            $this->inventory->move($item, 'in', $opening, [
                'note' => 'Opening stock',
                'unit_cost' => $item->purchase_price,
                'reference' => 'opening:'.$item->id,
            ]);
            $item->refresh();
        }

        AuditLogger::log('inventory.created', InventoryItem::class, $item->id, ['name' => $item->name]);

        return response()->json($item->load(['category', 'supplier']), 201);
    }

    public function update(Request $request, InventoryItem $inventoryItem)
    {
        $data = $this->validateItem($request, $inventoryItem->id);
        unset($data['quantity']);
        $before = $inventoryItem->only(['name', 'sku', 'min_stock', 'purchase_price', 'expires_at']);
        $inventoryItem->update($data);
        AuditLogger::log('inventory.updated', InventoryItem::class, $inventoryItem->id, [
            'before' => $before,
            'after' => $inventoryItem->only(array_keys($before)),
        ]);

        return $inventoryItem->fresh()->load(['category', 'supplier']);
    }

    public function move(Request $request, InventoryItem $inventoryItem)
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(['in', 'out', 'adjust'])],
            'quantity' => ['required', 'numeric', 'min:0'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:255'],
            'dentist_id' => ['nullable', 'exists:people,id'],
            'cure_id' => ['nullable', 'integer'],
            'happened_at' => ['nullable', 'date'],
        ]);

        $movement = $this->inventory->move($inventoryItem, $data['type'], (float) $data['quantity'], [
            'unit_cost' => $data['unit_cost'] ?? null,
            'note' => $data['note'] ?? null,
            'dentist_id' => $data['dentist_id'] ?? null,
            'cure_id' => $data['cure_id'] ?? null,
            'happened_at' => isset($data['happened_at']) ? Carbon::parse($data['happened_at']) : now(),
            'reference' => $data['type'].':'.$inventoryItem->id.':'.now()->timestamp,
            'idempotency_key' => $request->header('Idempotency-Key'),
        ]);

        return response()->json($movement, 201);
    }

    public function movements(Request $request)
    {
        $query = InventoryMovement::query()->with([
            'item:id,name,unit',
            'dentist:id,name',
            'user:id,first_name,last_name',
        ]);

        if ($request->filled('item_id')) {
            $query->where('inventory_item_id', $request->integer('item_id'));
        }
        if ($request->filled('dentist_id')) {
            $query->where('dentist_id', $request->integer('dentist_id'));
        }
        if ($request->filled('type')) {
            $query->where('type', $request->query('type'));
        }
        if ($request->filled('from')) {
            $query->whereDate('happened_at', '>=', $request->query('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('happened_at', '<=', $request->query('to'));
        }

        return $query->orderByDesc('happened_at')->paginate($request->integer('per_page', 15));
    }

    public function report(Request $request)
    {
        $items = InventoryItem::query()->with('category:id,name')->orderBy('name')->get()->map(function (InventoryItem $item) {
            return [
                'name' => $item->name,
                'sku' => $item->sku,
                'category' => $item->category?->name,
                'unit' => $item->unit,
                'quantity' => $item->quantity,
                'min_stock' => $item->min_stock,
                'purchase_price' => $item->purchase_price,
                'value' => round($item->quantity * $item->purchase_price, 2),
                'batch' => $item->batch_number,
                'expires_at' => optional($item->expires_at)->toDateString(),
                'status' => $item->stockStatus(),
            ];
        });

        $consumption = InventoryMovement::query()
            ->select('dentist_id', 'inventory_item_id', DB::raw('SUM(quantity) as quantity'))
            ->where('type', 'out')
            ->when($request->filled('from'), fn ($q) => $q->whereDate('happened_at', '>=', $request->query('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('happened_at', '<=', $request->query('to')))
            ->groupBy('dentist_id', 'inventory_item_id')
            ->with(['item:id,name', 'dentist:id,name'])
            ->get();

        return [
            'items' => $items,
            'consumption' => $consumption,
            'summary' => $this->summary(),
        ];
    }

    public function categories()
    {
        return InventoryCategory::query()->orderBy('name')->get();
    }

    public function storeCategory(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:inventory_categories,name'],
        ]);

        return response()->json(InventoryCategory::query()->create($data), 201);
    }

    private function validateItem(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'sku' => ['nullable', 'string', 'max:60', Rule::unique('inventory_items', 'sku')->ignore($id)],
            'barcode' => ['nullable', 'string', 'max:80', Rule::unique('inventory_items', 'barcode')->ignore($id)],
            'inventory_category_id' => ['nullable', 'exists:inventory_categories,id'],
            'supplier_id' => ['nullable', 'exists:people,id'],
            'unit' => ['required', 'string', 'max:40'],
            'quantity' => ['nullable', 'numeric', 'min:0'],
            'min_stock' => ['required', 'numeric', 'min:0'],
            'reorder_level' => ['required', 'numeric', 'min:0'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'batch_number' => ['nullable', 'string', 'max:80'],
            'expires_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }
}
