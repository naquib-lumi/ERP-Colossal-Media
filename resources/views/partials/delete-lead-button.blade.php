{{-- Delete lead button. Params: $lead, $url (DELETE endpoint, returns JSON), $redirect (lead list) --}}
<button type="button" class="btn btn-outline-danger btn-sm" id="deleteLeadBtn">
    <i class="bx bxs-trash me-1"></i> DELETE LEAD – {{ $lead->id }}
</button>

<script>
    document.getElementById('deleteLeadBtn').addEventListener('click', function () {
        Swal.fire({
            title: 'Delete this lead?',
            text: 'Its notes, meetings, reminders and attachments will also be deleted. This cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Delete',
            confirmButtonColor: '#d33',
        }).then(function (result) {
            if (!result.isConfirmed) return;

            fetch(@json($url), {
                method: 'DELETE',
                headers: {
                    // The layout only adds the csrf-token meta tag on some pages.
                    'X-CSRF-TOKEN': @json(csrf_token()),
                    'Accept': 'application/json',
                },
            })
                .then(function (res) { return res.json().then(function (body) { return { ok: res.ok, body: body }; }); })
                .then(function (r) {
                    if (r.ok) {
                        Swal.fire('Deleted', r.body.message || 'Lead deleted.', 'success')
                            .then(function () { window.location.href = @json($redirect); });
                    } else {
                        Swal.fire('Cannot delete', r.body.error || r.body.message || 'Something went wrong.', 'error');
                    }
                })
                .catch(function () {
                    Swal.fire('Error', 'Could not reach the server. Please try again.', 'error');
                });
        });
    });
</script>
