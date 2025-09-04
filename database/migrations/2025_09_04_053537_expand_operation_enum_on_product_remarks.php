<?php
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE product_remarks
            MODIFY COLUMN operation
            ENUM('printing','furnishing','installation','delivery','courier','self_pickup')
            NULL
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE product_remarks
            MODIFY COLUMN operation
            ENUM('printing','furnishing','installation','delivery')
            NULL
        ");
    }
};
