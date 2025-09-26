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
use Illuminate\Support\Facades\Storage;

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
            $orders->where('salesperson_id', $user->id);
        } else {
            if ($request->has('salesperson') && $request->salesperson) {
                $orders->where('salesperson_id', $request->salesperson);
            }
        }

        if ($request->has('status') && $request->status) {
            $orders->where('orderStatus', $request->status);
        }

        if ($request->has('search') && $request->input('search')['value']) {
            $search = $request->input('search')['value'];
            $orders->where(function ($query) use ($search) {
                $query->where('orderTitle', 'like', "%{$search}%")
                      ->orWhere('order_number', 'like', "%{$search}%")
                      ->orWhereHas('lead', function ($q) use ($search) {
                          $q->where('company_name', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%");
                      });
            });
        }

        return DataTables::of($orders)
            ->addColumn('order_id', function ($order) {
                return $order->order_number ?? $order->id;
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
                $assignedTo = $order->salesperson?->name ?? 'Unassigned';
                return '<div class="lead-details-cell text-secondary">' .
                       '<div class="d-flex align-items-center mb-1"><i class="bx bxs-user me-2"></i>' . ($lead->name ?? 'N/A') . '</div>' .
                       '<div class="d-flex align-items-center mb-1"><i class="bx bxs-phone me-2"></i>' . ($lead->phone ?? 'N/A') . '</div>' .
                       '<div class="d-flex align-items-center mb-1"><i class="bx bx-envelope me-2"></i>' . ($lead->email ?? 'N/A') . '</div>' .
                       '<div class="d-flex align-items-center mb-1"><i class="bx bxs-id-card me-2"></i>Assigned To: ' . $assignedTo . '</div>' .
                   '</div>';
            })
            ->addColumn('status', function ($order) {
                $color = match($order->orderStatus) {
                    'to_assign'   => 'danger',
                    'assigned'    => 'primary',
                    'pending'     => 'warning',
                    'in_progress' => 'info',
                    'completed'   => 'success',
                    'rejected'    => 'danger',
                    default       => 'secondary',
                };
                return '<span class="btn btn-sm btn-label-' . $color . '" 
                         style="white-space: nowrap; min-width:120px; text-align:center;">'
                    . ucwords(str_replace('_', ' ', $order->orderStatus)) .
                    '</span>';
            })
            ->addColumn('products', function ($order) {
                return '<button class="btn view-products" data-id="' . $order->id . '"  style="white-space: nowrap; min-width:120px; text-align:center;">
                            <span class="icon-base bx bxs-show me-2"></span>
                            View Products
                        </button>';
            })
            ->addColumn('actions', function ($order) {
                $editRoute = route('orders.edit', $order->id);
                $leadViewRoute = route('orders.show', $order->id);
                $html = '<div class="actions-cell d-flex gap-2">' .
                        '<a href="' . $editRoute . '" class="btn" title="Edit"><i class="bx bxs-edit me-2" style="font-size: 1.5em;"></i></a>' .
                        '<a href="' . $leadViewRoute . '" class="btn" title="View Lead"><i class="bx bxs-show me-2" style="font-size: 1.5em;"></i></a>';
                $html .= '</div>';
                return $html;
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
            'products.*.material_info' => 'nullable|string',
            'products.*.remarks' => 'nullable|array',
            'products.*.remarks.*.type' => 'required|in:printing,furnishing,installation,courier,self_pickup',
            'products.*.remarks.*.remark' => 'nullable|string',
            'csv_file' => 'nullable|file|mimes:csv,txt',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:pdf,jpg,png,ai|max:2048',
        ]);

        $lead = Lead::findOrFail($request->lead_id);

        $attachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('order_attachments', 'public');
                $attachments[] = $path;
            }
        }
        $attachmentString = implode(',', $attachments);

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
            'orderAttachment' => $attachmentString,
        ]);

        $productsData = $request->products;

        if ($request->hasFile('csv_file')) {
            $path = $request->file('csv_file')->getPathname();
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
            $spreadsheet = $reader->load($path);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            foreach ($rows as $key => $row) {
                if ($key == 0) continue;
                $productsData[] = [
                    'product_name' => isset($row[0]) ? trim($row[0], '"') : '',
                    'quantity' => isset($row[1]) ? trim($row[1], '"') : '',
                    'material_info' => isset($row[2]) ? trim($row[2], '"') : '',
                    'remarks' => [],
                ];
            }
        }

        foreach ($productsData as $productData) {
            $product = Product::create([
                'OrderID' => $order->id,
                'productName' => $productData['product_name'],
                'totalQuantity' => $productData['quantity'],
                'materialRemark' => $productData['material_info'] ?? null,
            ]);

            foreach ($productData['remarks'] ?? [] as $remarkData) {
                ProductRemark::create([
                    'ProductID' => $product->ProductID,
                    'operation' => $remarkData['type'],
                    'remark' => $remarkData['remark'] ?? null,
                ]);
            }
        }

        return redirect()->route('sales.orders')->with('success', 'Order created successfully');
    } catch (\Illuminate\Validation\ValidationException $e) {
        return redirect()->back()->withErrors($e->validator)->withInput();
    } catch (\Exception $e) {
        dd($e->getMessage());
    }
}

  public function csvTemplate()
{
    // CSV headers
    header("Content-type: text/csv");
    header("Content-Disposition: attachment; filename=products_template.csv");

    $output = fopen("php://output", "w");

    // Add column headers
    fputcsv($output, ['Product Name', 'Quantity', 'Remark', 'Material Info', 'Location', 'Date']);

    // Generate example data
    $data = [
        // 3 rows → 3 days from now
        ['Banner Print', 100, 'Urgent order', 'Vinyl 12oz', 'Kuala Lumpur', now()->addDays(3)->format('Y-m-d')],
        ['Flyer A5', 5000, 'Double sided', 'Art Paper 128gsm', 'Penang', now()->addDays(3)->format('Y-m-d')],
        ['T-Shirt', 50, 'Black color only', 'Cotton', 'Johor Bahru', now()->addDays(3)->format('Y-m-d')],

        // 2 rows → 5 days from now
        ['Poster A3', 200, 'Gloss finish', 'Art Card 260gsm', 'Melaka', now()->addDays(5)->format('Y-m-d')],
        ['Sticker Roll', 1000, 'Waterproof', 'PP Synthetic', 'Ipoh', now()->addDays(5)->format('Y-m-d')],

        // 5 rows → 2 days from now
        ['Name Card', 300, 'Matte Lamination', 'Art Card 310gsm', 'Shah Alam', now()->addDays(2)->format('Y-m-d')],
        ['Booklet A4', 100, 'Saddle stitch', '80gsm Simili', 'Kuantan', now()->addDays(2)->format('Y-m-d')],
        ['Backdrop', 5, 'Event hall size', 'Tarpaulin', 'Kota Kinabalu', now()->addDays(2)->format('Y-m-d')],
        ['Mug Print', 40, 'Full wrap print', 'Ceramic', 'Kuching', now()->addDays(2)->format('Y-m-d')],
        ['Cap Embroidery', 25, 'Logo front only', 'Polyester', 'Seremban', now()->addDays(2)->format('Y-m-d')],
    ];

    // Write rows
    foreach ($data as $row) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit;
}


    public function edit($id)
    {
        $order = Order::with('products')->findOrFail($id);
        if ($order->salesperson_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }
        return view('sales.order-edit', compact('order'));
    }

    public function update(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        if ($order->salesperson_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'products' => 'required|array',
            'products.*.id' => 'required|exists:products,ProductID',
            'products.*.location' => 'nullable|string|max:255',
        ]);

        foreach ($request->products as $productData) {
            $product = Product::findOrFail($productData['id']);
            if ($product->OrderID !== $order->id) abort(403);
            $product->update(['location' => $productData['location']]);
        }

        return redirect()->route('sales.orders')->with('success', 'Order updated successfully');
    }

public function show($id)
{
    $order = Order::with('lead.attachments', 'salesperson', 'products', 'artist')->findOrFail($id);
    $attachments = $order->getAttachmentPathsAttribute()->map(function ($path) {
        return ['url' => Storage::url($path), 'name' => basename($path), 'size' => Storage::size($path)];
    });
    $leadAttachments = $order->lead ? $order->lead->attachments->map(function ($attachment) {
        return [
            'url' => asset('storage/' . $attachment->file_location),
            'name' => basename($attachment->file_location),
            'size' => $attachment->file_size
        ];
    }) : collect();
    return view('sales.order-view', compact('order', 'attachments', 'leadAttachments'));
}

    public function submit($id)
    {
        $order = Order::findOrFail($id);
        if ($order->salesperson_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }
        $order->update(['orderStatus' => 'to_assign']);
        return response()->json(['success' => true]);
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
            'phone' => $lead->phone,
            'company_phone' => $lead->company_phone ?? '',
        ]);
    }
}