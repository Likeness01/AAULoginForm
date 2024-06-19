<?php

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
