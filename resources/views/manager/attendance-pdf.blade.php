<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Attendance Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            color: #333;
            margin: 0;
            padding: 0;
        }
        .summary {
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
        }
        .summary-item {
            text-align: center;
            padding: 10px;
            background: white;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .summary-item h3 {
            margin: 0;
            color: #333;
            font-size: 16px;
        }
        .summary-item p {
            margin: 5px 0 0;
            color: #666;
            font-size: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .status-badge {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: bold;
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
            color: black;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Attendance Report</h1>
        <p>Period: {{ $startDate ? date('M d, Y', strtotime($startDate)) : 'All Time' }} - {{ $endDate ? date('M d, Y', strtotime($endDate)) : 'Present' }}</p>
    </div>

    <div class="summary">
        <div class="summary-grid">
            <div class="summary-item">
                <h3>{{ $totalPresent }}</h3>
                <p>Present</p>
            </div>
            <div class="summary-item">
                <h3>{{ $totalAbsent }}</h3>
                <p>Absent</p>
            </div>
            <div class="summary-item">
                <h3>{{ $totalLate }}</h3>
                <p>Late</p>
            </div>
            <div class="summary-item">
                <h3>{{ $averageHours }}</h3>
                <p>Average Hours</p>
            </div>
        </div>
    </div>

    <table>
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
            @foreach($records as $record)
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
                <td>{{ $record->total_hours ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Generated on {{ date('M d, Y h:i A') }}</p>
    </div>
</body>
</html> 