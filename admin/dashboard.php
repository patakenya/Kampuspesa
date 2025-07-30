<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch counts
$users_count = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$articles_count = $conn->query("SELECT COUNT(*) as count FROM articles WHERE status = 'approved'")->fetch_assoc()['count'];
$transactions_count = $conn->query("SELECT COUNT(*) as count FROM transactions")->fetch_assoc()['count'];
$pending_articles = $conn->query("SELECT COUNT(*) as count FROM articles WHERE status = 'pending'")->fetch_assoc()['count'];
?>
<?php include 'header.php'; ?>
<section class="px-4 py-12 bg-gray-50">
  <div class="max-w-7xl mx-auto">
    <h1 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-6 text-center">Admin Dashboard</h1>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 text-center">
        <h2 class="text-xl font-semibold text-gray-900 mb-2">Total Users</h2>
        <p class="text-3xl font-bold text-primary"><?php echo $users_count; ?></p>
      </div>
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 text-center">
        <h2 class="text-xl font-semibold text-gray-900 mb-2">Approved Articles</h2>
        <p class="text-3xl font-bold text-primary"><?php echo $articles_count; ?></p>
      </div>
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 text-center">
        <h2 class="text-xl font-semibold text-gray-900 mb-2">Pending Articles</h2>
        <p class="text-3xl font-bold text-primary"><?php echo $pending_articles; ?></p>
      </div>
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 text-center">
        <h2 class="text-xl font-semibold text-gray-900 mb-2">Transactions</h2>
        <p class="text-3xl font-bold text-primary"><?php echo $transactions_count; ?></p>
      </div>
    </div>
  </div>
</section>
<?php include '../footer.php'; ?>