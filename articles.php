<?php
require_once 'config.php';

// Fetch all approved articles with featured image
$stmt = $conn->prepare("SELECT a.id, a.title, a.content, a.category, a.created_at, a.featured_image, u.full_name FROM articles a JOIN users u ON a.user_id = u.id WHERE a.status = 'approved' ORDER BY a.created_at DESC");
if (!$stmt) {
    error_log("Database error: Unable to prepare articles query: " . $conn->error);
    $articles = [];
} else {
    $stmt->execute();
    $articles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>
<?php include 'header.php'; ?>
<section class="px-4 py-12 bg-gray-50">
  <div class="max-w-7xl mx-auto">
    <h1 class="text-3xl sm:text-4xl md:text-5xl font-bold text-gray-900 mb-6 text-center">Published Articles</h1>
    <p class="text-lg sm:text-xl text-gray-600 mb-8 text-center max-w-3xl mx-auto">
      Explore inspiring articles written by CampusEarn students on student life, entrepreneurship, and more.
    </p>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php if (empty($articles)) { ?>
        <p class="text-gray-600 text-center col-span-full">No articles available yet.</p>
      <?php } else { ?>
        <?php foreach ($articles as $article) { ?>
          <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 flex flex-col">
            <img 
              src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='600' height='400'%3E%3Crect width='100%25' height='100%25' fill='%23f3f4f6'/%3E%3C/svg%3E"
              data-src="<?php echo htmlspecialchars($article['featured_image'] ?: 'https://readdy.ai/api/search-image?query=student%20writing&width=600&height=400&seq=default'); ?>"
              alt="Featured image for <?php echo htmlspecialchars($article['title']); ?>"
              class="w-full h-48 object-cover rounded-t-2xl mb-4 lazy-load"
              loading="lazy"
              aria-label="Featured image for <?php echo htmlspecialchars($article['title']); ?>">
            <h2 class="text-lg sm:text-xl font-bold text-gray-900 mb-2"><?php echo htmlspecialchars($article['title']); ?></h2>
            <p class="text-gray-600 mb-4 flex-grow"><?php echo substr(strip_tags($article['content']), 0, 150); ?>...</p>
            <p class="text-sm text-gray-500 mb-2">By <?php echo htmlspecialchars($article['full_name']); ?> | <?php echo date('M d, Y', strtotime($article['created_at'])); ?></p>
            <p class="text-sm text-gray-500 mb-4">Category: <?php echo ucfirst(str_replace('_', ' ', $article['category'])); ?></p>
            <a href="article.php?id=<?php echo $article['id']; ?>" 
               class="text-primary hover:underline font-semibold text-center" 
               aria-label="Read more about <?php echo htmlspecialchars($article['title']); ?>">Read More</a>
          </div>
        <?php } ?>
      <?php } ?>
    </div>
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