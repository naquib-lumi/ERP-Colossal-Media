<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class ArtistCalendarController extends Controller
{
    // GET /artist/calendar
    public function index()
    {
        return view('artist.calendar', [
            'artistId' => Auth::id(), // used by JS
        ]);
    }

    // GET /artist/calendar/events
    public function events(Request $request)
{
    $start = \Carbon\Carbon::parse($request->query('start', now()->startOfMonth()));
    $end   = \Carbon\Carbon::parse($request->query('end',   now()->endOfMonth()));

    // ---- MEETINGS: return ANY that overlap the requested range ----
    $meetings = DB::table('meetings')
        ->leftJoin('users', 'users.id', '=', 'meetings.user_id') // << add name
        ->select([
            'meetings.id',
            'meetings.title',
            'meetings.start_time','meetings.end_time',
            'meetings.new_start_time','meetings.new_end_time',
            'meetings.status','meetings.type','meetings.url','meetings.location',
            'meetings.note','meetings.lead_id','meetings.user_id',
            'users.name as artist_name', // << new
        ])
        ->where(function ($q) use ($start, $end) {
            $q->where(function($qq) use ($start,$end){
                $qq->whereNotNull('start_time')
                    ->where(function($qqq) use ($start,$end){
                        $qqq->whereBetween('start_time',[$start,$end])
                            ->orWhereBetween('end_time',[$start,$end])
                            ->orWhere(function($q4) use ($start,$end){
                                $q4->where('start_time','<=',$start)
                                ->where('end_time','>=',$start);
                            });
                    });
            })
            ->orWhere(function($qq) use ($start,$end){
                $qq->whereNotNull('new_start_time')
                    ->where(function($qqq) use ($start,$end){
                        $qqq->whereBetween('new_start_time',[$start,$end])
                            ->orWhereBetween('new_end_time',[$start,$end])
                            ->orWhere(function($q4) use ($start,$end){
                                $q4->where('new_start_time','<=',$start)
                                ->where('new_end_time','>=',$start);
                            });
                    });
            });
        })
        ->get();

    // ---- REMINDERS: any due_date in range (table has no user_id) ----
    // $reminders = DB::table('reminders')->select([
    //     'id','title','due_date','status','recurrence_type','recurrence_time','end_date','lead_id',
    // ])->whereBetween('due_date', [$start, $end])->get();

    // Map to FullCalendar events
    $events = [];

    $statusColors = [
        'scheduled' => ['bg' => '#4e73df', 'text' => '#fff'],
        'postponed' => ['bg' => '#f6c23e', 'text' => '#fff'],
        'canceled'  => ['bg' => '#000a0b', 'text' => '#fff'],
    ];

    foreach ($meetings as $m) {
        $startAt = $m->new_start_time ? \Carbon\Carbon::parse($m->new_start_time) : \Carbon\Carbon::parse($m->start_time);
        $endAt   = $m->new_end_time   ? \Carbon\Carbon::parse($m->new_end_time)   : ($m->end_time ? \Carbon\Carbon::parse($m->end_time) : (clone $startAt)->addMinutes(60));

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
                'type'        => 'meeting',
                'status'      => $m->status,
                'meeting_type'=> $m->type,
                'url'         => $m->url,
                'location'    => $m->location,
                'note'        => $m->note,
                'lead_id'     => $m->lead_id,
                'artist_id'   => $m->user_id,
                'artist_name' => $m->artist_name, // << use this in the dropdown
            ],
            'backgroundColor' => $colors['bg'],  // from your status map
            'borderColor'     => $colors['bg'],
            'textColor'       => $colors['text'],
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
