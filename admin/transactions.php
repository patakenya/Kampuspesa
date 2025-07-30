<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $transaction_id = intval($_POST['transaction_id']);
    $status = htmlspecialchars(trim($_POST['status']));
    if (in_array($status, ['pending', 'completed', 'failed'])) {
        $stmt = $conn->prepare("UPDATE transactions SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $transaction_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: transactions.php");
    exit();
}

// Fetch transactions
$transactions = $conn->query("SELECT t.id, t.user_id, t.amount, t.type, t.status, t.created_at, u.full_name FROM transactions t JOIN users u ON t.user_id = u.id ORDER BY t.created_at DESC")->fetch_all(MYSQLI_ASSOC);
?>
<?php include 'header.php'; ?>
<section class="px-4 py-12 bg-gray-50">
  <div class="max-w-7xl mx-auto">
    <h1 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-6 text-center">Manage Transactions</h1>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
      <table class="w-full text-left">
        <thead>
          <tr class="border-b border-gray-200">
            <th class="py-3 px-4 text-gray-700 font-semibold">User</th>
            <th class="py-3 px-4 text-gray-700 font-semibold">Amount</th>
            <th class="py-3 px-4 text-gray-700 font-semibold">Type</th>
            <th class="py-3 px-4 text-gray-700 font-semibold">Status</th>
            <th class="py-3 px-4 text-gray-700 font-semibold">Date</th>
            <th class="py-3 px-4 text-gray-700 font-semibold">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($transactions)) { ?>
            <tr>
              <td colspan="6" class="py-4 px-4 text-gray-600 text-center">No transactions found.</td>
            </tr>
          <?php } else { ?>
            <?php foreach ($transactions as $transaction) { ?>
              <tr class="border-b border-gray-200">
                <td class="py-3 px-4"><?php echo htmlspecialchars($transaction['full_name']); ?></td>
                <td class="py-3 px-4">KSh <?php echo number_format($transaction['amount'], 2); ?></td>
                <td class="py-3 px-4"><?php echo ucfirst($transaction['type']); ?></td>
                <td class="py-3 px-4"><?php echo ucfirst($transaction['status']); ?></td>
                <td class="py-3 px-4"><?php echo date('M d, Y', strtotime($transaction['created_at'])); ?></td>
                <td class="py-3 px-4">
                  <form method="POST">
                    <input type="hidden" name="transaction_id" value="<?php echo $transaction['id']; ?>">
                    <select name="status" class="px-2 py-1 border border-gray-300 rounded-button focus:outline-none focus:ring-2 focus:ring-primary" aria-label="Update transaction status">
                      <option value="pending" <?php echo $transaction['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                      <option value="completed" <?php echo $transaction['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                      <option value="failed" <?php echo $transaction['status'] == 'failed' ? 'selected' : ''; ?>>Failed</option>
                    </select>
                    <button type="submit" name="update_status" class="text-primary hover:text-emerald-600 ml-2" aria-label="Update status for transaction <?php echo $transaction['id']; ?>">
                      <i class="ri-save-line"></i>
                    </button>
                  </form>
                </td>
              </tr>
            <?php } ?>
          <?php } ?>
        </tbody>
      </table>
    </div>
  </div>
</section>
<?php include '../footer.php'; ?>