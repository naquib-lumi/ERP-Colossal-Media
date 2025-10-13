<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('installation_proofs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('ProductID')->index();
            $table->unsignedBigInteger('OrderID')->nullable()->index();
            $table->string('file_path');        // storage path
            $table->string('original_name');    // original file name
            $table->string('mime', 100)->nullable();
            $table->unsignedBigInteger('size')->nullable(); // bytes
            $table->unsignedBigInteger('uploaded_by')->nullable()->index();
            $table->timestamps();

            // optional FKs if you want:
            $table->foreign('ProductID')->references('ProductID')->on('products')->cascadeOnDelete();
            $table->foreign('OrderID')->references('id')->on('orders')->nullOnDelete();
        });
    }
    public function down(): void {
        Schema::dropIfExists('installation_proofs');
    }
};