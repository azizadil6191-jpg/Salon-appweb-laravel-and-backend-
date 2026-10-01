<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner List</title>
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
                <h1>Owner List</h1>
            </div>
                <form method="GET" action="{{ route('owners.index') }}" class="search-form">
                    <input type="text" name="search" placeholder="Search owners..." value="{{ request('search') }}">
                    <button type="submit"><i class="fas fa-search"></i> Search</button>
                </form>
                <a href="javascript:void(0)" onclick="openAddOwnerModal()" class="btn-add"><i class="fas fa-plus"></i>
                    Add Owner</a>
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
                        <!-- <th>Actions</th> -->
                    </tr>
                </thead>
                <tbody>
                    @foreach($owners as $owner)
                        <tr>
                            <td>
                                <img src="{{ asset('storage/' . $owner->profile_picture) }}" alt="Profile Image" width="50"
                                    height="50">
                            </td>
                            <td>{{ $owner->fullname }}</td>
                            <td>{{ $owner->email }}</td>
                            <td>{{ $owner->username }}</td>
                            <td>{{ $owner->number }}</td>
                            <td>{{ $owner->date_of_birth }}</td>
                            <!-- <td>
                                <button class="btn-edit" onclick="openEditModal({{ $owner }})"><i class="fas fa-edit"></i>
                                    Edit</button>
                                <form action="{{ route('owners.destroy', $owner->id) }}" method="POST"
                                    style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-delete"><i class="fas fa-trash"></i> Delete</button>
                                </form>
                            </td> -->
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Owner Modal -->
    <div id="addOwnerModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAddOwnerModal()">&times;</span>
            <h2>Add New Owner</h2>
            <form id="addOwnerForm" method="POST" action="{{ route('owners.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label for="fullname">Full Name:</label>
                    <input type="text" id="add_fullname" name="fullname" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="add_email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="add_username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="number">Phone Number:</label>
                    <input type="text" id="add_number" name="number" required>
                </div>
                <div class="form-group">
                    <label for="date_of_birth">Date of Birth:</label>
                    <input type="date" id="add_date_of_birth" name="date_of_birth" required>
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


                <button type="submit" class="btn-submit">Add Owner</button>
            </form>
        </div>
    </div>


    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h2>Edit Owner</h2>
            <form id="editOwnerForm" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label for="fullname">Full Name:</label>
                    <input type="text" id="fullname" name="fullname" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="number">Phone Number:</label>
                    <input type="text" id="number" name="number" required>
                </div>
                <div class="form-group">
                    <label for="date_of_birth">Date of Birth:</label>
                    <input type="date" id="date_of_birth" name="date_of_birth" required>
                </div>
                <button type="submit" class="btn-submit">Update Owner</button>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(owner) {
            document.getElementById('fullname').value = owner.fullname;
            document.getElementById('email').value = owner.email;
            document.getElementById('number').value = owner.number;
            document.getElementById('date_of_birth').value = owner.date_of_birth;

            // Set the form action to the update route
            document.getElementById('editOwnerForm').action = '/owners/' + owner.id;

            // Show the modal
            document.getElementById('editModal').style.display = "block";
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = "none";
        }

        function openAddOwnerModal() {
            document.getElementById('addOwnerModal').style.display = "block";
        }

        function closeAddOwnerModal() {
            document.getElementById('addOwnerModal').style.display = "none";
        }

        window.onclick = function (event) {
            if (event.target == document.getElementById('addOwnerModal')) {
                closeAddOwnerModal();
            }
            if (event.target == document.getElementById('editModal')) {
                closeEditModal();
            }
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

    </script>
</body>

</html>