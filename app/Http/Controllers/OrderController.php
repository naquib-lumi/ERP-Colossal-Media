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
    if (!$user->hasRole('head-salesperson')) {
        $orders->where(function ($query) use ($user) {
            $query->where('salesperson_id', $user->id)
                  ->orWhere('orderStatus', '!=', 'to_assign');
        });
    }

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
        ->addColumn('order_id', function ($order) {
            return $order->order_number;
        })
        ->addColumn('order_name', function ($order) {
            return $order->orderTitle;
        })
        ->addColumn('company_info', function ($order) {
            $lead = $order->lead;
            return '<div class="company-info-cell text-secondary">' .
                   '<div class="d-flex align-items-center mb-1"><i class="bx bxs-building me-2"></i>' . ($lead->company_name ?? 'N/A') . '</div>' .
                   '<div class="d-flex align-items-center mb-1"><i class="bx bxs-phone me-2"></i>' . ($lead->company_phone ?? 'N/A') . '</div>' .
                   '</div>';
        })
        ->addColumn('lead_details', function ($order) {
            $lead = $order->lead;
            return '<div class="lead-details-cell text-secondary">' .
                   '<div class="d-flex align-items-center mb-1"><i class="bx bxs-user me-2"></i>' . ($lead->name ?? 'N/A') . '</div>' .
                   '<div class="d-flex align-items-center mb-1"><i class="bx bxs-phone me-2"></i>' . ($lead->phone ?? 'N/A') . '</div>' .
                   '<div class="d-flex align-items-center mb-1"><i class="bx bx-envelope me-2"></i>' . ($lead->email ?? 'N/A') . '</div>' .
                   '</div>';
        })
        ->addColumn('status', function ($order) {
            $color = match($order->orderStatus) {
                'pending' => 'warning',
                'in_progress' => 'info',
                'completed' => 'success',
                default => 'secondary',
            };
            return '<span class="badge bg-' . $color . '">' . ucfirst($order->orderStatus) . '</span>';
        })
        ->addColumn('products', function ($order) {
            return '<button class="btn btn-sm btn-info view-products" data-id="' . $order->id . '">View Products</button>';
        })
        ->addColumn('actions', function ($order) {
            $editRoute = route('orders.edit', $order->id);
            $leadViewRoute = route('leads.show', $order->lead_id);
            return '<div class="actions-cell d-flex gap-2">' .
                   '<a href="' . $editRoute . '" class="btn" title="Edit"><i class="bx bxs-edit me-2" style="font-size: 1.5em;"></i></a>' .
                   '<a href="' . $leadViewRoute . '" class="btn" title="View Lead"><i class="bx bxs-show me-2" style="font-size: 1.5em;"></i></a>' .
                   '</div>';
        })
        ->rawColumns(['company_info', 'lead_details', 'status', 'products', 'actions'])
        ->toJson();
}

    public function getProducts($id)
    {
        $order = Order::findOrFail($id);
        if ($order->salesperson_id !== Auth::id()) {
            abort(403);
        }
        $products = $order->products->map(function($p) {
            return [
                'product_name' => $p->productName,
                'quantity' => $p->totalQuantity,
                'remark' => $p->productRemark,
                'material_info' => $p->materialRemark,
                'location' => $p->location,
                'date_time' => $p->date_time,
            ];
        });
        return response()->json($products);
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