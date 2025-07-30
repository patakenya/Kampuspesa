<?php
session_start();
require_once 'config.php';

// Validate article ID
$article_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($article_id <= 0) {
    header("Location: articles.php");
    exit();
}

// Fetch article
$stmt = $conn->prepare("SELECT a.id, a.title, a.content, a.category, a.created_at, a.featured_image, u.full_name FROM articles a JOIN users u ON a.user_id = u.id WHERE a.id = ? AND a.status = 'approved'");
if (!$stmt) {
    error_log("Database error: Unable to prepare article query: " . $conn->error);
    header("Location: articles.php");
    exit();
}
$stmt->bind_param("i", $article_id);
$stmt->execute();
$article = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$article) {
    header("Location: articles.php");
    exit();
}

// Fetch random articles for sidebar (exclude current article)
$stmt = $conn->prepare("SELECT a.id, a.title, a.featured_image FROM articles a WHERE a.status = 'approved' AND a.id != ? ORDER BY RAND() LIMIT 3");
$stmt->bind_param("i", $article_id);
$stmt->execute();
$random_articles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Handle comment submission
$comment_error = $comment_success = null;
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $comment_content = htmlspecialchars(trim($_POST['comment']));
    if (empty($comment_content)) {
        $comment_error = "Comment cannot be empty.";
    } else {
        $stmt = $conn->prepare("INSERT INTO comments (article_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iis", $article_id, $_SESSION['user_id'], $comment_content);
        if ($stmt->execute()) {
            $comment_success = "Comment submitted successfully.";
        } else {
            $comment_error = "Failed to submit comment. Please try again.";
            error_log("Database error: Comment insert failed: " . $conn->error);
        }
        $stmt->close();
    }
}

// Fetch comments
$stmt = $conn->prepare("SELECT c.content, c.created_at, u.full_name FROM comments c JOIN users u ON c.user_id = u.id WHERE c.article_id = ? ORDER BY c.created_at DESC");
$stmt->bind_param("i", $article_id);
$stmt->execute();
$comments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<?php include 'header.php'; ?>
<section class="px-4 py-12 bg-gray-50">
  <div class="max-w-7xl mx-auto flex flex-col lg:flex-row gap-8">
    <!-- Main Article -->
    <div class="lg:w-2/3">
      <article class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-4"><?php echo htmlspecialchars($article['title']); ?></h1>
        <img 
          src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='800' height='400'%3E%3Crect width='100%25' height='100%25' fill='%23f3f4f6'/%3E%3C/svg%3E"
          data-src="<?php echo htmlspecialchars($article['featured_image'] ?: 'https://readdy.ai/api/search-image?query=student%20writing&width=800&height=400&seq=default'); ?>"
          alt="Featured image for <?php echo htmlspecialchars($article['title']); ?>"
          class="w-full h-64 sm:h-80 object-cover rounded-lg mb-6 lazy-load"
          loading="lazy"
          aria-label="Featured image for <?php echo htmlspecialchars($article['title']); ?>">
        <p class="text-sm text-gray-500 mb-4">By <?php echo htmlspecialchars($article['full_name']); ?> | <?php echo date('M d, Y', strtotime($article['created_at'])); ?> | Category: <?php echo ucfirst(str_replace('_', ' ', $article['category'])); ?></p>
        <div class="text-gray-700 leading-relaxed mb-6 prose max-w-none"><?php echo $article['content']; ?></div>
        <!-- Social Share Buttons -->
        <div class="flex gap-4 mb-6">
          <a href="https://api.whatsapp.com/send?text=<?php echo urlencode('Check out this article: ' . $article['title'] . ' - http://kampuspesa/article.php?id=' . $article_id); ?>" 
             class="bg-green-500 text-white px-4 py-2 rounded-button font-semibold hover:bg-green-600 transition-colors" 
             aria-label="Share on WhatsApp">
            <i class="ri-whatsapp-line mr-2"></i> WhatsApp
          </a>
          <a href="https://twitter.com/intent/tweet?text=<?php echo urlencode($article['title']); ?>&url=<?php echo urlencode('http://kampuspesa/article.php?id=' . $article_id); ?>" 
             class="bg-blue-400 text-white px-4 py-2 rounded-button font-semibold hover:bg-blue-500 transition-colors" 
             aria-label="Share on Twitter/X">
            <i class="ri-twitter-x-line mr-2"></i> Twitter/X
          </a>
          <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode('http://kampuspesa/article.php?id=' . $article_id); ?>" 
             class="bg-blue-600 text-white px-4 py-2 rounded-button font-semibold hover:bg-blue-700 transition-colors" 
             aria-label="Share on Facebook">
            <i class="ri-facebook-line mr-2"></i> Facebook
          </a>
        </div>
        <!-- Comments Section -->
        <div class="mt-8">
          <h2 class="text-xl font-bold text-gray-900 mb-4">Comments</h2>
          <?php if (isset($_SESSION['user_id'])) { ?>
            <?php if (isset($comment_error)) { ?>
              <p class="text-red-500 bg-red-100 p-4 rounded-lg mb-4"><?php echo $comment_error; ?></p>
            <?php } ?>
            <?php if (isset($comment_success)) { ?>
              <p class="text-green-500 bg-green-100 p-4 rounded-lg mb-4"><?php echo $comment_success; ?></p>
            <?php } ?>
            <form method="POST" class="mb-6">
              <label for="comment" class="block text-gray-700 font-semibold mb-2">Add a Comment</label>
              <textarea name="comment" id="comment" rows="4" placeholder="Write your comment..." 
                        class="w-full px-4 py-3 rounded-button border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary text-gray-900" 
                        required aria-label="Comment"></textarea>
              <button type="submit" 
                      class="mt-4 bg-primary text-white px-6 py-3 rounded-button font-semibold hover:bg-emerald-600 transition-colors" 
                      aria-label="Submit Comment">Submit Comment</button>
            </form>
          <?php } else { ?>
            <p class="text-gray-600 mb-4">Please <a href="login.php" class="text-primary hover:underline">log in</a> to post a comment.</p>
          <?php } ?>
          <?php if (empty($comments)) { ?>
            <p class="text-gray-600">No comments yet. Be the first to comment!</p>
          <?php } else { ?>
            <?php foreach ($comments as $comment) { ?>
              <div class="bg-gray-100 rounded-lg p-4 mb-4">
                <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
                <p class="text-sm text-gray-500 mt-2">By <?php echo htmlspecialchars($comment['full_name']); ?> | <?php echo date('M d, Y', strtotime($comment['created_at'])); ?></p>
              </div>
            <?php } ?>
          <?php } ?>
        </div>
      </article>
    </div>
    <!-- Sidebar -->
    <aside class="lg:w-1/3">
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4">More Articles</h2>
        <?php if (empty($random_articles)) { ?>
          <p class="text-gray-600">No other articles available.</p>
        <?php } else { ?>
          <?php foreach ($random_articles as $random_article) { ?>
            <div class="mb-6 last:mb-0">
              <img 
                src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='300' height='150'%3E%3Crect width='100%25' height='100%25' fill='%23f3f4f6'/%3E%3C/svg%3E"
                data-src="<?php echo htmlspecialchars($random_article['featured_image'] ?: 'https://readdy.ai/api/search-image?query=student%20writing&width=300&height=150&seq=default'); ?>"
                alt="Featured image for <?php echo htmlspecialchars($random_article['title']); ?>"
                class="w-full h-24 object-cover rounded-lg mb-2 lazy-load"
                loading="lazy"
                aria-label="Featured image for <?php echo htmlspecialchars($random_article['title']); ?>">
              <a href="article.php?id=<?php echo $random_article['id']; ?>" 
                 class="text-gray-900 hover:text-primary font-semibold" 
                 aria-label="Read more about <?php echo htmlspecialchars($random_article['title']); ?>">
                <?php echo htmlspecialchars($random_article['title']); ?>
              </a>
            </div>
          <?php } ?>
        <?php } ?>
      </div>
    </aside>
  </div>
</section>
<script>
  // Lazy-load images
  document.addEventListener('DOMContentLoaded', function() {
    const images = document.querySelectorAll('img.lazy-load');
    if ('IntersectionObserver' in window) {
      const observer = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            const img = entry.target;
            img.src = img.dataset.src;
            img.classList.remove('lazy-load');
            observer.unobserve(img);
          }
        });
      });
      images.forEach(img => observer.observe(img));
    } else {
      images.forEach(img => {
        img.src = img.dataset.src;
        img.classList.remove('lazy-load');
      });
    }
  });
</script>
<?php include 'footer.php'; ?>