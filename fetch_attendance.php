<?php
// Include the database connection file
include 'db_connect.php';

// Check if the student ID is provided in the request
if (isset($_GET['student_id'])) {
    $studentId = $_GET['student_id'];

    // Fetch attendance data for the selected student from the database
    $stmt = $db_conn->prepare("SELECT date_time FROM attendance WHERE student_id = ?");
    $stmt->bind_param("s", $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $attendanceData = $result->fetch_all(MYSQLI_ASSOC);

    // Return attendance data as JSON response
    echo json_encode($attendanceData);
} else {
    // Return error response if student ID is not provided
    echo json_encode(['error' => 'Student ID not provided']);
}
?>
