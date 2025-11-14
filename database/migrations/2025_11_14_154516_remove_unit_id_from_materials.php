// database/migrations/xxxx_xx_xx_remove_unit_id_from_materials.php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
public function up(): void
{
    Schema::table('materials', function (Blueprint $table) {
        // $table->dropForeign(['unit_id']);
        $table->dropColumn('unit_id');
    });
}
    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->foreignId('unit_id')->constrained('units')->onDelete('cascade');
        });
    }
};