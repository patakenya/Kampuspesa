<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch user data
$stmt = $conn->prepare("SELECT tier FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($user['tier'] == 'bronze') {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = htmlspecialchars(trim($_POST['title']));
    $content = htmlspecialchars(trim($_POST['content']));
    $category = htmlspecialchars(trim($_POST['category']));
    
    if (empty($title) || empty($content) || empty($category)) {
        $error = "All fields are required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO articles (user_id, title, content, category, status, created_at) VALUES (?, ?, ?, ?, 'pending', NOW())");
        $stmt->bind_param("isss", $_SESSION['user_id'], $title, $content, $category);
        if ($stmt->execute()) {
            $success = "Article submitted successfully. Awaiting approval.";
        } else {
            $error = "Failed to submit article. Please try again.";
        }
        $stmt->close();
    }
}
?>
<?php include 'header.php'; ?>
<section class="px-4 py-16 bg-gray-50">
  <div class="max-w-4xl mx-auto">
    <h2 class="text-3xl md:text-5xl font-bold text-gray-900 mb-6 text-center">Write a New Article</h2>
    <p class="text-xl text-gray-600 mb-8 text-center">
      Create engaging content and earn KSh <?php echo $user['tier'] == 'silver' ? '300' : '500'; ?> per approved article.
    </p>
    <div class="bg-white rounded-2xl p-8 shadow-lg">
      <?php if (isset($error)) { echo "<p class='text-red-500 mb-4'>$error</p>"; } ?>
      <?php if (isset($success)) { echo "<p class='text-green-500 mb-4'>$success</p>"; } ?>
      <form method="POST" class="space-y-6">
        <div>
          <label for="title" class="block text-gray-700 font-semibold mb-2">Article Title</label>
          <input type="text" name="title" id="title" placeholder="Enter article title" class="w-full px-4 py-3 rounded-button border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary text-gray-900" required>
        </div>
        <div>
          <label for="category" class="block text-gray-700 font-semibold mb-2">Category</label>
          <select name="category" id="category" class="w-full px-4 py-3 rounded-button border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary text-gray-900" required>
            <option value="student_life">Student Life</option>
            <option value="entrepreneurship">Entrepreneurship</option>
            <option value="product_promotion">Product Promotion</option>
          </select>
        </div>
        <div>
          <label for="content" class="block text-gray-700 font-semibold mb-2">Content</label>
          <textarea name="content" id="content" rows="10" placeholder="Write your article here..." class="w-full px-4 py-3 rounded-button border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary text-gray-900" required></textarea>
        </div>
        <button type="submit" class="w-full bg-primary text-white px-6 py-3 rounded-button font-semibold hover:bg-emerald-600 transition-colors">Submit Article</button>
      </form>
    </div>
  </div>
</section>
<?php include 'footer.php'; ?>