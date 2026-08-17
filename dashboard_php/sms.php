<?php
/**
 * SMS NOTIFICATIONS
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

$success = '';
$error = '';

// Handle SMS sending
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'send_booking_sms') {
        $tracking_number = mysqli_real_escape_string($connect, $_POST['tracking_number']);
        $recipient_phone = mysqli_real_escape_string($connect, $_POST['recipient_phone']);
        $sender_name = mysqli_real_escape_string($connect, $_POST['sender_name']);
        $receiver_name = mysqli_real_escape_string($connect, $_POST['receiver_name']);
        $sender_city = mysqli_real_escape_string($connect, $_POST['sender_city']);
        $receiver_city = mysqli_real_escape_string($connect, $_POST['receiver_city']);
        
        $message = "Calma Courier: New shipment booked. Tracking: $tracking_number. From: $sender_name ($sender_city) To: $receiver_name ($receiver_city). Thank you!";
        
        // In real implementation, integrate with SMS API here
        // For demo, we'll just log it
        $query = "INSERT INTO sms_logs (tracking_number, recipient_phone, message, sms_type, status)
                  VALUES ('$tracking_number', '$recipient_phone', '$message', 'Booking', 'Sent')";
        
        if (mysqli_query($connect, $query)) {
            $success = "Booking SMS sent successfully to $recipient_phone";
        } else {
            $error = "Error sending SMS: " . mysqli_error($connect);
        }
    }
    
    if ($action === 'send_delivery_sms') {
        $tracking_number = mysqli_real_escape_string($connect, $_POST['tracking_number']);
        $recipient_phone = mysqli_real_escape_string($connect, $_POST['recipient_phone']);
        $status = mysqli_real_escape_string($connect, $_POST['status'] ?? 'In Transit');

        $message = "Calma Courier: Your shipment $tracking_number status is now: $status. For details visit our website or call 0300-0000000.";

        $query = "INSERT INTO sms_logs (tracking_number, recipient_phone, message, sms_type, status)
                  VALUES ('$tracking_number', '$recipient_phone', '$message', 'Delivery', 'Sent')";

        if (mysqli_query($connect, $query)) {
            $success = "Delivery SMS sent successfully to $recipient_phone";
        } else {
            $error = "Error sending SMS: " . mysqli_error($connect);
        }
    }
    
    if ($action === 'send_custom_sms') {
        $tracking_number = mysqli_real_escape_string($connect, $_POST['tracking_number']);
        $recipient_phone = mysqli_real_escape_string($connect, $_POST['recipient_phone']);
        $message = mysqli_real_escape_string($connect, $_POST['message']);
        
        $query = "INSERT INTO sms_logs (tracking_number, recipient_phone, message, sms_type, status)
                  VALUES ('$tracking_number', '$recipient_phone', '$message', 'Status Update', 'Sent')";
        
        if (mysqli_query($connect, $query)) {
            $success = "SMS sent successfully to $recipient_phone";
        } else {
            $error = "Error sending SMS: " . mysqli_error($connect);
        }
    }
}

// Get shipment details if tracking number provided
$shipment = null;
if (isset($_GET['tracking'])) {
    $tracking_number = mysqli_real_escape_string($connect, $_GET['tracking']);
    $query = "SELECT * FROM shipments WHERE tracking_number='$tracking_number'";
    $result = mysqli_query($connect, $query);
    if (mysqli_num_rows($result) > 0) {
        $shipment = mysqli_fetch_assoc($result);
    }
}

// Fetch SMS logs
$logs_query = "SELECT * FROM sms_logs ORDER BY sent_at DESC LIMIT 50";
$logs_result = mysqli_query($connect, $logs_query);
$sms_logs = [];
while ($row = mysqli_fetch_assoc($logs_result)) {
    $sms_logs[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SMS Notifications – Calma CMS</title>
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
.card{background:white;border-radius:10px;box-shadow:0 4px 8px rgba(0,0,0,0.05);margin-bottom:20px;overflow:hidden;}
.card-header{background:#f8fafc;padding:15px 20px;border-bottom:1px solid #e2e8f0;font-weight:600;}
.card-body{padding:20px;}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:15px;}
.form-full{grid-column:1/-1;}
.form-group{margin-bottom:12px;}
.form-group label{display:block;font-size:11px;font-weight:600;letter-spacing:1px;text-transform:uppercase;color:#64748b;margin-bottom:5px;}
.form-group input,.form-group select,.form-group textarea{width:100%;padding:10px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:14px;}
.form-group input:focus,.form-group select:focus,.form-group textarea:focus{outline:none;border-color:#c8553d;}
.btn{padding:10px 20px;border:none;border-radius:6px;cursor:pointer;font-size:13px;font-weight:600;}
.btn-primary{background:#c8553d;color:white;}
.btn-outline{background:white;border:1px solid #c8553d;color:#c8553d;}
.btn-success{background:#16a34a;color:white;}
.success-msg{background:#86efac;color:#166534;padding:12px;border-radius:8px;margin-bottom:15px;border:1px solid #16a34a;}
.error-msg{background:#fca5a5;color:#b91c1c;padding:12px;border-radius:8px;margin-bottom:15px;border:1px solid #fca5a5;}
.table-wrap{overflow-x:auto;}
.data-table{width:100%;border-collapse:collapse;}
.data-table th,.data-table td{padding:12px;text-align:left;border-bottom:1px solid #e2e8f0;font-size:13px;}
.data-table th{background:#f8fafc;font-weight:600;}
.badge{padding:4px 10px;border-radius:4px;font-size:11px;color:white;font-weight:600;}
.b-sent{background:#16a34a;}
.b-failed{background:#ef4444;}
.b-pending{background:#f59e0b;}
.sms-preview{background:#fef3c7;border:1px solid #f59e0b;padding:15px;border-radius:8px;margin-top:15px;}
.sms-preview-label{font-size:11px;color:#92400e;text-transform:uppercase;font-weight:600;margin-bottom:8px;}
.sms-preview-text{font-size:14px;color:#78350f;line-height:1.6;}
.shipment-info{background:#f0f9ff;border:1px solid #0ea5e9;padding:15px;border-radius:8px;margin-bottom:20px;}
.shipment-info h4{color:#0369a1;margin-bottom:10px;}
.info-row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #e0f2fe;}
.info-row:last-child{border-bottom:none;}
.info-label{color:#64748b;font-size:13px;}
.info-value{color:#1e293b;font-weight:600;font-size:13px;}
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
        <span class="page-title">📱 SMS Notifications</span>
      </div>
    </header>

    <?php if ($success): ?>
      <div class="success-msg">✓ <?php echo $success; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="error-msg">✗ <?php echo $error; ?></div>
    <?php endif; ?>

    <div class="card">
      <div class="card-header">Send SMS Notification</div>
      <div class="card-body">
        
        <?php if ($shipment): ?>
          <div class="shipment-info">
            <h4>📦 Shipment: <?php echo htmlspecialchars($shipment['tracking_number']); ?></h4>
            <div class="info-row">
              <span class="info-label">Sender:</span>
              <span class="info-value"><?php echo htmlspecialchars($shipment['sender_name']); ?> (<?php echo htmlspecialchars($shipment['sender_city']); ?>)</span>
            </div>
            <div class="info-row">
              <span class="info-label">Receiver:</span>
              <span class="info-value"><?php echo htmlspecialchars($shipment['receiver_name']); ?> (<?php echo htmlspecialchars($shipment['receiver_city']); ?>)</span>
            </div>
            <div class="info-row">
              <span class="info-label">Status:</span>
              <span class="info-value"><?php echo htmlspecialchars($shipment['status']); ?></span>
            </div>
          </div>
        <?php endif; ?>

        <form method="POST" id="smsForm">
          <input type="hidden" name="action" id="sms_action" value="send_custom_sms">
          <input type="hidden" name="status" id="sms_status" value="">

          <div class="form-grid">
            <div class="form-group">
              <label>Tracking Number</label>
              <input type="text" name="tracking_number" id="tracking_number" 
                     value="<?php echo $shipment ? htmlspecialchars($shipment['tracking_number']) : ''; ?>" 
                     placeholder="Enter tracking number or search" required onchange="loadShipment()">
            </div>
            <div class="form-group">
              <label>Recipient Phone</label>
              <input type="text" name="recipient_phone" id="recipient_phone" 
                     value="<?php echo $shipment ? htmlspecialchars($shipment['receiver_phone']) : ''; ?>" 
                     placeholder="0300-0000000" required>
            </div>
          </div>

          <div class="form-group">
            <label>Message</label>
            <textarea name="message" id="message" rows="4" placeholder="Enter your message..." required></textarea>
          </div>

          <div class="sms-preview">
            <div class="sms-preview-label">📱 SMS Preview</div>
            <div class="sms-preview-text" id="smsPreview">Your message will appear here...</div>
          </div>

          <div style="margin-top:20px;display:flex;gap:10px;flex-wrap:wrap;">
            <button type="button" class="btn btn-primary" onclick="setSmsType('booking')">Booking SMS Template</button>
            <button type="button" class="btn btn-success" onclick="setSmsType('delivery')">Delivery SMS Template</button>
            <button type="submit" class="btn btn-primary">Send SMS</button>
          </div>
        </form>
      </div>
    </div>

    <!-- SMS History -->
    <div class="card">
      <div class="card-header">SMS History (Last 50)</div>
      <div class="table-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th>Date/Time</th>
              <th>Tracking #</th>
              <th>Recipient</th>
              <th>Type</th>
              <th>Message</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($sms_logs)): ?>
            <tr><td colspan="6" style="text-align:center;padding:40px;color:#64748b;">No SMS logs found</td></tr>
            <?php else: ?>
              <?php foreach ($sms_logs as $log): 
                $badge_class = 'b-pending';
                if ($log['status'] === 'Sent') $badge_class = 'b-sent';
                elseif ($log['status'] === 'Failed') $badge_class = 'b-failed';
              ?>
              <tr>
                <td style="color:#64748b;font-size:12px;"><?php echo $log['sent_at']; ?></td>
                <td style="font-weight:600;"><?php echo htmlspecialchars($log['tracking_number']); ?></td>
                <td><?php echo htmlspecialchars($log['recipient_phone']); ?></td>
                <td><?php echo htmlspecialchars($log['sms_type']); ?></td>
                <td style="max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#64748b;">
                  <?php echo htmlspecialchars(substr($log['message'], 0, 80)); ?>...
                </td>
                <td><span class="badge <?php echo $badge_class; ?>"><?php echo $log['status']; ?></span></td>
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

// Load shipment details
function loadShipment() {
    const trackingNumber = document.getElementById('tracking_number').value;
    if (trackingNumber) {
        window.location.href = 'sms.php?tracking=' + encodeURIComponent(trackingNumber);
    }
}

// Set SMS type template
function setSmsType(type) {
    const trackingNumber = document.getElementById('tracking_number').value;
    const recipientPhone = document.getElementById('recipient_phone').value;

    if (!trackingNumber) {
        alert('Please enter a tracking number first');
        return;
    }

    let message = '';
    let action = '';
    let status = '';

    if (type === 'booking') {
        action = 'send_booking_sms';
        // Message will be generated on server side
    } else if (type === 'delivery') {
        action = 'send_delivery_sms';
        status = prompt('Enter current status:', 'In Transit');
        if (!status) return;
        message = 'Calma Courier: Your shipment ' + trackingNumber + ' status is now: ' + status + '. For details visit our website or call 0300-0000000.';
    }

    document.getElementById('sms_action').value = action;
    document.getElementById('sms_status').value = status;
    if (message) {
        document.getElementById('message').value = message;
        document.getElementById('smsPreview').textContent = message;
    } else {
        // Submit form for template generation
        document.getElementById('smsForm').submit();
    }
}

// Update preview on message change
document.getElementById('message').addEventListener('input', function() {
    document.getElementById('smsPreview').textContent = this.value;
});
</script>
</body>
</html>
