<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;

use App\Models\User;
use App\Models\Lead;
use App\Models\Note;
use App\Models\LeadAttachment;
use App\Models\Reminder;
use App\Models\NoteAttachment;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Validation\Rule;
use App\Helpers\Helpers;
use App\Models\Meeting;
use App\Models\Order;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

use Carbon\Carbon;

class BossLeadController extends Controller
{
    public function leadManagement()
    {
        $user = Auth::user();

        if (!($user->hasRole('boss'))) {
            abort(403, 'Unauthorized');
        }

        $salespeople = User::whereIn('role', ['salesperson', 'head-salesperson'])->get();

        if ($user->hasRole('boss')) {
            // Head-salesperson can see all leads
            $leads = Lead::with('user')->latest()->get();
        } else {
            // Salesperson only sees their own leads
            $leads = $user->leads()->with('user')->latest()->get();
        }

        return view('boss.lead-management', compact('leads', 'salespeople'));
    }


    public function getLead($id)
    {
        \Log::info('getLead called for lead ID: ' . $id . ' by user: ' . Auth::user()->email);

        $lead = Lead::findOrFail($id);
        if ($lead->salesperson_id !== Auth::id() && !Auth::user()->hasRole('boss')) {
            \Log::warning('Unauthorized access attempt for lead ID: ' . $id);
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

    public function searchLeads(Request $request)
    {
        $user = Auth::user();
        if (!($user->hasRole('boss'))) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $query = $request->input('query');
        if (!$query || strlen($query) < 2) {
            return response()->json([]);
        }

        // Start with base query
        $leadsQuery = Lead::query();

        // Restrict only if normal salesperson
        if ($user->hasRole('boss') && !$user->hasRole('head-salesperson')) {
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




    public function getLeads(Request $request)
    {
        $user = Auth::user();

        Log::info(
            'getLeads called for user: ' .
            ($user?->email ?? 'unknown')
        );

        if (!$user || !$user->hasRole('boss')) {
            return response()->json([
                'error' => 'Unauthorized',
            ], 403);
        }

        /*
         * Row-level assignment dropdowns must contain active eligible users
         * only. The currently assigned inactive user's name is still shown
         * above the dropdown through the lead's user relationship.
         */
        $salespeople = User::query()
            ->whereIn('role', [
                'salesperson',
                'head-salesperson',
                'boss',
            ])
            ->whereRaw('LOWER(TRIM(status)) = ?', ['active'])
            ->orderByRaw("\n                CASE\n                    WHEN role = 'head-salesperson' THEN 0\n                    WHEN role = 'salesperson' THEN 1\n                    WHEN role = 'boss' THEN 2\n                    ELSE 3\n                END\n            ")
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'email',
                'role',
                'status',
            ]);

        $leads = Lead::query()
            ->with([
                'user:id,name,email,role,status',
                'attachments',
                'reminders',
                'notes',
            ])
            ->orderByDesc('created_at');

        if ($request->filled('search.value')) {
            $search = trim((string) $request->input('search.value'));

            $leads->where(function ($query) use ($search) {
                $query
                    ->where('company_name', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $leads->where('status', $request->input('status'));
        }

        if ($request->filled('from_date')) {
            $leads->whereDate(
                'created_at',
                '>=',
                $request->input('from_date')
            );
        }

        if ($request->filled('to_date')) {
            $leads->whereDate(
                'created_at',
                '<=',
                $request->input('to_date')
            );
        }

        if ($request->filled('salesperson_id')) {
            $leads->where(
                'salesperson_id',
                $request->input('salesperson_id')
            );
        }

        return DataTables::of($leads)
            ->addColumn('lead_data', function ($lead) {
                $leadId = (int) $lead->id;

                $dropdown =
                    '<select class="form-select form-select-sm status-dropdown" ' .
                    'data-id="' . $leadId . '" ' .
                    'style="white-space: nowrap; width: auto;">';

                foreach ([
                    'accept' => 'Accept',
                    'reject' => 'Reject',
                    'followup' => 'Followup',
                    'meeting' => 'Meeting',
                    'new' => 'New',
                ] as $value => $label) {
                    $selected = $lead->status === $value
                        ? ' selected'
                        : '';

                    $dropdown .=
                        '<option value="' . e($value) . '"' .
                        $selected . '>' .
                        e($label) .
                        '</option>';
                }

                $dropdown .= '</select><br>';

                $opportunityDropdown =
                    '<select class="form-select form-select-sm opportunity-dropdown" ' .
                    'data-id="' . $leadId . '" ' .
                    'style="white-space: nowrap; width: auto;">';

                foreach ([
                    '50/50' => '50/50',
                    'High Chance' => 'High Chance',
                    'Low Chance' => 'Low Chance',
                    'None' => 'None',
                ] as $value => $label) {
                    $selected = $lead->opportunity === $value
                        ? ' selected'
                        : '';

                    $opportunityDropdown .=
                        '<option value="' . e($value) . '"' .
                        $selected . '>' .
                        e($label) .
                        '</option>';
                }

                $opportunityDropdown .= '</select>';

                return
                    '<div class="lead-data-cell" style="white-space: nowrap;">' .
                        '<span class="lead-id">' . $leadId . '</span><br>' .
                        $dropdown .
                        $opportunityDropdown .
                    '</div>';
            })
            ->addColumn('company_details', function ($lead) {
                $attachmentButton = '';

                if ($lead->attachments->isNotEmpty()) {
                    $attachmentButton =
                        '<div class="d-flex align-items-center text-secondary mb-1">' .
                            '<i class="bx bx-paperclip me-2"></i>' .
                            '<button class="btn btn-link p-0 m-0 view-attachments" ' .
                                'data-id="' . (int) $lead->id . '">' .
                                'View Attachments' .
                            '</button>' .
                        '</div>';
                }

                return
                    '<div class="company-details-cell text-secondary">' .
                        '<div class="d-flex align-items-center mb-1">' .
                            '<i class="bx bxs-building me-2"></i>' .
                            e($lead->company_name) .
                        '</div>' .
                        '<div class="d-flex align-items-center mb-1">' .
                            '<i class="bx bxs-phone me-2"></i>' .
                            e($lead->company_phone ?? 'N/A') .
                        '</div>' .
                        '<div class="d-flex align-items-center mb-1">' .
                            '<i class="bx bx-globe me-2"></i>' .
                            e($lead->website ?? 'N/A') .
                        '</div>' .
                        $attachmentButton .
                    '</div>';
            })
            ->addColumn('lead_details', function ($lead) {
                $latestNote = trim(
                    (string) ($lead->notes->last()->content ?? '')
                );

                $html =
                    '<div class="lead-details-cell">' .
                        '<div class="d-flex align-items-center mb-1">' .
                            '<i class="bx bxs-user me-2"></i>' .
                            e($lead->name) .
                        '</div>' .
                        '<div class="d-flex align-items-center mb-1">' .
                            '<i class="bx bxs-phone me-2"></i>' .
                            e($lead->phone ?? 'N/A') .
                        '</div>' .
                        '<div class="d-flex align-items-center mb-1">' .
                            '<i class="bx bx-envelope me-2"></i>' .
                            e($lead->email ?? 'N/A') .
                        '</div>';

                if ($latestNote !== '') {
                    $html .=
                        '<div class="d-flex align-items-center mb-1">' .
                            '<i class="bx bx-note me-2"></i>' .
                            e($latestNote) .
                        '</div>';
                }

                return $html . '</div>';
            })
            ->addColumn(
                'assigned_salesperson',
                function ($lead) use ($salespeople) {
                    $assignDropdown =
                        '<select class="form-select form-select-sm assign-dropdown" ' .
                            'data-id="' . (int) $lead->id . '" ' .
                            'data-previous-value="' .
                                e((string) ($lead->salesperson_id ?? '')) .
                            '">' .
                            '<option value="">Select Salesperson</option>';

                    foreach ($salespeople as $salesperson) {
                        $selected =
                            (int) $lead->salesperson_id ===
                            (int) $salesperson->id
                                ? ' selected'
                                : '';

                        $roleLabel = str_replace(
                            '-',
                            ' ',
                            (string) $salesperson->role
                        );

                        $assignDropdown .=
                            '<option value="' . (int) $salesperson->id . '"' .
                                ' data-user-status="active"' .
                                ' data-user-role="' . e($salesperson->role) . '"' .
                                $selected .
                            '>' .
                                e($salesperson->name) .
                                ' (' . e($roleLabel) . ')' .
                            '</option>';
                    }

                    $assignDropdown .= '</select>';

                    /*
                     * Keep displaying the existing assigned user's name even
                     * if that account has since become inactive. It is not
                     * included in the selectable dropdown options.
                     */
                    $assignedName = $lead->user?->name
                        ? e($lead->user->name)
                        : 'Not Assigned';

                    return
                        '<div class="assigned-salesperson-cell">' .
                            '<div class="mb-1">' . $assignedName . '</div>' .
                            $assignDropdown .
                        '</div>';
                }
            )
            ->addColumn('reminder', function ($lead) use ($user) {
                $reminders = $lead->reminders()
                    ->where('created_by', $user->id)
                    ->where('status', '!=', 'completed')
                    ->orderByRaw(
                        "FIELD(status, 'upcoming', 'overdue')"
                    )
                    ->orderBy('remind_at')
                    ->take(5)
                    ->get();

                if ($reminders->isEmpty()) {
                    return '<span class="text-muted">No Reminders</span>';
                }

                $html = '<ul class="list-unstyled">';

                foreach ($reminders as $reminder) {
                    $dueDate = $reminder->remind_at;

                    if (
                        $dueDate->isPast() &&
                        $reminder->status === 'upcoming'
                    ) {
                        $reminder->status = 'overdue';
                        $reminder->save();

                        $relativeTime =
                            'Overdue (' .
                            $dueDate->format('Y-m-d H:i') .
                            ')';
                    } else {
                        $relativeTime = $dueDate->diffForHumans();
                    }

                    if ($reminder->status === 'overdue') {
                        $colorClass = 'text-danger';
                    } elseif ($dueDate->diffInHours() <= 24) {
                        $colorClass = 'text-warning';
                    } else {
                        $colorClass = 'text-success';
                    }

                    $html .=
                        '<li>' .
                            '<a href="#" ' .
                                'class="confirm-reminder ' . $colorClass . '" ' .
                                'data-id="' . (int) $lead->id . '" ' .
                                'data-reminder-id="' .
                                    (int) $reminder->id .
                                '" ' .
                                'data-title="' .
                                    e($reminder->title) .
                                '">' .
                                    e($reminder->title) .
                                    ' (' . e($relativeTime) . ')' .
                            '</a>' .
                        '</li>';
                }

                return $html . '</ul>';
            })
            ->addColumn('actions', function ($lead) {
                return
                    '<div class="actions-cell d-flex gap-2">' .
                        '<a href="' .
                            route('boss.leads.edit', $lead->id) .
                            '" class="btn" title="Edit">' .
                            '<i class="bx bxs-edit me-2" ' .
                                'style="font-size: 1.5em;"></i>' .
                        '</a>' .
                        '<a href="' .
                            route('boss.leads.show', $lead->id) .
                            '" class="btn" title="View">' .
                            '<i class="bx bxs-show me-2" ' .
                                'style="font-size: 1.5em;"></i>' .
                        '</a>' .
                    '</div>';
            })
            ->rawColumns([
                'lead_data',
                'company_details',
                'lead_details',
                'assigned_salesperson',
                'reminder',
                'actions',
            ])
            ->toJson();
    }

    public function confirmReminderStatus(Request $request, $id, $reminderId)
    {
        \Log::info('confirmReminderStatus called for lead ID: ' . $id . ', reminder ID: ' . $reminderId, $request->all());

        $lead = Lead::findOrFail($id);
        $reminder = Reminder::where('lead_id', $id)->findOrFail($reminderId);

        if ($reminder->created_by !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($request->input('confirm') === 'yes') {
            $reminder->update(['status' => 'completed']);
            \Log::info("Reminder ID {$reminderId} marked as completed for lead ID {$id}");
        } else {
            \Log::info("Reminder ID {$reminderId} not marked as completed for lead ID {$id}");
        }

        return response()->json(['success' => true, 'message' => 'Reminder status updated']);
    }

    public function create()
    {
        $user = Auth::user();

        if (!$user || !$user->hasRole('boss')) {
            abort(403, 'Unauthorized');
        }

        $salespeople = User::query()
            ->whereIn('role', [
                'salesperson',
                'head-salesperson',
                'boss',
            ])
            ->whereRaw('LOWER(TRIM(status)) = ?', ['active'])
            ->orderByRaw("\n                CASE\n                    WHEN role = 'head-salesperson' THEN 0\n                    WHEN role = 'salesperson' THEN 1\n                    WHEN role = 'boss' THEN 2\n                    ELSE 3\n                END\n            ")
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'email',
                'role',
                'status',
            ]);

        return view(
            'boss.add-lead',
            compact('salespeople')
        );
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        if (!$user || !$user->hasRole('boss')) {
            return response()->json([
                'error' => 'Unauthorized',
            ], 403);
        }

        $validated = $request->validate(
            [
                'company_name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('leads')->where(
                        function ($query) use ($request) {
                            $query->whereRaw(
                                'LOWER(company_name) = LOWER(?)',
                                [$request->input('company_name')]
                            );
                        }
                    ),
                ],

                'company_phone' => [
                    'nullable',
                    'string',
                    'regex:/^[0-9+\-\s()]+$/',
                    'max:20',
                ],

                'website' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'name' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'phone' => [
                    'required',
                    'string',
                    'regex:/^[0-9+\-\s()]+$/',
                    'max:20',
                ],

                'email' => [
                    'nullable',
                    'email',
                    'max:255',
                ],

                'salesperson_id' => [
                    'required',
                    'integer',
                    Rule::exists('users', 'id')->where(
                        function ($query) {
                            $query
                                ->whereIn('role', [
                                    'salesperson',
                                    'head-salesperson',
                                    'boss',
                                ])
                                ->whereRaw(
                                    'LOWER(TRIM(status)) = ?',
                                    ['active']
                                );
                        }
                    ),
                ],

                'opportunity' => [
                    'required',
                    Rule::in([
                        '50/50',
                        'High Chance',
                        'Low Chance',
                        'None',
                    ]),
                ],

                'remark' => [
                    'nullable',
                    'string',
                ],

                'attachments' => [
                    'nullable',
                    'array',
                    'max:10',
                ],

                'attachments.*' => [
                    'file',
                    'mimes:pdf,doc,docx,jpg,jpeg,png',
                    'max:10240',
                ],
            ],
            [
                'salesperson_id.required' =>
                    'Please select a salesperson.',

                'salesperson_id.integer' =>
                    'The selected salesperson is invalid.',

                'salesperson_id.exists' =>
                    'The selected salesperson is inactive or unavailable.',
            ]
        );

        /*
         * Query again immediately before saving. This handles an account
         * becoming inactive after the form was opened.
         */
        $assignee = User::query()
            ->whereKey((int) $validated['salesperson_id'])
            ->whereIn('role', [
                'salesperson',
                'head-salesperson',
                'boss',
            ])
            ->whereRaw('LOWER(TRIM(status)) = ?', ['active'])
            ->first([
                'id',
                'name',
                'email',
                'role',
                'status',
            ]);

        if (!$assignee) {
            throw ValidationException::withMessages([
                'salesperson_id' => [
                    'The selected salesperson is inactive or unavailable.',
                ],
            ]);
        }

        $lead = Lead::create([
            'salesperson_id' => $assignee->id,
            'company_name' => $validated['company_name'],
            'company_phone' => $validated['company_phone'] ?? null,
            'website' => $validated['website'] ?? null,
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'status' => 'new',
            'opportunity' => $validated['opportunity'],
            'remark' => $validated['remark'] ?? null,
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments', []) as $file) {
                if (!$file || !$file->isValid()) {
                    continue;
                }

                $originalName = pathinfo(
                    $file->getClientOriginalName(),
                    PATHINFO_FILENAME
                );

                $extension = strtolower(
                    $file->getClientOriginalExtension()
                );

                $safeOriginalName = Str::slug($originalName);

                if ($safeOriginalName === '') {
                    $safeOriginalName = 'attachment';
                }

                $newName =
                    $safeOriginalName .
                    '_' .
                    now()->format('Ymd_His_u') .
                    '.' .
                    $extension;

                $path = $file->storeAs(
                    'leads/' . $lead->id,
                    $newName,
                    'public'
                );

                LeadAttachment::create([
                    'lead_id' => $lead->id,
                    'user_id' => $user->id,
                    'file_size' => $file->getSize(),
                    'file_location' => $path,
                    'file_extension' => $extension,
                ]);
            }
        }

        return redirect()
            ->route('boss.leads')
            ->with('success', 'Lead added successfully.');
    }

    public function exportCsv(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasRole('boss') && !$user->hasRole('head-salesperson')) {
            abort(403, 'Unauthorized');
        }

        $leads = Lead::with('user')->orderBy('created_at', 'desc');

        if ($user->hasRole('boss')) {
            $leads = $leads->where('salesperson_id', $user->id);
        }

        if ($request->has('search') && $request->input('search.value')) {
            $search = $request->input('search.value');
            $leads->where(function ($query) use ($search) {
                $query->where('company_name', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%");
            });
        }

        if ($request->has('status') && $request->input('status')) {
            $leads->where('status', $request->input('status'));
        }

        // if ($request->has('from_date') && $request->input('from_date')) {
        //     $leads->whereDate('created_at', '>=', $request->input('from_date'));
        // }

        // if ($request->has('to_date') && $request->input('to_date')) {
        //     $leads->whereDate('created_at', '<=', $request->input('to_date'));
        // }

        if ($user->hasRole('head-salesperson') && $user->hasRole('boss') && $request->has('salesperson_id') && $request->input('salesperson_id')) {
            $leads->where('salesperson_id', $request->input('salesperson_id'));
        }

        $leads = $leads->get();

        $filename = 'leads_' . now()->format('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        return response()->stream(function () use ($leads) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Company Name', 'PIC Name', 'Phone', 'Email', 'Company Phone', 'Website', 'Opportunity', 'Status', 'Remark', 'Assigned To', 'Created At']);

            foreach ($leads as $lead) {
                fputcsv($handle, [
                    $lead->id,
                    $lead->company_name,
                    $lead->name,
                    $lead->phone,
                    $lead->email,
                    $lead->company_phone,
                    $lead->website,
                    $lead->opportunity,
                    $lead->status,
                    $lead->remark,
                    $lead->user->name ?? 'N/A',
                    $lead->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    public function show($leadId)
    {
        $user = Auth::user();

        $lead = Lead::with([
            'orders' => function ($q) {
                $q->select([
                    'id',
                    'lead_id',
                    'order_number',
                    'orderTitle',
                    'orderStatus',
                    'created_at',
                    'redo',       // original order id if this row is a redo
                    'status',     // 0/null = active, 1 = hidden/archived
                ])
                // HIDE orders where status = 1
                ->where(function ($w) {
                    $w->whereNull('status')->orWhere('status', 0);
                })
                ->orderByDesc('created_at');
            },
        ])->findOrFail($leadId);

        // map: original order id -> original order_number (for redo rows)
        $redoIds = $lead->orders->pluck('redo')->filter()->unique()->values();
        $origMap = $redoIds->isNotEmpty()
            ? Order::whereIn('id', $redoIds)->pluck('order_number', 'id')
            : collect();

        // add display fields for the blade
        $lead->orders->transform(function ($o) use ($origMap) {
            $isRedo = !empty($o->redo);

            if ($isRedo && $origMap->has($o->redo)) {
                // show the ORIGINAL base order number + 'R'
                $o->display_order_number = rtrim($origMap[$o->redo]) . 'R';
                $o->display_order_id     = (int) $o->redo;
            } else {
                $o->display_order_number = $o->order_number;
                $o->display_order_id     = (int) $o->id;
            }

            $o->is_redo = $isRedo;
            return $o;
        });

        return view('boss.lead-view', compact('lead'));
    }


    public function addReminder(Request $request, $id)
    {
        \Log::info('addReminder called for lead ID: ' . $id, $request->all());

        $user = Auth::user();
        $lead = Lead::findOrFail($id);

        // Restrict normal salesperson but allow head-salesperson (and admin if needed)
        // if ($user->hasRole('boss') && $lead->salesperson_id !== $user->id) {
        //     return response()->json(['error' => 'Unauthorized'], 403);
        // }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'remind_at' => 'required|date',
            'status' => 'required|in:upcoming,overdue,completed',
            'recurrence_type' => 'nullable|in:none,daily,weekly,monthly',
            'recurrence_time' => 'nullable|date_format:H:i',
        ]);
        return response()->json($validated, 403);
        $validated['is_auto'] = false;
        $validated['end_date'] = Carbon::parse($validated['remind_at'])->addDays(3);
        $validated['last_notify_time'] = null;

        try {
            $reminder = $lead->reminders()->create($validated);
            $reminder->notifyUser();
            \Log::info("Reminder ID {$reminder->id} created for lead ID {$id}");
            return response()->json(['success' => true, 'reminder' => $reminder]);
        } catch (\Exception $e) {
            \Log::error("Error creating reminder for lead ID {$id}: " . $e->getMessage());
            return response()->json(['error' => 'Failed to create reminder: ' . $e->getMessage()], 500);
        }
    }


    public function addNote(Request $request, $id)
    {
        $user = Auth::user();
        $lead = Lead::findOrFail($id);

        // if ($user->hasRole('boss') && $lead->salesperson_id !== $user->id) {
        //     return response()->json(['error' => 'Unauthorized'], 403);
        // }

        $validated = $request->validate([
            'content' => 'required|string',
            'date' => 'nullable|date',
        ]);

        $validated['user_id'] = $user->id;
        $note = $lead->notes()->create($validated);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = $file->getClientOriginalExtension();
                $timestamp = now()->format('Ymd_His');
                $newName = $originalName . '_' . $timestamp . '.' . $extension;

                $path = $file->storeAs('notes/' . $note->id, $newName, 'public');

                NoteAttachment::create([
                    'note_id' => $note->id,
                    'user_id' => $user->id,
                    'file_size' => $file->getSize(),
                    'file_location' => $path,
                    'file_extension' => $extension,
                ]);
            }
        }


        return response()->json(['success' => true, 'note' => $note->load('attachments')]);
    }

    public function deleteNote($id, $noteId)
    {
        $lead = Lead::findOrFail($id);
        $this->authorizeLeadAccess($lead);

        $note = Note::findOrFail($noteId);
        $user = Auth::user();

        // if ($user->hasRole('boss') && $note->user_id !== $user->id) {
        //     return response()->json(['error' => 'Unauthorized'], 403);
        // }

        // Delete attachments
        foreach ($note->attachments as $attachment) {
            \Storage::disk('public')->delete($attachment->file_location);
            $attachment->delete();
        }

        $note->delete();

        return response()->json(['success' => true]);
    }

    public function deleteNoteAttachment($id, $noteId, $attachmentId)
    {
        $lead = Lead::findOrFail($id);
        $this->authorizeLeadAccess($lead);

        $attachment = NoteAttachment::where('note_id', $noteId)->findOrFail($attachmentId);
        \Storage::disk('public')->delete($attachment->file_location);
        $attachment->delete();

        return response()->json(['success' => true]);
    }


    public function getAttachments($id)
    {

        $user = Auth::user();
        $lead = Lead::with('attachments')->findOrFail($id);
        // if ($user->hasRole('boss') && $lead->salesperson_id !== $user->id) {
        //     return response()->json(['error' => 'Unauthorized'], 403);
        // }

        $html = '<div class="table-responsive">';
        $html .= '<table class="table table-bordered table-hover">';
        $html .= '<thead><tr><th>Name</th><th>Uploaded By</th><th>Date</th><th>Size</th><th>Actions</th></tr></thead>';
        $html .= '<tbody>';
        foreach ($lead->attachments as $attachment) {
            $html .= '<tr>';
            $html .= '<td>' . basename($attachment->file_location) . '</td>';
            $html .= '<td>' . ($attachment->user->name ?? 'Unknown') . '</td>';
            $html .= '<td>' . $attachment->created_at->format('Y-m-d') . '</td>';
            $html .= '<td>' . round($attachment->file_size / 1024) . ' KB</td>';
            $html .= '<td>';
            $html .= '<a href="' . asset('storage/' . $attachment->file_location) . '" class="text-primary me-2" target="_blank" title="View"><i class="bx bx-show"></i></a>';
            $html .= '<a href="' . asset('storage/' . $attachment->file_location) . '" class="text-secondary me-2" download title="Download"><i class="bx bx-download"></i></a>';
            $html .= '<a href="' . route('boss.leads.attachments.delete', ['id' => $lead->id, 'attachment' => $attachment->id]) . '" class="text-danger me-2 delete-attachment" title="Delete"><i class="bx bx-trash"></i></a>';
            $html .= '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
        $html .= '</div>';

        return $html;
    }
    public function addAttachment(Request $request, $id)
    {
        $user = Auth::user();
        $lead = Lead::findOrFail($id);
        // if ($user->hasRole('boss') && $lead->salesperson_id !== $user->id) {
        //     abort(403, 'Unauthorized');
        // }

        $validated = $request->validate([
            'attachments' => 'required|array|max:10', // Validate array
        ]);

        if ($request->hasFile('attachments')) {
            // Validate each file
            $validator = Validator::make($request->all(), [
                'attachments.*' => 'required|mimes:pdf,doc,jpg,png|max:10240',
            ]);

            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }

            foreach ($request->file('attachments') as $file) {
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = $file->getClientOriginalExtension();
                $timestamp = now()->format('Ymd_His');
                $newName = $originalName . '_' . $timestamp . '.' . $extension;
                $path = $file->storeAs('leads/' . $lead->id, $newName, 'public');
                LeadAttachment::create([
                    'lead_id' => $lead->id,
                    'user_id' => Auth::id(),
                    'file_size' => $file->getSize(),
                    'file_location' => $path,
                    'file_extension' => $extension,
                ]);
            }
        }

        return back()->with('success', 'Attachment added successfully');
    }

    protected function authorizeLeadAccess(Lead $lead)
    {
        $user = Auth::user();
        // Salesperson can only access their own leads
        // Head-salesperson can access all leads
        // if ($user->hasRole('boss') && $lead->salesperson_id !== $user->id) {
        //     abort(403, 'Unauthorized');
        // }
    }



    public function deleteAttachment($id, $attachmentId)
    {
        $lead = Lead::findOrFail($id);
        $this->authorizeLeadAccess($lead);

        $attachment = LeadAttachment::where('lead_id', $id)->findOrFail($attachmentId);
        \Storage::disk('public')->delete($attachment->file_location);
        $attachment->delete();

        return response()->json(['success' => true, 'message' => 'Attachment deleted successfully']);
    }


    public function edit($id)
    {
        $user = Auth::user();

        if (!$user || !$user->hasRole('boss')) {
            abort(403, 'Unauthorized');
        }

        $lead = Lead::with([
            'user:id,name,email,role,status',
            'attachments',
        ])->findOrFail($id);

        $this->authorizeLeadAccess($lead);

        $currentSalespersonId = (int) $lead->salesperson_id;

        $salespeople = User::query()
            ->whereIn('role', [
                'salesperson',
                'head-salesperson',
                'boss',
            ])
            ->where(function ($query) use ($currentSalespersonId) {
                $query->whereRaw(
                    'LOWER(TRIM(status)) = ?',
                    ['active']
                );

                if ($currentSalespersonId > 0) {
                    $query->orWhere('id', $currentSalespersonId);
                }
            })
            ->orderByRaw(
                'CASE WHEN id = ? THEN 0 ELSE 1 END',
                [$currentSalespersonId]
            )
            ->orderByRaw("\n                CASE\n                    WHEN role = 'head-salesperson' THEN 0\n                    WHEN role = 'salesperson' THEN 1\n                    WHEN role = 'boss' THEN 2\n                    ELSE 3\n                END\n            ")
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'email',
                'role',
                'status',
            ]);

        return view(
            'boss.lead-edit',
            compact('lead', 'salespeople')
        );
    }


    public function updateSalesperson(Request $request, $id)
    {
        Log::info(
            'updateSalesperson called with data: ',
            $request->all()
        );

        $user = Auth::user();

        if (!$user || !$user->hasRole('boss')) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized',
            ], 403);
        }

        $lead = Lead::findOrFail($id);

        $validated = $request->validate(
            [
                'salesperson_id' => [
                    'required',
                    'integer',
                    Rule::exists('users', 'id')->where(
                        function ($query) {
                            $query
                                ->whereIn('role', [
                                    'salesperson',
                                    'head-salesperson',
                                    'boss',
                                ])
                                ->whereRaw(
                                    'LOWER(TRIM(status)) = ?',
                                    ['active']
                                );
                        }
                    ),
                ],
            ],
            [
                'salesperson_id.required' =>
                    'Please select a salesperson.',

                'salesperson_id.integer' =>
                    'The selected salesperson is invalid.',

                'salesperson_id.exists' =>
                    'The selected salesperson is inactive or unavailable.',
            ]
        );

        $newSalesperson = User::query()
            ->whereKey((int) $validated['salesperson_id'])
            ->whereIn('role', [
                'salesperson',
                'head-salesperson',
                'boss',
            ])
            ->whereRaw('LOWER(TRIM(status)) = ?', ['active'])
            ->first([
                'id',
                'name',
                'email',
                'role',
                'status',
            ]);

        if (!$newSalesperson) {
            return response()->json([
                'success' => false,
                'message' =>
                    'The selected salesperson is inactive or unavailable.',
                'errors' => [
                    'salesperson_id' => [
                        'The selected salesperson is inactive or unavailable.',
                    ],
                ],
            ], 422);
        }

        $oldSalespersonId = $lead->salesperson_id;

        $lead->update([
            'salesperson_id' => $newSalesperson->id,
        ]);

        $message =
            "You have been assigned to lead '{$lead->name}' " .
            "({$lead->company_name}) by {$user->name}.";

        Helpers::notify(
            $newSalesperson,
            $message,
            route('boss.leads.show', $lead->id)
        );

        if (
            $oldSalespersonId &&
            (int) $oldSalespersonId !== (int) $newSalesperson->id
        ) {
            $oldSalesperson = User::find($oldSalespersonId);

            if ($oldSalesperson) {
                $oldMessage =
                    "Lead '{$lead->name}' has been reassigned " .
                    "from you to {$newSalesperson->name}.";

                Helpers::notify(
                    $oldSalesperson,
                    $oldMessage,
                    route('boss.leads')
                );
            }
        }

        return response()->json([
            'success' => true,
            'message' =>
                "Lead assigned to {$newSalesperson->name} successfully.",
            'salesperson' => [
                'id' => $newSalesperson->id,
                'name' => $newSalesperson->name,
                'role' => $newSalesperson->role,
            ],
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        \Log::info('updateStatus called with data: ', $request->all());

        $lead = Lead::findOrFail($id);
        $user = Auth::user();

        // Salesperson can only update their own leads
        // Head-salesperson can update any lead
        // if ($user->hasRole('boss') && $lead->salesperson_id !== $user->id) {
        //     return response()->json(['error' => 'Unauthorized'], 403);
        // }

        $request->validate([
            'status' => 'required|in:accept,reject,followup,new,meeting'
        ]);

        $lead->update(['status' => $request->input('status')]);

        // If status is 'accept', return redirect URL
        if ($request->input('status') === 'accept') {
            $url = route('orders.create', ['lead_id' => $lead->id]);
            return response()->json([
                'success' => true,
                'redirect' => $url
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function updateOpportunity(Request $request, $id)
    {
        $lead = Lead::findOrFail($id);
        $user = Auth::user();


        // if ($user->hasRole('boss') && $lead->salesperson_id !== $user->id) {
        //     return response()->json(['error' => 'Unauthorized'], 403);
        // }

        $lead->update(['opportunity' => $request->input('opportunity')]);

        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        $lead = Lead::findOrFail($id);
        $user = Auth::user();

        // Salesperson can only delete their own leads
        // Head-salesperson can delete any lead
        // if ($user->hasRole('boss') && $lead->salesperson_id !== $user->id) {
        //     return response()->json(['error' => 'Unauthorized'], 403);
        // }

        $lead->delete();

        return response()->json(['message' => 'Lead deleted successfully']);
    }


    public function update(Request $request, $id)
    {
        $user = Auth::user();

        if (!$user || !$user->hasRole('boss')) {
            abort(403, 'Unauthorized');
        }

        $lead = Lead::findOrFail($id);

        $rules = [
            'company_name' => [
                'required',
                'string',
                'max:255',
            ],

            'company_phone' => [
                'nullable',
                'string',
                'regex:/^[0-9+\-\s()]+$/',
                'max:20',
            ],

            'website' => [
                'nullable',
                'string',
                'max:255',
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'phone' => [
                'required',
                'string',
                'regex:/^[0-9+\-\s()]+$/',
                'max:20',
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'salesperson_id' => [
                'required',
                'integer',

                function ($attribute, $value, $fail) use ($lead) {
                    $requestedId = (int) $value;
                    $currentAssignedId = (int) $lead->salesperson_id;

                    $selectedUser = User::query()
                        ->whereKey($requestedId)
                        ->whereIn('role', [
                            'salesperson',
                            'head-salesperson',
                            'boss',
                        ])
                        ->first([
                            'id',
                            'role',
                            'status',
                        ]);

                    if (!$selectedUser) {
                        $fail(
                            'The selected salesperson is invalid or unavailable.'
                        );

                        return;
                    }

                    if ((int) $selectedUser->id === $currentAssignedId) {
                        return;
                    }

                    $isActive =
                        strtolower(trim((string) $selectedUser->status))
                        === 'active';

                    if (!$isActive) {
                        $fail(
                            'The newly selected salesperson is inactive or unavailable.'
                        );
                    }
                },
            ],

            'status' => [
                'required',
                Rule::in([
                    'accept',
                    'reject',
                    'followup',
                    'new',
                    'meeting',
                ]),
            ],

            'opportunity' => [
                'required',
                Rule::in([
                    '50/50',
                    'High Chance',
                    'Low Chance',
                    'None',
                ]),
            ],

            'remark' => [
                'nullable',
                'string',
            ],

            'attachments' => [
                'nullable',
                'array',
                'max:10',
            ],

            'attachments.*' => [
                'file',
                'mimes:pdf,doc,docx,jpg,jpeg,png',
                'max:10240',
            ],
        ];

        $validated = $request->validate(
            $rules,
            [
                'salesperson_id.required' =>
                    'Please select a salesperson.',

                'salesperson_id.integer' =>
                    'The selected salesperson is invalid.',

                'attachments.max' =>
                    'You may upload a maximum of 10 attachments.',

                'attachments.*.mimes' =>
                    'Attachments must be PDF, DOC, DOCX, JPG, JPEG or PNG.',

                'attachments.*.max' =>
                    'Each attachment must not exceed 10 MB.',
            ]
        );

        $requestedSalespersonId =
            (int) $validated['salesperson_id'];

        $assignee = User::query()
            ->whereKey($requestedSalespersonId)
            ->whereIn('role', [
                'salesperson',
                'head-salesperson',
                'boss',
            ])
            ->first([
                'id',
                'name',
                'email',
                'role',
                'status',
            ]);

        if (!$assignee) {
            throw ValidationException::withMessages([
                'salesperson_id' => [
                    'The selected salesperson is invalid or unavailable.',
                ],
            ]);
        }

        $isCurrentAssignee =
            (int) $assignee->id ===
            (int) $lead->salesperson_id;

        $isActive =
            strtolower(trim((string) $assignee->status))
            === 'active';

        if (!$isCurrentAssignee && !$isActive) {
            throw ValidationException::withMessages([
                'salesperson_id' => [
                    'The newly selected salesperson is inactive or unavailable.',
                ],
            ]);
        }

        $lead->update([
            'salesperson_id' => $assignee->id,
            'company_name' => $validated['company_name'],
            'company_phone' => $validated['company_phone'] ?? null,
            'website' => $validated['website'] ?? null,
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'status' => $validated['status'],
            'opportunity' => $validated['opportunity'],
            'remark' => $validated['remark'] ?? null,
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments', []) as $file) {
                if (!$file || !$file->isValid()) {
                    continue;
                }

                $originalName = pathinfo(
                    $file->getClientOriginalName(),
                    PATHINFO_FILENAME
                );

                $extension = strtolower(
                    $file->getClientOriginalExtension()
                );

                $safeOriginalName = Str::slug($originalName);

                if ($safeOriginalName === '') {
                    $safeOriginalName = 'attachment';
                }

                $newName =
                    $safeOriginalName .
                    '_' .
                    now()->format('Ymd_His_u') .
                    '.' .
                    $extension;

                $path = $file->storeAs(
                    'leads/' . $lead->id,
                    $newName,
                    'public'
                );

                LeadAttachment::create([
                    'lead_id' => $lead->id,
                    'user_id' => $user->id,
                    'file_size' => $file->getSize(),
                    'file_location' => $path,
                    'file_extension' => $extension,
                ]);
            }
        }

        $redirectRoute =
            $request->get('highlight') === 'remark'
                ? route('boss.leads.show', $id)
                : route('boss.leads');

        return redirect($redirectRoute)
            ->with('success', 'Lead updated successfully.');
    }

    public function storeReminder(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'lead_id' => 'required|exists:leads,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'remind_at' => 'required|date',
        ]);

        $lead = Lead::findOrFail($validated['lead_id']); 

        $validated['salesperson_id'] = $user->id;
        $validated['created_by'] = $user->id;
        $validated['is_auto'] = false;
        $validated['status'] = 'upcoming';

        try {
            $reminder = Reminder::create($validated);
            Log::info("Reminder ID {$reminder->id} created for lead ID {$validated['lead_id']}");
            return response()->json(['success' => true, 'reminder' => $reminder]);
        } catch (\Exception $e) {
            Log::error("Error creating reminder: " . $e->getMessage());
            return response()->json(['error' => 'Failed to create reminder'], 500);
        }
    }

    public function updateCalendarStatus(Request $request, $id)
    {
        $validated = $request->validate(['status' => 'required|in:scheduled,canceled,postponed']);
        $meeting = Meeting::findOrFail($id);
        $meeting->update(['status' => $validated['status']]);
        return response()->json(['success' => true]);
    }

    public function updateReminderStatus(Request $request, $id)
    {
            $user = Auth::user();
            $reminder = Reminder::findOrFail($id);
            $validated = $request->validate(['status' => 'required|in:upcoming,overdue,completed']);
            $reminder->update(['status' => $validated['status']]);
        return response()->json(['success' => true]);
    }

    public function updateMeetingStatus(Request $request, $id)
    {
        $validated = $request->validate(['status' => 'required|in:scheduled,canceled,postponed']);
        $meeting = Meeting::findOrFail($id);
        $meeting->update(['status' => $validated['status']]);
        return response()->json(['success' => true]);
    }

    public function storeFromLead(Request $request, $leadId)
    {
        $validated = $request->validate([
            // 'lead_id' => 'required|exists:leads,id',
            'title' => 'required|string|max:255',
            'start_time' => 'required|date',
            'duration' => 'required|integer|min:1',
            'type' => 'nullable|in:online,offline',
            'url' => 'nullable|url',
            'location' => 'nullable|string',
            'note' => 'nullable|string',
        ]);
        // Check for existing meeting with same lead, title, and start time
        $existingMeeting = Meeting::where('lead_id', $leadId)
            ->where('title', $validated['title'])
            ->where('start_time', Carbon::parse($validated['start_time']))
            ->first();

        if ($existingMeeting) {
            return response()->json(['error' => 'A meeting with this title and time already exists for this lead.'], 422);
        }

        $meeting = Meeting::create([
            'lead_id' => $leadId,
            'user_id' => Auth::id(),
            'title' => $validated['title'],
            'start_time' => Carbon::parse($validated['start_time']),
            'end_time' => Carbon::parse($validated['start_time'])->addMinutes((int) $validated['duration']),
            'type' => $validated['type'],
            'location' => $validated['type'] === 'offline' ? $validated['location'] : null,
            'url' => $validated['type'] === 'online' ? $validated['url'] : null,
            'note' => $validated['note'],
            'status' => 'scheduled',
        ]);

        return response()->json(['success' => true, 'message' => 'Meeting created successfully', 'meeting' => $meeting]);
    }

    public function updateFromCalendar(Request $request, $id)
    {
        $user = Auth::user();
        $meeting = Meeting::findOrFail($id);

        Log::info('updateMeeting FormData:', $request->all());

        $validated = $request->validate([
            'lead_id' => 'required|exists:leads,id',
            'title' => 'required|string|max:255',
            'start_time' => 'required|date',
            'duration' => 'required|integer|min:1',
            'type' => 'nullable|in:online,offline',
            'url' => 'nullable|url',
            'location' => 'nullable|string',
            'note' => 'nullable|string',
        ]);

        $lead = Lead::findOrFail($validated['lead_id']);

        $validated['end_time'] = Carbon::parse($validated['start_time'])->addMinutes((int) $validated['duration']);
        unset($validated['duration']);
        $type = $validated['type'] ?? null;
        if ($type === 'online') {
            $validated['location'] = null;
        } elseif ($type === 'offline') {
            $validated['url'] = null;
        }

        try {
            $meeting->update($validated);
            Log::info("Meeting ID {$id} updated for lead ID {$validated['lead_id']}");
            return response()->json(['success' => true, 'meeting' => $meeting]);
        } catch (\Exception $e) {
            Log::error("Error updating meeting: " . $e->getMessage());
            return response()->json(['error' => 'Failed to update meeting'], 500);
        }
    }

    public function leadshowOrder(Order $order, $id)
    {
        // keep what you already load here (products, items, deliveryBreakdowns, etc.)
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
        return view('boss.orders.show', compact('order', 'attachments', 'leadAttachments'));
    }

    private function fileInfoFromPath(string $relPath): array
    {
        // adjust disk if needed
        $url  = Storage::disk('public')->url($relPath);
        $ext  = pathinfo($relPath, PATHINFO_EXTENSION);

        return [
            'name' => basename($relPath),
            'url'  => $url,
            'size' => null, // unknown for order CSV; can be resolved if you want
            'ext'  => $ext,
        ];
    }
}
