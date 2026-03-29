<?php
require_once '../db.php';
require_once '../auth.php';
require_role('customer');

$customer_id = intval(str_replace('C','', $_SESSION['user_id']));
$parcel   = null;
$updates  = [];
$searched = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tracking = strtoupper(trim($_POST['tracking_number']));
    $searched = true;

    $stmt = mysqli_prepare($conn,
        "SELECT p.*, c.name AS customer_name FROM parcels p
         JOIN customers c ON p.customer_id=c.customer_id
         WHERE p.tracking_number=? AND p.customer_id=?");
    mysqli_stmt_bind_param($stmt, "si", $tracking, $customer_id);
    mysqli_stmt_execute($stmt);
    $parcel = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if ($parcel) {
        $pid = $parcel['parcel_id'];
        $stmt2 = mysqli_prepare($conn,
            "SELECT * FROM tracking_updates WHERE parcel_id=?
             ORDER BY updated_at ASC");
        mysqli_stmt_bind_param($stmt2, "i", $pid);
        mysqli_stmt_execute($stmt2);
        $res = mysqli_stmt_get_result($stmt2);
        while($u = mysqli_fetch_assoc($res)) $updates[] = $u;
        mysqli_stmt_close($stmt2);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <title>Track My Parcel — Wells Fargo CMS</title>
    <link rel="stylesheet" href="../style.css"/>
</head>
<body>
<div class="sidebar">
    <div class="logo">🚚 Wells Fargo CMS</div>
    <div class="nav-section">MY ACCOUNT</div>
    <a href="dashboard.php">📊 My Parcels</a>
    <a href="track_parcel.php" style="color:#fff;background:#334155;">🔍 Track Parcel</a>
    <div class="sidebar-footer">
        👤 <?= htmlspecialchars($_SESSION['full_name']) ?><br/>
        Customer Account<br/>
        <a href="../logout.php" style="color:#ef4444;font-size:11px;">🚪 Logout</a>
    </div>
</div>
<div class="main">
    <div class="page-header">
        <h1>Track My Parcel</h1>
        <p>Enter your tracking number to see the current status</p>
    </div>
    <div class="card" style="padding:18px;">
        <form method="POST" style="display:flex;gap:10px;">
            <input type="text" name="tracking_number"
                   placeholder="Enter your tracking number"
                   style="flex:1;padding:10px 14px;border:1px solid #d1d5db;
                          border-radius:6px;font-size:14px;
                          font-family:monospace;outline:none;
                          text-transform:uppercase;"
                   required/>
            <button type="submit" class="track-btn">🔍 Track</button>
        </form>
    </div>
    <?php if ($searched): ?>
        <?php if ($parcel): ?>
        <div class="card">
            <div style="display:flex;justify-content:space-between;
                        align-items:center;margin-bottom:16px;">
                <h2><?= htmlspecialchars($parcel['tracking_number']) ?></h2>
                <span class="badge badge-<?= strtolower(
                    str_replace(' ','-',$parcel['status'])) ?>"
                      style="font-size:13px;padding:6px 14px;">
                    <?= $parcel['status'] ?>
                </span>
            </div>
            <div class="detail-grid">
                <div class="detail-item">
                    <label>Recipient</label>
                    <span><?= htmlspecialchars($parcel['recipient_name']) ?></span>
                </div>
                <div class="detail-item">
                    <label>Delivery Address</label>
                    <span><?= htmlspecialchars($parcel['recipient_address']) ?></span>
                </div>
                <div class="detail-item">
                    <label>Price</label>
                    <span>KES <?= number_format($parcel['price'],2) ?></span>
                </div>
                <div class="detail-item">
                    <label>Booked On</label>
                    <span><?= date('d M Y',strtotime($parcel['date_registered'])) ?></span>
                </div>
            </div>
        </div>
        <div class="card">
            <h2>Tracking History</h2>
            <div class="timeline">
                <?php $last = count($updates)-1;
                      foreach($updates as $i=>$u): ?>
                <div class="tl-item">
                    <div class="tl-dot <?= $i===$last?'active':'' ?>"></div>
                    <div>
                        <div class="tl-status">
                            <?= htmlspecialchars($u['status']) ?></div>
                        <?php if($u['location']): ?>
                        <div class="tl-loc">
                            📍 <?= htmlspecialchars($u['location']) ?></div>
                        <?php endif; ?>
                        <div class="tl-meta">
                            <?= date('d M Y H:i',strtotime($u['updated_at'])) ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="card not-found">
            <h3>🔎 Parcel Not Found</h3>
            <p>No parcel found. Make sure you enter your own tracking number.</p>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html>