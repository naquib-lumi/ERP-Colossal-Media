<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Enums\StockMovementType;
use App\Http\Requests\StoreStockMovementRequest;
use App\Models\Material;
use App\Models\MaterialType;
use App\Services\MaterialStockService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Material stock: list, history, manual add/deduct and alert settings.
 * Viewing: every role on the route group. Changing stock: roles in canAdjust().
 * Settings (unit, alert levels): admin only (route middleware).
 */
class InventoryController extends Controller
{
    public function __construct(private MaterialStockService $stock)
    {
    }

    /** Roles that may add or deduct stock by hand. */
    public static function canAdjust(?string $role): bool
    {
        return in_array($role, [
            Role::Admin->value,
            Role::Salesperson->value, Role::HeadSalesperson->value,
            Role::Artist->value, Role::HeadArtist->value,
            Role::DataEntry->value,
        ], true);
    }

    public function index(Request $request)
    {
        $q      = trim((string) $request->query('q', ''));
        $type   = (string) $request->query('type', 'all');
        $status = (string) $request->query('status', 'all');

        $materials = Material::with('materialType:id,name')
            ->where('active', true)
            ->when($q !== '', fn (Builder $b) => $b->where('materialName', 'like', "%{$q}%"))
            ->when($type !== 'all' && $type !== '', fn (Builder $b) => $b->where('material_type_id', $type))
            ->when($status === 'low', fn (Builder $b) => $b->where(function (Builder $w) {
                $w->where(fn ($x) => $x->whereNotNull('low_stock_quantity')->whereColumn('stock_quantity', '<=', 'low_stock_quantity'))
                  ->orWhere(fn ($x) => $x->whereNotNull('low_stock_volume')->whereColumn('stock_volume', '<=', 'low_stock_volume'));
            }))
            ->when($status === 'negative', fn (Builder $b) => $b->where(fn ($w) => $w->where('stock_quantity', '<', 0)->orWhere('stock_volume', '<', 0)))
            ->orderBy('materialName')
            ->paginate(25)
            ->withQueryString();

        return view('inventory.index', [
            'materials' => $materials,
            'types'     => MaterialType::where('active', true)->orderBy('name')->get(['id', 'name']),
            'filters'   => compact('q', 'type', 'status'),
            'canAdjust' => self::canAdjust($request->user()->role),
            'isAdmin'   => $request->user()->hasRole(Role::Admin),
        ]);
    }

    public function show(Request $request, Material $material)
    {
        $movements = $material->stockMovements()
            ->with(['user:id,name,role', 'order:id,order_number'])
            ->latest('id')
            ->paginate(30);

        return view('inventory.show', [
            'material'  => $material->load('materialType:id,name'),
            'movements' => $movements,
            'canAdjust' => self::canAdjust($request->user()->role),
            'isAdmin'   => $request->user()->hasRole(Role::Admin),
        ]);
    }

    public function storeMovement(StoreStockMovementRequest $request, Material $material)
    {
        $sign = $request->input('direction') === 'add' ? 1 : -1;

        $this->stock->record(
            $material,
            StockMovementType::from($request->input('type')),
            $sign * (float) $request->input('quantity', 0),
            $sign * MaterialStockService::fromSqFt($request->input('volume_sqft', 0)),
            $request->input('reason'),
            $request->user()->id,
        );

        return back()->with('success', "Stock updated for {$material->materialName}.");
    }

    public function updateSettings(Request $request, Material $material)
    {
        $data = $request->validate([
            'quantity_unit'         => ['nullable', 'string', 'max:30'],
            'low_stock_quantity'    => ['nullable', 'numeric', 'min:0', 'max:1000000000'],
            'low_stock_volume_sqft' => ['nullable', 'numeric', 'min:0', 'max:1000000000'],
        ]);

        $material->update([
            'quantity_unit'      => isset($data['quantity_unit']) ? trim($data['quantity_unit']) : null,
            'low_stock_quantity' => $data['low_stock_quantity'] ?? null,
            'low_stock_volume'   => isset($data['low_stock_volume_sqft'])
                ? MaterialStockService::fromSqFt($data['low_stock_volume_sqft'])
                : null,
        ]);

        return back()->with('success', "Settings saved for {$material->materialName}.");
    }
}
