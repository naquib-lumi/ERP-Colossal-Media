<?php

namespace App\Http\Controllers;

use App\Http\Requests\QuotationRequest;
use App\Models\Company;
use App\Models\Quotation;
use App\Models\User;
use App\Services\QuotationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/** Quotations: list, create, view and edit (plan items C2–C3). */
class QuotationController extends Controller
{
    public function __construct(private QuotationService $service)
    {
    }

    public function index(Request $request)
    {
        $user   = $request->user();
        $q      = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', 'all');

        $quotations = QuotationService::scopeVisible(Quotation::query(), $user)
            ->with(['company:id,name', 'lead:id,company_name', 'salesperson:id,name', 'order:id,order_number'])
            ->when(in_array($status, [Quotation::STATUS_PENDING, Quotation::STATUS_CONVERTED], true), fn (Builder $b) => $b->where('status', $status))
            ->when($q !== '', fn (Builder $b) => $b->where(fn (Builder $w) => $w
                ->where('quotation_number', 'like', "%{$q}%")
                ->orWhere('attention', 'like', "%{$q}%")
                ->orWhereHas('lead', fn ($l) => $l->where('company_name', 'like', "%{$q}%"))))
            ->orderByDesc('quotation_date')->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('quotations.index', [
            'quotations' => $quotations,
            'filters'    => compact('q', 'status'),
            'canCreate'  => QuotationService::canCreate($user),
        ]);
    }

    public function create(Request $request)
    {
        abort_unless(QuotationService::canCreate($request->user()), 403);

        return view('quotations.form', $this->formData($request->user(), new Quotation([
            'company_id'     => Company::default()->id,
            'quotation_date' => now()->toDateString(),
        ])));
    }

    public function store(QuotationRequest $request)
    {
        abort_unless(QuotationService::canCreate($request->user()), 403);

        $quotation = $this->service->save(new Quotation(), $request->validated(), $request->user());

        return redirect()->route('quotations.show', $quotation)->with('success', "Quotation {$quotation->quotation_number} created.");
    }

    public function show(Request $request, Quotation $quotation)
    {
        abort_unless(QuotationService::canView($request->user(), $quotation), 403);

        $quotation->load(['company', 'lead', 'salesperson:id,name', 'creator:id,name', 'order:id,order_number', 'products.items']);

        return view('quotations.show', [
            'quotation' => $quotation,
            'canEdit'   => QuotationService::canEdit($request->user(), $quotation),
        ]);
    }

    public function edit(Request $request, Quotation $quotation)
    {
        abort_unless(QuotationService::canView($request->user(), $quotation) && QuotationService::canCreate($request->user()), 403);
        if (! $quotation->isEditable()) {
            return redirect()->route('quotations.show', $quotation)->with('error', 'This quotation is converted to an order and can no longer be edited.');
        }

        return view('quotations.form', $this->formData($request->user(), $quotation->load('products.items')));
    }

    public function update(QuotationRequest $request, Quotation $quotation)
    {
        abort_unless(QuotationService::canView($request->user(), $quotation) && QuotationService::canCreate($request->user()), 403);
        if (! $quotation->isEditable()) {
            return redirect()->route('quotations.show', $quotation)->with('error', 'This quotation is converted to an order and can no longer be edited.');
        }

        $this->service->save($quotation, $request->validated(), $request->user());

        return redirect()->route('quotations.show', $quotation)->with('success', "Quotation {$quotation->quotation_number} saved.");
    }

    private function formData(User $user, Quotation $quotation): array
    {
        return [
            'quotation'     => $quotation,
            'companies'     => Company::orderByDesc('is_default')->orderBy('name')->get(['id', 'name']),
            'leads'         => QuotationService::leadsFor($user),
            'materials'     => QuotationService::materialNames(),
            'sizeUnits'     => QuotationService::SIZE_UNITS,
            'quantityUnits' => QuotationService::QUANTITY_UNITS,
        ];
    }
}
