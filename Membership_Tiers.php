<?php
session_start();
require_once 'config.php';

// Check user status for personalized content
$user_tier = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT tier FROM users WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $user_tier = $user['tier'] ?? null;
        $stmt->close();
    } else {
        error_log("Database error: Unable to prepare user tier query: " . $conn->error);
    }
}
?>
<?php include 'header.php'; ?>
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
              <a href="<?php echo isset($_SESSION['user_id']) ? ($user_tier === 'bronze' ? 'dashboard.php' : 'payment.php?tier=bronze') : 'register.php?tier=bronze'; ?>" 
                 class="bg-gray-300 text-gray-700 px-6 py-3 rounded-button font-semibold hover:bg-gray-200 transition-colors <?php echo $user_tier === 'bronze' ? 'opacity-50 cursor-not-allowed' : ''; ?>" 
                 aria-label="Join or manage Bronze tier" 
                 <?php echo $user_tier === 'bronze' ? 'disabled' : ''; ?>>
                <?php echo isset($_SESSION['user_id']) && $user_tier === 'bronze' ? 'Current Tier' : 'Join Bronze'; ?>
              </a>
            </td>
            <td class="p-6 text-center">
              <a href="<?php echo isset($_SESSION['user_id']) ? ($user_tier === 'silver' ? 'dashboard.php' : 'payment.php?tier=silver') : 'register.php?tier=silver'; ?>" 
                 class="bg-primary text-white px-6 py-3 rounded-button font-semibold hover:bg-emerald-600 transition-colors <?php echo $user_tier === 'silver' ? 'opacity-50 cursor-not-allowed' : ''; ?>" 
                 aria-label="Join or manage Silver tier" 
                 <?php echo $user_tier === 'silver' ? 'disabled' : ''; ?>>
                <?php echo isset($_SESSION['user_id']) && $user_tier === 'silver' ? 'Current Tier' : 'Join Silver'; ?>
              </a>
            </td>
            <td class="p-6 text-center">
              <a href="<?php echo isset($_SESSION['user_id']) ? ($user_tier === 'gold' ? 'dashboard.php' : 'payment.php?tier=gold') : 'register.php?tier=gold'; ?>" 
                 class="bg-yellow-500 text-white px-6 py-3 rounded-button font-semibold hover:bg-yellow-600 transition-colors <?php echo $user_tier === 'gold' ? 'opacity-50 cursor-not-allowed' : ''; ?>" 
                 aria-label="Join or manage Gold tier" 
                 <?php echo $user_tier === 'gold' ? 'disabled' : ''; ?>>
                <?php echo isset($_SESSION['user_id']) && $user_tier === 'gold' ? 'Current Tier' : 'Join Gold'; ?>
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
        <a href="<?php echo isset($_SESSION['user_id']) ? ($user_tier === 'bronze' ? 'dashboard.php' : 'payment.php?tier=bronze') : 'register.php?tier=bronze'; ?>" 
           class="block text-center bg-gray-300 text-gray-700 px-6 py-3 rounded-button font-semibold hover:bg-gray-200 transition-colors <?php echo $user_tier === 'bronze' ? 'opacity-50 cursor-not-allowed' : ''; ?>" 
           aria-label="Join or manage Bronze tier" 
           <?php echo $user_tier === 'bronze' ? 'disabled' : ''; ?>>
          <?php echo isset($_SESSION['user_id']) && $user_tier === 'bronze' ? 'Current Tier' : 'Join Bronze'; ?>
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
        <a href="<?php echo isset($_SESSION['user_id']) ? ($user_tier === 'silver' ? 'dashboard.php' : 'payment.php?tier=silver') : 'register.php?tier=silver'; ?>" 
           class="block text-center bg-primary text-white px-6 py-3 rounded-button font-semibold hover:bg-emerald-600 transition-colors <?php echo $user_tier === 'silver' ? 'opacity-50 cursor-not-allowed' : ''; ?>" 
           aria-label="Join or manage Silver tier" 
           <?php echo $user_tier === 'silver' ? 'disabled' : ''; ?>>
          <?php echo isset($_SESSION['user_id']) && $user_tier === 'silver' ? 'Current Tier' : 'Join Silver'; ?>
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
        <a href="<?php echo isset($_SESSION['user_id']) ? ($user_tier === 'gold' ? 'dashboard.php' : 'payment.php?tier=gold') : 'register.php?tier=gold'; ?>" 
           class="block text-center bg-yellow-500 text-white px-6 py-3 rounded-button font-semibold hover:bg-yellow-600 transition-colors <?php echo $user_tier === 'gold' ? 'opacity-50 cursor-not-allowed' : ''; ?>" 
           aria-label="Join or manage Gold tier" 
           <?php echo $user_tier === 'gold' ? 'disabled' : ''; ?>>
          <?php echo isset($_SESSION['user_id']) && $user_tier === 'gold' ? 'Current Tier' : 'Join Gold'; ?>
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
<?php include 'footer.php'; ?>