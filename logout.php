<?php
session_start();

// Include the database connection file
include 'db_connect.php';

// Remove active session from the database
$stmt = $db_conn->prepare("DELETE FROM active_sessions WHERE username = ?");
$stmt->bind_param("s", $_COOKIE['username']);
$stmt->execute();
$stmt->close();

// Clear the 'loggedIn' cookie
setcookie('loggedIn', '', time() - 3600, '/'); // Set expiration to the past

// Clear the 'username' cookie
setcookie('username', '', time() - 3600, '/'); // Set expiration to the past

// Clear the 'role' cookie
setcookie('role', '', time() - 3600, '/'); // Set expiration to the past

// Clear the 'studentId' cookie
setcookie('studentId', '', time() - 3600, '/'); // Set expiration to the past

// Destroy the session
session_destroy();

// Redirect to the login page
header('Location: login.php');
exit;
?>