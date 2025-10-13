@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
  /* ===== 基础布局与卡片 ===== */
  .page-wrap {
    max-width: 1180px;
    margin: 0 auto
  }

  .card.soft {
    border: 0;
    box-shadow: 0 3px 10px rgba(16, 24, 40, .06);
    border-radius: 14px
  }

  .section-hd {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 700;
    color: #101828;
    margin-bottom: 12px
  }

  .section-hd .bi {
    color: #667085
  }

  /* 顶部信息 */
  .dl {
    display: grid;
    grid-template-columns: 180px 1fr;
    row-gap: 10px;
    column-gap: 16px
  }

  .dl dt {
    font-size: 12px;
    color: #667085;
    line-height: 1.2
  }

  .dl dd {
    margin: 0;
    color: #101828
  }

  .dl .muted {
    color: #475467
  }

  /* 备注 chips */
  .chips {
    display: flex;
    gap: 8px;
    flex-wrap: nowrap;
    overflow: auto hidden;
    padding-bottom: 2px
  }

  .chip {
    white-space: nowrap;
    border-radius: 999px;
    background: #F2F4F7;
    color: #344054;
    font-size: 12px;
    padding: 6px 10px
  }

  /* 子卡片 */
  .subcard {
    border: 1px solid #EEF2F7;
    border-radius: 12px;
    background: #fff;
    box-shadow: 0 1px 3px rgba(16, 24, 40, .04);
    padding: 0
  }

  .subcard-head {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 20px
  }

  .subcard-title {
    font-weight: 700;
    color: #101828
  }

  .subcard-desc {
    color: #667085;
    font-size: 13px;
    margin: 2px 0 0 0
  }

  .subcard-head .left {
    display: flex;
    align-items: flex-start;
    gap: 12px
  }

  .subcard-head .right {
    margin-left: auto
  }

  .subcard-body {
    padding: 20px
  }

  .subcard-body.hidden {
    display: none
  }

  /* 折叠按钮（图标版） */
  .btn-toggle-icon {
    border: 1px solid #E5E7EB;
    background: #fff;
    border-radius: 10px;
    width: 36px;
    height: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #475467
  }

  .btn-toggle-icon .bi {
    transition: transform .2s ease
  }

  .btn-toggle-icon.open .bi {
    transform: rotate(180deg)
  }

  /* 产品表格 */
  .table-products thead th {
    font-size: 12px;
    color: #475467;
    font-weight: 700;
    white-space: nowrap;
    background: #F8FAFC;
    position: sticky;
    top: 0;
    z-index: 1
  }

  .table-products> :not(caption)>*>* {
    padding: 12px 14px;
    vertical-align: middle
  }

  .table-products tbody tr:nth-child(odd) {
    background: #FCFCFD
  }

  .col-qty {
    width: 100px
  }

  .col-size {
    width: 30rem
  }

  .col-bleed {
    width: 20rem
  }

  .col-material {
    width: 140px
  }

  .col-centre {
    width: 80px
  }

  .col-lam {
    width: 160px
  }

  .col-printer {
    width: 170px
  }

  .col-cutter {
    width: 180px
  }

  .col-assemble {
    width: 80px
  }

  .badge-yes,
  .badge-no {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 34px;
    height: 22px;
    border-radius: 999px;
    font-size: 12px
  }

  .badge-yes {
    background: #ECFDF3;
    color: #027A48
  }

  .badge-no {
    background: #FFF1F3;
    color: #B42318
  }

  /* Delivery */
  .dlv-card {
    border: 0;
    box-shadow: 0 3px 10px rgba(16, 24, 40, .06);
    border-radius: 14px
  }

  .dlv-hd {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 700;
    color: #101828
  }

  .dlv-sub {
    color: #98A2B3;
    font-size: 12px
  }

  .dlv-product {
    margin-top: 14px
  }

  .dlv-product-title {
    font-weight: 600;
    color: #101828;
    margin-bottom: 10px
  }

  .dlv-list {
    display: grid;
    grid-template-columns: 1fr;
    gap: 12px
  }

  .dlv-item {
    display: grid;
    grid-template-columns: 40px 1fr;
    column-gap: 12px;
    align-items: flex-start;
    background: #fff;
    border: 1px solid #EEF2F7;
    border-radius: 12px;
    padding: 14px 16px
  }

  .dlv-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #F8FAFC;
    border: 1px solid #EEF2F7;
    color: #667085
  }

  .dlv-head {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px
  }

  .dlv-fields {
    display: grid;
    gap: 10px 16px;
    grid-template-columns: repeat(4, minmax(180px, 1fr))
  }

  @media (max-width:1200px) {
    .dlv-fields {
      grid-template-columns: repeat(3, minmax(180px, 1fr))
    }
  }

  @media (max-width:992px) {
    .dlv-fields {
      grid-template-columns: repeat(2, minmax(180px, 1fr))
    }
  }

  @media (max-width:576px) {
    .dlv-fields {
      grid-template-columns: 1fr
    }
  }

  .badge-method {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    border-radius: 999px;
    padding: 5px 10px;
    font-size: 12px;
    font-weight: 600
  }

  .badge-delivery {
    background: #EEF2FF;
    color: #3730A3
  }

  .badge-courier {
    background: #ECFEFF;
    color: #155E75
  }

  .badge-pickup {
    background: #F0FDF4;
    color: #166534
  }

  .field .label {
    font-size: 12px;
    color: #98A2B3;
    margin-bottom: 2px
  }

  .field .value {
    color: #111827
  }

  .field .value-strong {
    font-weight: 700
  }

  /* 附件/上传者 */
  .file-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    border: 1px solid #E5E7EB;
    border-radius: 10px;
    padding: 12px 16px;
    background: #fff
  }

  .file-row+.file-row {
    margin-top: 10px
  }

  .file-meta {
    display: flex;
    align-items: center;
    gap: 12px
  }

  .file-meta .bi {
    font-size: 20px;
    color: #667085
  }

  .file-name {
    color: #101828;
    font-weight: 500
  }

  .file-size {
    color: #98A2B3;
    font-size: 12px
  }

  .assignee-chip,
  .uploader-chip {
    display: inline-flex;
    align-items: center;
    padding: 6px 12px;
    border-radius: 999px;
    font-weight: 600;
    font-size: 12px;
    border: 1px solid #E0E7FF;
    background: #EEF2FF;
    color: #3730A3
  }

  /* ===== 编辑态：仅显示备注块 + Cutter 下拉 ===== */
  .edit-only {
    display: none !important;
  }

  .is-editing .edit-only {
    display: flex !important;
  }

  /* 进入编辑时，仅让 Cutter 列变更可编辑 */
  .td-cutter .edit-input {
    display: none
  }

  .is-editing .td-cutter {
    background: #FFFBEB
  }

  .is-editing .td-cutter .view-text {
    display: none
  }

  .is-editing .td-cutter .edit-input {
    display: block
  }

  .form-select-sm,
  .form-control-sm {
    min-height: 34px
  }

  /* ===== Actionbar 三状态（方角+定制色） ===== */
  .actionbar {
    margin-top: 12px
  }

  .actionbar .action-pre,
  .actionbar .action-post,
  .actionbar .action-edit {
    display: none !important;
    width: 100%
  }

  .actionbar .action-pre {
    display: block !important
  }

  .is-accepted .actionbar .action-pre {
    display: none !important
  }

  .is-accepted .actionbar .action-post {
    display: block !important
  }

  .is-editing .actionbar .action-pre,
  .is-editing .actionbar .action-post {
    display: none !important
  }

  .is-editing .actionbar .action-edit {
    display: block !important
  }

  .actionbar .toolbar {
    width: 100%;
    display: flex;
    justify-content: flex-end;
    gap: 18px
  }

  .actionbar .btn {
    border-radius: 8px;
    padding: 10px 16px;
    font-weight: 700;
    letter-spacing: .2px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all .15s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, .06)
  }

  .actionbar .btn-accept {
    background: #23263A;
    color: #fff;
    border: 1px solid #23263A
  }

  .actionbar .btn-accept:hover {
    background: #1D2033;
    border-color: #1D2033;
    box-shadow: 0 3px 12px rgba(35, 38, 58, .25)
  }

  .actionbar .btn-accept:active {
    transform: translateY(1px)
  }

  .actionbar .btn-reject {
    background: #fff;
    color: #E11D48;
    border: 2px solid #F43F5E;
    box-shadow: none
  }

  .actionbar .btn-reject:hover {
    background: #FFF1F2
  }

  .actionbar .btn-reject:active {
    background: #FFE4E6;
    transform: translateY(1px)
  }

  .actionbar .btn-back {
    background: #E9EDF2;
    color: #0F172A;
    border: 1px solid #DDE3EA
  }

  .actionbar .btn-back:hover {
    background: #E2E8F0
  }

  .actionbar .btn-back:active {
    transform: translateY(1px)
  }

  .actionbar .btn i {
    font-size: 14px;
    line-height: 1
  }

  /* ===== 自定义弹窗（避免与 Bootstrap 冲突） ===== */
  .cx-mask {
    position: fixed;
    inset: 0;
    background: rgba(17, 24, 39, .55);
    display: none !important;
    z-index: 1050
  }

  .cx-mask.show {
    display: block !important
  }

  .cx-wrap {
    position: absolute;
    inset: 0;
    display: grid;
    place-items: center;
    padding: 24px
  }

  .cx-modal {
    width: 520px;
    max-width: 92vw;
    background: #fff;
    border: 1px solid #E5E7EB;
    border-radius: 12px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, .25);
    overflow: hidden;
    display: block
  }

  .cx-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px 18px;
    border-bottom: 1px solid #EDF0F3
  }

  .cx-title {
    font-weight: 700;
    color: #0F172A
  }

  .cx-close {
    margin-left: auto;
    color: #9AA4B2;
    border: 0;
    background: transparent
  }

  .cx-close:hover {
    color: #6B7280
  }

  .cx-body {
    padding: 18px;
    color: #334155
  }

  .cx-body .help {
    color: #6B7280;
    font-size: 14px;
    margin-bottom: 10px
  }

  .cx-footer {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding: 14px 18px;
    border-top: 1px solid #EDF0F3;
    background: #FBFBFC
  }

  .cx-modal .btn {
    border-radius: 8px;
    padding: 8px 14px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 8px
  }

  .cx-modal .btn-accept {
    background: #23263A;
    color: #fff;
    border: 1px solid #23263A
  }

  .cx-modal .btn-accept:hover {
    background: #1D2033;
    border-color: #1D2033
  }

  .cx-modal .btn-back {
    background: #EEF2F6;
    color: #0F172A;
    border: 1px solid #E5E7EB
  }

  .cx-modal .btn-reject {
    background: #fff;
    color: #E11D48;
    border: 2px solid #F43F5E
  }

  .cx-modal i {
    font-size: 14px
  }

  .cx-modal textarea {
    width: 100%;
    min-height: 110px;
    resize: vertical;
    border: 1px solid #E5E7EB;
    border-radius: 8px;
    padding: 10px 12px;
    color: #0F172A;
    outline: none
  }

  .cx-modal textarea::placeholder {
    color: #9AA4B2
  }

  .cx-modal textarea:focus {
    border-color: #94A3B8;
    box-shadow: 0 0 0 3px rgba(148, 163, 184, .25)
  }

  /* initial state: hide edit inputs */
  .td-cutter .edit-input {
    display: none;
  }

  /* when pageRoot has .is-editing, show inputs and hide view text */
  #pageRoot.is-editing .td-cutter .edit-input {
    display: block;
  }

  #pageRoot.is-editing .td-cutter .view-text {
    display: none;
  }

  /* ===== Resizable table ===== */
  .resize-table{ table-layout: fixed; width:100%; }
  .resize-table thead th{ position:relative; overflow:visible; }
  .resize-handle{
    position:absolute; top:0; right:-4px; width:8px; height:100%;
    cursor:col-resize; z-index:2;
  }
  .resize-handle::after{
    content:""; position:absolute; top:0; bottom:0; left:3px; width:2px;
    background:transparent; transition:background .15s;
  }
  .resize-handle:hover::after{ background:#d0d5dd; }

  /* When dragging, show a guideline */
  .is-resizing *{ cursor:col-resize !important; }
  .resize-guide{
    position:fixed; top:0; bottom:0; width:1px; background:#94a3b8; pointer-events:none;
    z-index:9999; display:none;
  }

  /* ===== Remarks list (stacked) ===== */
  .remarks-block{
    border:1px solid #eceff3; border-radius:12px; padding:10px 12px; background:#fbfcfe;width: 100%;
  }
  .remarks-list{ list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:8px; }
  .remarks-list li{
    padding:8px 10px; border:1px dashed #d8dee6; border-radius:8px; background:#fff;
    white-space:normal; word-wrap:break-word; overflow-wrap:anywhere;word-break: break-word;
    line-height:1.35;
  }

  .remarks-list li .msg{
    white-space: normal;
    word-break: break-word;
    overflow-wrap: anywhere;
  }
  .remarks-list li .by{
    display:block;
    margin-top:4px;
    color:#98a2b3;               
    font-size:.75rem;            
  }
  .remarks-list li .op{ color:#667085; font-weight:600; margin-right:.35rem; text-transform:capitalize; }
</style>
<div class="resize-guide" id="colGuide"></div>
<div class="container-fluid py-4 px-4">
  <div class="page-wrap" id="pageRoot">
    {{-- 顶部 --}}
    @php
    $assignee = $header->artist_name ?? '—';
    $uploader = $header->artist_name ?? '—';
    @endphp
    @php
    // user can edit only when order is accepted=1 and not rejected
    $canEdit = ((int)($header->accepted ?? 0) === 1) && strtolower((string)($header->orderStatus ?? '')) !== 'rejected';
    @endphp
    <div class="d-flex align-items-center justify-content-between mb-2">
      <div class="d-flex align-items-center gap-2">
        <a href="javascript:history.back()" class="text-decoration-none text-muted"><i class="bi bi-arrow-left"></i></a>
        <h1 class="h4 fw-bold mb-0">Dispatch Control Task — <span class="text-muted">{{ $product_code }}</span></h1>
      </div>
      <span class="assignee-chip">{{ $assignee }}</span>
    </div>
    <div class="text-muted mb-3">Dispatch Control Module</div>

    {{-- Job Information --}}
    <div class="card soft mb-4">
      <div class="card-body">
        <div class="section-hd"><i class="bi bi-card-text"></i> Job Information</div>
        <div class="row g-4">
          <div class="col-12 col-lg-6">
            <dl class="dl">
              <dt>Job Order ID</dt>
              <dd>{{ $header->order_number ?: '—' }}</dd>
              <dt>Job Title</dt>
              <dd>{{ $header->order_title }}</dd>
              <dt>Company Name</dt>
              <dd>{{ $header->companyName ?? '—' }}</dd>
            </dl>
          </div>
          <div class="col-12 col-lg-6">
            <dl class="dl">
              <dt>Assigned By</dt>
              <dd>{{ $header->artist_name ?? '—' }}</dd>
              <dt>Received Date</dt>
              <dd>{{ $dates['order_date'] ?? '—' }}</dd>
              <dt>Deadline</dt>
              <dd><span class="muted">{{ $dates['deadline'] ?? '—' }}</span></dd>
              <dt>Design Confirmation</dt>
              <dd>
                @php $ok = strtolower((string)($header->status ?? '')) === 'completed'; @endphp

                <span class="{{ $ok ? 'badge-yes' : 'badge-no' }}">{{ $ok ? 'Yes' : 'No' }}</span>
              </dd>
            </dl>
          </div>
        </div>

        <div class="mt-3">
          <div class="section-hd" style="margin-bottom:8px"><i class="bi bi-chat-square-text"></i> Product Remarks</div>
          <div class="chips">
            @if(!empty($remarkLabels) && count($remarkLabels))
              <div class="remarks-block mt-2">
                <ul class="remarks-list">
                  @foreach ($remarkLabels as $line)
                    @php
                      // Optional: split "operation: message" → <span class="op">operation</span> message
                      $op = null; $msg = $line;
                      if (str_contains($line, ':')) {
                        [$op, $msg] = explode(':', $line, 2);
                        $op = trim($op); $msg = trim($msg);
                      }
                    @endphp
                    <li>
                      @if($op)<span class="op">{{ $op }}:</span>@endif
                      <span>{{ $msg }}</span>
                    </li>
                  @endforeach
                </ul>
              </div>
            @else
              <div class="text-muted">No product remarks.</div>
            @endif
          </div>
        </div>
      </div>
    </div>

    @foreach($blocks as $block)
    <div class="card soft mb-4">
      <div class="card-body">
        <div class="subcard mb-3">
          <div class="subcard-head">
            <div class="left">
              <i class="bi bi-box"></i>
              <div>
                <div class="fw-semibold" style="font-size:1rem;">
                  Product @if(!empty($block['product_header']['name'])) — {{ $block['product_header']['name'] }} @endif
                </div>
                <div class="text-muted small">
                  ID: {{ $block['product_header']['code'] ?? '—' }}
                  @if(isset($block['product_header']['qty'])) · Qty: {{ number_format($block['product_header']['qty']) }} @endif
                  @if(!empty($block['product_header']['material'])) · Material: {{ $block['product_header']['material'] }} @endif
                </div>
              </div>
            </div>
            <div class="right">
              <button class="btn-toggle-icon"
                data-toggle="subcard"
                data-target="p{{ $block['id'] }}-body">
                <i class="bi bi-chevron-down"></i>
              </button>
            </div>
          </div>

          <div class="subcard-body" id="p{{ $block['id'] }}-body" data-block-product="{{ $block['id'] }}">
            <div class="table-responsive">
              @php
                $widthKey = 'items-cols-' . ($header->ProductID ?? $product_header['code'] ?? 'prod');
              @endphp
              <table class="table align-middle resize-table js-resize-table" data-width-key="{{ $widthKey }}">
                <thead class="table-light">
                  <tr>
                    <th>Item <span class="resize-handle" aria-hidden="true"></span></th>
                    <th>Quantity <span class="resize-handle" aria-hidden="true"></span></th>
                    <th>Size <span class="resize-handle" aria-hidden="true"></span></th>
                    <th>Bleed <span class="resize-handle" aria-hidden="true"></span></th>
                    <th>Material <span class="resize-handle" aria-hidden="true"></span></th>
                    <th>Prime Centre <span class="resize-handle" aria-hidden="true"></span></th>
                    <th>Lamination <span class="resize-handle" aria-hidden="true"></span></th>
                    <th>Printer <span class="resize-handle" aria-hidden="true"></span></th>
                    <th>Cutter <span class="resize-handle" aria-hidden="true"></span></th>
                    <th>Assemble</th> {{-- no handle on the last column --}}
                  </tr>
                </thead>
                <tbody>
                  @forelse($block['items'] as $it)
                  <tr data-itemid="{{ $it['item_id'] }}">
                    <td>{{ $it['name'] ?? '—' }}</td>
                    <td>{{ $it['qty'] ?? '—' }}</td>
                    <td>{{ $it['size'] ?? '—' }}</td>
                    <td>{{ $it['bleed'] ?? '—' }}</td>
                    <td>{{ $it['material'] ?? '—' }}</td>
                    <td>{!! !empty($it['prime']) ? '<span class="badge-yes">Yes</span>' : '<span class="badge-no">No</span>' !!}</td>
                    <td>{{ $it['lamination'] ?? '—' }}</td>
                    <td>{{ $it['printer'] ?? '—' }}</td>
                    <td>
                      <span class="view-text">{{ $it['cutter'] ?? '—' }}</span>
                      
                    </td>
                    <td>{!! !empty($it['assemble']) ? '<span class="badge-yes">Yes</span>' : '<span class="badge-no">No</span>' !!}</td>
                  </tr>
                  @empty
                  <tr>
                    <td colspan="10" class="text-center text-muted">No items.</td>
                  </tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
        </div>
        <form id="saveForm"
          method="POST"
          action="{{ route('dispatchcontrol.jobs.save', $header->ProductID) }}"
          style="display:none">
          @csrf
        </form>
        {{-- Delivery Breakdown --}}
        @if(!empty($block['deliveries']))
        <div class="card dlv-card mb-4">
          <div class="card-body">
            @php $tot = $block['totals'] ?? ['total'=>0,'delivered'=>0,'remaining'=>0]; @endphp
            <div class="d-flex justify-content-between align-items-start mb-2">
              <div class="dlv-hd"><i class="bi bi-truck"></i> Delivery Breakdown</div>
              <div class="dlv-sub">
                Total: {{ number_format($tot['total']) }}
                · Delivered: {{ number_format($tot['delivered']) }}
                · Remaining: {{ number_format($tot['remaining']) }}
              </div>
            </div>

            <div class="dlv-product">
              <div class="dlv-product-title">
                Product — {{ $block['product_header']['name'] ?? 'Product' }}
              </div>

              <div class="dlv-list">
                @foreach ($block['deliveries'] as $d)
                @php
                $m = $d['method']; // courier | pickup | install
                $badgeClass = $m === 'courier' ? 'badge-courier' : ($m === 'pickup' ? 'badge-pickup' : 'badge-delivery');
                $badgeIcon = $d['icon'] ?? 'bi-truck';
                @endphp
                <div class="dlv-item">
                  <div class="dlv-icon"><i class="bi {{ $badgeIcon }}"></i></div>
                  <div class="dlv-main">
                    <div class="dlv-head">
                      <span class="badge-method {{ $badgeClass }}">
                        <i class="bi {{ $badgeIcon }}"></i> {{ $d['method_label'] }}
                      </span>
                    </div>
                    <div class="dlv-fields">
                      <div class="field">
                        <div class="label">Quantity</div>
                        <div class="value value-strong">{{ number_format($d['quantity']) }}</div>
                      </div>
                      <div class="field">
                        <div class="label">Location</div>
                        <div class="value">{{ ($d['location'] ?? '') !== '' ? $d['location'] : '—' }}</div>
                      </div>
                      <div class="field">
                        <div class="label">Delivery Date &amp; Time</div>
                        <div class="value">{{ $d['datetime'] ?: '—' }}</div>
                      </div>
                      @if(!empty($d['install']))
                      <div class="field">
                        <div class="label">Installation Type</div>
                        <div class="value">{{ $d['install'] }}</div>
                      </div>
                      @endif
                      @if(array_key_exists('cost',$d) && $d['cost'] !== null)
                      <div class="field">
                        <div class="label">Cost</div>
                        <div class="value">{{ number_format($d['cost'], 2) }}</div>
                      </div>
                      @endif
                    </div>
                  </div>
                </div>
                @endforeach
              </div>
            </div>

          </div>
        </div>
        @endif
      </div>
    </div>
    @endforeach



    {{-- Add Remarks（编辑态出现） --}}
    @if($canEdit)
    <div class="card soft mb-4 edit-only">
      <div class="card-body">
        <div class="section-hd"><i class="bi bi-chat-dots"></i> Add Remarks</div>
        <div id="remarks-list" class="d-flex flex-column gap-2">
          <div class="remark-row d-flex align-items-center gap-2">
            @php
              // keep this right above the select, or define it once earlier and reuse
              $ops = [
                'printing'     => 'Printing',
                'furnishing'   => 'Furnishing',
                'installation' => 'Delivery & Installation',
                'courier'      => 'Courier',
                'self_pickup'  => 'Self Pickup',
              ];
            @endphp
            <select class="form-select form-select-sm remark-cat" style="max-width:180px">
              @foreach($ops as $val => $label)
                <option value="{{ $val }}">{{ $label }}</option>
              @endforeach
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
    @endif

    {{-- Attachments --}}
    <div class="card soft mb-4">
      <div class="card-body">
        <div class="d-flex align-items-center mb-2">
          <div class="section-hd mb-0"><i class="bi bi-paperclip"></i> Attachments</div>
          <span class="uploader-chip ms-auto">{{ $uploader }}</span>
        </div>
        @php
        // If you later pass real $attachments, this will render them. Keeping demo fallback.
        $files = $attachments ?? [
        ['name' => 'requirements.pdf', 'size' => '1.2 MB', 'url' => '#'],
        ['name' => 'logo.png', 'size' => '856 KB', 'url' => '#'],
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
          <a class="btn btn-light border btn-sm" href="{{ $f['url'] ?? '#' }}"><i class="bi bi-eye me-1"></i>View</a>
        </div>
        @endforeach
      </div>
    </div>
    <form id="acceptForm" method="POST" action="{{ route('dispatchcontrol.orders.accept', $header->ProductID) }}" style="display:none">
      @csrf
    </form>

    <form id="rejectForm" method="POST" action="{{ route('dispatchcontrol.orders.reject', $header->ProductID) }}" style="display:none">
      @csrf
      <input type="hidden" name="reason" id="rejectReasonInput">
    </form>

    @php
      $role = auth()->user()->role ?? '';

      // role → stage
      $roleToStage = [
        'operations-printing'              => 'printing',
        'operation-furnishing'             => 'furnishing',
        'operations-delivery-installation' => 'installation',
        'operations-dispatch-control'      => 'delivery',
      ];

      $myStage   = $roleToStage[$role] ?? null;
      $prodStage = strtolower((string)($header->taskType ?? ''));
      $allowDeliveryOverride = ($prodStage === 'delivery');
      $canSeeDecision = empty($isHistoryView)
        && ( ($myStage === $prodStage) || $allowDeliveryOverride )
        && strtolower((string)($header->orderStatus ?? '')) !== 'rejected'
        && (int)($header->accepted ?? 0) === 0;

      $canEditThisStage = empty($isHistoryView) && $canEdit && ( ($myStage === $prodStage) || $allowDeliveryOverride );

      $isRejected = isset($header->accepted) && (int)$header->accepted === 0;
      $isAccepted = isset($header->accepted) && (int)$header->accepted === 1;
      $isPending  = !isset($header->accepted) || $header->accepted === null;

      $isCompleted = strtolower((string)($header->status ?? '')) === 'completed';

      use Illuminate\Support\Facades\DB;
      use Illuminate\Support\Facades\Request;

      // Get current product ID from URL, e.g. /installation/job/44
      $productId = (int) Request::route('product');

      // Fetch product name directly from DB (ensures correct product)
      $productName = DB::table('products')->where('ProductID', $productId)->value('productName');
    @endphp
    
    {{-- ===== 底部 Actionbar（三段式） ===== --}}
    <div class="actionbar">
      <div class="action-pre">
        <div class="toolbar">
          @if ($isRejected)
            <div class="alert alert-danger mb-2" style="font-weight:500;">
              This product <strong>{{ $productName }}</strong> has been rejected.
            </div>
            <a href="javascript:history.back()" class="btn btn-back">Back</a>
          @endif
          
          @if ($isPending && $canSeeDecision)
            <button type="button" id="btnAccept" class="btn btn-accept">
              <i class="bi bi-check2"></i> Accept
            </button>
            <button type="button" id="btnReject" class="btn btn-reject">
              <i class="bi bi-x-lg"></i> Reject
            </button>
            <a href="javascript:history.back()" class="btn btn-back">Back</a>
          @endif

          @if ($isAccepted && $canEditThisStage && !$isCompleted)
            <div class="toolbar">
              <button type="button" id="btnEdit" class="btn btn-back">
                <i class="bi bi-pencil"></i> Edit
              </button>
              <a href="javascript:history.back()" class="btn btn-accept">
                <i class="bi bi-arrow-left"></i> Back
              </a>
            </div>
          @endif
        </div>
      </div>

      @if(empty($isHistoryView))
      <div class="action-edit">
        <div class="toolbar">
          <button type="button" id="btnSave" class="btn btn-accept"><i class="bi bi-save2"></i> Save Task</button>
          <button type="button" id="btnCancel" class="btn btn-back">Cancel</button>
        </div>
      </div>
      @endif
    </div>

  </div>
</div>

<!-- ===== Accept 弹窗 ===== -->
@if(empty($isHistoryView))
<div id="modalAccept" class="cx-mask" aria-hidden="true">
  <div class="cx-wrap">
    <div class="cx-modal" role="dialog" aria-modal="true" aria-labelledby="acceptTitle">
      <div class="cx-header">
        <i class="bi bi-check2-circle text-success"></i>
        <div id="acceptTitle" class="cx-title">Accept Dispatch Control Task</div>
        <button type="button" class="cx-close" data-close="modalAccept"><i class="bi bi-x-lg"></i></button>
      </div>
      <div class="cx-body">Are you sure you want to accept this task?</div>
      <div class="cx-footer">
        <button type="button" class="btn btn-back" data-close="modalAccept">Cancel</button>
        <button type="button" id="confirmAccept" class="btn btn-accept"><i class="bi bi-check2"></i>Accept</button>
      </div>
    </div>
  </div>
</div>

<!-- ===== Reject 弹窗 ===== -->
<div id="modalReject" class="cx-mask" aria-hidden="true">
  <div class="cx-wrap">
    <div class="cx-modal" role="dialog" aria-modal="true" aria-labelledby="rejectTitle">
      <div class="cx-header">
        <div id="rejectTitle" class="cx-title">Reject Dispatch Control Task</div>
        <button type="button" class="cx-close" data-close="modalReject"><i class="bi bi-x-lg"></i></button>
      </div>
      <div class="cx-body">
        <div class="help">Please provide a reason for rejecting this task.</div>
        <textarea id="rejectReason" placeholder='e.g. "Provide reason for rejection..."'></textarea>
      </div>
      <div class="cx-footer">
        <button type="button" class="btn btn-back" data-close="modalReject">Cancel</button>
        <button type="button" id="confirmReject" class="btn btn-reject"><i class="bi bi-x-lg"></i>Reject</button>
      </div>
    </div>
  </div>
</div>
@endif

<script>
document.addEventListener('DOMContentLoaded', function () {
  /* ---------- Collapsible product sections ---------- */
  document.querySelectorAll('[data-toggle="subcard"]').forEach(btn => {
    const body = document.getElementById(btn.dataset.target);
    btn.addEventListener('click', () => {
      body?.classList.toggle('hidden');
      btn.classList.toggle('open');
    });
  });

  /* ---------- Modal helpers ---------- */
  function openModal(id){ document.getElementById(id)?.classList.add('show'); }
  function closeModal(id){ document.getElementById(id)?.classList.remove('show'); }
  window.openModal  = openModal;
  window.closeModal = closeModal;

  document.querySelectorAll('[data-close]').forEach(btn => {
    btn.addEventListener('click', () => closeModal(btn.getAttribute('data-close')));
  });
  ['modalAccept','modalReject'].forEach(mid => {
    const mask = document.getElementById(mid);
    mask?.addEventListener('click', e => { if (e.target === mask) closeModal(mid); });
  });

  /* ---------- Accept / Reject ---------- */
  document.getElementById('btnAccept')?.addEventListener('click', () => openModal('modalAccept'));
  document.getElementById('btnReject')?.addEventListener('click', () => openModal('modalReject'));

  document.getElementById('confirmAccept')?.addEventListener('click', () => {
    document.getElementById('acceptForm')?.submit();
  });

  document.getElementById('confirmReject')?.addEventListener('click', () => {
    const reason = (document.getElementById('rejectReason')?.value || '').trim();
    if (!reason) { alert('Please provide a reason.'); return; }

    document.getElementById('rejectReasonInput').value = reason;
    document.getElementById('rejectForm')?.submit();

    // instantly hide the Accept/Reject buttons before reload
    document.getElementById('btnAccept')?.classList.add('d-none');
    document.getElementById('btnReject')?.classList.add('d-none');
  });

  /* ---------- Edit mode helpers ---------- */
  function syncCutterSelects() {
    document.querySelectorAll('.td-cutter').forEach(td => {
      const span = td.querySelector('.view-text');
      const sel  = td.querySelector('.edit-input');
      if (span && sel) {
        [...sel.options].forEach(o => o.selected = (o.text.trim() === span.textContent.trim()));
      }
    });
  }

  document.getElementById('btnEdit')?.addEventListener('click', () => {
    document.getElementById('pageRoot')?.classList.add('is-editing');
    syncCutterSelects();
  });

  document.getElementById('btnCancel')?.addEventListener('click', () => {
    document.getElementById('pageRoot')?.classList.remove('is-editing');
  });

  /* ---------- Add Remarks (client-side rows) ---------- */
  (function initDynamicRemarks(){
    const list = document.getElementById('remarks-list');
    const btn  = document.getElementById('btn-add-remark');
    if (!list || !btn) return;

    // Match backend $ops
    const ops = {
      'printing': 'Printing',
      'furnishing': 'Furnishing',
      'installation': 'Delivery & Installation',
      'courier': 'Courier',
      'self_pickup': 'Self Pickup'
    };

    function makeRow(){
      const d = document.createElement('div');
      d.className = 'remark-row d-flex align-items-center gap-2';

      // generate options dynamically
      let options = '';
      for (const [val, label] of Object.entries(ops)) {
        options += `<option value="${val}">${label}</option>`;
      }

      d.innerHTML = `
        <select class="form-select form-select-sm remark-cat" style="max-width:180px">
          ${options}
        </select>
        <input class="form-control form-control-sm remark-text" placeholder="Add your remark..." />
        <button type="button" class="btn btn-link text-muted p-0 remove-remark" title="Remove">
          <i class="bi bi-trash"></i>
        </button>`;
      return d;
    }

    btn.addEventListener('click', () => list.appendChild(makeRow()));
    list.addEventListener('click', e => {
      const rm = e.target.closest('.remove-remark');
      if (!rm) return;
      const row = rm.closest('.remark-row');
      if (row && list.children.length > 1) row.remove();
      else if (row) row.querySelector('.remark-text').value = '';
    });
  })();

  /* ---------- SAVE TASK (hidden form submit) ---------- */
  document.getElementById('btnSave')?.addEventListener('click', () => {
    const productId = {{ (int)$header->ProductID }};
    const form = document.getElementById('saveForm');

    // 0) remove previously appended inputs BUT keep CSRF (_token)
    Array.from(form.querySelectorAll('input[type="hidden"]'))
      .forEach(inp => { if (inp.name !== '_token') inp.remove(); });

    // 1) scope to the current product block
    const block = document.querySelector(`[data-block-product='${productId}']`);
    if (!block) { alert('No product block found to save.'); return; }

    // 2) cutters => cutters[<ItemID>]
    block.querySelectorAll('tr[data-itemid]').forEach(tr => {
      const itemId = tr.getAttribute('data-itemid');
      const sel    = tr.querySelector('.td-cutter .edit-input');
      if (itemId && sel) {
        const h = document.createElement('input');
        h.type  = 'hidden';
        h.name  = `cutters[${itemId}]`;
        h.value = sel.value.trim();
        form.appendChild(h);
      }
    });

    // 3) remarks => remarks[n][operation], remarks[n][remark]
    const rows = document.querySelectorAll('#remarks-list .remark-row');
    let i = 0;
    rows.forEach(r => {
      const op = (r.querySelector('.remark-cat')?.value || '').trim();
      const tx = (r.querySelector('.remark-text')?.value || '').trim();
      if (!tx) return;

      const h1 = document.createElement('input');
      h1.type  = 'hidden';
      h1.name  = `remarks[${i}][operation]`;
      h1.value = op;
      form.appendChild(h1);

      const h2 = document.createElement('input');
      h2.type  = 'hidden';
      h2.name  = `remarks[${i}][remark]`;
      h2.value = tx;
      form.appendChild(h2);

      i++;
    });

    // 4) submit
    form.submit();
  });
});


(function() {
  const guide = document.getElementById('colGuide');

  function initResizableTable(tbl){
    const key = tbl.dataset.widthKey || 'tbl-cols';
    const thead = tbl.querySelector('thead');
    const ths   = [...thead.querySelectorAll('th')];
    if (!ths.length) return;

    // Build a <colgroup> so we can set widths cleanly
    let colgroup = tbl.querySelector('colgroup');
    if (!colgroup){
      colgroup = document.createElement('colgroup');
      for (let i=0;i<ths.length;i++){ colgroup.appendChild(document.createElement('col')); }
      tbl.insertBefore(colgroup, tbl.firstChild);
    }
    const cols = [...colgroup.querySelectorAll('col')];

    // load saved widths
    const saved = localStorage.getItem(key);
    if (saved){
      try {
        const widths = JSON.parse(saved);
        widths.forEach((w,i)=>{ if(cols[i]) cols[i].style.width = w; });
      } catch(e){}
    } else {
      // initialize from current header widths
      ths.forEach((th,i)=>{ cols[i].style.width = th.getBoundingClientRect().width + 'px'; });
    }

    let startX=0, startW=0, index=-1, moving=false;

    function save(){
      const widths = cols.map(c => c.style.width || (c.getBoundingClientRect().width + 'px'));
      localStorage.setItem(key, JSON.stringify(widths));
    }

    function onMove(e){
      if(!moving) return;
      const dx = e.clientX - startX;
      const newW = Math.max(60, startW + dx); // min 60px
      cols[index].style.width = newW + 'px';
      guide.style.left = (startX + dx) + 'px';
    }

    function onUp(){
      if(!moving) return;
      moving = false;
      document.body.classList.remove('is-resizing');
      guide.style.display = 'none';
      save();
      window.removeEventListener('mousemove', onMove, true);
      window.removeEventListener('mouseup', onUp, true);
    }

    // attach handle on all th except last
    ths.forEach((th,i)=>{
      const handle = th.querySelector('.resize-handle');
      if (!handle || i === ths.length - 1) return;

      handle.addEventListener('mousedown', (e)=>{
        e.preventDefault();
        startX = e.clientX;
        startW = parseFloat((cols[i].style.width || th.getBoundingClientRect().width));
        index  = i;
        moving = true;

        document.body.classList.add('is-resizing');
        guide.style.display = 'block';
        guide.style.left = startX + 'px';

        window.addEventListener('mousemove', onMove, true);
        window.addEventListener('mouseup', onUp, true);
      });
    });
  }

  document.addEventListener('DOMContentLoaded', ()=>{
    document.querySelectorAll('.js-resize-table').forEach(initResizableTable);
  });
})();
</script>

@endsection