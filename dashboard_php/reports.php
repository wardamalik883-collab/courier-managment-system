<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user']) || ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'agent')) {
    header('Location: login.php');
    exit;
}

$connect = mysqli_connect("localhost", "root", "", "courier_management");
if (!$connect) {
    die("Connection failed: " . mysqli_connect_error());
}

$user_type = $_SESSION['user_type'];
$user_city = $_SESSION['city'] ?? '';

// Handle XLSX Export
if (isset($_GET['export'])) {
    $export_type = $_GET['export'];
    
    if ($export_type === 'shipments') {
        $where_clause = "";
        if ($user_type === 'agent') {
            $where_clause = "WHERE sender_city='$user_city' OR receiver_city='$user_city'";
        }
        
        // Date filter
        if (isset($_GET['from_date']) && $_GET['from_date']) {
            $from_date = mysqli_real_escape_string($connect, $_GET['from_date']);
            $where_clause .= ($where_clause ? " AND " : "WHERE ");
            $where_clause .= "booked_date >= '$from_date'";
        }
        if (isset($_GET['to_date']) && $_GET['to_date']) {
            $to_date = mysqli_real_escape_string($connect, $_GET['to_date']);
            $where_clause .= ($where_clause ? " AND " : "WHERE ");
            $where_clause .= "booked_date <= '$to_date'";
        }
        
        $query = "SELECT * FROM shipments $where_clause ORDER BY booked_date DESC";
        $result = mysqli_query($connect, $query);
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="shipments_report_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Tracking Number', 'Sender Name', 'Sender Phone', 'Sender City', 'Receiver Name', 'Receiver Phone', 'Receiver City', 'Type', 'Weight', 'Status', 'Amount', 'Booked Date', 'Delivery Date']);
        
        while ($row = mysqli_fetch_assoc($result)) {
            fputcsv($output, [
                $row['tracking_number'],
                $row['sender_name'],
                $row['sender_phone'],
                $row['sender_city'],
                $row['receiver_name'],
                $row['receiver_phone'],
                $row['receiver_city'],
                $row['type'],
                $row['weight'],
                $row['status'],
                $row['amount'],
                $row['booked_date'],
                $row['delivery_date'] ?? ''
            ]);
        }
        fclose($output);
        exit;
    }
    
    if ($export_type === 'bills') {
        $where_clause = "";
        if ($user_type === 'agent') {
            $where_clause = "WHERE city='$user_city'";
        }
        
        $query = "SELECT * FROM bills $where_clause ORDER BY issue_date DESC";
        $result = mysqli_query($connect, $query);
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="bills_report_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Bill ID', 'Customer', 'Phone', 'City', 'Type', 'Amount', 'Paid', 'Balance', 'Status', 'Issue Date', 'Due Date']);
        
        while ($row = mysqli_fetch_assoc($result)) {
            fputcsv($output, [
                $row['id'],
                $row['customer'],
                $row['phone'],
                $row['city'],
                $row['type'],
                $row['amount'],
                $row['paid'],
                ($row['amount'] - $row['paid']),
                $row['status'],
                $row['issue_date'],
                $row['due_date']
            ]);
        }
        fclose($output);
        exit;
    }
}

// Build query based on user type
$shipment_where = "WHERE 1=1";
$bill_where = "WHERE 1=1";

if ($user_type === 'agent') {
    $shipment_where .= " AND (sender_city='$user_city' OR receiver_city='$user_city')";
    $bill_where .= " AND city='$user_city'";
}

// Fetch summary stats
$stats_query = "SELECT 
    (SELECT COUNT(*) FROM shipments $shipment_where) as total_shipments,
    (SELECT COUNT(*) FROM shipments $shipment_where AND status='Delivered') as delivered_shipments,
    (SELECT COUNT(*) FROM bills $bill_where) as total_bills,
    (SELECT SUM(paid) FROM bills $bill_where AND status='Paid') as total_revenue,
    (SELECT SUM(amount-paid) FROM bills $bill_where AND status!='Paid') as pending_amount";
    
$stats_result = mysqli_query($connect, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Shipments by status
$shipment_status_query = "SELECT status, COUNT(*) as count FROM shipments $shipment_where GROUP BY status";
$shipment_status_result = mysqli_query($connect, $shipment_status_query);
$shipment_status = [];
while ($row = mysqli_fetch_assoc($shipment_status_result)) {
    $shipment_status[] = $row;
}

// Bills by type
$bills_type_query = "SELECT type, COUNT(*) as count, SUM(amount) as total, SUM(paid) as paid FROM bills $bill_where GROUP BY type";
$bills_type_result = mysqli_query($connect, $bills_type_query);
$bills_type = [];
while ($row = mysqli_fetch_assoc($bills_type_result)) {
    $bills_type[] = $row;
}

// City-wise shipments
$city_query = "SELECT sender_city as city, COUNT(*) as total, 
               SUM(status='Delivered') as delivered,
               SUM(status='In Transit' OR status='Out for Delivery') as transit,
               SUM(status='Pending') as pending,
               SUM(amount) as value 
               FROM shipments $shipment_where GROUP BY sender_city ORDER BY total DESC";
$city_result = mysqli_query($connect, $city_query);
$city_data = [];
while ($row = mysqli_fetch_assoc($city_result)) {
    $city_data[] = $row;
}

// Recent shipments
$recent_query = "SELECT * FROM shipments $shipment_where ORDER BY booked_date DESC LIMIT 10";
$recent_result = mysqli_query($connect, $recent_query);
$recent_shipments = [];
while ($row = mysqli_fetch_assoc($recent_result)) {
    $recent_shipments[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reports – Calma CMS</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:Arial,sans-serif;background:#f0f2f5;}
.layout{display:flex;min-height:100vh;}
.sidebar{width:220px;background:#1e293b;color:white;flex-shrink:0;transition:all 0.3s ease;}
.sidebar h2{color:#c8553d;text-align:center;margin:20px 0;}
.sidebar ul{list-style:none;padding:10px 0;}
.sidebar ul li{margin:4px 0;}
.sidebar ul li a{color:white;text-decoration:none;display:block;padding:12px 16px;border-radius:8px;transition:all 0.2s;}
.sidebar ul li a:hover{background:#374151;}
.sidebar ul li a.active{background:#c8553d;}
.main{flex:1;padding:20px;}
.header{display:flex;justify-content:space-between;align-items:center;background:#c8553d;color:white;padding:20px;border-radius:8px;margin-bottom:20px;}
.page-title{font-size:24px;font-weight:700;color:white;}
.header-btn{padding:12px 24px;background:white;color:#c8553d;border:none;border-radius:8px;cursor:pointer;font-size:14px;font-weight:700;text-decoration:none;display:inline-block;transition:all 0.3s;box-shadow:0 4px 12px rgba(0,0,0,0.15);}
.header-btn:hover{background:#f8fafc;transform:translateY(-2px);box-shadow:0 6px 16px rgba(0,0,0,0.2);}
.hamburger{display:none;flex-direction:column;cursor:pointer;padding:5px;background:none;border:none;}
.hamburger span{height:3px;width:25px;background:white;margin:4px 0;border-radius:2px;transition:all 0.3s ease;}
.hamburger.active span:nth-child(1){transform:rotate(45deg) translate(5px,5px);}
.hamburger.active span:nth-child(2){opacity:0;}
.hamburger.active span:nth-child(3){transform:rotate(-45deg) translate(7px,-6px);}
.stats-grid{display:flex;gap:15px;flex-wrap:wrap;margin-bottom:20px;}
.stat-card{flex:1 1 150px;background:white;padding:20px;border-radius:10px;text-align:center;box-shadow:0 4px 8px rgba(0,0,0,0.05);}
.stat-val{font-size:22px;font-weight:700;color:#c8553d;margin-bottom:5px;}
.stat-lbl{font-size:13px;color:#555;}
.card{background:white;border-radius:10px;box-shadow:0 4px 8px rgba(0,0,0,0.05);margin-bottom:20px;overflow:hidden;}
.card-header{background:#f8fafc;padding:15px 20px;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;}
.card-title{font-weight:600;font-size:14px;}
.card-body{padding:20px;}
.grid2{display:grid;grid-template-columns:repeat(2,1fr);gap:20px;}
.table-wrap{overflow-x:auto;}
.data-table{width:100%;border-collapse:collapse;}
.data-table th,.data-table td{padding:12px;text-align:left;border-bottom:1px solid #e2e8f0;font-size:13px;}
.data-table th{background:#f8fafc;font-weight:600;}
.progress-row{margin-bottom:15px;}
.progress-labels{display:flex;justify-content:space-between;margin-bottom:5px;font-size:13px;}
.progress-track{height:10px;background:#e2e8f0;border-radius:5px;overflow:hidden;}
.progress-fill{height:100%;border-radius:5px;}
.fill-teal{background:#14b8a6;}
.fill-green{background:#16a34a;}
.fill-blue{background:#3b82f6;}
.fill-amber{background:#f59e0b;}
.fill-red{background:#ef4444;}
.btn{padding:8px 16px;border:none;border-radius:6px;cursor:pointer;font-size:13px;font-weight:600;text-decoration:none;display:inline-block;}
.btn-primary{background:#c8553d;color:white;}
.btn-outline{background:white;border:1px solid #c8553d;color:#c8553d;}
.btn-success{background:#16a34a;color:white;}
.filter-bar{display:flex;gap:10px;padding:15px 20px;background:#f8fafc;border-bottom:1px solid #e2e8f0;flex-wrap:wrap;align-items:center;}
.filter-bar input,.filter-bar select{padding:8px 12px;border:1px solid #e2e8f0;border-radius:6px;font-size:13px;}
.badge{padding:4px 10px;border-radius:4px;font-size:11px;color:white;font-weight:600;}
.b-pending{background:#f59e0b;}
.b-transit{background:#3b82f6;}
.b-delivered{background:#16a34a;}
.b-returned{background:#ef4444;}
.city-pill{display:inline-block;padding:4px 12px;background:#e0e7ff;color:#4338ca;border-radius:20px;font-size:12px;font-weight:600;}
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
    .grid2{grid-template-columns:1fr;}
    .stats-grid{grid-template-columns:repeat(2,1fr);}
    .header{flex-wrap:wrap;gap:10px;padding:15px;}
    .page-title{font-size:20px;}
    .header-btn{padding:10px 18px;font-size:13px;}
    .filter-bar{flex-direction:column;}
    .filter-bar input,.filter-bar select{width:100%;}
}
@media(max-width:480px){
    .stats-grid{grid-template-columns:1fr;}
    .header{flex-direction:column;align-items:flex-start;}
    .header-btn{width:100%;text-align:center;}
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
      <li><a href="logout.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'logout.php' ? 'active' : ''; ?>">Logout</a></li>
    </ul>
  </div>

  <!-- Main Content -->
  <div class="main">
    <header class="header">
      <div style="display:flex;align-items:center;gap:10px;">
        <button class="hamburger" id="hamburger" aria-label="Toggle sidebar"><span></span><span></span><span></span></button>
        <span class="page-title">📊 Reports</span>
      </div>
    </header>

    <!-- Summary Stats -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-val"><?php echo $stats['total_shipments'] ?? 0; ?></div>
        <div class="stat-lbl">Total Shipments</div>
      </div>
      <div class="stat-card">
        <div class="stat-val"><?php echo $stats['delivered_shipments'] ?? 0; ?></div>
        <div class="stat-lbl">Delivered</div>
      </div>
      <div class="stat-card">
        <div class="stat-val"><?php echo $stats['total_bills'] ?? 0; ?></div>
        <div class="stat-lbl">Total Bills</div>
      </div>
      <div class="stat-card">
        <div class="stat-val">Rs. <?php echo number_format($stats['total_revenue'] ?? 0); ?></div>
        <div class="stat-lbl">Total Revenue</div>
      </div>
      <div class="stat-card">
        <div class="stat-val">Rs. <?php echo number_format($stats['pending_amount'] ?? 0); ?></div>
        <div class="stat-lbl">Pending Amount</div>
      </div>
    </div>

    <div class="grid2">
      <!-- Shipments by Status -->
      <div class="card">
        <div class="card-header">
          <span class="card-title">📦 Shipments by Status</span>
        </div>
        <div class="card-body">
          <?php
          $total_ships = array_sum(array_column($shipment_status, 'count'));
          $max = !empty($shipment_status) ? max(array_column($shipment_status, 'count')) : 0;
          $colors = ['Pending' => 'fill-amber', 'In Transit' => 'fill-blue', 'Out for Delivery' => 'fill-blue', 'Delivered' => 'fill-green', 'Returned' => 'fill-red'];
          ?>
          <?php if (empty($shipment_status)): ?>
            <p style="text-align:center;padding:40px;color:#64748b;">No shipments found</p>
          <?php else: ?>
          <?php foreach ($shipment_status as $row):
            $pct = $max > 0 ? round($row['count'] / $max * 100) : 0;
            $color = $colors[$row['status']] ?? 'fill-teal';
          ?>
          <div class="progress-row">
            <div class="progress-labels">
              <span><?php echo htmlspecialchars($row['status']); ?> (<?php echo $row['count']; ?>)</span>
              <span style="color:#64748b;"><?php echo $total_ships > 0 ? round($row['count'] / $total_ships * 100) : 0; ?>%</span>
            </div>
            <div class="progress-track"><div class="progress-fill <?php echo $color; ?>" style="width:<?php echo $pct; ?>%"></div></div>
          </div>
          <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- Bills by Type -->
      <div class="card">
        <div class="card-header">
          <span class="card-title">💰 Bills by Type</span>
        </div>
        <div class="card-body">
          <?php
          $total_bills = array_sum(array_column($bills_type, 'count'));
          $max = !empty($bills_type) ? max(array_column($bills_type, 'count')) : 0;
          ?>
          <?php if (empty($bills_type)): ?>
            <p style="text-align:center;padding:40px;color:#64748b;">No bills found</p>
          <?php else: ?>
          <?php foreach ($bills_type as $row):
            $pct = $max > 0 ? round($row['count'] / $max * 100) : 0;
          ?>
          <div class="progress-row">
            <div class="progress-labels">
              <span><?php echo htmlspecialchars($row['type']); ?> (<?php echo $row['count']; ?>)</span>
              <span style="color:#64748b;">Rs. <?php echo number_format($row['total']); ?></span>
            </div>
            <div class="progress-track"><div class="progress-fill fill-teal" style="width:<?php echo $pct; ?>%"></div></div>
          </div>
          <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- City-wise Overview -->
      <div class="card" style="grid-column:1/3;">
        <div class="card-header">
          <span class="card-title">🏙️ City-wise Overview</span>
          <form method="GET" class="filter-bar" style="padding:10px 20px;border:none;">
            <input type="hidden" name="export" value="shipments">
            <label style="font-size:12px;">From:</label>
            <input type="date" name="from_date">
            <label style="font-size:12px;">To:</label>
            <input type="date" name="to_date">
            <button type="submit" class="btn btn-success">Export to CSV</button>
          </form>
        </div>
        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>City</th>
                <th>Total Shipments</th>
                <th>Delivered</th>
                <th>In Transit</th>
                <th>Pending</th>
                <th>Total Value</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($city_data)): ?>
              <tr><td colspan="6" style="text-align:center;padding:40px;color:#64748b;">No data available</td></tr>
              <?php else: ?>
                <?php foreach ($city_data as $row): ?>
                <tr>
                  <td><span class="city-pill"><?php echo htmlspecialchars($row['city']); ?></span></td>
                  <td style="font-weight:600;"><?php echo $row['total']; ?></td>
                  <td style="color:#16a34a;"><?php echo $row['delivered'] ?? 0; ?></td>
                  <td style="color:#3b82f6;"><?php echo $row['transit'] ?? 0; ?></td>
                  <td style="color:#f59e0b;"><?php echo $row['pending'] ?? 0; ?></td>
                  <td style="font-weight:600;">Rs. <?php echo number_format($row['value'] ?? 0); ?></td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Recent Shipments -->
      <div class="card" style="grid-column:1/3;">
        <div class="card-header">
          <span class="card-title">📋 Recent Shipments</span>
          <a href="?export=shipments" class="btn btn-outline">Export All</a>
        </div>
        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Tracking #</th>
                <th>From</th>
                <th>To</th>
                <th>Type</th>
                <th>Status</th>
                <th>Amount</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($recent_shipments)): ?>
              <tr><td colspan="7" style="text-align:center;padding:40px;color:#64748b;">No shipments found</td></tr>
              <?php else: ?>
                <?php foreach ($recent_shipments as $s): 
                  $badge_class = 'b-pending';
                  if ($s['status'] === 'Delivered') $badge_class = 'b-delivered';
                  elseif ($s['status'] === 'In Transit' || $s['status'] === 'Out for Delivery') $badge_class = 'b-transit';
                  elseif ($s['status'] === 'Returned') $badge_class = 'b-returned';
                ?>
                <tr>
                  <td style="font-weight:600;color:#c8553d;"><?php echo htmlspecialchars($s['tracking_number']); ?></td>
                  <td><?php echo htmlspecialchars($s['sender_city']); ?></td>
                  <td><?php echo htmlspecialchars($s['receiver_city']); ?></td>
                  <td><?php echo htmlspecialchars($s['type']); ?></td>
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
    </div>

  </div>
</div>

<script>
// Hamburger menu
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

    overlay.addEventListener('click', closeSidebar);

    sidebar.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 768) {
                closeSidebar();
            }
        });
    });
}

let resizeTimer;
window.addEventListener('resize', () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => {
        if (window.innerWidth > 768) {
            closeSidebar();
        }
    }, 250);
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && sidebar.classList.contains('active')) {
        closeSidebar();
    }
});
</script>
</body>
</html>
