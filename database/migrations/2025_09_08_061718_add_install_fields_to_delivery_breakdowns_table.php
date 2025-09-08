<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('delivery_breakdowns', function (Blueprint $table) {
            $table->string('deliver_install_type')->nullable()->after('method'); 
            $table->decimal('outsource_cost', 10, 2)->nullable()->after('deliver_install_type');
        });
    }

    public function down(): void
    {
        Schema::table('delivery_breakdowns', function (Blueprint $table) {
            $table->dropColumn(['deliver_install_type', 'outsource_cost']);
        });
    }
};