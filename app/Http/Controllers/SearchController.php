<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SearchController extends Controller
{
    public function __invoke(Request $request)
    {
        $term = trim((string) $request->query('q', ''));
        if (mb_strlen($term) < 2) {
            return ['patients' => [], 'appointments' => [], 'inventory' => []];
        }

        $like = '%'.$term.'%';
        $patients = DB::table('people')
            ->where('type', 'patient')
            ->where(function ($query) use ($like) {
                $query->where('name', 'like', $like)->orWhere('phone', 'like', $like);
            })
            ->orderBy('name')
            ->limit(6)
            ->get(['id', 'name', 'phone']);

        $appointments = DB::table('appointments')
            ->join('people', 'appointments.people_id', '=', 'people.id')
            ->where('people.name', 'like', $like)
            ->orderByDesc('appointments.date_time')
            ->limit(5)
            ->get(['appointments.id', 'people.name', 'appointments.date_time', 'appointments.status']);

        $inventory = [];
        if (Schema::hasTable('inventory_items')) {
            $inventory = InventoryItem::query()
                ->where(function ($query) use ($like) {
                    $query->where('name', 'like', $like)
                        ->orWhere('sku', 'like', $like)
                        ->orWhere('barcode', 'like', $like);
                })
                ->limit(5)
                ->get(['id', 'name', 'sku', 'quantity', 'unit']);
        }

        return compact('patients', 'appointments', 'inventory');
    }
}
