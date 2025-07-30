<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    error_log("Session error: user_id not set in dashboard.php");
    header("Location: login.php?error=Session expired. Please log in again.");
    exit();
}

// Fetch user data with enhanced error handling
$stmt = $conn->prepare("SELECT full_name, username, tier, payment_status, referrer_code_own, balance FROM users WHERE id = ?");
if (!$stmt) {
    error_log("Database error: Unable to prepare user query: " . $conn->error);
    header("Location: login.php?error=Database error occurred");
    exit();
}
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user || !isset($user['full_name'], $user['username'], $user['tier'], $user['payment_status'], $user['referrer_code_own'], $user['balance'])) {
    error_log("User query failed or incomplete data for ID: " . $_SESSION['user_id'] . ". Fetched: " . json_encode($user));
    header("Location: login.php?error=User not found or incomplete data");
    exit();
}

// Check if user has paid membership fee
if ($user['payment_status'] !== 'completed') {
    header("Location: payment.php?tier=" . urlencode($user['tier']));
    exit();
}

// Fetch earnings and referral stats
$earnings_stmt = $conn->prepare("SELECT SUM(amount) as total_earnings, SUM(CASE WHEN type = 'referral' THEN amount ELSE 0 END) as referral_earnings FROM earnings WHERE user_id = ?");
if (!$earnings_stmt) {
    error_log("Database error: Unable to prepare earnings query: " . $conn->error);
    $error = "Database error: Unable to fetch earnings.";
    $total_earnings = 0;
    $referral_earnings = 0;
} else {
    $earnings_stmt->bind_param("i", $_SESSION['user_id']);
    $earnings_stmt->execute();
    $earnings_data = $earnings_stmt->get_result()->fetch_assoc();
    $total_earnings = $earnings_data['total_earnings'] ?? 0;
    $referral_earnings = $earnings_data['referral_earnings'] ?? 0;
    $earnings_stmt->close();
}

// Fetch referral count and referred users with earnings
$referral_stmt = $conn->prepare("
    SELECT u2.id, u2.full_name, u2.tier, e.amount as commission
    FROM users u1
    JOIN users u2 ON u2.referrer_code = u1.referrer_code_own
    LEFT JOIN earnings e ON e.referred_user_id = u2.id AND e.type = 'referral' AND e.user_id = u1.id
    WHERE u1.id = ?
");
if (!$referral_stmt) {
    error_log("Database error: Unable to prepare referral query: " . $conn->error);
    $error = $error ?? "Database error: Unable to fetch referral data.";
    $referral_count = 0;
    $referred_users = [];
} else {
    $referral_stmt->bind_param("i", $_SESSION['user_id']);
    $referral_stmt->execute();
    $referred_users = $referral_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $referral_count = count($referred_users);
    $referral_stmt->close();
}

// Fetch submitted articles
$articles_stmt = $conn->prepare("SELECT title, status, created_at FROM articles WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
if (!$articles_stmt) {
    error_log("Database error: Unable to prepare articles query: " . $conn->error);
    $error = $error ?? "Database error: Unable to fetch articles.";
    $articles = [];
} else {
    $articles_stmt->bind_param("i", $_SESSION['user_id']);
    $articles_stmt->execute();
    $articles = $articles_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $articles_stmt->close();
}

// Fetch withdrawals and pending withdrawal amount
$withdrawals_stmt = $conn->prepare("SELECT amount, status, created_at FROM withdrawals WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
if (!$withdrawals_stmt) {
    error_log("Database error: Unable to prepare withdrawals query: " . $conn->error);
    $error = $error ?? "Database error: Unable to fetch withdrawals.";
    $withdrawals = [];
} else {
    $withdrawals_stmt->bind_param("i", $_SESSION['user_id']);
    $withdrawals_stmt->execute();
    $withdrawals = $withdrawals_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $withdrawals_stmt->close();
}

// Calculate pending withdrawals
$pending_withdrawals_stmt = $conn->prepare("SELECT SUM(amount) as pending_withdrawals FROM withdrawals WHERE user_id = ? AND status = 'pending'");
if (!$pending_withdrawals_stmt) {
    error_log("Database error: Unable to prepare pending withdrawals query: " . $conn->error);
    $error = $error ?? "Database error: Unable to fetch pending withdrawals.";
    $pending_withdrawals = 0;
} else {
    $pending_withdrawals_stmt->bind_param("i", $_SESSION['user_id']);
    $pending_withdrawals_stmt->execute();
    $pending_withdrawals = $pending_withdrawals_stmt->get_result()->fetch_assoc()['pending_withdrawals'] ?? 0;
    $pending_withdrawals_stmt->close();
}

// Handle tier upgrade
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upgrade_tier'])) {
    $new_tier = htmlspecialchars(trim($_POST['new_tier']));
    if (in_array($new_tier, ['bronze', 'silver', 'gold']) && $new_tier !== $user['tier']) {
        $amount = $new_tier == 'bronze' ? 500 : ($new_tier == 'silver' ? 750 : 1000);
        header("Location: payment.php?tier=$new_tier");
        exit();
    }
}

// Handle withdrawal request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['withdraw'])) {
    $amount = floatval($_POST['amount']);
    if ($amount > 0 && $amount <= $user['balance']) {
        $stmt = $conn->prepare("INSERT INTO withdrawals (user_id, amount, status, created_at) VALUES (?, ?, 'pending', NOW())");
        if (!$stmt) {
            $error = $error ?? "Database error: Unable to prepare withdrawal insertion.";
        } else {
            $stmt->bind_param("id", $_SESSION['user_id'], $amount);
            if ($stmt->execute()) {
                // Update user balance
                $stmt = $conn->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
                if (!$stmt) {
                    $error = $error ?? "Database error: Unable to prepare balance update.";
                } else {
                    $stmt->bind_param("di", $amount, $_SESSION['user_id']);
                    $stmt->execute();
                    $stmt->close();
                    header("Location: dashboard.php");
                    exit();
                }
            } else {
                $error = $error ?? "Withdrawal request failed. Please try again.";
            }
            $stmt->close();
        }
    } else {
        $error = "Invalid withdrawal amount or insufficient balance.";
    }
}

// Construct referral link
$referral_link = "http://kampuspesa/register.php?ref=" . urlencode($user['referrer_code_own']);
?>
<?php include 'header.php'; ?>
<section class="px-4 py-12 bg-gradient-to-br from-primary/10 to-emerald-50">
  <div class="max-w-7xl mx-auto">
    <div class="text-center mb-12">
      <?php if (isset($error)) { echo "<p class='text-red-500 mb-4'>$error</p>"; } ?>
      
      <p class="text-xl text-gray-600">Track your earnings, manage articles, and grow your income with CampusEarn.</p>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
      <!-- Earnings Overview -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
          <i class="ri-wallet-3-line text-primary mr-2"></i> Your Balance
        </h2>
        <p class="text-4xl font-bold text-primary mb-4">KSh <?php echo number_format($user['balance'] ?? 0, 2); ?></p>
        <p class="text-lg text-gray-600 mb-2">Total Earnings: <span class="font-semibold">KSh <?php echo number_format($total_earnings, 2); ?></span></p>
        <p class="text-lg text-gray-600 mb-6">Pending Withdrawals: <span class="font-semibold">KSh <?php echo number_format($pending_withdrawals, 2); ?></span></p>
        <button class="bg-primary text-white px-6 py-3 rounded-button font-semibold hover:bg-emerald-600 transition-colors">View Details</button>
      </div>
      <!-- Current Tier -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
          <i class="ri-medal-line text-primary mr-2"></i> Current Tier: <?php echo ucfirst($user['tier'] ?? 'Unknown'); ?>
        </h2>
        <p class="text-gray-600 mb-4">
          <?php echo ($user['tier'] ?? '') == 'bronze' ? '20% referral commission' : (($user['tier'] ?? '') == 'silver' ? '30% commission, KSh 300/article' : '45% commission, KSh 500/article'); ?>
        </p>
        <p class="text-sm text-green-600 mb-6 flex items-center">
          <i class="ri-checkbox-circle-line mr-1"></i> Membership Active
        </p>
        <form method="POST" class="space-y-4">
          <select name="new_tier" class="w-full px-4 py-3 rounded-button border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary text-gray-900">
            <option value="bronze" <?php echo ($user['tier'] ?? '') == 'bronze' ? 'disabled' : ''; ?>>Bronze (KSh 500)</option>
            <option value="silver" <?php echo ($user['tier'] ?? '') == 'silver' ? 'disabled' : ''; ?>>Silver (KSh 750)</option>
            <option value="gold" <?php echo ($user['tier'] ?? '') == 'gold' ? 'disabled' : ''; ?>>Gold (KSh 1,000)</option>
          </select>
          <button type="submit" name="upgrade_tier" class="w-full bg-secondary text-white px-6 py-3 rounded-button font-semibold hover:bg-yellow-600 transition-colors">Upgrade Tier</button>
        </form>
      </div>
      <!-- Referral Stats -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
          <i class="ri-user-add-line text-primary mr-2"></i> Referral Stats
        </h2>
        <p class="text-lg text-gray-600 mb-2">Referrals: <span class="font-semibold text-primary"><?php echo $referral_count; ?></span></p>
        <p class="text-lg text-gray-600 mb-6">Referral Earnings: <span class="font-semibold text-primary">KSh <?php echo number_format($referral_earnings, 2); ?></span></p>
        <a href="#referral-link" class="inline-block bg-primary text-white px-6 py-3 rounded-button font-semibold hover:bg-emerald-600 transition-colors">View Referral Link</a>
      </div>
    </div>
    <!-- Referral Link Section -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-8" id="referral-link">
      <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
        <i class="ri-link text-primary mr-2"></i> Your Referral Link
      </h2>
      <p class="text-xl font-semibold text-primary mb-4"><?php echo htmlspecialchars($referral_link); ?></p>
      <p class="text-gray-600 mb-4">Share this link to invite friends and earn commissions.</p>
      <p class="text-lg text-gray-600 mb-4">Referral Code: <span class="font-semibold text-primary"><?php echo htmlspecialchars($user['referrer_code_own'] ?? ''); ?></span></p>
      <button onclick="copyToClipboard('<?php echo htmlspecialchars($referral_link); ?>')" class="w-full bg-primary text-white px-6 py-3 rounded-button font-semibold hover:bg-emerald-600 transition-colors">Copy Referral Link</button>
    </div>
    <!-- Referral History -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-8">
      <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
        <i class="ri-history-line text-primary mr-2"></i> Referral History
      </h2>
      <?php if (empty($referred_users)) { ?>
        <p class="text-gray-600">No referrals yet. Share your referral link to start earning!</p>
      <?php } else { ?>
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-gray-100">
              <th class="p-4 text-gray-900 font-semibold">Referred User</th>
              <th class="p-4 text-gray-900 font-semibold">Tier</th>
              <th class="p-4 text-gray-900 font-semibold">Commission Earned</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($referred_users as $referred_user) { ?>
              <tr class="border-b border-gray-200">
                <td class="p-4 text-gray-600"><?php echo htmlspecialchars($referred_user['full_name'] ?? 'Unknown'); ?></td>
                <td class="p-4 text-gray-600"><?php echo ucfirst($referred_user['tier'] ?? 'Unknown'); ?></td>
                <td class="p-4 text-gray-600">KSh <?php echo number_format($referred_user['commission'] ?? 0, 2); ?></td>
              </tr>
            <?php } ?>
          </tbody>
        </table>
      <?php } ?>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
      <!-- Withdraw Earnings -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
          <i class="ri-bank-card-line text-primary mr-2"></i> Withdraw Earnings
        </h2>
        <?php if (isset($error)) { echo "<p class='text-red-500 mb-4'>$error</p>"; } ?>
        <form method="POST" class="space-y-4">
          <input type="number" name="amount" placeholder="Amount (KSh)" min="1" max="<?php echo $user['balance'] ?? 0; ?>" class="w-full px-4 py-3 rounded-button border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary text-gray-900" required>
          <button type="submit" name="withdraw" class="w-full bg-primary text-white px-6 py-3 rounded-button font-semibold hover:bg-emerald-600 transition-colors">Request Withdrawal</button>
        </form>
      </div>
      <!-- Submitted Articles -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
          <i class="ri-article-line text-primary mr-2"></i> Your Submitted Articles
        </h2>
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-gray-100">
              <th class="p-4 text-gray-900 font-semibold">Title</th>
              <th class="p-4 text-gray-900 font-semibold">Status</th>
              <th class="p-4 text-gray-900 font-semibold">Date</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($articles as $article) { ?>
            <tr class="border-b border-gray-200">
              <td class="p-4 text-gray-600"><?php echo htmlspecialchars($article['title'] ?? ''); ?></td>
              <td class="p-4 text-gray-600"><?php echo ucfirst($article['status'] ?? 'Unknown'); ?></td>
              <td class="p-4 text-gray-600"><?php echo isset($article['created_at']) ? date('M d, Y', strtotime($article['created_at'])) : 'N/A'; ?></td>
            </tr>
            <?php } ?>
          </tbody>
        </table>
        <a href="write_article.php" class="mt-6 inline-block w-full bg-primary text-white px-6 py-3 rounded-button font-semibold hover:bg-emerald-600 transition-colors text-center">Write New Article</a>
      </div>
    </div>
    <!-- Withdrawal History -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mt-8">
      <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
        <i class="ri-history-line text-primary mr-2"></i> Withdrawal History
      </h2>
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="bg-gray-100">
            <th class="p-4 text-gray-900 font-semibold">Amount</th>
            <th class="p-4 text-gray-900 font-semibold">Status</th>
            <th class="p-4 text-gray-900 font-semibold">Date</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($withdrawals as $withdrawal) { ?>
          <tr class="border-b border-gray-200">
            <td class="p-4 text-gray-600">KSh <?php echo number_format($withdrawal['amount'] ?? 0, 2); ?></td>
            <td class="p-4 text-gray-600"><?php echo ucfirst($withdrawal['status'] ?? 'Unknown'); ?></td>
            <td class="p-4 text-gray-600"><?php echo isset($withdrawal['created_at']) ? date('M d, Y', strtotime($withdrawal['created_at'])) : 'N/A'; ?></td>
          </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>
  </div>
</section>
<script>
function copyToClipboard(text) {
  navigator.clipboard.writeText(text).then(() => {
    alert('Referral link copied to clipboard!');
  }).catch(err => {
    console.error('Failed to copy: ', err);
  });
}
</script>
<?php include 'footer.php'; ?>