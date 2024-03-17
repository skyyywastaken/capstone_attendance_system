<?php
// Include the database connection file
include 'db_connect.php'; // Adjust the path if necessary

// Check if student_id is provided
if (isset($_GET['student_id'])) {
    $student_id = $_GET['student_id'];
    
    // Prepare and execute SQL query to fetch section_id from students table
    // Fetch attendance data for the selected student from the database
    $stmt = $db_conn->prepare("SELECT * FROM students WHERE student_id = ?");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch student information
    $student_info = $result->fetch_assoc();
    $section_id = $student_info['section_id'];

    if ($section_id) {
        
        // Prepare and execute the SQL query to fetch section schedules based on section_id
        $query = "SELECT * FROM section_schedules WHERE section_id = ?";
        $statement = $db_conn->prepare($query);
        $statement->bind_param('i', $section_id);
        $statement->execute();
        $result = $statement->get_result();

        // Fetch all rows as an associative array
        $sectionSchedules = $result->fetch_all(MYSQLI_ASSOC);
        
        // Check if any section schedules were found
        if ($sectionSchedules) {
            // Output JSON indicating section schedules found
            header('Content-Type: application/json'); // Set response header to indicate JSON content
            echo json_encode($sectionSchedules); // Output JSON data
        } else {
            // Output JSON indicating no section schedules found
            echo json_encode(array("message" => "No section schedules found for the provided section ID."));
        }
    } else {
        // Output JSON indicating no section found for the provided student ID
        echo json_encode(array("message" => "No section found for the provided student ID."));
    }
} else {
    // Output JSON indicating no student ID provided
    echo json_encode(array("message" => "Student ID not provided."));
}
?>
