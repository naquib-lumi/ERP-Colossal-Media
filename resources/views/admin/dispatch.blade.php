@extends('layouts.app')
@section('title','Dispatch Control Overview')

@section('content')
<link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
<style>
.page{max-width:1200px;margin:auto;padding:1.5rem;}
.h1{font-weight:800;font-size:28px;margin-bottom:1rem;}
.toolbar{background:#fff;border:1px solid #E5E7EB;border-radius:16px;padding:14px;display:flex;gap:10px;flex-wrap:wrap;margin-bottom:14px;}
.toolbar input,.toolbar select{height:42px;border:1px solid #E5E7EB;border-radius:10px;padding:0 12px;background:#fff;outline:0}
.toolbar .btn{height:42px;border-radius:10px;border:1px solid #E5E7EB;background:#fff;padding:0 16px;font-weight:700}
.table-wrap{background:#fff;border:1px solid #E5E7EB;border-radius:16px;box-shadow:0 4px 10px rgba(0,0,0,0.03);}
.table{width:100%;border-collapse:collapse}
.table th{background:#F9FAFB;text-align:left;font-size:13px;text-transform:uppercase;padding:12px;border-bottom:1px solid #E5E7EB;color:#64748B;}
.table td{padding:14px;border-bottom:1px solid #E5E7EB;color:#111827;}
.pill{display:inline-block;padding:.2rem .55rem;border-radius:999px;background:#EEF2FF;color:#4F46E5;font-size:.75rem;font-weight:700}
.badge{display:inline-block;padding:.2rem .55rem;border-radius:999px;background:#E0F2FE;color:#075985;font-size:.75rem;font-weight:700}
.badge.green{background:#ECFDF5;color:#047857}
.action i{font-size:18px;margin:0 6px;color:#64748B}
.action i:hover{color:#111827}
</style>

<div class="page">
  <div class="h1">Dispatch Control Overview</div>

  <div class="toolbar">
    <input type="text" placeholder="Search by Order ID or Job Title" style="flex:1;min-width:260px;">
    <select>
      <option>All Task Types</option><option>Self Pick up</option><option>Courier</option>
    </select>
    <select>
      <option>All Statuses</option><option>In Progress</option><option>Completed</option>
    </select>
    <a href="{{ route('admin.dispatch.export') }}" class="btn">
      <i class='bx bx-export'></i> Export CSV
    </a>
  </div>

  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Product ID</th>
          <th>Product Name</th>
          <th>Task Type</th>
          <th>Deadline</th>
          <th>Status</th>
          <th style="width:140px;">Action</th>
        </tr>
      </thead>
      <tbody>
        @foreach($tasks as $r)
        <tr>
          <td>{{ $r['id'] }}</td>
          <td>{{ $r['name'] }}</td>
          <td><span class="pill">{{ $r['type'] }}</span></td>
          <td>{{ $r['deadline'] }}</td>
          <td>
            @if($r['status']==='Completed')
              <span class="badge green">Completed</span>
            @else
              <span class="badge">In Progress</span>
            @endif
          </td>
          <td class="action">
            <i class='bx bx-show-alt'  title="View"></i>
            <i class='bx bx-link-external' title="Open"></i>
            <i class='bx bx-check'       title="Confirm"></i>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endsection
