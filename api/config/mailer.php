<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../src/Exception.php';
require_once __DIR__ . '/../../src/PHPMailer.php';
require_once __DIR__ . '/../../src/SMTP.php';

class Mailer {

    private static function create(): PHPMailer {
        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'shadrackmuinde23@gmail.com';
        $mail->Password   = 'hebswysqihmddvou';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('shadrackmuinde23@gmail.com', 'Wells Fargo Courier');
        $mail->isHTML(true);

        return $mail;
    }

    // ── SEND SINGLE EMAIL ─────────────────────────────────
    public static function sendStatusUpdate(
        string $toEmail,
        string $toName,
        string $trackingNo,
        string $status,
        string $location = '',
        string $notes    = ''
    ): bool {
        try {
            $mail = self::create();
            $mail->addAddress($toEmail, $toName);
            $mail->Subject = "Parcel {$trackingNo} — {$status}";
            $mail->Body    = self::buildEmail(
                $toName, $trackingNo, $status, $location, $notes
            );
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('Mailer Error: ' . $e->getMessage());
            return false;
        }
    }

    // ── SEND TO BOTH SENDER AND RECIPIENT ✅ ──────────────
    // ✅ Fixed — optional parameters moved to end
    public static function sendBothNotification(
        string $senderEmail,
        string $senderName,
        string $recipientName,
        string $trackingNo,
        string $status,
        string $location       = '',
        string $notes          = '',
        string $recipientEmail = ''
    ): void {

        // ✅ Email to SENDER — "Dear Jane,"
        if (!empty($senderEmail)) {
            self::sendStatusUpdate(
                $senderEmail,
                $senderName,
                $trackingNo,
                $status,
                $location,
                "Dear {$senderName}, " . $notes
            );
        }

        // ✅ Email to RECIPIENT — "Dear John,"
        if (!empty($recipientEmail)) {
            self::sendStatusUpdate(
                $recipientEmail,
                $recipientName,
                $trackingNo,
                $status,
                $location,
                "Dear {$recipientName}, " . $notes
            );
        }
    }

    // ── BUILD EMAIL TEMPLATE ──────────────────────────────
    private static function buildEmail(
        string $name,
        string $tracking,
        string $status,
        string $location,
        string $notes
    ): string {

        $statusColors = [
            'Booked'            => '#3b82f6',
            'Picked Up'         => '#f59e0b',
            'In Transit'        => '#8b5cf6',
            'Out for Delivery'  => '#f97316',
            'Delivered'         => '#10b981',
            'Cancelled'         => '#ef4444',
            'Payment Confirmed' => '#10b981',
        ];

        $color = $statusColors[$status] ?? '#6b7280';
        $time  = date('d M Y, h:i A');

        return "
        <!DOCTYPE html>
        <html>
        <body style='font-family: Arial, sans-serif;
                     background: #f3f4f6; padding: 20px;'>
            <div style='max-width: 600px; margin: 0 auto;
                        background: white; border-radius: 12px;
                        overflow: hidden;
                        box-shadow: 0 2px 8px rgba(0,0,0,0.1);'>

                <!-- HEADER -->
                <div style='background: #1e293b; padding: 24px;
                            text-align: center;'>
                    <h1 style='color: white; margin: 0; font-size: 22px;'>
                        🚚 Wells Fargo Courier
                    </h1>
                    <p style='color: #94a3b8; margin: 4px 0 0;'>
                        Nairobi CBD Branch
                    </p>
                </div>

                <!-- STATUS BANNER -->
                <div style='background: {$color}; padding: 16px;
                            text-align: center;'>
                    <h2 style='color: white; margin: 0;
                               font-size: 20px;'>{$status}</h2>
                </div>

                <!-- BODY -->
                <div style='padding: 32px;'>
                    <p style='font-size: 16px; color: #374151;'>
                        Dear <strong>{$name}</strong>,
                    </p>
                    <p style='color: #6b7280;'>
                        Your parcel status has been updated.
                        Here are the details:
                    </p>

                    <!-- DETAILS BOX -->
                    <div style='background: #f8fafc; border-radius: 8px;
                                padding: 20px; margin: 20px 0;
                                border-left: 4px solid {$color};'>
                        <table style='width:100%;
                                      border-collapse:collapse;'>
                            <tr>
                                <td style='padding:8px 0; color:#6b7280;
                                           width:40%;'>
                                    Tracking Number
                                </td>
                                <td style='padding:8px 0; font-weight:bold;
                                           color:#1e293b;
                                           font-family:monospace;'>
                                    {$tracking}
                                </td>
                            </tr>
                            <tr>
                                <td style='padding:8px 0; color:#6b7280;'>
                                    Status
                                </td>
                                <td style='padding:8px 0;'>
                                    <span style='background:{$color};
                                                 color:white;
                                                 padding:2px 10px;
                                                 border-radius:20px;
                                                 font-size:13px;'>
                                        {$status}
                                    </span>
                                </td>
                            </tr>
                            " . ($location ? "
                            <tr>
                                <td style='padding:8px 0; color:#6b7280;'>
                                    Location
                                </td>
                                <td style='padding:8px 0; color:#1e293b;'>
                                    {$location}
                                </td>
                            </tr>" : "") . "
                            " . ($notes ? "
                            <tr>
                                <td style='padding:8px 0; color:#6b7280;'>
                                    Notes
                                </td>
                                <td style='padding:8px 0; color:#1e293b;'>
                                    {$notes}
                                </td>
                            </tr>" : "") . "
                            <tr>
                                <td style='padding:8px 0; color:#6b7280;'>
                                    Updated At
                                </td>
                                <td style='padding:8px 0; color:#1e293b;'>
                                    {$time}
                                </td>
                            </tr>
                        </table>
                    </div>

                    <p style='color:#6b7280; font-size:14px;'>
                        You can track your parcel anytime by
                        logging into your customer portal.
                    </p>
                </div>

                <!-- FOOTER -->
                <div style='background:#f8fafc; padding:20px;
                            text-align:center;
                            border-top:1px solid #e2e8f0;'>
                    <p style='color:#94a3b8; font-size:12px; margin:0;'>
                        Wells Fargo Courier — Nairobi CBD Branch<br/>
                        This is an automated message,
                        please do not reply.
                    </p>
                </div>
            </div>
        </body>
        </html>";
    }
}
?>