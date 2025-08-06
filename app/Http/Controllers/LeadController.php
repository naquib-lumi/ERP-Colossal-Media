<?php

namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Lead;
use App\Models\LeadAttachment;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function leadManagement()
    {
        $user = Auth::user();
        if (!$user->hasRole('salesperson')) {
            abort(403, 'Unauthorized');
        }

        $leads = $user->leads()->with('user')->latest()->get(); // For initial load, if needed
        return view('sales.lead-management', compact('leads'));
    }

    public function getLeads(Request $request)
    {
        \Log::info('getLeads called');
        $user = Auth::user();
        if (!$user || !$user->hasRole('salesperson')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $leads = Lead::where('salesperson_id', $user->id)->with('user', 'attachments');

        if ($request->has('search') && $request->input('search')['value']) {
            $search = $request->input('search')['value'];
            $leads->where(function ($query) use ($search) {
                $query->where('company_name', 'like', "%{$search}%")
                      ->orWhere('name', 'like', "%{$search}%")
                      ->orWhere('id', 'like', "%{$search}%");
            });
        }

        return DataTables::of($leads)
        ->addColumn('lead_data', function ($lead) {
    return '<div class="lead-data-cell">' .
           '<span class="lead-id">' . $lead->id . '</span><br>' .
           '<select class="form-select form-select-sm status-dropdown" data-id="' . $lead->id . '">' .
           '<option value="accept" ' . ($lead->status == 'accept' ? 'selected' : '') . '>Accept</option>' .
           '<option value="reject" ' . ($lead->status == 'reject' ? 'selected' : '') . '>Reject</option>' .
           '<option value="followup" ' . ($lead->status == 'followup' ? 'selected' : '') . '>Followup</option>' .
           '<option value="new" ' . ($lead->status == 'new' ? 'selected' : '') . '>New</option>' .
           '</select><br>' .
           '<select class="form-select form-select-sm opportunity-dropdown" data-id="' . $lead->id . '">' .
           '<option value="50/50" ' . ($lead->opportunity == '50/50' ? 'selected' : '') . '>50/50</option>' .
           '<option value="High Chance" ' . ($lead->opportunity == 'High Chance' ? 'selected' : '') . '>High Chance</option>' .
           '<option value="Low Chance" ' . ($lead->opportunity == 'Low Chance' ? 'selected' : '') . '>Low Chance</option>' .
           '<option value="None" ' . ($lead->opportunity == 'None' ? 'selected' : '') . '>None</option>' .
           '</select>' .
           '</div>';
})
            ->addColumn('company_details', function ($lead) {
                return '<div class="company-details-cell">' .
                       '<i class="bx bxs-building"></i> ' . $lead->company_name . '<br>' .
                       '<i class="bx bxs-phone"></i> ' . ($lead->company_phone ?? 'N/A') . '<br>' .
                       '<i class="bx bx-globe"></i> ' . ($lead->website ?? 'N/A') . '<br>' .
                       '<button class="btn btn-sm btn-info view-attachments" data-id="' . $lead->id . '">View Attachments</button>' .
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
            ->addColumn('assigned_artist', function ($lead) {
                return $lead->user->name ?? 'Not Assigned';
            })
            ->addColumn('reminder', function ($lead) {
                $reminderText = $lead->date ? $lead->date->format('Y-m-d') : 'No Reminder';
                return '<a href="#" class="confirm-reminder" data-id="' . $lead->id . '" data-confirmed="' . ($lead->date ? '1' : '0') . '">' .
                       ($lead->date && $lead->date->isPast() ? '<s>' . $reminderText . '</s>' : $reminderText) .
                       '</a>';
            })
            ->addColumn('actions', function ($lead) {
                return '<div class="actions-cell d-flex gap-2">' .
                       '<a href="/leads/' . $lead->id . '/edit" class="btn btn-sm btn-primary">Edit</a>' .
                       '<a href="/leads/' . $lead->id . '" class="btn btn-sm btn-secondary">View</a>' .
                       '<form action="/leads/' . $lead->id . '" method="POST" style="display:inline;" onsubmit="return confirm(\'Are you sure?\');">' .
                       '<input type="hidden" name="_token" value="' . csrf_token() . '">' .
                       '<input type="hidden" name="_method" value="DELETE">' .
                       '<button type="submit" class="btn btn-sm btn-danger">Delete</button>' .
                       '</form>' .
                       '</div>';
            })
            ->rawColumns(['lead_data', 'company_details', 'lead_details', 'reminder', 'actions'])
            ->toJson();
    }

    public function create()
    {
        $user = Auth::user();
        if (!$user->hasRole('salesperson')) {
            abort(403, 'Unauthorized');
        }
        $artists = User::where('role', 'artist')->get();
        return view('sales.add-lead', compact('artists'));
    }
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasRole('salesperson')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'company_phone' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'salesperson_id' => 'required|exists:users,id',
            'date' => 'nullable|date',
            'status' => 'required|in:accepted,rejected,followup',
            'opportunity' => 'nullable|in:50/50,High Chance,Low Chance,None',
            'remark' => 'nullable|string',
            'attachments' => 'nullable|array|max:10|mimes:pdf,doc,jpg,png|max:10240',
        ]);

        $lead = $user->leads()->create([
            'salesperson_id' => $validated['salesperson_id'],
            'company_name' => $validated['company_name'],
            'company_phone' => $validated['company_phone'],
            'website' => $validated['website'],
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'],
            'date' => $validated['date'],
            'status' => $validated['status'],
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
        $lead = Lead::with('user', 'attachments', 'reminders', 'notes')->findOrFail($id);
        if ($lead->salesperson_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }
        return view('sales.lead-view', compact('lead'));
    }

    public function addReminder(Request $request, $id)
    {
        $lead = Lead::findOrFail($id);
        if ($lead->salesperson_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'due_date' => 'required|date',
            'status' => 'required|in:overdue,upcoming,completed',
        ]);

        $reminder = $lead->reminders()->create($validated);
        return response()->json(['success' => true, 'reminder' => $reminder]);
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
        $html = '<ul>';
        foreach ($lead->attachments as $attachment) {
            $html .= '<li>' . $attachment->file_location . ' (' . $attachment->file_extension . ', ' . $attachment->file_size . ' bytes)</li>';
        }
        $html .= '</ul>';
        return $html;
    }

    public function edit($id)
    {
        $lead = Lead::with('user', 'attachments')->findOrFail($id);
        if ($lead->salesperson_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }
        return view('sales.lead-edit', compact('lead'));
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

    public function confirmReminder(Request $request, $id)
    {
        $lead = Lead::findOrFail($id);
        if ($lead->salesperson_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $lead->update(['date' => null]); // Clear date to mark as done
        return response()->json(['success' => true, 'reminderText' => 'No Reminder']);
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
}