@extends('layouts.app')

@section('title', 'Manage Users')


@section('content')
    <div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Manage Users</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="fas fa-plus me-2"></i>Add New User
        </button>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                <div class="w-25">
                    <input type="text" class="form-control" placeholder="Search users..." id="searchUsers">
                </div>
                <div class="d-flex gap-2">
                    <select class="form-select form-select-sm" id="filterRole">
                        <option value="">All Roles</option>
                        <option value="admin">Admin</option>
                        <option value="salesperson">Salesperson</option>
                        <option value="head-salesperson">Head Salesperson</option>
                        <option value="head-artist">Head Artist</option>
                        <option value="artist">Artist</option>
                        <option value="installation">Installation</option>
                    </select>
                    <select class="form-select form-select-sm" id="filterStatus">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Role</th>
                            <th>Email</th>
                            <th>Contact No.</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="rounded-circle me-2" style="width: 32px; height: 32px;">
                                        {{ $user->name }}
                                    </div>
                                </td>
                                <td>{{ $user->getDisplayRoleAttribute() }}</td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->contact_number ?? 'N/A' }}</td>
                                <td>
                                    <span class="badge {{ $user->status_label === 'Active' ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $user->status_label }}
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#editUserModal{{ $user->id }}">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form action="{{ route('admin.user.disable', $user->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to {{ $user->status_label === 'Active' ? 'disable' : 'enable' }} this user?');">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-outline-{{ $user->status_label === 'Active' ? 'danger' : 'success' }}">
                                            <i class="fas fa-{{ $user->status_label === 'Active' ? 'ban' : 'check' }}"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>

                            <!-- Edit User Modal -->
                            <div class="modal fade" id="editUserModal{{ $user->id }}" tabindex="-1" aria-labelledby="editUserModalLabel{{ $user->id }}" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="editUserModalLabel{{ $user->id }}">Edit User: {{ $user->name }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form action="{{ route('admin.user.update', $user->id) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label for="name{{ $user->id }}" class="form-label">Name</label>
                                                    <input type="text" class="form-control" id="name{{ $user->id }}" name="name" value="{{ $user->name }}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="email{{ $user->id }}" class="form-label">Email</label>
                                                    <input type="email" class="form-control" id="email{{ $user->id }}" name="email" value="{{ $user->email }}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="contact_number{{ $user->id }}" class="form-label">Contact Number</label>
                                                    <input type="text" class="form-control" id="contact_number{{ $user->id }}" name="contact_number" value="{{ $user->contact_number }}">
                                                </div>
                                                <div class="mb-3">
                                                    <label for="role{{ $user->id }}" class="form-label">Role</label>
                                                    <select class="form-select" id="role{{ $user->id }}" name="role" required>
                                                        <option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>Admin</option>
                                                        <option value="salesperson" {{ $user->role === 'salesperson' ? 'selected' : '' }}>Salesperson</option>
                                                        <option value="head-salesperson" {{ $user->role === 'head-salesperson' ? 'selected' : '' }}>Head Salesperson</option>
                                                        <option value="head-artist" {{ $user->role === 'head-artist' ? 'selected' : '' }}>Head Artist</option>
                                                        <option value="artist" {{ $user->role === 'artist' ? 'selected' : '' }}>Artist</option>
                                                        <option value="installation" {{ $user->role === 'installation' ? 'selected' : '' }}>Installation</option>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="password{{ $user->id }}" class="form-label">New Password (optional)</label>
                                                    <input type="password" class="form-control" id="password{{ $user->id }}" name="password">
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">No users found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-3 border-top d-flex justify-content-between align-items-center">
                <small>Showing {{ $users->firstItem() }} to {{ $users->lastItem() }} of {{ $users->total() }} results</small>
                <nav aria-label="Page navigation">
                    <ul class="pagination pagination-sm mb-0">
                        {{ $users->links() }}
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('admin.user.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="contact_number" class="form-label">Contact Number</label>
                            <input type="text" class="form-control" id="contact_number" name="contact_number">
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="admin">Admin</option>
                                <option value="salesperson">Salesperson</option>
                                <option value="head-salesperson">Head Salesperson</option>
                                <option value="head-artist">Head Artist</option>
                                <option value="artist">Artist</option>
                                <option value="installation">Installation</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchUsers');
            const filterRole = document.getElementById('filterRole');
            const filterStatus = document.getElementById('filterStatus');

            function filterUsers() {
                const searchTerm = searchInput.value.toLowerCase();
                const roleFilter = filterRole.value;
                const statusFilter = filterStatus.value;

                document.querySelectorAll('tbody tr').forEach(row => {
                    const name = row.cells[0].textContent.toLowerCase();
                    const role = row.cells[1].textContent.toLowerCase();
                    const status = row.cells[4].querySelector('.badge').textContent.toLowerCase();

                    const matchesSearch = name.includes(searchTerm);
                    const matchesRole = !roleFilter || role.includes(roleFilter.toLowerCase());
                    const matchesStatus = !statusFilter || status.includes(statusFilter.toLowerCase());

                    row.style.display = matchesSearch && matchesRole && matchesStatus ? '' : 'none';
                });
            }

            searchInput.addEventListener('input', filterUsers);
            filterRole.addEventListener('change', filterUsers);
            filterStatus.addEventListener('change', filterUsers);
        });
    </script>
@endsection
