<?php

require_once 'config.php';

// Fetch user data for signed-in users
$user = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT username, tier FROM users WHERE id = ?");
    if (!$stmt) {
        error_log("Database error: Unable to prepare user query: " . $conn->error);
    } else {
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CampusEarn - Student Earning Platform</title>
    <link rel="icon" href="fav.jpg" type="image/jpg">
    <script src="https://cdn.tailwindcss.com/3.4.16"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Pacifico&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css">
    <link rel="stylesheet" href="style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/echarts/5.5.0/echarts.min.js"></script>
    <script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    primary: '#10b981',
                    secondary: '#f59e0b',
                    accent: '#6b7280'
                },
                borderRadius: {
                    'none': '0px',
                    'sm': '4px',
                    DEFAULT: '8px',
                    'md': '12px',
                    'lg': '16px',
                    'xl': '20px',
                    '2xl': '24px',
                    '3xl': '32px',
                    'full': '9999px',
                    'button': '8px'
                },
                fontFamily: {
                    'inter': ['Inter', 'sans-serif']
                }
            }
        }
    }
    </script>
</head>
<body class="bg-gray-50 min-h-screen font-inter">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-50">
        <div class="px-4 py-3">
            <div class="flex items-center justify-between">
                <!-- Logo -->
                 <img src="logo.png" alt="logo" height="40" class="h-10">
                <div class="flex items-center space-x-4">
                    <h1 class="text-2xl font-['Pacifico'] text-primary">CampusEarn</h1>
                </div>
                <!-- Desktop Navigation -->
                <nav class="hidden md:flex items-center space-x-6">
                    <a href="<?php echo isset($_SESSION['user_id']) ? 'dashboard.php' : 'index.php#home'; ?>" 
                       class="text-gray-600 hover:text-primary font-medium" 
                       aria-label="Home">Home</a>
                    <a href="how_it_works.php" 
                       class="text-gray-600 hover:text-primary font-medium" 
                       aria-label="How It Works">How It Works</a>
                    <a href="Membership_Tiers.php" 
                       class="text-gray-600 hover:text-primary font-medium" 
                       aria-label="Membership Tiers">Membership Tiers</a>
                    <a href="articles.php" 
                       class="text-gray-600 hover:text-primary font-medium" 
                       aria-label="Articles">Articles</a>
                    <?php if (isset($_SESSION['user_id']) && in_array($user['tier'], ['silver', 'gold'])) { ?>
                        <a href="write_article.php" 
                           class="text-gray-600 hover:text-primary font-medium" 
                           aria-label="Write Article">Write Article</a>
                    <?php } ?>
                    <?php if (isset($_SESSION['user_id'])) { ?>
                        <!-- User profile dropdown for signed-in users -->
                        <div class="relative group">
                            <button class="flex items-center space-x-2 text-gray-600 hover:text-primary font-medium focus:outline-none" 
                                    aria-haspopup="true" 
                                    aria-expanded="false">
                                <span><?php echo htmlspecialchars($user['username'] ?? 'User'); ?></span>
                                <i class="ri-arrow-down-s-line"></i>
                            </button>
                            <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 hidden group-hover:block z-50">
                                <div class="p-4">
                                    <p class="text-sm text-gray-600">Membership: 
                                        <span class="font-semibold text-primary"><?php echo ucfirst($user['tier'] ?? 'Unknown'); ?></span>
                                    </p>
                                </div>
                                <a href="dashboard.php" 
                                   class="block px-4 py-2 text-gray-600 hover:bg-gray-100 hover:text-primary" 
                                   aria-label="Dashboard">Dashboard</a>
                                <a href="logout.php" 
                                   class="block px-4 py-2 text-gray-600 hover:bg-gray-100 hover:text-primary" 
                                   aria-label="Log Out">Log Out</a>
                            </div>
                        </div>
                    <?php } else { ?>
                        <!-- Buttons for signed-out users -->
                        <a href="register.php" 
                           class="bg-primary text-white px-4 py-2 rounded-button font-semibold hover:bg-emerald-600 transition-colors" 
                           aria-label="Register">Register</a>
                        <a href="login.php" 
                           class="bg-white border-2 border-primary text-primary px-4 py-2 rounded-button font-semibold hover:bg-emerald-50 transition-colors" 
                           aria-label="Log In">Log In</a>
                    <?php } ?>
                </nav>
                <!-- Mobile Menu Button -->
                <button class="md:hidden text-gray-600 hover:text-primary focus:outline-none" 
                        id="mobile-menu-button" 
                        aria-label="Toggle mobile menu">
                    <i class="ri-menu-line text-2xl"></i>
                </button>
            </div>
            <!-- Mobile Navigation -->
            <nav class="md:hidden fixed inset-y-0 right-0 w-64 bg-white p-4 shadow-lg border-l border-gray-200 transform translate-x-full transition-transform duration-300 ease-in-out" 
                 id="mobile-menu" 
                 aria-label="Mobile navigation">
                <div class="flex justify-end">
                    <button class="text-gray-600 hover:text-primary" 
                            id="close-mobile-menu" 
                            aria-label="Close mobile menu">
                        <i class="ri-close-line text-2xl"></i>
                    </button>
                </div>
                <div class="flex flex-col space-y-4 mt-4">
                    <a href="<?php echo isset($_SESSION['user_id']) ? 'dashboard.php' : 'index.php#home'; ?>" 
                       class="text-gray-600 hover:text-primary font-medium" 
                       aria-label="Home">Home</a>
                    <a href="how_it_works.php" 
                       class="text-gray-600 hover:text-primary font-medium" 
                       aria-label="How It Works">How It Works</a>
                    <a href="Membership_Tiers.php" 
                       class="text-gray-600 hover:text-primary font-medium" 
                       aria-label="Membership Tiers">Membership Tiers</a>
                    <a href="articles.php" 
                       class="text-gray-600 hover:text-primary font-medium" 
                       aria-label="Articles">Articles</a>
                    <?php if (isset($_SESSION['user_id']) && in_array($user['tier'], ['silver', 'gold'])) { ?>
                        <a href="write_article.php" 
                           class="text-gray-600 hover:text-primary font-medium" 
                           aria-label="Write Article">Write Article</a>
                    <?php } ?>
                    <?php if (isset($_SESSION['user_id'])) { ?>
                        <!-- User profile section for signed-in users -->
                        <div class="border-t border-gray-200 pt-4">
                            <p class="text-sm text-gray-600">Signed in as 
                                <span class="font-semibold"><?php echo htmlspecialchars($user['username'] ?? 'User'); ?></span>
                            </p>
                            <p class="text-sm text-gray-600">Membership: 
                                <span class="font-semibold text-primary"><?php echo ucfirst($user['tier'] ?? 'Unknown'); ?></span>
                            </p>
                        </div>
                        <a href="dashboard.php" 
                           class="text-gray-600 hover:text-primary font-medium" 
                           aria-label="Dashboard">Dashboard</a>
                        <a href="logout.php" 
                           class="text-gray-600 hover:text-primary font-medium" 
                           aria-label="Log Out">Log Out</a>
                    <?php } else { ?>
                        <!-- Buttons for signed-out users -->
                        <a href="register.php" 
                           class="bg-primary text-white px-4 py-2 rounded-button font-semibold hover:bg-emerald-600 transition-colors text-center" 
                           aria-label="Register">Register</a>
                        <a href="login.php" 
                           class="bg-white border-2 border-primary text-primary px-4 py-2 rounded-button font-semibold hover:bg-emerald-50 transition-colors text-center" 
                           aria-label="Log In">Log In</a>
                    <?php } ?>
                </div>
            </nav>
        </div>
    </header>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const closeMobileMenuButton = document.getElementById('close-mobile-menu');
        const mobileMenu = document.getElementById('mobile-menu');

        mobileMenuButton.addEventListener('click', () => {
            mobileMenu.classList.toggle('translate-x-full');
        });

        closeMobileMenuButton.addEventListener('click', () => {
            mobileMenu.classList.add('translate-x-full');
        });

        // Close mobile menu when clicking outside
        document.addEventListener('click', (event) => {
            if (!mobileMenu.contains(event.target) && !mobileMenuButton.contains(event.target)) {
                mobileMenu.classList.add('translate-x-full');
            }
        });
    });
    </script>
</body>
</html>