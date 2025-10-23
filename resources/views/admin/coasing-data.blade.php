@extends('layouts.app')

@section('title','Costing Data Management')

@section('content')
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
  .right-addon{display:flex;align-items:center}
  .right-addon input{border-top-right-radius:0;border-bottom-right-radius:0}
  .addon{height:42px;border:1px solid var(--border);border-left:0;border-radius:0 10px 10px 0;background:#F9FAFB;padding:0 12px;color:var(--muted);display:flex;align-items:center}
  .hidden{display:none!important}
</style>

<div class="page-wrap">
  <div class="card">
    <div class="card-hd">
      <div class="title">Costing Data Management</div>
      <div class="actions">
        <button id="btnAddType" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Material Type</button>
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
          <option>Paper Materials</option>
          <option>Backlit Materials</option>
          <option>Vinyl Materials</option>
          <option>Stickers / Films</option>
        </select>
        <select id="qtyFilter">
          <option value="all">All Quantities</option>
          <option value="perSqInch">Per sq inch</option>
          <option value="perSqFt">Per sq ft</option>
        </select>
      </div>
    </div>

    <div class="table-wrap" style="padding:6px 12px 10px;">
      <table id="tbl">
        <thead>
          <tr>
            <th style="width:40%">Material Name</th>
            <th style="width:28%">Material Type</th>
            <th style="width:22%">Unit Cost (per sq inch)</th>
            <th style="width:10%;text-align:right">Actions</th>
          </tr>
        </thead>
        <tbody>
          @php
            $rows = [
              ['name'=>'Art Card','type'=>'Paper Materials','cost'=>'0.0030','uom'=>'perSqInch'],
              ['name'=>'Art Paper','type'=>'Paper Materials','cost'=>'0.0030','uom'=>'perSqInch'],
              ['name'=>'Backlit Digilight','type'=>'Backlit Materials','cost'=>'0.0600','uom'=>'perSqInch'],
              ['name'=>'Backlit Fabric','type'=>'Backlit Materials','cost'=>'0.0100','uom'=>'perSqInch'],
              ['name'=>'Boxboard Paper','type'=>'Paper Materials','cost'=>'0.0052','uom'=>'perSqInch'],
              ['name'=>'Chipboard 1mm','type'=>'Paper Materials','cost'=>'0.0059','uom'=>'perSqInch'],
              ['name'=>'Chipboard 2mm','type'=>'Paper Materials','cost'=>'0.0069','uom'=>'perSqInch'],
              ['name'=>'Clear Vinyl','type'=>'Vinyl Materials','cost'=>'0.0085','uom'=>'perSqInch'],
              ['name'=>'Synthetic Paper','type'=>'Paper Materials','cost'=>'0.0600','uom'=>'perSqInch'],
              // stickers / films
              ['name'=>'PVC Blockout Sticker','type'=>'Stickers / Films','cost'=>'0.0069','uom'=>'perSqInch'],
              ['name'=>'PVC White Sticker','type'=>'Stickers / Films','cost'=>'0.0069','uom'=>'perSqInch'],
              ['name'=>'Synthetic Sticker','type'=>'Stickers / Films','cost'=>'0.0060','uom'=>'perSqInch'],
              ['name'=>'One Way Vision Sticker','type'=>'Stickers / Films','cost'=>'0.0075','uom'=>'perSqInch'],
              ['name'=>'Ultra Clear Sticker','type'=>'Stickers / Films','cost'=>'0.0085','uom'=>'perSqInch'],
              ['name'=>'Special Color Sticker','type'=>'Stickers / Films','cost'=>'0.0350','uom'=>'perSqInch'],
              ['name'=>'Magnetic Printable Paper','type'=>'Stickers / Films','cost'=>'0.0069','uom'=>'perSqInch'],
              ['name'=>'Adhesive Magnetic Film','type'=>'Stickers / Films','cost'=>'0.0050','uom'=>'perSqInch'],
              ['name'=>'Iron Base Printable PP','type'=>'Stickers / Films','cost'=>'0.0069','uom'=>'perSqInch'],
            ];
          @endphp

          @foreach($rows as $i => $r)
            <tr data-type="{{ $r['type'] }}" data-uom="{{ $r['uom'] }}">
              <td>{{ $r['name'] }}</td>
              <td>{{ $r['type'] }}</td>
              <td>RM {{ number_format($r['cost'],4) }}</td>
              <td style="text-align:right; position:relative">
                <button class="kebab" data-toggle="dropdown" aria-expanded="false" title="Actions">
                  <i class="bi bi-three-dots-vertical"></i>
                </button>
                <div class="dropdown-menu">
                  <button class="dropdown-item btnEdit" data-name="{{ $r['name'] }}" data-type="{{ $r['type'] }}" data-cost="{{ $r['cost'] }}">
                    <i class="bi bi-pencil me-2"></i> Edit Unit Cost
                  </button>
                  <button class="dropdown-item text-danger btnDelete"><i class="bi bi-trash me-2"></i> Delete</button>
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
        Showing <span id="countShown">9</span> of <span id="countTotal">{{ count($rows) }}</span> materials
      </div>
    </div>
  </div>
</div>

{{-- ===== Modals ===== --}}

{{-- Add Material Type --}}
<div id="mdlType" class="x-mask" aria-hidden="true">
  <div class="x-dialog" role="dialog" aria-modal="true">
    <div class="x-hd">
      <div class="x-ttl">Add New Material Type</div>
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

{{-- Add / Edit Material --}}
<div id="mdlMaterial" class="x-mask" aria-hidden="true">
  <div class="x-dialog" role="dialog" aria-modal="true">
    <div class="x-hd">
      <div class="x-ttl" id="materialTitle">Material Information</div>
      <button class="kebab" data-close="mdlMaterial" aria-label="Close"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="x-bd">
      <div class="field">
        <div class="label">Material Name *</div>
        <input id="matName" type="text" class="control" placeholder="e.g., PVC Banner, Foam Core Board">
      </div>
      <div class="field">
        <div class="label">Material Category</div>
        <select id="matType" class="control">
          <option value="">Select category...</option>
          <option>Paper Materials</option>
          <option>Backlit Materials</option>
          <option>Vinyl Materials</option>
          <option>Stickers / Films</option>
        </select>
      </div>
      <div class="field">
        <div class="label">Unit Cost (RM) *</div>
        <div class="right-addon">
          <input id="matCost" type="number" step="0.0001" class="control" placeholder="6.50">
          <div class="addon">/ sqft</div>
        </div>
      </div>
      <div class="field hidden" id="currentWrap">
        <div class="label">Current Unit Cost</div>
        <input id="currentCost" class="control" disabled>
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
      <div class="field">
        <div class="label">New Material Name</div>
        <input id="qeName" type="text" class="control" placeholder="e.g., PVC Banner, Foam Core Board">
      </div>
      <div class="field">
        <div class="label">Current Unit Cost</div>
        <input id="qeCurrent" class="control" disabled>
      </div>
      <div class="field">
        <div class="label">New Unit Cost *</div>
        <div class="right-addon">
          <input id="qeNew" type="number" step="0.0001" class="control" placeholder="6.50">
          <div class="addon">/ sqft</div>
        </div>
        <div style="font-size:12px;color:var(--muted);margin-top:6px">Enter the new unit cost for this material</div>
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
  const $  = s => document.querySelector(s);
  const $$ = s => Array.from(document.querySelectorAll(s));

  // show/hide
  function openMask(id){ const m = document.getElementById(id); if(m) m.style.display='flex' }
  function closeMask(id){ const m = document.getElementById(id); if(m) m.style.display='none' }

  // filter
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
    const cs = document.getElementById('countShown');
    if (cs) cs.textContent = shown;
  }

  // events
  document.addEventListener('click', (e)=>{
    const t = e.target;

    // open add type
    if (t.closest('#btnAddType')) { openMask('mdlType'); return; }
    // open add material
    if (t.closest('#btnAddMaterial')) {
      $('#materialTitle').innerText = 'Material Information';
      $('#matName').value=''; $('#matType').value=''; $('#matCost').value='';
      $('#currentWrap').classList.add('hidden');
      openMask('mdlMaterial'); return;
    }
    // close by data-close
    const closer = t.closest('[data-close]');
    if (closer){ closeMask(closer.getAttribute('data-close')); return; }

    // dropdown
    const toggle = t.closest('[data-toggle="dropdown"]');
    $$('.dropdown-menu').forEach(m=>{
      if (toggle && m.previousElementSibling === toggle){
        m.style.display = m.style.display==='block' ? 'none' : 'block';
      } else {
        m.style.display = 'none';
      }
    });

    // edit unit cost
    const editBtn = t.closest('.btnEdit');
    if (editBtn){
      const name = editBtn.dataset.name;
      const cost = editBtn.dataset.cost;
      $('#qeTitle').innerText = `Edit Unit Cost – ${name}`;
      $('#qeName').value = name;
      $('#qeCurrent').value = `RM ${Number(cost).toFixed(2)} / sqft`;
      $('#qeNew').value = '';
      openMask('mdlQuickEdit');
      return;
    }

    // save type
    if (t.closest('#saveTypeBtn')){
      const name = ($('#typeName')?.value || '').trim();
      if(!name) return alert('Please enter a material type name');
      ['typeFilter','matType'].forEach(id=>{
        const sel = document.getElementById(id);
        if (sel){ const o = document.createElement('option'); o.textContent=name; sel.appendChild(o); }
      });
      closeMask('mdlType'); return;
    }

    // save material (demo)
    if (t.closest('#btnSaveMaterial')){
      alert('Saved (demo). Connect to your store endpoint.');
      closeMask('mdlMaterial'); return;
    }

    // save quick edit (demo)
    if (t.closest('#btnSaveQuick')){
      alert('Unit cost updated (demo). Connect to your update endpoint.');
      closeMask('mdlQuickEdit'); return;
    }
  });

  // search & filters
  ['input','change'].forEach(ev=>{
    document.addEventListener(ev, (e)=>{
      if (['q','typeFilter','qtyFilter'].includes(e.target.id)) applyFilter();
    });
  });

  window.addEventListener('load', applyFilter);
})();
</script>
@endsection
