<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cashier Dashboard</title>
    <link rel="stylesheet" href="{{ asset('css/cashierdashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/products-tab.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
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

        <div class="main-content">
            <header>
                <div class="header-content">
                    <h1>FrontDesk Dashboard</h1>
                    <div class="user-profile">
                        @auth
                            <img src="{{ auth()->user()->profile_image ? Storage::url(auth()->user()->profile_image) : 'https://via.placeholder.com/40' }}" 
                                 alt="{{ auth()->user()->first_name }} {{ auth()->user()->last_name }} Avatar"
                                 width="40" height="40">
                            <span>{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</span>
                            <form method="POST" action="{{ route('cashier.logout') }}" style="display: inline;">
                                @csrf
                                <button type="submit" class="logout-btn">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </button>
                            </form>
                        @endauth
                    </div>
                </div>
            </header>
            
            <!-- View Toggle -->
            <div class="view-toggle">
                <button class="active" data-tab="status-actions">Appointment</button>
                <button data-tab="dashboard">POS</button>
            </div>

            <!-- Dashboard Tab -->
            <div id="dashboard" class="tab-content" style="display: none;">
                <div class="dashboard-container">
                    <!-- Product List -->
                    <div class="product-list">
                        <!-- Header with Search Bar Positioned at Top-Right -->
                        <div class="product-list-header">
                            <h2>Select Products</h2>
                            <div class="search-container">
                                <i class="fas fa-search search-icon"></i>
                                <input type="text" id="searchBar" placeholder="Search products..." onkeyup="filterProducts()">
                            </div>
                        </div>

                        <div class="product-grid" id="productGrid">
                            @foreach($products as $product)
                                @if($product->category !== 'Consumable')
                                <div class="product-card">
                                    <div class="product-image-container">
                                        <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->product_name }}">
                                        <div class="product-overlay">
                                            <span class="product-category">{{ $product->category }}</span>
                                        </div>
                                    </div>

                                    <!-- Product Info -->
                                    <div class="product-info">
                                        <div class="product-header">
                                            <h3 class="product-name">{{ $product->product_name }}</h3>
                                            <span class="price">₱{{ number_format($product->price, 2) }}</span>
                                        </div>

                                        <!-- Stock Display -->
                                        <div class="stock-status">
                                            <span class="stock-label">Stock:</span>
                                            @if($product->stocks > 5)
                                            <span class="stock-available"><i class="fas fa-check-circle"></i> Available</span>
                                            @elseif($product->stocks > 0 && $product->stocks <= 5)
                                            <span class="stock-low"><i class="fas fa-exclamation-circle"></i> Low</span>
                                            @else
                                            <span class="stock-empty"><i class="fas fa-times-circle"></i> Empty</span>
                                            @endif
                                        </div>

                                        <!-- Quantity and Add Button -->
                                        <div class="quantity-add-container">
                                            <div class="quantity-container">
                                                <button type="button" onclick="decreaseQuantity({{ $product->id }})" class="qty-btn">
                                                    <i class="fas fa-minus"></i>
                                                </button>
                                                <input type="text" id="qty-{{ $product->id }}" value="1" min="1" readonly>
                                                <button type="button" onclick="increaseQuantity({{ $product->id }})" class="qty-btn">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </div>
                                            <button class="add-btn" onclick="addToCart({{ $product->id }}, '{{ $product->product_name }}', {{ $product->price }})">
                                                <i class="fas fa-shopping-cart"></i> Add to Cart
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    <!-- Cart Section -->
                    <div class="cart">
                        <div class="cart-header">
                            <h2><i class="fas fa-shopping-basket"></i> Order Summary</h2>
                        </div>
                        <div class="cart-content">
                            <table>
                                <tbody id="cart-items"></tbody>
                            </table>
                            <div class="cart-total">
                                <h3>Payable Total: ₱<span id="total-amount">0.00</span></h3>
                                <button onclick="openCheckoutModal()" class="checkout-btn">
                                    <i class="fas fa-credit-card"></i> Checkout
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Checkout Modal -->
            <div id="checkoutModal" class="modal" style="display: none;">
                <div class="modal-content">
                    <span class="close-x" onclick="closeCheckoutModal()">&times;</span>
                    <h2>Checkout</h2>
                    <label>Order ID:</label>
                    <input type="text" id="orderId" readonly>
                    <label>Total Amount:</label>
                    <input type="text" id="modalTotal" readonly>
                    <label>Payment:</label>
                    <input type="text" id="paymentAmount" oninput="calculateChange()" pattern="[0-9]*" inputmode="numeric"
                        onkeypress="return event.charCode >= 48 && event.charCode <= 57">
                    <label>Change:</label>
                    <input type="text" id="changeAmount" readonly>
                    <div class="modal-buttons">
                        <button onclick="confirmCheckout()">Proceed</button>
                    </div>
                    <!-- Updated receipt section -->
                    <div id="receiptSection" style="display: none; margin-top: 20px;">
                        <div class="receipt-buttons">
                            <button onclick="printReceipt()" class="print-btn"><i class="fas fa-print"></i> Print Receipt</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add this hidden div for receipt content -->
            <div id="receiptContent" style="display: none;"></div>

            <style>
                /* Modern Product List Styles */
                .product-list {
                    flex: 2;
                    padding: 25px;
                    background: #fff;
                    border-radius: 15px;
                    box-shadow: 0 4px 6px rgba(0,0,0,0.05);
                }

                .product-list-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 20px;
                }

                .product-list-header h2 {
                    font-size: 20px;
                    color: #2d3748;
                    font-weight: 600;
                }

                .search-container {
                    position: relative;
                    width: 250px;
                }

                .search-icon {
                    position: absolute;
                    left: 10px;
                    top: 50%;
                    transform: translateY(-50%);
                    color: #a0aec0;
                    font-size: 14px;
                }

                .search-container input {
                    width: 100%;
                    padding: 8px 8px 8px 35px;
                    border: 1px solid #e2e8f0;
                    border-radius: 8px;
                    font-size: 13px;
                    transition: all 0.3s ease;
                }

                .search-container input:focus {
                    outline: none;
                    border-color: #4CAF50;
                    box-shadow: 0 0 0 2px rgba(76,175,80,0.1);
                }

                .product-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                    gap: 20px;
                    margin-top: 20px;
                }

                .product-card {
                    background: #fff;
                    border-radius: 10px;
                    overflow: hidden;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
                    transition: all 0.3s ease;
                }

                .product-card:hover {
                    transform: translateY(-5px);
                    box-shadow: 0 8px 16px rgba(0,0,0,0.1);
                }

                .product-image-container {
                    position: relative;
                    height: 160px;
                    overflow: hidden;
                }

                .product-image-container img {
                    width: 100%;
                    height: 100%;
                    object-fit: cover;
                    transition: transform 0.3s ease;
                }

                .product-card:hover .product-image-container img {
                    transform: scale(1.05);
                }

                .product-overlay {
                    position: absolute;
                    top: 10px;
                    right: 10px;
                    background: rgba(76,175,80,0.9);
                    padding: 5px 10px;
                    border-radius: 20px;
                }

                .product-category {
                    color: white;
                    font-size: 12px;
                    font-weight: 500;
                }

                .product-info {
                    padding: 15px;
                }

                .product-header {
                    margin-bottom: 12px;
                }

                .product-name {
                    font-size: 14px;
                    font-weight: 600;
                    color: #2d3748;
                    margin: 0;
                    flex: 1;
                }

                .price {
                    font-size: 16px;
                    font-weight: 700;
                    color: #4CAF50;
                    margin-left: 8px;
                }

                .stock-status {
                    margin-bottom: 12px;
                    font-size: 12px;
                }

                .quantity-add-container {
                    gap: 8px;
                }

                .quantity-container {
                    padding: 4px;
                    border-radius: 6px;
                }

                .qty-btn {
                    width: 28px;
                    height: 28px;
                    font-size: 12px;
                }

                .quantity-container input {
                    width: 35px;
                    height: 28px;
                    font-size: 13px;
                }

                .add-btn {
                    padding: 8px;
                    font-size: 13px;
                }

                /* Modern Cart Styles */
                .cart {
                    flex: 1;
                    min-width: 350px;
                    max-width: 400px;
                    background: #fff;
                    border-radius: 15px;
                    box-shadow: 0 4px 6px rgba(0,0,0,0.05);
                    overflow: hidden;
                    height: fit-content;
                }

                .cart-header {
                    padding: 20px;
                    background: #4CAF50;
                    color: white;
                }

                .cart-header h2 {
                    margin: 0;
                    font-size: 20px;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }

                .cart-content {
                    padding: 20px;
                }

                .cart table {
                    width: 100%;
                    margin-bottom: 20px;
                }

                .cart table td {
                    padding: 10px 0;
                    border-bottom: 1px solid #e2e8f0;
                    font-size: 14px;
                }

                .cart-total {
                    margin-top: 20px;
                    padding-top: 20px;
                    border-top: 2px solid #e2e8f0;
                }

                .cart-total h3 {
                    color: #2d3748;
                    font-size: 18px;
                    margin-bottom: 15px;
                }

                .checkout-btn {
                    width: 100%;
                    padding: 14px;
                    background: #4CAF50;
                    color: white;
                    border: none;
                    border-radius: 8px;
                    cursor: pointer;
                    font-size: 16px;
                    font-weight: 500;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 8px;
                    transition: all 0.3s ease;
                }

                .checkout-btn:hover {
                    background: #43a047;
                    transform: translateY(-1px);
                }

                /* Modal styles */
                .modal {
                    display: none;
                    position: fixed;
                    z-index: 1000;
                    left: 0;
                    top: 0;
                    width: 100%;
                    height: 100%;
                    background-color: rgba(0,0,0,0.7);
                    
                    
                }

                .modal-content {
                    background-color: #fefefe;
                    margin: 0 auto;
                    padding: 20px;
                    border: 1px solid #888;
                    width: 80%;
                    max-width: 800px;
                    border-radius: 8px;
                    position: relative;
                    max-height: 90vh;
                    overflow-y: auto;
                }

                .modal-content input {
                    width: 100%;
                    padding: 8px;
                    margin: 5px 0 15px;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                }

                .modal-buttons {
                    display: flex;
                    justify-content: flex-end;
                    gap: 10px;
                }

                .modal-buttons button {
                    padding: 8px 20px;
                    border: none;
                    border-radius: 4px;
                    cursor: pointer;
                }

                .print-btn {
                    background: #4CAF50;
                    color: white;
                    padding: 10px 20px;
                    border: none;
                    border-radius: 4px;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    gap: 5px;
                }

                /* Add these styles to your existing styles */
                .appointment-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 10px;
                }

                .appointment-time {
                    font-weight: 600;
                    color: #1abc9c;
                }

                .more-options-container {
                    position: relative;
                }

                .btn-more {
                    background: none;
                    border: none;
                    color: #64748b;
                    cursor: pointer;
                    padding: 5px;
                    border-radius: 4px;
                    transition: all 0.2s ease;
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
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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

                /* Add this new style for the dashboard container */
                .dashboard-container {
                    display: flex;
                    gap: 25px;
                    padding: 25px;
                }

                /* Add these receipt styles */
                .receipt {
                    
                    font-family: Arial, sans-serif;
                    max-width: 800px;
                    margin: 0 auto;
                    padding: 20px;
                }
                .receipt-header {
                    text-align: center;
                    margin-bottom: 20px;
                    border-bottom: 2px solid #000;
                    padding-bottom: 10px;
                }
                .receipt-header img {
                    max-width: 100px;
                    height: auto;
                    margin-bottom: 10px;
                }
                .receipt-info {
                    margin-bottom: 15px;
                    border-bottom: 1px dashed #000;
                    padding-bottom: 10px;
                }
                .info-row {
                    display: flex;
                    justify-content: space-between;
                    margin: 5px 0;
                }
                .receipt-items {
                    margin: 15px 0;
                }
                .receipt-items table {
                    width: 100%;
                    border-collapse: collapse;
                }
                .receipt-items th {
                    text-align: left;
                    padding: 8px 0;
                    border-bottom: 2px solid #000;
                }
                .receipt-items td {
                    padding: 8px 0;
                    border-bottom: 1px dashed #000;
                }
                .text-right {
                    text-align: right;
                }
                .receipt-summary {
                    margin-top: 15px;
                    border-top: 1px dashed #000;
                    padding-top: 10px;
                }
                .summary-row {
                    display: flex;
                    justify-content: space-between;
                    margin: 5px 0;
                }
                .summary-row.total {
                    font-weight: bold;
                    border-top: 2px solid #000;
                    padding-top: 5px;
                    margin-top: 5px;
                }
                .receipt-footer {
                    margin-top: 20px;
                    text-align: center;
                    font-size: 12px;
                    border-top: 1px dashed #000;
                    padding-top: 10px;
                }
                .receipt-footer p {
                    margin: 5px 0;
                }
                .receipt-buttons {
                    display: flex;
                    gap: 10px;
                    justify-content: center;
                }
                .print-btn {
                    padding: 10px 20px;
                    border: none;
                    border-radius: 5px;
                    cursor: pointer;
                    font-size: 14px;
                    display: flex;
                    align-items: center;
                    gap: 5px;
                    transition: all 0.3s ease;
                    background-color: #4CAF50;
                    color: white;
                }
                .print-btn:hover {
                    background-color: #45a049;
                }
                /* Add these styles to your existing styles */
                .user-profile {
                    display: flex;
                    align-items: center;
                    gap: 15px;
                }

                .logout-btn {
                    background-color: #dc3545;
                    color: white;
                    border: none;
                    padding: 8px 15px;
                    border-radius: 4px;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    gap: 5px;
                    font-size: 14px;
                    transition: background-color 0.3s ease;
                }

                .logout-btn:hover {
                    background-color: #c82333;
                }

                .logout-btn i {
                    font-size: 16px;
                }

                /* Add these styles to your existing styles */
                .payment-method {
                    margin-top: 8px;
                    font-size: 0.9em;
                    color: #666;
                    display: flex;
                    align-items: center;
                    gap: 5px;
                }

                .payment-method i {
                    color: #4CAF50;
                }
            </style>

            <script>
                // Add the POS JavaScript code here
                let cart = [];
                let isProcessing = false;

                function addToCart(id, name, price) {
                    let qty = parseInt(document.getElementById('qty-' + id).value);
                    if (qty < 1) return;

                    let existingItem = cart.find(item => item.id === id);
                    if (existingItem) {
                        existingItem.quantity += qty;
                        existingItem.subtotal = existingItem.quantity * price;
                    } else {
                        cart.push({
                            id,
                            name,
                            price,
                            quantity: qty,
                            subtotal: qty * price
                        });
                    }

                    updateCart();
                    showNotification(`${name} added to cart`, 'success');
                }

                function updateCart() {
                    let cartTable = document.getElementById('cart-items');
                    cartTable.innerHTML = '';
                    let total = 0;

                    if (cart.length === 0) {
                        cartTable.innerHTML = '<tr><td colspan="3" class="empty-cart"></td></tr>';
                    } else {
                        cart.forEach((item, index) => {
                            total += item.subtotal;
                            cartTable.innerHTML += `
                            <tr>
                                <td>${item.name} x ${item.quantity}</td>
                                <td>₱${item.subtotal.toFixed(2)}</td>
                                <td><button class="remove-btn" onclick="removeFromCart(${index})"><i class="fas fa-times"></i></button></td>
                            </tr>
                            `;
                        });
                    }

                    document.getElementById('total-amount').innerText = total.toFixed(2);
                }

                function removeFromCart(index) {
                    const removedItem = cart[index];
                    cart.splice(index, 1);
                    updateCart();
                    showNotification(`${removedItem.name} removed from cart`, 'warning');
                }

                function confirmCheckout() {
                    if (isProcessing) return;
                    
                    const paymentInput = document.getElementById('paymentAmount');
                    const paymentAmount = parseFloat(paymentInput.value) || 0;
                    const totalAmount = parseFloat(document.getElementById('modalTotal').value.replace('₱', ''));

                    if (!cart.length) {
                        showNotification("Your cart is empty!", 'error');
                        return;
                    }

                    if (paymentAmount < totalAmount) {
                        showNotification("Payment amount is insufficient", 'error');
                        paymentInput.focus();
                        return;
                    }

                    isProcessing = true;
                    const checkoutBtn = document.querySelector('#checkoutModal .modal-buttons button:last-child');
                    checkoutBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                    checkoutBtn.disabled = true;

                    let orderData = {
                        order_id: document.getElementById("orderId").value,
                        total_amount: totalAmount,
                        amount_paid: paymentAmount,
                        products: cart.map(item => ({
                            id: item.id,
                            quantity: item.quantity
                        }))
                    };

                    fetch("{{ route('cashier.checkout') }}", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": "{{ csrf_token() }}",
                            "Accept": "application/json"
                        },
                        body: JSON.stringify(orderData)
                    })
                    .then(async response => {
                        const data = await response.json();
                        if (!response.ok) {
                            throw new Error(data.message || 'Payment processing failed');
                        }
                        return data;
                    })
                    .then(data => {
                        showNotification(data.message || "Order placed successfully!", 'success');
                        document.getElementById('receiptSection').style.display = 'block';
                        prepareReceiptContent(orderData.order_id, totalAmount, paymentAmount);
                        cart = [];
                        updateCart();
                        document.getElementById('paymentAmount').value = '';
                        document.getElementById('changeAmount').value = '';
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification(error.message || "Checkout failed. Please try again.", 'error');
                    })
                    .finally(() => {
                        isProcessing = false;
                        checkoutBtn.innerHTML = 'Proceed';
                        checkoutBtn.disabled = false;
                    });
                }

                function prepareReceiptContent(orderId, totalAmount, paymentAmount) {
                    try {
                        const change = paymentAmount - totalAmount;
                        const date = new Date().toLocaleString();
                        
                        let receiptHTML = `
                            <div class="receipt">
                                <div class="receipt-header">
                                    <img src="{{ asset('images/newlogo.png') }}" alt="King Salon Logo">
                                    <p>ANGATAN TABUC-TUBIG</p>
                                    <p>DUMAGUETE CITY NEGROS ORIENTAL</p>
                                    <p>VAT REG. TIN#314-007-068-00</p>
                                </div>
                                
                                <div class="receipt-info">
                                    <div class="info-row">
                                        <span>Order ID:</span>
                                        <span>#${orderId}</span>
                                    </div>
                                    <div class="info-row">
                                        <span>Sales Invoice No:</span>
                                        <span>00000000001</span>
                                    </div>
                                    <div class="info-row">
                                        <span>TXN NUMBER:</span>
                                        <span>0000000000</span>
                                    </div>
                                    <div class="info-row">
                                        <span>Cashier:</span>
                                        <span>REGINE</span>
                                    </div>
                                    <div class="info-row">
                                        <span>Date & Time:</span>
                                        <span>${date}</span>
                                    </div>
                                </div>
                                
                                <div class="receipt-items">
                                    <h3>PRODUCTS</h3>
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>Item</th>
                                                <th class="text-right">Qty</th>
                                                <th class="text-right">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${cart.map(item => `
                                                <tr>
                                                    <td>${item.name}</td>
                                                    <td class="text-right">${item.quantity}</td>
                                                    <td class="text-right">₱${item.subtotal.toFixed(2)}</td>
                                                </tr>
                                            `).join('')}
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div class="receipt-summary">
                                    <div class="summary-row">
                                        <span>TOTAL AMOUNT:</span>
                                        <span>₱${totalAmount.toFixed(2)}</span>
                                    </div>
                                    <div class="summary-row">
                                        <span>AMOUNT PAID:</span>
                                        <span>₱${paymentAmount.toFixed(2)}</span>
                                    </div>
                                    <div class="summary-row total">
                                        <span>CHANGE:</span>
                                        <span>₱${change.toFixed(2)}</span>
                                    </div>
                                </div>
                                
                                <div class="receipt-footer">
                                    <p>THANK YOU COME AGAIN</p>
                                    <p>PLEASE FOLLOW US ON FB: KING SALON II</p>
                                    <p>For Customers Feedback and Inquiries</p>
                                    <p>You may send an email to:</p>
                                    <p>info@kingsalon@gmail.com</p>
                                    <p>THE OFFICIAL RECEIPT WILL BE VALID FIVE (5) YEARS FROM DATE OF APPOINTMENT</p>
                                </div>
                            </div>
                        `;
                        
                        document.getElementById('receiptContent').innerHTML = receiptHTML;
                    } catch (error) {
                        console.error('Error preparing receipt:', error);
                        showNotification('Error preparing receipt', 'error');
                    }
                }

                function printReceipt() {
                    const receiptContent = document.getElementById('receiptContent').innerHTML;
                    const previewWindow = window.open('', '_blank', 'width=800,height=800');
                    
                    if (!previewWindow) {
                        showNotification('Please allow popups to view receipt', 'error');
                        return;
                    }

                    previewWindow.document.write(`
                        <!DOCTYPE html>
                        <html>
                        <head>
                            <title>Receipt Preview</title>
                            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
                            <style>
                                body { 
                                    font-family: Arial, sans-serif; 
                                    padding: 20px;
                                    max-width: 800px;
                                    margin: 0 auto;
                                    background: #f5f5f5;
                                }
                                .receipt {
                                    padding: 20px;
                                    background: white;
                                    box-shadow: 0 0 10px rgba(0,0,0,0.1);
                                    border-radius: 5px;
                                    margin-top: 60px;
                                }
                                .receipt-header { 
                                    text-align: center; 
                                    margin-bottom: 20px;
                                    border-bottom: 2px solid #000;
                                    padding-bottom: 10px;
                                }
                                .receipt-header img {
                                    max-width: 100px;
                                    height: auto;
                                    margin-bottom: 10px;
                                }
                                .receipt-info { 
                                    margin-bottom: 15px; 
                                    border-bottom: 1px dashed #000; 
                                    padding-bottom: 10px;
                                }
                                .info-row {
                                    display: flex;
                                    justify-content: space-between;
                                    margin: 5px 0;
                                }
                                .receipt-items {
                                    margin: 15px 0;
                                }
                                .receipt-items table {
                                    width: 100%;
                                    border-collapse: collapse;
                                }
                                .receipt-items th {
                                    text-align: left;
                                    padding: 8px 0;
                                    border-bottom: 2px solid #000;
                                }
                                .receipt-items td {
                                    padding: 8px 0;
                                    border-bottom: 1px dashed #000;
                                }
                                .text-right {
                                    text-align: right;
                                }
                                .receipt-summary {
                                    margin-top: 15px;
                                    border-top: 1px dashed #000;
                                    padding-top: 10px;
                                }
                                .summary-row {
                                    display: flex;
                                    justify-content: space-between;
                                    margin: 5px 0;
                                }
                                .summary-row.total {
                                    font-weight: bold;
                                    border-top: 2px solid #000;
                                    padding-top: 5px;
                                    margin-top: 5px;
                                }
                                .receipt-footer {
                                    margin-top: 20px;
                                    text-align: center;
                                    font-size: 12px;
                                    border-top: 1px dashed #000;
                                    padding-top: 10px;
                                }
                                .receipt-footer p {
                                    margin: 5px 0;
                                }
                                .action-buttons {
                                    position: fixed;
                                    top: 0;
                                    left: 0;
                                    right: 0;
                                    background: white;
                                    padding: 10px;
                                    display: flex;
                                    gap: 10px;
                                    justify-content: center;
                                    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
                                    z-index: 1000;
                                }
                                .action-btn {
                                    padding: 10px 20px;
                                    border: none;
                                    border-radius: 5px;
                                    cursor: pointer;
                                    font-size: 14px;
                                    display: flex;
                                    align-items: center;
                                    gap: 5px;
                                    transition: all 0.3s ease;
                                    color: white;
                                    min-width: 120px;
                                    justify-content: center;
                                }
                                .print-btn {
                                    background-color: #4CAF50;
                                }
                                .print-btn:hover {
                                    background-color: #45a049;
                                }
                                .close-btn {
                                    background-color: #6c757d;
                                }
                                .close-btn:hover {
                                    background-color: #5a6268;
                                }
                                @media print {
                                    body {
                                        background: white;
                                        padding: 0;
                                        margin: 0;
                                    }
                                    .receipt {
                                        box-shadow: none;
                                        margin-top: 0;
                                    }
                                    .action-buttons {
                                        display: none;
                                    }
                                }
                            </style>
                        </head>
                        <body>
                            <div class="action-buttons">
                                <button onclick="window.print()" class="action-btn print-btn">
                                    <i class="fas fa-print"></i> Print
                                </button>
                                <button onclick="window.close()" class="action-btn close-btn">
                                    <i class="fas fa-times"></i> Close
                                </button>
                            </div>
                            ${receiptContent}
                        </body>
                        </html>
                    `);

                    previewWindow.document.close();
                }

                function increaseQuantity(id) {
                    let qtyInput = document.getElementById('qty-' + id);
                    qtyInput.value = parseInt(qtyInput.value) + 1;
                }

                function decreaseQuantity(id) {
                    let qtyInput = document.getElementById('qty-' + id);
                    if (qtyInput.value > 1) {
                        qtyInput.value = parseInt(qtyInput.value) - 1;
                    }
                }

                function openCheckoutModal() {
                    if (cart.length === 0) {
                        showNotification("Your cart is empty!", 'error');
                        return;
                    }

                    let totalAmount = document.getElementById('total-amount').innerText;
                    document.getElementById('checkoutModal').style.display = 'flex';
                    document.getElementById('modalTotal').value = '₱' + totalAmount;
                    document.getElementById('orderId').value = 'ORD' + Date.now().toString().slice(-6);
                    document.getElementById('paymentAmount').focus();
                }

                function closeCheckoutModal() {
                    document.getElementById('checkoutModal').style.display = 'none';
                    document.getElementById('receiptSection').style.display = 'none';
                }

                function calculateChange() {
                    let total = parseFloat(document.getElementById('modalTotal').value.replace('₱', ''));
                    let payment = parseFloat(document.getElementById('paymentAmount').value) || 0;
                    let change = payment - total;
                    
                    const changeField = document.getElementById('changeAmount');
                    if (change >= 0) {
                        changeField.value = '₱' + change.toFixed(2);
                        changeField.classList.remove('negative-change');
                    } else {
                        changeField.value = '(₱' + Math.abs(change).toFixed(2) + ')';
                        changeField.classList.add('negative-change');
                    }
                }

                function showNotification(message, type = 'info') {
                    let notification = document.getElementById('notification');
                    if (!notification) {
                        notification = document.createElement('div');
                        notification.id = 'notification';
                        document.body.appendChild(notification);
                    }

                    notification.textContent = message;
                    notification.className = `notification show ${type}`;
                    
                    setTimeout(() => {
                        notification.classList.remove('show');
                    }, 3000);
                }

                // Initialize the POS functionality
                document.addEventListener('DOMContentLoaded', function() {
                    document.getElementById('checkoutModal').style.display = 'none';
                    document.getElementById('paymentAmount').value = '';
                    document.getElementById('changeAmount').value = '';
                });
            </script>

            <!-- Status Actions Tab -->
            <div id="status-actions" class="tab-content">
                <div class="weekly-calendar">
                    <div class="calendar-header">
                        <h2>All Appointments This Week </h2>
                        <div class="calendar-controls">
                            <div class="current-time">
                                <i class="fas fa-clock"></i>
                                <span id="current-time"></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="calendar-grid">
                        @php
                            $today = now();
                            $days = [];
                            // Get all unique dates from appointments
                            $allDates = collect($weeklyAppointments)->keys()->sort()->values();
                            
                            // If no appointments, show current week
                            if ($allDates->isEmpty()) {
                                for ($i = 0; $i < 7; $i++) {
                                    $date = $today->copy()->addDays($i);
                                    $days[] = [
                                        'date' => $date->format('Y-m-d'),
                                        'short_day' => $date->format('D'),
                                        'formatted_date' => $date->format('M d, Y'),
                                        'is_today' => $i === 0
                                    ];
                                }
                            } else {
                                // Use actual appointment dates
                                foreach ($allDates as $date) {
                                    $dateObj = \Carbon\Carbon::parse($date);
                                    $days[] = [
                                        'date' => $date,
                                        'short_day' => $dateObj->format('D'),
                                        'formatted_date' => $dateObj->format('M d, Y'),
                                        'is_today' => $date === $today->format('Y-m-d')
                                    ];
                                }
                            }
                        @endphp

                        @foreach($days as $day)
                            <div class="calendar-day {{ $day['is_today'] ? 'today' : '' }}">
                                <div class="day-header">
                                    <span class="day-name">{{ $day['short_day'] }}</span>
                                    <span class="day-date">{{ $day['formatted_date'] }}</span>
                                </div>
                                <div class="day-appointments">
                                    @if(isset($weeklyAppointments[$day['date']]))
                                        @foreach($weeklyAppointments[$day['date']]->sortBy('appointment_time') as $appointment)
                                            @if($appointment->status === 'Pending' || $appointment->status === 'Accepted')
                                                <!-- Debug info -->
                                                @php
                                                    echo "<!-- Debug: Payment Method = " . $appointment->payment_method . " -->";
                                                    echo "<!-- Debug: Upload Picture = " . ($appointment->upload_picture ?? 'null') . " -->";
                                                @endphp
                                                <div class="appointment-card status-{{ strtolower($appointment->status) }}">
                                                    <div class="appointment-header">
                                                        <div class="appointment-time">
                                                            {{ date('g:i A', strtotime($appointment->appointment_time)) }}
                                                        </div>
                                                        <div class="more-options-container">
                                                            <button type="button" class="btn-more" onclick="toggleMoreOptions({{ $appointment->id }})">
                                                                <i class="fas fa-ellipsis-v"></i>
                                                            </button>
                                                            <div id="moreOptions-{{ $appointment->id }}" class="more-options">
                                                                <ul>
                                                                    @if(strtoupper($appointment->payment_method) === 'GCASH' && $appointment->upload_picture)
                                                                        <li>
                                                                            <a href="javascript:void(0)" onclick="viewDetails({{ $appointment->id }}, '{{ $appointment->upload_picture }}')">
                                                                                <i class="fas fa-money-check-alt"></i> View Payment
                                                                            </a>
                                                                        </li>
                                                                    @endif
                                                                    <li>
                                                                        <a href="javascript:void(0)" onclick="viewReceipt({{ $appointment->id }})">
                                                                            <i class="fas fa-receipt"></i> View E-Receipt
                                                                        </a>
                                                                    </li>
                                                                </ul>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="appointment-details">
                                                        <h4>{{ $appointment->full_name }}</h4>
                                                        <p class="service">{{ $appointment->service_name }}</p>
                                                        <p class="staff">
                                                            <i class="fas fa-user"></i>
                                                            @php
                                                                try {
                                                                    $staffArray = is_string($appointment->selected_staff) 
                                                                        ? json_decode($appointment->selected_staff, true) 
                                                                        : $appointment->selected_staff;
                                                                    
                                                                    if (is_array($staffArray)) {
                                                                        $staffNames = array_map(function($staff) {
                                                                            if (is_array($staff)) {
                                                                                return ($staff['first_name'] ?? '') . ' ' . ($staff['last_name'] ?? '');
                                                                            }
                                                                            return $staff;
                                                                        }, $staffArray);
                                                                        echo implode(', ', $staffNames);
                                                                    } else {
                                                                        echo $appointment->selected_staff ?? 'N/A';
                                                                    }
                                                                } catch (\Exception $e) {
                                                                    echo 'N/A';
                                                                }
                                                            @endphp
                                                        </p>
                                                        <p class="payment-method">
                                                            <i class="fas fa-credit-card"></i>
                                                            Payment: {{ $appointment->payment_method ?? 'Not specified' }}
                                                        </p>
                                                    </div>
                                                    <div class="appointment-actions">
                                                        @if($appointment->status === 'Pending')
                                                            <form method="POST" action="{{ route('appointments.updateStatus', $appointment->id) }}" style="display: inline;">
                                                                @csrf
                                                                @method('PUT')
                                                                <input type="hidden" name="status" value="Accepted">
                                                                <button type="submit" class="action-btn approve-btn">Accept</button>
                                                            </form>
                                                            <button type="button" class="action-btn cancel-btn" onclick="openRejectModal('{{ $appointment->id }}')">Reject</button>
                                                        @endif
                                                        @if($appointment->status === 'Accepted')
                                                            <form method="POST" action="{{ route('appointments.updateStatus', $appointment->id) }}" style="display: inline;">
                                                                @csrf
                                                                @method('PUT')
                                                                <input type="hidden" name="status" value="Completed">
                                                                <button type="submit" class="action-btn complete-btn">Complete</button>
                                                            </form>
                                                            <form method="POST" action="{{ route('appointments.markAsNoShow', $appointment->id) }}" style="display: inline;">
                                                                @csrf
                                                                <button type="submit" class="action-btn no-show-btn">No Show</button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endif
                                        @endforeach
                                    @else
                                        <p class="no-appointments">No appointments</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Reschedule Modal -->
    <div id="rescheduleModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Reschedule Appointment</h3>
                <span class="close">&times;</span>
            </div>
            <form id="rescheduleForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="form-group">
                        <label for="reschedule_date">New Date:</label>
                        <input type="date" id="reschedule_date" name="appointment_date" required>
                    </div>
                    <div class="form-group">
                        <label for="reschedule_time">New Time:</label>
                        <input type="time" id="reschedule_time" name="appointment_time" required>
                    </div>
                    <div class="form-group">
                        <label for="reschedule_reason">Reason for Rescheduling:</label>
                        <textarea id="reschedule_reason" name="reason" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="cancel-btn" onclick="closeRescheduleModal()">Cancel</button>
                    <button type="submit" class="approve-btn">Confirm Reschedule</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get all tab buttons and content
            const tabButtons = document.querySelectorAll('.view-toggle button');
            const tabContents = document.querySelectorAll('.tab-content');

            // Function to switch tabs
            function switchTab(tabId) {
                // Hide all tab contents
                tabContents.forEach(content => {
                    content.style.display = 'none';
                });

                // Remove active class from all buttons
                tabButtons.forEach(button => {
                    button.classList.remove('active');
                });

                // Show selected tab content and activate button
                document.getElementById(tabId).style.display = 'block';
                document.querySelector(`[data-tab="${tabId}"]`).classList.add('active');
            }

            // Add click event listeners to tab buttons
            tabButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const tabId = button.getAttribute('data-tab');
                    switchTab(tabId);
                });
            });

            // Show initial tab (Appointments)
            switchTab('status-actions');
        });

        // Combined Sales Chart
 

        // Sales by Category Chart
 

        // Tab switching functionality
        document.querySelectorAll('.view-toggle button').forEach(button => {
            button.addEventListener('click', () => {
                // Update button states
                document.querySelectorAll('.view-toggle button').forEach(btn => {
                    btn.classList.remove('active');
                });
                button.classList.add('active');
                
                // Show/hide tabs
                const tabId = button.getAttribute('data-tab');
                document.querySelectorAll('.tab-content').forEach(tab => {
                    tab.classList.remove('active');
                });
                document.getElementById(tabId).classList.add('active');
            });
        });

        function previousWeek() {
            // Implement previous week navigation
        }

        function nextWeek() {
            // Implement next week navigation
        }

        function updateCurrentTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', { 
                hour: '2-digit', 
                minute: '2-digit',
                hour12: true 
            });
            document.getElementById('current-time').textContent = timeString;
        }

        // Update time immediately and then every second
        updateCurrentTime();
        setInterval(updateCurrentTime, 1000);

        // Add these functions to your existing script section
        function openRescheduleModal(appointmentId) {
            const modal = document.getElementById('rescheduleModal');
            const form = document.getElementById('rescheduleForm');
            form.action = `/appointments/${appointmentId}/reschedule`;
            modal.style.display = 'block';
        }

        function closeRescheduleModal() {
            const modal = document.getElementById('rescheduleModal');
            modal.style.display = 'none';
        }

        // Close modal when clicking the X
        document.querySelector('.close').addEventListener('click', closeRescheduleModal);

        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('rescheduleModal');
            if (event.target === modal) {
                closeRescheduleModal();
            }
        });

        // Set minimum date to today for reschedule date input
        document.getElementById('reschedule_date').min = new Date().toISOString().split('T')[0];
    </script>

    <!-- GCash Modal -->
    <div id="gcashModal" class="gcash-modal">
        <div class="gcash-modal-content">
            <span class="gcash-close" onclick="closeGcashModal()">&times;</span>
            <h2>GCash Payment Receipt</h2>
            <div id="gcashImageContainer">
                <img id="gcashImage" src="" alt="GCash Payment Receipt" onerror="this.onerror=null; this.src='{{ asset('images/no-image.png') }}';">
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    <div id="paymentModal" class="gcash-modal">
        <div class="gcash-modal-content">
            <span class="gcash-close" onclick="closePaymentModal()">&times;</span>
            <h2>Payment Receipt</h2>
            <div id="paymentImageContainer">
                <img id="paymentImage" src="" alt="Payment Receipt" onerror="this.onerror=null; this.src='{{ asset('images/no-image.png') }}';">
            </div>
        </div>
    </div>

    <!-- Receipt Modal -->
    <div id="receiptModal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <span class="close" onclick="closeReceiptModal()">&times;</span>
                <div class="receipt-actions" style="text-align:right; margin-bottom:10px;">
                    <button onclick="printEReceipt()" class="print-btn">
                        <i class="fas fa-print"></i> Print / Save as PDF
                    </button>
                </div>
            </div>
            <div class="receipt-container">
                <!-- Header -->
                <div class="receipt-header">
                    <img src="{{ asset('images/newlogo.png') }}" alt="King Salon Logo" style="width: 150px; height: auto;">
                    <h2>VAT REG. TIN#314-007-068-00</h2>
                    <p>ANGATAN TABUC-TUBIG</p>
                    <p>DUMAGUETE CITY</p>
                </div>

                <!-- Receipt Info -->
                <div class="receipt-info">
                    <div class="receipt-row">
                        <span class="label">Sales Invoice No:</span>
                        <span class="value" id="receipt-invoice">0000000001</span>
                    </div>
                    <div class="receipt-row">
                        <span class="label">TXN NUMBER:</span>
                        <span class="value" id="receipt-txn">0000000000</span>
                    </div>
                    <div class="receipt-row">
                        <span class="label">CASHIER:</span>
                        <span class="value" id="receipt-cashier">REGINE</span>
                    </div>
                    <div class="receipt-row">
                        <span class="label">BILLING TO:</span>
                        <span class="value" id="receipt-client"></span>
                    </div>
                </div>

                <!-- Services -->
                <div class="receipt-section">
                    <h3>SERVICES</h3>
                    <div id="receipt-services"></div>
                    <p id="receipt-duration" class="duration-text"></p>
                </div>

                <!-- Products -->
                <div class="receipt-section" id="receipt-products-section" style="display: none;">
                    <h3>PRODUCTS</h3>
                    <div id="receipt-products"></div>
                </div>

                <!-- Staff and Time -->
                <div class="receipt-details">
                    <div class="receipt-row">
                        <span class="label">STYLIST:</span>
                        <span class="value" id="receipt-stylist"></span>
                    </div>
                    <div class="receipt-row">
                        <span class="label">TIME:</span>
                        <span class="value" id="receipt-time"></span>
                    </div>
                    <div class="receipt-row">
                        <span class="label">DATE:</span>
                        <span class="value" id="receipt-date"></span>
                    </div>
                </div>

                <!-- Total and Payment -->
                <div class="receipt-total">
                    <div class="receipt-row">
                        <span class="label">TOTAL AMOUNT:</span>
                        <span class="value" id="receipt-total"></span>
                    </div>
                    <div class="receipt-row">
                        <span class="label">PAYMENT METHOD:</span>
                        <span class="value" id="receipt-payment"></span>
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
        </div>
    </div>

    <style>
        /* Modal Styles */
        .gcash-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.7);
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .gcash-modal-content {
            background-color: #fefefe;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 600px;
            border-radius: 8px;
            position: relative;
            max-height: 90vh;
            overflow-y: auto;
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
            text-align: center;
            margin-top: 20px;
        }

        #gcashImage, #paymentImage {
            max-width: 100%;
            height: auto;
            border-radius: 4px;
        }

        /* More Options Styles */
        .more-options-container {
            position: relative;
            display: inline-block;
        }

        .btn-more {
            background: none;
            border: none;
            color: #64748b;
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 4px;
            transition: all 0.2s ease;
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
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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

        /* Receipt Modal Styles */
        .receipt-container {
            padding: 20px;
            background: white;
            position: relative;
            height: 100%;
            overflow: hidden;
            
        }

        .receipt-header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #000;
            padding-bottom: 10px;
            background: white;
            z-index: 1;
        }

        .receipt-info, .receipt-details, .receipt-total {
            margin-bottom: 20px;
        }

        .receipt-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }

        .receipt-section {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 10px 0;
            margin-bottom: 20px;
        }

        .receipt-section h3 {
            margin-bottom: 10px;
        }

        .duration-text {
            font-style: italic;
            margin-top: 10px;
        }

        .receipt-footer {
            text-align: center;
            margin-top: 20px;
            border-top: 1px solid #000;
            padding-top: 10px;
        }

        .receipt-footer p {
            margin: 5px 0;
            font-size: 12px;
        }

        .label {
            font-weight: bold;
        }

        /* Ensure the close button is always visible */
        .close, .gcash-close {
            position: sticky;
            top: 0;
            right: 0;
            float: right;
            font-size: 28px;
            font-weight: bold;
            color: #aaa;
            cursor: pointer;
            z-index: 2;
            background: white;
            padding: 0 5px;
            border-radius: 4px;
        }

        .close:hover, .gcash-close:hover {
            color: black;
        }

        /* Add smooth scrolling to the modal content */
        .modal-content, .gcash-modal-content {
            scroll-behavior: smooth;
        }

        /* Ensure images in modals are properly sized */
        #gcashImage, #paymentImage {
            max-width: 100%;
            height: auto;
            border-radius: 4px;
            margin: 10px 0;
        }

        /* Add some spacing between sections in the receipt */
        .receipt-section {
            margin: 15px 0;
            padding: 10px 0;
        }

        /* Make sure the footer stays at the bottom */
        .receipt-footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #000;
        }

        /* Add these styles for the print button */
        .receipt-actions {
            position: sticky;
            top: 0;
            background: white;
            padding: 10px;
            text-align: right;
            z-index: 2;
            border-bottom: 1px solid #eee;
        }

        .print-btn {
            background: #1abc9c;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 14px;
            transition: background-color 0.3s;
        }

        .print-btn:hover {
            background-color: #16a085;
        }

        @media print {
            body * {
                visibility: hidden;
            }
            #receiptModal, #receiptModal * {
                visibility: visible;
            }
            #receiptModal {
                position: absolute;
                left: 0;
                top: 0;
            }
            .receipt-actions, .close {
                display: none;
            }
        }

        #receiptModal .modal-content {
            margin: 0 auto;
        }

        /* Add these styles to your existing styles */
        .modal-header {
            position: sticky;
            top: 0;
            background: white;
            padding: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #eee;
            z-index: 2;
        }

        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            padding: 0 10px;
            transition: color 0.3s ease;
        }

        .close:hover {
            color: #000;
        }

        .receipt-actions {
            display: flex;
            gap: 10px;
        }
    </style>

    <script>
        // Add these functions to your existing script section
        function openGcashModal(imageUrl) {
            console.log('Opening modal with image URL:', imageUrl);
            const modal = document.getElementById('gcashModal');
            const image = document.getElementById('gcashImage');
            
            image.src = imageUrl;
            modal.style.display = 'flex';
            
            image.onerror = function() {
                console.error('Error loading image:', imageUrl);
                this.src = '{{ asset('images/no-image.png') }}';
            };
        }

        function closeGcashModal() {
            document.getElementById('gcashModal').style.display = 'none';
        }

        function openPaymentModal(imageUrl) {
            console.log('Opening payment modal with image URL:', imageUrl);
            const modal = document.getElementById('paymentModal');
            const image = document.getElementById('paymentImage');
            
            image.src = imageUrl;
            modal.style.display = 'flex';
            
            image.onerror = function() {
                console.error('Error loading image:', imageUrl);
                this.src = '{{ asset('images/no-image.png') }}';
            };
        }

        function closePaymentModal() {
            document.getElementById('paymentModal').style.display = 'none';
        }

        // Initialize modals to be hidden on page load
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('gcashModal').style.display = 'none';
            document.getElementById('paymentModal').style.display = 'none';
            document.getElementById('receiptModal').style.display = 'none';
        });

        function toggleMoreOptions(appointmentId) {
            const options = document.getElementById(`moreOptions-${appointmentId}`);
            const allOptions = document.querySelectorAll('.more-options');
            
            // Close all other options
            allOptions.forEach(opt => {
                if (opt.id !== `moreOptions-${appointmentId}`) {
                    opt.style.display = 'none';
                }
            });

            // Toggle current options
            if (options.style.display === 'block') {
                options.style.display = 'none';
            } else {
                options.style.display = 'block';
            }
        }

        // Close options when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.btn-more') && !event.target.closest('.more-options')) {
                document.querySelectorAll('.more-options').forEach(opt => {
                    opt.style.display = 'none';
                });
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
            modal.querySelector('.modal-content').innerHTML = '<div style="text-align: center; padding: 20px;">Loading receipt data...</div>';

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
                        <div class="receipt-actions" style="text-align:right; margin-bottom:10px;">
                            <button onclick="printEReceipt()" class="print-btn">
                                <i class="fas fa-print"></i> Print / Save as PDF
                            </button>
                        </div>
                        <div class="receipt-container">
                            <!-- Header -->
                            <div class="receipt-header">
                                <img src="{{ asset('images/newlogo.png') }}" alt="King Salon Logo" style="width: 150px; height: auto;">
                                <h2>VAT REG. TIN#314-007-068-00</h2>
                                <p>ANGATAN TABUC-TUBIG</p>
                                <p>DUMAGUETE CITY</p>
                            </div>

                            <!-- Receipt Info -->
                            <div class="receipt-info">
                                <div class="receipt-row">
                                    <span class="label">Sales Invoice No:</span>
                                    <span class="value" id="receipt-invoice">0000000001</span>
                                </div>
                                <div class="receipt-row">
                                    <span class="label">TXN NUMBER:</span>
                                    <span class="value" id="receipt-txn">0000000000</span>
                                </div>
                                <div class="receipt-row">
                                    <span class="label">CASHIER:</span>
                                    <span class="value" id="receipt-cashier">REGINE</span>
                                </div>
                                <div class="receipt-row">
                                    <span class="label">BILLING TO:</span>
                                    <span class="value" id="receipt-client">${data.client_name}</span>
                                </div>
                            </div>

                            <!-- Services -->
                            <div class="receipt-section">
                                <h3>SERVICES</h3>
                                <div id="receipt-services">
                                    ${data.services.map(service => `
                                        <div class="receipt-row">
                                            <span>${service.name}</span>
                                            <span>₱${service.price.toFixed(2)}</span>
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
                                            <div class="receipt-row">
                                                <span>${product.name} x${product.quantity}</span>
                                                <span>₱${product.total_price.toFixed(2)}</span>
                                            </div>
                                        `).join('')}
                                    </div>
                                </div>
                            ` : ''}

                            <!-- Staff and Time -->
                            <div class="receipt-details">
                                <div class="receipt-row">
                                    <span class="label">STYLIST:</span>
                                    <span class="value" id="receipt-stylist">${data.stylist}</span>
                                </div>
                                <div class="receipt-row">
                                    <span class="label">TIME:</span>
                                    <span class="value" id="receipt-time">${data.time}</span>
                                </div>
                                <div class="receipt-row">
                                    <span class="label">DATE:</span>
                                    <span class="value" id="receipt-date">${data.date}</span>
                                </div>
                            </div>

                            <!-- Total and Payment -->
                            <div class="receipt-total">
                                <div class="receipt-row">
                                    <span class="label">TOTAL AMOUNT:</span>
                                    <span class="value" id="receipt-total">₱${data.total_amount.toFixed(2)}</span>
                                </div>
                                <div class="receipt-row">
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

        // Close modals when clicking outside
        window.onclick = function(event) {
            const gcashModal = document.getElementById('gcashModal');
            const paymentModal = document.getElementById('paymentModal');
            const receiptModal = document.getElementById('receiptModal');
            
            if (event.target === gcashModal) {
                closeGcashModal();
            }
            if (event.target === paymentModal) {
                closePaymentModal();
            }
            if (event.target === receiptModal) {
                closeReceiptModal();
            }
        }

        function printEReceipt() {
            var printContents = document.querySelector('#receiptModal .modal-content').innerHTML;
            var originalContents = document.body.innerHTML;
            document.body.innerHTML = printContents;
            window.print();
            document.body.innerHTML = originalContents;
            location.reload(); // Optional: reload to restore event listeners
        }
    </script>

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

    <script>
        function openRejectModal(appointmentId) {
            document.getElementById('rejectForm').action = `/cashier/appointments/${appointmentId}/reject`;
            document.getElementById('rejectModal').style.display = 'block';
        }

        function closeRejectModal() {
            document.getElementById('rejectModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('rejectModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>

    <style>
        /* Reject Modal Styles */
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
            border-radius: 8px;
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

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            resize: vertical;
        }

        .action-buttons {
            text-align: right;
        }

        .btn-reject-modal {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
        }

        .btn-reject-modal:hover {
            background-color: #c82333;
        }
    </style>
</body>
</html>