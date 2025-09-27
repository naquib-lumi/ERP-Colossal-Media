@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
  .page-wrap{max-width:1180px;margin:0 auto}
  .card.soft{border:0;box-shadow:0 3px 10px rgba(16,24,40,.06);border-radius:14px}
  .section-hd{display:flex;align-items:center;gap:8px;font-weight:700;color:#101828;margin-bottom:12px}
  .section-hd .bi{color:#667085}

  /* 顶部信息 */
  .dl{display:grid;grid-template-columns:180px 1fr;row-gap:10px;column-gap:16px}
  .dl dt{font-size:12px;color:#667085;line-height:1.2}
  .dl dd{margin:0;color:#101828}
  .dl .muted{color:#475467}

  /* remark chips */
  .chips{display:flex;gap:8px;flex-wrap:nowrap;overflow:auto hidden;padding-bottom:2px}
  .chip{white-space:nowrap;border-radius:999px;background:#F2F4F7;color:#344054;font-size:12px;padding:6px 10px}
  .chips::-webkit-scrollbar{height:6px}
  .chips::-webkit-scrollbar-thumb{background:#E5E7EB;border-radius:999px}

  /* 表格 */
  .table-products thead th{font-size:12px;color:#475467;font-weight:700;position:sticky;top:0;background:#F8FAFC;z-index:1;white-space:nowrap}
  .table-products> :not(caption)>*>*{padding:10px 12px;vertical-align:middle}
  .table-products tbody tr:nth-child(odd){background:#FCFCFD}
  .td-tight{padding-top:12px !important;padding-bottom:12px !important}

  .col-item{min-width:180px}
  .col-qty{width:90px}
  .col-size{width:110px}
  .col-bleed{width:90px}
  .col-material{width:120px}
  .col-centre{width:100px}
  .col-lam{width:140px}
  .col-printer{width:160px}
  .col-cutter{width:180px}
  .col-assemble{width:100px}

  /* 徽章 */
  .badge-yes,.badge-no{display:inline-flex;align-items:center;justify-content:center;min-width:34px;height:22px;border-radius:999px;font-size:12px;line-height:1;padding:0 .5rem}
  .badge-yes{background:#ECFDF3;color:#027A48}
  .badge-no{background:#FFF1F3;color:#B42318}

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
  .btn-toggle{border:1px solid #E5E7EB;background:#fff;border-radius:10px;padding:6px 8px;font-size:12px;color:#475467;display:inline-flex;align-items:center;justify-content:center;width:34px;height:28px}
  .btn-toggle .bi{transition:transform .2s ease}
  .btn-toggle.open .bi{transform:rotate(180deg)}

  /* Delivery cards */
  .dlv-card{border:0;box-shadow:0 3px 10px rgba(16,24,40,.06);border-radius:14px}
  .dlv-hd{display:flex;align-items:center;gap:8px;font-weight:700;color:#101828}
  .dlv-hd .bi{color:#667085}
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
  .badge-delivery{background:#EEF2FF;color:#3730A3}
  .badge-courier{background:#ECFEFF;color:#155E75}
  .badge-pickup{background:#F0FDF4;color:#166534}
  .field .label{font-size:12px;color:#98A2B3;margin-bottom:2px}
  .field .value{color:#111827}
  .field .value-strong{font-weight:700}

  /* 附件 */
  .file-row{display:flex;align-items:center;justify-content:space-between;border:1px solid #E5E7EB;border-radius:10px;padding:12px 16px;background:#fff}
  .file-row+.file-row{margin-top:10px}
  .file-meta{display:flex;align-items:center;gap:12px}
  .file-meta .bi{font-size:20px;color:#667085}
  .file-name{color:#101828;font-weight:500}
  .file-size{color:#98A2B3;font-size:12px}

  /* 标签 chips */
  .assignee-chip{display:inline-flex;align-items:center;padding:6px 12px;border-radius:999px;font-weight:600;font-size:12px;line-height:1;border:1px solid transparent}
  .assignee-chip--blue{background:#EEF2FF;color:#3730A3;border-color:#E0E7FF}
  .uploader-chip{background:#EEF2FF;color:#3730A3;border:1px solid #E0E7FF;border-radius:999px;padding:6px 12px;font-weight:600;font-size:12px}

  /* === 编辑态显隐（必须先 Accept，Edit 才显示备注） === */
  .edit-only{display:none !important;}
  .view-only{display:flex !important;}
  .is-editing .edit-only{display:flex !important;}
  .is-editing .view-only{display:none !important;}

  /* 仅在编辑时高亮 Cutter 列 */
  .is-editing .td-cutter{background:#FFFBEB}
  .form-select-sm,.form-control-sm{min-height:34px}
  .remark-row .bi-trash{font-size:16px}

  /* Actionbar 三状态 */
  .actionbar .action-pre,.actionbar .action-post,.actionbar .action-edit{display:none !important;}
  .actionbar .action-pre{display:flex !important;}                 /* 初始：未接受 */
  .is-accepted .actionbar .action-pre{display:none !important;}
  .is-accepted .actionbar .action-post{display:flex !important;}   /* 已接受：Edit/Back */
  .is-editing .actionbar .action-post{display:none !important;}
  .is-editing .actionbar .action-edit{display:flex !important;}    /* 编辑：Save/Cancel */

/* Attachments 头部：标题 + 上传者标签 */
.attach-head{
  display:flex;
  align-items:center;
  gap:8px;
  margin-bottom:12px;        /* 让标题与列表有合适间距 */
}
.attach-head .uploader-chip{
  margin-left:auto;
  padding:6px 14px;
  font-size:12px;
  line-height:1;
  border-radius:999px;
  background:#EEF2FF;
  color:#3730A3;
  border:1px solid #E0E7FF;
  display:inline-flex;
  /* 只剩文字，去掉 gap */
  gap:0;
}
/* 固定布局 + 统一行高/内边距 */
.table-products{
  table-layout: fixed;
  width: 100%;
}
.table-products thead th,
.table-products td{
  padding: 14px 12px !important;
  line-height: 1.5;
  vertical-align: top;
  white-space: nowrap;       /* 默认单行 */
  overflow: hidden;
  text-overflow: ellipsis;   /* 默认超出省略 */
}

/* 让表格能换行，而不是省略号 */
.table-products { table-layout: fixed; width: 100%; }
.table-products thead th,
.table-products td{
  white-space: normal;      /* 默认允许换行 */
  overflow: visible;        /* 不裁切 */
  text-overflow: clip;      /* 不显示 … */
  line-height: 1.5;
  padding: 14px 12px !important;
  word-break: break-word;   /* 长词也能断行 */
  hyphens: auto;
}

/* 紧凑的短数字列保持单行，避免行高被拉高 */
.table-products .col-qty,
.table-products .col-bleed,
.table-products .col-centre,
.table-products .col-assemble{
  white-space: nowrap;      /* 这些列不换行 */
  text-align: center;
}

/* 列宽再微调一下：把空间让给容易换行的几列 */
.table-products .col-item{     width:18%; }  /* ITEM 稍短 */
.table-products .col-qty{      width:6%;  }
.table-products .col-size{     width:11%; }  /* 尺寸加宽，避免挤在一起 */
.table-products .col-bleed{    width:6%;  }
.table-products .col-material{ width:11%; }
.table-products .col-centre{   width:7%;  }
.table-products .col-lam{      width:13%; }  /* 这几列可换行 */
.table-products .col-printer{  width:11%; }
.table-products .col-cutter{   width:10%; }
.table-products .col-assemble{ width:7%;  }

/* 可选：尺寸数字看齐一点点 */
.table-products .col-size{ font-variant-numeric: tabular-nums; }
/* === Printing/Furnishing 产品表：更均衡的列宽 + 适中间距 + 无横向滚动 === */

/* 1) 取消 .table-responsive 的横向滚动条（仅限这块表格） */
.subcard-body .table-responsive{
  overflow-x: visible;   /* 或者 clip；避免出现滚动条 */
}

/* 2) 固定列宽配比，整体 <= 100%，不会挤，也不会太松 */
.table-products{ table-layout: fixed; width:100%; }

/* 单元格间距：12px 垂直 + 10px 水平，适中不拥挤 */
.table-products> :not(caption)>*>*{
  padding:12px 10px !important;
  line-height:1.5;
  white-space: normal;    /* 允许自动换行 */
  overflow: visible;
  text-overflow: clip;
  word-break: break-word; /* 长词也能断行 */
  hyphens: auto;
}

/* 3) 数字/短文本列保持单行更紧凑 */
.table-products .col-qty,
.table-products .col-centre,
.table-products .col-assemble{
  white-space: nowrap;
  text-align:center;
}

/* —— 给长字段更多空间；保证总计=100% —— */
.table-products .col-item{     width:15%}  /* 产品名多行，看齐左侧 */
.table-products .col-qty{      width:9% }
.table-products .col-size{     width:11% }
.table-products .col-bleed{    width:9% }
.table-products .col-material{ width:10%}
.table-products .col-centre{   width:12% }  /* ← 加宽，标题不再截断 */
.table-products .col-lam{      width:12%}
.table-products .col-printer{  width:11%}
.table-products .col-cutter{   width:10%}
.table-products .col-assemble{ width:11% }


/* 5) 表头不需要换行，防止抖动（文字较长可自行简写） */
.table-products thead th{
  white-space: nowrap;
}


</style>

<div class="container-fluid py-4 px-4">
  <div class="page-wrap" id="pageRoot">
    {{-- 顶部 --}}
    @php
      $jobCreator = trim($jobCreator ?? '') !== '' ? $jobCreator : 'Data Keyin';  // 谁填写 Job Order
    @endphp
    <div class="d-flex align-items-center justify-content-between mb-2">
      <div class="d-flex align-items-center gap-2">
        <a href="javascript:history.back()" class="text-decoration-none text-muted"><i class="bi bi-arrow-left"></i></a>
        <h1 class="h4 fw-bold mb-0">Furnishing Task — <span class="text-muted">ORD005-P1</span></h1>
      </div>
      <span class="assignee-chip assignee-chip--blue">{{ $jobCreator }}</span>
    </div>
    <div class="text-muted mb-3">Furnishing Module</div>

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

    {{-- Product Details --}}
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
              <button class="btn-toggle open" data-toggle="subcard" data-target="p1-body" aria-label="Toggle section">
                <i class="bi bi-caret-down-fill"></i>
              </button>
            </div>
          </div>
          <div class="subcard-body" id="p1-body">
            <div class="table-responsive">
              <table class="table table-products align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th class="col-item">ITEM</th>
                    <th class="col-qty">QUANTITY</th>
                    <th class="col-size">SIZE</th>
                    <th class="col-bleed">BLEED</th>
                    <th class="col-material">MATERIAL</th>
                    <th class="col-centre">PRIME CENTRE</th>
                    <th class="col-lam">LAMINATION</th>
                    <th class="col-printer">PRINTER</th>
                    <th class="col-cutter">CUTTER</th>
                    <th class="col-assemble">ASSEMBLE</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td class="td-tight">Standard Business Card (ENG)</td>
                    <td class="td-tight">500</td>
                    <td class="td-tight">9 × 5.4 cm</td>
                    <td class="td-tight">0.3 cm</td>
                    <td class="td-tight">Art Card</td>
                    <td class="td-tight"><span class="badge-yes">Yes</span></td>
                    <td class="td-tight">Matt UV Lamination</td>
                    <td class="td-tight">Handtop Roll2Roll</td>
                    <td class="td-tight td-cutter">
                      <span class="view-text">Ruijie Flatbed Router</span>
                      <select class="form-select form-select-sm edit-input d-none">
                        <option>Ruijie Flatbed Router</option>
                        <option>Graphtec Cutter</option>
                        <option>Zünd G3 Digital Cutter</option>
                        <option>Laser Cutter</option>
                        <option>Manual Cutting</option>
                      </select>
                    </td>
                    <td class="td-tight"><span class="badge-yes">Yes</span></td>
                  </tr>

                  <tr>
                    <td class="td-tight">Standard Business Card (CN)</td>
                    <td class="td-tight">500</td>
                    <td class="td-tight">9 × 5.4 cm</td>
                    <td class="td-tight">0.3 cm</td>
                    <td class="td-tight">Art Card</td>
                    <td class="td-tight"><span class="badge-yes">Yes</span></td>
                    <td class="td-tight">Matt UV Lamination</td>
                    <td class="td-tight">Handtop Roll2Roll</td>
                    <td class="td-tight td-cutter">
                      <span class="view-text">Ruijie Flatbed Router</span>
                      <select class="form-select form-select-sm edit-input d-none">
                        <option>Ruijie Flatbed Router</option>
                        <option>Graphtec Cutter</option>
                        <option>Zünd G3 Digital Cutter</option>
                        <option>Laser Cutter</option>
                        <option>Manual Cutting</option>
                      </select>
                    </td>
                    <td class="td-tight"><span class="badge-yes">Yes</span></td>
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
              <button class="btn-toggle open" data-toggle="subcard" data-target="p2-body" aria-label="Toggle section">
                <i class="bi bi-caret-down-fill"></i>
              </button>
            </div>
          </div>
          <div class="subcard-body" id="p2-body">
            <div class="table-responsive">
              <table class="table table-products align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th class="col-item">ITEM</th>
                    <th class="col-qty">QUANTITY</th>
                    <th class="col-size">SIZE</th>
                    <th class="col-bleed">BLEED</th>
                    <th class="col-material">MATERIAL</th>
                    <th class="col-centre">PRIME CENTRE</th>
                    <th class="col-lam">LAMINATION</th>
                    <th class="col-printer">PRINTER</th>
                    <th class="col-cutter">CUTTER</th>
                    <th class="col-assemble">ASSEMBLE</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td class="td-tight nowrap">Business&nbsp;Card&nbsp;(ALT)</td>
                    <td class="td-tight">1000</td>
                    <td class="td-tight">9 × 5.4 cm</td>
                    <td class="td-tight">0.3 cm</td>
                    <td class="td-tight">Linen Card</td>
                    <td class="td-tight"><span class="badge-yes">Yes</span></td>
                    <td class="td-tight">Gloss Lamination</td>
                    <td class="td-tight">HP Indigo 7800</td>
                    <td class="td-tight td-cutter">
                      <span class="view-text">Graphtec Cutter</span>
                      <select class="form-select form-select-sm edit-input d-none">
                        <option>Graphtec Cutter</option>
                        <option>Ruijie Flatbed Router</option>
                        <option>Zünd G3 Digital Cutter</option>
                        <option>Laser Cutter</option>
                        <option>Manual Cutting</option>
                      </select>
                    </td>
                    <td class="td-tight"><span class="badge-no">No</span></td>
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

    {{-- Add Remarks（仅编辑出现） --}}
    <div class="card soft mb-4 edit-only">
      <div class="card-body">
        <div class="section-hd"><i class="bi bi-chat-dots"></i> Add Remarks</div>
        <div id="remarks-list" class="d-flex flex-column gap-2">
          <div class="remark-row d-flex align-items-center gap-2">
            <select class="form-select form-select-sm remark-cat" style="max-width:160px">
              <option>Installation</option>
              <option>Printing</option>
              <option>Packing</option>
              <option>General</option>
            </select>
            <input class="form-control form-control-sm remark-text" placeholder="Add your remark..." />
            <button type="button" class="btn btn-link text-muted p-0 remove-remark" title="Remove">
              <i class="bi bi-trash"></i>
            </button>
          </div>
        </div>
        <div class="mt-2">
          <button id="btn-add-remark" type="button" class="btn btn-dark btn-sm">
            <i class="bi bi-plus-lg me-1"></i>Add Remark
          </button>
        </div>
      </div>
    </div>

<div class="card soft mb-4">
  <div class="card-body">

    {{-- 这个头部替换成 attach-head --}}
    <div class="attach-head">
      <div class="section-hd mb-0">
        <i class="bi bi-paperclip"></i> Attachments
      </div>
<span class="uploader-chip">{{ $uploader ?? 'Artist A' }}</span>
    </div>

    {{-- ↓↓↓ 下面保持你原来的文件列表循环不变 ↓↓↓ --}}
    @php $files = $attachments ?? [
      ['name' => 'requirements.pdf', 'size' => '1.2 MB', 'url' => '#'],
      ['name' => 'logo.png',        'size' => '856 KB', 'url' => '#'],
      ['name' => 'design-specs.pdf','size' => '2.4 MB', 'url' => '#'],
    ]; @endphp

    @foreach($files as $f)
      @php
        $n = strtolower($f['name'] ?? '');
        $icon = (str_ends_with($n, '.pdf') ? 'file-earmark-pdf' :
                (preg_match('/\.(png|jpe?g|gif|svg)$/', $n) ? 'file-earmark-image' : 'file-earmark'));
      @endphp
      <div class="file-row">
        <div class="file-meta">
          <i class="bi bi-{{ $icon }}"></i>
          <div>
            <div class="file-name">{{ $f['name'] ?? 'file' }}</div>
            <div class="file-size">{{ $f['size'] ?? '' }}</div>
          </div>
        </div>
        <a class="btn btn-light border btn-sm" href="{{ $f['url'] ?? '#' }}">
          <i class="bi bi-download me-1"></i>Download
        </a>
      </div>
    @endforeach
  </div>
</div>


    {{-- Sticky Actionbar：三种状态 --}}
    <div class="actionbar">
      <!-- 未接受 -->
      <div class="action-pre d-flex justify-content-end gap-2">
        <button type="button" id="btnAccept" class="btn btn-dark btn-pill"><i class="bi bi-check2 me-1"></i>Accept</button>
        <button type="button" id="btnReject" class="btn btn-outline-danger btn-pill"><i class="bi bi-x-lg me-1"></i>Reject</button>
        <a href="javascript:history.back()" class="btn btn-light border btn-pill">Back</a>
      </div>
      <!-- 已接受 -->
      <div class="action-post d-flex justify-content-end gap-2">
        <button type="button" id="btnEdit" class="btn btn-light border btn-pill">Edit</button>
        <a href="javascript:history.back()" class="btn btn-light border btn-pill">Back</a>
      </div>
      <!-- 编辑中 -->
      <div class="action-edit d-flex justify-content-end gap-2">
        <button type="button" id="btnSave" class="btn btn-dark btn-pill"><i class="bi bi-save2 me-1"></i>Save Task</button>
        <button type="button" id="btnCancel" class="btn btn-outline-secondary btn-pill">Cancel</button>
      </div>
    </div>

  </div>
</div>

<script>
  // 折叠
  document.querySelectorAll('[data-toggle="subcard"]').forEach(btn=>{
    const body=document.getElementById(btn.dataset.target);
    btn.addEventListener('click',()=>{ body?.classList.toggle('hidden'); btn.classList.toggle('open'); });
  });

  const root = document.getElementById('pageRoot');
  document.addEventListener('DOMContentLoaded',()=>root?.classList.remove('is-editing')); // 初始非编辑

  // —— 状态流转
  document.getElementById('btnAccept')?.addEventListener('click',()=>{
    root.classList.add('is-accepted');
    root.classList.remove('is-editing');
  });
  document.getElementById('btnReject')?.addEventListener('click',()=>alert('Rejected (demo)'));

  document.getElementById('btnEdit')?.addEventListener('click',()=>{
    root.classList.add('is-editing');
    toggleCutterInputs(true);
  });

  document.getElementById('btnCancel')?.addEventListener('click',()=>{
    toggleCutterInputs(false, /*revert*/ true);
    root.classList.remove('is-editing');
  });

  document.getElementById('btnSave')?.addEventListener('click',()=>{
    toggleCutterInputs(false, /*revert*/ false); // 写回文本
    // TODO: 这里提交保存（Ajax / 表单）
    root.classList.remove('is-editing');
  });

  // 仅 Cutter 可编辑
  function toggleCutterInputs(edit, revert=false){
    document.querySelectorAll('.td-cutter').forEach(td=>{
      const span=td.querySelector('.view-text');
      const sel =td.querySelector('.edit-input');
      if(!span||!sel) return;

      if(edit){
        [...sel.options].forEach(o=>o.selected=(o.text.trim()===span.textContent.trim()));
        sel.classList.remove('d-none'); span.classList.add('d-none');
      }else{
        if(!revert) span.textContent = sel.value;
        sel.classList.add('d-none'); span.classList.remove('d-none');
      }
    });
  }

  // Add Remarks 动态行
  (function(){
    const list=document.getElementById('remarks-list');
    const btn =document.getElementById('btn-add-remark');
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
