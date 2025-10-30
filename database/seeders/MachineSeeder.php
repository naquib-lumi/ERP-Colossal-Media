<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MachineSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // if you want to bind to a specific user, set here
        $defaultUserId = null; // e.g. 1

        $machines = [
            // Lamination
            ['machine_name' => 'Tempered film laminator',  'machine_type' => 'lamination'],
            ['machine_name' => 'Hot stamping laminator',    'machine_type' => 'lamination'],
            ['machine_name' => 'Ultra thin pigment lam',    'machine_type' => 'lamination'],
            ['machine_name' => 'UV laminator',              'machine_type' => 'lamination'],
            ['machine_name' => 'Digital print laminator',   'machine_type' => 'lamination'],

            // Printer
            ['machine_name' => 'HT 1 RTR 3.2',              'machine_type' => 'printer'],
            ['machine_name' => 'HT 2 HYB 3.2',              'machine_type' => 'printer'],
            ['machine_name' => 'Latex 3.2',                 'machine_type' => 'printer'],
            ['machine_name' => 'Solvent 3.2',               'machine_type' => 'printer'],
            ['machine_name' => 'L1 UV6C 1.8',               'machine_type' => 'printer'],
            ['machine_name' => 'L2 UV6C 1.8 B',             'machine_type' => 'printer'],
            ['machine_name' => 'A1 UV4C 1.8',               'machine_type' => 'printer'],
            ['machine_name' => 'YF4C 5ft',                  'machine_type' => 'printer'],
            ['machine_name' => 'HTP8C 5ft',                 'machine_type' => 'printer'],
            ['machine_name' => 'flatbed 3.2',               'machine_type' => 'printer'],
            ['machine_name' => 'Flatbed A2 DTF',            'machine_type' => 'printer'],
            ['machine_name' => 'Minolta DGFP',              'machine_type' => 'printer'],
            ['machine_name' => 'Crystal label printer',     'machine_type' => 'printer'],

            // Cutter
            ['machine_name' => 'Jinwei 1 6x10',             'machine_type' => 'cutter'],
            ['machine_name' => 'AOL1 6x10',                 'machine_type' => 'cutter'],
            ['machine_name' => 'AOL2 1000x700',             'machine_type' => 'cutter'],
            ['machine_name' => 'Router 1',                  'machine_type' => 'cutter'],
            ['machine_name' => 'Laser 1 300W',              'machine_type' => 'cutter'],
            ['machine_name' => 'Laser 2 150W',              'machine_type' => 'cutter'],
            ['machine_name' => 'Laser 3 150W',              'machine_type' => 'cutter'],
            ['machine_name' => 'Paper cutter',              'machine_type' => 'cutter'],
        ];

        $rows = collect($machines)->map(function ($m) use ($now, $defaultUserId) {
            return [
                'user_id'      => $defaultUserId,
                'machine_name' => $m['machine_name'],
                'machine_type' => $m['machine_type'],
                'created_at'   => $now,
                'updated_at'   => $now,
            ];
        })->all();

        DB::table('machines')->insert($rows);
    }
}
