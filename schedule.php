<?php
session_start();

// Check if the user is already logged in using the loggedIn cookie
if (!isset($_COOKIE['loggedIn'])) {
    header('Location: login.php');
    exit;
}

// Include the database connection file
include 'db_connect.php';

// Set the active page to 'schedule'
$active_page = 'schedule';

// // Fetch section schedules
// $stmt_sections = $db_conn->prepare("SELECT * FROM section_schedules");
// $stmt_sections->execute();
// $result_sections = $stmt_sections->get_result();
// $sections = $result_sections->fetch_all(MYSQLI_ASSOC);
// $stmt_sections->close();

// Process form submission for adding a class for a specific day
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_class'])) {
    $section_id = $_POST['section_id'];
    $class_date = $_POST['class_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $no_classes = false;

    // Insert class into 'classes' table
    $stmt_class = $db_conn->prepare("INSERT INTO classes (section_id, class_date, start_time, end_time, no_classes) VALUES (?, ?, ?, ?, ?)");
    $stmt_class->bind_param("sssss", $section_id, $class_date, $start_time, $end_time, $no_classes);
    $stmt_class->execute();
    $stmt_class->close();

    // Redirect back to the same page after adding the class
    header("Location: schedule.php");
    exit;
}

// Process form submission for marking a day with no classes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_no_classes'])) {
  $section_id = $_POST['section_id'];
  $no_class_date = $_POST['no_classes_date'];
  $no_classes = true;

  // Delete existing classes for the specified date
  $stmt_delete = $db_conn->prepare("INSERT INTO classes (section_id, class_date, no_classes) VALUES (?, ?, ?)");
  $stmt_delete->bind_param("iss", $section_id, $no_class_date, $no_classes);
  $stmt_delete->execute();
  $stmt_delete->close();

  // Redirect back to the same page after marking the day with no classes
  header("Location: schedule.php");
  exit;
}


// if (isset($_GET['view_schedule'])) {
//   // Assuming $db_conn is your MySQLi database connection object
//   $section_id = 1; // Replace 123 with the actual section ID you want to retrieve schedules for

//   $query = "SELECT * FROM section_schedules WHERE section_id = ?";
//   $statement = $db_conn->prepare($query);
//   $statement->bind_param('i', $section_id);
//   $statement->execute();
//   $result = $statement->get_result();

//   // Check if there are any rows returned
//   if ($result->num_rows > 0) {
//       // Fetch all rows as an associative array
//       $sectionSchedules = $result->fetch_all(MYSQLI_ASSOC);

//       // Output the section schedules
//       foreach ($sectionSchedules as $schedule) {
//           echo "Day of Week: " . $schedule['day_of_week'] . "<br>";
//           echo "Start Time: " . $schedule['start_time'] . "<br>";
//           echo "End Time: " . $schedule['end_time'] . "<br>";
//           echo "<hr>";
//       }
//   } else {
//       // No schedules found
//       echo "No schedules found for the given section ID.";
//   }

//   // Close the statement and database connection
//   $statement->close();
// }
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>St. Joseph's Academy - Scheduling</title>
  <link rel="stylesheet" href="./css/schedule_style.css">
  <link rel="stylesheet" href="./css/static_style.css">
  <link rel="icon" type="image/x-icon" href="./img/sja-logo.png">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.4/xlsx.full.min.js"></script>

</head>

<body>
  <?php include './static/sidebar.php'; ?>

  <div class="content">
    <?php include './static/header.php'; ?>
    <div class="main">
<!-- Add Section Schedule Display Form -->
<h2>View Section Schedule</h2>
<form method="post">
  <label for="section_id">Section ID:</label>
  <input type="text" id="section_id" name="section_id" required>
  <button type="submit" name="view_schedule">View Schedule</button>
</form>

<!-- Add Class Addition Form -->
<h2>Add Class for a Day</h2>
<form method="post">
  <label for="section_id">Section ID:</label>
  <input type="text" id="section_id" name="section_id" required><br>
  <label for="date">Date:</label>
  <input type="date" id="class_date" name="class_date" required><br>
  <label for="start_time">Start Time:</label>
  <input type="time" id="start_time" name="start_time" required><br>
  <label for="end_time">End Time:</label>
  <input type="time" id="end_time" name="end_time" required><br>
  <button type="submit" name="add_class">Add Class</button>
</form>

<!-- Add Option for No Classes -->
<h2>No Classes for a Day</h2>
<form method="post">
  <label for="section_id">Section ID:</label>
  <input type="text" id="section_id" name="section_id" required><br
  <label for="no_classes_date">Select Date:</label>
  <input type="date" id="no_classes_date" name="no_classes_date" required><br>
  <button type="submit" name="mark_no_classes">Mark No Classes</button>
</form>

    </div>
  </div>

  <?php include './static/footer.php'; ?>

  <script>
    document.querySelectorAll('[name="view_schedule"]').forEach(button => {
    button.addEventListener('click', function(event) {
        event.preventDefault();
        const sectionId = this.parentElement.querySelector('[name="section_id"]').value;

        // Create overlay
        const overlay = document.createElement('div');
        overlay.classList.add('overlay');
        document.body.appendChild(overlay);

        // Send AJAX request to fetch schedule data
        // Send AJAX request to fetch schedule data
fetch('fetch_schedule.php?section_id=' + sectionId)
.then(response => response.json())
.then(data => {
    // Sort the schedule data by day of the week
    data.sort((a, b) => {
        const daysOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        return daysOfWeek.indexOf(a.day_of_week) - daysOfWeek.indexOf(b.day_of_week);
    });

    // Create floating div to display schedule
    const scheduleDiv = document.createElement('div');
    scheduleDiv.classList.add('floating-schedule');

    // Create close button for the schedule div
    const closeButton = document.createElement('button');
    closeButton.textContent = 'Close';
    closeButton.classList.add('close-btn');
    closeButton.addEventListener('click', function() {
        scheduleDiv.remove();
        overlay.remove(); // Remove the overlay when closing the schedule
    });
    scheduleDiv.appendChild(closeButton);

    // Create header for the schedule div
    const header = document.createElement('h2');
    header.textContent = `Section ${sectionId} Schedule`;
    scheduleDiv.appendChild(header);

    // Create table to display schedule
    const scheduleTable = document.createElement('table');
    const tableHeader = scheduleTable.createTHead();
    const headerRow = tableHeader.insertRow();
    const headers = ['Day of Week', 'Start Time', 'End Time'];
    headers.forEach(headerText => {
        const th = document.createElement('th');
        th.textContent = headerText;
        headerRow.appendChild(th);
    });

    // Create table body and populate it with schedule data
    const tableBody = scheduleTable.createTBody();
    data.forEach(schedule => {
        const row = tableBody.insertRow();
        row.insertCell().textContent = schedule.day_of_week;
        row.insertCell().textContent = convertToNormalTime(schedule.start_time);
        row.insertCell().textContent = convertToNormalTime(schedule.end_time);
    });

    scheduleDiv.appendChild(scheduleTable);
    document.body.appendChild(scheduleDiv);
})
.catch(error => {
    console.error('Error fetching schedule:', error);
    overlay.remove(); // Remove the overlay if an error occurs
});

    });

    function convertToNormalTime(militaryTime) {
    // Split the military time string into hours and minutes
    const [hours, minutes] = militaryTime.split(':').map(Number);
    
    // Determine if it's AM or PM
    const period = hours >= 12 ? 'PM' : 'AM';
    
    // Convert hours to 12-hour format
    let hours12 = hours % 12;
    hours12 = hours12 === 0 ? 12 : hours12; // Handle midnight (00:00) as 12:00 AM
    
    // Format minutes with leading zero if needed
    const minutesFormatted = minutes < 10 ? '0' + minutes : minutes;
    
    // Construct the normal time string
    const normalTime = `${hours12}:${minutesFormatted} ${period}`;
    
    return normalTime;
}
});

  </script>
</body>
</html>