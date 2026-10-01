<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager List</title>
    <link rel="stylesheet" href="{{ asset('css/managers.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        /* Add these styles at the top of your existing styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal.show {
            display: flex !important;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: #fefefe;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            position: relative;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            animation: modalFadeIn 0.3s ease-out;
            margin: 0 auto;
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #444;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }

        .btn-submit {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1em;
            width: 100%;
            transition: background-color 0.2s;
            margin-top: 10px;
        }

        .btn-submit:hover {
            background: #45a049;
        }

        .btn-edit {
            background: #2196F3;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 5px;
        }

        .btn-edit:hover {
            background: #1976D2;
        }
    </style>
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
                <h1>Manager List</h1>
            </div>
                <form method="GET" action="{{ route('managers.index') }}" class="search-form">
                    <input type="text" name="search" placeholder="Search managers..." value="{{ request('search') }}">
                    <button type="submit"><i class="fas fa-search"></i> Search</button>
                </form>
                <a href="javascript:void(0)" onclick="openAddManagerModal()" class="btn-add"><i class="fas fa-plus"></i>
                    Add Manager</a>
            </header>

            <table>
                <thead>
                    <tr>
                        <th>Profile Image</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Username</th>
                        <th>Phone Number</th>
                        <th>Date of Birth</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($managers as $manager)
                        <tr>
                            <td>
                                @if($manager->profile_picture)
                                    <img src="{{ asset('storage/' . $manager->profile_picture) }}" alt="Profile Image"
                                        width="50" height="50">
                                @else
                                    <img src="{{ asset('images/default-profile.png') }}" alt="Default Image" width="50"
                                        height="50">
                                @endif
                            </td>
                            <td>{{ $manager->fullname }}</td>
                            <td>{{ $manager->email }}</td>
                            <td>{{ $manager->username }}</td>
                            <td>{{ $manager->phone }}</td>
                            <td>{{ $manager->dateofbirth }}</td>
                            <td>
                                <button class="btn-edit" onclick="openEditModal({{ $manager }})"><i class="fas fa-edit"></i>
                                    </button>
                                <form action="{{ route('managers.destroy', $manager->id) }}" method="POST"
                                    style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <!-- <button type="submit" class="btn-delete"><i class="fas fa-trash"></i> Delete</button> -->
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Manager Modal -->
    <!-- Add Manager Modal -->
    <div id="addManagerModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAddManagerModal()">&times;</span>
            <h2>Add New Manager</h2>
            <form id="addManagerForm" method="POST" action="{{ route('managers.store') }}"
                enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label for="fullname">Full Name:</label>
                    <input type="text" id="add_fullname" name="fullname" value="{{ old('fullname') }}" required>
                </div>
                <div class="form-group">
                    <label for="nickname">Nickname:</label>
                    <input type="text" id="add_nickname" name="nickname" value="{{ old('nickname') }}">
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="add_email" name="email" value="{{ old('email') }}" required>
                </div>
                <div class="form-group">
                    <label for="number">Phone Number:</label>
                    <input type="text" id="add_number" name="phone" value="{{ old('number') }}" required>
                </div>
                <div class="form-group">
                    <label for="date_of_birth">Date of Birth:</label>
                    <input type="date" id="add_date_of_birth" name="dateofbirth" value="{{ old('date_of_birth') }}"
                        required>
                </div>
                <div class="form-group">
                    <label for="profile_picture">Profile Image:</label>
                    <input type="file" id="add_profile_picture" name="profile_picture" accept="image/*">
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="add_password" name="password" required>
                    <span class="eye-icon" onclick="togglePasswordVisibility()">
                        <i id="eyeIcon" class="fas fa-eye"></i>
                    </span>
                </div>

                <button type="submit" class="btn-submit">Add Manager</button>
            </form>
        </div>
    </div>


    <!-- Edit Manager Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h2>Edit Manager</h2>
            <form id="editManagerForm" method="POST" action="" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label for="edit_fullname">Full Name:</label>
                    <input type="text" id="edit_fullname" name="fullname" required>
                </div>
                <div class="form-group">
                    <label for="edit_nickname">Nickname:</label>
                    <input type="text" id="edit_nickname" name="nickname">
                </div>
                <div class="form-group">
                    <label for="edit_email">Email:</label>
                    <input type="email" id="edit_email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="edit_phone">Phone Number:</label>
                    <input type="text" id="edit_phone" name="phone" required>
                </div>
                <div class="form-group">
                    <label for="edit_dateofbirth">Date of Birth:</label>
                    <input type="date" id="edit_dateofbirth" name="dateofbirth" required>
                </div>
                <div class="form-group">
                    <label for="edit_profile_picture">Profile Image:</label>
                    <input type="file" id="edit_profile_picture" name="profile_picture" accept="image/*">
                </div>
                <button type="submit" class="btn-submit">Update Manager</button>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(manager) {
            const modal = document.getElementById('editModal');
            const form = document.getElementById('editManagerForm');
            
            // Set form values
            document.getElementById('edit_fullname').value = manager.fullname;
            document.getElementById('edit_nickname').value = manager.nickname || '';
            document.getElementById('edit_email').value = manager.email;
            document.getElementById('edit_phone').value = manager.phone;
            document.getElementById('edit_dateofbirth').value = manager.dateofbirth;

            // Set the form action to the update route
            form.action = `/admin/managers/${manager.id}`;

            // Show the modal
            modal.classList.add('show');
        }

        function closeEditModal() {
            const modal = document.getElementById('editModal');
            modal.classList.remove('show');
        }

        function openAddManagerModal() {
            const modal = document.getElementById('addManagerModal');
            modal.classList.add('show');
        }

        function closeAddManagerModal() {
            const modal = document.getElementById('addManagerModal');
            modal.classList.remove('show');
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const addModal = document.getElementById('addManagerModal');
            const editModal = document.getElementById('editModal');
            
            if (event.target == addModal) {
                closeAddManagerModal();
            }
            if (event.target == editModal) {
                closeEditModal();
            }
        }

        // Add keyboard support for closing modals
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeAddManagerModal();
                closeEditModal();
            }
        });

        function togglePasswordVisibility() {
            var passwordField = document.getElementById("add_password");
            var eyeIcon = document.getElementById("eyeIcon");

            if (passwordField.type === "password") {
                passwordField.type = "text";
                eyeIcon.classList.remove("fa-eye");
                eyeIcon.classList.add("fa-eye-slash");
            } else {
                passwordField.type = "password";
                eyeIcon.classList.remove("fa-eye-slash");
                eyeIcon.classList.add("fa-eye");
            }
        }

        document.querySelectorAll('.submenu-toggle').forEach(item => {
            item.addEventListener('click', event => {
                event.preventDefault();
                let submenu = item.nextElementSibling;
                submenu.classList.toggle('open');
            });
        });
    </script>
</body>

</html>