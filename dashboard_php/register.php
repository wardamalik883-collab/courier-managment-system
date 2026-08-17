<?php
session_start();

// Database connection
$connect = mysqli_connect("localhost", "root", "", "courier_management");
if (!$connect) {
    die("Connection failed: " . mysqli_connect_error());
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($connect, trim($_POST['name']));
    $username = mysqli_real_escape_string($connect, trim($_POST['username']));
    $email = mysqli_real_escape_string($connect, trim($_POST['email']));
    $phone = mysqli_real_escape_string($connect, trim($_POST['phone']));
    $city = mysqli_real_escape_string($connect, trim($_POST['city']));
    $address = mysqli_real_escape_string($connect, trim($_POST['address']));
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    // Validation
    if (empty($name) || empty($username) || empty($password)) {
        $error = "All marked fields are required";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters";
    } else {
        // Check if username already exists
        $check_query = "SELECT id FROM users WHERE username='$username'";
        $check_result = mysqli_query($connect, $check_query);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error = "Username already exists. Please choose another username.";
        } else {
            // Insert new user
            $password_hash = MD5($password);
            $query = "INSERT INTO users (username, password, name, email, phone, city, address, status) 
                      VALUES ('$username', '$password_hash', '$name', '$email', '$phone', '$city', '$address', 'Active')";
            
            if (mysqli_query($connect, $query)) {
                // Get the newly inserted user ID
                $new_user_id = mysqli_insert_id($connect);
                
                // Set session variables to auto-login the user
                $_SESSION['user_id'] = $new_user_id;
                $_SESSION['username'] = $username;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_type'] = 'user';
                $_SESSION['city'] = $city;
                $_SESSION['user'] = [
                    'id' => $new_user_id,
                    'username' => $username,
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'city' => $city,
                    'address' => $address
                ];
                
                // Redirect to user dashboard (user_dashboard.php)
                header("Location: home.php");
                exit;
            } else {
                $error = "Registration failed: " . mysqli_error($connect);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register – Calma CMS</title>
  <style>
    :root {
      --primary:#c8553d; --primary-d:#a33e2a;
      --text:#1e293b; --muted:#64748b;
      --border:#e2e8f0; --danger:#b91c1c; --danger-bg:#fca5a5;
      --success:#16a34a; --success-bg:#86efac;
      --r:10px; --shadow-lg:0 10px 25px rgba(0,0,0,0.1);
      --font:Arial, sans-serif;
    }
    body{margin:0;font-family:var(--font);background:linear-gradient(135deg,#0f172a 0%,#1e293b 60%,#0f172a 100%);min-height:100vh;padding:20px;}
    .register-page{display:flex;align-items:center;justify-content:center;min-height:100vh;}
    .register-card{background:#fff;border-radius:16px;padding:40px;width:100%;max-width:500px;box-shadow:var(--shadow-lg);}
    .register-logo{display:flex;align-items:center;gap:10px;justify-content:center;margin-bottom:6px;}
    .register-logo-mark{width:42px;height:42px;background:var(--primary);border-radius:11px;display:flex;align-items:center;justify-content:center;color:white;font-size:20px;font-weight:700;}
    .register-logo-text{font-size:24px;font-weight:700;color:var(--text);}
    .register-tag{text-align:center;font-size:11px;letter-spacing:3px;text-transform:uppercase;color:var(--muted);margin-bottom:20px;background:#f8fafc;padding:5px 16px;border-radius:20px;border:1px solid var(--border);display:inline-block;margin-left:50%;transform:translateX(-50%);}
    .form-group{margin-bottom:14px;}
    .form-group label{display:block;font-size:11px;font-weight:600;letter-spacing:1px;text-transform:uppercase;color:var(--muted);margin-bottom:5px;}
    .form-group input, .form-group select, .form-group textarea{width:100%;padding:10px 14px;border:1.5px solid var(--border);border-radius:var(--r);font-size:14px;color:var(--text);background:#f8fafc;transition:all 0.2s;box-sizing:border-box;}
    .form-group input:focus, .form-group select:focus, .form-group textarea:focus{outline:none;border-color:var(--primary);background:white;box-shadow:0 0 0 3px rgba(200,85,61,0.08);}
    .form-row{display:flex;gap:10px;}
    .form-row .form-group{flex:1;}
    .register-btn{width:100%;padding:14px;background:var(--primary);color:white;border-radius:var(--r);font-size:13px;font-weight:600;letter-spacing:1.5px;text-transform:uppercase;transition:all 0.3s;cursor:pointer;border:none;margin-top:10px;}
    .register-btn:hover{background:var(--primary-d);transform:translateY(-1px);}
    .register-error{background:var(--danger-bg);color:var(--danger);border-radius:var(--r);padding:11px 14px;font-size:13px;margin-bottom:16px;border:1px solid #fca5a5;}
    .register-success{background:var(--success-bg);color:var(--success);border-radius:var(--r);padding:11px 14px;font-size:13px;margin-bottom:16px;border:1px solid var(--success);}
    .register-footer{text-align:center;margin-top:20px;font-size:13px;color:var(--muted);}
    .register-footer a{color:var(--primary);text-decoration:none;}
    .register-footer a:hover{text-decoration:underline;}
    .required{color:var(--danger);}
  </style>
</head>
<body>
<div class="register-page">
  <div class="register-card">

    <div class="register-logo">
      <div class="register-logo-mark">C</div>
      <span class="register-logo-text">Calma</span>
    </div>
    <div class="register-tag">Create Your Account</div>

    <?php 
    if ($error) echo '<div class="register-error">' . $error . '</div>'; 
    if ($success) echo '<div class="register-success">' . $success . '</div>';
    ?>

    <form action="register.php" method="POST">
      <div class="form-group">
        <label>Full Name <span class="required">*</span></label>
        <input type="text" name="name" placeholder="Enter your full name" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
      </div>
      
      <div class="form-group">
        <label>Username <span class="required">*</span></label>
        <input type="text" name="username" placeholder="Choose a username" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
      </div>
      
      <div class="form-row">
        <div class="form-group">
          <label>Email</label>
          <input type="email" name="email" placeholder="your@email.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
        </div>
        <div class="form-group">
          <label>Phone</label>
          <input type="text" name="phone" placeholder="0300-0000000" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
        </div>
      </div>
      
      <div class="form-row">
        <div class="form-group">
          <label>City</label>
          <input type="text" name="city" placeholder="Your city" value="<?php echo isset($_POST['city']) ? htmlspecialchars($_POST['city']) : ''; ?>">
        </div>
      </div>
      
      <div class="form-group">
        <label>Address</label>
        <textarea name="address" rows="2" placeholder="Your address"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
      </div>
      
      <div class="form-group">
        <label>Password <span class="required">*</span></label>
        <input type="password" name="password" placeholder="Create a password" required>
      </div>
      
      <div class="form-group">
        <label>Confirm Password <span class="required">*</span></label>
        <input type="password" name="confirm_password" placeholder="Re-enter password" required>
      </div>
      
      <button type="submit" class="register-btn">Register</button>
    </form>

    <div class="register-footer">
      Already have an account? <a href="login.php">Login here</a>
    </div>

  </div>
</div>
</body>
</html>
