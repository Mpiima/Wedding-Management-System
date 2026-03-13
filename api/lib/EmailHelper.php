<?php
/**
 * Email helper: load config from email_config and return a configured PHPMailer instance.
 * Requires Composer autoload (vendor/autoload.php) and phpmailer/phpmailer.
 */

if (!function_exists('wmis_get_email_config')) {
    function wmis_get_email_config(PDO $dbh) {
        try {
            $stmt = $dbh->query("SELECT id, smtp_host, smtp_port, smtp_username, smtp_password, encryption, from_email, from_name, bcc_emails FROM email_config ORDER BY id ASC LIMIT 1");
            $row = $stmt ? $stmt->fetch(PDO::FETCH_OBJ) : null;
            return $row;
        } catch (PDOException $e) {
            return null;
        }
    }
}

if (!function_exists('wmis_create_mailer')) {
    /**
     * @return \PHPMailer\PHPMailer\PHPMailer|null
     */
    function wmis_create_mailer(PDO $dbh) {
        $autoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
        if (!is_file($autoload)) {
            return null;
        }
        require_once $autoload;

        $config = wmis_get_email_config($dbh);
        if (!$config || trim($config->smtp_host) === '') {
            return null;
        }

        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = trim($config->smtp_host);
            $mail->Port = (int) $config->smtp_port;
            $mail->SMTPAuth = !empty(trim($config->smtp_username ?? ''));
            if ($mail->SMTPAuth) {
                $mail->Username = trim($config->smtp_username);
                $mail->Password = $config->smtp_password ?? '';
            }
            $enc = strtolower(trim($config->encryption ?? 'tls'));
            if ($enc === 'ssl') {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($enc === 'none') {
                $mail->SMTPSecure = false;
            } else {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            }
            $mail->CharSet = \PHPMailer\PHPMailer\PHPMailer::CHARSET_UTF8;
            $mail->setFrom(trim($config->from_email), $config->from_name ?? '');
            return $mail;
        } catch (Exception $e) {
            return null;
        }
    }
}

if (!function_exists('wmis_get_bcc_emails')) {
    /** @return string[] */
    function wmis_get_bcc_emails(PDO $dbh) {
        $config = wmis_get_email_config($dbh);
        if (!$config || empty(trim($config->bcc_emails ?? ''))) {
            return [];
        }
        $emails = array_map('trim', explode(',', $config->bcc_emails));
        return array_filter($emails, function ($e) {
            return $e !== '' && filter_var($e, FILTER_VALIDATE_EMAIL);
        });
    }
}

if (!function_exists('wmis_send_email')) {
    /**
     * Send one email. Optionally add BCC from config and/or $options['bcc'].
     * @param PDO $dbh
     * @param string|string[] $to Single email or array of emails
     * @param string $subject
     * @param string $bodyHtml
     * @param array $options ['bcc' => string[], 'is_html' => bool]
     * @return bool
     */
    function wmis_send_email(PDO $dbh, $to, $subject, $bodyHtml, array $options = []) {
        $mail = wmis_create_mailer($dbh);
        if (!$mail) return false;
        $toList = is_array($to) ? $to : [$to];
        foreach ($toList as $addr) {
            $addr = trim($addr);
            if ($addr !== '' && filter_var($addr, FILTER_VALIDATE_EMAIL)) {
                $mail->addAddress($addr);
            }
        }
        if (count($mail->getToAddresses()) === 0) return false;
        $bcc = array_merge(wmis_get_bcc_emails($dbh), $options['bcc'] ?? []);
        foreach (array_unique($bcc) as $addr) {
            $addr = trim($addr);
            if ($addr !== '' && filter_var($addr, FILTER_VALIDATE_EMAIL)) {
                $mail->addBCC($addr);
            }
        }
        $mail->Subject = $subject;
        $mail->isHTML(isset($options['is_html']) ? (bool)$options['is_html'] : true);
        $mail->Body = $bodyHtml;
        if (empty($options['is_html'])) {
            $mail->AltBody = strip_tags(preg_replace('/<br\s*\/?>/i', "\n", $bodyHtml));
        }
        try {
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('wmis_send_email: ' . $e->getMessage());
            return false;
        }
    }
}
