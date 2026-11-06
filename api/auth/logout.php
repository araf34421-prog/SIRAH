<?php
require_once __DIR__ . '/../../includes/auth.php';
startAppSession();
session_destroy();
header("Location: login.php");
exit;
?>