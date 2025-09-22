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
use Illuminate\Support\Facades\DB;

class ArtistOrderController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if (!$user->hasRole('artist')) {
            abort(403, 'Unauthorized');
        }
        return view('artist.orders.order-management');
    }

    public function getOrders(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasRole('artist')) {
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
                $color = match ($order->orderStatus) {
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
                $editRoute = route('artist.orders.edit', $order->id);
                $leadViewRoute = route('artist.orders.show', $order->id);
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
        $products = $order->products->map(function ($p) {
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

    public function create(?int $lead_id = null)
    {
        $lead = $lead_id ? \App\Models\Lead::find($lead_id) : null;

        // Try the new name first, fall back to the old file name if present
        return view()->first([
            'artist.orders.create',     // resources/views/artist/orders/create.blade.php
            'artist.orders.add-order',  // fallback if you still had the old file name around
        ], compact('lead'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->hasRole('artist')) {
            return back()->with('error', 'Unauthorized');
        }

        try {
            $request->validate([
                'lead_id'                  => 'nullable|exists:leads,id', // artist can create without a lead
                'orderTitle'               => 'required|string|max:255',
                'deadline'                 => 'nullable|date|after_or_equal:today',
                'approval'                 => 'required|boolean',
                'orderDetail'              => 'nullable|string',

                'products'                         => ['required','array','min:1'],
                'products.*.product_name'          => ['required','string','max:255'],
                'products.*.quantity'              => ['required','integer','min:1'],
                'products.*.material_info'         => ['nullable','string'],
                'products.*.remarks'               => ['nullable','array'],
                'products.*.remarks.*.operation'   => ['required_with:products.*.remarks.*.remark','in:printing,furnishing,installation,courier,self_pickup'],
                'products.*.remarks.*.remark'      => ['required_with:products.*.remarks.*.operation','string'],

                'csv_file'                 => 'nullable|file|mimes:csv,txt',
                'attachments'              => 'nullable|array',
                'attachments.*'            => 'file|mimes:pdf,jpg,png,ai|max:2048',
            ]);

            $lead = $request->filled('lead_id') ? Lead::find($request->lead_id) : null;

            // Save attachments (optional)
            $attachments = [];
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $attachments[] = $file->store('order_attachments', 'public');
                }
            }
            $attachmentString = implode(',', $attachments) ?: null;

            // Create Order (as Artist)
            $order          = new Order();
            $order->order_number   = $this->makeOrderNumber();           // keep/introduce order_number
            $order->artist_id      = $user->id;                          // creator = artist
            $order->salesperson_id = $lead->salesperson_id ?? null;      // if lead exists

            $order->lead_id     = $lead->id           ?? null;
            $order->leadName    = $lead->name         ?? null;
            $order->leadPhone   = $lead->phone        ?? null;
            $order->leadEmail   = $lead->email        ?? null;
            $order->companyName = $lead->company_name ?? null;

            $order->orderTitle      = $request->orderTitle;
            $order->orderDetail     = $request->orderDetail;
            $order->deadline        = $request->deadline;
            $order->approval        = (int) $request->approval;
            $order->orderDate       = now();
            $order->orderAttachment = $attachmentString;

            // Default statuses for a new artist order
            $order->orderStatus = 'in_progress';
            $order->draft       = 1;
            $order->submit      = 0;
            $order->pending     = 0;
            $order->status      = 0;

            $order->save();

            // Collect products from form
            $productsData = $request->products;

            // If CSV uploaded, parse & append rows
            if ($request->hasFile('csv_file')) {
                $path        = $request->file('csv_file')->getPathname();
                $reader      = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
                $spreadsheet = $reader->load($path);
                $rows        = $spreadsheet->getActiveSheet()->toArray();

                foreach ($rows as $idx => $row) {
                    if ($idx === 0) continue; // header
                    $productsData[] = [
                        'product_name'  => $row[0] ?? '',
                        'quantity'      => (int)($row[1] ?? 0),
                        'remark'        => $row[2] ?? null,
                        'material_info' => $row[3] ?? null,
                        'location'      => $row[4] ?? null,
                        'date_time'     => $row[5] ?? null,
                    ];
                }
            }

            // Persist products
            foreach ($request->input('products', []) as $p) {
                if (empty($p['product_name']) || empty($p['quantity'])) {
                    continue;
                }

                // Save into products table (columns based on your screenshot)
                $product = \App\Models\Product::create([
                    'OrderID'       => $order->id,
                    'productName'   => $p['product_name'],
                    'totalQuantity' => (int) $p['quantity'],
                    'materialRemark'=> $p['material_info'] ?? null,
                    // let defaults handle: taskType, status ('in_progress'), editable (1)
                ]);

                // Save remarks into product_remarks (use model if you have one; else DB::table)
                if (!empty($p['remarks']) && is_array($p['remarks'])) {
                    $rows = [];
                    $now  = now();
                    foreach ($p['remarks'] as $r) {
                        if (empty($r['operation']) || empty($r['remark'])) continue;
                        $rows[] = [
                            'ProductID'   => $product->getKey(), // primary key (ProductID)
                            'operation'   => $r['operation'],    // enum: printing/furnishing/installation/delivery
                            'remark'      => $r['remark'],
                            'created_at'  => $now,
                            'updated_at'  => $now,
                        ];
                    }
                    if ($rows) {
                        DB::table('product_remarks')->insert($rows);
                    }
                }
            }

            return redirect()->route('artist.orders.show', $order->id)
                ->with('success', 'Order created successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->validator)->withInput();
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'Failed to create order.')->withInput();
        }
    }

    protected function makeOrderNumber(): string
    {
        $date = now()->format('Ymd');
        $count = Order::whereDate('created_at', now()->toDateString())->count() + 1;
        return sprintf('JO-%s-%04d', $date, $count);
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
        return view('artist.orders.order-edit', compact('order'));
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

        return redirect()->route('artist.orders')->with('success', 'Order updated successfully');
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
        return view('artist.orders.order-view', compact('order', 'attachments', 'leadAttachments'));
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

    public function searchLeads(\Illuminate\Http\Request $request)
    {
        $q = trim((string) $request->query('q', ''));   // << read ?q=
        if ($q === '' || strlen($q) < 2) {
            return response()->json(['results' => []]);
        }

        $leads = \App\Models\Lead::query()
            ->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                ->orWhere('company_name', 'like', "%{$q}%")
                ->orWhere('phone', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%");
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id','name','company_name','phone','email']);

        return response()->json([
            'results' => $leads->map(fn($l) => [
                'id'   => $l->id,
                'text' => $l->name,
                'meta' => [
                    'company_name' => $l->company_name,
                    'phone'        => $l->phone,
                    'email'        => $l->email,
                ],
            ]),
        ]);
    }

    public function getLead($id)
    {
        $lead = \App\Models\Lead::findOrFail($id);
        return response()->json([
            'id'            => $lead->id,
            'name'          => $lead->name,
            'company_name'  => $lead->company_name,
            'phone'         => $lead->phone,
            'email'         => $lead->email,
            'company_phone' => $lead->company_phone ?? '',
        ]);
    }
}
