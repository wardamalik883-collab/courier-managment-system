<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user']) || $_SESSION['user_type'] !== 'user') {
    header('Location: login.php');
    exit;
}

$connect = mysqli_connect("localhost", "root", "", "courier_management");
if (!$connect) {
    die("Connection failed: " . mysqli_connect_error());
}

$user_id = $_SESSION['user_id'];
$user = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM users WHERE id=$user_id"));
$user_name = $user['name'] ?? 'User';
$user_email = $user['email'] ?? '';
$user_phone = $user['phone'] ?? '';

// Fetch ONLY this user's shipments
$user_phone_esc = mysqli_real_escape_string($connect, $user_phone);
$user_email_esc = mysqli_real_escape_string($connect, $user_email);

$shipments_query = "SELECT * FROM shipments
                    WHERE (sender_phone='$user_phone_esc' AND sender_phone != '')
                       OR (sender_email='$user_email_esc' AND sender_email != '')
                    ORDER BY booked_date DESC";
$shipments_result = mysqli_query($connect, $shipments_query);
$shipments = [];
while ($row = mysqli_fetch_assoc($shipments_result)) {
    $shipments[] = $row;
}

// Count stats
$total_shipments = count($shipments);
$delivered = 0;
$in_transit = 0;
$pending = 0;
foreach ($shipments as $s) {
    if ($s['status'] === 'Delivered') $delivered++;
    elseif ($s['status'] === 'In Transit' || $s['status'] === 'Out for Delivery') $in_transit++;
    else $pending++;
}

mysqli_close($connect);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Shipments – Calma</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root {
  --primary:#c8553d;
  --primary-d:#a33e2a;
  --text:#1e293b;
  --muted:#64748b;
  --border:#e2e8f0;
  --success:#16a34a;
  --warning:#f59e0b;
  --info:#3b82f6;
  --danger:#ef4444;
  --purple:#7c3aed;
  --bg:#f8fafc;
}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Segoe UI',Arial,sans-serif;background:var(--bg);min-height:100vh;}

.layout{display:flex;min-height:100vh;}

/* ========== SIDEBAR ========== */
.sidebar{width:260px;background:linear-gradient(180deg,#1e293b 0%,#0f172a 100%);color:white;flex-shrink:0;position:sticky;top:0;height:100vh;overflow-y:auto;}
.sidebar-header{padding:25px 20px;border-bottom:1px solid rgba(255,255,255,0.1);}
.sidebar-logo{display:flex;align-items:center;gap:12px;font-size:24px;font-weight:700;color:var(--primary);text-decoration:none;}
.logo-mark{width:40px;height:40px;background:var(--primary);border-radius:10px;display:flex;align-items:center;justify-content:center;color:white;font-size:18px;font-weight:bold;}
.sidebar-user{margin-top:20px;padding:15px;background:rgba(255,255,255,0.05);border-radius:10px;}
.user-avatar-lg{width:50px;height:50px;background:linear-gradient(135deg,var(--primary),var(--primary-d));border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-size:20px;font-weight:bold;margin-bottom:10px;}
.user-name{font-size:14px;font-weight:600;margin-bottom:3px;}
.user-email{font-size:12px;color:var(--muted);}

.sidebar-nav{padding:15px 10px;}
.sidebar-nav ul{list-style:none;}
.sidebar-nav li{margin-bottom:5px;}
.sidebar-nav a{display:flex;align-items:center;gap:12px;padding:12px 15px;color:#cbd5e1;text-decoration:none;border-radius:8px;transition:all 0.2s;font-size:14px;}
.sidebar-nav a:hover,.sidebar-nav a.active{background:rgba(200,85,61,0.2);color:white;}
.sidebar-nav a i{width:20px;text-align:center;font-size:16px;}
.sidebar-nav .nav-section{font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:1px;padding:15px 15px 8px;margin-top:10px;}

.sidebar-footer{padding:20px;border-top:1px solid rgba(255,255,255,0.1);}
.logout-btn{display:flex;align-items:center;gap:10px;width:100%;padding:12px 15px;background:rgba(239,68,68,0.2);color:#fca5a5;border:none;border-radius:8px;cursor:pointer;font-size:14px;font-weight:600;transition:all 0.2s;text-decoration:none;}
.logout-btn:hover{background:rgba(239,68,68,0.3);color:white;}

/* ========== MAIN CONTENT ========== */
.main{flex:1;padding:30px;overflow-y:auto;}
.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:30px;}
.page-header h1{font-size:28px;color:var(--text);font-weight:700;}
.page-header p{color:var(--muted);font-size:14px;margin-top:5px;}

/* Stats Grid */
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px;margin-bottom:30px;}
.stat-card{background:white;padding:25px;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.04);display:flex;align-items:center;gap:20px;transition:transform 0.2s,box-shadow 0.2s;}
.stat-card:hover{transform:translateY(-3px);box-shadow:0 8px 20px rgba(0,0,0,0.08);}
.stat-icon{width:60px;height:60px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:24px;flex-shrink:0;}
.stat-icon.blue{background:#dbeafe;color:#2563eb;}
.stat-icon.green{background:#dcfce7;color:#16a34a;}
.stat-icon.purple{background:#ede9fe;color:#7c3aed;}
.stat-icon.orange{background:#fef3c7;color:#d97706;}
.stat-val{font-size:28px;font-weight:700;color:var(--text);margin-bottom:3px;}
.stat-lbl{font-size:13px;color:var(--muted);}

/* Card */
.card{background:white;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.04);overflow:hidden;margin-bottom:20px;}
.card-header{padding:20px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;}
.card-title{font-size:16px;font-weight:600;color:var(--text);display:flex;align-items:center;gap:8px;}
.card-header a{font-size:13px;color:var(--primary);text-decoration:none;font-weight:600;}
.card-header a:hover{text-decoration:underline;}

/* Table */
.table-wrap{overflow-x:auto;}
.data-table{width:100%;border-collapse:collapse;}
.data-table th,.data-table td{padding:12px;text-align:left;border-bottom:1px solid var(--border);font-size:13px;}
.data-table th{background:#f8fafc;font-weight:600;}
.tracking-number{font-weight:600;color:var(--primary);}
.badge{padding:4px 10px;border-radius:4px;font-size:11px;color:white;font-weight:600;}
.b-pending{background:#f59e0b;}
.b-transit{background:#3b82f6;}
.b-delivered{background:#16a34a;}
.b-returned{background:#ef4444;}
.btn{padding:6px 12px;border:none;border-radius:4px;cursor:pointer;font-size:12px;text-decoration:none;display:inline-block;}
.btn-primary{background:var(--primary);color:white;}
.btn-outline{background:white;border:1px solid var(--primary);color:var(--primary);}
.no-data{text-align:center;padding:40px;color:var(--muted);}

/* Responsive */
@media(max-width:1024px){
  .stats-grid{grid-template-columns:repeat(2,1fr);}
}
@media(max-width:768px){
  .layout{flex-direction:column;}
  .sidebar{width:100%;height:auto;position:relative;}
  .main{padding:20px;}
}
</style>
</head>
<body>
<div class="layout">

  <!-- ========== SIDEBAR ========== -->
  <aside class="sidebar">
    <div class="sidebar-header">
      <a href="user_dashboard.php" class="sidebar-logo">
        <div class="logo-mark">C</div>
        Calma
      </a>
      <div class="sidebar-user">
        <div class="user-avatar-lg"><?php echo strtoupper(substr($user_name, 0, 1)); ?></div>
        <div class="user-name"><?php echo htmlspecialchars($user_name); ?></div>
        <div class="user-email"><?php echo htmlspecialchars($user_email); ?></div>
      </div>
    </div>

    <nav class="sidebar-nav">
      <ul>
        <li><a href="user_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li><a href="home.php"><i class="fas fa-home"></i> Home</a></li>
        <li><a href="book_shipment.php"><i class="fas fa-plus-circle"></i> Book Shipment</a></li>
        <li><a href="my_shipments.php" class="active"><i class="fas fa-box"></i> My Shipments</a></li>
        <li><a href="track_shipment.php"><i class="fas fa-search-location"></i> Track Package</a></li>

        <div class="nav-section">Account</div>
        <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
        <li><a href="addresses.php"><i class="fas fa-map-marker-alt"></i> Addresses</a></li>
      </ul>
    </nav>

    <div class="sidebar-footer">
      <a href="logout.php" class="logout-btn">
        <i class="fas fa-sign-out-alt"></i>
        Logout
      </a>
    </div>
  </aside>

  <!-- ========== MAIN CONTENT ========== -->
  <main class="main">
    <div class="page-header">
      <div>
        <h1>📦 My Shipments</h1>
        <p>View and manage all your shipments</p>
      </div>
      <a href="book_shipment.php" class="btn btn-primary" style="padding:12px 24px;font-size:14px;font-weight:700;">+ Book New Shipment</a>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-box"></i></div>
        <div class="stat-info">
          <div class="stat-val"><?php echo $total_shipments; ?></div>
          <div class="stat-lbl">Total Shipments</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
        <div class="stat-info">
          <div class="stat-val"><?php echo $delivered; ?></div>
          <div class="stat-lbl">Delivered</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon orange"><i class="fas fa-truck"></i></div>
        <div class="stat-info">
          <div class="stat-val"><?php echo $in_transit; ?></div>
          <div class="stat-lbl">In Transit</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon purple"><i class="fas fa-clock"></i></div>
        <div class="stat-info">
          <div class="stat-val"><?php echo $pending; ?></div>
          <div class="stat-lbl">Pending</div>
        </div>
      </div>
    </div>

    <!-- Shipments Table -->
    <div class="card">
      <div class="card-header">
        <span class="card-title"><i class="fas fa-list"></i> All Your Shipments</span>
      </div>
      <div class="table-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th>Tracking Number</th>
              <th>From</th>
              <th>To</th>
              <th>Type</th>
              <th>Weight</th>
              <th>Status</th>
              <th>Amount</th>
              <th>Booked Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($shipments)): ?>
            <tr>
              <td colspan="9" class="no-data">
                <i class="fas fa-box-open" style="font-size:48px;opacity:0.5;display:block;margin-bottom:10px;"></i>
                No shipments found. Book your first shipment!
              </td>
            </tr>
            <?php else: ?>
              <?php foreach ($shipments as $s):
                $badge_class = 'b-pending';
                if ($s['status'] === 'Delivered') $badge_class = 'b-delivered';
                elseif ($s['status'] === 'In Transit' || $s['status'] === 'Out for Delivery') $badge_class = 'b-transit';
                elseif ($s['status'] === 'Returned') $badge_class = 'b-returned';
              ?>
              <tr>
                <td><span class="tracking-number"><?php echo htmlspecialchars($s['tracking_number']); ?></span></td>
                <td>
                  <div style="font-weight:600;"><?php echo htmlspecialchars($s['sender_name']); ?></div>
                  <div style="font-size:11px;color:#64748b;"><?php echo htmlspecialchars($s['sender_city']); ?></div>
                </td>
                <td>
                  <div style="font-weight:600;"><?php echo htmlspecialchars($s['receiver_name']); ?></div>
                  <div style="font-size:11px;color:#64748b;"><?php echo htmlspecialchars($s['receiver_city']); ?></div>
                </td>
                <td><?php echo htmlspecialchars($s['type']); ?></td>
                <td><?php echo $s['weight'] ? $s['weight'].' kg' : '-'; ?></td>
                <td><span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($s['status']); ?></span></td>
                <td style="font-weight:600;">Rs. <?php echo number_format($s['amount']); ?></td>
                <td style="color:#64748b;font-size:12px;"><?php echo $s['booked_date']; ?></td>
                <td>
                  <a href="track_shipment.php?tracking_number=<?php echo urlencode($s['tracking_number']); ?>" class="btn btn-outline">Track</a>
                  <a href="print_shipment.php?tracking=<?php echo urlencode($s['tracking_number']); ?>" class="btn btn-outline" target="_blank">Print</a>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </main>

</div>

<script>
// Set active class on current page link
document.querySelectorAll('.sidebar-nav a').forEach(link => {
    if (link.getAttribute('href') === window.location.pathname.split('/').pop()) {
        link.classList.add('active');
    }
});
</script>
</body>
</html>
