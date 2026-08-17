<?php
/**
 * SHIPMENTS MANAGEMENT
 * Calma Courier Management System
 * 
 * Access Control:
 * - Admin: Can see all shipments
 * - Agent: Can only see shipments from/to their city
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

// Get session variables with proper validation
$user_id = (int)($_SESSION['user_id'] ?? 0);
$user_type = $_SESSION['user_type'] ?? '';
$user_city = $_SESSION['city'] ?? '';
$branch_code = $_SESSION['branch_code'] ?? '';
$username = $_SESSION['username'] ?? $_SESSION['user']['username'] ?? '';
$user_name = $_SESSION['user_name'] ?? $_SESSION['user']['name'] ?? 'User';

// Validate user has required data
if ($user_type === 'agent' && empty($user_city)) {
    error_log("Agent $user_id ($username) has no city assigned");
}

// ============================================
// HANDLE FORM SUBMISSIONS
// ============================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $tracking_number = mysqli_real_escape_string($connect, generateTrackingNumber($connect));
        $sender_name = mysqli_real_escape_string($connect, $_POST['sender_name']);
        $sender_phone = mysqli_real_escape_string($connect, $_POST['sender_phone']);
        $sender_city = mysqli_real_escape_string($connect, $_POST['sender_city']);
        $sender_address = mysqli_real_escape_string($connect, $_POST['sender_address']);
        $sender_email = mysqli_real_escape_string($connect, $_POST['sender_email']);
        $receiver_name = mysqli_real_escape_string($connect, $_POST['receiver_name']);
        $receiver_phone = mysqli_real_escape_string($connect, $_POST['receiver_phone']);
        $receiver_city = mysqli_real_escape_string($connect, $_POST['receiver_city']);
        $receiver_address = mysqli_real_escape_string($connect, $_POST['receiver_address']);
        $receiver_email = mysqli_real_escape_string($connect, $_POST['receiver_email']);
        $type = mysqli_real_escape_string($connect, $_POST['type']);
        $weight = mysqli_real_escape_string($connect, $_POST['weight']);
        $amount = mysqli_real_escape_string($connect, $_POST['amount']);
        $delivery_date = mysqli_real_escape_string($connect, $_POST['delivery_date']);
        $notes = mysqli_real_escape_string($connect, $_POST['notes']);
        $agent_id = ($user_type === 'agent') ? $user_id : 'NULL';
        $created_by = mysqli_real_escape_string($connect, $username);

        $query = "INSERT INTO shipments (tracking_number, sender_name, sender_phone, sender_city, sender_address, sender_email,
                                        receiver_name, receiver_phone, receiver_city, receiver_address, receiver_email,
                                        type, weight, amount, delivery_date, notes, agent_id, created_by)
                  VALUES ('$tracking_number', '$sender_name', '$sender_phone', '$sender_city', '$sender_address', '$sender_email',
                          '$receiver_name', '$receiver_phone', '$receiver_city', '$receiver_address', '$receiver_email',
                          '$type', '$weight', '$amount', '$delivery_date', '$notes', $agent_id, '$created_by')";

        if (mysqli_query($connect, $query)) {
            $shipment_id = mysqli_insert_id($connect);
            // Add tracking history
            mysqli_query($connect, "INSERT INTO shipment_tracking (tracking_number, status, description, updated_by)
                                    VALUES ('$tracking_number', 'Pending', 'Shipment booked successfully', '$created_by')");
            
            // Log activity
            mysqli_query($connect, "INSERT INTO user_activity_logs (user_id, user_type, action, ip_address, created_at) 
                                    VALUES ('$user_id', '$user_type', 'Created shipment: $tracking_number', '{$_SERVER['REMOTE_ADDR']}', NOW())");
            
            header("Location: shipments.php?success=created");
            exit;
        } else {
            $error = "Error creating shipment: " . mysqli_error($connect);
        }
    }

    if ($action === 'edit') {
        $id = intval($_POST['id']);
        $status = mysqli_real_escape_string($connect, $_POST['status']);
        $amount = mysqli_real_escape_string($connect, $_POST['amount']);
        $delivery_date = mysqli_real_escape_string($connect, $_POST['delivery_date']);
        $notes = mysqli_real_escape_string($connect, $_POST['notes']);
        $payment_status = mysqli_real_escape_string($connect, $_POST['payment_status']);

        // Verify agent can only edit their city's shipments
        if ($user_type === 'agent') {
            $check_query = "SELECT sender_city, receiver_city FROM shipments WHERE id=$id";
            $check_result = mysqli_query($connect, $check_query);
            $shipment_data = mysqli_fetch_assoc($check_result);
            
            if ($shipment_data['sender_city'] !== $user_city && $shipment_data['receiver_city'] !== $user_city) {
                // Agent trying to edit shipment outside their jurisdiction
                mysqli_query($connect, "INSERT INTO user_activity_logs (user_id, user_type, action, ip_address, created_at) 
                                        VALUES ('$user_id', '$user_type', 'Unauthorized edit attempt on shipment $id', '{$_SERVER['REMOTE_ADDR']}', NOW())");
                header("Location: shipments.php?error=unauthorized");
                exit;
            }
        }

        // Get current shipment data
        $shipment_query = "SELECT * FROM shipments WHERE id=$id";
        $shipment_result = mysqli_query($connect, $shipment_query);
        $shipment = mysqli_fetch_assoc($shipment_result);
        $old_status = $shipment['status'];

        $delivered_date = 'NULL';
        if ($status === 'Delivered') {
            $delivered_date = 'NOW()';
        }

        $query = "UPDATE shipments SET status='$status', amount='$amount', delivery_date='$delivery_date',
                  notes='$notes', payment_status='$payment_status', delivered_date=$delivered_date
                  WHERE id=$id";

        if (mysqli_query($connect, $query)) {
            // Auto-create bill if status changed to Delivered and no bill exists
            if ($status === 'Delivered' && $old_status !== 'Delivered') {
                $tracking_number = $shipment['tracking_number'];
                $check_bill = mysqli_query($connect, "SELECT id FROM bills WHERE description LIKE '%shipment $tracking_number%'");
                if (mysqli_num_rows($check_bill) === 0) {
                    // No bill exists, create one
                    $bill_id = 'BILL-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
                    $customer_name = mysqli_real_escape_string($connect, $shipment['receiver_name']);
                    $customer_phone = mysqli_real_escape_string($connect, $shipment['receiver_phone']);
                    $city = mysqli_real_escape_string($connect, $shipment['receiver_city']);
                    $bill_amount = floatval($shipment['amount']);
                    $bill_type = 'Shipping';
                    $issue_date = date('Y-m-d');
                    $due_date = date('Y-m-d', strtotime('+7 days'));
                    $bill_description = "Bill for shipment $tracking_number - Delivery";

                    $bill_query = "INSERT INTO bills (id, customer, phone, city, type, amount, paid, status, issue_date, due_date, description)
                                   VALUES ('$bill_id', '$customer_name', '$customer_phone', '$city', '$bill_type', '$bill_amount', '0', 'Unpaid', '$issue_date', '$due_date', '$bill_description')";
                    mysqli_query($connect, $bill_query);
                }
            }

            // Add tracking history
            $tracking_row = mysqli_fetch_assoc(mysqli_query($connect, "SELECT tracking_number FROM shipments WHERE id=$id"));
            $updated_by = mysqli_real_escape_string($connect, $username);
            mysqli_query($connect, "INSERT INTO shipment_tracking (tracking_number, status, description, updated_by)
                                    VALUES ('{$tracking_row['tracking_number']}', '$status', 'Status updated to $status', '$updated_by')");
            
            // Log activity
            mysqli_query($connect, "INSERT INTO user_activity_logs (user_id, user_type, action, ip_address, created_at) 
                                    VALUES ('$user_id', '$user_type', 'Updated shipment ID: $id', '{$_SERVER['REMOTE_ADDR']}', NOW())");
            
            header("Location: shipments.php?success=updated");
            exit;
        } else {
            $error = "Error updating shipment: " . mysqli_error($connect);
        }
    }

    if ($action === 'delete') {
        $id = intval($_POST['id']);
        
        // Verify agent can only delete their city's shipments
        if ($user_type === 'agent') {
            $check_query = "SELECT sender_city, receiver_city FROM shipments WHERE id=$id";
            $check_result = mysqli_query($connect, $check_query);
            $shipment_data = mysqli_fetch_assoc($check_result);
            
            if ($shipment_data['sender_city'] !== $user_city && $shipment_data['receiver_city'] !== $user_city) {
                mysqli_query($connect, "INSERT INTO user_activity_logs (user_id, user_type, action, ip_address, created_at) 
                                        VALUES ('$user_id', '$user_type', 'Unauthorized delete attempt on shipment $id', '{$_SERVER['REMOTE_ADDR']}', NOW())");
                header("Location: shipments.php?error=unauthorized");
                exit;
            }
        }
        
        if (mysqli_query($connect, "DELETE FROM shipments WHERE id=$id")) {
            // Log activity
            mysqli_query($connect, "INSERT INTO user_activity_logs (user_id, user_type, action, ip_address, created_at) 
                                    VALUES ('$user_id', '$user_type', 'Deleted shipment ID: $id', '{$_SERVER['REMOTE_ADDR']}', NOW())");
            
            header("Location: shipments.php?success=deleted");
            exit;
        } else {
            $error = "Error deleting shipment: " . mysqli_error($connect);
        }
    }
}

// Handle GET delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // Verify agent can only delete their city's shipments
    if ($user_type === 'agent') {
        $check_query = "SELECT sender_city, receiver_city FROM shipments WHERE id=$id";
        $check_result = mysqli_query($connect, $check_query);
        $shipment_data = mysqli_fetch_assoc($check_result);
        
        if ($shipment_data['sender_city'] !== $user_city && $shipment_data['receiver_city'] !== $user_city) {
            header("Location: shipments.php?error=unauthorized");
            exit;
        }
    }
    
    mysqli_query($connect, "DELETE FROM shipments WHERE id=$id");
    header("Location: shipments.php?success=deleted");
    exit;
}

// ============================================
// HELPER FUNCTIONS
// ============================================

// Generate tracking number
function generateTrackingNumber($connect) {
    $prefix = "CLM-" . date('Y') . "-";
    $query = "SELECT tracking_number FROM shipments WHERE tracking_number LIKE '$prefix%' ORDER BY id DESC LIMIT 1";
    $result = mysqli_query($connect, $query);
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $last_num = intval(substr($row['tracking_number'], -4));
        return $prefix . str_pad($last_num + 1, 4, '0', STR_PAD_LEFT);
    }
    return $prefix . "0001";
}

// ============================================
// BUILD QUERY BASED ON USER ROLE
// ============================================

$where_clause = "";
if ($user_type === 'agent') {
    // Agent can only see shipments from their city or to their city
    $where_clause = "WHERE (sender_city='" . mysqli_real_escape_string($connect, $user_city) . "' 
                      OR receiver_city='" . mysqli_real_escape_string($connect, $user_city) . "')";
}

// Search and filter
$search = isset($_GET['search']) ? mysqli_real_escape_string($connect, $_GET['search']) : '';
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($connect, $_GET['status']) : '';
$type_filter = isset($_GET['type']) ? mysqli_real_escape_string($connect, $_GET['type']) : '';

if ($search) {
    $where_clause .= ($where_clause ? " AND " : "WHERE ");
    $where_clause .= "(tracking_number LIKE '%$search%' OR sender_name LIKE '%$search%' OR receiver_name LIKE '%$search%')";
}
if ($status_filter) {
    $where_clause .= ($where_clause ? " AND " : "WHERE ");
    $where_clause .= "status='$status_filter'";
}
if ($type_filter) {
    $where_clause .= ($where_clause ? " AND " : "WHERE ");
    $where_clause .= "type='$type_filter'";
}

// ============================================
// FETCH SHIPMENTS
// ============================================
$query = "SELECT * FROM shipments $where_clause ORDER BY booked_date DESC";
$shipments_result = mysqli_query($connect, $query);
$shipments = [];
while ($row = mysqli_fetch_assoc($shipments_result)) {
    $shipments[] = $row;
}

// ============================================
// FETCH STATS
// ============================================
$stats_query = "SELECT
    COUNT(*) as total,
    SUM(CASE WHEN status='Delivered' THEN 1 ELSE 0 END) as delivered,
    SUM(CASE WHEN status IN ('In Transit', 'Out for Delivery') THEN 1 ELSE 0 END) as in_transit,
    SUM(CASE WHEN status='Pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status='Returned' THEN 1 ELSE 0 END) as returned
    FROM shipments $where_clause";
    
$stats_result = mysqli_query($connect, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

mysqli_close($connect);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Shipments – Calma CMS</title>
<link rel="stylesheet" href="css/style.css">
<style>
/* Page-specific styles */
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
.btn{padding:6px 12px;border:none;border-radius:4px;cursor:pointer;font-size:12px;text-decoration:none;display:inline-block;line-height:1.4;}
.btn-primary{background:#c8553d;color:white;}
.btn-outline{background:white;border:1px solid #c8553d;color:#c8553d;}
.btn-danger{background:#ef4444;color:white;}
.btn-sm{padding:4px 10px;font-size:11px;white-space:nowrap;}
.filter-bar{display:flex;gap:10px;padding:15px 20px;background:#f8fafc;border-bottom:1px solid #e2e8f0;flex-wrap:wrap;align-items:center;}
.filter-bar input,.filter-bar select{padding:8px 12px;border:1px solid #e2e8f0;border-radius:6px;font-size:13px;}
.filter-bar input{flex:1;min-width:200px;}
.modal-overlay{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);display:none;justify-content:center;align-items:center;z-index:1000;}
.modal{background:white;padding:25px;border-radius:12px;width:90%;max-width:700px;max-height:90vh;overflow-y:auto;}
.modal-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;padding-bottom:15px;border-bottom:1px solid #e2e8f0;}
.modal-title{font-weight:600;font-size:18px;}
.modal-close{background:none;border:none;font-size:24px;cursor:pointer;color:#64748b;}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:15px;}
.form-full{grid-column:1/-1;}
.form-group{margin-bottom:12px;}
.form-group label{display:block;font-size:11px;font-weight:600;letter-spacing:1px;text-transform:uppercase;color:#64748b;margin-bottom:5px;}
.form-group input,.form-group select,.form-group textarea{width:100%;padding:10px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:14px;}
.form-group input:focus,.form-group select:focus,.form-group textarea:focus{outline:none;border-color:#c8553d;}
.section-divider{font-size:12px;font-weight:700;color:#c8553d;margin:20px 0 10px;padding-bottom:8px;border-bottom:2px solid #c8553d;}
.modal-footer{margin-top:20px;padding-top:15px;border-top:1px solid #e2e8f0;display:flex;justify-content:flex-end;gap:10px;}
.success-msg{background:#86efac;color:#166534;padding:12px;border-radius:8px;margin-bottom:15px;border:1px solid #16a34a;}
.error-msg{background:#fca5a5;color:#991b1b;padding:12px;border-radius:8px;margin-bottom:15px;border:1px solid #f87171;}
.person-info{display:flex;flex-direction:column;}
.pname{font-weight:600;}
.pcity{font-size:11px;color:#64748b;}

/* Responsive */
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
        <span class="page-title">📦 Shipments</span>
      </div>
      <button class="header-btn" onclick="openModal('addShipModal')">+ Add Shipment</button>
    </header>

    <?php if(isset($_GET['success'])):
      $msg = $_GET['success'] === 'created' ? 'Shipment created successfully!' :
             ($_GET['success'] === 'updated' ? 'Shipment updated successfully!' : 'Shipment deleted successfully!');
    ?>
      <div class="success-msg">✓ <?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>

    <?php if(isset($_GET['error']) && $_GET['error'] === 'unauthorized'): ?>
      <div class="error-msg">⚠ You are not authorized to perform this action on shipments outside your city</div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-grid">
      <div class="stat-card"><div class="stat-val"><?php echo $stats['total'] ?? 0; ?></div><div class="stat-lbl">Total</div></div>
      <div class="stat-card"><div class="stat-val"><?php echo $stats['delivered'] ?? 0; ?></div><div class="stat-lbl">Delivered</div></div>
      <div class="stat-card"><div class="stat-val"><?php echo $stats['in_transit'] ?? 0; ?></div><div class="stat-lbl">In Transit</div></div>
      <div class="stat-card"><div class="stat-val"><?php echo $stats['pending'] ?? 0; ?></div><div class="stat-lbl">Pending</div></div>
      <div class="stat-card"><div class="stat-val"><?php echo $stats['returned'] ?? 0; ?></div><div class="stat-lbl">Returned</div></div>
    </div>

    <!-- Shipments Table -->
    <div class="card">
      <div class="card-header">
        <span class="card-title">All Shipments</span>
        <?php if($user_type === 'agent'): ?>
          <span style="font-size:11px;color:#64748b;">📍 Showing shipments for: <?php echo htmlspecialchars($user_city); ?></span>
        <?php endif; ?>
      </div>

      <!-- Filter Bar -->
      <form method="GET" class="filter-bar">
        <input type="text" name="search" placeholder="Search tracking #, sender, receiver..." value="<?php echo htmlspecialchars($search); ?>">
        <select name="status">
          <option value="">All Status</option>
          <option value="Pending" <?php echo $status_filter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
          <option value="In Transit" <?php echo $status_filter === 'In Transit' ? 'selected' : ''; ?>>In Transit</option>
          <option value="Delivered" <?php echo $status_filter === 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
          <option value="Returned" <?php echo $status_filter === 'Returned' ? 'selected' : ''; ?>>Returned</option>
        </select>
        <select name="type">
          <option value="">All Types</option>
          <option value="Standard" <?php echo $type_filter === 'Standard' ? 'selected' : ''; ?>>Standard</option>
          <option value="Express" <?php echo $type_filter === 'Express' ? 'selected' : ''; ?>>Express</option>
          <option value="Overnight" <?php echo $type_filter === 'Overnight' ? 'selected' : ''; ?>>Overnight</option>
        </select>
        <button type="submit" class="btn btn-primary btn-sm">Search</button>
        <a href="shipments.php" class="btn btn-outline btn-sm">Reset</a>
      </form>

      <div class="table-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th>Tracking #</th>
              <th>Sender</th>
              <th>Receiver</th>
              <th>Type</th>
              <th>Weight</th>
              <th>Status</th>
              <th>Amount</th>
              <th>Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($shipments)): ?>
            <tr><td colspan="9" style="text-align:center;padding:40px;color:#64748b;">No shipments found</td></tr>
            <?php else: ?>
              <?php foreach ($shipments as $s):
                $badge_class = 'b-pending';
                if ($s['status'] === 'Delivered') $badge_class = 'b-delivered';
                elseif ($s['status'] === 'In Transit' || $s['status'] === 'Out for Delivery') $badge_class = 'b-transit';
                elseif ($s['status'] === 'Returned') $badge_class = 'b-returned';
              ?>
              <tr>
                <td><span class="tracking-num"><?php echo htmlspecialchars($s['tracking_number']); ?></span></td>
                <td>
                  <div style="font-weight:600;"><?php echo htmlspecialchars($s['sender_name']); ?></div>
                  <div style="font-size:11px;color:#64748b;"><?php echo htmlspecialchars($s['sender_city']); ?></div>
                </td>
                <td>
                  <div style="font-weight:600;"><?php echo htmlspecialchars($s['receiver_name']); ?></div>
                  <div style="font-size:11px;color:#64748b;"><?php echo htmlspecialchars($s['receiver_city']); ?></div>
                </td>
                <td><?php echo htmlspecialchars($s['type']); ?></td>
                <td><?php echo $s['weight'] ? htmlspecialchars($s['weight']).' kg' : '-'; ?></td>
                <td><span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($s['status']); ?></span></td>
                <td style="font-weight:600;">Rs. <?php echo number_format($s['amount']); ?></td>
                <td style="color:#64748b;font-size:12px;"><?php echo $s['booked_date']; ?></td>
                <td>
                  <button class="btn btn-outline btn-sm" onclick='openEditModal(<?php echo json_encode($s, JSON_HEX_APOS | JSON_HEX_TAG); ?>)'>Edit</button>
                  <a href="print_shipment.php?tracking=<?php echo urlencode($s['tracking_number']); ?>" class="btn btn-outline btn-sm" target="_blank">Print</a>
                  <button class="btn btn-danger btn-sm" onclick="if(confirm('Delete this shipment?')) deleteShipment(<?php echo $s['id']; ?>)">Delete</button>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- ADD SHIPMENT MODAL -->
<div class="modal-overlay" id="addShipModal">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Add New Shipment</span>
      <button class="modal-close" onclick="closeModal('addShipModal')">×</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add">

      <div class="section-divider">📤 Sender Information</div>
      <div class="form-grid">
        <div class="form-group"><label>Name *</label><input type="text" name="sender_name" required></div>
        <div class="form-group"><label>Phone *</label><input type="text" name="sender_phone" required></div>
        <div class="form-group"><label>Email</label><input type="email" name="sender_email"></div>
        <div class="form-group"><label>City *</label><input type="text" name="sender_city" required></div>
        <div class="form-group form-full"><label>Address</label><textarea name="sender_address" rows="2"></textarea></div>
      </div>

      <div class="section-divider">📥 Receiver Information</div>
      <div class="form-grid">
        <div class="form-group"><label>Name *</label><input type="text" name="receiver_name" required></div>
        <div class="form-group"><label>Phone *</label><input type="text" name="receiver_phone" required></div>
        <div class="form-group"><label>Email</label><input type="email" name="receiver_email"></div>
        <div class="form-group"><label>City *</label><input type="text" name="receiver_city" required></div>
        <div class="form-group form-full"><label>Address</label><textarea name="receiver_address" rows="2"></textarea></div>
      </div>

      <div class="section-divider">📦 Shipment Details</div>
      <div class="form-grid">
        <div class="form-group"><label>Type</label>
          <select name="type">
            <option value="Standard">Standard</option>
            <option value="Express">Express</option>
            <option value="Overnight">Overnight</option>
          </select>
        </div>
        <div class="form-group"><label>Weight (kg)</label><input type="number" name="weight" step="0.1"></div>
        <div class="form-group"><label>Amount (Rs.) *</label><input type="number" name="amount" required></div>
        <div class="form-group"><label>Delivery Date</label><input type="date" name="delivery_date"></div>
        <div class="form-group form-full"><label>Notes</label><textarea name="notes" rows="2"></textarea></div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('addShipModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Create Shipment</button>
      </div>
    </form>
  </div>
</div>

<!-- EDIT SHIPMENT MODAL -->
<div class="modal-overlay" id="editShipModal">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Edit Shipment</span>
      <button class="modal-close" onclick="closeModal('editShipModal')">×</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" id="edit_id">

      <div class="form-grid">
        <div class="form-group"><label>Status</label>
          <select name="status" id="edit_status">
            <option value="Pending">Pending</option>
            <option value="In Transit">In Transit</option>
            <option value="Out for Delivery">Out for Delivery</option>
            <option value="Delivered">Delivered</option>
            <option value="Returned">Returned</option>
          </select>
        </div>
        <div class="form-group"><label>Amount</label><input type="number" name="amount" id="edit_amount"></div>
        <div class="form-group"><label>Delivery Date</label><input type="date" name="delivery_date" id="edit_delivery_date"></div>
        <div class="form-group"><label>Payment Status</label>
          <select name="payment_status" id="edit_payment_status">
            <option value="Unpaid">Unpaid</option>
            <option value="Paid">Paid</option>
          </select>
        </div>
        <div class="form-group form-full"><label>Notes</label><textarea name="notes" id="edit_notes" rows="2"></textarea></div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('editShipModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Update Shipment</button>
      </div>
    </form>
  </div>
</div>

<script>
// Modal functions
function openModal(modalId) {
    document.getElementById(modalId).style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
    document.body.style.overflow = '';
}

function openEditModal(shipment) {
    document.getElementById('edit_id').value = shipment.id;
    document.getElementById('edit_status').value = shipment.status;
    document.getElementById('edit_amount').value = shipment.amount;
    document.getElementById('edit_delivery_date').value = shipment.delivery_date || '';
    document.getElementById('edit_payment_status').value = shipment.payment_status || 'Unpaid';
    document.getElementById('edit_notes').value = shipment.notes || '';
    openModal('editShipModal');
}

function deleteShipment(id) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = '<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="' + id + '">';
    document.body.appendChild(form);
    form.submit();
}

// Close modals on overlay click
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) {
            overlay.style.display = 'none';
            document.body.style.overflow = '';
        }
    });
});

// Hamburger toggle
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
