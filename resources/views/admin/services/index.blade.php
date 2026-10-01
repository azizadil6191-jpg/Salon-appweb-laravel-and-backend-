<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services Dashboard</title>
    <link rel="stylesheet" href="{{ asset('css/services.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
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

        <div class="main-content">
            <header>

                <div class="header-title">
                    <h1>Services</h1>
                </div>

                <div class="header-search">
                    <form method="GET" action="{{ route('services.index') }}" class="search-form">
                        <div class="search-input-container">
                            <input type="text" name="search" placeholder="Search services..." value="{{ request('search') }}">
                            <button type="submit" class="search-btn"><i class="fas fa-search"></i></button>
                        </div>
                    </form>
                </div>

                <div class="header-actions">
                    <a href="javascript:void(0)" onclick="openAddServiceModal()" class="btn-add">
                        <i class="fas fa-plus"></i> Add Service
                    </a>
                </div>
                
            </header>

            <!-- Services Table Section -->
            <div class="table-section">
                <div class="table-controls">
                    <div class="filter-controls">
                        <label for="category_id">Filter by Category:</label>
                        <select name="category_id" id="category_id" class="form-control" onchange="filterServices()">
                            <option value="">All Categories</option>
                            @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="services-table">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Category</th>
                                <th>Service Name</th>
                                <th>Image</th>
                                <th class="optional-column">Hair Length</th>
                                <th class="optional-column">Duration</th>
                                <th class="optional-column">Price</th>
                                <th class="optional-column">Price (Men)</th>
                                <th class="optional-column">Price (Women)</th>
                                
                                <th>Actions</th>
                               
                            </tr>
                        </thead>
                        <tbody>
                                @php
        $currentPage = request()->input('page', 1); // Get current page or default to 1
        $itemsPerPage = $services->perPage();
        $startNumber = ($currentPage - 1) * $itemsPerPage;
    @endphp
                            @foreach($services as $service)
                            <tr data-category="{{ $service->category_id }}">
                                 <td>{{ $startNumber + $loop->iteration }}</td> 
                                <td>{{ $service->category ? $service->category->name : '-' }}</td>
                                <td>{{ $service->service_name ?? '-' }}</td>
                                <td>
                                    @if($service->profile_image)
                                    <img src="{{ asset('storage/' . $service->profile_image) }}" 
                                         alt="{{ $service->service_name }}" class="service-image">
                                    @else
                                    <div class="no-image">No Image</div>
                                    @endif
                                </td>
                                <td class="optional-column">{{ $service->hair_length ?? '-' }}</td>
                                <td class="optional-column">{{ $service->duration ? $service->duration . ' mins' : '-' }}</td>
                                <td class="optional-column">{{ isset($service->price) ? '₱' . number_format($service->price, 2) : '-' }}</td>
                                <td class="optional-column">{{ isset($service->price_men) ? '₱' . number_format($service->price_men, 2) : '-' }}</td>
                                <td class="optional-column">{{ isset($service->price_women) ? '₱' . number_format($service->price_women, 2) : '-' }}</td>
                                
                                <td class="actions-cell">
                                    <div class="action-buttons">
                                        <button class="btn-edit" onclick='openEditServiceModal({!! json_encode($service) !!})'>
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form action="{{ route('services.destroy', $service->id) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <!-- <button type="submit" class="btn-delete">
                                                <i class="fas fa-trash"></i>
                                            </button> -->
                                        </form>
                                    </div>
                                </td>
                               
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="pagination-container">
                    {{ $services->links('pagination::bootstrap-4') }}
                </div>
            </div>

            <!-- Add Service Modal -->
            <div id="addServiceModal" class="modal" style="display:none;">
                <div class="modal-content">
                    <span class="close" onclick="closeAddServiceModal()">&times;</span>
                    <h2>Add New Service</h2>
                    <form id="addServiceForm" method="POST" action="{{ route('services.store') }}"
                        enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <label for="category_id">Category:</label>
                            <select id="category_id" name="category_id" required>
                                @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="service_name">Service Name:</label>
                            <input type="text" id="service_name" name="service_name" required>
                        </div>
                        <div class="form-group" id="hairLengthField">
                            <label for="hair_length">Hair Length:</label>
                            <input type="text" id="hair_length" name="hair_length">
                        </div>
                        <div class="form-group">
                            <label for="duration">Duration (minutes):</label>
                            <input type="number" id="duration" name="duration" min="1" required>
                        </div>
                        <div class="form-group">
                            <label for="price">Price:</label>
                            <input type="number" id="price" name="price">
                        </div>
                        <div class="form-group">
                            <label for="price_men">Price (Men):</label>
                            <input type="number" id="price_men" name="price_men">
                        </div>
                        <div class="form-group">
                            <label for="price_women">Price (Women):</label>
                            <input type="number" id="price_women" name="price_women">
                        </div>
                        <div class="form-group">
                            <label for="profile_image">Profile Image:</label>
                            <input type="file" id="profile_image" name="profile_image" accept="image/*">
                        </div>
                        <button type="submit" class="btn-submit">Add Service</button>
                    </form>
                </div>
            </div>

            <!-- Edit Service Modal -->
            <div id="editServiceModal" class="modal" style="display:none;">
                <div class="modal-content">
                    <span class="close" onclick="closeEditServiceModal()">&times;</span>
                    <h2>Edit Service</h2>
                    <form id="editServiceForm" method="POST" action="{{ route('services.update', ['id' => ':id']) }}"
                        enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <div class="form-group">
                            <label for="edit_category_id">Category:</label>
                            <select id="edit_category_id" name="category_id" required>
                                @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit_service_name">Service Name:</label>
                            <input type="text" id="edit_service_name" name="service_name" required>
                        </div>
                        <div class="form-group" id="edit_hairLengthField">
                            <label for="edit_hair_length">Hair Length:</label>
                            <input type="text" id="edit_hair_length" name="hair_length">
                        </div>
                        <div class="form-group">
                            <label for="edit_duration">Duration (minutes):</label>
                            <input type="number" id="edit_duration" name="duration" min="1" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_price">Price:</label>
                            <input type="number" id="edit_price" name="price">
                        </div>
                        <div class="form-group">
                            <label for="edit_price_men">Price (Men):</label>
                            <input type="number" id="edit_price_men" name="price_men">
                        </div>
                        <div class="form-group">
                            <label for="edit_price_women">Price (Women):</label>
                            <input type="number" id="edit_price_women" name="price_women">
                        </div>
                        <div class="form-group">
                            <label for="edit_profile_image">Profile Image:</label>
                            <input type="file" id="edit_profile_image" name="profile_image" accept="image/*">
                        </div>
                        <button type="submit" class="btn-submit">Update Service</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="{{ asset('js/admin/services.js') }}"></script>
    <script>
        function openEditServiceModal(service) {
            const modal = document.getElementById('editServiceModal');
            const form = document.getElementById('editServiceForm');
            
            // Set the form action URL
            form.action = `/admin/services/${service.id}`;
            
            // Populate form fields
            document.getElementById('edit_category_id').value = service.category_id;
            document.getElementById('edit_service_name').value = service.service_name;
            document.getElementById('edit_hair_length').value = service.hair_length || '';
            document.getElementById('edit_duration').value = service.duration || '';
            document.getElementById('edit_price').value = service.price || '';
            document.getElementById('edit_price_women').value = service.price_women || '';
            document.getElementById('edit_price_men').value = service.price_men || '';
            
            // Show the modal
            modal.style.display = 'block';
        }

        function closeEditServiceModal() {
            const modal = document.getElementById('editServiceModal');
            modal.style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('editServiceModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }

        function openAddServiceModal() {
            const modal = document.getElementById('addServiceModal');
            modal.style.display = 'block';
        }

        function closeAddServiceModal() {
            const modal = document.getElementById('addServiceModal');
            modal.style.display = 'none';
        }
    </script>
</body>
</html>