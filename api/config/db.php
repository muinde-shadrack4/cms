<?php
$host     = "localhost";
$user     = "root";
$password = "root123";
$database = "courier_cms";

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
```

Press **Ctrl+S**.

Then test it — go to browser:
```
http://localhost/courier_cms/login.php