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
$success = '';
$error = '';

// Handle add address
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_address'])) {
    $name = mysqli_real_escape_string($connect, $_POST['name']);
    $phone = mysqli_real_escape_string($connect, $_POST['phone']);
    $city = mysqli_real_escape_string($connect, $_POST['city']);
    $address = mysqli_real_escape_string($connect, $_POST['address']);
    
    $insert_query = "INSERT INTO customers (name, phone, city, address) VALUES ('$name', '$phone', '$city', '$address')";
    if (mysqli_query($connect, $insert_query)) {
        $success = "Address added successfully!";
    } else {
        $error = "Error adding address: " . mysqli_error($connect);
    }
}

// Handle delete address
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    mysqli_query($connect, "DELETE FROM customers WHERE id=$delete_id");
    header('Location: addresses.php?success=1');
    exit;
}

// Get user's addresses (by phone match)
$user_phone = $user['phone'] ?? '';
$addresses_query = "SELECT * FROM customers WHERE phone='$user_phone' ORDER BY id DESC";
$addresses_result = mysqli_query($connect, $addresses_query);
$addresses = [];
while ($row = mysqli_fetch_assoc($addresses_result)) {
    $addresses[] = $row;
}

$success = $success ?: (isset($_GET['success']) ? 'Address deleted successfully!' : '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Addresses – Calma</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root {
  --primary:#c8553d;
  --primary-d:#a33e2a;
  --text:#1e293b;
  --muted:#64748b;
  --border:#e2e8f0;
  --success:#16a34a;
  --danger:#ef4444;
  --bg:#f8fafc;
}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Segoe UI',Arial,sans-serif;background:var(--bg);min-height:100vh;}

.layout{display:flex;min-height:100vh;}
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
.logout-btn{display:flex;align-items:center;gap:10px;width:100%;padding:12px 15px;background:rgba(239,68,68,0.2);color:#fca5a5;border:none;border-radius:8px;cursor:pointer;font-size:14px;font-weight:600;transition:all 0.2s;}
.logout-btn:hover{background:rgba(239,68,68,0.3);color:white;}

.main{flex:1;padding:30px;overflow-y:auto;}
.page-header{margin-bottom:30px;}
.page-header h1{font-size:28px;color:var(--text);font-weight:700;margin-bottom:5px;}
.page-header p{color:var(--muted);font-size:14px;}

.success-msg{background:#dcfce7;border:1px solid var(--success);color:var(--success);padding:15px;border-radius:8px;margin-bottom:20px;}

.content-grid{display:grid;grid-template-columns:1fr 400px;gap:20px;}

.card{background:white;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.04);overflow:hidden;}
.card-header{padding:20px;border-bottom:1px solid var(--border);}
.card-title{font-size:18px;font-weight:600;color:var(--text);}
.card-body{padding:25px;}

.address-item{background:#f8fafc;padding:20px;border-radius:10px;margin-bottom:15px;border-left:4px solid var(--primary);position:relative;}
.address-header{display:flex;justify-content:space-between;align-items:start;margin-bottom:10px;}
.address-name{font-size:16px;font-weight:600;color:var(--text);}
.address-actions{display:flex;gap:8px;}
.address-actions a{padding:6px 12px;border-radius:6px;font-size:12px;text-decoration:none;}
.address-actions .delete-btn{background:#fee2e2;color:var(--danger);}
.address-actions .delete-btn:hover{background:#fecaca;}
.address-text{font-size:14px;color:var(--muted);line-height:1.8;}
.address-text strong{color:var(--text);}

.form-group{margin-bottom:20px;}
.form-group label{display:block;font-size:13px;font-weight:600;color:var(--text);margin-bottom:8px;}
.form-group input,.form-group textarea{width:100%;padding:12px;border:1.5px solid var(--border);border-radius:8px;font-size:14px;transition:border-color 0.2s;}
.form-group input:focus,.form-group textarea:focus{outline:none;border-color:var(--primary);}
.form-group textarea{resize:vertical;min-height:100px;}

.btn{padding:12px 24px;border:none;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-block;}
.btn-primary{background:var(--primary);color:white;}
.btn-primary:hover{background:var(--primary-d);}

.empty-state{text-align:center;padding:40px;color:var(--muted);}
.empty-state i{font-size:48px;margin-bottom:15px;opacity:0.5;}

@media(max-width:1024px){
  .content-grid{grid-template-columns:1fr;}
}
@media(max-width:768px){
  .layout{flex-direction:column;}
  .sidebar{width:100%;height:auto;position:relative;}
}
</style>
</head>
<body>
<div class="layout">
  
  <aside class="sidebar">
    <div class="sidebar-header">
      <a href="index.php" class="sidebar-logo">
        <div class="logo-mark">C</div>
        Calma
      </a>
      <div class="sidebar-user">
        <div class="user-avatar-lg"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></div>
        <div class="user-name"><?php echo htmlspecialchars($user['name']); ?></div>
        <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>
      </div>
    </div>

    <nav class="sidebar-nav">
      <ul>
        <li><a href="user_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
        <li><a href="my_shipments.php"><i class="fas fa-box"></i> My Shipments</a></li>
        <li><a href="track_shipment.php"><i class="fas fa-search-location"></i> Track Package</a></li>

        <div class="nav-section">Account</div>
        <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
        <li><a href="addresses.php" class="active"><i class="fas fa-map-marker-alt"></i> Addresses</a></li>
      </ul>
    </nav>

    <div class="sidebar-footer">
      <a href="logout.php" class="logout-btn">
        <i class="fas fa-sign-out-alt"></i>
        Logout
      </a>
    </div>
  </aside>

  <main class="main">
    <div class="page-header">
      <h1><i class="fas fa-map-marker-alt" style="color:var(--danger);"></i> My Addresses</h1>
      <p>Manage your saved delivery addresses</p>
    </div>

    <?php if($success): ?>
      <div class="success-msg">✓ <?php echo $success; ?></div>
    <?php endif; ?>

    <div class="content-grid">
      
      <!-- Addresses List -->
      <div class="card">
        <div class="card-header">
          <div class="card-title">Saved Addresses</div>
        </div>
        <div class="card-body">
          <?php if(empty($addresses)): ?>
            <div class="empty-state">
              <i class="fas fa-map-marker-alt"></i>
              <h4>No Saved Addresses</h4>
              <p>Add your first address using the form</p>
            </div>
          <?php else: ?>
            <?php foreach($addresses as $addr): ?>
            <div class="address-item">
              <div class="address-header">
                <div class="address-name">📍 <?php echo htmlspecialchars($addr['name']); ?></div>
                <div class="address-actions">
                  <a href="?delete=<?php echo $addr['id']; ?>" class="delete-btn" onclick="return confirm('Delete this address?')">
                    <i class="fas fa-trash"></i> Delete
                  </a>
                </div>
              </div>
              <div class="address-text">
                <strong>Address:</strong> <?php echo htmlspecialchars($addr['address']); ?><br>
                <strong>City:</strong> <?php echo htmlspecialchars($addr['city']); ?><br>
                <strong>Phone:</strong> <?php echo htmlspecialchars($addr['phone']); ?>
              </div>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- Add Address Form -->
      <div class="card">
        <div class="card-header">
          <div class="card-title">Add New Address</div>
        </div>
        <div class="card-body">
          <form method="POST">
            <div class="form-group">
              <label>Full Name</label>
              <input type="text" name="name" required placeholder="Muhammad Ahmed">
            </div>
            <div class="form-group">
              <label>Phone Number</label>
              <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required placeholder="0300-0000000">
            </div>
            <div class="form-group">
              <label>City</label>
              <input type="text" name="city" required placeholder="Karachi">
            </div>
            <div class="form-group">
              <label>Complete Address</label>
              <textarea name="address" required placeholder="House #, Street, Area, Landmark..."></textarea>
            </div>
            <button type="submit" name="add_address" class="btn btn-primary" style="width:100%;">
              <i class="fas fa-plus"></i> Add Address
            </button>
          </form>
        </div>
      </div>

    </div>
  </main>
</div>
</body>
</html>
