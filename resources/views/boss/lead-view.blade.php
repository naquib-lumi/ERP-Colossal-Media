@extends('layouts.app')
@section('title', 'Lead Details')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <!-- Lead Details -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center p-3">
              <h5 class="mb-0 text-white">Lead Details - {{ $lead->id }}</h5>
                <div class="d-flex align-items-center gap-2">
                    <select class="form-select form-select-sm bg-light text-dark border-0" id="statusDropdown"
                        style="min-width: 100px;">
                        <option value="accept" {{ $lead->status == 'accept' ? 'selected' : '' }}>Accept</option>
                        <option value="reject" {{ $lead->status == 'reject' ? 'selected' : '' }}>Reject</option>
                        <option value="followup" {{ $lead->status == 'followup' ? 'selected' : '' }}>Followup</option>
                        <option value="new" {{ $lead->status == 'new' ? 'selected' : '' }}>New</option>
                    </select>
                    <a href="{{ route('boss.leads') }}" class="btn btn-outline-light btn-sm">Back</a>
                </div>
            </div>
            <div class="card-body p-4">
                <ul class="nav nav-tabs nav-justified mb-4" id="leadTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active text-primary" id="overview-tab" data-bs-toggle="tab"
                            data-bs-target="#overview" type="button" role="tab" aria-controls="overview"
                            aria-selected="true">Overview</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link text-primary" id="meeting-tab" data-bs-toggle="tab"
                            data-bs-target="#meeting" type="button" role="tab" aria-controls="meeting"
                            aria-selected="false">Meeting</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link text-primary" id="order-history-tab" data-bs-toggle="tab"
                            data-bs-target="#order-history" type="button" role="tab" aria-controls="order-history"
                            aria-selected="false">Order History</button>
                    </li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview-tab">
                        <div class="row g-4">
                            <div class="col-md-4">
                                <div class="card h-100 border-light shadow-sm">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center mb-3">
                                            <div>
                                                <h6 class="mb-0 fw-bold">{{ $lead->name }}</h6>
                                                <small class="text-muted">Assigned To:
                                                    {{ $lead->user->name ?? 'Not Assigned' }}</small>
                                            </div>
                                            <a href="{{ route('boss.leads.edit', $lead->id) }}" class="ms-auto text-primary"><i
                                                    class="bx bx-pencil"></i></a>
                                        </div>
                                        <ul class="list-unstyled text-muted small">
                                            <li><strong>Created At:</strong>
                                                {{ $lead->created_at ? $lead->created_at->format('Y-m-d') : 'No Reminder' }}
                                            </li>
                                            <li><strong>Opportunity:</strong> {{ $lead->opportunity ?? 'None' }}</li>
                                            <li><strong>Company Name:</strong> {{ $lead->company_name }}</li>
                                            <li><strong>Company Phone:</strong> {{ $lead->company_phone ?? 'N/A' }}</li>
                                            <li><strong>Company Website:</strong> <a href="{{ $lead->website }}"
                                                    target="_blank">{{ $lead->website ?? 'N/A' }}</a></li>
                                            <li><strong>Lead Phone:</strong> {{ $lead->phone }}</li>
                                            <li><strong>Lead Email:</strong> <a
                                                    href="mailto:{{ $lead->email }}">{{ $lead->email }}</a></li>
                                            <br>
                                            @auth
                                                @if (auth()->user()->role === 'boss')
                                                    <li>
                                                        <form action="{{ route('boss.leads.destroy', $lead->id) }}" method="POST"
                                                            style="display:inline;"
                                                            onsubmit="return confirm('Are you sure you want to delete this lead?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                                                <i class="bx bxs-trash me-1"></i> DELETE LEAD –
                                                                {{ $lead->id }}
                                                            </button>
                                                        </form>
                                                    </li>
                                                @endif
                                            @endauth
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="card mb-4 border-light shadow-sm">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6 class="card-title">Remark</h6>
                                            <a href="{{ route('boss.leads.edit', $lead->id) }}?highlight=remark"
                                                class="btn btn-sm btn-outline-primary"><i class="bx bx-pencil"></i> Edit</a>
                                        </div>
                                        <div class="form-control bg-light p-2 {{ empty($lead->remark) ? 'bg-secondary-subtle' : '' }}"
                                            style="min-height: 100px; background-color: {{ empty($lead->remark) ? '#f8f9fa' : '#fff' }} !important;">
                                            {{ $lead->remark ?? 'No remarks available' }}
                                        </div>
                                    </div>
                                </div>
                                <div class="card mb-4 border-light shadow-sm">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6 class="card-title">Files</h6>
                                            <button class="btn btn-outline-primary btn-sm" id="addFileBtn">Add File</button>
                                        </div>
                                        <form action="{{ route('boss.leads.add.attachment', ['id' => $lead->id]) }}"
                                            method="POST" enctype="multipart/form-data" id="addFileForm"
                                            style="display: none;">
                                            @csrf
                                            <div class="mb-3">
                                                <input type="file" class="form-control" name="attachments[]" multiple
                                                    accept=".pdf,.doc,.jpg,.png">
                                            </div>
                                            <button type="submit" class="btn btn-primary btn-sm">Upload</button>
                                        </form>
                                        <table class="table table-bordered table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Uploaded By</th>
                                                    <th>Date</th>
                                                    <th>File Size</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="attachmentsTableBody">
                                                @foreach ($lead->attachments as $attachment)
                                                    <tr>
                                                        <td>{{ basename($attachment->file_location) }}</td>
                                                        <td>{{ $attachment->user->name ?? 'Unknown' }}</td>
                                                        <td>{{ $attachment->created_at->format('Y-m-d') }}</td>
                                                        <td>{{ round($attachment->file_size / 1024) }} KB</td>
                                                        <td>
                                                            <a href="{{ asset('storage/' . $attachment->file_location) }}"
                                                                class="btn btn-sm btn-outline-primary me-1" download><i
                                                                    class="bx bx-download"></i></a>
                                                            <a href="{{ route('boss.leads.attachments.delete', ['id' => $lead->id, 'attachment' => $attachment->id]) }}"
                                                                class="btn btn-sm btn-outline-danger delete-attachment"><i
                                                                    class="bx bx-trash"></i></a>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <script>
                                    $(document).ready(function() {
                                        $('#addFileBtn').on('click', function() {
                                            $('#addFileForm').slideToggle();
                                        });
                                        $('#addFileForm').on('submit', function(e) {
                                            const fileInput = $('input[name="attachments[]"]')[0];
                                            if (!fileInput.files || fileInput.files.length === 0) {
                                                e.preventDefault();
                                                Swal.fire('Warning', 'Please select at least one file to upload.', 'warning');
                                            }
                                        });
                                        $('#attachmentsTableBody').on('click', '.delete-attachment', function(e) {
                                            e.preventDefault();
                                            Swal.fire({
                                                title: 'Are you sure?',
                                                text: 'Delete this attachment?',
                                                icon: 'warning',
                                                showCancelButton: true,
                                                confirmButtonText: 'Yes, delete it!'
                                            }).then((result) => {
                                                if (result.isConfirmed) {
                                                    let url = $(this).attr('href');
                                                    $.ajax({
                                                        url: url,
                                                        type: 'DELETE',
                                                        data: {
                                                            _token: '{{ csrf_token() }}',
                                                        },
                                                        success: function(response) {
                                                            $(e.target).closest('tr').remove();
                                                            if ($('#attachmentsTableBody tr').length === 0) {
                                                                $('#attachmentsTableBody').html(
                                                                    '<tr><td colspan="5" class="text-center text-muted">No attachments</td></tr>'
                                                                );
                                                            }
                                                        },
                                                        error: function(xhr) {
                                                            let errorMsg = 'Error deleting attachment.';
                                                            try {
                                                                let resp = JSON.parse(xhr.responseText);
                                                                if (resp.message) errorMsg = resp.message;
                                                                else if (resp.errors) {
                                                                    let errs = [];
                                                                    for (let k in resp.errors) errs.push(...resp.errors[k]);
                                                                    errorMsg = errs.join(', ');
                                                                }
                                                            } catch {}
                                                            Swal.fire('Error!', errorMsg, 'error');
                                                        }
                                                    });
                                                }
                                            });
                                        });
                                    });
                                </script>
                                <div class="card mb-4 border-light shadow-sm">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6 class="card-title">Reminders</h6>
                                            <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal"
                                                data-bs-target="#reminderModal">Add Reminder</button>
                                        </div>
                                        <ul class="list-group list-group-flush">
                                            @forelse ($lead->reminders as $reminder)
                                                <li class="list-group-item reminder-item d-flex justify-content-between align-items-center cursor-pointer"
                                                    data-id="{{ $reminder->id }}" data-title="{{ $reminder->title }}"
                                                    data-description="{{ $reminder->description ?? '' }}"
                                                    data-remind-at="{{ $reminder->remind_at->format('Y-m-d\TH:i') }}">
                                                    <div class="flex-grow-1 me-2">
                                                        <strong>{{ $reminder->title }}</strong><br>
                                                        @if ($reminder->description)
                                                            <small
                                                                class="text-muted">{{ $reminder->description }}</small><br>
                                                        @endif
                                                        <small class="text-muted">Due:
                                                            {{ $reminder->remind_at instanceof \Carbon\Carbon ? $reminder->remind_at->format('Y-m-d H:i') : $reminder->remind_at }}</small>
                                                    </div>
                                                    <div class="ms-2 d-flex align-items-center justify-content-end">
                                                        <select class="form-select form-select-sm reminder-status-update"
                                                            data-id="{{ $reminder->id }}"
                                                            style="width: auto; background-color: white;">
                                                            <option value="upcoming"
                                                                {{ $reminder->status == 'upcoming' ? 'selected' : '' }}>
                                                                Upcoming</option>
                                                            <option value="overdue"
                                                                {{ $reminder->status == 'overdue' ? 'selected' : '' }}>
                                                                Overdue</option>
                                                            <option value="completed"
                                                                {{ $reminder->status == 'completed' ? 'selected' : '' }}>
                                                                Completed</option>
                                                        </select>
                                                        <a href="#" class="text-primary ms-2 edit-reminder"
                                                            style="font-size: 0.9rem;">
                                                            <i class="bx bx-pencil"></i>
                                                        </a>
                                                    </div>
                                                </li>
                                            @empty
                                                <li class="list-group-item text-muted">No reminders available.</li>
                                            @endforelse
                                        </ul>
                                    </div>
                                </div>
                                <!-- Notes card updated as in previous response -->
                                <div class="card border-light shadow-sm">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6 class="card-title">Notes</h6>
                                        </div>
                                        <div class="chat-container"
                                            style="max-height: 300px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 5px; padding: 10px;">
                                            @forelse ($lead->notes as $note)
                                                <div class="chat-message mb-2 p-2 bg-light rounded"
                                                    style="max-width: 70%;">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div>
                                                            <p class="mb-1">{{ $note->content ?: '-' }}</p>
                                                            <small class="text-muted">
                                                                {{ $note->user->name ?? 'Unknown' }} ·
                                                                @if ($note->created_at->diffInDays() == 0)
                                                                    Today
                                                                @elseif($note->created_at->diffInDays() == 1)
                                                                    Yesterday
                                                                @elseif($note->created_at->diffInDays() == 2)
                                                                    Two days ago
                                                                @else
                                                                    {{ $note->created_at->format('Y-m-d H:i') }}
                                                                @endif
                                                                {{ $note->created_at->diffInHours() >= 24 ? '' : 'at ' . $note->created_at->format('H:i') }}
                                                            </small>
                                                        </div>
                                                        <button class="btn btn-outline-danger btn-sm ms-2 delete-note-btn"
                                                            data-note-id="{{ $note->id }}"
                                                            data-lead-id="{{ $lead->id }}">
                                                            <i class="bx bx-trash"></i>
                                                        </button>
                                                    </div>
                                                    @if ($note->attachments->count() > 0)
                                                        <div class="mt-2 d-flex flex-wrap gap-2">
                                                            @foreach ($note->attachments as $attachment)
                                                                <a href="{{ asset('storage/' . $attachment->file_location) }}"
                                                                    download
                                                                    style="text-decoration: none; font-size: 2rem;">
                                                                    <i class="bx bx-paperclip text-secondary"></i>
                                                                </a>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                            @empty
                                                <p class="text-muted text-center">No notes yet.</p>
                                            @endforelse
                                        </div>
                                        <div class="mt-3">
                                            <div class="input-group">
                                                <label for="noteAttachment" class="btn btn-outline-secondary mb-0">
                                                    <i class="bx bx-paperclip"></i>
                                                </label>
                                                <input type="file" id="noteAttachment" accept=".pdf,.doc,.jpg,.png"
                                                    style="display: none;">
                                                <textarea class="form-control" id="noteContent" placeholder="Write a note..." rows="1" style="resize: none;"></textarea>
                                                <button class="btn btn-primary" id="sendNoteBtn">
                                                    <i class="bx bx-send"></i>
                                                </button>
                                            </div>
                                            <small id="fileName" class="text-muted d-block mt-1"></small>
                                        </div>
                                    </div>
                                </div>
                                <script>
                                    document.getElementById('noteAttachment').addEventListener('change', function() {
                                        const fileName = this.files.length ? this.files[0].name : '';
                                        document.getElementById('fileName').textContent = fileName ? `Attached: ${fileName}` : '';
                                    });
                                </script>
                                <script>
                                    $(document).ready(function() {
                                        $('#sendNoteBtn').on('click', function() {
                                            let content = $('#noteContent').val().trim();
                                            let file = $('#noteAttachment')[0].files[0];
                                            if (!content && !file) {
                                                Swal.fire('Warning', 'Please enter a note or attach a file.', 'warning');
                                                return;
                                            }
                                            if (!content && file) {
                                                content = '-';
                                            }
                                            let formData = new FormData();
                                            formData.append('content', content);
                                            formData.append('_token', '{{ csrf_token() }}');
                                            if (file) {
                                                formData.append('attachments[]', file);
                                            }
                                            $.ajax({
                                                url: '{{ route('boss.leads.add.note', ['id' => $lead->id]) }}',
                                                type: 'POST',
                                                data: formData,
                                                processData: false,
                                                contentType: false,
                                                success: function(response) {
                                                    $('#noteContent').val('');
                                                    $('#noteAttachment').val('');
                                                    document.getElementById('fileName').textContent = '';
                                                    location.reload();
                                                },
                                                error: function(xhr) {
                                                    let errorMsg = 'Error adding note.';
                                                    try {
                                                        let resp = JSON.parse(xhr.responseText);
                                                        if (resp.message) errorMsg = resp.message;
                                                        else if (resp.errors) {
                                                            let errs = [];
                                                            for (let k in resp.errors) errs.push(...resp.errors[k]);
                                                            errorMsg = errs.join(', ');
                                                        }
                                                    } catch {}
                                                    Swal.fire('Error!', errorMsg, 'error');
                                                }
                                            });
                                        });
                                        $('.delete-note-btn').on('click', function() {
                                            Swal.fire({
                                                title: 'Are you sure?',
                                                text: 'Delete this note and attachments?',
                                                icon: 'warning',
                                                showCancelButton: true,
                                                confirmButtonText: 'Yes, delete it!'
                                            }).then((result) => {
                                                if (result.isConfirmed) {
                                                    let noteId = $(this).data('note-id');
                                                    let leadId = $(this).data('lead-id');
                                                    $.ajax({
                                                        url: `/boss/leads/${leadId}/notes/${noteId}`,
                                                        type: 'DELETE',
                                                        data: {
                                                            _token: '{{ csrf_token() }}'
                                                        },
                                                        success: function() {
                                                            location.reload();
                                                        },
                                                        error: function(xhr) {
                                                            let errorMsg = 'Error deleting note.';
                                                            try {
                                                                let resp = JSON.parse(xhr.responseText);
                                                                if (resp.message) errorMsg = resp.message;
                                                                else if (resp.errors) {
                                                                    let errs = [];
                                                                    for (let k in resp.errors) errs.push(...resp.errors[k]);
                                                                    errorMsg = errs.join(', ');
                                                                }
                                                            } catch {}
                                                            Swal.fire('Error!', errorMsg, 'error');
                                                        }
                                                    });
                                                }
                                            });
                                        });
                                    });
                                </script>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="meeting" role="tabpanel" aria-labelledby="meeting-tab">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="card-title">Meetings</h6>
                            <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal"
                                data-bs-target="#meetingModal">Add
                                Meeting</button>
                        </div>
                        <div class="row">
                            @forelse ($lead->meetings->sortBy('start_time') as $meeting)
                                <div class="col-12">
                                    <div class="border rounded p-3 mb-3 bg-light meeting-item cursor-pointer"
                                        data-id="{{ $meeting->id }}" data-title="{{ $meeting->title }}"
                                        data-start-time="{{ $meeting->start_time->format('Y-m-d\TH:i') }}"
                                        data-end-time="{{ $meeting->end_time->format('Y-m-d\TH:i') }}"
                                        data-type="{{ $meeting->type }}" data-url="{{ $meeting->url ?? '' }}"
                                        data-location="{{ $meeting->location ?? '' }}"
                                        data-note="{{ $meeting->note ?? '' }}">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1 fw-bold">{{ $meeting->title }}</h6>
                                                <small class="text-muted d-block mb-1">
    {{ $meeting->start_time->format('D, h:i A') }} - {{ $meeting->end_time->format('h:i A') }}
</small>

                                                @if ($meeting->type === 'online' && $meeting->url)
                                                    <small class="text-primary d-block"><a href="{{ $meeting->url }}"
                                                            target="_blank"
                                                            class="text-decoration-none">{{ $meeting->url }}</a></small>
                                                @elseif ($meeting->type === 'offline' && $meeting->location)
                                                    <small class="text-muted d-block">{{ $meeting->location }}</small>
                                                @endif
                                                @if ($meeting->note)
                                                    <small class="text-muted d-block mt-1">{{ $meeting->note }}</small>
                                                @endif
                                            </div>
                                            <div class="ms-2 d-flex align-items-center justify-content-end">
                                                <select class="form-select form-select-sm status-update"
                                                    data-id="{{ $meeting->id }}"
                                                    style="width: auto; background-color: white;">
                                                    <option value="scheduled"
                                                        {{ $meeting->status == 'scheduled' ? 'selected' : '' }}>Scheduled
                                                    </option>
                                                    <option value="canceled"
                                                        {{ $meeting->status == 'canceled' ? 'selected' : '' }}>Canceled
                                                    </option>
                                                    <option value="postponed"
                                                        {{ $meeting->status == 'postponed' ? 'selected' : '' }}>Postponed
                                                    </option>
                                                </select>
                                                <a href="#" class="text-primary ms-2 edit-meeting"
                                                    style="font-size: 0.9rem;">
                                                    <i class="bx bx-pencil"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12">
                                    <p class="text-muted text-center">No meetings available.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                    <!-- Update Modal (reuse/add to existing) -->
                    <div class="modal fade" id="meetingModal" tabindex="-1" aria-labelledby="meetingModalLabel"
                        aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header bg-primary text-white">
                                    <h5 class="modal-title" id="meetingModalLabel">Add Meeting</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form id="meetingForm">
                                        @csrf
                                        <input type="hidden" id="meetingId" name="id">
                                        <input type="hidden" id="meetingLeadId" name="lead_id"
                                            value="{{ $lead->id }}">
                                        <div class="mb-3">
                                            <label for="meetingTitle" class="form-label">Title</label>
                                            <input type="text" class="form-control" id="meetingTitle" name="title"
                                                required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="meetingStartTime" class="form-label">Start Date & Time</label>
                                            <input type="datetime-local" class="form-control" id="meetingStartTime"
                                                name="start_time" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="meetingDuration" class="form-label">Duration (minutes)</label>
                                            <input type="number" class="form-control" id="meetingDuration"
                                                name="duration" min="1" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Type</label>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="type"
                                                    id="typeOnline" value="online" checked>
                                                <label class="form-check-label" for="typeOnline">Online</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="type"
                                                    id="typeOffline" value="offline">
                                                <label class="form-check-label" for="typeOffline">Offline</label>
                                            </div>
                                        </div>
                                        <div class="mb-3" id="onlineUrl" style="display: block;">
                                            <label for="meetingUrl" class="form-label">URL</label>
                                            <input type="url" class="form-control" id="meetingUrl" name="url">
                                        </div>
                                        <div class="mb-3" id="offlineLocation" style="display: none;">
                                            <label for="meetingLocation" class="form-label">Location</label>
                                            <input type="text" class="form-control" id="meetingLocation"
                                                name="location">
                                        </div>
                                        <div class="mb-3">
                                            <label for="meetingNote" class="form-label">Description</label>
                                            <textarea class="form-control" id="meetingNote" name="note" rows="3"></textarea>
                                        </div>
                                    </form>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary"
                                        data-bs-dismiss="modal">Close</button>
                                    <button type="button" class="btn btn-primary" id="saveMeetingBtn">Save
                                        Meeting</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="order-history" role="tabpanel" aria-labelledby="order-history-tab">
                        @if ($lead->orders->isEmpty())
                            <p class="text-muted">No orders yet.</p>
                        @else
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Job Title</th>
                                            <th>Created Date</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($lead->orders as $order)
                                            <tr>
                                                <td>{{ $order->order_number }}</td>
                                                <td>{{ $order->orderTitle }}</td>
                                                <td>{{ $order->created_at->format('Y-m-d') }}</td>
                                                <td><span
                                                        class="badge bg-{{ $order->orderStatus == 'To_assign' ? 'warning' : ($order->orderStatus == 'completed' ? 'success' : 'secondary') }}">{{ ucfirst($order->orderStatus) }}</span>
                                                </td>
                                                <td>
                                                    <a href="{{ route('orders.show', $order->id) }}"
                                                        class="btn btn-sm btn-outline-primary">View</a>
                                                    @if ($order->orderStatus == 'to_assign')
                                                        <a href="{{ route('orders.edit', $order->id) }}?from=lead&lead_id={{ $lead->id }}"
                                                            class="btn btn-sm btn-outline-warning ms-1">Edit</a>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <!-- Reminder Modal -->
            <div class="modal fade" id="reminderModal" tabindex="-1" aria-labelledby="reminderModalLabel"
                aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title" id="reminderModalLabel">Add Custom Reminder</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="reminderForm">
                                @csrf
                                <input type="hidden" name="lead_id" id="reminderLeadId" value="{{ $lead->id ?? '' }}">
                                <input type="hidden" name="status" value="upcoming">
                                <input type="hidden" id="reminderId" name="id">
                                <div class="mb-3">
                                    <label class="form-label" for="reminderTitle">Title</label>
                                    <input type="text" class="form-control" id="reminderTitle" name="title"
                                        placeholder="Reminder Title" required />
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="reminderRemindAt">Remind Time & Date</label>
                                    <input type="datetime-local" class="form-control" id="reminderRemindAt"
                                        name="remind_at" required />
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="reminderDescription">Description</label>
                                    <textarea class="form-control" id="reminderDescription" name="description" placeholder="Reminder Description"
                                        rows="3"></textarea>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="saveReminderBtn">Add</button>
                        </div>
                    </div>
                </div>
            </div>
            <script>
                function parseError(xhr) {
                    let errorMsg = 'An error occurred.';
                    try {
                        let resp = JSON.parse(xhr.responseText);
                        if (resp.message) errorMsg = resp.message;
                        else if (resp.errors) {
                            let errs = [];
                            for (let k in resp.errors) errs.push(...resp.errors[k]);
                            errorMsg = errs.join(', ');
                        }
                    } catch {}
                    return errorMsg;
                }
                $(document).ready(function() {
                    // Activate tab based on hash
                    if (location.hash === '#meeting') {
                        $('#meeting-tab').tab('show');
                    }
                    if (location.hash === '#order-history') {
                        $('#order-history-tab').tab('show');
                    }
                    // Update time badges
                    function updateTimeBadges() {
                        $('.meeting-item').each(function() {
                            let $item = $(this);
                            let start = moment($item.data('start-time'));
                            let now = moment();
                            let diff = start.diff(now, 'minutes');
                            let $badge = $item.find('.time-badge');
                            let $select = $item.find('.status-update');
                            let status = $select.val();
                            if (status === 'canceled') {
                                $badge.text('Canceled').removeClass('bg-primary bg-info bg-success').addClass(
                                    'bg-danger');
                            } else if (status === 'postponed') {
                                $badge.text('Postponed').removeClass('bg-primary bg-info bg-success').addClass(
                                    'bg-warning');
                            } else if (status === 'scheduled') {
                                if (diff > 0) {
                                    let unit = diff >= 60 ? 'hours' : 'minutes';
                                    let val = diff >= 60 ? Math.round(diff / 60) : diff;
                                    $badge.text(val + ' ' + unit + (val > 1 ? 's' : '') + ' from now').removeClass(
                                        'bg-info bg-success bg-danger bg-warning').addClass('bg-primary');
                                } else if (diff > -60) {
                                    $badge.text('Ongoing').removeClass('bg-primary bg-success bg-danger bg-warning')
                                        .addClass('bg-info');
                                } else {
                                    $badge.text('Completed').removeClass('bg-primary bg-info bg-danger bg-warning')
                                        .addClass('bg-success');
                                }
                            }
                        });
                    }
                    updateTimeBadges();
                    setInterval(updateTimeBadges, 60000); // Update every minute
                    // Meeting functions defined first
                    function saveNewMeeting() {
                        let formData = new FormData($('#meetingForm')[0]);
                        formData.append('_token', '{{ csrf_token() }}');
                        $.ajax({
                            url: '{{ route('boss.meetings.store', ['lead' => $lead->id]) }}',
                            type: 'POST',
                            data: formData,
                            contentType: false,
                            processData: false,
                            success: function(response) {
                                $('#meetingModal').modal('hide');
                                location.hash = 'meeting';
                                location.reload();
                            },
                            error: function(xhr) {
                                $('#meetingModal').modal('hide');
                                let errorMsg = parseError(xhr);
                                Swal.fire('Error!', errorMsg, 'error');
                            }
                        });
                    }
                    function updateMeeting() {
                        let id = $('#meetingId').val();
                        let formData = new FormData($('#meetingForm')[0]);
                        formData.append('_method', 'PUT');
                        formData.append('_token', '{{ csrf_token() }}');
                        $.ajax({
                            url: `/boss/calendar/meetings/${id}`,
                            type: 'POST',
                            data: formData,
                            contentType: false,
                            processData: false,
                            success: function(response) {
                                $('#meetingModal').modal('hide');
                                location.hash = 'meeting';
                                location.reload();
                            },
                            error: function(xhr) {
                                $('#meetingModal').modal('hide');
                                let errorMsg = parseError(xhr);
                                Swal.fire('Error!', errorMsg, 'error');
                            }
                        });
                    }
                    // Type radio toggle
                    $('input[name="type"]').on('change', function() {
                        if ($(this).val() === 'online') {
                            $('#onlineUrl').show();
                            $('#offlineLocation').hide();
                        } else {
                            $('#onlineUrl').hide();
                            $('#offlineLocation').show();
                        }
                    });
                    // Edit meeting - double click
                    $(document).on('dblclick', '.meeting-item', function(e) {
                        if ($(e.target).is('.status-update, .status-update *')) return;
                        let $item = $(this);
                        let id = $item.data('id');
                        let title = $item.data('title');
                        let startTime = $item.data('start-time');
                        let endTime = $item.data('end-time');
                        let duration = moment(endTime).diff(moment(startTime), 'minutes');
                        let type = $item.data('type');
                        let url = $item.data('url') || '';
                        let location = $item.data('location') || '';
                        let note = $item.data('note') || '';
                        $('#meetingModalLabel').text('Update Meeting');
                        $('#meetingId').val(id);
                        $('#meetingTitle').val(title);
                        $('#meetingStartTime').val(startTime);
                        $('#meetingDuration').val(duration);
                        $('input[name="type"][value="' + type + '"]').prop('checked', true).trigger('change');
                        if (type === 'online') {
                            $('#meetingUrl').val(url);
                        } else {
                            $('#meetingLocation').val(location);
                        }
                        $('#meetingNote').val(note);
                        $('#saveMeetingBtn').off('click').text('Update').on('click', updateMeeting);
                        $('#meetingModal').modal('show');
                    });
                    // Edit meeting - button click
$(document).on('click', '.edit-meeting', function(e) {
    e.preventDefault();
    e.stopPropagation();
    let $item = $(this).closest('.meeting-item');
    let id = $item.data('id');
    let title = $item.data('title');
    let startTime = $item.data('start-time');
    let endTime = $item.data('end-time');
    let duration = moment(endTime).diff(moment(startTime), 'minutes');
    let type = $item.data('type');
    let url = $item.data('url') || '';
    let location = $item.data('location') || '';
    let note = $item.data('note') || '';
    $('#meetingModalLabel').text('Update Meeting');
    $('#meetingId').val(id);
    $('#meetingTitle').val(title);
    $('#meetingStartTime').val(startTime);
    $('#meetingDuration').val(duration);
    $('input[name="type"][value="' + type + '"]').prop('checked', true).trigger('change');
    if (type === 'online') {
        $('#meetingUrl').val(url);
    } else {
        $('#meetingLocation').val(location);
    }
    $('#meetingNote').val(note);
    $('#saveMeetingBtn').off('click').text('Update').on('click', updateMeeting);
    $('#meetingModal').modal('show');
});
                    // Reset for add new
                    $('#meetingModal').on('hidden.bs.modal', function() {
                        $('#meetingModalLabel').text('Add Meeting');
                        $('#meetingId').val('');
                        $('#meetingForm')[0].reset();
                        $('input[name="type"][value="online"]').prop('checked', true).trigger('change');
                        $('#saveMeetingBtn').off('click').text('Save Meeting').on('click', saveNewMeeting);
                    });
                    // Status update for meetings
                    $('.status-update').on('change', function() {
                        let id = $(this).data('id');
                        let status = $(this).val();
                        $.ajax({
                            url: `/boss/meetings/${id}/status`,
                            type: 'POST',
                            data: {
                                status: status,
                                _token: '{{ csrf_token() }}'
                            },
                            success: function() {
                                location.hash = 'meeting';
                                location.reload();
                            },
                            error: function(xhr) {
                                let errorMsg = parseError(xhr);
                                Swal.fire('Error!', errorMsg, 'error');
                            }
                        });
                    });
                    // Status update for reminders
                    $('.reminder-status-update').on('change', function() {
                        let id = $(this).data('id');
                        let status = $(this).val();
                        $.ajax({
                            url: `/boss/calendar/reminders/${id}/update-status`,
                            type: 'POST',
                            data: {
                                status: status,
                                _token: '{{ csrf_token() }}'
                            },
                            success: function() {
                                location.reload();
                            },
                            error: function(xhr) {
                                let errorMsg = parseError(xhr);
                                Swal.fire('Error!', errorMsg, 'error');
                            }
                        });
                    });
                    // Edit reminder function
                    function editReminder($item) {
                        let id = $item.data('id');
                        let title = $item.data('title');
                        let description = $item.data('description');
                        let remindAt = $item.data('remind-at');
                        $('#reminderModalLabel').text('Update Reminder');
                        $('#reminderId').val(id);
                        $('#reminderTitle').val(title);
                        $('#reminderDescription').val(description);
                        $('#reminderRemindAt').val(remindAt);
                        $('#saveReminderBtn').off('click').text('Update').on('click', updateReminder);
                        $('#reminderModal').modal('show');
                    }
                    // Edit reminder - double click
                    $(document).on('dblclick', '.reminder-item', function(e) {
                        if ($(e.target).is(
                                '.reminder-status-update, .reminder-status-update *, .edit-reminder, .edit-reminder *'
                                )) return;
                        editReminder($(this));
                    });
                    // Edit reminder - button click
                    $(document).on('click', '.edit-reminder', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        let $item = $(this).closest('.reminder-item');
                        editReminder($item);
                    });
                    // Debug: Confirm jQuery is loaded
                    console.log('jQuery loaded:', typeof $);
                    // Status Dropdown Update
                    $('#statusDropdown').on('change', function() {
                        let status = $(this).val();
                        $.ajax({
                            url: '{{ route('boss.leads.update.status', ['id' => $lead->id]) }}',
                            type: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                status: status
                            },
                            success: function(response) {
                                location.reload();
                            },
                            error: function(xhr) {
                                let errorMsg = parseError(xhr);
                                Swal.fire('Error!', errorMsg, 'error');
                            }
                        });
                    });
                    // Reset modal for add
                    $('#reminderModal').on('hidden.bs.modal', function() {
                        $('#reminderModalLabel').text('Add Custom Reminder');
                        $('#reminderId').val('');
                        $('#reminderForm')[0].reset();
                        $('#saveReminderBtn').off('click').text('Add').on('click', saveNewReminder);
                    });
                    // Add Reminder with event delegation
                    function saveNewReminder() {
                        let formData = {
                            lead_id: $('#reminderLeadId').val(),
                            title: $('#reminderTitle').val(),
                            description: $('#reminderDescription').val(),
                            remind_at: $('#reminderRemindAt').val(),
                            _token: '{{ csrf_token() }}',
                        };
                        $.ajax({
                            url: '{{ route('boss.calendar.reminders.store') }}',
                            type: 'POST',
                            data: formData,
                            success: function(response) {
                                $('#reminderModal').modal('hide');
                                $('#reminderForm')[0].reset();
                                location.reload();
                            },
                            error: function(xhr) {
                                $('#reminderModal').modal('hide');
                                let errorMsg = parseError(xhr);
                                Swal.fire('Error!', errorMsg, 'error');
                            }
                        });
                    }
                    function updateReminder() {
                        let id = $('#reminderId').val();
                        let formData = new FormData($('#reminderForm')[0]);
                        formData.append('_method', 'PUT');
                        formData.append('_token', '{{ csrf_token() }}');
                        $.ajax({
                            url: `/boss/calendar/reminders/${id}`,
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            success: function(response) {
                                $('#reminderModal').modal('hide');
                                location.reload();
                            },
                            error: function(xhr) {
                                $('#reminderModal').modal('hide');
                                let errorMsg = parseError(xhr);
                                Swal.fire('Error!', errorMsg, 'error');
                            }
                        });
                    }
                    $(document).on('click', '#saveReminderBtn', function(e) {
                        e.preventDefault();
                        if ($('#reminderId').val()) {
                            updateReminder();
                        } else {
                            saveNewReminder();
                        }
                    });
                    // Attach initial event for saveMeetingBtn
                    $('#saveMeetingBtn').on('click', saveNewMeeting);
                });
            </script>
        </div>
    @endsection