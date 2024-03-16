<?php
session_start();

// Check if the user is already logged in using the loggedIn cookie
if (!isset($_COOKIE['loggedIn'])) {
    header('Location: login.php');
    exit;
}

// Check if the user is a student or an admin
if ($_COOKIE['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

// Set the active page to 'student_qr'
$active_page = 'student_qr';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>St. Joseph's Academy - QR Code</title>
    <link rel="stylesheet" href="./css/student_qr_css.css">
    <link rel="stylesheet" href="./css/static_style.css">
    <link rel="icon" type="image/x-icon" href="./img/sja-logo.png">
</head>

<body>
    <?php include './static/sidebar.php'; ?>
    <div class="content">
        <?php include './static/header.php'; ?>
        <div class="main">
            <h1>Student QR Code</h1>
            <p>Scan this QR Code using the system to record your attendance. Each QR Code is valid for 5 seconds only. If the QR Code is expired, please generate a new one. Recording your attendance using the QR Code helps us keep track of your attendance accurately and efficiently. Thank you for your cooperation!</p>
            <img class="qr-code-img" id="qr-code-img" src="generate_qr.php" alt="QR Code">
            <div class="center">
              <button onclick="generateNewQR()">Generate New QR Code</button>
            </div>
        </div>
    </div>

    <?php include './static/footer.php'; ?>

    <script>
        function generateNewQR() {
            // Update the src attribute of the QR code image to trigger a new QR code generation
            var img = document.getElementById('qr-code-img');
            img.src = 'generate_qr.php?' + new Date().getTime(); // Append timestamp to the URL to avoid caching
        }
    </script>
</body>
</html>
