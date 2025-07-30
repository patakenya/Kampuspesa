<?php
session_start();

// Clear admin session
unset($_SESSION['admin_id']);
session_destroy();

header("Location: login.php");
exit();
?>