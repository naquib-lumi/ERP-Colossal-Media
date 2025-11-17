@extends('layouts.app')

@section('content')
@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
:root{
  --bg:#F9FAFB; --card:#FFFFFF; --border:#E5E7EB; --thead:#F9FAFB;
  --text:#101828; --muted:#667085; --chip:#F2F4F7;
  --shadow:0 3px 10px rgba(16,24,40,.06);
  --primary:#2563EB; --primary-hover:#1D4ED8;
  --success:#16A34A;
}
body{background:var(--bg);}
.page-wrap{max-width:1140px;margin:0 auto}

/* Title */
.header-row{display:block;margin:0 18px 12px;}
.hd-title{margin:0;font-size:22px;font-weight:800;color:var(--text)}

/* Section Card */
.table-section{background:#fff;border:1px solid var(--border);border-radius:16px;box-shadow:var(--shadow);overflow:hidden;margin:0 18px 18px;}
.section-toolbar{display:flex;align-items:center;gap:12px;flex-wrap:wrap;padding:14px 16px;border-bottom:1px solid var(--border);background:#fff}
.control{height:40px;border:1px solid var(--border);border-radius:10px;background:#fff;outline:none;color:#111827;font-size:14px}
.control.input{padding:0 12px;min-width:320px}
.control.select{padding:0 36px 0 12px;min-width:170px;appearance:none;background-repeat:no-repeat;background-position:right 12px center;padding-right:40px;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16'%3E%3Cpath fill='%23667085' d='M4.47 6.97a.75.75 0 0 1 1.06 0L8 9.44l2.47-2.47a.75.75 0 0 1 1.06 1.06l-3 3a.75.75 0 0 1-1.06 0l-3-3a.75.75 0 0 1 0-1.06Z'/%3E%3C/svg%3E")}
.btn-rect{height:40px;border-radius:8px;padding:0 14px;font-weight:500;display:inline-flex;align-items:center;gap:8px;line-height:1}
.btn-secondary.soft{background:#fff;border:1px solid var(--border);color:#344054}
.btn-secondary.soft:hover{background:#F9FAFB}
.btn-dark{background:var(--primary);border:1px solid var(--primary);color:#fff}
.btn-dark:hover{background:var(--primary-hover);border-color:var(--primary-hover)}

/* Table */
.table thead th{background:var(--thead);color:#344054;font-weight:600;letter-spacing:.02em;white-space:nowrap;padding:14px 16px!important}
.table>:not(caption)>*>*{padding:14px 16px;vertical-align:middle}
.table tbody tr+tr td{border-top:1px solid #EEF2F7}
.table tbody tr:hover{background:#F9FAFB}
.col-actions{text-align:right}

/* Name cell */
.user-cell{display:flex;align-items:center;gap:12px}
.avatar{width:34px;height:34px;border-radius:999px;background:#EEF2F7;display:grid;place-items:center;font-size:18px;color:#344054}
.avatar img{width:34px;height:34px;border-radius:999px;object-fit:cover}

/* Status badge */
.badge-status{display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:999px;font-weight:600;font-size:12px}
.badge-active{background:rgba(22,163,74,.10);color:var(--success)}
.badge-inactive{background:#F2F4F7;color:#344054}

/* Row actions */
.icon-btn{border:0;background:transparent;padding:6px;border-radius:8px;color:#475467;line-height:1;transition:background .15s,color .15s}
.icon-btn:hover{background:#F2F4F7;color:#1F2937}

/* Pagination */
.section-foot{display:flex;align-items:center;justify-content:space-between;padding:12px 16px;background:#fff;border-top:1px solid var(--border)}
.range-text{color:#667085;font-size:14px}

/* Modal */
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

/* invalid helpers */
.is-invalid{border-color:#dc3545!important}
.invalid-feedback{display:block;color:#dc3545;font-size:12px;margin-top:6px}

/* helpers */
.hidden{display:none !important;}
</style>

<div class="container-fluid py-4 px-4">
  <div class="page-wrap">
    <div class="header-row">
      <h1 class="hd-title">Manage Users</h1>
    </div>

    @if(session('success'))
      <div class="alert alert-success mx-3">{{ session('success') }}</div>
    @endif
    @if($errors->any())
      <div class="alert alert-danger mx-3">
        <ul class="mb-0">
          @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
        </ul>
      </div>
    @endif

    <div class="table-section">
      {{-- Toolbar --}}
      <form id="filterForm" class="section-toolbar" onsubmit="return false;">
        <input id="searchInput" value="{{ request('q','') }}" type="text" class="control input" placeholder="Search users...">
        @php $role = request('role','all'); @endphp
        <select id="roleSelect" class="control select">
          <option value="all" {{ $role==='all'?'selected':'' }}>All Roles</option>
          <option value="admin" {{ $role==='admin'?'selected':'' }}>Admin</option>
          <option value="salesperson" {{ $role==='salesperson'?'selected':'' }}>Salesperson</option>
          <option value="head-salesperson" {{ $role==='head-salesperson'?'selected':'' }}>Head Salesperson</option>
          <option value="head-artist" {{ $role==='head-artist'?'selected':'' }}>Head Artist</option>
          <option value="artist" {{ $role==='artist'?'selected':'' }}>Artist</option>
          <option value="operations-printing" {{ $role==='operations-printing'?'selected':'' }}>Operations Printing</option>
          <option value="operations-furnishing" {{ $role==='operations-furnishing'?'selected':'' }}>Operations Furnishing</option>
          <option value="operations-dispatch-control" {{ $role==='operations-dispatch-control'?'selected':'' }}>Operations Dispatch Control</option>
          <option value="operations-delivery-installation" {{ $role==='operations-delivery-installation'?'selected':'' }}>Operations Delivery & Installation</option>
          <option value="data-entry" {{ $role==='data-entry'?'selected':'' }}>Data Entry</option>
        </select>
        @php $st = request('status','all'); @endphp
        <select id="statusSelect" class="control select">
          <option value="all" {{ $st==='all'?'selected':'' }}>All Status</option>
          <option value="active" {{ $st==='active'?'selected':'' }}>Active</option>
          <option value="inactive" {{ $st==='inactive'?'selected':'' }}>Inactive</option>
        </select>
        <div style="flex:1"></div>
        <button type="button" id="btnAddUser" class="btn btn-dark btn-rect">
          <i class="bi bi-plus-lg"></i> Add New User
        </button>
        <button type="button" id="resetFilter" class="btn btn-secondary soft btn-rect">
          <i class="bi bi-arrow-counterclockwise"></i> Reset
        </button>
      </form>

      {{-- Table --}}
      <div class="table-responsive">
        <table class="table align-middle">
          <thead>
            <tr>
              <th>Name</th>
              <th>Role</th>
              <th>Email</th>
              <th>Contact No.</th>
              <th>Status</th>
              <th class="col-actions">Actions</th>
            </tr>
          </thead>
          <tbody>
          @forelse($users as $user)
            @php $active = strtolower((string)($user->status ?? '')) === 'active'; @endphp
            <tr data-role="{{ $user->role }}" data-status="{{ strtolower((string)($user->status ?? '')) }}">
              <td>
                <div class="user-cell">
                  @php $av = $user->avatar_url ?? null; @endphp
                  @if($av)
                    <div class="avatar"><img src="{{ $av }}" alt="{{ $user->name }}"></div>
                  @else
                    <div class="avatar">{{ strtoupper(mb_substr($user->name,0,1)) }}</div>
                  @endif
                  <div>{{ $user->name }}</div>
                </div>
              </td>
              <td>{{ $user->display_role ?? ucwords(str_replace(['-','_'],' ',$user->role)) }}</td>
              <td>{{ $user->email }}</td>
              <td>{{ $user->contact_number ?? 'N/A' }}</td>
              <td>
                <span class="badge-status {{ $active ? 'badge-active' : 'badge-inactive' }}">
                  {{ $active ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td class="col-actions">
                <button class="icon-btn" title="Edit"
                        data-mode="edit"
                        data-id="{{ $user->id }}"
                        data-name="{{ $user->name }}"
                        data-email="{{ $user->email }}"
                        data-role="{{ $user->role }}"
                        data-phone="{{ $user->contact_number }}"
                        data-status="{{ strtolower((string)$user->status) }}">
                  <i class="bi bi-pencil-square"></i>
                </button>

                <form action="{{ route('admin.user.disable',$user->id) }}" method="POST" style="display:inline" class="disable-form">
                  @csrf @method('PATCH')
                  <button type="submit" class="icon-btn disable-btn" title="{{ $active? 'Disable':'Enable' }}">
                    <i class="bi bi-{{ $active? 'person-dash':'person-check' }}"></i>
                  </button>
                </form>
                <form action="{{ route('admin.user.resetpassword',$user->id) }}" method="POST" style="display:inline" class="reset-form">
                  @csrf @method('PATCH')
                  <button type="submit" class="icon-btn reset-btn" title="Reset Password">
                    <i class="bi bi-key"></i>
                  </button>
                </form>
              </td>
            </tr>
          @empty
            <tr><td colspan="6"><div class="text-center text-muted py-3">No results found.</div></td></tr>
          @endforelse
          </tbody>
        </table>
      </div>

      {{-- Foot --}}
      <div class="section-foot">
        <div class="range-text">Showing 0–0 of 0 results</div>
      </div>
    </div>
  </div>
</div>

{{-- Add/Edit Modal --}}
<div class="modal-backdrop-user" id="userModal" aria-hidden="true" role="dialog" aria-modal="true">
  <div class="modal-user" role="document">
    <div class="head">
      <div id="modalTitle">Add New User</div>
      <button class="close" id="userClose" aria-label="Close"><i class="bi bi-x-lg"></i></button>
    </div>

    <form id="userForm" method="POST" action="{{ route('admin.user.store') }}">
      @csrf
      <input type="hidden" name="_method" id="methodSpoof" value="POST">
      <input type="hidden" name="_mode" id="f_mode" value="{{ old('_mode','create') }}">

      <div class="body">
        <div class="row g-3">
          <div class="col-md-6">
            <label>Full Name</label>
            <input type="text" name="name" id="f_name"
                   class="form-control @error('name') is-invalid @enderror"
                   placeholder="John Lim" value="{{ old('name','') }}" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-6">
            <label>Email Address</label>
            <input type="email" name="email" id="f_email"
                   class="form-control @error('email') is-invalid @enderror"
                   placeholder="john@example.com" value="{{ old('email','') }}" required>
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-6">
            <label>Role</label>
            <select name="role" id="f_role" class="form-select @error('role') is-invalid @enderror" required>
              <option disabled value="" {{ old('role')? '':'selected' }}>Select a role</option>
              <option value="admin" {{ old('role')==='admin'?'selected':'' }}>Admin</option>
              <option value="salesperson" {{ old('role')==='salesperson'?'selected':'' }}>Salesperson</option>
              <option value="head-salesperson" {{ old('role')==='head-salesperson'?'selected':'' }}>Head Salesperson</option>
              <option value="head-artist" {{ old('role')==='head-artist'?'selected':'' }}>Head Artist</option>
              <option value="artist" {{ old('role')==='artist'?'selected':'' }}>Artist</option>
              <option value="operations-printing" {{ old('role')==='operations-printing'?'selected':'' }}>Operations – Printing</option>
              <option value="operations-furnishing" {{ old('role')==='operations-furnishing'?'selected':'' }}>Operations – Furnishing</option>
              <option value="operations-dispatch-control" {{ old('role')==='operations-dispatch-control'?'selected':'' }}>Operations – Dispatch Control</option>
              <option value="operations-delivery-installation" {{ old('role')==='operations-delivery-installation'?'selected':'' }}>Operations – Delivery & Installation</option>
              <option value="data-entry" {{ old('role')==='data-entry'?'selected':'' }}>Data Entry</option>
            </select>
            @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          {{-- 新增显示密码，编辑隐藏且不提交 --}}
          <div class="col-md-6" id="rowPwd">
            <label>Password</label>
            <div class="input-with-icon">
              <input type="password" name="password" id="f_pwd"
                     class="form-control @error('password') is-invalid @enderror"
                     placeholder="Enter temporary password">
              <button type="button" class="toggle-eye" id="togglePwd" aria-label="Show/Hide password">
                <i class="bi bi-eye"></i>
              </button>
              @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
          </div>

          <div class="col-md-6">
            <label>Contact Number</label>
            <input type="text" name="contact_number" id="f_phone"
                   class="form-control @error('contact_number') is-invalid @enderror"
                   placeholder="+60 12-345 6789" value="{{ old('contact_number','') }}">
            @error('contact_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-6 d-flex align-items-end">
            <div>
              <label>Status</label><br>
              @php $oldStatus = strtolower(old('status','active'))==='active'; @endphp
              <label class="switch">
                <input type="checkbox" id="f_status_sw" {{ $oldStatus? 'checked':'' }}>
                <span class="slider"></span>
              </label>
              <span class="status-label" id="statusText">{{ $oldStatus? 'Active':'Inactive' }}</span>
              <input type="hidden" name="status" id="f_status" value="{{ $oldStatus? 'active':'inactive' }}">
              @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
          </div>
        </div>
      </div>

      <div class="foot">
        <button type="button" class="btn btn-secondary soft btn-rect" id="userCancel">Cancel</button>
        <button type="submit" class="btn btn-dark btn-rect" id="userSave">
          <i class="bi bi-save me-1"></i><span id="saveText">Save User</span>
        </button>
      </div>
    </form>
  </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
(() => {
  const $ = s => document.querySelector(s);
  const $$ = s => Array.from(document.querySelectorAll(s));

  function openModal(){ $('#userModal').style.display = 'flex'; }
  function closeModal(){ $('#userModal').style.display = 'none'; }

  $('#btnAddUser').addEventListener('click', () => { prepareCreate(); openModal(); });
  $('#userClose').addEventListener('click', closeModal);
  $('#userCancel').addEventListener('click', closeModal);
  $('#userModal').addEventListener('click', e => { if (e.target === $('#userModal')) closeModal(); });
  document.addEventListener('keydown', e => { if (e.key === 'Escape' && $('#userModal').style.display === 'flex') closeModal(); });

  function prepareCreate() {
    $('#userForm').action = '{{ route('admin.user.store') }}';
    $('#methodSpoof').value = 'POST';
    $('#modalTitle').textContent = 'Add New User';
    $('#saveText').textContent = 'Save User';
    $('#rowPwd').classList.remove('hidden');
    $('#f_pwd').disabled = false;
    $('#f_pwd').setAttribute('name', 'password');
    $('#f_pwd').type = 'password';
    $('#f_pwd').value = '';
    $('#togglePwd').disabled = false;
    $('#f_mode').value = 'create';
    $('#f_role').disabled = false;
    $('#f_name').value = '{{ old('name', '') }}';
    $('#f_email').value = '{{ old('email', '') }}';
    $('#f_role').value = '{{ old('role', '') }}';
    $('#f_phone').value = '{{ old('contact_number', '') }}';
    const oldActive = '{{ strtolower(old('status', 'active')) === 'active' }}';
    $('#f_status_sw').checked = oldActive;
    $('#f_status').value = oldActive ? 'active' : 'inactive';
    $('#statusText').textContent = oldActive ? 'Active' : 'Inactive';
  }

  function prepareEdit(u) {
    $('#userForm').action = u.update;
    $('#methodSpoof').value = 'PUT';
    $('#modalTitle').textContent = 'Edit User';
    $('#saveText').textContent = 'Save Changes';
    $('#rowPwd').classList.add('hidden');
    $('#f_pwd').value = '';
    $('#f_pwd').type = 'password';
    $('#f_pwd').disabled = true;
    $('#f_pwd').removeAttribute('name');
    $('#togglePwd').disabled = true;
    $('#f_mode').value = 'edit';
    $('#f_role').disabled = true;
    $('#f_name').value = u.name;
    $('#f_email').value = u.email;
    $('#f_role').value = u.role || '';
    $('#f_phone').value = u.phone || '';
    const active = (u.status === 'active');
    $('#f_status_sw').checked = active;
    $('#f_status').value = active ? 'active' : 'inactive';
    $('#statusText').textContent = active ? 'Active' : 'Inactive';
  }

  $$('button[data-mode="edit"]').forEach(btn => {
    btn.addEventListener('click', () => {
      const u = {
        id: btn.dataset.id,
        name: btn.dataset.name,
        email: btn.dataset.email,
        role: btn.dataset.role,
        phone: btn.dataset.phone,
        status: btn.dataset.status,
        update: '{{ route('admin.user.update', ['user' => '___ID___']) }}'.replace('___ID___', btn.dataset.id)
      };
      prepareEdit(u);
      openModal();
    });
  });

  $('#f_status_sw').addEventListener('change', () => {
    const act = $('#f_status_sw').checked;
    $('#f_status').value = act ? 'active' : 'inactive';
    $('#statusText').textContent = act ? 'Active' : 'Inactive';
  });

  $('#togglePwd').addEventListener('click', ev => {
    if ($('#f_pwd').disabled) return;
    const isPw = $('#f_pwd').type === 'password';
    $('#f_pwd').type = isPw ? 'text' : 'password';
    ev.currentTarget.innerHTML = `<i class="bi ${isPw ? 'bi-eye-slash' : 'bi-eye'}"></i>`;
  });

  $('#userForm').addEventListener('submit', () => {
    $('#f_status').value = $('#f_status_sw').checked ? 'active' : 'inactive';
  });

  function applyFilter() {
    const q = ($('#searchInput').value || '').trim().toLowerCase();
    const role = $('#roleSelect').value || 'all';
    const status = $('#statusSelect').value || 'all';
    let shown = 0;
    $$('tbody tr').forEach(tr => {
      const name = tr.querySelector('.user-cell > div:nth-child(2)').textContent.toLowerCase();
      const email = tr.children[2].textContent.toLowerCase();
      const phone = tr.children[3].textContent.toLowerCase();
      const r = tr.dataset.role;
      const s = tr.dataset.status;
      const matchSearch = !q || name.includes(q) || email.includes(q) || phone.includes(q);
      const matchRole = role === 'all' || r === role;
      const matchStatus = status === 'all' || s === status;
      const ok = matchSearch && matchRole && matchStatus;
      tr.style.display = ok ? '' : 'none';
      if (ok) shown++;
    });
    const rangeText = $('.range-text');
    if (rangeText) rangeText.textContent = `Showing 1–${shown} of ${shown} results`;
  }

  $('#searchInput').addEventListener('input', applyFilter);
  $('#roleSelect').addEventListener('change', applyFilter);
  $('#statusSelect').addEventListener('change', applyFilter);

  $('#resetFilter').addEventListener('click', () => {
    $('#searchInput').value = '';
    $('#roleSelect').value = 'all';
    $('#statusSelect').value = 'all';
    applyFilter();
  });

  $$('.disable-btn').forEach(btn => {
    btn.addEventListener('click', e => {
      e.preventDefault();
      const form = btn.closest('form');
      const active = btn.title === 'Disable';
      const msg = `Are you sure you want to ${active ? 'disable' : 'enable'} this user?`;
      Swal.fire({
        title: 'Confirm',
        text: msg,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes'
      }).then(result => {
        if (result.isConfirmed) form.submit();
      });
    });
  });

  $$('.reset-btn').forEach(btn => {
    btn.addEventListener('click', e => {
      e.preventDefault();
      const form = btn.closest('form');
      Swal.fire({
        title: 'Confirm',
        text: 'Reset password to password123?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes'
      }).then(result => {
        if (result.isConfirmed) form.submit();
      });
    });
  });

  @if(session('success'))
  Swal.fire('Success', '{{ session('success') }}', 'success');
  @endif

  @if($errors->any())
  let errMsg = '{{ implode("<br>", $errors->all()) }}';
  Swal.fire('Error', errMsg, 'error');
  @endif

  @if($errors->any() && old('_mode')==='create')
  prepareCreate(); openModal();
  @endif

  window.addEventListener('load', applyFilter);
})();
</script>
@endpush
@endsection