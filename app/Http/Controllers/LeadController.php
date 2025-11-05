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



use Carbon\Carbon;

class LeadController extends Controller
{
   public function leadManagement()
{
    $user = Auth::user();

    if (!($user->hasRole('salesperson') || $user->hasRole('head-salesperson'))) {
        abort(403, 'Unauthorized');
    }

    $salespeople = User::whereIn('role', ['salesperson', 'head-salesperson'])->get();

    if ($user->hasRole('head-salesperson')) {
        // Head-salesperson can see all leads
        $leads = Lead::with('user')->latest()->get();
    } else {
        // Salesperson only sees their own leads
        $leads = $user->leads()->with('user')->latest()->get();
    }

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
    if (!($user->hasRole('salesperson') || $user->hasRole('head-salesperson'))) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    $query = $request->input('query');
    if (!$query || strlen($query) < 2) {
        return response()->json([]);
    }

    // Start with base query
    $leadsQuery = Lead::query();

    // Restrict only if normal salesperson
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




   public function getLeads(Request $request)
{
    \Log::info('getLeads called for user: ' . Auth::user()->email);
    $user = Auth::user();

    if (!$user || !($user->hasRole('salesperson') || $user->hasRole('head-salesperson'))) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    $salespeople = User::whereIn('role', ['salesperson', 'head-salesperson'])->get();
    $leads = Lead::with('user', 'attachments', 'reminders', 'notes')->orderBy('created_at', 'desc');

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
            $dropdown = '<select class="form-select form-select-sm status-dropdown" data-id="' . $lead->id . '" style="white-space: nowrap; width: auto;">';
            $dropdown .= '<option value="accept" ' . ($lead->status == 'accept' ? 'selected' : '') . '>Accept</option>';
            $dropdown .= '<option value="reject" ' . ($lead->status == 'reject' ? 'selected' : '') . '>Reject</option>';
            $dropdown .= '<option value="followup" ' . ($lead->status == 'followup' ? 'selected' : '') . '>Followup</option>';
            $dropdown .= '<option value="meeting" ' . ($lead->status == 'meeting' ? 'selected' : '') . '>Meeting</option>';
            $dropdown .= '<option value="new" ' . ($lead->status == 'new' ? 'selected' : '') . '>New</option>';
            $dropdown .= '</select><br>';

            $opportunityDropdown = '<select class="form-select form-select-sm opportunity-dropdown" data-id="' . $lead->id . '" style="white-space: nowrap; width: auto;">';
            $opportunityDropdown .= '<option value="50/50" ' . ($lead->opportunity == '50/50' ? 'selected' : '') . '>50/50</option>';
            $opportunityDropdown .= '<option value="High Chance" ' . ($lead->opportunity == 'High Chance' ? 'selected' : '') . '>High Chance</option>';
            $opportunityDropdown .= '<option value="Low Chance" ' . ($lead->opportunity == 'Low Chance' ? 'selected' : '') . '>Low Chance</option>';
            $opportunityDropdown .= '<option value="None" ' . ($lead->opportunity == 'None' ? 'selected' : '') . '>None</option>';
            $opportunityDropdown .= '</select>';

            return '<div class="lead-data-cell" style="white-space: nowrap;">' .
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
                $latestNote = trim($lead->notes->last()->content ?? '');
                $html = '<div class="lead-details-cell">' .
                    '<div class="d-flex align-items-center mb-1"><i class="bx bxs-user me-2"></i>' . $lead->name . '</div>' .
                    '<div class="d-flex align-items-center mb-1"><i class="bx bxs-phone me-2"></i>' . ($lead->phone ?? 'N/A') . '</div>' .
                    '<div class="d-flex align-items-center mb-1"><i class="bx bx-envelope me-2"></i>' . ($lead->email ?? 'N/A') . '</div>';
                if ($latestNote) {
                    $html .= '<div class="d-flex align-items-center mb-1"><i class="bx bx-note me-2"></i>' . $latestNote . '</div>';
                }
                $html .= '</div>';
                return $html;
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
        ->addColumn('reminder', function ($lead) use ($user) {
    $reminders = $lead->reminders()
        ->where('created_by', $user->id)
        ->where('status', '!=', 'completed')
        ->orderByRaw("FIELD(status, 'upcoming', 'overdue')")
        ->orderBy('remind_at', 'asc')
        ->take(5)
        ->get();

    if ($reminders->isEmpty()) {
        return '<span class="text-muted">No Reminders</span>';
    }

    $html = '<ul class="list-unstyled">';
    foreach ($reminders as $reminder) {
        $dueDate = $reminder->remind_at;

        if ($dueDate->isPast() && $reminder->status == 'upcoming') {
            $reminder->status = 'overdue';
            $reminder->save();
            $relativeTime = 'Overdue (' . $dueDate->format('Y-m-d H:i') . ')';
        } else {
            $relativeTime = $dueDate->diffForHumans();
        }

        $colorClass = '';
        if ($reminder->status == 'overdue') {
            $colorClass = 'text-danger';
        } elseif ($dueDate->diffInHours() <= 24) {
            $colorClass = 'text-warning';
        } else {
            $colorClass = 'text-success';
        }

        $html .= '<li>';
        $html .= '<a href="#" class="confirm-reminder ' . $colorClass . '" data-id="' . $lead->id . '" data-reminder-id="' . $reminder->id . '" data-title="' . htmlspecialchars($reminder->title) . '">' . htmlspecialchars($reminder->title) . ' (' . $relativeTime . ')</a>';
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
        'company_name' => [
        'required',
        'string',
        'max:255',
        Rule::unique('leads')->where(function ($query) use ($request) {
            $query->whereRaw('LOWER(company_name) = LOWER(?)', [$request->company_name]);
        }),
    ],
        'company_phone' => 'nullable|string|regex:/^[0-9+\-\s()]+$/|max:20',
        'website' => 'nullable|string|max:255',
        'name' => 'required|string|max:255',
        'phone' => 'required|string|regex:/^[0-9+\-\s()]+$/|max:20',
        'email' => 'nullable|email|max:255',
        'salesperson_id' => 'required|exists:users,id|in:' . implode(',', User::whereIn('role', ['salesperson', 'head-salesperson'])->pluck('id')->toArray()),
        'opportunity' => 'required|in:50/50,High Chance,Low Chance,None',
        'remark' => 'nullable|string',
        'attachments' => 'nullable|array|max:10',
        'attachments.*' => 'mimes:pdf,doc,jpg,png|max:10240',
    ]);

    $salesperson_id = $user->hasRole('salesperson') ? $user->id : $validated['salesperson_id'];

    $lead = Lead::create([
        'salesperson_id' => $salesperson_id,
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
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $file->getClientOriginalExtension();
            $timestamp = now()->format('Ymd_His');
            $newName = $originalName . '_' . $timestamp . '.' . $extension;
            $path = $file->storeAs('leads/' . $lead->id, $newName, 'public');
            LeadAttachment::create([
                'lead_id' => $lead->id,
                'user_id' => $user->id,
                'file_size' => $file->getSize(),
                'file_location' => $path,
                'file_extension' => $extension,
            ]);
        }
    }

    return redirect()->route('sales.leads')->with('success', 'Lead added successfully');
}

public function exportCsv(Request $request)
{
    $user = Auth::user();
    if (!$user->hasRole('salesperson') && !$user->hasRole('head-salesperson')) {
        abort(403, 'Unauthorized');
    }

    $leads = Lead::with('user')->orderBy('created_at', 'desc');

    if ($user->hasRole('salesperson')) {
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

    if ($user->hasRole('head-salesperson') && $request->has('salesperson_id') && $request->input('salesperson_id')) {
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

public function show($id)
{
    $user = Auth::user();

    $lead = Lead::with([
        'user', 'attachments', 'notes.attachments',
        'reminders' => fn($q) => $q->where('created_by', $user->id)->orderBy('remind_at')->take(10),
        'orders',
        'meetings' => fn($q) => $q->orderBy('start_time')
    ])->findOrFail($id);

    if ($user->hasRole('salesperson') && $lead->salesperson_id !== $user->id) {
        abort(403, 'Unauthorized');
    }

    return view('sales.lead-view', compact('lead'));
}


    public function addReminder(Request $request, $id)
{
    \Log::info('addReminder called for lead ID: ' . $id, $request->all());

    $user = Auth::user();
    $lead = Lead::findOrFail($id);

    // Restrict normal salesperson but allow head-salesperson (and admin if needed)
    if ($user->hasRole('salesperson') && $lead->salesperson_id !== $user->id) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

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

    if ($user->hasRole('salesperson') && $lead->salesperson_id !== $user->id) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

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
    
    if ($user->hasRole('salesperson') && $note->user_id !== $user->id) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }
    
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
        if ($user->hasRole('salesperson') && $lead->salesperson_id !== $user->id) {
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
    $user = Auth::user();
    $lead = Lead::findOrFail($id);
    if ($user->hasRole('salesperson') && $lead->salesperson_id !== $user->id) {
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
    if ($user->hasRole('salesperson') && $lead->salesperson_id !== $user->id) {
        abort(403, 'Unauthorized');
    }
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
    $lead = Lead::with('user', 'attachments')->findOrFail($id);
    $this->authorizeLeadAccess($lead);

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

    $oldSalespersonId = $lead->salesperson_id;
    $lead->update(['salesperson_id' => $request->input('salesperson_id')]);
    
    $newSalesperson = User::find($request->salesperson_id);
    $message = "You have been assigned to lead '{$lead->name}' ({$lead->company_name}) by {$user->name}.";
    $url = route('leads.show', $lead->id);
    Helpers::notify($newSalesperson, $message, $url);
    
    // Optional: Notify old salesperson if changed
    if ($oldSalespersonId && $oldSalespersonId != $request->salesperson_id) {
        $oldSalesperson = User::find($oldSalespersonId);
        $oldMessage = "Lead '{$lead->name}' has been reassigned from you to {$newSalesperson->name}.";
        $url = route('sales.leads');
        Helpers::notify($oldSalesperson, $oldMessage, $url);
    }

    return response()->json(['success' => true]);
}

  public function updateStatus(Request $request, $id)
{
    \Log::info('updateStatus called with data: ', $request->all());

    $lead = Lead::findOrFail($id);
    $user = Auth::user();

    // Salesperson can only update their own leads
    // Head-salesperson can update any lead
    if ($user->hasRole('salesperson') && $lead->salesperson_id !== $user->id) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

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

    
    if ($user->hasRole('salesperson') && $lead->salesperson_id !== $user->id) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    $lead->update(['opportunity' => $request->input('opportunity')]);

    return response()->json(['success' => true]);
}

public function destroy($id)
{
    $lead = Lead::findOrFail($id);
    $user = Auth::user();

    // Salesperson can only delete their own leads
    // Head-salesperson can delete any lead
    if ($user->hasRole('salesperson') && $lead->salesperson_id !== $user->id) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    $lead->delete();

    return response()->json(['message' => 'Lead deleted successfully']);
}


public function update(Request $request, $id)
{
    $lead = Lead::findOrFail($id);
    $user = Auth::user();

    // Salesperson can only update their own leads
    // Head-salesperson can update any lead
    if ($user->hasRole('salesperson') && $lead->salesperson_id !== $user->id) {
        abort(403, 'Unauthorized');
    }

    $validated = $request->validate([
        'company_name' => 'required|string|max:255',
        'company_phone' => 'nullable|string|regex:/^[0-9+\-\s()]+$/|max:20',
        'website' => 'nullable|string|max:255',
        'name' => 'required|string|max:255',
        'phone' => 'required|string|regex:/^[0-9+\-\s()]+$/|max:20',
        'email' => 'nullable|email|max:255',
        'salesperson_id' => 'required|exists:users,id|in:' . implode(',', User::whereIn('role', ['salesperson', 'head-salesperson'])->pluck('id')->toArray()),
        'status' => 'required|in:accept,reject,followup,new,meeting',
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
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $file->getClientOriginalExtension();
            $timestamp = now()->format('Ymd_His');
            $newName = $originalName . '_' . $timestamp . '.' . $extension;
            $path = $file->storeAs('leads/' . $lead->id, $newName, 'public');
            LeadAttachment::create([
                'lead_id' => $lead->id,
                'user_id' => $user->id,
                'file_size' => $file->getSize(),
                'file_location' => $path,
                'file_extension' => $extension,
            ]);
        }
    }

    $redirectRoute = $request->get('highlight') == 'remark' ? route('leads.show', $id) : route('sales.leads');
    return redirect($redirectRoute)->with('success', 'Lead updated successfully');
}

}