<?php
/**
 * TRACK SHIPMENT PAGE
 * Calma Courier Management System
 * Public page - anyone can track shipment
 */

session_start();

// Database connection
$connect = mysqli_connect("localhost", "root", "", "courier_management");
if (!$connect) {
    die("Connection failed: " . mysqli_connect_error());
}

$tracking_number = isset($_GET['tracking_number']) ? trim($_GET['tracking_number']) : '';
$shipment = null;
$tracking_history = [];
$error = '';
$success = false;

// Search shipment
if (!empty($tracking_number)) {
    $tracking_number_esc = mysqli_real_escape_string($connect, $tracking_number);
    
    // Fetch shipment details
    $query = "SELECT * FROM shipments WHERE tracking_number='$tracking_number_esc'";
    $result = mysqli_query($connect, $query);

    if (mysqli_num_rows($result) > 0) {
        $shipment = mysqli_fetch_assoc($result);
        $success = true;

        // Fetch tracking history
        $history_query = "SELECT * FROM shipment_tracking WHERE tracking_number='$tracking_number_esc' ORDER BY created_at DESC";
        $history_result = mysqli_query($connect, $history_query);
        while ($row = mysqli_fetch_assoc($history_result)) {
            $tracking_history[] = $row;
        }
        
        // If no tracking history, create one from shipment data
        if (empty($tracking_history)) {
            $tracking_history[] = [
                'status' => $shipment['status'],
                'description' => 'Shipment booked',
                'created_at' => $shipment['booked_date']
            ];
        }
    } else {
        $error = "No shipment found with tracking number: " . htmlspecialchars($tracking_number);
    }
}

$is_logged_in = isset($_SESSION['user']) && isset($_SESSION['user_type']);
$user_name = $_SESSION['user_name'] ?? $_SESSION['user']['name'] ?? '';
$user_type = $_SESSION['user_type'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Track Your Shipment – Calma Courier</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root {
    --primary: #c8553d;
    --primary-dark: #a33e2a;
    --success: #16a34a;
    --warning: #f59e0b;
    --info: #3b82f6;
    --danger: #ef4444;
    --text: #1e293b;
    --muted: #64748b;
    --bg: #f8fafc;
    --white: #ffffff;
    --shadow: 0 10px 40px rgba(0,0,0,0.1);
    --shadow-lg: 0 20px 60px rgba(0,0,0,0.15);
}

* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    font-family: 'Segoe UI', Arial, sans-serif;
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
    min-height: 100vh;
    padding-top: 80px;
}

/* ========== NAVBAR ========== */
.navbar {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    background: rgba(255,255,255,0.98);
    backdrop-filter: blur(10px);
    box-shadow: 0 2px 20px rgba(0,0,0,0.08);
    z-index: 1000;
    padding: 15px 0;
}

.nav-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.logo {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 24px;
    font-weight: 700;
    color: var(--primary);
    text-decoration: none;
}

.logo-icon {
    width: 45px;
    height: 45px;
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 20px;
    font-weight: bold;
}

.nav-links {
    display: flex;
    gap: 30px;
    align-items: center;
}

.hamburger {
    display: none;
    flex-direction: column;
    cursor: pointer;
    gap: 5px;
    padding: 5px;
    background: none;
    border: none;
}

.hamburger span {
    width: 25px;
    height: 3px;
    background: var(--text);
    border-radius: 3px;
    transition: all 0.3s ease;
}

.hamburger.active span:nth-child(1) {
    transform: rotate(45deg) translate(5px, 5px);
}

.hamburger.active span:nth-child(2) {
    opacity: 0;
}

.hamburger.active span:nth-child(3) {
    transform: rotate(-45deg) translate(7px, -6px);
}

.nav-links a {
    color: var(--text);
    text-decoration: none;
    font-weight: 500;
    font-size: 14px;
    transition: color 0.3s;
}

.nav-links a:hover {
    color: var(--primary);
}

.nav-btn {
    padding: 10px 24px;
    border-radius: 25px;
    font-weight: 600;
    font-size: 13px;
    text-decoration: none;
    transition: all 0.3s;
}

.nav-btn-outline {
    border: 2px solid var(--primary);
    color: var(--primary);
    background: transparent;
}

.nav-btn-outline:hover {
    background: var(--primary);
    color: white;
}

.nav-btn-primary {
    background: var(--primary);
    color: white;
    border: 2px solid var(--primary);
}

.nav-btn-primary:hover {
    background: var(--primary-dark);
    border-color: var(--primary-dark);
}

/* ========== TRACK PAGE ========== */
.track-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 40px 20px;
}

/* Header */
.track-header {
    text-align: center;
    margin-bottom: 30px;
    color: white;
}

.track-header h1 {
    font-size: 32px;
    margin-bottom: 10px;
    text-shadow: 0 2px 10px rgba(0,0,0,0.2);
}

.track-header p {
    font-size: 14px;
    opacity: 0.9;
}

/* Search Form */
.track-form {
    background: white;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    margin-bottom: 30px;
}

.track-form h2 {
    font-size: 24px;
    color: var(--text);
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.track-form h2 i {
    color: var(--primary);
}

.track-form .subtitle {
    color: var(--muted);
    font-size: 14px;
    margin-bottom: 25px;
}

.search-box {
    display: flex;
    gap: 10px;
}

.search-box input {
    flex: 1;
    padding: 15px 20px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-size: 15px;
    transition: all 0.3s;
}

.search-box input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 4px rgba(200,85,61,0.1);
}

.search-box button {
    padding: 15px 30px;
    background: var(--primary);
    color: white;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    font-size: 15px;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 8px;
}

.search-box button:hover {
    background: var(--primary-dark);
    transform: translateY(-2px);
}

/* Error Message */
.error-message {
    background: #fee2e2;
    color: #991b1b;
    padding: 15px 20px;
    border-radius: 10px;
    border-left: 4px solid var(--danger);
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
}

/* Result Card */
.result-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    overflow: hidden;
    margin-bottom: 20px;
}

.result-header {
    background: var(--primary);
    color: white;
    padding: 25px;
}

.result-header h3 {
    font-size: 20px;
    margin-bottom: 5px;
}

.result-header .tracking-num {
    font-family: monospace;
    font-size: 16px;
    opacity: 0.9;
}

.result-body {
    padding: 25px;
}

/* Status Badge */
.status-badge {
    display: inline-block;
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 14px;
    margin-bottom: 20px;
}

.status-pending { background: #fef3c7; color: #92400e; }
.status-transit { background: #dbeafe; color: #1e40af; }
.status-delivered { background: #dcfce7; color: #166534; }
.status-returned { background: #fee2e2; color: #991b1b; }

/* Info Grid */
.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 25px;
}

.info-box {
    background: #f8fafc;
    padding: 15px;
    border-radius: 8px;
    border-left: 4px solid var(--primary);
}

.info-box label {
    font-size: 11px;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: block;
    margin-bottom: 5px;
}

.info-box .value {
    font-size: 15px;
    color: var(--text);
    font-weight: 600;
}

/* Timeline */
.timeline {
    margin-top: 25px;
}

.timeline h4 {
    font-size: 16px;
    color: var(--text);
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.timeline-items {
    position: relative;
    padding-left: 30px;
}

.timeline-items::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 3px;
    background: #e2e8f0;
}

.timeline-item {
    position: relative;
    padding-bottom: 25px;
    padding-left: 20px;
}

.timeline-item:last-child {
    padding-bottom: 0;
}

.timeline-item::before {
    content: '';
    position: absolute;
    left: -26px;
    top: 0;
    width: 15px;
    height: 15px;
    background: var(--primary);
    border-radius: 50%;
    border: 3px solid white;
    box-shadow: 0 0 0 2px #e2e8f0;
}

.timeline-item.completed::before {
    background: var(--success);
}

.timeline-date {
    font-size: 12px;
    color: var(--muted);
    margin-bottom: 5px;
}

.timeline-status {
    font-size: 15px;
    color: var(--text);
    font-weight: 600;
    margin-bottom: 5px;
}

.timeline-desc {
    font-size: 13px;
    color: var(--muted);
    line-height: 1.5;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.1);
}

.empty-state i {
    font-size: 80px;
    color: var(--muted);
    opacity: 0.3;
    margin-bottom: 20px;
}

.empty-state h3 {
    font-size: 20px;
    color: var(--text);
    margin-bottom: 10px;
}

.empty-state p {
    color: var(--muted);
    font-size: 14px;
    margin-bottom: 25px;
}

/* Sample Tracking Numbers */
.sample-tracking {
    background: rgba(255,255,255,0.1);
    padding: 15px;
    border-radius: 10px;
    margin-top: 20px;
}

.sample-tracking h4 {
    color: white;
    font-size: 14px;
    margin-bottom: 10px;
}

.sample-tracking .samples {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.sample-tracking .sample-btn {
    background: rgba(255,255,255,0.2);
    color: white;
    padding: 8px 15px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 12px;
    font-family: monospace;
    transition: all 0.3s;
}

.sample-tracking .sample-btn:hover {
    background: rgba(255,255,255,0.3);
}

/* ========== FOOTER ========== */
.footer {
    background: var(--secondary);
    color: white;
    padding: 60px 30px 30px;
    margin-top: 60px;
}

.footer-content {
    max-width: 1200px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 40px;
    margin-bottom: 40px;
}

.footer-section h4 {
    font-size: 18px;
    margin-bottom: 20px;
    color: var(--primary);
}

.footer-section p,
.footer-section a {
    color: rgba(255,255,255,0.7);
    font-size: 14px;
    line-height: 2;
    text-decoration: none;
    display: block;
}

.footer-section a:hover {
    color: white;
}

.footer-bottom {


    max-width: 1200px;
    margin: 0 auto;
    padding-top: 30px;
    border-top: 1px solid rgba(255,255,255,0.1);
    text-align: center;
    color: rgba(255,255,255,0.5);
    font-size: 13px;
}

/* Responsive */
@media (max-width: 1024px) {
    .nav-container {
        padding: 0 20px;
    }
}

@media (max-width: 768px) {
    body {
        padding-top: 70px;
    }

    .navbar {
        padding: 12px 0;
    }

    .nav-container {
        padding: 0 15px;
    }

    .hamburger {
        display: flex;
    }

    .nav-links {
        position: fixed;
        top: 70px;
        left: -100%;
        width: 100%;
        background: white;
        flex-direction: column;
        padding: 20px 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        transition: left 0.3s cubic-bezier(0.4,0,0.2,1);
        z-index: 999;
        gap: 15px;
    }

    .nav-links.active {
        left: 0;
    }

    .nav-links a {
        width: 100%;
        text-align: center;
        padding: 10px;
    }

    .nav-btn {
        width: 100%;
        text-align: center;
        display: block;
    }

    .logo {
        font-size: 20px;
    }

    .logo-icon {
        width: 40px;
        height: 40px;
        font-size: 18px;
    }

    .track-container {
        padding: 20px 15px;
    }

    .track-header h1 {
        font-size: 24px;
    }

    .track-header p {
        font-size: 13px;
    }

    .track-form {
        padding: 20px;
    }

    .track-form h2 {
        font-size: 20px;
    }

    .search-box {
        flex-direction: column;
    }

    .search-box input {
        padding: 13px 16px;
    }

    .search-box button {
        width: 100%;
        justify-content: center;
        padding: 13px 24px;
    }

    .info-grid {
        grid-template-columns: 1fr;
    }

    .result-header {
        padding: 20px;
    }

    .result-header h3 {
        font-size: 18px;
    }

    .result-body {
        padding: 20px;
    }

    .sample-tracking .samples {
        flex-direction: column;
    }

    .sample-tracking .sample-btn {
        text-align: center;
    }

    .footer {
        padding: 40px 20px 20px;
        margin-top: 40px;
    }

    .footer-content {
        grid-template-columns: 1fr;
        gap: 30px;
    }
}

@media (max-width: 480px) {
    .track-header h1 {
        font-size: 20px;
    }

    .track-form {
        padding: 15px;
    }

    .track-form h2 {
        font-size: 18px;
    }

    .result-header h3 {
        font-size: 16px;
    }

    .result-header .tracking-num {
        font-size: 14px;
    }

    .info-box {
        padding: 12px;
    }

    .info-box label {
        font-size: 10px;
    }

    .info-box .value {
        font-size: 14px;
    }

    .timeline-items {
        padding-left: 25px;
    }

    .timeline-item {
        padding-left: 15px;
    }

    .timeline-item::before {
        left: -22px;
        width: 12px;
        height: 12px;
    }

    .timeline-status {
        font-size: 14px;
    }

    .timeline-desc {
        font-size: 12px;
    }

    .empty-state {
        padding: 40px 15px;
    }

    .empty-state i {
        font-size: 60px;
    }

    .empty-state h3 {
        font-size: 18px;
    }

    .empty-state p {
        font-size: 13px;
    }
}

@media (max-width: 768px) and (orientation: landscape) {
    body {
        padding-top: 60px;
    }

    .nav-links {
        max-height: calc(100vh - 60px);
        overflow-y: auto;
    }
}
</style>
</head>
<body>

<!-- ========== NAVBAR ========== -->
<nav class="navbar">
    <div class="nav-container">
        <a href="home.php" class="logo">
            <div class="logo-icon">C</div>
            Calma
        </a>
        <button class="hamburger" id="hamburger" aria-label="Toggle navigation">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <div class="nav-links" id="navLinks">
            <a href="home.php">Home</a>
            <a href="home.php#services">Services</a>
            <a href="home.php#features">Why Us</a>
            <a href="home.php#contact">Contact</a>
            <a href="track_shipment.php">Track Package</a>
            <?php if ($is_logged_in): ?>
                <?php if ($user_type === 'user'): ?>
                    <a href="my_shipments.php" class="nav-btn nav-btn-primary">My Shipments</a>
                    <a href="logout.php" class="nav-btn nav-btn-outline">Logout</a>
                <?php else: ?>
                    <a href="index.php" class="nav-btn nav-btn-primary">Dashboard</a>
                    <a href="logout.php" class="nav-btn nav-btn-outline">Logout</a>
                <?php endif; ?>
            <?php else: ?>
                <a href="login.php" class="nav-btn nav-btn-outline">Login</a>
                <a href="register.php" class="nav-btn nav-btn-primary">Sign Up</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- ========== TRACK PAGE CONTENT ========== -->
<div class="track-container">

    <!-- Header -->
    <div class="track-header">
        <h1><i class="fas fa-search-location"></i> Track Your Shipment</h1>
        <p>Enter your tracking/consignment number to view status</p>
    </div>

    <!-- Search Form -->
    <div class="track-form">
        <h2><i class="fas fa-box"></i> Track Package</h2>
        <p class="subtitle">Enter your tracking number to see real-time status</p>

        <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <form method="GET" class="search-box">
            <input type="text" name="tracking_number" placeholder="Enter Tracking Number (e.g., CLM-2026-0001)" 
                   value="<?php echo htmlspecialchars($tracking_number); ?>" required autofocus>
            <button type="submit">
                <i class="fas fa-search"></i>
                Track
            </button>
        </form>

        <!-- Sample Tracking Numbers -->
        <?php
        $sample_result = mysqli_query($connect, "SELECT tracking_number FROM shipments LIMIT 3");
        if (mysqli_num_rows($sample_result) > 0):
        ?>
        <div class="sample-tracking">
            <h4>📋 Sample Tracking Numbers:</h4>
            <div class="samples">
                <?php while ($sample = mysqli_fetch_assoc($sample_result)): ?>
                    <a href="?tracking_number=<?php echo urlencode($sample['tracking_number']); ?>" class="sample-btn">
                        <?php echo htmlspecialchars($sample['tracking_number']); ?>
                    </a>
                <?php endwhile; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Result -->
    <?php if ($success && $shipment): ?>
    <div class="result-card">
        <div class="result-header">
            <h3>📦 Shipment Details</h3>
            <div class="tracking-num">Tracking: <?php echo htmlspecialchars($shipment['tracking_number']); ?></div>
        </div>

        <div class="result-body">
            <!-- Status Badge -->
            <?php
            $status_class = 'status-pending';
            if ($shipment['status'] === 'Delivered') $status_class = 'status-delivered';
            elseif ($shipment['status'] === 'In Transit' || $shipment['status'] === 'Out for Delivery') $status_class = 'status-transit';
            elseif ($shipment['status'] === 'Returned') $status_class = 'status-returned';
            ?>
            <span class="status-badge <?php echo $status_class; ?>">
                <i class="fas fa-circle"></i> <?php echo htmlspecialchars($shipment['status']); ?>
            </span>

            <!-- Info Grid -->
            <div class="info-grid">
                <div class="info-box">
                    <label><i class="fas fa-user"></i> Sender</label>
                    <div class="value"><?php echo htmlspecialchars($shipment['sender_name']); ?></div>
                    <div style="font-size:12px;color:var(--muted);"><?php echo htmlspecialchars($shipment['sender_city']); ?></div>
                </div>

                <div class="info-box">
                    <label><i class="fas fa-user"></i> Receiver</label>
                    <div class="value"><?php echo htmlspecialchars($shipment['receiver_name']); ?></div>
                    <div style="font-size:12px;color:var(--muted);"><?php echo htmlspecialchars($shipment['receiver_city']); ?></div>
                </div>

                <div class="info-box">
                    <label><i class="fas fa-box"></i> Type</label>
                    <div class="value"><?php echo htmlspecialchars($shipment['type']); ?></div>
                </div>

                <div class="info-box">
                    <label><i class="fas fa-weight-hanging"></i> Weight</label>
                    <div class="value"><?php echo $shipment['weight'] ? $shipment['weight'].' kg' : '-'; ?></div>
                </div>

                <div class="info-box">
                    <label><i class="fas fa-rupee-sign"></i> Amount</label>
                    <div class="value">Rs. <?php echo number_format($shipment['amount']); ?></div>
                </div>

                <div class="info-box">
                    <label><i class="fas fa-calendar"></i> Booked Date</label>
                    <div class="value"><?php echo $shipment['booked_date']; ?></div>
                </div>
            </div>

            <!-- Tracking Timeline -->
            <div class="timeline">
                <h4><i class="fas fa-history"></i> Tracking History</h4>
                <div class="timeline-items">
                    <?php foreach ($tracking_history as $index => $history): ?>
                    <div class="timeline-item <?php echo $index === 0 ? 'completed' : ''; ?>">
                        <div class="timeline-date">
                            <i class="far fa-clock"></i> <?php echo $history['created_at'] ?? 'N/A'; ?>
                        </div>
                        <div class="timeline-status">
                            <i class="fas fa-circle"></i> <?php echo htmlspecialchars($history['status']); ?>
                        </div>
                        <div class="timeline-desc">
                            <?php echo htmlspecialchars($history['description']); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Empty State (when no search performed) -->
    <?php if (empty($tracking_number) && empty($error)): ?>
    <div class="empty-state">
        <i class="fas fa-search"></i>
        <h3>Enter a tracking number to begin</h3>
        <p>Type your consignment/tracking number above and click Track</p>
    </div>
    <?php endif; ?>

</div>

<!-- ========== FOOTER ========== -->
<footer class="footer" id="contact">
    <div class="footer-content">
        <div class="footer-section">
            <h4>📍 Calma Courier</h4>
            <p>Your trusted partner for fast and reliable delivery services across Pakistan.</p>
        </div>
        <div class="footer-section">
            <h4>Quick Links</h4>
            <a href="home.php">Home</a>
            <a href="home.php#services">Services</a>
            <a href="track_shipment.php">Track Package</a>
            <a href="login.php">Login</a>
        </div>
        <div class="footer-section">
            <h4>Services</h4>
            <a href="home.php#services">Standard Delivery</a>
            <a href="home.php#services">Express Delivery</a>
            <a href="home.php#services">Overnight Delivery</a>
            <a href="home.php#services">Corporate Solutions</a>
        </div>
        <div class="footer-section">
            <h4>Contact Us</h4>
            <p>📞 0300-0000000</p>
            <p>📧 info@calmacourier.com</p>
            <p>📍 Karachi, Pakistan</p>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; <?php echo date('Y'); ?> Calma Courier Services. All rights reserved.</p>
    </div>
</footer>

<script>
// Auto-focus on input if no tracking number
<?php if (empty($tracking_number)): ?>
document.querySelector('input[name="tracking_number"]').focus();
<?php endif; ?>

// Hamburger menu toggle
const hamburger = document.getElementById('hamburger');
const navLinks = document.getElementById('navLinks');

if (hamburger && navLinks) {
    hamburger.addEventListener('click', () => {
        hamburger.classList.toggle('active');
        navLinks.classList.toggle('active');
    });

    // Close menu when clicking on a link
    navLinks.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', () => {
            hamburger.classList.remove('active');
            navLinks.classList.remove('active');
        });
    });

    // Close menu when clicking outside
    document.addEventListener('click', (e) => {
        if (!hamburger.contains(e.target) && !navLinks.contains(e.target)) {
            hamburger.classList.remove('active');
            navLinks.classList.remove('active');
        }
    });
}

// Navbar scroll effect
window.addEventListener('scroll', function() {
    const navbar = document.querySelector('.navbar');
    if (window.scrollY > 50) {
        navbar.style.boxShadow = '0 4px 30px rgba(0,0,0,0.1)';
    } else {
        navbar.style.boxShadow = '0 2px 20px rgba(0,0,0,0.08)';
    }
});

// Close menu on window resize
let resizeTimer;
window.addEventListener('resize', () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => {
        if (window.innerWidth > 768) {
            hamburger.classList.remove('active');
            navLinks.classList.remove('active');
        }
    }, 250);
});

// Close menu on Escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && navLinks.classList.contains('active')) {
        hamburger.classList.remove('active');
        navLinks.classList.remove('active');
    }
});
</script>

</body>
</html>
<?php
mysqli_close($connect);
?>
