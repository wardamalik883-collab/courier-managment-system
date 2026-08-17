<?php
/**
 * DASHBOARD - index.php (Role-based)
 * Calma Courier Management System
 * 
 * Access Control:
 * - Admin: Full access to all data
 * - Agent: Access to shipments/bills for their city only
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

// Check if user is logged in with required role
if (!isset($_SESSION['user']) || !isset($_SESSION['user_type']) || !isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Only admin and agent can access this page
if (!in_array($_SESSION['user_type'], ['admin', 'agent'])) {
    header('Location: home.php');
    exit;
}

// Database connection
$connect = mysqli_connect("localhost", "root", "", "courier_management");
if (!$connect) {
    die("Connection failed: " . mysqli_connect_error());
}

// Get session variables with defaults
$user_id = (int)($_SESSION['user_id'] ?? 0);
$user_type = $_SESSION['user_type'] ?? '';
$user_city = $_SESSION['city'] ?? '';
$user_name = $_SESSION['user_name'] ?? $_SESSION['user']['name'] ?? 'User';
$username = $_SESSION['username'] ?? $_SESSION['user']['username'] ?? '';
$branch_code = $_SESSION['branch_code'] ?? '';

// Log page access
@mysqli_query($connect, "INSERT INTO user_activity_logs (user_id, user_type, action, ip_address, created_at) 
                        VALUES ('$user_id', '$user_type', 'Dashboard accessed', '{$_SERVER['REMOTE_ADDR']}', NOW())");

// ============================================
// BUILD QUERY FILTERS BASED ON USER ROLE
// ============================================

// Agent can only see data for their city
// Admin can see everything
if ($user_type === 'agent') {
    // Validate city is set for agent
    if (empty($user_city)) {
        // Agent without city assigned - show error or redirect
        error_log("Agent $user_id has no city assigned");
    }
    
    $shipment_filter = "WHERE (sender_city='" . mysqli_real_escape_string($connect, $user_city) . "' 
                          OR receiver_city='" . mysqli_real_escape_string($connect, $user_city) . "')";
    
    $bill_filter = "WHERE city='" . mysqli_real_escape_string($connect, $user_city) . "'";
} else {
    // Admin - no filters
    $shipment_filter = "";
    $bill_filter = "";
}

// ============================================
// FETCH BILL STATS
// ============================================
$bill_stats_query = "SELECT 
    COUNT(*) as total_bills,
    SUM(CASE WHEN status='Paid' THEN 1 ELSE 0 END) as paid_bills,
    SUM(CASE WHEN status='Unpaid' THEN 1 ELSE 0 END) as unpaid_bills,
    SUM(CASE WHEN status='Overdue' THEN 1 ELSE 0 END) as overdue_bills,
    COALESCE(SUM(CASE WHEN status='Paid' THEN paid ELSE 0 END), 0) as total_revenue,
    COALESCE(SUM(CASE WHEN status!='Paid' THEN (amount - paid) ELSE 0 END), 0) as pending_amount
    FROM bills $bill_filter";

$bill_stats = mysqli_fetch_assoc(mysqli_query($connect, $bill_stats_query));
$total_bills = (int)($bill_stats['total_bills'] ?? 0);
$paid_bills = (int)($bill_stats['paid_bills'] ?? 0);
$unpaid_bills = (int)($bill_stats['unpaid_bills'] ?? 0);
$overdue_bills = (int)($bill_stats['overdue_bills'] ?? 0);
$total_revenue = floatval($bill_stats['total_revenue'] ?? 0);
$pending_amount = floatval($bill_stats['pending_amount'] ?? 0);

// ============================================
// FETCH SHIPMENT STATS
// ============================================
$shipment_stats_query = "SELECT 
    COUNT(*) as total_shipments,
    SUM(CASE WHEN status='Delivered' THEN 1 ELSE 0 END) as delivered,
    SUM(CASE WHEN status IN ('In Transit', 'Out for Delivery') THEN 1 ELSE 0 END) as in_transit,
    SUM(CASE WHEN status='Pending' THEN 1 ELSE 0 END) as pending_ships,
    SUM(CASE WHEN status='Returned' THEN 1 ELSE 0 END) as returned
    FROM shipments $shipment_filter";

$shipment_stats = mysqli_fetch_assoc(mysqli_query($connect, $shipment_stats_query));
$total_shipments = (int)($shipment_stats['total_shipments'] ?? 0);
$delivered = (int)($shipment_stats['delivered'] ?? 0);
$in_transit = (int)($shipment_stats['in_transit'] ?? 0);
$pending_ships = (int)($shipment_stats['pending_ships'] ?? 0);
$returned = (int)($shipment_stats['returned'] ?? 0);

// ============================================
// FETCH RECENT SHIPMENTS (LIMIT 6)
// ============================================
$recent_ships_result = mysqli_query($connect, 
    "SELECT * FROM shipments $shipment_filter ORDER BY booked_date DESC LIMIT 6");
$recent_ships = [];
while ($row = mysqli_fetch_assoc($recent_ships_result)) {
    $recent_ships[] = $row;
}

// ============================================
// FETCH RECENT BILLS (LIMIT 6)
// ============================================
$recent_bills_result = mysqli_query($connect, 
    "SELECT * FROM bills $bill_filter ORDER BY issue_date DESC LIMIT 6");
$recent_bills = [];
while ($row = mysqli_fetch_assoc($recent_bills_result)) {
    $recent_bills[] = $row;
}

// ============================================
// ADMIN EXTRA STATS
// ============================================
if ($user_type === 'admin') {
    $total_agents = mysqli_fetch_row(mysqli_query($connect, 
        "SELECT COUNT(*) FROM agents WHERE status='Active'"))[0];
    $total_customers = mysqli_fetch_row(mysqli_query($connect, 
        "SELECT COUNT(*) FROM customers WHERE status='Active'"))[0];
}

mysqli_close($connect);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard – Calma CMS</title>
<link rel="stylesheet" href="css/style.css">
<style>
/* Page-specific overrides */
.layout{display:flex;min-height:100vh;}
.sidebar{width:220px;background:#1e293b;color:white;flex-shrink:0;transition:all 0.3s ease;}
.sidebar h2{color:#c8553d;text-align:center;margin:20px 0;}
.sidebar ul{list-style:none;padding:10px 0;}
.sidebar ul li{margin:4px 0;}
.sidebar ul li a{color:white;text-decoration:none;display:block;padding:12px 16px;border-radius:8px;transition:all 0.2s;}
.sidebar ul li a:hover{background:#374151;}
.sidebar ul li a.active{background:#c8553d;}
.main{flex:1;padding:20px;transition:all 0.3s ease;}
.header{display:flex;justify-content:space-between;align-items:center;background:#c8553d;color:white;padding:20px;border-radius:8px;margin-bottom:20px;position:relative;}
.header-btn{padding:12px 24px;background:white;color:#c8553d;border:none;border-radius:8px;cursor:pointer;font-size:14px;font-weight:700;text-decoration:none;display:inline-block;transition:all 0.3s;box-shadow:0 4px 12px rgba(0,0,0,0.15);}
.header-btn:hover{background:#f8fafc;transform:translateY(-2px);box-shadow:0 6px 16px rgba(0,0,0,0.2);}
.hamburger{display:none;flex-direction:column;cursor:pointer;padding:5px;background:none;border:none;}
.hamburger span{height:3px;width:25px;background:white;margin:4px 0;border-radius:2px;transition:all 0.3s ease;}
.hamburger.active span:nth-child(1){transform:rotate(45deg) translate(5px,5px);}
.hamburger.active span:nth-child(2){opacity:0;}
.hamburger.active span:nth-child(3){transform:rotate(-45deg) translate(7px,-6px);}
.welcome-text{font-size:14px;opacity:0.9;}
.role-badge{display:inline-block;padding:2px 8px;border-radius:4px;font-size:11px;background:rgba(255,255,255,0.2);margin-left:8px;}
.stats-grid{display:flex;gap:15px;flex-wrap:wrap;margin-bottom:20px;}
.stat-card{flex:1 1 150px;background:white;padding:20px;border-radius:10px;text-align:center;box-shadow:0 4px 8px rgba(0,0,0,0.05);}
.stat-val{font-size:22px;font-weight:700;color:#c8553d;margin-bottom:5px;}
.stat-lbl{font-size:13px;color:#555;}
.card{background:white;border-radius:10px;box-shadow:0 4px 8px rgba(0,0,0,0.05);margin-bottom:20px;overflow:hidden;}
.card-header{background:#f8fafc;padding:15px 20px;border-bottom:1px solid #e2e8f0;}
.card-title{font-weight:600;font-size:14px;}
.table-wrap{overflow-x:auto;}
.data-table{width:100%;border-collapse:collapse;}
.data-table th,.data-table td{padding:12px;text-align:left;border-bottom:1px solid #e2e8f0;font-size:13px;}
.data-table th{background:#f8fafc;font-weight:600;}
.tracking-num{font-weight:600;color:#c8553d;}
.badge{padding:4px 10px;border-radius:4px;font-size:11px;color:white;font-weight:600;}
.b-pending{background:#f59e0b;}
.b-transit{background:#3b82f6;}
.b-delivered{background:#16a34a;}
.b-returned{background:#ef4444;}
.btn{padding:6px 12px;border:none;border-radius:4px;cursor:pointer;font-size:12px;text-decoration:none;display:inline-block;}
.btn-primary{background:#c8553d;color:white;}
.btn-outline{background:white;border:1px solid #c8553d;color:#c8553d;}
.logout-btn{background:rgba(255,255,255,0.2);color:white;padding:8px 16px;border-radius:6px;text-decoration:none;font-size:13px;}
.logout-btn:hover{background:rgba(255,255,255,0.3);}
.person-info{display:flex;flex-direction:column;}
.pname{font-weight:600;}
.pcity{font-size:11px;color:#64748b;}
.type-tag{display:inline-flex;padding:3px 9px;border-radius:4px;font-size:11px;font-weight:600;background:#e0e7ff;color:#4338ca;}

/* Responsive overrides for this page */
@media(max-width:768px){
  .layout{flex-direction:column;}
  .sidebar{
    width:260px;
    position:fixed;
    top:0;
    left:-260px;
    height:100vh;
    z-index:1000;
    overflow-y:auto;
    transition:left 0.3s cubic-bezier(0.4,0,0.2,1);
  }
  .sidebar.active{left:0;}
  .hamburger{display:flex;}
  .main{margin-left:0;width:100%;}
  .stats-grid{grid-template-columns:repeat(2,1fr);}
  .header{flex-wrap:wrap;gap:10px;}
  .header h1{font-size:20px;}
}
@media(max-width:480px){
  .stats-grid{grid-template-columns:1fr;}
}
</style>
</head>
<body>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<div class="layout">
  <!-- Sidebar -->
  <div class="sidebar" id="sidebar">
    <h2>Calma CMS</h2>
    <ul>
      <li><a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">Dashboard</a></li>
      <?php if($user_type !== 'user'): ?>
      <li><a href="shipments.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'shipments.php' ? 'active' : ''; ?>">Shipments</a></li>
      <li><a href="bills.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'bills.php' ? 'active' : ''; ?>">Bills</a></li>
      <li><a href="customers.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'customers.php' ? 'active' : ''; ?>">Customers</a></li>
      <li><a href="sms.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'sms.php' ? 'active' : ''; ?>">SMS Notifications</a></li>
      <?php endif; ?>
      <?php if($user_type === 'admin'): ?>
      <li><a href="agents.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'agents.php' ? 'active' : ''; ?>">Agents</a></li>
      <li><a href="reports.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">Reports</a></li>
      <?php endif; ?>
      <?php if($user_type === 'user'): ?>
      <li><a href="user_dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'user_dashboard.php' ? 'active' : ''; ?>">My Dashboard</a></li>
      <li><a href="track_shipment.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'track_shipment.php' ? 'active' : ''; ?>">Track Shipment</a></li>
      <li><a href="my_shipments.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'my_shipments.php' ? 'active' : ''; ?>">My Shipments</a></li>
      <?php endif; ?>
      <li><a href="logout.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'logout.php' ? 'active' : ''; ?>">Logout</a></li>
    </ul>
  </div>

  <!-- Main Content -->
  <div class="main">
    <header class="header">
      <div style="display:flex;align-items:center;gap:10px;">
        <button class="hamburger" id="hamburger" aria-label="Toggle sidebar">
          <span></span>
          <span></span>
          <span></span>
        </button>
        <div>
          <h1 style="margin:0;font-size:24px;">Dashboard</h1>
          <div class="welcome-text">
              Welcome back, <?php echo htmlspecialchars($user_name); ?>!
              <span class="role-badge"><?php echo ucfirst($user_type); ?></span>
              <?php if($user_type === 'agent' && !empty($user_city)): ?>
                  <span class="role-badge">📍 <?php echo htmlspecialchars($user_city); ?></span>
              <?php endif; ?>
          </div>
        </div>
      </div>
      <a href="logout.php" class="header-btn">Logout</a>
    </header>

    <!-- ===== BILL STATS ===== -->
    <div class="stats-grid">
      <div class="stat-card"><div class="stat-val"><?php echo $total_bills; ?></div><div class="stat-lbl">Total Bills</div></div>
      <div class="stat-card"><div class="stat-val"><?php echo $paid_bills; ?></div><div class="stat-lbl">Paid</div></div>
      <div class="stat-card"><div class="stat-val"><?php echo $unpaid_bills; ?></div><div class="stat-lbl">Unpaid</div></div>
      <div class="stat-card"><div class="stat-val"><?php echo $overdue_bills; ?></div><div class="stat-lbl">Overdue</div></div>
      <div class="stat-card"><div class="stat-val">Rs.<?php echo number_format($total_revenue ?? 0); ?></div><div class="stat-lbl">Revenue</div></div>
      <div class="stat-card"><div class="stat-val">Rs.<?php echo number_format($pending_amount ?? 0); ?></div><div class="stat-lbl">Pending</div></div>
    </div>

    <!-- ===== SHIPMENT STATS ===== -->
    <div class="stats-grid">
      <div class="stat-card"><div class="stat-val"><?php echo $total_shipments; ?></div><div class="stat-lbl">Total Shipments</div></div>
      <div class="stat-card"><div class="stat-val"><?php echo $delivered; ?></div><div class="stat-lbl">Delivered</div></div>
      <div class="stat-card"><div class="stat-val"><?php echo $in_transit; ?></div><div class="stat-lbl">In Transit</div></div>
      <div class="stat-card"><div class="stat-val"><?php echo $pending_ships; ?></div><div class="stat-lbl">Pending</div></div>
      <div class="stat-card"><div class="stat-val"><?php echo $returned; ?></div><div class="stat-lbl">Returned</div></div>
    </div>

    <?php if($user_type === 'admin'): ?>
    <!-- ===== ADMIN EXTRA STATS ===== -->
    <div class="stats-grid">
      <div class="stat-card"><div class="stat-val"><?php echo $total_agents ?? 0; ?></div><div class="stat-lbl">Active Agents</div></div>
      <div class="stat-card"><div class="stat-val"><?php echo $total_customers ?? 0; ?></div><div class="stat-lbl">Active Customers</div></div>
    </div>
    <?php endif; ?>

    <!-- ===== RECENT SHIPMENTS TABLE ===== -->
    <div class="card">
      <div class="card-header">
        <span class="card-title">Recent Shipments</span>
      </div>
      <div class="table-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th>Tracking #</th>
              <th>Sender</th>
              <th>Receiver</th>
              <th>Type</th>
              <th>Status</th>
              <th>Amount</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($recent_ships)): ?>
            <tr><td colspan="7" style="text-align:center;padding:40px;color:#64748b;">No shipments found</td></tr>
            <?php else: ?>
              <?php foreach ($recent_ships as $s):
                $badge_class = 'b-pending';
                if ($s['status'] === 'Delivered') $badge_class = 'b-delivered';
                elseif ($s['status'] === 'In Transit' || $s['status'] === 'Out for Delivery') $badge_class = 'b-transit';
                elseif ($s['status'] === 'Returned') $badge_class = 'b-returned';
              ?>
              <tr>
                <td><span class="tracking-num"><?php echo htmlspecialchars($s['tracking_number']); ?></span></td>
                <td>
                  <div class="person-info">
                    <span class="pname"><?php echo htmlspecialchars($s['sender_name']); ?></span>
                    <span class="pcity"><?php echo htmlspecialchars($s['sender_city']); ?></span>
                  </div>
                </td>
                <td>
                  <div class="person-info">
                    <span class="pname"><?php echo htmlspecialchars($s['receiver_name']); ?></span>
                    <span class="pcity"><?php echo htmlspecialchars($s['receiver_city']); ?></span>
                  </div>
                </td>
                <td><span class="type-tag"><?php echo htmlspecialchars($s['type']); ?></span></td>
                <td><span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($s['status']); ?></span></td>
                <td style="font-weight:600;">Rs. <?php echo number_format($s['amount']); ?></td>
                <td style="color:#64748b;font-size:12px;"><?php echo $s['booked_date']; ?></td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ===== RECENT BILLS TABLE ===== -->
    <div class="card">
      <div class="card-header">
        <span class="card-title">Recent Bills</span>
      </div>
      <div class="table-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th>Bill ID</th>
              <th>Customer</th>
              <th>Type</th>
              <th>Amount</th>
              <th>Paid</th>
              <th>Balance</th>
              <th>Status</th>
              <th>Due Date</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($recent_bills)): ?>
            <tr><td colspan="8" style="text-align:center;padding:40px;color:#64748b;">No bills found</td></tr>
            <?php else: ?>
              <?php foreach ($recent_bills as $bill):
                $balance = $bill['amount'] - $bill['paid'];
                $badge_class = 'b-pending';
                if ($bill['status'] === 'Paid') $badge_class = 'b-delivered';
                elseif ($bill['status'] === 'Overdue') $badge_class = 'b-returned';
                elseif ($bill['status'] === 'Partial') $badge_class = 'b-transit';
              ?>
              <tr>
                <td><span class="tracking-num"><?php echo htmlspecialchars($bill['id']); ?></span></td>
                <td>
                  <div class="person-info">
                    <span class="pname"><?php echo htmlspecialchars($bill['customer']); ?></span>
                    <span class="pcity"><?php echo htmlspecialchars($bill['city']); ?></span>
                  </div>
                </td>
                <td><span class="type-tag"><?php echo htmlspecialchars($bill['type']); ?></span></td>
                <td style="font-weight:600;">Rs. <?php echo number_format($bill['amount']); ?></td>
                <td style="color:#16a34a;font-weight:600;">Rs. <?php echo number_format($bill['paid']); ?></td>
                <td style="color:#ef4444;font-weight:600;">Rs. <?php echo number_format($balance); ?></td>
                <td><span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($bill['status']); ?></span></td>
                <td style="color:#64748b;font-size:12px;"><?php echo $bill['due_date']; ?></td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>

<script>
// Hamburger toggle for mobile
const hamburger = document.getElementById('hamburger');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');

function openSidebar() {
    sidebar.classList.add('active');
    hamburger.classList.add('active');
    overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeSidebar() {
    sidebar.classList.remove('active');
    hamburger.classList.remove('active');
    overlay.classList.remove('active');
    document.body.style.overflow = '';
}

if (hamburger && sidebar && overlay) {
    hamburger.addEventListener('click', () => {
        if (sidebar.classList.contains('active')) {
            closeSidebar();
        } else {
            openSidebar();
        }
    });

    // Close sidebar when clicking overlay
    overlay.addEventListener('click', closeSidebar);

    // Close sidebar when clicking on a link
    sidebar.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 768) {
                closeSidebar();
            }
        });
    });
}

// Close sidebar on window resize if open
let resizeTimer;
window.addEventListener('resize', () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => {
        if (window.innerWidth > 768) {
            closeSidebar();
        }
    }, 250);
});

// Handle Escape key to close sidebar
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && sidebar.classList.contains('active')) {
        closeSidebar();
    }
});
</script>
</body>
</html>
