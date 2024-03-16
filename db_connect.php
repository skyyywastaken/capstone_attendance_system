<?php
    // Enable error reporting
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    // Database connection parameters
    $db_host = 'localhost'; // Database host (e.g., localhost)
    $db_username = 'root'; // Database username
    $db_password = ''; // Database password
    $db_name = 'capston_research_project_v1'; // Database name

    // Create a new database connection
    $db_conn = new mysqli($db_host, $db_username, $db_password, $db_name);

    // Check connection
    if ($db_conn->connect_error) {
        die("Connection failed: " . $db_conn->connect_error);
    }
?>