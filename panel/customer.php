<?php
/**
 * The page of the customer: the customer of the site (app.nexorashield.com.tr)
 * that is connected to this bot sees his own services here.
 *
 * The customer comes here in two ways:
 *   - from the site of the customers (the site sends his id with a signature)
 *   - with his name and his password of the site (the login of the panel)
 */
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/customers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// The customer that comes from the site.
$customer = nexora_customer_from_site();
if ($customer !== null) {
    nexora_customer_login_session($customer);
} else {
    $customer = nexora_customer_session();
}

// Signing out.
if (isset($_GET['logout'])) {
    nexora_customer_logout();
    header('Location: customer.php');
    exit;
}

// The customer is not signed in: the page of the login comes.
if ($customer === null) {
    header('Location: login.php');
    exit;
}

$domain = '';
foreach ([
    __DIR__ . '/../config.php',
    __DIR__ . '/../code/config.php',
] as $configFile) {
    if (is_file($configFile)) {
        $raw = (string) @file_get_contents($configFile);
        if (preg_match('/\$domainhosts\s*=\s*[\'"]([^\'"]+)[\'"]/', $raw, $match)) {
            $domain = $match[1];
            break;
        }
    }
}
if ($domain === '') {
    $domain = (string) ($_SERVER['HTTP_HOST'] ?? '');
}

// The services of the bot (the customer sees them, he cannot change them).
$services = [];
try {
    $rows = db_query(
        $pdo,
        "SELECT id, name_product, price_product, Volume_constraint, Service_time, Location
         FROM product WHERE hide_panel IS NULL OR hide_panel = 0
         ORDER BY display_order, id"
    );
    foreach ($rows as $row) {
        $services[] = $row;
    }
} catch (Exception $e) {
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1">
  <meta name="theme-color" content="#0F172A" id="mtc">
  <title>پنل من — <?= htmlspecialchars($domain, ENT_QUOTES, 'UTF-8') ?></title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/nexora-glass.css?v=1">
  <style>
    .cust-wrap{max-width:920px;margin:0 auto;padding:28px 18px 60px}
    .cust-head{display:flex;align-items:center;gap:14px;flex-wrap:wrap;
      padding:20px 22px;border-radius:22px;border:1px solid var(--border,rgba(255,255,255,.12));
      background:linear-gradient(150deg,rgba(255,255,255,.08),rgba(255,255,255,.02));
      box-shadow:0 18px 50px rgba(0,0,0,.35)}
    .cust-head h1{margin:0;font-size:1.15rem}
    .cust-head small{color:var(--mute,#939eb8);font-size:12.5px}
    .cust-grid{display:grid;gap:14px;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));margin-top:18px}
    .cust-card{padding:16px 18px;border-radius:18px;border:1px solid var(--border,rgba(255,255,255,.12));
      background:linear-gradient(150deg,rgba(255,255,255,.07),rgba(255,255,255,.02))}
    .cust-card h3{margin:0 0 6px;font-size:14.5px}
    .cust-card .price{font-weight:700;color:#e6c583}
    .cust-kv{display:flex;justify-content:space-between;gap:10px;font-size:13px;
      color:var(--mute,#939eb8);border-top:1px dashed rgba(255,255,255,.10);padding-top:8px;margin-top:8px}
    .cust-note{margin-top:20px;font-size:12.5px;color:var(--mute,#939eb8);line-height:2}
    .cust-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:18px}
  </style>
</head>

<body>
  <div class="cust-wrap">

    <div class="cust-head">
      <div>
        <h1>پنل من</h1>
        <small>
          <?= htmlspecialchars((string) ($customer['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
          · آیدی تلگرام: <?= htmlspecialchars((string) ($customer['telegram_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
          · <?= htmlspecialchars($domain, ENT_QUOTES, 'UTF-8') ?>
        </small>
      </div>
      <div class="cust-actions" style="margin:0 0 0 auto">
        <a class="btn" href="https://app.nexorashield.com.tr/">سایت نکسورا</a>
        <a class="btn" href="customer.php?logout=1">خروج</a>
      </div>
    </div>

    <h2 style="margin:26px 0 4px;font-size:1.02rem">سرویس‌های این ربات</h2>
    <p class="cust-note">
      سرویس‌هایی که این ربات می‌فروشد. برای خرید یا تمدید، از سایت نکسورا یا از خودِ ربات استفاده کنید.
    </p>

    <?php if (!$services): ?>
      <div class="cust-card" style="margin-top:14px">
        <p class="cust-note" style="margin:0">هنوز سرویسی برای این ربات تعریف نشده است.</p>
      </div>
    <?php else: ?>
      <div class="cust-grid">
        <?php foreach ($services as $service): ?>
          <div class="cust-card">
            <h3><?= htmlspecialchars((string) ($service['name_product'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h3>
            <div class="price"><?= htmlspecialchars((string) ($service['price_product'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            <div class="cust-kv"><span>حجم</span><span><?= htmlspecialchars((string) ($service['Volume_constraint'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></span></div>
            <div class="cust-kv"><span>مدت</span><span><?= htmlspecialchars((string) ($service['Service_time'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></span></div>
            <div class="cust-kv"><span>محل</span><span><?= htmlspecialchars((string) ($service['Location'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></span></div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div>
</body>

</html>
