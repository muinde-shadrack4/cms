<?php
require_once 'db.php';
require_once 'auth.php';


// If already logged in redirect to dashboard
if (is_logged_in()) {
    redirect_to_dashboard();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role     = $_POST['role'];

    // Check staff users table
    $stmt = mysqli_prepare($conn,
        "SELECT user_id, full_name, password, role, status
         FROM users WHERE username = ? AND role = ?");
    mysqli_stmt_bind_param($stmt, "ss", $username, $role);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user   = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if ($user && $user['status'] === 'active' &&
        password_verify($password, $user['password'])) {
        // Login successful
        $_SESSION['user_id']   = $user['user_id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role']      = $user['role'];
        $_SESSION['username']  = $username;
        redirect_to_dashboard();
    } else {
        // Check customers table
        if ($role === 'customer') {
            $stmt2 = mysqli_prepare($conn,
                "SELECT customer_id, name, password
                 FROM customers WHERE email = ?");
            mysqli_stmt_bind_param($stmt2, "s", $username);
            mysqli_stmt_execute($stmt2);
            $result2  = mysqli_stmt_get_result($stmt2);
            $customer = mysqli_fetch_assoc($result2);
            mysqli_stmt_close($stmt2);

            if ($customer && password_verify($password, $customer['password'])) {
                $_SESSION['user_id']   = 'C' . $customer['customer_id'];
                $_SESSION['full_name'] = $customer['name'];
                $_SESSION['role']      = 'customer';
                $_SESSION['username']  = $username;
                header('Location: /courier_cms/customer/dashboard.php');
                exit();
            }
        }
        $error = "Invalid username, password or role. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Login — Wells Fargo CMS</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-box {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 40px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
        }
        .logo {
            text-align: center;
            margin-bottom: 28px;
        }
        .logo-icon {
            font-size: 48px;
            display: block;
            margin-bottom: 10px;
        }
        .logo h1 {
            font-size: 20px;
            color: #1e293b;
            font-weight: 700;
        }
        .logo p {
            font-size: 12px;
            color: #64748b;
            margin-top: 4px;
        }
        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            outline: none;
            font-family: Arial, sans-serif;
        }
        .form-group input:focus,
        .form-group select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
        }
        .btn {
            width: 100%;
            padding: 12px;
            background: #1e293b;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 6px;
        }
        .btn:hover { background: #334155; }
        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 10px 14px;
            border-radius: 6px;
            font-size: 13px;
            margin-bottom: 18px;
        }
        .divider {
            border: none;
            border-top: 1px solid #f1f5f9;
            margin: 20px 0;
        }
        .register-link {
            text-align: center;
            font-size: 13px;
            color: #64748b;
        }
        .register-link a { color: #2563eb; text-decoration: none; }
        .footer-text {
            text-align: center;
            font-size: 11px;
            color: #94a3b8;
            margin-top: 24px;
        }
    </style>
</head>
<body>
<div class="login-box">
    <div class="logo">
        <span class="logo-icon">🚚</span>
        <h1>Wells Fargo CMS</h1>
        <p>Courier Management System — Nairobi CBD Branch</p>
    </div>

    <?php if ($error): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Username / Email</label>
            <input type="text" name="username"
                   placeholder="Enter your username or email" required/>
        </div>

        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password"
                   placeholder="Enter your password" required/>
        </div>

        <div class="form-group">
            <label>Login As</label>
            <select name="role" required>
                <option value="">-- Select Your Role --</option>
                <option value="admin">Branch Manager / Admin</option>
                <option value="customer_service">Customer Service Rep</option>
                <option value="dispatch">Dispatch Officer</option>
                <option value="warehouse">Warehouse Staff</option>
                <option value="driver">Driver</option>
                <option value="customer">Customer</option>
            </select>
        </div>

        <button type="submit" class="btn">LOGIN</button>
    </form>

    <hr class="divider"/>

    <div class="register-link">
        New customer?
        <a href="/courier_cms/customer/register.php">Register here</a>
    </div>

    <div class="footer-text">
        BUS-242-002/2022 &mdash; Githinji Jecinta Wacheke<br/>
        Multimedia University of Kenya
    </div>
</div>
</body>
</html>