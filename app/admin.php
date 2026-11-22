<?php

/**
 * app/admin.php
 * Código organizado e comentado para ser fácil de entender e manter.
 * Assinado por Flyzinho — toque próprio, feito com cuidado.
 */



declare(strict_types=1);
require_once __DIR__ . '/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/**
 * Converte texto em HTML seguro para evitar a execução de código introduzido pelo utilizador.
 */
function admin_escape(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
/**
 * Verifica se existe uma sessão administrativa válida.
 * Uma sessão sem actividade durante 30 minutos expira automaticamente.
 */
function admin_require_login(): void
{
    $timeout = 30 * 60;

    if (!empty($_SESSION['admin_id']) && isset($_SESSION['last_activity']) && time() - (int)$_SESSION['last_activity'] > $timeout) {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        header('Location: login.php?expired=1');
        exit;
    }

    if (empty($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit;
    }
    $_SESSION['last_activity'] = time();
}
/**
 * Obtém ou cria o token usado para confirmar que uma alteração veio do próprio painel.
 */
function admin_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}
/**
 * Compara o token enviado pelo formulário com o token guardado na sessão.
 */
function admin_verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';

    if (!is_string($token) || !hash_equals(admin_csrf_token(), $token)) {
        http_response_code(419);
        exit('Pedido expirado ou token de segurança inválido. Volte atrás e tente novamente.');
    }
}
/**
 * Envia o utilizador de volta à secção correcta após guardar ou eliminar dados.
 */
function admin_redirect(string $anchor = ''): never
{
    $pages = ['configuracoes' => 'configuracoes','projectos' => 'projectos','servicos' => 'servicos','testemunhos' => 'testemunhos','pedidos' => 'pedidos'];
    $target = $pages[$anchor] ?? 'inicio';
    header('Location: dashboard.php?page=' . rawurlencode($target) . '&ok=1');
    exit;
}
/**
 * Remove etiquetas HTML perigosas e mantém apenas a estrutura necessária para artigos.
 */
function admin_sanitize_blog_html(string $html): string
{
    $html = strip_tags($html, '<p><br><h2><h3><h4><strong><b><em><i><ul><ol><li><blockquote>');

    return preg_replace('/<([a-z0-9]+)\b[^>]*>/i', '<$1>', $html) ?? '';
}
/**
 * Valida e guarda uma imagem enviada pelo administrador.
 * Rejeita ficheiros grandes, formatos inesperados e imagens com dimensões excessivas.
 */
function admin_upload_image(string $field, ?string $oldPath = null): ?string
{
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return $oldPath;
    }
    $file = $_FILES[$field];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Falha no envio da imagem.');
    }

    if (!is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('Ficheiro de imagem inválido.');
    }

    if ((int)$file['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('A imagem não pode ultrapassar 5 MB.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Formato não permitido. Utilize JPG, PNG ou WEBP.');
    }
    $imageInfo = @getimagesize($file['tmp_name']);

    if ($imageInfo === false || ($imageInfo['mime'] ?? '') !== $mime) {
        throw new RuntimeException('O ficheiro enviado não é uma imagem válida.');
    }

    if (($imageInfo[0] ?? 0) > 6000 || ($imageInfo[1] ?? 0) > 6000) {
        throw new RuntimeException('A imagem excede as dimensões máximas de 6000 × 6000 píxeis.');
    }

    $uploadDir = dirname(__DIR__) . '/public/uploads';

    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        throw new RuntimeException('Não foi possível preparar a pasta de imagens.');
    }
    $filename = bin2hex(random_bytes(18)) . '.' . $allowed[$mime];

    if (!move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $filename)) {
        throw new RuntimeException('Não foi possível guardar a imagem.');
    }

    if ($oldPath && preg_match('~^uploads/[a-f0-9]{36}\.(jpg|png|webp)$~', $oldPath)) {
        $oldFile = dirname(__DIR__) . '/public/' . $oldPath;

        if (is_file($oldFile)) {
            @unlink($oldFile);
        }
    }

    return 'uploads/' . $filename;
}
/**
 * Guarda várias fotografias associadas a um projecto, respeitando o limite definido.
 */
function admin_upload_project_gallery(string $field, int $projectId, PDO $pdo): void
{
    if (!isset($_FILES[$field]) || !is_array($_FILES[$field]['name'] ?? null)) {
        return;
    }
    $files = $_FILES[$field];
    $total = count($files['name']);

    if ($total > 10) {
        throw new RuntimeException('Pode enviar no máximo 10 imagens de cada vez.');
    }
    $position = (int)$pdo->query('SELECT COALESCE(MAX(sort_order),0) FROM project_images WHERE project_id=' . $projectId)->fetchColumn();
    for ($i = 0; $i < $total; $i++) {
        if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $_FILES['_project_gallery_single'] = [
            'name' => $files['name'][$i], 'type' => $files['type'][$i] ?? '',
            'tmp_name' => $files['tmp_name'][$i] ?? '', 'error' => $files['error'][$i],
            'size' => $files['size'][$i] ?? 0,
        ];
        $path = admin_upload_image('_project_gallery_single');
        $q = $pdo->prepare('INSERT INTO project_images(project_id,image_path,sort_order) VALUES(?,?,?)');
        $q->execute([$projectId, $path, ++$position]);
    }
    unset($_FILES['_project_gallery_single']);
}
/**
 * Apaga uma imagem local apenas quando o endereço corresponde ao padrão seguro de uploads.
 */
function admin_delete_local_image(?string $path): void
{
    if ($path && preg_match('~^uploads/[a-f0-9]{36}\.(jpg|png|webp)$~', $path)) {
        $file = dirname(__DIR__) . '/public/' . $path;

        if (is_file($file)) {
            @unlink($file);
        }
    }
}
