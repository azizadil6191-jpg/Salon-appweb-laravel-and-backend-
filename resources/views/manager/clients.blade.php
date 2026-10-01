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
            <li><a href="{{ route('manager.dashboard') }}"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="{{ route('manager.appointments') }}"><i class="fas fa-calendar"></i> Appointments</a></li>
                <li><a href="{{ route('manager.inventory') }}" class="active"><i class="fas fa-boxes"></i> Inventory</a></li>
                <li><a href="{{ route('manager.attendance') }}" class="active"><i class="fas fa-clock"></i> Attendance</a></li>
                <li class="has-submenu">
                    <a href="#" class="submenu-toggle"><i class="fas fa-chart-bar"></i> Reports</a>
                    <ul class="submenu">
                        <li><a href="{{ route('manager.report') }}"> <i class="fas fa-calendar-check"></i>Appointment Reports</a></li>
                        <li><a href="{{ route('manager.report') }}"> <i class="fas fa-shopping-basket"></i>Product Sales Report</a></li>
                        <li><a href="{{ route('manager.report') }}"> <i class="fas fa-warehouse"></i>Inventory Reports</a></li>
                    </ul>
                </li>
                <li> <a href="{{ route('manager.schedule') }}" class="btn btn-primary"> <i class="fas fa-calendar-alt me-2"></i>Manage Staff Schedule</a></li>
                <li><a href="{{ route('manager.services') }}"><i class="fas fa-cut"></i> Services</a></li>
                <li><a href="{{ route('manager.clients') }}"><i class="fas fa-user"></i> Clients</a></li>
                <li><a href="{{ route('manager.staff') }}"> <i class="fas fa-user"></i> Staff</a></li>
                <li><a href="{{ route('manager.history') }}" class="active"><i class="fas fa-history"></i>Front Desk Transactions</a></li>
                <!-- <li><a href="{{ route('manager.reviews') }}"><i class="fas fa-star"></i> Reviews</a></li> -->
            </ul>
        </div>

        <div class="main-content">
            <header>
                <div class="header-content">
                    <h1>Clients</h1>
                </div>
                <form method="GET" action="{{ route('clients.index') }}" class="search-form">
                    <input type="text" name="search" placeholder="Search clients..." value="{{ request('search') }}">
                    <button type="submit"><i class="fas fa-search"></i> Search</button>
                </form>
            </header>

            <table class="client-table">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Profile Image</th>
                        <th>Full Name</th>
                        <th>Nickname</th>
                        <th>Email</th>
                        <th>Phone Number</th>
                        <th>Gender</th>
                        <th>Date of Birth</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($clients as $index => $client)
                        <tr>
                            <td>{{ $index + 1 }}</td>
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
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</body>

</html>
