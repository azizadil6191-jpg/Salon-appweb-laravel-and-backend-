<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="{{ asset('css/admindashboard.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        <!-- Main Content Area -->
        <div class="main-content">
            <header>
                <div class="header-content">
                    <!-- Title -->
                    <h1>Admin Dashboard</h1>

                    <!-- Right Side (Icons or User Info) -->
                    <div class="header-right">
                        <!-- PDF Export Button -->
                        <div class="export-container">
                            <button class="export-btn" onclick="exportToPDF()">
                                <i class="fas fa-file-pdf"></i>
                                <span>Export Dashboard</span>
                            </button>
                        </div>

                        <!-- Chat Notification -->
                        <div class="chat-admin-button">
                            <button class="chat-toggle-btn" onclick="toggleChat()">
                                <i class="fas fa-comments"></i>
                                <span>Manager Messages</span>
                                @if($unreadCount > 0)
                                    <span class="unread-counter">{{ $unreadCount }}</span>
                                @endif
                            </button>
                        </div>

                        <!-- Notification Icon with Badge -->
                        <div class="notification-container">
                            <!-- <i class="fas fa-bell"></i>
                            @if ($upcomingAppointmentsCount > 0)
                            <span class="notification-badge">
                                {{ $upcomingAppointmentsCount > 9 ? '9+' : $upcomingAppointmentsCount }}
                            </span>
                            @endif -->
                        </div>

                        <!-- Settings Icon -->
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

                        <!-- User Profile -->
                        <div class="user-profile">
                            <img src="{{ asset('images/admin.png') }}" alt="Admin Avatar">
                            <span>Admin</span>
                        </div>
                    </div>
                </div>
            </header>


            <!-- Dashboard Widgets -->
            <section class="widget-container">
                <!-- Total Clients -->
                <div class="widget">
                    <i class="fas fa-users"></i>
                    <h3>{{ $clientCount }}</h3>
                    <p>Total Clients</p>
                </div>

                <!-- Total Appointments -->
                <div class="widget">
                    <i class="fas fa-calendar-check"></i>
                    <h3>{{ $appointmentCount }}</h3>
                    <p>Total Appointments</p>
                </div>

                <!-- Total Services -->
                <div class="widget">
                    <i class="fas fa-cut"></i>
                    <h3>{{ $serviceCount }}</h3>
                    <p>Total Services</p>
                </div>

                <!-- Sales -->
                <div class="widget">
                    <i class="fas fa-coins"></i>
                    <h3>₱{{ number_format($todaysSales, 2) }}</h3>
                    <p>Today's Appointment</p>
                </div>

                <div class="widget">
                    <i class="fas fa-calendar-week"></i>
                    <h3>₱{{ number_format($weeklySales, 2) }}</h3>
                    <p>Weekly Appointment Sales</p>
                </div>

                <div class="widget">
                    <i class="fas fa-calendar-alt"></i>
                    <h3>₱{{ number_format($monthlySales, 2) }}</h3>
                    <p>Monthly Appointment Sales</p>
                </div>

                <div class="widget">
                    <i class="fas fa-wallet"></i>
                    <h3>₱{{ number_format($totalSales, 2) }}</h3>
                    <p>Overall Appointment Sales</p>
                </div>

                <div class="widget">
                    <i class="fas fa-shopping-cart"></i>
                    <h3>₱{{ number_format($totalFrontDeskSales + $totalAppSales, 2) }}</h3>
                    <p>Total Product Sales</p>
                </div>
            </section>

            <!-- Product Sales Overview -->
            <section class="sales-overview">
                <div class="section-header">
                    <h2><i class="fas fa-chart-line"></i> Product Sales Overview</h2>
                </div>
                <div class="sales-grid">
                    <!-- Front Desk Sales -->
                    <div class="sales-card">
                        <div class="sales-header">
                            <h3>Front Desk Sales</h3>
                            <span class="total">₱{{ number_format($totalFrontDeskSales, 2) }}</span>
                        </div>
                        <div class="sales-content">
                            <h4>Top Selling Products</h4>
                            <div class="product-list">
                                @foreach($frontDeskSales as $sale)
                                <div class="product-item">
                                    <span class="product-name">{{ $sale->product_name }}</span>
                                    <span class="product-quantity">{{ $sale->total_quantity }} units</span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- App Sales -->
                    <div class="sales-card">
                        <div class="sales-header">
                            <h3>App Sales</h3>
                            <span class="total">₱{{ number_format($totalAppSales, 2) }}</span>
                        </div>
                        <div class="sales-content">
                            <h4>Top Selling Products</h4>
                            <div class="product-list">
                                @foreach($appSales as $sale)
                                <div class="product-item">
                                    <span class="product-name">{{ $sale->product_name }}</span>
                                    <span class="product-quantity">{{ $sale->total_quantity }} units</span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Staff Schedule -->
            <!-- <section class="staff-schedule">
                <div class="section-header">
                    <h2><i class="fas fa-user-clock"></i> Staff Schedule</h2>
                </div>
                <div class="staff-grid">
                    @foreach($groupedStaff as $category => $staffMembers)
                    <div class="staff-category">
                        <h3>{{ $category }}</h3>
                        <div class="staff-list">
                            @foreach($staffMembers as $staff)
                            <div class="staff-item">
                                <div class="staff-info">
                                    <div class="flex items-center space-x-2">
                                        <div class="w-2 h-2 rounded-full {{ $staff->is_available ? 'bg-green-500' : 'bg-red-500' }}"></div>
                                        <span class="text-sm text-gray-600">{{ $staff->name }}</span>
                                    </div>
                                </div>
                                <a href="{{ route('admin.staff.schedule', $staff->id) }}" 
                                   class="text-xs text-blue-600 hover:text-blue-800">
                                    View Schedule
                                </a>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
            </section> -->

            <!-- Sales Charts Section -->
            <section class="charts-grid">
                <div class="chart">
                    <canvas id="sales-comparison"></canvas>
                </div>
                <div class="chart">
                    <canvas id="category-sales"></canvas>
                </div>
                <div class="chart">
                    <canvas id="monthly-revenue"></canvas>
                </div>
                <div class="chart">
                    <canvas id="weekly-performance"></canvas>
                </div>
            </section>

            <!-- Chat Panel -->
            <div class="chat-panel" id="chatPanel">
                <div class="chat-header">
                    <h3><i class="fas fa-comments"></i> Manager Messages</h3>
                    <button class="close-chat" onclick="toggleChat()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="chat-content">
                    <div class="notes-list">
                        @forelse($notes as $note)
                            <div class="note-item {{ !$note->is_read ? 'unread' : 'read' }}" data-note-id="{{ $note->id }}">
                                <div class="note-sender">
                                    @if($note->manager->profile_picture)
                                        <img src="{{ asset('storage/' . $note->manager->profile_picture) }}" 
                                             alt="{{ $note->manager->name }}" 
                                             class="sender-avatar">
                                    @else
                                        <div class="sender-avatar" style="background: #eee; display: flex; align-items: center; justify-content: center;">
                                            <span style="color: #666; font-weight: bold;">
                                                {{ substr($note->manager->name, 0, 1) }}
                                            </span>
                                        </div>
                                    @endif
                                    <span class="sender-name">{{ $note->manager->name }}</span>
                                </div>
                                <p class="note-message">{{ $note->message }}</p>
                                <p class="note-date">{{ $note->created_at->format('M d, Y h:i A') }}</p>
                                <span class="note-status">{{ !$note->is_read ? 'New Message' : 'Read' }}</span>
                            </div>
                        @empty
                            <div class="text-center py-8">
                                <p class="text-gray-500">No messages from managers yet.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
        <script>
        // Sidebar submenu toggle
        document.querySelectorAll('.submenu-toggle').forEach(item => {
            item.addEventListener('click', event => {
                event.preventDefault();
                let submenu = item.nextElementSibling;
                submenu.classList.toggle('open');
            });
        });

        // Sales Comparison Chart
        fetch('/admin/sales-comparison')
            .then(response => response.json())
            .then(data => {
                const salesComparisonCtx = document.getElementById('sales-comparison').getContext('2d');
                new Chart(salesComparisonCtx, {
                    type: 'bar',
                    data: {
                        labels: data.labels,
                        datasets: [
                            {
                                label: 'App Add-Ons',
                                data: data.appointmentProducts,
                                backgroundColor: 'rgba(178, 34, 34, 0.7)',
                                borderColor: '#B22222',
                                borderWidth: 2,
                                borderRadius: 5,
                                barThickness: 30,
                                order: 2
                            },
                            {
                                label: 'Front Desk Sales',
                                data: data.orderItems,
                                type: 'line',
                                borderColor: '#FFD700',
                                backgroundColor: 'rgba(255, 215, 0, 0.2)',
                                borderWidth: 3,
                                pointBackgroundColor: '#FFD700',
                                pointBorderColor: '#fff',
                                pointRadius: 6,
                                pointHoverRadius: 8,
                                fill: true,
                                tension: 0.4,
                                order: 1
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Sales Comparison: App Add-Ons vs Front Desk Sales',
                                font: {
                                    size: 16,
                                    weight: 'bold'
                                },
                                padding: 20
                            },
                            legend: {
                                position: 'top',
                                labels: {
                                    usePointStyle: true,
                                    padding: 20,
                                    font: {
                                        size: 12,
                                        weight: 'bold'
                                    }
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                padding: 12,
                                titleFont: {
                                    size: 14,
                                    weight: 'bold'
                                },
                                bodyFont: {
                                    size: 13
                                },
                                callbacks: {
                                    label: function(context) {
                                        return `${context.dataset.label}: ₱${context.raw.toLocaleString()}`;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Monthly Sales (₱)',
                                    font: {
                                        weight: 'bold',
                                        size: 12
                                    }
                                },
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.1)'
                                },
                                ticks: {
                                    callback: function(value) {
                                        return '₱' + value.toLocaleString();
                                    }
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    font: {
                                        size: 11
                                    }
                                },
                                title: {
                                    display: true,
                                    text: 'Months',
                                    font: {
                                        weight: 'bold',
                                        size: 12
                                    }
                                }
                            }
                        },
                        interaction: {
                            mode: 'index',
                            intersect: false
                        }
                    }
                });
            })
            .catch(error => console.error('Error fetching sales comparison data:', error));

        // Category Sales Chart
        fetch('/admin/category-sales')
            .then(response => response.json())
            .then(data => {
                console.log('Category Sales Data:', data); // Debug log
                const categorySalesCtx = document.getElementById('category-sales').getContext('2d');
                new Chart(categorySalesCtx, {
                    type: 'bar',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: 'Category Sales (₱)',
                            data: data.sales,
                            backgroundColor: [
                                'rgba(255, 99, 132, 0.7)',
                                'rgba(54, 162, 235, 0.7)',
                                'rgba(255, 206, 86, 0.7)',
                                'rgba(75, 192, 192, 0.7)'
                            ],
                            borderColor: [
                                'rgb(255, 99, 132)',
                                'rgb(54, 162, 235)',
                                'rgb(255, 206, 86)',
                                'rgb(75, 192, 192)'
                            ],
                            borderWidth: 2,
                            borderRadius: 5
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Sales by Category',
                                font: {
                                    size: 16,
                                    weight: 'bold'
                                },
                                padding: 20
                            },
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return `₱${context.raw.toLocaleString()}`;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Sales (₱)',
                                    font: {
                                        weight: 'bold'
                                    }
                                },
                                ticks: {
                                    callback: function(value) {
                                        return '₱' + value.toLocaleString();
                                    }
                                }
                            }
                        }
                    }
                });
            })
            .catch(error => {
                console.error('Error fetching category sales data:', error);
                // Add error message to the chart container
                const chartContainer = document.getElementById('category-sales').parentElement;
                chartContainer.innerHTML = '<div style="text-align: center; padding: 20px; color: #666;">Failed to load category sales data. Please try again later.</div>';
            });

        // Monthly Revenue Chart
        fetch('/admin/monthly-revenue')
            .then(response => response.json())
            .then(data => {
                const monthlyRevenueCtx = document.getElementById('monthly-revenue').getContext('2d');
                new Chart(monthlyRevenueCtx, {
                    type: 'bar',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: 'Monthly Revenue',
                            data: data.revenue,
                            backgroundColor: 'rgba(255, 159, 64, 0.5)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true
                    }
                });
            })
            .catch(error => console.error('Error fetching monthly revenue data:', error));

        // Weekly Performance Chart
        fetch('/admin/weekly-performance')
            .then(response => response.json())
            .then(data => {
                const weeklyPerformanceCtx = document.getElementById('weekly-performance').getContext('2d');
                new Chart(weeklyPerformanceCtx, {
                    type: 'line',
                    data: {
                        labels: data.days,
                        datasets: [{
                            label: 'Weekly Performance',
                            data: data.performance,
                            borderColor: 'rgba(153, 102, 255, 1)',
                            backgroundColor: 'rgba(153, 102, 255, 0.2)',
                            borderWidth: 2,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {},
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Revenue'
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: 'Days'
                                }
                            }
                        }
                    }
                });
            })
            .catch(error => console.error('Error fetching weekly performance data:', error));

        document.addEventListener("DOMContentLoaded", function() {
            const settingsIcon = document.querySelector(".settings-icon");
            const settingsContainer = document.querySelector(".settings-container");

            settingsIcon.addEventListener("click", function() {
                settingsContainer.classList.toggle("active");
            });

            // Close dropdown when clicking outside
            document.addEventListener("click", function(event) {
                if (!settingsContainer.contains(event.target)) {
                    settingsContainer.classList.remove("active");
                }
            });
        });

        function toggleChat() {
            const chatPanel = document.getElementById('chatPanel');
            chatPanel.classList.toggle('active');
        }

        // Mark messages as read when viewed
        document.addEventListener('DOMContentLoaded', function() {
            const unreadNotes = document.querySelectorAll('.note-item.unread');
            unreadNotes.forEach(note => {
                const noteId = note.getAttribute('data-note-id');
                if (noteId) {
                    fetch(`/admin/notes/${noteId}/mark-as-read`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            note.classList.remove('unread');
                            note.classList.add('read');
                            const statusElement = note.querySelector('.note-status');
                            if (statusElement) {
                                statusElement.textContent = 'Read';
                                statusElement.style.background = '#eee';
                                statusElement.style.color = '#666';
                            }
                        }
                    })
                    .catch(error => console.error('Error marking message as read:', error));
                }
            });
        });

        function exportToPDF() {
            // Elements to exclude from PDF
            const excludeElements = [
                '.widget:nth-child(1)', // Total Clients
                '.widget:nth-child(2)', // Total Appointments
                '.widget:nth-child(3)', // Total Services
                '.widget:nth-child(4)', // Today's Appointment
                '.widget:nth-child(5)', // Weekly Appointment Sales
                '.header-content',
                '.sidebar',
                '.chat-panel',
                '.settings-container',
                '.user-profile',
                '.notification-container',
                '.staff-schedule', // Exclude staff schedule section
                '.sales-overview' // Exclude product sales overview section
            ];

            // Hide elements that should not be in PDF
            excludeElements.forEach(selector => {
                const elements = document.querySelectorAll(selector);
                elements.forEach(el => el.style.display = 'none');
            });

            // Get the main content area
            const element = document.querySelector('.main-content');
            
            // Configure PDF options
            const opt = {
                margin: 10,
                filename: 'dashboard-report.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { 
                    scale: 2,
                    useCORS: true,
                    logging: true
                },
                jsPDF: { 
                    unit: 'mm', 
                    format: 'a4', 
                    orientation: 'landscape'
                }
            };

            // Generate PDF
            html2pdf().set(opt).from(element).save().then(() => {
                // Show the hidden elements again
                excludeElements.forEach(selector => {
                    const elements = document.querySelectorAll(selector);
                    elements.forEach(el => el.style.display = '');
                });
            });
        }
        </script>

        <style>
            /* Chat Panel Styles */
            .chat-panel {
                position: fixed;
                right: -400px;
                top: 0;
                width: 400px;
                height: 100vh;
                background: white;
                box-shadow: -2px 0 10px rgba(0,0,0,0.1);
                transition: right 0.3s ease;
                z-index: 1000;
            }

            .chat-panel.active {
                right: 0;
            }

            .chat-header {
                background: linear-gradient(135deg, #B22222 0%, #8B0000 100%);
                color: white;
                padding: 15px 20px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            }

            .chat-header h3 {
                margin: 0;
                font-size: 18px;
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .chat-header h3 i {
                font-size: 22px;
                color: #FFD700;
                text-shadow: 0 0 5px rgba(255, 215, 0, 0.5);
            }

            .chat-content {
                height: calc(100vh - 60px);
                display: flex;
                flex-direction: column;
                background: #f8f9fa;
            }

            .notes-list {
                flex: 1;
                overflow-y: auto;
                padding: 20px;
            }

            .note-item {
                background: white;
                padding: 15px;
                border-radius: 8px;
                margin-bottom: 15px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.05);
                position: relative;
                border-left: 4px solid #ddd;
                transition: all 0.3s ease;
            }

            .note-item.unread {
                border-left-color: #B22222;
                background: rgba(178, 34, 34, 0.05);
            }

            .note-item.unread::before {
                content: '';
                position: absolute;
                top: 10px;
                right: 10px;
                width: 10px;
                height: 10px;
                background: #B22222;
                border-radius: 50%;
                animation: pulse 2s infinite;
            }

            @keyframes pulse {
                0% { transform: scale(1); opacity: 1; }
                50% { transform: scale(1.2); opacity: 0.7; }
                100% { transform: scale(1); opacity: 1; }
            }

            .note-sender {
                display: flex;
                align-items: center;
                margin-bottom: 10px;
            }

            .sender-avatar {
                width: 40px;
                height: 40px;
                border-radius: 50%;
                object-fit: cover;
                margin-right: 10px;
            }

            .sender-name {
                font-weight: 600;
                color: #333;
            }

            .note-message {
                margin: 10px 0;
                color: #333;
                font-size: 14px;
                line-height: 1.5;
            }

            .note-date {
                font-size: 12px;
                color: #666;
                margin: 5px 0;
            }

            .note-status {
                position: absolute;
                top: 10px;
                right: 30px;
                font-size: 12px;
                padding: 4px 8px;
                border-radius: 20px;
                background: #eee;
                color: #666;
            }

            .note-item.unread .note-status {
                background: #B22222;
                color: white;
            }

            .chat-toggle-btn {
                position: relative;
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 8px 16px;
                background: #B22222;
                color: white;
                border: none;
                border-radius: 8px;
                cursor: pointer;
                transition: all 0.3s ease;
            }

            .chat-toggle-btn i {
                font-size: 20px;
                color: #FFD700;
                text-shadow: 0 0 5px rgba(255, 215, 0, 0.5);
            }

            .unread-counter {
                position: absolute;
                top: -5px;
                right: -5px;
                background: #B22222;
                color: white;
                border-radius: 50%;
                width: 20px;
                height: 20px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 12px;
                font-weight: bold;
            }

            .close-chat {
                background: none;
                border: none;
                color: white;
                cursor: pointer;
                font-size: 20px;
                padding: 5px;
            }

            @media (max-width: 768px) {
                .chat-panel {
                    width: 100%;
                    right: -100%;
                }

                .chat-toggle-btn span {
                    display: none;
                }
            }

            .charts-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
                margin-bottom: 20px;
            }

            .chart {
                background: white;
                padding: 20px;
                border-radius: 10px;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            }

            @media (max-width: 1200px) {
                .charts-grid {
                    grid-template-columns: 1fr;
                }
            }

            .chart-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 15px;
            }

            .chart-header h3 {
                margin: 0;
                color: #333;
                font-size: 18px;
            }

            .export-container {
                margin-right: 15px;
            }

            .export-btn {
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 8px 16px;
                background: #B22222;
                color: white;
                border: none;
                border-radius: 8px;
                cursor: pointer;
                transition: all 0.3s ease;
            }

            .export-btn:hover {
                background: #8B0000;
            }

            .export-btn i {
                font-size: 18px;
            }

            @media print {
                .sidebar,
                .header-content,
                .widget-container,
                .chat-panel,
                .settings-container,
                .user-profile,
                .notification-container {
                    display: none !important;
                }

                .main-content {
                    margin: 0 !important;
                    padding: 0 !important;
                }

                .charts-grid {
                    page-break-inside: avoid;
                }
            }

            /* Product Sales Overview Styles */
            .sales-overview {
                background: white;
                border-radius: 10px;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                margin: 20px 0;
                padding: 20px;
            }

            .section-header {
                margin-bottom: 20px;
            }

            .section-header h2 {
                color: #333;
                font-size: 1.5rem;
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .section-header h2 i {
                color: #B22222;
            }

            .sales-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
            }

            .sales-card {
                background: #f8f9fa;
                border-radius: 8px;
                padding: 15px;
            }

            .sales-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 15px;
            }

            .sales-header h3 {
                color: #333;
                font-size: 1.2rem;
            }

            .total {
                font-size: 1.5rem;
                font-weight: bold;
                color: #B22222;
            }

            .product-list {
                display: flex;
                flex-direction: column;
                gap: 10px;
            }

            .product-item {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 8px;
                background: white;
                border-radius: 4px;
            }

            .product-name {
                color: #333;
            }

            .product-quantity {
                color: #666;
                font-weight: 500;
            }

            /* Staff Schedule Styles */
            .staff-schedule {
                background: white;
                border-radius: 10px;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                margin: 20px 0;
                padding: 20px;
            }

            .staff-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 20px;
            }

            .staff-category {
                background: #f8f9fa;
                border-radius: 8px;
                padding: 15px;
            }

            .staff-category h3 {
                color: #333;
                font-size: 1.2rem;
                margin-bottom: 15px;
            }

            .staff-list {
                display: flex;
                flex-direction: column;
                gap: 10px;
            }

            .staff-item {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 10px;
                background: white;
                border-radius: 4px;
            }

            .staff-info {
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .status-indicator {
                width: 8px;
                height: 8px;
                border-radius: 50%;
            }

            .status-indicator.available {
                background: #28a745;
            }

            .status-indicator.unavailable {
                background: #dc3545;
            }

            .staff-name {
                color: #333;
            }

            .view-schedule {
                color: #B22222;
                text-decoration: none;
                font-size: 0.9rem;
            }

            .view-schedule:hover {
                text-decoration: underline;
            }

            @media (max-width: 768px) {
                .sales-grid {
                    grid-template-columns: 1fr;
                }

                .staff-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>

</body>

</html>