<?php

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "support_form";
$defaultAdminEmail = "admin@example.com";

// Connect to the database
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    sendErrorResponse("Connection failed: " . $conn->connect_error);
}

// Function to send JSON error response
function sendErrorResponse($error)
{
    header('Content-Type: application/json');
    echo json_encode(["success" => false, "error" => $error]);
    exit();
}
