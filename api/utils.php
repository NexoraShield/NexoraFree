<?php

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../function.php';

if (!function_exists('getallheaders')) {
    function getallheaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) !== 'HTTP_') {
                continue;
            }
            $headerName = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
            $headers[$headerName] = $value;
        }
        return $headers;
    }
}

function sendJsonResponse($status, $message, $data = [], $httpCode = 200)
{
    http_response_code($httpCode);
    echo json_encode([
        'status' => $status,
        'msg' => $message,
        'obj' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function sendReport($text, $groupid, $topic_id, $reply_markup = null)
{
    if (strlen((string) $groupid) > 0) {
        telegram('sendmessage', [
            'chat_id' => $groupid,
            'message_thread_id' => $topic_id,
            'text' => $text,
            'parse_mode' => "HTML",
            'reply_markup' => $reply_markup
        ]);
    }
}

function headerValue($headers, $name)
{
    if (!is_array($headers)) {
        return null;
    }

    foreach ($headers as $key => $value) {
        if (strcasecmp($key, $name) === 0) {
            return is_array($value) ? reset($value) : $value;
        }
    }

    return null;
}

function apiTokens()
{
    global $APIKEY;

    $tokens = [];

    $settingToken = trim((string) (select("setting", "*", null, null, "select", ['cache' => false])['api_token'] ?? ''));
    if ($settingToken !== '') {
        $tokens[] = $settingToken;
    }

    if (empty($tokens) && isset($APIKEY) && $APIKEY !== '') {
        $tokens[] = (string) $APIKEY;
    }

    return $tokens;
}

function validateToken($headers)
{
    $provided = headerValue($headers, 'Token');
    if ($provided === null) {
        return false;
    }

    $provided = trim((string) $provided);
    foreach (apiTokens() as $validToken) {
        if (hash_equals($validToken, $provided)) {
            return true;
        }
    }

    return false;
}

function requireApiToken($headers)
{
    if (!validateToken($headers)) {
        sendJsonResponse(false, "token invalid", [], 403);
    }
}

function hasAdminSession()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['admin_user'])) {
        return false;
    }

    try {
        $admin = select("admin", "*", "username", $_SESSION['admin_user'], "select");
    } catch (Exception $e) {
        error_log("Admin session check failed: " . $e->getMessage());
        return false;
    }

    return is_array($admin) && isset($admin['id_admin']) && $admin['rule'] === 'administrator';
}

function requireApiTokenOrAdminSession($headers)
{
    if (validateToken($headers) || hasAdminSession()) {
        return;
    }

    sendJsonResponse(false, "token invalid", [], 403);
}

function sanitizeRecursive($data)
{
    if (is_array($data)) {
        return array_map('sanitizeRecursive', $data);
    }

    return is_string($data) ? htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8') : $data;
}

function validateMethod($expected, $actual)
{
    if (strtoupper($expected) !== strtoupper((string) $actual)) {
        sendJsonResponse(false, "method invalid; method must be {$expected}", [], 405);
    }
}

function readJsonBody()
{
    $raw = file_get_contents("php://input");
    $data = $raw === false ? null : json_decode($raw, true);

    if (!is_array($data)) {
        sendJsonResponse(false, "data invalid", []);
    }

    return sanitizeRecursive($data);
}

function logApiRequest($headers, $data, $action)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare(
            "INSERT IGNORE INTO logs_api (header, data, time, ip, actions) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            json_encode(array_diff_key(array_change_key_case((array) $headers), array_flip(['token', 'authorization', 'cookie']))),
            json_encode($data),
            date('Y/m/d H:i:s'),
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $action
        ]);
    } catch (Exception $e) {
        error_log("API logging error: " . $e->getMessage());
    }
}

function apiRequestContext()
{
    $headers = getallheaders();
    requireApiToken($headers);

    $data = readJsonBody();
    $action = isset($data['actions']) && is_scalar($data['actions']) ? (string) $data['actions'] : '';

    logApiRequest($headers, $data, $action === '' ? 'unknown' : $action);

    return [$headers, $data, $action];
}

function panelAgentValue($json, $agent, $default = 0)
{
    $decoded = json_decode((string) $json, true);
    if (!is_array($decoded) || !array_key_exists($agent, $decoded)) {
        return $default;
    }

    return $decoded[$agent];
}

function topicId($name)
{
    $row = select("topicid", "idreport", "report", $name, "select");

    return is_array($row) && isset($row['idreport']) ? $row['idreport'] : null;
}

function paginationParams(array $data, $defaultLimit = 50, $maxLimit = 1000)
{
    $limit = $defaultLimit;
    if (isset($data['limit']) && is_numeric($data['limit'])) {
        $limit = min(max((int) $data['limit'], 1), $maxLimit);
    }

    $page = isset($data['page']) && is_numeric($data['page']) ? max((int) $data['page'], 1) : 1;

    return [
        'limit' => $limit,
        'page' => $page,
        'offset' => ($page - 1) * $limit,
        'q' => isset($data['q']) && is_scalar($data['q']) ? (string) $data['q'] : '',
    ];
}

function requireFields(array $data, array $fields)
{
    $missing = [];
    foreach ($fields as $field) {
        if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === []) {
            $missing[] = $field;
        }
    }

    if ($missing !== []) {
        sendJsonResponse(false, "Missing required fields: " . implode(', ', $missing), []);
    }
}

function requireInt(array $data, $field, $min = null, $max = null)
{
    if (!isset($data[$field]) || !is_numeric($data[$field])) {
        sendJsonResponse(false, "{$field} invalid", []);
    }

    $value = (int) $data[$field];
    if (($min !== null && $value < $min) || ($max !== null && $value > $max)) {
        sendJsonResponse(false, "{$field} out of range", []);
    }

    return $value;
}

/**
 * The columns of a table.
 *
 * A column that the database does not know yet is simply left out, so a query
 * never breaks on a database that was never migrated.
 */
function api_table_columns(PDO $pdo, string $table): array
{
    static $cache = [];
    $key = strtolower($table);
    if (isset($cache[$key])) {
        return $cache[$key];
    }
    $columns = [];
    try {
        $st = $pdo->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
        $st->execute([$table]);
        $columns = $st->fetchAll(PDO::FETCH_COLUMN);
    } catch (Throwable $e) {
        $columns = [];
    }
    $cache[$key] = $columns;
    return $columns;
}

/**
 * The part of a query that hides the test products.
 *
 * The test products are not products of the shop: they belong to the test
 * service and they have their own section in the web application. A database
 * that does not know the column "is_test" yet shows everything it has.
 */
function api_product_test_filter(PDO $pdo): string
{
    static $filter = null;
    if ($filter !== null) {
        return $filter;
    }
    $columns = array_map('strtolower', api_table_columns($pdo, 'product'));
    $filter = in_array('is_test', $columns, true) ? ' AND is_test = 0' : '';
    return $filter;
}

/**
 * The part of a query that shows only the test products.
 *
 * A database that does not know the column "is_test" yet has no test product
 * at all, so the query brings nothing back.
 */
function api_test_products_filter(PDO $pdo): string
{
    static $filter = null;
    if ($filter !== null) {
        return $filter;
    }
    $columns = array_map('strtolower', api_table_columns($pdo, 'product'));
    $filter = in_array('is_test', $columns, true) ? ' AND is_test = 1' : ' AND 1 = 0';
    return $filter;
}

/**
 * The name of the section of the test service in the web application.
 *
 * The test products are never mixed with the products of the shop: they have
 * their own section, so the customer always knows that he is looking at a
 * test service.
 */
if (!defined('NEXORA_TEST_SECTION')) {
    define('NEXORA_TEST_SECTION', 'nexora-test');
}

/**
 * The test products the customer may see.
 *
 * The test products belong to the customer of the bot (the agent "f"). The
 * share of the customer is not checked here: it is checked when he buys one.
 */
function api_test_products(PDO $pdo, string $agent): array
{
    static $cache = [];
    if (isset($cache[$agent])) {
        return $cache[$agent];
    }
    $rows = [];
    try {
        $sql = "SELECT * FROM product WHERE agent = :ag AND status_product = 'active'"
            . api_test_products_filter($pdo) . " ORDER BY display_order, id";
        $st = $pdo->prepare($sql);
        $st->execute([':ag' => $agent]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        error_log('api_test_products: ' . $e->getMessage());
        $rows = [];
    }
    $cache[$agent] = $rows;
    return $rows;
}
