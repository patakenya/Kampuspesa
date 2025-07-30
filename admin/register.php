<?php
session_start();
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = htmlspecialchars(trim($_POST['full_name']));
    $email = htmlspecialchars(trim($_POST['email']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($full_name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM admins WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) {
            $error = "Email already registered.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO admins (full_name, email, password, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("sss", $full_name, $email, $hashed_password);
            if ($stmt->execute()) {
                $success = "Registration successful. Please log in.";
            } else {
                $error = "Registration failed. Please try again.";
                error_log("Database error: Admin registration failed: " . $conn->error);
            }
        }
        $stmt->close();
    }
}
?>
<?php include 'header.php'; ?>
<section class="px-4 py-12 bg-gray-50">
  <div class="max-w-md mx-auto">
    <h2 class="text-3xl font-bold text-gray-900 mb-6 text-center">Admin Registration</h2>
    <p class="text-gray-600 mb-4 text-center">Create an admin account to manage CampusEarn.</p>
    <?php if (isset($error)) { ?>
      <p class="text-red-500 bg-red-100 p-4 rounded-lg mb-4"><?php echo $error; ?></p>
    <?php } ?>
    <?php if (isset($success)) { ?>
      <p class="text-green-500 bg-green-100 p-4 rounded-lg mb-4"><?php echo $success; ?></p>
    <?php } ?>
    <form method="POST" class="space-y-6 bg-white p-6 rounded-2xl shadow-lg">
      <div>
        <label for="full_name" class="block text-gray-700 font-semibold mb-2">Full Name</label>
        <input type="text" name="full_name" id="full_name" placeholder="Enter full name" class="w-full px-4 py-3 rounded-button border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary" required aria-label="Full Name">
      </div>
      <div>
        <label for="email" class="block text-gray-700 font-semibold mb-2">Email</label>
        <input type="email" name="email" id="email" placeholder="Enter email" class="w-full px-4 py-3 rounded-button border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary" required aria-label="Email">
      </div>
      <div>
        <label for="password" class="block text-gray-700 font-semibold mb-2">Password</label>
        <input type="password" name="password" id="password" placeholder="Enter password" class="w-full px-4 py-3 rounded-button border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary" required aria-label="Password">
      </div>
      <div>
        <label for="confirm_password" class="block text-gray-700 font-semibold mb-2">Confirm Password</label>
        <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm password" class="w-full px-4 py-3 rounded-button border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary" required aria-label="Confirm Password">
      </div>
      <button type="submit" class="w-full bg-primary text-white px-6 py-3 rounded-button font-semibold hover:bg-emerald-600 transition-colors">Register</button>
    </form>
    <p class="text-center text-gray-600 mt-4">Already have an account? <a href="login.php" class="text-primary hover:underline">Log In</a></p>
  </div>
</section>
<?php include '../footer.php'; ?>