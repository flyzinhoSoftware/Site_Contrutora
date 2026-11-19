<?php
declare(strict_types=1);
require_once __DIR__ . '/../../app/session.php';
require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');

function respond(int $status, array $data): never {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(405, ['ok' => false, 'message' => 'Método não permitido.']);
$csrf = (string)($_POST['csrf'] ?? '');
$expected = (string)($_SESSION['blog_reaction_csrf'] ?? '');
if ($expected === '' || !hash_equals($expected, $csrf)) respond(419, ['ok' => false, 'message' => 'Sessão expirada. Actualize a página.']);

$postId = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
$reaction = (string)($_POST['reaction'] ?? '');
$allowed = ['like', 'love', 'useful', 'insightful'];
if (!$postId || $postId < 1 || !in_array($reaction, $allowed, true)) respond(422, ['ok' => false, 'message' => 'Reacção inválida.']);
$visitor = (string)($_COOKIE['moz_blog_visitor'] ?? '');
if (!preg_match('/^[a-f0-9]{64}$/', $visitor)) respond(400, ['ok' => false, 'message' => 'Identificador de visitante inválido. Actualize a página.']);

try {
    $pdo = db();
    $check = $pdo->prepare("SELECT id FROM blog_posts WHERE id=? AND status='published' AND published_at<=NOW() LIMIT 1");
    $check->execute([$postId]);
    if (!$check->fetchColumn()) respond(404, ['ok' => false, 'message' => 'Artigo não encontrado.']);

    $hash = hash('sha256', $visitor);
    $pdo->beginTransaction();
    $find = $pdo->prepare('SELECT reaction FROM blog_post_reactions WHERE post_id=? AND visitor_hash=? FOR UPDATE');
    $find->execute([$postId, $hash]);
    $current = $find->fetchColumn();
    if ($current === $reaction) {
        $delete = $pdo->prepare('DELETE FROM blog_post_reactions WHERE post_id=? AND visitor_hash=?');
        $delete->execute([$postId, $hash]);
        $selected = null;
    } elseif ($current !== false) {
        $update = $pdo->prepare('UPDATE blog_post_reactions SET reaction=? WHERE post_id=? AND visitor_hash=?');
        $update->execute([$reaction, $postId, $hash]);
        $selected = $reaction;
    } else {
        $insert = $pdo->prepare('INSERT INTO blog_post_reactions(post_id,visitor_hash,reaction) VALUES(?,?,?)');
        $insert->execute([$postId, $hash, $reaction]);
        $selected = $reaction;
    }
    $pdo->commit();

    $counts = $pdo->prepare("SELECT reaction, COUNT(*) total FROM blog_post_reactions WHERE post_id=? GROUP BY reaction");
    $counts->execute([$postId]);
    $totals = ['like'=>0,'love'=>0,'useful'=>0,'insightful'=>0];
    foreach ($counts->fetchAll() as $row) $totals[$row['reaction']] = (int)$row['total'];
    respond(200, ['ok'=>true, 'selected'=>$selected, 'counts'=>$totals]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
    respond(500, ['ok'=>false, 'message'=>'Não foi possível guardar a reacção. Tente novamente.']);
}
