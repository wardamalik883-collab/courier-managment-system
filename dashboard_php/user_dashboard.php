<?php
/**
 * USER DASHBOARD
 * Calma Courier Management System
 * For: Regular Users (Customers)
 */

// Start session securely
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

// Add no-cache headers
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Check if user is logged in as 'user' role
if (!isset($_SESSION['user']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'user') {
    header('Location: login.php');
    exit;
}

// Database connection
$connect = mysqli_connect("localhost", "root", "", "courier_management");
if (!$connect) {
    die("Connection failed: " . mysqli_connect_error());
}

// Get user data
$user_id = (int)($_SESSION['user_id'] ?? 0);
$user_name = $_SESSION['user_name'] ?? $_SESSION['user']['name'] ?? 'User';
$user_email = $_SESSION['user_email'] ?? $_SESSION['user']['email'] ?? '';
$user_phone = $_SESSION['user_phone'] ?? $_SESSION['user']['phone'] ?? '';

// If email/phone not in session, fetch from database
if (empty($user_email) || empty($user_phone)) {
    $user_data = mysqli_fetch_assoc(mysqli_query($connect, "SELECT email, phone FROM users WHERE id=$user_id"));
    if ($user_data) {
        $user_email = $user_data['email'] ?? $user_email;
        $user_phone = $user_data['phone'] ?? $user_phone;
    }
}

$user_email_esc = mysqli_real_escape_string($connect, $user_email);
$user_phone_esc = mysqli_real_escape_string($connect, $user_phone);

// Fetch user's shipments
$shipments_query = "SELECT * FROM shipments 
                    WHERE (sender_email='$user_email_esc' AND sender_email != '') 
                       OR (sender_phone='$user_phone_esc' AND sender_phone != '') 
                    ORDER BY booked_date DESC LIMIT 5";
$shipments_result = mysqli_query($connect, $shipments_query);
$shipments = [];
while ($row = mysqli_fetch_assoc($shipments_result)) {
    $shipments[] = $row;
}

// Count shipment stats
$total_shipments = mysqli_fetch_row(mysqli_query($connect, 
    "SELECT COUNT(*) FROM shipments WHERE (sender_email='$user_email_esc' AND sender_email != '') OR (sender_phone='$user_phone_esc' AND sender_phone != '')"))[0];
$delivered_shipments = mysqli_fetch_row(mysqli_query($connect, 
    "SELECT COUNT(*) FROM shipments WHERE ((sender_email='$user_email_esc' AND sender_email != '') OR (sender_phone='$user_phone_esc' AND sender_phone != '')) AND status='Delivered'"))[0];
$in_transit_shipments = mysqli_fetch_row(mysqli_query($connect, 
    "SELECT COUNT(*) FROM shipments WHERE ((sender_email='$user_email_esc' AND sender_email != '') OR (sender_phone='$user_phone_esc' AND sender_phone != '')) AND status IN ('In Transit', 'Out for Delivery')"))[0];
$pending_shipments = mysqli_fetch_row(mysqli_query($connect, 
    "SELECT COUNT(*) FROM shipments WHERE ((sender_email='$user_email_esc' AND sender_email != '') OR (sender_phone='$user_phone_esc' AND sender_phone != '')) AND status='Pending'"))[0];

mysqli_close($connect);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Dashboard – Calma</title>
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

/* Hamburger */
.hamburger{display:none;flex-direction:column;cursor:pointer;padding:5px;gap:5px;}
.hamburger span{width:25px;height:3px;background:#c8553d;border-radius:2px;transition:all 0.3s ease;}
.hamburger.active span:nth-child(1){transform:rotate(45deg) translate(5px,5px);}
.hamburger.active span:nth-child(2){opacity:0;}
.hamburger.active span:nth-child(3){transform:rotate(-45deg) translate(7px,-6px);}
.overlay{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:998;}
.overlay.active{display:block;}

/* ========== MAIN CONTENT ========== */
.main{flex:1;padding:30px;overflow-y:auto;}
.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:30px;}
.page-header h1{font-size:28px;color:var(--text);font-weight:700;}
.page-header p{color:var(--muted);font-size:14px;margin-top:5px;}
.welcome-text{font-size:14px;color:var(--muted);margin-bottom:5px;}

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

/* Content Grid */
.content-grid{display:grid;grid-template-columns:2fr 1fr;gap:20px;}

/* Cards */
.card{background:white;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.04);overflow:hidden;}
.card-header{padding:20px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;}
.card-title{font-size:16px;font-weight:600;color:var(--text);display:flex;align-items:center;gap:8px;}
.card-header a{font-size:13px;color:var(--primary);text-decoration:none;font-weight:600;}
.card-header a:hover{text-decoration:underline;}
.card-body{padding:20px;}

/* Shipment Item */
.shipment-item{display:flex;align-items:center;gap:15px;padding:15px;border-bottom:1px solid var(--border);}
.shipment-item:last-child{border-bottom:none;}
.shipment-item:hover{background:#f8fafc;}
.tracking-badge{background:#f0f9ff;padding:8px 12px;border-radius:6px;font-family:monospace;font-size:12px;font-weight:600;color:var(--primary);letter-spacing:0.5px;}
.shipment-info{flex:1;}
.shipment-route{font-size:13px;color:var(--muted);margin-top:3px;}
.shipment-status{padding:5px 12px;border-radius:6px;font-size:11px;font-weight:600;}
.status-pending{background:#fef3c7;color:#d97706;}
.status-transit{background:#dbeafe;color:#2563eb;}
.status-delivered{background:#dcfce7;color:#16a34a;}
.status-returned{background:#fee2e2;color:#dc2626;}

/* Quick Actions */
.quick-actions{display:grid;grid-template-columns:1fr 1fr;gap:10px;}
.quick-action-btn{display:flex;flex-direction:column;align-items:center;gap:8px;padding:20px;background:linear-gradient(135deg,var(--primary),var(--primary-d));color:white;border:none;border-radius:10px;cursor:pointer;text-decoration:none;transition:transform 0.2s;}
.quick-action-btn:hover{transform:translateY(-3px);}
.quick-action-btn i{font-size:24px;}
.quick-action-btn span{font-size:13px;font-weight:600;}
.quick-action-btn.secondary{background:linear-gradient(135deg,#64748b,#475569);}

/* Empty State */
.empty-state{text-align:center;padding:40px 20px;color:var(--muted);}
.empty-state i{font-size:48px;margin-bottom:15px;opacity:0.5;}
.empty-state h4{font-size:16px;color:var(--text);margin-bottom:5px;}

/* Responsive */
@media(max-width:1024px){
  .content-grid{grid-template-columns:1fr;}
}
@media(max-width:768px){
  .layout{flex-direction:column;}
  .sidebar{width:260px;position:fixed;top:0;left:-100%;height:100vh;z-index:999;transition:left 0.3s ease;}
  .sidebar.active{left:0;}
  #hamburger{display:flex !important;}
  #hamburger-close{display:block !important;}
  .main{padding:20px 15px;}
  .stats-grid{grid-template-columns:1fr 1fr;}
  .page-header{flex-direction:column;gap:15px;align-items:flex-start !important;}
  .quick-actions{grid-template-columns:1fr;}
}
</style>
</head>
<body>
<div class="overlay" id="overlay"></div>
<div class="layout">

  <!-- ========== SIDEBAR ========== -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
      <div style="display:flex;justify-content:space-between;align-items:center;">
        <a href="user_dashboard.php" class="sidebar-logo">
          <div class="logo-mark">C</div>
          Calma
        </a>
        <div class="hamburger" id="hamburger-close" style="cursor:pointer;">
          <i class="fas fa-times" style="color:white;font-size:20px;"></i>
        </div>
      </div>
      <div class="sidebar-user">
        <div class="user-avatar-lg"><?php echo strtoupper(substr($user_name, 0, 1)); ?></div>
        <div class="user-name"><?php echo htmlspecialchars($user_name); ?></div>
        <div class="user-email"><?php echo htmlspecialchars($user_email); ?></div>
      </div>
    </div>

    <nav class="sidebar-nav">
      <ul>
        <li><a href="user_dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li><a href="home.php"><i class="fas fa-home"></i> Home</a></li>
        <li><a href="book_shipment.php"><i class="fas fa-plus-circle"></i> Book Shipment</a></li>
        <li><a href="my_shipments.php"><i class="fas fa-box"></i> My Shipments</a></li>
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
      <div style="display:flex;align-items:center;gap:15px;">
        <div class="hamburger" id="hamburger" style="display:none;">
          <span></span>
          <span></span>
          <span></span>
        </div>
        <div>
          <div class="welcome-text">Welcome back!</div>
          <h1>My Dashboard</h1>
        </div>
      </div>
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
          <div class="stat-val"><?php echo $delivered_shipments; ?></div>
          <div class="stat-lbl">Delivered</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon orange"><i class="fas fa-truck"></i></div>
        <div class="stat-info">
          <div class="stat-val"><?php echo $in_transit_shipments; ?></div>
          <div class="stat-lbl">In Transit</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon purple"><i class="fas fa-clock"></i></div>
        <div class="stat-info">
          <div class="stat-val"><?php echo $pending_shipments; ?></div>
          <div class="stat-lbl">Pending</div>
        </div>
      </div>
    </div>

    <!-- Content Grid -->
    <div class="content-grid">
      
      <!-- Recent Shipments -->
      <div class="card">
        <div class="card-header">
          <span class="card-title"><i class="fas fa-box"></i> Recent Shipments</span>
          <a href="my_shipments.php">View All</a>
        </div>
        <div class="card-body" style="padding:0;">
          <?php if (empty($shipments)): ?>
            <div class="empty-state">
              <i class="fas fa-box-open"></i>
              <h4>No shipments yet</h4>
              <p>Create your first shipment to see it here</p>
            </div>
          <?php else: ?>
            <?php foreach ($shipments as $s): 
              $status_class = 'status-pending';
              if ($s['status'] === 'Delivered') $status_class = 'status-delivered';
              elseif ($s['status'] === 'In Transit' || $s['status'] === 'Out for Delivery') $status_class = 'status-transit';
              elseif ($s['status'] === 'Returned') $status_class = 'status-returned';
            ?>
            <div class="shipment-item">
              <div style="flex:1;">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:5px;">
                  <span class="tracking-badge"><?php echo htmlspecialchars($s['tracking_number']); ?></span>
                  <span class="shipment-status <?php echo $status_class; ?>"><?php echo htmlspecialchars($s['status']); ?></span>
                </div>
                <div class="shipment-route">
                  <?php echo htmlspecialchars($s['sender_city']); ?> → <?php echo htmlspecialchars($s['receiver_city']); ?>
                </div>
              </div>
              <div style="text-align:right;">
                <div style="font-weight:700;color:var(--text);">Rs. <?php echo number_format($s['amount']); ?></div>
                <div style="font-size:11px;color:var(--muted);"><?php echo $s['booked_date']; ?></div>
              </div>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="card">
        <div class="card-header">
          <span class="card-title"><i class="fas fa-bolt"></i> Quick Actions</span>
        </div>
        <div class="card-body">
          <div class="quick-actions">
            <a href="my_shipments.php" class="quick-action-btn">
              <i class="fas fa-plus-circle"></i>
              <span>New Shipment</span>
            </a>
            <a href="track_shipment.php" class="quick-action-btn secondary">
              <i class="fas fa-search"></i>
              <span>Track</span>
            </a>
            <a href="profile.php" class="quick-action-btn">
              <i class="fas fa-user"></i>
              <span>Profile</span>
            </a>
            <a href="addresses.php" class="quick-action-btn secondary">
              <i class="fas fa-map-marker-alt"></i>
              <span>Addresses</span>
            </a>
          </div>
        </div>
      </div>

    </div>
  </main>

</div>

<script>
// Hamburger menu toggle
const hamburger = document.getElementById('hamburger');
const hamburgerClose = document.getElementById('hamburger-close');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('overlay');

if (hamburger && sidebar && overlay) {
    hamburger.addEventListener('click', () => {
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
    });
}

if (hamburgerClose && sidebar && overlay) {
    hamburgerClose.addEventListener('click', () => {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
    });
}

// Close sidebar when clicking overlay
if (overlay && sidebar) {
    overlay.addEventListener('click', () => {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
    });
}

// Close sidebar when clicking on a link (mobile only)
sidebar.querySelectorAll('a').forEach(link => {
    link.addEventListener('click', () => {
        if (window.innerWidth <= 768) {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        }
    });
});

// Close sidebar on window resize if open
window.addEventListener('resize', () => {
    if (window.innerWidth > 768) {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
    }
});

// Set active class on current page link
document.querySelectorAll('.sidebar-nav a').forEach(link => {
    if (link.getAttribute('href') === window.location.pathname.split('/').pop()) {
        link.classList.add('active');
    }
});
</script>
</body>
</html>
