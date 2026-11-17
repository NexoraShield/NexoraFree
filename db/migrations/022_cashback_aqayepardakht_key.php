<?php

/**
 * One key for the aqayepardakht cashback everywhere.
 *
 * The web panel (gateways.php) reads and writes `chashbackaqayepardakht`,
 * while the bot and the seed table used the legacy spelling
 * `chashbackaqaypardokht`. Configurations made in the panel were stored
 * under a key the bot never read, so the buyer never received the cashback.
 *
 * Fresh installations are fixed in db/tables/PaySetting.php; existing ones
 * are migrated here. The value configured by the admin is kept.
 */

return static function (PDO $pdo, Schema $schema): void {
    if (!$schema->tableExists('PaySetting')) {
        return;
    }

    // Copy the configured value onto the panel's key (first writer wins:
    // an admin who already configured both keeps the panel value).
    $pdo->exec(
        "INSERT IGNORE INTO PaySetting (NamePay, ValuePay)
         SELECT 'chashbackaqayepardakht', ValuePay FROM PaySetting WHERE NamePay = 'chashbackaqaypardokht'"
    );
    $pdo->exec("DELETE FROM PaySetting WHERE NamePay = 'chashbackaqaypardokht'");
};
