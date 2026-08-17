<?php
/**
 * CUSTOMERS MANAGEMENT
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
    die("DB Connection Failed: ".mysqli_connect_error());
}

$user_type = $_SESSION['user_type'];
$user_id = (int)($_SESSION['user_id'] ?? 0);
$user_city = $_SESSION['city'] ?? '';
$user_name = $_SESSION['user_name'] ?? $_SESSION['user']['name'] ?? 'User';
$username = $_SESSION['username'] ?? $_SESSION['user']['username'] ?? '';

// Handle POST Requests
if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = mysqli_real_escape_string($connect, $_POST['name']);
        $email = mysqli_real_escape_string($connect, $_POST['email']);
        $phone = mysqli_real_escape_string($connect, $_POST['phone']);
        $city = mysqli_real_escape_string($connect, $_POST['city']);
        $address = mysqli_real_escape_string($connect, $_POST['address']);
        $status = $_POST['status'] ?? 'Active';

        mysqli_query($connect, "INSERT INTO customers (name, email, phone, city, address, status)
                               VALUES ('$name', '$email', '$phone', '$city', '$address', '$status')");
        header("Location: customers.php?success=created");
        exit;
    }

    if ($action === 'edit') {
        $id = intval($_POST['id']);
        $name = mysqli_real_escape_string($connect, $_POST['name']);
        $email = mysqli_real_escape_string($connect, $_POST['email']);
        $phone = mysqli_real_escape_string($connect, $_POST['phone']);
        $city = mysqli_real_escape_string($connect, $_POST['city']);
        $address = mysqli_real_escape_string($connect, $_POST['address']);
        $status = $_POST['status'] ?? 'Active';

        mysqli_query($connect, "UPDATE customers SET name='$name', email='$email', phone='$phone', city='$city', address='$address', status='$status' WHERE id=$id");
        header("Location: customers.php?success=updated");
        exit;
    }
    
    if ($action === 'delete') {
        $id = intval($_POST['id']);
        mysqli_query($connect, "DELETE FROM customers WHERE id=$id");
        header("Location: customers.php?success=deleted");
        exit;
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    mysqli_query($connect, "DELETE FROM customers WHERE id=$del_id");
    header("Location: customers.php?success=deleted");
    exit;
}

// Search
$search = isset($_GET['search']) ? mysqli_real_escape_string($connect, $_GET['search']) : '';
$city_filter = isset($_GET['city']) ? mysqli_real_escape_string($connect, $_GET['city']) : '';

$where_clause = "";
if ($search) {
    $where_clause .= "WHERE name LIKE '%$search%' OR email LIKE '%$search%' OR phone LIKE '%$search%'";
}
if ($city_filter) {
    $where_clause .= ($where_clause ? " AND " : "WHERE ");
    $where_clause .= "city='$city_filter'";
}

// Fetch Customers
$customers_result = mysqli_query($connect, "SELECT * FROM customers $where_clause ORDER BY name ASC");
$customers = [];
while ($row = mysqli_fetch_assoc($customers_result)) {
    $customers[] = $row;
}

// Stats
$total = count($customers);
$active = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) AS cnt FROM customers WHERE status='Active'"))['cnt'];
$inactive = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) AS cnt FROM customers WHERE status='Inactive'"))['cnt'];

// Get cities for filter
$cities_result = mysqli_query($connect, "SELECT DISTINCT city FROM customers WHERE city IS NOT NULL AND city != '' ORDER BY city");
$cities = [];
while ($row = mysqli_fetch_assoc($cities_result)) {
    $cities[] = $row['city'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Customers – Calma CMS</title>
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
.badge{padding:4px 10px;border-radius:4px;font-size:11px;color:white;font-weight:600;}
.b-active{background:#16a34a;}
.b-inactive{background:#64748b;}
.btn{padding:6px 12px;border:none;border-radius:4px;cursor:pointer;font-size:12px;text-decoration:none;display:inline-block;line-height:1.4;}
.btn-primary{background:#c8553d;color:white;}
.btn-outline{background:white;border:1px solid #c8553d;color:#c8553d;}
.btn-danger{background:#ef4444;color:white;}
.btn-sm{padding:4px 10px;font-size:11px;white-space:nowrap;}
.filter-bar{display:flex;gap:10px;padding:15px 20px;background:#f8fafc;border-bottom:1px solid #e2e8f0;flex-wrap:wrap;align-items:center;}
.filter-bar input,.filter-bar select{padding:8px 12px;border:1px solid #e2e8f0;border-radius:6px;font-size:13px;}
.filter-bar input{flex:1;min-width:200px;}
.modal-overlay{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);display:none;justify-content:center;align-items:center;z-index:1000;}
.modal{background:white;padding:25px;border-radius:12px;width:90%;max-width:500px;max-height:90vh;overflow-y:auto;}
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
        <span class="page-title">👤 Customers</span>
      </div>
      <button class="header-btn" onclick="openModal('custModal')">+ Add Customer</button>
    </header>

    <?php if(isset($_GET['success'])): 
      $msg = $_GET['success'] === 'created' ? 'Customer created successfully!' : 
             ($_GET['success'] === 'updated' ? 'Customer updated successfully!' : 'Customer deleted successfully!');
    ?>
      <div class="success-msg">✓ <?php echo $msg; ?></div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-grid">
      <div class="stat-card"><div class="stat-val"><?php echo $total; ?></div><div class="stat-lbl">Total</div></div>
      <div class="stat-card"><div class="stat-val"><?php echo $active; ?></div><div class="stat-lbl">Active</div></div>
      <div class="stat-card"><div class="stat-val"><?php echo $inactive; ?></div><div class="stat-lbl">Inactive</div></div>
    </div>

    <!-- Customers Table -->
    <div class="card">
      <div class="card-header">
        <span class="card-title">All Customers</span>
      </div>
      
      <!-- Filter Bar -->
      <form method="GET" class="filter-bar">
        <input type="text" name="search" placeholder="Search name, email, phone..." value="<?php echo htmlspecialchars($search); ?>">
        <select name="city">
          <option value="">All Cities</option>
          <?php foreach ($cities as $c): ?>
          <option value="<?php echo htmlspecialchars($c); ?>" <?php echo $city_filter === $c ? 'selected' : ''; ?>><?php echo htmlspecialchars($c); ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary btn-sm">Search</button>
        <a href="customers.php" class="btn btn-outline btn-sm">Reset</a>
      </form>

      <div class="table-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Email</th>
              <th>Phone</th>
              <th>City</th>
              <th>Address</th>
              <th>Status</th>
              <th>Joined</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($customers)): ?>
            <tr><td colspan="9" style="text-align:center;padding:40px;color:#64748b;">No customers found</td></tr>
            <?php else: ?>
              <?php foreach ($customers as $c): ?>
              <tr>
                <td style="font-weight:600;color:#c8553d;"><?php echo $c['id']; ?></td>
                <td>
                  <div class="person-info">
                    <span class="pname"><?php echo htmlspecialchars($c['name']); ?></span>
                  </div>
                </td>
                <td><?php echo htmlspecialchars($c['email'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars($c['phone'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars($c['city'] ?? '-'); ?></td>
                <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#64748b;">
                  <?php echo htmlspecialchars($c['address'] ?? '-'); ?>
                </td>
                <td><span class="badge <?php echo $c['status']=='Active'?'b-active':'b-inactive'; ?>"><?php echo $c['status']; ?></span></td>
                <td style="color:#64748b;font-size:12px;"><?php echo $c['joined']; ?></td>
                <td>
                  <button class="btn btn-outline btn-sm" onclick='openEditModal(<?php echo json_encode($c); ?>)'>Edit</button>
                  <button class="btn btn-danger btn-sm" onclick="if(confirm('Delete this customer?')) deleteCustomer(<?php echo $c['id']; ?>)">Delete</button>
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

<!-- ADD CUSTOMER MODAL -->
<div class="modal-overlay" id="custModal">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Add Customer</span>
      <button class="modal-close" onclick="closeModal('custModal')">×</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add">
      <div class="form-grid">
        <div class="form-group form-full"><label>Name *</label><input type="text" name="name" required></div>
        <div class="form-group"><label>Email</label><input type="email" name="email"></div>
        <div class="form-group"><label>Phone</label><input type="text" name="phone"></div>
        <div class="form-group"><label>City</label><input type="text" name="city"></div>
        <div class="form-group form-full"><label>Address</label><textarea name="address" rows="2"></textarea></div>
        <div class="form-group"><label>Status</label>
          <select name="status">
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('custModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Customer</button>
      </div>
    </form>
  </div>
</div>

<!-- EDIT CUSTOMER MODAL -->
<div class="modal-overlay" id="editCustModal">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Edit Customer</span>
      <button class="modal-close" onclick="closeModal('editCustModal')">×</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" id="edit_id">
      <div class="form-grid">
        <div class="form-group form-full"><label>Name *</label><input type="text" name="name" id="edit_name" required></div>
        <div class="form-group"><label>Email</label><input type="email" name="email" id="edit_email"></div>
        <div class="form-group"><label>Phone</label><input type="text" name="phone" id="edit_phone"></div>
        <div class="form-group"><label>City</label><input type="text" name="city" id="edit_city"></div>
        <div class="form-group form-full"><label>Address</label><textarea name="address" id="edit_address" rows="2"></textarea></div>
        <div class="form-group"><label>Status</label>
          <select name="status" id="edit_status">
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('editCustModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Update Customer</button>
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

function openEditModal(customer) {
    document.getElementById('edit_id').value = customer.id;
    document.getElementById('edit_name').value = customer.name;
    document.getElementById('edit_email').value = customer.email || '';
    document.getElementById('edit_phone').value = customer.phone || '';
    document.getElementById('edit_city').value = customer.city || '';
    document.getElementById('edit_address').value = customer.address || '';
    document.getElementById('edit_status').value = customer.status;
    openModal('editCustModal');
}

function deleteCustomer(id) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = '<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="' + id + '">';
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
