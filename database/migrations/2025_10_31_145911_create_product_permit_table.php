<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_permit', function (Blueprint $table) {
            // 主键
            $table->bigIncrements('id');

            // 外键：users.id
            $table->unsignedBigInteger('user_id');

            // 外键：products.ProductID（注意是自定义主键名）
            $table->unsignedBigInteger('product_id');

            // 存储文件路径（图片或PDF的相对路径，如 storage/app/public/permits/xxx.pdf）
            $table->string('permit_file', 512);

            // 可选：记录上传时间
            $table->timestamp('uploaded_at')->nullable();

            $table->timestamps();

            // 外键约束
            $table->foreign('user_id')
                  ->references('id')->on('users')
                  ->onDelete('cascade');

            $table->foreign('product_id')
                  ->references('ProductID')->on('products')
                  ->onDelete('cascade');

            // 常用索引（查询或避免重复时有用）
            $table->index(['user_id', 'product_id']);

            // 如果同一用户对同一产品只允许一条记录，启用唯一约束：
            // $table->unique(['user_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_permit');
    }
};