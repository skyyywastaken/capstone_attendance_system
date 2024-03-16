<?php
session_start();

// Check if the user is already logged in using the loggedIn cookie
if (isset($_COOKIE['loggedIn'])) {
    header('Location: dashboard.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>St. Joseph's Academy - Login</title>
    <link rel="stylesheet" href="css/login_style.css">
    <link rel="icon" type="image/x-icon" href="./img/sja-logo.png">
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <img src="img/sja-logo.png" alt="St. Joseph's Academy Logo">
        </div>
        <h2 class="header-title">St. Joseph's Academy</h2>
        
        <?php if (isset($_SESSION['error'])) : ?>
            <div class="error-message"><?php echo $_SESSION['error']; ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <form action="login_process.php" method="POST">
            <div class="input-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="input-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Login</button>
            <p class="footer">© 2024 Josef Lopez. SJA.</p>
        </form>
    </div>
</body>
</html>