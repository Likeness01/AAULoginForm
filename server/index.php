<?php

require 'config.php';
require 'validation.php';
require 'email.php';

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Initialize error array
$errors = [];

// Allowed support types
$allowedSupportTypes = ["ICT", "Registrar", "Exams_and_Records", "Other"];

try {
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

        // If there are no errors, process the form
        if (empty($errors)) {
            // Assuming you have a function to insert the support request and get the last inserted ID
            $lastId = insertSupportRequest($supportType, $name, $email, $subject, $message);

            if ($lastId) {
                $uploadedFilePaths = [];
                if (!empty($files['name'][0])) {
                    list($uploadedFilePaths, $fileUploadErrors) = handleFileUploads($files, $lastId);
                    if (!empty($fileUploadErrors)) {
                        $errors = array_merge($errors, $fileUploadErrors);
                    }
                }

                // If file uploads succeeded, send email
                if (empty($errors)) {
                    $emailResult = sendEmail($supportType, $name, $email, $subject, $message, $uploadedFilePaths);
                    if ($emailResult['success']) {
                        echo json_encode(['success' => true]);
                    } else {
                        $errors[] = $emailResult['error'];
                        echo json_encode(['success' => false, 'error' => implode(', ', $errors)]);
                    }
                } else {
                    echo json_encode(['success' => false, 'error' => implode(', ', $errors)]);
                }
            } else {
                $errors[] = "Failed to insert support request.";
                echo json_encode(['success' => false, 'error' => implode(', ', $errors)]);
            }
        } else {
            echo json_encode(['success' => false, 'error' => implode(', ', $errors)]);
        }
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

// Function to insert support request and return last inserted ID
function insertSupportRequest($supportType, $name, $email, $subject, $message)
{
    global $conn;
    $stmt = $conn->prepare("INSERT INTO support_requests (support_type, name, email, subject, message) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $supportType, $name, $email, $subject, $message);
    if ($stmt->execute()) {
        return $stmt->insert_id;
    } else {
        return false;
    }
}
