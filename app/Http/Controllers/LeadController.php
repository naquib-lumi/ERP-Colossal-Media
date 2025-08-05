<?php

namespace App\Http\Controllers;

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

        $leads = $user->leads()->with('user')->latest()->get(); // Sort by latest first
        return view('sales.lead-management', compact('leads'));
    }

    public function getLeads(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->hasRole('salesperson')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $leads = Lead::where('salesperson_id', $user->id)->with('user', 'attachments');

        // Apply filters
        if ($request->has('search') && $request->input('search')['value']) {
            $search = $request->input('search')['value'];
            $leads->where(function ($query) use ($search) {
                $query->where('company_name', 'like', "%{$search}%")
                      ->orWhere('name', 'like', "%{$search}%");
            });
        }
        if ($request->has('status') && $request->input('status') != '') {
            $leads->where('status', $request->input('status'));
        }

        return DataTables::of($leads)
            ->addColumn('lead_data', function ($lead) {
                return $lead->id . '<br>' .
                       '<select class="form-select form-select-sm status-dropdown" data-id="' . $lead->id . '">
                            <option value="accepted" ' . ($lead->status == 'accepted' ? 'selected' : '') . '>Accepted</option>
                            <option value="rejected" ' . ($lead->status == 'rejected' ? 'selected' : '') . '>Rejected</option>
                            <option value="followup" ' . ($lead->status == 'followup' ? 'selected' : '') . '>Followup</option>
                        </select><br>' .
                       'Opportunity: 50/50'; // Placeholder; adjust logic if needed
            })
            ->addColumn('company_details', function ($lead) {
                return '<i class="bx bxs-building"></i> ' . $lead->company_name . '<br>' .
                       '<i class="bx bxs-phone"></i> ' . ($lead->company_phone ?? 'N/A') . '<br>' .
                       '<i class="bx bxs-globe"></i> ' . ($lead->website ?? 'N/A') . '<br>' .
                       '<button class="btn btn-sm btn-info view-attachments" data-id="' . $lead->id . '">View Attachments</button>';
            })
            ->addColumn('lead_details', function ($lead) {
                return $lead->name . '<br>' .
                       $lead->phone . '<br>' .
                       $lead->email . '<br>' .
                       ($lead->remark ?? 'No remark');
            })
            ->addColumn('assigned_to', function ($lead) {
                return $lead->user->name ?? 'Not Assigned';
            })
            ->addColumn('reminder', function ($lead) {
                $reminderText = $lead->date ? $lead->date->format('Y-m-d') : 'No Reminder';
                return '<a href="#" class="confirm-reminder" data-id="' . $lead->id . '" data-confirmed="' . ($lead->date ? '1' : '0') . '">' . ($lead->date && $lead->date->isPast() ? '<s>' . $reminderText . '</s>' : $reminderText) . '</a>';
            })
            ->addColumn('actions', function ($lead) {
                return '<div class="d-flex gap-2">
                            <a href="/leads/' . $lead->id . '/edit" class="btn btn-sm btn-primary">Edit</a>
                            <a href="/leads/' . $lead->id . '" class="btn btn-sm btn-secondary">View</a>
                            <form action="/leads/' . $lead->id . '" method="POST" style="display:inline;" onsubmit="return confirm(\'Are you sure?\');">
                                @csrf
                                @method("DELETE")
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </div>';
            })
            ->rawColumns(['lead_data', 'company_details', 'reminder', 'actions'])
            ->toJson();
    }

    public function show($id)
{
    $lead = Lead::with('user', 'attachments')->findOrFail($id);
    if ($lead->salesperson_id !== Auth::id()) {
        abort(403, 'Unauthorized');
    }
    return view('sales.lead-view', compact('lead'));
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
        $lead = Lead::findOrFail($id);
        if ($lead->salesperson_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $lead->update(['status' => $request->input('status')]);
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