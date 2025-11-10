<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\MaterialType;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BossDataManagementController extends Controller
{
    /**
     * Cost Data index (Boss view)
     */

    public function index(Request $request)
    {
        $perPage = 10;

        $activeTab = $request->query('tab', 'cost');

        // Filters lists
        $types = MaterialType::orderBy('name')->pluck('name', 'id');
        $units = Unit::orderByRaw('COALESCE(label, name)')->pluck('label', 'id');

        // Pull what we need once
        $rows = DB::table('product_items')
            ->select(['material', 'quantity', 'sizeWidth', 'sizeHeight', 'sizeUnit'])
            ->whereNotNull('material')
            ->get();

        // name -> totals
        $usedMap     = []; // sum(quantity)
        $areaQtyMap  = []; // sum(quantity * area_in_sqinch)

        // helper: to inches
        $toInches = function ($v, $unit) {
            $v = (float) $v;
            if ($v <= 0) return 0.0;

            $u = is_string($unit) ? strtolower(trim($unit)) : '';
            switch ($u) {
                case 'in': case 'inch': case 'inches':
                default:
                    return $v;
                case 'ft': case 'foot': case 'feet':
                    return $v * 12.0;
                case 'cm': case 'centimeter': case 'centimeters':
                    return $v * 0.3937007874;
                case 'mm': case 'millimeter': case 'millimeters':
                    return $v * 0.03937007874;
                case 'm': case 'meter': case 'meters':
                    return $v * 39.37007874;
            }
        };

        foreach ($rows as $r) {
            $qty = (float) $r->quantity;
            if ($qty <= 0) continue;

            // width/height -> inches; area in sq-in
            $wIn = $toInches($r->sizeWidth ?? 0,  $r->sizeUnit ?? 'in');
            $hIn = $toInches($r->sizeHeight ?? 0, $r->sizeUnit ?? 'in');
            $areaSqIn = max(0, $wIn) * max(0, $hIn); // 0 if any missing/invalid

            // parse materials (JSON first, fallback to comma list)
            $list = [];
            $raw  = is_string($r->material) ? trim($r->material) : $r->material;

            $decoded = is_string($raw) ? json_decode($raw, true) : null;
            if (is_array($decoded)) {
                $list = $decoded;
            } else if (is_string($raw)) {
                $str = trim($raw, "[]");
                if (strpos($str, ',') !== false) {
                    $list = array_map(function ($s) {
                        return trim($s, " \t\n\r\0\x0B\"'");
                    }, explode(',', $str));
                } elseif ($str !== '') {
                    $list = [trim($str, " \t\n\r\0\x0B\"'")];
                }
            }

            foreach ($list as $name) {
                $key = mb_strtolower(trim((string) $name));
                if ($key === '') continue;

                // used quantity: add whole item qty for each listed material
                $usedMap[$key] = ($usedMap[$key] ?? 0) + $qty;

                // area-qty: quantity × area per item (sum later × unitCost)
                $areaQtyMap[$key] = ($areaQtyMap[$key] ?? 0.0) + ($qty * $areaSqIn);
            }
        }

        // page materials and decorate with computed fields
        $materials = Material::with(['materialType:id,name', 'unit:id,label'])
            ->orderBy('materialName')
            ->paginate($perPage);

        $materials->getCollection()->transform(function ($m) use ($usedMap, $areaQtyMap) {
            $key = mb_strtolower(trim((string) $m->materialName));
            $usedQty     = (float) ($usedMap[$key] ?? 0);
            $areaQtySum  = (float) ($areaQtyMap[$key] ?? 0.0); // already qty × area
            $unitCost    = (float) $m->unitCost;

            $m->used_quantity = $usedQty;
            $m->total_cost    = $areaQtySum * $unitCost; // RM

            return $m;
        });

        // ===== Another Data: Orders + quantities =====

        $orders = DB::table('orders as o')
        // If this is a redo, o.redo points to the original order
        ->leftJoin('orders as base', 'base.id', '=', 'o.redo')

        // Aggregate product & item quantities under this order
        ->leftJoin('products as p', 'p.OrderID', '=', 'o.id')                  // products.OrderID → orders.id
        ->leftJoin('product_items as pi', 'pi.ProductID', '=', 'p.ProductID')  // product_items.ProductID → products.ProductID

        ->groupBy(
            'o.id',
            'o.order_number',
            'o.created_at',
            'o.status',
            'o.redo',
            'base.order_number'
        )
        ->select([
            'o.id',
            'o.order_number',                // the current order’s number
            'o.created_at',
            'o.status',                      // keep orders with status=1; blade can show REDO badge

            // For redo display logic in blade:
            'base.order_number as base_order_number',

            // simple indicator you can also use, though blade can test o.redo directly
            DB::raw('CASE WHEN o.redo IS NULL THEN 0 ELSE 1 END as is_redo'),

            // aggregates
            DB::raw('COUNT(DISTINCT p.ProductID) AS products_count'),
            DB::raw('COALESCE(SUM(pi.quantity), 0) AS total_item_quantity'),
        ])
        ->orderBy('o.created_at', 'desc')
        ->paginate(10, ['*'], 'orders_page')
        ->withQueryString();

        return view('boss.datamanagement', compact('materials', 'types', 'units', 'orders', 'activeTab'));
    }

    // ---------- Types ----------
    public function storeType(Request $request)
    {
        $request->validate([
            'typeName' => 'required|string|max:255|unique:material_types,name'
        ]);
        $type = MaterialType::create(['name' => $request->typeName]);
        return response()->json(['success' => true, 'type' => $type->name, 'id' => $type->id]);
    }

    public function updateType(Request $request, $id)
    {
        $type = MaterialType::findOrFail($id);
        $request->validate([
            'typeName' => 'required|string|max:255|unique:material_types,name,'.$type->id
        ]);
        $type->update(['name' => $request->typeName]);
        return response()->json(['success' => true, 'type' => $type->name, 'id' => $type->id]);
    }

    public function destroyType($id)
    {
        $type = MaterialType::findOrFail($id);
        $type->delete();
        return response()->json(['success' => true]);
    }

    // ---------- Units ----------
    public function storeUnit(Request $request)
    {
        $request->validate([
            'unitName'  => 'required|string|max:255|unique:units,name',
            'unitLabel' => 'required|string|max:255|unique:units,label',
        ]);
        $unit = Unit::create(['name' => $request->unitName, 'label' => $request->unitLabel]);
        return response()->json(['success' => true, 'label' => $unit->label, 'id' => $unit->id]);
    }

    public function updateUnit(Request $request, $id)
    {
        $unit = Unit::findOrFail($id);
        $request->validate([
            'unitName'  => 'required|string|max:255|unique:units,name,'.$unit->id,
            'unitLabel' => 'required|string|max:255|unique:units,label,'.$unit->id,
        ]);
        $unit->update(['name' => $request->unitName, 'label' => $request->unitLabel]);
        return response()->json(['success' => true, 'label' => $unit->label, 'id' => $unit->id]);
    }

    public function destroyUnit($id)
    {
        $unit = Unit::findOrFail($id);
        $unit->delete();
        return response()->json(['success' => true]);
    }

    // ---------- Materials ----------
    public function store(Request $request)
    {
        $request->validate([
            'materialName'     => 'required|string|max:255',
            'material_type_id' => 'required|exists:material_types,id',
            'unit_id'          => 'required|exists:units,id',
            'unitCost'         => 'required|numeric|min:0',
        ]);

        $material = Material::create([
            'materialName'     => $request->materialName,
            'material_type_id' => $request->material_type_id,
            'unit_id'          => $request->unit_id,
            'unitCost'         => $request->unitCost,
        ])->load(['materialType:id,name','unit:id,label']);

        return response()->json(['success' => true, 'material' => $material]);
    }

    public function update(Request $request, $id)
    {
        $material = Material::findOrFail($id);
        $request->validate([
            'qeName' => 'nullable|string|max:255',
            'qeNew'  => 'required|numeric|min:0',
            'qeUnit' => 'required|exists:units,id',
        ]);

        $material->update([
            'materialName' => $request->qeName ?: $material->materialName,
            'unitCost'     => $request->qeNew,
            'unit_id'      => $request->qeUnit,
        ]);

        $material->load(['unit:id,label']);
        return response()->json(['success' => true, 'material' => $material]);
    }

    public function destroy($id)
    {
        $material = Material::findOrFail($id);
        $material->delete();
        return response()->json(['success' => true]);
    }
}