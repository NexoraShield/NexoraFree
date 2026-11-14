<?php
/**
 * The update of the bot.
 *
 * The bot is updated by the hub of the fleet (the panel of the bots): the hub
 * stops the bot, writes the new code, runs the migration of the database and
 * starts the bot again. The panel of the bot only asks the hub to do it, so
 * the bot is never half written and the update uses the same script on every
 * bot of the fleet.
 *
 * There are two channels of the release: the stable one and the test one. The
 * hub decides which of them is the stable one (the tab "channels" of the hub
 * panel), the owner of the bot decides which one this bot takes.
 */
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/icons.php';
require_auth();

/**
 * Asks the hub of the fleet something and returns its answer.
 *
 * The answer of the hub is always a json: the panel of the bot never reads
 * anything else, so a broken hub can never break the panel.
 */
function nexora_update_ask(string $hubUrl, string $path, array $data): array
{
    if ($hubUrl === '') {
        return ['ok' => false, 'error' => 'the hub of the fleet is not known yet'];
    }
    $url = $hubUrl . '/' . ltrim($path, '/');
    $body = http_build_query($data, '', '&', PHP_QUERY_RFC3986);
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\nUser-Agent: NexoraPanel\r\n",
            'content' => $body,
            'timeout' => 30,
            // The answer of the hub is read even when it is an error.
            'ignore_errors' => true,
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ]);
    $raw = @file_get_contents($url, false, $context);
    if ($raw === false) {
        return ['ok' => false, 'error' => 'the hub of the fleet did not answer'];
    }
    $answer = json_decode($raw, true);
    if (!is_array($answer)) {
        return ['ok' => false, 'error' => 'the answer of the hub of the fleet is not clear'];
    }
    return $answer;
}

// The channels of the release. The hub of the fleet writes them into the
// config of the bot, so the panel always knows which one is the stable one.
$stableChannel = trim((string) ($nexora_channel_stable ?? ''));
$testChannel = trim((string) ($nexora_channel_test ?? ''));
$hubUrl = rtrim(trim((string) ($nexora_hub_url ?? '')), '/');
$hubToken = trim((string) ($nexora_hub_token ?? ''));
$domain = trim((string) ($domainhosts ?? ''));
$versionFile = dirname(__DIR__) . '/version';
$version = is_file($versionFile) ? trim((string) @file_get_contents($versionFile)) : '';
$hubReady = ($hubUrl !== '' && $hubToken !== '' && $domain !== '');

// The browser asks for the state of the update.
if (isset($_GET['state'])) {
    header('Content-Type: application/json; charset=utf-8');
    if (!$hubReady) {
        echo json_encode(['state' => 'idle', 'log' => '', 'version' => $version], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    $answer = nexora_update_ask($hubUrl, 'bot-update-state.php', [
        'domain' => $domain,
        'token' => $hubToken,
    ]);
    $answer['version'] = (string) ($answer['version'] ?? $version);
    echo json_encode($answer, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// The owner of the bot wants to update it.
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && (string) ($_POST['action'] ?? '') === 'update') {
    csrf_check_post();
    $channel = (string) ($_POST['channel'] ?? '');
    if (!in_array($channel, ['stable', 'test'], true)) {
        flash('error', 'The version of the update was not understood.');
    } elseif (!$hubReady) {
        flash('error', 'The hub of the fleet is not known yet, so the bot cannot update itself.');
    } else {
        $answer = nexora_update_ask($hubUrl, 'bot-update.php', [
            'domain' => $domain,
            'token' => $hubToken,
            'channel' => $channel,
        ]);
        if (!empty($answer['ok'])) {
            flash('success', 'The update of the bot has started. The page follows the log.');
        } else {
            flash('error', (string) ($answer['error'] ?? 'The update could not be started.'));
        }
    }
    header('Location: update.php');
    exit;
}

$pageTitle = '⬆️ آپدیت ربات';
$pageLede = 'ربات را با نسخهٔ پایدار یا با نسخهٔ آزمایشی به‌روزرسانی کن. هاب خودش کار را انجام می‌دهد و ربات در این مدت خاموش نمی‌ماند.';
$activeNav = 'update';
include __DIR__ . '/inc/layout_head.php';
?>

<div class="card fade-up d1">
    <div class="card-head">
        <div>
            <div class="card-title">⬆️ آپدیت ربات</div>
            <div class="card-subtitle">نسخهٔ پایدار برای همیشه و نسخهٔ آزمایشی برای کسی که می‌خواهد زودتر چیزهای تازه را ببیند.</div>
        </div>
    </div>
    <div class="card-body">
        <div class="tbl-wrap">
            <table class="tbl">
                <tbody>
                    <tr>
                        <td style="width:180px">نسخهٔ هم‌اکنون</td>
                        <td dir="ltr"><?= htmlspecialchars($version !== '' ? $version : '—') ?></td>
                    </tr>
                    <tr>
                        <td>نسخهٔ پایدار</td>
                        <td dir="ltr"><?= htmlspecialchars($stableChannel !== '' ? $stableChannel : '—') ?></td>
                    </tr>
                    <tr>
                        <td>نسخهٔ آزمایشی</td>
                        <td dir="ltr"><?= htmlspecialchars($testChannel !== '' ? $testChannel : '—') ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <?php if (!$hubReady): ?>
            <div class="notice notice-no" style="margin-top:14px">
                هاب هنوز این ربات را نشناخته است. از پنل هاب (<span dir="ltr">botsmonitor.nexorashield.com.tr</span>) تب «کانال نسخه‌ها» را باز کن و دکمهٔ «همگام‌سازی» را بزن تا این ربات هم آپدیت شود.
            </div>
        <?php endif; ?>

        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:16px">
            <button type="button" class="btn btn-primary" onclick="openModal('updateModal')" <?= $hubReady ? '' : 'disabled' ?>>
                ⬆️ آپدیت ربات
            </button>
            <a href="update.php" class="btn btn-ghost">↻ تازه‌سازی صفحه</a>
        </div>

        <div id="updateState" class="notice notice-warn" style="margin-top:14px;display:none">
            <span id="updateStateText">در حال بررسی وضعیت آپدیت…</span>
        </div>

        <pre id="updateLog" dir="ltr"
            style="margin-top:12px;max-height:340px;overflow:auto;display:none;background:var(--sf2);border:1px solid var(--bd);border-radius:10px;padding:14px;font-family:var(--mono);font-size:.72rem;line-height:1.7;color:var(--text2);white-space:pre-wrap;word-break:break-word"></pre>
    </div>
</div>

<div class="modal-veil" id="updateModal">
    <div class="modal" style="width:min(460px,100%)">
        <div class="modal-head">
            <h3>⬆️ آپدیت ربات</h3>
            <button class="modal-x" onclick="closeModal('updateModal')"><?= icon('close', 14) ?></button>
        </div>
        <div class="modal-body" style="line-height:1.9;font-size:.86rem;color:var(--mute)">
            کدام نسخه می‌خواهی ربات روی آن برود؟ نسخهٔ آزمایشی چیزهای تازه دارد، ولی ممکن است ایراد داشته باشد.
        </div>
        <div class="modal-foot" style="flex-wrap:wrap;gap:8px">
            <button type="button" class="btn btn-primary" onclick="nexoraStartUpdate('stable')">
                ✅ نسخهٔ پایدار
            </button>
            <button type="button" class="btn btn-ghost" onclick="nexoraStartUpdate('test')">
                🧪 نسخهٔ آزمایشی
            </button>
            <button type="button" class="btn btn-ghost" onclick="closeModal('updateModal')">انصراف</button>
        </div>
    </div>
</div>

<script>
    (function () {
        var timer = null;

        window.nexoraStartUpdate = function (channel) {
            var label = channel === 'test' ? 'نسخهٔ آزمایشی' : 'نسخهٔ پایدار';
            showConfirm('ربات روی «' + label + '» به‌روزرسانی شود؟', function () {
                closeModal('updateModal');
                var form = document.getElementById('updateForm');
                form.querySelector('input[name="channel"]').value = channel;
                form.submit();
            }, 'آپدیت ربات');
        };

        window.nexoraUpdateState = function () {
            fetch('update.php?state=1', { credentials: 'same-origin', cache: 'no-store' })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    var box = document.getElementById('updateState');
                    var text = document.getElementById('updateStateText');
                    var log = document.getElementById('updateLog');
                    box.style.display = 'block';
                    if (data.log) {
                        log.style.display = 'block';
                        log.textContent = data.log;
                    }
                    if (data.state === 'running') {
                        text.textContent = 'آپدیت در حال اجراست… لطفاً صفحه را نبندید.';
                        if (!timer) timer = setInterval(window.nexoraUpdateState, 4000);
                    } else if (data.state === 'done') {
                        text.textContent = 'آپدیت تمام شد. نسخهٔ تازه: ' + (data.version || '—');
                        if (timer) { clearInterval(timer); timer = null; }
                    } else if (data.state === 'failed') {
                        text.textContent = 'آپدیت کامل نشد. لاگ زیر را ببینید.';
                        if (timer) { clearInterval(timer); timer = null; }
                    } else {
                        text.textContent = 'آپدیتی در جریان نیست.';
                        if (timer) { clearInterval(timer); timer = null; }
                    }
                })
                .catch(function () { /* the hub is quiet: the page waits for the next try */ });
        };

        window.nexoraUpdateState();
    })();
</script>

<form method="post" id="updateForm" style="display:none">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="action" value="update">
    <input type="hidden" name="channel" value="stable">
</form>

<?php include __DIR__ . '/inc/layout_foot.php'; ?>
