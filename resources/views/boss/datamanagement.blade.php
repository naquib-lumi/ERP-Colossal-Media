@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
/* ===================== Global & Tabs ===================== */
:root{
  --bg:#F9FAFB; --card:#FFFFFF; --border:#E5E7EB; --thead:#F9FAFB;
  --text:#0f172a; --muted:#667085; --chip:#F2F4F7; --shadow:0 3px 10px rgba(16,24,40,.06);
  --primary:#2563EB; --primary-hover:#1D4ED8;
  --tab-fg:#1f2937; --tab-muted:#6b7280; --tab-active:#3b5bfd;
}
body{background:var(--bg);}
.page-wrap{max-width:1140px;margin:0 auto; padding:8px 0 28px;}
.header-row{display:block; margin:0 18px 6px;}
.hd-title{margin:0; font-size:22px; font-weight:800; color:var(--text)}

.tabs{ display:flex; gap:24px; padding:6px 18px 0; }
.tab-btn{
  position:relative; appearance:none; background:none; border:0;
  padding:10px 0 12px; cursor:pointer; white-space:nowrap;
  font:600 14.5px/1.2 ui-sans-serif,system-ui,-apple-system,Segoe UI,Roboto,Arial;
  color:var(--tab-muted); outline:none;
}
.tab-btn[aria-selected="true"]{ color:var(--tab-fg); }
.tab-btn[aria-selected="true"]::after{
  content:""; position:absolute; left:0; right:0; bottom:-1px;
  height:3px; border-radius:3px; background:var(--tab-active);
}
.tabs-border{ height:1px; background:#eef2f7; margin:0 18px 10px; }
.panels{ padding:0; }
.tab-panel{ display:none; }
.tab-panel.active{ display:block; }

/* ===================== Cost Data（保持你之前风格） ===================== */
.table-section{
  background:var(--card); border:1px solid var(--border);
  border-radius:16px; box-shadow:var(--shadow);
  overflow:hidden; margin:0 18px 18px;
}
.section-toolbar{
  display:flex; align-items:center; gap:12px; flex-wrap:wrap;
  padding:14px 16px; border-bottom:1px solid var(--border); background:#fff;
}
.toolbar-left{ flex:1 1 280px; min-width:240px; }
.toolbar-filters{ flex:0 1 auto; display:flex; gap:10px; }
.toolbar-actions{ margin-left:auto; display:flex; gap:10px; }

.control{
  height:40px;border:1px solid var(--border);border-radius:10px;background:#fff;outline:none;color:#101828;font-size:14px;
}
.control.input{ width:100%; min-width:280px; padding:0 12px; }
.control.select{
  padding:0 36px 0 12px; min-width:160px; appearance:none;
  background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16'%3E%3Cpath fill='%23667085' d='M4.47 6.97a.75.75 0 0 1 1.06 0L8 9.44l2.47-2.47a.75.75 0 0 1 1.06 1.06l-3 3a.75.75 0 0 1-1.06 0l-3-3a.75.75 0 0 1 0-1.06Z'/%3E%3C/svg%3E");
  background-repeat:no-repeat; background-position:right 12px center; padding-right:40px;
}
.btn-rect{height:40px;border-radius:8px;padding:0 14px;font-weight:500; display:inline-flex; align-items:center; gap:8px; line-height:1;}
.btn-secondary.soft{background:#fff;border:1px solid var(--border);color:#344054}
.btn-secondary.soft:hover{background:#F9FAFB}
.btn-dark{background:var(--primary);border:1px solid var(--primary);color:#fff}
.btn-dark:hover{background:var(--primary-hover);border-color:var(--primary-hover)}

.section-body{background:#fff;}
.table-wrap{border:none;border-radius:0;overflow:hidden;background:#fff;margin:0}
.table{margin:0;border-collapse:separate;border-spacing:0;width:100%}
.table thead th{background:var(--thead);color:#344054;font-weight:600;letter-spacing:.02em;white-space:nowrap;padding:14px 16px!important}
.table>:not(caption)>*>*{padding:14px 16px;vertical-align:middle}
.table tbody tr+tr td{border-top:1px solid #EEF2F7}
.table tbody tr:hover{background:#F9FAFB}
.col-actions{text-align:right}
.th-num,.td-num{text-align:right}

.section-foot{ padding:10px 12px; background:#fff; border-top:1px solid var(--border);
  display:flex; align-items:center; justify-content:space-between; }
.range-text{ font-size:13px; color:#6b7280; }
.pager{display:flex;align-items:center;gap:6px}
.page-btn{min-width:34px;height:34px;border:1px solid var(--border);border-radius:8px;background:#fff;color:#344054}
.page-btn.active{background:var(--primary);color:#fff;border-color:var(--primary)}
.page-btn:disabled{opacity:.5}
.page-btn.icon{display:grid;place-items:center;width:34px}

.badge-chip{display:inline-flex;align-items:center;gap:6px;padding:4px 9px;border-radius:999px;background:var(--chip);color:#344054;font-weight:600;font-size:11.5px}
.usage-scroll{display:flex;gap:6px;flex-wrap:wrap;row-gap:6px}
.kebab{border:0;background:transparent;padding:6px;border-radius:8px;color:#475467;line-height:1;transition:background .15s,color .15s}
.kebab:hover{background:#F2F4F7;color:#1F2937}

/* Modals 默认隐藏（避免摊开） */
.modal-backdrop-custom,.modal-backdrop-edit,.modal-backdrop-type{
  position:fixed; inset:0; background:rgba(17,24,39,.55);
  display:none !important; align-items:center; justify-content:center; z-index:1060;
}
.modal-backdrop-custom.open,.modal-backdrop-edit.open,.modal-backdrop-type.open{ display:flex !important; }
.modal-card,.modal-edit,.modal-type{width:min(520px,92vw);background:#fff;border-radius:12px;box-shadow:0 10px 30px rgba(16,24,40,.25);overflow:hidden}
.modal-body{padding:22px 22px 18px}
.modal-title{font-size:18px;font-weight:700;color:#111827;margin:0 0 14px}
.form-text-sm{font-size:12px;color:#667085;margin-bottom:6px}
.modal-footer{display:flex;gap:10px;justify-content:flex-end;padding:0 22px 20px}
.input-suffix{position:relative}
.input-suffix input.form-control{padding-right:64px}
.input-suffix .suffix{position:absolute;right:10px;top:50%;transform:translateY(-50%);color:#98A2B3;font-weight:600}
.btn-secondary-soft{background:#F3F4F6;border:1px solid #E5E7EB;color:#111827}
.btn-secondary-soft:hover{background:#E5E7EB}
.modal-edit .head{display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid #EEF2F7}
.modal-edit .head .title{font-weight:700;color:#111827}
.modal-edit .head .close{border:0;background:transparent;color:#667085;font-size:20px;line-height:1}
.modal-edit .body{padding:16px 20px 2px}
.modal-edit .foot{padding:14px 20px 18px;display:flex;gap:10px;justify-content:flex-end;border-top:1px solid #EEF2F7}
.modal-edit label{font-size:12px;color:#667085;margin-bottom:6px}
.modal-edit .form-control[readonly]{background:#F9FAFB}
.modal-type .head{display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-bottom:1px solid #EEF2F7;font-weight:700;color:#111827}
.modal-type .close{border:0;background:transparent;color:#667085;font-size:20px}
.modal-type .body{padding:16px 18px}
.modal-type .foot{padding:12px 18px 16px;display:flex;gap:10px;justify-content:flex-end;border-top:1px solid #EEF2F7}
.modal-type label{font-size:12px;color:#667085;margin-bottom:6px}

/* ===================== Another Data（按你截图风格） ===================== */
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

/* 大圆角卡片、轻边框、淡表头（覆盖任何外部样式） */
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

/* 强制右对齐（压过框架默认） */
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

/* 底部说明 & 分页（居右、紧凑） */
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

<div class="container-fluid py-4 px-4">
  <div class="page-wrap">
    <div class="header-row">
      <h1 class="hd-title">Data Management</h1>
    </div>

    <!-- Tabs -->
    <div class="tabs" role="tablist" aria-label="Data Management Tabs">
      <button class="tab-btn" role="tab" id="tab-cost"    aria-controls="panel-cost"    aria-selected="true">Cost Data</button>
      <button class="tab-btn" role="tab" id="tab-another" aria-controls="panel-another" aria-selected="false">Another Data</button>
    </div>
    <div class="tabs-border"></div>

    <div class="panels">
      <!-- ======================= Cost Data ======================= -->
      <section id="panel-cost" class="tab-panel active" role="tabpanel" aria-labelledby="tab-cost">
        <div class="header-row" style="margin-top:12px;">
          <h1 class="hd-title">Costing Data Management</h1>
        </div>

        <div class="table-section">
          <div class="section-toolbar">
            <div class="toolbar-left">
              <input id="searchInput" type="text" class="control input" placeholder="Search machine / material...">
            </div>
            <div class="toolbar-filters">
              <select id="typeFilter" class="control select" aria-label="All Types">
                <option value="all">All Types</option>
                <option value="Paper Materials">Paper Materials</option>
                <option value="Backlit Materials">Backlit Materials</option>
                <option value="Board Materials">Board Materials</option>
              </select>
              <select id="dateFilter" class="control select" aria-label="Last 30 Days">
                <option value="30">Last 30 Days</option>
                <option value="90">Last 90 Days</option>
                <option value="365">Last 12 Months</option>
                <option value="all">All Time</option>
              </select>
            </div>
            <div class="toolbar-actions">
              <button class="btn btn-secondary soft btn-rect" id="btnAddType">
                <i class="bi bi-plus-lg"></i> Add Material Type
              </button>
              <button class="btn btn-dark btn-rect" id="btnAddMaterial">
                <i class="bi bi-plus-lg"></i> Add Material
              </button>
            </div>
          </div>

          <div class="section-body">
            <div class="table-wrap">
              <div class="table-responsive">
                <table class="table align-middle" id="costingTable">
                  <thead>
                    <tr>
                      <th class="text-uppercase small">Material Name</th>
                      <th class="text-uppercase small">Machine Type</th>
                      <th class="text-uppercase small th-num">Unit Cost (per sq inch)</th>
                      <th class="text-uppercase small th-num">Used Quantity</th>
                      <th class="text-uppercase small th-num">Total Cost</th>
                      <th class="text-uppercase small">Past Usage</th>
                      <th class="col-actions text-uppercase small">Actions</th>
                    </tr>
                  </thead>
                  <tbody id="tableBody"><!-- dynamic --></tbody>
                </table>
              </div>
            </div>
          </div>

          <div class="section-foot">
            <div id="rangeText" class="range-text">Showing 0 to 0 of 0 results</div>
            <div class="pager" id="pager"><!-- dynamic --></div>
          </div>
        </div>
      </section>

      <!-- ======================= Another Data（你的设计） ======================= -->
      <section id="panel-another" class="tab-panel" role="tabpanel" aria-labelledby="tab-another">
        <!-- 头部：标题在左、搜索+筛选在右 -->
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

        <!-- 表格大卡 -->
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

        <!-- 底部说明 & 分页 -->
        <div class="ad-foot">
          <div class="ad-range" id="adRange">Showing 0 to 0 of 0 results</div>
          <div class="ad-pager" id="adPager"><!-- dynamic --></div>
        </div>
      </section>
    </div>
  </div>
</div>

<!-- =================== Modals（供 Cost Data 使用） =================== -->
<!-- Add Material Modal -->
<div class="modal-backdrop-custom" id="addMaterialModal" aria-hidden="true" role="dialog" aria-modal="true">
  <div class="modal-card" role="document">
    <div class="modal-body">
      <h3 class="modal-title">Material Information</h3>

      <label class="form-text-sm">Material Name <span class="text-danger">*</span></label>
      <input id="matName" type="text" class="form-control mb-3" placeholder="e.g., PVC Banner, Foam Core Board" autocomplete="off">

      <label class="form-text-sm">Material Category</label>
      <select id="matCategory" class="form-select mb-3">
        <option value="" selected disabled>Select category...</option>
        <option>Paper Materials</option>
        <option>Backlit Materials</option>
        <option>Board Materials</option>
      </select>

      <label class="form-text-sm">Unit Cost (RM) <span class="text-danger">*</span></label>
      <div class="input-suffix mb-1">
        <input id="matUnit" type="number" step="0.0001" min="0" class="form-control" placeholder="6.50">
        <span class="suffix">/ sqft</span>
      </div>
      <div id="matErr" class="text-danger small mt-1" style="display:none;"></div>
    </div>

    <div class="modal-footer">
      <button type="button" class="btn btn-secondary-soft btn-rect" id="btnMatCancel">Cancel</button>
      <button type="button" class="btn btn-dark btn-rect" id="btnMatSave"><i class="bi bi-save me-1"></i>Save Material</button>
    </div>
  </div>
</div>

<!-- Edit Unit Cost Modal -->
<div class="modal-backdrop-edit" id="editCostModal" aria-hidden="true" role="dialog" aria-modal="true">
  <div class="modal-edit" role="document">
    <div class="head">
      <div class="title" id="editTitle">Edit Unit Cost</div>
      <button class="close" id="editClose" aria-label="Close"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="body">
      <label>New Material Name</label>
      <input type="text" class="form-control mb-3" id="editNewName" placeholder="e.g., PVC Banner, Foam Core Board">

      <label>Current Unit Cost</label>
      <input type="text" class="form-control mb-3" id="editCurrent" readonly>

      <label>New Unit Cost*</label>
      <div class="input-suffix mb-1">
        <input type="number" step="0.0001" min="0" class="form-control" id="editNewUnit" placeholder="6.50">
        <span class="suffix">/ sqft</span>
      </div>
      <div id="editErr" class="text-danger small mt-2" style="display:none;"></div>
    </div>
    <div class="foot">
      <button type="button" class="btn btn-secondary-soft btn-rect" id="editCancel">Cancel</button>
      <button type="button" class="btn btn-dark btn-rect" id="editSave"><i class="bi bi-save me-1"></i>Save Changes</button>
    </div>
  </div>
</div>

<!-- Add Material Type Modal -->
<div class="modal-backdrop-type" id="typeModal" aria-hidden="true" role="dialog" aria-modal="true">
  <div class="modal-type" role="document">
    <div class="head">
      <div>Add New Material Type</div>
      <button class="close" id="typeClose" aria-label="Close"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="body">
      <label>Material Type Name</label>
      <input type="text" class="form-control" id="typeName" placeholder="e.g. Backlit Materials" autocomplete="off">
      <div id="typeErr" class="text-danger small mt-2" style="display:none;"></div>
    </div>
    <div class="foot">
      <button type="button" class="btn btn-secondary-soft btn-rect" id="typeCancel">Cancel</button>
      <button type="button" class="btn btn-dark btn-rect" id="typeSave">Save</button>
    </div>
  </div>
</div>

<script>
/* ============ Tabs（含 URL hash） ============ */
(function () {
  const tabs   = Array.from(document.querySelectorAll('.tab-btn'));
  const panels = Array.from(document.querySelectorAll('.tab-panel'));
  const map = { 'tab-cost':'panel-cost', 'tab-another':'panel-another' };

  function activate(tabId){
    tabs.forEach(t=>t.setAttribute('aria-selected', String(t.id===tabId)));
    panels.forEach(p=>p.classList.toggle('active', p.id===map[tabId]));
  }
  tabs.forEach(btn=>{
    btn.addEventListener('click', ()=>{
      activate(btn.id);
      history.replaceState(null, '', btn.id==='tab-another' ? '#another' : '#cost');
    });
    btn.addEventListener('keydown', (e)=>{
      if(e.key==='ArrowRight' || e.key==='ArrowLeft'){
        const dir = e.key==='ArrowRight' ? 1 : -1;
        const index = tabs.indexOf(btn);
        const next = tabs[(index + dir + tabs.length) % tabs.length];
        next.focus(); next.click();
      }
    });
  });
  const hash = (location.hash||'').toLowerCase();
  if(hash==='#another'){ activate('tab-another'); } else { activate('tab-cost'); }
})();

/* ============ Cost Data 逻辑（原样保留） ============ */
const DATA = [
  { name:'Art Card',       type:'Paper Materials',   unit:0.0030, qty:12540, total:37.62, usage:['#ORD005-P1','#ORD006-P1','#ORD007-P1'], days:12 },
  { name:'Art Paper',      type:'Paper Materials',   unit:0.0030, qty:8320,  total:99.84, usage:['#ORD003-C1','#ORD008-C1'], days:28 },
  { name:'Backlit Fabric', type:'Backlit Materials', unit:0.0100, qty:4200,  total:27.30, usage:['#ORD002-C2','#ORD010-C2'], days:7 },
  { name:'Chipboard 1mm',  type:'Board Materials',   unit:0.0059, qty:3400,  total:32.30, usage:['#ORD012-C3','#ORD013-C3'], days:17 },
  { name:'Foamboard 3mm',  type:'Board Materials',   unit:0.0080, qty:5120,  total:41.00, usage:['#ORD014-P1'], days:10 },
  { name:'Newsprint',      type:'Paper Materials',   unit:0.0015, qty:18200, total:27.30, usage:['#ORD018-C1','#ORD019-C2'], days:4 },
];
const PAGE_SIZE = 6;
let state = { q:'', type:'all', days:'30', page:1 };
const elBody  = document.getElementById('tableBody');
const elRange = document.getElementById('rangeText');
const elPager = document.getElementById('pager');

function formatMoney(n){ return 'RM ' + n.toFixed(2); }
function formatUnit(n){ return 'RM ' + n.toFixed(4); }
function fmtInt(n){ return n.toLocaleString(); }

function filterData(){
  const q = state.q.toLowerCase();
  const maxDays = state.days==='all' ? Infinity : Number(state.days);
  return DATA.filter(d=>{
    const hitQ = !q || d.name.toLowerCase().includes(q) || d.type.toLowerCase().includes(q);
    const hitT = state.type==='all' || d.type===state.type;
    const hitD = d.days <= maxDays;
    return hitQ && hitT && hitD;
  });
}
function paginate(items){
  const start = (state.page-1)*PAGE_SIZE;
  const end   = start + PAGE_SIZE;
  return { slice:items.slice(start,end), start:start+1, end:Math.min(end,items.length), total:items.length };
}
function chip(str){ return `<span class="badge-chip">${str}</span>`; }
function rowTpl(d, idx){
  const chips = d.usage.slice(0,3).map(chip).join('');
  const extra = d.usage.length>3 ? chip(`+${d.usage.length-3} more`) : '';
  return `
    <tr>
      <td class="fw-500 text-dark">${d.name}</td>
      <td class="text-muted">${d.type}</td>
      <td class="td-num">${formatUnit(d.unit)}</td>
      <td class="td-num">${fmtInt(d.qty)}</td>
      <td class="td-num">${formatMoney(d.total)}</td>
      <td><div class="usage-scroll">${chips}${extra}</div></td>
      <td class="col-actions">
        <button class="kebab btn-action" data-idx="${idx}" aria-label="More actions">
          <i class="bi bi-three-dots-vertical"></i>
        </button>
      </td>
    </tr>
  `;
}
function render(){
  const filtered = filterData();
  const pg = paginate(filtered);
  if(pg.total===0){
    elBody.innerHTML = `<tr><td colspan="7"><div class="empty">No results found.</div></td></tr>`;
    elRange.textContent = `Showing 0 to 0 of 0 results`;
    elPager.innerHTML = '';
    return;
  }
  elBody.innerHTML = pg.slice.map((d,i)=>rowTpl(d,(state.page-1)*PAGE_SIZE+i)).join('');
  elRange.textContent = `Showing ${pg.start} to ${pg.end} of ${pg.total} results`;
  const pages = Math.ceil(pg.total/PAGE_SIZE);
  let html = '';
  html += `<button class="page-btn icon" ${state.page<=1?'disabled':''} onclick="gotoPage(${state.page-1})"><i class="bi bi-chevron-left"></i></button>`;
  for(let i=1;i<=pages;i++){ html += `<button class="page-btn ${i===state.page?'active':''}" onclick="gotoPage(${i})">${i}</button>`; }
  html += `<button class="page-btn icon" ${state.page>=pages?'disabled':''} onclick="gotoPage(${state.page+1})"><i class="bi bi-chevron-right"></i></button>`;
  elPager.innerHTML = html;
}
function gotoPage(p){ state.page=p; render(); }
document.getElementById('searchInput').addEventListener('input', e=>{ state.q=e.target.value.trim(); state.page=1; render(); });
document.getElementById('typeFilter').addEventListener('change', e=>{ state.type=e.target.value; state.page=1; render(); });
document.getElementById('dateFilter').addEventListener('change', e=>{ state.days=e.target.value; state.page=1; render(); });

/* Add Material Modal */
const $modal = document.getElementById('addMaterialModal');
const $matName = document.getElementById('matName');
const $matCategory = document.getElementById('matCategory');
const $matUnit = document.getElementById('matUnit');
const $matErr = document.getElementById('matErr');
document.getElementById('btnAddMaterial').addEventListener('click', openAddMaterial);
document.getElementById('btnMatCancel').addEventListener('click', closeAddMaterial);
function openAddMaterial(){ $modal.classList.add('open'); $modal.setAttribute('aria-hidden','false'); $matName.value=''; $matCategory.selectedIndex=0; $matUnit.value=''; $matErr.style.display='none'; setTimeout(()=> $matName.focus(), 50); }
function closeAddMaterial(){ $modal.classList.remove('open'); $modal.setAttribute('aria-hidden','true'); }
$modal.addEventListener('click', e=>{ if(e.target===$modal) closeAddMaterial(); });
document.addEventListener('keydown', e=>{ if(e.key==='Escape' && $modal.classList.contains('open')) closeAddMaterial(); });
document.getElementById('btnMatSave').addEventListener('click', ()=>{ const name=$matName.value.trim(); const type=$matCategory.value || 'Paper Materials'; const unit=Number($matUnit.value); if(!name){ return showErr('Please enter material name.'); } if(!(unit>=0)){ return showErr('Please enter a valid unit cost.'); } DATA.unshift({ name, type, unit, qty:0, total:0, usage:[], days:0 }); state.page=1; render(); closeAddMaterial(); });
function showErr(msg){ $matErr.textContent=msg; $matErr.style.display='block'; }

/* Edit Unit Cost Modal */
const editModal=document.getElementById('editCostModal');
const editTitle=document.getElementById('editTitle');
const editNewName=document.getElementById('editNewName');
const editCurrent=document.getElementById('editCurrent');
const editNewUnit=document.getElementById('editNewUnit');
const editErr=document.getElementById('editErr');
let editingIndex=null;
elBody.addEventListener('click', e=>{
  const btn=e.target.closest('.btn-action'); if(!btn) return;
  const idx=Number(btn.dataset.idx); const item=DATA[idx]; editingIndex=idx;
  editTitle.textContent=`Edit Unit Cost – ${item.name}`;
  editNewName.value=item.name; editCurrent.value=`${formatUnit(item.unit)} / sqft`.replace('RM ','RM '); editNewUnit.value=item.unit.toFixed(4);
  editErr.style.display='none'; editModal.classList.add('open'); editModal.setAttribute('aria-hidden','false');
  setTimeout(()=>editNewUnit.focus(), 30);
});
document.getElementById('editClose').addEventListener('click', closeEdit);
document.getElementById('editCancel').addEventListener('click', closeEdit);
editModal.addEventListener('click', e=>{ if(e.target===editModal) closeEdit(); });
document.addEventListener('keydown', e=>{ if(e.key==='Escape' && editModal.classList.contains('open')) closeEdit(); });
function closeEdit(){ editModal.classList.remove('open'); editModal.setAttribute('aria-hidden','true'); editingIndex=null; }

/* Add Material Type */
const TYPES = Array.from(document.querySelectorAll('#typeFilter option')).map(o=>o.value).filter(v=>v && v!=='all');
const typeModal=document.getElementById('typeModal');
const typeName=document.getElementById('typeName');
const typeErr=document.getElementById('typeErr');
function refreshTypeOptions(selectedNew=''){
  const typeFilter=document.getElementById('typeFilter');
  const keepAll=typeFilter.querySelector('option[value="all"]');
  typeFilter.innerHTML=''; typeFilter.appendChild(keepAll.cloneNode(true));
  TYPES.forEach(t=>{ const opt=document.createElement('option'); opt.value=opt.textContent=t; typeFilter.appendChild(opt); });
  if(selectedNew) typeFilter.value=selectedNew;
  const cat=document.getElementById('matCategory');
  const first=cat.querySelector('option[disabled]')?.cloneNode(true);
  cat.innerHTML=''; if(first) cat.appendChild(first);
  TYPES.forEach(t=>{ const opt=document.createElement('option'); opt.textContent=t; cat.appendChild(opt); });
  if(selectedNew) cat.value=selectedNew;
}
function openTypeModal(){ typeName.value=''; typeErr.style.display='none'; typeModal.classList.add('open'); typeModal.setAttribute('aria-hidden','false'); setTimeout(()=>typeName.focus(),30); }
function closeTypeModal(){ typeModal.classList.remove('open'); typeModal.setAttribute('aria-hidden','true'); }
document.getElementById('btnAddType')?.addEventListener('click', openTypeModal);
document.getElementById('typeClose')?.addEventListener('click', closeTypeModal);
document.getElementById('typeCancel')?.addEventListener('click', closeTypeModal);
typeModal.addEventListener('click', e=>{ if(e.target===typeModal) closeTypeModal(); });
document.addEventListener('keydown', e=>{ if(e.key==='Escape' && typeModal.classList.contains('open')) closeTypeModal(); });
document.getElementById('typeSave')?.addEventListener('click', ()=>{ const name=(typeName.value||'').trim(); if(!name){ typeErr.textContent='Please enter a material type name.'; typeErr.style.display='block'; return; } const exists=TYPES.some(t=>t.toLowerCase()===name.toLowerCase()); if(exists){ typeErr.textContent='This type already exists.'; typeErr.style.display='block'; return; } TYPES.push(name); refreshTypeOptions(name); state.type='all'; render(); closeTypeModal(); });
document.addEventListener('DOMContentLoaded', ()=>{ refreshTypeOptions(); render(); });

/* ============ Another Data（独立逻辑） ============ */
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
/* 默认 All Time，与你示例的 12 条一致 */
let state2 = { q:'', type:'all', days:'all', page:1 };
const adBody = document.getElementById('adBody');
const adRange = document.getElementById('adRange');
const adPager = document.getElementById('adPager');

function filterData2(){
  const q = state2.q.toLowerCase();
  const maxDays = state2.days==='all' ? Infinity :
                  state2.days==='m' ? 31 :
                  state2.days==='lm' ? 62 : Number(state2.days);
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
  return { slice:items.slice(start,end), start:start+1, end:Math.min(end,items.length), total:items.length };
}
function rowTpl2(d){
  return `
    <tr>
      <td>${d.id}</td>
      <td><a href="javascript:void(0)" class="ad-qty-link">${d.qtyProducts} Products</a></td>
      <td class="ad-num">${d.used.toLocaleString()}</td>
      <td class="ad-num">RM ${d.total.toFixed(2)}</td>
      <td class="ad-actions"><button class="ad-eye" aria-label="View"><i class="bi bi-eye"></i></button></td>
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
function gotoPage2(p){ state2.page=p; render2(); }

document.getElementById('adSearch').addEventListener('input', e=>{ state2.q=e.target.value.trim(); state2.page=1; render2(); });
document.getElementById('adType').addEventListener('change', e=>{ state2.type=e.target.value; state2.page=1; render2(); });
document.getElementById('adDate').addEventListener('change', e=>{ state2.days=e.target.value; state2.page=1; render2(); });
document.addEventListener('DOMContentLoaded', ()=>{ render2(); });
</script>
@endsection
