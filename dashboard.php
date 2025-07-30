<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch user data
$stmt = $conn->prepare("SELECT full_name, username, tier, payment_status, referrer_code FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Check if user has paid membership fee
if ($user['payment_status'] !== 'completed') {
    header("Location: payment.php?tier=" . urlencode($user['tier']));
    exit();
}

// Fetch earnings
$earnings_stmt = $conn->prepare("SELECT SUM(amount) as total_earnings FROM earnings WHERE user_id = ?");
$earnings_stmt->bind_param("i", $_SESSION['user_id']);
$earnings_stmt->execute();
$earnings = $earnings_stmt->get_result()->fetch_assoc()['total_earnings'] ?? 0;
$earnings_stmt->close();

// Fetch submitted articles
$articles_stmt = $conn->prepare("SELECT title, status, created_at FROM articles WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$articles_stmt->bind_param("i", $_SESSION['user_id']);
$articles_stmt->execute();
$articles = $articles_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$articles_stmt->close();

// Fetch withdrawals
$withdrawals_stmt = $conn->prepare("SELECT amount, status, created_at FROM withdrawals WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$withdrawals_stmt->bind_param("i", $_SESSION['user_id']);
$withdrawals_stmt->execute();
$withdrawals = $withdrawals_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$withdrawals_stmt->close();

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
    if ($amount > 0 && $amount <= $earnings) {
        $stmt = $conn->prepare("INSERT INTO withdrawals (user_id, amount, status, created_at) VALUES (?, ?, 'pending', NOW())");
        $stmt->bind_param("id", $_SESSION['user_id'], $amount);
        $stmt->execute();
        $stmt->close();
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid withdrawal amount.";
    }
}
?>
<?php include 'header.php'; ?>
<section class="px-4 py-12 bg-gradient-to-br from-primary/10 to-emerald-50">
  <div class="max-w-7xl mx-auto">
    <div class="text-center mb-12">
      <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">Welcome, <?php echo htmlspecialchars($user['full_name']); ?>!</h1>
      <p class="text-xl text-gray-600">Your CampusEarn Dashboard - Track earnings, manage articles, and grow your income.</p>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
      <!-- Earnings Overview -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-2xl font-bold text-gray-900 mb-4">Your Earnings</h2>
        <p class="text-4xl font-bold text-primary mb-4">KSh <?php echo number_format($earnings, 2); ?></p>
        <p class="text-gray-600 mb-6">Total earnings from referrals and articles.</p>
        <button class="bg-primary text-white px-6 py-3 rounded-button font-semibold hover:bg-emerald-600 transition-colors">View Details</button>
      </div>
      <!-- Current Tier -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-2xl font-bold text-gray-900 mb-4">Current Tier: <?php echo ucfirst($user['tier']); ?></h2>
        <p class="text-gray-600 mb-6">
          <?php echo $user['tier'] == 'bronze' ? '20% referral commission' : ($user['tier'] == 'silver' ? '30% commission, KSh 300/article' : '45% commission, KSh 500/article'); ?>
        </p>
        <form method="POST" class="space-y-4">
          <select name="new_tier" class="w-full px-4 py-3 rounded-button border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary text-gray-900">
            <option value="bronze" <?php echo $user['tier'] == 'bronze' ? 'disabled' : ''; ?>>Bronze (KSh 500)</option>
            <option value="silver" <?php echo $user['tier'] == 'silver' ? 'disabled' : ''; ?>>Silver (KSh 750)</option>
            <option value="gold" <?php echo $user['tier'] == 'gold' ? 'disabled' : ''; ?>>Gold (KSh 1,000)</option>
          </select>
          <button type="submit" name="upgrade_tier" class="w-full bg-secondary text-white px-6 py-3 rounded-button font-semibold hover:bg-yellow-600 transition-colors">Upgrade Tier</button>
        </form>
      </div>
      <!-- Referral Code -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-2xl font-bold text-gray-900 mb-4">Your Referral Code</h2>
        <p class="text-xl font-semibold text-primary mb-4"><?php echo htmlspecialchars($user['referrer_code']); ?></p>
        <p class="text-gray-600 mb-6">Share this code with friends to earn commissions on their membership fees.</p>
        <button onclick="copyToClipboard('<?php echo htmlspecialchars($user['referrer_code']); ?>')" class="w-full bg-primary text-white px-6 py-3 rounded-button font-semibold hover:bg-emerald-600 transition-colors">Copy Code</button>
      </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
      <!-- Withdraw Earnings -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-2xl font-bold text-gray-900 mb-4">Withdraw Earnings</h2>
        <?php if (isset($error)) { echo "<p class='text-red-500 mb-4'>$error</p>"; } ?>
        <form method="POST" class="space-y-4">
          <input type="number" name="amount" placeholder="Amount (KSh)" min="1" max="<?php echo $earnings; ?>" class="w-full px-4 py-3 rounded-button border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary text-gray-900" required>
          <button type="submit" name="withdraw" class="w-full bg-primary text-white px-6 py-3 rounded-button font-semibold hover:bg-emerald-600 transition-colors">Request Withdrawal</button>
        </form>
      </div>
      <!-- Submitted Articles -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-2xl font-bold text-gray-900 mb-4">Your Submitted Articles</h2>
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
              <td class="p-4 text-gray-600"><?php echo htmlspecialchars($article['title']); ?></td>
              <td class="p-4 text-gray-600"><?php echo ucfirst($article['status']); ?></td>
              <td class="p-4 text-gray-600"><?php echo date('M d, Y', strtotime($article['created_at'])); ?></td>
            </tr>
            <?php } ?>
          </tbody>
        </table>
        <a href="write_article.php" class="mt-4 inline-block bg-primary text-white px-6 py-3 rounded-button font-semibold hover:bg-emerald-600 transition-colors">Write New Article</a>
      </div>
    </div>
    <!-- Withdrawal History -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mt-8">
      <h2 class="text-2xl font-bold text-gray-900 mb-4">Withdrawal History</h2>
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
            <td class="p-4 text-gray-600">KSh <?php echo number_format($withdrawal['amount'], 2); ?></td>
            <td class="p-4 text-gray-600"><?php echo ucfirst($withdrawal['status']); ?></td>
            <td class="p-4 text-gray-600"><?php echo date('M d, Y', strtotime($withdrawal['created_at'])); ?></td>
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
    alert('Referral code copied to clipboard!');
  }).catch(err => {
    console.error('Failed to copy: ', err);
  });
}
</script>
<?php include 'footer.php'; ?>