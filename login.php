<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = htmlspecialchars(trim($_POST['username']));
    $password = trim($_POST['password']);
    
    $stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "Username not found.";
    }
    $stmt->close();
}
?>
<?php include 'header.php'; ?>
<section class="px-4 py-16 bg-gray-50">
  <div class="max-w-4xl mx-auto text-center">
    <h2 class="text-3xl md:text-5xl font-bold text-gray-900 mb-6">Log In to CampusEarn</h2>
    <p class="text-xl text-gray-600 mb-8">
      Access your account to track earnings, submit articles, and manage referrals.
    </p>
    <div class="bg-white rounded-2xl p-8 max-w-md mx-auto shadow-lg">
      <h3 class="text-2xl font-bold text-gray-900 mb-6">Log In</h3>
      <?php if (isset($error)) { echo "<p class='text-red-500 mb-4'>$error</p>"; } ?>
      <form method="POST" class="space-y-4">
        <input type="text" name="username" placeholder="Username" class="w-full px-4 py-3 rounded-button border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary text-gray-900" required>
        <input type="password" name="password" placeholder="Password" class="w-full px-4 py-3 rounded-button border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary text-gray-900" required>
        <button type="submit" class="w-full bg-primary text-white px-6 py-3 rounded-button font-semibold hover:bg-emerald-600 transition-colors">Log In</button>
      </form>
      <p class="text-sm text-gray-600 mt-4">
        Don’t have an account? <a href="register.php" class="text-primary hover:underline">Sign Up</a>
      </p>
    </div>
  </div>
</section>
<?php include 'footer.php'; ?>