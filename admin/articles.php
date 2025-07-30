<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Handle article actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $article_id = intval($_POST['article_id']);
    if (isset($_POST['approve'])) {
        $stmt = $conn->prepare("UPDATE articles SET status = 'approved' WHERE id = ?");
        $stmt->bind_param("i", $article_id);
        $stmt->execute();
        $stmt->close();
    } elseif (isset($_POST['reject'])) {
        $stmt = $conn->prepare("UPDATE articles SET status = 'rejected' WHERE id = ?");
        $stmt->bind_param("i", $article_id);
        $stmt->execute();
        $stmt->close();
    } elseif (isset($_POST['delete'])) {
        $stmt = $conn->prepare("DELETE FROM articles WHERE id = ?");
        $stmt->bind_param("i", $article_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: articles.php");
    exit();
}

// Fetch articles
$articles = $conn->query("SELECT a.id, a.title, a.category, a.status, a.created_at, u.full_name FROM articles a JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC")->fetch_all(MYSQLI_ASSOC);
?>
<?php include 'header.php'; ?>
<section class="px-4 py-12 bg-gray-50">
  <div class="max-w-7xl mx-auto">
    <h1 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-6 text-center">Manage Articles</h1>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
      <table class="w-full text-left">
        <thead>
          <tr class="border-b border-gray-200">
            <th class="py-3 px-4 text-gray-700 font-semibold">Title</th>
            <th class="py-3 px-4 text-gray-700 font-semibold">Author</th>
            <th class="py-3 px-4 text-gray-700 font-semibold">Category</th>
            <th class="py-3 px-4 text-gray-700 font-semibold">Status</th>
            <th class="py-3 px-4 text-gray-700 font-semibold">Date</th>
            <th class="py-3 px-4 text-gray-700 font-semibold">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($articles)) { ?>
            <tr>
              <td colspan="6" class="py-4 px-4 text-gray-600 text-center">No articles found.</td>
            </tr>
          <?php } else { ?>
            <?php foreach ($articles as $article) { ?>
              <tr class="border-b border-gray-200">
                <td class="py-3 px-4">
                  <a href="../article.php?id=<?php echo $article['id']; ?>" class="text-primary hover:underline"><?php echo htmlspecialchars($article['title']); ?></a>
                </td>
                <td class="py-3 px-4"><?php echo htmlspecialchars($article['full_name']); ?></td>
                <td class="py-3 px-4"><?php echo ucfirst(str_replace('_', ' ', $article['category'])); ?></td>
                <td class="py-3 px-4"><?php echo ucfirst($article['status']); ?></td>
                <td class="py-3 px-4"><?php echo date('M d, Y', strtotime($article['created_at'])); ?></td>
                <td class="py-3 px-4 flex space-x-2">
                  <?php if ($article['status'] == 'pending') { ?>
                    <form method="POST">
                      <input type="hidden" name="article_id" value="<?php echo $article['id']; ?>">
                      <button type="submit" name="approve" class="text-green-500 hover:text-green-700" aria-label="Approve article <?php echo htmlspecialchars($article['title']); ?>">
                        <i class="ri-check-line"></i>
                      </button>
                    </form>
                    <form method="POST">
                      <input type="hidden" name="article_id" value="<?php echo $article['id']; ?>">
                      <button type="submit" name="reject" class="text-yellow-500 hover:text-yellow-700" aria-label="Reject article <?php echo htmlspecialchars($article['title']); ?>">
                        <i class="ri-close-line"></i>
                      </button>
                    </form>
                  <?php } ?>
                  <form method="POST" onsubmit="return confirm('Are you sure you want to delete this article?');">
                    <input type="hidden" name="article_id" value="<?php echo $article['id']; ?>">
                    <button type="submit" name="delete" class="text-red-500 hover:text-red-700" aria-label="Delete article <?php echo htmlspecialchars($article['title']); ?>">
                      <i class="ri-delete-bin-line"></i>
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