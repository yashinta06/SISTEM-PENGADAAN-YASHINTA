<?php
session_start();
session_destroy();

// Gunakan path absolut
header("Location: /uaspengadaan/auth/login.php");
exit();
?>