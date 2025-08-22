<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;

use App\Models\User;
use App\Models\Lead;
use App\Models\LeadAttachment;
use App\Models\Reminder;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class LeadController extends Controller
{
    public function leadManagement()
    {
        $user = Auth::user();
        if (!$user->hasRole('salesperson')) {
            abort(403, 'Unauthorized');
        }
        $salespeople = User::whereIn('role', ['salesperson', 'head-salesperson'])->get();

        $leads = $user->leads()->with('user')->latest()->get(); // For initial load, if needed
        return view('sales.lead-management', compact('leads', 'salespeople'));
    }

    public function getLead($id)
    {
        \Log::info('getLead called for lead ID: ' . $id . ' by user: ' . Auth::user()->email);

        $lead = Lead::findOrFail($id);
        if ($lead->salesperson_id !== Auth::id() && !Auth::user()->hasRole('head-salesperson')) {
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



    public function getLeads(Request $request)
    {
        \Log::info('getLeads called for user: ' . Auth::user()->email);
        $user = Auth::user();

        if (!$user || !($user->hasRole('salesperson') || $user->hasRole('head-salesperson'))) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $salespeople = User::whereIn('role', ['salesperson', 'head-salesperson'])->get();
        $leads = Lead::with('user', 'attachments', 'reminders')->orderBy('created_at', 'desc');

        if ($user->hasRole('salesperson')) {
            $leads = $leads->where('salesperson_id', $user->id);
        }

        if ($request->has('search') && $request->input('search')['value']) {
            $search = $request->input('search')['value'];
            $leads->where(function ($query) use ($search) {
                $query->where('company_name', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%");
            });
        }

        if ($request->has('status') && $request->input('status')) {
            $leads->where('status', $request->input('status'));
        }

        if ($request->has('from_date') && $request->input('from_date')) {
            $leads->whereDate('created_at', '>=', $request->input('from_date'));
        }

        if ($request->has('to_date') && $request->input('to_date')) {
            $leads->whereDate('created_at', '<=', $request->input('to_date'));
        }

        if ($user->hasRole('head-salesperson') && $request->has('salesperson_id') && $request->input('salesperson_id')) {
            $leads->where('salesperson_id', $request->input('salesperson_id'));
        }



        return DataTables::of($leads)
            ->addColumn('lead_data', function ($lead) use ($user) {
                $dropdown = '<select class="form-select form-select-sm status-dropdown" data-id="' . $lead->id . '">';
                $dropdown .= '<option value="accept" ' . ($lead->status == 'accept' ? 'selected' : '') . '>Accept</option>';
                $dropdown .= '<option value="reject" ' . ($lead->status == 'reject' ? 'selected' : '') . '>Reject</option>';
                $dropdown .= '<option value="followup" ' . ($lead->status == 'followup' ? 'selected' : '') . '>Followup</option>';
                $dropdown .= '<option value="new" ' . ($lead->status == 'new' ? 'selected' : '') . '>New</option>';
                $dropdown .= '</select><br>';

                $opportunityDropdown = '<select class="form-select form-select-sm opportunity-dropdown" data-id="' . $lead->id . '">';
                $opportunityDropdown .= '<option value="50/50" ' . ($lead->opportunity == '50/50' ? 'selected' : '') . '>50/50</option>';
                $opportunityDropdown .= '<option value="High Chance" ' . ($lead->opportunity == 'High Chance' ? 'selected' : '') . '>High Chance</option>';
                $opportunityDropdown .= '<option value="Low Chance" ' . ($lead->opportunity == 'Low Chance' ? 'selected' : '') . '>Low Chance</option>';
                $opportunityDropdown .= '<option value="None" ' . ($lead->opportunity == 'None' ? 'selected' : '') . '>None</option>';
                $opportunityDropdown .= '</select>';

                return '<div class="lead-data-cell">' .
                    '<span class="lead-id">' . $lead->id . '</span><br>' .
                    $dropdown .
                    $opportunityDropdown .
                    '</div>';
            })
            ->addColumn('company_details', function ($lead) {
                $attachmentButton = '';
                if ($lead->attachments->isNotEmpty()) {
                    $attachmentButton =
                        '<div class="d-flex align-items-center text-secondary mb-1">
                            <i class="bx bx-paperclip me-2"></i>
                            <button class="btn btn-link p-0 m-0 view-attachments" data-id="' . $lead->id . '">View Attachments</button>
                        </div>';
                }

                return '<div class="company-details-cell text-secondary">' .
                    '<div class="d-flex align-items-center mb-1">
                           <i class="bx bxs-building me-2"></i>' . $lead->company_name . '
                       </div>' .
                    '<div class="d-flex align-items-center mb-1">
                           <i class="bx bxs-phone me-2"></i>' . ($lead->company_phone ?? 'N/A') . '
                       </div>' .
                    '<div class="d-flex align-items-center mb-1">
                           <i class="bx bx-globe me-2"></i>' . ($lead->website ?? 'N/A') . '
                       </div>' .
                    $attachmentButton .
                    '</div>';
            })
            ->addColumn('lead_details', function ($lead) {
                return '<div class="lead-details-cell">' .
                    $lead->name . '<br>' .
                    $lead->phone . '<br>' .
                    $lead->email . '<br>' .
                    ($lead->remark ?? 'No remark') .
                    '</div>';
            })
            ->addColumn('assigned_salesperson', function ($lead) use ($user, $salespeople) {
                if ($user->hasRole('head-salesperson')) {
                    $assignDropdown = '<select class="form-select form-select-sm assign-dropdown" data-id="' . $lead->id . '">';
                    $assignDropdown .= '<option value="">Select Salesperson</option>';
                    foreach ($salespeople as $salesperson) {
                        $selected = $lead->salesperson_id == $salesperson->id ? 'selected' : '';
                        $assignDropdown .= '<option value="' . $salesperson->id . '" ' . $selected . '>' . $salesperson->name . '</option>';
                    }
                    $assignDropdown .= '</select>';
                    return '<div class="assigned-salesperson-cell">' . ($lead->user->name ?? 'Not Assigned') . '<br>' . $assignDropdown . '</div>';
                }
                $assignValue = $user->name;
                $assignInput = '<input type="text" class="form-control form-control-sm" value="' . $assignValue . '" readonly>';
                return '<div class="assigned-salesperson-cell">' . ($lead->user->name ?? 'Not Assigned') .  '</div>';
            })
            ->addColumn('reminder', function ($lead) {
                $reminders = $lead->reminders()
                    ->whereIn('status', ['upcoming', 'completed'])
                    ->orderByRaw("FIELD(status, 'upcoming', 'completed')")
                    ->orderBy('due_date', 'asc')
                    ->take(5)
                    ->get();

                if ($reminders->isEmpty()) {
                    return '<span class="text-muted">No Reminders</span>';
                }

                $html = '<ul class="list-unstyled">';
                foreach ($reminders as $reminder) {
                    $dueDate = $reminder->due_date;
                    $relativeTime = $dueDate->diffForHumans(); // e.g., "in 1 hour", "in 5 days", "2 days ago"

                    // Customize relative time for overdue
                    if ($dueDate->isPast() && $reminder->status != 'completed') {
                        $reminder->status = 'overdue';
                        $reminder->save();
                        $relativeTime = 'Overdue (' . $dueDate->format('Y-m-d H:i') . ')';
                    } elseif ($reminder->status == 'completed') {
                        $relativeTime = 'Completed (' . $dueDate->format('Y-m-d H:i') . ')';
                    }

                    // Determine color class
                    $colorClass = '';
                    if ($reminder->status == 'completed') {
                        $colorClass = 'text-secondary text-decoration-line-through'; // Gray and strikethrough for completed
                    } elseif ($dueDate->isPast()) {
                        $colorClass = 'text-danger'; // Red for overdue
                    } elseif ($dueDate->diffInHours() <= 24) {
                        $colorClass = 'text-warning'; // Yellow/orange for soon (within 24 hours)
                    } else {
                        $colorClass = 'text-success'; // Green for future
                    }

                    $html .= '<li>';
                    if ($reminder->status == 'upcoming') {
                        $html .= '<a href="#" class="confirm-reminder ' . $colorClass . '" data-id="' . $lead->id . '" data-reminder-id="' . $reminder->id . '" data-title="' . htmlspecialchars($reminder->title) . '">' . htmlspecialchars($reminder->title) . ' (' . $relativeTime . ')</a>';
                    } else {
                        $html .= '<span class="' . $colorClass . '">' . htmlspecialchars($reminder->title) . ' (' . $relativeTime . ')</span>';
                    }
                    $html .= '</li>';
                }
                $html .= '</ul>';

                return $html;
            })
            ->addColumn('actions', function ($lead) {
                return '<div class="actions-cell d-flex gap-2">' .
                    '<a href="' . route('leads.edit', $lead->id) . '" class="btn" title="Edit"><i class="bx bxs-edit me-2" style="font-size: 1.5em;"></i></a>' .
                    '<a href="' . route('leads.show', $lead->id) . '" class="btn" title="View"><i class="bx bxs-show me-2" style="font-size: 1.5em;"></i></a>' .
                    '</div>';
            })
            ->rawColumns(['lead_data', 'company_details', 'lead_details', 'assigned_salesperson', 'reminder', 'actions'])
            ->toJson();
    }

    public function confirmReminderStatus(Request $request, $id, $reminderId)
    {
        \Log::info('confirmReminderStatus called for lead ID: ' . $id . ', reminder ID: ' . $reminderId, $request->all());

        $lead = Lead::findOrFail($id);
        if ($lead->salesperson_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $reminder = Reminder::where('lead_id', $id)->findOrFail($reminderId);

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
        if (!$user->hasRole('salesperson') && !$user->hasRole('head-salesperson')) {
            abort(403, 'Unauthorized');
        }
        $salespeople = User::whereIn('role', ['salesperson', 'head-salesperson'])->get();
        return view('sales.add-lead', compact('salespeople'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasRole('salesperson') && !$user->hasRole('head-salesperson')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'company_phone' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'salesperson_id' => 'required|exists:users,id|in:' . implode(',', User::whereIn('role', ['salesperson', 'head-salesperson'])->pluck('id')->toArray()),
            'opportunity' => 'required|in:50/50,High Chance,Low Chance,None',
            'remark' => 'nullable|string',
            'attachments' => 'nullable|array|max:10',
            'attachments.*' => 'mimes:pdf,doc,jpg,png|max:10240',
        ]);

        $lead = $user->leads()->create([
            'salesperson_id' => $validated['salesperson_id'],
            'company_name' => $validated['company_name'],
            'company_phone' => $validated['company_phone'],
            'website' => $validated['website'],
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'],
            'status' => 'new',
            'opportunity' => $validated['opportunity'],
            'remark' => $validated['remark'],
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('leads/' . $lead->id, 'public');
                LeadAttachment::create([
                    'lead_id' => $lead->id,
                    'user_id' => $user->id,
                    'file_size' => $file->getSize(),
                    'file_location' => $path,
                    'file_extension' => $file->getClientOriginalExtension(),
                ]);
            }
        }

        return redirect()->route('sales.leads')->with('success', 'Lead added successfully');
    }

    public function show($id)
    {
        $lead = Lead::with([
            'user',
            'attachments',
            'notes',
            'reminders' => function ($query) {
                $query->orderBy('due_date', 'asc') // sort earliest first
                    ->take(10); // limit to 10
            }
        ])->findOrFail($id);

        if ($lead->salesperson_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        return view('sales.lead-view', compact('lead'));
    }
    public function addReminder(Request $request, $id)
    {
        \Log::info('addReminder called for lead ID: ' . $id, $request->all());

        $lead = Lead::findOrFail($id);
        if ($lead->salesperson_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'due_date' => 'required|date',
            'status' => 'required|in:upcoming,overdue,completed',
            'recurrence_type' => 'nullable|in:none,daily,weekly,monthly',
            'recurrence_time' => 'nullable|date_format:H:i',
        ]);

        $validated['is_auto'] = false;
        $validated['end_date'] = Carbon::parse($validated['due_date'])->addDays(3);
        $validated['last_notify_time'] = null; // Initialize as null

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
        $lead = Lead::findOrFail($id);
        if ($lead->salesperson_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'content' => 'required|string',
            'date' => 'nullable|date',
            'tags' => 'nullable|array',
        ]);

        $note = $lead->notes()->create($validated);
        return response()->json(['success' => true, 'note' => $note]);
    }

    public function getAttachments($id)
    {
        $lead = Lead::with('attachments')->findOrFail($id);
        if ($lead->salesperson_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

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
            $html .= '<a href="' . route('leads.attachments.delete', ['id' => $lead->id, 'attachment' => $attachment->id]) . '" class="text-danger me-2 delete-attachment" title="Delete"><i class="bx bx-trash"></i></a>';
            $html .= '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
        $html .= '</div>';

        return $html;
    }
    public function addAttachment(Request $request, $id)
    {
        $lead = Lead::findOrFail($id);
        if ($lead->salesperson_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

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
                $path = $file->store('leads/' . $lead->id, 'public');
                LeadAttachment::create([
                    'lead_id' => $lead->id,
                    'user_id' => Auth::id(),
                    'file_size' => $file->getSize(),
                    'file_location' => $path,
                    'file_extension' => $file->getClientOriginalExtension(),
                ]);
            }
        }

        return back()->with('success', 'Attachment added successfully');
    }

    public function deleteAttachment($id, $attachmentId)
    {
        $lead = Lead::findOrFail($id);
        if ($lead->salesperson_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        $attachment = LeadAttachment::where('lead_id', $id)->findOrFail($attachmentId);
        \Storage::disk('public')->delete($attachment->file_location);
        $attachment->delete();

        return response()->json(['success' => true, 'message' => 'Attachment deleted successfully']);
    }
    public function edit($id)
    {
        $lead = Lead::with('user', 'attachments')->findOrFail($id);
        if ($lead->salesperson_id !== Auth::id() && !Auth::user()->hasRole('head-salesperson')) {
            abort(403, 'Unauthorized');
        }
        $salespeople = User::whereIn('role', ['salesperson', 'head-salesperson'])->get();
        return view('sales.lead-edit', compact('lead', 'salespeople'));
    }

    public function updateSalesperson(Request $request, $id)
    {
        \Log::info('updateSalesperson called with data: ', $request->all());
        $lead = Lead::findOrFail($id);
        $user = Auth::user();

        if (!$user->hasRole('head-salesperson')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'salesperson_id' => 'required|exists:users,id|in:' . implode(',', User::whereIn('role', ['salesperson', 'head-salesperson'])->pluck('id')->toArray()),
        ]);

        $lead->update(['salesperson_id' => $request->input('salesperson_id')]);
        return response()->json(['success' => true]);
    }

    public function updateStatus(Request $request, $id)
    {
        \Log::info('updateStatus called with data: ', $request->all());
        $lead = Lead::findOrFail($id);

        if ($lead->salesperson_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'status' => 'required|in:accept,reject,followup,new'
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
        if ($lead->salesperson_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $lead->update(['opportunity' => $request->input('opportunity')]);
        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        $lead = Lead::findOrFail($id);
        if ($lead->salesperson_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $lead->delete();
        return response()->json(['message' => 'Lead deleted successfully']);
    }

    public function update(Request $request, $id)
    {
        $lead = Lead::findOrFail($id);
        if ($lead->salesperson_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'company_phone' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'salesperson_id' => 'required|exists:users,id|in:' . implode(',', User::whereIn('role', ['salesperson', 'head-salesperson'])->pluck('id')->toArray()),
            'status' => 'required|in:accept,reject,followup,new',
            'opportunity' => 'required|in:50/50,High Chance,Low Chance,None',
            'remark' => 'nullable|string',
            'attachments' => 'nullable|array|max:10',
            'attachments.*' => 'mimes:pdf,doc,jpg,png|max:10240',
        ]);

        $lead->update([
            'salesperson_id' => $validated['salesperson_id'],
            'company_name' => $validated['company_name'],
            'company_phone' => $validated['company_phone'],
            'website' => $validated['website'],
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'],
            'status' => $validated['status'],
            'opportunity' => $validated['opportunity'],
            'remark' => $validated['remark'],
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('leads/' . $lead->id, 'public');
                LeadAttachment::create([
                    'lead_id' => $lead->id,
                    'user_id' => Auth::id(),
                    'file_size' => $file->getSize(),
                    'file_location' => $path,
                    'file_extension' => $file->getClientOriginalExtension(),
                ]);
            }
        }

        return redirect()->route('sales.leads')->with('success', 'Lead updated successfully');
    }
}
