<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager - View Appointments</title>
    <link rel="stylesheet" href="{{ asset('css/appointments.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <!-- FullCalendar CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css">
    <style>
        /* Search and Filter Container Styles */
        .search-filter-container {
            display: flex;
            gap: 30px;
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            align-items: center;
        }

        .search-box {
            position: relative;
            width: 300px;
        }

        .search-box input {
            width: 100%;
            padding: 12px 40px 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: white;
        }

        .search-box input:focus {
            border-color: #dc3545;
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.15);
            outline: none;
        }

        .search-box .search-icon {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #dc3545;
            font-size: 16px;
            pointer-events: none;
            z-index: 1;
        }

        .status-filter select {
            padding: 12px 20px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            min-width: 180px;
            background-color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23dc3545' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            padding-right: 40px;
        }

        .status-filter select:focus {
            border-color: #dc3545;
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.15);
            outline: none;
        }

        /* Table Styles */
        .appointments-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 20px 0;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .appointments-table th {
            background: #f8f9fa;
            padding: 15px 20px;
            text-align: left;
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #e9ecef;
        }

        .appointments-table td {
            padding: 15px 20px;
            border-bottom: 1px solid #e9ecef;
            color: #212529;
        }

        .appointments-table tr:hover {
            background-color: #fff5f5; /* Light red background on hover */
        }

        /* Status Badge Styles */
        .status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-accepted {
            background-color: #cce5ff;
            color: #004085;
        }

        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }

        .status-No_show {
            background-color: #f8d7da;
            color: #dc3545; /* Updated to match theme */
        }

        /* View Toggle Buttons */
        .view-toggle {
            display: flex;
            gap: 10px;
            margin: 20px 0;
            padding: 0 20px;
        }

        .view-toggle button {
            padding: 10px 20px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            background: white;
            color: #495057;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .view-toggle button:hover {
            border-color: #dc3545;
            color: #dc3545;
        }

        .view-toggle button.active {
            background: #dc3545;
            border-color: #dc3545;
            color: white;
        }

        /* Pagination Styles */
        .pagination-container {
            margin: 30px 0;
            padding: 0 20px;
            display: flex;
            justify-content: center;
        }

        .pagination-wrapper {
            display: flex;
            align-items: center;
            gap: 10px;
            background: white;
            padding: 8px 16px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .pagination-numbers {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .pagination-number {
            min-width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            font-weight: 500;
            color: #495057;
            text-decoration: none;
            transition: all 0.3s ease;
            background: #f8f9fa;
            border: 2px solid transparent;
        }

        .pagination-number:hover {
            background: #fff5f5;
            border-color: #dc3545;
            color: #dc3545;
        }

        .pagination-number.active {
            background: #dc3545;
            color: white;
            border-color: #dc3545;
        }

        .pagination-arrow {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            color: #495057;
            text-decoration: none;
            transition: all 0.3s ease;
            background: #f8f9fa;
            border: 2px solid transparent;
        }

        .pagination-arrow:hover {
            background: #fff5f5;
            border-color: #dc3545;
            color: #dc3545;
        }

        .pagination-arrow.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
        }

        /* No Results Message */
        .appointments-table tr td[colspan="7"] {
            text-align: center;
            padding: 30px;
            color: #6c757d;
            font-style: italic;
        }

        /* Calendar View Styles */
        .calendar-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin: 20px;
        }

        /* Calendar Header */
        .fc .fc-toolbar {
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 20px !important;
        }

        .fc .fc-toolbar-title {
            color: #dc3545;
            font-size: 1.5em !important;
            font-weight: 600;
        }

        .fc .fc-button {
            background-color: white !important;
            border: 2px solid #dc3545 !important;
            color: #dc3545 !important;
            padding: 8px 16px !important;
            font-weight: 500 !important;
            text-transform: capitalize !important;
            transition: all 0.3s ease !important;
        }

        .fc .fc-button:hover {
            background-color: #dc3545 !important;
            color: white !important;
        }

        .fc .fc-button-active {
            background-color: #dc3545 !important;
            color: white !important;
        }

        /* Calendar Grid */
        .fc .fc-daygrid-day {
            border: 1px solid #e9ecef !important;
        }

        .fc .fc-daygrid-day.fc-day-today {
            background-color: #fff5f5 !important;
        }

        .fc .fc-col-header-cell {
            background: #f8f9fa;
            padding: 10px 0;
        }

        .fc .fc-col-header-cell-cushion {
            color: #495057;
            font-weight: 600;
            text-decoration: none;
            padding: 8px;
        }

        /* Calendar Events */
        .fc-event {
            border: none !important;
            border-radius: 4px !important;
            padding: 4px 8px !important;
            margin: 2px 0 !important;
            font-weight: 500 !important;
            cursor: pointer !important;
        }

        .fc-event:hover {
            transform: scale(1.02);
        }

        .fc-event-main-frame {
            padding: 2px !important;
        }

        .fc-event-time {
            font-size: 0.85em !important;
            margin-bottom: 2px !important;
        }

        .fc-event-title {
            font-weight: 600 !important;
        }

        .fc-event-staff {
            font-size: 0.85em !important;
            opacity: 0.9 !important;
        }

        .fc-event-status {
            font-size: 0.8em !important;
            font-weight: 600 !important;
            margin-top: 2px !important;
            padding: 2px 6px !important;
            border-radius: 4px !important;
            display: inline-block !important;
        }

        /* Calendar Event Content Styles */
        .fc-event-content {
            padding: 8px !important;
            font-size: 0.9em !important;
        }

        .fc-event-time {
            font-size: 0.85em !important;
            margin-bottom: 4px !important;
            font-weight: 600 !important;
            color: inherit !important;
            display: flex !important;
            align-items: center !important;
            gap: 4px !important;
        }

        .fc-event-time i {
            font-size: 0.9em !important;
            opacity: 0.8 !important;
        }

        .fc-event-title {
            font-weight: 600 !important;
            margin-bottom: 4px !important;
            color: inherit !important;
            display: flex !important;
            align-items: center !important;
            gap: 4px !important;
        }

        .fc-event-title i {
            font-size: 0.9em !important;
            opacity: 0.8 !important;
        }

        .fc-event-staff {
            font-size: 0.85em !important;
            margin-bottom: 4px !important;
            color: inherit !important;
            opacity: 0.9 !important;
            display: flex !important;
            align-items: center !important;
            gap: 4px !important;
        }

        .fc-event-staff i {
            font-size: 0.9em !important;
            opacity: 0.8 !important;
        }

        .fc-event-status {
            font-size: 0.8em !important;
            font-weight: 600 !important;
            padding: 2px 6px !important;
            border-radius: 4px !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 4px !important;
            background-color: rgba(255, 255, 255, 0.2) !important;
        }

        .fc-event-status i {
            font-size: 0.7em !important;
        }

        /* Calendar Event Status Colors */
        .fc-event[data-status="pending"] {
            background-color: #FFF3CD !important;
            border-left: 4px solid #FFC107 !important;
            color: #856404 !important;
        }

        .fc-event[data-status="accepted"] {
            background-color: #CCE5FF !important;
            border-left: 4px solid #0D6EFD !important;
            color: #004085 !important;
        }

        .fc-event[data-status="completed"] {
            background-color: #D4EDDA !important;
            border-left: 4px solid #198754 !important;
            color: #155724 !important;
        }

        .fc-event[data-status="no_show"] {
            background-color: #F8D7DA !important;
            border-left: 4px solid #DC3545 !important;
            color: #721C24 !important;
        }

        .fc-event[data-status="cancelled"] {
            background-color: #E2E3E5 !important;
            border-left: 4px solid #6C757D !important;
            color: #383D41 !important;
        }

        .fc-event[data-status="rejected"] {
            background-color: #F8D7DA !important;
            border-left: 4px solid #DC3545 !important;
            color: #721C24 !important;
        }

        /* Calendar Event Hover Effects */
        .fc-event[data-status="pending"]:hover {
            background-color: #FFEEBA !important;
            transform: scale(1.02);
            transition: all 0.3s ease;
        }

        .fc-event[data-status="accepted"]:hover {
            background-color: #B8DAFF !important;
            transform: scale(1.02);
            transition: all 0.3s ease;
        }

        .fc-event[data-status="completed"]:hover {
            background-color: #C3E6CB !important;
            transform: scale(1.02);
            transition: all 0.3s ease;
        }

        .fc-event[data-status="no_show"]:hover {
            background-color: #F5C6CB !important;
            transform: scale(1.02);
            transition: all 0.3s ease;
        }

        .fc-event[data-status="cancelled"]:hover {
            background-color: #D6D8DB !important;
            transform: scale(1.02);
            transition: all 0.3s ease;
        }

        .fc-event[data-status="rejected"]:hover {
            background-color: #F5C6CB !important;
            transform: scale(1.02);
            transition: all 0.3s ease;
        }

        /* Calendar Modal */
        .calendar-event-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .calendar-modal-content {
            background: white;
            padding: 25px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            position: relative;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .calendar-close {
            position: absolute;
            right: 20px;
            top: 15px;
            font-size: 24px;
            color: #6c757d;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .calendar-close:hover {
            color: #dc3545;
        }

        .event-details-modal h3 {
            color: #dc3545;
            margin-bottom: 20px;
            font-size: 1.5em;
        }

        .event-detail-row {
            display: flex;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e9ecef;
        }

        .event-detail-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .detail-label {
            font-weight: 600;
            width: 100px;
            color: #495057;
        }

        .detail-value {
            flex: 1;
            color: #212529;
        }

        /* Add these new styles */
        .status-tabs {
            display: flex;
            gap: 10px;
            margin: 20px;
            padding: 0 20px;
            flex-wrap: wrap;
        }

        .tab-button {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            background: #f8f9fa;
            color: #495057;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .tab-button:hover {
            background: #e9ecef;
        }

        .tab-button.active {
            background: #dc3545;
            color: white;
        }

        .tab-button[data-status="pending"] {
            border-left: 4px solid #ffc107;
        }

        .tab-button[data-status="accepted"] {
            border-left: 4px solid #0d6efd;
        }

        .tab-button[data-status="completed"] {
            border-left: 4px solid #198754;
        }

        .tab-button[data-status="no_show"] {
            border-left: 4px solid #dc3545;
        }

        .tab-button[data-status="cancelled"] {
            border-left: 4px solid #6c757d;
        }

        .tab-button[data-status="rejected"] {
            border-left: 4px solid #dc3545;
        }

        /* Remove the old status filter styles */
        .status-filter {
            display: none;
        }

        /* Calendar Day Styles */
        .fc-daygrid-day {
            background: white !important;
        }

        .fc-daygrid-day.fc-day-today {
            background-color: #fff5f5 !important;
        }

        /* Calendar Header Styles */
        .fc .fc-toolbar {
            padding: 20px !important;
            background: #f8f9fa !important;
            border-radius: 8px !important;
            margin-bottom: 20px !important;
        }

        .fc .fc-toolbar-title {
            color: #dc3545 !important;
            font-size: 1.5em !important;
            font-weight: 600 !important;
        }

        .fc .fc-button {
            background-color: white !important;
            border: 2px solid #dc3545 !important;
            color: #dc3545 !important;
            padding: 8px 16px !important;
            font-weight: 500 !important;
            text-transform: capitalize !important;
            transition: all 0.3s ease !important;
        }

        .fc .fc-button:hover {
            background-color: #dc3545 !important;
            color: white !important;
        }

        .fc .fc-button-active {
            background-color: #dc3545 !important;
            color: white !important;
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
            <li><a href="{{ route('manager.dashboard') }}"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="{{ route('manager.appointments') }}"><i class="fas fa-calendar"></i> Appointments</a></li>
                <li><a href="{{ route('manager.inventory') }}" class="active"><i class="fas fa-boxes"></i> Inventory</a></li>
                <li><a href="{{ route('manager.attendance') }}" class="active"><i class="fas fa-clock"></i> Attendance</a></li>

                                <li class="has-submenu">
                    <a href="#" class="submenu-toggle"><i class="fas fa-chart-bar"></i>Reports</a>
                    <ul class="submenu">
                        <li><a href="{{ route('manager.report') }}"> <i class="fas fa-calendar-check"></i>Appointment Reports</a></li>
                        <li><a href="{{ route('manager.product-sales-report') }}" class="active"> <i class="fas fa-shopping-basket"></i>Product Sales Report</a></li>
                        <li><a href="{{ route('manager.report') }}"> <i class="fas fa-warehouse"></i>Inventory Reports</a></li>
                    </ul>
                </li>

                <li> <a href="{{ route('manager.schedule') }}" class="btn btn-primary"> <i class="fas fa-calendar-alt me-2"></i>Manage Staff Schedule</a></li>
                <li><a href="{{ route('manager.services') }}"><i class="fas fa-cut"></i> Services</a></li>
                <!-- <li><a href="{{ route('manager.clients') }}"><i class="fas fa-user"></i> Clients</a></li> -->
                <li><a href="{{ route('manager.staff') }}"> <i class="fas fa-user"></i> Staff</a></li>
                <li><a href="{{ route('manager.history') }}" class="active"><i class="fas fa-history"></i>Front Desk Transactions</a></li>
                <!-- <li><a href="{{ route('manager.reviews') }}"><i class="fas fa-star"></i> Reviews</a></li> -->
            </ul>
        </div>

        <!-- Main Content Area -->
        <div class="main-content">
            <header>
                <div class="header-content">
                    <h1>Appointments</h1>
                </div>
            </header>

            <div class="view-toggle">
                <button id="tableViewBtn" class="active">Table View</button>
                <button id="calendarViewBtn">Calendar View</button>
            </div>

            <!-- Search and Filter Section -->
            <div class="search-filter-container">
                <div class="search-box">
                    <input type="text" id="appointmentSearch" placeholder="Search by name, phone, staff, service, date...">
                    <button type="button" class="search-btn" id="searchButton">
                        <i class="fas fa-search"></i>
                        Search
                    </button>
                </div>
            </div>

            <!-- Status Tabs -->
            <div class="status-tabs">
                <button class="tab-button active" data-status="all">All</button>
                <button class="tab-button" data-status="pending">Pending</button>
                <button class="tab-button" data-status="accepted">Accepted</button>
                <button class="tab-button" data-status="completed">Completed</button>
                <button class="tab-button" data-status="no_show">No Show</button>
                <button class="tab-button" data-status="cancelled">Cancelled</button>
                <button class="tab-button" data-status="rejected">Rejected</button>
            </div>

            <!-- Table View -->
            <section class="table-container" id="tableView">
                <table class="appointments-table">
                    <thead>
                        <tr>
                            <th>Client Name</th>
                            <th>Phone Number</th>
                            <th>Selected Staff</th>
                            <th>Service</th>
                            <th>Appointment Date</th>
                            <th>Appointment Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="appointmentsTableBody">
                        @forelse ($appointments as $appointment)
                        <tr data-appointment-id="{{ $appointment->id }}"
                            data-date="{{ $appointment->appointment_date }}"
                            data-time="{{ \Carbon\Carbon::parse($appointment->appointment_time)->format('H:i') }}"
                            data-client="{{ strtolower($appointment->full_name) }}"
                            data-phone="{{ $appointment->phone_number }}"
                            data-staff="{{ strtolower($appointment->primary_staff ? $appointment->primary_staff->first_name . ' ' . $appointment->primary_staff->last_name : '') }}"
                            data-service="{{ strtolower($appointment->service_name) }}"
                            data-status="{{ strtolower($appointment->status) }}">
                            <td>{{ $appointment->full_name }}</td>
                            <td>{{ $appointment->phone_number }}</td>
                            <td>
                                @if ($appointment->primary_staff)
                                    {{ $appointment->primary_staff->first_name }} {{ $appointment->primary_staff->last_name }}
                                @elseif (!empty($appointment->all_staff))
                                    {{ $appointment->all_staff->pluck('first_name', 'last_name')->join(', ') }}
                                @else
                                    N/A
                                @endif
                            </td>
                            <td>{{ $appointment->service_name }}</td>
                            <td>{{ \Carbon\Carbon::parse($appointment->appointment_date)->format('M d, Y') }}</td>
                            <td>{{ \Carbon\Carbon::parse($appointment->appointment_time)->format('g:i A') }}</td>
                            <td>
                                <span class="status status-{{ strtolower($appointment->status) }}">
                                    {{ ucfirst($appointment->status) }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7">No appointments found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>

                <!-- Pagination Links -->
                <div class="pagination-container">
                    @if($appointments->hasPages())
                        <div class="pagination-wrapper">
                            @if($appointments->onFirstPage())
                                <span class="pagination-arrow disabled">
                                    <i class="fas fa-chevron-left"></i>
                                </span>
                            @else
                                <a href="{{ $appointments->previousPageUrl() }}" class="pagination-arrow">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            @endif

                            <div class="pagination-numbers">
                                @foreach($appointments->getUrlRange(1, $appointments->lastPage()) as $page => $url)
                                    @if($page == $appointments->currentPage())
                                        <span class="pagination-number active">{{ $page }}</span>
                                    @else
                                        <a href="{{ $url }}" class="pagination-number">{{ $page }}</a>
                                    @endif
                                @endforeach
                            </div>

                            @if($appointments->hasMorePages())
                                <a href="{{ $appointments->nextPageUrl() }}" class="pagination-arrow">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            @else
                                <span class="pagination-arrow disabled">
                                    <i class="fas fa-chevron-right"></i>
                                </span>
                            @endif
                        </div>
                    @endif
                </div>
            </section>

            <!-- Calendar View -->
            <section class="calendar-container" id="calendarView" style="display: none;">
                <div id="calendar"></div>
            </section>

            <!-- Hidden input to store all appointments data -->
            <input type="hidden" id="allAppointmentsData" value="{{ json_encode($allAppointments) }}">
        </div>
    </div>

    <!-- FullCalendar JS -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script>
        // Format time in hh:mm AM/PM format
        function formatTime(timeString) {
            const [hours, minutes] = timeString.split(':');
            const hour = parseInt(hours, 10);
            const period = hour >= 12 ? 'PM' : 'AM';
            const formattedHour = hour % 12 || 12;
            return `${formattedHour}:${minutes} ${period}`;
        }

        // Initialize calendar
        function initializeCalendar() {
            const calendarEl = document.getElementById('calendar');
            const appointments = [];
            
            // Get all appointments from the hidden input
            const allAppointmentsData = JSON.parse(document.getElementById('allAppointmentsData').value);
            
            allAppointmentsData.forEach(appointment => {
                const status = appointment.status.toLowerCase();
                const date = appointment.appointment_date;
                const time = appointment.appointment_time;
                const [hours, minutes] = time.split(':');
                
                const startDateTime = new Date(date);
                startDateTime.setHours(parseInt(hours), parseInt(minutes));
                
                const endDateTime = new Date(startDateTime);
                endDateTime.setHours(endDateTime.getHours() + 1);
                
                let staff = appointment.primary_staff ? 
                    `${appointment.primary_staff.first_name} ${appointment.primary_staff.last_name}` : 
                    (appointment.all_staff ? 
                        appointment.all_staff.map(s => `${s.first_name} ${s.last_name}`).join(', ') : 
                        'No staff assigned');
                
                appointments.push({
                    id: appointment.id,
                    title: appointment.full_name + ' - ' + appointment.service_name,
                    start: startDateTime,
                    end: endDateTime,
                    className: 'fc-event-' + status,
                    extendedProps: {
                        id: appointment.id,
                        clientName: appointment.full_name,
                        phone: appointment.phone_number,
                        staff: staff,
                        service: appointment.service_name,
                        status: status,
                        date: date,
                        time: time
                    }
                });
            });

            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next',
                    center: 'title',
                    right: 'dayGridMonth'
                },
                titleFormat: { year: 'numeric', month: 'long' },
                buttonText: {
                    month: 'Month'
                },
                events: appointments,
                dayMaxEvents: 3,
                eventClick: function(info) {
                    const event = info.event;
                    const status = event.extendedProps.status;
                    const formattedTime = formatTime(event.extendedProps.time);
                    
                    // Create modal content
                    const modalContent = `
                        <div class="event-details-modal">
                            <h3>Appointment Details</h3>
                            <div class="event-detail-row">
                                <span class="detail-label">Client:</span>
                                <span class="detail-value">${event.extendedProps.clientName}</span>
                            </div>
                            <div class="event-detail-row">
                                <span class="detail-label">Phone:</span>
                                <span class="detail-value">${event.extendedProps.phone}</span>
                            </div>
                            <div class="event-detail-row">
                                <span class="detail-label">Staff:</span>
                                <span class="detail-value">${event.extendedProps.staff}</span>
                            </div>
                            <div class="event-detail-row">
                                <span class="detail-label">Service:</span>
                                <span class="detail-value">${event.extendedProps.service}</span>
                            </div>
                            <div class="event-detail-row">
                                <span class="detail-label">Date:</span>
                                <span class="detail-value">${new Date(event.extendedProps.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}</span>
                            </div>
                            <div class="event-detail-row">
                                <span class="detail-label">Time:</span>
                                <span class="detail-value">${formattedTime}</span>
                            </div>
                            <div class="event-detail-row">
                                <span class="detail-label">Status:</span>
                                <span class="detail-value status status-${status}">${status.charAt(0).toUpperCase() + status.slice(1)}</span>
                            </div>
                        </div>
                    `;
                    
                    // Create and show modal
                    const modal = document.createElement('div');
                    modal.className = 'calendar-event-modal';
                    modal.innerHTML = `
                        <div class="calendar-modal-content">
                            <span class="calendar-close">&times;</span>
                            ${modalContent}
                        </div>
                    `;
                    
                    // Add click event listener to close button
                    const closeBtn = modal.querySelector('.calendar-close');
                    closeBtn.addEventListener('click', function() {
                        modal.remove();
                    });
                    
                    document.body.appendChild(modal);
                },
                eventContent: function(arg) {
                    const status = arg.event.extendedProps.status;
                    const formattedTime = formatTime(arg.event.extendedProps.time);
                    const clientName = arg.event.extendedProps.clientName;
                    const staffName = arg.event.extendedProps.staff;

                    // Create a container element
                    const container = document.createElement('div');
                    container.className = 'fc-event-main-frame';
                    container.setAttribute('data-status', status.toLowerCase());

                    // Add the content with improved styling
                    container.innerHTML = `
                        <div class="fc-event-content" style="padding: 12px; font-size: 0.9em; background-color: inherit; border-radius: 6px;">
                            <div class="fc-event-time" style="font-size: 1.2em; margin-bottom: 8px; font-weight: bold; display: flex; align-items: center; gap: 6px; padding: 4px 8px; background-color: rgba(255, 255, 255, 0.2); border-radius: 4px;">
                                <i class="fas fa-clock" style="font-size: 0.9em; opacity: 0.8;"></i> ${formattedTime}
                            </div>
                            <div class="fc-event-title" style="font-weight: 600; margin-bottom: 8px; display: flex; align-items: center; gap: 6px; padding: 4px 8px; background-color: rgba(255, 255, 255, 0.2); border-radius: 4px;">
                                <i class="fas fa-user" style="font-size: 0.9em; opacity: 0.8;"></i> ${clientName}
                            </div>
                            <div class="fc-event-staff" style="font-size: 0.85em; margin-bottom: 8px; opacity: 0.9; display: flex; align-items: center; gap: 6px; padding: 4px 8px; background-color: rgba(255, 255, 255, 0.2); border-radius: 4px;">
                                <i class="fas fa-user-tie" style="font-size: 0.9em; opacity: 0.8;"></i> ${staffName}
                            </div>
                            <div class="fc-event-status" style="font-size: 0.8em; font-weight: 600; padding: 4px 8px; border-radius: 4px; display: inline-flex; align-items: center; gap: 6px; background-color: rgba(255, 255, 255, 0.3); width: 100%; justify-content: center;">
                                <i class="fas fa-circle" style="font-size: 0.7em;"></i> ${status.charAt(0).toUpperCase() + status.slice(1)}
                            </div>
                        </div>
                    `;

                    return { domNodes: [container] };
                },
                eventDidMount: function(info) {
                    // Add tooltip to show full details on hover
                    const event = info.event;
                    const tooltipContent = `
                        <strong>${event.extendedProps.clientName}</strong><br>
                        ${event.extendedProps.service}<br>
                        ${formatTime(event.extendedProps.time)}<br>
                        Staff: ${event.extendedProps.staff}
                    `;
                    
                    info.el.title = tooltipContent;
                }
            });

            calendar.render();
            window.calendar = calendar; // Store calendar instance globally
        }

        // Initialize view toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const tableViewBtn = document.getElementById('tableViewBtn');
            const calendarViewBtn = document.getElementById('calendarViewBtn');
            const tableView = document.getElementById('tableView');
            const calendarView = document.getElementById('calendarView');
            const statusTabs = document.querySelector('.status-tabs');
            const searchFilterContainer = document.querySelector('.search-filter-container');

            if (tableViewBtn && calendarViewBtn && tableView && calendarView) {
                tableViewBtn.addEventListener('click', function() {
                    tableView.style.display = 'block';
                    calendarView.style.display = 'none';
                    tableViewBtn.classList.add('active');
                    calendarViewBtn.classList.remove('active');
                    // Show status tabs and search in table view
                    statusTabs.style.display = 'flex';
                    searchFilterContainer.style.display = 'flex';
                });

                calendarViewBtn.addEventListener('click', function() {
                    tableView.style.display = 'none';
                    calendarView.style.display = 'block';
                    calendarViewBtn.classList.add('active');
                    tableViewBtn.classList.remove('active');
                    // Hide status tabs and search in calendar view
                    statusTabs.style.display = 'none';
                    searchFilterContainer.style.display = 'none';
                    if (!window.calendarInitialized) {
                        initializeCalendar();
                        window.calendarInitialized = true;
                    }
                });
            }
        });

        // Update the JavaScript for tab functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('appointmentSearch');
            const searchButton = document.getElementById('searchButton');
            const tabButtons = document.querySelectorAll('.tab-button');
            const tableBody = document.getElementById('appointmentsTableBody');
            const rows = tableBody.getElementsByTagName('tr');

            function performSearch() {
                const searchTerm = searchInput.value.toLowerCase().trim();
                const activeTab = document.querySelector('.tab-button.active');
                const selectedStatus = activeTab.dataset.status.toLowerCase();
                
                Array.from(rows).forEach(row => {
                    if (row.cells.length === 1) return; // Skip the "No appointments found" row
                    
                    const clientName = row.dataset.client || '';
                    const phone = row.dataset.phone || '';
                    const staff = row.dataset.staff || '';
                    const service = row.dataset.service || '';
                    const date = row.dataset.date || '';
                    const time = row.dataset.time || '';
                    const status = row.dataset.status || '';

                    const searchableText = `${clientName} ${phone} ${staff} ${service} ${date} ${time} ${status}`.toLowerCase();
                    const matchesSearch = searchableText.includes(searchTerm);
                    const matchesStatus = selectedStatus === 'all' || status === selectedStatus;
                    
                    if (matchesSearch && matchesStatus) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });

                // Show "No results" message if all rows are hidden
                const visibleRows = Array.from(rows).filter(row => row.style.display !== 'none');
                const noResultsRow = tableBody.querySelector('tr td[colspan="7"]');
                
                if (visibleRows.length === 0 && !noResultsRow) {
                    const newRow = document.createElement('tr');
                    newRow.innerHTML = '<td colspan="7">No appointments found matching your search.</td>';
                    tableBody.appendChild(newRow);
                } else if (visibleRows.length > 0 && noResultsRow) {
                    noResultsRow.parentElement.remove();
                }
            }

            // Add click event listeners to tab buttons
            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Remove active class from all buttons
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    // Add active class to clicked button
                    this.classList.add('active');
                    // Perform search with current search term
                    performSearch();
                });
            });

            // Search when button is clicked
            searchButton.addEventListener('click', function(e) {
                e.preventDefault();
                performSearch();
            });

            // Search when Enter key is pressed
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    performSearch();
                }
            });

            // Clear search when input is cleared
            searchInput.addEventListener('search', function() {
                if (this.value === '') {
                    performSearch();
                }
            });

            // Initial search to apply any URL parameters
            performSearch();
        });

        /* Update calendar styles to match table colors */
        const calendarStyles = document.createElement('style');
        calendarStyles.textContent = `
            .calendar-container {
                background: white;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                margin: 20px;
            }

            #calendar {
                max-width: 100%;
                margin: 0 auto;
            }

            .fc-event {
                cursor: pointer;
                padding: 4px 6px;
                margin: 2px 0;
                border-radius: 4px;
                font-size: 0.9em;
                display: flex;
                flex-direction: column;
            }

            .fc-event-time {
                font-size: 1.2em !important;
                font-weight: bold !important;
                margin-bottom: 4px;
                color: inherit;
            }

            .fc-event-title {
                font-weight: 500;
                font-size: 0.9em;
            }

            /* Match table status colors */
            .fc-event-pending {
                background-color: #fff3cd;
                border-left: 4px solid #ffc107;
                color: #856404;
            }

            .fc-event-accepted {
                background-color: #cce5ff;
                border-left: 4px solid #0d6efd;
                color: #004085;
            }

            .fc-event-completed {
                background-color: #d4edda;
                border-left: 4px solid #198754;
                color: #155724;
            }

            .fc-event-no_show {
                background-color: #f8d7da;
                border-left: 4px solid #dc3545;
                color: #721c24;
            }

            .fc-event-cancelled {
                background-color: #e2e3e5;
                border-left: 4px solid #6c757d;
                color: #383d41;
            }

            .fc-event-rejected {
                background-color: #f8d7da;
                border-left: 4px solid #dc3545;
                color: #721c24;
            }

            .fc-event-staff {
                font-size: 0.85em;
                opacity: 0.9;
            }

            .fc-event-status {
                font-size: 0.85em;
                font-weight: 500;
            }

            .calendar-event-modal {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 1000;
            }

            .calendar-modal-content {
                background: white;
                padding: 20px;
                border-radius: 8px;
                width: 90%;
                max-width: 500px;
                position: relative;
            }

            .calendar-close {
                position: absolute;
                right: 15px;
                top: 15px;
                font-size: 24px;
                cursor: pointer;
                color: #666;
            }

            .event-details-modal {
                padding: 20px;
            }

            .event-detail-row {
                margin: 10px 0;
                display: flex;
                justify-content: space-between;
            }

            .detail-label {
                font-weight: bold;
                color: #666;
            }

            .event-actions {
                margin-top: 20px;
                display: flex;
                gap: 10px;
                justify-content: flex-end;
            }

            .btn-accept, .btn-reject {
                padding: 8px 16px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-weight: 500;
                transition: all 0.3s ease;
            }

            .btn-accept {
                background-color: #dc3545;
                color: white;
            }

            .btn-accept:hover {
                background-color: #bb2d3b;
            }

            .btn-reject {
                background-color: #6c757d;
                color: white;
            }

            .btn-reject:hover {
                background-color: #5c636a;
            }

            /* Calendar header styling */
            .fc .fc-toolbar {
                padding: 20px;
                background: #f8f9fa;
                border-radius: 8px;
                margin-bottom: 20px !important;
            }

            .fc .fc-toolbar-title {
                color: #dc3545;
                font-size: 1.5em !important;
                font-weight: 600;
            }

            .fc .fc-button {
                background-color: white !important;
                border: 2px solid #dc3545 !important;
                color: #dc3545 !important;
                padding: 8px 16px !important;
                font-weight: 500 !important;
                text-transform: capitalize !important;
                transition: all 0.3s ease !important;
            }

            .fc .fc-button:hover {
                background-color: #dc3545 !important;
                color: white !important;
            }

            .fc .fc-button-active {
                background-color: #dc3545 !important;
                color: white !important;
            }
        `;
        document.head.appendChild(calendarStyles);

        document.querySelectorAll('.submenu-toggle').forEach(toggle => {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                const submenu = this.nextElementSibling;
                submenu.style.display = submenu.style.display === 'none' ? 'block' : 'none';
            });
        });
        
        // Global search functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('appointmentSearch');
            const searchButton = document.getElementById('searchButton');
            const tableBody = document.getElementById('appointmentsTableBody');
            const rows = tableBody.getElementsByTagName('tr');

            function performSearch() {
                const searchTerm = searchInput.value.toLowerCase().trim();
                
                Array.from(rows).forEach(row => {
                    if (row.cells.length === 1) return; // Skip the "No appointments found" row
                    
                    const clientName = row.dataset.client || '';
                    const phone = row.dataset.phone || '';
                    const staff = row.dataset.staff || '';
                    const service = row.dataset.service || '';
                    const date = row.dataset.date || '';
                    const time = row.dataset.time || '';
                    const status = row.dataset.status || '';

                    const searchableText = `${clientName} ${phone} ${staff} ${service} ${date} ${time} ${status}`.toLowerCase();
                    
                    if (searchableText.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });

                // Show "No results" message if all rows are hidden
                const visibleRows = Array.from(rows).filter(row => row.style.display !== 'none');
                const noResultsRow = tableBody.querySelector('tr td[colspan="7"]');
                
                if (visibleRows.length === 0 && !noResultsRow) {
                    const newRow = document.createElement('tr');
                    newRow.innerHTML = '<td colspan="7">No appointments found matching your search.</td>';
                    tableBody.appendChild(newRow);
                } else if (visibleRows.length > 0 && noResultsRow) {
                    noResultsRow.parentElement.remove();
                }
            }

            // Search when button is clicked
            searchButton.addEventListener('click', performSearch);

            // Search when Enter key is pressed
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    performSearch();
                }
            });

            // Clear search when input is cleared
            searchInput.addEventListener('search', function() {
                if (this.value === '') {
                    Array.from(rows).forEach(row => {
                        row.style.display = '';
                    });
                    const noResultsRow = tableBody.querySelector('tr td[colspan="7"]');
                    if (noResultsRow) {
                        noResultsRow.parentElement.remove();
                    }
                }
            });
        });
    </script>
</body>

</html>