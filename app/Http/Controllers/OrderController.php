<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if (!$user->hasRole('salesperson')) {
            abort(403, 'Unauthorized');
        }
        return view('sales.order-management');
    }

    public function getOrders(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasRole('salesperson')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $orders = Order::with('lead', 'salesperson', 'products')->orderBy('created_at', 'desc');
        $orders = $orders->where('salesperson_id', $user->id);

        if ($request->has('search') && $request->input('search')['value']) {
            $search = $request->input('search')['value'];
            $orders->where(function ($query) use ($search) {
                $query->where('orderTitle', 'like', "%{$search}%")
                      ->orWhere('id', 'like', "%{$search}%")
                      ->orWhereHas('lead', function ($q) use ($search) {
                          $q->where('company_name', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%");
                      });
            });
        }

        return DataTables::of($orders)
            ->addColumn('order_data', function ($order) {
                return '<div class="order-data-cell">' .
                       '<span class="order-id">' . $order->id . '</span><br>' .
                       '<small class="text-muted">' . $order->orderStatus . '</small>' .
                       '</div>';
            })
            ->addColumn('lead_details', function ($order) {
                $lead = $order->lead;
                return '<div class="lead-details-cell text-secondary">' .
                       '<div class="d-flex align-items-center mb-1"><i class="bx bxs-building me-2"></i>' . ($lead->company_name ?? $order->companyName ?? 'N/A') . '</div>' .
                       '<div class="d-flex align-items-center mb-1"><i class="bx bxs-user me-2"></i>' . ($lead->name ?? $order->leadName ?? 'N/A') . '</div>' .
                       '<div class="d-flex align-items-center mb-1"><i class="bx bxs-phone me-2"></i>' . ($lead->phone ?? $order->leadPhone ?? 'N/A') . '</div>' .
                       '<div class="d-flex align-items-center mb-1"><i class="bx bx-envelope me-2"></i>' . ($lead->email ?? $order->leadEmail ?? 'N/A') . '</div>' .
                       '</div>';
            })
            ->addColumn('order_details', function ($order) {
                return '<div class="order-details-cell">' .
                       $order->orderTitle . '<br>' .
                       '<small class="text-muted">Deadline: ' . $order->deadline . '</small><br>' .
                       '<small class="text-muted">Created: ' . $order->orderDate . '</small>' .
                       '</div>';
            })
            ->addColumn('products', function ($order) {
                $products = $order->products->take(3);
                $html = '<ul class="list-unstyled">';
                foreach ($products as $product) {
                    $html .= '<li>' . $product->product_name . ' (Qty: ' . $product->quantity . ')</li>';
                }
                if ($order->products->count() > 3) $html .= '<li>...</li>';
                $html .= '</ul>';
                return $html;
            })
            ->addColumn('actions', function ($order) {
                $editRoute = route('orders.edit', $order->id);
                $viewRoute = route('orders.show', $order->id);
                return '<div class="actions-cell d-flex gap-2">' .
                       '<a href="' . $editRoute . '" class="btn" title="Edit"><i class="bx bxs-edit me-2" style="font-size: 1.5em;"></i></a>' .
                       '<a href="' . $viewRoute . '" class="btn" title="View"><i class="bx bxs-show me-2" style="font-size: 1.5em;"></i></a>' .
                       '</div>';
            })
            ->rawColumns(['order_data', 'lead_details', 'order_details', 'products', 'actions'])
            ->toJson();
    }

public function create($leadId = null)
{
    $user = Auth::user();
    if (!$user->hasRole('salesperson')) {
        abort(403, 'Unauthorized');
    }
    $lead = null;
    if ($leadId) {
        $lead = Lead::findOrFail($leadId);
    } elseif (old('lead_id')) {
        $lead = Lead::find(old('lead_id'));
    }
    return view('sales.add-order', compact('lead'));
}
          public function store(Request $request)
{
    $user = Auth::user();
    if (!$user->hasRole('salesperson')) {
        return back()->with('error', 'Unauthorized');
    }

    try {
        $request->validate([
            'lead_id' => 'required|exists:leads,id',
            'orderTitle' => 'required|string|max:255',
            'deadline' => 'required|date|after_or_equal:today',
            'approval' => 'required|boolean',
            'orderDetail' => 'nullable|string',
            'products' => 'required|array|min:1',
            'products.*.product_name' => 'required|string|max:255',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.remark' => 'nullable|string',
            'products.*.material_info' => 'nullable|string',
            'products.*.location' => 'nullable|string|max:255',
            'products.*.date_time' => 'nullable|date',
        ]);

        $lead = Lead::findOrFail($request->lead_id);

        $order = Order::create([
            'lead_id' => $request->lead_id,
            'salesperson_id' => $user->id,
            'orderTitle' => $request->orderTitle,
            'deadline' => $request->deadline,
            'approval' => $request->approval,
            'orderDetail' => $request->orderDetail,
            'orderStatus' => 'pending',
            'leadName' => $lead->name,
            'leadPhone' => $lead->phone,
            'companyName' => $lead->company_name,
            'leadEmail' => $lead->email,
            'orderDate' => now(),
        ]);

        foreach ($request->products as $productData) {
            Product::create([
                'OrderID' => $order->id,
                'productName' => $productData['product_name'],
                'totalQuantity' => $productData['quantity'],
                'productRemark' => $productData['remark'],
                'materialRemark' => $productData['material_info'],
                'location' => $productData['location'],
                'date_time' => $productData['date_time'],
            ]);
        }

        return redirect()->route('sales.orders')->with('success', 'Order created successfully');
    } catch (\Illuminate\Validation\ValidationException $e) {
        return redirect()->back()->withErrors($e->validator)->withInput();
    }catch (\Exception $e) {
        dd($e->getMessage());
    }
}

    public function edit($id)
    {
        $order = Order::with('products')->findOrFail($id);
        if ($order->salesperson_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }
        return view('sales.edit-order', compact('order'));
    }

    public function update(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        if ($order->salesperson_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'location' => 'nullable|string|max:255', // Only location editable
        ]);

        $order->update([
            // Update only allowed fields
        ]);

        return redirect()->route('sales.orders')->with('success', 'Order updated successfully');
    }

    public function show($id)
    {
        $order = Order::with('lead', 'salesperson', 'products')->findOrFail($id);
        if ($order->salesperson_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }
        return view('sales.order-view', compact('order'));
    }

    public function destroy($id)
    {
        $order = Order::findOrFail($id);
        if ($order->salesperson_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $order->delete();
        return response()->json(['message' => 'Order deleted successfully']);
    }

    public function searchLeads(Request $request)
{
    $user = Auth::user();
    if (!$user->hasRole('salesperson')) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    $query = $request->input('query');
    if (!$query || strlen($query) < 2) {
        return response()->json([]);
    }

    $leads = Lead::where('salesperson_id', $user->id)
        ->where(function ($q) use ($query) {
            $q->where('company_name', 'LIKE', '%' . $query . '%')
              ->orWhere('name', 'LIKE', '%' . $query . '%');
        })
        ->take(20)
        ->get(['id', 'company_name', 'name'])
        ->map(function ($lead) {
            return [
                'id' => $lead->id,
                'text' => $lead->company_name . ' - ' . $lead->name
            ];
        });

    return response()->json($leads);
}

public function getLead($id)
{
    $lead = Lead::findOrFail($id);
    if ($lead->salesperson_id !== Auth::id() && !Auth::user()->hasRole('head-salesperson')) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    return response()->json([
        'id' => $lead->id,
        'company_name' => $lead->company_name,
        'name' => $lead->name,
        'email' => $lead->email,
        'phone' => $lead->phone
    ]);
}
}