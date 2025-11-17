<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductRemark;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Helpers\Helpers;

class OrderController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if (!($user->hasRole('salesperson') || $user->hasRole('head-salesperson'))) {
            abort(403, 'Unauthorized');
        }
        return view('sales.order-management');
    }

  public function getOrders(Request $request)
{
    $user = Auth::user();
    if (!($user->hasRole('salesperson') || $user->hasRole('head-salesperson'))) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

   $orders = Order::with('lead', 'salesperson', 'products', 'originalOrder')->where('status', 0)->orderBy('created_at', 'desc');

    if (!$user->hasRole('head-salesperson')) {
        // CHANGED: Filter by lead's salesperson_id instead of order's salesperson_id
        $orders->whereHas('lead', function($q) use($user) {
            $q->where('salesperson_id', $user->id);
        });
    }

    if ($request->filled('order_id')) {
        $orders->where('order_number', 'like', "%{$request->order_id}%");
    }

    if ($request->filled('q')) {
        $search = $request->q;
        $orders->where(function ($query) use ($search) {
            $query->where('orderTitle', 'like', "%{$search}%")
                  ->orWhere('order_number', 'like', "%{$search}%")
                  ->orWhereHas('lead', function ($q) use ($search) {
                      $q->where('company_name', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                  });
        });
    }

    if ($request->filled('salesperson')) { // For head: ID
        $orders->where('salesperson_id', $request->salesperson);
    }

    if ($request->filled('lead_name')) { // For regular: lead name search
        $orders->whereHas('lead', function ($q) use ($request) {
            $q->where('name', 'like', "%{$request->lead_name}%");
        });
    }

    if ($request->filled('from')) {
        $orders->whereDate('created_at', '>=', $request->from);
    }

    if ($request->filled('to')) {
        $orders->whereDate('created_at', '<=', $request->to);
    }

    if ($request->has('status') && $request->status) {
        $orders->where('orderStatus', $request->status);
    }

    $orders->whereNotExists(function ($query) {
        $query->select(DB::raw(1))
            ->from('orders as child')
            ->whereColumn('child.redo', 'orders.id');
    });

    return DataTables::of($orders)
        ->addColumn('order_id', function ($order) {
            if ($order->originalOrder) {
                return $order->originalOrder->order_number . 'R';
            }
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
            $assignedTo = $order->artist?->name ?? 'Unassigned';
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
            return '<span class="badge bg-label-' . $color . '" 
                     style="white-space: nowrap; min-width:120px; text-align:center;">'
                . ucwords(str_replace('_', ' ', $order->orderStatus)) .
                '</span>';
        })
        ->addColumn('view_url', function ($order) {
            return route('orders.show', $order->id);
        })
        ->addColumn('actions', function ($order) {
            $editRoute = route('orders.edit', $order->id);
            $leadViewRoute = route('orders.show', $order->id);
            $html = '<div class="actions-cell d-flex gap-2">';
            if ($order->orderStatus == 'to_assign') {
                $html .= '<a href="' . $editRoute . '" class="btn" title="Edit"><i class="bx bxs-edit me-2" style="font-size: 1.5em;"></i></a>';
            }
            $html .= '<a href="' . $leadViewRoute . '" class="btn" title="View Lead"><i class="bx bxs-show me-2" style="font-size: 1.5em;"></i></a>';
            $html .= '</div>';
            return $html;
        })
        ->rawColumns(['company_info', 'lead_details', 'status', 'products', 'actions'])
        ->toJson();
}

public function exportCsv(Request $request)
{
    $user = Auth::user();
    if (!$user->hasRole('salesperson') && !$user->hasRole('head-salesperson')) {
        abort(403, 'Unauthorized');
    }

    $orders = Order::with('lead', 'salesperson', 'originalOrder')->orderBy('created_at', 'desc');

    if (!$user->hasRole('head-salesperson')) {
        // CHANGED: Filter by lead's salesperson_id instead of order's salesperson_id
        $orders->whereHas('lead', function($q) use($user) {
            $q->where('salesperson_id', $user->id);
        });
    }

    if ($request->filled('order_id')) {
        $orders->where('order_number', 'like', "%{$request->order_id}%");
    }

    if ($request->filled('q')) {
        $search = $request->q;
        $orders->where(function ($query) use ($search) {
            $query->where('orderTitle', 'like', "%{$search}%")
                  ->orWhere('order_number', 'like', "%{$search}%")
                  ->orWhereHas('lead', function ($q) use ($search) {
                      $q->where('company_name', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                  });
        });
    }

    if ($request->filled('salesperson')) { // For head: ID
        $orders->where('salesperson_id', $request->salesperson);
    }

    if ($request->filled('lead_name')) { // For regular: lead name search
        $orders->whereHas('lead', function ($q) use ($request) {
            $q->where('name', 'like', "%{$request->lead_name}%");
        });
    }

    if ($request->filled('from')) {
        $orders->whereDate('created_at', '>=', $request->from);
    }

    if ($request->filled('to')) {
        $orders->whereDate('created_at', '<=', $request->to);
    }

    if ($request->filled('status')) {
        $orders->where('orderStatus', $request->status);
    }

    $orders->whereNotExists(function ($query) {
        $query->select(DB::raw(1))
            ->from('orders as child')
            ->whereColumn('child.redo', 'orders.id');
    });

    $orders = $orders->get();

    $filename = 'orders_' . now()->format('Y-m-d') . '.csv';
    $headers = [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => 'attachment; filename="' . $filename . '"',
    ];

    return response()->stream(function () use ($orders) {
        $handle = fopen('php://output', 'w');
        fputcsv($handle, ['Order ID', 'Order Name', 'Company Name', 'Lead Name', 'Lead Phone', 'Status', 'Salesperson', 'Created At', 'Deadline']);

        foreach ($orders as $order) {
            $orderId = $order->originalOrder ? $order->originalOrder->order_number . 'R' : ($order->order_number ?? $order->id);
            fputcsv($handle, [
                $orderId,
                $order->orderTitle,
                $order->lead->company_name ?? 'N/A',
                $order->lead->name ?? 'N/A',
                $order->lead->phone ?? 'N/A',
                $order->orderStatus,
                $order->salesperson->name ?? 'N/A',
                $order->created_at->format('Y-m-d H:i:s'),
                $order->deadline,
            ]);
        }

        fclose($handle);
    }, 200, $headers);
}

    public function getProducts($id)
    {
        $order = Order::findOrFail($id);
        // CHANGED: Check lead's salesperson_id instead of order's salesperson_id
        if ($order->lead->salesperson_id !== Auth::id() && !Auth::user()->hasRole('head-salesperson')) {
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
        if (!($user->hasRole('salesperson') || $user->hasRole('head-salesperson'))) {
            abort(403, 'Unauthorized');
        }
        $lead = null;
        if ($leadId) {
            $lead = Lead::findOrFail($leadId);
            // CHANGED: Check lead's salesperson_id for non-head
            if (!$user->hasRole('head-salesperson') && $lead->salesperson_id !== $user->id) {
                abort(403, 'Unauthorized for this lead');
            }
        } elseif (old('lead_id')) {
            $lead = Lead::find(old('lead_id'));
            if ($lead && !$user->hasRole('head-salesperson') && $lead->salesperson_id !== $user->id) {
                abort(403, 'Unauthorized for this lead');
            }
        }
        return view('sales.add-order', compact('lead'));
    }

    public function store(Request $request)
{
    $user = Auth::user();
    if (!($user->hasRole('salesperson') || $user->hasRole('head-salesperson'))) {
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
            'products.*.material_remark' => 'nullable|string',
            'products.*.remarks' => 'nullable|array',
            'products.*.remarks.*.operation' => 'required|in:printing,furnishing,installation,courier,self_pickup,artist',
            'products.*.remarks.*.remark' => 'nullable|string',
            'csv_file' => 'nullable|file|mimes:csv,txt',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:pdf,jpg,png,ai|max:2048',
        ]);

        $lead = Lead::findOrFail($request->lead_id);
        // CHANGED: Check lead's salesperson_id for non-head
        if (!$user->hasRole('head-salesperson') && $lead->salesperson_id !== $user->id) {
            return back()->with('error', 'Unauthorized for this lead');
        }

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
            'orderStatus' => 'to_assign',
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

            $headers = array_map('trim', $rows[0]);
            array_shift($rows); // Remove header row

            foreach ($rows as $row) {
                if (empty(array_filter($row))) continue; // Skip empty rows
                $rowData = array_map(function($value) { return trim($value, '"'); }, $row);
                $product = [
                    'product_name' => $rowData[array_search('Product Name', $headers)] ?? '',
                    'quantity' => $rowData[array_search('Quantity', $headers)] ?? '',
                    'material_remark' => $rowData[array_search('Material Info', $headers)] ?? '',
                    'remarks' => [],
                ];
                // Map remarks based on exact header names
                $remarkMappings = [
                    'Printing Remark' => 'printing',
                    'Furnishing Remark' => 'furnishing',
                    'Installation Remark' => 'installation',
                    'Courier Remark' => 'courier',
                    'Artist Remark' => 'artist',
                    'Self Pickup Remark' => 'self_pickup',
                ];
                foreach ($remarkMappings as $header => $operation) {
                    $index = array_search($header, $headers);
                    if ($index !== false && isset($rowData[$index]) && !empty(trim($rowData[$index]))) {
                        $product['remarks'][] = [
                            'operation' => $operation,
                            'remark' => $rowData[$index],
                        ];
                    }
                }
                $productsData[] = $product;
            }
        }

        foreach ($productsData as $productData) {
            $product = Product::create([
                'OrderID' => $order->id,
                'productName' => $productData['product_name'],
                'totalQuantity' => $productData['quantity'],
                'materialRemark' => $productData['material_remark'] ?? null,
            ]);

            foreach ($productData['remarks'] ?? [] as $remarkData) {
           ProductRemark::create([
        'ProductID' => $product->ProductID,
        'operation' => $remarkData['operation'],
        'remark' => $remarkData['remark'] ?? null,
        'user_id' => $user->id, 
    ]);
            }
        }


       
     $productCount = count($productsData);
        $actorName = $user->name;
        $actorRole = $user->display_role;
        $deadline = $order->deadline->format('Y-m-d');
        $message = "New order '{$order->orderTitle}' (No: {$order->id}) created by {$actorName} ({$actorRole}). "
                . "Needs assignment to artist. {$productCount} Product(s). Deadline: {$deadline}.";

        // Notify
        $headArtists = User::where('role', 'head-artist')->get();
        $url = route('artist.orders.assign.show', $order->id);
        foreach ($headArtists as $headArtist) {
            Helpers::notify($headArtist, $message, $url);
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
    header("Content-type: text/csv");
    header("Content-Disposition: attachment; filename=products_template.csv");

    $output = fopen("php://output", "w");

    // Add column headers
    $headers = ['Product_Name', 'Quantity', 'Material_Info', 'Printing_Remark', 'Furnishing_Remark', 'Installation_Remark', 'Courier_Remark', 'Self_Pickup_Remark'];
    fputcsv($output, $headers);

    // Generate example data with material info and up to 5 remarks, some empty
    $data = [
        ['Banner Print', 100, 'Vinyl 12oz', 'High resolution', '', '', 'Next day', ''],
        ['Flyer A5', 5000, 'Art Paper 128gsm', '', 'Glossy finish', '', '', ''],
        ['T-Shirt', 50, 'Cotton', 'Front print', '', 'Embroidery', '', ''],
        ['Poster A3', 200, 'Art Card 260gsm', '', '', '', 'Fragile', ''],
        ['Sticker Roll', 1000, 'PP Synthetic', '', '', '', '', 'Call ahead'],
        ['Name Card', 300, 'Art Card 310gsm', '', 'Double sided', '', '', ''],
        ['Booklet A4', 100, '80gsm Simili', 'Color print', '', '', 'Express', ''],
        ['Backdrop', 5, 'Tarpaulin', '', 'Sturdy frame', '', '', ''],
        ['Mug Print', 40, 'Ceramic', 'Heat resistant', '', '', '', ''],
        ['Cap Embroidery', 25, 'Polyester', '', 'Red thread', '', '', ''],
    ];

    foreach ($data as $row) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit;
}
    public function edit($id)
    {
        $order = Order::with(['lead', 'salesperson', 'products.remarks'])->findOrFail($id);
         // CHANGED: Check lead's salesperson_id instead of order's salesperson_id
         if ($order->lead->salesperson_id !== Auth::id() && !Auth::user()->hasRole('head-salesperson')) {
        abort(403, 'Unauthorized');
    }
        return view('sales.order-edit', compact('order'));
    }

public function update(Request $request, $id)
{
    $user = Auth::user();
    if (!($user->hasRole('salesperson') || $user->hasRole('head-salesperson'))) {
        return back()->with('error', 'Unauthorized');
    }

    $order = Order::findOrFail($id);
    $lead = $order->lead;
    if (!$user->hasRole('head-salesperson') && $lead->salesperson_id !== $user->id) {
        return back()->with('error', 'Unauthorized for this lead');
    }

    try {
        $request->validate([
            'orderTitle' => 'required|string|max:255',
            'deadline' => 'required|date|after_or_equal:today',
            'approval' => 'required|boolean',
            'orderDetail' => 'nullable|string',
            'products' => 'required|array|min:1',
            'products.*.id' => 'nullable|exists:products,ProductID',
            'products.*.product_name' => 'required|string|max:255',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.material_remark' => 'nullable|string',
            'products.*.remarks' => 'nullable|array',
            'products.*.remarks.*.operation' => 'required|in:printing,furnishing,installation,courier,self_pickup,artist',
            'products.*.remarks.*.remark' => 'nullable|string',
            'csv_file' => 'nullable|file|mimes:csv,txt',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:pdf,jpg,png,ai|max:2048',
        ]);

        $attachments = explode(',', $order->orderAttachment ?? '');
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('order_attachments', 'public');
                $attachments[] = $path;
            }
        }
        $attachmentString = implode(',', array_filter($attachments));

        $order->update([
            'orderTitle' => $request->orderTitle,
            'deadline' => $request->deadline,
            'approval' => $request->approval,
            'orderDetail' => $request->orderDetail,
            'orderAttachment' => $attachmentString,
        ]);

        $productsData = $request->products;

        if ($request->hasFile('csv_file')) {
            $path = $request->file('csv_file')->getPathname();
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
            $spreadsheet = $reader->load($path);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            $headers = array_map('trim', $rows[0]);
            array_shift($rows); // Remove header row

            foreach ($rows as $row) {
                if (empty(array_filter($row))) continue; // Skip empty rows
                $rowData = array_map(function($value) { return trim($value, '"'); }, $row);
                $product = [
                    'product_name' => $rowData[array_search('Product Name', $headers)] ?? '',
                    'quantity' => $rowData[array_search('Quantity', $headers)] ?? '',
                    'material_remark' => $rowData[array_search('Material Info', $headers)] ?? '',
                    'remarks' => [],
                ];
                // Map remarks based on exact header names
                $remarkMappings = [
                    'Printing Remark' => 'printing',
                    'Furnishing Remark' => 'furnishing',
                    'Installation Remark' => 'installation',
                    'Courier Remark' => 'courier',
                    'Artist Remark' => 'artist',
                    'Self Pickup Remark' => 'self_pickup',
                ];
                foreach ($remarkMappings as $header => $operation) {
                    $index = array_search($header, $headers);
                    if ($index !== false && isset($rowData[$index]) && !empty(trim($rowData[$index]))) {
                        $product['remarks'][] = [
                            'operation' => $operation,
                            'remark' => $rowData[$index],
                        ];
                    }
                }
                $productsData[] = $product;
            }
        }

        $currentProductIds = $order->products->pluck('ProductID')->toArray();
        $submittedProductIds = collect($productsData)->pluck('id')->filter()->unique()->values()->toArray();
        $deletedProductIds = array_diff($currentProductIds, $submittedProductIds);

        if (!empty($deletedProductIds)) {
            ProductRemark::whereIn('ProductID', $deletedProductIds)->delete();
            Product::destroy($deletedProductIds);
        }

        foreach ($productsData as $productData) {
            $product = isset($productData['id']) && $productData['id'] 
                ? Product::findOrFail($productData['id']) 
                : Product::create([
                    'OrderID' => $order->id,
                    'productName' => $productData['product_name'],
                    'totalQuantity' => $productData['quantity'],
                    'materialRemark' => $productData['material_remark'] ?? null,
                ]);

            if (isset($productData['id'])) {
                $product->update([
                    'productName' => $productData['product_name'],
                    'totalQuantity' => $productData['quantity'],
                    'materialRemark' => $productData['material_remark'] ?? null,
                ]);
            }

            ProductRemark::where('ProductID', $product->ProductID)->delete();

            foreach ($productData['remarks'] ?? [] as $remarkData) {
                ProductRemark::create([
                    'ProductID' => $product->ProductID,
                    'operation' => $remarkData['operation'],
                    'remark' => $remarkData['remark'] ?? null,
                    'user_id' => $user->id,
                ]);
            }
        }

        if ($request->has('from') && $request->input('from') === 'lead' && $request->has('lead_id')) {
            return redirect()->route('leads.show', $request->input('lead_id'))
                             ->withFragment('order-history')
                             ->with('success', 'Order updated successfully');
        }

        return redirect()->route('sales.orders')->with('success', 'Order updated successfully');
    } catch (\Illuminate\Validation\ValidationException $e) {
        return redirect()->back()->withErrors($e->validator)->withInput();
    } catch (\Exception $e) {
        dd($e->getMessage());
    }
}

public function show($id)
{
    $order = Order::with('lead.attachments', 'salesperson', 'products', 'artist')->findOrFail($id);
    // CHANGED: Check lead's salesperson_id instead of order's salesperson_id
    if ($order->lead->salesperson_id !== Auth::id() && !Auth::user()->hasRole('head-salesperson')) {
        abort(403, 'Unauthorized');
    }
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
        // CHANGED: Check lead's salesperson_id instead of order's salesperson_id
        if ($order->lead->salesperson_id !== Auth::id() && !Auth::user()->hasRole('head-salesperson')) {
            abort(403, 'Unauthorized');
        }
        $order->update(['orderStatus' => 'to_assign']);
        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        $order = Order::findOrFail($id);
        // CHANGED: Check lead's salesperson_id instead of order's salesperson_id
        if ($order->lead->salesperson_id !== Auth::id() && !Auth::user()->hasRole('head-salesperson')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $order->delete();
        return response()->json(['message' => 'Order deleted successfully']);
    }

    public function searchLeads(Request $request)
{
    $user = Auth::user();
    if (!($user->hasRole('salesperson') || $user->hasRole('head-salesperson'))) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    $query = $request->input('query');
    if (!$query || strlen($query) < 2) {
        return response()->json([]);
    }

    $leadsQuery = Lead::query();

    // Restrict if normal salesperson
    if ($user->hasRole('salesperson') && !$user->hasRole('head-salesperson')) {
        $leadsQuery->where('salesperson_id', $user->id);
    }

    $leads = $leadsQuery
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