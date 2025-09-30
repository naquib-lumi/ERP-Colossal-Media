@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
  /* ===== 基础布局与卡片 ===== */
  .page-wrap{max-width:1180px;margin:0 auto}
  .card.soft{border:0;box-shadow:0 3px 10px rgba(16,24,40,.06);border-radius:14px}
  .section-hd{display:flex;align-items:center;gap:8px;font-weight:700;color:#101828;margin-bottom:12px}
  .section-hd .bi{color:#667085}

  /* 顶部信息 */
  .dl{display:grid;grid-template-columns:180px 1fr;row-gap:10px;column-gap:16px}
  .dl dt{font-size:12px;color:#667085;line-height:1.2}
  .dl dd{margin:0;color:#101828}
  .dl .muted{color:#475467}

  /* 备注 chips */
  .chips{display:flex;gap:8px;flex-wrap:nowrap;overflow:auto hidden;padding-bottom:2px}
  .chip{white-space:nowrap;border-radius:999px;background:#F2F4F7;color:#344054;font-size:12px;padding:6px 10px}

  /* 子卡片 */
  .subcard{border:1px solid #EEF2F7;border-radius:12px;background:#fff;box-shadow:0 1px 3px rgba(16,24,40,.04);padding:0}
  .subcard-head{display:flex;align-items:center;gap:8px;padding:20px}
  .subcard-title{font-weight:700;color:#101828}
  .subcard-desc{color:#667085;font-size:13px;margin:2px 0 0 0}
  .subcard-head .left{display:flex;align-items:flex-start;gap:12px}
  .subcard-head .right{margin-left:auto}
  .subcard-body{padding:20px}
  .subcard-body.hidden{display:none}

  /* 折叠按钮（图标版） */
  .btn-toggle-icon{border:1px solid #E5E7EB;background:#fff;border-radius:10px;width:36px;height:32px;display:inline-flex;align-items:center;justify-content:center;color:#475467}
  .btn-toggle-icon .bi{transition:transform .2s ease}
  .btn-toggle-icon.open .bi{transform:rotate(180deg)}

  /* 产品表格 */
  .table-products thead th{font-size:12px;color:#475467;font-weight:700;white-space:nowrap;background:#F8FAFC;position:sticky;top:0;z-index:1}
  .table-products> :not(caption)>*>*{padding:12px 14px;vertical-align:middle}
  .table-products tbody tr:nth-child(odd){background:#FCFCFD}
  .col-item{min-width:200px}.col-qty{width:100px}.col-size{width:120px}.col-bleed{width:110px}
  .col-material{width:140px}.col-centre{width:110px}.col-lam{width:160px}.col-printer{width:170px}
  .col-cutter{width:170px}.col-assemble{width:110px}
  .badge-yes,.badge-no{display:inline-flex;align-items:center;justify-content:center;min-width:34px;height:22px;border-radius:999px;font-size:12px}
  .badge-yes{background:#ECFDF3;color:#027A48}.badge-no{background:#FFF1F3;color:#B42318}

  /* Delivery */
  .dlv-card{border:0;box-shadow:0 3px 10px rgba(16,24,40,.06);border-radius:14px}
  .dlv-hd{display:flex;align-items:center;gap:8px;font-weight:700;color:#101828}
  .dlv-sub{color:#98A2B3;font-size:12px}
  .dlv-product{margin-top:14px}
  .dlv-product-title{font-weight:600;color:#101828;margin-bottom:10px}
  .dlv-list{display:grid;grid-template-columns:1fr;gap:12px}
  .dlv-item{display:grid;grid-template-columns:40px 1fr;column-gap:12px;align-items:flex-start;background:#fff;border:1px solid #EEF2F7;border-radius:12px;padding:14px 16px}
  .dlv-icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;background:#F8FAFC;border:1px solid #EEF2F7;color:#667085}
  .dlv-head{display:flex;align-items:center;gap:8px;margin-bottom:8px}
  .dlv-fields{display:grid;gap:10px 16px;grid-template-columns:repeat(4,minmax(180px,1fr))}
  @media (max-width:1200px){ .dlv-fields{grid-template-columns:repeat(3,minmax(180px,1fr))} }
  @media (max-width:992px){  .dlv-fields{grid-template-columns:repeat(2,minmax(180px,1fr))} }
  @media (max-width:576px){  .dlv-fields{grid-template-columns:1fr} }
  .badge-method{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:5px 10px;font-size:12px;font-weight:600}
  .badge-delivery{background:#EEF2FF;color:#3730A3}.badge-courier{background:#ECFEFF;color:#155E75}.badge-pickup{background:#F0FDF4;color:#166534}
  .field .label{font-size:12px;color:#98A2B3;margin-bottom:2px}.field .value{color:#111827}.field .value-strong{font-weight:700}

  /* 附件/上传者 */
  .file-row{display:flex;align-items:center;justify-content:space-between;border:1px solid #E5E7EB;border-radius:10px;padding:12px 16px;background:#fff}
  .file-row+.file-row{margin-top:10px}
  .file-meta{display:flex;align-items:center;gap:12px}
  .file-meta .bi{font-size:20px;color:#667085}
  .file-name{color:#101828;font-weight:500}.file-size{color:#98A2B3;font-size:12px}
  .assignee-chip,.uploader-chip{display:inline-flex;align-items:center;padding:6px 12px;border-radius:999px;font-weight:600;font-size:12px;border:1px solid #E0E7FF;background:#EEF2FF;color:#3730A3}

  /* ===== 编辑态（只显示备注块） ===== */
  .edit-only{display:none !important;}
  .is-editing .edit-only{display:flex !important;}

  /* ===== Actionbar 三状态（右对齐） ===== */
  .actionbar{margin-top:12px}
  .actionbar .action-pre,
  .actionbar .action-post,
  .actionbar .action-edit{display:none !important;width:100%;}
  .actionbar .action-pre{display:block !important;}                    /* 初始 */
  .is-accepted .actionbar .action-pre{display:none !important;}        /* 已接受显示 post */
  .is-accepted .actionbar .action-post{display:block !important;}
  .is-editing .actionbar .action-pre,
  .is-editing .actionbar .action-post{display:none !important;}        /* 编辑只显示 edit */
  .is-editing .actionbar .action-edit{display:block !important;}
  .actionbar .toolbar{width:100%;display:flex;justify-content:flex-end;gap:.75rem}

  /* ===== Modal（自定义，不使用 Bootstrap JS） ===== */
  body.modal-open { overflow: hidden !important; }   /* 打开弹窗时锁滚动 */
  .modal-mask{position:fixed;inset:0;background:rgba(2,6,23,.60);display:none !important;z-index:1050}
  .modal-mask.show{display:block !important;}
  .modal-wrap{position:fixed !important;inset:0 !important;display:grid !important;place-items:center !important;padding:24px !important}
  .modal{
    display:block !important;position:relative !important;inset:auto !important;
    width:600px !important;max-width:92vw !important;height:auto !important;margin:0 !important;overflow:visible !important;
    background:#fff !important;border:1px solid #E5E7EB !important;border-radius:14px !important;box-shadow:0 25px 80px rgba(0,0,0,.28) !important;
  }
  .modal-header{padding:18px 24px !important;border-bottom:1px solid #E6E8EC !important;gap:12px !important;display:flex;align-items:center}
  .modal-title{font-weight:800 !important;font-size:18px !important;color:#0F172A !important}
  .modal-header i.bi-check2,.modal-header i.bi-check2-circle,.modal-header i.bi-check-circle{color:#16A34A !important;font-size:18px !important}
  .modal-close{margin-left:auto !important;color:#9AA4B2 !important;border:0 !important;background:transparent !important}
  .modal-close:hover{color:#6B7280 !important}
  .modal-body{padding:20px 24px !important;color:#334155 !important;font-size:16px !important;line-height:1.6 !important}
  .modal-body .help{color:#6B7280 !important}
  .modal-footer{display:flex !important;justify-content:flex-end !important;gap:12px !important;padding:14px 16px !important;border-top:1px solid #E6E8EC !important;background:#F8FAFC !important}
  .modal .btn{display:inline-flex !important;align-items:center !important;gap:8px !important;font-weight:700 !important;border-radius:999px !important;padding:10px 16px !important;box-shadow:none !important;letter-spacing:.2px}
  .modal .btn-back{background:#EEF2F6 !important;color:#0F172A !important;border:1px solid #E5E7EB !important}
  .modal .btn-back:hover{background:#E2E8F0 !important}
  .modal .btn-accept{background:#23263A !important;color:#fff !important;border:1px solid #23263A !important}
  .modal .btn-accept:hover{background:#1D2033 !important;border-color:#1D2033 !important}
  .modal .btn-reject{background:#fff !important;color:#E11D48 !important;border:2px solid #F43F5E !important}
  .modal .btn-reject:hover{background:#FFF1F2 !important}
  .modal i{font-size:14px}
  .modal textarea{width:100%;min-height:110px;resize:vertical;border:1px solid #E5E7EB;border-radius:8px;padding:10px 12px;color:#0F172A;outline:none}
  .modal textarea::placeholder{color:#9AA4B2}
  .modal textarea:focus{border-color:#94A3B8;box-shadow:0 0 0 3px rgba(148,163,184,.25)}
</style>

<div class="container-fluid py-4 px-4">
  <div class="page-wrap" id="pageRoot">
    @php $assignee = $assignee ?? 'Data Keyin'; @endphp
    <div class="d-flex align-items-center justify-content-between mb-2">
      <div class="d-flex align-items-center gap-2">
        <a href="javascript:history.back()" class="text-decoration-none text-muted"><i class="bi bi-arrow-left"></i></a>
        <h1 class="h4 fw-bold mb-0">Project Task — <span class="text-muted">ORD005-P2</span></h1>
      </div>
      <span class="assignee-chip">{{ $assignee }}</span>
    </div>
    <div class="text-muted mb-3">Dispatch Control</div>

    {{-- Job Information --}}
    <div class="card soft mb-4">
      <div class="card-body">
        <div class="section-hd"><i class="bi bi-card-text"></i> Job Information</div>
        <div class="row g-4">
          <div class="col-12 col-lg-6">
            <dl class="dl">
              <dt>Product ID</dt><dd>ORD005-P1</dd>
              <dt>Job Order ID</dt><dd>#ORD-2025-003</dd>
              <dt>Job Title</dt><dd>Corporate Business Card Design</dd>
              <dt>Company Name</dt><dd>TechCorp Solutions Ltd</dd>
            </dl>
          </div>
          <div class="col-12 col-lg-6">
            <dl class="dl">
              <dt>Assigned By</dt><dd>Artist A</dd>
              <dt>Received Date</dt><dd>2025-07-15</dd>
              <dt>Deadline</dt><dd><span class="muted">2025-07-25</span></dd>
              <dt>Design Confirmation</dt><dd><span class="badge-yes">Yes</span></dd>
            </dl>
          </div>
        </div>

        <div class="mt-3">
          <div class="section-hd" style="margin-bottom:8px"><i class="bi bi-chat-square-text"></i> Product Remarks</div>
          <div class="chips">
            <span class="chip">Delivery: Self Pickup must be on time before 10:30 AM</span>
            <span class="chip">Printing: Ensure color consistency with company brand palette</span>
            <span class="chip">Printing: Pack each batch separately (English vs. Chinese cards)</span>
          </div>
        </div>
      </div>
    </div>

    {{-- Product Details（只读） --}}
    <div class="card soft mb-4">
      <div class="card-body">
        {{-- Product 1 --}}
        <div class="subcard mb-3">
          <div class="subcard-head">
            <div class="left">
              <i class="bi bi-box"></i>
              <div>
                <div class="subcard-title">Product 1 — Poster A2 · Glossy</div>
                <p class="subcard-desc">ID: ORD003-P1 · Qty: 1000 · Material: 260gsm Art Card</p>
              </div>
            </div>
            <div class="right">
              <button class="btn-toggle-icon" data-toggle="subcard" data-target="p1-body"><i class="bi bi-chevron-down"></i></button>
            </div>
          </div>
          <div class="subcard-body" id="p1-body">
            <div class="table-responsive">
              <table class="table table-products align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th class="col-item">ITEM</th><th class="col-qty">QUANTITY</th><th class="col-size">SIZE</th><th class="col-bleed">BLEED</th>
                    <th class="col-material">MATERIAL</th><th class="col-centre">PRIME CENTRE</th><th class="col-lam">LAMINATION</th>
                    <th class="col-printer">PRINTER</th><th class="col-cutter">CUTTER</th><th class="col-assemble">ASSEMBLE</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>Standard Business Card (ENG)</td><td>500</td><td>9 × 5.4 cm</td><td>0.3 cm</td><td>Art Card</td>
                    <td><span class="badge-yes">Yes</span></td><td>Matt UV Lamination</td><td>Handtop Roll2Roll</td><td>Ruijie Flatbed Router</td><td><span class="badge-yes">Yes</span></td>
                  </tr>
                  <tr>
                    <td>Standard Business Card (CN)</td><td>500</td><td>9 × 5.4 cm</td><td>0.3 cm</td><td>Art Card</td>
                    <td><span class="badge-yes">Yes</span></td><td>Matt UV Lamination</td><td>Handtop Roll2Roll</td><td>Ruijie Flatbed Router</td><td><span class="badge-yes">Yes</span></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        {{-- Product 2 --}}
        <div class="subcard">
          <div class="subcard-head">
            <div class="left">
              <i class="bi bi-box"></i>
              <div>
                <div class="subcard-title">Product 2 — Business Card (Alternate Design)</div>
                <p class="subcard-desc">ID: ORD005-P2 · Qty: 1000 · Material: 300gsm Linen Card</p>
              </div>
            </div>
            <div class="right">
              <button class="btn-toggle-icon" data-toggle="subcard" data-target="p2-body"><i class="bi bi-chevron-down"></i></button>
            </div>
          </div>
          <div class="subcard-body" id="p2-body">
            <div class="table-responsive">
              <table class="table table-products align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th class="col-item">ITEM</th><th class="col-qty">QUANTITY</th><th class="col-size">SIZE</th><th class="col-bleed">BLEED</th>
                    <th class="col-material">MATERIAL</th><th class="col-centre">PRIME CENTRE</th><th class="col-lam">LAMINATION</th>
                    <th class="col-printer">PRINTER</th><th class="col-cutter">CUTTER</th><th class="col-assemble">ASSEMBLE</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td class="nowrap">Business&nbsp;Card&nbsp;(ALT)</td><td>1000</td><td>9 × 5.4 cm</td><td>0.3 cm</td><td>Linen Card</td>
                    <td><span class="badge-yes">Yes</span></td><td>Gloss Lamination</td><td>HP Indigo 7800</td><td>Graphtec Cutter</td><td><span class="badge-no">No</span></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

      </div>
    </div>

    {{-- Delivery Breakdown --}}
    <div class="card dlv-card mb-4">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <div class="dlv-hd"><i class="bi bi-truck"></i> Delivery Breakdown</div>
          <div class="dlv-sub">Total: 1000 · Delivered: 500 · Remaining: 500</div>
        </div>
        <div class="dlv-product">
          <div class="dlv-product-title">Product 1</div>
          <div class="dlv-list">
            <div class="dlv-item">
              <div class="dlv-icon"><i class="bi bi-geo-alt"></i></div>
              <div class="dlv-main">
                <div class="dlv-head"><span class="badge-method badge-delivery"><i class="bi bi-truck"></i> Delivery & Installation</span></div>
                <div class="dlv-fields">
                  <div class="field"><div class="label">Quantity</div><div class="value value-strong">500</div></div>
                  <div class="field"><div class="label">Address</div><div class="value">TechCorp HQ, KL</div></div>
                  <div class="field"><div class="label">Delivery Date &amp; Time</div><div class="value">2025-07-25 10:00 AM</div></div>
                  <div class="field"><div class="label">Install</div><div class="value">Outsource</div></div>
                  <div class="field"><div class="label">Cost</div><div class="value">RM50</div></div>
                </div>
              </div>
            </div>
            <div class="dlv-item">
              <div class="dlv-icon"><i class="bi bi-box-seam"></i></div>
              <div class="dlv-main">
                <div class="dlv-head"><span class="badge-method badge-courier"><i class="bi bi-box-arrow-up-right"></i> Courier</span></div>
                <div class="dlv-fields">
                  <div class="field"><div class="label">Quantity</div><div class="value value-strong">500</div></div>
                  <div class="field"><div class="label">Address</div><div class="value">TechCorp Penang Branch</div></div>
                  <div class="field"><div class="label">Delivery Date &amp; Time</div><div class="value">2025-07-26 02:00 PM</div></div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="dlv-product">
          <div class="dlv-product-title">Product 2</div>
          <div class="dlv-list">
            <div class="dlv-item">
              <div class="dlv-icon"><i class="bi bi-person-check"></i></div>
              <div class="dlv-main">
                <div class="dlv-head"><span class="badge-method badge-pickup"><i class="bi bi-bag-check"></i> Self Pickup</span></div>
                <div class="dlv-fields">
                  <div class="field"><div class="label">Quantity</div><div class="value value-strong">1000</div></div>
                  <div class="field"><div class="label">Address</div><div class="value">Not required for pickup</div></div>
                  <div class="field"><div class="label">Delivery Date &amp; Time</div><div class="value">2025-07-25 10:00 AM</div></div>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>

    {{-- Add Remarks（仅编辑态显示） --}}
    <div class="card soft mb-4 edit-only">
      <div class="card-body">
        <div class="section-hd"><i class="bi bi-chat-dots"></i> Add Remarks</div>
        <div id="remarks-list" class="d-flex flex-column gap-2">
          <div class="remark-row d-flex align-items-center gap-2">
            <select class="form-select form-select-sm remark-cat" style="max-width:160px">
              <option>Installation</option><option>Printing</option><option>Packing</option><option>General</option>
            </select>
            <input class="form-control form-control-sm remark-text" placeholder="Add your remark..." />
            <button type="button" class="btn btn-link text-muted p-0 remove-remark" title="Remove"><i class="bi bi-trash"></i></button>
          </div>
        </div>
        <div class="mt-2">
          <button id="btn-add-remark" type="button" class="btn btn-dark btn-sm"><i class="bi bi-plus-lg me-1"></i>Add Remark</button>
        </div>
      </div>
    </div>

    {{-- Attachments --}}
    <div class="card soft mb-4">
      <div class="card-body">
        <div class="d-flex align-items-center mb-2">
          <div class="section-hd mb-0"><i class="bi bi-paperclip"></i> Attachments</div>
          @php $uploader = $uploader ?? 'Artist A'; @endphp
          @if($uploader)<span class="uploader-chip ms-auto">{{ $uploader }}</span>@endif
        </div>
        @php
          $files = $attachments ?? [
            ['name' => 'requirements.pdf', 'size' => '1.2 MB', 'url' => '#'],
            ['name' => 'logo.png',        'size' => '856 KB', 'url' => '#'],
            ['name' => 'design-specs.pdf','size' => '2.4 MB', 'url' => '#'],
          ];
        @endphp
        @foreach($files as $f)
          @php
            $n = strtolower($f['name'] ?? '');
            $icon = (str_ends_with($n, '.pdf') ? 'file-earmark-pdf'
                   : (preg_match('/\.(png|jpe?g|gif|svg)$/', $n) ? 'file-earmark-image' : 'file-earmark'));
          @endphp
          <div class="file-row">
            <div class="file-meta">
              <i class="bi bi-{{ $icon }}"></i>
              <div>
                <div class="file-name">{{ $f['name'] ?? 'file' }}</div>
                <div class="file-size">{{ $f['size'] ?? '' }}</div>
              </div>
            </div>
            <a class="btn btn-light border btn-sm" href="{{ $f['url'] ?? '#' }}"><i class="bi bi-download me-1"></i>Download</a>
          </div>
        @endforeach
      </div>
    </div>

    {{-- Permit --}}
    <div class="card soft mb-4">
      <div class="card-body">
        <div class="section-hd"><i class="bi bi-file-earmark-lock"></i> Permit</div>
        @php $permit = $permit ?? ['name'=>'Permit.pdf','size'=>'1.2 MB','url'=>'#']; @endphp
        <div class="file-row">
          <div class="file-meta">
            <i class="bi bi-file-earmark-pdf"></i>
            <div>
              <div class="file-name">{{ $permit['name'] }}</div>
              <div class="file-size">{{ $permit['size'] }}</div>
            </div>
          </div>
          <a class="btn btn-light border btn-sm" href="{{ $permit['url'] }}"><i class="bi bi-download me-1"></i>Download</a>
        </div>
      </div>
    </div>

    {{-- ===== 底部 Actionbar（三段式） ===== --}}
    <div class="actionbar">
      <!-- 初始：Accept / Reject / Back -->
      <div class="action-pre">
        <div class="toolbar">
          <button type="button" id="btnAccept" class="btn btn-dark"><i class="bi bi-check2 me-1"></i>Accept</button>
          <button type="button" id="btnReject" class="btn btn-outline-danger"><i class="bi bi-x-lg me-1"></i>Reject</button>
          <a href="javascript:history.back()" class="btn btn-light border">Back</a>
        </div>
      </div>
      <!-- 已接受：Edit / Back -->
      <div class="action-post">
        <div class="toolbar">
          <button type="button" id="btnEdit" class="btn btn-light border"><i class="bi bi-pencil me-1"></i>Edit</button>
          <a href="javascript:history.back()" class="btn btn-light border">Back</a>
        </div>
      </div>
      <!-- 编辑中：Save / Cancel -->
      <div class="action-edit">
        <div class="toolbar">
          <button type="button" id="btnSave" class="btn btn-dark"><i class="bi bi-save2 me-1"></i>Save Task</button>
          <button type="button" id="btnCancel" class="btn btn-outline-secondary">Cancel</button>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- ===== Accept Modal ===== -->
<div id="modalAccept" class="modal-mask" aria-hidden="true">
  <div class="modal-wrap">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="acceptTitle">
      <div class="modal-header">
        <i class="bi bi-check2-circle"></i>
        <div id="acceptTitle" class="modal-title">Accept Logistic Task</div>
        <button type="button" class="modal-close" data-close="modalAccept"><i class="bi bi-x-lg"></i></button>
      </div>
      <div class="modal-body">
        Are you sure you want to accept this task?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-back" data-close="modalAccept">Cancel</button>
        <button type="button" id="confirmAccept" class="btn btn-accept"><i class="bi bi-check2"></i>Accept</button>
      </div>
    </div>
  </div>
</div>

<!-- ===== Reject Modal ===== -->
<div id="modalReject" class="modal-mask" aria-hidden="true">
  <div class="modal-wrap">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="rejectTitle">
      <div class="modal-header">
        <div id="rejectTitle" class="modal-title">Reject Logistic Task</div>
        <button type="button" class="modal-close" data-close="modalReject"><i class="bi bi-x-lg"></i></button>
      </div>
      <div class="modal-body">
        <div class="help">Please provide a reason for rejecting this task.</div>
        <textarea id="rejectReason" placeholder='e.g. "Provide reason for rejection..."'></textarea>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-back" data-close="modalReject">Cancel</button>
        <button type="button" id="confirmReject" class="btn btn-reject"><i class="bi bi-x-lg"></i>Reject</button>
      </div>
    </div>
  </div>
</div>

<script>
/* ===== 折叠（icon-only） ===== */
document.querySelectorAll('[data-toggle="subcard"]').forEach(btn=>{
  const body=document.getElementById(btn.dataset.target);
  btn.addEventListener('click',()=>{
    body?.classList.toggle('hidden');
    btn.classList.toggle('open');
  });
});

/* ===== Modal 工具 ===== */
function _openModalCore(id){
  const el=document.getElementById(id);
  if(el){ el.classList.add('show'); el.setAttribute('aria-hidden','false'); }
}
function _closeModalCore(id){
  const el=document.getElementById(id);
  if(el){ el.classList.remove('show'); el.setAttribute('aria-hidden','true'); }
}

// 包一层，顺便切换 body 滚动锁
window.openModal=function(id){
  document.body.classList.add('modal-open');
  _openModalCore(id);
};
window.closeModal=function(id){
  _closeModalCore(id);
  // 若无任何弹窗开启，移除锁
  if(!document.querySelector('.modal-mask.show')) document.body.classList.remove('modal-open');
};

/* 点击遮罩区域关闭 */
['modalAccept','modalReject'].forEach(mid=>{
  const mask=document.getElementById(mid);
  mask && mask.addEventListener('click',(e)=>{ if(e.target===mask) closeModal(mid); });
});

/* Esc 关闭 */
document.addEventListener('keydown',(e)=>{
  if(e.key==='Escape'){
    ['modalAccept','modalReject'].forEach(id=>{
      const el=document.getElementById(id);
      if(el && el.classList.contains('show')) closeModal(id);
    });
  }
});

/* ===== 三段式逻辑 + 绑定 ===== */
const root=document.getElementById('pageRoot');

document.getElementById('btnAccept')?.addEventListener('click',()=>openModal('modalAccept'));
document.getElementById('btnReject')?.addEventListener('click',()=>openModal('modalReject'));

document.getElementById('confirmAccept')?.addEventListener('click',()=>{
  // TODO: 调后端“接受任务”
  root.classList.add('is-accepted');
  root.classList.remove('is-editing');
  closeModal('modalAccept');
});

document.getElementById('confirmReject')?.addEventListener('click',()=>{
  const reason=(document.getElementById('rejectReason')?.value||'').trim();
  // TODO: 调后端“拒绝任务”，提交 {reason}
  closeModal('modalReject');
  console.log('Rejected with reason:', reason);
});

/* 编辑开关（仅备注块） */
document.getElementById('btnEdit')?.addEventListener('click',()=>root.classList.add('is-editing'));
document.getElementById('btnCancel')?.addEventListener('click',()=>root.classList.remove('is-editing'));
document.getElementById('btnSave')?.addEventListener('click',()=>{
  // TODO: 保存备注
  root.classList.remove('is-editing');
  root.classList.add('is-accepted');
});

/* Add Remarks 动态行 */
(function(){
  const list=document.getElementById('remarks-list');
  const btn=document.getElementById('btn-add-remark');
  function row(){
    const d=document.createElement('div');
    d.className='remark-row d-flex align-items-center gap-2';
    d.innerHTML=`
      <select class="form-select form-select-sm remark-cat" style="max-width:160px">
        <option>Installation</option><option>Printing</option><option>Packing</option><option>General</option>
      </select>
      <input class="form-control form-control-sm remark-text" placeholder="Add your remark..." />
      <button type="button" class="btn btn-link text-muted p-0 remove-remark" title="Remove">
        <i class="bi bi-trash"></i>
      </button>`;
    return d;
  }
  btn?.addEventListener('click',()=>list.appendChild(row()));
  list?.addEventListener('click',e=>{
    const r=e.target.closest('.remove-remark'); if(!r) return;
    const line=r.closest('.remark-row');
    if(line && list.children.length>1) line.remove();
    else if(line) line.querySelector('.remark-text').value='';
  });
})();
</script>
@endsection
