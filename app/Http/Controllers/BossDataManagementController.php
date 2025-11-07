<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\MaterialType;
use App\Models\Unit;
use Illuminate\Http\Request;

class BossDataManagementController extends Controller
{
    /**
     * Cost Data index (Boss view)
     */
    public function index(Request $request)
    {
        // preload for client-side filtering
        $perPage = 10;

        $materials = Material::with(['materialType:id,name', 'unit:id,label'])
            ->orderBy('materialName')
            ->paginate($perPage); // Laravel auto-handles ?page query

        $types = MaterialType::orderBy('name')->pluck('name', 'id');
        $units = Unit::orderByRaw('COALESCE(label, name)')->pluck('label', 'id');

        return view('boss.datamanagement', compact('materials', 'types', 'units'));
    }

    // ---------- Types ----------
    public function storeType(Request $request)
    {
        $request->validate([
            'typeName' => 'required|string|max:255|unique:material_types,name'
        ]);
        $type = MaterialType::create(['name' => $request->typeName]);
        return response()->json(['success' => true, 'type' => $type->name, 'id' => $type->id]);
    }

    public function updateType(Request $request, $id)
    {
        $type = MaterialType::findOrFail($id);
        $request->validate([
            'typeName' => 'required|string|max:255|unique:material_types,name,'.$type->id
        ]);
        $type->update(['name' => $request->typeName]);
        return response()->json(['success' => true, 'type' => $type->name, 'id' => $type->id]);
    }

    public function destroyType($id)
    {
        $type = MaterialType::findOrFail($id);
        $type->delete();
        return response()->json(['success' => true]);
    }

    // ---------- Units ----------
    public function storeUnit(Request $request)
    {
        $request->validate([
            'unitName'  => 'required|string|max:255|unique:units,name',
            'unitLabel' => 'required|string|max:255|unique:units,label',
        ]);
        $unit = Unit::create(['name' => $request->unitName, 'label' => $request->unitLabel]);
        return response()->json(['success' => true, 'label' => $unit->label, 'id' => $unit->id]);
    }

    public function updateUnit(Request $request, $id)
    {
        $unit = Unit::findOrFail($id);
        $request->validate([
            'unitName'  => 'required|string|max:255|unique:units,name,'.$unit->id,
            'unitLabel' => 'required|string|max:255|unique:units,label,'.$unit->id,
        ]);
        $unit->update(['name' => $request->unitName, 'label' => $request->unitLabel]);
        return response()->json(['success' => true, 'label' => $unit->label, 'id' => $unit->id]);
    }

    public function destroyUnit($id)
    {
        $unit = Unit::findOrFail($id);
        $unit->delete();
        return response()->json(['success' => true]);
    }

    // ---------- Materials ----------
    public function store(Request $request)
    {
        $request->validate([
            'materialName'     => 'required|string|max:255',
            'material_type_id' => 'required|exists:material_types,id',
            'unit_id'          => 'required|exists:units,id',
            'unitCost'         => 'required|numeric|min:0',
        ]);

        $material = Material::create([
            'materialName'     => $request->materialName,
            'material_type_id' => $request->material_type_id,
            'unit_id'          => $request->unit_id,
            'unitCost'         => $request->unitCost,
        ])->load(['materialType:id,name','unit:id,label']);

        return response()->json(['success' => true, 'material' => $material]);
    }

    public function update(Request $request, $id)
    {
        $material = Material::findOrFail($id);
        $request->validate([
            'qeName' => 'nullable|string|max:255',
            'qeNew'  => 'required|numeric|min:0',
            'qeUnit' => 'required|exists:units,id',
        ]);

        $material->update([
            'materialName' => $request->qeName ?: $material->materialName,
            'unitCost'     => $request->qeNew,
            'unit_id'      => $request->qeUnit,
        ]);

        $material->load(['unit:id,label']);
        return response()->json(['success' => true, 'material' => $material]);
    }

    public function destroy($id)
    {
        $material = Material::findOrFail($id);
        $material->delete();
        return response()->json(['success' => true]);
    }
}