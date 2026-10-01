<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff List</title>
    <link rel="stylesheet" href="{{ asset('css/staff.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>

<body>
    <div class="container">
        <div class="sidebar">
            <div class="sidebar-logo">
            <img src="{{ asset('images/kingsalon.png') }}" alt="Admin Logo" style="width: 200px; height: auto;">
            </div>
            <ul>
                <li><a href="{{ route('admin.dashboard') }}"><i class="fas fa-home"></i> Dashboard</a></li>
                <!-- <li><a href="{{ route('appointments.index') }}"><i class="fas fa-calendar"></i> Appointments</a></li> -->
                <li><a href="{{ route('services.index') }}"><i class="fas fa-cut"></i> Services</a></li>
                <li><a href="{{ route('clients.index') }}"><i class="fas fa-user"></i> Clients</a></li>
                <li class="has-submenu">
                    <a href="#" class="submenu-toggle"><i class="fas fa-users"></i> Members</a>
                    <ul class="submenu">
                        <li><a href="{{ route('owners.index') }}"><i class="fas fa-user"></i> Owner</a></li>
                        <li><a href="{{ route('managers.index') }}"> <i class="fas fa-user"></i> Manager</a></li>
                        <li><a href="{{ route('staff.index') }}"> <i class="fas fa-user"></i> Staff</a></li>
                        <li><a href="{{ route('cashiers.index') }}"> <i class="fas fa-user"></i> Cashier</a></li>
                    </ul>
                </li>
                <li><a href="{{ route('admin.products.index') }}"><i class="fas fa-box"></i>Inventory</a></li>
                <li><a href="{{ route('admin.history') }}"><i class="fas fa-history"></i> Front Desk Transactions</a></li>
                 <!-- <li><a href="{{ route('reviews.index') }}"> <i class="fas fa-star"></i> Reviews</a></li> -->
            </ul>
        </div>

        <div class="main-content">
            <header>
            <div class="header-content">
                    <h1>Staff</h1>
            </div>
                <form method="GET" action="{{ route('staff.index') }}" class="search-form">
                    <input type="text" name="search" placeholder="Search staff..." value="{{ request('search') }}">
                    <button type="submit"><i class="fas fa-search"></i> Search</button>
                </form>
                <a href="javascript:void(0)" onclick="openAddStaffModal()" class="btn-add"><i class="fas fa-plus"></i>
                    Add Staff</a>
            </header>

            <table>
                <thead>
                    <tr>
                        <th>Profile Image</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Username</th>
                        <th>Gender</th>
                        <th>Date of Birth</th>
                        <th>Specialization</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($staff as $member)
                        <tr>
                            <td>
                                <img src="{{ asset('storage/' . $member->profile_picture) }}" alt="Profile Image" width="50"
                                    height="50">
                            </td>
                            <td>{{ $member->first_name }} {{ $member->last_name }}</td>
                            <td>{{ $member->email }}</td>
                            <td>{{ $member->username }}</td>
                            <td>{{ $member->gender }}</td>
                            <td>{{ date('Y-m-d', strtotime($member->date_of_birth)) }}</td>
                            <td>
                                @foreach($member->categories as $category)
                                    {{ $category->name }}<br>
                                @endforeach
                            </td>
                            <td>
                                <span class="status-badge {{ $member->is_active ? 'active' : 'inactive' }}">
                                    {{ $member->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn-edit" onclick="openEditModal({{ $member }})">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-reset" onclick="openResetPasswordModal({{ $member->id }})">
                                        <i class="fas fa-key"></i>
                                    </button>
                                    <form action="{{ route('staff.toggle-status', $member->id) }}" method="POST" style="display:inline;">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn-toggle {{ $member->is_active ? 'btn-deactivate' : 'btn-activate' }}" onclick="return confirm('Are you sure you want to {{ $member->is_active ? 'deactivate' : 'activate' }} this staff account?')">
                                            <i class="fas {{ $member->is_active ? 'fa-user-slash' : 'fa-user-check' }}"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Staff Modal -->
    <div id="addStaffModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAddStaffModal()">&times;</span>
            <h2>Add New Staff</h2>
            <form id="addStaffForm" method="POST" action="{{ route('staff.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label for="first_name">First Name:</label>
                    <input type="text" id="first_name" name="first_name" required>
                </div>
                <div class="form-group">
                    <label for="middle_name">Middle Name:</label>
                    <input type="text" id="middle_name" name="middle_name">
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name:</label>
                    <input type="text" id="last_name" name="last_name" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="gender">Gender:</label>
                    <select id="gender" name="gender" required>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="date_of_birth">Date of Birth:</label>
                    <input type="date" id="date_of_birth" name="date_of_birth" required>
                </div>
                <div class="form-group">
                    <label for="profile_picture">Profile Picture:</label>
                    <input type="file" id="profile_picture" name="profile_picture" accept="image/*">
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="categories">Specializations:</label>
                    <select id="categories" name="categories[]" multiple required>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn-submit">Add Staff</button>
            </form>
        </div>
    </div>


    <!-- Edit Staff Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h2>Edit Staff</h2>
            <form id="editStaffForm" method="POST" action="" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label for="first_name">First Name:</label>
                    <input type="text" id="edit_first_name" name="first_name" required>
                </div>
                <div class="form-group">
                    <label for="middle_name">Middle Name:</label>
                    <input type="text" id="edit_middle_name" name="middle_name">
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name:</label>
                    <input type="text" id="edit_last_name" name="last_name" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="edit_email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="edit_username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="gender">Gender:</label>
                    <select id="edit_gender" name="gender" required>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="date_of_birth">Date of Birth:</label>
                    <input type="date" id="edit_date_of_birth" name="date_of_birth">
                </div>
                <div class="form-group">
                    <label for="categories">Specializations:</label>
                    <select id="categories" name="categories[]" multiple required>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="profile_picture">Profile Picture:</label>
                    <input type="file" id="edit_profile_picture" name="profile_picture" accept="image/*">
                </div>
                <button type="submit" class="btn-submit">Update Staff</button>
            </form>
        </div>
    </div>

    <!-- Reset Password Modal -->
    <div id="resetPasswordModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeResetPasswordModal()">&times;</span>
            <h2>Reset Password</h2>
            <form id="resetPasswordForm" method="POST" action="">
                @csrf
                @method('PATCH')
                <div class="form-group">
                    <label for="new_password">New Password:</label>
                    <input type="password" id="new_password" name="new_password" required minlength="8">
                </div>
                <div class="form-group">
                    <label for="new_password_confirmation">Confirm Password:</label>
                    <input type="password" id="new_password_confirmation" name="new_password_confirmation" required minlength="8">
                </div>
                <button type="submit" class="btn-submit">Reset Password</button>
            </form>
        </div>
    </div>

    <style>
        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-reset {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-toggle {
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-deactivate {
            background-color: #f44336;
            color: white;
        }

        .btn-activate {
            background-color: #2196F3;
            color: white;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-badge.active {
            background-color: #4CAF50;
            color: white;
        }

        .status-badge.inactive {
            background-color: #f44336;
            color: white;
        }
    </style>

    <script>
        function openEditModal(staff) {
            document.getElementById('edit_first_name').value = staff.first_name;
            document.getElementById('edit_middle_name').value = staff.middle_name;
            document.getElementById('edit_last_name').value = staff.last_name;
            document.getElementById('edit_email').value = staff.email;
            document.getElementById('edit_username').value = staff.username;
            document.getElementById('edit_gender').value = staff.gender;
            document.getElementById('edit_date_of_birth').value = staff.date_of_birth;
            document.getElementById('editStaffForm').action = '/admin/staff/' + staff.id;
            document.getElementById('editModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        function openAddStaffModal() {
            document.getElementById('addStaffModal').style.display = 'block';
        }

        function closeAddStaffModal() {
            document.getElementById('addStaffModal').style.display = 'none';
        }

        function openResetPasswordModal(staffId) {
            const form = document.getElementById('resetPasswordForm');
            form.action = `/admin/staff/${staffId}/reset-password`;
            document.getElementById('resetPasswordModal').style.display = 'block';
        }

        function closeResetPasswordModal() {
            document.getElementById('resetPasswordModal').style.display = 'none';
            document.getElementById('resetPasswordForm').reset();
        }

        // Add form submission handler
        document.getElementById('resetPasswordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('new_password_confirmation').value;
            
            if (newPassword !== confirmPassword) {
                alert('Passwords do not match!');
                return;
            }
            
            if (newPassword.length < 8) {
                alert('Password must be at least 8 characters long!');
                return;
            }
            
            this.submit();
        });

        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>

</html>