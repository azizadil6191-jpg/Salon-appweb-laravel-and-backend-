<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client List</title>
    <link rel="stylesheet" href="{{ asset('css/clients.css') }}">

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
                    <h1>Clients</h1>
                </div>
                @if(session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                @endif
                <form method="GET" action="{{ route('clients.index') }}" class="search-form">
                    <input type="text" name="search" placeholder="Search clients..." value="{{ request('search') }}">
                    <button type="submit"><i class="fas fa-search"></i> Search</button>
                </form>
                <a href="javascript:void(0)" onclick="openAddClientModal()" class="btn-add"><i class="fas fa-plus"></i>
                    Add Client</a>
            </header>

            <table>
                <table class="client-table">
                    <thead>
                        <tr>
                            <th>Profile Image</th>
                            <th>Full Name</th>
                            <th>Nickname</th>
                            <th>Email</th>
                            <th>Phone Number</th>
                            <th>Gender</th>
                            <th>Date of Birth</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($clients as $client)
                        <tr class="{{ $client->is_blocked ? 'blocked-user' : '' }}">
                            <td>
                                <img src="{{ asset('storage/' . $client->profile_image) }}" alt="Profile Image"
                                    width="50" height="50">
                            </td>
                            <td>{{ $client->fullname }}</td>
                            <td>{{ $client->nickname }}</td>
                            <td>{{ $client->email }}</td>
                            <td>{{ $client->phone_number }}</td>
                            <td>{{ $client->gender }}</td>
                            <td>{{ $client->date_of_birth }}</td>
                            <td>
                                <span class="status-badge {{ $client->is_blocked ? 'blocked' : 'active' }}">
                                    {{ $client->is_blocked ? 'Blocked' : 'Active' }}
                                </span>
                            </td>
                            <td>
                                <div class="actions-container">
                                    <button class="btn-edit" onclick="openEditModal({{ $client }})">
                                        <i class="fas fa-edit"></i> 
                                    </button>
                                    <form action="{{ $client->is_blocked ? route('admin.unblock-client', $client->id) : route('admin.block-client', $client->id) }}" method="POST" style="display:inline-block;">
                                        @csrf
                                        <button type="submit" class="{{ $client->is_blocked ? 'btn-unblock' : 'btn-block' }}" title="{{ $client->is_blocked ? 'Unblock Client' : 'Block Client' }}" onclick="return confirm('Are you sure you want to {{ $client->is_blocked ? 'unblock' : 'block' }} this client?')">
                                            <i class="fas {{ $client->is_blocked ? 'fa-unlock' : 'fa-ban' }}"></i>
                                            {{ $client->is_blocked ? 'Unblock' : 'Block' }}
                                        </button>
                                    </form>
                                    <form action="{{ route('clients.destroy', $client->id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

        </div>
    </div>

    <!-- Add Client Modal -->
    <!-- Add Client Modal -->
    <div id="addClientModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAddClientModal()">&times;</span>
            <h2>Add New Client</h2>
            <form id="addClientForm" method="POST" action="{{ route('clients.store') }}">
                @csrf
                <div class="form-group">
                    <label for="fullname">Full Name:</label>
                    <input type="text" id="add_fullname" name="fullname" required>
                </div>
                <div class="form-group">
                    <label for="nickname">Nickname:</label>
                    <input type="text" id="add_nickname" name="nickname" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="add_email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="phone_number">Phone Number:</label>
                    <input type="text" id="add_phone_number" name="phone_number" required>
                </div>
                <div class="form-group">
                    <label for="gender">Gender:</label>
                    <select id="add_gender" name="gender" required>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="date_of_birth">Date of Birth:</label>
                    <input type="date" id="add_date_of_birth" name="date_of_birth" required>
                </div>
                <div class="form-group">
                    <label for="profile_image">Profile Image:</label>
                    <input type="file" id="add_profile_image" name="profile_image" accept="image/*">
                </div>

                <!-- Password input with toggle functionality -->
                <div class="form-group">
    <label for="password">Password:</label>
    <div class="password-wrapper">
        <input type="password" id="add_password" name="password" required>
        <i class="fas fa-eye" id="togglePassword" onclick="togglePasswordVisibility()"></i>
    </div>
</div>


                <button type="submit" class="btn-submit" id="addClientButton" onclick="disableSubmitButton()">Add
                    Client</button>

                <!-- Add a loading spinner (optional) -->
                <div id="loadingSpinner" style="display:none;">
                    <i class="fas fa-spinner fa-spin"></i> Adding Client...
                </div>

            </form>
        </div>
    </div>



    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h2>Edit Client</h2>
            <form id="editClientForm" method="POST" action="">
                @csrf
                 @method('PUT')
                <div class="form-group">
                    <label for="fullname">Full Name:</label>
                    <input type="text" id="fullname" name="fullname" required>
                </div>
                <div class="form-group">
                    <label for="nickname">Nickname:</label>
                    <input type="text" id="nickname" name="nickname" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="phone_number">Phone Number:</label>
                    <input type="text" id="phone_number" name="phone_number" required>
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
                <button type="submit" class="btn-submit">Update Client</button>
            </form>
        </div>
    </div>
    <style>
        .actions-container {
            display: flex;
            gap: 5px;
        }
        .btn-block, .btn-unblock {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .btn-block {
            background-color: #dc3545;
            color: white;
        }
        .btn-unblock {
            background-color: #28a745;
            color: white;
        }
        .btn-block:hover {
            background-color: #c82333;
        }
        .btn-unblock:hover {
            background-color: #218838;
        }
        .blocked-user {
            background-color: #fff3f3;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-badge.blocked {
            background-color: #dc3545;
            color: white;
        }
        .status-badge.active {
            background-color: #28a745;
            color: white;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
    </style>

    <script src="{{ asset('js/admin/clients.js') }}">

    </script>
</body>

</html>