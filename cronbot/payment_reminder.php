<?php
// payment_reminder.php - one reminder per unpaid order older than 24h.
// Reminders are tracked in payment_reminder.json (order => timestamp), so no
// schema column is required and the file self-prunes after 14 days.
chdir(__DIR__);
ini_set('error_log', 'error_log');
date_default_timezone_set('Asia/Tehran');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../botapi.php';
require_once __DIR__ . '/../function.php';
require_once __DIR__ . '/../panels.php';
require_once __DIR__ . '/../jdf.php';

$stateFile = __DIR__ . '/payment_reminder.json';
$reminded = json_decode((string) @file_get_contents($stateFile), true);
if (!is_array($reminded)) {
    $reminded = [];
}
$cut = time() - 14 * 86400;
foreach ($reminded as $order => $ts) {
    if ((int) $ts < $cut) {
        unset($reminded[$order]);
    }
}

// Payment_report.time uses 'Y/m/d H:i:s' (slash-separated), unlike MySQL's
// NOW(), so the threshold is computed in PHP with the same format.
$threshold = date('Y/m/d H:i:s', time() - 86400);
$sentCount = 0;

$stmt = $pdo->prepare("SELECT id_order, id_user, price, time FROM Payment_report WHERE payment_Status = 'Unpaid' AND time < ? ORDER BY id ASC LIMIT 100");
$stmt->execute([$threshold]);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if ($sentCount >= 20) {
        break;
    }
    $order = (string) ($row['id_order'] ?? '');
    $uid = (string) ($row['id_user'] ?? '');
    if ($order === '' || $uid === '' || isset($reminded[$order])) {
        continue;
    }
    $price = number_format((float) ($row['price'] ?? 0));
    $msg = "⏰ یادآوری پرداخت\n\nسفارش #<code>{$order}</code>\nمبلغ: {$price} تومان\n\nبرای پرداخت از بخش کیف پول اقدام کنید.";
    try {
        sendmessage($uid, $msg, null, 'HTML');
        $reminded[$order] = time();
        $sentCount++;
    } catch (Throwable $e) {
        error_log('payment_reminder: send failed for user ' . $uid . ': ' . $e->getMessage());
    }
}

@file_put_contents($stateFile, json_encode($reminded, JSON_UNESCAPED_UNICODE), LOCK_EX);
echo "Payment reminders sent: {$sentCount}\n";
