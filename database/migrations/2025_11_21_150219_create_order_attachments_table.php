<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Order;
use App\Models\OrderAttachment;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up()
    {
        Schema::create('order_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('file_path');
            $table->string('original_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->timestamps();
        });

        // Migrate all old comma-separated files
        Order::whereNotNull('orderAttachment')
            ->where('orderAttachment', '!=', '')
            ->chunk(200, function ($orders) {
                foreach ($orders as $order) {
                    $paths = array_filter(explode(',', $order->orderAttachment));
                    foreach ($paths as $path) {
                        $path = trim($path);
                        if ($path && Storage::disk('public')->exists($path)) {
                            OrderAttachment::create([
                                'order_id'      => $order->id,
                                'user_id'       => $order->salesperson_id ?? 1, // fallback to admin/user 1
                                'file_path'     => $path,
                                'original_name' => basename($path),
                                'mime_type'     => Storage::disk('public')->mimeType($path),
                                'size'          => Storage::disk('public')->size($path),
                                'created_at'    => $order->created_at,
                                'updated_at'    => $order->updated_at,
                            ]);
                        }
                    }
                }
            });

        // Optional: clear old column after successful migration
        // Order::query()->update(['orderAttachment' => null]);
    }

    public function down()
    {
        Schema::dropIfExists('order_attachments');
    }
};