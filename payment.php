<?php
session_start();
require_once 'config.php';

if (!isset($_GET['tier']) || !in_array($_GET['tier'], ['bronze', 'silver', 'gold'])) {
    header("Location: register.php");
    exit();
}

$tier = htmlspecialchars($_GET['tier']);
$amount = $tier == 'bronze' ? 500 : ($tier == 'silver' ? 750 : 1000);
$payment_ref = htmlspecialchars($_GET['ref'] ?? '4178866'); // Default to 4178866
$till_number = '4178866';

// Mock payment processing (store transaction code for admin verification)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $phone = htmlspecialchars(trim($_POST['phone']));
    $transaction_code = htmlspecialchars(trim($_POST['transaction_code']));
    
    if (!preg_match('/^\+254\d{9}$/', $phone)) {
        $error = "Invalid phone number format.";
    } elseif (empty($transaction_code) || !preg_match('/^[A-Z0-9]{10}$/', $transaction_code)) {
        $error = "Invalid M-PESA transaction code. It should be a 10-character alphanumeric code.";
    } else {
        // Fetch user ID based on phone
        $stmt = $conn->prepare("SELECT id, referrer_code FROM users WHERE phone = ?");
        if (!$stmt) {
            $error = "Database error: Unable to prepare user query.";
        } else {
            $stmt->bind_param("s", $phone);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows === 0) {
                $error = "No user found with this phone number.";
            } else {
                $user = $result->fetch_assoc();
                $user_id = $user['id'];
                $referrer_code = $user['referrer_code'];
                // Store payment details in payments table
                $stmt = $conn->prepare("INSERT INTO payments (user_id, tier, amount, transaction_code, payment_ref, status, created_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
                if (!$stmt) {
                    $error = "Database error: Payments table may not exist. Contact support.";
                } else {
                    $stmt->bind_param("issss", $user_id, $tier, $amount, $transaction_code, $payment_ref);
                    if ($stmt->execute()) {
                        $success = "Payment submitted successfully. Awaiting admin verification.";
                    } else {
                        $error = "Payment submission failed. Please try again.";
                    }
                }
                $stmt->close();
            }
        }
    }
}
?>
<?php include 'header.php'; ?>
<section class="px-4 py-16 bg-gray-50">
  <div class="max-w-4xl mx-auto text-center">
    <h2 class="text-3xl md:text-5xl font-bold text-gray-900 mb-6">Complete Your Payment</h2>
    <p class="text-xl text-gray-600 mb-8">
      Pay KSh <?php echo $amount; ?> for your <?php echo ucfirst($tier); ?> tier to activate your CampusEarn membership.
    </p>
    <div class="bg-white rounded-2xl p-8 max-w-md mx-auto shadow-lg">
      <h3 class="text-2xl font-bold text-gray-900 mb-6">M-PESA Payment</h3>
      <?php if (isset($error)) { echo "<p class='text-red-500 mb-4'>$error</p>"; } ?>
      <?php if (isset($success)) { echo "<p class='text-green-500 mb-4'>$success</p>"; } ?>
      <div class="text-left text-gray-600 mb-6">
        <p class="mb-2">1. Go to M-PESA on your phone.</p>
        <p class="mb-2">2. Select <strong>Lipa na M-PESA</strong></p>
        <p class="mb-2">3. Enter Till Number: <strong><?php echo $till_number; ?></strong></p>
        <p class="mb-2">5. Enter Amount: <strong>KSh <?php echo $amount; ?></strong></p>
        <p>6. Submit the transaction code received below.</p>
      </div>
      <form method="POST" class="space-y-4">
        <input type="tel" name="phone" placeholder="Phone Number (+254XXXXXXXXX)" class="w-full px-4 py-3 rounded-button border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary text-gray-900" required>
        <input type="text" name="transaction_code" placeholder="M-PESA Transaction Code (e.g., TE81U7PBN)" class="w-full px-4 py-3 rounded-button border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary text-gray-900" required>
        <p class="text-gray-600">Amount: KSh <?php echo $amount; ?></p>
        <button type="submit" class="w-full bg-primary text-white px-6 py-3 rounded-button font-semibold hover:bg-emerald-600 transition-colors">Submit Payment</button>
      </form>
    </div>
  </div>
</section>
<?php include 'footer.php'; ?>