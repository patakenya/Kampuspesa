<?php
session_start();
require_once 'config.php';

// Generate unique referral code
function generateReferralCode() {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $code = '';
    for ($i = 0; $i < 8; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $code;
}

// Generate OTP (for development: display OTP, in production use SMS API)
function generateOTP() {
    return rand(100000, 999999);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['otp'])) {
    $full_name = htmlspecialchars(trim($_POST['full_name']));
    $username = htmlspecialchars(trim($_POST['username']));
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $phone = htmlspecialchars(trim($_POST['phone']));
    $password = password_hash(trim($_POST['password']), PASSWORD_BCRYPT);
    $referrer_code_input = htmlspecialchars(trim($_POST['referrer_code']));
    $tier = htmlspecialchars(trim($_POST['tier']));
    
    // Validate inputs
    if (empty($full_name) || empty($username) || empty($email) || empty($phone) || empty($password) || empty($tier)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (!preg_match('/^\+254\d{9}$/', $phone)) {
        $error = "Phone number must be in the format +254XXXXXXXXX.";
    } else {
        // Check if username or email exists
        $stmt_check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt_check->bind_param("ss", $username, $email);
        $stmt_check->execute();
        $result = $stmt_check->get_result();
        if ($result->num_rows > 0) {
            $error = "Username or email already exists.";
            $stmt_check->close();
        } else {
            // Generate unique referral code
            do {
                $referrer_code = generateReferralCode();
                $stmt_ref = $conn->prepare("SELECT id FROM users WHERE referrer_code_own = ?");
                $stmt_ref->bind_param("s", $referrer_code);
                $stmt_ref->execute();
                $result_ref = $stmt_ref->get_result();
            } while ($result_ref->num_rows > 0);
            $stmt_ref->close();

            $otp = generateOTP();
            $_SESSION['temp_user'] = [
                'full_name' => $full_name,
                'username' => $username,
                'email' => $email,
                'phone' => $phone,
                'password' => $password,
                'referrer_code' => $referrer_code_input ?: null,
                'own_referrer_code' => $referrer_code,
                'tier' => $tier,
                'otp' => $otp
            ];
            $otp_message = "Your OTP is: $otp (For development purposes only)";
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['otp'])) {
    $entered_otp = trim($_POST['otp']);
    if ($entered_otp == $_SESSION['temp_user']['otp']) {
        $payment_ref = '4178866'; // Specific payment reference code
        $stmt_insert = $conn->prepare("INSERT INTO users (full_name, username, email, phone, password, referrer_code, tier, payment_ref, referrer_code_own, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt_insert->bind_param("sssssssss", 
            $_SESSION['temp_user']['full_name'],
            $_SESSION['temp_user']['username'],
            $_SESSION['temp_user']['email'],
            $_SESSION['temp_user']['phone'],
            $_SESSION['temp_user']['password'],
            $_SESSION['temp_user']['referrer_code'],
            $_SESSION['temp_user']['tier'],
            $payment_ref,
            $_SESSION['temp_user']['own_referrer_code']
        );
        if ($stmt_insert->execute()) {
            $user_id = $stmt_insert->insert_id;
            $_SESSION['user_id'] = $user_id; // Log the user in
            unset($_SESSION['temp_user']);
            header("Location: payment.php?tier=" . urlencode($_SESSION['temp_user']['tier']) . "&ref=$payment_ref");
            exit();
        } else {
            $error = "Registration failed. Please try again.";
        }
        $stmt_insert->close();
    } else {
        $error = "Invalid OTP. Please try again.";
    }
}
?>
<?php include 'header.php'; ?>
<section class="px-4 py-16 bg-gray-50">
  <div class="max-w-4xl mx-auto text-center">
    <h2 class="text-3xl md:text-5xl font-bold text-gray-900 mb-6">Join CampusEarn Today</h2>
    <p class="text-xl text-gray-600 mb-8">
      Create your account to start earning through referrals and article writing.
    </p>
    <div class="bg-white rounded-2xl p-8 max-w-md mx-auto shadow-lg">
      <h3 class="text-2xl font-bold text-gray-900 mb-6">Register Now</h3>
      <?php if (isset($error)) { echo "<p class='text-red-500 mb-4'>$error</p>"; } ?>
      <?php if (isset($otp_message)) { echo "<p class='text-green-500 mb-4'>$otp_message</p>"; } ?>
      <form method="POST" class="space-y-4">
        <?php if (!isset($_SESSION['temp_user'])) { ?>
        <input type="text" name="full_name" placeholder="Full Name" class="w-full px-4 py-3 rounded-button border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary text-gray-900" required>
        <input type="text" name="username" placeholder="Username" class="w-full px-4 py-3 rounded-button border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary text-gray-900" required>
        <input type="email" name="email" placeholder="Your Email" class="w-full px-4 py-3 rounded-button border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary text-gray-900" required>
        <input type="tel" name="phone" placeholder="Phone Number (+254XXXXXXXXX)" class="w-full px-4 py-3 rounded-button border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary text-gray-900" required>
        <input type="password" name="password" placeholder="Password" class="w-full px-4 py-3 rounded-button border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary text-gray-900" required>
        <input type="text" name="referrer_code" placeholder="Referrer Code (Optional)" class="w-full px-4 py-3 rounded-button border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary text-gray-900">
        <select name="tier" class="w-full px-4 py-3 rounded-button border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary text-gray-900" required>
          <option value="bronze">Bronze (KSh 500)</option>
          <option value="silver">Silver (KSh 750)</option>
          <option value="gold">Gold (KSh 1,000)</option>
        </select>
        <button type="submit" class="w-full bg-primary text-white px-6 py-3 rounded-button font-semibold hover:bg-emerald-600 transition-colors">Register</button>
        <?php } else { ?>
        <input type="text" name="otp" placeholder="Enter OTP" class="w-full px-4 py-3 rounded-button border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary text-gray-900" required>
        <button type="submit" class="w-full bg-primary text-white px-6 py-3 rounded-button font-semibold hover:bg-emerald-600 transition-colors">Verify OTP</button>
        <?php } ?>
      </form>
      <p class="text-sm text-gray-600 mt-4">
        Already have an account? <a href="login.php" class="text-primary hover:underline">Log In</a>
      </p>
    </div>
  </div>
</section>
<?php include 'footer.php'; ?>