<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MaterialSeederLatest extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // Seed units (insert or ignore)
        DB::table('units')->insertOrIgnore([
            ['name' => 'perSqInch', 'label' => 'Per sq inch', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'perSqFt', 'label' => 'Per sq ft', 'created_at' => $now, 'updated_at' => $now],
        ]);

        // Seed material types (insert or ignore)
        $typeNames = [
            'Stickers / Films', 'Backlit Materials', 'Paper Materials', 'Boards / Sheets',
            'Outdoor Materials', 'Rigid Films / Sheets', 'Special Materials / Laminates'
        ];
        foreach ($typeNames as $typeName) {
            DB::table('material_types')->insertOrIgnore([
                ['name' => $typeName, 'created_at' => $now, 'updated_at' => $now]
            ]);
        }

        // Wipe materials
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('materials')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $unitId = DB::table('units')->where('name', 'perSqInch')->value('id');

        // Materials data with type mapping
        $materials = [
            // Stickers / Films
            ['PVC Blockout Sticker', 0.006900, 'Stickers / Films'],
            ['PVC White Sticker', 0.006900, 'Stickers / Films'],
            ['Synthetic Sticker', 0.006000, 'Stickers / Films'],
            ['One Way Vision Sticker', 0.007500, 'Stickers / Films'],
            ['Ultra Clear Sticker', 0.008500, 'Stickers / Films'],
            ['Special Color Sticker', 0.035000, 'Stickers / Films'],
            ['Magnetic Printable Paper', 0.006900, 'Stickers / Films'],
            ['Adhesive Magnetic Film', 0.005000, 'Stickers / Films'],
            ['Iron Base Printable PP', 0.006900, 'Stickers / Films'],

            // Backlit Materials
            ['Backlit Fabric', 0.010000, 'Backlit Materials'],
            ['Backlit Digilight', 0.060000, 'Backlit Materials'],
            ['UV Backlit Film', 0.010000, 'Backlit Materials'],

            // Paper Materials
            ['Synthetic Paper', 0.006000, 'Paper Materials'],
            ['Art Card', 0.003000, 'Paper Materials'],
            ['Boxboard Paper', 0.003000, 'Paper Materials'],
            ['Art Paper', 0.003000, 'Paper Materials'],
            ['Chipboard 1mm', 0.005200, 'Paper Materials'],
            ['Chipboard 2mm', 0.005900, 'Paper Materials'],
            ['Chipboard 3mm', 0.006900, 'Paper Materials'],

            // Boards / Sheets
            ['Paper Foam Board 5mm', 0.010000, 'Boards / Sheets'],
            ['Paper Foam Board 10mm', 0.015000, 'Boards / Sheets'],
            ['5mm Compress Foam Board', 0.010000, 'Boards / Sheets'],
            ['PVC Foam Sheet 1mm', 0.010000, 'Boards / Sheets'],
            ['PVC Foam Sheet 3mm', 0.013000, 'Boards / Sheets'],
            ['PVC Foam Sheet 5mm', 0.017000, 'Boards / Sheets'],
            ['PVC Foam Sheet 10mm', 0.026000, 'Boards / Sheets'],
            ['PVC Foam Sheet 18mm', 0.050000, 'Boards / Sheets'],
            ['PVC Foam Sheet 20mm', 0.055000, 'Boards / Sheets'],
            ['Clear Acrylic Sheet 2mm', 0.035000, 'Boards / Sheets'],
            ['Clear Acrylic Sheet 3mm', 0.042000, 'Boards / Sheets'],
            ['Clear Acrylic Sheet 5mm', 0.055000, 'Boards / Sheets'],
            ['Clear Acrylic Sheet 10mm', 0.085000, 'Boards / Sheets'],
            ['Clear Acrylic Sheet 15mm', 0.100000, 'Boards / Sheets'],
            ['Clear Acrylic Sheet 20mm', 0.220000, 'Boards / Sheets'],
            ['Corrugated Board EF', 0.007500, 'Boards / Sheets'],
            ['Corrugated Board BF', 0.008000, 'Boards / Sheets'],

            // Outdoor Materials
            ['Tarpaulin 380gsm', 0.006500, 'Outdoor Materials'],
            ['Block Up Banner', 0.010000, 'Outdoor Materials'],
            ['Mesh Fabric', 0.028000, 'Outdoor Materials'],

            // Rigid Films / Sheets
            ['Rigid Film 0.3mm / 0.5mm / 0.8mm / 1mm', 0.020000, 'Rigid Films / Sheets'],

            // Special Materials / Laminates
            ['Crystal Label', 0.350000, 'Special Materials / Laminates'],
            ['UV Matt Laminate', 0.015000, 'Special Materials / Laminates'],
            ['UV Gloss Laminate', 0.015000, 'Special Materials / Laminates'],
            ['Pigment Crystal Matte Laminate', 0.012000, 'Special Materials / Laminates'],
            ['Pigment Crystal Gloss Laminate', 0.012000, 'Special Materials / Laminates'],
            ['Tempered Gloss Laminate', 0.005000, 'Special Materials / Laminates'],
            ['Tempered Matte Laminate', 0.005000, 'Special Materials / Laminates'],
        ];

        $rows = [];
        foreach ($materials as [$name, $cost, $typeName]) {
            $typeId = DB::table('material_types')->where('name', $typeName)->value('id');
            if (!$typeId) continue; // Skip if type not found
            $rows[] = [
                'materialName' => $name,
                'material_type_id' => $typeId,
                'unit_id' => $unitId,
                'unitCost' => $cost,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('materials')->insert($rows);
    }
}