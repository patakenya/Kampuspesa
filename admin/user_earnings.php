<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Handle filters
$search = isset($_GET['search']) ? htmlspecialchars(trim($_GET['search'])) : '';
$tier = isset($_GET['tier']) ? htmlspecialchars(trim($_GET['tier'])) : '';
$user_id_filter = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

// Build query
$query = "SELECT u.id, u.full_name, u.email, u.tier, u.balance, 
                 COALESCE(SUM(t.amount), 0) as total_earnings 
          FROM users u 
          LEFT JOIN transactions t ON u.id = t.user_id AND t.type = 'earning' AND t.status = 'completed'
          WHERE 1=1";
$params = [];
$types = '';

if ($user_id_filter > 0) {
    $query .= " AND u.id = ?";
    $params[] = $user_id_filter;
    $types .= 'i';
} elseif ($search) {
    $query .= " AND (u.full_name LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'ss';
}
if ($tier && in_array($tier, ['bronze', 'silver', 'gold'])) {
    $query .= " AND u.tier = ?";
    $params[] = $tier;
    $types .= 's';
}
$query .= " GROUP BY u.id ORDER BY u.full_name";

// Execute query
$stmt = $conn->prepare($query);
if (!$stmt) {
    error_log("Database error: Unable to prepare users query: " . $conn->error);
    $users = [];
} else {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Fetch user earnings history if user_id is provided
$earnings_history = [];
if ($user_id_filter > 0) {
    $stmt = $conn->prepare("SELECT amount, type, status, created_at FROM transactions WHERE user_id = ? AND type = 'earning' ORDER BY created_at DESC");
    $stmt->bind_param("i", $user_id_filter);
    $stmt->execute();
    $earnings_history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>
<?php include 'header.php'; ?>
<section class="px-4 py-12 bg-gray-50">
  <div class="max-w-7xl mx-auto">
    <h1 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-6 text-center">User Earnings & Balances</h1>
    <div class="mb-6 flex flex-col sm:flex-row gap-4">
      <form method="GET" class="flex-1">
        <label for="search" class="block text-gray-700 font-semibold mb-2">Search by Name or Email</label>
        <input type="text" name="search" id="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Enter name or email" class="w-full px-4 py-3 rounded-button border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary" aria-label="Search users by name or email">
      </form>
      <form method="GET" class="flex-1">
        <label for="tier" class="block text-gray-700 font-semibold mb-2">Filter by Tier</label>
        <select name="tier" id="tier" class="w-full px-4 py-3 rounded-button border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary" aria-label="Filter users by tier">
          <option value="">All Tiers</option>
          <option value="bronze" <?php echo $tier == 'bronze' ? 'selected' : ''; ?>>Bronze</option>
          <option value="silver" <?php echo $tier == 'silver' ? 'selected' : ''; ?>>Silver</option>
          <option value="gold" <?php echo $tier == 'gold' ? 'selected' : ''; ?>>Gold</option>
        </select>
        <button type="submit" class="mt-4 w-full bg-primary text-white px-6 py-3 rounded-button font-semibold hover:bg-emerald-600 transition-colors" aria-label="Apply tier filter">Filter</button>
      </form>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 overflow-x-auto">
      <table class="w-full text-left">
        <thead>
          <tr class="border-b border-gray-200">
            <th class="py-3 px-4 text-gray-700 font-semibold">Full Name</th>
            <th class="py-3 px-4 text-gray-700 font-semibold">Email</th>
            <th class="py-3 px-4 text-gray-700 font-semibold">Tier</th>
            <th class="py-3 px-4 text-gray-700 font-semibold">Balance (KSh)</th>
            <th class="py-3 px-4 text-gray-700 font-semibold">Total Earnings (KSh)</th>
            <th class="py-3 px-4 text-gray-700 font-semibold">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($users)) { ?>
            <tr>
              <td colspan="6" class="py-4 px-4 text-gray-600 text-center">No users found.</td>
            </tr>
          <?php } else { ?>
            <?php foreach ($users as $user) { ?>
              <tr class="border-b border-gray-200">
                <td class="py-3 px-4"><?php echo htmlspecialchars($user['full_name']); ?></td>
                <td class="py-3 px-4"><?php echo htmlspecialchars($user['email']); ?></td>
                <td class="py-3 px-4"><?php echo ucfirst($user['tier']); ?></td>
                <td class="py-3 px-4"><?php echo number_format($user['balance'], 2); ?></td>
                <td class="py-3 px-4"><?php echo number_format($user['total_earnings'], 2); ?></td>
                <td class="py-3 px-4">
                  <a href="user_earnings.php?user_id=<?php echo $user['id']; ?>" class="text-primary hover:text-emerald-600" aria-label="View earnings history for <?php echo htmlspecialchars($user['full_name']); ?>">
                    <i class="ri-eye-line"></i>
                  </a>
                </td>
              </tr>
            <?php } ?>
          <?php } ?>
        </tbody>
      </table>
    </div>
    <?php if ($user_id_filter > 0 && !empty($earnings_history)) { ?>
      <div class="mt-8 bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Earnings History for <?php echo htmlspecialchars($users[0]['full_name']); ?></h2>
        <table class="w-full text-left">
          <thead>
            <tr class="border-b border-gray-200">
              <th class="py-3 px-4 text-gray-700 font-semibold">Amount (KSh)</th>
              <th class="py-3 px-4 text-gray-700 font-semibold">Type</th>
              <th class="py-3 px-4 text-gray-700 font-semibold">Status</th>
              <th class="py-3 px-4 text-gray-700 font-semibold">Date</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($earnings_history as $transaction) { ?>
              <tr class="border-b border-gray-200">
                <td class="py-3 px-4"><?php echo number_format($transaction['amount'], 2); ?></td>
                <td class="py-3 px-4"><?php echo ucfirst($transaction['type']); ?></td>
                <td class="py-3 px-4"><?php echo ucfirst($transaction['status']); ?></td>
                <td class="py-3 px-4"><?php echo date('M d, Y H:i', strtotime($transaction['created_at'])); ?></td>
              </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    <?php } elseif ($user_id_filter > 0) { ?>
      <div class="mt-8 bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <p class="text-gray-600 text-center">No earnings history for <?php echo htmlspecialchars($users[0]['full_name']); ?>.</p>
      </div>
    <?php } ?>
  </div>
</section>
<?php include '../footer.php'; ?>