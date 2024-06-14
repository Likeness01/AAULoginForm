<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load Composer's autoloader
require 'vendor/autoload.php';

// Database configuration
$servername = "localhost"; // Adjust as needed
$username = "root"; // Adjust as needed
$password = ""; // Adjust as needed
$dbname = "support_form"; // Ensure this matches your database name
$adminEmail = "############"; // Admin email address

// Connect to the database
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to validate email
function validateEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Function to validate file
function validateFile($file)
{
    $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/jpg'];
    $maxFileSize = 2 * 1024 * 1024; // 2MB

    if (!in_array($file['type'], $allowedMimeTypes)) {
        return false;
    }

    if ($file['size'] > $maxFileSize) {
        return false;
    }

    return true;
}

// Initialize error array
$errors = [];

// Check and sanitize form inputs
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);

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

    // If no errors, proceed to store data
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO support_requests (name, email, subject, message) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $subject, $message);

        if ($stmt->execute()) {
            $lastId = $conn->insert_id;

            // Store uploaded files
            $uploadDir = 'uploads/';
            $uploadedFilePaths = [];
            foreach ($files['tmp_name'] as $key => $tmpName) {
                $fileName = basename($files['name'][$key]);
                $uploadFilePath = $uploadDir . $lastId . '_' . $fileName;

                if (move_uploaded_file($tmpName, $uploadFilePath)) {
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
                    // Server settings
                    $mail->isSMTP();
                    // Set the SMTP server to send through
                    $mail->Host = 'smtp.adminEmail';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'adminEmail'; // SMTP username
                    $mail->Password = '##########'; // SMTP password
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;

                    // Recipients
                    $mail->setFrom('no-reply@example.com', 'Support System');
                    $mail->addAddress($adminEmail);

                    // Attachments
                    foreach ($uploadedFilePaths as $filePath) {
                        $mail->addAttachment($filePath);
                    }

                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = "New Support Request: " . $subject;
                    $mail->Body    = "You have received a new support request.<br><br>" .
                        "Name: $name<br>" .
                        "Email: $email<br>" .
                        "Subject: $subject<br>" .
                        "Message:<br>" . nl2br($message) . "<br><br>" .
                        "Please log in to the admin panel to view the details and attached files.";

                    $mail->send();
                    echo "Support request submitted successfully!</br> </br>";
                } catch (Exception $e) {
                    echo "Support request submitted, but failed to send email. Mailer Error: {$mail->ErrorInfo}<br/>";
                }
            } else {
                echo "<br/>Errors occurred: " . implode(", ", $errors);
            }
        } else {
            echo "<br/>Error: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "<br/>Errors occurred: " . implode(", ", $errors);
    }

    $conn->close();
}

