<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->string('url')->nullable()->after('location'); // Add url column after location
        });

        // Update existing records: Move location data to url if it looks like a URL
        DB::table('meetings')->whereNotNull('location')->where('location', 'like', '%://%')->update([
            'url' => DB::raw('location'),
            'location' => null,
        ]);
    }

    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropColumn('url');
        });
    }
};