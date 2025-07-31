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

// Handle form submission
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
    } elseif (!in_array($tier, ['bronze', 'silver', 'gold'])) {
        $error = "Invalid membership tier.";
    } else {
        // Check if username or email exists
        $stmt_check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        if (!$stmt_check) {
            error_log("Database error: Unable to prepare email/username check query: " . $conn->error);
            $error = "Database error occurred.";
        } else {
            $stmt_check->bind_param("ss", $username, $email);
            $stmt_check->execute();
            $result = $stmt_check->get_result();
            if ($result->num_rows > 0) {
                $error = "Username or email already exists.";
            }
            $stmt_check->close();
        }

        if (!isset($error)) {
            // Generate unique referral code
            do {
                $referrer_code_own = generateReferralCode();
                $stmt_ref = $conn->prepare("SELECT id FROM users WHERE referrer_code_own = ?");
                $stmt_ref->bind_param("s", $referrer_code_own);
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
                'referrer_code_own' => $referrer_code_own,
                'tier' => $tier,
                'otp' => $otp
            ];
            $otp_message = "Your OTP is: $otp (For development purposes only)";
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['otp'])) {
    $entered_otp = trim($_POST['otp']);
    if (!isset($_SESSION['temp_user']) || $entered_otp != $_SESSION['temp_user']['otp']) {
        $error = "Invalid OTP. Please try again.";
    } else {
        $payment_ref = '4178866'; // Specific payment reference code
        $stmt_insert = $conn->prepare("INSERT INTO users (full_name, username, email, phone, password, referrer_code, referrer_code_own, tier, payment_ref, payment_status, balance, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 0.00, NOW())");
        if (!$stmt_insert) {
            error_log("Database error: Unable to prepare user insert query: " . $conn->error);
            $error = "Database error occurred.";
        } else {
            $stmt_insert->bind_param("sssssssss", 
                $_SESSION['temp_user']['full_name'],
                $_SESSION['temp_user']['username'],
                $_SESSION['temp_user']['email'],
                $_SESSION['temp_user']['phone'],
                $_SESSION['temp_user']['password'],
                $_SESSION['temp_user']['referrer_code'],
                $_SESSION['temp_user']['referrer_code_own'],
                $_SESSION['temp_user']['tier'],
                $payment_ref
            );
            if ($stmt_insert->execute()) {
                $_SESSION['user_id'] = $stmt_insert->insert_id;
                $tier = $_SESSION['temp_user']['tier'];
                unset($_SESSION['temp_user']);
                header("Location: payment.php?tier=" . urlencode($tier) . "&ref=$payment_ref");
                exit();
            } else {
                error_log("Database error: User insert failed: " . $conn->error);
                $error = "Registration failed. Please try again.";
            }
            $stmt_insert->close();
        }
    }
}
?>
<?php include 'header.php'; ?>
<!-- Introduction Section -->
<section id="home" class="relative bg-gradient-to-br from-primary/5 to-emerald-50 overflow-hidden min-h-[85vh] flex items-center">
  <div class="absolute inset-0">
    <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='1920' height='1080'%3E%3Crect width='100%25' height='100%25' fill='%23f3f4f6'/%3E%3C/svg%3E"
         data-src="https://readdy.ai/api/search-image?query=young%20african%20students%20studying%20together%2C%20vibrant%20campus%20environment%2C%20modern%20tech%2C%20collaborative%2C%20bright%20colors%2C%20high-energy%2C%20inspirational&width=1920&height=1080&seq=hero1&orientation=landscape"
         data-srcset="https://readdy.ai/api/search-image?query=young%20african%20students%20studying%20together%2C%20vibrant%20campus%20environment%2C%20modern%20tech%2C%20collaborative%2C%20bright%20colors%2C%20high-energy%2C%20inspirational&width=768&height=432&seq=hero1&orientation=landscape 768w,
                      https://readdy.ai/api/search-image?query=young%20african%20students%20studying%20together%2C%20vibrant%20campus%20environment%2C%20modern%20tech%2C%20collaborative%2C%20bright%20colors%2C%20high-energy%2C%20inspirational&width=1200&height=675&seq=hero1&orientation=landscape 1200w,
                      https://readdy.ai/api/search-image?query=young%20african%20students%20studying%20together%2C%20vibrant%20campus%20environment%2C%20modern%20tech%2C%20collaborative%2C%20bright%20colors%2C%20high-energy%2C%20inspirational&width=1920&height=1080&seq=hero1&orientation=landscape 1920w"
         sizes="100vw"
         alt="Young African students studying together in a vibrant campus environment"
         class="w-full h-full object-cover object-center opacity-90 lazy-load"
         loading="lazy"
         aria-label="Vibrant campus environment with students">
  </div>
  <div class="relative w-full">
    <div class="max-w-7xl mx-auto px-4 text-center py-16 md:py-24">
      <h1 class="text-5xl md:text-7xl font-bold text-gray-900 mb-8 leading-tight">
        Earn Money While Studying with <span class="text-primary">CampusEarn</span>
      </h1>
      <p class="text-xl md:text-2xl text-gray-600 mb-12 leading-relaxed max-w-3xl mx-auto">
        Join thousands of Kenyan students earning through article writing and referrals. Start building your income today!
      </p>
      <div class="flex flex-col sm:flex-row gap-4 justify-center">
        <a href="<?php echo isset($_SESSION['user_id']) ? 'dashboard.php' : 'register.php'; ?>" 
           class="bg-primary text-white px-8 py-4 rounded-button font-semibold text-lg hover:bg-emerald-600 transition-colors whitespace-nowrap flex items-center justify-center" 
           aria-label="Start earning with CampusEarn">
          Start Earning Now <i class="ri-arrow-right-line ml-2"></i>
        </a>
        <a href="how_it_works.php" 
           class="bg-white border-2 border-gray-200 text-gray-700 px-8 py-4 rounded-button font-semibold text-lg hover:bg-gray-50 transition-colors whitespace-nowrap flex items-center justify-center" 
           aria-label="Learn more about CampusEarn">
          <i class="ri-information-line mr-2"></i> Learn More
        </a>
      </div>
    </div>
  </div>
</section>

<!-- Membership Tiers Section -->
<section id="tiers" class="px-4 py-12 bg-gradient-to-br from-primary/5 to-emerald-50 min-h-[85vh] flex items-center">
  <div class="max-w-7xl mx-auto">
    <div class="text-center mb-12">
      <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">Choose Your <span class="text-primary">CampusEarn</span> Membership Tier</h1>
      <p class="text-xl text-gray-600 max-w-3xl mx-auto">
        Unlock earning opportunities with referrals and article writing. Select the tier that suits your goals!
      </p>
      <?php if (isset($user_tier)) { ?>
        <p class="text-lg text-primary mt-4">You are currently a <span class="font-semibold"><?php echo ucfirst($user_tier); ?></span> member. Upgrade to unlock more benefits!</p>
      <?php } ?>
    </div>
    <!-- Desktop Table (Hidden on Mobile) -->
    <div class="hidden md:block overflow-x-auto">
      <table class="w-full text-left border-collapse bg-white rounded-2xl shadow-sm border border-gray-200">
        <thead>
          <tr class="bg-gray-100">
            <th class="p-6 text-gray-900 font-semibold">Feature</th>
            <th class="p-6 text-gray-900 font-semibold text-center">Bronze</th>
            <th class="p-6 text-gray-900 font-semibold text-center">Silver</th>
            <th class="p-6 text-gray-900 font-semibold text-center">Gold</th>
          </tr>
        </thead>
        <tbody>
          <tr class="border-b border-gray-200">
            <td class="p-6 text-gray-600">Membership Fee</td>
            <td class="p-6 text-center text-gray-900">KSh 500</td>
            <td class="p-6 text-center text-gray-900">KSh 750</td>
            <td class="p-6 text-center text-gray-900">KSh 1,000</td>
          </tr>
          <tr class="border-b border-gray-200">
            <td class="p-6 text-gray-600">Referral Earnings</td>
            <td class="p-6 text-center"><i class="ri-check-line text-primary mr-2"></i>20% commission</td>
            <td class="p-6 text-center"><i class="ri-check-line text-primary mr-2"></i>30% commission</td>
            <td class="p-6 text-center"><i class="ri-check-line text-primary mr-2"></i>45% commission</td>
          </tr>
          <tr class="border-b border-gray-200">
            <td class="p-6 text-gray-600">Article Writing</td>
            <td class="p-6 text-center"><i class="ri-close-line text-gray-400 mr-2"></i>Not available</td>
            <td class="p-6 text-center"><i class="ri-check-line text-primary mr-2"></i>KSh 300/article</td>
            <td class="p-6 text-center"><i class="ri-check-line text-primary mr-2"></i>KSh 500/article</td>
          </tr>
          <tr class="border-b border-gray-200">
            <td class="p-6 text-gray-600">Training & Support</td>
            <td class="p-6 text-center">Basic training</td>
            <td class="p-6 text-center">Advanced training</td>
            <td class="p-6 text-center">VIP training & dedicated support</td>
          </tr>
          <tr>
            <td class="p-6"></td>
            <td class="p-6 text-center">
              <a href="<?php echo isset($_SESSION['user_id']) ? 'dashboard.php' : 'register.php?tier=bronze'; ?>" 
                 class="bg-gray-300 text-gray-700 px-6 py-3 rounded-button font-semibold hover:bg-gray-200 transition-colors" 
                 aria-label="Join Bronze tier">
                Join Bronze
              </a>
            </td>
            <td class="p-6 text-center">
              <a href="<?php echo isset($_SESSION['user_id']) ? 'dashboard.php' : 'register.php?tier=silver'; ?>" 
                 class="bg-primary text-white px-6 py-3 rounded-button font-semibold hover:bg-emerald-600 transition-colors" 
                 aria-label="Join Silver tier">
                Join Silver
              </a>
            </td>
            <td class="p-6 text-center">
              <a href="<?php echo isset($_SESSION['user_id']) ? 'dashboard.php' : 'register.php?tier=gold'; ?>" 
                 class="bg-yellow-500 text-white px-6 py-3 rounded-button font-semibold hover:bg-yellow-600 transition-colors" 
                 aria-label="Join Gold tier">
                Join Gold
              </a>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <!-- Mobile Cards (Hidden on Desktop) -->
    <div class="md:hidden grid grid-cols-1 gap-6">
      <!-- Bronze Tier -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-2xl font-bold text-gray-900 mb-4 text-center">Bronze</h2>
        <ul class="space-y-4 mb-6">
          <li class="flex items-center justify-between">
            <span class="text-gray-600">Membership Fee</span>
            <span class="text-gray-900 font-semibold">KSh 500</span>
          </li>
          <li class="flex items-center justify-between">
            <span class="text-gray-600">Referral Earnings</span>
            <span><i class="ri-check-line text-primary mr-2"></i>20% commission</span>
          </li>
          <li class="flex items-center justify-between">
            <span class="text-gray-600">Article Writing</span>
            <span><i class="ri-close-line text-gray-400 mr-2"></i>Not available</span>
          </li>
          <li class="flex items-center justify-between">
            <span class="text-gray-600">Training & Support</span>
            <span>Basic training</span>
          </li>
        </ul>
        <a href="<?php echo isset($_SESSION['user_id']) ? 'dashboard.php' : 'register.php?tier=bronze'; ?>" 
                 class="bg-gray-300 text-gray-700 px-6 py-3 rounded-button font-semibold hover:bg-gray-200 transition-colors" 
                 aria-label="Join Bronze tier">
                Join Bronze
              </a>
      </div>
      <!-- Silver Tier -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-2xl font-bold text-gray-900 mb-4 text-center">Silver</h2>
        <ul class="space-y-4 mb-6">
          <li class="flex items-center justify-between">
            <span class="text-gray-600">Membership Fee</span>
            <span class="text-gray-900 font-semibold">KSh 750</span>
          </li>
          <li class="flex items-center justify-between">
            <span class="text-gray-600">Referral Earnings</span>
            <span><i class="ri-check-line text-primary mr-2"></i>30% commission</span>
          </li>
          <li class="flex items-center justify-between">
            <span class="text-gray-600">Article Writing</span>
            <span><i class="ri-check-line text-primary mr-2"></i>KSh 300/article</span>
          </li>
          <li class="flex items-center justify-between">
            <span class="text-gray-600">Training & Support</span>
            <span>Advanced training</span>
          </li>
        </ul>
        <a href="<?php echo isset($_SESSION['user_id']) ? 'dashboard.php' : 'register.php?tier=silver'; ?>" 
                 class="bg-primary text-white px-6 py-3 rounded-button font-semibold hover:bg-emerald-600 transition-colors" 
                 aria-label="Join Silver tier">
                Join Silver
              </a>
      </div>
      <!-- Gold Tier -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-2xl font-bold text-gray-900 mb-4 text-center">Gold</h2>
        <ul class="space-y-4 mb-6">
          <li class="flex items-center justify-between">
            <span class="text-gray-600">Membership Fee</span>
            <span class="text-gray-900 font-semibold">KSh 1,000</span>
          </li>
          <li class="flex items-center justify-between">
            <span class="text-gray-600">Referral Earnings</span>
            <span><i class="ri-check-line text-primary mr-2"></i>45% commission</span>
          </li>
          <li class="flex items-center justify-between">
            <span class="text-gray-600">Article Writing</span>
            <span><i class="ri-check-line text-primary mr-2"></i>KSh 500/article</span>
          </li>
          <li class="flex items-center justify-between">
            <span class="text-gray-600">Training & Support</span>
            <span>VIP training & dedicated support</span>
          </li>
        </ul>
        <a href="<?php echo isset($_SESSION['user_id']) ? 'dashboard.php' : 'register.php?tier=gold'; ?>" 
                 class="bg-yellow-500 text-white px-6 py-3 rounded-button font-semibold hover:bg-yellow-600 transition-colors" 
                 aria-label="Join Gold tier">
                Join Gold
              </a>
      </div>
    </div>
    <div class="mt-8 text-center">
      <a href="<?php echo isset($_SESSION['user_id']) ? 'dashboard.php' : 'register.php'; ?>" 
         class="bg-primary text-white px-8 py-4 rounded-button font-semibold text-lg hover:bg-emerald-600 transition-colors" 
         aria-label="Get started with CampusEarn">
        Get Started Now <i class="ri-arrow-right-line ml-2"></i>
      </a>
    </div>
  </div>
</section>
<!-- Article Writing Opportunities Section -->
<section id="articles" class="px-4 py-12 bg-gray-50">
  <div class="max-w-7xl mx-auto">
    <div class="text-center mb-12">
      <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Earn by Writing Articles</h2>
      <p class="text-xl text-gray-600 max-w-3xl mx-auto">
        Create engaging content and get paid KSh 300 (Silver) or KSh 500 (Gold) per article.
      </p>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-xl font-bold text-gray-900 mb-4">How It Works</h3>
        <p class="text-gray-600 mb-6">
          Submit articles on topics like entrepreneurship, student life, or product promotions. Approved articles earn you instant payments via M-PESA.
        </p>
        <ul class="space-y-3 mb-6">
          <li class="flex items-center">
            <i class="ri-check-line text-primary mr-3"></i>
            <span>Choose from pre-approved topics</span>
          </li>
          <li class="flex items-center">
            <i class="ri-check-line text-primary mr-3"></i>
            <span>Submit via our online portal</span>
          </li>
          <li class="flex items-center">
            <i class="ri-check-line text-primary mr-3"></i>
            <span>Get paid within 24 hours</span>
          </li>
        </ul>
        <a href="<?php echo isset($_SESSION['user_id']) ? 'write_article.php' : 'register.php'; ?>" 
           class="bg-primary text-white px-6 py-3 rounded-button font-semibold hover:bg-emerald-600 transition-colors" 
           aria-label="Start writing articles">
          Start Writing
        </a>
      </div>
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-xl font-bold text-gray-900 mb-4">Tips for Success</h3>
        <p class="text-gray-600 mb-6">
          Maximize your earnings with these proven strategies for writing high-quality articles.
        </p>
        <ul class="space-y-3 mb-6">
          <li class="flex items-center">
            <i class="ri-check-line text-primary mr-3"></i>
            <span>Use clear, engaging headlines</span>
          </li>
          <li class="flex items-center">
            <i class="ri-check-line text-primary mr-3"></i>
            <span>Incorporate student-relevant examples</span>
          </li>
          <li class="flex items-center">
            <i class="ri-check-line text-primary mr-3"></i>
            <span>Optimize with SEO keywords</span>
          </li>
        </ul>
        <a href="how_it_works.php#article-tips" 
           class="bg-secondary text-white px-6 py-3 rounded-button font-semibold hover:bg-yellow-600 transition-colors" 
           aria-label="Learn more about article writing">
          Learn More
        </a>
      </div>
    </div>
  </div>
</section>
<!-- Referral Program Section -->
<section id="how-it-works" class="px-4 py-12 bg-white">
  <div class="max-w-7xl mx-auto">
    <div class="text-center mb-12">
      <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Grow Your Network, Grow Your Earnings</h2>
      <p class="text-xl text-gray-600 max-w-3xl mx-auto">
        Invite friends to join CampusEarn and earn commissions on their memberships.
      </p>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <div class="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center mb-4">
          <i class="ri-share-line text-primary text-2xl"></i>
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-2">Share Your Link</h3>
        <p class="text-gray-600">
          Get a unique referral link to share with classmates via WhatsApp, social media, or email.
        </p>
      </div>
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <div class="w-12 h-12 bg-secondary/10 rounded-lg flex items-center justify-center mb-4">
          <i class="ri-user-add-line text-secondary text-2xl"></i>
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-2">Invite Friends</h3>
        <p class="text-gray-600">
          Earn 20% (Bronze), 30% (Silver), or 45% (Gold) commission on every membership fee paid by your referrals.
        </p>
      </div>
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mb-4">
          <i class="ri-wallet-3-line text-purple-600 text-2xl"></i>
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-2">Get Paid</h3>
        <p class="text-gray-600">
          Receive weekly M-PESA payments for your referral earnings, with no minimum threshold.
        </p>
      </div>
    </div>
    <div class="mt-8 text-center">
      <a href="<?php echo isset($_SESSION['user_id']) ? 'dashboard.php#referral-link' : 'register.php'; ?>" 
         class="bg-primary text-white px-8 py-4 rounded-button font-semibold text-lg hover:bg-emerald-600 transition-colors" 
         aria-label="Get your referral link">
        Get Your Referral Link
      </a>
    </div>
  </div>
</section>
<!-- Testimonials Section -->
<section class="px-4 py-12 bg-gray-50">
  <div class="max-w-7xl mx-auto">
    <div class="text-center mb-12">
      <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Student Success Stories</h2>
      <p class="text-xl text-gray-600">
        Hear from students who are earning with CampusEarn
      </p>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center mb-4">
          <div class="w-12 h-12 bg-gradient-to-br from-primary to-emerald-600 rounded-full flex items-center justify-center mr-4">
            <span class="text-white font-bold">JK</span>
          </div>
          <div>
            <h4 class="font-semibold text-gray-900">Joyce Kamau</h4>
            <p class="text-sm text-gray-600">University of Nairobi</p>
          </div>
        </div>
        <p class="text-gray-700 mb-4">
          "I earned KSh 10,000 in my first month by referring friends to the Gold tier. It's so easy to share my link!"
        </p>
        <div class="flex items-center text-sm text-gray-500">
          <i class="ri-star-fill text-yellow-400 mr-1"></i>
          <span>Gold Tier Member</span>
        </div>
      </div>
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center mb-4">
          <div class="w-12 h-12 bg-gradient-to-br from-secondary to-orange-500 rounded-full flex items-center justify-center mr-4">
            <span class="text-white font-bold">SM</span>
          </div>
          <div>
            <h4 class="font-semibold text-gray-900">Samuel Mutua</h4>
            <p class="text-sm text-gray-600">Kenyatta University</p>
          </div>
        </div>
        <p class="text-gray-700 mb-4">
          "Writing articles for the Silver tier helped me earn KSh 9,000 last month while improving my writing skills."
        </p>
        <div class="flex items-center text-sm text-gray-500">
          <i class="ri-medal-2-line text-gray-400 mr-1"></i>
          <span>Silver Tier Member</span>
        </div>
      </div>
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center mb-4">
          <div class="w-12 h-12 bg-gradient-to-br from-purple-600 to-indigo-600 rounded-full flex items-center justify-center mr-4">
            <span class="text-white font-bold">LN</span>
          </div>
          <div>
            <h4 class="font-semibold text-gray-900">Linda Njeri</h4>
            <p class="text-sm text-gray-600">Strathmore University</p>
          </div>
        </div>
        <p class="text-gray-700 mb-4">
          "As a Bronze member, I earned KSh 2,500 by referring classmates. It's a great way to make money on campus!"
        </p>
        <div class="flex items-center text-sm text-gray-500">
          <i class="ri-medal-line text-orange-400 mr-1"></i>
          <span>Bronze Tier Member</span>
        </div>
      </div>
    </div>
  </div>
</section>
<section id="signup" class="px-4 py-16 bg-gradient-to-r from-primary to-emerald-600 text-white">
  <div class="max-w-4xl mx-auto text-center">
    <h2 class="text-3xl md:text-5xl font-bold mb-6">
      Start Earning Today!
    </h2>
    <p class="text-xl md:text-2xl mb-8 text-emerald-100">
      Join CampusEarn and turn your campus life into a money-making opportunity with referrals and article writing.
    </p>
    <div class="bg-white rounded-2xl p-8 max-w-md mx-auto shadow-lg">
      <h3 class="text-2xl font-bold text-gray-900 mb-6">Get Started Now</h3>
      <div class="space-y-4">
        <a href="register.php" class="w-full bg-primary text-white px-6 py-3 rounded-button font-semibold hover:bg-emerald-600 transition-colors inline-block" aria-label="Register for CampusEarn">
          Register
        </a>
        <a href="login.php" class="w-full bg-primary text-white px-6 py-3 rounded-button font-semibold hover:bg-emerald-600 transition-colors inline-block" aria-label="Log in to CampusEarn">
          Log In
        </a>
      </div>
      <p class="text-sm text-gray-600 mt-4">
        By signing up or logging in, you agree to our <a href="terms.php" class="text-primary hover:underline">Terms of Service</a> and <a href="privacy.php" class="text-primary hover:underline">Privacy Policy</a>.
      </p>
    </div>
  </div>
</section>
<?php include 'footer.php'; ?>
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
            if (img.dataset.srcset) img.srcset = img.dataset.srcset;
            img.classList.remove('lazy-load');
            observer.unobserve(img);
          }
        });
      });
      images.forEach(img => observer.observe(img));
    } else {
      images.forEach(img => {
        img.src = img.dataset.src;
        if (img.dataset.srcset) img.srcset = img.dataset.srcset;
        img.classList.remove('lazy-load');
      });
    }
  });
</script>