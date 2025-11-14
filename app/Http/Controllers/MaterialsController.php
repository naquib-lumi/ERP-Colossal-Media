<?php
namespace App\Http\Controllers;
use App\Models\Material;
use App\Models\MaterialType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class MaterialsController extends Controller
{
    public function index(Request $request)
    {
        $types = MaterialType::all();
        $materials = Material::with('materialType')->get();
        if ($request->ajax()) {
            return response()->json([
                'materials' => $materials,
                'types' => $types,
            ]);
        }
        return view('admin.materials.index', compact('materials', 'types'));
    }
    public function storeType(Request $request)
    {
        $request->validate(['typeName' => 'required|string|max:255|unique:material_types,name']);
        $type = MaterialType::create(['name' => $request->typeName, 'active' => true]);
        return response()->json(['success' => true, 'type' => $type]);
    }
    public function updateType(Request $request, $id)
    {
        $type = MaterialType::findOrFail($id);
        $request->validate(['typeName' => 'required|string|max:255|unique:material_types,name,' . $id . ',id']);
        $type->update(['name' => $request->typeName]);
        return response()->json(['success' => true, 'type' => $type]);
    }
    public function destroyType($id)
    {
        $type = MaterialType::findOrFail($id);
        $type->delete();
        return response()->json(['success' => true]);
    }
    public function toggleType($id)
    {
        $type = MaterialType::findOrFail($id);
        $type->active = !$type->active;
        $type->save();
        return response()->json(['success' => true, 'active' => $type->active]);
    }
    public function store(Request $request)
    {
        $request->validate([
            'matName' => 'required|string|max:255',
            'matType' => 'required|exists:material_types,id',
            'matCost' => 'required|numeric|min:0',
        ]);
        $material = Material::create([
            'UserID' => Auth::id(),
            'materialName' => $request->matName,
            'material_type_id' => $request->matType,
            'unitCost' => $request->matCost,
            'active' => true,
        ]);
        $material->load('materialType');
        return response()->json(['success' => true, 'material' => $material]);
    }
    public function update(Request $request, $id)
    {
        $material = Material::findOrFail($id);
        $request->validate([
            'qeName' => 'sometimes|string|max:255',
            'qeNew' => 'required|numeric|min:0',
            'qeType' => 'sometimes|exists:material_types,id',
        ]);
        $material->update([
            'materialName' => $request->qeName ?? $material->materialName,
            'material_type_id' => $request->qeType ?? $material->material_type_id,
            'unitCost' => $request->qeNew,
        ]);
        $material->load('materialType');
        return response()->json(['success' => true, 'material' => $material]);
    }
    public function destroy($id)
    {
        $material = Material::findOrFail($id);
        $material->delete();
        return response()->json(['success' => true]);
    }
    public function toggle($id)
    {
        $material = Material::findOrFail($id);
        $material->active = !$material->active;
        $material->save();
        return response()->json(['success' => true, 'active' => $material->active]);
    }
}