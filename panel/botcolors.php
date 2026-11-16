<?php
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/icons.php';
$__nexoraStyleLib = __DIR__ . '/../nexora_style.php';
if (is_file($__nexoraStyleLib)) {
    require_once $__nexoraStyleLib;
}
require_auth();

// Reuse botbuttons logic by including? Instead, redirect-style: this page is a hub.
$pageTitle = 'رنگ تمام کلیدهای ربات';
$pageLede = 'مدیریت رنگ همهٔ کلیدهای ربات در یک جا: کلیدهای کیبورد، دکمه‌های منو، محصول/دسته/پنل.';
$activeNav = 'botcolors';
$emojiLibrary = function_exists('nexora_emoji_list') ? nexora_emoji_list() : [];
include __DIR__ . '/inc/layout_head.php';
?>
<div class="card fade-up d1" style="margin-bottom:18px">
  <div class="card-head"><div><div class="card-title">بخش‌های ویرایش رنگ</div></div></div>
  <div class="card-body" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px">
    <a class="btn btn-primary" href="panels.php" style="text-align:center">🖥️ پنل‌های VPN</a>
    <a class="btn btn-primary" href="btnstyle.php" style="text-align:center">🛍️ محصول / دسته / پنل</a>
    <a class="btn btn-primary" href="testservice.php" style="text-align:center">🧪 سرویس تست</a>
    <a class="btn btn-primary" href="bottext.php" style="text-align:center">📝 متن‌های ربات</a>
  </div>
</div>

<div class="card fade-up d1">
  <div class="card-head"><div><div class="card-title">راهنما</div></div></div>
  <div class="card-body">
    <p style="line-height:1.8">
      رنگ محصول‌های مختلف (محصول / دسته / پنل) از صفحه‌ای «محصول / دسته / پنل» استفانه کنید، رنگ سرویس‌های تست از صفحه‌ای «سرویس تست» استفانه کنید و رنگ متن‌های ربات از صفحه‌ای «متن‌های ربات» استفانه کنید.
      تغییرات بلافاصله در ربات اعمال می‌شوند.
    </p>
  </div>
</div>
<?php include __DIR__ . '/inc/layout_foot.php'; ?>
