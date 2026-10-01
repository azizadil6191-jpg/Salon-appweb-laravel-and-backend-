<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cashier Dashboard</title>
    <link rel="stylesheet" href="{{ asset('css/history.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
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
                <h1><i class="fas fa-history"></i> Transaction History</h1>
            </div>
        </header>

        <div class="table-container">
            <div class="search-filter">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search transactions...">
                </div>
                <div class="date-filter">
                    <i class="fas fa-calendar-alt"></i>
                    <select>
                        <option>Today</option>
                        <option>This Week</option>
                        <option>This Month</option>
                        <option>Custom Range</option>
                    </select>
                </div>
            </div>

            @if($orders->isEmpty())
            <div class="empty-state">
                <i class="fas fa-receipt"></i>
                <h3>No transactions found</h3>
                <p>Completed transactions will appear here</p>
            </div>
            @else
            <div class="history-list">
                @foreach($orders as $order)
                <div class="order-card" data-order='{
                    "id": "{{ $order->order_id }}",
                    "date": "{{ $order->created_at->format('M d, Y h:i A') }}",
                    "items": @json($order->items->map(function($item) {
                        return [
                            "name" => $item->product->product_name,
                            "qty" => $item->quantity,
                            "subtotal" => $item->subtotal
                        ];
                    })),
                    "total": "{{ number_format($order->total_amount, 2) }}",
                    "paid": "{{ number_format($order->amount_paid, 2) }}",
                    "change": "{{ number_format($order->change_amount, 2) }}",
                    "cashier": "{{ Auth::guard('cashier')->user()->first_name }} {{ Auth::guard('cashier')->user()->last_name }}"
                }'>
                    <div class="order-header">
                        <span class="order-id">#{{ $order->order_id }}</span>
                        <span class="order-date">{{ $order->created_at->format('M d, Y h:i A') }}</span>
                    </div>
                    <div class="order-details">
                        <div class="order-items-count">
                            <i class="fas fa-boxes"></i> {{ $order->items->count() }} items
                        </div>
                        <div class="order-total">
                            <i class="fas fa-tag"></i> ₱{{ number_format($order->total_amount, 2) }}
                        </div>
                    </div>
                    <div class="order-actions">
                        <button class="view-btn" onclick="event.stopPropagation(); openOrderModal(JSON.parse(this.parentElement.parentElement.getAttribute('data-order')))">
                            <i class="fas fa-eye"></i> View
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    <div id="orderModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal()">&times;</span>
            <div id="modalContent"></div>
            <div class="modal-actions">
                <button class="btn-accept" onclick="printReceipt()">
                    <i class="fas fa-print"></i> Print Receipt
                </button>
                <button class="btn-save" onclick="saveAsPDF()">
                    <i class="fas fa-file-pdf"></i> Save as PDF
                </button>
                <button class="btn-reject" onclick="closeModal()">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>
        </div>
    </div>

    <style>
.btn-save {
    background-color: #e74c3c;
    color: white;
    border: none;
    padding: 8px 15px;
    border-radius: 8px; /* Soft, rounded corners */
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 5px;
    transition: background-color 0.3s;
}

.btn-save:hover {
    background-color: #c0392b;
}


        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
        }

        .receipt-logo {
            display: block;
            margin: 0 auto 10px auto;
            max-width: 320px; /* Adjust as needed */
            width: 100%;
            height: auto;
        }
    </style>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll(".order-card").forEach(card => {
                card.addEventListener("click", function() {
                    let orderData = JSON.parse(this.getAttribute("data-order"));
                    openOrderModal(orderData);
                });
            });
        });

        function openOrderModal(order) {
            const productsHTML = order.items.map(item => `
                <tr>
                    <td>${item.name}</td>
                    <td class="text-right">${item.qty}</td>
                    <td class="text-right">₱${parseFloat(item.subtotal).toFixed(2)}</td>
                </tr>
            `).join('');
            
            document.getElementById('modalContent').innerHTML = `
                <div class="receipt-header">
                    <img src="{{ asset('images/newlogo.png') }}" alt="King Salon Logo" class="receipt-logo" style="width: 100px; height: auto;">

                 
                    <p>ANGATAN TABUC-TUBIG</p>
                    <p>DUMAGUETE CITY NEGROS ORIENTAL</p>
                      <p>VAT REG. TIN#314-007-068-00</p>
            
                </div>
                
                <div class="receipt-info">
                                    <div class="info-row">
                        <span class="label"></span>
                        <span class="value">#${order.id}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Sales Invoice No:</span>
                        <span class="value">00000000001</span>
                    </div>
                    <div class="info-row">
                        <span class="label">TXN NUMBER:</span>
                        <span class="value">0000000000</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Cashier:</span>
                        <span class="value">${order.cashier}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Date & Time:</span>
                        <span class="value">${order.date}</span>
                    </div>
                </div>
                
                <div class="receipt-items">
                    <h3>SERVICES</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th class="text-right">Qty</th>
                                <th class="text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${productsHTML}
                        </tbody>
                    </table>
                </div>
                
                <div class="receipt-summary">
                    <div class="summary-row">
                        <span class="label">TOTAL AMOUNT:</span>
                        <span class="value">₱${order.total}</span>
                    </div>
                    <div class="summary-row">
                        <span class="label">AMOUNT PAID:</span>
                        <span class="value">₱${order.paid}</span>
                    </div>
                    <div class="summary-row total">
                        <span class="label">CHANGE:</span>
                        <span class="value">₱${order.change}</span>
                    </div>
                </div>
                
                <div class="receipt-footer">
                    <p>THANK YOU COME AGAIN</p>
                    <p>PLEASE FOLLOW US ON FB: KING SALON II</p>
                    <p>For Customers Feedback and Inquiries</p>
                    <p>You may send an email to:</p>
                    <p>info@kingsalon@gmail.com</p>
                    <p>THE OFFICIAL RECEIPT WILL BE VALID FIVE (5) YEARS FROM DATE OF APPOINTMENT</p>
                </div>`;
            
            document.getElementById('orderModal').style.display = 'flex';
        }
        
        function closeModal() {
            document.getElementById('orderModal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            if (event.target === document.getElementById('orderModal')) closeModal();
        }
        
        function saveAsPDF() {
            const modalContent = document.getElementById('modalContent').innerHTML;
            const element = document.createElement('div');
            element.innerHTML = `
                <div style="font-family: Arial, sans-serif; padding: 20px;">
                    ${modalContent}
                </div>
            `;
            
            const opt = {
                margin: 1,
                filename: `receipt-${document.querySelector('#modalContent .info-row .value').textContent}.pdf`,
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
            };

            html2pdf().set(opt).from(element).save();
        }

        function printReceipt() {
            const modalContent = document.getElementById('modalContent').innerHTML;
            const printWindow = window.open('', '', 'width=600,height=600');
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Receipt #${document.querySelector('#modalContent .info-row .value').textContent}</title>
                    <style>
                        body { 
                            font-family: Arial, sans-serif; 
                            padding: 20px;
                            max-width: 800px;
                            margin: 0 auto;
                        }
                        .receipt-header { 
                            text-align: center; 
                            margin-bottom: 20px;
                            border-bottom: 2px solid #000;
                            padding-bottom: 10px;
                        }
                        .receipt-header h2 { 
                            margin: 5px 0;
                            color: #000;
                            font-size: 24px;
                        }
                        .receipt-header p { 
                            margin: 3px 0; 
                            font-size: 14px;
                            color: #000;
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
                        .label {
                            font-weight: bold;
                            color: #000;
                        }
                        .value {
                            color: #000;
                        }
                        .receipt-items h3 {
                            text-align: center;
                            margin: 10px 0;
                            font-size: 18px;
                            color: #000;
                        }
                        table { 
                            width: 100%; 
                            border-collapse: collapse; 
                            margin: 15px 0;
                        }
                        th { 
                            text-align: left; 
                            padding: 8px 0; 
                            border-bottom: 2px solid #000;
                            color: #000;
                        }
                        td { 
                            padding: 8px 0; 
                            border-bottom: 1px dashed #000;
                            color: #000;
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
                            font-size: 14px;
                        }
                        .summary-row.total { 
                            font-weight: bold; 
                            font-size: 16px;
                            color: #000;
                            border-top: 2px solid #000;
                            padding-top: 5px;
                            margin-top: 5px;
                        }
                        .receipt-footer { 
                            margin-top: 20px; 
                            text-align: center; 
                            font-size: 10px; 
                            color: #000;
                            border-top: 1px dashed #000;
                            padding-top: 10px;
                        }
                        .receipt-footer p {
                            margin: 5px 0;
                        }
                        @media print {
                            body {
                                padding: 0;
                                margin: 0;
                            }
                            .receipt-header {
                                border-bottom: 2px solid #000;
                            }
                            .receipt-info {
                                border-bottom: 1px dashed #000;
                            }
                            .receipt-summary {
                                border-top: 1px dashed #000;
                            }
                            .summary-row.total {
                                border-top: 2px solid #000;
                            }
                            .receipt-footer {
                                border-top: 1px dashed #000;
                            }
                        }
                    </style>
                </head>
                <body>
                    ${modalContent}
                    <script>
                        window.onload = function() {
                            window.print();
                            setTimeout(function() { window.close(); }, 500);
                        };
                    <\/script>
                </body>
                </html>
            `);
            printWindow.document.close();
        }
    </script>
</body>
</html>