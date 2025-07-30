<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch article and user data
$article_id = isset($_GET['article_id']) ? intval($_GET['article_id']) : 0;
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$article = null;
$user = null;
$default_amount = 0;

if ($article_id > 0 && $user_id > 0) {
    // Fetch article
    $stmt_article = $conn->prepare("SELECT a.title, a.user_id, u.full_name, u.tier FROM articles a JOIN users u ON a.user_id = u.id WHERE a.id = ? AND a.user_id = ? AND a.status = 'pending'");
    if (!$stmt_article) {
        error_log("Database error: Unable to prepare article query: " . $conn->error);
        header("Location: articles.php");
        exit();
    }
    $stmt_article->bind_param("ii", $article_id, $user_id);
    $stmt_article->execute();
    $article = $stmt_article->get_result()->fetch_assoc();
    $stmt_article->close();

    if (!$article) {
        header("Location: articles.php");
        exit();
    }

    // Set default amount based on user tier
    $default_amount = $article['tier'] == 'silver' ? 300.00 : ($article['tier'] == 'gold' ? 500.00 : 0);
    $user = ['id' => $article['user_id'], 'full_name' => $article['full_name'], 'tier' => $article['tier']];
}

// Fetch all users for dropdown (if no article_id provided)
$users = $conn->query("SELECT id, full_name, tier FROM users ORDER BY full_name")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = intval($_POST['user_id']);
    $amount = floatval($_POST['amount']);
    $article_id = intval($_POST['article_id']);

    if ($user_id <= 0 || $amount <= 0) {
        $error = "Invalid user or amount.";
    } else {
        // Begin transaction
        $conn->begin_transaction();
        try {
            // Update user balance
            $stmt_balance = $conn->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
            if (!$stmt_balance) {
                throw new Exception("Unable to prepare balance update query: " . $conn->error);
            }
            $stmt_balance->bind_param("di", $amount, $user_id);
            $stmt_balance->execute();
            $stmt_balance->close();

            // Insert transaction
            $stmt_transaction = $conn->prepare("INSERT INTO transactions (user_id, amount, type, status, created_at) VALUES (?, ?, 'earning', 'completed', NOW())");
            if (!$stmt_transaction) {
                throw new Exception("Unable to prepare transaction insert query: " . $conn->error);
            }
            $stmt_transaction->bind_param("id", $user_id, $amount);
            $stmt_transaction->execute();
            $stmt_transaction->close();

            // Update article status
            if ($article_id > 0) {
                $stmt_article_update = $conn->prepare("UPDATE articles SET status = 'approved' WHERE id = ?");
                if (!$stmt_article_update) {
                    throw new Exception("Unable to prepare article update query: " . $conn->error);
                }
                $stmt_article_update->bind_param("i", $article_id);
                $stmt_article_update->execute();
                $stmt_article_update->close();
            }

            $conn->commit();
            $success = "Balance updated successfully.";
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Failed to update balance. Please try again.";
            error_log("Database error: " . $e->getMessage());
        }
    }
}
?>
<?php include 'header.php'; ?>
<section class="px-4 py-12 bg-gray-50">
  <div class="max-w-md mx-auto">
    <h2 class="text-3xl font-bold text-gray-900 mb-6 text-center">Add Balance</h2>
    <p class="text-gray-600 mb-4 text-center">Add funds to a user's balance after approving an article.</p>
    <?php if (isset($error)) { ?>
      <p class="text-red-500 bg-red-100 p-4 rounded-lg mb-4"><?php echo $error; ?></p>
    <?php } ?>
    <?php if (isset($success)) { ?>
      <p class="text-green-500 bg-green-100 p-4 rounded-lg mb-4"><?php echo $success; ?></p>
    <?php } ?>
    <form method="POST" class="space-y-6 bg-white p-6 rounded-2xl shadow-lg">
      <div>
        <label for="user_id" class="block text-gray-700 font-semibold mb-2">Select User</label>
        <select name="user_id" id="user_id" class="w-full px-4 py-3 rounded-button border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary" required aria-label="Select User">
          <?php if ($user) { ?>
            <option value="<?php echo $user['id']; ?>" selected><?php echo htmlspecialchars($user['full_name']) . ' (' . ucfirst($user['tier']) . ')'; ?></option>
          <?php } else { ?>
            <option value="" disabled selected>Select a user</option>
            <?php foreach ($users as $u) { ?>
              <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['full_name']) . ' (' . ucfirst($u['tier']) . ')'; ?></option>
            <?php } ?>
          <?php } ?>
        </select>
      </div>
      <div>
        <label for="amount" class="block text-gray-700 font-semibold mb-2">Amount (KSh)</label>
        <input type="number" name="amount" id="amount" value="<?php echo $default_amount; ?>" step="0.01" min="0" placeholder="Enter amount" class="w-full px-4 py-3 rounded-button border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary" required aria-label="Amount in KSh">
      </div>
      <input type="hidden" name="article_id" value="<?php echo $article_id; ?>">
      <button type="submit" class="w-full bg-primary text-white px-6 py-3 rounded-button font-semibold hover:bg-emerald-600 transition-colors">Add Balance</button>
    </form>
    <?php if ($article) { ?>
      <p class="text-gray-600 mt-4 text-center">Article: <a href="../article.php?id=<?php echo $article_id; ?>" class="text-primary hover:underline"><?php echo htmlspecialchars($article['title']); ?></a></p>
    <?php } ?>
    <p class="text-center text-gray-600 mt-4"><a href="articles.php" class="text-primary hover:underline">Back to Articles</a></p>
  </div>
</section>
<?php include '../footer.php'; ?>