<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\Lead;
use App\Models\Material;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Who may see and change quotations, and saving one with its products and
 * items in one transaction. Every amount is taken as typed; nothing is
 * calculated (client rule).
 */
class QuotationService
{
    /** Roles that create and edit quotations (client answer no. 1). */
    public const EDITOR_ROLES = [
        Role::HeadSalesperson, Role::Salesperson, Role::HeadArtist, Role::Artist,
    ];

    /** Editors plus admin and boss, who see every quotation. */
    public const VIEWER_ROLES = [
        Role::Admin, Role::Boss, Role::HeadSalesperson, Role::Salesperson, Role::HeadArtist, Role::Artist,
    ];

    public const SIZE_UNITS = ['mm', 'cm', 'inch', 'ft', 'piece'];

    public const QUANTITY_UNITS = ['pcs', 'job', 'set', 'lot', 'unit'];

    public static function roleValues(array $roles): array
    {
        return array_map(fn (Role $r) => $r->value, $roles);
    }

    public static function canCreate(User $user): bool
    {
        return in_array($user->role, self::roleValues(self::EDITOR_ROLES), true);
    }

    /** Salespeople see the quotations they own or created; the other roles see all. */
    public static function canView(User $user, Quotation $quotation): bool
    {
        if (! in_array($user->role, self::roleValues(self::VIEWER_ROLES), true)) {
            return false;
        }

        return ! $user->hasRole(Role::Salesperson)
            || (int) $quotation->salesperson_id === (int) $user->id
            || (int) $quotation->created_by === (int) $user->id;
    }

    public static function canEdit(User $user, Quotation $quotation): bool
    {
        return self::canCreate($user) && self::canView($user, $quotation) && $quotation->isEditable();
    }

    /** Limit a quotation query to what $user may see. */
    public static function scopeVisible(Builder $query, User $user): Builder
    {
        return $query->when($user->hasRole(Role::Salesperson), fn (Builder $q) => $q
            ->where(fn (Builder $w) => $w->where('salesperson_id', $user->id)->orWhere('created_by', $user->id)));
    }

    /** Customers $user may quote for: a salesperson only their own leads. */
    public static function leadsFor(User $user)
    {
        return Lead::query()
            ->when($user->hasRole(Role::Salesperson), fn (Builder $q) => $q->where('salesperson_id', $user->id))
            ->orderBy('company_name')
            ->get(['id', 'company_name', 'name', 'salesperson_id']);
    }

    /** Active material names for the picker, A–Z. */
    public static function materialNames(): array
    {
        return Material::where('active', true)->orderBy('materialName')->pluck('materialName')->all();
    }

    /**
     * Create or update $quotation from validated form data:
     * header fields + products[] { product_name, description, materials[], items[] {...} }.
     * Products and items are replaced as a whole.
     */
    public function save(Quotation $quotation, array $data, User $user): Quotation
    {
        return DB::transaction(function () use ($quotation, $data, $user) {
            $lead = Lead::findOrFail($data['lead_id']);
            $canonical = $this->canonicalMaterialNames();

            $quotation->fill([
                'company_id'     => $data['company_id'],
                'lead_id'        => $lead->id,
                'attention'      => $data['attention'] ?? null,
                'salesperson_id' => $data['salesperson_id'] ?? $lead->salesperson_id,
                'quotation_date' => $data['quotation_date'],
                'terms'          => $data['terms'] ?? null,
                'po_number'      => $data['po_number'] ?? null,
                'subtotal'       => $data['subtotal'] ?? 0,
                'discount'       => $data['discount'] ?? 0,
                'tax'            => $data['tax'] ?? 0,
                'grand_total'    => $data['grand_total'] ?? 0,
                'notes'          => $data['notes'] ?? null,
            ]);
            if (! $quotation->exists) {
                $quotation->created_by = $user->id;
            }
            $quotation->save();

            $quotation->products()->delete(); // items cascade

            foreach (array_values($data['products']) as $p => $product) {
                $materials = collect($product['materials'] ?? [])
                    ->map(fn ($name) => $canonical[mb_strtolower(trim((string) $name))] ?? null)
                    ->filter()->unique()->values()->all();

                $saved = $quotation->products()->create([
                    'sort'         => $p,
                    'product_name' => trim($product['product_name']),
                    'description'  => $product['description'] ?? null,
                    'materials'    => $materials,
                ]);

                foreach (array_values($product['items']) as $i => $item) {
                    $saved->items()->create([
                        'sort'          => $i,
                        'description'   => $item['description'] ?? null,
                        'size_width'    => $item['size_width'] ?? null,
                        'size_height'   => $item['size_height'] ?? null,
                        'size_unit'     => $item['size_unit'] ?? null,
                        'quantity'      => (int) $item['quantity'],
                        'quantity_unit' => $item['quantity_unit'] ?? 'pcs',
                        'unit_price'    => $item['unit_price'] ?? 0,
                        'total'         => $item['total'] ?? 0,
                    ]);
                }
            }

            return $quotation->load('products.items');
        });
    }

    /** lower-cased name => name as spelled in the materials list. */
    private function canonicalMaterialNames(): array
    {
        return Material::pluck('materialName')
            ->mapWithKeys(fn ($name) => [mb_strtolower(trim($name)) => $name])
            ->all();
    }
}
