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
                   <li><a href="{{ route('cashier.dashboard') }}" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                   <li><a href="{{ route('cashier.appointments') }}"><i class="fas fa-calendar-alt"></i> Appointments</a></li>
                <li><a href="{{ route('cashier.products') }}"><i class="fas fa-box"></i> After Care Products</a></li>
                <li><a href="{{ route('cashier.transaction') }}"><i class="fas fa-cash-register"></i> POS</a></li>
                <li><a href="{{ route('cashier.history') }}"><i class="fas fa-history"></i>Recent Walk-In Sales</a></li>
                <!-- <li><a href="{{ route('cashier.stocks') }}"><i class="fas fa-boxes"></i> Stocks View</a></li> -->
             
            </ul>
        </div>

        <div class="main-content">
            <header>
                <div class="header-content">
                    <h1>Products</h1>
                </div>
                <!-- <button onclick="openAddStockModal()" class="btn-add-stock"><i class="fas fa-boxes"></i> Add
                    Stocks</button> -->
            </header>

            <table class="table">
                <thead>
                    <tr>
                        <th>NO.</th>
                        <th>Image</th>
                        <th>Product Name</th>
                        <!-- <th>Category</th> -->
                        <!-- <th>Brand</th> -->
                        <th>Price</th>
                        <th>Stock</th>
                        <!-- <th>Actions</th> -->
                    </tr>
                </thead>
                <tbody>
                    @foreach ($products as $index => $product)
                        @if($product->category !== 'Consumable')
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                @if($product->image)
                                <img src="{{ asset('storage/' . $product->image) }}" alt="Product Image" width="50">
                                @else
                                <img src="https://via.placeholder.com/50" alt="No Image" width="50">
                                @endif
                            </td>
                            <td>{{ $product->product_name }}</td>
                            <!-- <td>{{ $product->category }}</td> -->
                            <!-- <td>{{ $product->brand }}</td> -->
                            <td>₱{{ number_format($product->price, 2) }}</td>
                            <td>{{ $product->stocks }}</td>
                            <td>
                                {{-- Edit and Delete buttons are temporarily hidden
                                <button onclick="openEditModal({{ $product->id }}, '{{ $product->product_name }}', '{{ $product->category }}', '{{ $product->brand }}', {{ $product->price }}, {{ $product->stocks }}, '{{ $product->image }}')" class="btn-edit">Edit</button>
                                <form action="{{ route('cashier.products.destroy', $product->id) }}" method="POST"
                                    style="display:inline-block;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-delete"
                                        onclick="return confirm('Are you sure?')">Delete</button>
                                </form>
                                --}}
                            </td>
                        </tr>
                        @endif
                    @endforeach
                    @if($products->where('category', '!=', 'Consumable')->isEmpty())
                    <tr>
                        <td colspan="8" class="text-center">No retail products found.</td>
                    </tr>
                    @endif
                </tbody>
            </table>

            <!-- Add Stock Modal -->
            <div id="addStockModal" class="modal" style="display:none;">
                <div class="modal-content">
                    <span class="close" onclick="closeAddStockModal()">&times;</span>
                    <h2>Add Stock to Product</h2>
                    <form id="addStockForm" method="POST" action="{{ route('cashier.products.addStock') }}">
                        @csrf
                        <div class="form-group">
                            <label for="product_id">Product:</label>
                            <select id="product_id" name="product_id" required>
                                @foreach ($products as $product)
                                <option value="{{ $product->id }}">{{ $product->product_name }} (Current:
                                    {{ $product->stocks }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="quantity">Quantity to Add:</label>
                            <input type="number" id="quantity" name="quantity" min="1" required>
                        </div>
                        <button type="submit" class="btn-submit">Add Stock</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            width: 90%;
            max-width: 500px;
            position: relative;
            margin: 20px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            position: absolute;
            right: 20px;
            top: 10px;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .btn-submit {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-submit:hover {
            background-color: #45a049;
        }
    </style>

    <script>
    function openAddStockModal() {
        document.getElementById('addStockModal').style.display = 'block';
    }

    function closeAddStockModal() {
        document.getElementById('addStockModal').style.display = 'none';
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target == document.getElementById('addStockModal')) {
            closeAddStockModal();
        }
    }
    </script>
</body>

</html>