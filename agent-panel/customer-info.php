<?php
/**
 * The details of a customer of the agent.
 *
 * The window of the panel (the details of a user and the links of the
 * services) is filled with the answer of this file. The answer is JSON.
 *
 * The file never changes anything: it only reads. A customer that does not
 * belong to the agent is never answered, so the links of the other agents
 * can not be seen from here.
 */
// The head of the answer is written before anything else, so a warning of
// PHP can never break the answer of the file.
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/inc/auth.php';
require_once dirname(__DIR__) . '/function.php';
require_once dirname(__DIR__) . '/panels.php';
require_once __DIR__ . '/inc/shop.php';

/**
 * The answer of the file: the code of the answer and the answer itself.
 */
function nx_agent_json(array $payload, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$me = nexora_agent_current();
if ($me === null) {
    nx_agent_json(['ok' => false, 'msg' => 'نشست شما تمام شده است؛ دوباره وارد شوید.'], 401);
}
if (!nx_agent_csrf_check()) {
    nx_agent_json(['ok' => false, 'msg' => 'نشست نامعتبر است؛ صفحه را دوباره باز کنید.'], 403);
}

$customerId = trim((string) ($_GET['customer'] ?? ''));
if ($customerId === '') {
    nx_agent_json(['ok' => false, 'msg' => 'کاربر انتخاب نشده است.'], 400);
}

// The links are only asked for when the window needs them.
$withLinks = ((string) ($_GET['links'] ?? '0')) === '1';
$ManagePanel = new ManagePanel();
$report = nx_agent_customer_report($pdo, $ManagePanel, $me, $customerId, $withLinks);
if (empty($report['ok'])) {
    $code = (strpos((string) $report['msg'], 'پنل شما') !== false) ? 404 : 500;
    nx_agent_json(['ok' => false, 'msg' => (string) $report['msg']], $code);
}
nx_agent_json([
    'ok' => true,
    'customer' => $report['customer'],
    'services' => $report['services'],
]);
