<?php
session_start();
session_destroy();
header('Location: /courier_cms/login.php');
exit();
?>