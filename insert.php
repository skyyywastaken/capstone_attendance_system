<?php
// Include the database connection file
include "db_connect.php";

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the QR data from the form
    $qr_data = $_POST["student_id"];

    // Separate the student ID, unique ID, and expiration time
    $parts = explode("_", $qr_data);

    // Check if the array keys exist before accessing them
    $student_id = isset($parts[0]) ? $parts[0] : null;
    $expiration_time = isset($parts[1]) ? $parts[1] : null;

    // Check if the student ID is registered
    if ($student_id) {
        $stmt = $db_conn->prepare("SELECT * FROM students WHERE student_id = ?");
        $stmt->bind_param("s", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();

        // Fetch student information
        $student_info = $result->fetch_assoc();

        // Check if the QR code is expired
        $now = time();

        if ($now <= $expiration_time) {
            if ($result->num_rows > 0) {
                // Date and Time
                $current_day_of_week = date("l");
                $now = strtotime(date("H:i:s"));

                // Student Information
                $section_id = $student_info["section_id"];
                $student_name = $student_info["name"];
                $student_image = $student_info["image"];
                $parent_guardian_username = $student_info["parent_guardian_username"];

                // Fetch the email of the parent/guardian
                $email_stmt = $db_conn->prepare("SELECT email FROM users WHERE username = ?");
                $email_stmt->bind_param("s", $parent_guardian_username);
                $email_stmt->execute();
                $email_result = $email_stmt->get_result();
                $email_user = $email_result->fetch_assoc();

                // Check if the student is in school
                if ($student_info["in_school"] == 1) {
                    // Fetch section schedule for the current day
                    $end_time_stmt = $db_conn->prepare("SELECT end_time FROM section_schedules WHERE section_id = ? AND day_of_week = ?");
                    $end_time_stmt->bind_param("ss", $section_id, $current_day_of_week);
                    $end_time_stmt->execute();
                    $end_time_result = $end_time_stmt->get_result();

                    if ($end_time_result->num_rows > 0) {
                        $end_time_row = $end_time_result->fetch_assoc();
                        $scheduled_end_time = $end_time_row["end_time"];

                        // Compare the current time with the scheduled end time
                        $scheduled_end_time = strtotime($scheduled_end_time);

                        if ($now >= $scheduled_end_time) {
                            // Update the attendance record for leaving time
                            $update_leave_stmt = $db_conn->prepare("UPDATE attendance SET leave_time = NOW() WHERE student_id = ? AND date(date_time) = CURDATE()");
                            $update_leave_stmt->bind_param("s", $student_id);

                            if ($update_leave_stmt->execute()) {
                                // Update the student's in_school status to 0 (out of school)
                                $update_leave_status_stmt = $db_conn->prepare("UPDATE students SET in_school = 0 WHERE student_id = ?");
                                $update_leave_status_stmt->bind_param("s", $student_id);
                                $update_leave_status_stmt->execute();

                                // Send an email to the student's parent
                                require "vendor/autoload.php"; // Include the PHPMailer autoloader

                                // Instantiate PHPMailer
                                $mail = new PHPMailer(true);

                                try {
                                    // Server settings
                                    $mail->isSMTP(); // Send using SMTP
                                    $mail->Host = "smtp.gmail.com"; // Set the SMTP server to send through
                                    $mail->SMTPAuth = true; // Enable SMTP authentication
                                    $mail->Username = "sjaattendancesystem@gmail.com"; // SMTP username
                                    $mail->Password = "cmxkvzmswwmcgioq"; // SMTP password
                                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
                                    $mail->Port = 587; // TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above

                                    // Recipients
                                    $mail->setFrom("sjaattendancesystem@gmail.com", "SJA Attendance System");
                                    $mail->addAddress($email_user["email"]); // Add a recipient

                                    // Content
                                    $mail->isHTML(true); // Set email format to HTML
                                    $mail->Subject = "Attendance Notification";

                                    // Construct the HTML body with additional information and styling
                                    $mail->Body =
                                    '
                                    <html>
                                    <head>
                                        <style>
                                            body {
                                                font-family: Arial, sans-serif;
                                                background-color: #f4f4f4;
                                                padding: 20px;
                                            }
    
                                            .container {
                                                max-width: 600px;
                                                margin: 0 auto;
                                                padding: 20px;
                                                background-color: #fff;
                                                border-radius: 5px;
                                                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
                                            }
                                            
                                            h1 {
                                                color: #333;
                                            }
    
                                            p {
                                                margin-bottom: 20px;
                                            }
    
                                            .info {
                                                background-color: #f9f9f9;
                                                padding: 10px;
                                                border-radius: 5px;
                                            }
                                        </style>
                                    </head>
                                    <body>
                                        <div class="container">
                                            <h1>Attendance Notification</h1>
    
                                            <p>Your child, <strong>' .
                                        $student_info["name"] .
                                        "</strong>, left the campus at <strong>" .
                                        date("Y-m-d H:i:s") .
                                        " " .
                                        $attendance_status .
                                        '</strong>.</p>
                                            
                                            <div class="info">
                                                <p><strong>Student ID:</strong> ' .
                                        $student_id .
                                        '</p>
                                                <p><strong>Parent/Guardian Email:</strong> ' .
                                        $email_user["email"] .
                                        '</p>
                                            </div>
    
                                            <p>Thank you for using our attendance system.</p>
                                            <br>
                                            <p>&copy; 2024 Josef Lopez. SJA.</p>
                                        </div>
                                    </body>
                                    </html>
                                    ';

                                    $mail->AltBody =
                                        "Your child, " .
                                        $student_info["name"] .
                                        ", left the campus at " .
                                        date("Y-m-d H:i:s") .
                                        ".";

                                    $mail->send();

                                    // Redirect back to attendance_system.php with success message
                                    header(
                                        "Location: attendance_system.php?success=Attendance%20recorded%20successfully.%20Stay%20safe!&student_name=$student_name&student_id=$student_id&student_image=$student_image"
                                    );
                                    exit(); // Ensure script stops executing after redirection
                                } catch (Exception $e) {
                                    // Redirect back to attendance_system.php with error message
                                    header(
                                        "Location: attendance_system.php?error=Message%20could%20not%20be%20sent.%20Mailer%20Error:%20" .
                                            $mail->ErrorInfo
                                    );
                                    exit(); // Ensure script stops executing after redirection
                                }
                            } else {
                                // Redirect back to attendance_system.php with error message
                                header(
                                    "Location: attendance_system.php?error=Error%20recording%20attendance:%20" .
                                        $update_leave_stmt->error
                                );
                                exit(); // Ensure script stops executing after redirection
                            }
                        } else {
                            header(
                                "Location: attendance_system.php?error=You%20are%20expected%20to%20remain%20on%20the%20school%20premises%20until%20the%20designated%20dismissal%20time." .
                                    $now .
                                    "%20" .
                                    $scheduled_end_time
                            );
                            exit(); // Ensure script stops executing after redirection
                        }
                    } else {
                        header(
                            "Location: attendance_system.php?error=Not2%20recorded%20successfully."
                        );
                        exit(); // Ensure script stops executing after redirection
                    }
                } else {
                    // Fetch section schedule for the current day
                    $schedule_stmt = $db_conn->prepare(
                        "SELECT start_time FROM section_schedules WHERE section_id = ? AND day_of_week = ?"
                    );
                    $schedule_stmt->bind_param(
                        "ss",
                        $student_info["section_id"],
                        $current_day_of_week
                    );
                    $schedule_stmt->execute();
                    $schedule_result = $schedule_stmt->get_result();

                    if ($schedule_result->num_rows > 0) {
                        $schedule_row = $schedule_result->fetch_assoc();
                        $scheduled_start_time = $schedule_row["start_time"];

                        // Compare the current time with the scheduled start time plus a lateness threshold
                        $scheduled_start_time = strtotime(
                            $scheduled_start_time
                        );
                        $lateness_threshold = 2 * 60; // 2 minutes in seconds

                        if (
                            $now >
                            $scheduled_start_time + $lateness_threshold
                        ) {
                            // Student is late
                            $attendance_status = "Late";
                        } else {
                            // Student is on time
                            $attendance_status = "On Time";
                        }

                        // Check if the student already has an attendance record for today
                        $check_attendance_stmt = $db_conn->prepare(
                            "SELECT * FROM attendance WHERE student_id = ? AND DATE(date_time) = CURDATE()"
                        );
                        $check_attendance_stmt->bind_param("s", $student_id);
                        $check_attendance_stmt->execute();
                        $existing_attendance_result = $check_attendance_stmt->get_result();
                        if ($existing_attendance_result->num_rows == 0) {
                            // Student ID is registered, insert the attendance record into the database
                            $insert_stmt = $db_conn->prepare(
                                "INSERT INTO attendance (student_id, date_time, status) VALUES (?, NOW(), ?)"
                            );
                            $insert_stmt->bind_param(
                                "ss",
                                $student_id,
                                $attendance_status
                            );

                            // Update students table to set in_school to 1
                            $update_stmt = $db_conn->prepare(
                                "UPDATE students SET in_school = 1 WHERE student_id = ?"
                            );
                            $update_stmt->bind_param("s", $student_id);
                            $update_stmt->execute();

                            if ($insert_stmt->execute()) {
                                // Send an email to the student's parent

                                require "vendor/autoload.php"; // Include the PHPMailer autoloader

                                // Instantiate PHPMailer
                                $mail = new PHPMailer(true);

                                try {
                                    // Server settings
                                    $mail->isSMTP(); // Send using SMTP
                                    $mail->Host = "smtp.gmail.com"; // Set the SMTP server to send through
                                    $mail->SMTPAuth = true; // Enable SMTP authentication
                                    $mail->Username =
                                        "sjaattendancesystem@gmail.com"; // SMTP username
                                    $mail->Password = "cmxkvzmswwmcgioq"; // SMTP password
                                    $mail->SMTPSecure =
                                        PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
                                    $mail->Port = 587; // TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above

                                    // Recipients
                                    $mail->setFrom(
                                        "sjaattendancesystem@gmail.com",
                                        "SJA Attendance System"
                                    );
                                    $mail->addAddress($email_user["email"]); // Add a recipient

                                    // Content
                                    $mail->isHTML(true); // Set email format to HTML
                                    $mail->Subject = "Attendance Notification";

                                    // Construct the HTML body with additional information and styling
                                    $mail->Body =
                                        '
            <html>
            <head>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        background-color: #f4f4f4;
                        padding: 20px;
                    }
                    .container {
                        max-width: 600px;
                        margin: 0 auto;
                        padding: 20px;
                        background-color: #fff;
                        border-radius: 5px;
                        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
                    }
                    h1 {
                        color: #333;
                    }
                    p {
                        margin-bottom: 20px;
                    }
                    .info {
                        background-color: #f9f9f9;
                        padding: 10px;
                        border-radius: 5px;
                    }
                </style>
            </head>
            <body>
                <div class="container">
                    <h1>Attendance Notification</h1>
                    <p>Your child, <strong>' .
                                        $student_info["name"] .
                                        "</strong>, has been marked present for today at <strong>" .
                                        date("Y-m-d H:i:s") .
                                        " " .
                                        $attendance_status .
                                        '</strong>.</p>
                    <div class="info">
                        <p><strong>Student ID:</strong> ' .
                                        $student_id .
                                        '</p>
                        <p><strong>Parent/Guardian Email:</strong> ' .
                                        $email_user["email"] .
                                        '</p>
                    </div>
                    <p>Thank you for using our attendance system.</p>
                    <br>
                    <p>&copy; 2024 Josef Lopez. SJA.</p>
                </div>
            </body>
            </html>
        ';

                                    $mail->AltBody =
                                        "Your child, " .
                                        $student_info["name"] .
                                        ", has been marked present for today at " .
                                        date("Y-m-d H:i:s") .
                                        ".";

                                    $mail->send();

                                    if ($attendance_status == "On Time") {
                                        // Redirect back to attendance_system.php with success message
                                        header(
                                            "Location: attendance_system.php?success=Attendance%20recorded%20successfully.&student_name=$student_name&student_id=$student_id&student_image=$student_image"
                                        );
                                        exit(); // Ensure script stops executing after redirection
                                    } elseif ($attendance_status == "Late") {
                                        // Redirect back to attendance_system.php with success message
                                        header(
                                            "Location: attendance_system.php?error=Attendance%20recorded%20successfully. Please go to the Prefect of Students.&student_name=$student_name&student_id=$student_id&student_image=$student_image"
                                        );
                                        exit(); // Ensure script stops executing after redirection
                                    }
                                } catch (Exception $e) {
                                    // Redirect back to attendance_system.php with error message
                                    header(
                                        "Location: attendance_system.php?error=Message%20could%20not%20be%20sent.%20Mailer%20Error:%20" .
                                            $mail->ErrorInfo
                                    );
                                    exit(); // Ensure script stops executing after redirection
                                }
                            } else {
                                // Redirect back to attendance_system.php with error message
                                header(
                                    "Location: attendance_system.php?error=Error%20recording%20attendance:%20" .
                                        $insert_stmt->error
                                );
                                exit(); // Ensure script stops executing after redirection
                            }
                        } else {
                            // Attendance already recorded for today, redirect back with a message
                            header(
                                "Location: attendance_system.php?error=Attendance%20already%20recorded%20for%20today."
                            );
                            exit();
                        }

                        $insert_stmt->close();
                    } else {
                        // Redirect back to attendance_system.php with error message
                        header(
                            "Location: attendance_system.php?error=Error:%20(" .
                                $current_day_of_week .
                                ")%20Schedule%20not%20found%20for%20today."
                        );
                        exit();
                    }
                }
            } else {
                // Redirect back to attendance_system.php with error message
                header(
                    "Location: attendance_system.php?error=Error:%20Student%20ID%20is%20not%20registered."
                );
                exit(); // Ensure script stops executing after redirection
            }
        } else {
            // Redirect back to attendance_system.php with error message
            header(
                "Location: attendance_system.php?error=Error:%20QR%20code%20has%20expired."
            );
            exit(); // Ensure script stops executing after redirection
        }

        $stmt->close();
    } else {
        // Redirect back to attendance_system.php with error message
        header(
            "Location: attendance_system.php?error=Error:%20Invalid%20QR%20code%20data%20format." .
                $qr_data
        );
        exit(); // Ensure script stops executing after redirection
    }
}

// Hide loading overlay if the script reaches here without redirection
echo "<script>hideLoadingOverlay();</script>";
?>