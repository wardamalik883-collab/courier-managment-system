<?php
/**
 * AGENTS MANAGEMENT (Admin Only)
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

// Check if user is logged in as Admin
if (!isset($_SESSION['user']) || !isset($_SESSION['user_type']) || !isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Only admin can access this page
if ($_SESSION['user_type'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$connect = mysqli_connect("localhost", "root", "", "courier_management");
if (!$connect) {
    die("DB connection failed: ".mysqli_connect_error());
}

$user_type = $_SESSION['user_type'];
$user_id = (int)($_SESSION['user_id'] ?? 0);
$user_name = $_SESSION['user_name'] ?? $_SESSION['user']['name'] ?? 'User';
$username = $_SESSION['username'] ?? $_SESSION['user']['username'] ?? '';

// Handle POST Requests
if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $username = mysqli_real_escape_string($connect, $_POST['username']);
        $password = mysqli_real_escape_string($connect, $_POST['password']);
        $name     = mysqli_real_escape_string($connect, $_POST['name']);
        $email    = mysqli_real_escape_string($connect, $_POST['email']);
        $phone    = mysqli_real_escape_string($connect, $_POST['phone']);
        $city     = mysqli_real_escape_string($connect, $_POST['city']);
        $branch_code = mysqli_real_escape_string($connect, $_POST['branch_code']);
        $status   = $_POST['status'] ?? 'Active';

        $pass_hash = MD5($password);
        
        // Check if username exists
        $check = mysqli_query($connect, "SELECT id FROM agents WHERE username='$username'");
        if (mysqli_num_rows($check) > 0) {
            $error = "Username already exists!";
        } else {
            mysqli_query($connect, "INSERT INTO agents (username, password, name, email, phone, city, branch_code, status)
                                   VALUES ('$username', '$pass_hash', '$name', '$email', '$phone', '$city', '$branch_code', '$status')");
            header("Location: agents.php?success=created");
            exit;
        }
    }
    
    if ($action === 'edit') {
        $id       = intval($_POST['id']);
        $name     = mysqli_real_escape_string($connect, $_POST['name']);
        $email    = mysqli_real_escape_string($connect, $_POST['email']);
        $phone    = mysqli_real_escape_string($connect, $_POST['phone']);
        $city     = mysqli_real_escape_string($connect, $_POST['city']);
        $branch_code = mysqli_real_escape_string($connect, $_POST['branch_code']);
        $status   = $_POST['status'] ?? 'Active';

        mysqli_query($connect, "UPDATE agents SET name='$name', email='$email', phone='$phone', city='$city', branch_code='$branch_code', status='$status' WHERE id=$id");
        header("Location: agents.php?success=updated");
        exit;
    }
    
    if ($action === 'delete') {
        $del_id = intval($_POST['id']);
        mysqli_query($connect, "DELETE FROM agents WHERE id=$del_id");
        header("Location: agents.php?success=deleted");
        exit;
    }
}

// Handle GET DELETE
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    mysqli_query($connect, "DELETE FROM agents WHERE id=$del_id");
    header("Location: agents.php?success=deleted");
    exit;
}

// Fetch Agents
$agents_result = mysqli_query($connect, "SELECT * FROM agents ORDER BY city ASC, name ASC");
$agents = [];
while ($row = mysqli_fetch_assoc($agents_result)) {
    $agents[] = $row;
}

// Stats
$total_agents = count($agents);
$active_agents = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) AS cnt FROM agents WHERE status='Active'"))['cnt'];
$inactive_agents = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) AS cnt FROM agents WHERE status='Inactive'"))['cnt'];

// Get cities for branch codes
$cities_result = mysqli_query($connect, "SELECT DISTINCT city FROM agents ORDER BY city");
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
<title>Agents – Calma CMS</title>
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
.agents-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;}
.agent-card{background:white;padding:20px;border-radius:10px;box-shadow:0 4px 8px rgba(0,0,0,0.05);}
.agent-card-top{display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;}
.agent-av{width:45px;height:45px;background:#c8553d;color:white;border-radius:50%;display:flex;justify-content:center;align-items:center;font-weight:700;font-size:18px;}
.agent-name{font-weight:600;font-size:15px;}
.agent-city{font-size:13px;color:#64748b;margin-top:3px;}
.badge{padding:4px 10px;border-radius:4px;font-size:11px;color:white;font-weight:600;}
.b-active{background:#16a34a;}
.b-inactive{background:#64748b;}
.agent-details{margin-bottom:15px;}
.agent-details span{display:block;font-size:13px;margin-bottom:6px;color:#64748b;}
.agent-stats-row{display:flex;gap:8px;margin-top:12px;}
.a-stat{flex:1;text-align:center;padding:8px;background:#f8fafc;border-radius:6px;}
.a-stat .av{font-weight:700;font-size:16px;color:#c8553d;}
.a-stat .lbl{font-size:10px;color:#64748b;text-transform:uppercase;}
.btn{padding:6px 12px;border:none;border-radius:4px;cursor:pointer;font-size:12px;text-decoration:none;display:inline-block;}
.btn-primary{background:#c8553d;color:white;}
.btn-outline{background:white;border:1px solid #c8553d;color:#c8553d;}
.btn-danger{background:#ef4444;color:white;}
.modal-overlay{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);display:none;justify-content:center;align-items:center;z-index:1000;}
.modal{background:white;padding:25px;border-radius:12px;width:90%;max-width:500px;max-height:90vh;overflow-y:auto;}
.modal-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;padding-bottom:15px;border-bottom:1px solid #e2e8f0;}
.modal-title{font-weight:600;font-size:18px;}
.modal-close{background:none;border:none;font-size:24px;cursor:pointer;color:#64748b;}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:15px;}
.form-full{grid-column:1/-1;}
.form-group{margin-bottom:12px;}
.form-group label{display:block;font-size:11px;font-weight:600;letter-spacing:1px;text-transform:uppercase;color:#64748b;margin-bottom:5px;}
.form-group input,.form-group select{width:100%;padding:10px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:14px;}
.form-group input:focus,.form-group select:focus{outline:none;border-color:#c8553d;}
.modal-footer{margin-top:20px;padding-top:15px;border-top:1px solid #e2e8f0;display:flex;justify-content:flex-end;gap:10px;}
.success-msg{background:#86efac;color:#166534;padding:12px;border-radius:8px;margin-bottom:15px;border:1px solid #16a34a;}

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
  .agents-grid{grid-template-columns:1fr;}
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
        <span class="page-title">👥 Agents</span>
      </div>
      <button class="header-btn" onclick="openModal('agentModal')">+ Add Agent</button>
    </header>

    <?php if(isset($_GET['success'])): 
      $msg = $_GET['success'] === 'created' ? 'Agent created successfully!' : 
             ($_GET['success'] === 'updated' ? 'Agent updated successfully!' : 'Agent deleted successfully!');
    ?>
      <div class="success-msg">✓ <?php echo $msg; ?></div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-grid">
      <div class="stat-card"><div class="stat-val"><?php echo $total_agents; ?></div><div class="stat-lbl">Total Agents</div></div>
      <div class="stat-card"><div class="stat-val"><?php echo $active_agents; ?></div><div class="stat-lbl">Active</div></div>
      <div class="stat-card"><div class="stat-val"><?php echo $inactive_agents; ?></div><div class="stat-lbl">Inactive</div></div>
    </div>

    <!-- Agents Grid -->
    <div class="agents-grid">
      <?php foreach($agents as $a): ?>
      <div class="agent-card">
        <div class="agent-card-top">
          <div style="display:flex;align-items:center;gap:12px;">
            <div class="agent-av"><?php echo strtoupper(substr($a['name'],0,1)); ?></div>
            <div>
              <div class="agent-name"><?php echo htmlspecialchars($a['name']); ?></div>
              <div class="agent-city">📍 <?php echo htmlspecialchars($a['city']); ?> (<?php echo htmlspecialchars($a['branch_code']); ?>)</div>
            </div>
          </div>
          <span class="badge <?php echo $a['status']=='Active'?'b-active':'b-inactive'; ?>"><?php echo $a['status']; ?></span>
        </div>
        <div class="agent-details">
          <span>📞 <?php echo htmlspecialchars($a['phone']); ?></span>
          <span>✉️ <?php echo htmlspecialchars($a['email']); ?></span>
          <span>👤 <?php echo htmlspecialchars($a['username']); ?></span>
        </div>
        <div style="display:flex;gap:6px;margin-top:12px;">
          <button class="btn btn-outline" onclick='openEditModal(<?php echo json_encode($a); ?>)'>Edit</button>
          <button class="btn btn-danger" onclick="if(confirm('Delete this agent?')) deleteAgent(<?php echo $a['id']; ?>)">Delete</button>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- ADD AGENT MODAL -->
<div class="modal-overlay" id="agentModal">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Add Agent</span>
      <button class="modal-close" onclick="closeModal('agentModal')">×</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add">
      <div class="form-grid">
        <div class="form-group form-full"><label>Name *</label><input type="text" name="name" required></div>
        <div class="form-group"><label>Username *</label><input type="text" name="username" required></div>
        <div class="form-group"><label>Password *</label><input type="password" name="password" required></div>
        <div class="form-group"><label>Email</label><input type="email" name="email"></div>
        <div class="form-group"><label>Phone</label><input type="text" name="phone"></div>
        <div class="form-group"><label>City *</label><input type="text" name="city" required list="cityList"></div>
        <datalist id="cityList">
          <?php foreach($cities as $c): ?>
          <option value="<?php echo htmlspecialchars($c); ?>">
          <?php endforeach; ?>
        </datalist>
        <div class="form-group"><label>Branch Code</label><input type="text" name="branch_code" placeholder="e.g., KHI-001"></div>
        <div class="form-group"><label>Status</label>
          <select name="status">
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('agentModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Agent</button>
      </div>
    </form>
  </div>
</div>

<!-- EDIT AGENT MODAL -->
<div class="modal-overlay" id="editAgentModal">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Edit Agent</span>
      <button class="modal-close" onclick="closeModal('editAgentModal')">×</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" id="edit_id">
      <div class="form-grid">
        <div class="form-group form-full"><label>Name *</label><input type="text" name="name" id="edit_name" required></div>
        <div class="form-group"><label>Email</label><input type="email" name="email" id="edit_email"></div>
        <div class="form-group"><label>Phone</label><input type="text" name="phone" id="edit_phone"></div>
        <div class="form-group"><label>City</label><input type="text" name="city" id="edit_city" list="cityList2"></div>
        <datalist id="cityList2">
          <?php foreach($cities as $c): ?>
          <option value="<?php echo htmlspecialchars($c); ?>">
          <?php endforeach; ?>
        </datalist>
        <div class="form-group"><label>Branch Code</label><input type="text" name="branch_code" id="edit_branch_code"></div>
        <div class="form-group"><label>Status</label>
          <select name="status" id="edit_status">
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('editAgentModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Update Agent</button>
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

function openEditModal(agent) {
    document.getElementById('edit_id').value = agent.id;
    document.getElementById('edit_name').value = agent.name;
    document.getElementById('edit_email').value = agent.email;
    document.getElementById('edit_phone').value = agent.phone;
    document.getElementById('edit_city').value = agent.city;
    document.getElementById('edit_branch_code').value = agent.branch_code;
    document.getElementById('edit_status').value = agent.status;
    openModal('editAgentModal');
}

function deleteAgent(id) {
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
