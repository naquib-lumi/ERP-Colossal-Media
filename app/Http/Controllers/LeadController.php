<?php

namespace App\Http\Controllers;

use App\Models\Lead;
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

        $leads = $user->leads()->with('user')->get();
        return view('sales.lead-management', compact('leads'));
    }

    public function getLeads(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->hasRole('salesperson')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $leads = Lead::where('salesperson_id', $user->id)->with('user');

        return DataTables::of($leads)
            ->addColumn('actions', function ($lead) {
                return '<div class="d-flex gap-2">
                            <button class="btn btn-sm btn-primary edit-lead" data-id="' . $lead->id . '">Edit</button>
                            <button class="btn btn-sm btn-danger delete-lead" data-id="' . $lead->id . '">Delete</button>
                        </div>';
            })
            ->editColumn('date', function ($lead) {
                return $lead->date ? $lead->date->format('Y-m-d') : 'No Reminder';
            })
            ->rawColumns(['actions'])
            ->toJson();
    }

    public function destroy($id)
    {
        $lead = Lead::findOrFail($id);
        if ($lead->salesperson_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $lead->delete();
        return response()->json(['message' => 'Lead deleted successfully']);
    }
}