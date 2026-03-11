<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductRemark;
use App\Models\Lead;
use App\Models\User;
use App\Models\OrderAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Helpers\Helpers;
use Illuminate\Support\Str;
use App\Models\OrderRecord;

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

        if ($request->filled('status')) {

            if ($request->status === 'draft') {

                $orders->where('orderStatus', 'in_progress')
                    ->where('draft', 1)
                    ->whereNull('artist_id');

            } elseif ($request->status === 'in_progress') {

                // ✅ In Progress should EXCLUDE draft rows
                $orders->where('orderStatus', 'in_progress')
                    ->where(function ($q) {
                        $q->where('draft', 0)
                            ->orWhereNotNull('artist_id');
                    });

            } else {

                $orders->where('orderStatus', $request->status);

            }
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

                // ✅ Draft display override
                if (
                    $order->orderStatus === 'in_progress'
                    && (int) $order->draft === 1
                    && is_null($order->artist_id)
                ) {
                    return '<span class="badge bg-label-secondary"
                            style="white-space: nowrap; min-width:120px; text-align:center;">
                            Draft
                        </span>';
                }

                // Normal status mapping
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
                $editRoute     = route('orders.edit', $order->id);
                $leadViewRoute = route('orders.show', $order->id);

                $html = '<div class="actions-cell d-flex gap-2">';

                // ✅ NEW: allow edit only when:
                // orderStatus = in_progress, draft = 1, artist_id is null
                $canEdit =
                $order->orderStatus === 'to_assign'
                || (
                    $order->orderStatus === 'in_progress'
                    && (int) $order->draft === 1
                    && is_null($order->artist_id)
                );

                if ($canEdit) {
                    $html .= '<a href="' . $editRoute . '" class="btn" title="Edit">
                                <i class="bx bxs-edit me-2" style="font-size: 1.5em;"></i>
                            </a>';
                }

                $html .= '<a href="' . $leadViewRoute . '" class="btn" title="View Lead">
                            <i class="bx bxs-show me-2" style="font-size: 1.5em;"></i>
                        </a>';

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

        $isDraft = $request->input('save_type') === 'draft';

        try {
            // ✅ Validation rules
            if ($isDraft) {
                // Draft: allow empty form, only validate if provided
                $rules = [
                    'lead_id'     => 'nullable|exists:leads,id',
                    'orderTitle'  => 'nullable|string|max:255',
                    'deadline'    => 'nullable|date', // draft can be empty; if filled, must be date
                    'approval'    => 'nullable|boolean',
                    'orderDetail' => 'nullable|string',
                    'csv_file'    => 'nullable|file|mimes:csv,txt',

                    'products' => 'nullable|array',
                    'products.*.product_name' => 'nullable|string|max:255',
                    'products.*.quantity' => 'nullable|integer|min:1',
                    'products.*.material_remark' => 'nullable|string',
                    'products.*.remarks' => 'nullable|array',
                    'products.*.remarks.*.operation' => 'nullable|in:printing,furnishing,installation,courier,self_pickup,artist',
                    'products.*.remarks.*.remark' => 'nullable|string',

                    'attachments'   => 'nullable|array',
                    'attachments.*' => 'file|mimes:pdf,jpg,jpeg,png,ai,psd,eps,svg,tiff,indd,xls,xlsx,csv|max:51200',
                ];
            } else {
                // Final: strict
                $rules = [
                    'lead_id'     => 'required|exists:leads,id',
                    'orderTitle'  => 'required|string|max:255',
                    'deadline'    => 'required|date|after_or_equal:today',
                    'approval'    => 'required|boolean',
                    'orderDetail' => 'nullable|string',
                    'csv_file'    => 'nullable|file|mimes:csv,txt',

                    'products' => 'required|array|min:1',
                    'products.*.product_name' => 'required|string|max:255',
                    'products.*.quantity' => 'required|integer|min:1',
                    'products.*.material_remark' => 'required|string',
                    'products.*.remarks' => 'nullable|array',
                    'products.*.remarks.*.operation' => 'required|in:printing,furnishing,installation,courier,self_pickup,artist',
                    'products.*.remarks.*.remark' => 'nullable|string',

                    'attachments'   => 'required|array|min:1',
                    'attachments.*' => 'file|mimes:pdf,jpg,jpeg,png,ai,psd,eps,svg,tiff,indd|max:51200',
                ];
            }

            $request->validate($rules);

            // ✅ Load lead only if provided
            $lead = null;
            if ($request->filled('lead_id')) {
                $lead = Lead::find($request->lead_id);

                // ownership check only if lead exists
                if ($lead && !$user->hasRole('head-salesperson') && (int)$lead->salesperson_id !== (int)$user->id) {
                    return back()->with('error', 'Unauthorized for this lead');
                }
            }

            // ✅ Filter empty product rows from UI (draft/final both)
            $productsData = collect($request->products ?? [])
                ->filter(function ($p) {
                    $name = trim((string)($p['product_name'] ?? ''));
                    $qty  = trim((string)($p['quantity'] ?? ''));
                    return $name !== '' || $qty !== '';
                })
                ->values()
                ->toArray();

            // ✅ Final save must have at least 1 product (either UI or CSV)
            if (!$isDraft && count($productsData) === 0 && !$request->hasFile('csv_file')) {
                return back()
                    ->withErrors(['products' => 'At least one product is required'])
                    ->withInput();
            }

            DB::transaction(function () use ($request, $user, $lead, $isDraft, &$productsData) {

                // ✅ Create order
                $order = Order::create([
                    'lead_id'        => $lead?->id, // nullable for draft
                    'salesperson_id' => $user->id,

                    'orderTitle'     => $request->input('orderTitle'), // nullable for draft
                    'deadline'       => $request->input('deadline'),   // nullable for draft
                    'approval'       => $request->has('approval') ? (int)$request->approval : 0,
                    'orderDetail'    => $request->orderDetail,

                    'orderStatus'    => $isDraft ? 'in_progress' : 'to_assign',
                    'draft'          => $isDraft ? 1 : 0,

                    // snapshot lead fields (nullable)
                    'leadName'       => $lead?->name,
                    'leadPhone'      => $lead?->phone,
                    'companyName'    => $lead?->company_name,
                    'leadEmail'      => $lead?->email,

                    'orderDate'      => now(),
                ]);

                // ✅ Record first created time ONLY for final save (not draft)
                if (!$isDraft) {
                    OrderRecord::firstOrCreate(['order_id' => $order->id]);

                    OrderRecord::where('order_id', $order->id)
                        ->whereNull('first_created_at')
                        ->update(['first_created_at' => $order->created_at ?? now()]);
                }

                // ✅ Attachments (optional for draft, required already enforced for final)
                if ($request->hasFile('attachments')) {
                    foreach ($request->file('attachments') as $file) {
                        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                        $extension    = $file->getClientOriginalExtension();
                        $timestamp    = now()->format('Ymd_His');
                        $newName      = $originalName . '_' . $timestamp . '.' . $extension;

                        $path = $file->storeAs('orders/' . $order->id, $newName, 'public');

                        OrderAttachment::create([
                            'order_id'      => $order->id,
                            'user_id'       => $user->id,
                            'file_path'     => $path,
                            'original_name' => $file->getClientOriginalName(),
                            'mime_type'     => $file->getMimeType(),
                            'size'          => $file->getSize(),
                        ]);
                    }
                }

                // ✅ Merge CSV products into $productsData (draft/final both)
                if ($request->hasFile('csv_file')) {
                    $path = $request->file('csv_file')->getPathname();
                    $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
                    $spreadsheet = $reader->load($path);
                    $sheet = $spreadsheet->getActiveSheet();
                    $rows = $sheet->toArray();

                    if (!empty($rows)) {
                        $headers = array_map('trim', $rows[0]);
                        array_shift($rows);

                        $remarkMappings = [
                            'Printing Remark'     => 'printing',
                            'Furnishing Remark'   => 'furnishing',
                            'Installation Remark' => 'installation',
                            'Courier Remark'      => 'courier',
                            'Artist Remark'       => 'artist',
                            'Self Pickup Remark'  => 'self_pickup',
                        ];

                        foreach ($rows as $row) {
                            if (empty(array_filter($row))) continue;

                            $rowData = array_map(function ($value) {
                                return trim((string)$value, '"');
                            }, $row);

                            $productNameIndex = array_search('Product Name', $headers);
                            $qtyIndex         = array_search('Quantity', $headers);
                            $matIndex         = array_search('Material Info', $headers);

                            $product = [
                                'product_name'    => $productNameIndex !== false ? ($rowData[$productNameIndex] ?? '') : '',
                                'quantity'        => $qtyIndex !== false ? ($rowData[$qtyIndex] ?? '') : '',
                                'material_remark' => $matIndex !== false ? ($rowData[$matIndex] ?? '') : '',
                                'remarks'         => [],
                            ];

                            foreach ($remarkMappings as $header => $operation) {
                                $index = array_search($header, $headers);
                                if ($index !== false && isset($rowData[$index]) && trim($rowData[$index]) !== '') {
                                    $product['remarks'][] = [
                                        'operation' => $operation,
                                        'remark'    => $rowData[$index],
                                    ];
                                }
                            }

                            $productsData[] = $product;
                        }
                    }
                }

                // ✅ Create products + remarks
                foreach ($productsData as $productData) {
                    $name = trim((string)($productData['product_name'] ?? ''));
                    $qty  = (int)($productData['quantity'] ?? 0);

                    // ignore still-empty rows safely (draft allowed)
                    if ($name === '' || $qty <= 0) {
                        continue;
                    }

                    $product = Product::create([
                        'OrderID'        => $order->id,
                        'productName'    => $name,
                        'totalQuantity'  => $qty,
                        'materialRemark' => $productData['material_remark'] ?? null,
                    ]);

                    foreach (($productData['remarks'] ?? []) as $remarkData) {
                        // skip invalid remark rows safely
                        if (empty($remarkData['operation'])) continue;

                        ProductRemark::create([
                            'ProductID' => $product->ProductID,
                            'operation' => $remarkData['operation'],
                            'remark'    => $remarkData['remark'] ?? null,
                            'user_id'   => $user->id,
                        ]);
                    }

                    // ✅ Create delivery breakdowns (optional)
                    foreach (($productData['deliveries'] ?? []) as $deliveryData) {
                        $method   = trim((string)($deliveryData['method'] ?? ''));
                        $location = trim((string)($deliveryData['location'] ?? ''));
                        $dtRaw    = trim((string)($deliveryData['datetime'] ?? ''));

                        if ($method === '' && $location === '' && $dtRaw === '') {
                            continue;
                        }

                        $date = null;
                        $time = null;

                        if ($dtRaw !== '') {
                            try {
                                $dt = \Illuminate\Support\Carbon::parse($dtRaw);
                                $date = $dt->toDateString();
                                $time = $dt->format('H:i:s');
                            } catch (\Throwable $e) {
                                // ignore parse error, keep nulls
                                $date = null;
                                $time = null;
                            }
                        }

                        \DB::table('delivery_breakdowns')->insert([
                            'ProductID'            => $product->ProductID,
                            'method'               => $method !== '' ? $method : null,
                            'location'             => $location !== '' ? $location : null,
                            'date'                 => $date,
                            'time'                 => $time,
                            'deliver_install_type' => null,
                            'outsource_cost'       => null,
                            'quantity'             => null,
                            'created_at'           => now(),
                            'updated_at'           => now(),
                        ]);
                    }

                }

                // ✅ Notify head-artist ONLY for final save
                if (!$isDraft) {
                    $productCount = count($productsData);
                    $actorName = $user->name;
                    $actorRole = $user->display_role;
                    $deadline  = optional($order->deadline)->format('Y-m-d');

                    $message = "New order '{$order->orderTitle}' (No: {$order->id}) created by {$actorName} ({$actorRole}). "
                        . "Needs assignment to artist. {$productCount} Product(s). Deadline: {$deadline}.";

                    $headArtists = User::where('role', 'head-artist')->get();
                    $url = route('artist.orders.assign.show', $order->id);

                    foreach ($headArtists as $headArtist) {
                        Helpers::notify($headArtist, $message, $url);
                    }
                }
            });

            return redirect()->route('sales.orders')
                ->with('success', $isDraft ? 'Draft saved successfully' : 'Order created successfully');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->validator)->withInput();

        } catch (\Exception $e) {
            Log::error('Order store failed', [
                'user_id'  => $user->id ?? null,
                'is_draft' => $isDraft,
                'error'    => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'Something went wrong. Please try again.')
                ->withInput();
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
        $user = Auth::user();

        if (!($user->hasRole('salesperson') || $user->hasRole('head-salesperson'))) {
            abort(403, 'Unauthorized');
        }

        $order = Order::with(['lead', 'salesperson', 'products.remarks'])->findOrFail($id);

        // ✅ Attach deliveries from delivery_breakdowns for each product
        $productIds = $order->products->pluck('ProductID')->toArray();

        $deliveryRowsByProduct = DB::table('delivery_breakdowns')
        ->whereIn('ProductID', $productIds)
        ->orderBy('BreakdownID', 'asc')
        ->get()
        ->groupBy('ProductID');


        foreach ($order->products as $p) {
        $rows = $deliveryRowsByProduct[$p->ProductID] ?? collect();


        $p->deliveries = $rows->map(function ($r) {
        $datetime = '';
        if (!empty($r->date) && !empty($r->time)) {
        // datetime-local needs: YYYY-MM-DDTHH:MM
        $datetime = Carbon::parse($r->date . ' ' . $r->time)->format('Y-m-d\TH:i');
        }


        return [
        'method' => $r->method ?? '',
        'location' => $r->location ?? '',
        'datetime' => $datetime,
        ];
        })->values();
        }

        // Ownership (salesperson can only see their own lead's orders; head can see all)
        if (!$user->hasRole('head-salesperson')) {
            if (!$order->lead || (int)$order->lead->salesperson_id !== (int)$user->id) {
                abort(403, 'Unauthorized');
            }
        }

        // ✅ New rule: only editable when in_progress + draft=1 + artist_id is null
        $canEdit =
            $order->orderStatus === 'to_assign'
            || (
                $order->orderStatus === 'in_progress'
                && (int) $order->draft === 1
                && is_null($order->artist_id)
            );

        abort_unless($canEdit, 403, 'This order cannot be edited.');

        return view('sales.order-edit', compact('order'));
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        if (!($user->hasRole('salesperson') || $user->hasRole('head-salesperson'))) {
            return back()->with('error', 'Unauthorized');
        }

        $order = Order::with(['lead', 'products', 'attachments'])->findOrFail($id);

        $wasDraft = (int)($order->draft ?? 0) === 1;

        $lead = $order->lead;

        if (!$user->hasRole('head-salesperson')) {
            if ($lead && (int)$lead->salesperson_id !== (int)$user->id) {
                return back()->with('error', 'Unauthorized for this lead');
            }
        }

        if (!$user->hasRole('head-salesperson') && (int)$lead->salesperson_id !== (int)$user->id) {
            return back()->with('error', 'Unauthorized for this lead');
        }

        // ✅ New rule: only editable when in_progress + draft=1 + artist_id is null
        $canEdit =
            $order->orderStatus === 'to_assign'
            || (
                $order->orderStatus === 'in_progress'
                && (int) $order->draft === 1
                && is_null($order->artist_id)
            );

        if (!$canEdit) {
            return back()->with('error', 'This order can no longer be edited.');
        }

        try {
            $isDraft = $request->input('save_type') === 'draft';

            $rules = [
            'lead_id' => $isDraft ? 'nullable|exists:leads,id' : 'required|exists:leads,id',
            'orderTitle' => $isDraft ? 'nullable|string|max:255' : 'required|string|max:255',
            'deadline' => $isDraft ? 'nullable|date' : 'required|date|after_or_equal:today',
            'approval' => $isDraft ? 'nullable|boolean' : 'required|boolean',
            'orderDetail'=> 'nullable|string',
            'products' => $isDraft ? 'nullable|array' : 'required|array|min:1',
            'products.*.id' => 'nullable|exists:products,ProductID',
            'products.*.product_name' => $isDraft ? 'nullable|string|max:255' : 'required|string|max:255',
            'products.*.quantity' => $isDraft ? 'nullable|integer|min:1' : 'required|integer|min:1',
            'products.*.material_remark' => 'nullable|string',
            'products.*.remarks' => 'nullable|array',
            'products.*.remarks.*.operation' => 'required_with:products.*.remarks|in:printing,furnishing,installation,courier,self_pickup,artist',
            'products.*.remarks.*.remark' => 'nullable|string',
            'csv_file' => 'nullable|file|mimes:csv,txt',
            'products.*.deliveries' => 'nullable|array',
            'products.*.deliveries.*.method' => 'nullable|in:courier,self_pickup,delivery,installation',
            'products.*.deliveries.*.location' => 'nullable|string|max:255',
            'products.*.deliveries.*.datetime' => 'nullable|date',

            // ✅ attachments required only for final update (and only if no existing)
            'attachments' => [
            $isDraft ? 'nullable' : 'array',
            function ($attribute, $value, $fail) use ($order, $isDraft) {
            if (!$isDraft && $order->attachments->isEmpty() && empty($value)) {
            $fail('At least one attachment is required.');
            }
            },
            ],
            'attachments.*' => 'file|mimes:pdf,jpg,jpeg,png,ai,psd,eps,svg,tiff,indd|max:51200',
            ];


            $request->validate($rules);

            $order->orderTitle = $request->filled('orderTitle') ? $request->orderTitle : $order->orderTitle;
            $order->deadline = $request->filled('deadline') ? $request->deadline : $order->deadline;
            $order->approval = $request->has('approval') ? $request->approval : $order->approval;
            $order->orderDetail = $request->orderDetail;

            $order->save();

            // If user selected a new lead in draft edit page, update lead_id + snapshot fields
            if ($request->filled('lead_id') && (int)$request->lead_id !== (int)$order->lead_id) {
                $newLead = Lead::findOrFail($request->lead_id);

                $order->lead_id = $newLead->id;
                $order->companyName = $newLead->company_name;
                $order->leadName = $newLead->name;
                $order->leadPhone = $newLead->phone;
                $order->leadEmail = $newLead->email;

                $order->save(); 
            }

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $extension = $file->getClientOriginalExtension();
                    $timestamp = now()->format('Ymd_His');
                    $newName = $originalName . '_' . $timestamp . '.' . $extension;
                    $path = $file->storeAs('orders/' . $order->id, $newName, 'public');

                    OrderAttachment::create([
                        'order_id'       => $order->id,
                        'user_id'        => $user->id,
                        'file_path'      => $path,
                        'original_name'  => $file->getClientOriginalName(),
                        'mime_type'      => $file->getMimeType(),
                        'size'           => $file->getSize(),
                    ]);
                }
            }

            $productsData = $request->input('products', []);

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
                DB::table('delivery_breakdowns')->whereIn('ProductID', $deletedProductIds)->delete();
                Product::destroy($deletedProductIds);
            }

            foreach ($productsData as $productData) {
                $product = (isset($productData['id']) && $productData['id'])
                    ? Product::where('OrderID', $order->id)->where('ProductID', $productData['id'])->firstOrFail()
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

                // ✅ Sync delivery breakdowns
                DB::table('delivery_breakdowns')
                    ->where('ProductID', $product->ProductID)
                    ->delete();


                    foreach (($productData['deliveries'] ?? []) as $delivery) {
                    $method = $delivery['method'] ?? null;
                    $location = $delivery['location'] ?? null;
                    $dtInput = $delivery['datetime'] ?? null;


                    // skip totally empty row
                    if (empty($method) && empty($location) && empty($dtInput)) {
                    continue;
                    }


                    $date = null;
                    $time = null;


                    if (!empty($dtInput)) {
                    try {
                    $dt = Carbon::parse($dtInput);
                    $date = $dt->toDateString(); // YYYY-MM-DD
                    $time = $dt->format('H:i:s'); // HH:MM:SS
                    } catch (\Exception $e) {
                    $date = null;
                    $time = null;
                    }
                    }


                    DB::table('delivery_breakdowns')->insert([
                    'ProductID' => $product->ProductID,
                    'method' => $method,
                    'location' => $location,
                    'date' => $date,
                    'time' => $time,
                    'created_at' => now(),
                    'updated_at' => now(),
                    ]);
                }
            }

            if ($request->has('from') && $request->input('from') === 'lead' && $request->has('lead_id')) {
                return redirect()->route('leads.show', $request->input('lead_id'))
                                ->withFragment('order-history')
                                ->with('success', 'Order updated successfully');
            }

            // After editing a draft in_progress order (no artist yet), mark it as non-draft
            if (
                !$isDraft &&
                $order->orderStatus === 'in_progress'
                && (int)$order->draft === 1
                && is_null($order->artist_id)
                ) {
                $order->draft = 0;
                $order->orderStatus = "to_assign";
                $order->save();

                // ✅ record create time ONLY when converting from draft → final
                if ($wasDraft) {
                    OrderRecord::firstOrCreate(['order_id' => $order->id]);

                    OrderRecord::where('order_id', $order->id)
                    ->whereNull('first_created_at')
                    ->update(['first_created_at' => $order->created_at ?? now()]);
                }
            }

            if ($isDraft) {
            return redirect()->route('sales.orders')->with('success', 'Draft saved successfully');
            }

            return redirect()->route('sales.orders')->with('success', 'Order updated successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->validator)->withInput();
        } catch (\Exception $e) {
            \Log::error('Order update failed', [
                'order_id' => $id,
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Something went wrong. Please try again.')->withInput();
        }
    }

    public function deleteAttachment(Order $order, OrderAttachment $attachment)
    {
        // Authorization
        if (Auth::id() !== $attachment->user_id && !Auth::user()->hasRole('head-salesperson')) {
            // For Ajax return JSON error, otherwise normal redirect
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            return back()->with('error', 'Unauthorized');
        }

        Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();

        // Return JSON for Ajax, normal redirect for browser
        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Attachment deleted']);
        }

        return back()->with('success', 'Attachment deleted successfully');
    }

    public function show($id)
    {
        $order = Order::with(['lead', 'salesperson', 'products', 'artist', 'attachments.uploader'])->findOrFail($id);

        if ($order->lead->salesperson_id !== Auth::id() && !Auth::user()->hasRole('head-salesperson')) {
            abort(403);
        }

            $toPublicUrl = function (string $p): string {
                $p = ltrim($p, '/');

                if (Str::startsWith($p, 'storage/')) {
                    return url($p);
                }

                return Storage::disk('public')->url($p);
            };

        $artistRoles = ['artist', 'head-artist', 'data-entry'];

            // 1) Try order_attachments table first
            $rows = OrderAttachment::with('uploader:id,name,role')
                ->where('order_id', $order->id)
                ->orderBy('id')
                ->get();

            $mapRow = function (OrderAttachment $att) use ($toPublicUrl) {
                $p = ltrim((string) $att->file_path, '/');
                $web = \Illuminate\Support\Str::startsWith($p, 'storage/') ? $p : 'storage/' . $p;

                return [
                    'id'            => $att->id,
                    'name'          => $att->original_name ?: basename($p),
                    'url'           => $toPublicUrl($web),
                    'size'          => (int) $att->size,
                    'ext'           => pathinfo($p, PATHINFO_EXTENSION),
                    'uploaded_by'   => optional($att->uploader)->name,
                    'uploader_role' => optional($att->uploader)->role,
                    'uploaded_at'   => optional($att->created_at)->format('d M Y'),
                ];
            };

            if ($rows->isNotEmpty()) {
                // split by uploader role
                $headerAttachments = $rows
                    ->filter(function ($att) use ($artistRoles) {
                        return !in_array(optional($att->uploader)->role, $artistRoles, true);
                    })
                    ->map($mapRow)
                    ->values();

                $attachments = $rows
                    ->filter(function ($att) use ($artistRoles) {
                        return in_array(optional($att->uploader)->role, $artistRoles, true);
                    })
                    ->map($mapRow)
                    ->values();
            }

        return view('sales.order-view', compact('order', 'attachments','headerAttachments'));
    }

    public function showCy(Order $order)
    {
        // keep what you already load here (products, items, deliveryBreakdowns, etc.)
        $order->loadMissing([
            'salesperson:id,name',
            'artist:id,name',
            'products'        => fn ($q) => $q->orderBy('ProductID'),
            'products.items'  => fn ($q) => $q->orderBy('ItemID'),
            'products.items.spec',
            // 'leadAttachments',
            'deliveryBreakdowns' => fn ($q) => $q->orderBy('BreakdownID'),
        ]);

        // helper to turn a storage path into a public URL
        $toPublicUrl = function (string $p): string {
            $p = ltrim($p, '/');

            if (Str::startsWith($p, 'storage/')) {
                return url($p);
            }

            return Storage::disk('public')->url($p);
        };

        // roles considered "artist-side"
        $artistRoles = ['artist', 'head-artist'];

        // 1) Try order_attachments table first
        $rows = OrderAttachment::with('uploader:id,name,role')
            ->where('order_id', $order->id)
            ->orderBy('id')
            ->get();

        $mapRow = function (OrderAttachment $att) use ($toPublicUrl) {
            $p = ltrim((string) $att->file_path, '/');
            $web = \Illuminate\Support\Str::startsWith($p, 'storage/') ? $p : 'storage/' . $p;

            return [
                'id'            => $att->id,
                'name'          => $att->original_name ?: basename($p),
                'url'           => $toPublicUrl($web),
                'size'          => (int) $att->size,
                'ext'           => pathinfo($p, PATHINFO_EXTENSION),
                'uploaded_by'   => optional($att->uploader)->name,
                'uploader_role' => optional($att->uploader)->role,
                'uploaded_at'   => optional($att->created_at)->format('d M Y'),
            ];
        };

        if ($rows->isNotEmpty()) {
            // split by uploader role
            $headerAttachments = $rows
                ->filter(function ($att) use ($artistRoles) {
                    return !in_array(optional($att->uploader)->role, $artistRoles, true);
                })
                ->map($mapRow)
                ->values();

            $attachments = $rows
                ->filter(function ($att) use ($artistRoles) {
                    return in_array(optional($att->uploader)->role, $artistRoles, true);
                })
                ->map($mapRow)
                ->values();
        }

        return view('artist.orders.show', compact(
            'order',
            'attachments',
            'headerAttachments'
        ));
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