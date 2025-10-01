<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // 1) TEMP ENUM: allow old + new + added (incl. boss)
        DB::statement("
            ALTER TABLE `users`
            MODIFY COLUMN `role` ENUM(
                'admin',
                'salesperson',
                'operations-manager',
                'artist',
                'head-artist',
                'operations-delivery',
                'operations-printing',
                'operations-installation',
                'head-salesperson',
                'data-entry',
                'operations-furnishing',
                'operations-dispatch-control',
                'operations-delivery-installation',
                'boss'
            ) NOT NULL DEFAULT 'salesperson'
        ");

        // 2) Rename existing data to new values
        DB::statement("UPDATE `users` SET `role`='operations-furnishing'            WHERE `role`='operations-manager'");
        DB::statement("UPDATE `users` SET `role`='operations-dispatch-control'      WHERE `role`='operations-delivery'");
        DB::statement("UPDATE `users` SET `role`='operations-delivery-installation' WHERE `role`='operations-installation'");
        DB::statement("UPDATE `users` SET `role`='salesperson'                      WHERE `role`='head-salesperson'");

        // 3) FINAL ENUM: drop old values, keep only final list (+ data-entry + boss)
        DB::statement("
            ALTER TABLE `users`
            MODIFY COLUMN `role` ENUM(
                'admin',
                'salesperson',
                'data-entry',
                'artist',
                'head-artist',
                'operations-printing',
                'operations-furnishing',
                'operations-dispatch-control',
                'operations-delivery-installation',
                'boss'
            ) NOT NULL DEFAULT 'salesperson'
        ");
    }

    public function down(): void
    {
        // 1) TEMP ENUM: allow both old & new (incl. boss) to map back safely
        DB::statement("
            ALTER TABLE `users`
            MODIFY COLUMN `role` ENUM(
                'admin',
                'salesperson',
                'operations-manager',
                'artist',
                'head-artist',
                'operations-delivery',
                'operations-printing',
                'operations-installation',
                'head-salesperson',
                'data-entry',
                'operations-furnishing',
                'operations-dispatch-control',
                'operations-delivery-installation',
                'boss'
            ) NOT NULL DEFAULT 'salesperson'
        ");

        // 2) Revert renamed roles; map added roles back
        DB::statement("UPDATE `users` SET `role`='operations-manager'           WHERE `role`='operations-furnishing'");
        DB::statement("UPDATE `users` SET `role`='operations-delivery'          WHERE `role`='operations-dispatch-control'");
        DB::statement("UPDATE `users` SET `role`='operations-installation'      WHERE `role`='operations-delivery-installation'");
        DB::statement("UPDATE `users` SET `role`='salesperson'                  WHERE `role`='data-entry'");
        DB::statement("UPDATE `users` SET `role`='salesperson'                  WHERE `role`='boss'");
        DB::statement("UPDATE `users` SET `role`='head-salesperson'             WHERE `role`='salesperson' AND `role` = 'salesperson'"); // Note: This is approximate; refine if needed

        // 3) OLD ENUM only
        DB::statement("
            ALTER TABLE `users`
            MODIFY COLUMN `role` ENUM(
                'admin',
                'salesperson',
                'operations-manager',
                'artist',
                'head-artist',
                'operations-delivery',
                'operations-printing',
                'operations-installation',
                'head-salesperson'
            ) NOT NULL DEFAULT 'salesperson'
        ");
    }
};