<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use App\Models\User;
use App\Models\Meeting;

class ArtistCalendarController extends Controller
{
    // GET /artist/calendar
    public function index()
    {
        $salespeople = User::query()
        ->whereIn('role', ['salesperson', 'head-salesperson']) // add 'head-salesperson' here only if that role exists in your DB
        ->orderBy('name')
        ->get(['id','name']);

        return view('artist.calendar', [
            'artistId' => Auth::id(), // used by JS
            'salespeople' => $salespeople,
        ]);
    }

    // GET /artist/calendar/events
public function events(Request $request)
{
    $start = \Carbon\Carbon::parse($request->query('start', now()->startOfMonth()));
    $end   = \Carbon\Carbon::parse($request->query('end',   now()->endOfMonth()));
    $salespersonId = (int) $request->query('salesperson_id', 0);
    $q = trim((string) $request->query('q', ''));

    $statusColors = [
        'scheduled' => ['bg' => '#4e73df', 'text' => '#fff'],
        'postponed' => ['bg' => '#f6c23e', 'text' => '#fff'],
        'canceled'  => ['bg' => '#000a0b', 'text' => '#fff'],
    ];

    $meetings = DB::table('meetings')
        ->leftJoin('users', 'users.id', '=', 'meetings.user_id')
        ->select([
            'meetings.id','meetings.title',
            'meetings.start_time','meetings.end_time',
            'meetings.new_start_time','meetings.new_end_time',
            'meetings.status','meetings.type','meetings.url',
            'meetings.location','meetings.note','meetings.lead_id',
            'meetings.user_id','users.name as artist_name',
        ])
        // any overlap with [start, end]
        ->where(function ($q1) use ($start, $end) {
            $q1->where(function ($qq) use ($start, $end) {
                $qq->whereNotNull('start_time')
                   ->where(function ($qqq) use ($start, $end) {
                       $qqq->whereBetween('start_time', [$start, $end])
                           ->orWhereBetween('end_time',   [$start, $end])
                           ->orWhere(function ($q4) use ($start, $end) {
                               $q4->where('start_time','<=',$start)->where('end_time','>=',$start);
                           });
                   });
            })->orWhere(function ($qq) use ($start, $end) {
                $qq->whereNotNull('new_start_time')
                   ->where(function ($qqq) use ($start, $end) {
                       $qqq->whereBetween('new_start_time', [$start, $end])
                           ->orWhereBetween('new_end_time',   [$start, $end])
                           ->orWhere(function ($q4) use ($start, $end) {
                               $q4->where('new_start_time','<=',$start)->where('new_end_time','>=',$start);
                           });
                   });
            });
        })
        ->when($salespersonId > 0, fn($q2) => $q2->where('meetings.user_id', $salespersonId))
        ->when($q !== '', fn($q2) => $q2->where('meetings.title', 'like', "%{$q}%"))
        ->get();

    $events = [];
    foreach ($meetings as $m) {
        $startAt = $m->new_start_time ? \Carbon\Carbon::parse($m->new_start_time)
                                      : \Carbon\Carbon::parse($m->start_time);
        $endAt   = $m->new_end_time   ? \Carbon\Carbon::parse($m->new_end_time)
                                      : ($m->end_time ? \Carbon\Carbon::parse($m->end_time)
                                                      : (clone $startAt)->addMinutes(60));
        $colors = $statusColors[$m->status] ?? ['bg' => '#6c757d', 'text' => '#fff'];

        $events[] = [
            'id'    => "meeting-{$m->id}",
            'title' => $m->title ?? 'Meeting',
            'start' => $startAt->toIso8601String(),
            'end'   => $endAt->toIso8601String(),
            'allDay'=> false,
            'backgroundColor' => $colors['bg'],
            'borderColor'     => $colors['bg'],
            'textColor'       => $colors['text'],
            'extendedProps' => [
                'type'         => 'meeting',
                'status'       => $m->status,
                'meeting_type' => $m->type,
                'url'          => $m->url,
                'location'     => $m->location,
                'note'         => $m->note,
                'lead_id'      => $m->lead_id,
                'artist_id'    => $m->user_id,
                'artist_name'  => $m->artist_name,
            ],
        ];
    }

    return response()->json($events);
}



    // All mutate endpoints disabled in view-only:
    public function storeReminder(Request $r){ abort(403,'View-only calendar'); }
    public function updateReminder(Request $r,$id){ abort(403,'View-only calendar'); }
    public function completeReminder(Request $r,$id){ abort(403,'View-only calendar'); }
    public function destroyReminder($id){ abort(403,'View-only calendar'); }
    public function storeMeeting(Request $r){ abort(403,'View-only calendar'); }
    public function updateMeeting(Request $r,$id){ abort(403,'View-only calendar'); }
    public function updateMeetingStatus(Request $r,$id){ abort(403,'View-only calendar'); }
    public function destroyMeeting($id){ abort(403,'View-only calendar'); }
}
