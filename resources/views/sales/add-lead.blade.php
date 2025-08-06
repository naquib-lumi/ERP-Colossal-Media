@extends('layouts.app')

@section('title', 'Add Lead')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="row g-6">
        <div class="col-md-12">
            <div class="card">
                <h5 class="card-header">Add Lead</h5>
                <div class="card-body">
                    <a href="{{ route('sales.leads') }}" class="btn btn-secondary mb-4">Back to Leads</a>
                    <form action="{{ route('leads.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-4">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="companyName" name="company_name" placeholder="Enter company name" value="{{ old('company_name') }}" required>
                                <label for="companyName">Company Name</label>
                                @error('company_name')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="mb-4">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="companyPhone" name="company_phone" placeholder="Enter company phone" value="{{ old('company_phone') }}">
                                <label for="companyPhone">Company Phone</label>
                                @error('company_phone')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="mb-4">
                            <div class="form-floating">
                                <input type="url" class="form-control" id="website" name="website" placeholder="http://example.com" value="{{ old('website') }}">
                                <label for="website">Website</label>
                                @error('website')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="mb-4">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="leadName" name="name" placeholder="Enter lead name" value="{{ old('name') }}" required>
                                <label for="leadName">Lead Name</label>
                                @error('name')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="mb-4">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="leadPhone" name="phone" placeholder="Enter lead phone" value="{{ old('phone') }}" required>
                                <label for="leadPhone">Lead Phone</label>
                                @error('phone')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="mb-4">
                            <div class="form-floating">
                                <input type="email" class="form-control" id="leadEmail" name="email" placeholder="Enter lead email" value="{{ old('email') }}" required>
                                <label for="leadEmail">Lead Email</label>
                                @error('email')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
     
                    <div class="mb-4">
                        <div class="form-floating">
                          <select class="form-select" id="assignTo" name="salesperson_id" required>
                            <option value="">Select Artist</option>
                            @foreach ($artists as $artist)
                                @php
                                    $selected = old('salesperson_id', $selectedArtistId ?? '') == $artist->id ? 'selected' : '';
                                @endphp
                                <option value="{{ $artist->id }}" {{ $selected }}>
                                    {{ $artist->name }}
                                </option>
                            @endforeach
                        </select>

                            <label for="assignTo">Assign To</label>
                            @error('salesperson_id')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                        <div class="mb-4">
                            <div class="form-floating">
                                <input type="date" class="form-control" id="leadDate" name="date" value="{{ old('date') }}">
                                <label for="leadDate">Lead Date (dd/mm/yy)</label>
                                @error('date')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="mb-4">
                            <div class="form-floating">
                                <select class="form-select" id="salesStatus" name="status" required>
                                    <option value="">Select Status</option>
                                    <option value="accepted" {{ old('status') == 'accepted' ? 'selected' : '' }}>Accept</option>
                                    <option value="rejected" {{ old('status') == 'rejected' ? 'selected' : '' }}>Reject</option>
                                    <option value="followup" {{ old('status') == 'followup' ? 'selected' : '' }}>Followup</option>
                                </select>
                                <label for="salesStatus">Sales Status</label>
                                @error('status')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="mb-4">
                        <div class="form-floating">
                            <select class="form-select" id="opportunity" name="opportunity">
                                <option value="" disabled {{ old('opportunity') ? '' : 'selected' }}>Select Opportunity</option>
                                <option value="50/50" {{ old('opportunity') == '50/50' ? 'selected' : '' }}>50/50</option>
                                <option value="High Chance" {{ old('opportunity') == 'High Chance' ? 'selected' : '' }}>High Chance</option>
                                <option value="Low Chance" {{ old('opportunity') == 'Low Chance' ? 'selected' : '' }}>Low Chance</option>
                                <option value="None" {{ old('opportunity') == 'None' ? 'selected' : '' }}>None</option>
                            </select>
                            <label for="opportunity">Opportunity</label>
                            @error('opportunity')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        </div>
                        <div class="mb-4">
                            <div class="form-floating">
                                <textarea class="form-control" id="remarks" name="remark" placeholder="Enter any additional remarks or notes" rows="3">{{ old('remark') }}</textarea>
                                <label for="remarks">Remarks</label>
                                @error('remark')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="mb-4">
    <label for="attachments" class="form-label">Attachment from Lead</label>
    <div id="dropzone" class="dropzone" 
         style="min-height: 150px; border: 2px dashed #ccc; padding: 20px; text-align: center;">
        <p id="dropzone-message">Drag and drop files here, or click to browse</p>
        <p>Supported formats: PDF, DOC, JPG, PNG (Max 10MB)</p>
        <input type="file" class="form-control" id="attachments" name="attachments[]" multiple
               accept=".pdf,.doc,.jpg,.png" style="display: none;">
        <button type="button" class="btn btn-secondary"
                onclick="document.getElementById('attachments').click();">Choose file</button>
    </div>
    @error('attachments')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<script>
    const dropzone = document.getElementById('dropzone');
    const fileInput = document.getElementById('attachments');
    const message = document.getElementById('dropzone-message');

    // Prevent default behaviors for drag/drop
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, e => e.preventDefault());
        dropzone.addEventListener(eventName, e => e.stopPropagation());
    });

    // Highlight dropzone on drag over
    dropzone.addEventListener('dragover', () => {
        dropzone.style.borderColor = '#666';
        dropzone.style.backgroundColor = '#f8f8f8';
    });

    // Remove highlight on drag leave
    dropzone.addEventListener('dragleave', () => {
        dropzone.style.borderColor = '#ccc';
        dropzone.style.backgroundColor = 'transparent';
    });

    // Handle dropped files
    dropzone.addEventListener('drop', (e) => {
        dropzone.style.borderColor = '#ccc';
        dropzone.style.backgroundColor = 'transparent';

        const files = e.dataTransfer.files;
        fileInput.files = files;

        message.textContent = files.length + ' file(s) selected';
    });

    // Update message on file input change
    fileInput.addEventListener('change', () => {
        message.textContent = fileInput.files.length + ' file(s) selected';
    });
</script>

                        <button type="submit" class="btn btn-primary">Save</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection