<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cashier List</title>
    <link rel="stylesheet" href="{{ asset('css/owners.css') }}">
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
                <h1>Cashiers</h1>
            </div>
                <form method="GET" action="{{ route('cashiers.index') }}" class="search-form">
                    <input type="text" name="search" placeholder="Search cashiers..." value="{{ request('search') }}">
                    <button type="submit"><i class="fas fa-search"></i> Search</button>
                </form>
                <a href="javascript:void(0)" onclick="openAddCashierModal()" class="btn-add"><i class="fas fa-plus"></i> Add Cashier</a>
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
                    @foreach($cashiers as $cashier)
                        <tr>
                            <td><img src="{{ asset('storage/' . $cashier->profile_image) }}" alt="Profile Image" width="50"  height="50"></td>
                            <td>{{ $cashier->username }}</td>
                            <td>{{ $cashier->first_name }} {{ $cashier->middle_name }} {{ $cashier->last_name }}</td>
                            <td>{{ $cashier->gender }}</td>
                            <td>{{ $cashier->date_of_birth }}</td>
                            <td>{{ $cashier->email }}</td>
                            
                            <td>
                                <button class="btn-edit" onclick="openEditModal({{ $cashier }})"></i><i class="fas fa-edit"></i> </button>
                                <!-- <form action="{{ route('cashiers.destroy', $cashier->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-delete"><i class="fas fa-trash"></i> Delete</button>
                                </form> -->
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Cashier Modal -->
    <div id="addCashierModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAddCashierModal()">&times;</span>
            <h2>Add New Cashier</h2>
            <form method="POST" action="{{ route('cashiers.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required>
                </div>
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
                    <label for="gender">Gender:</label>
                    <select id="gender" name="gender" required>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="date_of_birth">Date of Birth:</label>
                    <input type="date" id="date_of_birth" name="date_of_birth" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="add_password" name="password" required>
                    <span class="eye-icon" onclick="togglePasswordVisibility()">
                        <i id="eyeIcon" class="fas fa-eye"></i>
                    </span>
                </div>
                <div class="form-group">
                    <label for="profile_image">Profile Image:</label>
                    <input type="file" id="profile_image" name="profile_image">
                </div>
                <button type="submit" class="btn-submit">Add Cashier</button>
            </form>
        </div>
    </div>

    <!-- Edit Cashier Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h2>Edit Cashier</h2>
            <form id="editCashierForm" method="POST" action="" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label for="edit_username">Username:</label>
                    <input type="text" id="edit_username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="edit_first_name">First Name:</label>
                    <input type="text" id="edit_first_name" name="first_name" required>
                </div>
                <div class="form-group">
                    <label for="edit_middle_name">Middle Name:</label>
                    <input type="text" id="edit_middle_name" name="middle_name">
                </div>
                <div class="form-group">
                    <label for="edit_last_name">Last Name:</label>
                    <input type="text" id="edit_last_name" name="last_name" required>
                </div>
                <div class="form-group">
                    <label for="edit_gender">Gender:</label>
                    <select id="edit_gender" name="gender" required>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_date_of_birth">Date of Birth:</label>
                    <input type="date" id="edit_date_of_birth" name="date_of_birth" required>
                </div>
                <div class="form-group">
                    <label for="edit_email">Email:</label>
                    <input type="email" id="edit_email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="edit_profile_image">Profile Image:</label>
                    <input type="file" id="edit_profile_image" name="profile_image">
                </div>
                <button type="submit" class="btn-submit">Update Cashier</button>
            </form>
        </div>
    </div>

    <style>
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
    </style>

    <script>
        function openAddCashierModal() {
            document.getElementById('addCashierModal').style.display = 'block';
        }

        function closeAddCashierModal() {
            document.getElementById('addCashierModal').style.display = 'none';
        }

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
                event.preventDefault(); // Prevents the default link behavior
                let submenu = item.nextElementSibling; // Targets the next element, which is the submenu
                submenu.classList.toggle('open'); // Toggles the open class
            });
        });

        function openEditModal(cashier) {
            const modal = document.getElementById('editModal');
            const form = document.getElementById('editCashierForm');
            
            // Set form values
            document.getElementById('edit_username').value = cashier.username;
            document.getElementById('edit_first_name').value = cashier.first_name;
            document.getElementById('edit_middle_name').value = cashier.middle_name || '';
            document.getElementById('edit_last_name').value = cashier.last_name;
            document.getElementById('edit_gender').value = cashier.gender;
            document.getElementById('edit_date_of_birth').value = cashier.date_of_birth;
            document.getElementById('edit_email').value = cashier.email;

            // Set the form action to the update route
            form.action = `/admin/cashiers/${cashier.id}`;

            // Show the modal
            modal.classList.add('show');
        }

        function closeEditModal() {
            const modal = document.getElementById('editModal');
            modal.classList.remove('show');
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const editModal = document.getElementById('editModal');
            if (event.target == editModal) {
                closeEditModal();
            }
        }

        // Add keyboard support for closing modal
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeEditModal();
            }
        });
    </script>
</body>

</html>
