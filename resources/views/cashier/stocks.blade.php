<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Management</title>
    <link rel="stylesheet" href="{{ asset('css/stocks.css') }}">
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
            <li><a href="{{ route('cashier.dashboard') }}" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                   <li><a href="{{ route('cashier.appointments') }}"><i class="fas fa-calendar-alt"></i> Appointments</a></li>
                <li><a href="{{ route('cashier.products') }}"><i class="fas fa-box"></i> After Care Products</a></li>
                <li><a href="{{ route('cashier.transaction') }}"><i class="fas fa-cash-register"></i> POS</a></li>
                <li><a href="{{ route('cashier.history') }}"><i class="fas fa-history"></i>Recent Walk-In Sales</a></li>
                <!-- <li><a href="{{ route('cashier.stocks') }}"><i class="fas fa-boxes"></i> Stocks View</a></li> -->
             
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
    <h2>Stock Overview</h2>
    <table>
        <thead>
            <tr>
                <th>Product Name</th>
                <th>Current Stock</th>
                <th>Added Stock</th>
                <th>Sold Quantity</th>
                <th>Total Added</th>
            </tr>
        </thead>
        <tbody>
        @foreach ($products as $product)
            @if($product->category !== 'Consumable')
            <tr class="{{ $product->stocks < 10 ? 'low-stock' : '' }}">
                <td>{{ $product->product_name }}</td>
                <td>{{ $product->stocks }}</td>
                <td>{{ $product->added_stock ?? 0 }}</td>
                <td>{{ $product->orderItems->sum('quantity') ?? 0 }}</td>
                <td>{{ ($product->stocks + $product->orderItems->sum('quantity')) ?? 0 }}</td>
            </tr>
            @endif
        @endforeach
        </tbody>
    </table>
</div>
    </div>
</body>

</html>