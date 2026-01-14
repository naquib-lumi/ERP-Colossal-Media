<?php

namespace App\Http\Controllers;

use App\Models\Material;
use Illuminate\Support\Facades\Auth;
use App\Models\Lead;
use App\Models\Order;
use App\Models\MaterialType;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class BossDataManagementController extends Controller
{
    /**
     * Cost Data index (Boss view)
     */

    public function index(Request $request)
    {
        $perPage = 10;
        $page     = (int) max(1, $request->query('orders_page', 1));
        $activeTab = $request->query('tab', 'cost');
        $range  = $request->query('range', '30');                     // 30, 7, m, lm, all
        $q_id   = trim((string)$request->query('q_id', ''));          // search by order number
        $q_text = trim((string)$request->query('q_text', ''));        // title/company/product
        $ostate = $request->query('status', 'all');                   // in_progress, completed, rejected, all

        $sort   = $request->query('sort', 'date');                    // products|used|cost|date
        $dir    = strtolower($request->query('dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        // Filters lists
        $types = MaterialType::orderBy('name')->get();
        // $units = Unit::orderByRaw('COALESCE(label, name)')->pluck('label', 'id');

        // Machines for Machines Data tab
        $machines = DB::table('machines')
            ->select('id', 'machine_name', 'machine_type', 'active')
            ->orderBy('machine_type')
            ->orderBy('machine_name')
            ->get();

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
                case 'in':
                case 'inch':
                case 'inches':
                default:
                    return $v;
                case 'ft':
                case 'foot':
                case 'feet':
                    return $v * 12.0;
                case 'cm':
                case 'centimeter':
                case 'centimeters':
                    return $v * 0.3937007874;
                case 'mm':
                case 'millimeter':
                case 'millimeters':
                    return $v * 0.03937007874;
                case 'm':
                case 'meter':
                case 'meters':
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
        $q = trim((string) $request->query('q', ''));
        $type = $request->query('type', 'all');

        $materialsQuery = Material::with(['materialType:id,name'])
            ->orderBy('materialName');

        if ($q !== '') {
            $materialsQuery->where('materialName', 'like', "%{$q}%");
        }

        if ($type !== 'all' && $type !== '') {
            $materialsQuery->where('material_type_id', $type);
        }

        $materials = $materialsQuery
            ->paginate($perPage)
            ->withQueryString(); 

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

        // $orders = DB::table('orders as o')
        //     // base order for redo
        //     ->leftJoin('orders as base', 'base.id', '=', 'o.redo')

        //     // only join non-printing products
        //     ->leftJoin('products as p', function ($join) {
        //         $join->on('p.OrderID', '=', 'o.id')
        //             ->where('p.taskType', '!=', 'printing')
        //             ->orWhereNull('p.taskType');   // keep products with NULL taskType if you want
        //     })

        //     ->leftJoin('product_items as pi', 'pi.ProductID', '=', 'p.ProductID')
        //     ->groupBy(
        //         'o.id',
        //         'o.order_number',
        //         'o.created_at',
        //         'o.status',
        //         'o.redo',
        //         'base.order_number'
        //     )
        //     ->select([
        //         'o.id',
        //         'o.order_number',
        //         'o.created_at',
        //         'o.status',
        //         'base.order_number as base_order_number',
        //         DB::raw('CASE WHEN o.redo IS NULL THEN 0 ELSE 1 END as is_redo'),
        //         DB::raw('COUNT(DISTINCT p.ProductID) AS products_count'),
        //         DB::raw('COALESCE(SUM(pi.quantity), 0) AS total_item_quantity'),
        //     ])
        //     ->orderBy('o.created_at', 'desc')
        //     ->paginate(10, ['*'], 'orders_page')
        //     ->withQueryString();


        // ================== ANOTHER DATA: ORDERS ==================
        // 1) Build query WITH filters first
        $ordersQuery = DB::table('orders as o')
            ->leftJoin('orders as base', 'base.id', '=', 'o.redo')
            ->select(
                'o.id',
                'o.order_number',
                'o.created_at',
                'o.status',
                'o.orderStatus',        // <-- ADD THIS
                'o.redo',
                DB::raw('CASE WHEN o.redo IS NULL THEN 0 ELSE 1 END as is_redo'),
                'base.order_number as base_order_number'
            );

        // search by order id / number
        if ($q_id !== '') {
        $needle  = ltrim($q_id, '#');
        $needleR = rtrim($needle, "Rr");
        $numOnly = (int) preg_replace('/\D+/', '', $needleR);
        $ordersQuery->where(function ($q) use ($needleR, $numOnly) {
            $q->where('o.order_number', 'like', "%{$needleR}%")
            ->orWhere('base.order_number', 'like', "%{$needleR}%");
            if ($numOnly > 0) $q->orWhere('o.id', $numOnly);
        });
        }

        // Status filter (your enum)
        if ($ostate === 'rejected') {
            // include rejected orders + redo orders (status = 1)
            $ordersQuery->where(function ($q) {
                $q->where('o.orderStatus', 'rejected')
                ->orWhere('o.status', 1);
            });
        } elseif ($ostate === 'in_progress') {
            // in progress only (exclude redo)
            $ordersQuery->where('o.orderStatus', 'in_progress')
                        ->where('o.status', '<>', 1);
        } elseif ($ostate === 'completed') {
            // completed only (exclude redo)
            $ordersQuery->where('o.orderStatus', 'completed')
                        ->where('o.status', '<>', 1);
        }

        // Date range filter
        switch ($range) {
        case '7':  $ordersQuery->where('o.created_at', '>=', now()->subDays(7));  break;
        case '30': $ordersQuery->where('o.created_at', '>=', now()->subDays(30)); break;
        case 'm':  $ordersQuery->whereBetween('o.created_at', [now()->startOfMonth(), now()->endOfMonth()]); break;
        case 'lm': $ordersQuery->whereBetween('o.created_at', [
                    now()->subMonthNoOverflow()->startOfMonth(),
                    now()->subMonthNoOverflow()->endOfMonth()
                    ]); break;
        case 'all':
        default: /* no date filter */ break;
        }

        // Text search (title/company/product)
        if ($q_text !== '') {
        $ordersQuery->where(function ($q) use ($q_text) {
            $q->where('o.orderTitle', 'like', "%{$q_text}%")
            ->orWhere('o.companyName', 'like', "%{$q_text}%")
            ->orWhereExists(function ($sq) use ($q_text) {
                $sq->from('products as p')
                ->whereColumn('p.OrderID', 'o.id')
                ->where('p.productName', 'like', "%{$q_text}%");
            });
        });
        }

        // Load machines for Machines Data tab
        $machines = DB::table('machines')
            ->select('id', 'machine_name', 'machine_type', 'active')
            ->orderBy('machine_type')
            ->orderBy('machine_name')
            ->get();

        // Pull ALL matching orders (we'll sort and paginate after computing totals)
        $ordersAll = collect($ordersQuery->orderBy('o.created_at', 'desc')->get());

        // Nothing to do?
        if ($ordersAll->isEmpty()) {

            $orders = new LengthAwarePaginator([], 0, $perPage, $page, [
                'path' => url()->current(),
                'pageName' => 'orders_page'
            ]);

            // keep query string in pagination links
            $orders->appends($request->except('orders_page'));

            // IMPORTANT: use $types (not $type)
            return view('boss.datamanagement', compact('materials', 'types', 'orders', 'activeTab', 'machines'));
        }

        // 2) Preload aggregates for ALL matching order IDs
        $orderIds = $ordersAll->pluck('id')->all();

        // products_count in SQL
        $productsAgg = DB::table('products')
        ->select('OrderID', DB::raw('COUNT(DISTINCT ProductID) as products_count'))
        ->whereIn('OrderID', $orderIds)
        ->where('taskType', '!=', 'printing')    
        ->groupBy('OrderID')
        ->get()
        ->keyBy('OrderID');

        // used_quantity in SQL (sum of item qty across products)
        $usedAgg = DB::table('products as p')
        ->join('product_items as pi', 'pi.ProductID', '=', 'p.ProductID')
        ->select('p.OrderID', DB::raw('COALESCE(SUM(pi.quantity),0) as used_quantity'))
        ->whereIn('p.OrderID', $orderIds)
        ->where('p.taskType', '!=', 'printing') 
        ->groupBy('p.OrderID')
        ->get()
        ->keyBy('OrderID');

        // Items + materials for total_cost (we still do cost in PHP)
        $products = DB::table('products')
        ->select('ProductID', 'OrderID', 'totalQuantity', 'status', 'created_at')
        ->whereIn('OrderID', $orderIds)
        ->where('taskType', '!=', 'printing') 
        ->get();
        
        $items = DB::table('product_items')
        ->select('ItemID','ProductID','quantity','sizeWidth','sizeHeight','sizeUnit','material')
        ->whereIn('ProductID', $products->pluck('ProductID')->all() ?: [0])->get();
        
        $itemsByProduct = $items->groupBy('ProductID');

        // material unit costs
        $materialCosts = DB::table('materials')
        ->select('materialName','unitCost')
        ->get()
        ->keyBy(fn($m)=> trim(mb_strtolower($m->materialName)));

        // helper for area factor
        $toInchFactor = function (?string $u): float {
            $u = strtolower((string)$u);
            return match ($u) {
                'mm' => 1/25.4,
                'cm' => 1/2.54,
                'ft', 'feet' => 12.0,
                'inch','in','"' => 1.0,
                default => 1.0,
            };
        };

        // 3) Compute totals for ALL, then filter & sort
        $computed = $ordersAll->map(function ($ord) use ($products, $itemsByProduct, $materialCosts, $productsAgg, $usedAgg, $toInchFactor) {
            $ord->products_count = (int) optional($productsAgg->get($ord->id))->products_count ?? 0;
            $ord->used_quantity  = (int) optional($usedAgg->get($ord->id))->used_quantity ?? 0;
            $ord->total_item_quantity = $ord->used_quantity;

            $totalCost = 0.0;
            foreach ($products->where('OrderID', $ord->id) as $p) {
                foreach ($itemsByProduct->get($p->ProductID, collect()) as $it) {
                    $qty = (int) ($it->quantity ?? 0);
                    if ($qty <= 0) continue;
                    $w = (float) ($it->sizeWidth ?? 0);
                    $h = (float) ($it->sizeHeight ?? 0);
                    $f = $toInchFactor($it->sizeUnit);
                    $areaSqIn = max(0,$w) * max(0,$h) * ($f*$f);

                    $names = [];
                    if (!empty($it->material)) {
                        try {
                            $dec = json_decode($it->material, true, flags: JSON_THROW_ON_ERROR);
                            if (is_array($dec)) $names = array_filter(array_map('strval', $dec));
                        } catch (\Throwable $e) {}
                    }
                    foreach ($names as $name) {
                        $key = trim(mb_strtolower($name));
                        if (isset($materialCosts[$key])) {
                            $unit = (float) $materialCosts[$key]->unitCost;
                            $totalCost += $qty * $areaSqIn * $unit;
                        }
                    }
                }
            }
            $ord->total_cost = $totalCost;

            return $ord;
        });

        // 👇 NEW: drop orders that have no non-printing products
        $computed = $computed->filter(function ($ord) {
            $hasProducts = (int)($ord->products_count ?? 0) > 0;

            // Only completed orders appear in costing data
            $isCompleted = ($ord->orderStatus === 'completed');

            return $hasProducts && $isCompleted;
        });

        $computed = match ($sort) {
            'products' => $computed->sortBy('products_count', SORT_REGULAR, $dir === 'desc'),
            'used'     => $computed->sortBy('used_quantity', SORT_REGULAR, $dir === 'desc'),
            'cost'     => $computed->sortBy('total_cost', SORT_REGULAR, $dir === 'desc'),
            default    => $computed->sortBy('created_at',  SORT_REGULAR, $dir === 'desc'),
        };

        $total = $computed->count();
        $slice = $computed->values()->slice(($page-1)*$perPage, $perPage)->values();


        $orders = new LengthAwarePaginator(
            $slice,
            $total,
            $perPage,
            $page,
            ['path' => url()->current(), 'pageName' => 'orders_page']
        );
        $orders->appends($request->except('orders_page'));

        return view('boss.datamanagement', compact('materials', 'types', 'orders', 'activeTab', 'machines'));
    }

    public function orderProducts(int $orderId)
    {
        // Pull products for this order
        $products = DB::table('products')
            ->select('ProductID', 'OrderID', 'productName', 'totalQuantity', 'status', 'accepted', 'created_at')
            ->where('OrderID', $orderId)
            ->orderBy('created_at', 'asc')
            ->get();

        if ($products->isEmpty()) {
            return response()->json([
                'success'  => true,
                'order_id' => $orderId,
                'products' => [],
            ]);
        }

        // Items for these products
        $productIds = $products->pluck('ProductID')->all();

        $items = DB::table('product_items')
            ->select('ItemID', 'ProductID', 'quantity', 'sizeWidth', 'sizeHeight', 'sizeUnit', 'material')
            ->whereIn('ProductID', $productIds ?: [0])
            ->get()
            ->groupBy('ProductID');

        // Material unit costs
        $materialCosts = DB::table('materials')
            ->select('materialName', 'unitCost')
            ->get()
            ->keyBy(fn ($m) => trim(mb_strtolower($m->materialName)));

        // unit conversion (linear → inches; area uses factor^2)
        $toInchFactor = function (?string $u): float {
            $u = strtolower((string) $u);
            return match ($u) {
                'mm' => 1 / 25.4,
                'cm' => 1 / 2.54,
                'ft', 'feet' => 12.0,
                'inch', 'in', '"' => 1.0,
                default => 1.0,
            };
        };

        $out = [];
        foreach ($products as $p) {
            $prodItems  = $items->get($p->ProductID, collect());
            $usedQty    = 0;
            $totalCost  = 0.0;

            foreach ($prodItems as $it) {
                $itemQty = (int) ($it->quantity ?? 0);
                $usedQty += $itemQty;

                $w = (float) ($it->sizeWidth ?? 0);
                $h = (float) ($it->sizeHeight ?? 0);
                $f = $toInchFactor($it->sizeUnit);
                $areaSqIn = max(0, $w) * max(0, $h) * ($f * $f);

                $names = [];
                if (!empty($it->material)) {
                    try {
                        $decoded = json_decode($it->material, true, flags: JSON_THROW_ON_ERROR);
                        if (is_array($decoded)) {
                            $names = array_filter(array_map('strval', $decoded));
                        }
                    } catch (\Throwable $e) { /* ignore */ }
                }

                foreach ($names as $name) {
                    $key = trim(mb_strtolower($name));
                    if (isset($materialCosts[$key])) {
                        $unit = (float) $materialCosts[$key]->unitCost;
                        $totalCost += $itemQty * $areaSqIn * $unit;
                    }
                }
            }

            $out[] = [
                'product_id'      => $p->ProductID,
                'name'            => $p->productName,
                'status'          => $p->status,
                'accepted'        => (int) $p->accepted,
                'created_at'      => \Carbon\Carbon::parse($p->created_at)->format('Y-m-d'),
                'items_quantity'  => $usedQty,          // sum of item.quantity in this product
                'order_quantity'  => (int) ($p->totalQuantity ?? 0), // the product's own “order qty” field
                'total_cost'      => $totalCost,
            ];
        }

        return response()->json([
            'success'  => true,
            'order_id' => $orderId,
            'products' => $out,
        ]);
    }

    // ---------- Types ----------
    public function storeType(Request $request)
    {
        // $request->validate([
        //     'typeName' => 'required|string|max:255|unique:material_types,name'
        // ]);
        // $type = MaterialType::create(['name' => $request->typeName]);
        // return response()->json(['success' => true, 'type' => $type->name, 'id' => $type->id]);

        // Same validation behaviour as admin side
        $request->validate([
            'typeName' => 'required|string|max:255|unique:material_types,name',
        ]);

        $type = MaterialType::create([
            'name'   => $request->typeName,
            'active' => 1, // new types are active by default
        ]);

        return response()->json([
            'success' => true,
            'type'    => $type,   // return full model (id, name, active…)
        ]);
    }

    public function updateType(Request $request, $id)
    {
        $type = MaterialType::findOrFail($id);

        $request->validate([
            'typeName' => 'required|string|max:255|unique:material_types,name,' . $type->id,
        ]);

        $type->update([
            'name' => $request->typeName,
        ]);

        return response()->json([
            'success' => true,
            'type'    => $type,
        ]);
    }

    /**
     * Toggle Active / Inactive for a material type.
     * Active  = 1
     * Inactive = 0
     */
    public function toggleType($id)
    {
        $type = MaterialType::findOrFail($id);

        // flip 1 → 0, 0 → 1
        $type->active = $type->active ? 0 : 1;
        $type->save();

        return response()->json([
            'success' => true,
            'active'  => (bool) $type->active,
            'type'    => $type,
        ]);
    }

    public function toggleMaterial(int $id)
    {
        $material = Material::findOrFail($id);

        $material->active = $material->active ? 0 : 1;
        $material->save();

        return response()->json([
            'success' => true,
            'active'  => (bool) $material->active,
        ]);
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
            'unitName'  => 'required|string|max:255|unique:units,name,' . $unit->id,
            'unitLabel' => 'required|string|max:255|unique:units,label,' . $unit->id,
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
            'unitCost'         => 'required|numeric|min:0',
        ]);

        $material = Material::create([
            'materialName'     => $request->materialName,
            'material_type_id' => $request->material_type_id,
            'unitCost'         => $request->unitCost,
        ])->load(['materialType:id,name']);

        return response()->json(['success' => true, 'material' => $material]);
    }

    public function update(Request $request, $id)
    {
        $material = Material::findOrFail($id);

        $request->validate([
            'qeName' => 'required|string|max:255',
            'qeNew'  => 'required|numeric|min:0',
            'qeType' => 'required|integer|exists:material_types,id',
        ]);

        $material->materialName     = $request->qeName;
        $material->unitCost         = $request->qeNew;
        $material->material_type_id = (int) $request->qeType;

        $material->save();
        $material->load('materialType');

        return response()->json([
            'success'  => true,
            'material' => [
                'id'        => $material->MaterialID,
                'name'      => $material->materialName,
                'unit_cost' => $material->unitCost,
                'type_id'   => $material->material_type_id,
                'type_name' => optional($material->materialType)->name,
            ],
        ]);
    }

    public function destroy($id)
    {
        $material = Material::findOrFail($id);
        $material->delete();
        return response()->json(['success' => true]);
    }

    public function storeMachine(Request $request)
    {
        $data = $request->validate([
            'machine_name' => 'required|string|max:255',
            'machine_type' => 'required|in:printer,cutter,lamination',
        ]);

        $id = DB::table('machines')->insertGetId([
            'user_id'      => Auth::id(),   // or null if you prefer
            'machine_name' => $data['machine_name'],
            'machine_type' => $data['machine_type'],
            'active'       => 1,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'machine' => [
                    'id'           => $id,
                    'machine_name' => $data['machine_name'],
                    'machine_type' => $data['machine_type'],
                    'active'       => 1,
                ],
            ]);
        }

        return redirect()
            ->route('boss.datamanagement', ['tab' => 'machines'])
            ->with('status', 'Machine added');
    }

public function updateMachine(Request $request, $id)
{
    $data = $request->validate([
        'machine_name' => 'required|string|max:255',
    ]);

    $updated = DB::table('machines')
        ->where('id', $id)
        ->update([
            'machine_name' => $data['machine_name'],
            'updated_at'   => now(),
        ]);

    if (!$updated) {
        return response()->json(['success' => false, 'message' => 'Machine not found'], 404);
    }

    $machine = DB::table('machines')->where('id', $id)->first();

    return response()->json([
        'success' => true,
        'machine' => $machine,
    ]);
}

public function toggleMachineStatus(Request $request, $id)
{
    $machine = DB::table('machines')->where('id', $id)->first();

    if (!$machine) {
        return response()->json(['success' => false, 'message' => 'Machine not found'], 404);
    }

    $newActive = $machine->active ? 0 : 1;

    DB::table('machines')
        ->where('id', $id)
        ->update([
            'active'     => $newActive,
            'updated_at' => now(),
        ]);

    return response()->json([
        'success' => true,
        'active'  => $newActive,
    ]);
}
}
