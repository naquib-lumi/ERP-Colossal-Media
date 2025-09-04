<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('report_redo', function (Blueprint $table) {
            $table->bigIncrements('ReportID');
            $table->unsignedBigInteger('OrderID')->index();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->foreign('OrderID')
                ->references('id')->on('orders')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_redo');
    }
};
