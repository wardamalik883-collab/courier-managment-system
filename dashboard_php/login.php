<?php
/**
 * Login Page - Calma Courier Management System
 * Secure authentication with proper session isolation
 */

// Start session with security settings
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_httponly', 1);
    session_start();
}

// Database connection
$connect = mysqli_connect("localhost", "root", "", "courier_management");
if (!$connect) {
    die("Connection failed: " . mysqli_connect_error());
}

$error = '';

// Check if already logged in - redirect based on role
if (isset($_SESSION['user']) && isset($_SESSION['user_type']) && isset($_SESSION['user_id'])) {
    session_write_close();
    
    switch ($_SESSION['user_type']) {
        case 'user':
            header('Location: home.php');
            break;
        case 'agent':
        case 'admin':
            header('Location: index.php');
            break;
        default:
            // Invalid role - destroy session
            session_unset();
            session_destroy();
    }
    exit;
}

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Destroy any existing session data
    session_unset();
    
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $user_type = trim($_POST['user_type'] ?? 'admin');

    if (empty($username) || empty($password)) {
        $error = "Please enter username and password";
    } else {
        $password_hash = MD5($password);
        $found = false;
        $user_data = null;

        // Validate user_type
        if (!in_array($user_type, ['admin', 'agent', 'user'])) {
            $error = "Invalid user type selected";
        } else {
            // Query based on user type
            if ($user_type === 'admin') {
                $query = "SELECT id, username, name, email, password FROM admins 
                         WHERE username='" . mysqli_real_escape_string($connect, $username) . "' 
                         AND password='" . mysqli_real_escape_string($connect, $password_hash) . "'";
            } elseif ($user_type === 'agent') {
                $query = "SELECT id, username, name, email, city, branch_code, password 
                         FROM agents 
                         WHERE username='" . mysqli_real_escape_string($connect, $username) . "' 
                         AND password='" . mysqli_real_escape_string($connect, $password_hash) . "'
                         AND status='Active'";
            } else { // user
                $query = "SELECT id, username, name, email, city, password 
                         FROM users 
                         WHERE username='" . mysqli_real_escape_string($connect, $username) . "' 
                         AND password='" . mysqli_real_escape_string($connect, $password_hash) . "'
                         AND status='Active'";
            }

            $result = mysqli_query($connect, $query);
            
            if ($result && mysqli_num_rows($result) === 1) {
                $user_data = mysqli_fetch_assoc($result);
                $found = true;
            }
        }

        if ($found && $user_data) {
            // CRITICAL: Regenerate session ID to prevent session fixation
            session_regenerate_id(true);
            
            // Set session variables with explicit values
            $_SESSION['user_id'] = $user_data['id'];
            $_SESSION['user_type'] = $user_type;
            $_SESSION['username'] = $user_data['username'];
            $_SESSION['user_name'] = $user_data['name'];
            $_SESSION['user_email'] = $user_data['email'] ?? '';
            
            // Set user-specific data
            if ($user_type === 'agent') {
                $_SESSION['city'] = $user_data['city'] ?? '';
                $_SESSION['branch_code'] = $user_data['branch_code'] ?? '';
                
                // Update last login
                mysqli_query($connect, "UPDATE agents SET last_login=NOW() WHERE id={$user_data['id']}");
                
            } elseif ($user_type === 'user') {
                $_SESSION['city'] = $user_data['city'] ?? '';
                
                // Update last login
                mysqli_query($connect, "UPDATE users SET last_login=NOW() WHERE id={$user_data['id']}");
            }
            
            // Store minimal user data (without password)
            unset($user_data['password']);
            $_SESSION['user'] = $user_data;
            
            // Log the login activity
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
            mysqli_query($connect, "INSERT INTO user_activity_logs (user_id, user_type, action, ip_address, created_at) 
                                    VALUES ({$_SESSION['user_id']}, '$user_type', 'Login successful', '$ip', NOW())");
            
            // Redirect based on role
            session_write_close();
            
            if ($user_type === 'user') {
                header('Location: home.php');
            } else {
                header('Location: index.php');
            }
            exit;
        } else {
            // Failed login attempt
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
            mysqli_query($connect, "INSERT INTO user_activity_logs (user_type, action, ip_address, created_at) 
                                    VALUES ('$user_type', 'Failed login attempt for username: $username', '$ip', NOW())");
            
            $error = "Invalid username, password or account is inactive";
        }
    }
}

mysqli_close($connect);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login – Calma Courier Services</title>
  <style>
    :root {
      --primary:#c8553d;
      --primary-d:#a33e2a;
      --text:#1e293b;
      --muted:#64748b;
      --border:#e2e8f0;
      --danger:#b91c1c;
      --danger-bg:#fca5a5;
      --success:#16a34a;
      --success-bg:#86efac;
      --r:12px;
      --shadow-lg:0 10px 40px rgba(0,0,0,0.15);
      --font:Arial, sans-serif;
    }
    *{margin:0;padding:0;box-sizing:border-box;}
    body{
      margin:0;
      font-family:var(--font);
      background:linear-gradient(135deg, #1e293b 0%, #334155 100%);
      min-height:100vh;
      display:flex;
      align-items:center;
      justify-content:center;
      padding:20px;
    }
    .login-container{
      display:flex;
      max-width:900px;
      width:100%;
      background:#fff;
      border-radius:20px;
      overflow:hidden;
      box-shadow:var(--shadow-lg);
    }
    .login-left{
      flex:1;
      background:linear-gradient(135deg, var(--primary) 0%, var(--primary-d) 100%);
      padding:40px;
      display:flex;
      flex-direction:column;
      justify-content:center;
      color:white;
      position:relative;
      overflow:hidden;
    }
    .login-left::before{
      content:'';
      position:absolute;
      top:-50%;
      left:-50%;
      width:200%;
      height:200%;
      background:radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
      animation: pulse 4s ease-in-out infinite;
    }
    @keyframes pulse{
      0%,100%{transform:scale(1);}
      50%{transform:scale(1.1);}
    }
    .login-left-content{
      position:relative;
      z-index:1;
    }
    .login-brand{
      display:flex;
      align-items:center;
      gap:15px;
      margin-bottom:30px;
    }
    .brand-logo{
      width:60px;
      height:60px;
      background:white;
      border-radius:15px;
      display:flex;
      align-items:center;
      justify-content:center;
      font-size:28px;
      font-weight:bold;
      color:var(--primary);
    }
    .brand-name{
      font-size:28px;
      font-weight:bold;
    }
    .login-left h1{
      font-size:32px;
      margin-bottom:15px;
      line-height:1.2;
    }
    .login-left p{
      font-size:14px;
      opacity:0.9;
      line-height:1.6;
      margin-bottom:30px;
    }
    .feature-list{
      list-style:none;
    }
    .feature-list li{
      padding:10px 0;
      display:flex;
      align-items:center;
      gap:10px;
      font-size:14px;
    }
    .feature-list li::before{
      content:'✓';
      background:white;
      color:var(--primary);
      width:20px;
      height:20px;
      border-radius:50%;
      display:flex;
      align-items:center;
      justify-content:center;
      font-size:12px;
      font-weight:bold;
      flex-shrink:0;
    }
    .login-right{
      flex:1;
      padding:50px 40px;
      display:flex;
      flex-direction:column;
      justify-content:center;
    }
    .login-header{
      text-align:center;
      margin-bottom:30px;
    }
    .login-header h2{
      font-size:26px;
      color:var(--text);
      margin-bottom:8px;
    }
    .login-header p{
      color:var(--muted);
      font-size:14px;
    }
    .user-type-selector{
      display:flex;
      gap:10px;
      margin-bottom:25px;
      background:#f8fafc;
      padding:5px;
      border-radius:var(--r);
    }
    .type-btn{
      flex:1;
      padding:12px;
      border:2px solid transparent;
      border-radius:var(--r);
      background:transparent;
      cursor:pointer;
      font-size:13px;
      font-weight:600;
      color:var(--muted);
      transition:all 0.3s;
      display:flex;
      flex-direction:column;
      align-items:center;
      gap:5px;
    }
    .type-btn .icon{
      font-size:18px;
    }
    .type-btn:hover{
      color:var(--primary);
      background:rgba(200,85,61,0.05);
    }
    .type-btn.active{
      background:var(--primary);
      color:white;
      box-shadow:0 4px 12px rgba(200,85,61,0.3);
    }
    .form-group{
      margin-bottom:20px;
    }
    .form-group label{
      display:block;
      font-size:12px;
      font-weight:600;
      letter-spacing:0.5px;
      text-transform:uppercase;
      color:var(--muted);
      margin-bottom:8px;
    }
    .form-group input{
      width:100%;
      padding:14px 16px;
      border:2px solid var(--border);
      border-radius:var(--r);
      font-size:14px;
      color:var(--text);
      background:#f8fafc;
      transition:all 0.3s;
    }
    .form-group input:focus{
      outline:none;
      border-color:var(--primary);
      background:white;
      box-shadow:0 0 0 4px rgba(200,85,61,0.1);
    }
    .login-btn{
      width:100%;
      padding:15px;
      background:var(--primary);
      color:white;
      border:none;
      border-radius:var(--r);
      font-size:14px;
      font-weight:700;
      letter-spacing:1px;
      text-transform:uppercase;
      cursor:pointer;
      transition:all 0.3s;
      margin-top:10px;
    }
    .login-btn:hover{
      background:var(--primary-d);
      transform:translateY(-2px);
      box-shadow:0 6px 20px rgba(200,85,61,0.3);
    }
    .error-msg{
      background:var(--danger-bg);
      color:var(--danger);
      padding:12px 16px;
      border-radius:var(--r);
      font-size:13px;
      margin-bottom:20px;
      border:1px solid #fca5a5;
      display:flex;
      align-items:center;
      gap:10px;
    }
    .error-msg::before{
      content:'⚠️';
    }
    .login-footer{
      text-align:center;
      margin-top:25px;
      padding-top:25px;
      border-top:1px solid var(--border);
      font-size:13px;
      color:var(--muted);
    }
    .login-footer a{
      color:var(--primary);
      text-decoration:none;
      font-weight:600;
    }
    .login-footer a:hover{
      text-decoration:underline;
    }
    .credentials-box{
      background:#f8fafc;
      padding:15px;
      border-radius:var(--r);
      margin-top:20px;
      font-size:12px;
    }
    .credentials-box h4{
      color:var(--text);
      margin-bottom:10px;
      font-size:13px;
    }
    .cred-row{
      display:flex;
      justify-content:space-between;
      padding:5px 0;
      border-bottom:1px dashed var(--border);
    }
    .cred-row:last-child{
      border-bottom:none;
    }
    .cred-label{
      color:var(--muted);
    }
    .cred-value{
      color:var(--text);
      font-weight:600;
      font-family:monospace;
    }
    @media(max-width:768px){
      .login-container{
        flex-direction:column;
      }
      .login-left{
        padding:30px;
      }
      .login-right{
        padding:30px 20px;
      }
      .user-type-selector{
        flex-direction:column;
      }
      .type-btn{
        flex-direction:row;
        justify-content:flex-start;
        gap:10px;
      }
    }
  </style>
</head>
<body>

<div class="login-container">

  <!-- Left Side - Branding -->
  <div class="login-left">
    <div class="login-left-content">
      <div class="login-brand">
        <div class="brand-logo">C</div>
        <div class="brand-name">Calma</div>
      </div>
      <h1>Courier Management System</h1>
      <p>Streamline your courier operations with our comprehensive management solution.</p>

      <ul class="feature-list">
        <li>Track shipments in real-time</li>
        <li>Manage bills and payments</li>
        <li>Send SMS notifications</li>
        <li>Generate detailed reports</li>
        <li>Multi-role access (Admin/Agent/User)</li>
      </ul>
    </div>
  </div>

  <!-- Right Side - Login Form -->
  <div class="login-right">
    <div class="login-header">
      <h2>Welcome Back!</h2>
      <p>Please login to your account</p>
    </div>

    <?php if ($error): ?>
      <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form action="login.php" method="POST">

      <!-- Role Selector -->
      <label style="display:block;font-size:12px;font-weight:600;letter-spacing:0.5px;text-transform:uppercase;color:var(--muted);margin-bottom:10px;">Select Your Role</label>
      <div class="user-type-selector">
        <button type="button" class="type-btn active" onclick="selectType('admin')" id="btn-admin">
          <span class="icon">👤</span>
          <span>Admin</span>
        </button>
        <button type="button" class="type-btn" onclick="selectType('agent')" id="btn-agent">
          <span class="icon">🏢</span>
          <span>Agent</span>
        </button>
        <button type="button" class="type-btn" onclick="selectType('user')" id="btn-user">
          <span class="icon">📦</span>
          <span>User</span>
        </button>
      </div>
      <input type="hidden" name="user_type" id="user_type" value="admin">

      <div class="form-group">
        <label>Username</label>
        <input type="text" name="username" placeholder="Enter your username" required autofocus autocomplete="username">
      </div>

      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" placeholder="Enter your password" required autocomplete="current-password">
      </div>

      <button type="submit" class="login-btn">Login to Dashboard</button>

    </form>

    <div class="login-footer">
      Don't have an account? <a href="register.php">Register here</a>
    </div>

    <!-- Demo Credentials -->
    <div class="credentials-box">
      <h4>📝 Demo Credentials:</h4>
      <div class="cred-row">
        <span class="cred-label">Admin:</span>
        <span class="cred-value">admin / admin123</span>
      </div>
      <div class="cred-row">
        <span class="cred-label">Agent:</span>
        <span class="cred-value">agent1 / agent123</span>
      </div>
      <div class="cred-row">
        <span class="cred-label">User:</span>
        <span class="cred-value">user1 / user123</span>
      </div>
    </div>

  </div>

</div>

<script>
function selectType(type) {
    // Update hidden input
    document.getElementById('user_type').value = type;

    // Update button styles
    document.querySelectorAll('.type-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    document.getElementById('btn-' + type).classList.add('active');

    // Update placeholder text based on role
    const usernameInput = document.querySelector('input[name="username"]');
    if (type === 'admin') {
        usernameInput.placeholder = 'Enter admin username';
    } else if (type === 'agent') {
        usernameInput.placeholder = 'Enter agent username';
    } else {
        usernameInput.placeholder = 'Enter your username';
    }
}
</script>

</body>
</html>
