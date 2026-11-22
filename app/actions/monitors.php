<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/auth/includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

const BOARD_INTERVALS = [60000, 300000, 900000, 1800000, 3600000];
const BOARD_REGIONS = ['frankfurt', 'capetown', 'singapore', 'sydney'];
const BOARD_KINDS = ['website', 'api', 'keyword', 'port'];

$user = auth_user();
if ($user === null) {
    board_out(['ok' => false, 'error' => 'Sign in again.'], 401);
}

try {
    $db = auth_db();
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        board_out(board_list($db, $user['id']));
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        board_out(['ok' => false, 'error' => 'That request is not supported.'], 405);
    }
    $raw = file_get_contents('php://input');
    $body = json_decode(is_string($raw) ? $raw : '', true);
    if (!is_array($body)) {
        board_out(['ok' => false, 'error' => 'The monitor could not be read.'], 400);
    }
    $action = (string) ($body['action'] ?? '');
    if ($action === 'create') {
        board_create($db, $user['id'], $body);
    } elseif ($action === 'update') {
        board_update($db, $user['id'], $body);
    } elseif ($action === 'pause') {
        board_pause($db, $user['id'], $body);
    } elseif ($action === 'delete') {
        board_delete($db, $user['id'], $body);
    } elseif ($action === 'delete-all') {
        $stmt = $db->prepare('DELETE FROM monitors WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $user['id']]);
        board_out(['ok' => true]);
    } else {
        board_out(['ok' => false, 'error' => 'That request is not supported.'], 400);
    }
} catch (PDOException $error) {
    if ($error->getCode() === '42P01') {
        board_out(['ok' => false, 'error' => 'The monitors table is not in the database yet.'], 500);
    }
    board_out(['ok' => false, 'error' => 'The monitor could not be saved.'], 500);
}

function board_out(array $payload, int $code = 200): never
{
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

function board_has_exclude(PDO $db): bool
{
    static $known = null;
    if ($known !== null) {
        return $known;
    }
    $stmt = $db->query("SELECT 1 FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'monitors' AND column_name = 'exclude'");
    $known = (bool) $stmt->fetchColumn();
    return $known;
}

function board_list(PDO $db, string $userId): array
{
    $excludeSql = board_has_exclude($db) ? 'exclude::text AS exclude' : "'{}'::text AS exclude";
    $stmt = $db->prepare(
        'SELECT id::text AS id, name, url, kind, phrase, port, interval_ms, regions::text AS regions, notify, paused, '
        . $excludeSql
        . ' FROM monitors WHERE user_id = :user_id ORDER BY created_at ASC'
    );
    $stmt->execute(['user_id' => $userId]);
    $rows = $stmt->fetchAll();

    $latest = [];
    $samples = [];
    $counts = [];
    $log = [];

    if ($rows) {
        $latestStmt = $db->prepare(
            'SELECT DISTINCT ON (c.monitor_id, c.region)
                c.monitor_id::text AS monitor_id, c.region, c.state, c.http_code, c.ms, c.note,
                (EXTRACT(EPOCH FROM c.checked_at) * 1000)::bigint AS at_ms
             FROM check_results c
             INNER JOIN monitors m ON m.id = c.monitor_id
             WHERE m.user_id = :user_id
             ORDER BY c.monitor_id, c.region, c.checked_at DESC'
        );
        $latestStmt->execute(['user_id' => $userId]);
        foreach ($latestStmt->fetchAll() as $row) {
            $latest[$row['monitor_id']][] = $row;
        }

        $sampleStmt = $db->prepare(
            'SELECT DISTINCT ON (c.monitor_id, date_trunc(\'hour\', c.checked_at))
                c.monitor_id::text AS monitor_id, c.state, c.ms,
                (EXTRACT(EPOCH FROM date_trunc(\'hour\', c.checked_at)) * 1000)::bigint AS at_ms
             FROM check_results c
             INNER JOIN monitors m ON m.id = c.monitor_id
             WHERE m.user_id = :user_id
               AND c.checked_at > NOW() - INTERVAL \'30 days\'
             ORDER BY c.monitor_id, date_trunc(\'hour\', c.checked_at),
               CASE
                 WHEN c.state = \'down\' THEN 3
                 WHEN c.state = \'answered\' OR (c.state = \'up\' AND c.ms >= 2000) THEN 2
                 WHEN c.state = \'up\' THEN 1
                 ELSE 0
               END DESC'
        );
        $sampleStmt->execute(['user_id' => $userId]);
        foreach ($sampleStmt->fetchAll() as $row) {
            $samples[$row['monitor_id']][] = [
                'at' => (int) $row['at_ms'],
                'state' => $row['state'],
                'ms' => $row['ms'] === null ? null : (int) $row['ms'],
            ];
        }

        $countStmt = $db->prepare(
            'SELECT c.monitor_id::text AS monitor_id,
                COUNT(*)::int AS total,
                COUNT(*) FILTER (WHERE c.state = \'down\')::int AS down
             FROM check_results c
             INNER JOIN monitors m ON m.id = c.monitor_id
             WHERE m.user_id = :user_id
               AND c.checked_at > NOW() - INTERVAL \'30 days\'
             GROUP BY c.monitor_id'
        );
        $countStmt->execute(['user_id' => $userId]);
        foreach ($countStmt->fetchAll() as $row) {
            $counts[$row['monitor_id']] = [
                'total' => (int) $row['total'],
                'down' => (int) $row['down'],
            ];
        }

        $logStmt = $db->prepare(
            'SELECT m.name, m.url, c.state, c.http_code, c.ms, c.note, c.region,
                (EXTRACT(EPOCH FROM c.checked_at) * 1000)::bigint AS at_ms
             FROM check_results c
             INNER JOIN monitors m ON m.id = c.monitor_id
             WHERE m.user_id = :user_id
             ORDER BY c.checked_at DESC
             LIMIT 200'
        );
        $logStmt->execute(['user_id' => $userId]);
        foreach ($logStmt->fetchAll() as $row) {
            $log[] = [
                'at' => (int) $row['at_ms'],
                'name' => $row['name'],
                'url' => $row['url'],
                'state' => $row['state'],
                'code' => $row['http_code'] === null ? null : (int) $row['http_code'],
                'ms' => $row['ms'] === null ? null : (int) $row['ms'],
                'note' => (string) $row['note'],
                'region' => (string) $row['region'],
            ];
        }
    }

    $monitors = [];
    foreach ($rows as $row) {
        $id = (string) $row['id'];
        $monitors[] = [
            'id' => $id,
            'name' => $row['name'],
            'url' => $row['url'],
            'kind' => $row['kind'],
            'phrase' => $row['phrase'],
            'exclude' => array_map('intval', board_pg_array($row['exclude'])),
            'port' => (int) $row['port'],
            'interval' => (int) $row['interval_ms'],
            'regions' => board_pg_array($row['regions']),
            'notify' => 'email',
            'paused' => board_flag($row['paused']),
            'last' => board_worst($latest[$id] ?? []),
            'samples' => $samples[$id] ?? [],
            'checks' => $counts[$id] ?? null,
        ];
    }

    return ['ok' => true, 'monitors' => $monitors, 'log' => $log];
}

function board_worst(array $rows): ?array
{
    $best = null;
    $rank = -1;
    foreach ($rows as $row) {
        $ms = $row['ms'] === null ? null : (int) $row['ms'];
        $state = (string) $row['state'];
        $score = 0;
        if ($state === 'down') {
            $score = 3;
        } elseif ($state === 'answered' || ($state === 'up' && $ms !== null && $ms >= 2000)) {
            $score = 2;
        } elseif ($state === 'up') {
            $score = 1;
        }
        if ($score > $rank) {
            $rank = $score;
            $best = [
                'at' => (int) $row['at_ms'],
                'state' => $state,
                'code' => $row['http_code'] === null ? null : (int) $row['http_code'],
                'ms' => $ms,
                'note' => (string) $row['note'],
                'region' => (string) $row['region'],
            ];
        }
    }
    return $best;
}

function board_create(PDO $db, string $userId, array $body): never
{
    $count = $db->prepare('SELECT COUNT(*) FROM monitors WHERE user_id = :user_id');
    $count->execute(['user_id' => $userId]);
    if ((int) $count->fetchColumn() >= 50) {
        board_out(['ok' => false, 'error' => 'You can watch up to 50 monitors.'], 400);
    }

    $fields = board_fields($body);
    $hasExclude = board_has_exclude($db);
    if ($fields['exclude'] && !$hasExclude) {
        board_out(['ok' => false, 'error' => 'Excluded codes need the latest database update.'], 409);
    }

    if ($hasExclude) {
        $sql = 'INSERT INTO monitors (user_id, name, url, kind, phrase, port, interval_ms, regions, notify, exclude)
                VALUES (:user_id, :name, :url, :kind, :phrase, :port, :interval_ms, CAST(:regions AS text[]), :notify, CAST(:exclude AS integer[]))';
        $params = [
            'user_id' => $userId,
            'name' => $fields['name'],
            'url' => $fields['url'],
            'kind' => $fields['kind'],
            'phrase' => $fields['phrase'],
            'port' => $fields['port'],
            'interval_ms' => $fields['interval'],
            'regions' => board_pg_text($fields['regions']),
            'notify' => 'email',
            'exclude' => board_pg_ints($fields['exclude']),
        ];
    } else {
        $sql = 'INSERT INTO monitors (user_id, name, url, kind, phrase, port, interval_ms, regions, notify)
                VALUES (:user_id, :name, :url, :kind, :phrase, :port, :interval_ms, CAST(:regions AS text[]), :notify)';
        $params = [
            'user_id' => $userId,
            'name' => $fields['name'],
            'url' => $fields['url'],
            'kind' => $fields['kind'],
            'phrase' => $fields['phrase'],
            'port' => $fields['port'],
            'interval_ms' => $fields['interval'],
            'regions' => board_pg_text($fields['regions']),
            'notify' => 'email',
        ];
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    board_out(['ok' => true]);
}

function board_update(PDO $db, string $userId, array $body): never
{
    $id = board_id($body);
    $name = board_clip((string) ($body['name'] ?? ''), 80);
    $interval = (int) ($body['interval'] ?? 0);
    if (!in_array($interval, BOARD_INTERVALS, true)) {
        board_out(['ok' => false, 'error' => 'Pick how often to check this address.'], 400);
    }
    $current = board_owned($db, $userId, $id);
    if ($name === '') {
        $name = board_host($current['url']);
    }
    $stmt = $db->prepare(
        'UPDATE monitors SET name = :name, interval_ms = :interval_ms, updated_at = NOW()
         WHERE id = :id AND user_id = :user_id'
    );
    $stmt->execute([
        'name' => $name,
        'interval_ms' => $interval,
        'id' => $id,
        'user_id' => $userId,
    ]);
    board_out(['ok' => true]);
}

function board_pause(PDO $db, string $userId, array $body): never
{
    $id = board_id($body);
    board_owned($db, $userId, $id);
    $stmt = $db->prepare(
        'UPDATE monitors SET paused = NOT paused, updated_at = NOW() WHERE id = :id AND user_id = :user_id'
    );
    $stmt->execute(['id' => $id, 'user_id' => $userId]);
    board_out(['ok' => true]);
}

function board_delete(PDO $db, string $userId, array $body): never
{
    $id = board_id($body);
    $stmt = $db->prepare('DELETE FROM monitors WHERE id = :id AND user_id = :user_id');
    $stmt->execute(['id' => $id, 'user_id' => $userId]);
    if ($stmt->rowCount() < 1) {
        board_out(['ok' => false, 'error' => 'That monitor is not on your account.'], 404);
    }
    board_out(['ok' => true]);
}

function board_owned(PDO $db, string $userId, string $id): array
{
    $stmt = $db->prepare('SELECT url FROM monitors WHERE id = :id AND user_id = :user_id');
    $stmt->execute(['id' => $id, 'user_id' => $userId]);
    $row = $stmt->fetch();
    if (!$row) {
        board_out(['ok' => false, 'error' => 'That monitor is not on your account.'], 404);
    }
    return $row;
}

function board_id(array $body): string
{
    $id = strtolower(trim((string) ($body['id'] ?? '')));
    if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $id)) {
        board_out(['ok' => false, 'error' => 'That monitor is not on your account.'], 400);
    }
    return $id;
}

function board_fields(array $body): array
{
    $kind = (string) ($body['kind'] ?? 'website');
    if (!in_array($kind, BOARD_KINDS, true)) {
        $kind = 'website';
    }
    $interval = (int) ($body['interval'] ?? 0);
    if (!in_array($interval, BOARD_INTERVALS, true)) {
        board_out(['ok' => false, 'error' => 'Pick how often to check this address.'], 400);
    }

    $regions = [];
    $incoming = is_array($body['regions'] ?? null) ? $body['regions'] : [];
    foreach ($incoming as $region) {
        $region = (string) $region;
        if (in_array($region, BOARD_REGIONS, true) && !in_array($region, $regions, true)) {
            $regions[] = $region;
        }
    }
    if (!$regions) {
        board_out(['ok' => false, 'error' => 'Pick at least one region.'], 400);
    }

    $phrase = '';
    $port = 0;
    $exclude = [];
    $url = trim((string) ($body['url'] ?? ''));

    if ($kind === 'port') {
        $port = (int) ($body['port'] ?? 0);
        if ($port < 1 || $port > 65535) {
            board_out(['ok' => false, 'error' => 'Enter a port from 1 to 65535.'], 400);
        }
        $url = board_port_url($url, $port);
        if ($url === '') {
            board_out(['ok' => false, 'error' => 'That host cannot be checked.'], 400);
        }
    } else {
        $url = board_http_url($url);
        if ($url === '') {
            board_out(['ok' => false, 'error' => 'That address cannot be checked.'], 400);
        }
    }

    if ($kind === 'keyword') {
        $phrase = board_clip((string) ($body['phrase'] ?? ''), 120);
        if ($phrase === '') {
            board_out(['ok' => false, 'error' => 'Type the phrase this page must contain.'], 400);
        }
    }

    if ($kind === 'website' || $kind === 'api') {
        $exclude = board_exclude($body['exclude'] ?? []);
        if ($exclude === null) {
            board_out(['ok' => false, 'error' => 'Enter status codes from 100 to 599, separated by commas.'], 400);
        }
    }

    $name = board_clip((string) ($body['name'] ?? ''), 80);
    if ($name === '') {
        $name = board_host($url);
    }

    return [
        'name' => $name,
        'url' => $url,
        'kind' => $kind,
        'phrase' => $phrase,
        'port' => $port,
        'interval' => $interval,
        'regions' => $regions,
        'exclude' => $exclude,
    ];
}

function board_exclude(mixed $value): ?array
{
    if (is_string($value)) {
        $value = preg_split('/[\s,]+/', trim($value)) ?: [];
    }
    if (!is_array($value)) {
        return null;
    }
    $out = [];
    foreach ($value as $item) {
        if ($item === '' || $item === null) {
            continue;
        }
        if (is_string($item) && !preg_match('/^\d{3}$/', $item) && !is_int($item)) {
            return null;
        }
        $code = (int) $item;
        if ($code < 100 || $code > 599) {
            return null;
        }
        if (!in_array($code, $out, true)) {
            $out[] = $code;
        }
    }
    return $out;
}

function board_http_url(string $value): string
{
    if (strlen($value) > 2000) {
        return '';
    }
    $parts = parse_url($value);
    if (!is_array($parts)) {
        return '';
    }
    $scheme = strtolower((string) ($parts['scheme'] ?? ''));
    $host = (string) ($parts['host'] ?? '');
    if (($scheme !== 'http' && $scheme !== 'https') || $host === '' || board_blocked($host)) {
        return '';
    }
    if (!empty($parts['user']) || !empty($parts['pass'])) {
        return '';
    }
    return $value;
}

function board_port_url(string $value, int $port): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }
    if (!preg_match('#^https?://#i', $value)) {
        $value = 'http://' . $value;
    }
    $parts = parse_url($value);
    if (!is_array($parts)) {
        return '';
    }
    $scheme = strtolower((string) ($parts['scheme'] ?? ''));
    $host = (string) ($parts['host'] ?? '');
    if (($scheme !== 'http' && $scheme !== 'https') || $host === '' || board_blocked($host)) {
        return '';
    }
    return $scheme . '://' . $host . ':' . $port . '/';
}

function board_blocked(string $host): bool
{
    $host = strtolower(trim($host, '[]'));
    if ($host === '' || $host === 'localhost' || str_ends_with($host, '.localhost') || str_ends_with($host, '.local')) {
        return true;
    }
    if ($host === '0.0.0.0' || $host === '::' || $host === '::1') {
        return true;
    }
    if (preg_match('/^127\./', $host) || preg_match('/^10\./', $host) || preg_match('/^192\.168\./', $host) || preg_match('/^169\.254\./', $host)) {
        return true;
    }
    if (preg_match('/^172\.(1[6-9]|2\d|3[0-1])\./', $host)) {
        return true;
    }
    if (str_starts_with($host, 'fc') || str_starts_with($host, 'fd') || str_starts_with($host, 'fe80:')) {
        return true;
    }
    return false;
}

function board_host(string $url): string
{
    $host = (string) parse_url($url, PHP_URL_HOST);
    return $host !== '' ? $host : $url;
}

function board_clip(string $value, int $max): string
{
    $value = trim($value);
    if (mb_strlen($value) > $max) {
        return mb_substr($value, 0, $max);
    }
    return $value;
}

function board_flag(mixed $value): bool
{
    return $value === true || $value === 1 || $value === '1' || $value === 't' || $value === 'true';
}

function board_pg_array(?string $raw): array
{
    if ($raw === null) {
        return [];
    }
    $raw = trim($raw);
    if ($raw === '' || $raw === '{}') {
        return [];
    }
    if ($raw[0] === '{') {
        $raw = substr($raw, 1, -1);
    }
    if ($raw === '') {
        return [];
    }
    $items = str_getcsv($raw, ',', '"', '\\');
    $out = [];
    foreach ($items as $item) {
        $item = trim((string) $item, " \t\"");
        if ($item !== '') {
            $out[] = $item;
        }
    }
    return $out;
}

function board_pg_text(array $items): string
{
    $out = [];
    foreach ($items as $item) {
        $out[] = '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], (string) $item) . '"';
    }
    return '{' . implode(',', $out) . '}';
}

function board_pg_ints(array $items): string
{
    $out = [];
    foreach ($items as $item) {
        $out[] = (string) (int) $item;
    }
    return '{' . implode(',', $out) . '}';
}
