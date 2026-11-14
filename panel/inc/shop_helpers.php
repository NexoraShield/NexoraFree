<?php
// The helpers of the shop pages (agent shop, agent panel shop, top up).

/**
 * The name of the bot of this installation (without the @).
 *
 * The answer of Telegram is kept in the cache of the files for an hour, so
 * the panel does not ask Telegram every time a page is opened.
 */
function panel_bot_username(): string
{
    static $username = null;
    if ($username !== null) {
        return $username;
    }
    $username = '';
    $cacheFile = dirname(__DIR__, 2) . '/storage/cache/panel_bot_username.txt';
    if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < 3600) {
        $cached = @file_get_contents($cacheFile);
        $username = is_string($cached) ? trim($cached) : '';
    }
    if ($username === '') {
        try {
            global $APIKEY;
            if (!empty($APIKEY)) {
                $ch = curl_init('https://api.telegram.org/bot' . $APIKEY . '/getMe');
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_CONNECTTIMEOUT => 3,
                    CURLOPT_TIMEOUT => 6,
                ]);
                $raw = curl_exec($ch);
                curl_close($ch);
                $decoded = json_decode((string) $raw, true);
                $username = trim((string) ($decoded['result']['username'] ?? ''));
                if ($username !== '') {
                    $dir = dirname($cacheFile);
                    if (!is_dir($dir)) {
                        @mkdir($dir, 0775, true);
                    }
                    @file_put_contents($cacheFile, $username, LOCK_EX);
                }
            }
        } catch (Throwable $e) {
            $username = '';
        }
    }
    return $username;
}

/**
 * The link that opens the bot of this installation.
 *
 * With a payload the customer lands right in the shop of the bot (the shop of
 * the reseller panel or the shop of the agent panel).
 */
function panel_bot_link(string $payload = ''): string
{
    $username = panel_bot_username();
    if ($username === '') {
        return '';
    }
    $link = 'https://t.me/' . $username;
    if ($payload !== '') {
        $link .= '?start=' . rawurlencode($payload);
    }
    return $link;
}

function nexora_agentshop_clean_style($v): string
{
    $v = trim((string) $v);
    return in_array($v, function_exists('nexora_color_slugs') ? nexora_color_slugs() : [], true) ? $v : '';
}
function nexora_agentshop_clean_emoji($v): string
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
function nexora_agentshop_style_select($selected, string $name = 'btn_style', string $id = ''): string
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
function nexora_agentshop_preview($name, $style, $emoji, $colors): string
{
    $bg = $colors[$style] ?? 'var(--surface-3)';
    $fg = ($style !== '' && isset($colors[$style])) ? '#fff' : 'inherit';
    $icon = function_exists('nexora_emoji_preview_html') ? nexora_emoji_preview_html($emoji) : '';
    $label = function_exists('nexora_emoji_text_to_html')
        ? nexora_emoji_text_to_html((string) $name)
        : htmlspecialchars((string) $name);
    return '<span style="display:inline-block;background:' . $bg . ';color:' . $fg . ';border-radius:8px;padding:4px 12px;font-size:.8rem">' . $icon . $label . '</span>';
}
