<?php
require 'db.php';
$parcel   = null;
$updates  = [];
$searched = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tracking = strtoupper(trim($_POST['tracking_number']));
    $searched = true;

    // SECURITY: Prepared statement
    $stmt = mysqli_prepare($conn,
        "SELECT p.*, c.name AS customer_name, c.phone AS customer_phone
         FROM parcels p
         JOIN customers c ON p.customer_id = c.customer_id
         WHERE p.tracking_number = ?");
    mysqli_stmt_bind_param($stmt, "s", $tracking);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $parcel = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if ($parcel) {
        $pid = $parcel['parcel_id'];

        // SECURITY: Prepared statement for tracking history
        $stmt2 = mysqli_prepare($conn,
            "SELECT * FROM tracking_updates
             WHERE parcel_id = ?
             ORDER BY updated_at ASC");
        mysqli_stmt_bind_param($stmt2, "i", $pid);
        mysqli_stmt_execute($stmt2);
        $upd_result = mysqli_stmt_get_result($stmt2);
        while($u = mysqli_fetch_assoc($upd_result)) $updates[] = $u;
        mysqli_stmt_close($stmt2);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <title>Track Parcel — Wells Fargo CMS</title>
    <link rel="stylesheet" href="style.css"/>
</head>
<body>
<div class="sidebar">
    <div class="logo">🚚 Wells Fargo CMS</div>
    <a href="index.php">📊 Dashboard</a>
    <div class="nav-section">OBJECTIVE 1</div>
    <a href="register_customer.php">👤 Register Customer</a>
    <a href="register_parcel.php">📦 Register Parcel</a>
    <div class="nav-section">OBJECTIVE 2</div>
    <a href="update_status.php">🔄 Update Status</a>
    <a href="track_parcel.php" style="color:#fff;background:#334155;">🔍 Track Parcel</a>
</div>

<div class="main">
    <div class="page-header">
        <h1>Track Your Parcel</h1>
        <p>Objective 3 — Enter tracking number to see full status history</p>
    </div>

    <div class="card" style="padding:18px;">
        <form method="POST" style="display:flex;gap:10px;">
            <input type="text" name="tracking_number"
                   placeholder="Enter Tracking Number — e.g. WF-2026-1234567"
                   style="flex:1;padding:10px 14px;border:1px solid #d1d5db;
                          border-radius:6px;font-size:14px;font-family:monospace;
                          outline:none;text-transform:uppercase;"
                   required/>
            <button type="submit" class="track-btn">🔍 Track</button>
        </form>
    </div>

    <?php if ($searched): ?>
        <?php if ($parcel): ?>

        <div class="card">
            <div style="display:flex;justify-content:space-between;
                        align-items:center;margin-bottom:16px;">
                <h2>Parcel Details</h2>
                <span class="badge badge-<?= strtolower(str_replace(' ','-',$parcel['status'])) ?>"
                      style="font-size:13px;padding:6px 14px;">
                    <?= htmlspecialchars($parcel['status']) ?>
                </span>
            </div>
            <div class="detail-grid">
                <div class="detail-item">
                    <label>Tracking Number</label>
                    <span class="tracking"><?= htmlspecialchars($parcel['tracking_number']) ?></span>
                </div>
                <div class="detail-item">
                    <label>Sender</label>
                    <span><?= htmlspecialchars($parcel['customer_name']) ?></span>
                </div>
                <div class="detail-item">
                    <label>Recipient</label>
                    <span><?= htmlspecialchars($parcel['recipient_name']) ?></span>
                </div>
                <div class="detail-item">
                    <label>Delivery Address</label>
                    <span><?= htmlspecialchars($parcel['recipient_address']) ?></span>
                </div>
                <div class="detail-item">
                    <label>Weight</label>
                    <span><?= $parcel['weight'] ?> kg</span>
                </div>
                <div class="detail-item">
                    <label>Service Type</label>
                    <span><?= ucfirst($parcel['service_type']) ?></span>
                </div>
                <div class="detail-item">
                    <label>Price</label>
                    <span><strong>KES <?= $parcel['price'] ?></strong></span>
                </div>
                <div class="detail-item">
                    <label>Booked On</label>
                    <span><?= date('d M Y H:i', strtotime($parcel['date_registered'])) ?></span>
                </div>
            </div>
        </div>

        <div class="card">
            <h2>Tracking History</h2>
            <?php if ($updates): ?>
            <div class="timeline">
                <?php $last = count($updates)-1;
                      foreach($updates as $i => $u): ?>
                <div class="tl-item">
                    <div class="tl-dot <?= $i===$last ? 'active' : '' ?>"></div>
                    <div>
                        <div class="tl-status"><?= htmlspecialchars($u['status']) ?></div>
                        <?php if($u['location']): ?>
                        <div class="tl-loc">📍 <?= htmlspecialchars($u['location']) ?></div>
                        <?php endif; ?>
                        <?php if($u['notes']): ?>
                        <div class="tl-loc"><?= htmlspecialchars($u['notes']) ?></div>
                        <?php endif; ?>
                        <div class="tl-meta">
                            By <?= htmlspecialchars($u['updated_by']) ?> —
                            <?= date('d M Y H:i', strtotime($u['updated_at'])) ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p style="color:#94a3b8;padding:20px 0;">No tracking updates yet.</p>
            <?php endif; ?>
        </div>

        <?php else: ?>
        <div class="card not-found">
            <h3>🔎 Parcel Not Found</h3>
            <p>No parcel found with that tracking number. Please check and try again.</p>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html>
