<?php
// renewal_reminder.php - reminder N days before a service expires.
// NOTE: this job is not wired into cronbot/jobs.php (the dispatcher). Whether
// to enable it is an operational decision; the script itself is functional.
chdir(__DIR__);
ini_set('error_log', 'error_log');
date_default_timezone_set('Asia/Tehran');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../botapi.php';
require_once __DIR__ . '/../function.php';
require_once __DIR__ . '/../panels.php';
require_once __DIR__ . '/../jdf.php';
$ManagePanel = new ManagePanel();
$setting = select("setting", "*");
$textbotlang = languagechange();

$stateFile = __DIR__ . '/renewal_reminder.json';
$sent = json_decode((string) @file_get_contents($stateFile), true);
if (!is_array($sent)) {
    $sent = [];
}
$cut = time() - 14 * 86400;
foreach ($sent as $key => $entry) {
    if ((int) ($entry['time'] ?? 0) < $cut) {
        unset($sent[$key]);
    }
}

// Expiry is derived from the invoice (time_sell + Service_time); the
// service_other table does not store an expire_date column.
$stmt = $pdo->prepare("SELECT id_invoice, id_user, name_product, username, time_sell, Service_time FROM invoice WHERE Status = 'active' LIMIT 500");
$stmt->execute();
$sentCount = 0;
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $inv) {
    $ts = strtotime((string) ($inv['time_sell'] ?? ''));
    $days = (int) ($inv['Service_time'] ?? 0);
    $uid = trim((string) ($inv['id_user'] ?? ''));
    $invoiceId = trim((string) ($inv['id_invoice'] ?? ''));
    if ($ts === false || $days <= 0 || $uid === '' || $invoiceId === '') {
        continue;
    }
    $expiry = $ts + $days * 86400;
    $remaining = (int) floor(($expiry - time()) / 86400);
    if (!in_array($remaining, [3, 1], true)) {
        continue;
    }
    $stateKey = $invoiceId . ':' . $remaining;
    if (isset($sent[$stateKey])) {
        continue;
    }
    $msg = "⏰ یادآوری: سرویس شما {$remaining} روز دیگر منقضی می‌شود.\nبرای تمدید به بات مراجعه کنید.";
    try {
        sendmessage($uid, $msg, null, 'HTML');
        $sent[$stateKey] = ['time' => time()];
        $sentCount++;
    } catch (Throwable $e) {
        error_log('renewal_reminder: send failed for user ' . $uid . ': ' . $e->getMessage());
    }
}

@file_put_contents($stateFile, json_encode($sent, JSON_UNESCAPED_UNICODE), LOCK_EX);
echo "Renewal reminders sent: {$sentCount}\n";
