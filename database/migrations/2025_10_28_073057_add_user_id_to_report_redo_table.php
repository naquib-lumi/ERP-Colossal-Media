<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_redo', function (Blueprint $table) {
            // add user_id column after OrderID
            $table->unsignedBigInteger('user_id')->after('OrderID')->nullable();

            // optional: add foreign key to users table if applicable
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('report_redo', function (Blueprint $table) {
            // drop foreign key first if added
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};