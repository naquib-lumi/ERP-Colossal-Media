<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FurnishingController extends Controller
{
    /**
     * Dashboard list + KPI
     */
    public function dashboard(Request $request)
    {
        // KPIs
        $inProgress = DB::table('products')
            ->whereRaw('LOWER(COALESCE(taskType,"")) = "furnishing"')
            ->whereRaw('LOWER(COALESCE(status,"")) = "in_progress"')
            ->count();

        $completed  = DB::table('products')
            ->whereRaw('LOWER(COALESCE(taskType,"")) = "furnishing"')
            ->whereRaw('LOWER(COALESCE(status,"")) = "completed"')
            ->count();

        // Data table (join orders only if present)
        $jobs = DB::table('products as p')
            ->leftJoin('orders as o', 'o.id', '=', 'p.OrderID')
            ->select([
                'p.ProductID',
                'p.OrderID',
                'p.productName',
                'p.updated_at as submission_date',
                'p.status',
                'p.taskType',
                DB::raw('o.deadline as deadline'),
            ])
            ->whereRaw('LOWER(COALESCE(p.taskType,"")) = "furnishing"')
            ->whereRaw('LOWER(COALESCE(p.status,"")) = "in_progress"')
            ->orderByDesc('p.updated_at')
            ->paginate(10)
            ->withQueryString();

        return view('furnishing.dashboard', compact('inProgress','completed','jobs'));
    }

    /**
     * Mark a single furnishing job completed
     */
    public function markComplete($productId, Request $request)
    {
        $updated = DB::table('products')
            ->where('ProductID', $productId)
            ->update([
                'status'     => 'completed',
                'updated_at' => now(),
            ]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => (bool)$updated]);
        }

        return back()->with('status', $updated ? 'Marked as completed.' : 'Nothing updated.');
    }
}
