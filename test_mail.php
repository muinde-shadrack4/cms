<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once 'src/Exception.php';
require_once 'src/PHPMailer.php';
require_once 'src/SMTP.php';

try {
    $mail = new PHPMailer(true);
    $mail->SMTPDebug  = SMTP::DEBUG_SERVER;
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'shadrackmuinde23@gmail.com'; // ← your Gmail
    $mail->Password   = 'hebs wysq ihmd dvou';     // ← new app password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('shadrackmuinde23@gmail.com', 'Wells Fargo Courier');
    $mail->addAddress('milele455@gmail.com'); // send to yourself
    $mail->isHTML(true);
    $mail->Subject = 'Test Email';
    $mail->Body    = '<b>Test email from Wells Fargo CMS</b>';

    $mail->send();
    echo '✅ Email sent successfully!';

} catch (Exception $e) {
    echo '❌ Error: ' . $e->getMessage();
}
?>