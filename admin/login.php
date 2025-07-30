<?php
session_start();
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = htmlspecialchars(trim($_POST['email']));
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "All fields are required.";
    } else {
        $stmt = $conn->prepare("SELECT id, password FROM admins WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>
<?php include 'header.php'; ?>
<section class="px-4 py-12 bg-gray-50">
  <div class="max-w-md mx-auto">
    <h2 class="text-3xl font-bold text-gray-900 mb-6 text-center">Admin Login</h2>
    <p class="text-gray-600 mb-4 text-center">Log in to manage CampusEarn.</p>
    <?php if (isset($error)) { ?>
      <p class="text-red-500 bg-red-100 p-4 rounded-lg mb-4"><?php echo $error; ?></p>
    <?php } ?>
    <form method="POST" class="space-y-6 bg-white p-6 rounded-2xl shadow-lg">
      <div>
        <label for="email" class="block text-gray-700 font-semibold mb-2">Email</label>
        <input type="email" name="email" id="email" placeholder="Enter email" class="w-full px-4 py-3 rounded-button border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary" required aria-label="Email">
      </div>
      <div>
        <label for="password" class="block text-gray-700 font-semibold mb-2">Password</label>
        <input type="password" name="password" id="password" placeholder="Enter password" class="w-full px-4 py-3 rounded-button border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary" required aria-label="Password">
      </div>
      <button type="submit" class="w-full bg-primary text-white px-6 py-3 rounded-button font-semibold hover:bg-emerald-600 transition-colors">Log In</button>
    </form>
    <p class="text-center text-gray-600 mt-4">No account? <a href="register.php" class="text-primary hover:underline">Register</a></p>
  </div>
</section>
<?php include '../footer.php'; ?>