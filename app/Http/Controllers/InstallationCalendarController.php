<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class InstallationCalendarController extends Controller
{
    public function index()
    {
        return view('installation.calendar');
    }

    public function events(Request $request)
    {
        // Normalize range coming from FullCalendar
        $startDate = \Carbon\Carbon::parse($request->query('start', now()->startOfMonth()))->startOfDay();
        $endDate   = \Carbon\Carbon::parse($request->query('end', now()->endOfMonth()))->endOfDay();

        $rows = DB::table('products as p')
            ->leftJoin('orders as o', 'o.id', '=', 'p.OrderID')
            // artist is defined on the ORDER → get the user's name from there
            ->leftJoin('users as u', 'u.id', '=', 'o.artist_id')
            ->select([
                'p.ProductID',
                'p.OrderID',
                'p.productName',
                'p.taskType',
                'p.status',
                'p.updated_at',
                'o.order_number',
                'o.companyName as company_name',
                'u.name as artist_name',     // ← artist from orders table
            ])
            ->whereRaw("LOWER(TRIM(p.taskType)) = 'installation'")
            ->whereBetween(DB::raw('DATE(p.updated_at)'), [$startDate->toDateString(), $endDate->toDateString()])
            ->orderBy('p.updated_at')
            ->get();

        $events = $rows->map(function ($r) {
            $orderCode = $r->order_number ?: ('ORD-' . str_pad((string)($r->OrderID ?? 0), 3, '0', STR_PAD_LEFT));
            $productCode = sprintf('%s-P%04d', $orderCode, (int)$r->ProductID);

            $status = strtolower((string)$r->status);
            $palette = [
                'completed'   => ['#22c55e', '#22c55e', '#ffffff'],
                'rejected'    => ['#ef4444', '#ef4444', '#ffffff'],
                'in_progress' => ['#3b82f6', '#3b82f6', '#ffffff'],
            ];
            [$bg, $border, $text] = $palette[$status] ?? ['#3b82f6', '#3b82f6', '#ffffff'];

            return [
                'id'              => $r->ProductID,
                'title'           => $productCode,
                'start'           => \Carbon\Carbon::parse($r->updated_at)->toIso8601String(),
                'allDay'          => true,
                'backgroundColor' => $bg,
                'borderColor'     => $border,
                'textColor'       => $text,
                'extendedProps'   => [
                    'type'          => 'installation',
                    'status'        => $status,
                    'product_name'  => $r->productName,
                    'product_code'  => $productCode,
                    'company_name'  => $r->company_name,
                    'artist_name'   => $r->artist_name,    // ← pass to modal
                    'product_id'    => $r->ProductID,
                ],
            ];
        })->values();

        return response()->json($events);
    }
}
