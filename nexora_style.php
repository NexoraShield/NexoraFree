<?php
/**
 * nexoraStyle — button colors + premium emoji library for nexorabot.
 * Additive only: no existing behavior changes when btn_style / btn_emoji_id
 * are empty and no {emoji:} placeholders are used.
 */

if (!function_exists('nexora_clear_table_cache')) {
    /**
     * Drops the file cache of a table.
     *
     * The settings of the bot and of the shop are read from the file cache,
     * so a change in the web panel has to drop it: the bot picks the new
     * value right away.
     */
    function nexora_clear_table_cache(string $table): void
    {
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        if ($table === '') {
            return;
        }
        $pcDir = __DIR__ . '/storage/cache';
        $pcFiles = glob($pcDir . '/pc_' . $table . '_*.json');
        if (is_array($pcFiles)) {
            foreach ($pcFiles as $pcFile) {
                @unlink($pcFile);
            }
        }
    }
}

if (!function_exists('nexora_color_palette')) {
    function nexora_color_palette(): array
    {
        return [
            ''        => ['label' => 'پیش‌فرض', 'hex' => '#888888', 'native' => ''],
            'primary' => ['label' => 'آبی', 'hex' => '#3390ec', 'native' => 'primary'],
            'success' => ['label' => 'سبز', 'hex' => '#4fae4e', 'native' => 'success'],
            'danger'  => ['label' => 'قرمز', 'hex' => '#e17076', 'native' => 'danger'],
            'warning' => ['label' => 'نارنجی', 'hex' => '#f59e0b', 'native' => 'danger'],
            'info'    => ['label' => 'آبی روشن', 'hex' => '#38bdf8', 'native' => 'primary'],
            'purple'  => ['label' => 'بنفش', 'hex' => '#8a2be2', 'native' => 'primary'],
            'pink'    => ['label' => 'صورتی', 'hex' => '#ec4899', 'native' => 'danger'],
            'teal'    => ['label' => 'فیروزه‌ای', 'hex' => '#14b8a6', 'native' => 'success'],
            'gray'    => ['label' => 'خاکستری', 'hex' => '#6b7280', 'native' => ''],
            'orange'  => ['label' => 'نارنجی تیره', 'hex' => '#ea580c', 'native' => 'danger'],
            'yellow'  => ['label' => 'زرد', 'hex' => '#eab308', 'native' => 'warning'],
            'indigo'  => ['label' => 'نیلی', 'hex' => '#6366f1', 'native' => 'primary'],
            'cyan'    => ['label' => 'فیروزه', 'hex' => '#06b6d4', 'native' => 'primary'],
            'lime'    => ['label' => 'لیمویی', 'hex' => '#84cc16', 'native' => 'success'],
            'amber'   => ['label' => 'کهربایی', 'hex' => '#d97706', 'native' => 'warning'],
            'rose'    => ['label' => 'رز', 'hex' => '#f43f5e', 'native' => 'danger'],
            'violet'  => ['label' => 'بنفش کمرنگ', 'hex' => '#a78bfa', 'native' => 'primary'],
        ];
    }
}
if (!function_exists('nexora_color_slugs')) {
    function nexora_color_slugs(): array { return array_keys(nexora_color_palette()); }
}
if (!function_exists('nexora_btn_style')) {
    function nexora_btn_style($style): string
    {
        $style = trim((string) $style);
        $palette = nexora_color_palette();
        if (!isset($palette[$style])) return '';
        return $palette[$style]['native'];
    }
}

if (!function_exists('nexora_btn_emoji_id')) {
    function nexora_btn_emoji_id($raw): string
    {
        // The free version never uses the premium emoji of telegram.
        return '';
    }
}

if (!function_exists('nexora_ensure_style_columns')) {
    function nexora_ensure_style_columns(PDO $pdo): array
    {
        $targets = [
            'product' => [['btn_style', 'VARCHAR(20) NULL'], ['btn_emoji_id', 'VARCHAR(32) NULL'], ['display_order', 'INT NOT NULL DEFAULT 0'], ['is_test', 'TINYINT(1) NOT NULL DEFAULT 0']],
            'category' => [['btn_style', 'VARCHAR(20) NULL'], ['btn_emoji_id', 'VARCHAR(32) NULL'], ['display_order', 'INT NULL DEFAULT 0']],
            'marzban_panel' => [['btn_style', 'VARCHAR(20) NULL'], ['btn_emoji_id', 'VARCHAR(32) NULL'], ['display_order', 'INT NOT NULL DEFAULT 0']],
            'invoice' => [['id_payment', 'VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL']],
            'service_other' => [['id_payment', 'VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL']],
        ];
        $added = [];
        foreach ($targets as $table => $cols) {
            foreach ($cols as [$col, $def]) {
                $check = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
                $check->execute([$table, $col]);
                if ((int) $check->fetchColumn() === 0) {
                    $pdo->exec("ALTER TABLE `{$table}` ADD `{$col}` {$def}");
                    $added[] = "{$table}.{$col}";
                }
            }
        }
        return $added;
    }
}

if (!function_exists('nexora_ensure_emoji_table')) {
    function nexora_ensure_emoji_table($pdo = null): void
    {
        // The free version has no library of the premium emoji: no table is
        // needed.
    }
}



if (!function_exists('nexora_emoji_list')) {
    function nexora_emoji_list($pdo = null, bool $fresh = false): array
    {
        // The free version has no library of the premium emoji.
        return [];
    }
}

if (!function_exists('nexora_emoji_lookup')) {
    function nexora_emoji_lookup(string $ref, $pdo = null): ?array
    {
        // The free version has no library of the premium emoji.
        return null;
    }
}



if (!function_exists('nexora_emoji_image_url')) {
    /**
     * The address of the picture of a premium emoji.
     *
     * Telegram serves every custom emoji as a small picture, so the web panel
     * can show the very same emoji the bot uses in its buttons.
     */
    function nexora_emoji_image_url($ref): string
    {
        // The free version never draws the premium emoji.
        return '';
    }
}

if (!function_exists('nexora_emoji_text_to_html')) {
    /**
     * Turns a label that may carry premium emoji tags into HTML.
     *
     * The tag becomes the picture of the custom emoji, so the web panel shows
     * the same emoji the bot uses instead of the raw "<tg-emoji ...>" text.
     */
    function nexora_emoji_text_to_html(string $text): string
    {
        if ($text === '') {
            return '';
        }
        // The free version shows the plain text: the premium emoji is not
        // drawn as a picture.
        $plain = function_exists('stripCustomEmojiTags')
            ? stripCustomEmojiTags($text)
            : $text;
        return htmlspecialchars($plain);
    }
}

if (!function_exists('nexora_emoji_preview_html')) {    /**
     * The premium emoji of a button as a picture.
     *
     * When the emoji cannot be resolved, nothing is shown at all: the raw id
     * is never displayed, because it means nothing to the administrator.
     */
    function nexora_emoji_preview_html($ref, string $class = 'nx-emoji'): string
    {
        // The free version has no picture of a premium emoji.
        return '';
    }
}









if (!function_exists('nexora_resolve_btn_label')) {
    function nexora_resolve_btn_label(string $raw): array
    {
        try {
            if (strpos($raw, '{emoji:') === false) {
                return ['text' => $raw, 'icon' => ''];
            }
            $icon = '';
            $used = false;
            $text = preg_replace_callback('/\{emoji:([^}]+)\}/u', function ($m) use (&$icon, &$used) {
                try { $row = nexora_emoji_lookup(trim($m[1])); } catch (Throwable $e) { return ''; }
                if (!$row || empty($row['custom_emoji_id'])) return '';
                if (!$used) {
                    $used = true;
                    $icon = (string) $row['custom_emoji_id'];
                }
                return '';
            }, $raw);
            $text = trim(preg_replace('/\s{2,}/u', ' ', (string) $text));
            return ['text' => $text, 'icon' => $icon];
        } catch (Throwable $e) {
            return ['text' => $raw, 'icon' => ''];
        }
    }
}

if (!function_exists('nexora_resolve_btn_emoji_field')) {
    function nexora_resolve_btn_emoji_field($raw): string
    {
        // The free version has no premium emoji: the field is always empty.
        return '';
    }
}

if (!function_exists('nexora_render_text_emoji')) {
    function nexora_render_text_emoji(string $text): string
    {
        // The free version has no premium emoji of telegram: the code of an
        // emoji is taken out of the text and nothing else is changed.
        try {
            if (strpos($text, '{emoji:') === false) return $text;
            $out = preg_replace('/\{emoji:[^}]+\}/u', '', $text);
            return trim(preg_replace('/\s{2,}/u', ' ', (string) $out));
        } catch (Throwable $e) { return $text; }
    }
}

if (!function_exists('nexora_strip_placeholders')) {
    function nexora_strip_placeholders(string $text): string
    {
        try {
            if (strpos($text, '{emoji:') === false) return $text;
            $out = preg_replace('/\{emoji:[^}]+\}/u', '', $text);
            $out = trim(preg_replace('/\s{2,}/u', ' ', (string) $out));
            return $out;
        } catch (Throwable $e) { return $text; }
    }
}

if (!function_exists('nexora_render_markup_emoji')) {
    function nexora_render_markup_emoji(string $json): string
    {
        try {
            if (strpos($json, '{emoji:') === false) return $json;
            $markup = json_decode($json, true);
            if (!is_array($markup)) return $json;
            $changed = false;
            foreach (['keyboard', 'inline_keyboard'] as $mkey) {
                if (!isset($markup[$mkey]) || !is_array($markup[$mkey])) continue;
                foreach ($markup[$mkey] as $rk => $row) {
                    if (!is_array($row)) continue;
                    foreach ($row as $bk => $btn) {
                        if (!is_array($btn) || !isset($btn['text']) || !is_string($btn['text'])) continue;
                        if (strpos($btn['text'], '{emoji:') === false) continue;
                        $r = nexora_resolve_btn_label($btn['text']);
                        $btn['text'] = $r['text'] !== '' ? $r['text'] : $btn['text'];
                        if ($r['icon'] !== '' && !isset($btn['icon_custom_emoji_id'])) {
                            $btn['icon_custom_emoji_id'] = $r['icon'];
                        }
                        $markup[$mkey][$rk][$bk] = $btn;
                        $changed = true;
                    }
                }
            }
            if (!$changed) return $json;
            $enc = json_encode($markup, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return $enc === false ? $json : $enc;
        } catch (Throwable $e) { return $json; }
    }
}

if (!function_exists('nexora_strip_markup_placeholders')) {
    function nexora_strip_markup_placeholders(string $json): string
    {
        try {
            if (strpos($json, '{emoji:') === false) return $json;
            $markup = json_decode($json, true);
            if (!is_array($markup)) return $json;
            $changed = false;
            foreach (['keyboard', 'inline_keyboard'] as $mkey) {
                if (!isset($markup[$mkey]) || !is_array($markup[$mkey])) continue;
                foreach ($markup[$mkey] as $rk => $row) {
                    if (!is_array($row)) continue;
                    foreach ($row as $bk => $btn) {
                        if (!is_array($btn) || !isset($btn['text']) || !is_string($btn['text'])) continue;
                        if (strpos($btn['text'], '{emoji:') === false) continue;
                        $btn['text'] = nexora_strip_placeholders($btn['text']);
                        unset($btn['icon_custom_emoji_id']);
                        $markup[$mkey][$rk][$bk] = $btn;
                        $changed = true;
                    }
                }
            }
            if (!$changed) return $json;
            $enc = json_encode($markup, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return $enc === false ? $json : $enc;
        } catch (Throwable $e) { return $json; }
    }
}

if (!function_exists('nexora_style_btn')) {
    function nexora_style_btn($text, $callback_data, $row = []): array
    {
        try {
            if (!is_array($row)) $row = [];
            $resolved = nexora_resolve_btn_label((string) $text);
            $label = $resolved['text'] !== '' ? $resolved['text'] : (string) $text;
            $btn = ['text' => $label, 'callback_data' => (string) $callback_data];
            // The free version has no premium emoji: a button never gets an
            // icon.
            unset($btn['icon_custom_emoji_id']);
            $style = nexora_btn_style($row['btn_style'] ?? '');
            if ($style !== '') $btn['style'] = $style;
            return $btn;
        } catch (Throwable $e) {
            return ['text' => (string) $text, 'callback_data' => (string) $callback_data];
        }
    }
}

if (!function_exists('nexora_menu_emoji_map')) {
    function nexora_menu_emoji_map($pdo = null): array
    {
        try {
            $pdo = $pdo instanceof PDO ? $pdo : ($GLOBALS['pdo'] ?? null);
            if (!($pdo instanceof PDO)) return [];
            $stmt = $pdo->query("SELECT NamePay, ValuePay FROM PaySetting WHERE NamePay LIKE 'nx\\_emoji\\_%' ESCAPE '\\'");
            $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
            $map = [];
            foreach ($rows as $r) {
                $k = trim((string) ($r['NamePay'] ?? ''));
                $v = trim((string) ($r['ValuePay'] ?? ''));
                if (strpos($k, 'nx_emoji_') === 0 && $v !== '') {
                    $map[substr($k, 9)] = $v;
                }
            }
            return $map;
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('nexora_apply_menu_emoji')) {
    function nexora_apply_menu_emoji(array $rows, array $labels = []): array
    {
        // The free version has no premium emoji of telegram: the buttons keep
        // the text they have.
        return $rows;
        try {
            $flip = [];
            foreach ($labels as $k => $v) {
                if (is_string($v) && $v !== '' && !isset($flip[$v])) {
                    $flip[$v] = $k;
                }
            }
            $map = nexora_menu_emoji_map();
            if ($map === []) return $rows;
            foreach ($rows as $rk => $row) {
                if (!is_array($row)) continue;
                foreach ($row as $bk => $btn) {
                    if (!is_array($btn) || !isset($btn['text']) || !is_string($btn['text'])) continue;
                    $key = $flip[$btn['text']] ?? null;
                    if ($key === null || !isset($map[$key])) continue;
                    $ref = $map[$key];
                    $icon = '';
                    $char = '';
                    if (preg_match('/^\d{1,32}$/', $ref)) {
                        $icon = $ref;
                    } elseif (preg_match('/^\{emoji:([^}]+)\}$/u', $ref, $mm)) {
                        try { $erow = nexora_emoji_lookup(trim($mm[1])); } catch (Throwable $e) { $erow = null; }
                        if ($erow && !empty($erow['custom_emoji_id'])) {
                            $icon = (string) $erow['custom_emoji_id'];
                            $char = trim((string) ($erow['emoji_utf8'] ?? ''));
                        } else {
                            continue;
                        }
                    } else {
                        continue;
                    }
                    if (isset($btn['callback_data']) || isset($btn['url'])) {
                        if ($icon !== '' && !isset($btn['icon_custom_emoji_id'])) {
                            $btn['icon_custom_emoji_id'] = $icon;
                            $rows[$rk][$bk] = $btn;
                        }
                    } elseif ($char !== '' && strpos($btn['text'], $char) !== 0) {
                        $btn['text'] = $char . ' ' . $btn['text'];
                        $rows[$rk][$bk] = $btn;
                    }
                }
            }
            return $rows;
        } catch (Throwable $e) {
            return $rows;
        }
    }
}



// === Runtime application of nx_bb_* (color+emoji for keyboard-group buttons) ===
if (!function_exists('nexora_bb_map')) {
    function nexora_bb_map(): array
    {
        // The map is read once per request. It used to run one query per button
        // (hundreds of them on every webhook call), which made the bot answer
        // in seconds instead of milliseconds.
        static $memo = null;
        if ($memo !== null) {
            return $memo;
        }
        try {
            global $pdo;
            $pdo = $pdo instanceof PDO ? $pdo : ($GLOBALS['pdo'] ?? null);
            if (!($pdo instanceof PDO)) {
                $memo = ['emoji' => [], 'style' => []];
                return $memo;
            }
            $rows = $pdo->query("SELECT NamePay, ValuePay FROM PaySetting WHERE NamePay LIKE 'nx_bb_emoji_%' OR NamePay LIKE 'nx_bb_style_%'")->fetchAll(PDO::FETCH_ASSOC);
            $emoji = []; $style = [];
            foreach ($rows as $r) {
                $k = trim((string) ($r['NamePay'] ?? ''));
                $v = trim((string) ($r['ValuePay'] ?? ''));
                if ($v === '') continue;
                if (strpos($k, 'nx_bb_emoji_') === 0) $emoji[substr($k, 12)] = $v;
                elseif (strpos($k, 'nx_bb_style_') === 0) $style[substr($k, 12)] = $v;
            }
            // The colour of a payment gateway is stored under its own name, so
            // the button of the gateway gets the colour of the web panel.
            if (function_exists('nexora_payment_gateways') && function_exists('getPaySettingValue')) {
                foreach (nexora_payment_gateways() as $gatewayKey => $gateway) {
                    if (isset($style[$gatewayKey])) {
                        continue;
                    }
                    $gatewayStyle = trim((string) getPaySettingValue('nx_gw_style_' . $gatewayKey, ''));
                    if ($gatewayStyle !== '' && in_array($gatewayStyle, nexora_color_slugs(), true)) {
                        $style[$gatewayKey] = $gatewayStyle;
                    }
                }
            }
            $memo = ['emoji' => $emoji, 'style' => $style];
            return $memo;
        } catch (Throwable $e) {
            $memo = ['emoji' => [], 'style' => []];
            return $memo;
        }
    }
}
if (!function_exists('nexora_apply_bb_style')) {
    function nexora_apply_bb_style(array $rows, array $flip = []): array
    {
        try {
            $map = nexora_bb_map();
            if ($map['emoji'] === [] && $map['style'] === []) return $rows;
            foreach ($rows as $rk => $row) {
                if (!is_array($row)) continue;
                foreach ($row as $bk => $btn) {
                    if (!is_array($btn) || !isset($btn['text']) || !is_string($btn['text'])) continue;
                    $key = $flip[$btn['text']] ?? null;
                    if ($key === null && $btn['text'] !== '') {
                        // Tolerant prefix-match fallback: nexora_apply_menu_emoji()
                        // prepends a leading emoji glyph to non-inline menu buttons
                        // (e.g. "🎯 خانه") before this runs, which defeats the exact
                        // flip lookup. Strip a leading run of symbol/mark/whitespace
                        // so the plain label still resolves to its nx_bb_* override.
                        $stripped = preg_replace('/^[\p{S}\p{M}\s]+/u', '', $btn['text']);
                        if ($stripped !== $btn['text'] && $stripped !== '') {
                            $key = $flip[$stripped] ?? null;
                        }
                    }
                    if ($key === null) continue;
                    // A label can belong to more than one storage key (the back
                    // button exists for the user and for the admin menu), so
                    // every candidate is checked for a colour/emoji override.
                    $candidates = is_array($key) ? $key : [$key];
                    $emojiKey = null;
                    $styleKey = null;
                    foreach ($candidates as $candidate) {
                        if ($emojiKey === null && isset($map['emoji'][$candidate])) $emojiKey = $candidate;
                        if ($styleKey === null && isset($map['style'][$candidate])) $styleKey = $candidate;
                    }
                    $changed = false;
                    // The free version has no premium emoji of telegram: the
                    // colour of a button is set, the icon never.
                    if ($styleKey !== null) {
                        $native = nexora_btn_style($map['style'][$styleKey]);
                        // A colour that was chosen in the web panel always wins:
                        // the keyboard layout only carries the default colour
                        // of the button.
                        if ($native !== '' && (!isset($btn['style']) || $btn['style'] !== $native)) {
                            $btn['style'] = $native; $changed = true;
                        }
                    }
                    if ($changed) $rows[$rk][$bk] = $btn;
                }
            }
            return $rows;
        } catch (Throwable $e) { return $rows; }
    }
}
if (!function_exists('nexora_ensure_indexes')) {
    function nexora_ensure_indexes(PDO $pdo): void
    {
        $indexes = [
            ['invoice', 'idx_invoice_id_user', '`id_user`(100)'],
            ['invoice', 'idx_invoice_status', '`Status`(100)'],
            ['invoice', 'idx_invoice_time_sell', '`time_sell`(50)'],
            ['Payment_report', 'idx_payreport_status', '`payment_Status`(100)'],
            ['Payment_report', 'idx_payreport_time', '`time`(50)'],
            ['Payment_report', 'idx_payreport_user_status', '`id_user`(100), `payment_Status`(100)'],
            ['product', 'idx_product_location', '`Location`(100)'],
            ['product', 'idx_product_agent', '`agent`(50)'],
            ['product', 'idx_product_status', '`status_product`(20)'],
            ['product', 'idx_product_display', '`display_order`'],
            ['user', 'idx_user_agent', '`agent`(10)'],
            ['user', 'idx_user_register', '`register`(50)'],
            ['marzban_panel', 'idx_panel_display', '`display_order`'],
        ];
        foreach ($indexes as [$table, $indexName, $columns]) {
            try {
                $check = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?");
                $check->execute([$table, $indexName]);
                if ((int) $check->fetchColumn() === 0) {
                    $pdo->exec("ALTER TABLE `{$table}` ADD INDEX `{$indexName}` ({$columns})");
                }
            } catch (Throwable $e) {
                // Index might already exist or table might not exist - skip
            }
        }
    }
}
if (!function_exists('nexora_ensure_shop_tables')) {
    /**
     * The tables of the two panel shops and of the questions of the agent
     * shop. They are created here as well, so the shops work on an
     * installation that was updated without running the migration.
     */
    function nexora_ensure_shop_tables(PDO $pdo): void
    {
    }
}

if (!function_exists('nexora_self_heal')) {
    function nexora_self_heal(): void {
        static $done = false;
        if ($done) return;
        $done = true;
        // The schema is repaired at most once per hour. The flag is written
        // BEFORE the work starts, so a failing statement can no longer make
        // every single request run the whole (slow) DDL block again.
        $flagFile = __DIR__ . '/storage/.heal_flag';
        $flagDir = dirname($flagFile);
        if (!is_dir($flagDir)) @mkdir($flagDir, 0775, true);
        if (is_file($flagFile) && (time() - filemtime($flagFile)) < 3600) {
            return;
        }
        $pdo = $GLOBALS['pdo'] ?? null;
        if (!($pdo instanceof PDO)) return;
        // Only one process may repair the schema at a time.
        $lockHandle = @fopen($flagFile . '.lock', 'c');
        if ($lockHandle !== false) {
            if (!@flock($lockHandle, LOCK_EX | LOCK_NB)) {
                @fclose($lockHandle);
                return;
            }
        }
        @touch($flagFile);
        $safe = function (callable $fn) use ($pdo) {
            try {
                $fn();
            } catch (Throwable $e) {
                error_log('nexora_self_heal: ' . $e->getMessage());
            }
        };
        try {
            $safe(function () use ($pdo) {
                if (function_exists('nexora_ensure_style_columns')) {
                    nexora_ensure_style_columns($pdo);
                }
            });
            $safe(function () use ($pdo) {
                if (function_exists('nexora_ensure_emoji_table')) {
                    nexora_ensure_emoji_table($pdo);
                }
            });
            // Ensure database indexes for performance
            $safe(function () use ($pdo) {
                if (function_exists('nexora_ensure_indexes')) {
                    nexora_ensure_indexes($pdo);
                }
            });
            $safe(function () use ($pdo) {
            });
            $safe(function () use ($pdo) {
            });
            $safe(function () use ($pdo) {
                $pdo->exec("CREATE TABLE IF NOT EXISTS support_tickets (id INT AUTO_INCREMENT PRIMARY KEY, id_user VARCHAR(32) NOT NULL, subject VARCHAR(255) NOT NULL, message TEXT NOT NULL, status VARCHAR(20) NOT NULL DEFAULT 'open', admin_reply TEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, INDEX idx_status (status), INDEX idx_user (id_user)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            });
            $safe(function () use ($pdo) {
                $pdo->exec("CREATE TABLE IF NOT EXISTS op_queue (id INT AUTO_INCREMENT PRIMARY KEY, id_user VARCHAR(32) NOT NULL, id_invoice VARCHAR(64) NULL, op_type VARCHAR(32) NOT NULL, payload TEXT NULL, status VARCHAR(20) NOT NULL DEFAULT 'pending', attempts INT NOT NULL DEFAULT 0, last_error TEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, INDEX idx_status (status), INDEX idx_user (id_user)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            });
            $safe(function () use ($pdo) {
                $pdo->exec("CREATE TABLE IF NOT EXISTS custom_apps (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(128) NOT NULL, platform VARCHAR(64) NULL, download_link VARCHAR(512) NULL, tutorial TEXT NULL, icon VARCHAR(64) NULL, sort_order INT NOT NULL DEFAULT 0, status VARCHAR(20) NOT NULL DEFAULT 'active', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            });
            $safe(function () use ($pdo) {
                $pdo->exec("CREATE TABLE IF NOT EXISTS collab_notes (id INT AUTO_INCREMENT PRIMARY KEY, admin_user VARCHAR(64) NULL, title VARCHAR(255) NOT NULL, body TEXT NOT NULL, pinned TINYINT(1) NOT NULL DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            });
            // The tables of the two panel shops and of the questions of the
            // agent shop, with the ten default packages of the agent panel.
            $safe(function () use ($pdo) {
                nexora_ensure_shop_tables($pdo);
            });
            $safe(function () use ($pdo) {
            });
            // The FrenzyEx base URL stays empty on purpose: a fresh install must
            // not point at the gateway of another installation.
            $frenzyDefaults = ['frenzyexstatus' => 'offfrenzyex', 'frenzyex_api_key' => '', 'frenzyex_base_url' => '', 'frenzyex_callback_secret' => '', 'cashbackfrenzyex' => '0'];
            $safe(function () use ($pdo, $frenzyDefaults) {
                foreach ($frenzyDefaults as $name => $val) {
                    $chk = $pdo->prepare('SELECT COUNT(*) FROM PaySetting WHERE NamePay = ?');
                    $chk->execute([$name]);
                    if ((int) $chk->fetchColumn() === 0) {
                        $pdo->prepare('INSERT INTO PaySetting (NamePay, ValuePay) VALUES (?, ?)')->execute([$name, $val]);
                    }
                }
            });
        } catch (Throwable $e) {
            error_log('nexora_self_heal: ' . $e->getMessage());
        }
        if ($lockHandle !== false) {
            @flock($lockHandle, LOCK_UN);
            @fclose($lockHandle);
        }
    }
}
nexora_self_heal();
