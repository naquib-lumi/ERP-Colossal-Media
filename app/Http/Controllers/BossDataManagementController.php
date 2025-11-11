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

        // ============== ANOTHER DATA: Orders summary with total cost =================
        // 1) Base orders page (keep your existing filters here if you add any)
        $orders = DB::table('orders as o')
            ->leftJoin('orders as base', 'base.id', '=', 'o.redo') // for redo label
            ->select([
                'o.id',
                'o.order_number',
                'o.created_at',
                'o.status',
                'o.redo',                                // keep redo flag/id
                DB::raw('CASE WHEN o.redo IS NULL THEN 0 ELSE 1 END as is_redo'),
                'base.order_number as base_order_number' // original order number when redo
            ])
            ->orderBy('o.created_at', 'desc')
            ->paginate(10, ['*'], 'orders_page')
            ->withQueryString();

        if ($orders->isEmpty()) {
            return view('boss.datamanagement', compact('materials', 'types', 'units', 'orders', 'activeTab'));
        }

        // Preload products for these orders
        $orderIds = $orders->pluck('id')->all();

        $products = DB::table('products')
            ->select('ProductID', 'OrderID', 'totalQuantity')
            ->whereIn('OrderID', $orderIds)
            ->get();

        $productsByOrder = $products->groupBy('OrderID');

        // Preload items for those products
        $productIds = $products->pluck('ProductID')->all();

        $items = DB::table('product_items')
            ->select('ItemID', 'ProductID', 'quantity', 'sizeWidth', 'sizeHeight', 'sizeUnit', 'material')
            ->whereIn('ProductID', $productIds ?: [0])
            ->get();

        $itemsByProduct = $items->groupBy('ProductID');

        // Material unit costs keyed by name
        $materialCosts = DB::table('materials')
            ->select('materialName', 'unitCost')
            ->get()
            ->keyBy(fn($m) => trim(mb_strtolower($m->materialName)));

        // linear→inch factor (area uses factor^2)
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

        // Attach counts + total cost per order
        $orders->setCollection(
            $orders->getCollection()->map(function ($ord) use ($productsByOrder, $itemsByProduct, $materialCosts) {

                // factor to convert linear units to inches (area uses factor^2)
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

                $prods = $productsByOrder->get($ord->id, collect());

                // products count (optional, if you show it)
                $ord->products_count = $prods->count();

                // === Used quantity: sum of ALL item quantities across ALL products in this order
                $ord->used_quantity = 0;
                $totalCost = 0.0;

                foreach ($prods as $p) {
                    $prodItems = $itemsByProduct->get($p->ProductID, collect());

                    foreach ($prodItems as $it) {
                        $itemQty = (int) ($it->quantity ?? 0);
                        $ord->used_quantity += $itemQty;

                        // area per item (square inches)
                        $w = (float) ($it->sizeWidth ?? 0);
                        $h = (float) ($it->sizeHeight ?? 0);
                        $f = $toInchFactor($it->sizeUnit);
                        $areaSqIn = max(0, $w) * max(0, $h) * ($f * $f);

                        // materials on this item (expects JSON array of names)
                        $names = [];
                        if (!empty($it->material)) {
                            try {
                                $decoded = json_decode($it->material, true, flags: JSON_THROW_ON_ERROR);
                                if (is_array($decoded)) {
                                    $names = array_filter(array_map('strval', $decoded));
                                }
                            } catch (\Throwable $e) { /* ignore bad json */
                            }
                        }

                        // cost: itemQty × area × unitCost, summed for each material on the item
                        foreach ($names as $name) {
                            $key = trim(mb_strtolower($name));
                            if (isset($materialCosts[$key])) {
                                $unitCost = (float) $materialCosts[$key]->unitCost;
                                $totalCost += $itemQty * $areaSqIn * $unitCost;
                            }
                        }
                    }
                }

                $ord->total_cost = $totalCost;

                // keep this if your blade still reads total_item_quantity
                $ord->total_item_quantity = $ord->used_quantity;

                return $ord;
            })
        );

        return view('boss.datamanagement', compact('materials', 'types', 'units', 'orders', 'activeTab'));
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
            'typeName' => 'required|string|max:255|unique:material_types,name,' . $type->id
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
            'unit_id'          => 'required|exists:units,id',
            'unitCost'         => 'required|numeric|min:0',
        ]);

        $material = Material::create([
            'materialName'     => $request->materialName,
            'material_type_id' => $request->material_type_id,
            'unit_id'          => $request->unit_id,
            'unitCost'         => $request->unitCost,
        ])->load(['materialType:id,name', 'unit:id,label']);

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
