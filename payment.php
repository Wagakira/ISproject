<?php
session_start();

$host = 'localhost';
$dbname = 'catering_system';
$username = 'hannah_b';
$password = 'hannah1234$$';

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error = '';
$success = '';
$client_id = 1;  
$payments = [];
$pending_orders = [];
$receipt_data = null;

function simulateMpesaPayment($phone, $amount, $order_id) {
    return [
        'status' => 'success',
        'transaction_id' => 'MPE' . mt_rand(10000000, 99999999),
        'message' => "M-Pesa payment of Ksh $amount for Order #$order_id received from $phone."
    ];
}

function sendConfirmationEmail($client_id, $order_id, $amount, $payment_method, $transaction_id) {
    return "Confirmation email sent to client #$client_id for Order #$order_id: Paid Ksh $amount via $payment_method (Transaction ID: $transaction_id).";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['make_payment'])) {
    $order_id = $conn->real_escape_string($_POST['order_id']);
    $amount = $conn->real_escape_string($_POST['amount']);
    $payment_method = $conn->real_escape_string($_POST['payment_method']);
    $phone_number = isset($_POST['phone_number']) ? $conn->real_escape_string($_POST['phone_number']) : '';

    if (!in_array($payment_method, ['M-Pesa', 'Cash'])) {
        $error = "Invalid payment method selected.";
    } else {
        $order_query = $conn->query("SELECT caterer_id, total_price FROM orders WHERE order_id = '$order_id'");
        if ($order_query->num_rows == 0) {
            $error = "Invalid order selected.";
        } else {
            $order_data = $order_query->fetch_assoc();
            $caterer_id = $order_data['caterer_id'];
            $expected_amount = $order_data['total_price'];

            if ($amount != $expected_amount) {
                $error = "Amount does not match the order total (Ksh " . number_format($expected_amount, 2) . ").";
            } else {
                $transaction_id = '';
                $status = 'Completed';

                if ($payment_method == 'M-Pesa') {
                    if (empty($phone_number)) {
                        $error = "Phone number is required for M-Pesa payment.";
                    } else {
                        $mpesa_response = simulateMpesaPayment($phone_number, $amount, $order_id);
                        if ($mpesa_response['status'] == 'success') {
                            $transaction_id = $mpesa_response['transaction_id'];
                            $success .= $mpesa_response['message'] . "<br>";
                        } else {
                            $error = "M-Pesa payment failed.";
                            $status = 'Failed';
                        }
                    }
                } else {
                    $transaction_id = 'CASH' . mt_rand(10000000, 99999999);
                    $success .= "Cash payment of Ksh $amount for Order #$order_id recorded.<br>";
                }

                if (empty($error)) {
                    $sql = "INSERT INTO payments (client_id, caterer_id, amount, payment_method, transaction_id, status, payment_date)
                            VALUES ('$client_id', '$caterer_id', '$amount', '$payment_method', '$transaction_id', '$status', NOW())";
                    
                    if ($conn->query($sql)) {
                        // Update order status to paid
                        $conn->query("UPDATE orders SET status = 'Paid' WHERE order_id = '$order_id'");
                        
                        $email_message = sendConfirmationEmail($client_id, $order_id, $amount, $payment_method, $transaction_id);
                        $success .= $email_message . "<br>";

                        $receipt_data = [
                            'order_id' => $order_id,
                            'amount' => $amount,
                            'payment_method' => $payment_method,
                            'transaction_id' => $transaction_id,
                            'date' => date('M j, Y g:i a')
                        ];

                        $success .= "Payment completed successfully! Transaction ID: $transaction_id";
                    } else {
                        $error = "Payment failed: " . $conn->error;
                    }
                }
            }
        }
    }
}

$sql = "SELECT p.*, o.order_id 
        FROM payments p
        JOIN orders o ON p.client_id = o.client_id
        WHERE p.client_id = '$client_id'
        ORDER BY p.payment_date DESC";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $payments[] = $row;
    }
}

$sql = "SELECT * FROM orders 
        WHERE client_id = '$client_id' 
        AND status NOT IN ('Paid', 'Cancelled')
        ORDER BY order_date DESC";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $pending_orders[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment System - Catering Service</title>
    <link rel="stylesheet" href="payment.css">
    <script src="payment.js"></script>
</head>
<body>
    <div class="container">
        <header>
            <h1>Payment System</h1>
            <p>Make payments and view your history</p>
        </header>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
                <?php if ($receipt_data): ?>
                    <br>
                    <a href="#" class="download-receipt" data-receipt='<?php echo json_encode($receipt_data); ?>'>Download Receipt</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <h2>Make a Payment</h2>
            
            <?php if (!empty($pending_orders)): ?>
                <form action="" method="post" id="paymentForm">
                    <div class="form-group">
                        <label for="order_id">Select Order</label>
                        <select name="order_id" id="order_id" required>
                            <option value="">-- Select an order --</option>
                            <?php foreach ($pending_orders as $order): ?>
                                <option value="<?php echo $order['order_id']; ?>" 
                                        data-amount="<?php echo $order['total_price']; ?>">
                                    Order #<?php echo $order['order_id']; ?> - 
                                    Ksh <?php echo number_format($order['total_price'], 2); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="amount">Amount (Ksh)</label>
                        <input type="number" name="amount" id="amount" step="0.01" required readonly>
                    </div>
                    
                    <h3>Select Payment Method</h3>
                    <div class="payment-methods">
                        <div class="method" data-method="M-Pesa">
                            <h4>M-Pesa</h4>
                            <p>Pay via M-Pesa</p>
                        </div>
                        <div class="method" data-method="Cash">
                            <h4>Cash</h4>
                            <p>Pay in person</p>
                        </div>
                    </div>
                    
                    <input type="hidden" name="payment_method" id="payment_method" required>
                    
                    <div id="mpesaDetails" class="hidden">
                        <div class="form-group">
                            <label for="phone_number">Phone Number (e.g., 07XX XXX XXX)</label>
                            <input type="text" name="phone_number" id="phone_number" placeholder="Enter your phone number">
                        </div>
                        <div class="form-group">
                            <label>M-Pesa Payment Instructions</label>
                            <ol>
                                <li>Go to M-Pesa menu on your phone</li>
                                <li>Select "Lipa na M-Pesa"</li>
                                <li>Select "Pay Bill"</li>
                                <li>Enter Business No: <strong>123456</strong></li>
                                <li>Enter Account No: <strong id="mpesaOrderId"></strong></li>
                                <li>Enter Amount: <strong id="mpesaAmount"></strong></li>
                                <li>Enter your M-Pesa PIN and confirm</li>
                            </ol>
                        </div>
                    </div>
                    
                    <button type="submit" name="make_payment" class="btn btn-block">
                        Complete Payment
                    </button>
                </form>
            <?php else: ?>
                <p>You have no pending payments.</p>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2>Payment History</h2>
            
            <?php if (!empty($payments)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Payment ID</th>
                            <th>Order ID</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Transaction ID</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td>#<?php echo $payment['payment_id']; ?></td>
                                <td>#<?php echo $payment['order_id']; ?></td>
                                <td>Ksh <?php echo number_format($payment['amount'], 2); ?></td>
                                <td><?php echo $payment['payment_method']; ?></td>
                                <td><?php echo $payment['transaction_id'] ?: 'N/A'; ?></td>
                                <td><?php echo date('M j, Y g:i a', strtotime($payment['payment_date'])); ?></td>
                                <td>
                                    <span class="status status-<?php echo strtolower($payment['status']); ?>">
                                        <?php echo $payment['status']; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No payment history found.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>