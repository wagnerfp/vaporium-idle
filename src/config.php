<?php
require_once __DIR__ . '/../env.php'; // deploy-only, fora do git (define IDLE_DB_PASS)
date_default_timezone_set('America/Sao_Paulo'); // alinhar com MySQL NOW() (SYSTEM) (define IDLE_DB_PASS)
define('TICK_SECONDS', 6);
define('OFFLINE_CAP_SECONDS', 8 * 3600);
define('LOG_KEEP', 50);
