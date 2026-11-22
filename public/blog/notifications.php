<?php

/**
 * public/blog/notifications.php
 * Código organizado e comentado para ser fácil de entender e manter.
 * Assinado por Flyzinho — toque próprio, feito com cuidado.
 */



declare(strict_types=1);
require_once __DIR__ . '/../../app/session.php';
require_once __DIR__ . '/../../app/blog-notifications.php';
header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);

        throw new RuntimeException('Método não permitido.');
    }

    $user = $_SESSION['comment_user'] ?? null;

    if (!is_array($user) || empty($user['id'])) {
        http_response_code(401);

        throw new RuntimeException('Entre com a sua conta Google para gerir notificações.');
    }

    $data = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
    $expectedCsrf = (string)($_SESSION['comment_csrf'] ?? '');
    $csrf = (string)($data['csrf'] ?? '');

    if ($expectedCsrf === '' || !hash_equals($expectedCsrf, $csrf)) {
        http_response_code(419);

        throw new RuntimeException('Sessão inválida. Actualize a página.');
    }

    $emailEnabled = $data['email_enabled'] ?? null;
    $pushEnabled = $data['push_enabled'] ?? null;

    if (!is_bool($emailEnabled) || !is_bool($pushEnabled)) {
        http_response_code(400);

        throw new RuntimeException('Seleccione as preferências de notificação.');
    }

    if ($emailEnabled && !blog_email_notifications_ready()) {
        http_response_code(503);

        throw new RuntimeException('As notificações por e-mail ainda não estão configuradas.');
    }

    if ($pushEnabled && !blog_push_notifications_ready()) {
        http_response_code(503);

        throw new RuntimeException('As notificações no navegador ainda não estão configuradas.');
    }

    $subscription = $data['subscription'] ?? null;

    if ($pushEnabled && !is_array($subscription)) {
        http_response_code(400);

        throw new RuntimeException('Autorize as notificações no navegador e tente novamente.');
    }

    $pdo = db();
    $pdo->beginTransaction();
    $save = $pdo->prepare('INSERT INTO blog_notification_preferences(user_id,email_enabled,push_enabled) VALUES(?,?,?) ON DUPLICATE KEY UPDATE email_enabled=VALUES(email_enabled),push_enabled=VALUES(push_enabled)');
    $save->execute([(int)$user['id'], (int)$emailEnabled, (int)$pushEnabled]);

    if ($pushEnabled) {
        $endpoint = (string)($subscription['endpoint'] ?? '');
        $keys = $subscription['keys'] ?? [];
        $p256dh = is_array($keys) ? (string)($keys['p256dh'] ?? '') : '';
        $auth = is_array($keys) ? (string)($keys['auth'] ?? '') : '';
        $parts = parse_url($endpoint);
        $host = strtolower((string)($parts['host'] ?? ''));
        $allowedPushHost = $host === 'fcm.googleapis.com'
            || $host === 'web.push.apple.com'
            || str_ends_with($host, '.push.services.mozilla.com')
            || str_ends_with($host, '.notify.windows.com');

        if (strlen($endpoint) > 2048 || ($parts['scheme'] ?? '') !== 'https' || !empty($parts['user']) || !empty($parts['pass']) || isset($parts['port']) && (int)$parts['port'] !== 443 || !$allowedPushHost) {
            http_response_code(400);

            throw new RuntimeException('A subscrição de notificações do navegador é inválida.');
        }

        if (!preg_match('/^[A-Za-z0-9_-]{20,200}$/', $p256dh) || !preg_match('/^[A-Za-z0-9_-]{8,200}$/', $auth)) {
            http_response_code(400);

            throw new RuntimeException('As chaves da subscrição do navegador são inválidas.');
        }

        $saveSubscription = $pdo->prepare('INSERT INTO blog_push_subscriptions(user_id,endpoint_hash,endpoint,p256dh,auth_token) VALUES(?,?,?,?,?) ON DUPLICATE KEY UPDATE user_id=VALUES(user_id),endpoint=VALUES(endpoint),p256dh=VALUES(p256dh),auth_token=VALUES(auth_token)');
        $saveSubscription->execute([(int)$user['id'], hash('sha256', $endpoint), $endpoint, $p256dh, $auth]);
    } else {
        $deleteSubscriptions = $pdo->prepare('DELETE FROM blog_push_subscriptions WHERE user_id=?');
        $deleteSubscriptions->execute([(int)$user['id']]);
    }

    $pdo->commit();
    echo json_encode(['ok' => true,'email_enabled' => $emailEnabled,'push_enabled' => $pushEnabled], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if (http_response_code() < 400) {
        http_response_code(400);
    }

    if ($e instanceof PDOException || $e instanceof JsonException) {
        error_log('Blog notification preference update failed: ' . $e->getMessage());
        echo json_encode(['ok' => false,'message' => 'Não foi possível guardar as preferências. Tente novamente.'], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['ok' => false,'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}
