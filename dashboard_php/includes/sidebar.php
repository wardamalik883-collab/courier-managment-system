<?php
// =============================================
// SIDEBAR INCLUDE
// Har page mein: <?php include 'includes/sidebar.php'; ?>
// $active_page variable set karo page mein:
// $active_page = 'bills'; // ya 'shipments', 'customers', 'agents', 'reports'
//
// Logged in user ka naam dikhane ke liye:
// $_SESSION['user']['name'] use karo
// =============================================
$active = $active_page ?? '';
?>
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <div class="s-logo-mark">C</div>
    <div><span class="s-logo-text">Calma</span><span class="s-logo-sub">CMS Admin</span></div>
  </div>
  <div class="sidebar-user">
    <div class="s-avatar">
      <?php
      // echo strtoupper(substr($_SESSION['user']['name'], 0, 1));
      echo 'A';
      ?>
    </div>
    <div>
      <div class="s-user-name">
        <?php
        // echo $_SESSION['user']['name'];
        echo 'Admin';
        ?>
      </div>
      <div class="s-user-role">
        <?php
        // echo strtoupper($_SESSION['user']['role']);
        echo 'ADMIN';
        ?>
      </div>
    </div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section">Main</div>
    <a href="index.php" class="nav-link <?= $active === 'dashboard' ? 'active' : '' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
      Dashboard
    </a>

    <div class="nav-section">Billing</div>
    <a class="nav-link <?= $active === 'bills' ? 'active sub-open' : '' ?>" data-sub="sub-bills" href="#">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
      Bills
      <svg class="nav-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
    </a>
    <div class="nav-sub <?= $active === 'bills' ? 'open' : '' ?>" id="sub-bills">
      <a href="bills.php" class="nav-sub-link <?= $active === 'bills' ? 'active' : '' ?>">All Bills</a>
      <a href="bills.php?action=add" class="nav-sub-link">Add New Bill</a>
    </div>

    <div class="nav-section">Shipments</div>
    <a class="nav-link <?= $active === 'shipments' ? 'active sub-open' : '' ?>" data-sub="sub-ship" href="#">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
      Shipments
      <svg class="nav-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
    </a>
    <div class="nav-sub <?= $active === 'shipments' ? 'open' : '' ?>" id="sub-ship">
      <a href="shipments.php" class="nav-sub-link <?= $active === 'shipments' ? 'active' : '' ?>">All Shipments</a>
      <a href="shipments.php?action=add" class="nav-sub-link">Add Shipment</a>
    </div>

    <div class="nav-section">Management</div>
    <a class="nav-link <?= $active === 'agents' ? 'active sub-open' : '' ?>" data-sub="sub-agents" href="#">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      Agents
      <svg class="nav-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
    </a>
    <div class="nav-sub <?= $active === 'agents' ? 'open' : '' ?>" id="sub-agents">
      <a href="agents.php" class="nav-sub-link <?= $active === 'agents' ? 'active' : '' ?>">All Agents</a>
      <a href="agents.php?action=add" class="nav-sub-link">Add Agent</a>
    </div>

    <a class="nav-link <?= $active === 'customers' ? 'active sub-open' : '' ?>" data-sub="sub-cust" href="#">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      Customers
      <svg class="nav-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
    </a>
    <div class="nav-sub <?= $active === 'customers' ? 'open' : '' ?>" id="sub-cust">
      <a href="customers.php" class="nav-sub-link <?= $active === 'customers' ? 'active' : '' ?>">All Customers</a>
      <a href="customers.php?action=add" class="nav-sub-link">Add Customer</a>
    </div>

    <a href="reports.php" class="nav-link <?= $active === 'reports' ? 'active' : '' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
      Reports
    </a>
  </nav>

  <div class="sidebar-foot">
    <a href="logout.php" class="logout-link">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      Logout
    </a>
  </div>
</aside>
