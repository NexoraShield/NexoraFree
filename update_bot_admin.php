<?php
/**
 * The update of the bot, from inside the bot.
 *
 * The bot is updated by the hub of the fleet (the panel of the bots): the hub
 * stops the bot, writes the new code, runs the migration of the database and
 * starts the bot again. The bot itself only asks the hub to do it, so the bot
 * is never half written and every bot of the fleet is updated with the same
 * script.
 *
 * There are two channels of the release: the stable one and the test one. The
 * hub decides which of them is the stable one (the tab "channels" of the hub
 * panel), the owner of the bot decides which one this bot takes.
 *
 * The secret of the bot is the one the hub has given to it, so only the panel
 * of this bot can ask the hub to update this bot.
 */

if (!function_exists('nexora_update_bot_ready')) {
    /**
     * The bot knows the hub of the fleet?
     */
    function nexora_update_bot_ready(): bool
    {
        global $nexora_hub_url, $nexora_hub_token, $domainhosts;

        return trim((string) ($nexora_hub_url ?? '')) !== ''
            && trim((string) ($nexora_hub_token ?? '')) !== ''
            && trim((string) ($domainhosts ?? '')) !== '';
    }
}

if (!function_exists('nexora_update_bot_ask')) {
    /**
     * Asks the hub of the fleet something and returns its answer.
     *
     * The answer of the hub is always a json: the bot never reads anything
     * else, so a broken hub can never break the bot.
     */
    function nexora_update_bot_ask(string $path, array $data): array
    {
        global $nexora_hub_url, $nexora_hub_token, $domainhosts;

        $hubUrl = rtrim(trim((string) ($nexora_hub_url ?? '')), '/');
        $token = trim((string) ($nexora_hub_token ?? ''));
        $domain = trim((string) ($domainhosts ?? ''));
        if ($hubUrl === '' || $token === '' || $domain === '') {
            return ['ok' => false, 'error' => 'هاب هنوز این ربات را نشناخته است.'];
        }

        $body = http_build_query(array_merge(['domain' => $domain, 'token' => $token], $data), '', '&', PHP_QUERY_RFC3986);
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\nUser-Agent: NexoraBot\r\n",
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
        $raw = @file_get_contents($hubUrl . '/' . ltrim($path, '/'), false, $context);
        if ($raw === false) {
            return ['ok' => false, 'error' => 'هاب پاسخی نداد.'];
        }
        $answer = json_decode($raw, true);
        if (!is_array($answer)) {
            return ['ok' => false, 'error' => 'پاسخ هاب روشن نبود.'];
        }
        return $answer;
    }
}

if (!function_exists('nexora_update_bot_start')) {
    /**
     * The owner of the bot wants to update his bot.
     */
    function nexora_update_bot_start(string $channel): array
    {
        if (!in_array($channel, ['stable', 'test'], true)) {
            return ['ok' => false, 'error' => 'نسخه آپدیت شناخته نشد.'];
        }
        return nexora_update_bot_ask('bot-update.php', ['channel' => $channel]);
    }
}

if (!function_exists('nexora_update_bot_state')) {
    /**
     * How far is the update of the bot?
     */
    function nexora_update_bot_state(): array
    {
        return nexora_update_bot_ask('bot-update-state.php', []);
    }
}

if (!function_exists('nexora_update_bot_log')) {
    /**
     * The last lines of the log of the update.
     */
    function nexora_update_bot_log(array $state, int $lines = 12): string
    {
        $log = trim((string) ($state['log'] ?? ''));
        if ($log === '') {
            return '';
        }
        $rows = preg_split('/\r\n|\r|\n/', $log);
        $rows = array_values(array_filter(array_map('trim', (array) $rows), static function ($row) {
            return $row !== '';
        }));
        if (count($rows) > $lines) {
            $rows = array_slice($rows, -$lines);
        }
        return implode("\n", $rows);
    }
}

if (!function_exists('nexora_update_bot_keyboard')) {
    /**
     * The keyboard of the update: the owner of the bot chooses the version he
     * wants and can follow the log of the update.
     */
    function nexora_update_bot_keyboard(): string
    {
        return json_encode(['inline_keyboard' => [
            [['text' => '✅ نسخه پایدار', 'callback_data' => 'adv_update_stable']],
            [['text' => '🧪 نسخه آزمایشی', 'callback_data' => 'adv_update_test']],
            [['text' => '📄 وضعیت آپدیت', 'callback_data' => 'adv_update_state']],
            [['text' => '🏠 بازگشت به منوی مدیریت', 'callback_data' => 'admin']],
        ]], JSON_UNESCAPED_UNICODE);
    }
}
