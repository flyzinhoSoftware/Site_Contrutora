<?php
declare(strict_types=1);
require_once __DIR__ . '/../../app/session.php';
require_once __DIR__ . '/../../app/db.php';
if (!is_file(__DIR__ . '/../../vendor/autoload.php')) { http_response_code(503); header('Content-Type: application/json; charset=utf-8'); echo json_encode(['ok'=>false,'message'=>'Execute composer install para activar o login Google.']); exit; }
require_once __DIR__ . '/../../vendor/autoload.php';
header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); throw new RuntimeException('Método não permitido.'); }
    if (GOOGLE_CLIENT_ID === '') { http_response_code(503); throw new RuntimeException('O acesso Google ainda não está configurado.'); }
    $data = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
    $loginCsrf = (string)($data['csrf'] ?? '');
    $expectedCsrf = (string)($_SESSION['google_login_csrf'] ?? '');
    if ($expectedCsrf === '' || !hash_equals($expectedCsrf, $loginCsrf)) { http_response_code(419); throw new RuntimeException('Pedido de autenticação inválido. Actualize a página.'); }
    $nonce = (string)($data['nonce'] ?? '');
    $expectedNonce = (string)($_SESSION['google_login_nonce'] ?? '');
    if ($expectedNonce === '' || !hash_equals($expectedNonce, $nonce)) { http_response_code(419); throw new RuntimeException('Não foi possível validar esta tentativa de acesso.'); }
    $credential = (string)($data['credential'] ?? '');
    if ($credential === '' || strlen($credential) > 10000) { http_response_code(400); throw new RuntimeException('Credencial Google inválida.'); }
    $client = new Google\Client(['client_id' => GOOGLE_CLIENT_ID]);
    $payload = $client->verifyIdToken($credential);
    if (!$payload || ($payload['aud'] ?? '') !== GOOGLE_CLIENT_ID || !hash_equals($expectedNonce, (string)($payload['nonce'] ?? '')) || empty($payload['sub']) || empty($payload['email']) || empty($payload['email_verified'])) {
        http_response_code(401); throw new RuntimeException('Não foi possível validar a conta Google.');
    }
    $pdo = db();
    $stmt = $pdo->prepare('INSERT INTO comment_users(google_sub,name,email,avatar_url) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE name=VALUES(name),email=VALUES(email),avatar_url=VALUES(avatar_url)');
    $stmt->execute([(string)$payload['sub'], (string)($payload['name'] ?? 'Visitante'), (string)$payload['email'], isset($payload['picture']) ? (string)$payload['picture'] : null]);
    $find = $pdo->prepare('SELECT id,name,email,avatar_url FROM comment_users WHERE google_sub=?');
    $find->execute([(string)$payload['sub']]);
    session_regenerate_id(true);
    $_SESSION['comment_user'] = $find->fetch();
    $_SESSION['comment_csrf'] = bin2hex(random_bytes(32));
    unset($_SESSION['google_login_csrf'], $_SESSION['google_login_nonce']);
    echo json_encode(['ok'=>true,'name'=>$_SESSION['comment_user']['name'],'csrf'=>$_SESSION['comment_csrf']], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    if (http_response_code() < 400) http_response_code(400);
    echo json_encode(['ok'=>false,'message'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
