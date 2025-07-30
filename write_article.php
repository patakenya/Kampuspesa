<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch user data
$stmt = $conn->prepare("SELECT tier FROM users WHERE id = ?");
if (!$stmt) {
    error_log("Database error: Unable to prepare user query: " . $conn->error);
    header("Location: dashboard.php");
    exit();
}
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user || $user['tier'] == 'bronze') {
    header("Location: dashboard.php");
    exit();
}

// Basic HTML sanitization function
function sanitize_html($html) {
    // Allow specific tags and attributes
    $allowed_tags = '<p><b><i><strong><em><h1><h2><h3><ul><ol><li><a><br>';
    $cleaned_html = strip_tags($html, $allowed_tags);
    // Remove dangerous attributes and scripts
    $cleaned_html = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/i', '', $cleaned_html);
    $cleaned_html = preg_replace('/on\w*="[^"]*"/i', '', $cleaned_html);
    // Ensure safe links
    $cleaned_html = preg_replace('/<a\s+href="([^"]*)"/i', '<a href="$1" rel="noopener noreferrer"', $cleaned_html);
    return trim($cleaned_html);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = htmlspecialchars(trim($_POST['title']));
    $content = sanitize_html(trim($_POST['content']));
    $category = htmlspecialchars(trim($_POST['category']));
    $featured_image = null;

    // Validate inputs
    if (empty($title) || empty($content) || empty($category)) {
        $error = "All fields are required.";
    } elseif (!in_array($category, ['student_life', 'entrepreneurship', 'product_promotion'])) {
        $error = "Invalid category selected.";
    } else {
        // Handle file upload
        if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] == UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png'];
            $max_size = 2 * 1024 * 1024; // 2MB
            $file_type = $_FILES['featured_image']['type'];
            $file_size = $_FILES['featured_image']['size'];
            $file_tmp = $_FILES['featured_image']['tmp_name'];

            if (!in_array($file_type, $allowed_types)) {
                $error = "Only JPEG and PNG images are allowed.";
            } elseif ($file_size > $max_size) {
                $error = "Image size must be less than 2MB.";
            } else {
                $ext = pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION);
                $filename = 'article_' . time() . '_' . uniqid() . '.' . $ext;
                $upload_dir = 'images/articles/';
                $upload_path = $upload_dir . $filename;

                // Create upload directory if it doesn't exist
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                if (move_uploaded_file($file_tmp, $upload_path)) {
                    $featured_image = $upload_path;
                } else {
                    $error = "Failed to upload image. Please try again.";
                }
            }
        }

        if (!isset($error)) {
            $stmt = $conn->prepare("INSERT INTO articles (user_id, title, content, category, status, created_at, featured_image) VALUES (?, ?, ?, ?, 'pending', NOW(), ?)");
            $stmt->bind_param("issss", $_SESSION['user_id'], $title, $content, $category, $featured_image);
            if ($stmt->execute()) {
                $success = "Article submitted successfully. Awaiting approval.";
            } else {
                $error = "Failed to submit article. Please try again.";
                error_log("Database error: Article insert failed: " . $conn->error);
            }
            $stmt->close();
        }
    }
}
?>
<?php include 'header.php'; ?>
<section class="px-4 py-12 sm:py-16 bg-gray-50">
  <div class="max-w-4xl mx-auto">
    <h2 class="text-3xl sm:text-4xl md:text-5xl font-bold text-gray-900 mb-6 text-center">Write a New Article</h2>
    <p class="text-lg sm:text-xl text-gray-600 mb-8 text-center max-w-3xl mx-auto">
      Create engaging content and earn KSh <?php echo $user['tier'] == 'silver' ? '300' : '500'; ?> per approved article.
    </p>
    <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-lg">
      <?php if (isset($error)) { ?>
        <p class="text-red-500 bg-red-100 p-4 rounded-lg mb-4"><?php echo $error; ?></p>
      <?php } ?>
      <?php if (isset($success)) { ?>
        <p class="text-green-500 bg-green-100 p-4 rounded-lg mb-4"><?php echo $success; ?></p>
      <?php } ?>
      <form method="POST" enctype="multipart/form-data" class="space-y-6">
        <div>
          <label for="title" class="block text-gray-700 font-semibold mb-2">Article Title</label>
          <input type="text" name="title" id="title" placeholder="Enter article title" class="w-full px-4 py-3 rounded-button border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary text-gray-900" required aria-label="Article Title">
        </div>
        <div>
          <label for="category" class="block text-gray-700 font-semibold mb-2">Category</label>
          <select name="category" id="category" class="w-full px-4 py-3 rounded-button border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary text-gray-900" required aria-label="Article Category">
            <option value="student_life">Student Life</option>
            <option value="entrepreneurship">Entrepreneurship</option>
            <option value="product_promotion">Product Promotion</option>
          </select>
        </div>
        <div>
          <label for="featured_image" class="block text-gray-700 font-semibold mb-2">Featured Image (Optional)</label>
          <input type="file" name="featured_image" id="featured_image" accept="image/jpeg,image/png" class="w-full px-4 py-3 rounded-button border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary text-gray-900" aria-label="Upload Featured Image">
          <p class="text-sm text-gray-500 mt-2">Upload a JPEG or PNG image (max 2MB).</p>
        </div>
        <div>
          <label for="content" class="block text-gray-700 font-semibold mb-2">Content</label>
          <div id="editor" class="w-full border border-gray-300 rounded-button focus-within:ring-2 focus-within:ring-primary bg-white"></div>
          <input type="hidden" name="content" id="content">
        </div>
        <button type="submit" class="w-full bg-primary text-white px-6 py-3 rounded-button font-semibold hover:bg-emerald-600 transition-colors">Submit Article</button>
      </form>
    </div>
  </div>
</section>
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const quill = new Quill('#editor', {
      theme: 'snow',
      modules: {
        toolbar: [
          ['bold', 'italic'],
          [{ 'header': [1, 2, 3, false] }],
          [{ 'list': 'ordered'}, { 'list': 'bullet' }],
          ['link'],
          ['clean']
        ]
      },
      placeholder: 'Write your article here...',
    });

    // Sync Quill content with hidden input
    const form = document.querySelector('form');
    const contentInput = document.getElementById('content');
    form.addEventListener('submit', function() {
      contentInput.value = quill.root.innerHTML;
    });
  });
</script>
<style>
  .ql-editor {
    min-height: 200px;
    font-size: 1rem;
    line-height: 1.5;
  }
  .ql-toolbar {
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
    border-color: #d1d5db;
  }
  .ql-container {
    border-bottom-left-radius: 8px;
    border-bottom-right-radius: 8px;
    border-color: #d1d5db;
  }
</style>
<?php include 'footer.php'; ?>