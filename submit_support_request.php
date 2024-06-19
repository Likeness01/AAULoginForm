<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load Composer's autoloader
require 'vendor/autoload.php';

// Suppress PHP errors and warnings
error_reporting(0);
ini_set('display_errors', 0);

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "support_form";
$adminEmail = "admin@example.com";

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

// Function to validate email
function validateEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Function to validate file
function validateFile($file)
{
    if ($file['size'] === 0) {
        return true; // Skip validation for empty file
    }

    $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/jpg'];
    $maxFileSize = 2 * 1024 * 1024; // 2MB

    if (!in_array($file['type'], $allowedMimeTypes) || $file['size'] > $maxFileSize) {
        return false;
    }

    return true;
}

// Initialize error array
$errors = [];

// Allowed support types
$allowedSupportTypes = ["ICT", "Registrar", "Exams_and_Records", "Other"];

// Check and sanitize form inputs
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $supportType = trim($_POST['supportType']);
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);

    // Validate support type
    if (!in_array($supportType, $allowedSupportTypes)) {
        $errors[] = "Invalid support type selected.";
    }

    // Validate inputs
    if (strlen($name) < 4) {
        $errors[] = "Name must be at least 4 characters long.";
    }

    if (!validateEmail($email)) {
        $errors[] = "Invalid email format.";
    }

    if (strlen($subject) < 5) {
        $errors[] = "Subject must be at least 5 characters long.";
    }

    if (strlen($message) < 10) {
        $errors[] = "Message must be at least 10 characters long.";
    }

    // Validate files
    $files = $_FILES['file'];
    if (!empty($files['name'][0])) {
        $fileCount = count($files['name']);
        if ($fileCount < 1 || $fileCount > 4) {
            $errors[] = "You must upload between 1 and 4 images.";
        }

        for ($i = 0; $i < $fileCount; $i++) {
            if (!validateFile([
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i]
            ])) {
                $errors[] = "File " . $files['name'][$i] . " is invalid.";
            }
        }
    }

    // If no errors, proceed to store data
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO support_requests (support_type, name, email, subject, message) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $supportType, $name, $email, $subject, $message);

        if ($stmt->execute()) {
            $lastId = $conn->insert_id;

            // Store uploaded files
            $uploadDir = 'uploads/';
            $uploadedFilePaths = [];
            foreach ($files['tmp_name'] as $key => $tmpName) {
                $fileName = basename($files['name'][$key]);
                $uploadFilePath = $uploadDir . $lastId . '_' . $fileName;

                if ($files['size'][$key] > 0 && move_uploaded_file($tmpName, $uploadFilePath)) {
                    // Insert file record into database
                    $stmt = $conn->prepare("INSERT INTO support_files (support_request_id, file_path) VALUES (?, ?)");
                    $stmt->bind_param("is", $lastId, $uploadFilePath);
                    $stmt->execute();
                    $uploadedFilePaths[] = $uploadFilePath;
                } else {
                    $errors[] = "Failed to upload file " . $fileName;
                }
            }

            if (empty($errors)) {
                // Send email to admin with PHPMailer
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.support@dfgh.edu.ng'; // Adjust to your SMTP host
                    $mail->SMTPAuth = true;
                    $mail->Username = 'support@dfghj.edu.ng'; // SMTP username
                    $mail->Password = '######wP'; // SMTP password
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;

                    $mail->setFrom('support@aauekpoma.edu.ng', 'Support System');
                    $mail->addAddress($adminEmail);

                    foreach ($uploadedFilePaths as $filePath) {
                        $mail->addAttachment($filePath);
                    }

                    $mail->isHTML(true);
                    $mail->Subject = "New Support Request: " . $subject;
                    $mail->Body    = "You have received a new support request.<br><br>" .
                        "Support Type: $supportType<br>" .
                        "Name: $name<br>" .
                        "Email: $email<br>" .
                        "Subject: $subject<br>" .
                        "Message:<br>" . nl2br($message) . "<br><br>" .
                        "Please log in to the admin panel to view the details and attached files.";

                    $mail->send();
                    $response = ["success" => true];
                } catch (Exception $e) {
                    $response = [
                        "success" => false,
                        "error" => "Support request submitted, but failed to send email. Mailer Error: {$mail->ErrorInfo}"
                    ];
                }
            } else {
                $response = [
                    "success" => false,
                    "error" => "Errors occurred: " . implode(", ", $errors)
                ];
            }
        } else {
            $response = [
                "success" => false,
                "error" => "Error: " . $stmt->error
            ];
        }

        $stmt->close();
    } else {
        $response = [
            "success" => false,
            "error" => "Errors occurred: " . implode(", ", $errors)
        ];
    }

    $conn->close();

    // Return JSON response to JavaScript
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}
