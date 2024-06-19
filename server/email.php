<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load Composer's autoloader
require '../vendor/autoload.php';

function sendEmail($supportType, $subject, $message, $name, $email, $uploadedFilePaths)
{
    $adminEmail = determineAdminEmail($supportType);

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.support@dfgh.edu.ng'; // Adjust to your SMTP host
        $mail->SMTPAuth = true;
        $mail->Username = 'support@dfghj.edu.ng'; // SMTP username
        $mail->Password = '######wP'; // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->addCC("peterotakhor@aauekpoma.edu.ng");

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
        return ["success" => true];
    } catch (Exception $e) {
        return [
            "success" => false,
            "error" => "Support request submitted, but failed to send email. Mailer Error: {$mail->ErrorInfo}"
        ];
    }
}

function determineAdminEmail($supportType)
{
    switch ($supportType) {
        case "ICT":
            return "info@aauekpoma.edu.ng";
        case "Registrar":
            return "registrar@aauekpoma.edu.ng";
        case "Exams_and_Records":
            return "examsrecords@aauekpoma.edu.ng";
        default:
            return "info@aauekpoma.edu.ng";
    }
}
