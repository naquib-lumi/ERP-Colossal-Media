<div class="modal fade" id="meetingModal" tabindex="-1" aria-labelledby="meetingModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="meetingModalLabel">Add Meeting</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="meetingForm">
                    @csrf
                    <div class="mb-3">
                        <label for="meetingTitle" class="form-label">Title</label>
                        <input type="text" class="form-control" id="meetingTitle" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="meetingStartTime" class="form-label">Start Date & Time</label>
                        <input type="datetime-local" class="form-control" id="meetingStartTime" name="start_time" required>
                    </div>
                    <div class="mb-3">
                        <label for="meetingDuration" class="form-label">Duration (minutes)</label>
                        <input type="number" class="form-control" id="meetingDuration" name="duration" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="type" id="typeOnline" value="online" checked>
                            <label class="form-check-label" for="typeOnline">Online</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="type" id="typeOffline" value="offline">
                            <label class="form-check-label" for="typeOffline">Offline</label>
                        </div>
                    </div>
                    <div class="mb-3" id="onlineUrl" style="display: block;">
                        <label for="meetingUrl" class="form-label">URL</label>
                        <input type="url" class="form-control" id="meetingUrl" name="url">
                    </div>
                    <div class="mb-3" id="offlineLocation" style="display: none;">
                        <label for="meetingLocation" class="form-label">Location</label>
                        <input type="text" class="form-control" id="meetingLocation" name="location">
                    </div>
                    <div class="mb-3">
                        <label for="meetingNote" class="form-label">Description</label>
                        <textarea class="form-control" id="meetingNote" name="note" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveMeetingBtn">Save Meeting</button>
            </div>
        </div>
    </div>
</div>