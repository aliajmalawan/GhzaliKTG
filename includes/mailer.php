<?php
/**
 * Mailer - sends site email through authenticated SMTP (no external libraries).
 *
 * Credentials are NEVER stored in code. They are read from, in order:
 *   1. Real environment variables (cPanel "Setup PHP/Environment", server config)
 *   2. includes/mail.env  (copy of includes/mail.env.example; blocked from web + git-ignored)
 *
 * Variables: MAIL_HOST, MAIL_PORT, MAIL_ENCRYPTION (ssl|tls|none), MAIL_USERNAME,
 *            MAIL_PASSWORD, MAIL_FROM_ADDRESS, MAIL_FROM_NAME, MAIL_ADMIN_TO
 *
 * If MAIL_HOST is empty, falls back to PHP mail() (works only if the server has
 * a working local mail transport).
 */

function mail_env($key, $default = '') {
    static $file = null;
    if ($file === null) {
        $file = [];
        $path = __DIR__ . '/mail.env';
        if (is_readable($path)) {
            foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);
                if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;
                list($k, $v) = explode('=', $line, 2);
                $file[trim($k)] = trim(trim($v), "\"'");
            }
        }
    }
    $v = getenv($key);
    if ($v !== false && $v !== '') return $v;
    return isset($file[$key]) && $file[$key] !== '' ? $file[$key] : $default;
}

function mail_log($msg) {
    @file_put_contents(__DIR__ . '/mail.log', '[' . date('Y-m-d H:i:s') . "] $msg\n", FILE_APPEND);
}

function mail_clean($s) { // prevent header injection
    return trim(str_replace(["\r", "\n", "%0a", "%0d"], ' ', (string)$s));
}

function mail_admin_address() {
    return mail_env('MAIL_ADMIN_TO', defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'info@ghazaliktg.com');
}

/**
 * Send an HTML email. Returns true on success, false on failure (reason logged to includes/mail.log).
 */
function send_site_mail($to, $subject, $html, $replyTo = '') {
    $to = mail_clean($to);
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) { mail_log("Invalid recipient: $to"); return false; }

    $fromAddr = mail_env('MAIL_FROM_ADDRESS', mail_env('MAIL_USERNAME', 'info@ghazaliktg.com'));
    $fromName = mail_clean(mail_env('MAIL_FROM_NAME', defined('SITE_NAME') ? trim(SITE_NAME) : 'Ghazali School'));
    $subject  = mail_clean($subject);
    $replyTo  = mail_clean($replyTo);

    $headers  = "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <$fromAddr>\r\n";
    if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) $headers .= "Reply-To: $replyTo\r\n";

    $host = mail_env('MAIL_HOST');
    if ($host === '') {
        $ok = @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $html, $headers);
        if (!$ok) mail_log("mail() failed sending to $to (MAIL_HOST not configured)");
        return $ok;
    }

    return smtp_send($host, (int)mail_env('MAIL_PORT', '465'), strtolower(mail_env('MAIL_ENCRYPTION', 'ssl')),
        mail_env('MAIL_USERNAME'), mail_env('MAIL_PASSWORD'), $fromAddr, $to, $subject, $headers, $html);
}

function smtp_send($host, $port, $enc, $user, $pass, $from, $to, $subject, $headers, $body) {
    $err = ''; $errno = 0;
    $target = ($enc === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
    $fp = @stream_socket_client($target, $errno, $err, 15);
    if (!$fp) { mail_log("SMTP connect failed ($target): $err"); return false; }
    stream_set_timeout($fp, 15);

    $read = function () use ($fp) {
        $out = '';
        while (($l = fgets($fp, 515)) !== false) { $out .= $l; if (strlen($l) < 4 || $l[3] === ' ') break; }
        return $out;
    };
    $cmd = function ($c, $expect) use ($fp, $read) {
        if ($c !== null) fwrite($fp, $c . "\r\n");
        $r = $read();
        return [substr($r, 0, 3) == $expect || in_array(substr($r, 0, 3), (array)$expect), trim($r)];
    };
    $fail = function ($step, $resp) use ($fp) { mail_log("SMTP $step failed: $resp"); @fwrite($fp, "QUIT\r\n"); @fclose($fp); return false; };

    list($ok, $r) = $cmd(null, '220');                       if (!$ok) return $fail('greeting', $r);
    $ehlo = $_SERVER['SERVER_NAME'] ?? 'localhost';
    list($ok, $r) = $cmd("EHLO $ehlo", '250');               if (!$ok) return $fail('EHLO', $r);
    if ($enc === 'tls') {
        list($ok, $r) = $cmd('STARTTLS', '220');             if (!$ok) return $fail('STARTTLS', $r);
        if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) return $fail('TLS handshake', 'crypto failed');
        list($ok, $r) = $cmd("EHLO $ehlo", '250');           if (!$ok) return $fail('EHLO2', $r);
    }
    if ($user !== '') {
        list($ok, $r) = $cmd('AUTH LOGIN', '334');           if (!$ok) return $fail('AUTH', $r);
        list($ok, $r) = $cmd(base64_encode($user), '334');   if (!$ok) return $fail('AUTH user', $r);
        list($ok, $r) = $cmd(base64_encode($pass), '235');   if (!$ok) return $fail('AUTH password (check credentials)', $r);
    }
    list($ok, $r) = $cmd("MAIL FROM:<$from>", '250');        if (!$ok) return $fail('MAIL FROM', $r);
    list($ok, $r) = $cmd("RCPT TO:<$to>", ['250', '251']);   if (!$ok) return $fail('RCPT TO', $r);
    list($ok, $r) = $cmd('DATA', '354');                     if (!$ok) return $fail('DATA', $r);

    $msg  = "Date: " . date('r') . "\r\n";
    $msg .= "To: <$to>\r\n";
    $msg .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
    $msg .= "Message-ID: <" . bin2hex(random_bytes(8)) . '@' . (substr(strrchr($from, '@'), 1) ?: 'localhost') . ">\r\n";
    $msg .= $headers . "Content-Transfer-Encoding: base64\r\n\r\n";
    $msg .= chunk_split(base64_encode($body));
    list($ok, $r) = $cmd($msg . "\r\n.", '250');             if (!$ok) return $fail('message body', $r);
    $cmd('QUIT', '221');
    fclose($fp);
    return true;
}
