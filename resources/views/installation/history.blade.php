@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
<style>
  .lb-meta .lb-caption {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .lb-strip img.active {
    outline: 3px solid #4ade80; /* green highlight */
  }
  /* Page & cards */
  .page-wrap {
    max-width: 1180px;
    margin: 0 auto;
  }

  .card.shadow-soft {
    border: 1px solid #ECEFF3;
    border-radius: 14px;
    box-shadow: 0 3px 10px rgba(16, 24, 40, .06)
  }

  .table> :not(caption)>*>* {
    padding: 14px 16px
  }

  /* Toolbar */
  .toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center
  }

  .toolbar .grow {
    flex: 1 1 360px
  }

  .toolbar .date {
    width: 140px;
    min-width: 140px
  }

  .toolbar .btn-icon {
    width: 40px;
    height: 40px;
    display: inline-flex;
    align-items: center;
    justify-content: center
  }

  /* Pills / small buttons */
  .pill {
    border-radius: 999px;
    padding: .25rem .85rem;
    border: 1px solid #E5E7EB;
    background: #fff;
    color: #111827;
    font-weight: 600;
    line-height: 1;
  }

  .pill:disabled {
    opacity: .55;
    cursor: not-allowed
  }

  /* Icon-only buttons (for “Product details”) */
  .icon-btn {
    width: 36px;
    height: 36px;
    border: 1px solid #E5E7EB;
    border-radius: 10px;
    background: #fff;
    color: #475467;
    display: inline-flex;
    align-items: center;
    justify-content: center
  }

  .icon-btn:hover {
    background: #F2F4F7;
    color: #111827
  }

  /* Pagination (pill style) */
  .pager .page-link {
    border-radius: 999px;
    border: 1px solid #E5E7EB
  }

  .pager .active>.page-link {
    background: #635bff;
    border-color: #635bff;
    color: #fff
  }

  .table thead th {
    font-size: 12px;
    color: #475467;
    font-weight: 700;
    background: #F8FAFC
  }

  .table tbody tr:hover {
    background: #FAFBFC
  }

  .toolbar .form-control {
    border-radius: 6px;
    font-size: 14px;
    display: inline-block;
  }

  .toolbar .btn {
    font-size: 14px;
    padding: 0 16px;
  }

  /* Keep on one line for wide screens, wrap on smaller */
  @media (max-width: 768px) {
    .toolbar form {
      flex-direction: column;
      align-items: stretch;
    }
    .toolbar .text-muted {
      display: none;
    }
  }

  /* make the overlay actually pop */
  .cx-mask { display:none; position:fixed; inset:0; background:rgba(0,0,0,.65); z-index:1050; }
  .cx-mask.show { display:block; }
  .cx-wrap { display:flex; min-height:100%; align-items:center; justify-content:center; padding:24px; }
  .cx-modal.cx-lightbox { width:min(1100px, 96vw); max-height:92vh; overflow:hidden; display:flex; flex-direction:column; }

  .cx-header { display:flex; align-items:center; gap:.5rem; border-bottom:1px solid #eee; padding:.75rem 1rem; }
  .cx-title { font-weight:700; color: white;}
  .cx-close { margin-left:auto; background:transparent; border:0; cursor:pointer; color: white; }

  .cx-body { padding:12px 16px; display:flex; flex-direction:column; gap:12px; }

  .lb-main { position:relative; background:#0f1115; border-radius:14px; overflow:hidden; display:flex; align-items:center; justify-content:center; min-height:420px; }
  #lbImage { max-width:100%; max-height:70vh; object-fit:contain; display:block; }

  .lb-nav { position:absolute; top:50%; transform:translateY(-50%); border:0; width:44px; height:44px; border-radius:50%;
            background:rgba(255,255,255,.9); display:flex; align-items:center; justify-content:center; cursor:pointer; }
  .lb-prev { left:10px; } .lb-next { right:10px; }
  .lb-nav:hover { background:#fff; }

  .lb-caption { text-align:center; color: #ffffffff; font-size:.9rem; padding:4px; min-height:22px; }

  .lb-strip { display:flex; gap:10px; overflow:auto; padding:8px; border-top:1px solid #eee; }
  .lb-thumb { flex:0 0 auto; width:110px; height:80px; border-radius:10px; overflow:hidden; border:2px solid transparent; cursor:pointer; background:#f3f4f6; }
  .lb-thumb img { width:100%; height:100%; object-fit:cover; display:block; }
  .lb-thumb.active { border-color:#16a34a; }

  /* footer buttons */
  .cx-footer { padding:10px 16px; border-top:1px solid #eee; display:flex; justify-content:flex-end; gap:8px; }
  .cx-footer .btn.btn-back {
    background:#f3f4f6; border:1px solid #e5e7eb; color:#374151; font-weight:600; border-radius:10px; min-width:120px; padding:10px 14px;
  }
.history-filter.card{border:0;border-radius:14px;box-shadow:0 3px 14px rgba(18,23,42,.06)}
  .history-filter .toolbar{display:flex;flex-wrap:wrap;gap:.75rem 1rem;align-items:center;padding:14px 16px}
  .history-filter .form-control{height:42px;border-radius:10px;border-color:#e6e8f0;box-shadow:none}
  .history-filter .form-control:focus{border-color:#bfc6ff;box-shadow:0 0 0 .15rem rgba(99,91,255,.12)}
  .history-filter .grow{flex:1 1 340px;min-width:260px}
  .history-filter .dates{display:flex;align-items:center;gap:.5rem}
  .history-filter .date-input{width:180px}
  .history-filter .actions{margin-left:auto;display:flex;gap:.5rem}
  .history-filter .btn{height:42px;border-radius:10px}
  .history-filter .btn-light{border-color:#e6e8f0;background:#f6f7fb;color:#111827}
  .history-filter .btn-light:hover{background:#eef0f8}
  .history-filter .btn-dark{background:#1f2233;border-color:#1f2233}
  .history-filter .btn-dark:hover{background:#2a2f47}
  .history-filter .with-icon{position:relative}
  .history-filter .with-icon>i{position:absolute;left:12px;top:50%;transform:translateY(-50%);font-size:16px;color:#7b8191;pointer-events:none}
  .history-filter .with-icon>.form-control{padding-left:36px}

  .artist-select{flex:0 1 220px}
  .artist-select select{height:42px;border-radius:10px;border-color:#e6e8f0;background:#fff;padding-left:36px}
  .artist-select i{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#7b8191;pointer-events:none}
</style>

<div class="container-fluid py-4 px-4">
  <div class="page-wrap">
    <h1 class="fw-bold mb-3" style="font-size:28px;letter-spacing:-.2px;">Order History</h1>

    {{-- Toolbar --}}
    <div class="card history-filter shadow-soft mb-3">
      <form method="GET" action="{{ route('installation.history') }}">
        <div class="card-body toolbar">
          <div class="with-icon grow">
            <i class="bi bi-hash"></i>
            <input type="text" name="pid" value="{{ request('pid', $pid ?? '') }}" class="form-control" placeholder="Enter Product ID">
          </div>

          <div class="with-icon grow">
            <i class="bi bi-search"></i>
            <input type="text" name="q" value="{{ request('q', $q ?? '') }}" class="form-control"
                  placeholder="Search by Order Title, Company, Product, Remarks">
          </div>

          <div class="with-icon artist-select">
            <i class="bi bi-person-badge"></i>
            <select name="artist" class="form-select">
              <option value="">All artists</option>
              @foreach(($artists ?? []) as $a)
                <option value="{{ $a->id }}" {{ (string)$a->id === (string)request('artist', $artist ?? '') ? 'selected' : '' }}>
                  {{ $a->name }}
                </option>
              @endforeach
            </select>
          </div>

          <div class="dates">
            <div class="with-icon">
              <i class="bi bi-calendar-event"></i>
              <input type="text" name="start" value="{{ request('start', $start ?? '') }}" class="form-control date-input js-date" placeholder="mm/dd/yyyy">
            </div>
            <span class="text-muted">to</span>
            <div class="with-icon">
              <i class="bi bi-calendar-check"></i>
              <input type="text" name="end" value="{{ request('end', $end ?? '') }}" class="form-control date-input js-date" placeholder="mm/dd/yyyy">
            </div>
          </div>

          <div class="actions">
            <a href="{{ route('installation.history') }}" class="btn btn-light border">
              <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
            </a>
            <button class="btn btn-dark btn-apply">
              <i class="bi bi-funnel me-1"></i> Apply Filter
            </button>
          </div>
        </div>
      </form>
    </div>

    {{-- Table --}}
    <div class="card shadow-soft">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2 small text-muted">
          <div class="fw-semibold">Completed Orders</div>
          <div>{{ number_format($orders->total()) }} total results</div>
        </div>

        <div class="table-responsive">
          @php
            $qAll = request()->query();
            $urlWith = function(array $overrides) use ($qAll) {
              return route('installation.history', array_filter(array_merge($qAll, $overrides), fn($v)=>$v!==null && $v!==''));
            };
            $dir     = request('dir','desc') === 'asc' ? 'asc' : 'desc';
            $nextDir = $dir === 'asc' ? 'desc' : 'asc';
          @endphp
          <table class="table align-middle">
            <thead class="table-light">
  <tr>
    <th>PRODUCT ID</th>
    <th>PRODUCT NAME</th>
    <th>
      @php
        $isCompleted = request('sort_by') === 'completed';
        $nextDir     = $isCompleted && request('sort_dir') === 'asc' ? 'desc' : 'asc';
      @endphp
      <a class="text-decoration-none text-dark"
        href="{{ $urlWith(['sort'=>'completed','dir'=>$nextDir]) }}">
        COMPLETION DATE
        <span class="sort-caret">{{ $dir === 'asc' ? '↑' : '↓' }}</span>
      </a>
    </th>
    <th>PROOF FILE</th>   {{-- 👈 keep this column exactly as in your original --}}
    
    <th class="text-center">PRODUCT DETAILS</th>
  </tr>
</thead>
            <tbody>
@forelse($orders as $row)
  @php
    $code      = $row->product_code ?? (($row->order_number ?: ('ORD'.$row->order_id)).'-P'.str_pad((string)$row->ProductID, 4, '0', STR_PAD_LEFT));
    $prodName  = $row->productName ?? '—';
    $completed = $row->completed_date ? \Carbon\Carbon::parse($row->completed_date)->format('M d, Y') : '—';
    $remarks   = $row->materialRemark ?: '–';
      $pid     = $row->ProductID ?? $row['ProductID'];
  $viewUrl = route('installation.job.show', [$pid, 'from' => 'history']);
  @endphp
  <tr class="js-row-open" data-href="{{ $viewUrl }}" style="cursor:pointer;">
    <td class="fw-semibold">{{ $code }}</td>
    <td><span class="truncate" title="{{ $prodName }}">{{ $prodName }}</span></td>
    <td>{{ $completed }}</td>

    {{-- 🔒 Keep your existing proof button/logic here --}}
    <td>
      <button
        type="button"
        class="btn btn-light border btn-sm js-view-proofs"
        data-product="{{ $row->ProductID ?? $row['ProductID'] }}"
        data-url="{{ route('installation.history.proofs', ['product' => $row->ProductID ?? $row['ProductID']]) }}"
        data-upload-url="{{ route('installation.history.proofs.store', ['product' => $row->ProductID ?? $row['ProductID']]) }}"
      >
        <i class="bi bi-eye me-1"></i> View
      </button>
    </td>


    <td class="text-center">
      <a href="{{ $viewUrl }}" class="icon-btn" title="View details"><i class="bi bi-eye"></i></a>
    </td>
  </tr>
@empty
  <tr><td colspan="6" class="text-center text-muted">No records found.</td></tr>
@endforelse
</tbody>
          </table>
        </div>

        {{-- Pagination (pill style) --}}
        @if ($orders->hasPages())
        <div class="d-flex justify-content-end mt-3">
          <nav>
            <ul class="pagination pager mb-0">
              @if ($orders->onFirstPage())
              <li class="page-item disabled"><span class="page-link"><i class="bi bi-chevron-left"></i></span></li>
              @else
              <li class="page-item"><a class="page-link" href="{{ $orders->previousPageUrl() }}"><i class="bi bi-chevron-left"></i></a></li>
              @endif

              @php
              $startPage = max(1, $orders->currentPage() - 1);
              $endPage = min($orders->lastPage(), $orders->currentPage() + 1);
              @endphp
              @if ($startPage > 1)
              <li class="page-item"><a class="page-link" href="{{ $orders->url(1) }}">1</a></li>
              @if ($startPage > 2)
              <li class="page-item disabled"><span class="page-link">…</span></li>
              @endif
              @endif

              @for ($p = $startPage; $p <= $endPage; $p++)
                @if ($p==$orders->currentPage())
                <li class="page-item active"><span class="page-link">{{ $p }}</span></li>
                @else
                <li class="page-item"><a class="page-link" href="{{ $orders->url($p) }}">{{ $p }}</a></li>
                @endif
                @endfor

                @if ($endPage < $orders->lastPage())
                  @if ($endPage < $orders->lastPage() - 1)
                    <li class="page-item disabled"><span class="page-link">…</span></li>
                    @endif
                    <li class="page-item"><a class="page-link" href="{{ $orders->url($orders->lastPage()) }}">{{ $orders->lastPage() }}</a></li>
                    @endif

                    @if ($orders->hasMorePages())
                    <li class="page-item"><a class="page-link" href="{{ $orders->nextPageUrl() }}"><i class="bi bi-chevron-right"></i></a></li>
                    @else
                    <li class="page-item disabled"><span class="page-link"><i class="bi bi-chevron-right"></i></span></li>
                    @endif
            </ul>
          </nav>
        </div>

        <div class="small text-muted mt-2">
          Showing {{ $orders->firstItem() }} to {{ $orders->lastItem() }} of {{ $orders->total() }} results
        </div>
        @endif
      </div>
    </div>
  </div>
</div>

{{-- Proof viewer modal --}}
<div id="proofModal" class="cx-mask" aria-hidden="true">
  <div class="cx-wrap">
    <div class="cx-modal cx-lightbox" role="dialog" aria-modal="true" aria-labelledby="proofTitle">
      <div class="cx-header">
        <i class="bi bi-images text-success"></i>
        <div id="proofTitle" class="cx-title">Installation Proof</div>
        <button type="button" class="cx-close" data-close="proofModal"><i class="bi bi-x-lg"></i></button>
      </div>

      <div class="cx-body">
        <div class="lb-main">
          <button class="lb-nav lb-prev" type="button" aria-label="Previous"><i class="bi bi-chevron-left"></i></button>
          <img id="lbImage" alt="Proof" />
          <button class="lb-nav lb-next" type="button" aria-label="Next"><i class="bi bi-chevron-right"></i></button>
        </div>

        <div class="lb-meta" style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-top:10px;">
          <div id="lbCaption" class="lb-caption" style="font-weight:600;">—</div>
          <div id="lbUploadedAt" class="text-muted small" style="white-space:nowrap; color: white;">
            <!-- Uploaded: Oct 10, 2025 07:19 AM -->
          </div>
        </div>

        <div id="lbStrip" class="lb-strip">
          <!-- thumbnails injected here -->
        </div>

        <div id="lbEmpty" class="text-muted text-center py-4" style="display:none;">No files found.</div>
      </div>

      <div class="cx-footer d-flex justify-content-between align-items-center gap-2 flex-wrap">
          <form id="lbUploadForm"
                class="d-flex align-items-center gap-2"
                enctype="multipart/form-data"
                method="post">
              @csrf
              <input
                  type="file"
                  id="lbFiles"
                  name="files[]"
                  class="form-control form-control-sm"
                  multiple
                  accept="image/*,.pdf"
                  style="background: white;"
              >
              <button type="submit" class="btn btn-primary btn-sm" style="width: 100%;">
                  <i class="bi bi-cloud-upload me-1"></i> Upload new proof
              </button>
          </form>

          <button type="button" class="btn btn-back ms-auto" data-close="proofModal">Close</button>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
  (function() {
    // Shared options
    const opts = {
      dateFormat: "m/d/Y",
      allowInput: true,
      clickOpens: true,
      position: "auto", // prefer below; auto handles viewport
      disableMobile: false, // keep the same look on mobile
      static: false // let it attach to body and position correctly
    };

    const start = flatpickr("#startDate", {
      ...opts,
      onChange: function(selectedDates) {
        if (selectedDates?.length) {
          end.set('minDate', selectedDates[0]);
        } else {
          end.set('minDate', null);
        }
      }
    });

    const end = flatpickr("#endDate", {
      ...opts,
      onChange: function(selectedDates) {
        if (selectedDates?.length) {
          start.set('maxDate', selectedDates[0]);
        } else {
          start.set('maxDate', null);
        }
      }
    });
  })();

  document.addEventListener('DOMContentLoaded', () => {
  const modal = document.getElementById('proofModal');
  const img   = document.getElementById('lbImage');
  const cap   = document.getElementById('lbCaption');
  const strip = document.getElementById('lbStrip');
  const empty = document.getElementById('lbEmpty');
  const prev  = modal.querySelector('.lb-prev');
  const next  = modal.querySelector('.lb-next');
  const uploadedEl = document.getElementById('lbUploadedAt');

  const uploadForm  = document.getElementById('lbUploadForm');
  const uploadInput = document.getElementById('lbFiles');

  let files = [];        // [{url,name,...}]
  let idx   = 0;         // current index
  let keyBound = false;
  let listUrl   = null;  // GET proofs url
  let uploadUrl = null;  // POST upload url

  function openModal() {
    modal.classList.add('show');
    modal.setAttribute('aria-hidden', 'false');
    document.documentElement.style.overflow = 'hidden';
    if (!keyBound) {
      keyBound = true;
      window.addEventListener('keydown', onKey);
    }
  }
  function closeModal() {
    modal.classList.remove('show');
    modal.setAttribute('aria-hidden', 'true');
    document.documentElement.style.overflow = '';
    if (keyBound) {
      keyBound = false;
      window.removeEventListener('keydown', onKey);
    }
  }
  function onKey(e) {
    if (e.key === 'Escape') closeModal();
    if (e.key === 'ArrowLeft') go(-1);
    if (e.key === 'ArrowRight') go(+1);
  }

  modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
  modal.querySelectorAll('[data-close="proofModal"]').forEach(b => b.addEventListener('click', closeModal));
  prev.addEventListener('click', () => go(-1));
  next.addEventListener('click', () => go(+1));

  function renderThumbs(active = 0) {
    strip.innerHTML = '';
    const frag = document.createDocumentFragment();
    files.forEach((f, i) => {
      const d = document.createElement('div');
      d.className = 'lb-thumb' + (i === active ? ' active' : '');
      d.innerHTML = `<img src="${f.url}" alt="${(f.name || 'proof').replace(/"/g,'&quot;')}">`;
      d.addEventListener('click', () => show(i));
      frag.appendChild(d);
    });
    strip.appendChild(frag);
  }

  // ✅ this is the ONLY place we set image, caption AND uploaded time
  function show(i) {
    if (!files.length) return;
    if (i < 0) i = files.length - 1;
    if (i >= files.length) i = 0;
    idx = i;

    const file = files[idx];
    img.src = file.url;
    cap.textContent = file.name || '';

    // show uploaded datetime
    if (uploadedEl) {
      uploadedEl.textContent = file.uploaded_at
        ? `Uploaded: ${file.uploaded_at}`
        : '';
    }

    renderThumbs(idx);
  }

  function go(delta) { show(idx + delta); }

  async function loadProofs(url) {
    listUrl = url;
    img.src = '';
    cap.textContent = '';
    strip.innerHTML = '';
    if (uploadedEl) uploadedEl.textContent = '';
    empty.style.display = 'none';
    files = [];
    idx   = 0;

    try {
      const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      if (!res.ok) throw new Error('HTTP ' + res.status);
      const json = await res.json();
      files = json?.files || [];
      if (!files.length) {
        empty.style.display = 'block';
        return;
      }
      show(0);
    } catch (err) {
      console.error(err);
      empty.style.display = 'block';
      empty.textContent = 'Unable to load proofs.';
    }
  }

  // delegate click from table
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.js-view-proofs');
    if (!btn) return;

    const url = btn.getAttribute('data-url');
    if (!url) return;

    uploadUrl = btn.getAttribute('data-upload-url') || null;

    openModal();
    loadProofs(url);
  });

  if (uploadForm && uploadInput) {
    uploadForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      if (!uploadUrl) {
        alert('Missing upload URL.');
        return;
      }
      if (!uploadInput.files.length) {
        alert('Please choose at least one file.');
        return;
      }

      const fd = new FormData(uploadForm);
      const submitBtn = uploadForm.querySelector('button[type="submit"]');

      try {
        if (submitBtn) {
          submitBtn.disabled = true;
          submitBtn.innerHTML =
            '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Uploading...';
        }

        const res = await fetch(uploadUrl, {
          method: 'POST',
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          body: fd,
        });

        const json = await res.json().catch(() => ({}));
        if (!res.ok || json.ok === false) {
          throw new Error(json.message || 'Upload failed.');
        }

        uploadInput.value = '';

        // reload list so new proofs appear immediately
        if (listUrl) {
          await loadProofs(listUrl);
        }
      } catch (err) {
        console.error(err);
        alert('Unable to upload proof(s). Please try again.');
      } finally {
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.innerHTML = '<i class="bi bi-cloud-upload me-1"></i> Upload new proof';
        }
      }
    });
  }
});

(() => {
  const y2us=v=>/^\d{4}-\d{2}-\d{2}$/.test(v)?(v.slice(5,7)+'/'+v.slice(8,10)+'/'+v.slice(0,4)):v;
  const us2y=v=>{const m=v.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);if(!m)return'';const[,mm,dd,yy]=m;return `${yy}-${mm.padStart(2,'0')}-${dd.padStart(2,'0')}`};
  function attachNativeDate(input){
    const open=()=>{if(input.type!=='date'){const y=us2y(input.value.trim());input.type='date';if(y)input.value=y;input.showPicker?input.showPicker():input.focus();}};
    const close=()=>{if(input.type==='date'){if(input.value)input.value=y2us(input.value);input.type='text';}};
    input.addEventListener('focus',open); input.addEventListener('click',open);
    input.addEventListener('change',()=>{if(input.type==='date' && input.value){input.type='text';input.value=y2us(input.value);input.dispatchEvent(new Event('change',{bubbles:true}));}});
    input.addEventListener('blur',close);
  }
  document.querySelectorAll('.js-date').forEach(attachNativeDate);
})();

/* Double-click row → view */
document.addEventListener('dblclick', e => {
  const tr = e.target.closest('tr.js-row-open');
  if (!tr) return;
  const tag = (e.target.tagName||'').toLowerCase();
  if (['a','button','input','select','textarea','label','svg','path','i'].includes(tag)) return;
  const url = tr.dataset.href;
  if (url) location.href = url;
});

function showProofAt(index) {
  const file = files[index];
  if (!file) return;

  document.getElementById('lbImage').src = file.url;
  document.getElementById('lbCaption').textContent = file.name || '—';

  const uploadedEl = document.getElementById('lbUploadedAt');
  if (uploadedEl) {
    uploadedEl.textContent = file.uploaded_at
      ? `Uploaded: ${file.uploaded_at}`
      : '';
  }
}
</script>
@endpush
@endsection