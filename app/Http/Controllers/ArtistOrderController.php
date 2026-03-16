<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\DeliveryBreakdown;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Helpers\Helpers;
use Illuminate\Validation\Rule;
use App\Models\OrderAttachment;
use Illuminate\Support\Str;
use App\Models\OrderRecord;

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
            'artist.orders.create',    
            'artist.orders.add-order',  
        ], compact('lead'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user || !($user->hasRole('artist') || $user->hasRole('head-artist'))) {
            return back()->with('error', 'Unauthorized');
        }

        try {
            $request->validate([
                'lead_id'     => 'nullable|exists:leads,id',
                'orderTitle'  => 'required|string|max:255',
                'deadline'    => 'required|date|after_or_equal:today',
                'approval'    => 'required|boolean',
                'orderDetail' => 'nullable|string',

                'products'                       => ['required','array','min:1'],
                'products.*.product_name'        => ['required','string','max:255'],
                'products.*.quantity'            => ['required','integer','min:1'],
                'products.*.material_info'       => ['required','string'],
                'products.*.remarks'             => ['nullable','array'],
                'products.*.remarks.*.operation' => [
                'required_with:products.*.remarks.*.remark',
                Rule::in(['printing','furnishing','installation','courier','self_pickup','artist']),
                ],
                'products.*.remarks.*.remark'    => ['required_with:products.*.remarks.*.operation','string'],

                'csv_file'    => 'nullable|file|mimes:csv,txt',
                'attachments' => 'required|array',
                'attachments.*' => 'file|mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx,ppt,pptx,ai,ps|max:20480',

                'products.*.deliveries' => ['required','array','min:1'],

                'products.*.deliveries.*.method' => [
                    'required',
                    Rule::in(['self_pickup','courier','installation'])
                ],

                'products.*.deliveries.*.location' => [
                    'required',
                    'string',
                    'max:255'
                ],

                'products.*.deliveries.*.date_time' => [
                    'required',
                    'date'
                ],
            ]);

            $lead = $request->filled('lead_id') ? Lead::find($request->lead_id) : null;

            $attachmentFiles = [];
            if ($request->hasFile('attachments')) {
                $attachmentFiles = $request->file('attachments');
                if (!is_array($attachmentFiles)) {
                    $attachmentFiles = [$attachmentFiles];
                }
            }

            // Create Order (as Artist)
            $order                    = new Order();
            $order->order_number      = $this->makeOrderNumber();
            $order->artist_id         = $user->id;                        
            $order->salesperson_id    = $lead->salesperson_id ?? null;
            $order->lead_id           = $lead->id           ?? null;
            $order->leadName          = $lead->name         ?? null;
            $order->leadPhone         = $lead->phone        ?? null;
            $order->leadEmail         = $lead->email        ?? null;
            $order->companyName       = $lead->company_name ?? null;

            $order->orderTitle        = $request->orderTitle;
            $order->orderDetail       = $request->orderDetail;
            $order->deadline          = $request->deadline;
            $order->approval          = (int) $request->approval;
            $order->orderDate         = now();

            // Default statuses for a new artist order
            $order->orderStatus = 'in_progress';
            $order->draft       = 1;
            $order->submit      = 0;
            $order->pending     = 0;
            $order->status      = 0;
            
            // Head-artist assigning to a normal artist
            $assignee = null;
            if (auth()->user()->hasRole('head-artist') && $request->filled('assignee_artist_id')) {
                $assigneeId        = (int) $request->input('assignee_artist_id');
                $order->artist_id  = $assigneeId;
                $assignee          = \App\Models\User::find($assigneeId);

                if ($assignee && $assignee->hasRole('head-artist')) {
                    // Selected a head-artist → set to in_progress (your requirement)
                    $order->orderStatus = 'in_progress';
                    $order->pending     = 0;
                } else {
                    // Selected a normal artist → keep your previous behavior
                    $order->orderStatus = 'assigned';
                    $order->pending     = 1;
                }
            }

            $order->save();

            // ✅ Order record timestamps
            OrderRecord::firstOrCreate(['order_id' => $order->id]);

            // Use order created timestamp (more accurate than now())
            $createdTs = $order->created_at ?? now();

            // Always stamp first_created_at once
            OrderRecord::where('order_id', $order->id)
                ->whereNull('first_created_at')
                ->update(['first_created_at' => $createdTs]);

            // Extra rule: if created by normal artist OR head-artist assigned to head-artist,
            // then first edit time = first create time
            $creatorRole = strtolower((string)($user->role ?? ''));
            $assigneeRole = strtolower((string)($assignee->role ?? '')); // $assignee may be null

            $shouldAutoFirstEdit =
                ($creatorRole === 'artist')
                || ($creatorRole === 'head-artist' && $assignee && $assigneeRole === 'head-artist');

            if ($shouldAutoFirstEdit) {
                OrderRecord::where('order_id', $order->id)
                    ->whereNull('first_edited_at')
                    ->update(['first_edited_at' => $createdTs]);
            }
            
            // ----- Save attachments into order_attachments table -----
            $attachmentPaths = [];
            $attachmentFiles = $request->file('attachments', []);

            if ($attachmentFiles && !is_array($attachmentFiles)) {
                $attachmentFiles = [$attachmentFiles];
            }

            if (!empty($attachmentFiles)) {
                $dir = "orders/{$order->id}/attachments";

                foreach ($attachmentFiles as $file) {
                    if (!$file || !$file->isValid()) {
                        continue;
                    }

                    $orig = $file->getClientOriginalName();
                    $base = pathinfo($orig, PATHINFO_FILENAME);
                    $ext  = strtolower($file->getClientOriginalExtension());

                    $baseSlug = Str::slug($base) ?: 'file';

                    // Avoid name clashes within this order
                    $candidate = "{$baseSlug}.{$ext}";
                    $i = 1;
                    while (Storage::disk('public')->exists("$dir/$candidate")) {
                        $candidate = "{$baseSlug} ({$i}).{$ext}";
                        $i++;
                    }

                    // Store file
                    $path = $file->storeAs($dir, $candidate, 'public');

                    // For backward compatibility (comma-separated path list)
                    $attachmentPaths[] = $path;

                    // Insert into order_attachments
                    OrderAttachment::create([
                        'order_id'      => $order->id,
                        'user_id'       => $user->id,
                        'file_path'     => $path,
                        'original_name' => $orig,
                        'mime_type'     => $file->getClientMimeType(),
                        'size'          => $file->getSize(),
                    ]);
                }
            }
            
            // Collect products from form
            $productsData = $request->products;

            // If CSV uploaded, parse & append rows
            if ($request->hasFile('csv_file')) {
                $path        = $request->file('csv_file')->getPathname();
                $reader      = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
                $spreadsheet = $reader->load($path);
                $rows        = $spreadsheet->getActiveSheet()->toArray();

                foreach ($rows as $idx => $row) {
                    if ($idx === 0) continue; 
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
            $authorId = (int) auth()->id();
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
                ]);

                // Save remarks into product_remarks (use model if you have one; else DB::table)
                if (!empty($p['remarks']) && is_array($p['remarks'])) {
                    $rows = [];
                    $now  = now();
                    foreach ($p['remarks'] as $r) {
                        if (empty($r['operation']) || empty($r['remark'])) continue;
                        $rows[] = [
                            'ProductID'   => $product->getKey(), 
                            'operation'   => $r['operation'],   
                            'remark'      => $r['remark'],
                            'user_id'    => $authorId,
                            'created_at'  => $now,
                            'updated_at'  => $now,
                        ];
                    }
                    if ($rows) {
                        DB::table('product_remarks')->insert($rows);
                    }
                }

                // Save deliveries into delivery_breakdowns (YOUR REAL TABLE)
                if (!empty($p['deliveries']) && is_array($p['deliveries'])) {
                    $rows = [];
                    $now  = now();

                    foreach ($p['deliveries'] as $d) {
                        // delivery is optional: skip only if totally empty
                        $method   = $d['method'] ?? null;
                        $location = $d['location'] ?? null;
                        $dt       = $d['date_time'] ?? null;

                        if (empty($method) && empty($location) && empty($dt)) {
                            continue;
                        }

                        // Split datetime-local into DATE + TIME (your DB uses separate columns)
                        $date = null;
                        $time = null;
                        if (!empty($dt)) {
                            try {
                                $c = \Carbon\Carbon::parse($dt);
                                $date = $c->toDateString();     // YYYY-MM-DD
                                $time = $c->format('H:i:s');    // HH:MM:SS
                            } catch (\Throwable $e) {
                                // ignore parse error, keep null
                            }
                        }

                        $rows[] = [
                            'ProductID'   => $product->getKey(),
                            'method'      => $method,
                            'location'    => $location,
                            'date'        => $date,
                            'time'        => $time,
                            // quantity is optional too (because your UI currently doesn’t collect it)
                            'quantity'    => !empty($d['quantity']) ? (int)$d['quantity'] : null,
                            'created_at'  => $now,
                            'updated_at'  => $now,
                        ];
                    }

                    if ($rows) {
                        DB::table('delivery_breakdowns')->insert($rows);
                    }
                }
            }

            /**
             * =======================
             *  NOTIFICATIONS (NEW)
             * =======================
             */
            $actor     = $user;
            $actorName = $actor->name;
            $actorRole = str_replace('-', ' ', strtolower($actor->role));

            $productCount = DB::table('products')->where('OrderID', $order->id)->count();
            $deadlineTxt  = $order->deadline
                ? \Carbon\Carbon::parse($order->deadline)->timezone('Asia/Kuala_Lumpur')->format('Y-m-d')
                : '-';

            // Base message for everyone
            $messageCommon = "New order {$order->order_number} created by {$actorName} ({$actorRole}). "
                        . "{$productCount} Product(s) added. Deadline: {$deadlineTxt}.";

            // Optional special message when head-artist assigns to a normal artist
            $messageForAssignee = null;
            if ($actor->hasRole('head-artist') && isset($assignee) && $assignee && $assignee->hasRole('artist')) {
                $messageForAssignee = "You have been **assigned** a new order {$order->order_number} by {$actorName} "
                                    . "(head-artist). {$productCount} Product(s) added. Deadline: {$deadlineTxt}.";
            }

            // Role-aware URL builder
            $urlFor = function (User $u) use ($order) {
                return match ($u->role) {
                    'artist'                          => url("/artist/orders/{$order->id}/edit"),
                    'head-artist'                     => url("/artist/orders/{$order->id}"),
                    'salesperson', 'head-salesperson' => url("/orders/{$order->id}"),
                    'admin', 'Admin'                  => url("/admin/orders/{$order->id}"),
                    'boss',  'Boss'                   => url("/boss/orders/{$order->id}"),
                    default                           => url("/"),
                };
            };

            // ======================
            // Build recipients list
            // ======================
            $recipients = collect();

            // 1) Always include these roles
            $baseRoles = ['admin', 'boss', 'head-artist', 'head-salesperson']; // head-salesperson is optional in your DB
            $recipients = $recipients->merge(
                User::whereIn('role', $baseRoles)->get()
            );

            // 2) Assigned salesperson (if any)
            if (!empty($order->salesperson_id)) {
                $recipients = $recipients->merge(
                    User::where('id', $order->salesperson_id)->get()
                );
            }

            // 3) If creator is a NORMAL artist, make sure head-artists are included (already in baseRoles, but keep this for clarity/safety)
            if (strtolower($actor->role) === 'artist') {
                $recipients = $recipients->merge(
                    User::where('role', 'head-artist')->get()
                );
            }

            // 4) If creator is head-artist and assigned to a normal artist, include that assignee
            if (strtolower($actor->role) === 'head-artist' && isset($assignee) && $assignee && strtolower($assignee->role) === 'artist') {
                $recipients = $recipients->merge([$assignee]);
            }

            // De-duplicate on user id
            $recipients = $recipients->unique('id')->values();

            // Send notifications
            foreach ($recipients as $u) {
                $msg = ($messageForAssignee && isset($assignee) && $u->id === $assignee->id)
                    ? $messageForAssignee
                    : $messageCommon;

                Helpers::notify($u, $msg, $urlFor($u), ['database', 'mail']);
            }

            if (auth()->user()->hasRole('head-artist')) {
                if ($assignee && $assignee->hasRole('head-artist')) {
                    // Assigned to head-artist → go straight to edit
                    return redirect()
                        ->route('artist.orders.edit', $order->id)
                        ->with('success', 'Order created and assigned.');
                }
                // Others unchanged → go back to list
                return redirect()
                    ->route('artist.orders')
                    ->with('success', 'Order created and assigned.');
            }

            return redirect()->route('artist.orders.edit', $order->id)
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
        $q = trim((string) $request->query('q', ''));   
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

    public function searchArtists(\Illuminate\Http\Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $base = \App\Models\User::query()
            ->whereIn('role', ['artist', 'head-artist'])
            ->orderBy('name');

        if ($q !== '') {
            $base->where('name', 'like', "%{$q}%");
        }

        $users = $base->limit(100)->get(['id','name','role']);

        return response()->json([
            'results' => $users->map(fn($u) => [
                'id'   => $u->id,
                'text' => "{$u->name} ({$u->role})",
            ]),
        ]);
    }

    public function storeProduct(Request $request, \App\Models\Order $order)
    {
        $user = auth()->user();
        if (!$user || !($user->hasRole('artist') || $user->hasRole('head-artist'))) {
            return back()->with('error', 'Unauthorized');
        }

        $request->validate([
            'product_name'        => ['nullable','string','max:255'],
            'quantity'            => ['nullable','integer','min:1'],
            'material_info'       => ['nullable','string'],
            'remarks'             => ['nullable','array'],
            'remarks.*.operation' => ['nullable','in:printing,furnishing,installation,courier,self_pickup,artist'],
            'remarks.*.remark'    => ['nullable','string'],
        ]);

        // Create product (columns match your products table)
        $product = \App\Models\Product::create([
            'OrderID'        => $order->id,
            'productName'    => $request->product_name,
            'totalQuantity'  => (int) $request->quantity,
            'materialRemark' => $request->material_info,
            // optional defaults that exist in your schema:
            'status'      => 'in_progress',
            'editable'    => 1,
        ]);

        // Optional: save product remarks if provided (product_remarks table)
        if ($request->filled('remarks') && is_array($request->remarks)) {
            $rows = [];
            $now  = now();
            $userId = auth()->id();
            foreach ($request->remarks as $r) {
                if (empty($r['operation']) || empty($r['remark'])) continue;
                $rows[] = [
                    'ProductID'  => $product->getKey(),
                    'user_id'    => $userId, 
                    'operation'  => $r['operation'],
                    'remark'     => $r['remark'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            if ($rows) {
                DB::table('product_remarks')->insert($rows);
            }
        }

        // Redirect back to edit page so the new accordion block appears
        return redirect()
            ->back()
            ->with('success', 'Product added to order.');
    }

    public function saveDeliveries(Request $request, $orderId)
    {
        $deliveries = $request->input('deliveries', []); // shape: [productId => [rows...]]

        foreach ($deliveries as $productId => $rows) {
            $productTotal = (int) Product::whereKey($productId)->value('totalQuantity');
            $alreadySaved = (int) DeliveryBreakdown::where('ProductID', $productId)->sum('quantity');

            $incoming = 0;
            foreach ($rows as $row) {
                $incoming += (int) ($row['quantity'] ?? 0);
            }

            if ($alreadySaved + $incoming > $productTotal) {
                $remain = max($productTotal - $alreadySaved, 0);
                return back()->withErrors([
                    "deliveries.$productId" =>
                        "Quantity for product #$productId exceeds its total ($productTotal). Remaining: $remain."
                ])->withInput();
            }
        }

        // ... proceed to upsert rows ...
    }

    public function assign(Request $request, \App\Models\Order $order)
    {
        try {
            if (auth()->user()->role !== 'head-artist') {
                return response()->json(['ok' => false, 'message' => 'Forbidden'], 403);
            }

            $validated = $request->validate([
                'user_id' => ['nullable','integer','exists:users,id'],
            ]);

            $assignee = isset($validated['user_id'])
                ? User::find($validated['user_id'])
                : null;

            /**
             * ✅ Record first edit time ONLY IF:
             * 1) current orderStatus is 'to_assign'
             * 2) assignee is head-artist
             */
            if ($order->orderStatus === 'to_assign' && $assignee && $assignee->role === 'head-artist') {

                // ensure record exists
                OrderRecord::firstOrCreate(
                    ['order_id' => $order->id],
                    ['first_created_at' => $order->created_at] // optional seed
                );

                // stamp only once
                OrderRecord::where('order_id', $order->id)
                    ->whereNull('first_edited_at')
                    ->update(['first_edited_at' => now()]);
            }

            // defaults
            $newStatus = 'to_assign';
            $pending   = 0;

            if ($assignee) {
                if ($assignee->role === 'head-artist') {
                    $newStatus = 'in_progress';
                    $pending   = 0;
                } else {
                    $newStatus = 'assigned';
                    $pending   = 1;
                }
            }

            $order->artist_id   = $assignee?->id;
            $order->orderStatus = $newStatus;
            $order->pending     = $pending;
            $order->save();

            return response()->json([
                'ok'           => true,
                'artist_id'    => $order->artist_id,
                'orderStatus'  => $order->orderStatus,
                'pending'      => $order->pending,
                'assigneeRole' => $assignee?->role,
            ]);
        } catch (\Throwable $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Please select a valid artist or head artist to assign.',
                ], 422);
            }
            return back()->with('error', 'Failed to assign. Please try again.');
        }
    }

    public function destroyOrder($id)
    {
        if(auth()->user()->role !== 'head-artist'){
            abort(403, 'Unauthorized action.');
        }

        $order = Order::findOrFail($id);

        // Only allow delete for allowed status
        if (!in_array($order->orderStatus, ['to_assign','in_progress'])) {
            return back()->with('error','This order cannot be deleted.');
        }

        DB::transaction(function () use ($order) {

            // Get product ids first
            $productIds = DB::table('products')
                ->where('OrderID', $order->id)
                ->pluck('ProductID');

            if ($productIds->count()) {

                // Delete product items
                DB::table('product_items')
                    ->whereIn('ProductID', $productIds)
                    ->delete();

                // Delete product remarks
                DB::table('product_remarks')
                    ->whereIn('ProductID', $productIds)
                    ->delete();

                // Delete delivery breakdowns
                DB::table('delivery_breakdowns')
                    ->whereIn('ProductID', $productIds)
                    ->delete();
            }

            // Delete products
            DB::table('products')
                ->where('OrderID', $order->id)
                ->delete();

            // Delete attachments
            DB::table('order_attachments')
                ->where('order_id', $order->id)
                ->delete();

            // Delete order record log
            DB::table('order_records')
                ->where('order_id', $order->id)
                ->delete();

            // Finally delete the order
            $order->delete();
        });

        return redirect()
            ->route('artist.orders')
            ->with('success','Order deleted successfully.');
    }

}
