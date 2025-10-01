<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductTask;

class FurnishingController extends Controller
{
    public function dashboard(Request $request)
    {
        // Mapping for filter dropdown & pills
        $statuses = [
            'all'         => 'All Status',
            'in_progress' => 'In Progress',
            'completed'   => 'Completed',
            'pending'     => 'Pending',
            'issue'       => 'Issue',
        ];

        $search = trim((string) $request->query('q', ''));
        $status = $request->query('status', 'all');

        

        return view('furnishing.dashboard', [
            'statuses' => $statuses,
            'search'   => $search,
            'status'   => $status,
        ]);
    }

}
