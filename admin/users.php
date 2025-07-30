<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Handle user deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_user'])) {
    $user_id = intval($_POST['user_id']);
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: users.php");
    exit();
}

// Fetch users
$users = $conn->query("SELECT id, full_name, email, tier, created_at FROM users ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
?>
<?php include 'header.php'; ?>
<section class="px-4 py-12 bg-gray-50">
  <div class="max-w-7xl mx-auto">
    <h1 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-6 text-center">Manage Users</h1>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
      <table class="w-full text-left">
        <thead>
          <tr class="border-b border-gray-200">
            <th class="py-3 px-4 text-gray-700 font-semibold">Full Name</th>
            <th class="py-3 px-4 text-gray-700 font-semibold">Email</th>
            <th class="py-3 px-4 text-gray-700 font-semibold">Tier</th>
            <th class="py-3 px-4 text-gray-700 font-semibold">Joined</th>
            <th class="py-3 px-4 text-gray-700 font-semibold">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($users)) { ?>
            <tr>
              <td colspan="5" class="py-4 px-4 text-gray-600 text-center">No users found.</td>
            </tr>
          <?php } else { ?>
            <?php foreach ($users as $user) { ?>
              <tr class="border-b border-gray-200">
                <td class="py-3 px-4"><?php echo htmlspecialchars($user['full_name']); ?></td>
                <td class="py-3 px-4"><?php echo htmlspecialchars($user['email']); ?></td>
                <td class="py-3 px-4"><?php echo ucfirst($user['tier']); ?></td>
                <td class="py-3 px-4"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                <td class="py-3 px-4">
                  <form method="POST" onsubmit="return confirm('Are you sure you want to delete this user?');">
                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                    <button type="submit" name="delete_user" class="text-red-500 hover:text-red-700" aria-label="Delete user <?php echo htmlspecialchars($user['full_name']); ?>">
                      <i class="ri-delete-bin-line"></i>
                    </button>
                  </form>
                </td>
              </tr>
            <?php } ?>
          <?php } ?>
        </tbody>
      </table>
    </div>
  </div>
</section>
<?php include '../footer.php'; ?>