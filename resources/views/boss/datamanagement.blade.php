@extends('layouts.app')

@section('title','Data Management')

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
  .title{font-size:28px;font-weight:700;color:var(--text)}
  .actions{display:flex;gap:10px}
  .btn{display:inline-flex;align-items:center;gap:8px;border:1px solid var(--border);background:#fff;padding:8px 12px;border-radius:10px;font-weight:600;cursor:pointer}
  .btn i{font-size:16px}
  .btn-primary{background:var(--primary);color:#fff;border-color:var(--primary)}
  .btn-dark{background:var(--dark);color:#fff;border-color:var(--dark)}
  .btn-ghost{background:#fff;color:var(--text)}
  .toolbar{display:flex;gap:12px;align-items:center;padding:12px 16px;border-bottom:1px solid var(--border)}
  .search{display:flex;align-items:center;gap:8px;background:#fff;border:1px solid var(--border);border-radius:10px;padding:8px 10px;width:100%}
  .search input{border:0;outline:0;width:100%}
  .select select{border:1px solid var(--border);border-radius:10px;padding:8px 10px;background:#fff}
  .select {
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .table-wrap{overflow:auto}
  table{width:100%;border-collapse:separate;border-spacing:0 8px}
  thead th{font-size:12px;letter-spacing:.04em;color:var(--muted);text-transform:uppercase;text-align:left;padding:8px 10px}
  tbody tr{background:#fff;box-shadow:var(--shadow)}
  tbody td{padding:14px 10px;border-top:1px solid var(--border);border-bottom:1px solid var(--border)}
  tbody td:first-child{border-left:1px solid var(--border);border-radius:12px 0 0 12px}
  tbody td:last-child{border-right:1px solid var(--border);border-radius:0 12px 12px 0}
  .chip{display:inline-flex;align-items:center;gap:6px;background:var(--chip);color:var(--muted);border-radius:999px;padding:6px 10px;font-size:12px}
  .kebab{border:1px solid var(--border);background:#fff;border-radius:10px;width:38px;height:38px;display:flex;align-items:center;justify-content:center;cursor:pointer}
  .count{font-size:12px;color:var(--muted)}
  /* Tabs */
  .tabs{display:flex;gap:24px;padding:6px 2px 0}
  .tab-btn{appearance:none;background:none;border:0;padding:10px 2px;font-weight:700;color:var(--muted);cursor:pointer;position:relative}
  .tab-btn.active{color:var(--text)}
  .tab-btn.active::after{content:"";position:absolute;left:0;right:0;bottom:-8px;height:3px;background:var(--dark);border-radius:99px}
  .tabs-border{height:1px;background:var(--border);margin-bottom:16px}
  .tab-panel{display:none}
  .tab-panel.active{display:block}
  /* Modal */
  .x-mask{position:fixed;inset:0;background:rgba(2,6,23,.5);display:none;align-items:center;justify-content:center;padding:20px;z-index:50}
  .x-mask.show{display:flex}
  .x{width:min(680px,95vw);background:#fff;border:1px solid var(--border);border-radius:16px;box-shadow:var(--shadow);display:flex;flex-direction:column;max-height:90vh}
  .x-hd{padding:14px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px;font-weight:700}
  .x-bd{padding:16px;overflow:auto}
  .x-ft{padding:14px 16px;border-top:1px solid var(--border);display:flex;gap:10px;justify-content:flex-end}
  .field{display:flex;flex-direction:column;gap:6px;margin-bottom:12px}
  .label{font-weight:700;color:var(--text)}
  .control{border:1px solid var(--border);border-radius:10px;padding:8px 10px}

  /* ===================== Another Data (original design) ===================== */
  .another-head{
    display:flex; align-items:center; justify-content:space-between;
    margin:12px 18px 10px;
  }
  .another-title{
    font-size:26px; font-weight:800; letter-spacing:.2px; color:#0f172a;
    margin:0;
  }
  .another-controls{ display:flex; gap:12px; }
  .ad-input, .ad-select{
    height:44px; border:1px solid #E6E9EF; border-radius:12px; background:#fff; outline:none;
    font-size:14px; color:#0f172a;
  }
  .ad-input{ width:320px; padding:0 14px; }
  .ad-select{ padding:0 40px 0 14px; min-width:150px; appearance:none;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16'%3E%3Cpath fill='%23667085' d='M4.47 6.97a.75.75 0 0 1 1.06 0L8 9.44l2.47-2.47a.75.75 0 0 1 1.06 1.06l-3 3a.75.75 0 0 1-1.06 0l-3-3a.75.75 0 0 1 0-1.06Z'/%3E%3C/svg%3E");
    background-repeat:no-repeat; background-position:right 12px center;
  }

  /* big rounded card + soft header */
  .ad-card{
    margin:0 18px 12px; background:#fff; border:1px solid #E6E9EF;
    border-radius:16px; box-shadow:0 1px 0 rgba(16,24,40,.03); overflow:hidden;
  }
  .ad-table{ width:100%; border-collapse:separate; border-spacing:0; }
  .ad-table thead th{
    background:#F3F6FA;
    color:#475467; font-weight:700; text-align:left;
    padding:16px 18px; letter-spacing:.02em; border-bottom:1px solid #EEF2F7;
  }
  .ad-table tbody td{
    padding:18px; color:#0f172a; border-top:1px solid #F1F5F9;
  }
  .ad-table tbody tr:hover{ background:#FAFAFB; }

  /* force right aligned numerics */
  .ad-table th.ad-num,
  .ad-table td.ad-num{
    text-align:right !important;
    white-space:nowrap;
  }

  .ad-actions{ text-align:center; width:80px; min-width:80px; }
  .ad-qty-link{ color:#2563eb; font-weight:600; text-decoration:none; }
  .ad-eye{ display:inline-flex; width:36px; height:36px; border-radius:9999px; align-items:center; justify-content:center;
    border:1px solid #E6E9EF; background:#fff; }
  .ad-eye .bi{ font-size:18px; color:#111827; }

  /* footer & pager */
  .ad-foot{
    display:flex; align-items:center; justify-content:space-between;
    margin:10px 18px 0;
  }
  .ad-range{ color:#6b7280; font-size:14px; }
  .ad-pager{ display:flex; gap:8px; align-items:center; }
  .ad-page, .ad-nav{
    min-width:36px; height:36px; border:1px solid #E6E9EF; background:#fff; color:#0f172a;
    border-radius:10px; display:grid; place-items:center; padding:0 10px; cursor:pointer;
  }
  .ad-page.active{ background:#0f172a; color:#fff; border-color:#0f172a; }
  .ad-nav{ width:36px; }
</style>

<div class="page-wrap">
  <div class="d-flex align-items-center justify-content-between">
    <h1 class="title">Data Management</h1>
  </div>

  <!-- Tabs -->
  <div class="tabs">
    <button type="button" class="tab-btn active" data-target="#costData">Cost Data</button>
    <button type="button" class="tab-btn" data-target="#anotherData">Another Data</button>
  </div>
  <div class="tabs-border"></div>

  <div class="tab-panel active" id="costData">
    <div class="card">
      <div class="card-hd">
        <div>
          <div class="title" style="font-size: 20px !important;">Cost Data</div>
        </div>
        <div class="actions">
          <button id="btnAddType" class="btn"><i class="bi bi-tags"></i> Add Type</button>
          <button id="btnAddUnit" class="btn btn-primary"><i class="bi bi-rulers"></i> Add Unit</button>
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
              <th style="font-weight: bold;">Material Name</th>
              <th style="font-weight: bold;">Material Type</th>
              <th style="font-weight: bold;">Unit Cost</th>
              <th style="font-weight: bold;">Used Quantity</th>
              <th style="font-weight: bold;">Total Cost</th> 
              <th style="text-align:right; font-weight:bold">Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach($materials as $m)
              <tr data-type="{{ $m->material_type_id }}" data-uom="{{ $m->unit_id }}">
                <td>{{ $m->materialName }}</td>
                <td>{{ optional($m->materialType)->name }}</td>
                <td>RM {{ number_format($m->unitCost, 4) }} {{ optional($m->unit)->label }}</td>
                <td class="usedQty">
                  {{ number_format((float)($m->used_quantity ?? 0)) }}
                </td>
                <td class="totalCost">
                  RM {{ number_format((float)($m->total_cost ?? 0), 2) }}
                </td>
                <td class="text-end" style="display:flex;justify-content:end;position:relative">
                  <button class="kebab" title="Actions" data-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></button>
                  <div class="dropdown-menu" style="position:absolute;right:0;top:40px;background:#fff;border:1px solid var(--border);border-radius:10px;min-width:180px;padding:6px">
                    <button 
                      class="dropdown-item btnEdit"
                      data-id="{{ $m->MaterialID }}"
                      data-name="{{ $m->materialName }}"
                      data-cost="{{ $m->unitCost }}"
                      data-uom="{{ $m->unit_id }}"
                    >
                      <i class="bi bi-pencil me-2"></i> Edit Unit Cost
                    </button>
                    <button class="dropdown-item text-danger btnDelete" data-id="{{ $m->MaterialID }}"><i class="bi bi-trash me-2"></i> Delete</button>
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
        <div class="mt-3 d-flex justify-content-between align-items-center px-2">
          <div class="count text-muted">
            Showing {{ $materials->firstItem() ?? 0 }}–{{ $materials->lastItem() ?? 0 }}
            of {{ $materials->total() }} materials
          </div>
          <div>
            {{ $materials->links('pagination::bootstrap-5') }}
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Keep "Another Data" as-is (do not touch) -->
  <div class="tab-panel" id="anotherData">
  <!-- Header: title left, controls right -->
  <div class="another-head">
    <h2 class="another-title">Costing Data Management</h2>
    <div class="another-controls">
      <input id="adSearch" class="ad-input" type="search" placeholder="Search machine...">
      <select id="adType" class="ad-select" aria-label="All Types">
        <option value="all">All Types</option>
        <option value="Printing">Printing</option>
        <option value="Furnishing">Furnishing</option>
        <option value="Delivery">Delivery</option>
        <option value="Installation">Installation</option>
      </select>
      <select id="adDate" class="ad-select" aria-label="Last 30 Days">
        <option value="30">Last 30 Days</option>
        <option value="7">Last 7 Days</option>
        <option value="m">This Month</option>
        <option value="lm">Last Month</option>
        <option value="all">All Time</option>
      </select>
    </div>
  </div>

  <!-- Card + table -->
  <div class="ad-card">
    <table class="ad-table">
      <thead>
        <tr>
          <th>Product ID</th>
          <th>Product Quantity</th>
          <th class="ad-num">Used Quantity</th>
          <th class="ad-num">Total Cost</th>
          <th class="ad-actions">Actions</th>
        </tr>
      </thead>
      <tbody id="adBody"><!-- dynamic --></tbody>
    </table>
  </div>

  <!-- Footer (range + pager) -->
  <div class="ad-foot">
    <div class="ad-range" id="adRange">Showing 0 to 0 of 0 results</div>
    <div class="ad-pager" id="adPager"><!-- dynamic --></div>
  </div>
</div>
</div>

<!-- ========== MODALS ========== -->
<div class="x-mask" id="mdlType">
  <div class="x">
    <div class="x-hd"><i class="bi bi-tags"></i> Add Material Type</div>
    <div class="x-bd">
      <div class="field">
        <div class="label">Type Name *</div>
        <input id="typeName" type="text" class="control" placeholder="e.g., PVC, Board, Fabric">
      </div>
    </div>
    <div class="x-ft">
      <button class="btn btn-ghost" data-close="mdlType">Cancel</button>
      <button class="btn btn-primary" id="btnSaveType">Save Type</button>
    </div>
  </div>
</div>

<div class="x-mask" id="mdlUnit">
  <div class="x">
    <div class="x-hd"><i class="bi bi-rulers"></i> Add Unit</div>
    <div class="x-bd">
      <div class="field">
        <div class="label">Unit Name *</div>
        <input id="unitName" type="text" class="control" placeholder="e.g., meter, sqft, pcs">
      </div>
      <div class="field">
        <div class="label">Unit Label *</div>
        <input id="unitLabel" type="text" class="control" placeholder="e.g., m, sqft, pcs">
      </div>
    </div>
    <div class="x-ft">
      <button class="btn btn-ghost" data-close="mdlUnit">Cancel</button>
      <button class="btn btn-primary" id="btnSaveUnit">Save Unit</button>
    </div>
  </div>
</div>

<div class="x-mask" id="mdlMaterial">
  <div class="x">
    <div class="x-hd"><i class="bi bi-plus-square"></i> Add Material</div>
    <div class="x-bd">
      <div class="field">
        <div class="label">Material Name *</div>
        <input id="matName" type="text" class="control" placeholder="e.g., PVC Banner, Foam Board">
      </div>
      <div class="field">
        <div class="label">Material Type *</div>
        <select id="matType" class="control">
          <option value="">Select type…</option>
          @foreach($types as $id => $name)
            <option value="{{ $id }}">{{ $name }}</option>
          @endforeach
        </select>
      </div>
      <div class="field">
        <div class="label">Unit *</div>
        <select id="matUnit" class="control">
          <option value="">Select unit…</option>
          @foreach($units as $id => $label)
            <option value="{{ $id }}">{{ $label }}</option>
          @endforeach
        </select>
      </div>
      <div class="field">
        <div class="label">Unit Cost (RM) *</div>
        <input id="matCost" type="number" step="0.0001" class="control" placeholder="0.0000">
      </div>
    </div>
    <div class="x-ft">
      <button class="btn btn-ghost" data-close="mdlMaterial">Cancel</button>
      <button class="btn btn-dark" id="btnSaveMaterial"><i class="bi bi-floppy2"></i> Save Material</button>
    </div>
  </div>
</div>

<div class="x-mask" id="mdlQuickEdit">
  <div class="x">
    <div class="x-hd"><i class="bi bi-pencil-square"></i> Edit Unit Cost</div>
    <div class="x-bd">
      <input type="hidden" id="qeId">
      <div class="field">
        <div class="label">Material Name</div>
        <input id="qeName" type="text" class="control">
      </div>
      <div class="field">
        <div class="label">Current Unit Cost</div>
        <input id="qeCurrent" class="control" disabled>
      </div>
      <div class="field">
        <div class="label">New Unit Cost *</div>
        <input id="qeNew" type="number" step="0.0001" class="control">
      </div>
      <div class="field">
        <div class="label">Unit</div>
        <select id="qeUnit" class="control">
          <option value="">Select unit…</option>
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
  const csrf = document.querySelector('meta[name="csrf-token"]').content;

  // Tabs
  document.addEventListener('click', (e)=>{
    const t = e.target.closest('.tab-btn');
    if(!t) return;
    $$('.tab-btn').forEach(b=>b.classList.remove('active'));
    t.classList.add('active');
    $$('.tab-panel').forEach(p=>p.classList.remove('active'));
    $(t.dataset.target).classList.add('active');
  });

  // Dropdown (simple)
  document.addEventListener('click',(e)=>{
    if(e.target.closest('.kebab')){
      const menu = e.target.closest('td').querySelector('.dropdown-menu');
      menu.style.display = (menu.style.display==='block'?'none':'block');
      return;
    }
    $$('.dropdown-menu').forEach(m=>m.style.display='none');
  });

  function openMask(id){ $('#'+id).classList.add('show'); }
  function closeMask(id){ $('#'+id).classList.remove('show'); }
  document.addEventListener('click',(e)=>{
    const c = e.target.dataset.close;
    if(c) closeMask(c);
  });

  // Filters
  function applyFilter(){
    const q = $('#q').value.toLowerCase().trim();
    const t = $('#typeFilter').value;
    const u = $('#qtyFilter').value;
    let shown = 0;
    $$('#tbl tbody tr').forEach(tr=>{
      const name = tr.children[0].textContent.toLowerCase();
      const passQ = !q || name.includes(q);
      const passT = (t==='all' || tr.dataset.type===t);
      const passU = (u==='all' || tr.dataset.uom===u);
      const ok = passQ && passT && passU;
      tr.style.display = ok ? '' : 'none';
      if(ok) shown++;
    });
    $('#countTotal').textContent = shown;
  }

  ['input','change'].forEach(ev=>{
    document.addEventListener(ev, (e)=>{
      if (['q','typeFilter','qtyFilter'].includes(e.target.id)) applyFilter();
    });
  });

  // Add Type
  $('#btnAddType').addEventListener('click', ()=> openMask('mdlType'));
  $('#btnSaveType').addEventListener('click', ()=>{
    const name = $('#typeName').value.trim();
    if(!name){ alert('Type name is required'); return; }
    fetch("{{ route('boss.material-types.store') }}", {
      method:'POST',
      headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'Accept':'application/json'},
      body:JSON.stringify({typeName:name})
    }).then(r=>r.json()).then(data=>{
      if(data.success){
        const opt = document.createElement('option');
        opt.value = data.id; opt.textContent = data.type;
        $('#typeFilter').appendChild(opt);
        const opt2 = opt.cloneNode(true);
        $('#matType').appendChild(opt2);
        closeMask('mdlType');
      }else{ alert('Failed to save'); }
    }).catch(()=>alert('Error'));
  });

  // Add Unit
  $('#btnAddUnit').addEventListener('click', ()=> openMask('mdlUnit'));
  $('#btnSaveUnit').addEventListener('click', ()=>{
    const name = $('#unitName').value.trim();
    const label = $('#unitLabel').value.trim();
    if(!name||!label){ alert('Both name and label are required'); return; }
    fetch("{{ route('boss.units.store') }}", {
      method:'POST',
      headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'Accept':'application/json'},
      body:JSON.stringify({unitName:name, unitLabel:label})
    }).then(r=>r.json()).then(data=>{
      if(data.success){
        const opt = document.createElement('option');
        opt.value = data.id; opt.textContent = data.label;
        $('#qtyFilter').appendChild(opt);
        const opt2 = opt.cloneNode(true);
        $('#matUnit').appendChild(opt2);
        $('#qeUnit').appendChild(opt.cloneNode(true));
        closeMask('mdlUnit');
      }else{ alert('Failed to save'); }
    }).catch(()=>alert('Error'));
  });

  // Add Material
  $('#btnAddMaterial').addEventListener('click', ()=> openMask('mdlMaterial'));
  $('#btnSaveMaterial').addEventListener('click', ()=>{
    const postData = {
      materialName: $('#matName').value.trim(),
      material_type_id: $('#matType').value,
      unit_id: $('#matUnit').value,
      unitCost: $('#matCost').value
    };
    if(!postData.materialName || !postData.material_type_id || !postData.unit_id || !postData.unitCost){
      alert('Please fill in all required fields'); return;
    }
    fetch("{{ route('boss.materials.store') }}", {
      method:'POST',
      headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'Accept':'application/json'},
      body:JSON.stringify(postData)
    }).then(r=>r.json()).then(data=>{
      if(data.success){
        const m = data.material;
        const tr = document.createElement('tr');
        tr.setAttribute('data-type', m.material_type_id);
        tr.setAttribute('data-uom', m.unit_id);
        tr.innerHTML = `
          <td>${m.materialName}</td>
          <td>${m.materialType ? m.materialType.name : ''}</td>
          <td>RM ${Number(m.unitCost).toFixed(4)} ${m.unit ? m.unit.label : ''}</td>
          <td class="text-end usedQty">-</td>      <!-- NEW -->
          <td class="text-end totalCost">-</td>    <!-- NEW -->
          <td style="text-align:right;position:relative">
            <button class="kebab" title="Actions" data-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></button>
            <div class="dropdown-menu" style="position:absolute;right:0;top:40px;background:#fff;border:1px solid var(--border);border-radius:10px;min-width:180px;padding:6px">
              <button class="dropdown-item btnEdit"
                data-id="${m.MaterialID}"
                data-name="${m.materialName}"
                data-cost="${m.unitCost}"
                data-uom="${m.unit_id}">
                <i class="bi bi-pencil me-2"></i> Edit Unit Cost
              </button>
              <button class="dropdown-item text-danger btnDelete" data-id="${m.MaterialID}"><i class="bi bi-trash me-2"></i> Delete</button>
            </div>
          </td>`;
        $('#tbl tbody').appendChild(tr);
        closeMask('mdlMaterial');
        applyFilter();
      }else{ alert('Failed to save'); }
    }).catch(()=>alert('Error'));
  });

  // Edit (open)
  document.addEventListener('click', (e)=>{
    const btn = e.target.closest('.btnEdit');
    if(!btn) return;
    $('#qeId').value = btn.dataset.id;
    $('#qeName').value = btn.dataset.name || '';
    $('#qeCurrent').value = btn.dataset.cost ? Number(btn.dataset.cost).toFixed(4) : '';
    $('#qeNew').value = btn.dataset.cost || '';
    $('#qeUnit').value = btn.dataset.uom || '';
    openMask('mdlQuickEdit');
  });

  // Save Quick Edit
  $('#btnSaveQuick').addEventListener('click', ()=>{
    const id = $('#qeId').value;
    const postData = {
      qeName: $('#qeName').value,
      qeNew: $('#qeNew').value,
      qeUnit: $('#qeUnit').value
    };
    fetch("{{ route('boss.materials.update', ['id' => '___ID___']) }}".replace('___ID___', id), {
      method:'PUT',
      headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'Accept':'application/json'},
      body:JSON.stringify(postData)
    }).then(r=>r.json()).then(data=>{
      if(data.success){
        const tr = Array.from($('#tbl tbody').children).find(tr => tr.querySelector('.btnEdit')?.dataset.id === id);
        if(tr){
          tr.children[0].textContent = postData.qeName || tr.children[0].textContent;
          tr.children[2].textContent = `RM ${Number(postData.qeNew||0).toFixed(4)} ${data.material.unit.label}`;
          tr.dataset.uom = postData.qeUnit;
          const btn = tr.querySelector('.btnEdit');
          btn.dataset.name = tr.children[0].textContent;
          btn.dataset.cost = postData.qeNew;
          btn.dataset.uom = postData.qeUnit;
        }
        closeMask('mdlQuickEdit');
        applyFilter();
      }else{ alert('Failed to update'); }
    }).catch(()=>alert('Error'));
  });

  // Delete
  document.addEventListener('click', (e)=>{
    const btn = e.target.closest('.btnDelete');
    if(!btn) return;
    if(!confirm('Delete this material?')) return;
    fetch("{{ route('boss.materials.destroy', ['id' => '___ID___']) }}".replace('___ID___', btn.dataset.id), {
      method:'DELETE',
      headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json'}
    }).then(r=>r.json()).then(data=>{
      if(data.success){
        const tr = btn.closest('tr'); tr?.remove();
        applyFilter();
      }else{ alert('Failed to delete'); }
    }).catch(()=>alert('Error'));
  });

  // Init
  window.addEventListener('load', applyFilter);
})();

// ========= PAGINATION =========
let currentPage = 1;
const rowsPerPage = 10;

function paginateTable() {
  const allRows = Array.from(document.querySelectorAll('#tbl tbody tr')).filter(r => r.style.display !== 'none');
  const totalRows = allRows.length;
  const totalPages = Math.max(1, Math.ceil(totalRows / rowsPerPage));
  currentPage = Math.min(currentPage, totalPages);

  allRows.forEach((row, i) => {
    const start = (currentPage - 1) * rowsPerPage;
    const end = start + rowsPerPage;
    row.style.display = (i >= start && i < end) ? '' : 'none';
  });

  document.getElementById('totalCount').textContent = totalRows;
  document.getElementById('shownCount').textContent = Math.min(totalRows, rowsPerPage);
  document.getElementById('pageInfo').textContent = `${currentPage} / ${totalPages}`;
  document.getElementById('prevPage').disabled = currentPage === 1;
  document.getElementById('nextPage').disabled = currentPage === totalPages;
}

document.getElementById('prevPage').addEventListener('click', () => {
  if (currentPage > 1) {
    currentPage--;
    paginateTable();
  }
});

document.getElementById('nextPage').addEventListener('click', () => {
  currentPage++;
  paginateTable();
});

// Modify applyFilter to reapply pagination after filtering
const _oldApplyFilter = applyFilter;
applyFilter = function() {
  _oldApplyFilter();
  currentPage = 1;
  paginateTable();
};

// Initial pagination after load
window.addEventListener('load', paginateTable);

// ============ Another Data (original, with dummy data) ============
const DATA2 = [
  { id:'ORD-2025-001', qtyProducts:6, used:12540, total:37.62, type:'Printing',     days:10 },
  { id:'ORD-2025-002', qtyProducts:4, used: 8320, total:99.84, type:'Furnishing',   days:22 },
  { id:'ORD-2025-003', qtyProducts:3, used: 6750, total:57.38, type:'Delivery',     days:15 },
  { id:'ORD-2025-004', qtyProducts:3, used: 4200, total:27.30, type:'Installation', days:5  },
  { id:'ORD-2025-005', qtyProducts:5, used: 9850, total:44.33, type:'Printing',     days:30 },
  { id:'ORD-2025-006', qtyProducts:2, used: 3400, total:32.30, type:'Furnishing',   days:2  },
  { id:'ORD-2025-007', qtyProducts:2, used: 2980, total:18.90, type:'Delivery',     days:60 },
  { id:'ORD-2025-008', qtyProducts:7, used:15440, total:71.10, type:'Installation', days:9  },
  { id:'ORD-2025-009', qtyProducts:4, used: 7720, total:36.26, type:'Printing',     days:12 },
  { id:'ORD-2025-010', qtyProducts:5, used: 9180, total:49.02, type:'Furnishing',   days:42 },
  { id:'ORD-2025-011', qtyProducts:3, used: 6000, total:27.00, type:'Delivery',     days:8  },
  { id:'ORD-2025-012', qtyProducts:6, used:12010, total:66.55, type:'Installation', days:28 },
];
const PAGE_SIZE2 = 6;
let state2 = { q:'', type:'all', days:'all', page:1 };

const adBody  = document.getElementById('adBody');
const adRange = document.getElementById('adRange');
const adPager = document.getElementById('adPager');

function filterData2(){
  const q = state2.q.toLowerCase();
  const maxDays = state2.days==='all' ? Infinity :
                  state2.days==='m'   ? 31 :
                  state2.days==='lm'  ? 62 : Number(state2.days);
  return DATA2.filter(d=>{
    const hitQ = !q || d.id.toLowerCase().includes(q);
    const hitT = state2.type==='all' || d.type===state2.type;
    const hitD = d.days <= maxDays;
    return hitQ && hitT && hitD;
  });
}
function paginate2(items){
  const start = (state2.page-1)*PAGE_SIZE2;
  const end   = start + PAGE_SIZE2;
  return { slice:items.slice(start,end), start:start+1, end:Math.min(end, items.length), total:items.length };
}
function rowTpl2(d){
  return `
    <tr>
      <td>${d.id}</td>
      <td><a href="javascript:void(0)" class="ad-qty-link">${d.qtyProducts} Products</a></td>
      <td class="ad-num">${d.used.toLocaleString()}</td>
      <td class="ad-num">RM ${d.total.toFixed(2)}</td>
      <td class="ad-actions">
        <button class="ad-eye" aria-label="View"><i class="bi bi-eye"></i></button>
      </td>
    </tr>
  `;
}
function render2(){
  const filtered = filterData2();
  const pg = paginate2(filtered);
  if(pg.total===0){
    adBody.innerHTML = `<tr><td colspan="5" style="padding:20px; color:#667085;">No results found.</td></tr>`;
    adRange.textContent = `Showing 0 to 0 of 0 results`;
    adPager.innerHTML = '';
    return;
  }
  adBody.innerHTML = pg.slice.map(rowTpl2).join('');
  adRange.textContent = `Showing ${pg.start} to ${pg.end} of ${pg.total} results`;

  const pages = Math.ceil(pg.total/PAGE_SIZE2);
  let html = '';
  html += `<button class="ad-nav" ${state2.page<=1?'disabled':''} onclick="gotoPage2(${state2.page-1})"><i class="bi bi-chevron-left"></i></button>`;
  for(let i=1;i<=pages;i++){
    html += `<button class="ad-page ${i===state2.page?'active':''}" onclick="gotoPage2(${i})">${i}</button>`;
  }
  html += `<button class="ad-nav" ${state2.page>=pages?'disabled':''} onclick="gotoPage2(${state2.page+1})"><i class="bi bi-chevron-right"></i></button>`;
  adPager.innerHTML = html;
}
function gotoPage2(p){ state2.page = p; render2(); }

document.getElementById('adSearch').addEventListener('input', e => { state2.q   = e.target.value.trim(); state2.page=1; render2(); });
document.getElementById('adType').addEventListener('change',  e => { state2.type= e.target.value;       state2.page=1; render2(); });
document.getElementById('adDate').addEventListener('change',  e => { state2.days= e.target.value;       state2.page=1; render2(); });

// initial render (only when the tab exists on page)
if (adBody && adRange && adPager) render2();
</script>
@endsection