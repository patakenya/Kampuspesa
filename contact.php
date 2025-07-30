<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = htmlspecialchars(trim($_POST['name']));
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $message = htmlspecialchars(trim($_POST['message']));
    
    if (empty($name) || empty($email) || empty($message)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        $stmt = $conn->prepare("INSERT INTO contact_submissions (name, email, message, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("sss", $name, $email, $message);
        if ($stmt->execute()) {
            $success = "Your message has been sent successfully. We'll get back to you soon!";
        } else {
            $error = "Failed to send message. Please try again.";
        }
        $stmt->close();
    }
}
?>
<?php include 'header.php'; ?>
<section class="px-4 py-16 bg-gray-50">
  <div class="max-w-4xl mx-auto text-center">
    <h2 class="text-3xl md:text-5xl font-bold text-gray-900 mb-6">Contact Us</h2>
    <p class="text-xl text-gray-600 mb-8">
      Have questions or need support? Reach out to the CampusEarn team, and we’ll respond within 24 hours.
    </p>
    <div class="bg-white rounded-2xl p-8 max-w-md mx-auto shadow-lg">
      <h3 class="text-2xl font-bold text-gray-900 mb-6">Send Us a Message</h3>
      <?php if (isset($error)) { echo "<p class='text-red-500 mb-4'>$error</p>"; } ?>
      <?php if (isset($success)) { echo "<p class='text-green-500 mb-4'>$success</p>"; } ?>
      <form method="POST" class="space-y-4">
        <input type="text" name="name" placeholder="Your Name" class="w-full px-4 py-3 rounded-button border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary text-gray-900" required>
        <input type="email" name="email" placeholder="Your Email" class="w-full px-4 py-3 rounded-button border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary text-gray-900" required>
        <textarea name="message" placeholder="Your Message" rows="5" class="w-full px-4 py-3 rounded-button border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary text-gray-900" required></textarea>
        <button type="submit" class="w-full bg-primary text-white px-6 py-3 rounded-button font-semibold hover:bg-emerald-600 transition-colors">Send Message</button>
      </form>
      <p class="text-sm text-gray-600 mt-4">
        Prefer direct contact? Email us at <a href="mailto:support@campusearn.co.ke" class="text-primary hover:underline">support@campusearn.co.ke</a> or call +254 123 456 789.
      </p>
    </div>
  </div>
</section>
<?php include 'footer.php'; ?>