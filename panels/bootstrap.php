<?php

require_once __DIR__ . '/Api/Marzban.php';
require_once __DIR__ . '/Api/x-ui_single.php';
require_once __DIR__ . '/Api/hiddify.php';
require_once __DIR__ . '/Api/marzneshin.php';
require_once __DIR__ . '/Api/WGDashboard.php';
require_once __DIR__ . '/Api/ibsng.php';
require_once __DIR__ . '/Api/mikrotik.php';
require_once __DIR__ . '/Api/Rebeca.php';
require_once __DIR__ . '/Api/nexora.php';
require_once __DIR__ . '/Api/wgmate.php';

require_once __DIR__ . '/BasePanelDriver.php';
require_once __DIR__ . '/PanelRegistry.php';

// The protocol and the inbound of a product (the account the administrator
// named): the tools of the panels need it.
require_once dirname(__DIR__) . '/panel_protocol.php';
