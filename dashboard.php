<?php
session_start();

// Check if the user is already logged in using the loggedIn cookie
if (!isset($_COOKIE['loggedIn'])) {
    header('Location: login.php');
    exit;
}

// Set the active page to 'dashboard'
$active_page = 'dashboard';

// Include the database connection file
include 'db_connect.php';

// Get the user's name and role from the database
$username = $_COOKIE['username'];
$stmt = $db_conn->prepare("SELECT name, role FROM users WHERE username=?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$name = $user['name'];
$role = $_COOKIE['role'];
$stmt->close();

// Get the children's names and student IDs based on the user's role
$children_data = [];
if ($role === 'parent' || $role === 'guardian') {
    // If user is a parent or guardian, get the names and student IDs of their children
    $stmt = $db_conn->prepare("SELECT name, student_id, in_school FROM students WHERE parent_guardian_username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $children_data[] = $row;
    }
    $stmt->close();
} elseif ($role === 'student') {
    // If user is a student, get their attendance status
    $stmt = $db_conn->prepare("SELECT in_school FROM students WHERE username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $attendance_status = $result->fetch_assoc()['in_school'] ? 'In School' : 'Not In School';
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>St. Joseph's Academy - Dashboard</title>
    <link rel="stylesheet" href="./css/dashboard_style.css">
    <link rel="stylesheet" href="./css/static_style.css">
    <link rel="icon" type="image/x-icon" href="./img/sja-logo.png">
</head>

<body>
    <?php include './static/sidebar.php'; ?>

    <div class="content">
        <?php include './static/header.php'; ?>
        <div class="main">
            <h2>Welcome, <?php echo $name; ?>!</h2>
            <?php if ($role === 'parent' || $role === 'guardian') : ?>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Student ID</th>
                            <th>In School</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($children_data as $child) : ?>
                            <tr>
                                <td><?php echo $child['name']; ?></td>
                                <td><?php echo $child['student_id']; ?></td>
                                <td><?php echo $child['in_school'] ? 'Yes' : 'No'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php elseif ($role === 'student') : ?>
                <p>Attendance Status: <?php echo $attendance_status; ?></p>
            <?php endif; ?>
        </div>
    </div>

    <?php include './static/footer.php'; ?>
</body>

</html>