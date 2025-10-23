@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
/* ===== Theme (white clean) – 与上一页一致 ===== */
:root{
  --bg:#F9FAFB; --card:#FFFFFF; --border:#E5E7EB; --thead:#F9FAFB;
  --text:#101828; --muted:#667085; --chip:#F2F4F7;
  --shadow:0 3px 10px rgba(16,24,40,.06);
  --primary:#2563EB; --primary-hover:#1D4ED8;
  --success:#16A34A; --danger:#DC2626;
}
body{background:var(--bg);}
.page-wrap{max-width:1140px;margin:0 auto}

/* ===== Title ===== */
.header-row{display:block;margin:0 18px 12px;}
.hd-title{margin:0;font-size:22px;font-weight:800;color:var(--text)}

/* ===== White Section Card (toolbar + table + pagination) ===== */
.table-section{
  background:var(--card); border:1px solid var(--border); border-radius:16px;
  box-shadow:var(--shadow); overflow:hidden; margin:0 18px 18px;
}

/* Toolbar */
.section-toolbar{
  display:flex;align-items:center;gap:12px;flex-wrap:wrap;
  padding:14px 16px;border-bottom:1px solid var(--border);background:#fff;
}
.control{height:40px;border:1px solid var(--border);border-radius:10px;background:#fff;outline:none;color:var(--text);font-size:14px}
.control.input{padding:0 12px;min-width:320px}
.control.select{padding:0 36px 0 12px;min-width:150px;appearance:none;background-repeat:no-repeat;background-position:right 12px center;padding-right:40px;
  background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16'%3E%3Cpath fill='%23667085' d='M4.47 6.97a.75.75 0 0 1 1.06 0L8 9.44l2.47-2.47a.75.75 0 0 1 1.06 1.06l-3 3a.75.75 0 0 1-1.06 0l-3-3a.75.75 0 0 1 0-1.06Z'/%3E%3C/svg%3E");
}
.btn-rect{height:40px;border-radius:8px;padding:0 14px;font-weight:500;display:inline-flex;align-items:center;gap:8px;line-height:1}
.btn-secondary.soft{background:#fff;border:1px solid var(--border);color:#344054}
.btn-secondary.soft:hover{background:#F9FAFB}
.btn-dark{background:var(--primary);border:1px solid var(--primary);color:#fff}
.btn-dark:hover{background:var(--primary-hover);border-color:var(--primary-hover)}

/* ===== Table ===== */
.section-body{background:#fff;}
.table-wrap{border:none;border-radius:0;overflow:hidden;background:#fff;margin:0}
.table{margin:0;border-collapse:separate;border-spacing:0;width:100%}
.table thead th{background:var(--thead);color:#344054;font-weight:600;letter-spacing:.02em;white-space:nowrap;padding:14px 16px!important}
.table>:not(caption)>*>*{padding:14px 16px;vertical-align:middle}
.table tbody tr+tr td{border-top:1px solid #EEF2F7}
.table tbody tr:hover{background:#F9FAFB}
.col-actions{text-align:right}

/* Name cell */
.user-cell{display:flex;align-items:center;gap:12px}
.avatar{width:34px;height:34px;border-radius:999px;background:#EEF2F7;display:grid;place-items:center;font-size:18px}
.avatar img{width:34px;height:34px;border-radius:999px;object-fit:cover}

/* Status badge */
.badge-status{display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:999px;font-weight:600;font-size:12px}
.badge-active{background:rgba(22,163,74,.10);color:var(--success)}
.badge-inactive{background:#F2F4F7;color:#344054}

/* Row action icons */
.kebab, .icon-btn{
  border:0;background:transparent;padding:6px;border-radius:8px;color:#475467;line-height:1;
  transition:background .15s,color .15s
}
.kebab:hover,.icon-btn:hover{background:#F2F4F7;color:#1F2937}
.kebab:focus,.icon-btn:focus{outline:2px solid #E5E7EB;outline-offset:2px}

/* Pagination */
.section-foot{display:flex;align-items:center;justify-content:space-between;padding:12px 16px;background:#fff;border-top:1px solid var(--border)}
.range-text{color:#667085;font-size:14px}
.pager{display:flex;align-items:center;gap:8px}
.page-btn{min-width:36px;height:36px;border:1px solid var(--border);border-radius:10px;background:#fff;color:#344054}
.page-btn.active{background:var(--primary);color:#fff;border-color:var(--primary)}
.page-btn:disabled{opacity:.5}
.page-btn.icon{display:grid;place-items:center}

/* ===== Modal: Add/Edit User (Pop-up) ===== */
.modal-backdrop-user{position:fixed;inset:0;display:none;align-items:center;justify-content:center;background:rgba(17,24,39,.55);z-index:1060}
.modal-backdrop-user.open{display:flex}
.modal-user{width:min(720px,92vw);background:#fff;border-radius:12px;box-shadow:0 10px 30px rgba(16,24,40,.25);overflow:hidden}
.modal-user .head{display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid #EEF2F7;font-weight:700;color:#111827}
.modal-user .close{border:0;background:transparent;color:#667085;font-size:20px}
.modal-user .body{padding:16px 20px}
.modal-user .foot{padding:14px 20px 18px;display:flex;gap:10px;justify-content:flex-end;border-top:1px solid #EEF2F7}
.modal-user label{font-size:12px;color:#667085;margin-bottom:6px}
.modal-user input.form-control,.modal-user select.form-select{height:44px}
.modal-user .form-control:focus,.modal-user .form-select:focus{border-color:#A5B4FC;box-shadow:0 0 0 3px rgba(99,102,241,.3)}

/* Status switch */
.switch{position:relative;display:inline-block;width:48px;height:28px;vertical-align:middle}
.switch input{opacity:0;width:0;height:0}
.slider{position:absolute;cursor:pointer;top:0;left:0;right:0;bottom:0;background:#E5E7EB;border-radius:999px;transition:.2s}
.slider:before{position:absolute;content:"";height:22px;width:22px;left:3px;top:3px;background:#fff;border-radius:50%;transition:.2s;box-shadow:0 1px 3px rgba(0,0,0,.2)}
input:checked + .slider{background:#111827}
input:checked + .slider:before{transform:translateX(20px)}
.status-label{margin-left:10px;color:#111827;font-weight:500}

/* Password eye */
.input-with-icon{position:relative}
.input-with-icon .toggle-eye{position:absolute;right:12px;top:50%;transform:translateY(-50%);border:0;background:transparent;color:#667085}

/* helpers */
.hidden{display:none !important;}
</style>

<div class="container-fluid py-4 px-4">
  <div class="page-wrap">
    {{-- Title --}}
    <div class="header-row">
      <h1 class="hd-title">Manage Users</h1>
    </div>

    {{-- Section Card (Toolbar + Table + Pagination) --}}
    <div class="table-section">
      <div class="section-toolbar">
        <input id="q" type="text" class="control input" placeholder="Search users...">
        <select id="role" class="control select">
          <option value="all">All Roles</option>
          <option>Head Artist</option><option>Artist</option>
          <option>Head Salesperson</option><option>Salesperson</option>
          <option>Installation</option>
        </select>
        <select id="status" class="control select">
          <option value="all">All Status</option>
          <option value="Active">Active</option><option value="Inactive">Inactive</option>
        </select>
        <div style="flex:1"></div>
        <button id="btnAddUser" class="btn btn-dark btn-rect">
          <i class="bi bi-plus-lg"></i> Add New User
        </button>
      </div>

      <div class="section-body">
        <div class="table-wrap">
          <div class="table-responsive">
            <table class="table align-middle" id="userTable">
              <thead>
              <tr>
                <th>Name</th><th>Role</th><th>Email</th><th>Contact No.</th><th>Status</th><th class="col-actions">Actions</th>
              </tr>
              </thead>
              <tbody id="tbody"></tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="section-foot">
        <div class="range-text" id="rangeText">Showing 0–0 of 0 results</div>
        <div class="pager" id="pager"></div>
      </div>
    </div>
  </div>
</div>

<!-- ===== Add/Edit User Modal (Pop-up) ===== -->
<div class="modal-backdrop-user" id="userModal" aria-hidden="true" role="dialog" aria-modal="true">
  <div class="modal-user" role="document">
    <div class="head">
      <div id="modalTitle">Add New User</div>
      <button class="close" id="userClose" aria-label="Close"><i class="bi bi-x-lg"></i></button>
    </div>

    <div class="body">
      <div class="row g-3">
        <div class="col-md-6">
          <label>Full Name</label>
          <input type="text" id="f_name" class="form-control" placeholder="John Lim">
        </div>
        <div class="col-md-6">
          <label>Email Address</label>
          <input type="email" id="f_email" class="form-control" placeholder="john@example.com">
        </div>

        <div class="col-md-6">
          <label>Role</label>
          <select id="f_role" class="form-select">
            <option disabled selected>Select a role</option>
            <option>Head Artist</option><option>Artist</option>
            <option>Head Salesperson</option><option>Salesperson</option>
            <option>Installation</option>
          </select>
        </div>

        <!-- Password 行：新增时显示、编辑时隐藏 -->
        <div class="col-md-6" id="rowPwd">
          <label>Password</label>
          <div class="input-with-icon">
            <input type="password" id="f_pwd" class="form-control" placeholder="Enter temporary password">
            <button type="button" class="toggle-eye" id="togglePwd" aria-label="Show/Hide password">
              <i class="bi bi-eye"></i>
            </button>
          </div>
        </div>

        <div class="col-md-6">
          <label>Contact Number</label>
          <input type="text" id="f_phone" class="form-control" placeholder="+60 12-345 6789">
        </div>
        <div class="col-md-6 d-flex align-items-end">
          <div>
            <label>Status</label><br>
            <label class="switch">
              <input type="checkbox" id="f_status_sw" checked>
              <span class="slider"></span>
            </label>
            <span class="status-label" id="statusText">Active</span>
          </div>
        </div>
      </div>
    </div>

    <div class="foot">
      <button class="btn btn-secondary soft btn-rect" id="userCancel">Cancel</button>
      <button class="btn btn-dark btn-rect" id="userSave">
        <i class="bi bi-save me-1"></i><span id="saveText">Save User</span>
      </button>
    </div>
  </div>
</div>

<script>
/** ===== Demo Data ===== */
const USERS = [
  {name:'Sarah Johnson', role:'Head Artist', email:'sarah.johnson@company.com', phone:'+1 234 567 8901', status:'Active'},
  {name:'Mike Chen', role:'Artist', email:'mike.chen@company.com', phone:'+1 234 567 8902', status:'Active'},
  {name:'Emma Davis', role:'Head Salesperson', email:'emma.davis@company.com', phone:'+1 234 567 8903', status:'Inactive'},
  {name:'James Wilson', role:'Salesperson', email:'james.wilson@company.com', phone:'+1 234 567 8904', status:'Active'},
  {name:'Lisa Martinez', role:'Installation', email:'lisa.martinez@company.com', phone:'+1 234 567 8905', status:'Active'},
];

const PAGE = {size:10,page:1,q:'',role:'all',status:'all'};
const elBody=document.getElementById('tbody');
const elRange=document.getElementById('rangeText');
const elPager=document.getElementById('pager');

/** ===== Helpers ===== */
function badge(status){
  const cls = status==='Active' ? 'badge-status badge-active' : 'badge-status badge-inactive';
  return `<span class="${cls}">${status}</span>`;
}
function avatarFor(name){
  const letter=(name||'?').trim().charAt(0).toUpperCase();
  return `<div class="avatar">${letter}</div>`;
}
function filterUsers(){
  const q=PAGE.q.toLowerCase();
  return USERS.filter(u=>{
    const hitQ=!q||u.name.toLowerCase().includes(q)||u.email.toLowerCase().includes(q)||u.phone.toLowerCase().includes(q)||u.role.toLowerCase().includes(q);
    const hitR=PAGE.role==='all'||u.role===PAGE.role;
    const hitS=PAGE.status==='all'||u.status===PAGE.status;
    return hitQ&&hitR&&hitS;
  });
}
function paginate(list){
  const start=(PAGE.page-1)*PAGE.size, end=start+PAGE.size;
  return {slice:list.slice(start,end), start:start+1, end:Math.min(end,list.length), total:list.length};
}
function rowTpl(u,idx){
  const gidx=(PAGE.page-1)*PAGE.size+idx;
  return `
  <tr>
    <td><div class="user-cell">${avatarFor(u.name)}<div>${u.name}</div></div></td>
    <td>${u.role}</td>
    <td>${u.email}</td>
    <td>${u.phone}</td>
    <td>${badge(u.status)}</td>
    <td class="col-actions">
      <button class="icon-btn" title="Edit" data-edit="${gidx}"><i class="bi bi-pencil-square"></i></button>
      <button class="icon-btn" title="Delete" data-del="${gidx}"><i class="bi bi-trash3"></i></button>
    </td>
  </tr>`;
}
function render(){
  const filtered=filterUsers();
  const pg=paginate(filtered);
  if(pg.total===0){
    elBody.innerHTML=`<tr><td colspan="6"><div class="text-center text-muted py-3">No results found.</div></td></tr>`;
    elRange.textContent=`Showing 0–0 of 0 results`;
    elPager.innerHTML=''; return;
  }
  elBody.innerHTML=pg.slice.map(rowTpl).join('');
  elRange.textContent=`Showing ${pg.start}–${pg.end} of ${pg.total} results`;
  const pages=Math.ceil(pg.total/PAGE.size);
  let html='';
  html+=`<button class="page-btn icon" ${PAGE.page<=1?'disabled':''} onclick="gotoPage(${PAGE.page-1})"><i class="bi bi-chevron-left"></i></button>`;
  for(let i=1;i<=pages;i++){ html+=`<button class="page-btn ${i===PAGE.page?'active':''}" onclick="gotoPage(${i})">${i}</button>`; }
  html+=`<button class="page-btn icon" ${PAGE.page>=pages?'disabled':''} onclick="gotoPage(${PAGE.page+1})"><i class="bi bi-chevron-right"></i></button>`;
  elPager.innerHTML=html;
}
function gotoPage(p){ PAGE.page=p; render(); }

/** ===== Bind filters ===== */
document.getElementById('q').addEventListener('input',e=>{PAGE.q=e.target.value.trim();PAGE.page=1;render();});
document.getElementById('role').addEventListener('change',e=>{PAGE.role=e.target.value;PAGE.page=1;render();});
document.getElementById('status').addEventListener('change',e=>{PAGE.status=e.target.value;PAGE.page=1;render();});

/** ===== Actions: Edit / Delete ===== */
elBody.addEventListener('click',(e)=>{
  const editBtn=e.target.closest('[data-edit]'); const delBtn=e.target.closest('[data-del]');
  if(editBtn){ const idx=Number(editBtn.dataset.edit); openUserModal(idx); }
  else if(delBtn){ const idx=Number(delBtn.dataset.del);
    if(confirm('Delete this user?')){ USERS.splice(idx,1); if((PAGE.page-1)*PAGE.size>=USERS.length) PAGE.page=Math.max(1,PAGE.page-1); render(); }
  }
});

/** ===== Modal controls ===== */
const modal=document.getElementById('userModal');
const modalTitle=document.getElementById('modalTitle');
const saveText=document.getElementById('saveText');
const rowPwd=document.getElementById('rowPwd');

const fName=document.getElementById('f_name');
const fRole=document.getElementById('f_role');
const fEmail=document.getElementById('f_email');
const fPhone=document.getElementById('f_phone');
const fPwd=document.getElementById('f_pwd');
const fStatusSw=document.getElementById('f_status_sw');
const statusText=document.getElementById('statusText');
let editingIndex=null;

function openUserModal(idx=null){
  editingIndex=idx;

  if(idx===null){
    // Add 模式：显示密码、默认Active
    modalTitle.textContent='Add New User';
    saveText.textContent='Save User';
    rowPwd.classList.remove('hidden');

    fName.value=''; fEmail.value=''; fPhone.value=''; fPwd.value='';
    fRole.selectedIndex=0; // Select a role
    fStatusSw.checked=true; statusText.textContent='Active';
  }else{
    // Edit 模式：隐藏密码（与你第二张图一致）
    modalTitle.textContent='Edit User';
    saveText.textContent='Save User'; // 如要“Update User”，改成 'Update User'
    rowPwd.classList.add('hidden');

    const u=USERS[idx];
    fName.value=u.name; fEmail.value=u.email; fPhone.value=u.phone; fRole.value=u.role;
    fPwd.value='';
    fStatusSw.checked=(u.status==='Active'); statusText.textContent=u.status;
  }

  modal.classList.add('open'); modal.setAttribute('aria-hidden','false');
  setTimeout(()=>fName.focus(),30);
}
function closeUserModal(){ modal.classList.remove('open'); modal.setAttribute('aria-hidden','true'); }
document.getElementById('btnAddUser').addEventListener('click',()=>openUserModal(null));
document.getElementById('userClose').addEventListener('click',closeUserModal);
document.getElementById('userCancel').addEventListener('click',closeUserModal);
modal.addEventListener('click',e=>{ if(e.target===modal) closeUserModal(); });
document.addEventListener('keydown',e=>{ if(e.key==='Escape'&&modal.classList.contains('open')) closeUserModal(); });

/* status text sync */
fStatusSw.addEventListener('change',()=>{ statusText.textContent = fStatusSw.checked ? 'Active' : 'Inactive'; });

/* password eye */
document.getElementById('togglePwd').addEventListener('click',(ev)=>{
  const isPw=fPwd.getAttribute('type')==='password';
  fPwd.setAttribute('type', isPw?'text':'password');
  ev.currentTarget.innerHTML = `<i class="bi ${isPw?'bi-eye-slash':'bi-eye'}"></i>`;
});

/** ===== Save ===== */
document.getElementById('userSave').addEventListener('click',()=>{
  const needPwd = (editingIndex===null); // 仅新增必填密码

  const rec={
    name:fName.value.trim(),
    role:(fRole.value && fRole.value!=='Select a role') ? fRole.value : '',
    email:fEmail.value.trim(),
    phone:fPhone.value.trim(),
    status:fStatusSw.checked?'Active':'Inactive'
  };
  if(!rec.name||!rec.email||!rec.role|| (needPwd && !fPwd.value.trim())){
    alert('Please fill Full Name, Email and Role' + (needPwd?' and Password':'') + '.'); 
    return;
  }

  if(needPwd){
    // 真实提交后端时带上密码
    rec.password = fPwd.value;
  }

  if(editingIndex===null){ USERS.unshift(rec); PAGE.page=1; }
  else{ USERS[editingIndex]={...USERS[editingIndex], ...rec}; }

  render(); closeUserModal();
});

/** ===== Init ===== */
document.addEventListener('DOMContentLoaded',render);
</script>
@endsection
