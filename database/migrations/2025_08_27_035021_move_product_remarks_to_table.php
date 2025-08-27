<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // New table for remarks
        Schema::create('product_remarks', function (Blueprint $table) {
            $table->bigIncrements('RemarkID');
            $table->unsignedBigInteger('ProductID')->index();
            $table->enum('operation', ['printing','furnishing','installation','delivery'])->nullable(); // optional
            $table->text('remark')->nullable();
            $table->timestamps();

            $table->foreign('ProductID')->references('ProductID')->on('products')->cascadeOnDelete();
        });

        // Remove old free-text column (if it exists)
        if (Schema::hasColumn('products', 'productRemark')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('productRemark');
            });
        }
    }

    public function down(): void
    {
        // put the legacy column back (nullable so rollback never breaks)
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'productRemark')) {
                $table->text('productRemark')->nullable()->after('materialRemark');
            }
        });

        Schema::dropIfExists('product_remarks');
    }
};
