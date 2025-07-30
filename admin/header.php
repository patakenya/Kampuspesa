<?php

require_once '../config.php';

// Skip session check for register and login pages
$current_page = basename($_SERVER['PHP_SELF']);
$public_pages = ['register.php', 'login.php'];
if (!in_array($current_page, $public_pages) && !isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch admin data
$admin = null;
if (isset($_SESSION['admin_id'])) {
    $stmt = $conn->prepare("SELECT full_name FROM admins WHERE id = ?");
    if (!$stmt) {
        error_log("Database error: Unable to prepare admin query: " . $conn->error);
    } else {
        $stmt->bind_param("i", $_SESSION['admin_id']);
        $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CampusEarn Admin - <?php echo htmlspecialchars($admin['full_name'] ?? 'Admin'); ?></title>
    <link rel="icon" href="../fav.jpg" type="image/jpg">
    <script src="https://cdn.tailwindcss.com/3.4.16"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Pacifico&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css">
    <link rel="stylesheet" href="../style.css">
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
    <header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-50">
        <div class="px-4 py-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <h1 class="text-2xl font-['Pacifico'] text-primary">CampusEarn Admin</h1>
                </div>
                <nav class="hidden md:flex items-center space-x-6">
                    <?php if (!in_array($current_page, $public_pages)) { ?>
                        <a href="dashboard.php" class="text-gray-600 hover:text-primary font-medium" aria-label="Admin Dashboard">Dashboard</a>
                        <a href="users.php" class="text-gray-600 hover:text-primary font-medium" aria-label="Manage Users">Users</a>
                        <a href="articles.php" class="text-gray-600 hover:text-primary font-medium" aria-label="Manage Articles">Articles</a>
                        <a href="transactions.php" class="text-gray-600 hover:text-primary font-medium" aria-label="Manage Transactions">Transactions</a>
                        <div class="relative group">
                            <button class="flex items-center space-x-2 text-gray-600 hover:text-primary font-medium focus:outline-none" aria-haspopup="true" aria-expanded="false">
                                <span><?php echo htmlspecialchars($admin['full_name'] ?? 'Admin'); ?></span>
                                <i class="ri-arrow-down-s-line"></i>
                            </button>
                            <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 hidden group-hover:block z-50">
                                <a href="logout.php" class="block px-4 py-2 text-gray-600 hover:bg-gray-100 hover:text-primary" aria-label="Log Out">Log Out</a>
                            </div>
                        </div>
                    <?php } else { ?>
                        <a href="login.php" class="text-gray-600 hover:text-primary font-medium" aria-label="Admin Login">Login</a>
                        <a href="register.php" class="text-gray-600 hover:text-primary font-medium" aria-label="Admin Register">Register</a>
                    <?php } ?>
                </nav>
                <button class="md:hidden text-gray-600 hover:text-primary focus:outline-none" id="mobile-menu-button" aria-label="Toggle mobile menu">
                    <i class="ri-menu-line text-2xl"></i>
                </button>
            </div>
            <nav class="md:hidden fixed inset-y-0 right-0 w-64 bg-white p-4 shadow-lg border-l border-gray-200 transform translate-x-full transition-transform duration-300 ease-in-out" id="mobile-menu" aria-label="Mobile navigation">
                <div class="flex justify-end">
                    <button class="text-gray-600 hover:text-primary" id="close-mobile-menu" aria-label="Close mobile menu">
                        <i class="ri-close-line text-2xl"></i>
                    </button>
                </div>
                <div class="flex flex-col space-y-4 mt-4">
                    <?php if (!in_array($current_page, $public_pages)) { ?>
                        <a href="dashboard.php" class="text-gray-600 hover:text-primary font-medium" aria-label="Admin Dashboard">Dashboard</a>
                        <a href="users.php" class="text-gray-600 hover:text-primary font-medium" aria-label="Manage Users">Users</a>
                        <a href="articles.php" class="text-gray-600 hover:text-primary font-medium" aria-label="Manage Articles">Articles</a>
                        <a href="transactions.php" class="text-gray-600 hover:text-primary font-medium" aria-label="Manage Transactions">Transactions</a>
                        <div class="border-t border-gray-200 pt-4">
                            <p class="text-sm text-gray-600">Signed in as <span class="font-semibold"><?php echo htmlspecialchars($admin['full_name'] ?? 'Admin'); ?></span></p>
                        </div>
                        <a href="logout.php" class="text-gray-600 hover:text-primary font-medium" aria-label="Log Out">Log Out</a>
                    <?php } else { ?>
                        <a href="login.php" class="text-gray-600 hover:text-primary font-medium" aria-label="Admin Login">Login</a>
                        <a href="register.php" class="text-gray-600 hover:text-primary font-medium" aria-label="Admin Register">Register</a>
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

        document.addEventListener('click', (event) => {
            if (!mobileMenu.contains(event.target) && !mobileMenuButton.contains(event.target)) {
                mobileMenu.classList.add('translate-x-full');
            }
        });
    });
    </script>
</body>
</html>