<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Attendance Tracking</title>
    <link rel="stylesheet" href="{{ asset('css/managerdashboard.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            overflow-y: auto;
            height: 100%;
        }
        .container {
            min-height: 100vh;
            overflow-y: auto;
        }
        .main-content {
            overflow-y: auto;
            height: 100%;
        }
        .attendance-section {
            padding: 20px;
            min-height: 100%;
        }
        .attendance-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .attendance-header {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .attendance-header h2 {
            margin: 0;
            color: #333;
            font-size: 1.5rem;
        }
        .attendance-body {
            padding: 20px;
        }
        .filters {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .filter-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .filter-group label {
            font-weight: 500;
            color: #555;
        }
        .filter-group select,
        .filter-group input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        .attendance-table-container {
            overflow-y: auto;
            max-height: calc(100vh - 400px);
            border: 1px solid #eee;
            border-radius: 4px;
        }
        .attendance-table {
            width: 100%;
            border-collapse: collapse;
        }
        .attendance-table th {
            background-color: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #333;
        }
        .attendance-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        .attendance-table tr:hover {
            background-color: #f8f9fa;
        }
        .attendance-table thead {
            position: sticky;
            top: 0;
            background-color: #f8f9fa;
            z-index: 1;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .status-present {
            background-color: #28a745;
            color: white;
        }
        .status-absent {
            background-color: #dc3545;
            color: white;
        }
        .status-late {
            background-color: #ffc107;
            color: #000;
        }
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .summary-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        .summary-card h3 {
            margin: 0;
            font-size: 2rem;
            color: #333;
        }
        .summary-card p {
            margin: 5px 0 0;
            color: #666;
            font-size: 0.9rem;
        }
        .export-btn {
            padding: 8px 15px;
            background-color: #dc3545;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            margin-right: 10px;
        }
        .export-btn:hover {
            background-color: #b02a37;
        }
        .export-buttons {
            display: flex;
            gap: 10px;
        }
        .outside-hours {
            color: #ffc107;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <div class="sidebar-logo">
                <img src="{{ asset('images/kingsalon.png') }}" alt="Manager Logo" style="width: 200px; height: auto;">
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

        <div class="main-content">
            <header>
                <div class="header-content">
                    <h1>Staff Attendance Tracking</h1>
                    <div class="header-right">
                        <div class="settings-container">
                            <i class="fas fa-cog settings-icon"></i>
                            <div class="settings-dropdown">
                                <form action="{{ route('manager.logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="logout-btn">
                                        <i class="fas fa-sign-out-alt"></i> Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <section class="attendance-section">
                <div class="summary-cards">
                    <div class="summary-card">
                        <h3>{{ $totalPresent }}</h3>
                        <p>Present Today</p>
                    </div>
                    <div class="summary-card">
                        <h3>{{ $totalAbsent }}</h3>
                        <p>Absent Today</p>
                    </div>
                    <div class="summary-card">
                        <h3>{{ $totalLate }}</h3>
                        <p>Late Today</p>
                    </div>
                    <div class="summary-card">
                        <h3>{{ $averageHours }}</h3>
                        <p>Average Hours</p>
                    </div>
                </div>

                <div class="attendance-card">
                    <div class="attendance-header">
                        <h2><i class="fas fa-clock"></i> Attendance Records</h2>
                        <div class="export-buttons">
                            <button class="export-btn" onclick="exportAttendance()">
                                <i class="fas fa-file-excel"></i> Export to Excel
                            </button>
                            <button class="export-btn" onclick="exportToPDF()">
                                <i class="fas fa-file-pdf"></i> Export to PDF
                            </button>
                        </div>
                    </div>
                    <div class="attendance-body">
                        <div class="filters">
                            <div class="filter-group">
                                <label>Staff:</label>
                                <select id="staff-filter" onchange="filterAttendance()">
                                    <option value="">All Staff</option>
                                    @foreach($staff as $member)
                                        <option value="{{ $member->id }}">{{ $member->first_name }} {{ $member->last_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="filter-group">
                                <label>Date Range:</label>
                                <input type="date" id="start-date" onchange="filterAttendance()">
                                <input type="date" id="end-date" onchange="filterAttendance()">
                            </div>
                            <div class="filter-group">
                                <label>Status:</label>
                                <select id="status-filter" onchange="filterAttendance()">
                                    <option value="">All</option>
                                    <option value="present">Present</option>
                                    <option value="absent">Absent</option>
                                    <option value="late">Late</option>
                                </select>
                            </div>
                        </div>

                        <div class="attendance-table-container">
                        <table class="attendance-table">
                            <thead>
                                <tr>
                                    <th>Staff Name</th>
                                    <th>Date</th>
                                    <th>Time In</th>
                                    <th>Time Out</th>
                                    <th>Status</th>
                                    <th>Total Hours</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($attendanceRecords as $record)
                                <tr>
                                    <td>{{ $record->staff->first_name }} {{ $record->staff->last_name }}</td>
                                    <td>{{ $record->date->format('M d, Y') }}</td>
                                    <td>{{ $record->time_in ? $record->time_in->format('h:i A') : '-' }}</td>
                                    <td>{{ $record->time_out ? $record->time_out->format('h:i A') : '-' }}</td>
                                    <td>
                                        <span class="status-badge status-{{ $record->status }}">
                                            {{ ucfirst($record->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $record->formatted_hours }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.5.3/jspdf.min.js"></script>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        // Set default date range to current month
        const today = new Date();
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
        
        document.getElementById('start-date').value = firstDay.toISOString().split('T')[0];
        document.getElementById('end-date').value = lastDay.toISOString().split('T')[0];

        // Settings dropdown
        const settingsIcon = document.querySelector(".settings-icon");
        const settingsContainer = document.querySelector(".settings-container");

        settingsIcon.addEventListener("click", function() {
            settingsContainer.classList.toggle("active");
        });

        document.addEventListener("click", function(event) {
            if (!settingsContainer.contains(event.target)) {
                settingsContainer.classList.remove("active");
            }
        });
    });

    function formatTotalHours(hours) {
        if (!hours) return '-';
        const totalMinutes = Math.round(parseFloat(hours) * 60);
        const hoursInt = Math.floor(totalMinutes / 60);
        const minutes = totalMinutes % 60;
        return (hoursInt > 0 ? hoursInt + 'h ' : '') + (minutes > 0 ? minutes + 'm' : '0m');
    }

    function isWithinBusinessHours(time) {
        if (!time) return false;
        
        const timeDate = new Date(time);
        const hours = timeDate.getHours();
        const minutes = timeDate.getMinutes();
        
        // Convert to minutes for easier comparison
        const timeInMinutes = hours * 60 + minutes;
        const openingTime = 9 * 60; // 9:00 AM
        const closingTime = 21 * 60; // 9:00 PM
        
        return timeInMinutes >= openingTime && timeInMinutes <= closingTime;
    }

    function filterAttendance() {
        const staffId = document.getElementById('staff-filter').value;
        const startDate = document.getElementById('start-date').value;
        const endDate = document.getElementById('end-date').value;
        const status = document.getElementById('status-filter').value;

        // Show loading state
        const tableBody = document.querySelector('.attendance-table tbody');
        tableBody.innerHTML = '<tr><td colspan="6" class="text-center">Loading...</td></tr>';

        // Make AJAX call to fetch filtered data
        fetch(`/manager/attendance/filter?staff_id=${staffId}&start_date=${startDate}&end_date=${endDate}&status=${status}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            // Clear the table
            tableBody.innerHTML = '';
            
            // Calculate total hours and count for average
            let totalHours = 0;
            let presentCount = 0;
            
            // Populate with new data
            data.forEach(record => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${record.staff.first_name} ${record.staff.last_name}</td>
                    <td>${new Date(record.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}</td>
                    <td>${record.time_in ? new Date(record.time_in).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true }) : '-'}</td>
                    <td>${record.time_out ? new Date(record.time_out).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true }) : '-'}</td>
                    <td>
                        <span class="status-badge status-${record.status}">
                            ${record.status.charAt(0).toUpperCase() + record.status.slice(1)}
                        </span>
                    </td>
                    <td>${record.formatted_hours}</td>
                `;
                tableBody.appendChild(row);

                // Add to total hours if present and has hours
                if (record.status === 'present' && record.total_hours) {
                    totalHours += parseFloat(record.total_hours);
                    presentCount++;
                }
            });

            // Update average hours in the summary card
            const averageHours = presentCount > 0 ? (totalHours / presentCount).toFixed(2) : '0.00';
            document.querySelector('.summary-card:nth-child(4) h3').textContent = averageHours;

            // Check business hours for each record
            const rows = document.querySelectorAll('.attendance-table tbody tr');
            rows.forEach(row => {
                const timeInCell = row.cells[2];
                const timeOutCell = row.cells[3];
                
                const timeIn = timeInCell.textContent;
                const timeOut = timeOutCell.textContent;
                
                if (timeIn !== '-' && !isWithinBusinessHours(timeIn)) {
                    timeInCell.classList.add('outside-hours');
                }
                if (timeOut !== '-' && !isWithinBusinessHours(timeOut)) {
                    timeOutCell.classList.add('outside-hours');
                }
            });
        })
        .catch(error => {
            console.error('Error:', error);
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center">Error loading data</td></tr>';
        });
    }

    function exportAttendance() {
        const staffId = document.getElementById('staff-filter').value;
        const startDate = document.getElementById('start-date').value;
        const endDate = document.getElementById('end-date').value;
        const status = document.getElementById('status-filter').value;

        // Create the export URL with the current filters
        const exportUrl = `/manager/attendance/export?staff_id=${staffId}&start_date=${startDate}&end_date=${endDate}&status=${status}`;
        
        // Create a temporary link and trigger the download
        const link = document.createElement('a');
        link.href = exportUrl;
        link.setAttribute('download', 'attendance_report.csv');
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    function exportToPDF() {
        try {
            // Create PDF in landscape for better table fit
            const doc = new jsPDF('l', 'mm', 'a4');
            const pageWidth = doc.internal.pageSize.getWidth();
            const margin = 10;
            let y = 15;

            // Set default font
            doc.setFont('helvetica');

            // Header
            doc.setFontSize(20);
            doc.setFont(undefined, 'bold');
            doc.text('KING SALON ATTENDANCE REPORT', pageWidth / 2, y, { align: 'center' });
            y += 10;
            
            doc.setFontSize(10);
            doc.setFont(undefined, 'normal');
            doc.text('Antagan Tabuc-Tubig Dumaguete City', pageWidth / 2, y, { align: 'center' });
            y += 15;

            // Date range info
            const startDate = document.getElementById('start-date').value || 'All Dates';
            const endDate = document.getElementById('end-date').value || 'All Dates';
            doc.text(`Start Date: ${startDate}`, margin, y);
            doc.text(`End Date: ${endDate}`, pageWidth - margin, y, { align: 'right' });
            y += 10;

            // Table setup
            const headers = ['#', 'Staff Name', 'Date', 'Time In', 'Time Out', 'Status', 'Total Hours'];
            const columnWidths = [10, 40, 30, 30, 30, 30, 30];
            const columnPositions = [margin];

            // Calculate column positions
            let currentPos = margin;
            columnWidths.forEach(width => {
                currentPos += width;
                columnPositions.push(currentPos);
            });
            const tableWidth = currentPos - margin;

            // Table header with box styling
            doc.setFillColor(220, 53, 69); // Red color
            doc.rect(margin, y, tableWidth, 8, 'F');
            doc.setTextColor(255, 255, 255);

            headers.forEach((header, i) => {
                doc.text(header, columnPositions[i] + 2, y + 6);
            });
            y += 10;

            // Table data
            doc.setTextColor(0, 0, 0);
            doc.setFontSize(9);

            const table = document.querySelector('.attendance-table');
            const rows = table.querySelectorAll('tbody tr');
            const rowHeight = 10;
            const textPadding = 2;
            let currentY = y;
            let rowNumber = 1;

            // Draw initial horizontal line above data
            doc.setDrawColor(200, 200, 200);
            doc.line(margin, currentY, margin + tableWidth, currentY);
            doc.setDrawColor(0, 0, 0);

            rows.forEach(row => {
                if (!row.classList.contains('text-center')) {
                    // Check for page break
                    if (currentY + rowHeight > 190) {
                        doc.addPage('l');
                        currentY = 15;

                        // Redraw header on new page
                        doc.setFillColor(220, 53, 69);
                        doc.rect(margin, currentY, tableWidth, 8, 'F');
                        doc.setTextColor(255, 255, 255);
                        headers.forEach((header, i) => {
                            doc.text(header, columnPositions[i] + 2, currentY + 6);
                        });
                        doc.setTextColor(0, 0, 0);
                        currentY += 10;
                        doc.setDrawColor(200, 200, 200);
                        doc.line(margin, currentY, margin + tableWidth, currentY);
                        doc.setDrawColor(0, 0, 0);
                    }

                    const cells = row.cells;
                    const startRowY = currentY;

                    // Draw vertical lines
                    doc.setDrawColor(200, 200, 200);
                    columnPositions.forEach(x => {
                        doc.line(x, startRowY, x, startRowY + rowHeight);
                    });
                    doc.setDrawColor(0, 0, 0);

                    // Row number
                    doc.text(rowNumber.toString(), columnPositions[0] + textPadding, startRowY + rowHeight / 2 + textPadding / 2);

                    // Staff Name
                    doc.text(cells[0].innerText.trim(), columnPositions[1] + textPadding, startRowY + rowHeight / 2 + textPadding / 2);

                    // Date
                    doc.text(cells[1].innerText.trim(), columnPositions[2] + textPadding, startRowY + rowHeight / 2 + textPadding / 2);

                    // Time In
                    doc.text(cells[2].innerText.trim(), columnPositions[3] + textPadding, startRowY + rowHeight / 2 + textPadding / 2);

                    // Time Out
                    doc.text(cells[3].innerText.trim(), columnPositions[4] + textPadding, startRowY + rowHeight / 2 + textPadding / 2);

                    // Status
                    doc.text(cells[4].innerText.trim(), columnPositions[5] + textPadding, startRowY + rowHeight / 2 + textPadding / 2);

                    // Total Hours
                    doc.text(cells[5].innerText.trim(), columnPositions[6] + textPadding, startRowY + rowHeight / 2 + textPadding / 2);

                    currentY += rowHeight;
                    rowNumber++;

                    // Draw horizontal line below row
                    doc.setDrawColor(200, 200, 200);
                    doc.line(margin, currentY, margin + tableWidth, currentY);
                    doc.setDrawColor(0, 0, 0);
                }
            });

            // Draw final vertical line
            doc.setDrawColor(200, 200, 200);
            doc.line(margin + tableWidth, y, margin + tableWidth, currentY);
            doc.setDrawColor(0, 0, 0);

            // Save PDF
            const filename = `Attendance_${startDate}_to_${endDate}.pdf`;
            doc.save(filename);

        } catch (error) {
            console.error('Error generating PDF:', error);
            alert('Error generating PDF. Please try again.');
        }
    }

    document.querySelectorAll('.submenu-toggle').forEach(toggle => {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                const submenu = this.nextElementSibling;
                submenu.style.display = submenu.style.display === 'none' ? 'block' : 'none';
            });
        });
    </script>
</body>
</html> 