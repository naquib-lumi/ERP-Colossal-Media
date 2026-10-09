<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Quotations (plan item C1). A quotation belongs to one of the two issuing
     * companies and one customer (lead). It holds products, each with materials
     * picked from the material list and priced items. Every amount is typed by
     * hand; nothing is calculated. Once an order is created from it, the
     * quotation is "converted" and locked (one quotation, one order).
     *
     * Numbers are shared by both companies, e.g. ST26-0253, and come from
     * document_sequences so the client can continue their current numbering.
     */
    public function up(): void
    {
        Schema::create('document_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('key', 30);                  // e.g. quotation
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();

            $table->unique(['key', 'year']);
        });

        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quotation_number', 30)->unique();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('lead_id')->constrained('leads');
            $table->unsignedBigInteger('lead_contact_id')->nullable(); // PIC; FK added with the lead contacts table
            $table->string('attention')->nullable();                    // "Attn" as printed
            $table->foreignId('salesperson_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('quotation_date');
            $table->string('terms')->nullable();
            $table->string('po_number', 50)->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('pending');           // pending | converted
            $table->foreignId('order_id')->nullable()->unique()->constrained('orders')->nullOnDelete();
            $table->timestamp('converted_at')->nullable();
            $table->timestamp('emailed_at')->nullable();
            $table->string('emailed_to')->nullable();
            $table->timestamps();

            $table->index(['status', 'quotation_date']);
        });

        Schema::create('quotation_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained('quotations')->cascadeOnDelete();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->string('product_name');
            $table->text('description')->nullable();
            $table->json('materials')->nullable();                       // names from the material list
            $table->timestamps();
        });

        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_product_id')->constrained('quotation_products')->cascadeOnDelete();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->string('description')->nullable();
            $table->decimal('size_width', 10, 2)->nullable();
            $table->decimal('size_height', 10, 2)->nullable();
            $table->string('size_unit', 10)->nullable();                 // mm, cm, inch, ft, piece (as order items)
            $table->unsignedInteger('quantity')->default(1);
            $table->string('quantity_unit', 20)->default('pcs');          // pcs, job...
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_items');
        Schema::dropIfExists('quotation_products');
        Schema::dropIfExists('quotations');
        Schema::dropIfExists('document_sequences');
    }
};
