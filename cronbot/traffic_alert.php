<?php
// traffic_alert.php - alert users whose service passes 80% / 90% usage.
//
// The local database does not track usage; the panel does. Each run probes a
// random sample of active invoices (the panels answer in real time), compares
// used_traffic against data_limit and sends the user one message per
// threshold. Sent alerts are remembered in traffic_alert.json so the user is
// not spammed every hour; the state self-prunes after 14 days.
chdir(__DIR__);
ini_set('error_log', 'error_log');
date_default_timezone_set('Asia/Tehran');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../botapi.php';
require_once __DIR__ . '/../function.php';
require_once __DIR__ . '/../panels.php';
require_once __DIR__ . '/../jdf.php';
$ManagePanel = new ManagePanel();

$stateFile = __DIR__ . '/traffic_alert.json';
$sent = json_decode((string) @file_get_contents($stateFile), true);
if (!is_array($sent)) {
    $sent = [];
}
// Prune state older than 14 days.
$cut = time() - 14 * 86400;
foreach ($sent as $key => $entry) {
    if ((int) ($entry['time'] ?? 0) < $cut) {
        unset($sent[$key]);
    }
}

$sentCount = 0;

$stmt = $pdo->prepare("SELECT id_invoice, id_user, username, Service_location FROM invoice WHERE Status = 'active' ORDER BY RAND() LIMIT 30");
$stmt->execute();
$invoices = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

foreach ($invoices as $inv) {
    $location = trim((string) ($inv['Service_location'] ?? ''));
    $username = trim((string) ($inv['username'] ?? ''));
    $uid = trim((string) ($inv['id_user'] ?? ''));
    $invoiceId = trim((string) ($inv['id_invoice'] ?? ''));
    if ($location === '' || $username === '' || $uid === '' || $invoiceId === '') {
        continue;
    }
    try {
        $usage = $ManagePanel->DataUser($location, $username);
    } catch (Throwable $e) {
        error_log('traffic_alert: DataUser failed for ' . $username . ' @ ' . $location . ': ' . $e->getMessage());
        continue;
    }
    if (!is_array($usage) || (($usage['status'] ?? '') === 'Unsuccessful')) {
        continue;
    }
    $limit = $usage['data_limit'] ?? null;
    $used = $usage['used_traffic'] ?? null;
    if (!is_numeric($limit) || !is_numeric($used)) {
        continue; // unlimited service, or the panel does not report usage
    }
    $limit = (float) $limit;
    $used = (float) $used;
    if ($limit <= 0) {
        continue;
    }
    $pct = ($used / $limit) * 100;
    $level = null;
    if ($pct >= 90) {
        $level = '90';
    } elseif ($pct >= 80) {
        $level = '80';
    }
    if ($level === null) {
        continue;
    }
    $stateKey = $invoiceId . ':' . $level;
    if (isset($sent[$stateKey])) {
        continue; // alert already delivered for this threshold
    }
    $pctText = round($pct);
    $msg = $level === '90'
        ? "⚠️ هشدار: {$pctText}% از حجم سرویس شما استفاده شده است.\nلطفاً زودتر نسبت به تمدید یا افزایش حجم اقدام کنید."
        : "📊 اطلاع: {$pctText}% از حجم سرویس شما استفاده شده است.";
    try {
        sendmessage($uid, $msg, null, 'HTML');
        $sent[$stateKey] = ['time' => time(), 'pct' => $pctText];
        $sentCount++;
    } catch (Throwable $e) {
        error_log('traffic_alert: send failed for user ' . $uid . ': ' . $e->getMessage());
    }
}

@file_put_contents($stateFile, json_encode($sent, JSON_UNESCAPED_UNICODE), LOCK_EX);
echo "Traffic alerts sent: {$sentCount}\n";
