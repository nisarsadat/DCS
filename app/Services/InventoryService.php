<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function move(InventoryItem $item, string $type, float $quantity, array $context = []): InventoryMovement
    {
        $idempotency = $context['idempotency_key'] ?? null;
        if ($idempotency) {
            $cached = Cache::get($this->cacheKey($idempotency));
            if ($cached) {
                return InventoryMovement::query()->findOrFail($cached);
            }
        }

        $movement = DB::transaction(function () use ($item, $type, $quantity, $context) {
            $locked = InventoryItem::query()->whereKey($item->id)->lockForUpdate()->firstOrFail();
            $before = (float) $locked->quantity;

            try {
                $after = StockMath::apply($type, $before, $quantity);
            } catch (\InvalidArgumentException $exception) {
                throw ValidationException::withMessages([
                    'quantity' => [$exception->getMessage()],
                ]);
            }

            $locked->quantity = $after;
            if ($type === 'in' && isset($context['unit_cost'])) {
                $locked->purchase_price = $context['unit_cost'];
            }
            $locked->save();

            $movement = InventoryMovement::query()->create([
                'inventory_item_id' => $locked->id,
                'type' => $type,
                'quantity' => $quantity,
                'quantity_before' => $before,
                'quantity_after' => $after,
                'unit_cost' => $context['unit_cost'] ?? $locked->purchase_price,
                'note' => $context['note'] ?? null,
                'reference' => $context['reference'] ?? null,
                'user_id' => $context['user_id'] ?? auth()->id(),
                'dentist_id' => $context['dentist_id'] ?? null,
                'cure_id' => $context['cure_id'] ?? null,
                'happened_at' => $context['happened_at'] ?? now(),
            ]);

            AuditLogger::log('inventory.'.$type, InventoryItem::class, $locked->id, [
                'before' => $before,
                'after' => $after,
                'quantity' => $quantity,
                'reference' => $movement->reference,
            ]);

            return $movement;
        });

        if ($idempotency) {
            Cache::put($this->cacheKey($idempotency), $movement->id, now()->addMinutes(10));
        }

        return $movement;
    }

    public function consumeMany(array $lines, array $context = []): void
    {
        foreach ($lines as $line) {
            $item = InventoryItem::query()->findOrFail($line['inventory_item_id']);
            $this->move($item, 'out', (float) $line['quantity'], [
                'note' => $line['note'] ?? 'Treatment consumption',
                'reference' => $context['reference'] ?? null,
                'dentist_id' => $context['dentist_id'] ?? null,
                'cure_id' => $context['cure_id'] ?? null,
                'user_id' => $context['user_id'] ?? auth()->id(),
                'happened_at' => $context['happened_at'] ?? now(),
                'idempotency_key' => $context['idempotency_key'] ?? null
                    ? ($context['idempotency_key'].':'.$item->id)
                    : null,
            ]);
        }
    }

    private function cacheKey(string $key): string
    {
        return 'inventory-move:'.sha1($key);
    }
}
