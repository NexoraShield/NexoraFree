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
    // The free version has no premium emoji of telegram: no id is kept.
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
/**
 * The picture of a button of a shop, the way the bot draws it.
 *
 * The name of the product is the one the owner has chosen, the premium emoji
 * is the picture of the emoji of the bot and the colour is the colour of the
 * button. The two shops (the reseller panel and the agent panel) draw their
 * buttons the same way, so the picture is made here once.
 */
function nexora_shop_preview($name, $style, $emoji, $colors): string
{
    $bg = $colors[$style] ?? 'var(--surface-3)';
    $fg = ($style !== '' && isset($colors[$style])) ? '#fff' : 'inherit';
    $icon = function_exists('nexora_emoji_preview_html') ? nexora_emoji_preview_html($emoji) : '';
    $label = function_exists('nexora_emoji_text_to_html')
        ? nexora_emoji_text_to_html((string) $name)
        : htmlspecialchars((string) $name);
    return '<span style="display:inline-block;background:' . $bg . ';color:' . $fg . ';border-radius:8px;padding:4px 12px;font-size:.8rem">' . $icon . $label . '</span>';
}
function nexora_agentshop_preview($name, $style, $emoji, $colors): string
{
    return nexora_shop_preview($name, $style, $emoji, $colors);
}
/**
 * The picture of a button of the shop of the agent panel.
 *
 * The page of the shops shows both of them, so the picture of the button of
 * the agent panel has to be here as well (the tab of the agent panel is not
 * loaded there).
 */
function nexora_agentpanel_preview($name, $style, $emoji, $colors): string
{
    return nexora_shop_preview($name, $style, $emoji, $colors);
}
