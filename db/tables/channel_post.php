<?php

return [
    'create' => <<<SQL
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        op_id varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
        admin_id varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
        channel_link varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
        channel_chat_id varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
        channel_title varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
        content_type varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text',
        message_text TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
        file_id varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
        buttons varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '[]',
        status varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
        published_message_id varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
        last_error TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        published_at TIMESTAMP NULL DEFAULT NULL,
        INDEX idx_admin (admin_id, status)
        SQL,
    'columns' => [
        ['op_id', null, 'VARCHAR(64) NOT NULL'],
        ['admin_id', null, 'VARCHAR(64) NOT NULL'],
        ['channel_link', '', 'VARCHAR(255) NOT NULL DEFAULT \'\''],
        ['channel_chat_id', '', 'VARCHAR(64) NOT NULL DEFAULT \'\''],
        ['channel_title', '', 'VARCHAR(255) NOT NULL DEFAULT \'\''],
        ['content_type', 'text', 'VARCHAR(16) NOT NULL DEFAULT \'text\''],
        ['message_text', null, 'TEXT'],
        ['file_id', '', 'VARCHAR(255) NOT NULL DEFAULT \'\''],
        ['buttons', '[]', 'VARCHAR(2000) NOT NULL DEFAULT \'[]\''],
        ['status', 'draft', 'VARCHAR(20) NOT NULL DEFAULT \'draft\''],
        ['published_message_id', '', 'VARCHAR(64) NOT NULL DEFAULT \'\''],
        ['last_error', null, 'TEXT'],
    ],
];
