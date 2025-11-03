@extends('layouts.app')

@section('title', 'Lead Management')
@section('content')

<style>
  .swal2-popup.z-index-1060{ z-index:1060 !important; }

  /* ===== Compact Buttons (小个子按钮) ===== */
  .btn-compact{
    --h:34px; --px:14px; --radius:10px;
    height:var(--h); padding:0 var(--px);
    line-height:calc(var(--h) - 2px);
    font-size:.875rem; border-radius:var(--radius);
    display:inline-flex; align-items:center; gap:.5rem;
    box-shadow:none; transition:box-shadow .15s, background .15s, color .15s, border-color .15s;
  }
  .btn-compact i{ font-size:1rem; line-height:1; }
  .btn-dark-ink{ background:#1E2235; color:#fff; border:0; }
  .btn-dark-ink:hover{ background:#191c2d; color:#fff; box-shadow:0 2px 6px rgba(0,0,0,.12); }
  .btn-outline-slate{ background:#fff; color:#6B7280; border:1px solid #D1D5DB; }
  .btn-outline-slate:hover{ background:#F8FAFC; color:#4B5563; border-color:#CBD5E1; }
  .btn-soft-primary{ background:#E9ECEF; color:#4F46E5; border:0; }
  .btn-soft-primary:hover{ background:#E5E7EB; color:#4338CA; }

  /* ===== DataTables 底部条（info + 分页） ===== */
  .dt-bottom{
    padding:12px 16px; background:#fff; border-top:1px solid #EEF2F6;
    border-bottom-left-radius:12px; border-bottom-right-radius:12px;
  }
  .dataTables_wrapper .dataTables_info{
    margin:0 !important; color:#6B7280; font-size:14px;
  }
  .dataTables_wrapper .dataTables_paginate .pagination{ margin:0; gap:8px; }
  .dataTables_wrapper .dataTables_paginate .page-item .page-link{
    min-width:36px; height:32px; padding:0 10px; border:0; border-radius:10px;
    display:inline-flex; align-items:center; justify-content:center;
    font-weight:600; font-size:14px; color:#6B7280; background:#F1F3F5;
    transition:background .15s, color .15s, box-shadow .15s;
  }
  .dataTables_wrapper .dataTables_paginate .page-item .page-link:hover{ background:#E9ECEF; }
  .dataTables_wrapper .dataTables_paginate .page-item.active .page-link{
    background:#6D6AFE; color:#fff; box-shadow:0 4px 12px rgba(109,106,254,.25);
  }
  .dataTables_wrapper .dataTables_paginate .page-item.disabled .page-link{
    background:#F3F4F6; color:#CBD5E1; box-shadow:none;
  }
</style>

<div class="container-xxl flex-grow-1 container-p-y">
  <div class="card">
    <h5 class="card-header bg-white text-dark pb-2 pt-2 text-md-start">Lead Management</h5>

    <div class="card-datatable table-responsive p-3">
      <!-- Filters -->
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div class="w-100 border rounded-3 px-3 py-3">
          <form id="leadsFilterForm" method="GET" action="{{ route('sales.leads') }}">
            <div class="row g-2 align-items-center">
              {{-- Global search --}}
              <div class="col-12 col-lg-3">
                <div class="input-group">
                  <span class="input-group-text bg-white"><i class="bx bx-search"></i></span>
                  <input id="globalSearch" type="text" name="q" class="form-control"
                         placeholder="Search leads by ID, Company, or Name..." value="{{ request('q') }}">
                </div>
              </div>

              @if(Auth::user()->hasRole('head-salesperson'))
              {{-- Salesperson filter --}}
              <div class="col-12 col-lg-3">
                <div class="input-group">
                  <span class="input-group-text bg-white"><i class="bx bx-user"></i></span>
                  <select id="salespersonFilter" name="salesperson_id" class="form-control">
                    <option value="">All Salespersons</option>
                    @foreach($salespeople as $salesperson)
                      <option value="{{ $salesperson->id }}" {{ request('salesperson_id') == $salesperson->id ? 'selected' : '' }}>
                        {{ $salesperson->name }}
                      </option>
                    @endforeach
                  </select>
                </div>
              </div>
              @endif

              {{-- Status --}}
              <div class="col-12 col-lg-2">
                <div class="input-group">
                  <span class="input-group-text bg-white"><i class="bx bx-filter-alt"></i></span>
                  <select id="statusFilter" name="status" class="form-control">
                    <option value="">All Status</option>
                    <option value="accept"   {{ request('status')=='accept'?'selected':'' }}>Accept</option>
                    <option value="reject"   {{ request('status')=='reject'?'selected':'' }}>Reject</option>
                    <option value="followup" {{ request('status')=='followup'?'selected':'' }}>Followup</option>
                    <option value="new"      {{ request('status')=='new'?'selected':'' }}>New</option>
                  </select>
                </div>
              </div>

              {{-- Date from --}}
              <div class="col-6 col-lg-2">
                <div class="input-group">
                  <span class="input-group-text bg-white"><i class="bx bx-calendar"></i></span>
                  <input type="date" id="fromDate" name="from_date" class="form-control" value="{{ request('from_date') }}">
                </div>
              </div>

              {{-- Date to --}}
              <div class="col-6 col-lg-2">
                <div class="input-group">
                  <span class="input-group-text bg-white"><i class="bx bx-calendar"></i></span>
                  <input type="date" id="toDate" name="to_date" class="form-control" value="{{ request('to_date') }}">
                </div>
              </div>

              {{-- Actions（小个按钮） --}}
              <div class="col-12 col-lg d-flex gap-2 justify-content-lg-end">
                <button type="button" class="btn-compact btn-dark-ink" onclick="exportCsvWithFilters()">
                  <i class="bx bx-export"></i> Export
                </button>
                <a href="{{ route('sales.leads') }}" class="btn-compact btn-outline-slate">
                  Reset
                </a>
                <a href="{{ route('leads.create') }}" class="btn-compact btn-soft-primary">
                  Add Lead
                </a>
              </div>
            </div>
          </form>
        </div>
      </div>

      <table class="datatables-ajax table table-striped table-hover" id="leadTable" style="width:100%;">
        <thead class="table-light sticky-top">
          <tr>
            <th class="text-center align-middle" style="width:15%;">Lead Data</th>
            <th class="text-center align-middle" style="width:20%;">Company Details</th>
            <th class="text-center align-middle" style="width:20%;">Lead Details</th>
            <th class="text-center align-middle" style="width:15%;">Assigned Salesperson</th>
            <th class="text-center align-middle" style="width:15%;">Reminder</th>
            <th class="text-center align-middle" style="width:15%;">Actions</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>

  <!-- Attachments Modal -->
  <div class="modal fade" id="attachmentModal" tabindex="-1" aria-labelledby="attachmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header bg-info text-white">
          <h5 class="modal-title" id="attachmentModalLabel">Attachments</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4" id="attachmentBody"></div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Reminder Confirm Modal -->
  <div class="modal fade" id="reminderConfirmModal" tabindex="-1" aria-labelledby="reminderConfirmLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="reminderConfirmLabel">Confirm Reminder Completion</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p id="reminderConfirmText"></p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
          <button type="button" class="btn btn-primary" id="confirmReminderDoneBtn">Yes</button>
        </div>
      </div>
    </div>
  </div>

  <hr class="my-12"/>
</div>

<script>
  // jQuery fallback
  window.jQuery || document.write('<script src="https://code.jquery.com/jquery-3.6.0.min.js"><\/script>');

  $(function(){
    let table = $('#leadTable').DataTable({
      processing:true,
      serverSide:true,
      ajax:{
        url:'{{ route('leads.get') }}',
        type:'POST',
        data:function(d){
          d._token      = $('meta[name="csrf-token"]').attr('content');
          d.search      = { value: $('#globalSearch').val() };
          d.status      = $('#statusFilter').val();
          d.from_date   = $('#fromDate').val();
          d.to_date     = $('#toDate').val();
          if($('#salespersonFilter').length){
            d.salesperson_id = $('#salespersonFilter').val();
          }
          return d;
        }
      },
      columns:[
        { data:'lead_data',            orderable:true  },
        { data:'company_details',      orderable:false },
        { data:'lead_details',         orderable:false },
        { data:'assigned_salesperson', orderable:true  },
        { data:'reminder',             orderable:true  },
        { data:'actions',              orderable:false, searchable:false }
      ],

      /* === 布局：底部一行包含 info + 分页 === */
      dom: 'rt<"dt-bottom d-flex justify-content-between align-items-center mt-2"i p>',

      /* === 分页类型 & 语言（首末页 « »） === */
      pagingType: 'full_numbers',
      language:{
        paginate:{ first:'«', last:'»', previous:'‹', next:'›' },
        info:'Showing _START_ to _END_ of _TOTAL_ leads'
      },

      order:[[0,'desc']],
      initComplete:function(){
        $('#leadsFilterForm').on('submit', function(e){ e.preventDefault(); table.draw(); });
        $('#statusFilter, #fromDate, #toDate, #salespersonFilter').on('change', function(){ table.draw(); });
        $('#globalSearch').on('keyup', function(){ table.search(this.value).draw(); });
      },
      drawCallback:function(){
        $('.dataTables_paginate .pagination').addClass('pagination-sm');
      }
    });

    // 双击行进入查看
    $('#leadTable').on('dblclick', 'tbody tr', function(e){
      if($(e.target).closest('select, button, a, i').length) return;
      const leadId = $(this).find('.lead-id').text();
      window.location.href = `/leads/${leadId}/view`;
    });

    // —— 以下保留你原有的事件绑定（状态/机会/指派/提醒/附件/行内表单） —— //
    $('#leadTable').on('change', '.status-dropdown', function(e){
      e.stopPropagation();
      let id=$(this).data('id'), status=$(this).val();
      $.post('{{ route('leads.update.status', ['id' => ':id']) }}'.replace(':id', id),
        { _token:$('meta[name="csrf-token"]').attr('content'), status },
        function(resp){ resp.redirect ? location.href=resp.redirect : table.ajax.reload(null,false); }
      ).fail(xhr=>alert('Error updating status: '+xhr.responseText));
    });

    $('#leadTable').on('change', '.opportunity-dropdown', function(e){
      e.stopPropagation();
      let id=$(this).data('id'), opportunity=$(this).val();
      $.post('{{ route('leads.update.opportunity', ['id' => ':id']) }}'.replace(':id', id),
        { _token:$('meta[name="csrf-token"]').attr('content'), opportunity },
        function(){ table.ajax.reload(null,false); }
      ).fail(xhr=>alert('Error updating opportunity: '+xhr.responseText));
    });

    $('#leadTable').on('change', '.assign-dropdown', function(e){
      e.stopPropagation();
      let id=$(this).data('id'), salespersonId=$(this).val();
      if(salespersonId){
        $.post('{{ route('leads.update.salesperson', ['id' => ':id']) }}'.replace(':id', id),
          { _token:$('meta[name="csrf-token"]').attr('content'), salesperson_id:salespersonId },
          function(){ table.ajax.reload(null,false); }
        ).fail(xhr=>alert('Error reassigning lead: '+xhr.responseText));
      }
    });

    $(document).on('click', '.confirm-reminder', function(e){
      e.stopPropagation(); e.preventDefault();
      let leadId=$(this).data('id'), reminderId=$(this).data('reminder-id'), title=$(this).data('title');
      $('#reminderConfirmText').text(`Is the reminder "${title}" done?`);
      $('#reminderConfirmModal').data('lead-id',leadId).data('reminder-id',reminderId).modal('show');
    });

    $(document).on('click', '#confirmReminderDoneBtn', function(){
      let leadId=$('#reminderConfirmModal').data('lead-id'), reminderId=$('#reminderConfirmModal').data('reminder-id');
      $.post('{{ route('leads.confirm.reminder.status', ['id' => ':leadId', 'reminderId' => ':reminderId']) }}'.replace(':leadId',leadId).replace(':reminderId',reminderId),
        { _token:$('meta[name="csrf-token"]').attr('content'), confirm:'yes' },
        function(){
          $('#reminderConfirmModal').modal('hide');
          let $link=$(`a.confirm-reminder[data-reminder-id="${reminderId}"]`);
          if($link.length){
            let $li=$link.closest('li');
            $li.addClass('text-secondary text-decoration-line-through').fadeOut(2500,function(){
              $li.remove();
              let $ul=$li.parent('ul');
              if($ul.children('li').length===0){ $ul.replaceWith('<span class="text-muted">No Reminders</span>'); }
            });
          }
        }
      ).fail(xhr=>{
        $('#reminderConfirmModal').modal('hide');
        let errorMsg='Error confirming reminder';
        if(xhr.status===403){
          try{ const r=JSON.parse(xhr.responseText); if(r.error==='Unauthorized'){ errorMsg='Only the assigned salesperson can confirm this reminder.'; } }catch(e){}
        }else{ errorMsg += ': ' + (xhr.responseText || 'Unknown error'); }
        Swal.fire({ icon:'error', title:'Error!', text:errorMsg, confirmButtonText:'OK', allowOutsideClick:true, allowEscapeKey:true });
      });
    });

    $('#leadTable').on('click', '.view-attachments', function(e){
      e.stopPropagation();
      let id=$(this).data('id');
      $.get('{{ route('leads.attachments', ['id' => ':id']) }}'.replace(':id', id), function(resp){
        $('#attachmentBody').html(resp); $('#attachmentModal').modal('show');
      }).fail(xhr=>alert('Error loading attachments: '+xhr.responseText));
    });

    $('#attachmentModal').on('shown.bs.modal', function(){
      $('#attachmentBody').on('click', '.delete-attachment', function(e){
        e.preventDefault();
        if(confirm('Are you sure you want to delete this attachment?')){
          $.ajax({
            url: $(this).attr('href'), type:'DELETE', data:{ _token:'{{ csrf_token() }}' }
          }).done(function(){
            $(e.target).closest('tr').remove();
            if($('#attachmentBody tbody tr').length===0){ $('#attachmentModal').modal('hide'); }
          }).fail(xhr=>alert('Error deleting attachment: '+xhr.responseText));
        }
      });
    });

    $('#leadTable').on('submit', 'form', function(e){
      e.preventDefault();
      $.post($(this).attr('action'), $(this).serialize())
       .done(function(resp){ if(resp.message){ alert(resp.message); } table.ajax.reload(null,false); })
       .fail(function(xhr){
         if(xhr.status===403){ alert('Unauthorized: You can only delete your own leads.'); }
         else{ alert('Error deleting lead: ' + xhr.responseText); }
       });
    });
  });

  // 导出（你的原逻辑）
  function exportCsvWithFilters(){
    var params = new URLSearchParams();
    params.append('search[value]', $('#globalSearch').val());
    params.append('status',        $('#statusFilter').val());
    params.append('from_date',     $('#fromDate').val());
    params.append('to_date',       $('#toDate').val());
    if($('#salespersonFilter').length){ params.append('salesperson_id', $('#salespersonFilter').val()); }
    window.location.href = '{{ route('leads.export-csv') }}?' + params.toString();
  }
</script>
@endsection
