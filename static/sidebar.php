<div class="sidebar">
  <div class="logo">
    <img src="img/sja-logo.png" alt="St. Joseph's Academy Logo">
  </div>
  <a href="dashboard.php" class="<?php echo ($active_page === 'dashboard') ? 'active' : ''; ?>">Dashboard</a>

  <?php
    if ($_COOKIE['role'] == 'student' || $_COOKIE['role'] == 'admin') {
      echo '<a href="student_qr.php" class="' . ($active_page === 'student_qr' ? 'active' : '') . '">QR Code</a>';
    }

    if ($_COOKIE['role'] == 'admin') {
      echo '<a href="students.php" class="' . ($active_page === 'students' ? 'active' : '') . '">Students</a>';
      echo '<a href="attendance_system.php" target="_blank" class="' . ($active_page === 'attendance_system' ? 'active' : '') . '">Attendance System</a>';
    }
  ?>

</div>