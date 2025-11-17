<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('materials', function (Blueprint $table) {
            if (Schema::hasColumn('materials', 'materialType')) {
                $table->dropColumn('materialType');
            }
            if (Schema::hasColumn('materials', 'unitType')) {
                $table->dropColumn('unitType');
            }
            if (!Schema::hasColumn('materials', 'material_type_id')) {
                $table->foreignId('material_type_id')->constrained('material_types')->onDelete('cascade');
            }
            // if (!Schema::hasColumn('materials', 'unit_id')) {
            //     $table->foreignId('unit_id')->constrained('units')->onDelete('cascade');
            // }
        });
    }

    public function down()
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->dropForeign(['material_type_id']);
            $table->dropForeign(['unit_id']);
            $table->dropColumn(['material_type_id', 'unit_id']);
            if (!Schema::hasColumn('materials', 'materialType')) {
                $table->string('materialType');
            }
            if (!Schema::hasColumn('materials', 'unitType')) {
                $table->string('unitType');
            }
        });
    }
};