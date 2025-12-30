@extends('layouts.app')
@section('title','Material Management')
@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
  .pager{width:34px;height:34px;border:1px solid var(--border);border-radius:8px;display:grid;place-items:center;background:#fff;cursor:pointer}
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
  .inactive td { color: var(--muted); }
  .dropdown-menu .dropdown-item { opacity: 1; }
  .swal2-container { z-index: 3000 !important; }
</style>
<div class="page-wrap">
  <div class="card">
    <div class="card-hd">
      <div class="title">Material Management</div>
      <div class="actions">
        <button id="btnManageTypes" class="btn btn-primary"><i class="bi bi-list"></i> Manage Types</button>
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
          @foreach($types as $type)
            <option value="{{ $type->id }}">{{ $type->name }}</option>
          @endforeach
        </select>
      </div>
    </div>
    <div class="table-wrap" style="padding:6px 12px 10px;">
      <table id="tbl">
        <thead>
          <tr>
            <th style="width:35%">Material Name</th>
            <th style="width:25%">Material Type</th>
            <th style="width:20%">Unit Cost</th>
            <th style="width:10%">Status</th>
            <th style="width:10%;text-align:right">Actions</th>
          </tr>
        </thead>
@foreach($materials as $material)
  <tr data-type="{{ $material->material_type_id }}" class="{{ $material->active ? '' : 'inactive' }}">
    <td>{{ $material->materialName }}</td>
    <td>{{ $material->materialType->name }}</td>
    <td>RM {{ number_format($material->unitCost,4) }} / SQ INCH</td>
    <td>{{ $material->active ? 'Active' : 'Inactive' }}</td>
    <td style="text-align:right; position:relative">
      <button class="kebab" data-toggle="dropdown" aria-expanded="false" title="Actions">
        <i class="bi bi-three-dots-vertical"></i>
      </button>
      <div class="dropdown-menu dm-menu">
        <button class="dropdown-item btnEdit" data-id="{{ $material->MaterialID }}" data-name="{{ $material->materialName }}" data-type="{{ $material->material_type_id }}" data-cost="{{ $material->unitCost }}">
          <i class="bi bi-pencil me-2"></i> Edit
        </button>
        <button class="dropdown-item btnToggle" data-id="{{ $material->MaterialID }}" data-active="{{ $material->active }}">
          <i class="bi bi-toggle{{ $material->active ? 'on' : 'off' }} me-2"></i> {{ $material->active ? 'Deactivate' : 'Activate' }}
        </button>
      </div>
    </td>
  </tr>
@endforeach
      </table>

    </div>
  </div>
</div>
{{-- Modals --}}
<div id="mdlTypes" class="x-mask" aria-hidden="true">
  <div class="x-dialog" style="width:800px; max-width:90vw">
    <div class="x-hd">
      <div class="x-ttl">Manage Material Types</div>
      <button class="kebab" data-close="mdlTypes" aria-label="Close"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="x-bd" style="padding:0">
      <div class="toolbar" style="padding:14px 20px;border-bottom:1px solid var(--border);justify-content:flex-end">
        <button id="btnAddType" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Type</button>
      </div>
      <div style="overflow:auto;max-height:400px">
        <table id="tblTypes">
          <thead>
            <tr>
              <th style="width:70%">Material Type Name</th>
              <th style="width:15%">Status</th>
              <th style="width:15%;text-align:right">Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach($types as $type)
              <tr class="{{ $type->active ? '' : 'inactive' }}">
                <td>{{ $type->name }}</td>
                <td>{{ $type->active ? 'Active' : 'Inactive' }}</td>
                <td style="text-align:right; position:relative">
                  <button class="kebab" data-toggle="dropdown" aria-expanded="false" title="Actions">
                    <i class="bi bi-three-dots-vertical"></i>
                  </button>
                  <div class="dropdown-menu dm-menu">
                    <button class="dropdown-item btnEditType" data-id="{{ $type->id }}" data-name="{{ $type->name }}">
                      <i class="bi bi-pencil me-2"></i> Edit
                    </button>
                    <button class="dropdown-item btnToggleType" data-id="{{ $type->id }}" data-active="{{ $type->active }}">
                      <i class="bi bi-toggle{{ $type->active ? 'on' : 'off' }} me-2"></i> {{ $type->active ? 'Deactivate' : 'Activate' }}
                    </button>
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
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
    @foreach($types as $type)
      @if($type->active)
        <option value="{{ $type->id }}">{{ $type->name }}</option>
      @endif
    @endforeach
  </select>
</div>
      <div class="field">
        <div class="label">Unit Cost (RM) *</div>
        <input id="matCost" type="number" step="0.0001" class="control" placeholder="0.0069">
      </div>
    </div>
    <div class="x-ft">
      <button class="btn btn-ghost" data-close="mdlMaterial">Cancel</button>
      <button class="btn btn-dark" id="btnSaveMaterial"><i class="bi bi-floppy"></i> Save Material</button>
    </div>
  </div>
</div>
<div id="mdlQuickEdit" class="x-mask" aria-hidden="true">
  <div class="x-dialog" role="dialog" aria-modal="true">
    <div class="x-hd">
      <div class="x-ttl" id="qeTitle">Edit Material</div>
      <button class="kebab" data-close="mdlQuickEdit" aria-label="Close"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="x-bd">
      <input type="hidden" id="qeId">
      <div class="field">
        <div class="label">Material Name</div>
        <input id="qeName" type="text" class="control">
      </div>
      <div class="field">
        <div class="label">Material Type</div>
        <select id="qeType" class="control">
          <option value="">Select type...</option>
          @foreach($types as $type)
            <option value="{{ $type->id }}">{{ $type->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="field">
        <div class="label">Current Unit Cost</div>
        <input id="qeCurrent" class="control" disabled>
      </div>
      <div class="field">
        <div class="label">New Unit Cost *</div>
        <input id="qeNew" type="number" step="0.0001" class="control" placeholder="0.0069">
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
    const q = ($('#q')?.value || '').toLowerCase();
    const type = $('#typeFilter')?.value || 'all';
    $$('#tbl tbody tr').forEach(tr => {
      const name = tr.children[0].textContent.toLowerCase();
      const matType = tr.dataset.type;
      const matchesSearch = name.includes(q);
      const matchesType = type === 'all' || matType === type;
      tr.style.display = matchesSearch && matchesType ? '' : 'none';
    });
  }
  document.addEventListener('click', (e)=>{
    const t = e.target;
    if (t.closest('#btnManageTypes')) {
      openMask('mdlTypes');
      return;
    }
    if (t.closest('#btnAddType')) {
      $('#typeTitle').innerText = 'Add New Material Type';
      $('#typeName').value = '';
      $('#saveTypeBtn').removeAttribute('data-id');
      openMask('mdlType'); return;
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

    // only handle OUR menus
    $$('.dm-menu').forEach(m => {
      const btn = m.previousElementSibling; // the kebab button
      if (toggle && btn === toggle) {
        m.style.display = (m.style.display === 'block' ? 'none' : 'block');
      } else {
        m.style.display = 'none';
      }
    });
    const editTypeBtn = t.closest('.btnEditType');
    if (editTypeBtn){
      const name = editTypeBtn.dataset.name;
      const id = editTypeBtn.dataset.id;
      $('#typeTitle').innerText = `Edit Material Type - ${name}`;
      $('#typeName').value = name;
      $('#saveTypeBtn').dataset.id = id;
      openMask('mdlType');
      return;
    }
    const togTypeBtn = t.closest('.btnToggleType');
    if (togTypeBtn){
      const id = togTypeBtn.dataset.id;
      fetch(`/admin/material-types/${id}/toggle`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json'},
        body: JSON.stringify({_method: 'PATCH'})
      }).then(res => res.json()).then(data => {
        if(data.success){
          const tr = togTypeBtn.closest('tr');
          const statusTd = tr.children[1];
          statusTd.textContent = data.active ? 'Active' : 'Inactive';
          togTypeBtn.dataset.active = data.active;
          togTypeBtn.innerHTML = `<i class="bi bi-toggle${data.active ? 'on' : 'off'} me-2"></i> ${data.active ? 'Deactivate' : 'Activate'}`;
          tr.classList.toggle('inactive', !data.active);
          applyFilter();
        } else {
          Swal.fire({ icon: 'error', title: 'Error toggling' });
        }
      }).catch(() => Swal.fire({ icon: 'error', title: 'Error' }));
      return;
    }
    const editBtn = t.closest('.btnEdit');
    if (editBtn){
      const name = editBtn.dataset.name;
      const type = editBtn.dataset.type;
      const cost = editBtn.dataset.cost;
      const id = editBtn.dataset.id;
      $('#qeTitle').innerText = `Edit Material - ${name}`;
      $('#qeName').value = name;
      $('#qeType').value = type;
      $('#qeCurrent').value = `RM ${Number(cost).toFixed(4)}`;
      $('#qeNew').value = '';
      $('#qeId').value = id;
      openMask('mdlQuickEdit');
      return;
    }
    const togBtn = t.closest('.btnToggle');
    if (togBtn){
      const id = togBtn.dataset.id;
      fetch(`/admin/materials/${id}/toggle`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json'},
        body: JSON.stringify({_method: 'PATCH'})
      }).then(res => res.json()).then(data => {
        if(data.success){
          const tr = togBtn.closest('tr');
          const statusTd = tr.children[3];
          statusTd.textContent = data.active ? 'Active' : 'Inactive';
          togBtn.dataset.active = data.active;
          togBtn.innerHTML = `<i class="bi bi-toggle${data.active ? 'on' : 'off'} me-2"></i> ${data.active ? 'Deactivate' : 'Activate'}`;
          tr.classList.toggle('inactive', !data.active);
          applyFilter();
        } else {
          Swal.fire({ icon: 'error', title: 'Error toggling' });
        }
      }).catch(() => Swal.fire({ icon: 'error', title: 'Error' }));
      return;
    }
 if (t.closest('#saveTypeBtn')){
  const name = ($('#typeName')?.value || '').trim();
  if(!name) return Swal.fire({ icon: 'warning', title: 'Please enter a material type name' });
  const id = $('#saveTypeBtn').dataset.id;
  const url = id ? `/admin/material-types/${id}` : '{{ route("admin.material-types.store") }}';
  const method = id ? 'POST' : 'POST';
  const postBody = id ? {typeName: name, _method: 'PUT'} : {typeName: name};
  fetch(url, {
    method,
    headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json'},
    body: JSON.stringify(postBody)
  }).then(res => res.json()).then(data => {
    if(data.success){
      const selIds = ['typeFilter', 'matType', 'qeType'];
      selIds.forEach(selId => {
        const sel = $(`#${selId}`);
        if(sel){
          if (id) {
            [...sel.options].forEach(o => {
              if (o.value === id) o.textContent = data.type.name;
            });
          } else {
            const o = document.createElement('option');
            o.value = data.type.id;
            o.textContent = data.type.name;
            sel.appendChild(o);
          }
        }
      });
      const tblTypes = $('#tblTypes tbody');
      if(tblTypes){
        if (id) {
          const tr = $$('#tblTypes tbody tr').find(tr => tr.querySelector('.btnEditType').dataset.id === id);
          if(tr) tr.children[0].textContent = data.type.name;
        } else {
          const tr = document.createElement('tr');
          tr.innerHTML = `
            <td>${data.type.name}</td>
            <td>Active</td>
            <td style="text-align:right; position:relative">
              <button class="kebab" data-toggle="dropdown" aria-expanded="false" title="Actions">
                <i class="bi bi-three-dots-vertical"></i>
              </button>
              <div class="dropdown-menu dm-menu">
                <button class="dropdown-item btnEditType" data-id="${data.type.id}" data-name="${data.type.name}">
                  <i class="bi bi-pencil me-2"></i> Edit
                </button>
                <button class="dropdown-item btnToggleType" data-id="${data.type.id}" data-active="true">
                  <i class="bi bi-toggleon me-2"></i> Deactivate
                </button>
              </div>
            </td>
          `;
          tblTypes.appendChild(tr);
        }
      }
      closeMask('mdlType');
      Swal.fire({ icon: 'success', title: 'Success', text: 'Material type saved successfully.', timer: 1500, showConfirmButton: false });
      applyFilter();
    } else {
      Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Error saving type' });
    }
  }).catch(() => Swal.fire({ icon: 'error', title: 'Error', text: 'Network error' }));
  return;
}
    if (t.closest('#btnSaveMaterial')) {
      const postData = {
        matName: $('#matName').value.trim(),
        matType: $('#matType').value,
        matCost: parseFloat($('#matCost').value),
      };
      if (!postData.matName || !postData.matType || isNaN(postData.matCost)) {
        return Swal.fire({ icon: 'warning', title: 'Missing Fields', text: 'Please fill all required fields.' });
      }
      fetch('{{ route('admin.materials.store') }}', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json'},
        body: JSON.stringify(postData)
      }).then(res => res.json()).then(data => {
        if (data.success) {
          closeMask('mdlMaterial');
          Swal.fire({ icon: 'success', title: 'Success', text: 'Material added successfully.', timer: 1500, showConfirmButton: false }).then(() => location.reload());
        } else {
          Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Failed to save material.' });
        }
      }).catch(() => Swal.fire({ icon: 'error', title: 'Request Failed' }));
      return;
    }
if (t.closest('#btnSaveQuick')){
  const postData = {
    qeName: $('#qeName').value.trim(),
    qeType: $('#qeType').value,
    qeNew: parseFloat($('#qeNew').value),
  };
  const id = $('#qeId').value;
  if (!id) return Swal.fire({ icon: 'warning', title: 'Missing ID' });
  if (isNaN(postData.qeNew)) return Swal.fire({ icon: 'warning', title: 'Please enter a valid unit cost' });
  fetch(`/admin/materials/update/${id}`, {
    method: 'PUT',
    headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json'},
    body: JSON.stringify(postData)
  }).then(res => res.json())
  .then(data => {
    console.log("DEBUG:", data);
    if (data.success) {
      closeMask('mdlQuickEdit');
      Swal.fire({
        icon: 'success',
        title: 'Success',
        text: 'Material updated successfully.',
        timer: 1500,
        showConfirmButton: false
      }).then(() => location.reload());
    } else {
      Swal.fire({
        icon: 'error',
        title: 'Update Failed',
        text: data.message ?? 'Unknown error occurred'
      });
    }
  })
  .catch(err => {
    console.error("FETCH ERROR:", err);
    Swal.fire({
      icon: 'error',
      title: 'Request Failed',
      text: err.message
    });
  });
  return;
}
  });
  ['input','change'].forEach(ev=>{
    document.addEventListener(ev, (e)=>{
      if (['q','typeFilter'].includes(e.target.id)) applyFilter();
    });
  });
  window.addEventListener('load', applyFilter);
  document.querySelector('#tbl tbody').addEventListener('dblclick', e => {
    const tr = e.target.closest('tr');
    if (tr) {
      const btn = tr.querySelector('.btnEdit');
      if (btn) btn.click();
    }
  });
  document.querySelector('#tblTypes tbody').addEventListener('dblclick', e => {
    const tr = e.target.closest('tr');
    if (tr) {
      const btn = tr.querySelector('.btnEditType');
      if (btn) btn.click();
    }
  });
})();
</script>
@endsection