<?php
// frenzyex.php - recovery path for FrenzyEx orders whose callback never
// arrived. Polls GET /api/v1/payment-requests/{request_id} (merchant guide
// v5) for orders stuck in 'Unpaid' and fulfils the ones FrenzyEx reports
// as paid. The primary path is payment/frenzyex.php (pushed callback);
// claimPaymentPaid() keeps the two paths from delivering twice.
chdir(__DIR__);
ini_set('error_log', 'error_log');
date_default_timezone_set('Asia/Tehran');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../botapi.php';
require_once __DIR__ . '/../function.php';
require_once __DIR__ . '/../panels.php';
require_once __DIR__ . '/../jdf.php';
$ManagePanel = new ManagePanel();
$setting = select('setting', '*');
$textbotlang = languagechange();
$frenzyexStatus = getPaySettingValue('frenzyexstatus', 'offfrenzyex');
if (!in_array($frenzyexStatus, ['onfrenzyex', '1', 'on'], true)) {
    // Included by the dispatcher: return instead of exit so the remaining
    // jobs of this minute still run.
    return;
}
$apiKey = getPaySettingValue('frenzyex_api_key', '');
$baseUrl = getPaySettingValue('frenzyex_base_url', '');
if ($apiKey === '' || $baseUrl === '') {
    return;
}
$baseUrl = rtrim($baseUrl, '/');
$stmt = $pdo->prepare("SELECT * FROM Payment_report WHERE Payment_Method = 'frenzyex' AND payment_Status = 'Unpaid' ORDER BY id ASC LIMIT 50");
$stmt->execute();
$pending = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($pending as $payment) {
    $requestId = (string) $payment['dec_not_confirmed'];
    if ($requestId === '' || $requestId === '0') {
        continue;
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $baseUrl . '/api/v1/payment-requests/' . urlencode($requestId));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $apiKey, 'Accept: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($httpCode !== 200 || !is_string($response)) {
        continue;
    }
    $data = json_decode($response, true);
    if (!is_array($data)) {
        continue;
    }
    // The response must be about this very order.
    $respRequestId = (string) ($data['request_id'] ?? '');
    $respOrderRef = (string) ($data['order_ref'] ?? $data['order_id'] ?? '');
    if ($respRequestId !== $requestId || $respOrderRef !== (string) $payment['id_order']) {
        error_log('[frenzyex-cron] reference mismatch order=' . $payment['id_order']);
        continue;
    }
    $status = (string) ($data['status'] ?? '');
    if (in_array($status, ['expired', 'canceled'], true)) {
        // FrenzyEx will never pay this order; stop nudging the user about it.
        update('Payment_report', 'payment_Status', 'expire', 'id_order', $payment['id_order']);
        continue;
    }
    if ($status !== 'paid') {
        continue;
    }
    $priceCurrency = strtoupper(trim((string) ($data['price_currency'] ?? '')));
    $priceAmount = $data['price_amount'] ?? null;
    if ($priceCurrency !== 'TMN' || !is_numeric($priceAmount) || abs((float) $priceAmount - (float) $payment['price']) > 0.001) {
        error_log('[frenzyex-cron] amount mismatch order=' . $payment['id_order']);
        continue;
    }
    if (!claimPaymentPaid($payment['id_order'])) {
        continue; // the callback claimed it first
    }
    try {
        DirectPayment($payment['id_order'], __DIR__ . '/../images.jpg');
    } catch (Throwable $e) {
        error_log('[frenzyex-cron] DirectPayment failed order=' . $payment['id_order'] . ' error=' . $e->getMessage());
        reportDirectPaymentFailure($payment['id_order'], $e->getMessage());
        continue;
    }
    // Cashback parity with payment/frenzyex.php: the recovery path pays it too.
    $pricecashback = select('PaySetting', 'ValuePay', 'NamePay', 'cashbackfrenzyex', 'select')['ValuePay'] ?? '0';
    $Balance_id = select('user', '*', 'id', $payment['id_user'], 'select');
    if ($pricecashback != '0') {
        $cashback = (int) round(((float) $payment['price'] * (float) $pricecashback) / 100);
        if ($cashback > 0) {
            addBalance($Balance_id['id'], $cashback);
            $text_report = sprintf($textbotlang['paymentGateway']['giftReport'], $cashback);
            sendmessage($Balance_id['id'], $text_report, null, 'HTML');
        }
    }
    $paymentreports = select('topicid', 'idreport', 'report', 'paymentreport', 'select')['idreport'];
    $text_reportpayment = sprintf(
        $textbotlang['paymentGateway']['reportFrenzyEx'] ?? "پرداخت FrenzyEx موفق\nکاربر: %s\nآیدی: %s\nمبلغ: %s تومان",
        $Balance_id['username'],
        $Balance_id['id'],
        $payment['price']
    );
    if (strlen((string) ($setting['Channel_Report'] ?? '')) > 0) {
        telegram('sendmessage', ['chat_id' => $setting['Channel_Report'], 'message_thread_id' => $paymentreports, 'text' => $text_reportpayment, 'parse_mode' => 'HTML']);
    }
}
