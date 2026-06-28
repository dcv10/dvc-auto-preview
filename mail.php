<?php
/**
 * DVC Auto — quote-request form handler.
 * Emails submissions to the shop. Works with the AJAX fetch in index.html
 * and degrades to a normal POST (with redirect) if JavaScript is off.
 */

$TO      = 'info@dvcauto.co.za';
$SUBJECT = 'New quote request — DVC Auto website';

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.html');
    exit;
}

$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']);

function fail($isAjax, $msg = 'Could not send your message.') {
    if ($isAjax) { http_response_code(400); echo $msg; }
    else { header('Location: index.html?sent=0'); }
    exit;
}
function done($isAjax) {
    if ($isAjax) { http_response_code(200); echo 'OK'; }
    else { header('Location: index.html?sent=1#contact'); }
    exit;
}

// Honeypot: real users never fill the hidden "company" field
if (!empty($_POST['company'])) { done($isAjax); } // silently accept-and-drop bots

$name    = trim($_POST['name']    ?? '');
$phone   = trim($_POST['phone']   ?? '');
$email   = trim($_POST['email']   ?? '');
$service = trim($_POST['service'] ?? '');
$message = trim($_POST['message'] ?? '');

if ($name === '' || $phone === '' || $message === '') {
    fail($isAjax, 'Please fill in your name, phone and message.');
}
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fail($isAjax, 'Please enter a valid email address.');
}

// Strip header-injection attempts from anything used in headers
$clean = fn($v) => str_replace(["\r", "\n", "%0a", "%0d"], '', $v);
$name  = $clean($name);
$email = $clean($email);

$body  = "New quote request from the DVC Auto website\n";
$body .= "----------------------------------------\n";
$body .= "Name:    {$name}\n";
$body .= "Phone:   {$phone}\n";
$body .= "Email:   " . ($email !== '' ? $email : '(not provided)') . "\n";
$body .= "Service: {$service}\n";
$body .= "----------------------------------------\n";
$body .= "Message:\n{$message}\n";

$headers  = "From: DVC Auto Website <info@dvcauto.co.za>\r\n";
if ($email !== '') { $headers .= "Reply-To: {$name} <{$email}>\r\n"; }
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

if (@mail($TO, $SUBJECT, $body, $headers)) {
    done($isAjax);
} else {
    fail($isAjax, 'Mail server error. Please WhatsApp or call us instead.');
}
