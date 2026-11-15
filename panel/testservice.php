<?php
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/icons.php';
$__nexoraStyleLib = __DIR__ . '/../nexora_style.php';
if (is_file($__nexoraStyleLib)) {
    require_once $__nexoraStyleLib;
}
require_once __DIR__ . '/../panel_protocol.php';
require_auth();

function nexora_testservice_clean_style($v): string
{
    $v = trim((string) $v);
    return in_array($v, nexora_color_slugs(), true) ? $v : '';
}
function nexora_testservice_clean_emoji($v): string
{
    $v = trim((string) $v);
    if ($v === '') return '';
    if (preg_match('/^\d{1,32}$/', $v)) return $v;
    if (preg_match('/emoji-id\s*=\s*"(\d{1,32})"/i', $v, $m)) return $m[1];
    if (preg_match('/^\{emoji:([^}]+)\}$/u', $v, $m) && function_exists('nexora_emoji_lookup')) {
        $row = nexora_emoji_lookup(trim($m[1]));
        if ($row && !empty($row['custom_emoji_id'])) return (string) $row['custom_emoji_id'];
    }
    return '';
}
function nexora_testservice_style_select($selected, string $name = 'btn_style', string $id = ''): string
{
    $h = '<select name="' . htmlspecialchars($name) . '"'
        . ($id !== '' ? ' id="' . htmlspecialchars($id) . '"' : '')
        . ' class="select" style="min-width:90px">';
    foreach (nexora_color_palette() as $slug => $info) {
        $label = $info['label'] ?? $slug;
        if ($slug === '') $label = 'پیش‌فرض';
        $sel = ((string) $slug === (string) $selected) ? ' selected' : '';
        $h .= '<option value="' . htmlspecialchars($slug) . '"' . $sel . '>' . htmlspecialchars($label) . '</option>';
    }
    return $h . '</select>';
}
function nexora_testservice_preview($name, $style, $emoji, $colors): string
{
    $bg = $colors[$style] ?? 'var(--surface-3)';
    $fg = ($style !== '' && isset($colors[$style])) ? '#fff' : 'inherit';
    $icon = function_exists('nexora_emoji_preview_html') ? nexora_emoji_preview_html($emoji) : '';
    $label = function_exists('nexora_emoji_text_to_html')
        ? nexora_emoji_text_to_html((string) $name)
        : htmlspecialchars((string) $name);
    return '<span style="display:inline-block;background:' . $bg . ';color:' . $fg . ';border-radius:8px;padding:4px 12px;font-size:.8rem">' . $icon . $label . '</span>';
}
// The name of the panel without the premium emoji tag (only for the display)
function nexora_testservice_panel_label(string $name): string
{
    return function_exists('panel_plain_name') ? panel_plain_name($name) : $name;
}
// The inbounds of a panel. The value is a json list, but an installation that
// saved a single inbound without json has to be read as well.
function nexora_testservice_inbounds(array $panel): array
{
    return nexora_testservice_inbound_values((string) ($panel['inbounds'] ?? ''));
}

// The inbounds of a product as a list of values
function nexora_testservice_inbound_values($raw): array
{
    $raw = trim((string) $raw);
    if ($raw === '' || $raw === 'null') {
        return [];
    }
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        $out = [];
        foreach ($decoded as $v) {
            if (is_scalar($v) && (string) $v !== '') {
                $out[] = (string) $v;
            }
        }
        return array_values(array_unique($out));
    }
    // a single value that was saved without json
    if (is_scalar($decoded) && (string) $decoded !== '') {
        return [(string) $decoded];
    }
    $out = [];
    foreach (explode(',', $raw) as $v) {
        $v = trim($v);
        if ($v !== '') {
            $out[] = $v;
        }
    }
    return array_values(array_unique($out));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') !== '') {
    csrf_check_post();
    $action = (string) $_POST['action'];
    $protocolMessage = '';
    if ($action === 'add' || $action === 'edit') {
        $name = trim($_POST['name_product'] ?? '');
        $panel = trim($_POST['namepanel'] ?? '');
        $reference = trim($_POST['protocol_account'] ?? '');
        // The panel of the test service never goes away: an empty field means
        // that the panel was not changed, not that the test has no panel. The
        // same for the account the administrator named.
        if ($panel === '' && !empty($_POST['edit_id'])) {
            $current = db_fetch($pdo, "SELECT Location, protocol_account FROM product WHERE id = ?", [(int) $_POST['edit_id']]);
            $panel = (string) ($current['Location'] ?? '');
            if ($reference === '') {
                $reference = (string) ($current['protocol_account'] ?? '');
            }
        }
        if ($name === '') {
            flash('error', 'نام سرویس تست را وارد کنید.');
        } elseif ($panel === '') {
            flash('error', 'پنل را انتخاب کنید.');
        } else {
            try {
                $inboundList = [];
                foreach ((array) ($_POST['inbounds'] ?? []) as $inboundValue) {
                    $inboundValue = trim((string) $inboundValue);
                    if ($inboundValue !== '') {
                        $inboundList[] = $inboundValue;
                    }
                }
                $inbounds = '';
                // The test service may name an account of the panel: the group
                // of that account is the group the customer of the test is
                // created in. The panel itself is not changed.
                $protocol = nexora_product_protocol_for_product($pdo, $panel, $reference, '', '');
                $inbounds = $protocol['inbounds'];
                $volumeUnit = strtolower(trim((string) ($_POST['volume_unit'] ?? 'gb'))) === 'mb' ? 'mb' : 'gb';
                $volume = nexora_volume_to_gb($_POST['volume_product'] ?? 0, $volumeUnit);
                if ($action === 'add') {
                    $code = bin2hex(random_bytes(2));
                    db_query(
                        $pdo,
                        "INSERT INTO product (name_product,code_product,price_product,Volume_constraint,Service_time,Location,agent,data_limit_reset,note,category,hide_panel,one_buy_status,status_product,is_test,btn_style,btn_emoji_id,inbounds,proxies,protocol_account) VALUES (?,?,?,?,?,?,?,'no_reset',?,?,'{}','0','active',1,?,?,?,?,?,?)",
                        [
                            $name,
                            $code,
                            0,
                            $volume,
                            (int) ($_POST['time_product'] ?? 0),
                            $panel,
                            'f',
                            (string) ($_POST['note_product'] ?? ''),
                            '',
                            nexora_testservice_clean_style($_POST['btn_style'] ?? '') ?: null,
                            nexora_testservice_clean_emoji($_POST['btn_emoji_id'] ?? '') ?: null,
                            $inbounds === '' ? null : $inbounds,
                            $protocol['proxies'] === '' ? null : $protocol['proxies'],
                            $reference !== '' ? $reference : null,
                        ]
                    );
                    $newId = (int) $pdo->lastInsertId();
                    db_query($pdo, "UPDATE product SET display_order = id WHERE id = ?", [$newId, $newId]);
                    flash('success', 'سرویس تست اضافه شد.');
                } else {
                    $id = (int) ($_POST['edit_id'] ?? 0);
                    db_query(
                        $pdo,
                        "UPDATE product SET name_product=?,Volume_constraint=?,Service_time=?,Location=?,note=?,status_product=?,is_test=1,btn_style=?,btn_emoji_id=?,inbounds=?,proxies=?,protocol_account=? WHERE id=?",
                        [
                            $name,
                            $volume,
                            (int) ($_POST['time_product'] ?? 0),
                            $panel,
                            (string) ($_POST['note_product'] ?? ''),
                            ($_POST['status_product'] ?? 'active') === 'hidden' ? 'hidden' : 'active',
                            nexora_testservice_clean_style($_POST['btn_style'] ?? '') ?: null,
                            nexora_testservice_clean_emoji($_POST['btn_emoji_id'] ?? '') ?: null,
                            $inbounds === '' ? null : $inbounds,
                            $protocol['proxies'] === '' ? null : $protocol['proxies'],
                            $reference !== '' ? $reference : null,
                            $id,
                        ]
                    );
                    if (isset($_POST['position'])) {
                        move_display_order($pdo, 'product', $id, (int) $_POST['position']);
                    }
                    flash('success', 'سرویس تست بروزرسانی شد.');
                }
                // The group of the account the administrator named: the
                // customer of the test is created in that group.
                if ($reference !== '' && !empty($protocol['message'])) {
                    flash($protocol['ok'] ? 'success' : 'error', $protocol['message']);
                }
            } catch (Exception $e) {
                flash('error', 'خطای دیتابیس: ' . $e->getMessage());
            }
        }
    } elseif ($action === 'delete') {
        db_query($pdo, "DELETE FROM product WHERE id = ? AND is_test = 1", [(int) ($_POST['edit_id'] ?? 0)]);
        flash('success', 'سرویس تست حذف شد.');
    } elseif ($action === 'save_limit') {
        $limit = max(0, (int) ($_POST['test_limit'] ?? 0));
        db_query($pdo, "UPDATE setting SET limit_usertest_all = ?", [(string) $limit]);
        if (!empty($_POST['apply_all'])) {
            db_query($pdo, "UPDATE user SET limit_usertest = ?", [$limit]);
            flash('success', 'سهمیه سرویس تست برای همه کاربران روی ' . $limit . ' تنظیم شد.');
        } else {
            flash('success', 'سهمیه سرویس تست برای کاربران جدید روی ' . $limit . ' تنظیم شد.');
        }
    }
    if ($protocolMessage !== '') {
        flash('success', $protocolMessage);
    }
    header('Location: testservice.php');
    exit;
}

$panels = panel_load_panels($pdo);
$panelLoadError = '';
if (!$panels) {
    $panelLoadError = 'هیچ پنلی در دیتابیس پیدا نشد. اگر پنل دارید، ابتدا «مدیریت پنل‌ها» را در ربات بررسی کنید یا مهاجرت دیتابیس را اجرا کنید (php migrate.php).';
    flash('error', $panelLoadError);
}
$items = db_fetchAll($pdo, "SELECT * FROM product WHERE is_test = 1 ORDER BY display_order, id");
$colors = [];
foreach (nexora_color_palette() as $slug => $info) {
    if ($slug !== '') $colors[$slug] = $info['hex'];
}
// the inbounds of every panel (for the form)
$panelInbounds = [];
foreach ($panels as $pn) {
    $panelInbounds[(string) $pn['name_panel']] = nexora_testservice_inbounds($pn);
}

$pageTitle = 'سرویس تست';
$pageLede = 'سرویس‌های تست را با نام دلخواه، حجم، مدت و پنل تعریف کنید. نام کاربری یکی از اکانت‌های موجود همان پنل را بنویسید تا سرویس تست در گروه همان اکانت ساخته شود (اگر خالی بماند، گروه پیش‌فرض پنل استفاده می‌شود).';
$activeNav = 'testservice';
include __DIR__ . '/inc/layout_head.php';
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px" class="fade-up">
  <div style="font-size:.85rem;color:var(--mute)"><?= count($items) ?> سرویس تست</div>
  <button class="btn btn-primary" onclick="openModal('addModal')"><?= icon('plus', 14) ?> افزودن سرویس تست</button>
</div>

<?php include __DIR__ . '/inc/emoji_picker_bar.php'; ?>

<?php
$testLimit = (int) (db_fetch($pdo, "SELECT limit_usertest_all FROM setting LIMIT 1")['limit_usertest_all'] ?? 1);
$testUsersWith = (int) db_count($pdo, "SELECT COUNT(*) FROM user WHERE limit_usertest > 0");
$testUsersTotal = (int) db_count($pdo, "SELECT COUNT(*) FROM user");
?>
<div class="card fade-up d1" style="margin-bottom:18px;border-right:3px solid var(--ac)">
  <div class="card-head">
    <div>
      <div class="card-title">🧪 سهمیه سرویس تست هر کاربر</div>
      <div class="card-subtitle">هر کاربری که به ربات جوین می‌شود می‌تواند به این تعداد سرویس تست باز کند. بعد از آن ربات به او اجازه سرویس تست بیشتری نمی‌دهد.</div>
    </div>
  </div>
  <div class="card-body">
    <div class="kv-list" style="margin-bottom:14px">
      <div class="kv">
        <span class="kv-key">سهمیه هر کاربر</span>
        <span class="kv-val"><?= $testLimit ?> سرویس تست</span>
      </div>
      <div class="kv">
        <span class="kv-key">کاربرانی که هنوز تست دارند</span>
        <span class="kv-val"><?= $testUsersWith ?> از <?= $testUsersTotal ?></span>
      </div>
      <div class="kv">
        <span class="kv-key">سرویس‌های تعریف‌شده</span>
        <span class="kv-val"><?= count($items) ?></span>
      </div>
    </div>
    <form method="POST" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
      <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
      <input type="hidden" name="action" value="save_limit">
      <div class="field" style="max-width:180px">
        <label>تعداد سرویس تست هر کاربر</label>
        <input class="input" type="number" name="test_limit" min="0" value="<?= $testLimit ?>">
      </div>
      <label style="display:flex;align-items:center;gap:6px;font-size:.8rem;color:var(--mute);margin-bottom:10px">
        <input type="checkbox" name="apply_all" value="1"> برای همه کاربران فعلی هم اعمال شود
      </label>
      <button type="submit" class="btn btn-primary" style="margin-bottom:2px">💾 ذخیره سهمیه</button>
    </form>
  </div>
</div>

<?php if (!$panels): ?>
  <div class="card fade-up d1" style="margin-bottom:18px;border-right:3px solid var(--no)">
    <div class="card-body">
      <div class="card-title" style="color:var(--no)">⚠️ پنلی برای انتخاب وجود ندارد</div>
      <p style="margin-top:8px;color:var(--mute)"><?= htmlspecialchars($panelLoadError) ?></p>
    </div>
  </div>
<?php endif; ?>

<div class="card fade-up d1" style="margin-bottom:18px">
  <div class="tbl-wrap"><table class="tbl">
    <thead><tr><th>پیش‌نمایش</th><th>نام</th><th>حجم (GB)</th><th>مدت (ساعت)</th><th>پنل</th><th>اینباند</th><th>توضیحات</th><th>رنگ</th><th>ایموجی</th><th>وضعیت</th><th>ترتیب</th><th></th></tr></thead>
    <tbody>
      <?php if (!$items): ?>
        <tr><td colspan="12" style="text-align:center;color:var(--mute);padding:22px">هنوز سرویس تستی تعریف نشده است. تا وقتی سرویس تست تعریف نکنید، سرویس تست قبلی (بر اساس تنظیمات هر پنل) مثل قبل کار می‌کند.</td></tr>
      <?php endif; ?>
      <?php foreach ($items as $it):
        $inbounds = nexora_testservice_inbound_values((string) ($it['inbounds'] ?? ''));
        $inboundText = $inbounds ? implode('، ', $inbounds) : 'پیش‌فرض پنل';
      ?>
        <tr>
          <td><?= nexora_testservice_preview($it['name_product'] ?? '', (string) ($it['btn_style'] ?? ''), (string) ($it['btn_emoji_id'] ?? ''), $colors) ?></td>
          <td><?= function_exists('nexora_emoji_text_to_html') ? nexora_emoji_text_to_html((string) ($it['name_product'] ?? '')) : htmlspecialchars((string) ($it['name_product'] ?? '')) ?></td>
          <td><?= htmlspecialchars(nexora_format_volume($it['Volume_constraint'] ?? 0) ?: '—') ?></td>
          <td><?= (int) ($it['Service_time'] ?? 0) ?></td>
          <td><?= function_exists('nexora_emoji_text_to_html') ? nexora_emoji_text_to_html((string) ($it['Location'] ?? '')) : htmlspecialchars((string) ($it['Location'] ?? '')) ?></td>
          <td style="min-width:160px"><?= htmlspecialchars($inboundText) ?></td>
          <td style="min-width:200px"><?= nl2br(htmlspecialchars((string) ($it['note'] ?? ''))) ?></td>
          <td><span class="pill"><?= htmlspecialchars((string) ($it['btn_style'] ?? 'پیش‌فرض')) ?></span></td>
          <td><?= nexora_emoji_preview_html((string) ($it['btn_emoji_id'] ?? '')) ?: '<span class="cf">—</span>' ?></td>
          <td><?= ($it['status_product'] ?? 'active') === 'active' ? '✅ فعال' : '⛔️ پنهان' ?></td>
          <td><?= (int) ($it['display_order'] ?? 0) ?></td>
          <td style="white-space:nowrap">
            <button class="btn btn-primary btn-sm" type="button" onclick='openEditTest(<?= json_encode([
                'id' => (int) $it['id'],
                'name_product' => (string) ($it['name_product'] ?? ''),
                'volume_product' => (float) ($it['Volume_constraint'] ?? 0),
                'volume_unit' => nexora_volume_unit($it['Volume_constraint'] ?? 0),
                'time_product' => (int) ($it['Service_time'] ?? 0),
                'namepanel' => (string) ($it['Location'] ?? ''),
                'note_product' => (string) ($it['note'] ?? ''),
                'btn_style' => (string) ($it['btn_style'] ?? ''),
                'btn_emoji_id' => (string) ($it['btn_emoji_id'] ?? ''),
                'status_product' => (string) ($it['status_product'] ?? 'active'),
                'position' => (int) ($it['display_order'] ?? 0),
                'inbounds' => $inbounds,
            ], JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>✏️ ویرایش</button>
            <form method="POST" style="display:inline">
              <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="edit_id" value="<?= (int) $it['id'] ?>">
              <button class="btn btn-no btn-sm" type="submit" data-confirm="این سرویس تست حذف شود؟">حذف</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table></div>
</div>

<?php
// the form of the inbounds (shared by the add and the edit modal)
?>

<div class="modal-veil" id="addModal">
  <div class="modal">
    <div class="modal-head">
      <h3>افزودن سرویس تست</h3>
      <button class="modal-x" onclick="closeModal('addModal')"><?= icon('close', 14) ?></button>
    </div>
    <form method="POST">
      <div class="modal-body">
        <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="action" value="add">
        <div class="form-grid">
          <label class="field"><span>نام سرویس تست</span><input class="input" name="name_product" required></label>
          <label class="field"><span>حجم</span>
            <div style="display:flex;gap:8px">
              <input class="input" type="number" name="volume_product" value="1" step="any" min="0" required style="flex:1">
              <select name="volume_unit" class="select" style="max-width:120px">
                <option value="gb">گیگابایت</option>
                <option value="mb">مگابایت</option>
              </select>
            </div>
            <small style="display:block;color:var(--mute);font-size:.72rem;margin-top:5px">حجم را می‌توانید به مگابایت یا گیگابایت وارد کنید.</small>
          </label>
          <label class="field"><span>مدت (ساعت)</span><input class="input" type="number" name="time_product" value="1" required></label>
          <label class="field"><span>پنل</span>
            <select name="namepanel" id="ts_add_panel" class="select" required>
              <option value="">— انتخاب کنید —</option>
              <?php foreach ($panels as $pn): ?>
                <option value="<?= htmlspecialchars((string) $pn['name_panel']) ?>"><?= htmlspecialchars(nexora_testservice_panel_label((string) $pn['name_panel'])) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label class="field"><span>نام کاربری یک اکانت داخل همین پنل (برای تنظیم پروتکل و اینباند)</span>
            <input class="input" name="protocol_account" placeholder="مثلاً نام کاربری یکی از اکانت‌های موجود در پنل">
            <small style="display:block;color:var(--mute);font-size:.72rem;margin-top:5px">دقیقاً مثل «تنظیم پروتکل و اینباند» در ربات: با این اکانت، پروتکل و اینباند پنل تنظیم می‌شود.</small>
          </label>
          <label class="field"><span>رنگ دکمه</span><?= nexora_testservice_style_select('') ?></label>
          <label class="field"><span>ایموجی پرمیوم</span><input class="input ms-emoji-field" data-ms-max="1" name="btn_emoji_id" placeholder="آیدی عددی یا {emoji:نام}"></label>
          <label class="field" style="grid-column:1/-1"><span>توضیحات (برای مشتری)</span><textarea class="input" name="note_product" rows="2"></textarea></label>
        </div>
      </div>
      <div class="modal-foot">
        <button class="btn btn-primary" type="submit">افزودن سرویس تست</button>
        <button class="btn btn-ghost" type="button" onclick="closeModal('addModal')">انصراف</button>
      </div>
    </form>
  </div>
</div>

<div class="modal-veil" id="editModal">
  <div class="modal">
    <div class="modal-head">
      <h3>ویرایش سرویس تست</h3>
      <button class="modal-x" onclick="closeModal('editModal')"><?= icon('close', 14) ?></button>
    </div>
    <form method="POST">
      <div class="modal-body">
        <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="edit_id" id="ts_edit_id">
        <div class="form-grid">
          <label class="field"><span>نام سرویس تست</span><input class="input" name="name_product" id="ts_edit_name" required></label>
          <label class="field"><span>حجم</span>
            <div style="display:flex;gap:8px">
              <input class="input" type="number" name="volume_product" id="ts_edit_volume" value="0" step="any" min="0" required style="flex:1">
              <select name="volume_unit" id="ts_edit_volume_unit" class="select" style="max-width:120px">
                <option value="gb">گیگابایت</option>
                <option value="mb">مگابایت</option>
              </select>
            </div>
            <small style="display:block;color:var(--mute);font-size:.72rem;margin-top:5px">حجم را می‌توانید به مگابایت یا گیگابایت وارد کنید.</small>
          </label>
          <label class="field"><span>مدت (ساعت)</span><input class="input" type="number" name="time_product" id="ts_edit_time" value="0" required></label>
          <label class="field"><span>پنل</span>
            <select name="namepanel" id="ts_edit_panel" class="select" required>
              <?php foreach ($panels as $pn): ?>
                <option value="<?= htmlspecialchars((string) $pn['name_panel']) ?>"><?= htmlspecialchars(nexora_testservice_panel_label((string) $pn['name_panel'])) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label class="field"><span>نام کاربری یک اکانت داخل همین پنل (برای تنظیم پروتکل و اینباند)</span>
            <input class="input" name="protocol_account" id="ts_edit_protocol" placeholder="اختیاری — مثلاً نام کاربری یکی از اکانت‌های موجود در پنل">
            <small style="display:block;color:var(--mute);font-size:.72rem;margin-top:5px">اگر پر شود، پروتکل و اینباند پنل با اطلاعات همین اکانت تنظیم می‌شود.</small>
          </label>
          <label class="field"><span>رنگ دکمه</span><?= nexora_testservice_style_select('', 'btn_style', 'ts_edit_style') ?></label>
          <label class="field"><span>ایموجی پرمیوم</span><input class="input ms-emoji-field" data-ms-max="1" name="btn_emoji_id" id="ts_edit_emoji" placeholder="آیدی عددی یا {emoji:نام}"></label>
          <label class="field"><span>وضعیت</span>
            <select name="status_product" id="ts_edit_status" class="select">
              <option value="active">فعال</option>
              <option value="hidden">پنهان</option>
            </select>
          </label>
          <label class="field"><span>ترتیب نمایش</span><input class="input" type="number" name="position" id="ts_edit_position" value="0"></label>
          <label class="field" style="grid-column:1/-1"><span>توضیحات (برای مشتری)</span><textarea class="input" name="note_product" id="ts_edit_note" rows="2"></textarea></label>
        </div>
      </div>
      <div class="modal-foot">
        <button class="btn btn-primary" type="submit"><?= icon('check', 13) ?> ذخیره</button>
        <button class="btn btn-ghost" type="button" onclick="closeModal('editModal')">انصراف</button>
      </div>
    </form>
  </div>
</div>

<script>
window.openEditTest = function (p) {
    p = p || {};
    document.getElementById('ts_edit_id').value = p.id || '';
    document.getElementById('ts_edit_name').value = p.name_product || '';
    var tsVolume = parseFloat(p.volume_product) || 0;
    var tsUnit = p.volume_unit || 'gb';
    document.getElementById('ts_edit_volume').value = tsUnit === 'mb' ? Math.round(tsVolume * 1024 * 100) / 100 : tsVolume;
    var tsUnitSelect = document.getElementById('ts_edit_volume_unit');
    if (tsUnitSelect) {
        tsUnitSelect.value = tsUnit;
    }
    document.getElementById('ts_edit_time').value = p.time_product || 0;
    document.getElementById('ts_edit_note').value = p.note_product || '';
    document.getElementById('ts_edit_emoji').value = p.btn_emoji_id || '';
    document.getElementById('ts_edit_position').value = p.position || 0;
    document.getElementById('ts_edit_status').value = p.status_product || 'active';
    // The account the administrator named is the one the test is made with.
    document.getElementById('ts_edit_protocol').value = p.protocol_account || '';
    // The panel of the test: the location of the row is the name of the panel.
    var sel = document.getElementById('ts_edit_panel');
    if (sel) {
        var tsLocation = p.Location || p.namepanel || '';
        for (var i = 0; i < sel.options.length; i++) {
            sel.options[i].selected = sel.options[i].value === tsLocation;
        }
    }
    var st = document.getElementById('ts_edit_style');
    if (st) {
        for (var k = 0; k < st.options.length; k++) {
            st.options[k].selected = st.options[k].value === (p.btn_style || '');
        }
    }
    openModal('editModal');
};
</script>
<script src="js/emoji-insert.js"></script>

<?php include __DIR__ . '/inc/layout_foot.php'; ?>
