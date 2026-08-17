<?php
/**
 * BILLS MANAGEMENT
 * Calma Courier Management System
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

$connect = mysqli_connect("localhost", "root", "", "courier_management");
if (!$connect) {
    die("Connection failed: " . mysqli_connect_error());
}

$user_type = $_SESSION['user_type'];
$user_id = (int)($_SESSION['user_id'] ?? 0);
$user_city = $_SESSION['city'] ?? '';
$user_name = $_SESSION['user_name'] ?? $_SESSION['user']['name'] ?? 'User';
$username = $_SESSION['username'] ?? $_SESSION['user']['username'] ?? '';

// Generate Bill ID
function generateBillId($connect) {
    $prefix = "BIL-" . date('Y') . "-";
    $query = "SELECT id FROM bills WHERE id LIKE '$prefix%' ORDER BY id DESC LIMIT 1";
    $result = mysqli_query($connect, $query);
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $last_num = intval(substr($row['id'], -4));
        return $prefix . str_pad($last_num + 1, 4, '0', STR_PAD_LEFT);
    }
    return $prefix . "0001";
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $id = mysqli_real_escape_string($connect, generateBillId($connect));
        $customer = mysqli_real_escape_string($connect, $_POST['customer']);
        $phone = mysqli_real_escape_string($connect, $_POST['phone']);
        $city = mysqli_real_escape_string($connect, $_POST['city']);
        $type = mysqli_real_escape_string($connect, $_POST['type']);
        $amount = floatval($_POST['amount']);
        $paid = floatval($_POST['paid']);
        $issue_date = mysqli_real_escape_string($connect, $_POST['issue_date']);
        $due_date = mysqli_real_escape_string($connect, $_POST['due_date']);
        $description = mysqli_real_escape_string($connect, $_POST['description']);
        $notes = mysqli_real_escape_string($connect, $_POST['notes']);
        
        // Determine status
        $balance = $amount - $paid;
        if ($paid >= $amount) {
            $status = 'Paid';
        } elseif ($paid > 0) {
            $status = 'Partial';
        } else {
            $status = 'Unpaid';
        }
        
        // Check if overdue
        if ($status !== 'Paid' && $due_date < date('Y-m-d')) {
            $status = 'Overdue';
        }
        
        $query = "INSERT INTO bills (id, customer, phone, city, type, amount, paid, status, issue_date, due_date, description, notes)
                  VALUES ('$id', '$customer', '$phone', '$city', '$type', '$amount', '$paid', '$status', '$issue_date', '$due_date', '$description', '$notes')";
        
        if (mysqli_query($connect, $query)) {
            header("Location: bills.php?success=created");
            exit;
        } else {
            $error = "Error creating bill: " . mysqli_error($connect);
        }
    }
    
    if ($action === 'edit') {
        $bill_id = mysqli_real_escape_string($connect, $_POST['bill_id']);
        $customer = mysqli_real_escape_string($connect, $_POST['customer']);
        $phone = mysqli_real_escape_string($connect, $_POST['phone']);
        $city = mysqli_real_escape_string($connect, $_POST['city']);
        $type = mysqli_real_escape_string($connect, $_POST['type']);
        $amount = floatval($_POST['amount']);
        $paid = floatval($_POST['paid']);
        $status = mysqli_real_escape_string($connect, $_POST['status']);
        $due_date = mysqli_real_escape_string($connect, $_POST['due_date']);
        $description = mysqli_real_escape_string($connect, $_POST['description']);
        $notes = mysqli_real_escape_string($connect, $_POST['notes']);
        
        // Recalculate status based on payment
        $balance = $amount - $paid;
        if ($status !== 'Overdue') {
            if ($paid >= $amount) {
                $status = 'Paid';
            } elseif ($paid > 0) {
                $status = 'Partial';
            } else {
                $status = 'Unpaid';
            }
        }
        
        $query = "UPDATE bills SET customer='$customer', phone='$phone', city='$city', type='$type', 
                  amount='$amount', paid='$paid', status='$status', due_date='$due_date', 
                  description='$description', notes='$notes' WHERE id='$bill_id'";
        
        if (mysqli_query($connect, $query)) {
            header("Location: bills.php?success=updated");
            exit;
        } else {
            $error = "Error updating bill: " . mysqli_error($connect);
        }
    }
    
    if ($action === 'delete') {
        $bill_id = mysqli_real_escape_string($connect, $_POST['bill_id']);
        if (mysqli_query($connect, "DELETE FROM bills WHERE id='$bill_id'")) {
            header("Location: bills.php?success=deleted");
            exit;
        } else {
            $error = "Error deleting bill: " . mysqli_error($connect);
        }
    }
}

// Handle GET delete
if (isset($_GET['delete'])) {
    $bill_id = mysqli_real_escape_string($connect, $_GET['delete']);
    mysqli_query($connect, "DELETE FROM bills WHERE id='$bill_id'");
    header("Location: bills.php?success=deleted");
    exit;
}

// Build query based on user type
$where_clause = "";
if ($user_type === 'agent') {
    $where_clause = "WHERE city='$user_city'";
}

// Search and filter
$search = isset($_GET['search']) ? mysqli_real_escape_string($connect, $_GET['search']) : '';
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($connect, $_GET['status']) : '';
$type_filter = isset($_GET['type']) ? mysqli_real_escape_string($connect, $_GET['type']) : '';

if ($search) {
    $where_clause .= ($where_clause ? " AND " : "WHERE ");
    $where_clause .= "(customer LIKE '%$search%' OR city LIKE '%$search%' OR id LIKE '%$search%')";
}
if ($status_filter) {
    $where_clause .= ($where_clause ? " AND " : "WHERE ");
    $where_clause .= "status='$status_filter'";
}
if ($type_filter) {
    $where_clause .= ($where_clause ? " AND " : "WHERE ");
    $where_clause .= "type='$type_filter'";
}

// Fetch bills
$query = "SELECT * FROM bills $where_clause ORDER BY issue_date DESC";
$bills_result = mysqli_query($connect, $query);
$bills = [];
while ($row = mysqli_fetch_assoc($bills_result)) {
    $bills[] = $row;
}

// Stats
$stats_query = "SELECT 
    COUNT(*) as total,
    SUM(status='Paid') as paid_count,
    SUM(status='Unpaid') as unpaid_count,
    SUM(status='Overdue') as overdue_count,
    SUM(status='Partial') as partial_count,
    SUM(CASE WHEN status='Paid' THEN paid ELSE 0 END) as revenue,
    SUM(CASE WHEN status!='Paid' THEN amount-paid ELSE 0 END) as pending_amt
    FROM bills $where_clause";
$stats_result = mysqli_query($connect, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bills – Calma CMS</title>
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
.bill-id{font-weight:600;color:#c8553d;}
.badge{padding:4px 10px;border-radius:4px;font-size:11px;color:white;font-weight:600;}
.b-paid{background:#16a34a;}
.b-unpaid{background:#f59e0b;}
.b-overdue{background:#ef4444;}
.b-partial{background:#3b82f6;}
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
.modal-footer{margin-top:20px;padding-top:15px;border-top:1px solid #e2e8f0;display:flex;justify-content:flex-end;gap:10px;}
.success-msg{background:#86efac;color:#166534;padding:12px;border-radius:8px;margin-bottom:15px;border:1px solid #16a34a;}
.person-info{display:flex;flex-direction:column;}
.pname{font-weight:600;}
.pcity{font-size:11px;color:#64748b;}
.type-tag{padding:3px 8px;border-radius:4px;font-size:11px;background:#e0e7ff;color:#4338ca;}

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
        <span class="page-title">📄 Bills</span>
      </div>
      <button class="header-btn" onclick="openModal('addBillModal')">+ Add Bill</button>
    </header>

    <?php if(isset($_GET['success'])): 
      $msg = $_GET['success'] === 'created' ? 'Bill created successfully!' : 
             ($_GET['success'] === 'updated' ? 'Bill updated successfully!' : 'Bill deleted successfully!');
    ?>
      <div class="success-msg">✓ <?php echo $msg; ?></div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-grid">
      <div class="stat-card"><div class="stat-val"><?php echo $stats['total'] ?? 0; ?></div><div class="stat-lbl">Total Bills</div></div>
      <div class="stat-card"><div class="stat-val"><?php echo $stats['paid_count'] ?? 0; ?></div><div class="stat-lbl">Paid</div></div>
      <div class="stat-card"><div class="stat-val"><?php echo $stats['unpaid_count'] ?? 0; ?></div><div class="stat-lbl">Unpaid</div></div>
      <div class="stat-card"><div class="stat-val"><?php echo $stats['overdue_count'] ?? 0; ?></div><div class="stat-lbl">Overdue</div></div>
      <div class="stat-card"><div class="stat-val">Rs. <?php echo number_format($stats['revenue'] ?? 0); ?></div><div class="stat-lbl">Revenue</div></div>
      <div class="stat-card"><div class="stat-val">Rs. <?php echo number_format($stats['pending_amt'] ?? 0); ?></div><div class="stat-lbl">Pending</div></div>
    </div>

    <!-- Bills Table -->
    <div class="card">
      <div class="card-header">
        <span class="card-title">All Bills</span>
      </div>
      
      <!-- Filter Bar -->
      <form method="GET" class="filter-bar">
        <input type="text" name="search" placeholder="Search bill ID, customer, city..." value="<?php echo htmlspecialchars($search); ?>">
        <select name="status">
          <option value="">All Status</option>
          <option value="Paid" <?php echo $status_filter === 'Paid' ? 'selected' : ''; ?>>Paid</option>
          <option value="Unpaid" <?php echo $status_filter === 'Unpaid' ? 'selected' : ''; ?>>Unpaid</option>
          <option value="Overdue" <?php echo $status_filter === 'Overdue' ? 'selected' : ''; ?>>Overdue</option>
          <option value="Partial" <?php echo $status_filter === 'Partial' ? 'selected' : ''; ?>>Partial</option>
        </select>
        <select name="type">
          <option value="">All Types</option>
          <option value="Shipping" <?php echo $type_filter === 'Shipping' ? 'selected' : ''; ?>>Shipping</option>
          <option value="Service" <?php echo $type_filter === 'Service' ? 'selected' : ''; ?>>Service</option>
          <option value="Storage" <?php echo $type_filter === 'Storage' ? 'selected' : ''; ?>>Storage</option>
          <option value="Consultation" <?php echo $type_filter === 'Consultation' ? 'selected' : ''; ?>>Consultation</option>
        </select>
        <button type="submit" class="btn btn-primary btn-sm">Search</button>
        <a href="bills.php" class="btn btn-outline btn-sm">Reset</a>
      </form>

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
              <th>Issue Date</th>
              <th>Due Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($bills)): ?>
            <tr><td colspan="10" style="text-align:center;padding:40px;color:#64748b;">No bills found</td></tr>
            <?php else: ?>
              <?php foreach ($bills as $b): 
                $badge_class = 'b-unpaid';
                if ($b['status'] === 'Paid') $badge_class = 'b-paid';
                elseif ($b['status'] === 'Overdue') $badge_class = 'b-overdue';
                elseif ($b['status'] === 'Partial') $badge_class = 'b-partial';
                $balance = $b['amount'] - $b['paid'];
              ?>
              <tr>
                <td><span class="bill-id"><?php echo htmlspecialchars($b['id']); ?></span></td>
                <td>
                  <div class="person-info">
                    <span class="pname"><?php echo htmlspecialchars($b['customer']); ?></span>
                    <span class="pcity"><?php echo htmlspecialchars($b['city']); ?></span>
                  </div>
                </td>
                <td><span class="type-tag"><?php echo htmlspecialchars($b['type']); ?></span></td>
                <td style="font-weight:600;">Rs. <?php echo number_format($b['amount']); ?></td>
                <td style="color:#16a34a;font-weight:600;">Rs. <?php echo number_format($b['paid']); ?></td>
                <td style="color:#ef4444;font-weight:600;">Rs. <?php echo number_format($balance); ?></td>
                <td><span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($b['status']); ?></span></td>
                <td style="color:#64748b;font-size:12px;"><?php echo $b['issue_date']; ?></td>
                <td style="color:#64748b;font-size:12px;"><?php echo $b['due_date']; ?></td>
                <td>
                  <button class="btn btn-outline btn-sm" onclick='openEditModal(<?php echo json_encode($b); ?>)'>Edit</button>
                  <button class="btn btn-danger btn-sm" onclick="if(confirm('Delete this bill?')) deleteBill('<?php echo $b['id']; ?>')">Delete</button>
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

<!-- ADD BILL MODAL -->
<div class="modal-overlay" id="addBillModal">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Add New Bill</span>
      <button class="modal-close" onclick="closeModal('addBillModal')">×</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add">
      
      <div class="form-grid">
        <div class="form-group"><label>Customer Name *</label><input type="text" name="customer" placeholder="Full name" required></div>
        <div class="form-group"><label>Phone</label><input type="text" name="phone" placeholder="0300-0000000"></div>
        <div class="form-group"><label>City</label><input type="text" name="city" placeholder="Karachi"></div>
        <div class="form-group"><label>Bill Type</label>
          <select name="type">
            <option value="Shipping">Shipping</option>
            <option value="Service">Service</option>
            <option value="Storage">Storage</option>
            <option value="Consultation">Consultation</option>
          </select>
        </div>
        <div class="form-group"><label>Total Amount (Rs.) *</label><input type="number" name="amount" placeholder="0" required step="0.01"></div>
        <div class="form-group"><label>Amount Paid (Rs.)</label><input type="number" name="paid" placeholder="0" value="0" step="0.01"></div>
        <div class="form-group"><label>Issue Date</label><input type="date" name="issue_date" value="<?php echo date('Y-m-d'); ?>"></div>
        <div class="form-group"><label>Due Date</label><input type="date" name="due_date"></div>
        <div class="form-group form-full"><label>Description</label><input type="text" name="description" placeholder="Bill description..."></div>
        <div class="form-group form-full"><label>Notes</label><textarea name="notes" placeholder="Additional notes..." rows="2"></textarea></div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('addBillModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Bill</button>
      </div>
    </form>
  </div>
</div>

<!-- EDIT BILL MODAL -->
<div class="modal-overlay" id="editBillModal">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Edit Bill</span>
      <button class="modal-close" onclick="closeModal('editBillModal')">×</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="bill_id" id="edit_bill_id">
      
      <div class="form-grid">
        <div class="form-group"><label>Customer Name *</label><input type="text" name="customer" id="edit_customer" required></div>
        <div class="form-group"><label>Phone</label><input type="text" name="phone" id="edit_phone"></div>
        <div class="form-group"><label>City</label><input type="text" name="city" id="edit_city"></div>
        <div class="form-group"><label>Bill Type</label>
          <select name="type" id="edit_type">
            <option value="Shipping">Shipping</option>
            <option value="Service">Service</option>
            <option value="Storage">Storage</option>
            <option value="Consultation">Consultation</option>
          </select>
        </div>
        <div class="form-group"><label>Total Amount (Rs.) *</label><input type="number" name="amount" id="edit_amount" required step="0.01"></div>
        <div class="form-group"><label>Amount Paid (Rs.)</label><input type="number" name="paid" id="edit_paid" step="0.01"></div>
        <div class="form-group"><label>Status</label>
          <select name="status" id="edit_status">
            <option value="Unpaid">Unpaid</option>
            <option value="Partial">Partial</option>
            <option value="Paid">Paid</option>
            <option value="Overdue">Overdue</option>
          </select>
        </div>
        <div class="form-group"><label>Due Date</label><input type="date" name="due_date" id="edit_due_date"></div>
        <div class="form-group form-full"><label>Description</label><input type="text" name="description" id="edit_description"></div>
        <div class="form-group form-full"><label>Notes</label><textarea name="notes" id="edit_notes" rows="2"></textarea></div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('editBillModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Update Bill</button>
      </div>
    </form>
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

// Modal functions
function openModal(id) { document.getElementById(id).style.display = 'flex'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }

function openEditModal(bill) {
    document.getElementById('edit_bill_id').value = bill.id;
    document.getElementById('edit_customer').value = bill.customer;
    document.getElementById('edit_phone').value = bill.phone;
    document.getElementById('edit_city').value = bill.city;
    document.getElementById('edit_type').value = bill.type;
    document.getElementById('edit_amount').value = bill.amount;
    document.getElementById('edit_paid').value = bill.paid;
    document.getElementById('edit_status').value = bill.status;
    document.getElementById('edit_due_date').value = bill.due_date;
    document.getElementById('edit_description').value = bill.description;
    document.getElementById('edit_notes').value = bill.notes;
    openModal('editBillModal');
}

function deleteBill(billId) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = '<input type="hidden" name="action" value="delete"><input type="hidden" name="bill_id" value="' + billId + '">';
    document.body.appendChild(form);
    form.submit();
}

// Close modals on outside click
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) this.style.display = 'none';
    });
});
</script>
</body>
</html>
