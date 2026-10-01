<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products Dashboard</title>
    <link rel="stylesheet" href="{{ asset('css/products.css') }}">
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
                    <h1>Inventory</h1>
                </div>
                <div class="header-actions">
                    <div class="filter-section">
                        <select id="categoryFilter" onchange="filterProducts()">
                            <option value="">All Categories</option>
                            <option value="Retail">Retail</option>
                            <option value="Consumable">Consumable</option>
                        </select>
                        <select id="stockFilter" onchange="filterProducts()">
                            <option value="">All Stock Status</option>
                            <option value="low">Low Stock (≤ 10)</option>
                            <option value="out">Out of Stock</option>
                            <option value="available">In Stock</option>
                        </select>
                    </div>
                    <button onclick="openAddProductModal()" class="btn-add"><i class="fas fa-plus"></i> Add Product</button>
                </div>
            </header>

            <table class="table">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Image</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Brand</th>
                        <th>Price</th>
                        <th>Stocks</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($products as $index => $product)
                    <tr class="product-row" 
                        data-category="{{ $product->category }}"
                        data-stocks="{{ $product->stocks }}">
                        <td>{{ $index + 1 }}</td>
                        <td>
                            @if($product->image)
                                <img src="{{ asset('storage/' . $product->image) }}" alt="Product Image" width="50">
                            @else
                                <img src="https://via.placeholder.com/50" alt="No Image" width="50">
                            @endif
                        </td>
                        <td>{{ $product->product_name }}</td>
                        <td>{{ $product->category }}</td>
                        <td>{{ $product->brand }}</td>
                        <td>₱{{ number_format($product->price, 2) }}</td>
                        <td>{{ $product->stocks }}</td>
                        <td>
                            @if($product->stocks <= 0)
                                <span class="status out-of-stock">Out of Stock</span>
                            @elseif($product->stocks <= 10)
                                <span class="status low-stock">Low Stock</span>
                            @else
                                <span class="status in-stock">In Stock</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.products.edit', $product->id) }}" class="btn-edit"><i class="fas fa-edit"></i></a>
                            <button onclick="openAddStocksModal({{ $product->id }}, '{{ $product->product_name }}', {{ $product->stocks }})" class="btn-stocks"><i class="fas fa-boxes"></i></button>
                            <form action="{{ route('admin.products.destroy', $product->id) }}" method="POST" style="display:inline-block;">
                                @csrf
                                @method('DELETE')
                                <!-- <button type="submit" class="btn-delete" onclick="return confirm('Are you sure?')">Delete</button> -->
                            </form>
                        </td>
                    </tr>
                    @endforeach
                    @if($products->isEmpty())
                    <tr>
                        <td colspan="9" class="text-center">No products found.</td>
                    </tr>
                    @endif
                </tbody>
            </table>

            <style>
                .header-actions {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 20px;
                }
                .filter-section {
                    display: flex;
                    gap: 10px;
                }
                .filter-section select {
                    padding: 8px;
                    border-radius: 4px;
                    border: 1px solid #ddd;
                }
                .status {
                    padding: 4px 8px;
                    border-radius: 4px;
                    font-size: 0.9em;
                }
                .out-of-stock {
                    background-color: #ffebee;
                    color: #c62828;
                }
                .low-stock {
                    background-color: #fff3e0;
                    color: #ef6c00;
                }
                .in-stock {
                    background-color: #e8f5e9;
                    color: #2e7d32;
                }
                .btn-stocks {
                    background: #4CAF50;
                    color: white;
                    border: none;
                    padding: 5px 10px;
                    border-radius: 4px;
                    cursor: pointer;
                    margin: 0 5px;
                }
                .btn-stocks:hover {
                    background: #45a049;
                }
                .stock-input-group {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }
                .stock-btn {
                    background: #f8f9fa;
                    border: 1px solid #ddd;
                    padding: 5px 15px;
                    border-radius: 4px;
                    cursor: pointer;
                    font-size: 1.2em;
                }
                .stock-btn:hover {
                    background: #e9ecef;
                }
                #stock_change {
                    width: 100px;
                    text-align: center;
                    padding: 5px;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                }
                .help-text {
                    color: #666;
                    font-size: 0.9em;
                    margin-top: 5px;
                }
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

                .modal.show {
                    display: flex !important;
                    align-items: center;
                    justify-content: center;
                }

                .modal-content {
                    background-color: #fefefe;
                    padding: 30px;
                    border-radius: 12px;
                    width: 90%;
                    max-width: 500px;
                    position: relative;
                    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
                    animation: modalFadeIn 0.3s ease-out;
                    margin: 0 auto;
                }

                @keyframes modalFadeIn {
                    from {
                        opacity: 0;
                        transform: translateY(-20px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }
                .modal h2 {
                    color: #333;
                    margin-bottom: 20px;
                    font-size: 1.5em;
                    border-bottom: 2px solid #4CAF50;
                    padding-bottom: 10px;
                }
                .close {
                    position: absolute;
                    right: 20px;
                    top: 15px;
                    color: #666;
                    font-size: 24px;
                    font-weight: bold;
                    cursor: pointer;
                    transition: color 0.2s;
                }
                .close:hover {
                    color: #333;
                }
                .form-group {
                    margin-bottom: 20px;
                }
                .form-group label {
                    display: block;
                    margin-bottom: 8px;
                    font-weight: 500;
                    color: #444;
                }
                .form-group p {
                    background: #f8f9fa;
                    padding: 10px;
                    border-radius: 6px;
                    margin: 0;
                }
                .stock-input-group {
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    margin-bottom: 8px;
                }
                .stock-btn {
                    background: #f8f9fa;
                    border: 1px solid #ddd;
                    padding: 8px 16px;
                    border-radius: 6px;
                    cursor: pointer;
                    font-size: 1.2em;
                    transition: all 0.2s;
                }
                .stock-btn:hover {
                    background: #e9ecef;
                    border-color: #4CAF50;
                    color: #4CAF50;
                }
                #stock_change {
                    width: 120px;
                    text-align: center;
                    padding: 8px;
                    border: 1px solid #ddd;
                    border-radius: 6px;
                    font-size: 1.1em;
                }
                .help-text {
                    color: #666;
                    font-size: 0.9em;
                    margin-top: 8px;
                    display: block;
                }
                textarea {
                    width: 100%;
                    padding: 10px;
                    border: 1px solid #ddd;
                    border-radius: 6px;
                    resize: vertical;
                    min-height: 80px;
                    font-family: inherit;
                }
                .btn-submit {
                    background: #4CAF50;
                    color: white;
                    border: none;
                    padding: 12px 24px;
                    border-radius: 6px;
                    cursor: pointer;
                    font-size: 1em;
                    width: 100%;
                    transition: background-color 0.2s;
                    margin-top: 10px;
                }
                .btn-submit:hover {
                    background: #45a049;
                }
                .alert {
                    padding: 12px 20px;
                    border-radius: 6px;
                    margin-bottom: 20px;
                    font-weight: 500;
                }
                .alert-success {
                    background-color: #d4edda;
                    color: #155724;
                    border: 1px solid #c3e6cb;
                }
                .alert-error {
                    background-color: #f8d7da;
                    color: #721c24;
                    border: 1px solid #f5c6cb;
                }
            </style>

            <script>
                function filterProducts() {
                    const categoryFilter = document.getElementById('categoryFilter').value;
                    const stockFilter = document.getElementById('stockFilter').value;
                    const rows = document.querySelectorAll('.product-row');

                    rows.forEach(row => {
                        const category = row.dataset.category;
                        const stocks = parseInt(row.dataset.stocks);
                        
                        let categoryMatch = !categoryFilter || category === categoryFilter;
                        let stockMatch = !stockFilter || 
                            (stockFilter === 'low' && stocks <= 10 && stocks > 0) ||
                            (stockFilter === 'out' && stocks <= 0) ||
                            (stockFilter === 'available' && stocks > 10);

                        row.style.display = categoryMatch && stockMatch ? '' : 'none';
                    });
                }

                function openAddProductModal() {
                    document.getElementById('addProductModal').style.display = 'block';
                }
                
                function closeAddProductModal() {
                    document.getElementById('addProductModal').style.display = 'none';
                }

                function openAddStocksModal(productId, productName, currentStock) {
                    const modal = document.getElementById('addStocksModal');
                    modal.classList.add('show');
                    document.getElementById('product_id').value = productId;
                    document.getElementById('product_name_display').textContent = productName;
                    document.getElementById('current_stock').textContent = currentStock;
                    document.getElementById('stock_change').value = 0;
                    document.getElementById('notes').value = '';
                }

                function closeAddStocksModal() {
                    const modal = document.getElementById('addStocksModal');
                    modal.classList.remove('show');
                }

                function incrementStock() {
                    const input = document.getElementById('stock_change');
                    input.value = parseInt(input.value) + 1;
                }

                function decrementStock() {
                    const input = document.getElementById('stock_change');
                    input.value = parseInt(input.value) - 1;
                }

                // Close modal when clicking outside
                window.onclick = function(event) {
                    const modal = document.getElementById('addStocksModal');
                    if (event.target == modal) {
                        closeAddStocksModal();
                    }
                }

                // Add keyboard support for closing modal
                document.addEventListener('keydown', function(event) {
                    if (event.key === 'Escape') {
                        closeAddStocksModal();
                    }
                });
            </script>

            <!-- Add Product Modal -->
            <div id="addProductModal" class="modal" style="display:none;">
                <div class="modal-content">
                    <span class="close" onclick="closeAddProductModal()">&times;</span>
                    <h2>Add New Product</h2>
                    <form id="addProductForm" method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <label for="product_name">Product Name:</label>
                            <input type="text" id="product_name" name="product_name" required>
                        </div>
                        <div class="form-group">
                            <label for="category">Category:</label>
                            <select id="category" name="category" required>
                                <option value="Retail">Retail</option>
                                <option value="Consumable">Consumable</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="brand">Brand:</label>
                            <input type="text" id="brand" name="brand" required>
                        </div>
                        <div class="form-group">
                            <label for="price">Price:</label>
                            <input type="number" id="price" name="price" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label for="image">Image:</label>
                            <input type="file" id="image" name="image">
                        </div>
                        <button type="submit" class="btn-submit">Add Product</button>
                    </form>
                </div>
            </div>

            <!-- Add Stocks Modal -->
            <div id="addStocksModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeAddStocksModal()">&times;</span>
                    <h2>Manage Stocks</h2>
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-error">
                            {{ session('error') }}
                        </div>
                    @endif
                    <form id="addStocksForm" method="POST" action="{{ route('admin.products.update-stocks') }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" id="product_id" name="product_id">
                        <div class="form-group">
                            <label>Product Name:</label>
                            <p id="product_name_display"></p>
                        </div>
                        <div class="form-group">
                            <label>Current Stock:</label>
                            <p id="current_stock"></p>
                        </div>
                        <div class="form-group">
                            <label for="stock_change">Stock Change:</label>
                            <div class="stock-input-group">
                                <button type="button" onclick="decrementStock()" class="stock-btn">-</button>
                                <input type="number" id="stock_change" name="stock_change" value="0" min="-999" max="999" required>
                                <button type="button" onclick="incrementStock()" class="stock-btn">+</button>
                            </div>
                            <small class="help-text">Enter positive number to add stocks, negative to remove</small>
                        </div>
                        <div class="form-group">
                            <label for="notes">Notes (optional):</label>
                            <textarea id="notes" name="notes" rows="3" placeholder="Add any notes about this stock change"></textarea>
                        </div>
                        <button type="submit" class="btn-submit">Update Stocks</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
