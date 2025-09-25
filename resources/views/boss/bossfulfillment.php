@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
:root{
  --bg:#F7F8FA; --card:#FFFFFF; --border:#E5E7EB; --muted:#667085; --text:#101828;
  --shadow:0 2px 12px rgba(16,24,40,.06);
  --primary:#111827; --primary-700:#0C1424;
  --pill:#F1F5F9;
  --blue:#2563EB; --blue-50:#EEF2FF;
  --green:#16A34A; --green-50:#EAF7EE;
  --orange:#F59E0B; --orange-50:#FFF7E8;
  --red:#DC2626; --red-50:#FEECEC;
  --indigo:#6366F1; --indigo-50:#EEF2FF;
}
body{background:var(--bg)}
.page{max-width:1240px;margin:0 auto;padding:16px 0}
.hstack{display:flex;gap:12px;align-items:center}
.card{background:var(--card);border:1px solid var(--border);border-radius:14px;box-shadow:var(--shadow)}
.card.pad{padding:16px}
.kpi{display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:12px}
.kpi .tile{display:flex;gap:12px;align-items:center;padding:14px;border-radius:14px;border:1px solid var(--border);background:#fff}
.kpi .ico{width:36px;height:36px;border-radius:10px;display:grid;place-items:center;background:var(--blue-50);color:var(--blue)}
.kpi .label{font-size:12px;color:var(--muted);line-height:1.3}
.kpi .num{font-weight:700;color:var(--text);font-size:22px;margin-top:2px}

.header-row{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px}
.h-title{font-weight:800;color:var(--text);font-size:22px}

.filters{display:grid;grid-template-columns: 1fr 180px 180px auto;gap:10px}
.input, .select{height:42px;border:1px solid var(--border);border-radius:10px;padding:0 12px;background:#fff;color:var(--text)}
.input:focus,.select:focus{outline:0;border-color:#CBD5E1;box-shadow:0 0 0 3px rgba(37,99,235,.08)}
.btn{height:42px;border-radius:10px;padding:0 16px;display:inline-flex;align-items:center;gap:8px;border:1px solid transparent}
.btn-dark{background:var(--primary);color:#fff}
.btn-dark:hover{background:var(--primary-700)}

.table-wrap{margin-top:12px}
table{width:100%;border-collapse:separate;border-spacing:0}
thead th{background:#F8FAFC;color:#475467;font-weight:700;font-size:13px;padding:12px 14px;border-top:1px solid var(--border)}
tbody td{padding:14px;border-top:1px solid var(--border);font-size:14px;color:#1F2937;background:#fff}
tbody tr:first-child td{border-top:1px solid var(--border)}
.badge{padding:4px 10px;border-radius:999px;font-size:12px;font-weight:600;display:inline-flex;align-items:center;gap:6px;border:1px solid transparent}
.badge.gray{background:#F1F5F9;color:#334155;border-color:#E2E8F0}
.badge.blue{background:var(--blue-50);color:var(--blue);border-color:#CFD7FF}
.badge.green{background:var(--green-50);color:var(--green);border-color:#BEE9CE}
.badge.orange{background:var(--orange-50);color:#C77705;border-color:#FFE2B8}
.badge.red{background:var(--red-50);color:var(--red);border-color:#F9C4C4}
.icon-btn{width:36px;height:36px;border:1px solid var(--border);border-radius:10px;background:#fff;display:grid;place-items:center;color:#475467}
.icon-btn:hover{background:#F8FAFC}

.pager{display:flex;justify-content:flex-end;gap:8px;margin-top:12px}
.pill{min-width:36px;height:36px;border-radius:999px;border:1px solid var(--border);display:grid;place-items:center;background:#fff;color:#111827;font-weight:600}
.pill.active{background:#3B82F6;color:#fff;border-color:#3B82F6}

/* 小屏 */
@media (max-width: 992px){
  .kpi{grid-template-columns:1fr 1fr}
  .filters{grid-template-columns:1fr 1fr}
}
</style>

<div class="page">
  <!-- 标题 -->
  <div class="card pad" style="margin-bottom:14px">
    <div class="hstack" style="gap:8px">
      <i class="bi bi-truck" style="color:#3B82F6"></i>
      <div class="h-title">Fulfillment Overview</div>
    </div>
  </div>

  <!-- 统计卡 -->
  <div class="kpi" style="margin-bottom:14px">
    <div class="tile">
      <div class="ico"><i class="bi bi-upload"></i></div>
      <div>
        <div class="label">Installation Needing Permit Upload</div>
        <div class="num">12</div>
      </div>
    </div>
    <div class="tile">
      <div class="ico" style="background:#E8F5FF;color:#0EA5E9"><i class="bi bi-printer"></i></div>
      <div>
        <div class="label">Printing Tasks in Progress</div>
        <div class="num">7</div>
      </div>
    </div>
    <div class="tile">
      <div class="ico" style="background:#F1EEFF;color:#7C3AED"><i class="bi bi-lamp"></i></div>
      <div>
        <div class="label">Furnishing Tasks in Progress</div>
        <div class="num">5</div>
      </div>
    </div>
    <div class="tile">
      <div class="ico" style="background:#FFF3E8;color:#F59E0B"><i class="bi bi-box-seam"></i></div>
      <div>
        <div class="label">Dispatch Control in Progress</div>
        <div class="num">8</div>
      </div>
    </div>
  </div>

  <!-- 筛选条 -->
  <div class="card pad">
    <div class="filters">
      <div class="input hstack" style="gap:10px">
        <i class="bi bi-search text-muted"></i>
        <input class="flex-grow-1" style="border:0;outline:0;height:38px" type="text" placeholder="Search by Order ID or Job Title">
      </div>

      <select class="select">
        <option>All Task Types</option>
        <option>Printing</option>
        <option>Furnishing</option>
        <option>Installation</option>
        <option>Courier</option>
        <option>Self Pickup</option>
      </select>

      <select class="select">
        <option>All Statuses</option>
        <option>Pending</option>
        <option>In Progress</option>
        <option>Completed</option>
      </select>

      <button class="btn btn-dark"><i class="bi bi-download"></i> Export CSV</button>
    </div>

    <!-- 表格 -->
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th align="left">PRODUCT ID</th>
            <th align="left">PRODUCT NAME</th>
            <th align="left">TASK TYPE</th>
            <th align="left">DEADLINE</th>
            <th align="left">STATUS</th>
            <th align="left">DELIVERY DATE</th>
            <th align="left">DELIVERY LOCATION</th>
            <th align="left">INSTALLATION TYPE</th>
            <th align="left">COST</th>
            <th align="right">ACTIONS</th>
          </tr>
        </thead>
        <tbody>
          @php
            $rows = [
              ['#ORD005-P1','Business Cards','Delivery & Installation','2025-07-25',['In Progress','blue'],'2025-07-25','TechCorp HQ, KL',['Outsource','blue'],'RM 450'],
              ['#ORD005-P1','Brochures','Delivery & Installation','2025-07-26',['Pending','orange'],'—','—',['Inhouse','green'],'N/A'],
              ['#ORD007-P1','Posters','Delivery & Installation','2025-07-27',['In Progress','blue'],'2025-07-27','Printing Lab A',['Outsource','blue'],'RM 450'],
              ['#ORD009-P3','Flyers','Furnishing','2025-07-28',['In Progress','blue'],'2025-07-28','Furnishing Dept, HQ',['N/A','gray'],'N/A'],
              ['#ORD010-P1','Signboards','Self Pickup','2025-07-29',['Completed','green'],'2025-07-29','Not required for pickup',['N/A','gray'],'N/A'],
              ['#ORD011-P2','Catalogues','Courier','2025-07-30',['In Progress','blue'],'2025-07-30','12 Business Ave, State 77890',['N/A','gray'],'N/A'],
              ['#ORD012-P1','Banners','Printing','2025-07-31',['Pending','orange'],'—','Printing Lab B',['N/A','gray'],'N/A'],
              ['#ORD013-P1','Exhibition Kit','Furnishing','2025-08-01',['Completed','green'],'2025-08-01','Furnishing Workshop, KLCC',['N/A','gray'],'N/A'],
              ['#ORD014-P3','Labels','Courier','2025-08-02',['Pending','orange'],'—','—',['N/A','gray'],'N/A'],
              ['#ORD015-P1','Catalogues','Self Pickup','2025-08-03',['In Progress','blue'],'—','Not required for pickup',['N/A','gray'],'N/A'],
              ['#ORD016-P2','Posters','Printing','2025-08-04',['In Progress','blue'],'—','Printing Lab A',['N/A','gray'],'N/A'],
            ];
          @endphp

          @foreach($rows as $r)
          <tr>
            <td>{{ $r[0] }}</td>
            <td>{{ $r[1] }}</td>
            <td><span class="badge gray">{{ $r[2] }}</span></td>
            <td>{{ $r[3] }}</td>
            <td>
              @php [$label,$color]=$r[4]; @endphp
              <span class="badge {{ $color }}">{{ $label }}</span>
            </td>
            <td>{{ $r[5] }}</td>
            <td>{{ $r[6] }}</td>
            <td>
              @php [$inst,$icolor]=$r[7]; @endphp
              <span class="badge {{ $icolor }}">{{ $inst }}</span>
            </td>
            <td>{{ $r[8] }}</td>
            <td align="right" class="hstack" style="justify-content:flex-end;gap:8px">
              <button class="icon-btn" title="View"><i class="bi bi-eye"></i></button>
              <button class="icon-btn" title="Edit"><i class="bi bi-pencil"></i></button>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>

      <!-- 分页 -->
      <div class="pager">
        <button class="pill">Previous</button>
        <button class="pill active">1</button>
        <button class="pill">2</button>
        <button class="pill">3</button>
        <button class="pill">Next</button>
      </div>
    </div>
  </div>
</div>
@endsection
