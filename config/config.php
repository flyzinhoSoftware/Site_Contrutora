<?php

/**
 * config/config.php
 * Código organizado e comentado para ser fácil de entender e manter.
 * Assinado por Flyzinho — toque próprio, feito com cuidado.
 */



declare(strict_types=1);
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('DB_NAME') ?: 'obraforte_db');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('APP_NAME', 'Moz Construções');
define('WHATSAPP_NUMBER', '258840000000'); // Altere para o número oficial, apenas dígitos.
define('BASE_URL', '/obraforte_site/public');
define('SITE_URL', rtrim(getenv('SITE_URL') ?: 'http://localhost/obraforte_site/public', '/'));
define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID') ?: '');
define('SMTP_HOST', getenv('SMTP_HOST') ?: '');
define('SMTP_PORT', (int)(getenv('SMTP_PORT') ?: 587));
define('SMTP_USERNAME', getenv('SMTP_USERNAME') ?: '');
define('SMTP_PASSWORD', getenv('SMTP_PASSWORD') ?: '');
define('SMTP_FROM_EMAIL', getenv('SMTP_FROM_EMAIL') ?: '');
define('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME') ?: APP_NAME);
define('VAPID_PUBLIC_KEY', getenv('VAPID_PUBLIC_KEY') ?: '');
define('VAPID_PRIVATE_KEY', getenv('VAPID_PRIVATE_KEY') ?: '');
define('VAPID_SUBJECT', getenv('VAPID_SUBJECT') ?: '');