<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Appointments Management</title>
    <!-- FullCalendar CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/dashboardfront.css') }}">

</head>

<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
        <div class="sidebar-logo">
            <img src="{{ asset('images/kingsalon.png') }}" alt="Admin Logo" style="width: 200px; height: auto;">
            </div>
            <ul>
                <li><a href="{{ route('cashier.dashboard') }}" class="active"><i class="fas fa-home"></i> Dashboard</a>
                </li>
                <li><a href="{{ route('cashier.appointments') }}"><i class="fas fa-calendar-alt"></i> Appointments</a>
                </li>
                <li><a href="{{ route('cashier.products') }}"><i class="fas fa-box"></i> After Care Products</a></li>
                <li><a href="{{ route('cashier.transaction') }}"><i class="fas fa-cash-register"></i> POS</a></li>
                <li><a href="{{ route('cashier.history') }}"><i class="fas fa-history"></i>Recent Walk-In Sales</a></li>
                <!-- <li><a href="{{ route('cashier.stocks') }}"><i class="fas fa-boxes"></i> Stocks View</a></li> -->
            </ul>
        </div>
        <div class="main-content">
            <header>
                <div class="header-content">
                    <h1>Appointments Management</h1>
                </div>
            </header>
            <div class="search-filter-container">
                <div class="search-container">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="appointmentSearch"
                        placeholder="Search by client name, phone, service, or status..." class="search-input">
                </div>
            </div>
            <div class="view-toggle">
                <button id="tableViewBtn" class="active">Table View</button>
                <button id="calendarViewBtn">Calendar View</button>
            </div>
            <!-- Table View -->
            <section class="table-container" id="tableView">
                <table class="appointments-table">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Client Name</th>
                            <th>Phone Number</th>
                            <th>Selected Staff</th>
                            <th>Service</th>
                            <th>Appointment Date</th>
                            <th>Appointment Time</th>
                            <th>Payment Method</th>
                            <th>Status</th>
                            <th>Actions</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $counter = ($appointments->currentPage() - 1) * 10 + 1;
                        @endphp
                        @forelse ($appointments as $appointment)
                        @if($appointment->status === 'Pending' || $appointment->status === 'Accepted')
                        <tr data-appointment-id="{{ $appointment->id }}"
                            data-date="{{ $appointment->appointment_date }}"
                            data-time="{{ \Carbon\Carbon::parse($appointment->appointment_time)->format('H:i') }}"
                            data-service-id="{{ $appointment->service_id }}">
                            <td>{{ $counter++ }}</td>
                            <td>{{ $appointment->full_name }}</td>
                            <td>{{ $appointment->phone_number }}</td>
                            <td>
                                @php
                                // Debug output
                                echo "
                                <!-- Debug: selected_staff = " . json_encode($appointment->selected_staff) . " -->";
                                @endphp
                                @if (!empty($appointment->selected_staff))
                                @if (is_array($appointment->selected_staff))
                                {{ implode(', ', $appointment->selected_staff) }}
                                @elseif (is_string($appointment->selected_staff))
                                {{ $appointment->selected_staff }}
                                @else
                                {{ json_encode($appointment->selected_staff) }}
                                @endif
                                @else
                                N/A
                                @endif
                            </td>
                            <td>{{ $appointment->service_name }}</td>
                            <td>{{ \Carbon\Carbon::parse($appointment->appointment_date)->format('M d, Y') }}</td>
                            <td>{{ \Carbon\Carbon::parse($appointment->appointment_time)->format('g:i A') }}</td>
                            <td>
                                @if($appointment->payment_method === 'GCash' && $appointment->upload_picture)
                                <a href="javascript:void(0)"
                                    onclick="openGcashModal('{{ asset('storage/' . $appointment->upload_picture) }}')"
                                    class="payment-link">
                                    {{ $appointment->payment_method }}
                                </a>
                                @else
                                {{ $appointment->payment_method }}
                                @endif
                            </td>
                            <td>
                                <span class="status status-{{ strtolower($appointment->status) }}">
                                    {{ ucfirst($appointment->status) }}
                                </span>
                            </td>
                            <td class="actions-container">
                                @if ($appointment->status === 'Pending')
                                <form method="POST"
                                    action="{{ route('cashier.appointments.updateStatus', $appointment->id) }}"
                                    style="display: inline;">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="Accepted">
                                    <button type="submit" class="btn-accept">Accept</button>
                                </form>
                                <button type="button" class="btn-reject"
                                    onclick="openRejectModal({{ $appointment->id }})">Reject</button>
                                @endif
                                @if ($appointment->status === 'Accepted')
                                <!-- <button type="button" class="btn-reschedule" onclick="openRescheduleModal({{ $appointment->id }})">
                                Reschedule
                            </button> -->
                                @endif
                            </td>
                            <td>
                                <div class="more-options-container" data-appointment-id="{{ $appointment->id }}">
                                    <button type="button" class="btn-more" onclick="toggleMoreOptions({{ $appointment->id }})">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div class="more-options-menu" style="display: none;">
                                        @if($appointment->upload_picture)
                                        <button onclick="openGcashModal('{{ asset('storage/' . $appointment->upload_picture) }}')" class="option-btn">
                                            <i class="fas fa-money-bill-wave"></i> View GCash Payment
                                        </button>
                                        @endif
                                        <button onclick="viewReceipt({{ $appointment->id }})" class="option-btn">
                                            <i class="fas fa-receipt"></i> View Receipt
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endif
                        @empty
                        <tr>
                            <td colspan="10" class="text-center">No appointments found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="pagination-container">
                    @if ($appointments->lastPage() > 1)
                        <ul class="pagination" style="justify-content: center;">
                            {{-- Double Back (<<) --}}
                            @php
                                $total = $appointments->lastPage();
                                $current = $appointments->currentPage();
                                $max = 5;
                                $doubleBack = max(1, $current - 5);
                            @endphp
                            <li class="page-item{{ $current == 1 ? ' disabled' : '' }}">
                                <a class="page-link" href="{{ $current == 1 ? '#' : $appointments->url($doubleBack) }}">&laquo;</a>
                            </li>
                            {{-- Single Back (<) --}}
                            <li class="page-item{{ $appointments->onFirstPage() ? ' disabled' : '' }}">
                                <a class="page-link" href="{{ $appointments->onFirstPage() ? '#' : $appointments->previousPageUrl() }}">&lt;</a>
                            </li>

                            {{-- Always show first page --}}
                            @php
                                if ($total <= $max) {
                                    $start = 1;
                                    $end = $total;
                                } else {
                                    if ($current <= 3) {
                                        $start = 1;
                                        $end = 5;
                                    } elseif ($current >= $total - 2) {
                                        $start = $total - 4;
                                        $end = $total;
                                    } else {
                                        $start = $current - 2;
                                        $end = $current + 2;
                                    }
                                }
                            @endphp
                            <li class="page-item{{ $current == 1 ? ' active' : '' }}"><a class="page-link" href="{{ $appointments->url(1) }}">1</a></li>
                            {{-- Ellipsis after first page if needed --}}
                            @if ($start > 2)
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            @endif
                            {{-- Main page range (excluding first and last) --}}
                            @for ($i = max($start, 2); $i <= min($end, $total - 1); $i++)
                                @if ($i == $current)
                                    <li class="page-item active"><span class="page-link">{{ $i }}</span></li>
                                @else
                                    <li class="page-item"><a class="page-link" href="{{ $appointments->url($i) }}">{{ $i }}</a></li>
                                @endif
                            @endfor
                            {{-- Ellipsis before last page if needed --}}
                            @if ($end < $total - 1)
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            @endif
                            {{-- Always show last page if more than 1 page --}}
                            @if ($total > 1)
                                <li class="page-item{{ $current == $total ? ' active' : '' }}"><a class="page-link" href="{{ $appointments->url($total) }}">{{ $total }}</a></li>
                            @endif

                            {{-- Next Page Link --}}
                            @php
                                $doubleNext = min($total, $current + 5);
                            @endphp
                            <li class="page-item{{ $appointments->hasMorePages() ? '' : ' disabled' }}">
                                <a class="page-link" href="{{ $appointments->hasMorePages() ? $appointments->nextPageUrl() : '#' }}">&gt;</a>
                            </li>
                            {{-- Double Next (>>) --}}
                            <li class="page-item{{ $appointments->hasMorePages() ? '' : ' disabled' }}">
                                <a class="page-link" href="{{ $appointments->hasMorePages() ? $appointments->url($doubleNext) : '#' }}">&raquo;</a>
                            </li>
                        </ul>
                    @endif
                </div>
            </section>
            <!-- Calendar View -->

            <!-- Calendar View -->
            <section class="calendar-container" id="calendarView" style="display: none;">
                <div id="calendar"></div>
            </section>

            <!-- Reject Modal -->
            <div id="rejectModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeRejectModal()">&times;</span>
                    <h2>Reject Appointment</h2>
                    <form id="rejectForm" method="POST">
                        @csrf
                        <div class="form-group">
                            <label for="reason">Reason for Rejection:</label>
                            <textarea name="reason" id="reason" rows="4" required></textarea>
                        </div>
                        <div class="action-buttons">
                            <button type="submit" class="btn-reject-modal">Reject</button>
                        </div>
                    </form>
                </div>
            </div>
            <!-- Reschedule Modal -->
            <div id="rescheduleModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeRescheduleModal()">&times;</span>
                    <h2>Reschedule Appointment</h2>
                    <form id="rescheduleForm" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="current-selection">
                            <strong>Current Appointment:</strong><br>
                            Date: <span id="currentDateDisplay"></span><br>
                            Time: <span id="currentTimeDisplay"></span>
                        </div>

                        <div class="form-group">
                            <label for="reschedule_date">New Date:</label>
                            <input type="date" name="appointment_date" id="reschedule_date" required>
                        </div>

                        <div class="form-group">
                            <label for="timeSlot">New Time:</label>
                            <select name="appointment_time" id="timeSlot" required>
                                <option value="">Select a time slot</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="selected_staff">Select Staff:</label>
                            <select name="selected_staff[]" id="selected_staff" multiple required>
                                <option value="">Loading staff...</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="reschedule_reason">Reason for Rescheduling:</label>
                            <textarea name="reason" id="reschedule_reason" rows="4" required></textarea>
                        </div>

                        <div class="action-buttons">
                            <button type="submit" class="btn-reschedule-modal" id="rescheduleBtn">
                                Reschedule
                                <span class="loading" id="rescheduleLoading" style="display:none;"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <style>
            .payment-link {
                color: #1abc9c;
                text-decoration: none;
                font-weight: 500;
                cursor: pointer;
                transition: all 0.2s ease;
            }

            .payment-link:hover {
                color: #16a085;
                text-decoration: underline;
            }

            .btn-more {
                background: none;
                border: none;
                color: #64748b;
                cursor: pointer;
                padding: 5px 10px;
                border-radius: 4px;
                transition: all 0.2s ease;
                position: relative;
            }

            .btn-more:hover {
                background-color: #f1f5f9;
                color: #1abc9c;
            }

            .more-options {
                display: none;
                position: absolute;
                background: white;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                z-index: 1000;
                min-width: 150px;
                right: 0;
                top: 100%;
                margin-top: 5px;
            }

            .more-options ul {
                list-style: none;
                padding: 0;
                margin: 0;
            }

            .more-options li {
                padding: 0;
            }

            .more-options a {
                display: flex;
                align-items: center;
                padding: 10px 15px;
                color: #475569;
                text-decoration: none;
                transition: all 0.2s ease;
                white-space: nowrap;
            }

            .more-options a:hover {
                background-color: #f8fafc;
                color: #1abc9c;
            }

            .more-options i {
                margin-right: 8px;
                width: 16px;
            }

            /* Add container for relative positioning */
            .more-options-container {
                position: relative;
                display: inline-block;
            }

            .header-content {
                display: flex;
                justify-content: space-between;
                align-items: center;
                width: 100%;
            }

            .search-filter-container {
                margin: 20px 0;
                padding: 0 20px;
            }

            .search-container {
                position: relative;
                max-width: 500px;
                margin: 0 auto;
            }

            .search-input {
                width: 100%;
                padding: 12px 20px 12px 45px;
                border: 2px solid #e0e0e0;
                border-radius: 25px;
                font-size: 14px;
                transition: all 0.3s ease;
                background-color: #fff;
                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            }

            .search-input:focus {
                outline: none;
                border-color: #8B0000;
                box-shadow: 0 0 0 3px rgba(139, 0, 0, 0.1);
            }

            .search-icon {
                position: absolute;
                left: 15px;
                top: 50%;
                transform: translateY(-50%);
                color: #8B0000;
                font-size: 16px;
            }

            .search-input::placeholder {
                color: #999;
            }

            @media (max-width: 768px) {
                .search-container {
                    max-width: 100%;
                }
            }

            .more-options-container {
                position: relative;
                display: inline-block;
            }

            .btn-more {
                background: none;
                border: none;
                cursor: pointer;
                padding: 5px;
                color: #666;
            }

            .btn-more:hover {
                color: #333;
            }

            .more-options-menu {
                position: absolute;
                right: 0;
                top: 100%;
                background: white;
                border: 1px solid #ddd;
                border-radius: 4px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
                z-index: 1000;
                min-width: 150px;
                margin-top: 5px;
            }

            .option-btn {
                display: block;
                width: 100%;
                padding: 8px 12px;
                text-align: left;
                border: none;
                background: none;
                cursor: pointer;
                color: #333;
                font-size: 14px;
                white-space: nowrap;
            }

            .option-btn:hover {
                background: #f5f5f5;
            }

            .option-btn i {
                margin-right: 8px;
                width: 16px;
                text-align: center;
            }

            /* Modal Styles */
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

            .modal-content {
                background-color: #fefefe;
                margin: 15% auto;
                padding: 20px;
                border: 1px solid #888;
                width: 80%;
                max-width: 500px;
                border-radius: 5px;
                position: relative;
            }

            .close {
                color: #aaa;
                float: right;
                font-size: 28px;
                font-weight: bold;
                cursor: pointer;
            }

            .close:hover {
                color: black;
            }

            /* GCash Modal Styles */
            .gcash-modal {
                display: none;
                position: fixed;
                z-index: 1000;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0,0,0,0.5);
            }

            .gcash-modal-content {
                background-color: #fefefe;
                margin: 5% auto;
                padding: 20px;
                border: 1px solid #888;
                width: 90%;
                max-width: 600px;
                border-radius: 5px;
                position: relative;
            }

            .gcash-close {
                color: #aaa;
                float: right;
                font-size: 28px;
                font-weight: bold;
                cursor: pointer;
            }

            .gcash-close:hover {
                color: black;
            }

            #gcashImageContainer, #paymentImageContainer {
                margin-top: 20px;
                text-align: center;
            }

            #gcashImage, #paymentImage {
                max-width: 100%;
                height: auto;
                border-radius: 5px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            }

            /* Receipt Modal Styles */
            .receipt-container {
                padding: 20px;
                background: white;
            }

            /* Pagination Styles */
            .pagination {
                display: flex;
                justify-content: center;
                list-style: none;
                padding: 0;
                margin: 20px 0;
            }

            .pagination li {
                margin: 0 5px;
                display: none; /* Hide all by default */
            }

            /* Show current set of 3 numbers and navigation buttons */
            .pagination li.page-item.active,
            .pagination li.page-item.active + li,
            .pagination li.page-item.active + li + li,
            .pagination li.page-item:last-child {
                display: inline-block;
            }

            /* Show previous button if not on first set */
            .pagination li.page-item:first-child {
                display: inline-block;
            }

            /* Style for the Next 5 button */
            .pagination .next-5 {
                background-color: #f8f9fa;
                border: 1px solid #ddd;
                border-radius: 4px;
                padding: 8px 12px;
                margin: 0 5px;
                color: #333;
                text-decoration: none;
                transition: all 0.3s ease;
                cursor: pointer;
            }

            .pagination .next-5:hover {
                background-color: #e9ecef;
            }

            .pagination .page-link {
                padding: 8px 12px;
                border: 1px solid #ddd;
                border-radius: 4px;
                color: #333;
                text-decoration: none;
                transition: all 0.3s ease;
            }

            .pagination .page-link:hover {
                background-color: #f5f5f5;
            }

            .pagination .active .page-link {
                background-color: #8B0000;
                border-color: #8B0000;
                color: white;
            }

            /* Receipt Modal Divider Styles */
            #receiptModal .divider {
                border: none;
                height: 1px;
                background-color: #8B0000;
                margin: 20px 0;
                opacity: 0.3;
            }

            #receiptModal .receipt-section {
                margin: 25px 0;
                padding: 15px 0;
            }

            #receiptModal .receipt-section h3 {
                font-size: 1.13rem;
                font-weight: 700;
                margin-bottom: 15px;
                margin-top: 0;
                color: #8B0000;
                letter-spacing: 0.5px;
                text-transform: uppercase;
            }

            #receiptModal .receipt-total {
                margin: 25px 0;
                padding: 0;
                background: none;
            }

            #receiptModal .receipt-total .receipt-row-wide {
                font-size: 1.08rem;
                margin-bottom: 10px;
                font-weight: 500;
                color: #222;
            }

            #receiptModal .receipt-total .receipt-row-wide:last-child {
                margin-bottom: 0;
            }

            #receiptModal .receipt-total .receipt-row-wide .label {
                font-weight: 500;
            }

            #receiptModal .receipt-total .receipt-row-wide .value {
                font-weight: 400;
            }

            /* Remove the total-row specific styles since we're not using them anymore */
            #receiptModal .receipt-row-wide.total-row {
                background: none;
                padding: 0;
                margin: 0;
            }

            /* E-Receipt Modern Card Style */
            #receiptModal .modal-content {
                background: #fff;
                border-radius: 18px;
                box-shadow: 0 6px 32px rgba(139,0,0,0.10), 0 1.5px 6px rgba(0,0,0,0.04);
                padding: 0;
                max-width: 440px;
                margin: 40px auto;
                font-family: 'Segoe UI', 'Poppins', Arial, sans-serif;
                overflow: hidden;
            }

            #receiptModal .receipt-container {
                padding: 36px 28px 28px 28px;
                background: #faf9f6;
                border-radius: 18px;
            }

            #receiptModal .receipt-header {
                text-align: center;
                margin-bottom: 18px;
            }

            #receiptModal .receipt-header img {
                max-width: 90px;
                margin-bottom: 8px;
            }

            #receiptModal .receipt-header h2 {
                font-size: 1.25rem;
                font-weight: 700;
                margin: 0 0 4px 0;
                letter-spacing: 1px;
                color: #8B0000;
            }

            #receiptModal .receipt-header p {
                margin: 0;
                font-size: 1.05rem;
                font-weight: 500;
                letter-spacing: 0.5px;
                color: #333;
            }

            /* Receipt Modal Divider Styles */
            #receiptModal .divider {
                border: none;
                height: 2px;
                background-color: #8B0000;
                margin: 20px 0;
                opacity: 0.3;
            }

            #receiptModal .receipt-row-wide {
                display: flex;
                justify-content: space-between;
                align-items: center;
                width: 100%;
                font-size: 1.08rem;
                margin-bottom: 10px;
                font-weight: 500;
                color: #222;
            }

            #receiptModal .receipt-row-wide .label {
                flex: 1;
                text-align: left;
                font-weight: 500;
            }

            #receiptModal .receipt-row-wide .value {
                flex: 1;
                text-align: right;
                font-weight: 400;
            }

            #receiptModal .receipt-section {
                margin: 25px 0;
                padding: 15px 0;
            }

            #receiptModal .receipt-section h3 {
                font-size: 1.13rem;
                font-weight: 700;
                margin-bottom: 15px;
                margin-top: 0;
                color: #8B0000;
                letter-spacing: 0.5px;
                text-transform: uppercase;
            }

            #receiptModal .duration-text {
                font-style: italic;
                font-size: 0.98rem;
                margin-top: 6px;
                margin-bottom: 0;
                color: #555;
            }

            #receiptModal .receipt-footer {
                margin-top: 30px;
                padding-top: 20px;
                border-top: 2.5px solid #8B0000;
                opacity: 0.85;
                text-align: center;
                font-size: 1.01rem;
                font-style: italic;
                color: #8B0000;
                font-weight: 600;
            }

            #receiptModal .receipt-footer p {
                margin: 4px 0;
            }

            #receiptModal .close {
                position: absolute;
                top: 18px;
                right: 24px;
                font-size: 28px;
                color: #aaa;
                cursor: pointer;
                background: none;
                border: none;
                z-index: 2;
            }

            #receiptModal .close:hover {
                color: #8B0000;
            }

            #receiptModal .print-btn {
                background: #8B0000;
                color: #fff;
                border: none;
                padding: 10px 22px;
                border-radius: 8px;
                cursor: pointer;
                font-size: 1.08rem;
                margin-top: 18px;
                margin-bottom: 0;
                float: right;
                font-weight: 600;
                box-shadow: 0 2px 8px rgba(139,0,0,0.08);
                transition: background 0.2s;
            }

            #receiptModal .print-btn:hover {
                background: #a52a2a;
            }

            @media print {
                body, html {
                    background: #fff !important;
                    margin: 0 !important;
                    padding: 0 !important;
                    height: 100% !important;
                    width: 100% !important;
                }
                #receiptModal .modal-content, #receiptModal .receipt-container {
                    box-shadow: none !important;
                    border-radius: 0 !important;
                    margin: 0 auto !important;
                    max-width: 100vw !important;
                    width: 100vw !important;
                    padding: 0 !important;
                    background: #fff !important;
                }
                #receiptModal .receipt-container {
                    padding: 24px 24px 16px 24px !important;
                    width: 100vw !important;
                    max-width: 100vw !important;
                }
                #receiptModal .print-btn, #receiptModal .close {
                    display: none !important;
                }
                #receiptModal .receipt-footer {
                    page-break-after: avoid !important;
                }
                .modal, .modal-content, .receipt-container {
                    page-break-inside: avoid !important;
                }
                @page {
                    size: A4 portrait;
                    margin: 0.5cm;
                }
            }
            </style>

            <!-- GCash Modal -->
            <div id="gcashModal" class="gcash-modal">
                <div class="gcash-modal-content">
                    <span class="gcash-close" onclick="closeGcashModal()">&times;</span>
                    <h2>GCash Payment Receipt</h2>
                    <div id="gcashImageContainer">
                        <img id="gcashImage" src="" alt="GCash Payment Receipt"
                            onerror="this.onerror=null; this.src='{{ asset('images/no-image.png') }}';">
                    </div>
                </div>
            </div>

            <!-- Payment Modal -->
            <div id="paymentModal" class="gcash-modal">
                <div class="gcash-modal-content">
                    <span class="gcash-close" onclick="closePaymentModal()">&times;</span>
                    <h2>Payment Receipt</h2>
                    <div id="paymentImageContainer">
                        <img id="paymentImage" src="" alt="Payment Receipt"
                            onerror="this.onerror=null; this.src='{{ asset('images/no-image.png') }}';">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- FullCalendar JS -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
    // Debug logging
    console.log('Appointments page loaded');
    console.log('Appointments data:', @json($appointments));

    // Global utility functions
    function formatTime(timeString) {
        console.log('Formatting time:', timeString);
        const [hours, minutes] = timeString.split(':');
        const hour = parseInt(hours);
        const ampm = hour >= 12 ? 'PM' : 'AM';
        const hour12 = hour % 12 || 12;
        return `${hour12}:${minutes} ${ampm}`;
    }

    // Global modal functions
    function openRejectModal(appointmentId) {
        console.log('Opening reject modal for appointment:', appointmentId);
        // Close any existing calendar modals
        document.querySelectorAll('.calendar-event-modal').forEach(modal => modal.remove());

        document.getElementById('rejectForm').action = `/cashier/appointments/${appointmentId}/reject`;
        document.getElementById('rejectModal').style.display = 'block';
    }

    function closeRejectModal() {
        console.log('Closing reject modal');
        document.getElementById('rejectModal').style.display = 'none';
    }

    function openRescheduleModal(appointmentId) {
        console.log('Opening reschedule modal for appointment:', appointmentId);
        // Close any existing calendar modals
        document.querySelectorAll('.calendar-event-modal').forEach(modal => modal.remove());

        const row = document.querySelector(`tr[data-appointment-id="${appointmentId}"]`);
        if (!row) {
            console.error('Appointment row not found:', appointmentId);
            return;
        }

        const date = new Date(row.dataset.date);
        const formattedDate = date.toISOString().split('T')[0];
        const formattedTime = formatTime(row.dataset.time);
        const serviceId = row.dataset.serviceId;

        console.log('Appointment details:', {
            date: formattedDate,
            time: formattedTime,
            serviceId: serviceId
        });

        document.getElementById('currentDateDisplay').textContent = date.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });
        document.getElementById('currentTimeDisplay').textContent = formattedTime;
        document.getElementById('rescheduleForm').action = `/cashier/appointments/${appointmentId}/reschedule`;
        document.getElementById('rescheduleModal').style.display = 'block';

        // Set initial date and populate time slots
        const dateInput = document.getElementById('reschedule_date');
        dateInput.value = formattedDate;
        populateTimeSlots(formattedDate);

        // Load staff based on service
        loadStaffByService(serviceId);
    }

    function closeRescheduleModal() {
        console.log('Closing reschedule modal');
        document.getElementById('rescheduleModal').style.display = 'none';
    }

    function populateTimeSlots(selectedDate) {
        console.log('Populating time slots for date:', selectedDate);
        const timeSlotSelect = document.getElementById('timeSlot');
        timeSlotSelect.innerHTML = '<option value="">Select a time slot</option>';

        // Generate time slots from 9 AM to 5 PM
        const startHour = 9;
        const endHour = 17;

        for (let hour = startHour; hour <= endHour; hour++) {
            const timeValue = `${hour.toString().padStart(2, '0')}:00`;
            const displayTime = formatTime(timeValue);
            const option = document.createElement('option');
            option.value = timeValue;
            option.textContent = displayTime;
            timeSlotSelect.appendChild(option);
        }
    }

    // Add error handling for AJAX requests
    function loadStaffByService(serviceId) {
        console.log('Loading staff for service:', serviceId);
        fetch(`/cashier/appointments/staff-by-service/${serviceId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Staff data received:', data);
                const staffSelect = document.getElementById('selected_staff');
                staffSelect.innerHTML = '<option value="">Select staff members</option>';

                if (data.status === 'success' && data.staff) {
                    data.staff.forEach(staff => {
                        const option = document.createElement('option');
                        option.value = JSON.stringify({
                            id: staff.id,
                            firstName: staff.first_name,
                            lastName: staff.last_name
                        });
                        option.textContent = `${staff.first_name} ${staff.last_name}`;
                        staffSelect.appendChild(option);
                    });
                }
            })
            .catch(error => {
                console.error('Error loading staff:', error);
                const staffSelect = document.getElementById('selected_staff');
                staffSelect.innerHTML = '<option value="">Error loading staff</option>';
            });
    }

    // Initialize view toggle functionality
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM loaded, initializing view toggle');
        const tableViewBtn = document.getElementById('tableViewBtn');
        const calendarViewBtn = document.getElementById('calendarViewBtn');
        const tableView = document.getElementById('tableView');
        const calendarView = document.getElementById('calendarView');

        if (tableViewBtn && calendarViewBtn && tableView && calendarView) {
            tableViewBtn.addEventListener('click', function() {
                console.log('Switching to table view');
                tableView.style.display = 'block';
                calendarView.style.display = 'none';
                tableViewBtn.classList.add('active');
                calendarViewBtn.classList.remove('active');
            });

            calendarViewBtn.addEventListener('click', function() {
                console.log('Switching to calendar view');
                tableView.style.display = 'none';
                calendarView.style.display = 'block';
                calendarViewBtn.classList.add('active');
                tableViewBtn.classList.remove('active');
                if (!window.calendarInitialized) {
                    initializeCalendar();
                    window.calendarInitialized = true;
                }
            });
        } else {
            console.error('View toggle elements not found');
        }
    });

    // Initialize calendar
    function initializeCalendar() {
        console.log('Initializing calendar...');
        const calendarEl = document.getElementById('calendar');
        
        if (!calendarEl) {
            console.error('Calendar element not found!');
            return;
        }

        // Fetch all appointments
        fetch('/cashier/appointments/all')
            .then(response => response.json())
            .then(appointments => {
                console.log('Fetched appointments:', appointments);
                
                const events = appointments.map(appointment => {
                    const date = appointment.appointment_date;
                    const time = appointment.appointment_time;
                    const [hours, minutes] = time.split(':');

                    const startDateTime = new Date(date);
                    startDateTime.setHours(parseInt(hours), parseInt(minutes), 0, 0);

                    const endDateTime = new Date(startDateTime);
                    endDateTime.setHours(endDateTime.getHours() + 1);

                    // Format staff data
                    let staff = appointment.selected_staff;
                    if (Array.isArray(staff)) {
                        staff = staff.map(staffMember => {
                            if (typeof staffMember === 'object') {
                                return `${staffMember.first_name || staffMember.firstName} ${staffMember.last_name || staffMember.lastName}`;
                            }
                            return staffMember;
                        }).join(', ');
                    } else if (typeof staff === 'object') {
                        staff = `${staff.first_name || staff.firstName} ${staff.last_name || staff.lastName}`;
                    }
                    staff = !staff || staff === 'N/A' ? 'No staff assigned' : staff;

                    // Format time for display
                    const formattedTime = formatTime(time);

                    return {
                        id: appointment.id,
                        title: `${appointment.full_name} - ${appointment.service_name}`,
                        start: startDateTime.toISOString(),
                        end: endDateTime.toISOString(),
                        className: `fc-event-${appointment.status.toLowerCase()}`,
                        extendedProps: {
                            id: appointment.id,
                            clientName: appointment.full_name,
                            phone: appointment.phone_number,
                            staff: staff,
                            service: appointment.service_name,
                            status: appointment.status.toLowerCase(),
                            date: date,
                            time: time,
                            formattedTime: formattedTime
                        }
                    };
                });

                console.log('Processed events:', events);

                const calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    headerToolbar: {
                        left: 'prev,next',
                        center: 'title',
                        right: 'dayGridMonth'
                    },
                    titleFormat: {
                        year: 'numeric',
                        month: 'long'
                    },
                    buttonText: {
                        month: 'Appointments Calendar'
                    },
                    events: events,
                    eventContent: function(arg) {
                        return {
                            html: `
                                <div class="fc-event-time">${arg.event.extendedProps.formattedTime}</div>
                                <div class="fc-event-title">${arg.event.extendedProps.clientName}</div>
                            `
                        };
                    },
                    eventClick: function(info) {
                        const event = info.event;
                        const status = event.extendedProps.status;
                        const formattedTime = event.extendedProps.formattedTime;

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
                                <div class="event-actions">
                                    ${status === 'pending' ? `
                                        <button class="btn-accept" onclick="handleCalendarAction('accept', ${event.extendedProps.id})">Accept</button>
                                        <button class="btn-reject" onclick="openRejectModal(${event.extendedProps.id})">Reject</button>
                                    ` : ''}
                                    ${status === 'accepted' ? `
                                       
                                    ` : ''}
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
                    }
                });

                calendar.render();
                window.calendar = calendar; // Store calendar instance globally
                console.log('Calendar initialized successfully');
            })
            .catch(error => {
                console.error('Error loading appointments:', error);
                calendarEl.innerHTML = '<div class="error-message">Error loading appointments. Please try again.</div>';
            });
    }

    // Add event listener for date change
    const dateInput = document.getElementById('reschedule_date');
    if (dateInput) {
        dateInput.addEventListener('change', function() {
            populateTimeSlots(this.value);
        });
    }

    function toggleMoreOptions(appointmentId) {
        console.log('Toggling options for appointment:', appointmentId);
        const optionsContainer = document.querySelector(`.more-options-container[data-appointment-id="${appointmentId}"]`);
        const optionsMenu = optionsContainer.querySelector('.more-options-menu');
        
        if (!optionsContainer || !optionsMenu) {
            console.error('Options container or menu not found');
            return;
        }
        
        // Close all other open menus first
        document.querySelectorAll('.more-options-menu').forEach(menu => {
            if (menu !== optionsMenu) {
                menu.style.display = 'none';
            }
        });
        
        // Toggle the clicked menu
        if (optionsMenu.style.display === 'block') {
            optionsMenu.style.display = 'none';
        } else {
            optionsMenu.style.display = 'block';
        }
    }

    function openGcashModal(imageUrl) {
        console.log('Opening GCash modal with image:', imageUrl);
        const modal = document.getElementById('gcashModal');
        const image = document.getElementById('gcashImage');
        
        if (!modal || !image) {
            console.error('Modal or image element not found');
            return;
        }
        
        // Set image source
        image.src = imageUrl;
        
        // Show modal
        modal.style.display = 'block';
        
        // Add error handling for image
        image.onerror = function() {
            console.error('Error loading image:', imageUrl);
            this.src = '{{ asset('images/no-image.png') }}';
        };
    }

    function closeGcashModal() {
        const modal = document.getElementById('gcashModal');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    // Close menus when clicking outside
    document.addEventListener('click', function(event) {
        if (!event.target.closest('.more-options-container')) {
            document.querySelectorAll('.more-options-menu').forEach(menu => {
                menu.style.display = 'none';
            });
        }
        
        // Close GCash modal when clicking outside
        const gcashModal = document.getElementById('gcashModal');
        if (event.target === gcashModal) {
            gcashModal.style.display = 'none';
        }
    });

    function viewDetails(appointmentId, uploadPicture) {
        if (uploadPicture) {
            openPaymentModal('{{ asset('storage/') }}/' + uploadPicture);
        } else {
            alert('No payment receipt available');
        }
    }

    function viewReceipt(appointmentId) {
        console.log('Fetching receipt data for appointment:', appointmentId);

        // Show loading state
        const modal = document.getElementById('receiptModal');
        modal.style.display = 'block';
        modal.querySelector('.modal-content').innerHTML =
            '<div style="text-align: center; padding: 20px;">Loading receipt data...</div>';

        // Fetch receipt data
        fetch(`/cashier/appointments/${appointmentId}/ereceipt`)
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Received data:', data);

                // Restore modal content
                const modalContent = document.createElement('div');
                modalContent.className = 'modal-content';
                modalContent.style.maxWidth = '800px';
                modalContent.innerHTML = `
                    <span class="close" onclick="closeReceiptModal()">&times;</span>
                    <div class="receipt-container" id="receiptToPrint">
                        <!-- Header -->
                        <div class="receipt-header">
                            <img src="{{ asset('images/newlogo.png') }}" alt="King Salon Logo" style="width: 150px; height: auto;">
                            <h2>VAT REG. TIN#314-007-068-00</h2>
                            <p>ANGATAN TABUC-TUBIG</p>
                            <p>DUMAGUETE CITY</p>
                        </div>

                        <!-- Receipt Info -->
                        <div class="receipt-info">
                            <div class="receipt-row-wide">
                                <span class="label">Sales Invoice No:</span>
                                <span class="value" id="receipt-invoice">0000000001</span>
                            </div>
                            <div class="receipt-row-wide">
                                <span class="label">TXN NUMBER:</span>
                                <span class="value" id="receipt-txn">0000000000</span>
                            </div>
                            <div class="receipt-row-wide">
                                <span class="label">CASHIER: REGINE</span>
                                <span class="value" id="receipt-date-time"></span>
                            </div>
                            <div class="receipt-row-wide">
                                <span class="label">BILLING TO:</span>
                                <span class="value" id="receipt-client">${data.client_name}</span>
                            </div>
                        </div>

                        <!-- Services -->
                        <div class="receipt-section">
                            <h3>SERVICES</h3>
                            <div id="receipt-services">
                                ${data.services.map(service => `
                                    <div class="receipt-row-wide">
                                        <span class="label">${service.name}</span>
                                        <span class="value">₱${service.price.toFixed(2)}</span>
                                    </div>
                                `).join('')}
                            </div>
                            <p id="receipt-duration" class="duration-text">Total Duration: ${data.duration}</p>
                        </div>

                        <!-- Products -->
                        ${data.products && data.products.length > 0 ? `
                            <div class="receipt-section">
                                <h3>PRODUCTS</h3>
                                <div id="receipt-products">
                                    ${data.products.map(product => `
                                        <div class="receipt-row-wide">
                                            <span class="label">${product.name} x${product.quantity}</span>
                                            <span class="value">₱${product.total_price.toFixed(2)}</span>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        ` : ''}

                        <!-- Staff and Time -->
                        <div class="receipt-details">
                            <div class="receipt-row-wide">
                                <span class="label">STYLIST:</span>
                                <span class="value" id="receipt-stylist">${data.stylist}</span>
                            </div>
                            <div class="receipt-row-wide">
                                <span class="label">TIME:</span>
                                <span class="value" id="receipt-time">${data.time}</span>
                            </div>
                            <div class="receipt-row-wide">
                                <span class="label">DATE:</span>
                                <span class="value" id="receipt-date">${data.date}</span>
                            </div>
                        </div>

                        <!-- Total and Payment -->
                        <div class="receipt-total">
                            <div class="receipt-row-wide">
                                <span class="label">TOTAL AMOUNT:</span>
                                <span class="value" id="receipt-total">₱${data.total_amount.toFixed(2)}</span>
                            </div>
                            <div class="receipt-row-wide">
                                <span class="label">PAYMENT METHOD:</span>
                                <span class="value" id="receipt-payment">${data.payment_method}</span>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="receipt-footer">
                            <p>THANK YOU COME AGAIN</p>
                            <p>PLEASE FOLLOW US ON FB: KING SALON II</p>
                            <p>For Customers Feedback and Inquires</p>
                            <p>You may send an email to:</p>
                            <p>info@kingsalon@gmail.com</p>
                            <p>THE OFFICIAL RECEIPT WILL BE VALID FIVE (5) YEARS FROM DATE OF APPOINTMENT</p>
                        </div>
                    </div>
                    <div class="receipt-actions">
                        <button onclick="printEReceipt()" class="print-btn">
                            <i class="fas fa-print"></i> Print Receipt
                        </button>
                        <button onclick="saveAsPDF()" class="btn-save-pdf">
                            <i class="fas fa-file-pdf"></i> Save as PDF
                        </button>
                    </div>
                `;

                modal.innerHTML = '';
                modal.appendChild(modalContent);
            })
            .catch(error => {
                console.error('Error fetching receipt data:', error);
                modal.querySelector('.modal-content').innerHTML = `
                    <div style="text-align: center; padding: 20px;">
                        <h3>Error Loading Receipt</h3>
                        <p>${error.message}</p>
                        <button onclick="closeReceiptModal()">Close</button>
                    </div>
                `;
            });
    }

    function closeReceiptModal() {
        document.getElementById('receiptModal').style.display = 'none';
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('receiptModal');
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }

    function openPaymentModal(imageUrl) {
        console.log('Opening payment modal with image URL:', imageUrl);
        const modal = document.getElementById('paymentModal');
        const image = document.getElementById('paymentImage');

        image.src = imageUrl;
        modal.style.display = 'block';

        image.onerror = function() {
            console.error('Error loading image:', imageUrl);
            this.src = '{{ asset('images/no-image.png') }}';
        };
    }

    function closePaymentModal() {
        document.getElementById('paymentModal').style.display = 'none';
    }

    // Close payment modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('paymentModal');
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }

    function viewEReceipt(appointmentId) {
        console.log('Fetching receipt data for appointment:', appointmentId);
        // Show loading state
        const modal = document.getElementById('receiptModal');
        modal.style.display = 'block';
        modal.querySelector('.receipt-container').innerHTML = '<div style="text-align: center; padding: 20px;">Loading receipt data...</div>';

        // Fetch receipt data
        fetch(`/cashier/appointments/${appointmentId}/ereceipt`)
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Received data:', data);
                // Fill in the receipt fields
                document.getElementById('receipt-invoice').textContent = '0000000001';
                document.getElementById('receipt-txn').textContent = '0000000000';
                document.getElementById('receipt-client').textContent = data.client_name;
                document.getElementById('receipt-date-time').textContent = data.date + ', ' + data.time;
                // Services
                document.getElementById('receipt-services').innerHTML = data.services.map(service =>
                    `<div class='receipt-row-wide'><span class='label'>${service.name}</span><span class='value'>₱${service.price.toFixed(2)}</span></div>`
                ).join('');
                document.getElementById('receipt-duration').textContent = 'Total Duration: ' + data.duration;
                // Products
                if (data.products && data.products.length > 0) {
                    document.getElementById('receipt-products-section').style.display = '';
                    document.getElementById('receipt-products').innerHTML = data.products.map(product =>
                        `<div class='receipt-row-wide'><span class='label'>${product.name} x${product.quantity}</span><span class='value'>₱${product.total_price.toFixed(2)}</span></div>`
                    ).join('');
                } else {
                    document.getElementById('receipt-products-section').style.display = 'none';
                }
                document.getElementById('receipt-stylist').textContent = data.stylist;
                document.getElementById('receipt-time').textContent = data.time;
                document.getElementById('receipt-date').textContent = data.date;
                document.getElementById('receipt-total').textContent = '₱' + data.total_amount.toFixed(2);
                document.getElementById('receipt-payment').innerHTML =
                    (data.payment_method && data.payment_method.toLowerCase() === 'gcash')
                        ? `<img src='https://upload.wikimedia.org/wikipedia/commons/5/5e/GCash_logo.png' alt='GCash' style='height:20px;vertical-align:middle;margin-right:6px;'> Gcash`
                        : data.payment_method;
            })
            .catch(error => {
                modal.querySelector('.receipt-container').innerHTML = `<div style='text-align:center;padding:20px;'><h3>Error Loading Receipt</h3><p>${error.message}</p><button onclick='closeReceiptModal()'>Close</button></div>`;
            });
    }

    function printEReceipt() {
        const modal = document.getElementById('receiptModal');
        const receiptContent = modal.querySelector('.receipt-container').cloneNode(true);
        // Get all <style> and <link rel="stylesheet"> tags
        let styles = '';
        document.querySelectorAll('style, link[rel="stylesheet"]').forEach(el => {
            styles += el.outerHTML;
        });
        // Create a print window
        const printWindow = window.open('', '', 'width=800,height=900');
        printWindow.document.write(`
            <html>
            <head>
                <title>Print Receipt</title>
                ${styles}
            </head>
            <body style="background: #faf9f6;">
                <div style="display: flex; justify-content: center; align-items: center; min-height: 100vh;">
                    <div style="box-shadow: 0 6px 32px rgba(139,0,0,0.10), 0 1.5px 6px rgba(0,0,0,0.04); border-radius: 18px; background: #fff;">
                        ${receiptContent.outerHTML}
                    </div>
                </div>
            </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.onload = function() {
            printWindow.focus();
            printWindow.print();
        };
    }

    function saveAsPDF() {
        const receiptContent = document.querySelector('#receiptModal .receipt-container');
        if (!receiptContent) return;
        const opt = {
            margin: 0.3,
            filename: 'receipt.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2 },
            jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
        };
        html2pdf().set(opt).from(receiptContent).save();
    }

    // Add this to your existing JavaScript
    document.getElementById('appointmentSearch').addEventListener('keyup', function() {
        const searchValue = this.value.toLowerCase().trim();
        const tableRows = document.querySelectorAll('.appointments-table tbody tr');
        let hasResults = false;

        tableRows.forEach(row => {
            const clientName = row.cells[0].textContent.toLowerCase();
            const phoneNumber = row.cells[1].textContent.toLowerCase();
            const staff = row.cells[2].textContent.toLowerCase();
            const service = row.cells[3].textContent.toLowerCase();
            const date = row.cells[4].textContent.toLowerCase();
            const time = row.cells[5].textContent.toLowerCase();
            const paymentMethod = row.cells[6].textContent.toLowerCase();
            const status = row.cells[7].textContent.toLowerCase();

            if (clientName.includes(searchValue) ||
                phoneNumber.includes(searchValue) ||
                staff.includes(searchValue) ||
                service.includes(searchValue) ||
                date.includes(searchValue) ||
                time.includes(searchValue) ||
                paymentMethod.includes(searchValue) ||
                status.includes(searchValue)) {
                row.style.display = '';
                hasResults = true;
            } else {
                row.style.display = 'none';
            }
        });

        // Show/hide "No results" message
        const noResultsRow = document.querySelector('.no-results-row');
        if (!hasResults && searchValue !== '') {
            if (!noResultsRow) {
                const tbody = document.querySelector('.appointments-table tbody');
                const newRow = document.createElement('tr');
                newRow.className = 'no-results-row';
                newRow.innerHTML =
                    '<td colspan="10" class="text-align: center">No appointments found matching your search.</td>';
                tbody.appendChild(newRow);
            }
        } else if (noResultsRow) {
            noResultsRow.remove();
        }
    });

    // Add calendar styles
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

        .fc-event-pending {
            background-color: #ffd700;
            border-color: #ffd700;
            color: #000;
        }

        .fc-event-accepted {
            background-color: #4CAF50;
            border-color: #4CAF50;
            color: white;
        }

        .fc-event-rejected {
            background-color: #f44336;
            border-color: #f44336;
        }

        .fc-event-completed {
            background-color: #2196F3;
            border-color: #2196F3;
        }

        .fc-event-staff {
            font-size: 0.85em;
            opacity: 0.9;
        }

        .fc-event-status {
            font-size: 0.85em;
            font-weight: 500;
        }

        .view-more-btn {
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            padding: 0 2px;
            font-size: 12px;
            line-height: 1;
        }

        .view-more-btn:hover {
            color: #333;
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
            background-color: #4CAF50;
            color: white;
        }

        .btn-accept:hover {
            background-color: #45a049;
        }

        .btn-reject {
            background-color: #f44336;
            color: white;
        }

        .btn-reject:hover {
            background-color: #da190b;
        }
    `;
    document.head.appendChild(calendarStyles);
    </script>

    <!-- Receipt Modal (E-Receipt) -->
    <div id="receiptModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeReceiptModal()">&times;</span>
            <div class="receipt-container">
                <div class="receipt-header">
                    <img src="{{ asset('images/newlogo.png') }}" alt="King Salon Logo">
                    <h2>VAT REG. TIN#314-007-068-00</h2>
                    <p>ANGATAN TABUC-TUBIG</p>
                    <p>DUMAGUETE CITY</p>
                </div>
                <hr class="divider">
                <div class="receipt-row-wide">
                    <span class="label">Sales Invoice No:</span>
                    <span class="value" id="receipt-invoice">0000000001</span>
                </div>
                <div class="receipt-row-wide">
                    <span class="label">TXN NUMBER:</span>
                    <span class="value" id="receipt-txn">0000000000</span>
                </div>
                <div class="receipt-row-wide">
                    <span class="label">CASHIER: REGINE</span>
                    <span class="value" id="receipt-date-time"></span>
                </div>
                <hr class="divider">
                <div class="receipt-section">
                    <h3>SERVICES</h3>
                    <div id="receipt-services"></div>
                    <p id="receipt-duration" class="duration-text"></p>
                </div>
                <hr class="divider">
                <div class="receipt-section" id="receipt-products-section" style="display: none;">
                    <h3>PRODUCTS</h3>
                    <div id="receipt-products"></div>
                </div>
                <hr class="divider">
                <div class="receipt-row-wide">
                    <span class="label">STYLIST:</span>
                    <span class="value" id="receipt-stylist"></span>
                </div>
                <div class="receipt-row-wide">
                    <span class="label">TIME:</span>
                    <span class="value" id="receipt-time"></span>
                </div>
                <div class="receipt-row-wide">
                    <span class="label">DATE:</span>
                    <span class="value" id="receipt-date"></span>
                </div>
                <hr class="divider">
                <div class="receipt-row-wide">
                    <span class="label">TOTAL AMOUNT:</span>
                    <span class="value" id="receipt-total"></span>
                </div>
                <div class="receipt-row-wide">
                    <span class="label">PAYMENT METHOD:</span>
                    <span class="value" id="receipt-payment"></span>
                </div>
                <hr class="divider">
                <div class="receipt-footer">
                    <p>THANK YOU COME AGAIN</p>
                    <p>PLEASE FOLLOW US ON FB: KING SALON II</p>
                    <p>For Customers Feedback and Inquires</p>
                    <p>You may sned an email to:</p>
                    <p>info@kingsalon@gmail.com</p>
                    <p>THE OFFICIAL RECEIPT WILL BE VALID FIVE (5) YEARS FROM DATE OF APPOINTMENT</p>
                </div>
                <div class="receipt-actions">
                    <button onclick="printEReceipt()" class="print-btn">
                        <i class="fas fa-print"></i> Print Receipt
                    </button>
                    <button onclick="saveAsPDF()" class="btn-save-pdf">
                        <i class="fas fa-file-pdf"></i> Save as PDF
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    function toggleEventContent(button) {
        const textElement = button.previousElementSibling;
        if (textElement.style.whiteSpace === 'nowrap') {
            textElement.style.whiteSpace = 'normal';
            button.textContent = '▲';
        } else {
            textElement.style.whiteSpace = 'nowrap';
            button.textContent = '...';
        }
    }
    </script>
</body>

</html>