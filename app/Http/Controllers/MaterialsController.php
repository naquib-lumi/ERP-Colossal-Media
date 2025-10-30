<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\MaterialType;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MaterialsController extends Controller
{
    public function index()
    {
        $materials = Material::with(['materialType', 'unit'])->get();
        $types = MaterialType::pluck('name', 'id');
        $units = Unit::pluck('label', 'id');
        return view('admin.materials.index', compact('materials', 'types', 'units'));
    }

    public function storeType(Request $request)
    {
        $request->validate(['typeName' => 'required|string|max:255|unique:material_types,name']);
        $type = MaterialType::create(['name' => $request->typeName]);
        return response()->json(['success' => true, 'type' => $type->name, 'id' => $type->id]);
    }

    public function updateType(Request $request, $id)
    {
        $type = MaterialType::findOrFail($id);
        $request->validate(['typeName' => 'required|string|max:255|unique:material_types,name,' . $id . ',id']);
        $type->update(['name' => $request->typeName]);
        return response()->json(['success' => true, 'type' => $type->name, 'id' => $type->id]);
    }

    public function destroyType($id)
    {
        $type = MaterialType::findOrFail($id);
        $type->delete();
        return response()->json(['success' => true]);
    }

    public function storeUnit(Request $request)
    {
        $request->validate(['unitName' => 'required|string|max:255|unique:units,name', 'unitLabel' => 'required|string|max:255|unique:units,label']);
        $unit = Unit::create(['name' => $request->unitName, 'label' => $request->unitLabel]);
        return response()->json(['success' => true, 'label' => $unit->label, 'id' => $unit->id]);
    }

    public function updateUnit(Request $request, $id)
    {
        $unit = Unit::findOrFail($id);
        $request->validate([
            'unitName' => 'required|string|max:255|unique:units,name,' . $id . ',id',
            'unitLabel' => 'required|string|max:255|unique:units,label,' . $id . ',id'
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

    public function store(Request $request)
    {
        $request->validate([
            'matName' => 'required|string|max:255',
            'matType' => 'required|exists:material_types,id',
            'matCost' => 'required|numeric|min:0',
            'unitType' => 'required|exists:units,id',
        ]);

        $material = Material::create([
            'UserID' => Auth::id(),
            'materialName' => $request->matName,
            'material_type_id' => $request->matType,
            'unit_id' => $request->unitType,
            'unitCost' => $request->matCost,
        ]);

        $material->load(['materialType', 'unit']);

        return response()->json(['success' => true, 'material' => $material]);
    }

    public function update(Request $request, $id)
    {
        $material = Material::findOrFail($id);
        $request->validate([
            'qeName' => 'sometimes|string|max:255',
            'qeNew' => 'required|numeric|min:0',
            'qeUnit' => 'sometimes|exists:units,id',
        ]);

        $material->update([
            'materialName' => $request->qeName ?? $material->materialName,
            'unitCost' => $request->qeNew,
            'unit_id' => $request->qeUnit ?? $material->unit_id,
        ]);

        $material->load(['materialType', 'unit']);

        return response()->json(['success' => true, 'material' => $material]);
    }

    public function destroy($id)
    {
        $material = Material::findOrFail($id);
        $material->delete();
        return response()->json(['success' => true]);
    }
}