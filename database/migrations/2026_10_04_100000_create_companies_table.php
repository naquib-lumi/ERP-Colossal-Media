<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The companies that issue quotations and delivery orders. Both share one
     * document layout; only the logo and these details differ. The two rows are
     * inserted here so every environment has them without running a seeder.
     * Details are taken from the client's sample quotations ST26-0226 and ST26-0252.
     */
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();       // stable key, e.g. colossal-media
            $table->string('name');
            $table->string('reg_no', 30)->nullable();
            $table->text('address')->nullable();         // multi-line
            $table->string('phone', 50)->nullable();
            $table->string('fax', 50)->nullable();
            $table->string('mobile', 50)->nullable();
            $table->string('email')->nullable();
            $table->string('logo')->nullable();          // path under public/
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        $address = "No.10, Jalan Sitar Satu 33/6A,\nAlam Premier Industrial Park,\n40400 Shah Alam,\nSelangor, Malaysia.";
        $now = now();

        DB::table('companies')->insert([
            [
                'code' => 'colossal-media', 'name' => 'COLOSSAL MEDIA SDN BHD', 'reg_no' => '658233-U',
                'address' => $address, 'phone' => '03-5103 6001', 'fax' => '03-5103 6610',
                'mobile' => '019-358 2003', 'email' => 'steven.colossal@gmail.com',
                'logo' => 'assets/img/companies/colossal-media.png', 'is_default' => true,
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'code' => 'colossal-xceed', 'name' => 'COLOSSAL XCEED SDN BHD', 'reg_no' => '200801020648',
                'address' => $address, 'phone' => '03-5103 6001/6050', 'fax' => '03-5103 6610',
                'mobile' => null, 'email' => null,
                'logo' => 'assets/img/companies/colossal-xceed.png', 'is_default' => false,
                'created_at' => $now, 'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
