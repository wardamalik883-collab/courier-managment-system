<?php
/**
 * HOME PAGE (Public with optional login)
 * Calma Courier Services
 */

// Start session securely
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

// Add no-cache headers for logged-in users
if (isset($_SESSION['user'])) {
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
}

$connect = mysqli_connect("localhost", "root", "", "courier_management");
if (!$connect) {
    die("Connection failed: " . mysqli_connect_error());
}

$is_logged_in = isset($_SESSION['user']) && isset($_SESSION['user_type']);
$user_name = $_SESSION['user_name'] ?? $_SESSION['user']['name'] ?? '';
$user_type = $_SESSION['user_type'] ?? '';
$username = $_SESSION['username'] ?? $_SESSION['user']['username'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calma Courier Services - Fast & Reliable Delivery</title>
    <style>
        :root {
            --primary: #c8553d;
            --primary-dark: #a33e2a;
            --secondary: #1e293b;
            --accent: #f59e0b;
            --light: #f8fafc;
            --gray: #64748b;
            --white: #ffffff;
            --shadow: 0 10px 40px rgba(0,0,0,0.1);
            --shadow-lg: 0 20px 60px rgba(0,0,0,0.15);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--white);
            color: var(--secondary);
            overflow-x: hidden;
        }

        /* ========== HEADER ========== */
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
        }

        .hamburger span {
            width: 25px;
            height: 3px;
            background: var(--secondary);
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
            color: var(--secondary);
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

        /* ========== HERO SECTION ========== */
        .hero {
            min-height: 100vh;
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 100px 30px 60px;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.05) 0%, transparent 70%);
            animation: pulse 4s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .hero-content {
            max-width: 1200px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
            position: relative;
            z-index: 1;
        }

        .hero-text h1 {
            font-size: 52px;
            color: white;
            line-height: 1.1;
            margin-bottom: 20px;
            font-weight: 800;
        }

        .hero-text p {
            font-size: 18px;
            color: rgba(255,255,255,0.9);
            line-height: 1.7;
            margin-bottom: 35px;
        }

        .hero-buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 14px 32px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            transition: all 0.3s;
            cursor: pointer;
            border: none;
            display: inline-block;
        }

        .btn-white {
            background: white;
            color: var(--primary);
        }

        .btn-white:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .btn-outline-white {
            background: transparent;
            color: red !important;
            border: 2px solid white;
        }

        .btn-outline-white:hover {
            background: white;
            color: var(--primary) !important;
        }

        /* Track Package Card */
        .track-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: var(--shadow-lg);
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .track-card h3 {
            font-size: 24px;
            margin-bottom: 10px;
            color: var(--secondary);
        }

        .track-card p {
            color: var(--gray);
            margin-bottom: 25px;
            font-size: 14px;
        }

        .track-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .track-input {
            padding: 15px 20px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .track-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(200,85,61,0.1);
        }

        /* ========== SERVICES SECTION ========== */
        .services {
            padding: 100px 30px;
            background: var(--light);
        }

        .section-header {
            text-align: center;
            margin-bottom: 60px;
        }

        .section-header h2 {
            font-size: 42px;
            color: var(--secondary);
            margin-bottom: 15px;
        }

        .section-header p {
            color: var(--gray);
            font-size: 16px;
            max-width: 600px;
            margin: 0 auto;
        }

        .services-grid {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
        }

        .service-card {
            background: white;
            padding: 35px;
            border-radius: 16px;
            box-shadow: var(--shadow);
            transition: all 0.3s;
            text-align: center;
        }

        .service-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-lg);
        }

        .service-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin: 0 auto 20px;
        }

        .service-card h3 {
            font-size: 20px;
            margin-bottom: 12px;
            color: var(--secondary);
        }

        .service-card p {
            color: var(--gray);
            font-size: 14px;
            line-height: 1.6;
        }

        /* ========== FEATURES SECTION ========== */
        .features {
            padding: 100px 30px;
            background: white;
        }

        .features-grid {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 40px;
        }

        .feature-item {
            display: flex;
            gap: 20px;
            align-items: flex-start;
        }

        .feature-number {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 18px;
            flex-shrink: 0;
        }

        .feature-text h4 {
            font-size: 18px;
            margin-bottom: 8px;
            color: var(--secondary);
        }

        .feature-text p {
            color: var(--gray);
            font-size: 14px;
            line-height: 1.6;
        }

        /* ========== CTA SECTION ========== */
        .cta {
            padding: 80px 30px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            text-align: center;
        }

        .cta h2 {
            font-size: 36px;
            color: white;
            margin-bottom: 15px;
        }

        .cta p {
            color: rgba(255,255,255,0.9);
            font-size: 16px;
            margin-bottom: 30px;
        }

        /* ========== FOOTER ========== */
        .footer {
            background: var(--secondary);
            color: white;
            padding: 60px 30px 30px;
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

        /* ========== MOBILE RESPONSIVE ========== */
        @media (max-width: 768px) {
            .hero-content {
                grid-template-columns: 1fr;
                gap: 40px;
            }

            .hero-text h1 {
                font-size: 36px;
            }

            .hamburger {
                display: flex;
            }

            .nav-links {
                position: fixed;
                top: 80px;
                left: -100%;
                width: 100%;
                background: white;
                flex-direction: column;
                padding: 30px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.1);
                transition: left 0.3s ease;
                z-index: 999;
                gap: 20px;
            }

            .nav-links.active {
                left: 0;
            }

            .nav-btn {
                width: 100%;
                text-align: center;
                display: block;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }

            .section-header h2 {
                font-size: 32px;
            }

            .hero {
                padding-top: 120px;
            }

            .track-card {
                padding: 30px;
            }
        }

        /* ========== SUCCESS MESSAGE ========== */
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin: 20px auto;
            max-width: 600px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
    </style>
</head>
<body>

    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="home.php" class="logo">
                <div class="logo-icon">C</div>
                Calma
            </a>
            <div class="hamburger" id="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>
            <div class="nav-links" id="navLinks">
                <a href="#services">Services</a>
                <a href="#features">Why Us</a>
                <a href="#contact">Contact</a>
                <a href="track_shipment.php">Track Package</a>
                <?php if ($is_logged_in): ?>
                    <?php if ($user_type === 'user'): ?>
                        <a href="user_dashboard.php" class="nav-btn nav-btn-primary">Dashboard</a>
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

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <div class="hero-text">
                <h1>Fast & Reliable Courier Services</h1>
                <p>Experience seamless delivery with Calma Courier. From documents to packages, we deliver with care and speed across Pakistan.</p>
                <div class="hero-buttons">
                    <?php if ($is_logged_in && $user_type === 'user'): ?>
                        <a href="book_shipment.php" class="btn btn-white">📦 Book a Shipment</a>
                    <?php else: ?>
                        <a href="register.php" class="btn btn-white">📦 Book a Shipment</a>
                    <?php endif; ?>
                    <a href="track_shipment.php" class="btn btn-outline-white">🔍 Track Package</a>
                </div>
            </div>

            <!-- Track Package Card -->
            <div class="track-card" id="track">
                <h3>🔍 Track Your Package</h3>
                <p>Enter your tracking number to see real-time status</p>

                <?php if (isset($_GET['tracking'])): ?>
                    <div class="alert alert-success">✓ Tracking number: <?php echo htmlspecialchars($_GET['tracking']); ?></div>
                <?php endif; ?>

                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-error">⚠ <?php echo htmlspecialchars($_GET['error']); ?></div>
                <?php endif; ?>

                <form class="track-form" action="track_shipment.php" method="GET">
                    <input type="text" name="tracking_number" class="track-input" placeholder="Enter tracking number (e.g., CLM-2026-0001)" required>
                    <button type="submit" class="btn btn-white" style="width: 100%;">
                        <i class="fas fa-search"></i> Track Now
                    </button>
                </form>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section class="services" id="services">
        <div class="section-header">
            <h2>Our Services</h2>
            <p>We offer a range of delivery services to meet your needs</p>
        </div>
        <div class="services-grid">
            <div class="service-card">
                <div class="service-icon">📬</div>
                <h3>Standard Delivery</h3>
                <p>Affordable and reliable delivery within 3-5 business days. Perfect for non-urgent shipments.</p>
            </div>
            <div class="service-card">
                <div class="service-icon">🚀</div>
                <h3>Express Delivery</h3>
                <p>Fast delivery within 1-2 business days. When you need it there quickly.</p>
            </div>
            <div class="service-card">
                <div class="service-icon">⚡</div>
                <h3>Overnight Delivery</h3>
                <p>Next day delivery by 12 PM. Our fastest service for urgent shipments.</p>
            </div>
            <div class="service-card">
                <div class="service-icon">🏢</div>
                <h3>Corporate Solutions</h3>
                <p>Customized logistics solutions for businesses with regular shipping needs.</p>
            </div>
            <div class="service-card">
                <div class="service-icon">📦</div>
                <h3>Packaging Services</h3>
                <p>Professional packaging to ensure your items arrive safely and securely.</p>
            </div>
            <div class="service-card">
                <div class="service-icon">💰</div>
                <h3>Cash on Delivery</h3>
                <p>Collect payment from recipients with our secure COD service.</p>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="section-header">
            <h2>Why Choose Calma?</h2>
            <p>We make shipping simple, fast, and reliable</p>
        </div>
        <div class="features-grid">
            <div class="feature-item">
                <div class="feature-number">1</div>
                <div class="feature-text">
                    <h4>Real-Time Tracking</h4>
                    <p>Track your shipment every step of the way with our advanced tracking system.</p>
                </div>
            </div>
            <div class="feature-item">
                <div class="feature-number">2</div>
                <div class="feature-text">
                    <h4>Nationwide Network</h4>
                    <p>Coverage across all major cities in Pakistan with local expertise.</p>
                </div>
            </div>
            <div class="feature-item">
                <div class="feature-number">3</div>
                <div class="feature-text">
                    <h4>Secure Handling</h4>
                    <p>Your packages are handled with care and insured for peace of mind.</p>
                </div>
            </div>
            <div class="feature-item">
                <div class="feature-number">4</div>
                <div class="feature-text">
                    <h4>24/7 Support</h4>
                    <p>Our customer support team is always ready to assist you.</p>
                </div>
            </div>
            <div class="feature-item">
                <div class="feature-number">5</div>
                <div class="feature-text">
                    <h4>Competitive Pricing</h4>
                    <p>Best rates in the market with no hidden charges.</p>
                </div>
            </div>
            <div class="feature-item">
                <div class="feature-number">6</div>
                <div class="feature-text">
                    <h4>On-Time Delivery</h4>
                    <p>99% on-time delivery rate you can count on.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
        <h2>Ready to Ship?</h2>
        <p>Join thousands of satisfied customers across Pakistan</p>
        <a href="book_shipment.php" class="btn btn-white" style="background: white; color: var(--primary);">Create Shipment</a>
    </section>

    <!-- Footer -->
    <footer class="footer" id="contact">
        <div class="footer-content">
            <div class="footer-section">
                <h4>📍 Calma Courier</h4>
                <p>Your trusted partner for fast and reliable delivery services across Pakistan.</p>
            </div>
            <div class="footer-section">
                <h4>Quick Links</h4>
                <a href="home.php">Home</a>
                <a href="#services">Services</a>
                <a href="track_shipment.php">Track Package</a>
                <a href="login.php">Login</a>
            </div>
            <div class="footer-section">
                <h4>Services</h4>
                <a href="#services">Standard Delivery</a>
                <a href="#services">Express Delivery</a>
                <a href="#services">Overnight Delivery</a>
                <a href="#services">Corporate Solutions</a>
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

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.style.boxShadow = '0 4px 30px rgba(0,0,0,0.1)';
            } else {
                navbar.style.boxShadow = '0 2px 20px rgba(0,0,0,0.08)';
            }
        });
    </script>
</body>
</html>
