<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product</title>
    <link rel="stylesheet" href="{{ asset('css/products.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <div class="sidebar-logo">
                <h2>Cashier</h2>
            </div>
            <ul>
                <li><a href="{{ route('cashier.dashboard') }}"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="{{ route('cashier.products') }}"><i class="fas fa-box"></i> Products</a></li>
                <li><a href="{{ route('cashier.transaction') }}"><i class="fas fa-cash-register"></i> POS</a></li>
                <li><a href="{{ route('cashier.history') }}"><i class="fas fa-history"></i>Walk-In Sales</a></li>
                <li><a href="{{ route('cashier.stocks') }}"> <i class="fas fa-boxes"></i> Stocks View</a></li>
            </ul>
        </div>

        <div class="main-content">
            <header>
                <div class="header-content">
                    <h1>Edit Product</h1>
                </div>
            </header>

            <div class="edit-form-container">
                <form action="{{ route('cashier.products.update', $product->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    
                    <div class="form-group">
                        <label for="product_name">Product Name:</label>
                        <input type="text" id="product_name" name="product_name" value="{{ $product->product_name }}" required>
                    </div>

                    <div class="form-group">
                        <label for="category">Category:</label>
                        <select id="category" name="category" required>
                            <option value="Shampoo" {{ $product->category == 'Shampoo' ? 'selected' : '' }}>Shampoo</option>
                            <option value="Conditioner" {{ $product->category == 'Conditioner' ? 'selected' : '' }}>Conditioner</option>
                            <option value="Hair Toners" {{ $product->category == 'Hair Toners' ? 'selected' : '' }}>Hair Toners</option>
                            <option value="Hair Electrical" {{ $product->category == 'Hair Electrical' ? 'selected' : '' }}>Hair Electrical</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="brand">Brand:</label>
                        <input type="text" id="brand" name="brand" value="{{ $product->brand }}" required>
                    </div>

                    <div class="form-group">
                        <label for="price">Price:</label>
                        <input type="number" id="price" name="price" step="0.01" value="{{ $product->price }}" required>
                    </div>

                    <div class="form-group">
                        <label for="stocks">Stock Quantity:</label>
                        <input type="number" id="stocks" name="stocks" min="0" value="{{ $product->stocks }}" required>
                    </div>

                    <div class="form-group">
                        <label for="image">Current Image:</label>
                        @if($product->image)
                            <img src="{{ asset('storage/' . $product->image) }}" alt="Current Product Image" style="max-width: 200px; margin: 10px 0;">
                        @else
                            <p>No image available</p>
                        @endif
                        <input type="file" id="image" name="image">
                        <small>Leave empty to keep current image</small>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-submit">Update Product</button>
                        <a href="{{ route('cashier.products') }}" class="btn-cancel">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html> 