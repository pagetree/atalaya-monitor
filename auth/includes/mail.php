<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

function auth_send_code_email(string $toEmail, string $code): void
{
    $cfg = auth_config();
    $accountId = $cfg['cf_account_id'];
    $token = $cfg['cf_api_token'];
    $from = $cfg['cf_from_email'];

    if ($accountId === '' || $token === '' || $from === '') {
        throw new RuntimeException('Cloudflare Email Service is not configured.');
    }

    $message = auth_code_message($toEmail, $code, (int) $cfg['code_ttl_minutes'], auth_public_url($cfg));

    $payload = [
        'to' => $toEmail,
        'from' => $from,
        'subject' => $message['subject'],
        'text' => $message['text'],
        'html' => $message['html'],
    ];

    $url = 'https://api.cloudflare.com/client/v4/accounts/' . rawurlencode($accountId) . '/email/sending/send';

    $ch = curl_init($url);
    if ($ch === false) {
        throw new RuntimeException('Could not start the email request.');
    }

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_THROW_ON_ERROR),
        CURLOPT_TIMEOUT => 20,
    ]);

    $body = curl_exec($ch);
    $errno = curl_errno($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($errno !== 0 || $body === false) {
        throw new RuntimeException('Could not reach Cloudflare Email Service.');
    }

    $decoded = json_decode($body, true);
    $ok = is_array($decoded) && !empty($decoded['success']);
    if (!$ok || $status < 200 || $status >= 300) {
        throw new RuntimeException('Cloudflare Email Service rejected the send.');
    }
}

function auth_public_url(array $cfg): string
{
    $app = rtrim((string) ($cfg['app_url'] ?? ''), '/');
    if ($app !== '' && !preg_match('#://(localhost|127\.0\.0\.1)([:/]|$)#i', $app)) {
        return $app;
    }

    $from = (string) ($cfg['cf_from_email'] ?? '');
    $host = substr(strrchr($from, '@') ?: '', 1);
    if ($host === '') {
        return $app;
    }

    return 'https://' . $host;
}

function auth_code_message(string $toEmail, string $code, int $minutes, string $appUrl): array
{
    $safeCode = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
    $safeEmail = htmlspecialchars($toEmail, ENT_QUOTES, 'UTF-8');
    $window = $minutes === 1 ? '1 minute' : $minutes . ' minutes';
    $home = $appUrl !== '' ? $appUrl . '/' : '';
    $logoUrl = $appUrl !== '' ? $appUrl . '/assets/img/atalaya-mark.png' : '';
    $safeHome = htmlspecialchars($home, ENT_QUOTES, 'UTF-8');
    $safeLogo = htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8');

    $logo = $logoUrl === ''
        ? ''
        : '<td width="48" style="padding:0 14px 0 0;vertical-align:middle;">'
            . '<img src="' . $safeLogo . '" width="48" height="42" alt="Atalaya" style="display:block;border:0;outline:none;text-decoration:none;width:48px;height:42px;">'
            . '</td>';

    $open = $home === ''
        ? ''
        : '<p style="margin:14px 0 0;font-family:\'Segoe UI\',Helvetica,Arial,sans-serif;font-size:14px;line-height:1.5;">'
            . '<a href="' . $safeHome . '" style="color:#0033a0;text-decoration:underline;">Open Atalaya</a>'
            . '</p>';

    $html = '<!DOCTYPE html>'
        . '<html lang="en">'
        . '<head>'
        . '<meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<meta name="color-scheme" content="light">'
        . '<meta name="supported-color-schemes" content="light">'
        . '<title>Your Atalaya verification code</title>'
        . '</head>'
        . '<body style="margin:0;padding:0;background-color:#f3f3f1;">'
        . '<div style="display:none;max-height:0;overflow:hidden;mso-hide:all;">Your code is ' . $safeCode . '. It is valid for ' . $window . '.'
        . str_repeat('&nbsp;&zwnj;', 40)
        . '</div>'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f3f1;border-collapse:collapse;">'
        . '<tr><td align="center" style="padding:32px 16px;">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;background-color:#ffffff;border-collapse:collapse;">'
        . '<tr><td height="4" style="height:4px;background-color:#c2410c;font-size:0;line-height:0;">&nbsp;</td></tr>'
        . '<tr><td style="padding:32px 36px 28px;">'
        . '<table role="presentation" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">'
        . '<tr>' . $logo
        . '<td style="vertical-align:middle;font-family:\'Segoe UI\',Helvetica,Arial,sans-serif;font-size:15px;font-weight:700;letter-spacing:0.22em;color:#3F3F69;">ATALAYA</td>'
        . '</tr></table>'
        . '<p style="margin:28px 0 0;font-family:\'Segoe UI\',Helvetica,Arial,sans-serif;font-size:12px;font-weight:700;letter-spacing:0.16em;text-transform:uppercase;color:#65AFDB;">Verification</p>'
        . '<h1 style="margin:8px 0 0;font-family:\'Segoe UI\',Helvetica,Arial,sans-serif;font-size:28px;line-height:1.2;font-weight:700;color:#141414;">Enter this code</h1>'
        . '<p style="margin:12px 0 0;font-family:\'Segoe UI\',Helvetica,Arial,sans-serif;font-size:16px;line-height:1.5;color:#141414;">Use it on the Atalaya sign in screen. It is valid for ' . $window . '.</p>'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:22px;border-collapse:collapse;background-color:#f6f6f4;border:1px solid #e4e4e1;">'
        . '<tr><td align="center" style="padding:22px 16px;font-family:Consolas,\'Courier New\',monospace;font-size:32px;line-height:1.2;font-weight:700;letter-spacing:6px;color:#141414;">' . $safeCode . '</td></tr>'
        . '</table>'
        . '<p style="margin:10px 0 0;font-family:\'Segoe UI\',Helvetica,Arial,sans-serif;font-size:13px;line-height:1.5;color:#5c5c5c;">Six digits. No spaces.</p>'
        . '<p style="margin:22px 0 0;padding-top:18px;border-top:1px solid #e4e4e1;font-family:\'Segoe UI\',Helvetica,Arial,sans-serif;font-size:14px;line-height:1.5;color:#5c5c5c;">If you did not ask to sign in, you can ignore this email. Nothing changes on your account, and the code stops working when the time is up.</p>'
        . '</td></tr>'
        . '<tr><td style="padding:8px 36px 28px;font-family:\'Segoe UI\',Helvetica,Arial,sans-serif;font-size:13px;line-height:1.5;color:#6b6b6b;">'
        . 'Atalaya<br>Free status monitor for a site or an API.<br>Sent to ' . $safeEmail . ' because a sign in was requested for this address.'
        . $open
        . '</td></tr>'
        . '</table>'
        . '</td></tr></table>'
        . '</body></html>';

    $text = "Enter this code\n\n"
        . $code . "\n\n"
        . "Use it on the Atalaya sign in screen. It is valid for {$window}.\n"
        . "Six digits. No spaces.\n\n"
        . "If you did not ask to sign in, you can ignore this email. Nothing changes on your account, and the code stops working when the time is up.\n\n"
        . "Atalaya\n"
        . "Free status monitor for a site or an API.\n"
        . "Sent to {$toEmail} because a sign in was requested for this address.\n";

    if ($home !== '') {
        $text .= "\n" . $home . "\n";
    }

    return [
        'subject' => 'Your Atalaya verification code',
        'text' => $text,
        'html' => $html,
    ];
}
