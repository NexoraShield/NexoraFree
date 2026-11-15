<?php

// ---------------------------------------------------------------------------
// Sets the inbound / protocol of a panel with the data of an account that
// already exists inside that panel. The same logic is used by the bot
// ("تنظیم پروتکل و اینباند") and by the web panel (product form).
// ---------------------------------------------------------------------------
if (!function_exists('nexora_panel_protocol_helper_bootstrap')) {
    function nexora_panel_protocol_helper_bootstrap(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        $bootstrap = __DIR__ . '/panels/bootstrap.php';
        if (is_file($bootstrap)) {
            require_once $bootstrap;
        }
    }
}

if (!function_exists('nexora_panel_protocol_error_text')) {
    function nexora_panel_protocol_error_text($error): string
    {
        $error = is_array($error) ? ($error['msg'] ?? json_encode($error)) : (string) $error;
        $decoded = json_decode((string) $error, true);
        if (is_array($decoded)) {
            $error = (string) ($decoded['message'] ?? $decoded['msg'] ?? $error);
        }
        return trim((string) $error) !== '' ? (string) $error : 'خطای نامشخص';
    }
}

if (!function_exists('nexora_panel_protocol_clear_cache')) {
    function nexora_panel_protocol_clear_cache(): void
    {
        if (function_exists('clearSelectCache')) {
            clearSelectCache('marzban_panel');
        }
        $dir = __DIR__ . '/storage/cache';
        if (!is_dir($dir)) {
            return;
        }
        foreach ((array) glob($dir . '/pc_marzban_panel_*.json') as $file) {
            @unlink((string) $file);
        }
    }
}

if (!function_exists('nexora_panel_protocol_values')) {
    /**
     * The protocol and the inbound of a reference account.
     *
     * The account is read from the panel and the values of the account are
     * returned: the groups of the marzban, the protocols of the other panels.
     * The panel itself is never touched here: the values belong to the
     * product that names the account, not to the whole panel.
     *
     * ['ok' => bool, 'message' => string, 'inbounds' => mixed, 'proxies' => mixed]
     */
    function nexora_panel_protocol_values(PDO $pdo, string $panelName, string $username): array
    {
        $panelName = trim($panelName);
        $username = trim($username);
        if ($panelName === '') {
            return ['ok' => false, 'message' => 'پنل انتخاب نشده است.', 'inbounds' => null, 'proxies' => null];
        }
        if ($username === '') {
            return ['ok' => false, 'message' => 'نام کاربری اکانت داخل پنل را وارد کنید.', 'inbounds' => null, 'proxies' => null];
        }
        nexora_panel_protocol_helper_bootstrap();

        $stmt = $pdo->prepare("SELECT * FROM marzban_panel WHERE name_panel = ? LIMIT 1");
        $stmt->execute([$panelName]);
        $panel = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$panel) {
            return ['ok' => false, 'message' => 'این پنل در دیتابیس پیدا نشد.', 'inbounds' => null, 'proxies' => null];
        }

        $type = (string) ($panel['type'] ?? '');
        $ok = ['ok' => true, 'inbounds' => null, 'proxies' => null];

        try {
            if ($type === 'marzban') {
                if (($panel['version_panel'] ?? '1') === '1') {
                    $out = getuser($username, $panelName);
                    if (!empty($out['error'])) {
                        return ['ok' => false, 'message' => nexora_panel_protocol_error_text($out['error']), 'inbounds' => null, 'proxies' => null];
                    }
                    if (!empty($out['status']) && (int) $out['status'] !== 200) {
                        return ['ok' => false, 'message' => 'کد خطای پنل: ' . (int) $out['status'], 'inbounds' => null, 'proxies' => null];
                    }
                    $data = json_decode((string) $out['body'], true);
                    if (!is_array($data) || !isset($data['group_ids'])) {
                        return ['ok' => false, 'message' => 'اکانت «' . $username . '» در این پنل پیدا نشد.', 'inbounds' => null, 'proxies' => null];
                    }
                    $ok['inbounds'] = $data['group_ids'];
                    $ok['message'] = 'گروه اکانت «' . $username . '» پیدا شد.';
                    return $ok;
                }
                $out = getuser($username, $panelName);
                if (!empty($out['error'])) {
                    return ['ok' => false, 'message' => nexora_panel_protocol_error_text($out['error']), 'inbounds' => null, 'proxies' => null];
                }
                if (!empty($out['status']) && (int) $out['status'] !== 200) {
                    return ['ok' => false, 'message' => 'کد خطای پنل: ' . (int) $out['status'], 'inbounds' => null, 'proxies' => null];
                }
                $data = json_decode((string) $out['body'], true);
                if (!is_array($data) || !isset($data['proxies'])) {
                    return ['ok' => false, 'message' => 'اکانت «' . $username . '» در این پنل پیدا نشد.', 'inbounds' => null, 'proxies' => null];
                }
                foreach ($data['proxies'] as $key => &$value) {
                    if ($key === 'shadowsocks' || $key === 'trojan') {
                        unset($data['proxies'][$key]['password']);
                    } else {
                        unset($data['proxies'][$key]['id']);
                    }
                    if (count($data['proxies'][$key]) === 0) {
                        $data['proxies'][$key] = new stdClass();
                    }
                }
                unset($value);
                $ok['inbounds'] = $data['inbounds'] ?? [];
                $ok['proxies'] = $data['proxies'];
                $ok['message'] = 'پروتکل و اینباند اکانت «' . $username . '» پیدا شد.';
                return $ok;
            }

            if ($type === 'x-ui_single') {
                $out = get_clinets($username, $panel);
                if (!empty($out['error'])) {
                    return ['ok' => false, 'message' => nexora_panel_protocol_error_text($out['error']), 'inbounds' => null, 'proxies' => null];
                }
                if (!empty($out['status']) && (int) $out['status'] !== 200) {
                    return ['ok' => false, 'message' => 'کد خطای پنل: ' . (int) $out['status'], 'inbounds' => null, 'proxies' => null];
                }
                $data = json_decode((string) $out['body'], true);
                if (!is_array($data) || empty($data['success'])) {
                    return ['ok' => false, 'message' => 'اکانت «' . $username . '» در این پنل پیدا نشد.', 'inbounds' => null, 'proxies' => null];
                }
                $ok['inbounds'] = $data['obj']['inboundIds'] ?? [];
                $ok['message'] = 'اینباند اکانت «' . $username . '» پیدا شد.';
                return $ok;
            }

            if ($type === 'wg_mate') {
                $data = getuser_wgmate($username, $panelName)['user'] ?? null;
                if (!is_array($data)) {
                    return ['ok' => false, 'message' => 'اکانت «' . $username . '» در این پنل پیدا نشد.', 'inbounds' => null, 'proxies' => null];
                }
                $ok['proxies'] = ['protocols' => $data['protocols'] ?? [], 'serverId' => $data['serverId'] ?? null];
                $ok['message'] = 'پروتکل اکانت «' . $username . '» پیدا شد.';
                return $ok;
            }

            if ($type === 'nexora') {
                $data = getuser_nexora($username, $panelName)['user'] ?? null;
                if (!is_array($data)) {
                    return ['ok' => false, 'message' => 'اکانت «' . $username . '» در این پنل پیدا نشد.', 'inbounds' => null, 'proxies' => null];
                }
                $ok['proxies'] = [
                    'templateIds' => $data['templateIds'] ?? [],
                    'allTemplates' => $data['allTemplates'] ?? [],
                    'group' => $data['group'] ?? '',
                ];
                $ok['message'] = 'پروتکل اکانت «' . $username . '» پیدا شد.';
                return $ok;
            }

            if ($type === 'rebecca') {
                $data = json_decode((string) getuser_rebecca($username, $panelName)['body'], true);
                if (!is_array($data) || !isset($data['service_id'])) {
                    return ['ok' => false, 'message' => 'اکانت «' . $username . '» در این پنل پیدا نشد.', 'inbounds' => null, 'proxies' => null];
                }
                $ok['proxies'] = [$data['service_id']];
                $ok['message'] = 'سرویس اکانت «' . $username . '» پیدا شد.';
                return $ok;
            }

            if ($type === 'ibsng' || $type === 'mikrotik') {
                $ok['proxies'] = [$username];
                $ok['message'] = 'نام گروه پنل با «' . $username . '» پیدا شد.';
                return $ok;
            }
        } catch (Throwable $e) {
            error_log('nexora_panel_protocol_values: ' . $e->getMessage());
            return ['ok' => false, 'message' => 'خطا: ' . $e->getMessage(), 'inbounds' => null, 'proxies' => null];
        }

        return ['ok' => false, 'message' => 'این نوع پنل از تنظیم پروتکل با اکانت پشتیبانی نمی‌کند.', 'inbounds' => null, 'proxies' => null];
    }
}

if (!function_exists('nexora_product_protocol_values')) {
    /**
     * The values the account of a customer has to be created with.
     *
     * A product may name an account of the panel (the administrator writes
     * the name of an account that already exists there). The account is asked
     * again at every purchase, so the customer is always created in the group
     * the account is in at that moment - with the specs of that group.
     *
     * Without such an account the product keeps its own inbounds, and a
     * product without inbounds uses the ones of the panel.
     *
     * ['inbounds' => mixed, 'proxies' => mixed, 'reference' => string, 'resolved' => bool]
     */
    function nexora_product_protocol_values(PDO $pdo, array $panel, array $product): array
    {
        $reference = trim((string) ($product['protocol_account'] ?? ''));
        $result = ['inbounds' => null, 'proxies' => null, 'reference' => $reference, 'resolved' => false];

        if ($reference !== '') {
            $values = nexora_panel_protocol_values($pdo, (string) ($panel['name_panel'] ?? ''), $reference);
            if (!empty($values['ok'])) {
                $result['inbounds'] = $values['inbounds'];
                $result['proxies'] = $values['proxies'];
                $result['resolved'] = true;
                return $result;
            }
            error_log('nexora_product_protocol_values: the reference account could not be read: ' . (string) ($values['message'] ?? ''));
        }

        $productInbounds = trim((string) ($product['inbounds'] ?? ''));
        if ($productInbounds !== '' && $productInbounds !== 'null') {
            $list = json_decode($productInbounds, true);
            if (is_array($list) && $list) {
                $result['inbounds'] = $list;
            }
        }
        if ($result['inbounds'] === null) {
            $panelInbounds = trim((string) ($panel['inbounds'] ?? ''));
            if ($panelInbounds !== '' && $panelInbounds !== 'null') {
                $list = json_decode($panelInbounds, true);
                if (is_array($list) && $list) {
                    $result['inbounds'] = $list;
                }
            }
        }
        $productProxies = trim((string) ($product['proxies'] ?? ''));
        if ($productProxies !== '' && $productProxies !== 'null') {
            $result['proxies'] = json_decode($productProxies, true);
        }
        if ($result['proxies'] === null) {
            $panelProxies = trim((string) ($panel['proxies'] ?? ''));
            if ($panelProxies !== '' && $panelProxies !== 'null') {
                $result['proxies'] = json_decode($panelProxies, true);
            }
        }
        return $result;
    }
}

if (!function_exists('nexora_product_protocol_for_product')) {
    /**
     * The inbounds and the proxies a product is saved with.
     *
     * The administrator may name an account of the panel: the group of that
     * account is then the group every customer of the product is created in,
     * with the specs of that group. The panel itself is never changed, so the
     * other products of the panel keep their own inbounds.
     *
     * ['inbounds' => string, 'proxies' => string, 'message' => string, 'ok' => bool]
     */
    function nexora_product_protocol_for_product(PDO $pdo, string $panelName, string $reference, ?string $inbounds, ?string $proxies): array
    {
        $reference = trim($reference);
        $inbounds = (string) $inbounds;
        $proxies = (string) $proxies;
        if ($reference === '') {
            return ['inbounds' => $inbounds, 'proxies' => $proxies, 'message' => '', 'ok' => true];
        }
        $values = nexora_panel_protocol_values($pdo, $panelName, $reference);
        if (empty($values['ok'])) {
            return [
                'inbounds' => $inbounds,
                'proxies' => $proxies,
                'message' => '⚠️ ' . (string) ($values['message'] ?? 'اکانت مرجع پیدا نشد.'),
                'ok' => false,
            ];
        }
        $newInbounds = $values['inbounds'];
        $newProxies = $values['proxies'];
        return [
            'inbounds' => is_array($newInbounds) && $newInbounds ? json_encode(array_values($newInbounds)) : $inbounds,
            'proxies' => $newProxies === null ? $proxies : json_encode($newProxies),
            'message' => '✅ گروه اکانت «' . $reference . '» پیدا شد؛ مشتری‌های این محصول در همان گروه ساخته می‌شوند.',
            'ok' => true,
        ];
    }
}

// $username = the name of an account that already exists inside the panel.
// Returns ['ok' => bool, 'message' => string].
if (!function_exists('nexora_apply_panel_protocol')) {
    function nexora_apply_panel_protocol(PDO $pdo, string $panelName, string $username): array
    {
        $result = nexora_apply_panel_protocol_inner($pdo, $panelName, $username);
        // the panel is read from the cache, so it has to be cleared
        nexora_panel_protocol_clear_cache();
        return $result;
    }
}

if (!function_exists('nexora_apply_panel_protocol_inner')) {
    function nexora_apply_panel_protocol_inner(PDO $pdo, string $panelName, string $username): array
    {
        // The values of the account are asked once (the same way the bot asks
        // them) and the panel is written with them.
        $values = nexora_panel_protocol_values($pdo, $panelName, $username);
        if (empty($values['ok'])) {
            return ['ok' => false, 'message' => (string) ($values['message'] ?? 'خطای نامشخص')];
        }

        $panelName = trim($panelName);
        $username = trim($username);
        $stmt = $pdo->prepare("SELECT * FROM marzban_panel WHERE name_panel = ? LIMIT 1");
        $stmt->execute([$panelName]);
        $panel = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$panel) {
            return ['ok' => false, 'message' => 'این پنل در دیتابیس پیدا نشد.'];
        }
        $type = (string) ($panel['type'] ?? '');

        try {
            if ($type === 'marzban' && ($panel['version_panel'] ?? '1') === '1') {
                $up = $pdo->prepare("UPDATE marzban_panel SET inbounds = ? WHERE name_panel = ?");
                $up->execute([json_encode($values['inbounds']), $panelName]);
                return ['ok' => true, 'message' => 'پروتکل و اینباند پنل با اکانت «' . $username . '» تنظیم شد.'];
            }
            if ($type === 'marzban') {
                $up = $pdo->prepare("UPDATE marzban_panel SET inbounds = ?, proxies = ? WHERE name_panel = ?");
                $up->execute([json_encode($values['inbounds'] ?? []), json_encode($values['proxies'], true), $panelName]);
                return ['ok' => true, 'message' => 'پروتکل و اینباند پنل با اکانت «' . $username . '» تنظیم شد.'];
            }
            if ($type === 'x-ui_single') {
                $up = $pdo->prepare("UPDATE marzban_panel SET inbounds = ? WHERE name_panel = ?");
                $up->execute([json_encode($values['inbounds']), $panelName]);
                return ['ok' => true, 'message' => 'پروتکل و اینباند پنل با اکانت «' . $username . '» تنظیم شد.'];
            }
            if ($type === 'ibsng' || $type === 'mikrotik') {
                $up = $pdo->prepare("UPDATE marzban_panel SET proxies = ? WHERE name_panel = ?");
                $up->execute([json_encode([$username]), $panelName]);
                return ['ok' => true, 'message' => 'نام گروه پنل با «' . $username . '» تنظیم شد.'];
            }
            if (in_array($type, ['wg_mate', 'nexora', 'rebecca'], true)) {
                $up = $pdo->prepare("UPDATE marzban_panel SET proxies = ? WHERE name_panel = ?");
                $up->execute([json_encode($values['proxies'], true), $panelName]);
                return ['ok' => true, 'message' => 'پروتکل و اینباند پنل با اکانت «' . $username . '» تنظیم شد.'];
            }
        } catch (Throwable $e) {
            error_log('nexora_apply_panel_protocol: ' . $e->getMessage());
            return ['ok' => false, 'message' => 'خطا: ' . $e->getMessage()];
        }

        return ['ok' => false, 'message' => 'این نوع پنل از تنظیم پروتکل با اکانت پشتیبانی نمی‌کند.'];
    }
}