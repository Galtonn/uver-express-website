<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/PHPMailer/PHPMailer.php';
require __DIR__ . '/PHPMailer/SMTP.php';
require __DIR__ . '/PHPMailer/Exception.php';

// reCAPTCHA configuration
$recaptcha_secret = "6Ler5k0rAAAAAKGmGXoeWKrAAciA5meck5s8PMAs"; // Replace with your secret key
$recaptcha_response = $_POST['recaptcha_response'];

// Verify reCAPTCHA
$verify_url = "https://www.google.com/recaptcha/api/siteverify";
$data = [
    'secret' => $recaptcha_secret,
    'response' => $recaptcha_response,
    'remoteip' => $_SERVER['REMOTE_ADDR']
];

$options = [
    'http' => [
        'header' => "Content-type: application/x-www-form-urlencoded\r\n",
        'method' => 'POST',
        'content' => http_build_query($data)
    ]
];

$context = stream_context_create($options);
$verify_response = file_get_contents($verify_url, false, $context);
$captcha_success = json_decode($verify_response);

if ($captcha_success->success == false) {
    // reCAPTCHA verification failed
    header("Location: contact.html?status=error");
    exit();
}

// If score is less than 0.5, consider it spam
if ($captcha_success->score < 0.5) {
    header("Location: contact.html?status=error");
    exit();
}

// Get form data
$name = $_POST['name'];
$email = $_POST['email'];
$subject = $_POST['subject'];
$message = $_POST['message'];

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: contact.html?status=error");
    exit();
}

// Sanitize inputs
$name = filter_var($name, FILTER_SANITIZE_STRING);
$subject = filter_var($subject, FILTER_SANITIZE_STRING);
$message = filter_var($message, FILTER_SANITIZE_STRING);

// Recipient email (where you want to receive messages)
$to = 'info@uverexpress.com';

// Create a new PHPMailer instance
$mail = new PHPMailer(true);

try {
    //Server settings
    $mail->isSMTP();
    $mail->Host       = 'smtp-mail.outlook.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'info@uverexpress.com'; // your Outlook/Office 365 email
    $mail->Password   = 'Uver!1105'; // your Outlook/Office 365 password
    $mail->Port       = 587;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->SMTPKeepAlive = true;

    //Recipients
    $mail->setFrom('info@uverexpress.com', 'UverExpress Contact Form'); // Send from the authenticated email
    $mail->addAddress($to);
    $mail->addReplyTo($email, $name);
    $mail->addCustomHeader('X-PMFlags', '1');

    // Content
    $mail->isHTML(false);
    $mail->Subject = 'New Email Query';
    $mail->Body    = "User Name: $name\nUser Email: $email\nSubject: $subject\nMessage: $message";

    $mail->send();
    header("Location: contact.html?status=success");
    exit();
} catch (Exception $e) {
    error_log('Mailer Error: ' . $mail->ErrorInfo);
    header("Location: contact.html?status=error");
    exit();
}
?>