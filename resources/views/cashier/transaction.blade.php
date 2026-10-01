<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cashier Dashboard</title>
    <link rel="stylesheet" href="{{ asset('css/transaction.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
            <header></header>

            <!-- Product List -->
            <div class="product-list">
                <!-- Header with Search Bar Positioned at Top-Right -->
                <div class="product-list-header">
                    <h2>Select Products</h2>
                    <div class="search-container">
                        <input type="text" id="searchBar" placeholder="Search products..." onkeyup="filterProducts()">
                    </div>
                </div>

                <div class="product-grid" id="productGrid">
                    @foreach($products as $product)
                    <div class="product-card">
                        <div class="product-image-container">
                            <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->product_name }}">
                        </div>

                        <!-- Product Info -->
                        <div class="product-info">
                            <h3 class="product-name">{{ $product->product_name }}</h3>
                            <span class="category">{{ $product->category }}</span>
                            <span class="price">₱{{ number_format($product->price, 2) }}</span>

                            <!-- Stock Display -->
                            <div class="stock-status">
                                <span class="stock-label">Stock:</span>
                                @if($product->stocks > 5)
                                <span class="stock-available">Available</span>
                                @elseif($product->stocks > 0 && $product->stocks <= 5) <span class="stock-low">
                                    Low</span>
                                    @else
                                    <span class="stock-empty">Empty</span>
                                    @endif
                            </div>


                            <!-- Quantity and Add Button -->
                            <div class="quantity-add-container">
                                <div class="quantity-container">
                                    <button type="button" onclick="decreaseQuantity({{ $product->id }})">-</button>
                                    <input type="text" id="qty-{{ $product->id }}" value="1" min="1" readonly>
                                    <button type="button" onclick="increaseQuantity({{ $product->id }})">+</button>
                                </div>
                                <button class="add-btn"
                                    onclick="addToCart({{ $product->id }}, '{{ $product->product_name }}', {{ $product->price }})">
                                    Add
                                </button>
                            </div>
                        </div>

                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Cart Section -->
            <div class="cart">
                <h2>Order Summary</h2>
                <table>
                    <tbody id="cart-items"></tbody>
                </table>
                <h3>Payable Total: ₱<span id="total-amount">0.00</span></h3>
                <button onclick="openCheckoutModal()">Checkout</button>
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
            <!-- Add this new div for receipt printing -->
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
        /* Add these styles at the end of your existing styles */
        .receiptSection {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
        }

        .print-btn, .close-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }

        .print-btn {
            background-color: #4CAF50;
            color: white;
        }

        .close-btn {
            background-color: #f44336;
            color: white;
        }

        .print-btn:hover {
            background-color: #45a049;
        }

        .close-btn:hover {
            background-color: #da190b;
        }

        /* Add these styles */
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .close-modal {
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #666;
            transition: color 0.3s ease;
        }

        .close-modal:hover {
            color: #f44336;
        }

        /* Add these styles */
        .modal-content {
            position: relative;
        }

        .close-x {
            position: absolute;
            right: 10px;
            top: 10px;
            font-size: 20px;
            font-weight: bold;
            cursor: pointer;
            color: #666;
            transition: color 0.3s ease;
        }

        .close-x:hover {
            color: #f44336;
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
        /* Add these styles */
        .receipt-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .pdf-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
            background-color: #dc3545;
            color: white;
        }

        .pdf-btn:hover {
            background-color: #c82333;
        }
    </style>

    <script>
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

        // Validation
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
            cashier_id: {{ auth()->user()->id }},
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
                
                // Show the receipt section and prepare receipt content
                document.getElementById('receiptSection').style.display = 'block';
                prepareReceiptContent(orderData.order_id, totalAmount, paymentAmount);
                
                cart = [];
                updateCart();
                
                // Reset form
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

    function saveAsPDF() {
        try {
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
                        .pdf-btn {
                            background-color: #dc3545;
                        }
                        .pdf-btn:hover {
                            background-color: #c82333;
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

        } catch (error) {
            console.error('Error showing receipt preview:', error);
            showNotification('Error showing receipt preview', 'error');
        }
    }

    function printReceipt() {
        saveAsPDF(); // Use the same preview window for both print and PDF
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
        // Create notification element if it doesn't exist
        let notification = document.getElementById('notification');
        if (!notification) {
            notification = document.createElement('div');
            notification.id = 'notification';
            document.body.appendChild(notification);
        }

        notification.textContent = message;
        notification.className = `notification show ${type}`;
        
        // Auto-hide after 3 seconds
        setTimeout(() => {
            notification.classList.remove('show');
        }, 3000);
    }

    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('checkoutModal').style.display = 'none';
        document.getElementById('paymentAmount').value = '';
        document.getElementById('changeAmount').value = '';
    });
</script>

</body>
</html>