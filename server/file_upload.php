<?php

function handleFileUploads($files, $lastId)
{
    $errors = [];
    $uploadDir = 'uploads/';
    $uploadedFilePaths = [];

    // Ensure the uploads directory exists
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            return [[], ["Failed to create upload directory."]];
        }
    }

    // Normalize the file input to always be an array of files
    $fileArray = is_array($files['name']) ? $files : array_map(function ($value) {
        return [$value];
    }, $files);

    foreach ($fileArray['tmp_name'] as $key => $tmpName) {
        $fileName = basename($fileArray['name'][$key]);
        $uploadFilePath = $uploadDir . $lastId . '_' . $fileName;

        if ($fileArray['size'][$key] > 0 && move_uploaded_file($tmpName, $uploadFilePath)) {
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
