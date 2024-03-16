<?php
session_start();

// Set the active page to 'attendance_system'
$active_page = 'attendance_system';

// Check if there is an error message
$error_message = isset($_GET['error']) ? urldecode($_GET['error']) : null;

// Check if there is a success message
$success_message = isset($_GET['success']) ? urldecode($_GET['success']) : null;

// Check if student name and ID are set
$student_name = isset($_GET['student_name']) ? urldecode($_GET['student_name']) : null;
$student_id = isset($_GET['student_id']) ? urldecode($_GET['student_id']) : null;
$student_image = isset($_GET['student_image']) ? urldecode($_GET['student_image']) : null;
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>St. Joseph's Academy - QR Code Reader</title>
  <link rel="stylesheet" href="./css/dashboard_style.css">
  <link rel="stylesheet" href="./css/static_style.css">
  <link rel="stylesheet" href="./css/attendance_system_style.css"> <!-- Add the new CSS file -->
  <link rel="icon" type="image/x-icon" href="./img/sja-logo.png">
  <script type="text/javascript" src="https://rawgit.com/schmich/instascan-builds/master/instascan.min.js"></script>
</head>

<body>
  
  <div class="content">
    <?php include './static/header.php'; ?>
    <div class="main">
    <div class="loading-overlay">Please wait...</div>

      <h1>Attendance System</h1>
      <p class="instructions">Please scan your QR code to record your attendance. The QR code is only valid for 5
        seconds. If the QR code has expired, please refresh it by selecting 'Generate New QR Code' on your device. Your
        prompt attendance recording helps us ensure accurate attendance tracking. Thank you for your cooperation!</p>

      <?php if ($student_name && $student_id) : ?>
      <div class="student-info">
        <div class="student-image">
          <img src="<?php echo $student_image; ?>" alt="Student Image">
        </div>
        <div class="student-details">
          <div class="info-label">Student Name: <span class="info-value"><?php echo $student_name; ?></span></div>

          <div class="info-label">Student ID: <span class="info-value"><?php echo $student_id; ?></span></div>

        </div>
      </div>
      <?php endif; ?>

      <?php if ($success_message) : ?>
      <div class="message success-message"><?php echo $success_message; ?></div>
      <?php endif; ?>

      <?php if ($error_message) : ?>
      <div class="message error-message"><?php echo $error_message; ?></div>
      <?php endif; ?>

      <form action="insert.php" method="post" class="form">
        <input type="text" name="student_id" id="student_id" class="student_id" placeholder="" class="form-control"
          autofocus>
      </form>
    </div>
  </div>


  <?php include './static/footer.php'; ?>

  <script>
  function ensureInputFocus() {
    var inputField = document.getElementById("student_id");
    if (!inputField) {
      // Recreate the input field if it's not present
      var form = document.querySelector(".form");
      inputField = document.createElement("input");
      inputField.type = "text";
      inputField.name = "student_id";
      inputField.id = "student_id";
      inputField.className = "student_id form-control";
      inputField.placeholder = "";
      inputField.autofocus = true;
      form.appendChild(inputField);
    }
    // Set focus to the input field
    inputField.focus();
  }

  function focusInputPeriodically() {
    setInterval(function() {
      ensureInputFocus();
    }, 5000); // Focus every 10 seconds (10000 milliseconds)
  }

  function showLoadingOverlay() {
    var overlay = document.querySelector('.loading-overlay');
    overlay.style.display = 'flex';
    disableForm();
  }

  function hideLoadingOverlay() {
    var overlay = document.querySelector('.loading-overlay');
    overlay.style.display = 'none';
    enableForm();
  }

  function disableForm() {
    var form = document.querySelector('.form');
    var inputField = document.getElementById('student_id');
    inputField.disabled = true;
  }

  function enableForm() {
    var form = document.querySelector('.form');
    var inputField = document.getElementById('student_id');
    inputField.disabled = false;
  }

  document.querySelector(".form").addEventListener("submit", function(event) {
    // Show loading overlay when the form is submitted
    // showLoadingOverlay();

    var qrCodeData = document.getElementById("student_id").value;
    console.log(qrCodeData)
    if (!qrCodeData) {
      alert("Please scan a QR code.");
      event.preventDefault(); // Prevent form submission
    }

    setTimeout(function() {
      showLoadingOverlay();
    }, 500);
  });

  document.addEventListener('DOMContentLoaded', function() {
    ensureInputFocus(); // Focus initially
    focusInputPeriodically(); // Focus every 10 seconds
  });
</script>



</body>

</html>