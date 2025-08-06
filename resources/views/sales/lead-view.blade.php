@extends('layouts.app')

@section('title', 'Lead Details')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Lead Details -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center p-3">
            <h5 class="mb-0">Lead Details - {{ $lead->id }}</h5>
            <div>
                <select class="form-select form-select-sm d-inline-block me-2 text-dark" id="statusDropdown">
                    <option value="accept" {{ $lead->status == 'accept' ? 'selected' : '' }}>Accept</option>
                    <option value="reject" {{ $lead->status == 'reject' ? 'selected' : '' }}>Reject</option>
                    <option value="followup" {{ $lead->status == 'followup' ? 'selected' : '' }}>Followup</option>
                    <option value="new" {{ $lead->status == 'new' ? 'selected' : '' }}>New</option>
                </select>
                <a href="{{ route('sales.leads') }}" class="btn btn-outline-light btn-sm">Back</a>
            </div>
        </div>
        <div class="card-body p-4">
            <ul class="nav nav-tabs nav-justified mb-4" id="leadTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active text-primary" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab" aria-controls="overview" aria-selected="true">Overview</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link text-primary" id="meeting-tab" data-bs-toggle="tab" data-bs-target="#meeting" type="button" role="tab" aria-controls="meeting" aria-selected="false">Meeting</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link text-primary" id="order-history-tab" data-bs-toggle="tab" data-bs-target="#order-history" type="button" role="tab" aria-controls="order-history" aria-selected="false">Order History</button>
                </li>
            </ul>
            <div class="tab-content">
                <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview-tab">
                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="card h-100 border-light shadow-sm">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="avatar me-3">
                                            <img src="{{ asset('assets/img/avatars/1.png') }}" alt="User Avatar" class="rounded-circle" style="width: 50px; height: 50px;">
                                        </div>
                                        <div>
                                            <h6 class="mb-0 fw-bold">{{ $lead->name }}</h6>
                                            <small class="text-muted">Assigned To: {{ $lead->user->name ?? 'Not Assigned' }}</small>
                                        </div>
                                        <a href="#" class="ms-auto text-primary"><i class="bx bx-pencil"></i></a>
                                    </div>
                                    <ul class="list-unstyled text-muted small">
                                        <li><strong>Last Contacted:</strong> {{ $lead->updated_at->diffForHumans() }}</li>
                                        <li><strong>Reminder Date:</strong> {{ $lead->date ? $lead->date->format('Y-m-d') : 'No Reminder' }}</li>
                                        <li><strong>Opportunity:</strong> {{ $lead->opportunity ?? 'None' }}</li>
                                        <li><strong>Company Name:</strong> {{ $lead->company_name }}</li>
                                        <li><strong>Company Phone:</strong> {{ $lead->company_phone ?? 'N/A' }}</li>
                                        <li><strong>Company Website:</strong> <a href="{{ $lead->website }}" target="_blank">{{ $lead->website ?? 'N/A' }}</a></li>
                                        <li><strong>Lead Phone:</strong> {{ $lead->phone }}</li>
                                        <li><strong>Lead Email:</strong> <a href="mailto:{{ $lead->email }}">{{ $lead->email }}</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="card mb-4 border-light shadow-sm">
                                <div class="card-body p-3">
                                    <h6 class="card-title">Remark</h6>
                                    <div class="form-control bg-light p-2 {{ empty($lead->remark) ? 'bg-secondary-subtle' : '' }}" style="min-height: 100px; background-color: {{ empty($lead->remark) ? '#f8f9fa' : '#fff' }} !important;">
                                        {{ $lead->remark ?? 'No remarks available' }}
                                    </div>
                                </div>
                            </div>
                            <div class="card mb-4 border-light shadow-sm">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="card-title">Files</h6>
                                        <button class="btn btn-dark btn-sm" id="addFileBtn">Add File</button>
                                    </div>
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
                                        <tbody>
                                            @foreach ($lead->attachments as $attachment)
                                            <tr>
                                                <td>{{ basename($attachment->file_location) }}</td>
                                                <td>{{ $attachment->user->name ?? 'Unknown' }}</td>
                                                <td>{{ $attachment->created_at->format('Y-m-d') }}</td>
                                                <td>{{ round($attachment->file_size / 1024) }} KB</td>
                                                <td><a href="{{ asset('storage/' . $attachment->file_location) }}" class="btn btn-sm btn-dark" download><i class="bx bx-download"></i></a></td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="card mb-4 border-light shadow-sm">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="card-title">Reminders</h6>
                                        <button class="btn btn-dark btn-sm" data-bs-toggle="modal" data-bs-target="#reminderModal">Add Reminder</button>
                                    </div>
                                    <ul class="list-group list-group-flush">
                                        @forelse ($lead->reminders as $reminder)
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong>{{ $reminder->title }}</strong><br>
                                                <small class="text-muted">Due: {{ $reminder->due_date instanceof \Carbon\Carbon ? $reminder->due_date->format('Y-m-d H:i') : $reminder->due_date }}</small>
                                            </div>
                                            <span class="badge bg-{{ $reminder->status == 'overdue' ? 'danger' : ($reminder->status == 'upcoming' ? 'warning' : 'success') }} rounded-pill">
                                                {{ ucfirst($reminder->status) }}
                                            </span>
                                        </li>
                                        @empty
                                        <li class="list-group-item text-muted">No reminders available.</li>
                                        @endforelse
                                    </ul>
                                </div>
                            </div>
                            <div class="card border-light shadow-sm">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="card-title">Notes</h6>
                                        <div>
                                            <input type="text" class="form-control form-control-sm me-2" id="noteTagFilter" placeholder="Filter by tags">
                                            <button class="btn btn-dark btn-sm" id="addNoteBtn">Add Note</button>
                                        </div>
                                    </div>
                                    <div class="chat-container" style="max-height: 300px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 5px; padding: 10px;">
                                        @forelse ($lead->notes as $note)
                                        <div class="chat-message mb-2 p-2 bg-light rounded" style="max-width: 70%;">
                                            <p class="mb-1">{{ $note->content }}</p>
                                            <small class="text-muted">{{ $note->date instanceof \Carbon\Carbon ? $note->date->format('Y-m-d H:i') : $note->date }}</small>
                                            @if ($note->tags)
                                            <div class="mt-1">
                                                @foreach (json_decode($note->tags) as $tag)
                                                <span class="badge bg-secondary me-1">{{ $tag }}</span>
                                                @endforeach
                                            </div>
                                            @endif
                                        </div>
                                        @empty
                                        <p class="text-muted text-center">No notes yet.</p>
                                        @endforelse
                                    </div>
                                    <div class="mt-3">
                                        <textarea class="form-control mb-2" id="noteContent" placeholder="Type a note..." rows="2"></textarea>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="noteTags" placeholder="Add tags (comma-separated)">
                                            <button class="btn btn-primary" id="sendNoteBtn">Send</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="meeting" role="tabpanel" aria-labelledby="meeting-tab">
                    <p class="text-muted">Meeting details will be added here.</p>
                </div>
                <div class="tab-pane fade" id="order-history" role="tabpanel" aria-labelledby="order-history-tab">
                    <p class="text-muted">Order history will be added here.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Reminder Modal -->
    <div class="modal fade" id="reminderModal" tabindex="-1" aria-labelledby="reminderModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="reminderModalLabel">Add Reminder</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="reminderForm">
                        @csrf
                        <div class="mb-3">
                            <label for="reminderTitle" class="form-label">Title</label>
                            <input type="text" class="form-control" id="reminderTitle" required>
                        </div>
                        <div class="mb-3">
                            <label for="reminderDueDate" class="form-label">Due Date</label>
                            <input type="datetime-local" class="form-control" id="reminderDueDate" required>
                        </div>
                        <div class="mb-3">
                            <label for="reminderStatus" class="form-label">Status</label>
                            <select class="form-select" id="reminderStatus" required>
                                <option value="upcoming">Upcoming</option>
                                <option value="overdue">Overdue</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="saveReminderBtn">Save Reminder</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        $(document).ready(function() {
            // Status Dropdown Update
            $('#statusDropdown').on('change', function() {
                let status = $(this).val();
                $.ajax({
                    url: '{{ route('leads.update.status', ['id' => $lead->id]) }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        status: status
                    },
                    success: function(response) {
                        alert('Status updated successfully');
                    },
                    error: function(xhr) {
                        alert('Error updating status: ' + xhr.responseText);
                    }
                });
            });

            // Add Reminder
            $('#saveReminderBtn').on('click', function() {
                let formData = {
                    title: $('#reminderTitle').val(),
                    due_date: $('#reminderDueDate').val(),
                    status: $('#reminderStatus').val(),
                    _token: '{{ csrf_token() }}'
                };
                $.ajax({
                    url: '{{ route('leads.add.reminder', ['id' => $lead->id]) }}',
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        $('#reminderModal').modal('hide');
                        $('#reminderTitle').val('');
                        $('#reminderDueDate').val('');
                        $('#reminderStatus').val('upcoming');
                        location.reload(); // Refresh to show new reminder
                    },
                    error: function(xhr) {
                        alert('Error adding reminder: ' + xhr.responseText);
                    }
                });
            });

            // Add Note
            $('#sendNoteBtn').on('click', function() {
                let content = $('#noteContent').val();
                let tags = $('#noteTags').val().split(',').map(tag => tag.trim()).filter(tag => tag);
                let formData = {
                    content: content,
                    tags: tags,
                    _token: '{{ csrf_token() }}'
                };
                $.ajax({
                    url: '{{ route('leads.add.note', ['id' => $lead->id]) }}',
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        $('#noteContent').val('');
                        $('#noteTags').val('');
                        location.reload(); // Refresh to show new note
                    },
                    error: function(xhr) {
                        alert('Error adding note: ' + xhr.responseText);
                    }
                });
            });

            // Filter Notes by Tags
            $('#noteTagFilter').on('keyup', function() {
                let filter = $(this).val().toLowerCase();
                $('.chat-message').each(function() {
                    let tags = $(this).find('.badge').map(function() { return $(this).text().toLowerCase(); }).get();
                    $(this).toggle($(this).text().toLowerCase().includes(filter) || tags.some(tag => tag.includes(filter)));
                });
            });
        });
    </script>
    @endpush
</div>
@endsection