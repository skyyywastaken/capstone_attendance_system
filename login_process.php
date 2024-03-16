<?php
// Include the database connection file
include 'db_connect.php';

session_start();

// Get username and password from POST request
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

// Sanitize inputs to prevent SQL injection
$username = $db_conn->real_escape_string($username);
$password = $db_conn->real_escape_string($password);

// Prepare the SQL statement
$stmt = $db_conn->prepare("SELECT * FROM users WHERE username=?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

// Verify the password
if ($result->num_rows == 1) {
    $user = $result->fetch_assoc();
    if (password_verify($password, $user['password'])) {
        $stmt = $db_conn->prepare("SELECT * FROM active_sessions WHERE username=?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $sessionResult = $stmt->get_result();

        if ($sessionResult->num_rows > 0) {
            // User is already logged in from another device
            $_SESSION['error'] = 'Error!';
            header('Location: login.php');
            exit;
        }

        // Insert active session into the database
        $insertStmt = $db_conn->prepare("INSERT INTO active_sessions (username) VALUES (?)");
        $insertStmt->bind_param("s", $username);
        $insertStmt->execute();

        // Authentication successful
        $stmt = $db_conn->prepare("SELECT student_id FROM students WHERE username=?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $studentResult = $stmt->get_result();
        $student = $studentResult->fetch_assoc();

        // Set a cookie to remember that the user is logged in
        setcookie('loggedIn', 'true', time() + (86400 * 30), '/'); // Cookie valid for 30 days

        // Set a cookie to remember the username
        setcookie('username', $username, time() + (86400 * 30), '/'); // Cookie valid for 30 days

        // Set a cookie to remember the role
        setcookie('role', $user['role'], time() + (86400 * 30), '/'); // Cookie valid for 30 days

        // Set a cookie to remember the student id if the user is a student
        if ($user['role'] == 'student' || $user['role'] == 'admin') {
            setcookie('studentId', $student['student_id'], time() + (86400 * 30), '/'); // Cookie valid for 30 days
        }

        header('Location: dashboard.php');
        exit;
    }
}

// Authentication failed
$_SESSION['error'] = 'Invalid username or password';
header('Location: login.php');
exit;

// Close prepared statement
$stmt->close();

// Close database connection
$db_conn->close();
?>