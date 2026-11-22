<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/auth/includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['state' => 'down', 'ms' => null, 'note' => 'closed']);
    exit;
}

if (auth_user() === null) {
    http_response_code(401);
    echo json_encode(['state' => 'down', 'ms' => null, 'note' => 'closed']);
    exit;
}

$raw = file_get_contents('php://input');
$body = json_decode(is_string($raw) ? $raw : '', true);
$host = is_array($body) ? strtolower(trim((string) ($body['host'] ?? ''))) : '';
$port = is_array($body) ? (int) ($body['port'] ?? 0) : 0;
$host = trim($host, '[]');

$ip = filter_var($host, FILTER_VALIDATE_IP);
$name = (bool) preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)*[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/', $host);
if (($ip === false && !$name) || $port < 1 || $port > 65535) {
    echo json_encode(['state' => 'down', 'ms' => null, 'note' => 'closed']);
    exit;
}

$target = 'tcp://' . $host . ':' . $port;
if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
    $target = 'tcp://[' . $host . ']:' . $port;
}

$errno = 0;
$errstr = '';
$started = microtime(true);
$socket = @stream_socket_client($target, $errno, $errstr, 5, STREAM_CLIENT_CONNECT);
$ms = (int) round((microtime(true) - $started) * 1000);

if (is_resource($socket)) {
    fclose($socket);
    echo json_encode(['state' => 'up', 'ms' => $ms, 'note' => '']);
    exit;
}

echo json_encode(['state' => 'down', 'ms' => $ms, 'note' => 'closed']);
