<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage Appointments</title>
    <link rel="stylesheet" href="{{ asset('css/appointments.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <meta name="csrf-token" content="{{ csrf_token() }}">
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
                <li><a href="{{ route('appointments.index') }}"><i class="fas fa-calendar"></i> Appointments</a></li>
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

        <!-- Main Content Area -->
        <div class="main-content">
            <header>
                <div class="header-content">
                    <h1>Appointments Management</h1>
                </div>
            </header>

            <section class="table-container">
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
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($appointments as $appointment)
                        <tr>
                            <td>{{ $appointment->full_name }}</td>
                            <td>{{ $appointment->phone_number }}</td>
                            <td>
                                @if (!empty($appointment->selected_staff))
                                {{ implode(', ', $appointment->selected_staff) }}
                                @else
                                N/A
                                @endif
                            </td>
                            <td>{{ $appointment->service_name }}</td>
                            <!-- Apply formatting functions dynamically -->
                            <td class="formatted-date">{{ $appointment->appointment_date }}</td>
                            <td class="formatted-time">{{ $appointment->appointment_time }}</td>
                            <td>
                                <span class="status status-{{ strtolower($appointment->status) }}">
                                    {{ ucfirst($appointment->status) }}
                                </span>
                            </td>
                            <td class="actions-container">
                                @if ($appointment->status === 'Pending')
                                <form method="POST" action="{{ route('appointments.updateStatus', $appointment->id) }}"
                                    style="display: inline;">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="status" value="Accepted">
                                    <button type="submit" class="btn-accept">Accept</button>
                                </form>

                                <button type="button" class="btn-reject"
                                    onclick="openRejectModal({{ $appointment->id }})">Reject</button>

                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8">No appointments found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>

                <!-- Pagination -->
                <div class="pagination-container">
                    {{ $appointments->links('pagination::bootstrap-4') }}
                </div>

            </section>

            <div id="rejectModal" style="display:none;" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeRejectModal()">&times;</span>
                    <h2>Reject Appointment</h2>
                    <form id="rejectForm" method="POST">
                        @csrf
                        @method('PUT')
                        <label for="reason">Reason for Rejection:</label>
                        <textarea name="reason" id="reason" rows="4" required></textarea>
                        <button type="submit" class="btn-reject-modal">Submit</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Function to format date in dd/mm/yy format
    function formatDate(dateString) {
        const date = new Date(dateString);
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = String(date.getFullYear()).slice(-2);
        return `${day}/${month}/${year}`;
    }

    // Function to format time in hh:mm AM/PM format
    function formatTime(timeString) {
        const [hours, minutes] = timeString.split(':');
        const hour = parseInt(hours, 10);
        const period = hour >= 12 ? 'PM' : 'AM';
        const formattedHour = hour % 12 || 12; // Convert 0 to 12 for 12-hour format
        return `${formattedHour}:${minutes} ${period}`;
    }

    // Apply formatting dynamically to appointment dates and times
    document.querySelectorAll('.formatted-date').forEach(cell => {
        cell.textContent = formatDate(cell.textContent);
    });

    document.querySelectorAll('.formatted-time').forEach(cell => {
        cell.textContent = formatTime(cell.textContent);
    });

    // Sidebar toggle logic
    document.querySelectorAll('.submenu-toggle').forEach(item => {
        item.addEventListener('click', event => {
            event.preventDefault();
            const submenu = item.nextElementSibling;
            submenu.classList.toggle('open');
        });
    });


    // Modal logic
    function openRejectModal(appointmentId) {
        const modal = document.getElementById("rejectModal");
        const rejectForm = document.getElementById("rejectForm");

        // Use Laravel route helper to set the form action dynamically
        rejectForm.action = "{{ route('appointments.reject', ':appointmentId') }}".replace(':appointmentId',
            appointmentId);

        // Show the modal
        modal.style.display = "block";
    }

    function closeRejectModal() {
        const modal = document.getElementById("rejectModal");
        modal.style.display = "none";
    }

    // Close modal when clicking outside of it
    window.onclick = function(event) {
        const modal = document.getElementById("rejectModal");
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }
    </script>
</body>

</html>