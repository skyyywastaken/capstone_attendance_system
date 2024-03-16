<div class="header">
  <div class="toggle-btn" onclick="toggleSidebar()">☰</div>
  <h2><a class="sja" href="dashboard.php">St. Joseph's Academy</a></h2>
  <?php
    if ($active_page !== 'attendance_system') {
      echo '<a class="logout" href="logout.php">Logout</a>';
    }
  ?>
</div>

<script>
  function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    sidebar.classList.toggle('show');
  }

  // Close the sidebar when clicking outside of it
  document.addEventListener('click', function(event) {
    const sidebar = document.querySelector('.sidebar');
    const toggleBtn = document.querySelector('.toggle-btn');
    if (!sidebar.contains(event.target) && !toggleBtn.contains(event.target)) {
      sidebar.classList.remove('show');
    }
  });
</script>