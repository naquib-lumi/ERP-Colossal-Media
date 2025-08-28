@extends('layouts.app')

@section('title', 'Edit Lead')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="row g-6">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Edit Lead</h5>
                    <a href="{{ route('sales.leads') }}" class="btn btn-secondary">Back to Leads</a>
                </div>
                <div class="card-body">
                    <form action="{{ route('leads.update', $lead->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
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
                                    <input type="text" class="form-control" id="companyPhone" name="company_phone" placeholder="Enter company phone" value="{{ old('company_phone', $lead->company_phone) }}">
                                    <label for="companyPhone">Company Phone</label>
                                    @error('company_phone')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Website and Lead Name -->
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="url" class="form-control" id="website" name="website" placeholder="http://example.com" value="{{ old('website', $lead->website) }}">
                                    <label for="website">Website</label>
                                    @error('website')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="leadName" name="name" placeholder="Enter lead name" value="{{ old('name', $lead->name) }}" required>
                                    <label for="leadName">Lead Name</label>
                                    @error('name')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Lead Phone and Lead Email -->
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="leadPhone" name="phone" placeholder="Enter lead phone" value="{{ old('phone', $lead->phone) }}" required>
                                    <label for="leadPhone">Lead Phone</label>
                                    @error('phone')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="email" class="form-control" id="leadEmail" name="email" placeholder="Enter lead email" value="{{ old('email', $lead->email) }}" required>
                                    <label for="leadEmail">Lead Email</label>
                                    @error('email')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Assign To -->
                            <div class="col-md-6">
                                <div class="form-floating">
                                    @if (Auth::user()->hasRole('salesperson'))
                                       <input type="text" class="form-control" id="assignTo" name="salesperson_id"
                                            value="{{ Auth::user()->name }}" readonly>
                                        <input type="hidden" name="salesperson_id" value="{{ Auth::user()->id }}">
                                        <label for="assignTo">Assigned To (Me)</label>
                                        @error('salesperson_id')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    @else
                                        <select class="form-select" id="assignTo" name="salesperson_id" required>
                                            <option value="">Select Salesperson</option>
                                            @foreach ($salespeople as $salesperson)
                                                <option value="{{ $salesperson->id }}" {{ old('salesperson_id', $lead->salesperson_id) == $salesperson->id ? 'selected' : '' }}>
                                                    {{ $salesperson->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <label for="assignTo">Assign To</label>
                                        @error('salesperson_id')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    @endif
                                </div>
                            </div>

                            <!-- Sales Status and Opportunity -->
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select class="form-select" id="salesStatus" name="status" required>
                                        <option value="new" {{ old('status', $lead->status) == 'new' ? 'selected' : '' }}>New</option>
                                        <option value="accept" {{ old('status', $lead->status) == 'accept' ? 'selected' : '' }}>Accept</option>
                                        <option value="reject" {{ old('status', $lead->status) == 'reject' ? 'selected' : '' }}>Reject</option>
                                        <option value="followup" {{ old('status', $lead->status) == 'followup' ? 'selected' : '' }}>Followup</option>                            
                                    </select>
                                    <label for="salesStatus">Sales Status</label>
                                    @error('status')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
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
                                <textarea class="form-control" id="remarks" name="remark" placeholder="Enter any additional remarks or notes" rows="3">{{ old('remark', $lead->remark) }}</textarea>
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
                                <p>Supported formats: PDF, DOC, JPG, PNG (Max 10MB)</p>
                                <input type="file" class="form-control" id="attachments" name="attachments[]" multiple
                                    accept=".pdf,.doc,.jpg,.png" style="display: none;">
                                <button type="button" class="btn btn-secondary"
                                        onclick="document.getElementById('attachments').click();">Choose File</button>
                            </div>
                            <div id="selected-files-list" class="mt-2"></div>
                            @error('attachments')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary">Update</button>
                    </form>
                    @if ($lead->attachments->isNotEmpty())
                        <div class="mt-2" id="existing-attachments">
                            <strong>Existing Attachments:</strong>
                            <ul>
                                @foreach ($lead->attachments as $attachment)
                                    <li data-attachment-id="{{ $attachment->id }}">
                                        {{ basename($attachment->file_location) }} (<a href="{{ asset('storage/' . $attachment->file_location) }}" target="_blank">View</a>)
                                        <button type="button" class="btn btn-sm btn-danger delete-attachment" data-url="{{ route('leads.attachments.delete', ['id' => $lead->id, 'attachment' => $attachment->id]) }}" onclick="return confirm('Are you sure?')">Delete</button>
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
        dropzone.style.borderColor = '#ccc';
        dropzone.style.backgroundColor = '#f8f9fa';

        const files = e.dataTransfer.files;
        addFiles(files);
    });

    // Update on file input change
    fileInput.addEventListener('change', () => {
        addFiles(fileInput.files);
        fileInput.value = ''; // Clear input to allow re-selecting same files
    });

    function addFiles(files) {
        Array.from(files).forEach(file => {
            if (!selectedFiles.some(f => f.name === file.name && f.size === file.size)) {
                selectedFiles.push(file);
            }
        });
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

            if (!confirm('Are you sure?')) return;

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
                } else {
                    alert('Failed to delete attachment');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error deleting attachment');
            });
        });
    });
</script>
@endsection