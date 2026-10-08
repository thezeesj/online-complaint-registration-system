<?php
// Load dependencies
require_once '../PHPMailer-PHPMailer-3cd2a2a/src/Exception.php';
require_once '../PHPMailer-PHPMailer-3cd2a2a/src/PHPMailer.php';
require_once '../PHPMailer-PHPMailer-3cd2a2a/src/SMTP.php';
require_once '../config/email_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    echo "Connecting to " . SMTP_HOST . "...\n";
    
    //Server settings
    $mail->SMTPDebug = 2;                      // Enable verbose debug output
    $mail->isSMTP();                                            
    $mail->Host       = SMTP_HOST;                     
    $mail->SMTPAuth   = true;                                   
    $mail->Username   = SMTP_USER;                     
    $mail->Password   = SMTP_PASS;                               
    $mail->SMTPSecure = (SMTP_SECURE === 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;            
    $mail->Port       = SMTP_PORT;                                    

    //Recipients
    $mail->setFrom(FROM_EMAIL, FROM_NAME);
    $mail->addAddress(SMTP_USER, 'Test Recipient'); // Send to self

    //Content
    $mail->isHTML(true);                                  
    $mail->Subject = 'Test Email from Complaint System';
    $mail->Body    = 'This is a test email to verify that your SMTP configuration is working correctly.';
    $mail->AltBody = 'This is a test email to verify that your SMTP configuration is working correctly.';

    $mail->send();
    echo "\n------------------------------------------------\n";
    echo "SUCCESS: Email sent successfully to " . SMTP_USER . "\n";
    echo "------------------------------------------------\n";
} catch (Exception $e) {
    echo "\n------------------------------------------------\n";
    echo "ERROR: Message could not be sent.\n";
    echo "Mailer Error: {$mail->ErrorInfo}\n";
    echo "------------------------------------------------\n";
}
?>
