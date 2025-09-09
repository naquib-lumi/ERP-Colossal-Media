<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneNotifications extends Command
{
    protected $signature = 'notifications:prune';
    protected $description = 'Prune old read notifications';

    public function handle()
    {
        DB::table('notifications')
            ->whereNotNull('read_at')
            ->where('read_at', '<', now()->subDays(30)) // e.g., 30 days
            ->delete();
    }
}