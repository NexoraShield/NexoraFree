<?php
// ---------------------------------------------------------------------------
// The shops of the bot, exactly as the customer sees them.
//
// The bot has two shops that are not the services of the bot:
//   - the shop of the reseller panel ("خرید پنل نمایندگی"),
//   - the shop of the agent panel ("خرید اجنت پنل").
// Both of them have their own top up section. This page shows both shops the
// same way the bot shows them: the same names, the same premium emoji, the
// same prices and the same button of the top up. The owner sees here what
// the customer sees before he buys anything.
// ---------------------------------------------------------------------------
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/icons.php';
$__nexoraStyleLib = __DIR__ . '/../nexora_style.php';
if (is_file($__nexoraStyleLib)) {
    require_once $__nexoraStyleLib;
}
require_once __DIR__ . '/inc/shop_helpers.php';
require_auth();

$STYLE_COLORS = [];
foreach (nexora_color_palette() as $slug => $info) {
    if ($slug !== '') {
        $STYLE_COLORS[$slug] = $info['hex'];
    }
}

$agentShop = nexora_agent_shop_screen();
$agentPanelShop = nexora_agent_panel_shop_screen();
$agentTopupOptions = nexora_topup_products('agentshop', false);
$agentPanelTopupOptions = nexora_topup_products('agentpanelshop', false);

$botLink = panel_bot_link();
$agentShopLink = panel_bot_link('agentshop');
$agentPanelShopLink = panel_bot_link('agentpanelshop');

$pageTitle = 'فروشگاه‌های ربات';
$pageLede = 'این دو فروشگاه در ربات با کلید «خرید پنل نمایندگی» و «خرید اجنت پنل» به مشتری نشان داده می‌شوند. اینجا دقیقاً همان چیزی را می‌بینید که مشتری می‌بیند: نامی که خودتان گذاشته‌اید، ایموجی پرمیوم، قیمت و دکمه شارژ پنل. اگر نام یا ایموجی اینجا درست نبود، همان چیزی است که مشتری می‌بیند.';
$activeNav = 'shop';
include __DIR__ . '/inc/layout_head.php';
?>

<div class="card fade-up" style="margin-bottom:14px"><div class="card-body" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
  <span style="font-size:.85rem;color:var(--mute)">باز کردن فروشگاه در خود ربات:</span>
  <?php if ($agentShopLink !== ''): ?>
    <a class="btn btn-primary btn-sm" href="<?= htmlspecialchars($agentShopLink) ?>" target="_blank" rel="noopener">🛒 خرید پنل نمایندگی</a>
    <a class="btn btn-primary btn-sm" href="<?= htmlspecialchars($agentPanelShopLink) ?>" target="_blank" rel="noopener">🖥 خرید اجنت پنل</a>
  <?php else: ?>
    <span class="pill">نام ربات در دسترس نیست</span>
  <?php endif; ?>
  <?php if ($botLink !== ''): ?>
    <a class="btn btn-ghost btn-sm" href="<?= htmlspecialchars($botLink) ?>" target="_blank" rel="noopener"><?= icon('robot', 14) ?> باز کردن ربات</a>
  <?php endif; ?>
</div></div>

<?php
/**
 * The products of one shop, drawn the way the bot draws them.
 */
$renderShop = function (string $title, string $subtitle, array $items, array $topup, array $topupOptions, string $shop, string $link) {
    ?>
    <div class="card fade-up" style="margin-bottom:18px">
      <div class="card-head">
        <div>
          <div class="card-title"><?= htmlspecialchars($title) ?></div>
          <div class="card-subtitle"><?= htmlspecialchars($subtitle) ?></div>
        </div>
        <?php if ($link !== ''): ?>
          <a class="btn btn-primary btn-sm" href="<?= htmlspecialchars($link) ?>" target="_blank" rel="noopener">باز کردن در ربات</a>
        <?php endif; ?>
      </div>
      <div class="card-body">
        <?php if (!$items): ?>
          <div style="color:var(--mute);font-size:.85rem">هنوز محصولی برای این فروشگاه تعریف نشده است.</div>
        <?php else: ?>
          <div style="display:flex;flex-direction:column;gap:8px;align-items:flex-start">
            <?php foreach ($items as $item):
                $name = (string) ($item['name_product'] ?? $item['name'] ?? '');
                $style = (string) ($item['btn_style'] ?? '');
                $emoji = (string) ($item['btn_emoji_id'] ?? '');
                $price = (string) ($item['price_product'] ?? $item['price'] ?? '');
                ?>
              <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                <?php if ($shop === 'agentshop'): ?>
                  <?= nexora_agentshop_preview($name, $style, $emoji, $GLOBALS['STYLE_COLORS']) ?>
                <?php else: ?>
                  <?= nexora_agentpanel_preview($name, $style, $emoji, $GLOBALS['STYLE_COLORS']) ?>
                <?php endif; ?>
                <span class="pill"><?= number_format((float) $price) ?> تومان</span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <div style="margin-top:16px;padding-top:14px;border-top:1px solid var(--border)">
          <div style="font-size:.85rem;color:var(--mute);margin-bottom:8px">دکمه شارژ پنل (همان دکمه‌ای که مشتری می‌بیند):</div>
          <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
            <?php
            $topupBg = $GLOBALS['STYLE_COLORS'][$topup['style'] ?? ''] ?? 'var(--surface-3)';
            $topupFg = (($topup['style'] ?? '') !== '' && isset($GLOBALS['STYLE_COLORS'][$topup['style']])) ? '#fff' : 'inherit';
            $topupIcon = function_exists('nexora_emoji_preview_html') ? nexora_emoji_preview_html((string) ($topup['emoji'] ?? '')) : '';
            ?>
            <span style="display:inline-block;background:<?= htmlspecialchars($topupBg) ?>;color:<?= htmlspecialchars($topupFg) ?>;border-radius:8px;padding:4px 12px;font-size:.8rem"><?= $topupIcon ?><?= htmlspecialchars((string) ($topup['label'] ?? '')) ?></span>
            <span class="pill"><?= count($topupOptions) ?> گزینه</span>
          </div>

          <?php if ($topupOptions): ?>
            <div style="margin-top:12px">
              <div style="font-size:.85rem;color:var(--mute);margin-bottom:8px">گزینه‌های شارژ:</div>
              <div class="tbl-wrap"><table class="tbl">
                <thead><tr><th>پیش‌نمایش</th><th>نام</th><th>مبلغ</th><th>اعتبار</th><th>واحد</th><th>وضعیت</th></tr></thead>
                <tbody>
                  <?php foreach ($topupOptions as $option):
                      $optionName = (string) ($option['name'] ?? '');
                      ?>
                    <tr>
                      <td><?= nexora_agentshop_preview($optionName, (string) ($option['btn_style'] ?? ''), (string) ($option['btn_emoji_id'] ?? ''), $GLOBALS['STYLE_COLORS']) ?></td>
                      <td><?= function_exists('nexora_emoji_text_to_html') ? nexora_emoji_text_to_html($optionName) : htmlspecialchars($optionName) ?></td>
                      <td><?= number_format((float) ($option['price'] ?? 0)) ?></td>
                      <td><?= number_format((float) ($option['credit'] ?? 0)) ?></td>
                      <td><?= htmlspecialchars((string) ($option['unit'] ?? '')) ?></td>
                      <td><?= ($option['status_product'] ?? 'active') === 'active' ? '✅ فعال' : '⛔️ پنهان' ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table></div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php
};

$renderShop(
    '🖥 خرید اجنت پنل',
    'پنل‌هایی که به نمایندگان فروخته می‌شود. نام و ایموجی هر پنل همان چیزی است که در ربات به نماینده نشان داده می‌شود.',
    $agentPanelShop['items'],
    $agentPanelShop['topup'],
    $agentPanelTopupOptions,
    'agentpanelshop',
    $agentPanelShopLink
);

$renderShop(
    '🛒 خرید پنل نمایندگی',
    'پنل‌های نمایندگی فروشگاه. نام و ایموجی هر پنل همان چیزی است که در ربات به مشتری نشان داده می‌شود.',
    $agentShop['items'],
    $agentShop['topup'],
    $agentTopupOptions,
    'agentshop',
    $agentShopLink
);
?>

<?php include __DIR__ . '/inc/layout_foot.php'; ?>
