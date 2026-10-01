<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Staff Schedule - Admin Dashboard</title>
    <link rel="stylesheet" href="{{ asset('css/admindashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff-schedule.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        .schedule-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin: 20px 0;
        }

        .staff-info {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        .staff-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
        }

        .staff-details h2 {
            color: #333;
            margin: 0 0 5px 0;
        }

        .staff-categories {
            color: #666;
            font-size: 0.9rem;
        }

        .schedule-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
            margin-top: 20px;
        }

        .day-column {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
        }

        .day-header {
            text-align: center;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .time-slot {
            background: white;
            border-radius: 4px;
            padding: 8px;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        .time-slot.available {
            border-left: 4px solid #28a745;
        }

        .time-slot.unavailable {
            border-left: 4px solid #dc3545;
            opacity: 0.7;
        }

        .appointment {
            background: #B22222;
            color: white;
            border-radius: 4px;
            padding: 8px;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        .schedule-form {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
        }

        .form-control {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .btn-primary {
            background: #B22222;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-primary:hover {
            background: #8B0000;
        }

        @media (max-width: 768px) {
            .schedule-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-logo">
                <img src="{{ asset('images/kingsalon.png') }}" alt="Admin Logo" style="width: 200px; height: auto;">
            </div>
            <ul>
                <li><a href="{{ route('admin.dashboard') }}"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="{{ route('services.index') }}"><i class="fas fa-cut"></i> Services</a></li>
                <li><a href="{{ route('clients.index') }}"><i class="fas fa-user"></i> Clients</a></li>
                <li class="has-submenu">
                    <a href="#" class="submenu-toggle"><i class="fas fa-users"></i> Members</a>
                    <ul class="submenu">
                        <li><a href="{{ route('owners.index') }}"><i class="fas fa-user"></i> Owner</a></li>
                        <li><a href="{{ route('managers.index') }}"><i class="fas fa-user"></i> Manager</a></li>
                        <li><a href="{{ route('staff.index') }}"><i class="fas fa-user"></i> Staff</a></li>
                        <li><a href="{{ route('cashiers.index') }}"><i class="fas fa-user"></i> Cashier</a></li>
                    </ul>
                </li>
                <li><a href="{{ route('admin.products.index') }}"><i class="fas fa-box"></i>Inventory</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <header>
                <div class="header-content">
                    <h1>Staff Schedule</h1>
                    <div class="header-right">
                        <div class="settings-container">
                            <i class="fas fa-cog settings-icon"></i>
                            <div class="settings-dropdown">
                                <form action="{{ route('admin.logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="logout-btn">
                                        <i class="fas fa-sign-out-alt"></i> Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                        <div class="user-profile">
                            <img src="{{ asset('images/admin.png') }}" alt="Admin Avatar">
                            <span>Admin</span>
                        </div>
                    </div>
                </div>
            </header>

            <div class="schedule-container">
                <a href="{{ route('admin.dashboard') }}" class="back-button">
                    <i class="fas fa-arrow-left"></i>
                    Back to Dashboard
                </a>

                <div class="staff-info">
                    @if($staff->profile_picture)
                        <img src="{{ asset('storage/' . $staff->profile_picture) }}" alt="{{ $staff->name }}" class="staff-avatar">
                    @else
                        <div class="staff-avatar" style="background: #eee; display: flex; align-items: center; justify-content: center;">
                            <span style="color: #666; font-size: 2rem;">{{ substr($staff->name, 0, 1) }}</span>
                        </div>
                    @endif
                    <div class="staff-details">
                        <h2>{{ $staff->name }}</h2>
                        <div class="staff-categories">
                            {{ $staff->categories->pluck('name')->join(', ') }}
                        </div>
                    </div>
                </div>

                <div class="schedule-grid">
                    @foreach($days as $dayName => $dayNumber)
                        <div class="day-column">
                            <div class="day-header">{{ $dayName }}</div>
                            @if(isset($schedule[$dayNumber]) && $schedule[$dayNumber][0]->is_working_day)
                                @foreach($schedule[$dayNumber] as $timeSlot)
                                    <div class="time-slot {{ $timeSlot->is_available ? 'available' : 'unavailable' }}">
                                        {{ \Carbon\Carbon::parse($timeSlot->start_time)->format('h:i A') }} - 
                                        {{ \Carbon\Carbon::parse($timeSlot->end_time)->format('h:i A') }}
                                    </div>
                                @endforeach
                            @else
                                <div class="time-slot unavailable" style="text-align: center; padding: 15px;">
                                    <i class="fas fa-calendar-times"></i>
                                    <br>
                                    Day Off
                                </div>
                            @endif
                            @if(isset($appointments[$startOfWeek->copy()->addDays($dayNumber - 1)->format('Y-m-d')]))
                                @foreach($appointments[$startOfWeek->copy()->addDays($dayNumber - 1)->format('Y-m-d')] as $appointment)
                                    <div class="appointment">
                                        {{ \Carbon\Carbon::parse($appointment->appointment_time)->format('h:i A') }} - 
                                        {{ $appointment->client->name }}
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    @endforeach
                </div>

                <form action="{{ route('admin.staff.schedule.update', $staff->id) }}" method="POST" class="schedule-form">
                    @csrf
                    @method('PUT')
                    <h3>Update Schedule</h3>
                    <div class="form-group">
                        <label>Regular Schedule</label>
                        @foreach($days as $dayName => $dayNumber)
                            <div class="day-schedule">
                                <h4>{{ $dayName }}</h4>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Start Time</label>
                                        <input type="time" name="schedules[{{ $dayNumber }}][start_time]" 
                                               value="{{ $schedule[$dayNumber][0]->start_time ?? '' }}" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label>End Time</label>
                                        <input type="time" name="schedules[{{ $dayNumber }}][end_time]" 
                                               value="{{ $schedule[$dayNumber][0]->end_time ?? '' }}" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label>Available</label>
                                        <input type="checkbox" name="schedules[{{ $dayNumber }}][is_available]" 
                                               {{ isset($schedule[$dayNumber][0]) && $schedule[$dayNumber][0]->is_available ? 'checked' : '' }}>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <button type="submit" class="btn-primary">Update Schedule</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Sidebar submenu toggle
        document.querySelectorAll('.submenu-toggle').forEach(item => {
            item.addEventListener('click', event => {
                event.preventDefault();
                let submenu = item.nextElementSibling;
                submenu.classList.toggle('open');
            });
        });

        // Settings dropdown toggle
        document.querySelector(".settings-icon").addEventListener("click", function() {
            document.querySelector(".settings-container").classList.toggle("active");
        });

        // Close dropdown when clicking outside
        document.addEventListener("click", function(event) {
            if (!event.target.closest('.settings-container')) {
                document.querySelector(".settings-container").classList.remove("active");
            }
        });
    </script>
</body>
</html> 