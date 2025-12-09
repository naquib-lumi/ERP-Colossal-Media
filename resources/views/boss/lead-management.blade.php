@extends('layouts.app')

@section('title', 'Lead Management')
@section('content')
<style>
  .swal2-popup.z-index-1060{ z-index:1060 !important; }

  /* ===== 仅美化底部信息 + 分页（作用于本表 wrapper） ===== */
  #leadTable_wrapper .dt-footer{
    padding:12px 16px; background:#fff; border-top:1px solid #EEF2F6;
    border-bottom-left-radius:12px; border-bottom-right-radius:12px;
  }
  #leadTable_wrapper .dt-info{
    color:#9AA3AE; font-size:14px; margin:0;
  }
  #leadTable_wrapper .dt-paging{
    display:flex; align-items:center; gap:8px; margin:0;
  }
  #leadTable_wrapper .dt-paging .dt-paging-button{
    min-width:36px; height:32px; padding:0 10px;
    border:0; border-radius:10px;
    display:inline-flex; align-items:center; justify-content:center;
    font-weight:600; font-size:14px;
    color:#6B7280; background:#F1F3F5;
    transition:background .15s, color .15s, box-shadow .15s;
    cursor:pointer;
  }
  #leadTable_wrapper .dt-paging .dt-paging-button:hover{
    background:#E9ECEF;
  }
  #leadTable_wrapper .dt-paging .dt-paging-button.current,
  #leadTable_wrapper .dt-paging .dt-paging-button.current:hover{
    background:#6D6AFE; color:#fff;
    box-shadow:0 4px 12px rgba(109,106,254,.25);
  }
  #leadTable_wrapper .dt-paging .dt-paging-button.disabled,
  #leadTable_wrapper .dt-paging .dt-paging-button.disabled:hover{
    background:#F3F4F6; color:#CBD5E1; box-shadow:none; cursor:not-allowed;
  }
  #leadTable_wrapper .dt-paging .ellipsis{
    padding:0 6px; color:#9AA3AE; background:transparent; box-shadow:none;
  }
</style>

<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Lead Management Table -->
  <div class="card">
    <h5 class="card-header bg-white text-dark pb-2 pt-2 text-md-start">Lead Management</h5>

    <div class="card-datatable table-responsive p-3">
      <!-- Filters -->
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div class="w-100 border rounded-3 px-3 py-3">
          <form id="leadsFilterForm" method="GET" action="{{ route('boss.leads') }}">
            <div class="row g-2 align-items-center">
              {{-- Global search --}}
              <div class="col-12 col-lg-3">
                <div class="input-group">
                  <span class="input-group-text bg-white"><i class="bx bx-search"></i></span>
                  <input id="globalSearch" type="text" name="q" class="form-control" placeholder="Search leads by ID, Company, or Name..." value="{{ request('q') }}">
                </div>
              </div>

              @if(Auth::user()->hasRole('boss'))
              {{-- Salesperson filter --}}
              <div class="col-12 col-lg-3">
                <div class="input-group">
                  <span class="input-group-text bg-white"><i class="bx bx-user"></i></span>
                  <select id="salespersonFilter" name="salesperson_id" class="form-control">
                    <option value="">All Salespersons</option>
                    @foreach($salespeople as $salesperson)
                      <option value="{{ $salesperson->id }}" {{ request('salesperson_id') == $salesperson->id ? 'selected' : '' }}>{{ $salesperson->name }}</option>
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
                    <option value="accept" {{ request('status') == 'accept' ? 'selected' : '' }}>Accept</option>
                    <option value="reject" {{ request('status') == 'reject' ? 'selected' : '' }}>Reject</option>
                    <option value="followup" {{ request('status') == 'followup' ? 'selected' : '' }}>Followup</option>
                    <option value="new" {{ request('status') == 'new' ? 'selected' : '' }}>New</option>
                  </select>
                </div>
              </div>

              {{-- Actions --}}
              <div class="col-12 col-lg d-flex gap-2 justify-content-lg-end">
                <button type="button" class="btn btn-dark" onclick="exportCsvWithFilters()">
                  <i class="bx bx-export me-1"></i> Export
                </button>
                <a href="{{ route('boss.leads') }}" class="btn btn-outline-secondary">Reset</a>
                <a href="{{ route('boss.leads.create') }}" class="btn btn-light text-primary">Add Lead</a>
              </div>
            </div>
          </form>
        </div>
      </div>

      <table class="datatables-ajax table table-striped table-hover" id="leadTable" style="width: 100%;">
        <thead class="table-light sticky-top">
          <tr>
            <th class="text-center align-middle" style="width: 15%;">Lead Data</th>
            <th class="text-center align-middle" style="width: 20%;">Company Details</th>
            <th class="text-center align-middle" style="width: 20%;">Lead Details</th>
            <th class="text-center align-middle" style="width: 15%;">Assigned Salesperson</th>
            <th class="text-center align-middle" style="width: 15%;">Reminder</th>
            <th class="text-center align-middle" style="width: 15%;">Actions</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>

  <!-- Attachment Modal -->
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
        <div class="modal-body"><p id="reminderConfirmText"></p></div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
          <button type="button" class="btn btn-primary" id="confirmReminderDoneBtn">Yes</button>
        </div>
      </div>
    </div>
  </div>

  <hr class="my-12" />
</div>

<script>
  // jQuery fallback
  window.jQuery || document.write('<script src="https://code.jquery.com/jquery-3.6.0.min.js"><\\/script>');

  $(document).ready(function() {
    let table = $('#leadTable').DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: '{{ route('boss.leads.get') }}',
        type: 'POST',
        data: function(d) {
          d._token = $('meta[name="csrf-token"]').attr('content');
          d.search = { value: $('#globalSearch').val() };
          d.status = $('#statusFilter').val();
          d.from_date = $('#fromDate')?.val?.();
          d.to_date   = $('#toDate')?.val?.();
          if ($('#salespersonFilter').length) d.salesperson_id = $('#salespersonFilter').val();
          return d;
        }
      },
      columns: [
        { data: 'lead_data', name: 'lead_data', orderable: true },
        { data: 'company_details', name: 'company_details', orderable: false },
        { data: 'lead_details', name: 'lead_details', orderable: false },
        { data: 'assigned_salesperson', name: 'assigned_salesperson', orderable: true },
        { data: 'reminder', name: 'reminder', orderable: true },
        { data: 'actions', name: 'actions', orderable: false, searchable: false }
      ],

      /* 仅调整底部 info + 分页在一条线上（其它保持不变） */
      dom: 'rt<"dt-footer d-flex justify-content-between align-items-center"ip>',

      /* 顶部还是保留你原来的 pageLength 控件 */
      layout: {
        topStart: {
          rowClass: 'row mx-3 my-2 justify-content-between align-items-center',
          features: [{
            pageLength: {
              menu: [7, 10, 25, 50, 100],
              text: '<span class="text-muted">Show</span> <select class="form-select form-select-sm ms-2"><option value="7">7</option><option value="10">10</option><option value="25">25</option><option value="50">50</option><option value="100">100</option></select> <span class="text-muted">entries</span>'
            }
          }]
        }
        // 不再在 layout.bottomStart / bottomEnd 放 info/paging，避免重复
      },

      /* 分页字符与信息文案 */
      pagingType: 'full_numbers',
      language: {
        paginate: { first:'«', last:'»', previous:'‹', next:'›' },
        info: 'Showing _START_ to _END_ of _TOTAL_ leads'
      },

      order: [[0, 'desc']],
      initComplete: function() {
        $('#leadsFilterForm').on('submit', function(e){ e.preventDefault(); table.draw(); });
        $('#statusFilter, #fromDate, #toDate, #salespersonFilter').on('change', function(){ table.draw(); });
        $('#globalSearch').on('keyup', function(){ table.search(this.value).draw(); });
      }
    });

    // 双击行跳转（避开控件）
    $('#leadTable').on('dblclick', 'tbody tr', function(e) {
      if ($(e.target).closest('select, button, a, i').length) return;
      const leadId = $(this).find('.lead-id').text();
      if (leadId) window.location.href = `/boss/leads/${leadId}/view`;
    });

    // —— 下方保留你原有的事件绑定（状态/机会/指派/提醒/附件/表单） —— //
    $('#leadTable').on('change', '.status-dropdown', function(e){
      e.stopPropagation();
      $.ajax({
        url: '{{ route('boss.leads.update.status', ['id' => ':id']) }}'.replace(':id', $(this).data('id')),
        type: 'POST',
        data: { _token: $('meta[name="csrf-token"]').attr('content'), status: $(this).val() },
        success: function(r){ r.redirect ? location.href = r.redirect : table.ajax.reload(null, false); },
        error: function(xhr){ alert('Error updating status: ' + xhr.responseText); }
      });
    });

    $('#leadTable').on('change', '.opportunity-dropdown', function(e){
      e.stopPropagation();
      $.ajax({
        url: '{{ route('boss.leads.update.opportunity', ['id' => ':id']) }}'.replace(':id', $(this).data('id')),
        type: 'POST',
        data: { _token: $('meta[name="csrf-token"]').attr('content'), opportunity: $(this).val() },
        success: function(){ table.ajax.reload(null, false); },
        error: function(xhr){ alert('Error updating opportunity: ' + xhr.responseText); }
      });
    });

    $('#leadTable').on('change', '.assign-dropdown', function(e){
      e.stopPropagation();
      let id = $(this).data('id');
      let salespersonId = $(this).val();
      if (salespersonId) {
        $.ajax({
          url: '{{ route('boss.leads.update.salesperson', ['id' => ':id']) }}'.replace(':id', id),
          type: 'POST',
          data: { _token: $('meta[name="csrf-token"]').attr('content'), salesperson_id: salespersonId },
          success: function(){ table.ajax.reload(null, false); },
          error: function(xhr){ alert('Error reassigning lead: ' + xhr.responseText); }
        });
      }
    });

    $(document).on('click', '.confirm-reminder', function(e){
      e.stopPropagation(); e.preventDefault();
      let leadId = $(this).data('id');
      let reminderId = $(this).data('reminder-id');
      let title = $(this).data('title');
      $('#reminderConfirmText').text(`Is the reminder "${title}" done?`);
      $('#reminderConfirmModal').data('lead-id', leadId).data('reminder-id', reminderId).modal('show');
    });

    $(document).on('click', '#confirmReminderDoneBtn', function(){
      let leadId = $('#reminderConfirmModal').data('lead-id');
      let reminderId = $('#reminderConfirmModal').data('reminder-id');
      $.ajax({
        url: '{{ route('boss.leads.confirm.reminder.status', ['id' => ':leadId', 'reminderId' => ':reminderId']) }}'
              .replace(':leadId', leadId).replace(':reminderId', reminderId),
        type: 'POST',
        data: { _token: $('meta[name="csrf-token"]').attr('content'), confirm: 'yes' },
        success: function(){
          $('#reminderConfirmModal').modal('hide');
          let $link = $(`a.confirm-reminder[data-reminder-id="${reminderId}"]`);
          if ($link.length){
            let $li = $link.closest('li');
            $li.addClass('text-secondary text-decoration-line-through').fadeOut(2500, function(){
              $li.remove();
              let $ul = $li.parent('ul');
              if ($ul.children('li').length === 0) $ul.replaceWith('<span class="text-muted">No Reminders</span>');
            });
          }
        },
        error: function(xhr){
          $('#reminderConfirmModal').modal('hide');
          let errorMsg = 'Error confirming reminder';
          if (xhr.status === 403){
            try {
              const response = JSON.parse(xhr.responseText);
              if (response.error === 'Unauthorized') errorMsg = 'Only the assigned salesperson can confirm this reminder.';
            } catch(e){}
          } else {
            errorMsg += ': ' + (xhr.responseText || 'Unknown error');
          }
          Swal.fire({ icon:'error', title:'Error!', text:errorMsg, confirmButtonText:'OK', allowOutsideClick:true, allowEscapeKey:true });
        }
      });
    });

    $('#leadTable').on('click', '.view-attachments', function(e){
      e.stopPropagation();
      $.ajax({
        url: '{{ route('boss.leads.attachments', ['id' => ':id']) }}'.replace(':id', $(this).data('id')),
        type: 'GET',
        success: function(res){ $('#attachmentBody').html(res); $('#attachmentModal').modal('show'); },
        error: function(xhr){ alert('Error loading attachments: ' + xhr.responseText); }
      });
    });

    $('#attachmentModal').on('shown.bs.modal', function(){
      $('#attachmentBody').on('click', '.delete-attachment', function(e){
        e.preventDefault();
        if (!confirm('Are you sure you want to delete this attachment?')) return;
        $.ajax({
          url: $(this).attr('href'),
          type:'DELETE',
          data:{ _token: '{{ csrf_token() }}' },
          success: function(){
            $(e.target).closest('tr').remove();
            if ($('#attachmentBody tbody tr').length === 0) $('#attachmentModal').modal('hide');
          },
          error: function(xhr){ alert('Error deleting attachment: ' + xhr.responseText); }
        });
      });
    });

    $('#leadTable').on('submit', 'form', function(e){
      e.preventDefault();
      let form = $(this);
      $.ajax({
        url: form.attr('action'), type:'POST', data: form.serialize(),
        success: function(resp){
          if (resp.message) alert(resp.message);
          table.ajax.reload(null, false);
        },
        error: function(xhr){
          if (xhr.status === 403) alert('Unauthorized: You can only delete your own leads.');
          else alert('Error deleting lead: ' + xhr.responseText);
        }
      });
    });
  });

  function exportCsvWithFilters(){
    var params = new URLSearchParams();
    params.append('search[value]', $('#globalSearch').val());
    params.append('status',        $('#statusFilter').val());
    params.append('from_date',     $('#fromDate')?.val?.() || '');
    params.append('to_date',       $('#toDate')?.val?.()   || '');
    if ($('#salespersonFilter').length) params.append('salesperson_id', $('#salespersonFilter').val());
    window.location.href = '{{ route('boss.leads.export-csv') }}?' + params.toString();
  }
</script>
@endsection
