<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // --------- helpers ----------
    private function fkName(string $table, string $column): ?string
    {
        $db = DB::getDatabaseName();
        $row = DB::selectOne("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?
              AND REFERENCED_TABLE_NAME IS NOT NULL
            LIMIT 1
        ", [$db, $table, $column]);

        return $row?->CONSTRAINT_NAME;
    }

    private function indexesOn(string $table, string $column): array
    {
        $db = DB::getDatabaseName();
        $rows = DB::select("
            SELECT INDEX_NAME, NON_UNIQUE
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?
        ", [$db, $table, $column]);

        return array_map(fn($r) => $r->INDEX_NAME, $rows);
    }

    public function up(): void
    {
        // 0) Deduplicate ItemID so unique index can be added
        if (Schema::hasTable('specifications') && Schema::hasColumn('specifications', 'ItemID')) {
            $dups = DB::table('specifications')
                ->select('ItemID', DB::raw('COUNT(*) as c'), DB::raw('MIN(SpecificationID) as keep_id'))
                ->whereNotNull('ItemID')
                ->groupBy('ItemID')
                ->having('c', '>', 1)
                ->get();

            foreach ($dups as $d) {
                DB::table('specifications')
                    ->where('ItemID', $d->ItemID)
                    ->where('SpecificationID', '<>', $d->keep_id)
                    ->delete();
            }
        }

        // 1) Drop existing FK (whatever its name is)
        if (Schema::hasTable('specifications') && Schema::hasColumn('specifications', 'ItemID')) {
            if ($fk = $this->fkName('specifications', 'ItemID')) {
                DB::statement("ALTER TABLE `specifications` DROP FOREIGN KEY `{$fk}`");
            }
        }

        // 2) Drop existing indexes/unique on ItemID (any names)
        foreach ($this->indexesOn('specifications', 'ItemID') as $idx) {
            // PRIMARY cannot be dropped here; this is safe for normal indexes/uniques
            if (strtoupper($idx) !== 'PRIMARY') {
                DB::statement("ALTER TABLE `specifications` DROP INDEX `{$idx}`");
            }
        }

        // 3) Ensure column exists + type
        Schema::table('specifications', function (Blueprint $table) {
            if (Schema::hasColumn('specifications', 'ItemID')) {
                $table->unsignedBigInteger('ItemID')->nullable()->change();
            } else {
                $table->unsignedBigInteger('ItemID')->nullable()->after('SpecificationID');
            }
        });

        // 4) Add UNIQUE(ItemID) and FK to product_items(ItemID)
        Schema::table('specifications', function (Blueprint $table) {
            $table->unique('ItemID', 'specifications_itemid_unique');
        });

        // If the FK exists already under the same name this will fail, so check first
        if (!$this->fkName('specifications', 'ItemID')) {
            Schema::table('specifications', function (Blueprint $table) {
                $table->foreign('ItemID', 'specifications_itemid_fk')
                      ->references('ItemID')->on('product_items')
                      ->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        // Drop FK if present
        if ($fk = $this->fkName('specifications', 'ItemID')) {
            DB::statement("ALTER TABLE `specifications` DROP FOREIGN KEY `{$fk}`");
        }
        // Drop unique if present
        foreach ($this->indexesOn('specifications', 'ItemID') as $idx) {
            if (strtoupper($idx) !== 'PRIMARY') {
                DB::statement("ALTER TABLE `specifications` DROP INDEX `{$idx}`");
            }
        }
        // (Optionally revert column type or re-add old indexes here if you need)
    }
};
