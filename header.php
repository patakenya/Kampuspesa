<?php

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CampusEarn - Student Earning Platform</title>
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
      <div class="flex items-center space-x-4">
        <h1 class="text-2xl font-['Pacifico'] text-primary">CampusEarn</h1>
      </div>
      <!-- Desktop Navigation -->
      <nav class="hidden md:flex space-x-6">
        <a href="index.php#home" class="text-gray-600 hover:text-primary font-medium">Home</a>
        <a href="how_it_works.php" class="text-gray-600 hover:text-primary font-medium">How It Works</a>
        <a href="index.php#tiers" class="text-gray-600 hover:text-primary font-medium">Membership Tiers</a>
        <a href="articles.php" class="text-gray-600 hover:text-primary font-medium">Articles</a>
        <?php if (isset($_SESSION['user_id'])) { ?>
          <a href="dashboard.php" class="text-gray-600 hover:text-primary font-medium">Dashboard</a>
          <a href="logout.php" class="text-gray-600 hover:text-primary font-medium">Log Out</a>
        <?php } else { ?>
          <a href="register.php" class="text-gray-600 hover:text-primary font-medium">Sign Up</a>
          <a href="login.php" class="text-gray-600 hover:text-primary font-medium">Log In</a>
        <?php } ?>
      </nav>
      <!-- Desktop Buttons -->
      <div class="hidden md:flex items-center space-x-4">
        <?php if (isset($_SESSION['user_id'])) { ?>
          <a href="dashboard.php" class="bg-primary text-white px-4 py-2 rounded-button font-semibold hover:bg-emerald-600 transition-colors">Dashboard</a>
        <?php } else { ?>
          <a href="register.php" class="bg-primary text-white px-4 py-2 rounded-button font-semibold hover:bg-emerald-600 transition-colors">Register</a>
          <a href="login.php" class="bg-white border-2 border-primary text-primary px-4 py-2 rounded-button font-semibold hover:bg-emerald-50 transition-colors">Log In</a>
        <?php } ?>
      </div>
      <!-- Mobile Menu Button -->
      <button class="md:hidden text-gray-600 hover:text-primary focus:outline-none" id="mobile-menu-button">
        <i class="ri-menu-line text-2xl"></i>
      </button>
    </div>
    <!-- Mobile Navigation -->
    <nav class="md:hidden hidden flex-col space-y-4 mt-4 bg-white p-4 rounded-lg shadow-sm border border-gray-200" id="mobile-menu">
      <a href="index.php#home" class="text-gray-600 hover:text-primary font-medium">Home</a>
      <a href="how_it_works.php" class="text-gray-600 hover:text-primary font-medium">How It Works</a>
      <a href="index.php#tiers" class="text-gray-600 hover:text-primary font-medium">Membership Tiers</a>
      <a href="articles.php" class="text-gray-600 hover:text-primary font-medium">Articles</a>
      <?php if (isset($_SESSION['user_id'])) { ?>
        <a href="dashboard.php" class="text-gray-600 hover:text-primary font-medium">Dashboard</a>
        <a href="logout.php" class="text-gray-600 hover:text-primary font-medium">Log Out</a>
      <?php } else { ?>
        <a href="register.php" class="text-gray-600 hover:text-primary font-medium">Sign Up</a>
        <a href="login.php" class="text-gray-600 hover:text-primary font-medium">Log In</a>
      <?php } ?>
      <div class="flex flex-col space-y-2">
        <?php if (isset($_SESSION['user_id'])) { ?>
          <a href="dashboard.php" class="bg-primary text-white px-4 py-2 rounded-button font-semibold hover:bg-emerald-600 transition-colors text-center">Dashboard</a>
        <?php } else { ?>
          <a href="register.php" class="bg-primary text-white px-4 py-2 rounded-button font-semibold hover:bg-emerald-600 transition-colors text-center">Register</a>
          <a href="login.php" class="bg-white border-2 border-primary text-primary px-4 py-2 rounded-button font-semibold hover:bg-emerald-50 transition-colors text-center">Log In</a>
        <?php } ?>
      </div>
    </nav>
  </div>
</header>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const mobileMenuButton = document.getElementById('mobile-menu-button');
  const mobileMenu = document.getElementById('mobile-menu');
  mobileMenuButton.addEventListener('click', () => {
    mobileMenu.classList.toggle('hidden');
  });
});
</script>