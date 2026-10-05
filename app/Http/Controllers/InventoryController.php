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
use Illuminate\Validation\Rule;

/**
 * Material stock: list, history, manual add/deduct and alert settings.
 * Viewing: every role on the route group. Changing stock: roles in canAdjust().
 * Settings (unit, low-stock alert level): admin only (route middleware).
 */
class InventoryController extends Controller
{
    public function __construct(private MaterialStockService $stock)
    {
    }

    /** Roles that may add or deduct stock by hand: admin and management (boss). */
    public static function canAdjust(?string $role): bool
    {
        return in_array($role, [Role::Admin->value, Role::Boss->value], true);
    }

    public function index(Request $request)
    {
        $q      = trim((string) $request->query('q', ''));
        $type   = (string) $request->query('type', 'all');
        $status = (string) $request->query('status', 'all');

        $materials = Material::with('materialType:id,name')
            ->where('active', true)
            ->when($q !== '', fn (Builder $b) => $b->where(fn (Builder $w) => $w->where('materialName', 'like', "%{$q}%")->orWhere('internal_ref', 'like', "%{$q}%")))
            ->when($type !== 'all' && $type !== '', fn (Builder $b) => $b->where('material_type_id', $type))
            ->when($status === 'low', fn (Builder $b) => $b->whereNotNull('low_stock_quantity')->whereColumn('stock_quantity', '<=', 'low_stock_quantity'))
            ->when($status === 'near', fn (Builder $b) => $b->whereNotNull('low_stock_quantity')
                ->whereColumn('stock_quantity', '>', 'low_stock_quantity')
                ->whereRaw('stock_quantity <= CEIL(low_stock_quantity * ?)', [Material::NEAR_LOW_FACTOR]))
            ->when($status === 'negative', fn (Builder $b) => $b->where('stock_quantity', '<', 0))
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
            ->with('user:id,name,role')
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
            $sign * (int) $request->input('quantity'),
            $request->input('reason'),
            $request->user()->id,
        );

        return back()->with('success', "Stock updated for {$material->materialName}.");
    }

    public function updateSettings(Request $request, Material $material)
    {
        $data = $request->validate([
            'quantity_unit'       => ['nullable', Rule::in(Material::UNITS)],
            'low_stock_quantity'  => ['nullable', 'integer', 'min:0', 'max:1000000000'],
            'materialDescription' => ['nullable', 'string', 'max:1000'],
            'internal_ref'        => ['nullable', 'string', 'max:50'],
        ], [
            'quantity_unit.in'           => 'Pick a unit from the list.',
            'low_stock_quantity.integer' => 'The low-stock alert must be a whole number.',
        ]);

        $material->update([
            'quantity_unit'       => $data['quantity_unit'] ?? null,
            'low_stock_quantity'  => $data['low_stock_quantity'] ?? null,
            'materialDescription' => isset($data['materialDescription']) ? trim($data['materialDescription']) : null,
            'internal_ref'        => isset($data['internal_ref']) ? trim($data['internal_ref']) : null,
        ]);

        return back()->with('success', "Settings saved for {$material->materialName}.");
    }
}
