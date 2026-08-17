<?php
/**
 * BOOK A SHIPMENT - User Page
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
$user_email = $_SESSION['user_email'] ?? '';
$user_phone = $_SESSION['user_phone'] ?? '';

// If email/phone not in session, fetch from database
if (empty($user_email) || empty($user_phone)) {
    $user_data = mysqli_fetch_assoc(mysqli_query($connect, "SELECT email, phone FROM users WHERE id=$user_id"));
    if ($user_data) {
        $user_email = $user_data['email'] ?? '';
        $user_phone = $user_data['phone'] ?? '';
    }
}

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate inputs
    $sender_name = mysqli_real_escape_string($connect, trim($_POST['sender_name']));
    $sender_phone = mysqli_real_escape_string($connect, trim($_POST['sender_phone']));
    $sender_email = mysqli_real_escape_string($connect, trim($_POST['sender_email']));
    $sender_address = mysqli_real_escape_string($connect, trim($_POST['sender_address']));
    $sender_city = mysqli_real_escape_string($connect, trim($_POST['sender_city']));
    
    $receiver_name = mysqli_real_escape_string($connect, trim($_POST['receiver_name']));
    $receiver_phone = mysqli_real_escape_string($connect, trim($_POST['receiver_phone']));
    $receiver_email = mysqli_real_escape_string($connect, trim($_POST['receiver_email']));
    $receiver_address = mysqli_real_escape_string($connect, trim($_POST['receiver_address']));
    $receiver_city = mysqli_real_escape_string($connect, trim($_POST['receiver_city']));
    
    $shipment_type = mysqli_real_escape_string($connect, $_POST['shipment_type']);
    $weight = floatval($_POST['weight']);
    $description = mysqli_real_escape_string($connect, trim($_POST['description']));
    
    // Validation
    if (empty($sender_name) || empty($sender_phone) || empty($sender_city)) {
        $error = 'Please fill in all sender details';
    } elseif (empty($receiver_name) || empty($receiver_phone) || empty($receiver_city)) {
        $error = 'Please fill in all receiver details';
    } elseif ($weight <= 0) {
        $error = 'Weight must be greater than 0';
    } else {
        // Generate tracking number
        $prefix = "CLM-" . date('Y') . "-";
        $query = "SELECT tracking_number FROM shipments WHERE tracking_number LIKE '$prefix%' ORDER BY id DESC LIMIT 1";
        $result = mysqli_query($connect, $query);
        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $last_num = intval(substr($row['tracking_number'], -4));
            $tracking_number = $prefix . str_pad($last_num + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $tracking_number = $prefix . "0001";
        }
        
        // Calculate amount based on weight and type
        $amount = 0;
        if ($shipment_type === 'Standard') {
            $amount = $weight * 150; // Rs. 150 per kg
        } elseif ($shipment_type === 'Express') {
            $amount = $weight * 250; // Rs. 250 per kg
        } elseif ($shipment_type === 'Overnight') {
            $amount = $weight * 400; // Rs. 400 per kg
        }
        
        // Insert shipment
        $insert_query = "INSERT INTO shipments (
            tracking_number, sender_name, sender_phone, sender_email, sender_address, sender_city,
            receiver_name, receiver_phone, receiver_email, receiver_address, receiver_city,
            type, weight, amount, status, booked_date
        ) VALUES (
            '$tracking_number', '$sender_name', '$sender_phone', '$sender_email', '$sender_address', '$sender_city',
            '$receiver_name', '$receiver_phone', '$receiver_email', '$receiver_address', '$receiver_city',
            '$shipment_type', '$weight', '$amount', 'Pending', NOW()
        )";
        
        if (mysqli_query($connect, $insert_query)) {
            $shipment_id = mysqli_insert_id($connect);
            
            $success = "✅ Shipment booked successfully!<br>
                       <strong>Tracking Number:</strong> $tracking_number<br><br>
                       📍 Sent to: <strong>$sender_city</strong> city agent<br>
                       📋 Admin notified<br>
                       📦 Track in 'My Shipments'";
        } else {
            $error = 'Failed to book shipment. Please try again. Error: ' . mysqli_error($connect);
        }
    }
}

// Fetch cities for dropdown
$cities_result = mysqli_query($connect, "SELECT DISTINCT city FROM agents WHERE status='Active' ORDER BY city");
$cities = [];
while ($row = mysqli_fetch_assoc($cities_result)) {
    $cities[] = $row['city'];
}

// Also add cities from customers if not already present
$all_cities_result = mysqli_query($connect, "SELECT DISTINCT city FROM customers WHERE status='Active'");
while ($row = mysqli_fetch_assoc($all_cities_result)) {
    if (!in_array($row['city'], $cities)) {
        $cities[] = $row['city'];
    }
}

// Add common cities if database is empty
if (empty($cities)) {
    $cities = ['Karachi', 'Islamabad', 'Lahore', 'Rawalpindi', 'Faisalabad', 'Multan', 'Peshawar', 'Quetta'];
}

mysqli_close($connect);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Book a Shipment – Calma</title>
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

.sidebar-footer{padding:20px;border-top:1px solid rgba(255,255,255,0.1);}
.logout-btn{display:flex;align-items:center;gap:10px;width:100%;padding:12px 15px;background:rgba(239,68,68,0.2);color:#fca5a5;border:none;border-radius:8px;cursor:pointer;font-size:14px;font-weight:600;transition:all 0.2s;text-decoration:none;}
.logout-btn:hover{background:rgba(239,68,68,0.3);color:white;}

/* ========== MAIN CONTENT ========== */
.main{flex:1;padding:30px;overflow-y:auto;}
.page-header{margin-bottom:30px;}
.page-header h1{font-size:28px;color:var(--text);font-weight:700;}
.page-header p{color:var(--muted);font-size:14px;margin-top:5px;}

/* Alert Messages */
.alert{padding:15px 20px;border-radius:10px;margin-bottom:20px;font-size:14px;display:flex;align-items:center;gap:10px;}
.alert-success{background:#dcfce7;color:#166534;border:1px solid #86efac;}
.alert-error{background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;}

/* Form Card */
.card{background:white;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.04);overflow:hidden;margin-bottom:20px;}
.card-header{padding:20px;border-bottom:1px solid var(--border);background:#f8fafc;}
.card-title{font-size:16px;font-weight:600;color:var(--text);display:flex;align-items:center;gap:8px;}
.card-body{padding:25px;}

/* Form Styles */
.form-section{margin-bottom:30px;}
.form-section-title{font-size:18px;font-weight:700;color:var(--primary);margin-bottom:20px;padding-bottom:10px;border-bottom:2px solid var(--border);display:flex;align-items:center;gap:10px;}
.form-section-title i{font-size:20px;}

.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;}
.form-group{display:flex;flex-direction:column;gap:8px;}
.form-group.full-width{grid-column:1 / -1;}
.form-label{font-size:14px;font-weight:600;color:var(--text);}
.form-label .required{color:var(--danger);}
.form-input,.form-select,.form-textarea{padding:12px 15px;border:2px solid var(--border);border-radius:8px;font-size:14px;transition:all 0.3s;font-family:inherit;}
.form-input:focus,.form-select:focus,.form-textarea:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 4px rgba(200,85,61,0.1);}
.form-textarea{resize:vertical;min-height:80px;}

/* Submit Button */
.submit-btn{padding:14px 32px;background:linear-gradient(135deg,var(--primary),var(--primary-d));color:white;border:none;border-radius:8px;cursor:pointer;font-size:16px;font-weight:700;transition:all 0.3s;display:flex;align-items:center;gap:10px;width:100%;justify-content:center;}
.submit-btn:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(200,85,61,0.3);}
.submit-btn:active{transform:translateY(0);}

/* Info Box */
.info-box{background:#dbeafe;border:1px solid #93c5fd;padding:15px;border-radius:8px;margin-bottom:20px;display:flex;align-items:flex-start;gap:10px;}
.info-box i{color:#2563eb;font-size:18px;margin-top:2px;}
.info-box p{color:#1e40af;font-size:14px;line-height:1.6;}

/* Responsive */
@media(max-width:1024px){
  .form-grid{grid-template-columns:1fr;}
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
        <li><a href="book_shipment.php" class="active"><i class="fas fa-plus-circle"></i> Book Shipment</a></li>
        <li><a href="my_shipments.php"><i class="fas fa-box"></i> My Shipments</a></li>
        <li><a href="track_shipment.php"><i class="fas fa-search-location"></i> Track Package</a></li>

        <div style="font-size:11px;color:#94a3b8;text-transform:uppercase;letter-spacing:1px;padding:15px 15px 8px;margin-top:10px;">Account</div>
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
      <h1>📦 Book a Shipment</h1>
      <p>Fill in the details below to book your shipment</p>
    </div>

    <?php if ($success): ?>
      <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <div><?php echo $success; ?></div>
      </div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i>
        <div><?php echo htmlspecialchars($error); ?></div>
      </div>
    <?php endif; ?>

    <div class="info-box">
      <i class="fas fa-info-circle"></i>
      <p>Your shipment will be automatically assigned to the respective city agent and admin for processing. You'll receive updates via email and SMS.</p>
    </div>

    <form method="POST" action="">
      <!-- Sender Information -->
      <div class="card">
        <div class="card-header">
          <span class="card-title"><i class="fas fa-user"></i> Sender Information</span>
        </div>
        <div class="card-body">
          <div class="form-section">
            <div class="form-grid">
              <div class="form-group">
                <label class="form-label">Full Name <span class="required">*</span></label>
                <input type="text" name="sender_name" class="form-input" value="<?php echo htmlspecialchars($user_name); ?>" required>
              </div>
              <div class="form-group">
                <label class="form-label">Phone Number <span class="required">*</span></label>
                <input type="tel" name="sender_phone" class="form-input" value="<?php echo htmlspecialchars($user_phone); ?>" required>
              </div>
              <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="sender_email" class="form-input" value="<?php echo htmlspecialchars($user_email); ?>">
              </div>
              <div class="form-group">
                <label class="form-label">City <span class="required">*</span></label>
                <select name="sender_city" class="form-select" required>
                  <option value="">Select City</option>
                  <?php foreach ($cities as $city): ?>
                    <option value="<?php echo htmlspecialchars($city); ?>"><?php echo htmlspecialchars($city); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group full-width">
                <label class="form-label">Complete Address <span class="required">*</span></label>
                <textarea name="sender_address" class="form-textarea" placeholder="Enter complete address" required></textarea>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Receiver Information -->
      <div class="card">
        <div class="card-header">
          <span class="card-title"><i class="fas fa-user-friends"></i> Receiver Information</span>
        </div>
        <div class="card-body">
          <div class="form-section" style="margin-bottom:0;">
            <div class="form-grid">
              <div class="form-group">
                <label class="form-label">Full Name <span class="required">*</span></label>
                <input type="text" name="receiver_name" class="form-input" required>
              </div>
              <div class="form-group">
                <label class="form-label">Phone Number <span class="required">*</span></label>
                <input type="tel" name="receiver_phone" class="form-input" required>
              </div>
              <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="receiver_email" class="form-input">
              </div>
              <div class="form-group">
                <label class="form-label">City <span class="required">*</span></label>
                <select name="receiver_city" class="form-select" required>
                  <option value="">Select City</option>
                  <?php foreach ($cities as $city): ?>
                    <option value="<?php echo htmlspecialchars($city); ?>"><?php echo htmlspecialchars($city); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group full-width">
                <label class="form-label">Complete Address <span class="required">*</span></label>
                <textarea name="receiver_address" class="form-textarea" placeholder="Enter complete address" required></textarea>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Shipment Details -->
      <div class="card">
        <div class="card-header">
          <span class="card-title"><i class="fas fa-box"></i> Shipment Details</span>
        </div>
        <div class="card-body">
          <div class="form-section" style="margin-bottom:0;">
            <div class="form-grid">
              <div class="form-group">
                <label class="form-label">Shipment Type <span class="required">*</span></label>
                <select name="shipment_type" class="form-select" required>
                  <option value="">Select Type</option>
                  <option value="Standard">Standard (3-5 days) - Rs. 150/kg</option>
                  <option value="Express">Express (1-2 days) - Rs. 250/kg</option>
                  <option value="Overnight">Overnight (Next day) - Rs. 400/kg</option>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Weight (kg) <span class="required">*</span></label>
                <input type="number" name="weight" class="form-input" step="0.1" min="0.1" placeholder="e.g., 2.5" required>
              </div>
              <div class="form-group full-width">
                <label class="form-label">Description (Optional)</label>
                <textarea name="description" class="form-textarea" placeholder="Describe the contents of your shipment"></textarea>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Submit Button -->
      <button type="submit" class="submit-btn">
        <i class="fas fa-paper-plane"></i>
        Book Shipment
      </button>
    </form>
  </main>

</div>

<script>
// Auto-calculate estimated cost
const weightInput = document.querySelector('input[name="weight"]');
const typeSelect = document.querySelector('select[name="shipment_type"]');

function calculateCost() {
    const weight = parseFloat(weightInput.value) || 0;
    const type = typeSelect.value;
    
    let rate = 0;
    if (type === 'Standard') rate = 150;
    else if (type === 'Express') rate = 250;
    else if (type === 'Overnight') rate = 400;
    
    const cost = weight * rate;
    
    if (cost > 0) {
        console.log('Estimated cost: Rs. ' + cost);
    }
}

weightInput.addEventListener('input', calculateCost);
typeSelect.addEventListener('change', calculateCost);
</script>
</body>
</html>
