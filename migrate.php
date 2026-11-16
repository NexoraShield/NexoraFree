<?php
$target = $argv[1] ?? '';
$target = rtrim($target, "/\\");
if ($target === '' || !is_dir($target)) { fwrite(STDERR, "Target directory not found.\n"); exit(1); }
$configFile = $target . '/config.php';
$helperFile = __DIR__ . '/nexora_style.php';
if (!is_file($configFile)) { fwrite(STDERR, "config.php not found in target.\n"); exit(1); }
if (!is_file($helperFile)) { fwrite(STDERR, "nexora_style.php not found.\n"); exit(1); }
require $configFile;
require $helperFile;
if (!isset($pdo) || !($pdo instanceof PDO)) { fwrite(STDERR, "Database connection (\$pdo) not available.\n"); exit(1); }

// The installed code owns the helper functions the migrations rely on
// (extendMethodKey / usernameMethodKey / languagechange). Prefer the copy that
// is already installed in the target, fall back to the packaged one.
if (!function_exists('extendMethodKey') || !function_exists('languagechange')) {
    foreach ([$target . '/function.php', __DIR__ . '/function.php'] as $functionFile) {
        if (!is_file($functionFile)) {
            continue;
        }
        require_once $functionFile;
        if (function_exists('extendMethodKey') && function_exists('languagechange')) {
            break;
        }
    }
}

try {
    // Full schema: tables, missing columns, indexes and data migrations.
    // This is what the web installer runs (db/bootstrap.php); the CLI updater
    // must do the same so fresh installs AND upgrades end up complete.
    $schemaFile = __DIR__ . '/db/Schema.php';
    if (is_file($schemaFile)) {
        require_once $schemaFile;
        $schema = new Schema($pdo, [
            'adminnumber' => isset($adminnumber) ? $adminnumber : '',
            'textbotlang' => static function () {
                return languagechange();
            },
        ]);
        $schema->applyTables(__DIR__ . '/db/tables', require __DIR__ . '/db/tables.php');
        $schema->runMigrations(__DIR__ . '/db/migrations');
        $schema->applyIndexes(require __DIR__ . '/db/indexes.php');
        echo "Schema applied (tables, columns, indexes, migrations).\n";
    } else {
        fwrite(STDERR, "WARNING: db/Schema.php not found - only auxiliary tables were ensured.\n");
    }
    $added = nexora_ensure_style_columns($pdo);
    if (function_exists('nexora_ensure_emoji_table')) nexora_ensure_emoji_table($pdo);
    $pdo->exec("CREATE TABLE IF NOT EXISTS support_tickets (id INT AUTO_INCREMENT PRIMARY KEY, id_user VARCHAR(32) NOT NULL, subject VARCHAR(255) NOT NULL, message TEXT NOT NULL, status VARCHAR(20) NOT NULL DEFAULT 'open', admin_reply TEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, INDEX idx_status (status), INDEX idx_user (id_user)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "support_tickets table ensured.\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS op_queue (id INT AUTO_INCREMENT PRIMARY KEY, id_user VARCHAR(32) NOT NULL, id_invoice VARCHAR(64) NULL, op_type VARCHAR(32) NOT NULL, payload TEXT NULL, status VARCHAR(20) NOT NULL DEFAULT 'pending', attempts INT NOT NULL DEFAULT 0, last_error TEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, INDEX idx_status (status), INDEX idx_user (id_user)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "op_queue table ensured.\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS custom_apps (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(128) NOT NULL, platform VARCHAR(64) NULL, download_link VARCHAR(512) NULL, tutorial TEXT NULL, icon VARCHAR(64) NULL, sort_order INT NOT NULL DEFAULT 0, status VARCHAR(20) NOT NULL DEFAULT 'active', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "custom_apps table ensured.\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS collab_notes (id INT AUTO_INCREMENT PRIMARY KEY, admin_user VARCHAR(64) NULL, title VARCHAR(255) NOT NULL, body TEXT NOT NULL, pinned TINYINT(1) NOT NULL DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "collab_notes table ensured.\n";
    // The operations of the messages of the channel: every operation of the
    // administrator has a row of its own, so two administrators never step on
    // each other and a half finished operation can always be found again.
    $pdo->exec("CREATE TABLE IF NOT EXISTS channel_post (id INT AUTO_INCREMENT PRIMARY KEY, op_id VARCHAR(64) NOT NULL, admin_id VARCHAR(64) NOT NULL, channel_link VARCHAR(255) NOT NULL DEFAULT '', channel_chat_id VARCHAR(64) NOT NULL DEFAULT '', channel_title VARCHAR(255) NOT NULL DEFAULT '', content_type VARCHAR(16) NOT NULL DEFAULT 'text', message_text TEXT NULL, file_id VARCHAR(255) NOT NULL DEFAULT '', buttons VARCHAR(2000) NOT NULL DEFAULT '[]', status VARCHAR(20) NOT NULL DEFAULT 'draft', published_message_id VARCHAR(64) NOT NULL DEFAULT '', last_error TEXT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, published_at TIMESTAMP NULL DEFAULT NULL, INDEX idx_admin (admin_id, status)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    // An older bot has the column of the buttons without a default: the
    // column is made wider and it gets its default.
    try {
        $pdo->exec("ALTER TABLE channel_post MODIFY buttons VARCHAR(2000) NOT NULL DEFAULT '[]'");
    } catch (Throwable $e) {
        echo "the table of the operations keeps its shape: " . $e->getMessage() . "\n";
    }
    echo "channel_post table ensured.\n";
    // The products remember the account of the panel that shows the group the
    // customer has to be created in (the same way the bot does it).
    try {
        $pdo->exec("ALTER TABLE product ADD COLUMN protocol_account VARCHAR(200) NULL");
        echo "the column of the reference account of a product was added.\n";
    } catch (Throwable $e) {
        echo "the column of the reference account of a product is already there.\n";
    }
    if ($added === []) echo "Columns already exist — nothing to do.\n";
    else echo "Added columns: " . implode(', ', $added) . "\n";
    $frenzyDefaults = [
        'frenzyexstatus' => 'offfrenzyex',
        'frenzyex_api_key' => '',
        'frenzyex_base_url' => '',
        'frenzyex_callback_secret' => '',
        'cashbackfrenzyex' => '0',
        'minbalancefrenzyex' => '20000',
        'maxbalancefrenzyex' => '1000000',
    ];
    foreach ($frenzyDefaults as $name => $val) {
        $chk = $pdo->prepare("SELECT COUNT(*) FROM PaySetting WHERE NamePay = ?");
        $chk->execute([$name]);
        if ((int) $chk->fetchColumn() === 0) {
            $pdo->prepare("INSERT INTO PaySetting (NamePay, ValuePay) VALUES (?, ?)")->execute([$name, $val]);
            echo "Added PaySetting: {$name}\n";
        }
    }
    echo "Migration OK.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Migration failed: " . $e->getMessage() . "\n");
    exit(1);
}
