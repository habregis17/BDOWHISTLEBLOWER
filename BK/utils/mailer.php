<?php
function sendMail($to, $subject, $message) {
    $headers = "From: noreply@yourdomain.com\r\nContent-Type: text/plain; charset=UTF-8";
    mail($to, $subject, $message, $headers);
}
?>