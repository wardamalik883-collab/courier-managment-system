/* ============================================
   CALMA CMS - UI Only (No data logic)
   Sidebar, hamburger, modals, responsive
   ============================================ */

function openModal(id)  { document.getElementById(id).classList.add('show'); }
function closeModal(id) { document.getElementById(id).classList.remove('show'); }

document.addEventListener('DOMContentLoaded', function () {

  // Sidebar sub-menus
  document.querySelectorAll('.nav-link[data-sub]').forEach(function (link) {
    link.addEventListener('click', function (e) {
      e.preventDefault();
      var sub  = document.getElementById(this.dataset.sub);
      var open = sub.classList.contains('open');
      document.querySelectorAll('.nav-sub').forEach(function (s) { s.classList.remove('open'); });
      document.querySelectorAll('.nav-link').forEach(function (l) { l.classList.remove('sub-open'); });
      if (!open) { sub.classList.add('open'); this.classList.add('sub-open'); }
    });
  });

  // Hamburger (mobile)
  var ham = document.getElementById('hamburger');
  var sb  = document.getElementById('sidebar');
  var overlay = document.getElementById('sidebarOverlay');

  if (ham && sb) {
    function openSidebar() {
      sb.classList.add('open');
      ham.classList.add('active');
      if (overlay) overlay.classList.add('active');
      document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
      sb.classList.remove('open');
      ham.classList.remove('active');
      if (overlay) overlay.classList.remove('active');
      document.body.style.overflow = '';
    }

    function toggleSidebar() {
      if (sb.classList.contains('open')) {
        closeSidebar();
      } else {
        openSidebar();
      }
    }

    ham.addEventListener('click', toggleSidebar);

    // Close sidebar when clicking overlay
    if (overlay) {
      overlay.addEventListener('click', closeSidebar);
    }

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function (e) {
      if (window.innerWidth <= 768 && sb.classList.contains('open')) {
        if (!sb.contains(e.target) && !ham.contains(e.target)) {
          closeSidebar();
        }
      }
    });

    // Close sidebar when clicking a nav link on mobile
    sb.querySelectorAll('a, .nav-sub-link').forEach(function (link) {
      link.addEventListener('click', function () {
        if (window.innerWidth <= 768) {
          closeSidebar();
        }
      });
    });

    // Close sidebar on window resize if open
    var resizeTimer;
    window.addEventListener('resize', function () {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(function () {
        if (window.innerWidth > 768) {
          closeSidebar();
        }
      }, 250);
    });

    // Handle Escape key to close sidebar
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && sb.classList.contains('open')) {
        closeSidebar();
      }
    });
  }

  // Modal overlay click to close
  document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
    overlay.addEventListener('click', function (e) {
      if (e.target === overlay) overlay.classList.remove('show');
    });
  });

});
