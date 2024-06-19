<?php

function handleFileUploads($files, $lastId)
{
    $errors = [];
    $uploadDir = 'uploads/';
    $uploadedFilePaths = [];

    foreach ($files['tmp_name'] as $key => $tmpName) {
        $fileName = basename($files['name'][$key]);
        $uploadFilePath = $uploadDir . $lastId . '_' . $fileName;

        if ($files['size'][$key] > 0 && move_uploaded_file($tmpName, $uploadFilePath)) {
            // Insert file record into database
            global $conn;
            $stmt = $conn->prepare("INSERT INTO support_files (support_request_id, file_path) VALUES (?, ?)");
            $stmt->bind_param("is", $lastId, $uploadFilePath);
            $stmt->execute();
            $uploadedFilePaths[] = $uploadFilePath;
        } else {
            $errors[] = "Failed to upload file " . $fileName;
        }
    }

    return [$uploadedFilePaths, $errors];
}
