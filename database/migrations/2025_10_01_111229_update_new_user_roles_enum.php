<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // 1) TEMP ENUM: allow old + new + head-salesperson
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

        // 2) Rename old roles to new standardized names
        DB::statement("UPDATE `users` SET `role`='operations-furnishing'            WHERE `role`='operations-manager'");
        DB::statement("UPDATE `users` SET `role`='operations-dispatch-control'      WHERE `role`='operations-delivery'");
        DB::statement("UPDATE `users` SET `role`='operations-delivery-installation' WHERE `role`='operations-installation'");
        // NOTE: Do NOT overwrite 'head-salesperson', keep existing ones intact

        // 3) FINAL ENUM: keep all needed roles including head-salesperson
        DB::statement("
            ALTER TABLE `users`
            MODIFY COLUMN `role` ENUM(
                'admin',
                'salesperson',
                'head-salesperson',
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
        // 1) TEMP ENUM to map back safely
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

        // 2) Revert renamed roles
        DB::statement("UPDATE `users` SET `role`='operations-manager'           WHERE `role`='operations-furnishing'");
        DB::statement("UPDATE `users` SET `role`='operations-delivery'          WHERE `role`='operations-dispatch-control'");
        DB::statement("UPDATE `users` SET `role`='operations-installation'      WHERE `role`='operations-delivery-installation'");

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
