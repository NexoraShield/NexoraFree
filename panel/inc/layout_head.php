<?php
require_once __DIR__ . '/icons.php';
$pageLede = $pageLede ?? '';
$activeNav = $activeNav ?? '';
$showPageHead = $showPageHead ?? true;
$currentUser = $_SESSION['admin_user'] ?? $textbotlang['panel']['layoutDefaultAdminName'];
$initials = mb_strtoupper(mb_substr($currentUser, 0, 1, 'UTF-8'), 'UTF-8');
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
  <meta name="theme-color" content="#0F172A" id="mtc">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <title><?= $textbotlang['panel']['layoutBrandName'] ?></title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/themes-extra.css?v=1">
  <link rel="stylesheet" href="css/nexora-glass.css?v=1">
  <link rel="stylesheet" href="css/reports.css?v=1">
  <script>
    (function () {
      var t = localStorage.getItem('panel-theme') || 'navy';
      var bg = {
        navy: '#0F172A', purple: '#180D2E', emerald: '#0A1F1C',
        sunset: '#1A0D0D', slate: '#080808', light: '#F1F5F9',
        linen: '#FAF7F2', mint: '#F0FDF4', lavender: '#FAF5FF',
        rose: '#1A0A14', indigo: '#0F0F2A', teal: '#0A1F1F',
        amber: '#1A150A', sky: '#F0F9FF', peach: '#FFF7ED'
      };

      var root = document.documentElement;
      root.style.backgroundColor = bg[t] || '#0F172A';
      root.setAttribute('data-theme', t);

      root.style.colorScheme = (t === 'light' || t === 'linen' || t === 'mint' || t === 'lavender' || t === 'sky' || t === 'peach') ? 'light' : 'dark';
      var mtc = document.getElementById('mtc');
      if (mtc && bg[t]) mtc.content = bg[t];
      if (localStorage.getItem('panel-sb-collapsed') === '1' && window.innerWidth > 768)
        root.classList.add('sb-pre-collapsed');
    }());
  </script>
  <script>
    window.PANEL_I18N = <?= json_encode(
      array_filter(
        $textbotlang['panel'] ?? [],
        fn($k) => preg_match('/^js[A-Z]/', $k) === 1,
        ARRAY_FILTER_USE_KEY
      ),
      JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    ) ?>;
    window.t = function (key, vars) {
      var s = (window.PANEL_I18N && window.PANEL_I18N[key]) || key;
      if (vars) {
        for (var k in vars) {
          if (Object.prototype.hasOwnProperty.call(vars, k)) {
            s = s.replace('{' + k + '}', vars[k]);
          }
        }
      }
      return s;
    };
  </script>
</head>

<body>

  <!-- The splash of the panel: the mark of Nexora turns while the page is
       loaded (when the owner changes the bot, the panel of the other one
       comes with this mark until it is ready). -->
  <div id="nx-splash" aria-hidden="true">
    <div class="nx-splash-mark">
      <img src="assets/nexora-logo-icon.png" alt="Nexora">
      <span class="nx-splash-ring"></span>
    </div>
    <div class="nx-splash-name">Nexora</div>
    <div class="nx-splash-bar"><span></span></div>
  </div>

  <script>
    (function () {
      var splash = document.getElementById('nx-splash');
      if (!splash) { return; }
      var hide = function () { splash.classList.add('gone'); };
      var done = false;
      var finish = function () {
        if (done) { return; }
        done = true;
        hide();
      };
      // The mark stays a moment, so it does not flash on a fast page.
      setTimeout(finish, 1200);
      if (document.readyState === 'complete') {
        setTimeout(finish, 120);
      } else {
        window.addEventListener('load', function () { setTimeout(finish, 120); });
      }
    }());
  </script>

  <div id="load-bar"></div>
  <div id="toast-area"></div>

  <div class="confirm-veil" id="confirm-veil">
    <div class="confirm-box">
      <div class="confirm-icon"><?= icon('block', 26) ?></div>
      <h4 id="confirm-title"><?= $textbotlang['panel']['layoutNavDashboard'] ?></h4>
      <p id="confirm-msg"><?= $textbotlang['panel']['layoutNavUsers'] ?></p>
      <div class="confirm-btns">
        <button class="btn btn-no" id="confirm-ok"><?= $textbotlang['panel']['layoutNavOrders'] ?></button>
        <button class="btn btn-ghost"
          onclick="closeConfirm()"><?= $textbotlang['panel']['layoutNavServices'] ?></button>
      </div>
    </div>
  </div>

  <div class="app">
    <div class="sidebar-backdrop" id="backdrop" onclick="closeSidebar()"></div>

    <aside class="sidebar" id="sidebar">
      <div class="sidebar-brand">
        <div class="brand-mark">M</div>
        <div class="brand-name"><?= $textbotlang['panel']['layoutNavProducts'] ?><span>
            <?= $textbotlang['panel']['layoutNavPayments'] ?></span></div>
      </div>
      <nav class="sidebar-nav">
        <div class="nav-section">
          <div class="nav-heading"><?= $textbotlang['panel']['layoutNavKeyboard'] ?></div>
          <a href="index.php" class="nav-item <?= $activeNav === 'dashboard' ? 'active' : '' ?>"
            title="<?= $textbotlang['panel']['layoutPageTitleDashboard'] ?>">
            <span class="nav-icon"><?= icon('dashboard') ?></span><span
              class="nav-label"><?= $textbotlang['panel']['layoutNavSettings'] ?></span>
          </a>
          </div>
        <div class="nav-section">
          <div class="nav-heading"><?= $textbotlang['panel']['layoutNavLogout'] ?></div>
          <a href="users.php" class="nav-item <?= $activeNav === 'users' ? 'active' : '' ?>"
            title="<?= $textbotlang['panel']['layoutPageTitleUsers'] ?>">
            <span class="nav-icon"><?= icon('users') ?></span><span
              class="nav-label"><?= $textbotlang['panel']['layoutMenuSectionMain'] ?></span>
          </a>
          <a href="invoice.php" class="nav-item <?= $activeNav === 'invoice' ? 'active' : '' ?>"
            title="<?= $textbotlang['panel']['layoutPageTitleInvoice'] ?>">
            <span class="nav-icon"><?= icon('invoice') ?></span><span
              class="nav-label"><?= $textbotlang['panel']['layoutMenuSectionManagement'] ?></span>
          </a>
          <a href="service.php" class="nav-item <?= $activeNav === 'service' ? 'active' : '' ?>"
            title="<?= $textbotlang['panel']['layoutPageTitleService'] ?>">
            <span class="nav-icon"><?= icon('server') ?></span><span
              class="nav-label"><?= $textbotlang['panel']['layoutMenuSectionFinancial'] ?></span>
          </a>
          <a href="panels.php" class="nav-item" title="پنل‌های VPN">
            <span class="nav-icon"><?= icon('server') ?></span><span
              class="nav-label">پنل‌های VPN</span>
          </a>
          <a href="product.php" class="nav-item <?= $activeNav === 'product' ? 'active' : '' ?>"
            title="<?= $textbotlang['panel']['layoutPageTitleProduct'] ?>">
            <span class="nav-icon"><?= icon('package') ?></span><span
              class="nav-label"><?= $textbotlang['panel']['layoutMenuSectionSystem'] ?></span>
          </a>
          <a href="category.php" class="nav-item <?= $activeNav === 'category' ? 'active' : '' ?>"
            title="<?= $textbotlang['panel']['categoryPageTitle'] ?>">
            <span class="nav-icon"><?= icon('folder') ?></span><span
              class="nav-label"><?= $textbotlang['panel']['categoryPageTitle'] ?></span>
          </a>
          <a href="payment.php" class="nav-item <?= $activeNav === 'payment' ? 'active' : '' ?>"
            title="<?= $textbotlang['panel']['layoutPageTitlePayment'] ?>">
            <span class="nav-icon"><?= icon('card') ?></span><span
              class="nav-label"><?= $textbotlang['panel']['layoutSearchBoxPlaceholder'] ?></span>
          </a>
          <a href="bottext.php" class="nav-item <?= $activeNav === 'bottext' ? 'active' : '' ?>"
            title="<?= $textbotlang['panel']['bottextPageTitle'] ?>">
            <span class="nav-icon"><?= icon('edit') ?></span><span
              class="nav-label"><?= $textbotlang['panel']['bottextPageTitle'] ?></span>
          </a>
          <a href="maintenance.php" class="nav-item <?= $activeNav === 'maintenance' ? 'active' : '' ?>"
            title="حالت تعمیرات">
            <span class="nav-icon"><?= icon('tool') ?></span><span
              class="nav-label">تعمیرات</span>
          </a>
          <a href="botcolors.php" class="nav-item <?= $activeNav === 'botcolors' ? 'active' : '' ?>"
            title="رنگ تمام کلیدها">
            <span class="nav-icon"><?= icon('palette') ?></span><span
              class="nav-label">رنگ تمام کلیدها</span>
          </a>
          <a href="backup.php" class="nav-item <?= $activeNav === 'backup' ? 'active' : '' ?>"
            title="پشتیبان‌گیری">
            <span class="nav-icon"><?= icon('database') ?></span><span
              class="nav-label">پشتیبان‌گیری</span>
          </a>
          <a href="queue.php" class="nav-item <?= $activeNav === 'queue' ? 'active' : '' ?>"
            title="صف عملیات‌های ناقص">
            <span class="nav-icon"><?= icon('clock') ?></span><span
              class="nav-label">صف عملیات</span>
          </a>
          <a href="service_volumes.php" class="nav-item <?= $activeNav === 'service_volumes' ? 'active' : '' ?>"
            title="بررسی حجم/زمان سرویس‌ها">
            <span class="nav-icon"><?= icon('chart') ?></span><span
              class="nav-label">بررسی حجم/زمان</span>
          </a>
            title="فروشگاه‌های ربات، دقیقاً مثل خود ربات (پنل نمایندگی، پنل اجنت و شارژ آن‌ها)">
            <span class="nav-icon"><?= icon('robot') ?></span><span
              class="nav-label">فروشگاه‌های ربات</span>
          </a>
          <a href="testservice.php" class="nav-item <?= $activeNav === 'testservice' ? 'active' : '' ?>"
            title="سرویس تست">
            <span class="nav-icon"><?= icon('package') ?></span><span
              class="nav-label">سرویس تست</span>
          </a>
          <a href="update.php" class="nav-item <?= $activeNav === 'update' ? 'active' : '' ?>"
            title="آپدیت ربات">
            <span class="nav-icon"><?= icon('download') ?></span><span
              class="nav-label">آپدیت ربات</span>
          </a>

        </div>
        <div class="nav-section">
          <div class="nav-heading"><?= $textbotlang['panel']['layoutSidebarToggleLabel'] ?></div>
          <a href="settings.php" class="nav-item <?= $activeNav === 'settings' ? 'active' : '' ?>"
            title="<?= $textbotlang['panel']['layoutPageTitleSettings'] ?>">
            <span class="nav-icon"><?= icon('settings') ?></span><span
              class="nav-label"><?= $textbotlang['panel']['layoutProfileMenuLabel'] ?></span>
          </a>
          <a href="logout.php" class="nav-item" title="<?= $textbotlang['panel']['layoutPageTitleLogout'] ?>">
            <span class="nav-icon"><?= icon('logout') ?></span><span
              class="nav-label"><?= $textbotlang['panel']['layoutNotificationsLabel'] ?></span>
          </a>
        </div>
      </nav>
      <div class="sidebar-foot">
        <div class="user-pill">
          <div class="user-mono"><?= htmlspecialchars($initials) ?></div>
          <div class="user-info">
            <div class="uname"><?= htmlspecialchars($currentUser) ?></div>
            <div class="urole"><?= $textbotlang['panel']['layoutMobileMenuLabel'] ?></div>
          </div>
        </div>
      </div>
    </aside>

    <div class="main">
      <header class="topbar">
        <div class="topbar-left">
          <button class="icon-btn menu-toggle" onclick="openSidebar()"><?= icon('menu', 18) ?></button>
          <button class="icon-btn sb-toggle" onclick="toggleSidebar()"><?= icon('menu', 17) ?></button>
          <div>
            <div class="topbar-title"><?= htmlspecialchars($pageTitle ?? '') ?></div>
            <div class="crumb"><span><?= $textbotlang['panel']['layoutPageTitleSuffix'] ?></span><span
                style="opacity:.4;margin:0 3px">/</span><span><?= htmlspecialchars($pageTitle ?? '') ?></span></div>
          </div>
        </div>
        <div class="topbar-tools">
          <a href="settings.php" class="icon-btn"
            title="<?= $textbotlang['panel']['layoutPageTitleSettings'] ?>"><?= icon('settings', 16) ?></a>
          <a href="logout.php" class="icon-btn"
            title="<?= $textbotlang['panel']['layoutPageTitleLogout'] ?>"><?= icon('logout', 16) ?></a>
        </div>
      </header>
      <main class="content">
        <?php
        $s = get_flash('success');
        $e = get_flash('error');
        $w = get_flash('warning');
        if ($s): ?>
          <div class="notice notice-ok"><?= htmlspecialchars($s) ?></div><?php endif;
        if ($e): ?>
          <div class="notice notice-no"><?= htmlspecialchars($e) ?></div><?php endif;
        if ($w): ?>
          <div class="notice notice-warn"><?= htmlspecialchars($w) ?></div><?php endif;
        if ($showPageHead): ?>
          <div class="page-head fade-up">
            <h1><?= htmlspecialchars($pageTitle ?? '') ?></h1>
            <?php if ($pageLede): ?>
              <p><?= htmlspecialchars($pageLede) ?></p><?php endif; ?>
          </div>
        <?php endif; ?>