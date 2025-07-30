<?php
require_once 'config.php';

// Fetch all approved articles
$stmt = $conn->prepare("SELECT a.title, a.content, a.category, a.created_at, u.full_name FROM articles a JOIN users u ON a.user_id = u.id WHERE a.status = 'approved' ORDER BY a.created_at DESC");
$stmt->execute();
$articles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<?php include 'header.php'; ?>
<section class="px-4 py-12 bg-gray-50">
  <div class="max-w-7xl mx-auto">
    <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-6 text-center">Published Articles</h1>
    <p class="text-xl text-gray-600 mb-8 text-center">
      Explore inspiring articles written by CampusEarn students on student life, entrepreneurship, and more.
    </p>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
      <?php foreach ($articles as $article) { ?>
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-2"><?php echo htmlspecialchars($article['title']); ?></h2>
        <p class="text-gray-600 mb-4"><?php echo substr(htmlspecialchars($article['content']), 0, 150); ?>...</p>
        <p class="text-sm text-gray-500 mb-2">By <?php echo htmlspecialchars($article['full_name']); ?> | <?php echo date('M d, Y', strtotime($article['created_at'])); ?></p>
        <p class="text-sm text-gray-500">Category: <?php echo ucfirst(str_replace('_', ' ', $article['category'])); ?></p>
      </div>
      <?php } ?>
    </div>
  </div>
</section>
<?php include 'footer.php'; ?>