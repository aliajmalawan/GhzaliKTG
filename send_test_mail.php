<?php
// Command-line only:  php send_test_mail.php recipient@example.com
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('SITE_NAME', 'Ghazali School & College Katlang');
require __DIR__ . '/includes/mailer.php';
$to = $argv[1] ?? mail_admin_address();
echo "MAIL_HOST: " . (mail_env('MAIL_HOST') ?: '(not set - falling back to PHP mail())') . "\n";
$ok = send_site_mail($to, 'SMTP test - ' . SITE_NAME, '<p>If you can read this, website email is working.</p>', mail_admin_address());
echo $ok ? "SENT to $to\n" : "FAILED - see includes/mail.log\n";
exit($ok ? 0 : 1);
