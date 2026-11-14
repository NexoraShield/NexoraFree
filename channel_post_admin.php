<?php
/**
 * The message of the channel ("متن در کانال").
 *
 * The administrator of the bot writes a message - a text, with a picture if he
 * wants - and publishes it in one of his channels. The message can carry the
 * buttons of the bot: every button has a deep link of its own and brings the
 * customer straight to the part of the bot the button belongs to.
 *
 * The work of the administrator is kept in the table "channel_post": every
 * operation has a row of its own with an id of its own, so two administrators
 * never step on each other and a half finished operation can always be found
 * again.
 *
 * The buttons never build a second copy of the screens of the bot: the
 * trigger of every button is the very name of the button of the menu of the
 * bot, so the customer of the channel sees exactly the same screen (and the
 * same names) as a customer inside the bot.
 */

if (!function_exists('nexora_channel_post_table')) {
    /**
     * Makes sure the table of the operations exists.
     *
     * The table is part of the schema of the bot (db/tables), but the bot is
     * also updated without the installer, so the table is created here as
     * well: a missing table must never stop the bot.
     */
    function nexora_channel_post_table(): void
    {
        global $pdo;
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS channel_post (
                id INT AUTO_INCREMENT PRIMARY KEY,
                op_id VARCHAR(64) NOT NULL,
                admin_id VARCHAR(64) NOT NULL,
                channel_link VARCHAR(255) NOT NULL DEFAULT '',
                channel_chat_id VARCHAR(64) NOT NULL DEFAULT '',
                channel_title VARCHAR(255) NOT NULL DEFAULT '',
                content_type VARCHAR(16) NOT NULL DEFAULT 'text',
                message_text TEXT NULL,
                file_id VARCHAR(255) NOT NULL DEFAULT '',
                buttons VARCHAR(2000) NOT NULL DEFAULT '[]',
                status VARCHAR(20) NOT NULL DEFAULT 'draft',
                published_message_id VARCHAR(64) NOT NULL DEFAULT '',
                last_error TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                published_at TIMESTAMP NULL,
                INDEX idx_admin (admin_id, status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } catch (Throwable $e) {
            error_log('nexora_channel_post_table: ' . $e->getMessage());
        }
    }
}

if (!function_exists('nexora_channel_post_actions')) {
    /**
     * The eight buttons of the message and where they bring the customer.
     *
     * The order of the list is the order the buttons have in the message.
     * The label of every button is the name of the button of the menu of the
     * bot, so the owner of the bot sees the names he has chosen.
     *
     * The trigger is the way the customer asks the bot for that screen: the
     * name of the button of the menu (the customer of the channel presses the
     * button of the channel and the bot does exactly what the button of the
     * menu does).
     */
    function nexora_channel_post_actions(): array
    {
        global $textbotlang;

        return [
            'start' => [
                'label' => '🏠 استارت',
                'payload' => 'nxcp_start',
                'trigger' => ['datain', 'start'],
            ],
            'buy' => [
                'label' => (string) ($textbotlang['textbot']['sell'] ?? 'خرید اشتراک'),
                'payload' => 'nxcp_buy',
                'trigger' => ['text', (string) ($textbotlang['textbot']['sell'] ?? '')],
            ],
            'test' => [
                'label' => (string) ($textbotlang['textbot']['userTest'] ?? 'اکانت تست'),
                'payload' => 'nxcp_test',
                'trigger' => ['text', (string) ($textbotlang['textbot']['userTest'] ?? '')],
            ],
            'agentshop' => [
                'label' => (string) ($textbotlang['textbot']['agentShop'] ?? 'خرید پنل نمایندگی'),
                'payload' => 'nxcp_agentshop',
                'trigger' => ['text', (string) ($textbotlang['textbot']['agentShop'] ?? '')],
            ],
            'agentpanelshop' => [
                'label' => (string) ($textbotlang['textbot']['agentPanelShop'] ?? 'خرید پنل پاسارگارد'),
                'payload' => 'nxcp_agentpanelshop',
                'trigger' => ['text', (string) ($textbotlang['textbot']['agentPanelShop'] ?? '')],
            ],
            'wallet' => [
                'label' => (string) ($textbotlang['textbot']['accountWallet'] ?? 'کیف پول | افزایش موجودی'),
                'payload' => 'nxcp_wallet',
                'trigger' => ['text', (string) ($textbotlang['textbot']['accountWallet'] ?? '')],
            ],
            'support' => [
                'label' => (string) ($textbotlang['textbot']['support'] ?? 'پشتیبانی'),
                'payload' => 'nxcp_support',
                'trigger' => ['text', (string) ($textbotlang['textbot']['support'] ?? '')],
            ],
            'tutorial' => [
                'label' => (string) ($textbotlang['textbot']['help'] ?? 'آموزش اتصال'),
                'payload' => 'nxcp_tutorial',
                'trigger' => ['text', (string) ($textbotlang['textbot']['help'] ?? '')],
            ],
        ];
    }
}

if (!function_exists('nexora_channel_post_action')) {
    /**
     * One button of the message, or nothing when the name is not known.
     */
    function nexora_channel_post_action(string $key): ?array
    {
        $actions = nexora_channel_post_actions();
        $key = strtolower(trim($key));
        return $actions[$key] ?? null;
    }
}

if (!function_exists('nexora_channel_post_deep_link')) {
    /**
     * The link of one button of the message.
     *
     * Every button has a parameter of its own, so the bot knows exactly which
     * button the customer has pressed.
     */
    function nexora_channel_post_deep_link(string $key): string
    {
        global $usernamebot;

        $action = nexora_channel_post_action($key);
        if ($action === null) {
            return '';
        }
        $bot = ltrim(trim((string) $usernamebot), '@');
        if ($bot === '') {
            return '';
        }
        return 'https://t.me/' . $bot . '?start=' . $action['payload'];
    }
}

if (!function_exists('nexora_channel_post_payload_key')) {
    /**
     * The name of the button of a link of the bot, or nothing when the
     * parameter is not one of ours.
     */
    function nexora_channel_post_payload_key(string $payload): ?string
    {
        if (!preg_match('/^nxcp_([a-z_]+)$/i', trim($payload), $match)) {
            return null;
        }
        $key = strtolower((string) $match[1]);
        return nexora_channel_post_action($key) === null ? null : $key;
    }
}

if (!function_exists('nexora_channel_post_current')) {
    /**
     * The operation the administrator is working on right now.
     */
    function nexora_channel_post_current(string $adminId): ?array
    {
        global $pdo;
        nexora_channel_post_table();
        try {
            $stmt = $pdo->prepare("SELECT * FROM channel_post WHERE admin_id = ? AND status IN ('draft','publishing') ORDER BY id DESC LIMIT 1");
            $stmt->execute([(string) $adminId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return is_array($row) ? $row : null;
        } catch (Throwable $e) {
            error_log('nexora_channel_post_current: ' . $e->getMessage());
            return null;
        }
    }
}

if (!function_exists('nexora_channel_post_create')) {
    /**
     * A new operation for the administrator.
     *
     * Every operation has an id of its own, so the operations of two
     * administrators never mix.
     */
    function nexora_channel_post_create(string $adminId): array
    {
        global $pdo;
        nexora_channel_post_table();
        // The half finished work of the administrator is not needed any more:
        // every operation has an id of its own, so the new one starts clean.
        try {
            $stmt = $pdo->prepare("DELETE FROM channel_post WHERE admin_id = ? AND status = 'draft'");
            $stmt->execute([(string) $adminId]);
        } catch (Throwable $e) {
            error_log('nexora_channel_post_create: ' . $e->getMessage());
        }
        $opId = 'op_' . bin2hex(random_bytes(8));
        try {
            $stmt = $pdo->prepare("INSERT INTO channel_post (op_id, admin_id, status, buttons, created_at) VALUES (?, ?, 'draft', '[]', NOW())");
            $stmt->execute([$opId, (string) $adminId]);
            $id = (int) $pdo->lastInsertId();
        } catch (Throwable $e) {
            error_log('nexora_channel_post_create: ' . $e->getMessage());
            return [];
        }
        return [
            'id' => $id,
            'op_id' => $opId,
            'admin_id' => (string) $adminId,
            'channel_link' => '',
            'channel_chat_id' => '',
            'channel_title' => '',
            'content_type' => 'text',
            'message_text' => '',
            'file_id' => '',
            'buttons' => '[]',
            'status' => 'draft',
            'published_message_id' => '',
            'last_error' => '',
        ];
    }
}

if (!function_exists('nexora_channel_post_save')) {
    /**
     * Writes the operation back to the table.
     */
    function nexora_channel_post_save(array $op): void
    {
        global $pdo;
        nexora_channel_post_table();
        try {
            $stmt = $pdo->prepare("UPDATE channel_post SET channel_link = ?, channel_chat_id = ?, channel_title = ?, content_type = ?, message_text = ?, file_id = ?, buttons = ?, status = ?, published_message_id = ?, last_error = ?, published_at = COALESCE(?, published_at) WHERE id = ?");
            $stmt->execute([
                (string) ($op['channel_link'] ?? ''),
                (string) ($op['channel_chat_id'] ?? ''),
                (string) ($op['channel_title'] ?? ''),
                (string) ($op['content_type'] ?? 'text'),
                (string) ($op['message_text'] ?? ''),
                (string) ($op['file_id'] ?? ''),
                (string) ($op['buttons'] ?? '[]'),
                (string) ($op['status'] ?? 'draft'),
                (string) ($op['published_message_id'] ?? ''),
                (string) ($op['last_error'] ?? ''),
                (string) ($op['published_at'] ?? ''),
                (int) ($op['id'] ?? 0),
            ]);
        } catch (Throwable $e) {
            error_log('nexora_channel_post_save: ' . $e->getMessage());
        }
    }
}

if (!function_exists('nexora_channel_post_remove')) {
    /**
     * The operation of the administrator is over: it is not needed any more.
     */
    function nexora_channel_post_remove(string $adminId): void
    {
        global $pdo;
        nexora_channel_post_table();
        try {
            $stmt = $pdo->prepare("DELETE FROM channel_post WHERE admin_id = ? AND status = 'draft'");
            $stmt->execute([(string) $adminId]);
        } catch (Throwable $e) {
            error_log('nexora_channel_post_remove: ' . $e->getMessage());
        }
    }
}

if (!function_exists('nexora_channel_post_buttons_list')) {
    /**
     * The buttons the administrator has chosen, in the order of the list.
     */
    function nexora_channel_post_buttons_list(array $op): array
    {
        $raw = json_decode((string) ($op['buttons'] ?? '[]'), true);
        if (!is_array($raw)) {
            $raw = [];
        }
        $actions = nexora_channel_post_actions();
        $list = [];
        foreach ($actions as $key => $action) {
            if (in_array($key, $raw, true)) {
                $list[] = $key;
            }
        }
        return $list;
    }
}

if (!function_exists('nexora_channel_post_check_channel')) {
    /**
     * The channel of the administrator: is it a channel and is the bot its
     * administrator?
     *
     * The answer tells the administrator what is wrong, so a broken channel
     * never stops the work without a reason.
     */
    function nexora_channel_post_check_channel(string $link): array
    {
        $link = trim($link);
        if ($link === '') {
            return ['ok' => false, 'chat_id' => '', 'title' => '', 'error' => 'لینک کانال خالی است.'];
        }

        $chatId = function_exists('nexora_channel_chat_id') ? nexora_channel_chat_id($link) : '';
        if (trim((string) $chatId) === '') {
            return ['ok' => false, 'chat_id' => '', 'title' => '', 'error' => 'این لینک یک کانال نیست (لینک دعوت یا لینک عمومی پذیرفته نمی‌شود).'];
        }

        $chat = telegram('getChat', ['chat_id' => $chatId]);
        if (empty($chat['ok'])) {
            $reason = (string) ($chat['description'] ?? 'کانال پیدا نشد.');
            return ['ok' => false, 'chat_id' => (string) $chatId, 'title' => '', 'error' => 'کانال پیدا نشد: ' . $reason];
        }
        $info = is_array($chat['result'] ?? null) ? $chat['result'] : [];
        // A channel, a group or a supergroup: the bot may write in all of
        // them, when it is their administrator.
        $isChannel = !empty($info['is_channel']) || !empty($info['is_supergroup']) || !empty($info['is_group']);
        if (!$isChannel) {
            return ['ok' => false, 'chat_id' => (string) $chatId, 'title' => '', 'error' => 'این گفتگو یک گروه یا کانال نیست.'];
        }
        $title = (string) ($info['title'] ?? '');

        // The bot must be an administrator of the channel and it must be
        // allowed to write in it.
        $me = telegram('getMe');
        $botId = (int) (is_array($me['result'] ?? null) ? ($me['result']['id'] ?? 0) : 0);
        if ($botId <= 0) {
            return ['ok' => false, 'chat_id' => (string) $chatId, 'title' => $title, 'error' => 'شناسه ربات در تلگرام پیدا نشد.'];
        }

        $admins = telegram('getChatAdministrators', ['chat_id' => $chatId]);
        if (empty($admins['ok'])) {
            $reason = (string) ($admins['description'] ?? 'دسترسی به مدیران گروه ممکن نشد.');
            return ['ok' => false, 'chat_id' => (string) $chatId, 'title' => $title, 'error' => 'ربات در این گروه ادمین نیست: ' . $reason];
        }
        $list = is_array($admins['result'] ?? null) ? $admins['result'] : [];
        $isAdmin = false;
        $canPost = false;
        foreach ($list as $admin) {
            if (!is_array($admin) || (int) ($admin['user']['id'] ?? 0) !== $botId) {
                continue;
            }
            $isAdmin = true;
            $status = (string) ($admin['status'] ?? '');
            if ($status === 'creator') {
                $canPost = true;
            } elseif ($status === 'administrator') {
                $canPost = !array_key_exists('can_post_messages', $admin) || !empty($admin['can_post_messages']);
            }
            break;
        }
        if (!$isAdmin) {
            return ['ok' => false, 'chat_id' => (string) $chatId, 'title' => $title, 'error' => 'ربات ادمین این گروه نیست.'];
        }
        if (!$canPost) {
            return ['ok' => false, 'chat_id' => (string) $chatId, 'title' => $title, 'error' => 'ربات اجازه ارسال پیام در این گروه را ندارد.'];
        }

        return ['ok' => true, 'chat_id' => (string) $chatId, 'title' => $title, 'error' => ''];
    }
}

if (!function_exists('nexora_channel_post_utf16')) {
    /**
     * The text of the message in UTF-16 (the unit in which Telegram counts
     * the places of the format).
     */
    function nexora_channel_post_utf16(string $text, bool $back = false): string
    {
        if (function_exists('mb_convert_encoding')) {
            return (string) mb_convert_encoding($text, $back ? 'UTF-8' : 'UTF-16LE', 'UTF-8');
        }
        $converted = @iconv('UTF-8', $back ? 'UTF-8' : 'UTF-16LE//IGNORE', $text);
        return $converted === false ? $text : $converted;
    }
}

if (!function_exists('nexora_channel_post_offsets')) {
    /**
     * The places of the characters of the text.
     *
     * Telegram counts the places of the format in the units of UTF-16, but
     * the text of the bot is UTF-8. The map tells where every character of
     * the text begins, so the places of Telegram can be found in the text of
     * the bot.
     */
    function nexora_channel_post_offsets(string $text): array
    {
        $utf16 = nexora_channel_post_utf16($text);
        $length = strlen($utf16);
        $map = [0 => 0];
        $utf8 = 0;
        for ($i = 0; $i + 1 < $length; $i += 2) {
            $unit = unpack('v', substr($utf16, $i, 2))[1];
            if ($unit >= 0xD800 && $unit <= 0xDBFF && $i + 3 < $length) {
                $low = unpack('v', substr($utf16, $i + 2, 2))[1];
                if ($low >= 0xDC00 && $low <= 0xDFFF) {
                    // A character outside the basic plane: four bytes in UTF-8
                    // and two units in UTF-16.
                    $utf8 += 4;
                    $i += 2;
                    $map[$i + 2] = $utf8;
                    continue;
                }
            }
            $utf8 += $unit < 0x80 ? 1 : ($unit < 0x800 ? 2 : 3);
            $map[$i + 2] = $utf8;
        }
        return $map;
    }
}

if (!function_exists('nexora_channel_post_entities_html')) {
    /**
     * The text of the administrator with the format of Telegram.
     *
     * Telegram tells the bot which part of the text is bold, which part is a
     * link and so on. The message of the channel keeps that format, so the
     * message of the administrator looks the same in the channel as it looks
     * in the chat of the bot.
     */
    function nexora_channel_post_entities_html(string $text, $entities): string
    {
        if ($text === '') {
            return '';
        }
        if (!is_array($entities) || !$entities) {
            return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        $map = nexora_channel_post_offsets($text);
        $list = [];
        foreach ($entities as $entity) {
            if (!is_array($entity)) {
                continue;
            }
            $offset = (int) ($entity['offset'] ?? 0);
            $size = (int) ($entity['length'] ?? 0);
            if ($size <= 0 || $offset < 0) {
                continue;
            }
            $from = $map[$offset * 2] ?? null;
            $to = $map[($offset + $size) * 2] ?? null;
            if ($from === null || $to === null || $to <= $from) {
                continue;
            }
            $list[] = [
                'type' => (string) ($entity['type'] ?? ''),
                'from' => (int) $from,
                'to' => (int) $to,
                'url' => (string) ($entity['url'] ?? ''),
                'user_id' => (int) ($entity['user']['id'] ?? 0),
                'custom_emoji_id' => (string) ($entity['custom_emoji_id'] ?? ''),
            ];
        }
        if (!$list) {
            return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        $used = [];
        return nexora_channel_post_entities_walk($text, $list, 0, strlen($text), $used, false);
    }

    /**
     * The tags of one kind of format.
     */
    function nexora_channel_post_entity_tags(array $entity): array
    {
        switch ($entity['type']) {
            case 'bold':
                return ['<b>', '</b>'];
            case 'italic':
                return ['<i>', '</i>'];
            case 'underline':
                return ['<u>', '</u>'];
            case 'strikethrough':
                return ['<s>', '</s>'];
            case 'spoiler':
                return ['<tg-spoiler>', '</tg-spoiler>'];
            case 'code':
                return ['<code>', '</code>'];
            case 'pre':
                return ['<pre>', '</pre>'];
            case 'blockquote':
                return ['<blockquote>', '</blockquote>'];
            case 'expandable_blockquote':
                return ['<blockquote expandable>', '</blockquote>'];
            case 'link':
            case 'text_link':
                $url = $entity['url'];
                return $url === '' ? ['', ''] : ['<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">', '</a>'];
            case 'text_mention':
                return $entity['user_id'] > 0
                    ? ['<a href="tg://user?id=' . $entity['user_id'] . '">', '</a>']
                    : ['', ''];
            case 'custom_emoji':
                return $entity['custom_emoji_id'] === ''
                    ? ['', '']
                    : ['<tg-emoji emoji-id="' . htmlspecialchars($entity['custom_emoji_id'], ENT_QUOTES, 'UTF-8') . '">', '</tg-emoji>'];
            default:
                return ['', ''];
        }
    }

    /**
     * The plain text of a part of the message.
     */
    function nexora_channel_post_escape(string $part, bool $code = false): string
    {
        if ($code) {
            return htmlspecialchars($part, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }
        return htmlspecialchars($part, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Walks the text of the message and the format of it.
     */
    function nexora_channel_post_entities_walk(string $text, array $entities, int $from, int $to, array &$used, bool $code): string
    {
        if ($from >= $to) {
            return '';
        }
        // The format that holds the whole part.
        $holder = null;
        foreach ($entities as $index => $entity) {
            if (isset($used[$index])) {
                continue;
            }
            if ($entity['from'] <= $from && $entity['to'] >= $to) {
                $holder = $index;
                break;
            }
        }
        if ($holder !== null) {
            $entity = $entities[$holder];
            $used[$holder] = true;
            [$open, $close] = nexora_channel_post_entity_tags($entity);
            $inner = nexora_channel_post_entities_walk($text, $entities, $entity['from'], $entity['to'], $used, $code || $entity['type'] === 'code' || $entity['type'] === 'pre');
            if ($open === '') {
                return $inner;
            }
            return $open . $inner . $close;
        }
        // The first format that begins inside the part.
        $next = null;
        foreach ($entities as $index => $entity) {
            if (isset($used[$index])) {
                continue;
            }
            if ($entity['from'] >= $from && $entity['from'] < $to) {
                if ($next === null || $entity['from'] < $entities[$next]['from']) {
                    $next = $index;
                }
            }
        }
        if ($next === null) {
            return nexora_channel_post_escape(substr($text, $from, $to - $from), $code);
        }
        $entity = $entities[$next];
        $used[$next] = true;
        [$open, $close] = nexora_channel_post_entity_tags($entity);
        $before = nexora_channel_post_escape(substr($text, $from, $entity['from'] - $from), $code);
        $inner = nexora_channel_post_entities_walk($text, $entities, $entity['from'], $entity['to'], $used, $code || $entity['type'] === 'code' || $entity['type'] === 'pre');
        $after = nexora_channel_post_entities_walk($text, $entities, $entity['to'], $to, $used, $code);
        if ($open === '') {
            return $before . $inner . $after;
        }
        return $before . $open . $inner . $close . $after;
    }
}

if (!function_exists('nexora_channel_post_list_text')) {
    /**
     * The list of the eight buttons, with the sign of every button.
     *
     * A button with the green sign is chosen, a button with the red sign is
     * not. The administrator takes the sign off a button by pressing it again.
     */
    function nexora_channel_post_list_text(array $selected): string
    {
        $lines = ["📢 <b>متن در کانال</b>", ""];
        $lines[] = "دکمه‌هایی را که می‌خواهید زیر پیام باشد انتخاب کنید.";
        $lines[] = "برای برداشتن تیکِ یک دکمه، دوباره همان دکمه را بزنید.";
        $lines[] = "";
        foreach (nexora_channel_post_actions() as $key => $action) {
            $mark = in_array($key, $selected, true) ? '✅' : '❌';
            $lines[] = $mark . ' ' . $action['label'];
        }
        return implode("\n", $lines);
    }
}

if (!function_exists('nexora_channel_post_list_keyboard')) {
    /**
     * The keyboard of the list of the buttons.
     */
    function nexora_channel_post_list_keyboard(array $selected): string
    {
        $rows = [];
        foreach (nexora_channel_post_actions() as $key => $action) {
            $mark = in_array($key, $selected, true) ? '✅' : '❌';
            $rows[] = [['text' => $mark . ' ' . $action['label'], 'callback_data' => 'adv_cp_toggle_' . $key]];
        }
        $rows[] = [['text' => '✅ تأیید و ادامه', 'callback_data' => 'adv_cp_next']];
        $rows[] = [['text' => '🗑 لغو عملیات', 'callback_data' => 'adv_cp_cancel']];
        return json_encode(['inline_keyboard' => $rows], JSON_UNESCAPED_UNICODE);
    }
}

if (!function_exists('nexora_channel_post_keyboard')) {
    /**
     * The keyboard of the message of the channel: only the buttons the
     * administrator has chosen, one below the other.
     */
    function nexora_channel_post_keyboard(array $selected): string
    {
        $rows = [];
        foreach ($selected as $key) {
            $action = nexora_channel_post_action((string) $key);
            if ($action === null) {
                continue;
            }
            $link = nexora_channel_post_deep_link((string) $key);
            if ($link === '') {
                continue;
            }
            $rows[] = [['text' => $action['label'], 'url' => $link]];
        }
        if (!$rows) {
            return '';
        }
        return json_encode(['inline_keyboard' => $rows], JSON_UNESCAPED_UNICODE);
    }
}

if (!function_exists('nexora_channel_post_report')) {
    /**
     * The report of an error in the group of the reports.
     *
     * The report says in which step the error happened, which channel was
     * meant, which administrator was working and what Telegram answered.
     */
    function nexora_channel_post_report(string $stage, array $op, string $reason, string $adminId): void
    {
        global $setting;

        $group = (string) ($setting['Channel_Report'] ?? '');
        if (trim($group) === '') {
            return;
        }
        $topicId = 0;
        if (function_exists('select')) {
            $topic = select("topicid", "idreport", "report", "errorreport", "select");
            if (is_array($topic) && isset($topic['idreport'])) {
                $topicId = (int) $topic['idreport'];
            }
        }
        $text = "❌ <b>خطا در «متن در کانال»</b>\n\n"
            . "مرحله: <b>" . htmlspecialchars($stage, ENT_QUOTES, 'UTF-8') . "</b>\n"
            . "کانال: <code>" . htmlspecialchars((string) ($op['channel_link'] ?? ''), ENT_QUOTES, 'UTF-8') . "</code>"
            . ((string) ($op['channel_title'] ?? '') !== '' ? ' (' . htmlspecialchars((string) $op['channel_title'], ENT_QUOTES, 'UTF-8') . ')' : '') . "\n"
            . "شناسه کانال: <code>" . htmlspecialchars((string) ($op['channel_chat_id'] ?? ''), ENT_QUOTES, 'UTF-8') . "</code>\n"
            . "مدیر: <code>" . htmlspecialchars((string) $adminId, ENT_QUOTES, 'UTF-8') . "</code>\n"
            . "نوع خطا: خطای تلگرام یا خطای کانال\n"
            . "علت دقیق: " . htmlspecialchars($reason, ENT_QUOTES, 'UTF-8') . "\n"
            . "زمان: " . date('Y/m/d H:i:s') . "\n"
            . "شناسه عملیات: <code>" . htmlspecialchars((string) ($op['op_id'] ?? ''), ENT_QUOTES, 'UTF-8') . "</code>";

        $datas = [
            'chat_id' => $group,
            'text' => $text,
            'parse_mode' => 'HTML',
        ];
        if ($topicId > 0) {
            $datas['message_thread_id'] = $topicId;
        }
        telegram('sendMessage', $datas);
    }
}

if (!function_exists('nexora_channel_post_publish')) {
    /**
     * The message goes to the channel.
     *
     * The operation is marked as "publishing" before the message is sent, so
     * a second press of the button can never publish the same message twice.
     */
    function nexora_channel_post_publish(array $op): array
    {
        global $pdo;
        nexora_channel_post_table();

        $selected = nexora_channel_post_buttons_list($op);
        if (!$selected) {
            return ['ok' => false, 'error' => 'هیچ دکمه‌ای انتخاب نشده است.'];
        }
        $keyboard = nexora_channel_post_keyboard($selected);
        if ($keyboard === '') {
            return ['ok' => false, 'error' => 'لینک دکمه‌ها ساخته نشد.'];
        }

        // Only one publish for one operation.
        try {
            $stmt = $pdo->prepare("UPDATE channel_post SET status = 'publishing' WHERE id = ? AND status = 'draft'");
            $stmt->execute([(int) ($op['id'] ?? 0)]);
            if ($stmt->rowCount() !== 1) {
                return ['ok' => false, 'error' => 'این پیام قبلاً منتشر شده است.'];
            }
        } catch (Throwable $e) {
            error_log('nexora_channel_post_publish: ' . $e->getMessage());
            return ['ok' => false, 'error' => 'وضعیت پیام در پایگاه داده نوشته نشد: ' . $e->getMessage()];
        }

        $text = (string) ($op['message_text'] ?? '');
        $chatId = (string) ($op['channel_chat_id'] ?? '');
        $fileId = (string) ($op['file_id'] ?? '');
        $isPhoto = ($op['content_type'] ?? 'text') === 'photo' && $fileId !== '';

        $datas = ['chat_id' => $chatId, 'reply_markup' => $keyboard];
        if ($isPhoto) {
            $datas['photo'] = $fileId;
            // Telegram keeps only 1024 characters of a caption: a longer text
            // goes as a message of its own, right below the picture.
            if ($text !== '' && strlen($text) <= 1024) {
                $datas['caption'] = $text;
            } else {
                $textAfterPicture = $text;
                $text = '';
            }
        } else {
            if ($text === '') {
                $text = ' ';
            }
            $datas['text'] = $text;
            $datas['parse_mode'] = 'HTML';
        }

        $result = telegram($isPhoto ? 'sendPhoto' : 'sendMessage', $datas);
        if (empty($result['ok'])) {
            $reason = (string) ($result['description'] ?? 'تلگرام پیام را نپذیرفت.');
            $op['status'] = 'draft';
            $op['last_error'] = $reason;
            nexora_channel_post_save($op);
            return ['ok' => false, 'error' => $reason];
        }
        if ($isPhoto && isset($textAfterPicture) && trim((string) $textAfterPicture) !== '') {
            telegram('sendMessage', [
                'chat_id' => $chatId,
                'text' => $textAfterPicture,
                'reply_markup' => $keyboard,
                'parse_mode' => 'HTML',
            ]);
        }

        $op['status'] = 'published';
        $op['published_message_id'] = (string) (is_array($result['result'] ?? null) ? ($result['result']['message_id'] ?? '') : '');
        $op['published_at'] = date('Y-m-d H:i:s');
        $op['last_error'] = '';
        nexora_channel_post_save($op);
        return ['ok' => true, 'error' => '', 'message_id' => $op['published_message_id']];
    }
}

if (!function_exists('nexora_channel_post_run_action')) {
    /**
     * The button of the channel is pressed: the bot does what the button of
     * the menu of the bot does.
     *
     * The trigger of the button is the name of the button of the menu, so the
     * screen of the bot is the one that runs: nothing is written twice.
     */
    function nexora_channel_post_run_action(string $adminId, string $key): bool
    {
        global $text, $datain;

        $action = nexora_channel_post_action($key);
        if ($action === null) {
            return false;
        }
        [$type, $value] = $action['trigger'];
        if ($type === 'datain') {
            $datain = (string) $value;
        } else {
            $text = (string) $value;
        }
        return true;
    }
}
