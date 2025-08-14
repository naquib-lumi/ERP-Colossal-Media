<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // 0) Drop any existing FK on user_id (older migrations)
        try { Schema::table('orders', fn (Blueprint $t) => $t->dropForeign(['user_id'])); } catch (\Throwable $e) {}
        try { DB::statement('ALTER TABLE `orders` DROP FOREIGN KEY `orders_user_id_foreign`'); } catch (\Throwable $e) {}

        // 1) Ensure user_id is NULLABLE BEFORE any rename
        try { DB::statement('ALTER TABLE `orders` MODIFY `user_id` BIGINT UNSIGNED NULL'); }
        catch (\Throwable $e) { DB::statement('ALTER TABLE `orders` CHANGE `user_id` `user_id` BIGINT UNSIGNED NULL'); }

        // 2) Add artist_id (nullable)
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'artist_id')) {
                $table->unsignedBigInteger('artist_id')->nullable()->after('id');
            }
        });

        // 3) Backfill artist_id from user_id (existing data)
        DB::table('orders')->update(['artist_id' => DB::raw('user_id')]);

        // 4) Rename user_id -> salesperson_id
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'user_id') && !Schema::hasColumn('orders', 'salesperson_id')) {
                $table->renameColumn('user_id', 'salesperson_id');
            }
        });

        // 5) Double-ensure salesperson_id is nullable (post-rename)
        try { DB::statement('ALTER TABLE `orders` MODIFY `salesperson_id` BIGINT UNSIGNED NULL'); }
        catch (\Throwable $e) { DB::statement('ALTER TABLE `orders` CHANGE `salesperson_id` `salesperson_id` BIGINT UNSIGNED NULL'); }

        // 6) Add FKs with SET NULL
        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('artist_id')
                  ->references('id')->on('users')
                  ->onDelete('set null');

            $table->foreign('salesperson_id')
                  ->references('id')->on('users')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        // Drop the FKs if present
        try { Schema::table('orders', fn (Blueprint $t) => $t->dropForeign(['artist_id'])); } catch (\Throwable $e) {}
        try { Schema::table('orders', fn (Blueprint $t) => $t->dropForeign(['salesperson_id'])); } catch (\Throwable $e) {}
        try { DB::statement('ALTER TABLE `orders` DROP FOREIGN KEY `orders_artist_id_foreign`'); } catch (\Throwable $e) {}
        try { DB::statement('ALTER TABLE `orders` DROP FOREIGN KEY `orders_salesperson_id_foreign`'); } catch (\Throwable $e) {}

        // Rename salesperson_id back to user_id
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'salesperson_id')) {
                $table->renameColumn('salesperson_id', 'user_id');
            }
        });

        // Ensure user_id exists and is nullable (to recreate original FK if needed)
        try { DB::statement('ALTER TABLE `orders` MODIFY `user_id` BIGINT UNSIGNED NULL'); }
        catch (\Throwable $e) { DB::statement('ALTER TABLE `orders` CHANGE `user_id` `user_id` BIGINT UNSIGNED NULL'); }

        // Drop artist_id
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'artist_id')) {
                $table->dropColumn('artist_id');
            }
        });

        // Optionally re-add original FK on user_id
        try {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            });
        } catch (\Throwable $e) {}
    }
};
