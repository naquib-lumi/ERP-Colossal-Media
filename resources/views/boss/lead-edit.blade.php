@extends('layouts.app')

@section('title', 'Edit Lead')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="row g-6">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Edit Lead</h5>
                    <a href="{{ request()->get('highlight') == 'remark' ? route('boss.leads.show', $lead->id) : route('boss.leads') }}" class="btn btn-secondary">Back</a>
                </div>
                <div class="card-body">
                    <form action="{{ route('boss.leads.update', $lead->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="highlight" value="{{ request()->get('highlight') }}">
                        <div class="row g-4">
                            <!-- Company Name and Company Phone -->
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="companyName" name="company_name" placeholder="Enter company name" value="{{ old('company_name', $lead->company_name) }}" required>
                                    <label for="companyName">Company Name</label>
                                    @error('company_name')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="companyPhone" name="company_phone" placeholder="Enter company phone" value="{{ old('company_phone', $lead->company_phone) }}" pattern="[0-9\s\-\+\(\)]*">
                                    <label for="companyPhone">Company Phone</label>
                                    @error('company_phone')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Website and PIC Name -->
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="website" name="website" placeholder="example.com" value="{{ old('website', $lead->website) }}">
                                    <label for="website">Website</label>
                                    @error('website')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="picName" name="name" placeholder="Enter PIC name" value="{{ old('name', $lead->name) }}" required>
                                    <label for="picName">PIC Name</label>
                                    @error('name')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Lead Phone and Lead Email -->
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="tel" class="form-control" id="leadPhone" name="phone" placeholder="Enter lead phone" value="{{ old('phone', $lead->phone) }}" required pattern="[0-9\s\-\+\(\)]*">
                                    <label for="leadPhone">Lead Phone</label>
                                    @error('phone')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="email" class="form-control" id="leadEmail" name="email" placeholder="Enter lead email" value="{{ old('email', $lead->email) }}">
                                    <label for="leadEmail">Lead Email</label>
                                    @error('email')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Status -->
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="" disabled>Select Status</option>
                                        <option value="new" {{ old('status', $lead->status) == 'new' ? 'selected' : '' }}>New</option>
                                        <option value="accept" {{ old('status', $lead->status) == 'accept' ? 'selected' : '' }}>Accept</option>
                                        <option value="reject" {{ old('status', $lead->status) == 'reject' ? 'selected' : '' }}>Reject</option>
                                        <option value="followup" {{ old('status', $lead->status) == 'followup' ? 'selected' : '' }}>Followup</option>
                                        <option value="meeting" {{ old('status', $lead->status) == 'meeting' ? 'selected' : '' }}>Meeting</option>
                                    </select>
                                    <label for="status">Status</label>
                                    @error('status')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Assign To -->
                            <div class="col-md-6">
                                @php
                                    $selectedSalespersonId = (string) old(
                                        'salesperson_id',
                                        $lead->salesperson_id
                                    );
                                @endphp

                                <div class="form-floating">
                                    <select
                                        class="form-select @error('salesperson_id') is-invalid @enderror"
                                        id="assignTo"
                                        name="salesperson_id"
                                        required
                                    >
                                        <option value="">Select Salesperson</option>

                                        @foreach($salespeople as $salesperson)
                                            @php
                                                $isActive = strtolower(
                                                    trim((string) $salesperson->status)
                                                ) === 'active';

                                                $isCurrentAssignee =
                                                    (int) $salesperson->id ===
                                                    (int) $lead->salesperson_id;

                                                $isCurrentInactive =
                                                    !$isActive && $isCurrentAssignee;

                                                $isSelected =
                                                    $selectedSalespersonId ===
                                                    (string) $salesperson->id;
                                            @endphp

                                            {{--
                                                The controller provides active eligible users plus the
                                                existing assignee when that account is inactive.

                                                Other inactive users must never be rendered.
                                            --}}
                                            @continue(!$isActive && !$isCurrentAssignee)

                                            <option
                                                value="{{ $salesperson->id }}"
                                                @selected($isSelected)
                                                @if($isCurrentInactive) hidden @endif
                                            >
                                                {{ $salesperson->name }} ({{ $salesperson->role }})
                                            </option>
                                        @endforeach
                                    </select>

                                    <label for="assignTo">Assign To</label>
                                </div>

                                @error('salesperson_id')
                                    <div class="text-danger small mt-1">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <!-- Opportunity -->
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select class="form-select" id="opportunity" name="opportunity" required>
                                        <option value="" disabled {{ old('opportunity', $lead->opportunity) ? '' : 'selected' }}>Select Opportunity</option>
                                        <option value="50/50" {{ old('opportunity', $lead->opportunity) == '50/50' ? 'selected' : '' }}>50/50</option>
                                        <option value="High Chance" {{ old('opportunity', $lead->opportunity) == 'High Chance' ? 'selected' : '' }}>High Chance</option>
                                        <option value="Low Chance" {{ old('opportunity', $lead->opportunity) == 'Low Chance' ? 'selected' : '' }}>Low Chance</option>
                                        <option value="None" {{ old('opportunity', $lead->opportunity) == 'None' ? 'selected' : '' }}>None</option>
                                    </select>
                                    <label for="opportunity">Opportunity</label>
                                    @error('opportunity')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Remark (Full Width) -->
                        <div class="mb-4 mt-4">
                            <div class="form-floating">
                                <textarea class="form-control {{ request()->get('highlight') == 'remark' ? 'border-warning' : '' }}" id="remarks" name="remark" placeholder="Enter any additional remarks or notes" rows="3">{{ old('remark', $lead->remark) }}</textarea>
                                <label for="remarks">Remarks</label>
                                @error('remark')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Attachment (Full Width) -->
                        <div class="mb-4">
                            <label for="attachments" class="form-label">Attachment from Lead</label>
                            <div id="dropzone" class="dropzone" 
                                style="min-height: 150px; border: 2px dashed #ccc; padding: 20px; text-align: center; background-color: #f8f9fa;">
                                <p id="dropzone-message">Drag and drop files here, or click to browse</p>
                                <p>Supported formats: PDF, DOC, DOCX, JPG, JPEG, PNG (Max 10MB)</p>
                                <input type="file" class="form-control" id="attachments" name="attachments[]" multiple
                                    accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" style="display: none;">
                                <button type="button" class="btn btn-secondary"
                                        onclick="document.getElementById('attachments').click();">Choose File</button>
                            </div>
                            <div id="selected-files-list" class="mt-2"></div>
                            @error('attachments')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <a href="{{ request()->get('highlight') == 'remark' ? route('boss.leads.show', $lead->id) : route('boss.leads') }}" class="btn btn-secondary">Back</a>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </form>
                    @if ($lead->attachments->isNotEmpty())
                        <div class="mt-2" id="existing-attachments">
                            <strong>Existing Attachments:</strong>
                            <ul>
                                @foreach ($lead->attachments as $attachment)
                                    <li data-attachment-id="{{ $attachment->id }}">
                                        {{ basename($attachment->file_location) }} (<a href="{{ asset('storage/' . $attachment->file_location) }}" target="_blank">View</a>)
                                        <button type="button" class="btn btn-sm btn-danger delete-attachment" data-url="{{ route('boss.leads.attachments.delete', ['id' => $lead->id, 'attachment' => $attachment->id]) }}">Delete</button>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    const dropzone = document.getElementById('dropzone');
    const fileInput = document.getElementById('attachments');
    const message = document.getElementById('dropzone-message');
    const selectedFilesList = document.getElementById('selected-files-list');
    let selectedFiles = [];

    const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png', 'image/jpg'];
    const maxSize = 10240 * 1024; // 10MB

    // Prevent default behaviors for drag/drop
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, e => e.preventDefault());
        dropzone.addEventListener(eventName, e => e.stopPropagation());
    });

    // Highlight dropzone on drag over
    dropzone.addEventListener('dragover', () => {
        dropzone.style.borderColor = '#666';
        dropzone.style.backgroundColor = '#e9ecef';
    });

    // Remove highlight on drag leave
    dropzone.addEventListener('dragleave', () => {
        dropzone.style.borderColor = '#ccc';
        dropzone.style.backgroundColor = '#f8f9fa';
    });

    // Handle dropped files
    dropzone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropzone.style.borderColor = '#ccc';
        dropzone.style.backgroundColor = '#f8f9fa';

        const files = e.dataTransfer.files;
        processFiles(files);
    });

    // Update on file input change
    fileInput.addEventListener('change', () => {
        processFiles(fileInput.files);
    });

    function processFiles(files) {
        let validFiles = [];
        let sizeErrors = [];
        let typeErrors = [];

        Array.from(files).forEach(file => {
            if (file.size > maxSize) {
                sizeErrors.push(file.name);
                return;
            }
            if (!allowedTypes.includes(file.type)) {
                typeErrors.push(file.name);
                return;
            }
            if (!selectedFiles.some(f => f.name === file.name && f.size === file.size)) {
                validFiles.push(file);
            }
        });

        // Add valid files
        selectedFiles.push(...validFiles);

        // Show consolidated errors with SweetAlert
        if (sizeErrors.length > 0) {
            Swal.fire('Warning!', `${sizeErrors.join(', ')} exceed 10MB limit.`, 'warning');
        }
        if (typeErrors.length > 0) {
            Swal.fire('Warning!', `${typeErrors.join(', ')} not allowed. Only PDF, DOC, DOCX, JPG, JPEG, PNG permitted.`, 'warning');
        }

        updateFileInput();
        updateFileList();
    }

    function updateFileInput() {
        const dt = new DataTransfer();
        selectedFiles.forEach(file => dt.items.add(file));
        fileInput.files = dt.files;
    }

    function updateFileList() {
        selectedFilesList.innerHTML = '';
        if (selectedFiles.length > 0) {
            message.textContent = `${selectedFiles.length} file(s) selected`;
            const ul = document.createElement('ul');
            ul.classList.add('list-group');
            selectedFiles.forEach((file, index) => {
                const li = document.createElement('li');
                li.classList.add('list-group-item', 'd-flex', 'justify-content-between', 'align-items-center');
                li.textContent = file.name;
                const removeBtn = document.createElement('button');
                removeBtn.classList.add('btn', 'btn-sm', 'btn-danger');
                removeBtn.textContent = 'Remove';
                removeBtn.onclick = () => removeFile(index);
                li.appendChild(removeBtn);
                ul.appendChild(li);
            });
            selectedFilesList.appendChild(ul);
        } else {
            message.textContent = 'Drag and drop files here, or click to browse';
        }
    }

    function removeFile(index) {
        selectedFiles.splice(index, 1);
        updateFileInput();
        updateFileList();
    }

    document.querySelectorAll('.delete-attachment').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.dataset.url;
            const li = this.closest('li');

            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(url, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            li.remove();
                            if (document.querySelectorAll('#existing-attachments li').length === 0) {
                                document.getElementById('existing-attachments').innerHTML = '<p>No attachments available.</p>';
                            }
                            Swal.fire('Deleted!', 'Attachment has been deleted.', 'success');
                        } else {
                            Swal.fire('Error!', 'Failed to delete attachment', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire('Error!', 'Error deleting attachment', 'error');
                    });
                }
            });
        });
    });

    // Highlight remark if requested
    if (new URLSearchParams(window.location.search).get('highlight') === 'remark') {
        const remarksField = document.getElementById('remarks');
        remarksField.scrollIntoView({ behavior: 'smooth', block: 'center' });
        remarksField.focus();
        remarksField.classList.add('border-warning');
        setTimeout(() => {
            remarksField.classList.remove('border-warning');
        }, 3000);
    }
</script>
@endsection