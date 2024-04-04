<?php
session_start();

// Check if the user is already logged in using the loggedIn cookie
if (!isset($_COOKIE['loggedIn'])) {
    header('Location: login.php');
    exit;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Set the active page to 'dashboard'
$active_page = 'students';

// Include the database connection file
include 'db_connect.php';

// Set the number of students to show per page
$students_per_page = 5;

// Get the current page number
$current_page = isset($_GET['page']) ? intval($_GET['page']) : 1;

// Calculate the offset
$offset = ($current_page - 1) * $students_per_page;

// Check if a search query is submitted
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

// Fetch student data including parent/guardian's name and email with pagination and search
$stmt = $db_conn->prepare("SELECT s.id,s.student_id AS student_id, s.name AS student_name, u.name AS parent_guardian_name, u.email AS student_email
                           FROM students s
                           INNER JOIN users u ON u.username = s.username
                           WHERE s.name LIKE CONCAT('%', ?, '%')
                           LIMIT ?, ?");
$stmt->bind_param("sii", $search_query, $offset, $students_per_page);
$stmt->execute();
$result = $stmt->get_result();
$students = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Process form submissions for student updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $student_id = $_POST['student_id'];

        // Delete student from database
        $stmt = $db_conn->prepare("DELETE FROM students WHERE id = ?");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $stmt->close();

        // Refresh the page after deletion
        header("Location: students.php?page=$current_page");
        exit;
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_users'])) {
      // Process form submission for adding a new student and parent
      $student_id = $_POST['student_id'];
      $student_name = $_POST['student_name'];
      $student_email = $_POST['student_email'];
      $student_username = $_POST['student_username'];
      $student_password = $_POST['student_password'];
      $student_image_name = $_FILES['student_image']['name'];
      $student_image_tmp = $_FILES['student_image']['tmp_name'];
      
      // Get the file extension
      $student_file_extension = pathinfo($student_image_name, PATHINFO_EXTENSION);
      
      // Rename the file to studentusername.filetype
      $student_new_image_name = $student_username . '.' . $student_file_extension;
      
      // Define the URL for the image
      $student_url_link = 'http://localhost/capstone_research_project_v69/student_images/' . $student_new_image_name;
      
      // Move uploaded file to /student_images folder with the new name
      $student_image_path = 'student_images/' . $student_new_image_name;
      move_uploaded_file($student_image_tmp, $student_image_path);
      
      // Process form submission for adding a new parent
      $parent_name = $_POST['parent_name'];
      $parent_email = $_POST['parent_email'];
      $parent_username = $_POST['parent_username'];
      $parent_password = $_POST['parent_password'];

      // Insert student into 'students' table
      $stmt_students = $db_conn->prepare("INSERT INTO students (student_id, username, parent_guardian_username, name, image) VALUES (?, ?, ?, ?, ?)");
      $stmt_students->bind_param("sssss", $student_id, $student_username, $parent_username, $student_name, $student_url_link);
      $stmt_students->execute();
      $stmt_students->close();

      // Generate student hashed password
      $student_hashed_password = password_hash($student_password, PASSWORD_DEFAULT);

      // Insert parent into 'users' table with role 'student'
      $stmt_student = $db_conn->prepare("INSERT INTO users (username, name, password, role, email) VALUES (?, ?, ?, 'student', ?)");
      $stmt_student->bind_param("ssss", $student_username, $student_name, $student_hashed_password, $student_email);
      $stmt_student->execute();
      $stmt_student->close();

      // Generate parent hashed password
      $parent_hashed_password = password_hash($parent_password, PASSWORD_DEFAULT);
      
      // Insert parent into 'users' table with role 'parent'
      $stmt_parent = $db_conn->prepare("INSERT INTO users (username, name, password, role, email) VALUES (?, ?, ?, 'parent', ?)");
      $stmt_parent->bind_param("ssss", $parent_username, $parent_name, $parent_hashed_password, $parent_email);
      $stmt_parent->execute();
      $stmt_parent->close();

      // After adding the users to the database, send emails to the student and parent
    // Send email to student
    sendEmail($student_email, $student_username, $student_password, $student_name, 'student');

    // Send email to parent
    sendEmail($parent_email, $parent_username, $parent_password, $parent_name, 'parent');
      
      // Redirect back to the same page to display the loading overlay
    header("Location: students.php?page=$current_page");
      exit;
  }
  
}

// Function to send email
function sendEmail($recipient_email, $username, $password, $name, $recipient_type) {
  require 'vendor/autoload.php'; // Include the PHPMailer autoloader

  // Instantiate PHPMailer
  $mail = new PHPMailer(true);

  try {
      // Server settings
      $mail->isSMTP();                                        // Send using SMTP
      $mail->Host       = 'smtp.gmail.com';                   // Set the SMTP server to send through
      $mail->SMTPAuth   = true;                               // Enable SMTP authentication
      $mail->Username   = 'sjaattendancesystem@gmail.com';     // SMTP username
      $mail->Password   = 'cmxkvzmswwmcgioq';                 // SMTP password
      $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;     // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
      $mail->Port       = 587;                                // TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above

      // Recipients
      $mail->setFrom('sjaattendancesystem@gmail.com', 'SJA Attendance System');
      $mail->addAddress($recipient_email);                    // Add a recipient

      // Content
      $mail->isHTML(true);                                    // Set email format to HTML
      $mail->Subject = 'Account Creation';
      $mail_body = '
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
                        <h1>Account Created</h1>
                        <p>Hello ' . $name . ',</p>
                        <p>Your account has been successfully created with the following credentials:</p>
                        <div class="info">
                            <p><strong>Username:</strong> ' . $username . '</p>
                            <p><strong>Password:</strong> ' . $password . '</p>
                        </div>
                        <p>You can now log in to access your account @ <a href="http://www.sjaattendancesystem.xyz/">SJA Attendance System</a>.</p>
        ';

        // Add instructions based on recipient type
        if ($recipient_type === 'student') {
            $mail_body .= '<p><strong>Instructions for Students:</strong></p>
                           <p>When scanning the QR code, ensure your phone brightness is set to maximum.</p>
                           <p>QR Codes are valid for only 5 seconds. If necessary, regenerate the code.</p>';
        } elseif ($recipient_type === 'parent') {
            $mail_body .= '<p><strong>Instructions for Parents:</strong></p>
                           <p>You can check whether your child is currently in school or not using the system.</p>
                           <p>You will receive notifications regarding your child\'s presence in or departure from school along with timestamps.</p>';
        }

        // Common instructions for all recipients
        $mail_body .= '<p><strong>General Instructions:</strong></p>
                       <p>Please use a web browser (e.g., Safari, Google Chrome) for the initial login as it is a one-time process.</p>
                       <p>If you need to change your device, please seek assistance from the administrator.</p>
                       <br>
                       <p>&copy; 2024 Josef Lopez. SJA.</p>
                    </div>
                </body>
            </html>
        ';

        $mail->Body = $mail_body;

        $mail->AltBody = "Hello $name,\n\nYour account has been successfully created with the following credentials:\n\nUsername: $username\nPassword: $password\n\nYou can now log in to access your account.";

        $mail->send();
        echo 'Email sent successfully';
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}


// Fetch total number of students with search filter
$stmt_total = $db_conn->prepare("SELECT COUNT(*) AS total FROM students WHERE name LIKE CONCAT('%', ?, '%')");
$stmt_total->bind_param("s", $search_query);
$stmt_total->execute();
$result_total = $stmt_total->get_result();
$totalStudents = $result_total->fetch_assoc()['total'];
$stmt_total->close();

// Calculate the total number of pages
$totalPages = ceil($totalStudents / $students_per_page);

// Set the number of parents to show per page
$parents_per_page = 5;

// Get the current page number for parents
$current_page_parents = isset($_GET['parent_page']) ? intval($_GET['parent_page']) : 1;

// Calculate the offset for parents
$parent_offset = ($current_page_parents - 1) * $parents_per_page;

// Check if a search query is submitted for parents
$parent_search_query = isset($_GET['parent_search']) ? $_GET['parent_search'] : '';

// Fetch parent data with pagination and search
$stmt_parents = $db_conn->prepare("SELECT u.id, u.name, u.email, s.name AS student_name
                                   FROM users u
                                   LEFT JOIN students s ON u.username = s.parent_guardian_username
                                   WHERE u.role = 'parent' AND u.name LIKE CONCAT('%', ?, '%')
                                   LIMIT ?, ?");
$stmt_parents->bind_param("sii", $parent_search_query, $parent_offset, $parents_per_page);
$stmt_parents->execute();
$result_parents = $stmt_parents->get_result();
$parents = $result_parents->fetch_all(MYSQLI_ASSOC);
$stmt_parents->close();

// Fetch total number of parents with search filter
$stmt_total_parents = $db_conn->prepare("SELECT COUNT(*) AS total FROM users WHERE role = 'parent' AND name LIKE CONCAT('%', ?, '%')");
$stmt_total_parents->bind_param("s", $parent_search_query);
$stmt_total_parents->execute();
$result_total_parents = $stmt_total_parents->get_result();
$totalParents = $result_total_parents->fetch_assoc()['total'];
$stmt_total_parents->close();

// Calculate the total number of pages for parents with search filter
$totalParentPages = ceil($totalParents / $parents_per_page);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>St. Joseph's Academy - Students</title>
  <link rel="stylesheet" href="./css/students_style.css">
  <link rel="stylesheet" href="./css/static_style.css">
  <link rel="icon" type="image/x-icon" href="./img/sja-logo.png">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.4/xlsx.full.min.js"></script>

</head>

<body>
  <?php include './static/sidebar.php'; ?>

  <div class="content">
    <?php include './static/header.php'; ?>
    <div class="main">
      <h2>Students List</h2>
      <div class="search-form">
        <form method="get">
          <input type="text" name="search" placeholder="Search by student name"
            value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
          <button type="submit">Search</button>
        </form>
      </div>
      <table id="student-table">
        <thead>
          <tr>
            <th>Student Number</th>
            <th>Student Name</th>
            <th>Student Email</th>
            <th>Parent/Guardian Name</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($students as $student) : ?>
          <tr>
            <td><?php echo $student['student_id']; ?></td>
            <td><?php echo $student['student_name']; ?></td>
            <td><?php echo $student['student_email']; ?></td>
            <td><?php echo $student['parent_guardian_name']; ?></td>
            <td>
              <form method="post">
                <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                <button type="submit" name="action" value="delete">Delete</button>
              </form>

              <form method="post">
        <input type="hidden" name="student_id" value="<?php echo $student['student_id']; ?>">
        <button type="submit" name="view_attendance">View Attendance</button>
    </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>



      <!-- Pagination -->
      <div class="pagination">
        <?php if ($current_page > 1) : ?>
        <a href="?page=<?php echo ($current_page - 1); ?>">Previous</a>
        <?php endif; ?>

        <?php
    // Calculate the range of page numbers to display
    $start = max(1, $current_page - 2);
    $end = min($totalPages, $start + 4); // Adjusted the calculation for the end

    // Display page numbers within the range
    for ($i = $start; $i <= $end; $i++) : ?>
        <a href="?page=<?php echo $i; ?>"
          <?php echo ($i == $current_page) ? 'class="active"' : ''; ?>><?php echo $i; ?></a>
        <?php endfor; ?>

        <?php if ($current_page < $totalPages) : ?>
        <a href="?page=<?php echo ($current_page + 1); ?>">Next</a>
        <?php endif; ?>
      </div>


      <h2>Parents List</h2>
      <div class="search-form">
        <form method="get">
          <input type="text" name="parent_search" placeholder="Search by parent name"
            value="<?php echo htmlspecialchars($_GET['parent_search'] ?? ''); ?>">
          <button type="submit">Search</button>
        </form>
      </div>
      <table>
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Child's Name</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($parents as $parent) : ?>
          <tr>
            <td><?php echo $parent['name']; ?></td>
            <td><?php echo $parent['email']; ?></td>
            <td><?php echo $parent['student_name'] ?? 'No child'; ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <!-- Pagination for Parents -->
      <div class="pagination">
        <?php if ($current_page_parents > 1) : ?>
        <a href="?parent_page=<?php echo ($current_page_parents - 1); ?>">Previous</a>
        <?php endif; ?>

        <?php
        // Display page numbers within the range
        for ($i = 1; $i <= $totalParentPages; $i++) : ?>
        <a href="?parent_page=<?php echo $i; ?>"
          <?php echo ($i == $current_page_parents) ? 'class="active"' : ''; ?>><?php echo $i; ?></a>
        <?php endfor; ?>

        <?php if ($current_page_parents < $totalParentPages) : ?>
        <a href="?parent_page=<?php echo ($current_page_parents + 1); ?>">Next</a>
        <?php endif; ?>
      </div>
    </div>

    <!-- Add Student and Parent Form -->
    <div class="main add-user-form">
      <h2 id="add-student-header" class="add-student-header">Add Student</h2>
      <form method="post" enctype="multipart/form-data" id="add-student-form" class="add-student-form">
        <h3>Student Details:</h3>
        <label for="student_name">Student Name:</label>
        <input type="text" id="student_name" name="student_name" required><br>
        <label for="student_email">Student Email:</label>
        <input type="text" id="student_email" name="student_email" required><br>
        <label for="student_id">Student ID:</label>
        <input type="text" id="student_id" name="student_id" required><br>
        <label for="student_username">Student Username:</label>
        <input type="text" id="student_username" name="student_username" required><br>
        <label for="student_password">Student Password:</label>
        <input type="text" id="student_password" name="student_password" required><br>
        <label for="student_image">Student Image:</label>
        <input type="file" id="student_image" name="student_image" accept="image/*" required><br>

        <h3>Parent Details:</h3>
        <label for="parent_name">Parent Name:</label>
        <input type="text" id="parent_name" name="parent_name" required><br>
        <label for="parent_email">Parent Email:</label>
        <input type="email" id="parent_email" name="parent_email" required><br>
        <label for="parent_username">Parent Username:</label>
        <input type="text" id="parent_username" name="parent_username" required><br>
        <label for="parent_password">Parent Password:</label>
        <input type="text" id="parent_password" name="parent_password" required><br>

        <button type="submit" name="add_users">Add Users</button>
      </form>
    </div>



  </div>


  </div>

  <?php include './static/footer.php'; ?>

  <script>
  document.addEventListener('DOMContentLoaded', function() {
    // Get references to the header and form
    const addStudentHeader = document.getElementById('add-student-header');
    const addStudentForm = document.getElementById('add-student-form');

    // Add click event listener to the header
    addStudentHeader.addEventListener('click', function() {
      // Toggle the visibility of the form
      addStudentForm.style.display = (addStudentForm.style.display === 'none') ? 'block' : 'none';
    });

    // Add click event listener to document to hide form when clicking outside
    document.addEventListener('click', function(event) {
      if (!addStudentHeader.contains(event.target) && !addStudentForm.contains(event.target)) {
        addStudentForm.style.display = 'none';
      }
    });
  });

  document.querySelectorAll('[name="view_attendance"]').forEach(button => {
    button.addEventListener('click', function(event) {
        event.preventDefault();
        const studentId = this.parentElement.querySelector('[name="student_id"]').value;

        // Create overlay
        const overlay = document.createElement('div');
        overlay.classList.add('overlay');
        document.body.appendChild(overlay);

        // Send AJAX request to fetch attendance data
        fetch('fetch_attendance.php?student_id=' + studentId)
    .then(response => response.json())
    .then(data => {
        // Group attendance data by month
        const attendanceByMonth = {};
        data.forEach(record => {
            const date = new Date(record.date_time);
            const monthKey = `${date.getFullYear()}-${date.getMonth() + 1}`;
            if (!attendanceByMonth[monthKey]) {
                attendanceByMonth[monthKey] = [];
            }
            // Include student ID in the attendance record
            record.student_id = studentId;
            attendanceByMonth[monthKey].push(record);
        });

                // Display attendance data in a floating div
                const attendanceContainer = document.createElement('div');
                attendanceContainer.classList.add('attendance-container');

                const closeBtn = document.createElement('button');
                closeBtn.textContent = 'Close';
                closeBtn.classList.add('close-btn');
                closeBtn.addEventListener('click', function() {
                    attendanceContainer.remove();
                    overlay.remove(); // Remove the overlay when closing the table
                });
                attendanceContainer.appendChild(closeBtn);

                // Create a button to export data as Excel
                const exportBtn = document.createElement('button');
                exportBtn.textContent = 'Export as Excel';
                exportBtn.classList.add('export-btn');
                exportBtn.addEventListener('click', function() {
                    exportAttendanceToExcel(attendanceByMonth, studentId);
                });
                attendanceContainer.appendChild(exportBtn);

                // Create a container div for the attendance tables and pagination
                const tableContainer = document.createElement('div');
                tableContainer.classList.add('attendance-table-container');

                // Loop through each month's attendance data
                Object.entries(attendanceByMonth).forEach(([monthKey, monthData]) => {
                    const monthHeader = document.createElement('div');
                    monthHeader.classList.add('month-header');
                    monthHeader.textContent = getMonthName(monthKey);
                    tableContainer.appendChild(monthHeader);

                    const monthAttendanceTable = createAttendanceTable(monthData, studentId, monthKey);
                    tableContainer.appendChild(monthAttendanceTable);

                    // Add margin-bottom to create space between tables
                    monthHeader.style.marginBottom = '10px';
                    monthAttendanceTable.style.marginBottom = '20px';
                });

                attendanceContainer.appendChild(tableContainer);
                document.body.appendChild(attendanceContainer);
            })
            .catch(error => {
                console.error('Error fetching attendance:', error);
                overlay.remove(); // Remove the overlay if an error occurs
            });
    });
});

// Function to create an attendance table for a specific month
function createAttendanceTable(data, studentId, monthKey) {
    const attendanceTable = document.createElement('table');
    attendanceTable.classList.add('attendance-calendar');

    // Parse the year and month from the month key
    const [year, month] = monthKey.split('-');

    // Create thead element for table header
    const tableHeader = document.createElement('thead');
    const headerRow = document.createElement('tr');

    // Array of days of the week
    const daysOfWeek = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

    // Create th elements for days of the week
    daysOfWeek.forEach(day => {
        const dayHeader = document.createElement('th');
        dayHeader.textContent = day;
        headerRow.appendChild(dayHeader);
    });

    // Append the header row to the table header
    tableHeader.appendChild(headerRow);

    // Append the table header to the attendance table
    attendanceTable.appendChild(tableHeader);

    // Create tbody element for table body
    const tableBody = document.createElement('tbody');

    // Get the first day of the specified month
    const firstDayOfMonth = new Date(year, month - 1, 1);

    // Get the last day of the specified month
    const lastDayOfMonth = new Date(year, month, 0);

    let currentRow;
    let currentDayOfWeek = firstDayOfMonth.getDay();

    // Loop through each date in the month
    for (let date = firstDayOfMonth; date <= lastDayOfMonth; date.setDate(date.getDate() + 1)) {
        const dayOfMonth = date.getDate();
        const dayOfWeek = date.getDay(); // Sunday is 0, Monday is 1, etc.

        // Start a new row for each week
        if (dayOfWeek === 0 || !currentRow) {
            currentRow = document.createElement('tr');
            tableBody.appendChild(currentRow);

            // Fill in placeholders for days before the first day of the month
            for (let i = 0; i < currentDayOfWeek; i++) {
                const cell = document.createElement('td');
                cell.textContent = '-';
                currentRow.appendChild(cell);
            }
        }

        // Fill in the cell with the day of the month
        const cell = document.createElement('td');
        cell.textContent = dayOfMonth;

        // Find the corresponding attendance record for this date
        const record = data.find(record => {
            const recordDate = new Date(record.date_time);
            return recordDate.getDate() === dayOfMonth;
        });

        // Fetch section schedules for the current student
        fetch('fetch_section_schedules.php?student_id=' + studentId)
            .then(response => response.json())
            .then(scheduleData => {
                // Get the day of the week for the current date
                const daysOfWeekFull = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                const currentDayName = daysOfWeekFull[dayOfWeek];

                // Check if the current day exists in the fetched section schedules
                const matchingSchedule = scheduleData.find(schedule => schedule.day_of_week === currentDayName);

                // If the current day exists in the schedule data, check attendance and add classes
                if (matchingSchedule) {
                    // If attendance record exists for the current day, add 'present' class
                    if (record) {
                        cell.classList.add('present');
                    } else {
                        cell.classList.add('absent');
                    }
                } else {
                  if (record) {
                        cell.classList.add('present');
                    }
                }
              
            });

        currentRow.appendChild(cell);

        // Reset the day of the week counter if it's the last day of the week
        if (dayOfWeek === 6) {
            currentDayOfWeek = 0;
        } else {
            currentDayOfWeek++;
        }
    }

    // Append the table body to the attendance table
    attendanceTable.appendChild(tableBody);

    return attendanceTable;
}

// Function to get the numerical representation of the day of the week
function getDayOfWeekNumber(dayOfWeekString) {
    const daysOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    return daysOfWeek.indexOf(dayOfWeekString);
}












// Function to get the name of the month from a month key
function getMonthName(monthKey) {
    const [year, month] = monthKey.split('-');
    const monthNames = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ];
    return `${monthNames[parseInt(month) - 1]} ${year}`;
}

// Function to export attendance data to Excel
async function exportAttendanceToExcel(data, studentId) {
    const workbook = XLSX.utils.book_new();

    // Iterate over each month's attendance data
    for (const [monthKey, monthData] of Object.entries(data)) {
        const worksheetData = [];

        // Add headers
        const daysOfWeek = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        const headerRow = [''];
        daysOfWeek.forEach(day => headerRow.push(day));
        worksheetData.push(headerRow);

        // Get the current date
        const currentDate = new Date(monthKey);

        // Get the first day of the current month
        const firstDayOfMonth = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);

        // Get the last day of the current month
        const lastDayOfMonth = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);

        let currentRow = [];
        let currentDayOfWeek = firstDayOfMonth.getDay();
        let totalPresent = 0; // Variable to store total present days
        let totalAbsent = 0; // Variable to store total present days
        

        // Fill in placeholders for days before the first day of the month
        currentRow.push(''); // Add one blank cell in column A
        for (let i = 0; i < currentDayOfWeek; i++) {
            currentRow.push('');
        }

        // Loop through each date in the month
        for (let date = firstDayOfMonth; date <= lastDayOfMonth; date.setDate(date.getDate() + 1)) {
            const dayOfMonth = date.getDate();
            const dayOfWeek = date.getDay(); // Sunday is 0, Monday is 1, etc.

            // If it's the first day of the week or the first iteration, insert a blank cell
            if (dayOfWeek === 0 || currentRow.length === 0) {
                currentRow.push(''); // Insert blank cell in column A
            }

            // Find the corresponding attendance record for this date
            const record = monthData.find(record => {
                const recordDate = new Date(record.date_time);
                return recordDate.getDate() === dayOfMonth;
            });

            // Fetch section schedules for the current student
            const response = await fetch('fetch_section_schedules.php?student_id=' + studentId);
            const scheduleData = await response.json();

            // Get the day of the week for the current date
            const daysOfWeekFull = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            const currentDayName = daysOfWeekFull[dayOfWeek];

            // Check if the current day exists in the fetched section schedules
            const matchingSchedule = scheduleData.find(schedule => schedule.day_of_week === currentDayName);

            // If the current day exists in the schedule data, check attendance and add classes
            if (matchingSchedule) {
                // Check if attendance record exists for the current day and add 'present' or 'absent' class
                if (record) {
                    currentRow.push(`${dayOfMonth} - Present`);
                    totalPresent++; // Increment total present days
                } else {
                    currentRow.push(`${dayOfMonth} - Absent`);
                    totalAbsent++;
                }
            } else {
                // Check if attendance record exists for the current day and add 'present' or 'absent' class
                if (record) {
                    currentRow.push(`${dayOfMonth} - Present`);
                    totalPresent++; // Increment total present days
                } else {
                  currentRow.push(`${dayOfMonth}`);
                }
            }

            // Move to the next row if it's the last day of the week
            if (dayOfWeek === 6) {
                worksheetData.push(currentRow);
                currentRow = [];                
            }
        }

        // Push the last row if it's not complete
        if (currentRow.length > 0) {
            worksheetData.push(currentRow);
        }

        // Add present count row
        worksheetData.push(['', '', '', '', '', '', '', `Total Present: ${totalPresent}`]);
        worksheetData.push(['', '', '', '', '', '', '', `Total Absent: ${totalAbsent}`]);

        // Create a worksheet
        const worksheet = XLSX.utils.aoa_to_sheet(worksheetData);

        // Add the worksheet to the workbook
        workbook.SheetNames.push(getMonthName(monthKey));
        workbook.Sheets[getMonthName(monthKey)] = worksheet;

        // Set column widths (optional)
        const wscols = [{ wpx: 100 }]; // Adjust column width as needed
        for (let i = 0; i < daysOfWeek.length; i++) {
            wscols.push({ wpx: 100 }); // Adjust column width as needed
        }
        worksheet['!cols'] = wscols;

    }

    // Generate Excel file and trigger download
    XLSX.writeFile(workbook, 'attendance.xlsx');
}













  </script>
</body>

</html>