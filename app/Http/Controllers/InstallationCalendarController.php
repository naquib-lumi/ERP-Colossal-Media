<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class InstallationCalendarController extends Controller
{
    public function index()
    {
        // 前端 Calendar 页面
        return view('installation.calendar');
    }

    public function events(Request $request)
    {
        // 假数据 – 可替换为数据库取数
        $events = [
            [
                'id'    => 1,
                'title' => 'Site Installation – Project A',
                'start' => '2025-10-01 09:00:00',
                'end'   => '2025-10-01 11:00:00',
                'backgroundColor' => '#4caf50',
                'borderColor'     => '#4caf50',
                'textColor'       => '#fff',
                'extendedProps' => [
                    'type' => 'meeting',
                    'installer_id' => 101,
                    'installer_name' => 'John Installer',
                    'status' => 'Scheduled',
                    'location' => 'KLCC Tower',
                    'note' => 'Bring required tools',
                ],
            ],
            [
                'id'    => 2,
                'title' => 'Installation Reminder – Project B',
                'start' => '2025-10-03 14:00:00',
                'end'   => '2025-10-03 15:00:00',
                'backgroundColor' => '#ff9800',
                'borderColor'     => '#ff9800',
                'textColor'       => '#fff',
                'extendedProps' => [
                    'type' => 'reminder',
                    'installer_id' => 102,
                    'installer_name' => 'Jane Installer',
                    'status' => 'Pending',
                    'note' => 'Check equipment before visiting site',
                ],
            ],
        ];

        return response()->json($events);
    }
}
