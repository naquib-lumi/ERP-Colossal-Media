@extends('layouts.app')

@section('title','Material Management')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
  :root{
    --bg:#F9FAFB; --card:#FFFFFF; --border:#E5E7EB; --thead:#F9FAFB;
    --text:#101828; --muted:#667085; --chip:#F2F4F7;
    --shadow:0 3px 10px rgba(16,24,40,.06);
    --primary:#3B82F6; --primary-600:#2563EB;
    --dark:#111827; --dark-700:#0B1220;
  }
  body{background:var(--bg)}
  .page-wrap{max-width:1200px;margin:0 auto;padding:20px}

  .card{background:var(--card);border:1px solid var(--border);border-radius:16px;box-shadow:var(--shadow)}
  .card-hd{display:flex;align-items:center;justify-content:space-between;padding:18px 20px;border-bottom:1px solid var(--border)}
  .title{font-size:20px;font-weight:700;color:var(--text)}
  .actions{display:flex;gap:10px}
  .btn{display:inline-flex;align-items:center;gap:8px;border-radius:10px;border:1px solid transparent;padding:10px 14px;font-weight:600;cursor:pointer}
  .btn-primary{background:var(--primary);color:#fff}
  .btn-primary:hover{background:var(--primary-600)}
  .btn-dark{background:var(--dark);color:#fff}
  .btn-dark:hover{background:var(--dark-700)}
  .btn-ghost{background:#fff;border-color:var(--border);color:var(--text)}
  .btn-ghost:hover{background:#F3F4F6}

  .toolbar{display:flex;gap:12px;padding:14px 20px;border-bottom:1px solid var(--border)}
  .search{position:relative;flex:1}
  .search input{width:100%;height:42px;border:1px solid var(--border);border-radius:10px;padding:0 40px 0 40px;background:#fff}
  .search .bi-search{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--muted)}
  .select{display:flex;align-items:center;gap:8px}
  .select select{height:42px;border:1px solid var(--border);border-radius:10px;padding:0 12px;background:#fff;color:var(--text)}

  table{width:100%;border-collapse:separate;border-spacing:0 8px;margin:12px 0 8px}
  thead th{font-size:12px;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;padding:10px 16px;background:var(--thead)}
  tbody tr{background:#fff;border:1px solid var(--border)}
  tbody td{padding:14px 16px;color:var(--text);vertical-align:middle}
  tbody tr{border-radius:12px;overflow:hidden}
  tbody tr td:first-child{border-top-left-radius:12px;border-bottom-left-radius:12px}
  tbody tr td:last-child{border-top-right-radius:12px;border-bottom-right-radius:12px}

  .kebab{border:none;background:transparent;cursor:pointer}
  .dropdown-menu{position:absolute;right:0;top:28px;background:#fff;border:1px solid var(--border);border-radius:10px;box-shadow:var(--shadow);display:none;min-width:180px;z-index:40}
  .dropdown-item{display:block;width:100%;text-align:left;border:0;background:#fff;padding:10px 12px;color:#111827}
  .dropdown-item:hover{background:#F3F4F6}
  .text-danger{color:#DC2626}

  .pagination{display:flex;gap:8px;align-items:center;justify-content:flex-end;padding:0 20px 18px}
  .pager{width:34px;height:34px;border:1px solid var(--border);border-radius:8px;display:grid;place-items:center;background:#fff}
  .pager.active{background:#111827;color:#fff;border-color:#111827}

  /* ===== Custom modal (no bootstrap classes) ===== */
  .x-mask{position:fixed;inset:0;background:rgba(0,0,0,.45);display:none;align-items:center;justify-content:center;padding:20px;z-index:2000}
  .x-dialog{width:520px;max-width:96vw;background:#fff;border-radius:12px;box-shadow:var(--shadow);overflow:hidden;display:block}
  .x-hd{padding:16px 18px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center}
  .x-ttl{font-weight:700;color:var(--text)}
  .x-bd{padding:18px}
  .x-ft{padding:16px 18px;border-top:1px solid var(--border);display:flex;justify-content:flex-end;gap:10px}
  .field{margin-bottom:14px}
  .label{font-size:12px;color:var(--muted);margin-bottom:6px}
  .control{height:42px;border:1px solid var(--border);border-radius:10px;padding:0 12px;width:100%}
  .hidden{display:none!important}
</style>

<div class="page-wrap">
  <div class="card">
    <div class="card-hd">
      <div class="title">Material Management</div>
      <div class="actions">
        <button id="btnAddType" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Material Type</button>
        <button id="btnAddUnit" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Unit</button>
        <button id="btnAddMaterial" class="btn btn-dark"><i class="bi bi-plus-lg"></i> Add Material</button>
      </div>
    </div>

    <div class="toolbar">
      <div class="search">
        <i class="bi bi-search"></i>
        <input id="q" type="text" placeholder="Search materials...">
      </div>
      <div class="select">
        <select id="typeFilter">
          <option value="all">All Material Types</option>
          @foreach($types as $id => $name)
            <option value="{{ $id }}">{{ $name }}</option>
          @endforeach
        </select>
        <select id="qtyFilter">
          <option value="all">All Units</option>
          @foreach($units as $id => $label)
            <option value="{{ $id }}">{{ $label }}</option>
          @endforeach
        </select>
      </div>
    </div>

    <div class="table-wrap" style="padding:6px 12px 10px;">
      <table id="tbl">
        <thead>
          <tr>
            <th style="width:40%">Material Name</th>
            <th style="width:28%">Material Type</th>
            <th style="width:22%">Unit Cost</th>
            <th style="width:10%;text-align:right">Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach($materials as $material)
            <tr data-type="{{ $material->material_type_id }}" data-uom="{{ $material->unit_id }}">
              <td>{{ $material->materialName }}</td>
              <td>{{ $material->materialType->name }}</td>
              <td>RM {{ number_format($material->unitCost,4) }} {{ $material->unit->label }}</td>
              <td style="text-align:right; position:relative">
                <button class="kebab" data-toggle="dropdown" aria-expanded="false" title="Actions">
                  <i class="bi bi-three-dots-vertical"></i>
                </button>
                <div class="dropdown-menu">
                  <button class="dropdown-item btnEdit" data-id="{{ $material->MaterialID }}" data-name="{{ $material->materialName }}" data-type="{{ $material->material_type_id }}" data-cost="{{ $material->unitCost }}" data-uom="{{ $material->unit_id }}">
                    <i class="bi bi-pencil me-2"></i> Edit Unit Cost
                  </button>
                  <button class="dropdown-item text-danger btnDelete" data-id="{{ $material->MaterialID }}"><i class="bi bi-trash me-2"></i> Delete</button>
                </div>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>

      <div class="pagination">
        <div class="pager" id="prev"><i class="bi bi-chevron-left"></i></div>
        <div class="pager active">1</div>
        <div class="pager" id="next"><i class="bi bi-chevron-right"></i></div>
      </div>

      <div style="padding:0 20px 18px;color:var(--muted);font-size:12px">
        Showing <span id="countShown">{{ $materials->count() }}</span> of <span id="countTotal">{{ $materials->count() }}</span> materials
      </div>
    </div>
  </div>
</div>

{{-- ===== Modals ===== --}}

{{-- Add / Edit Material Type --}}
<div id="mdlType" class="x-mask" aria-hidden="true">
  <div class="x-dialog" role="dialog" aria-modal="true">
    <div class="x-hd">
      <div class="x-ttl" id="typeTitle">Add New Material Type</div>
      <button class="kebab" data-close="mdlType" aria-label="Close"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="x-bd">
      <div class="field">
        <div class="label">Material Type Name</div>
        <input id="typeName" type="text" class="control" placeholder="e.g. Backlit Materials">
      </div>
    </div>
    <div class="x-ft">
      <button class="btn btn-ghost" data-close="mdlType">Cancel</button>
      <button class="btn btn-dark" id="saveTypeBtn">Save</button>
    </div>
  </div>
</div>

{{-- Add / Edit Unit --}}
<div id="mdlUnit" class="x-mask" aria-hidden="true">
  <div class="x-dialog" role="dialog" aria-modal="true">
    <div class="x-hd">
      <div class="x-ttl" id="unitTitle">Add New Unit</div>
      <button class="kebab" data-close="mdlUnit" aria-label="Close"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="x-bd">
      <div class="field">
        <div class="label">Unit Name</div>
        <input id="unitName" type="text" class="control" placeholder="e.g. perSqInch">
      </div>
      <div class="field">
        <div class="label">Unit Label</div>
        <input id="unitLabel" type="text" class="control" placeholder="e.g. Per sq inch">
      </div>
    </div>
    <div class="x-ft">
      <button class="btn btn-ghost" data-close="mdlUnit">Cancel</button>
      <button class="btn btn-dark" id="saveUnitBtn">Save</button>
    </div>
  </div>
</div>

{{-- Add / Edit Material --}}
<div id="mdlMaterial" class="x-mask" aria-hidden="true">
  <div class="x-dialog" role="dialog" aria-modal="true">
    <div class="x-hd">
      <div class="x-ttl" id="materialTitle">Add New Material</div>
      <button class="kebab" data-close="mdlMaterial" aria-label="Close"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="x-bd">
      <div class="field">
        <div class="label">Material Name *</div>
        <input id="matName" type="text" class="control" placeholder="e.g., PVC Banner, Foam Core Board">
      </div>
      <div class="field">
        <div class="label">Material Category *</div>
        <select id="matType" class="control">
          <option value="">Select category...</option>
          @foreach($types as $id => $name)
            <option value="{{ $id }}">{{ $name }}</option>
          @endforeach
        </select>
      </div>
      <div class="field">
        <div class="label">Unit Cost (RM) *</div>
        <input id="matCost" type="number" step="0.0001" class="control" placeholder="0.0069">
      </div>
      <div class="field">
        <div class="label">Unit *</div>
        <select id="matUnit" class="control">
          <option value="">Select unit...</option>
          @foreach($units as $id => $label)
            <option value="{{ $id }}">{{ $label }}</option>
          @endforeach
        </select>
      </div>
    </div>
    <div class="x-ft">
      <button class="btn btn-ghost" data-close="mdlMaterial">Cancel</button>
      <button class="btn btn-dark" id="btnSaveMaterial"><i class="bi bi-floppy"></i> Save Material</button>
    </div>
  </div>
</div>

{{-- Edit Unit Cost quick modal --}}
<div id="mdlQuickEdit" class="x-mask" aria-hidden="true">
  <div class="x-dialog" role="dialog" aria-modal="true">
    <div class="x-hd">
      <div class="x-ttl" id="qeTitle">Edit Unit Cost</div>
      <button class="kebab" data-close="mdlQuickEdit" aria-label="Close"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="x-bd">
      <input type="hidden" id="qeId">
      <div class="field">
        <div class="label">Material Name</div>
        <input id="qeName" type="text" class="control" placeholder="e.g., PVC Banner, Foam Core Board">
      </div>
      <div class="field">
        <div class="label">Current Unit Cost</div>
        <input id="qeCurrent" class="control" disabled>
      </div>
      <div class="field">
        <div class="label">New Unit Cost *</div>
        <input id="qeNew" type="number" step="0.0001" class="control" placeholder="0.0069">
        <div style="font-size:12px;color:var(--muted);margin-top:6px">Enter the new unit cost for this material</div>
      </div>
      <div class="field">
        <div class="label">Unit</div>
        <select id="qeUnit" class="control">
          <option value="">Select unit...</option>
          @foreach($units as $id => $label)
            <option value="{{ $id }}">{{ $label }}</option>
          @endforeach
        </select>
      </div>
    </div>
    <div class="x-ft">
      <button class="btn btn-ghost" data-close="mdlQuickEdit">Cancel</button>
      <button class="btn btn-dark" id="btnSaveQuick"><i class="bi bi-floppy2"></i> Save Changes</button>
    </div>
  </div>
</div>

<script>
(() => {
  const $ = s => document.querySelector(s);
  const $$ = s => Array.from(document.querySelectorAll(s));
  const csrf = $('meta[name="csrf-token"]')?.content;

  function openMask(id){ const m = $(`#${id}`); if(m) m.style.display='flex' }
  function closeMask(id){ const m = $(`#${id}`); if(m) m.style.display='none' }

  function applyFilter(){
    const q = ($('#q')?.value || '').trim().toLowerCase();
    const type = $('#typeFilter')?.value || 'all';
    const uom  = $('#qtyFilter')?.value || 'all';
    let shown = 0;
    $$('#tbl tbody tr').forEach(tr=>{
      const name = tr.children[0].innerText.toLowerCase();
      const tVal = tr.getAttribute('data-type');
      const uVal = tr.getAttribute('data-uom');
      const ok = (!q || name.includes(q)) && (type==='all' || tVal===type) && (uom==='all' || uVal===uom);
      tr.style.display = ok ? '' : 'none';
      if(ok) shown++;
    });
    const cs = $('#countShown');
    if (cs) cs.textContent = shown;
  }

  document.addEventListener('click', (e)=>{
    const t = e.target;

    if (t.closest('#btnAddType')) {
      $('#typeTitle').innerText = 'Add New Material Type';
      $('#typeName').value = '';
      $('#saveTypeBtn').removeAttribute('data-id');
      openMask('mdlType'); return;
    }
    if (t.closest('#btnAddUnit')) {
      $('#unitTitle').innerText = 'Add New Unit';
      $('#unitName').value = '';
      $('#unitLabel').value = '';
      $('#saveUnitBtn').removeAttribute('data-id');
      openMask('mdlUnit'); return;
    }
    if (t.closest('#btnAddMaterial')) {
      $('#materialTitle').innerText = 'Add New Material';
      $('#matName').value = '';
      $('#matType').value = '';
      $('#matCost').value = '';
      openMask('mdlMaterial'); return;
    }
    const closer = t.closest('[data-close]');
    if (closer){ closeMask(closer.getAttribute('data-close')); return; }

   const toggle = t.closest('[data-toggle="dropdown"]');
const bsToggle = t.closest('[data-bs-toggle="dropdown"]'); // Add for Bootstrap
$$('.dropdown-menu').forEach(m => {
  if ((toggle && m.previousElementSibling === toggle) || (bsToggle && m.parentElement.querySelector('[data-bs-toggle="dropdown"]') === bsToggle)) {
    m.style.display = m.style.display === 'block' ? 'none' : 'block';
  } else {
    m.style.display = 'none';
  }
});

    const editBtn = t.closest('.btnEdit');
    if (editBtn){
      const name = editBtn.dataset.name;
      const cost = editBtn.dataset.cost;
      const uom = editBtn.dataset.uom;
      const id = editBtn.dataset.id;
      $('#qeTitle').innerText = `Edit Unit Cost – ${name}`;
      $('#qeName').value = name;
      $('#qeCurrent').value = `RM ${Number(cost).toFixed(4)}`;
      $('#qeNew').value = '';
      $('#qeUnit').value = uom;
      $('#qeId').value = id;
      openMask('mdlQuickEdit');
      return;
    }

    const delBtn = t.closest('.btnDelete');
    if (delBtn){
      if(!confirm('Delete this material?')) return;
      const id = delBtn.dataset.id;
      fetch(`/admin/materials/${id}`, {
        method: 'DELETE',
        headers: {'X-CSRF-TOKEN': csrf}
      }).then(res => res.json()).then(data => {
        if(data.success){
          delBtn.closest('tr').remove();
          $('#countTotal').textContent = Number($('#countTotal').textContent) - 1;
          applyFilter();
        } else {
          alert('Error deleting');
        }
      }).catch(() => alert('Error'));
      return;
    }

    if (t.closest('#saveTypeBtn')){
      const name = ($('#typeName')?.value || '').trim();
      if(!name) return alert('Please enter a material type name');
      const id = $('#saveTypeBtn').dataset.id;
      const url = id ? `/admin/material-types/${id}` : '{{ route("admin.material-types.store") }}';
      const method = id ? 'PUT' : 'POST';
      fetch(url, {
        method,
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json'},
        body: JSON.stringify({typeName: name})
      }).then(res => res.json()).then(data => {
        if(data.success){
          const selIds = ['typeFilter', 'matType'];
          selIds.forEach(selId => {
            const sel = $(`#${selId}`);
            if(sel){
              if (id) {
                [...sel.options].forEach(o => {
                  if (o.value === id) o.textContent = data.type;
                });
              } else {
                const o = document.createElement('option');
                o.value = data.id;
                o.textContent = data.type;
                sel.appendChild(o);
              }
            }
          });
          closeMask('mdlType');
        } else {
          alert('Error saving type');
        }
      }).catch(() => alert('Error'));
      return;
    }

    if (t.closest('#saveUnitBtn')){
      const name = ($('#unitName')?.value || '').trim();
      const label = ($('#unitLabel')?.value || '').trim();
      if(!name || !label) return alert('Please enter unit name and label');
      const id = $('#saveUnitBtn').dataset.id;
      const url = id ? `/admin/units/${id}` : '{{ route("admin.units.store") }}';
      const method = id ? 'PUT' : 'POST';
      fetch(url, {
        method,
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json'},
        body: JSON.stringify({unitName: name, unitLabel: label})
      }).then(res => res.json()).then(data => {
        if(data.success){
          const selIds = ['qtyFilter', 'matUnit', 'qeUnit'];
          selIds.forEach(selId => {
            const sel = $(`#${selId}`);
            if(sel){
              if (id) {
                [...sel.options].forEach(o => {
                  if (o.value === id) o.textContent = data.label;
                });
              } else {
                const o = document.createElement('option');
                o.value = data.id;
                o.textContent = data.label;
                sel.appendChild(o);
              }
            }
          });
          closeMask('mdlUnit');
        } else {
          alert('Error saving unit');
        }
      }).catch(() => alert('Error'));
      return;
    }

  if (t.closest('#btnSaveMaterial')) {
  const postData = {
    matName: $('#matName').value.trim(),
    matType: $('#matType').value,
    matCost: parseFloat($('#matCost').value),
    unitType: $('#matUnit').value
  };

  if (!postData.matName || !postData.matType || isNaN(postData.matCost) || !postData.unitType) {
    return Swal.fire({ icon: 'warning', title: 'Missing Fields', text: 'Please fill all required fields.' });
  }

  fetch('{{ route('admin.materials.store') }}', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': csrf,
      'Accept': 'application/json'
    },
    body: JSON.stringify(postData)
  })
  .then(res => {
    if (!res.ok) throw res;
    return res.json();
  })
  .then(data => {
    console.log('Server response:', data); // Log full response
    if (data.success) {
      const m = data.material;
      console.log('Material data:', m); // Log material object
      const tr = document.createElement('tr');
      tr.setAttribute('data-type', m.material_type_id);
      tr.setAttribute('data-uom', m.unit_id);
      tr.innerHTML = `
        <td>${m.materialName}</td>
        <td>${m.materialType ? m.materialType.name : 'N/A'}</td> <!-- Fallback if undefined -->
        <td>RM ${Number(m.unitCost).toFixed(4)} ${m.unit ? m.unit.label : 'N/A'}</td> <!-- Fallback if undefined -->
        <td style="text-align:right; position:relative">
          <button class="kebab" data-toggle="dropdown" aria-expanded="false" title="Actions">
            <i class="bi bi-three-dots-vertical"></i>
          </button>
          <div class="dropdown-menu">
            <button class="dropdown-item btnEdit" data-id="${m.MaterialID}" data-name="${m.materialName}" data-type="${m.material_type_id}" data-cost="${m.unitCost}" data-uom="${m.unit_id}">
              <i class="bi bi-pencil me-2"></i> Edit Unit Cost
            </button>
            <button class="dropdown-item text-danger btnDelete" data-id="${m.MaterialID}"><i class="bi bi-trash me-2"></i> Delete</button>
          </div>
        </td>
      `;
      $('#tbl tbody').append(tr);
      $('#countTotal').textContent = Number($('#countTotal').textContent) + 1;
      closeMask('mdlMaterial');
      applyFilter();
      Swal.fire({ icon: 'success', title: 'Success', text: 'Material added successfully.', timer: 1500, showConfirmButton: false });
    } else {
      console.error('Server error:', data);
      Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Failed to save material.' });
    }
  })
  .catch(err => {
    console.error('Fetch error:', err);
    if (err.status) {
      err.json().then(json => {
        console.log('Error details:', json);
        Swal.fire({ icon: 'error', title: `Error ${err.status}`, text: json.message || 'An error occurred.' });
      }).catch(() => {
        Swal.fire({ icon: 'error', title: 'Network Error', text: 'Failed to reach server.' });
      });
    } else {
      Swal.fire({ icon: 'error', title: 'Request Failed', text: 'Check console for details.' });
    }
  });
  return;
}

    if (t.closest('#btnSaveQuick')){
      const postData = {
        qeName: $('#qeName').value.trim(),
        qeNew: parseFloat($('#qeNew').value),
        qeUnit: $('#qeUnit').value
      };
      const id = $('#qeId').value;
      if(isNaN(postData.qeNew)) return alert('Please enter a valid unit cost');
      fetch(`/admin/materials/${id}`, {
        method: 'PUT',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json'},
        body: JSON.stringify(postData)
      }).then(res => res.json()).then(data => {
        if(data.success){
          const tr = $$('#tbl tbody tr').find(tr => tr.querySelector('.btnEdit').dataset.id === id);
          if(tr){
            tr.children[0].textContent = postData.qeName || tr.children[0].textContent;
            tr.children[2].textContent = `RM ${Number(postData.qeNew).toFixed(4)} ${data.material.unit.label}`;
            tr.dataset.uom = postData.qeUnit;
            const btn = tr.querySelector('.btnEdit');
            btn.dataset.name = tr.children[0].textContent;
            btn.dataset.cost = postData.qeNew;
            btn.dataset.uom = postData.qeUnit;
          }
          closeMask('mdlQuickEdit');
          applyFilter();
        } else {
          alert('Error updating');
        }
      }).catch(() => alert('Error'));
      return;
    }
  });

  ['input','change'].forEach(ev=>{
    document.addEventListener(ev, (e)=>{
      if (['q','typeFilter','qtyFilter'].includes(e.target.id)) applyFilter();
    });
  });

  window.addEventListener('load', applyFilter);
})();
</script>
@endsection