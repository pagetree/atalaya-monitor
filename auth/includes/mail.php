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

    $subject = 'Your Atalaya verification code';
    $text = "Your Atalaya verification code is {$code}.\n\nThis code is valid for 30 minutes.\nIf you did not request this, you can ignore this email.";
    $html = '<p>Your Atalaya verification code is <strong>' . htmlspecialchars($code, ENT_QUOTES, 'UTF-8') . '</strong>.</p>'
        . '<p>This code is valid for 30 minutes.</p>'
        . '<p>If you did not request this, you can ignore this email.</p>';

    $payload = [
        'to' => $toEmail,
        'from' => $from,
        'subject' => $subject,
        'text' => $text,
        'html' => $html,
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
