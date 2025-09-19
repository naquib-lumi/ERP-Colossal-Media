<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('orders', function (Blueprint $t) {
            $t->foreignId('data_entry_id')->nullable()
              ->constrained('users')->nullOnDelete();
        });
    }
    public function down(): void {
        Schema::table('orders', function (Blueprint $t) {
            $t->dropConstrainedForeignId('data_entry_id');
        });
    }
};
