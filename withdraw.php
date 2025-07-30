<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    error_log("Session error: user_id not set in withdraw.php");
    header("Location: login.php?error=Session expired. Please log in again.");
    exit();
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch user data
$stmt = $conn->prepare("SELECT full_name, tier, payment_status, balance FROM users WHERE id = ?");
if (!$stmt) {
    error_log("Database error: Unable to prepare user query: " . $conn->error);
    header("Location: dashboard.php?error=Database error occurred");
    exit();
}
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user || !isset($user['full_name'], $user['tier'], $user['payment_status'], $user['balance'])) {
    error_log("User query failed or incomplete data for ID: " . $_SESSION['user_id'] . ". Fetched: " . json_encode($user));
    header("Location: dashboard.php?error=User not found or incomplete data");
    exit();
}

// Check if user has paid membership fee
if ($user['payment_status'] !== 'completed') {
    header("Location: payment.php?tier=" . urlencode($user['tier']));
    exit();
}

// Handle withdrawal request
$minimum_withdrawal = 100.00; // Minimum withdrawal amount (KSh)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['withdraw']) && isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    $amount = floatval($_POST['amount']);
    if ($amount < $minimum_withdrawal) {
        $error = "Withdrawal amount must be at least KSh $minimum_withdrawal.";
    } elseif ($amount > $user['balance']) {
        $error = "Insufficient balance for withdrawal.";
    } else {
        $conn->begin_transaction();
        try {
            // Insert withdrawal
            $stmt = $conn->prepare("INSERT INTO withdrawals (user_id, amount, status, created_at) VALUES (?, ?, 'pending', NOW())");
            if (!$stmt) {
                throw new Exception("Unable to prepare withdrawal insertion: " . $conn->error);
            }
            $stmt->bind_param("id", $_SESSION['user_id'], $amount);
            $stmt->execute();
            $stmt->close();

            // Update user balance
            $stmt = $conn->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
            if (!$stmt) {
                throw new Exception("Unable to prepare balance update: " . $conn->error);
            }
            $stmt->bind_param("di", $amount, $_SESSION['user_id']);
            $stmt->execute();
            $stmt->close();

            // Insert transaction
            $stmt = $conn->prepare("INSERT INTO transactions (user_id, amount, type, status, created_at) VALUES (?, ?, 'withdrawal', 'pending', NOW())");
            if (!$stmt) {
                throw new Exception("Unable to prepare transaction insertion: " . $conn->error);
            }
            $stmt->bind_param("id", $_SESSION['user_id'], $amount);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            $success = "Withdrawal request submitted successfully.";
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Withdrawal request failed. Please try again.";
            error_log("Database error: " . $e->getMessage());
        }
    }
}
?>
<?php include 'header.php'; ?>
<section class="px-4 py-12 bg-gradient-to-br from-primary/10 to-emerald-50">
  <div class="max-w-md mx-auto">
    <h1 class="text-3xl font-bold text-gray-900 mb-6 text-center">Request Withdrawal</h1>
    <p class="text-gray-600 mb-4 text-center">Withdraw your earnings from CampusEarn.</p>
    <?php if (isset($error)) { ?>
      <p class="text-red-500 bg-red-100 p-4 rounded-lg mb-4"><?php echo htmlspecialchars($error); ?></p>
    <?php } ?>
    <?php if (isset($success)) { ?>
      <p class="text-green-500 bg-green-100 p-4 rounded-lg mb-4"><?php echo htmlspecialchars($success); ?></p>
    <?php } ?>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
      <p class="text-lg text-gray-600 mb-4">Available Balance: <span class="font-semibold text-primary">KSh <?php echo number_format($user['balance'], 2); ?></span></p>
      <p class="text-sm text-gray-600 mb-4">Minimum withdrawal amount: KSh <?php echo number_format($minimum_withdrawal, 2); ?></p>
      <form method="POST" class="space-y-4">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
        <div>
          <label for="amount" class="block text-gray-700 font-semibold mb-2">Amount (KSh)</label>
          <input type="number" name="amount" id="amount" placeholder="Enter amount" min="<?php echo $minimum_withdrawal; ?>" max="<?php echo $user['balance']; ?>" step="0.01" class="w-full px-4 py-3 rounded-button border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary" required aria-label="Withdrawal amount in KSh">
        </div>
        <button type="submit" name="withdraw" class="w-full bg-primary text-white px-6 py-3 rounded-button font-semibold hover:bg-emerald-600 transition-colors" aria-label="Submit withdrawal request">Request Withdrawal</button>
      </form>
      <p class="text-center text-gray-600 mt-4"><a href="dashboard.php" class="text-primary hover:underline" aria-label="Return to dashboard">Back to Dashboard</a></p>
    </div>
  </div>
</section>
<?php include 'footer.php'; ?>