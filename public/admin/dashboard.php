<?php

/**
 * public/admin/dashboard.php
 * Código organizado e comentado para ser fácil de entender e manter.
 * Assinado por Flyzinho — toque próprio, feito com cuidado.
 */


declare(strict_types=1);
require_once __DIR__ . '/../../app/admin.php';
require_once __DIR__ . '/../../app/partners.php';
admin_require_login();
$pdo = db();
$error = '';

// Cada página válida possui um título próprio no painel.
$page = (string)($_GET['page'] ?? 'inicio');
$allowedPages = ['inicio','configuracoes','projectos','servicos','testemunhos','pedidos','conta'];

if (!in_array($page, $allowedPages, true)) {
    $page = 'inicio';
}
$pageTitles = ['inicio' => 'Painel principal','configuracoes' => 'Conteúdos e contactos','projectos' => 'Gestão de projectos','servicos' => 'Gestão de serviços','testemunhos' => 'Testemunhos','pedidos' => 'Pedidos de orçamento','conta' => 'Conta do administrador'];

// Terminar a sessão envia o utilizador de volta ao início de sessão.
if (isset($_GET['logout'])) {
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    header('Location: login.php');
    exit;
}

// Todas as alterações feitas no painel passam por este bloco seguro.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_verify_csrf();
    $action = (string)($_POST['action'] ?? '');

    try {
        if ($action === 'update_admin_account') {
            $adminId = (int)($_SESSION['admin_id'] ?? 0);
            $currentPassword = (string)($_POST['current_password'] ?? '');
            $newEmail = trim((string)($_POST['email'] ?? ''));
            $newPassword = (string)($_POST['new_password'] ?? '');
            $confirmPassword = (string)($_POST['confirm_password'] ?? '');

            if ($adminId < 1 || $currentPassword === '') {
                throw new RuntimeException('Introduza a palavra-passe actual para confirmar as alterações.');
            }

            $adminQuery = $pdo->prepare('SELECT email, password FROM admins WHERE id = ? LIMIT 1');
            $adminQuery->execute([$adminId]);
            $adminAccount = $adminQuery->fetch();

            if (!$adminAccount || !password_verify($currentPassword, $adminAccount['password'])) {
                throw new RuntimeException('A palavra-passe actual está incorrecta.');
            }

            if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Introduza um endereço de e-mail válido.');
            }

            $duplicateEmail = $pdo->prepare('SELECT id FROM admins WHERE email = ? AND id <> ? LIMIT 1');
            $duplicateEmail->execute([$newEmail, $adminId]);

            if ($duplicateEmail->fetch()) {
                throw new RuntimeException('Este endereço de e-mail já está associado a outra conta.');
            }

            $passwordHash = $adminAccount['password'];

            if ($newPassword !== '' || $confirmPassword !== '') {
                if (strlen($newPassword) < 12) {
                    throw new RuntimeException('A nova palavra-passe deve ter pelo menos 12 caracteres.');
                }

                if (!hash_equals($newPassword, $confirmPassword)) {
                    throw new RuntimeException('A confirmação da nova palavra-passe não corresponde.');
                }

                $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            }

            $updateAdmin = $pdo->prepare('UPDATE admins SET email = ?, password = ? WHERE id = ?');
            $updateAdmin->execute([$newEmail, $passwordHash, $adminId]);

            // Renova os identificadores da sessão após uma alteração de credenciais.
            session_regenerate_id(true);
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            admin_redirect('conta');
        }

        if ($action === 'save_project') {
            $id = (int)($_POST['id'] ?? 0);
            $title = trim((string)($_POST['title'] ?? ''));
            $location = trim((string)($_POST['location'] ?? ''));
            $category = trim((string)($_POST['category'] ?? ''));
            $description = trim((string)($_POST['description'] ?? ''));
            $status = (string)($_POST['status'] ?? 'Planeado');

            if ($title === '' || $location === '' || $category === '' || !in_array($status, ['Concluído','Em andamento','Planeado'], true)) {
                throw new RuntimeException('Preencha os campos obrigatórios do projecto.');
            }
            $old = null;

            if ($id > 0) {
                $q = $pdo->prepare('SELECT image_url FROM projects WHERE id=?');
                $q->execute([$id]);
                $old = $q->fetchColumn();

                if ($old === false) {
                    throw new RuntimeException('Projecto não encontrado.');
                }
            }
            $uploadProvided = isset($_FILES['image_file']) && ($_FILES['image_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
            $externalImage = trim((string)($_POST['image_url'] ?? ''));

            if ($uploadProvided) {
                $image = admin_upload_image('image_file', is_string($old) ? $old : null);
            } elseif ($externalImage !== '') {
                if (!filter_var($externalImage, FILTER_VALIDATE_URL) || !in_array(strtolower((string)parse_url($externalImage, PHP_URL_SCHEME)), ['http','https'], true)) {
                    throw new RuntimeException('Indique um URL de imagem válido com HTTP ou HTTPS.');
                }

                if (is_string($old)) {
                    admin_delete_local_image($old);
                }
                $image = $externalImage;
            } else {
                $image = is_string($old) ? $old : null;
            }

            if ($id > 0) {
                $q = $pdo->prepare('UPDATE projects SET title=?,location=?,category=?,description=?,image_url=?,status=? WHERE id=?');
                $q->execute([$title,$location,$category,$description,$image,$status,$id]);
                $projectId = $id;
            } else {
                $q = $pdo->prepare('INSERT INTO projects(title,location,category,description,image_url,status) VALUES(?,?,?,?,?,?)');
                $q->execute([$title,$location,$category,$description,$image,$status]);
                $projectId = (int)$pdo->lastInsertId();
            }
            admin_upload_project_gallery('gallery_files', $projectId, $pdo);
            admin_redirect('projectos');
        }

        if ($action === 'delete_project') {
            $id = (int)($_POST['id'] ?? 0);
            $q = $pdo->prepare('SELECT image_url FROM projects WHERE id=?');
            $q->execute([$id]);
            $img = $q->fetchColumn();
            $gallery = $pdo->prepare('SELECT image_path FROM project_images WHERE project_id=?');
            $gallery->execute([$id]);

            foreach ($gallery->fetchAll(PDO::FETCH_COLUMN) as $galleryImage) {
                admin_delete_local_image((string)$galleryImage);
            }
            $q = $pdo->prepare('DELETE FROM projects WHERE id=?');
            $q->execute([$id]);

            if (is_string($img)) {
                admin_delete_local_image($img);
            }
            admin_redirect('projectos');
        }

        if ($action === 'save_service') {
            $id = (int)($_POST['id'] ?? 0);
            $title = trim((string)($_POST['title'] ?? ''));
            $description = trim((string)($_POST['description'] ?? ''));
            $icon = trim((string)($_POST['icon'] ?? 'bi-building'));

            if ($title === '' || $description === '') {
                throw new RuntimeException('Indique o nome e a descrição do serviço.');
            }

            if (!preg_match('/^bi-[a-z0-9-]{2,60}$/', $icon)) {
                $icon = 'bi-building';
            }

            if ($id > 0) {
                $q = $pdo->prepare('UPDATE services SET title=?,description=?,icon=? WHERE id=?');
                $q->execute([$title,$description,$icon,$id]);
            } else {
                $q = $pdo->prepare('INSERT INTO services(title,description,icon) VALUES(?,?,?)');
                $q->execute([$title,$description,$icon]);
            }
            admin_redirect('servicos');
        }

        if ($action === 'delete_service') {
            $q = $pdo->prepare('DELETE FROM services WHERE id=?');
            $q->execute([(int)($_POST['id'] ?? 0)]);
            admin_redirect('servicos');
        }

        if ($action === 'save_testimonial') {
            $id = (int)($_POST['id'] ?? 0);
            $name = trim((string)($_POST['client_name'] ?? ''));
            $message = trim((string)($_POST['message'] ?? ''));

            if ($name === '' || $message === '') {
                throw new RuntimeException('Preencha o nome do cliente e o testemunho.');
            }

            if ($id > 0) {
                $q = $pdo->prepare('UPDATE testimonials SET client_name=?,message=? WHERE id=?');
                $q->execute([$name,$message,$id]);
            } else {
                $q = $pdo->prepare('INSERT INTO testimonials(client_name,message) VALUES(?,?)');
                $q->execute([$name,$message]);
            }
            admin_redirect('testemunhos');
        }

        if ($action === 'delete_testimonial') {
            $q = $pdo->prepare('DELETE FROM testimonials WHERE id=?');
            $q->execute([(int)($_POST['id'] ?? 0)]);
            admin_redirect('testemunhos');
        }

        if ($action === 'update_request') {
            $status = (string)($_POST['status'] ?? 'Novo');

            if (!in_array($status, ['Novo','Em análise','Respondido','Concluído'], true)) {
                throw new RuntimeException('Estado do pedido inválido.');
            }
            $q = $pdo->prepare('UPDATE quote_requests SET status=? WHERE id=?');
            $q->execute([$status,(int)($_POST['id'] ?? 0)]);
            admin_redirect('pedidos');
        }

        if ($action === 'save_settings') {
            $allowed = ['company_name','company_tagline','company_description','hero_title','hero_description','about_title','about_text','city','phone','email','whatsapp','address','opening_hours','partners'];

            foreach ($allowed as $key) {
                $value = trim((string)($_POST[$key] ?? ''));

                if ($key === 'email' && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    throw new RuntimeException('O endereço de e-mail não é válido.');
                }

if ($key === 'whatsapp' && !preg_match('/^[0-9]{9,15}$/', $value)) {
                    throw new RuntimeException('O WhatsApp deve conter apenas 9 a 15 dígitos, incluindo o indicativo do país.');
                }$q = $pdo->prepare('INSERT INTO site_settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)');
                $q->execute([$key,$value]);
            }

            foreach (['hero_image','about_image'] as $field) {
                $oldQ = $pdo->prepare('SELECT setting_value FROM site_settings WHERE setting_key=?');
                $oldQ->execute([$field]);
                $old = $oldQ->fetchColumn();
                $new = admin_upload_image($field, is_string($old) ? $old : null);

                if ($new !== $old) {
                    $q = $pdo->prepare('INSERT INTO site_settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)');
                    $q->execute([$field,$new ?? '']);
                }
            }
            admin_redirect('configuracoes');
        }
    } catch (Throwable $e) {
        $error = $e instanceof PDOException ? 'Ocorreu um erro ao guardar os dados. Verifique as relações e os dados introduzidos.' : $e->getMessage();
    }
}

$stats = ['visits' => (int)$pdo->query('SELECT COUNT(*) FROM visits')->fetchColumn(),'projects' => (int)$pdo->query('SELECT COUNT(*) FROM projects')->fetchColumn(),'services' => (int)$pdo->query('SELECT COUNT(*) FROM services')->fetchColumn(),'requests' => (int)$pdo->query('SELECT COUNT(*) FROM quote_requests')->fetchColumn()];
$projects = $pdo->query('SELECT p.*, (SELECT COUNT(*) FROM project_images pi WHERE pi.project_id=p.id) AS gallery_count FROM projects p ORDER BY p.id DESC')->fetchAll();
$services = $pdo->query('SELECT * FROM services ORDER BY id DESC')->fetchAll();
$testimonials = $pdo->query('SELECT * FROM testimonials ORDER BY id DESC')->fetchAll();
$requests = $pdo->query('SELECT * FROM quote_requests ORDER BY id DESC')->fetchAll();
$stats['ongoingProjects'] = count(array_filter($projects, static fn (array $project): bool => $project['status'] === 'Em andamento'));
$stats['testimonials'] = count($testimonials);
$stats['pendingRequests'] = count(array_filter($requests, static fn (array $request): bool => !in_array($request['status'], ['Respondido','Concluído'], true)));
$settings = [];

foreach ($pdo->query('SELECT setting_key,setting_value FROM site_settings')->fetchAll() as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$defaults = ['company_name' => 'Moz Construções','company_tagline' => 'CONSTRUÇÃO & ENGENHARIA','company_description' => '','hero_title' => 'Construímos hoje o futuro que imagina.','hero_description' => '','about_title' => 'Mais do que construir, criamos confiança.','about_text' => '','city' => 'Maputo, Moçambique','phone' => '+258 84 000 0000','email' => 'contacto@horizonte.co.mz','whatsapp' => '258840000000','address' => 'Maputo, Moçambique','opening_hours' => 'Segunda – Sexta | 08:00 – 17:00','hero_image' => '','about_image' => '','partners' => default_site_partners()];
$settings = array_merge($defaults, $settings);

if (trim((string)$settings['partners']) === '') {
    $settings['partners'] = default_site_partners();
}$csrf = admin_csrf_token();
/**
 * Devolve um valor de tabela já protegido para exibição segura no HTML.
 */
function field_value(array $row, string $key): string
{
    return admin_escape($row[$key] ?? '');
}
/**
 * Escreve os campos ocultos comuns que identificam uma operação e protegem o formulário.
 */
function edit_form(string $action, int $id, string $token): void
{
    echo '<input type="hidden" name="csrf_token" value="' . admin_escape($token) . '"><input type="hidden" name="action" value="' . admin_escape($action) . '"><input type="hidden" name="id" value="' . $id . '">';
}
?>
<!doctype html>
<html lang="pt">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Painel administrativo | Moz Construções</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
    :root {
        --navy: #123b65;
        --blue: #2878c8;
        --line: #e5ebf0
    }

    body {
        background: #f3f6f9;
        color: #34495e
    }

    .adminbar {
        background: var(--navy);
        color: #fff;
        padding: 16px 0
    }

    .adminbar .container {
        display: flex;
        justify-content: space-between;
        align-items: center
    }

    .adminbar a {
        color: #d6eaff
    }

    .adminmain {
        padding: 30px 0 60px
    }

    .admin-menu {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 16px;
        margin: 24px 0
    }

    .admin-menu a {
        display: block;
        background: #fff;
        border: 1px solid var(--line);
        border-radius: 10px;
        padding: 22px;
        text-decoration: none;
        transition: .2s
    }

    .admin-menu a:hover {
        border-color: var(--blue);
        transform: translateY(-2px);
        box-shadow: 0 10px 24px #123b6512
    }

    .admin-menu b {
        display: block;
        color: var(--navy);
        font-size: 16px;
        margin-bottom: 7px
    }

    .admin-menu span {
        display: block;
        color: #718293;
        font-size: 13px;
        line-height: 1.5
    }

    .back-link {
        color: var(--blue);
        font-weight: 700
    }

    .adminmain h1 {
        color: var(--navy);
        margin-bottom: 4px
    }

    .stats {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 14px;
        margin: 22px 0
    }

    .stat,
    .panel {
        background: #fff;
        padding: 20px;
        border: 1px solid var(--line);
        border-radius: 5px
    }

    .stat small {
        display: block;
        color: #718293
    }

    .stat strong {
        font-size: 28px;
        color: var(--navy)
    }

    .panel {
        margin-bottom: 18px;
        scroll-margin-top: 20px
    }

    .panel h2 {
        font-size: 18px;
        color: var(--navy);
        margin-top: 0
    }

    .panel h3 {
        font-size: 14px;
        color: var(--navy)
    }

    .panel input,
    .panel textarea,
    .panel select {
        width: 100%;
        padding: 10px;
        border: 1px solid #dce4eb;
        margin: 5px 0 12px;
        border-radius: 3px;
        font: 12px 'DM Sans'
    }

    .panel label {
        font-size: 11px;
        color: #536779;
        display: block
    }

    .panel textarea {
        resize: vertical
    }

    .panel table {
        width: 100%;
        border-collapse: collapse;
        font-size: 11px
    }

    .panel td,
    .panel th {
        text-align: left;
        padding: 10px;
        border-bottom: 1px solid #e8edf2;
        vertical-align: top
    }

    .panel th {
        color: var(--navy)
    }

    .panel .btn {
        padding: 10px 14px;
        font-size: 11px
    }

    .danger-btn {
        background: #fff0f0;
        color: #a12c2c;
        border: 1px solid #f1cccc
    }

    .tabs {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin: 20px 0
    }

    .tabs a {
        padding: 9px 13px;
        background: #fff;
        border: 1px solid var(--line);
        border-radius: 4px;
        color: var(--navy);
        font-size: 12px;
        font-weight: 700
    }

    .tabs a:hover {
        background: #eaf3fb
    }

    .two-col {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px
    }

    .notice {
        padding: 12px;
        background: #eaf3fb;
        color: #174c7c;
        margin: 12px 0;
        font-size: 12px
    }

    .error {
        padding: 12px;
        background: #fff0f0;
        color: #9c2525;
        margin: 12px 0;
        font-size: 12px
    }

    .thumb {
        width: 85px;
        height: 58px;
        object-fit: cover;
        border-radius: 3px
    }

    .inline-form {
        display: inline
    }

    .muted {
        color: #8795a2;
        font-size: 11px
    }

    @media(max-width:750px) {
        .admin-menu {
            grid-template-columns: repeat(2, minmax(0, 1fr))
        }

        .stats {
            grid-template-columns: repeat(2, 1fr)
        }

        .two-col,
        .form-row {
            grid-template-columns: 1fr
        }

        .adminbar .container {
            align-items: flex-start;
            gap: 12px;
            flex-direction: column
        }

        .panel {
            overflow-x: auto
        }

        .panel table {
            min-width: 600px
        }
    }

    @media(max-width:500px) {
        .admin-menu {
            grid-template-columns: 1fr
        }
    }
    </style>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
    body {
        background: #f4f7fb;
        color: #26384b
    }

    .admin-layout {
        min-height: 100vh;
        display: grid;
        grid-template-columns: 258px minmax(0, 1fr)
    }

    .admin-sidebar {
        position: sticky;
        top: 0;
        height: 100vh;
        padding: 28px 18px;
        background: linear-gradient(180deg, #0d2b49, #123b65);
        color: #fff;
        display: flex;
        flex-direction: column;
        z-index: 40
    }

    .sidebar-brand {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 2px 8px 28px;
        border-bottom: 1px solid #ffffff20
    }

    .sidebar-brand-mark {
        width: 42px;
        height: 42px;
        display: grid;
        place-items: center;
        border-radius: 12px;
        background: #2878c8;
        font-size: 21px
    }

    .sidebar-brand b,
    .sidebar-brand small {
        display: block
    }

    .sidebar-brand b {
        font-size: 14px;
        letter-spacing: .3px
    }

    .sidebar-brand small {
        margin-top: 4px;
        color: #a9bfd2;
        font-size: 9px;
        letter-spacing: 1.2px
    }

    .sidebar-label {
        margin: 25px 10px 10px;
        color: #8da9c0;
        font-size: 9px;
        font-weight: 800;
        letter-spacing: 1.5px;
        text-transform: uppercase
    }

    .sidebar-nav {
        display: grid;
        gap: 5px
    }

    .sidebar-nav a {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 13px;
        border-radius: 8px;
        color: #c5d6e5;
        font-size: 12px;
        font-weight: 600;
        transition: background .2s, color .2s
    }

    .sidebar-nav a i {
        width: 18px;
        text-align: center;
        font-size: 16px
    }

    .sidebar-nav a:hover,
    .sidebar-nav a[aria-current=page] {
        background: #ffffff18;
        color: #fff
    }

    .sidebar-nav a[aria-current=page] {
        box-shadow: inset 3px 0 #7fc1f5
    }

    .sidebar-footer {
        margin-top: auto;
        padding: 17px 9px 0;
        border-top: 1px solid #ffffff20
    }

    .sidebar-footer span {
        display: block;
        color: #9eb6ca;
        font-size: 10px;
        margin-bottom: 12px
    }

    .sidebar-footer a {
        display: flex;
        gap: 10px;
        align-items: center;
        color: #e3edf6;
        font-size: 11px
    }

    .admin-main-area {
        min-width: 0
    }

    .adminbar {
        height: 74px;
        padding: 0 36px;
        background: #fff;
        border-bottom: 1px solid #e8edf3;
        display: flex;
        align-items: center;
        justify-content: space-between
    }

    .adminbar .container {
        width: auto;
        margin: 0;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: space-between
    }

    .adminbar b {
        font-size: 12px;
        color: #123b65
    }

    .adminbar span {
        font-size: 11px;
        color: #718293
    }

    .adminmain {
        width: min(1360px, 100%);
        margin: 0 auto;
        padding: 34px 36px 60px
    }

    .page-heading {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 20px;
        margin-bottom: 26px
    }

    .page-heading h1 {
        margin: 0;
        color: #153a5c;
        font-size: 27px;
        letter-spacing: -.7px
    }

    .page-heading p {
        margin: 7px 0 0;
        color: #8492a0;
        font-size: 12px
    }

    .page-heading .eyebrow-admin {
        color: #2878c8;
        font-size: 9px;
        font-weight: 800;
        letter-spacing: 1.5px;
        text-transform: uppercase
    }

    .stats {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 16px;
        margin: 0 0 28px
    }

    .stat-card {
        position: relative;
        overflow: hidden;
        min-height: 142px;
        padding: 20px 21px;
        background: #fff;
        border: 1px solid #e8edf3;
        border-radius: 12px;
        box-shadow: 0 8px 24px rgba(18, 59, 101, .04);
        transition: transform .2s, box-shadow .2s
    }

    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 14px 28px rgba(18, 59, 101, .09)
    }

    .stat-card-top {
        display: flex;
        align-items: center;
        justify-content: space-between
    }

    .stat-card small {
        color: #758596;
        font-size: 11px;
        font-weight: 600
    }

    .stat-icon {
        width: 38px;
        height: 38px;
        display: grid;
        place-items: center;
        border-radius: 11px;
        background: #eaf3fb;
        color: #2878c8;
        font-size: 17px
    }

    .stat-card strong {
        display: block;
        margin-top: 12px;
        color: #123b65;
        font-size: 29px;
        line-height: 1;
        font-weight: 800
    }

    .stat-card p {
        margin: 8px 0 0;
        color: #91a0ad;
        font-size: 10px
    }

    .dashboard-columns {
        display: grid;
        grid-template-columns: minmax(0, 1.35fr) minmax(250px, .65fr);
        gap: 18px;
        align-items: start
    }

    .dashboard-panel {
        background: #fff;
        border: 1px solid #e8edf3;
        border-radius: 12px;
        box-shadow: 0 8px 24px rgba(18, 59, 101, .04);
        overflow: hidden
    }

    .dashboard-panel-heading {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 19px 21px;
        border-bottom: 1px solid #edf1f5
    }

    .dashboard-panel-heading h2 {
        margin: 0;
        color: #153a5c;
        font-size: 14px
    }

    .dashboard-panel-heading p {
        margin: 5px 0 0;
        color: #91a0ad;
        font-size: 10px
    }

    .dashboard-panel-heading>a {
        color: #2878c8;
        font-size: 10px;
        font-weight: 700
    }

    .recent-requests {
        padding: 0 21px
    }

    .recent-request {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 12px;
        align-items: center;
        padding: 15px 0;
        border-bottom: 1px solid #f0f3f6
    }

    .recent-request:last-child {
        border-bottom: 0
    }

    .recent-request b,
    .recent-request small {
        display: block
    }

    .recent-request b {
        color: #334d63;
        font-size: 11px
    }

    .recent-request small {
        margin-top: 5px;
        color: #91a0ad;
        font-size: 9px
    }

    .request-status {
        padding: 6px 9px;
        border-radius: 20px;
        background: #fff5e7;
        color: #9b6815;
        font-size: 9px;
        font-weight: 700;
        white-space: nowrap
    }

    .request-status.status-done {
        background: #e8f7ef;
        color: #217348
    }

    .dashboard-empty {
        padding: 25px;
        color: #91a0ad;
        text-align: center;
        font-size: 11px
    }

    .admin-main-area .admin-menu {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        margin: 0
    }

    .admin-main-area .admin-menu a {
        border-radius: 11px
    }

    .mobile-menu-toggle {
        display: none;
        border: 0;
        background: #eaf3fb;
        color: #123b65;
        width: 38px;
        height: 38px;
        border-radius: 9px;
        font-size: 18px
    }

    .sidebar-backdrop {
        display: none
    }

    @media(max-width:1050px) {
        .admin-layout {
            grid-template-columns: 225px minmax(0, 1fr)
        }

        .adminmain {
            padding: 28px 24px 48px
        }

        .adminbar {
            padding: 0 24px
        }

        .dashboard-columns {
            grid-template-columns: 1fr
        }

        .stats {
            grid-template-columns: repeat(2, minmax(0, 1fr))
        }
    }

    @media(max-width:760px) {
        .admin-layout {
            display: block
        }

        .admin-sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 270px;
            transform: translateX(-105%);
            transition: transform .25s ease;
            box-shadow: 15px 0 40px #0b29444d
        }

        .sidebar-open .admin-sidebar {
            transform: translateX(0)
        }

        .sidebar-open .sidebar-backdrop {
            position: fixed;
            inset: 0;
            z-index: 35;
            display: block;
            border: 0;
            background: #071e3477
        }

        .adminbar {
            height: 64px;
            padding: 0 18px;
            gap: 14px
        }

        .mobile-menu-toggle {
            display: grid;
            place-items: center;
            flex: 0 0 auto
        }

        .adminbar .container {
            flex: 1;
            min-width: 0
        }

        .adminbar .container>b {
            display: none
        }

        .adminbar span {
            font-size: 10px
        }

        .adminmain {
            padding: 25px 18px 40px
        }

        .page-heading h1 {
            font-size: 23px
        }

        .stats {
            gap: 11px
        }

        .stat-card {
            min-height: 130px;
            padding: 16px
        }

        .stat-card strong {
            font-size: 25px
        }

        .two-col,
        .form-row {
            grid-template-columns: 1fr
        }

        .panel {
            overflow-x: auto
        }

        .panel table {
            min-width: 600px
        }
    }

    @media(max-width:480px) {
        .adminbar {
            padding: 0 12px
        }

        .adminmain {
            padding: 21px 13px 32px
        }

        .stats {
            grid-template-columns: 1fr 1fr;
            gap: 9px
        }

        .stat-card {
            min-height: 118px;
            padding: 13px
        }

        .stat-card small {
            font-size: 10px
        }

        .stat-icon {
            width: 32px;
            height: 32px;
            font-size: 15px
        }

        .stat-card strong {
            font-size: 23px
        }

        .stat-card p {
            font-size: 9px
        }

        .page-heading h1 {
            font-size: 21px
        }

        .dashboard-panel-heading {
            padding: 16px
        }

        .recent-requests {
            padding: 0 16px
        }

        .request-status {
            font-size: 8px;
            padding: 5px 7px
        }
    }
    </style>
</head>

<body>
    <div class="admin-layout">
        <?php $adminPage = $page;
include __DIR__ . '/sidebar.php'; ?>
        <div class="admin-main-area">
            <div class="adminbar"><button type="button" class="mobile-menu-toggle" id="adminMenuToggle"
                    aria-label="Abrir menu" aria-expanded="false"><i class="bi bi-list"></i></button>
                <div class="container"><b><?= admin_escape($settings['company_name']) ?> · PAINEL
                        ADMINISTRATIVO</b><span>Olá,
                        <?= admin_escape($_SESSION['admin_name'] ?? 'Administrador') ?></span></div>
            </div>
            <main class="adminmain">
                <div class="page-heading">
                    <div><span class="eyebrow-admin">Visão geral</span>
                        <h1><?= admin_escape($pageTitles[$page]) ?></h1>
                        <p>Gestão do website e dos conteúdos públicos.</p>
                    </div><?php if ($page !== 'inicio'): ?><a class="back-link" href="dashboard.php">← Painel
                        principal</a><?php endif; ?>
                </div>
                <?php if ($error): ?><div class="error"><?= admin_escape($error) ?></div><?php endif; ?>
                <?php if (isset($_GET['ok'])): ?><div class="notice">Operação concluída com sucesso.</div><?php endif; ?>
                <?php if ($page === 'inicio'): ?>
                <?php
$dashboardStats = [
    ['Visitas registadas',$stats['visits'],'bi-eye','Total de visitas contabilizadas'],
    ['Projectos',$stats['projects'],'bi-buildings','Projectos publicados'],
    ['Em andamento',$stats['ongoingProjects'],'bi-cone-striped','Obras actualmente em execução'],
    ['Serviços',$stats['services'],'bi-tools','Serviços apresentados no site'],
    ['Pedidos de orçamento',$stats['requests'],'bi-inbox',$stats['pendingRequests'] . ' por responder'],
    ['Testemunhos',$stats['testimonials'],'bi-chat-quote','Avaliações de clientes'],
];
                    ?>
                <div class="stats"><?php foreach ($dashboardStats as [$label,$value,$icon,$detail]): ?><article
                        class="stat-card">
                        <div class="stat-card-top"><small><?= admin_escape($label) ?></small><span class="stat-icon"><i
                                    class="bi <?= admin_escape($icon) ?>"></i></span></div>
                        <strong><?= number_format((int)$value, 0, ',', '.') ?></strong>
                        <p><?= admin_escape($detail) ?></p>
                    </article><?php endforeach; ?></div>
                <div class="dashboard-columns">
                    <section class="dashboard-panel">
                        <div class="dashboard-panel-heading">
                            <div>
                                <h2>Pedidos recentes</h2>
                                <p>Últimos pedidos recebidos pelo formulário do site</p>
                            </div><a href="?page=pedidos">Ver todos <i class="bi bi-arrow-up-right"></i></a>
                        </div>
                        <?php if ($requests): ?><div class="recent-requests">
                            <?php foreach (array_slice($requests, 0, 5) as $request): ?><div class="recent-request">
                                <div><b><?= admin_escape($request['name']) ?></b><small><?= admin_escape($request['service'] ?: 'Serviço não indicado') ?>
                                        · <?= admin_escape(date('d/m/Y', strtotime($request['created_at']))) ?></small>
                                </div><span
                                    class="request-status <?= in_array($request['status'], ['Respondido','Concluído'], true) ? 'status-done' : '' ?>"><?= admin_escape($request['status']) ?></span>
                            </div><?php endforeach; ?></div><?php else: ?><div class="dashboard-empty">Ainda não foram
                            recebidos pedidos de orçamento.</div><?php endif; ?>
                    </section>
                    <section class="dashboard-panel">
                        <div class="dashboard-panel-heading">
                            <div>
                                <h2>Acesso rápido</h2>
                                <p>Atalhos para gerir o conteúdo</p>
                            </div>
                        </div>
                        <div class="recent-requests">
                            <a class="recent-request" href="?page=projectos">
                                <div><b>Adicionar projecto</b><small>Actualizar o portfólio da empresa</small></div><i
                                    class="bi bi-arrow-up-right"></i>
                            </a>
                            <a class="recent-request" href="?page=servicos">
                                <div><b>Gerir serviços</b><small>Editar os serviços apresentados no site</small></div><i
                                    class="bi bi-arrow-up-right"></i>
                            </a>
                            <a class="recent-request" href="?page=configuracoes">
                                <div><b>Editar conteúdos</b><small>Actualizar textos e contactos</small></div><i
                                    class="bi bi-arrow-up-right"></i>
                            </a>
                        </div>
                    </section>
                </div>
                <?php endif; ?>
                <?php if ($page === 'configuracoes'): ?><section class="panel" id="configuracoes">
                    <h2>Conteúdos institucionais e contactos</h2>
                    <form method="post" enctype="multipart/form-data"><?php edit_form('save_settings', 0, $csrf); ?><div
                            class="two-col">
                            <div><label>Nome da empresa<input name="company_name" required maxlength="120"
                                        value="<?= field_value($settings, 'company_name') ?>"></label><label>Assinatura /
                                    actividade<input name="company_tagline" maxlength="160"
                                        value="<?= field_value($settings, 'company_tagline') ?>"></label><label>Descrição
                                    institucional<textarea name="company_description"
                                        rows="3"><?= field_value($settings, 'company_description') ?></textarea></label><label>Título
                                    do banner<input name="hero_title" maxlength="220"
                                        value="<?= field_value($settings, 'hero_title') ?>"></label><label>Texto do
                                    banner<textarea name="hero_description"
                                        rows="3"><?= field_value($settings, 'hero_description') ?></textarea></label><label>Título
                                    da secção Sobre<input name="about_title" maxlength="220"
                                        value="<?= field_value($settings, 'about_title') ?>"></label><label>Texto da
                                    secção Sobre<textarea name="about_text"
                                        rows="3"><?= field_value($settings, 'about_text') ?></textarea></label></div>
                            <div><label>Cidade / localização<input name="city"
                                        value="<?= field_value($settings, 'city') ?>"></label><label>Morada<input
                                        name="address"
                                        value="<?= field_value($settings, 'address') ?>"></label><label>Telefone<input
                                        name="phone"
                                        value="<?= field_value($settings, 'phone') ?>"></label><label>E-mail<input
                                        type="email" name="email"
                                        value="<?= field_value($settings, 'email') ?>"></label><label>WhatsApp (apenas
                                    dígitos com indicativo)<input name="whatsapp" pattern="[0-9]{9,15}"
                                        value="<?= field_value($settings, 'whatsapp') ?>"></label><label>Horário de
                                    atendimento<input name="opening_hours"
                                        value="<?= field_value($settings, 'opening_hours') ?>"></label><label>Parceiros
                                    (um por linha: Nome | URL do logo | URL do site)<textarea name="partners" rows="5"
                                        placeholder="Empresa A | https://.../logo.png | https://empresa-a.com"><?= field_value($settings, 'partners') ?></textarea></label><label>Imagem
                                    do banner (JPG, PNG ou WEBP, máx. 5 MB)<input type="file" name="hero_image"
                                        accept="image/jpeg,image/png,image/webp"></label><?php if ($settings['hero_image']): ?><img
                                    class="thumb" src="../<?= admin_escape($settings['hero_image']) ?>"
                                    alt="Banner actual"><?php endif; ?><label>Imagem da secção Sobre<input type="file"
                                        name="about_image"
                                        accept="image/jpeg,image/png,image/webp"></label><?php if ($settings['about_image']): ?><img
                                    class="thumb" src="../<?= admin_escape($settings['about_image']) ?>"
                                    alt="Imagem actual"><?php endif; ?></div>
                        </div><button class="btn btn-primary">Guardar conteúdos e contactos</button></form>
                </section><?php endif; ?>
                <?php if ($page === 'conta'): ?><section class="panel" id="conta">
                    <h2>Dados de acesso</h2>
                    <p>Altere o e-mail e, se desejar, defina uma nova palavra-passe. Para proteger a sua conta, confirme sempre a palavra-passe actual.</p>
                    <form method="post" autocomplete="off">
                        <?php edit_form('update_admin_account', 0, $csrf); ?>
                        <?php
                        $accountQuery = $pdo->prepare('SELECT email FROM admins WHERE id = ? LIMIT 1');
                        $accountQuery->execute([(int)$_SESSION['admin_id']]);
                        $accountEmail = (string)$accountQuery->fetchColumn();
                        ?>
                        <div class="two-col">
                            <div>
                                <label>E-mail da conta
                                    <input type="email" name="email" required maxlength="190" autocomplete="username"
                                        value="<?= admin_escape($accountEmail) ?>">
                                </label>
                                <label>Palavra-passe actual
                                    <input type="password" name="current_password" required autocomplete="current-password">
                                </label>
                            </div>
                            <div>
                                <label>Nova palavra-passe <small>(opcional, mínimo 12 caracteres)</small>
                                    <input type="password" name="new_password" minlength="12" autocomplete="new-password">
                                </label>
                                <label>Confirmar nova palavra-passe
                                    <input type="password" name="confirm_password" minlength="12" autocomplete="new-password">
                                </label>
                            </div>
                        </div>
                        <button class="btn btn-primary" type="submit">Guardar dados de acesso</button>
                    </form>
                </section><?php endif; ?>
                <?php if ($page === 'projectos'): ?><section class="panel" id="projectos">
                    <h2>Projectos publicados</h2>
                    <div class="two-col">
                        <div>
                            <h3>Adicionar projecto</h3>
                            <form method="post" enctype="multipart/form-data">
                                <?php edit_form('save_project', 0, $csrf); ?><label>Nome do projecto<input name="title"
                                        required></label><label>Localização<input name="location"
                                        required></label><label>Categoria<input name="category"
                                        required></label><label>Descrição<textarea name="description"
                                        rows="3"></textarea></label><label>Imagem (JPG, PNG ou WEBP, máx. 5 MB)<input
                                        type="file" name="image_file"
                                        accept="image/jpeg,image/png,image/webp"></label><label>Ou URL externa da
                                    imagem<input name="image_url" type="url"
                                        placeholder="https://..."></label><label>Imagens adicionais para a galeria (até
                                    10 por envio)<input type="file" name="gallery_files[]"
                                        accept="image/jpeg,image/png,image/webp" multiple></label><small
                                    class="muted">Pode seleccionar várias fotografias da obra. Cada imagem até 5
                                    MB.</small><label>Estado<select name="status">
                                        <option>Concluído</option>
                                        <option>Em andamento</option>
                                        <option>Planeado</option>
                                    </select></label><button class="btn btn-primary">Adicionar projecto</button></form>
                        </div>
                        <div>
                            <h3>Lista de projectos</h3>
                            <table>
                                <tr>
                                    <th>Imagem</th>
                                    <th>Projecto</th>
                                    <th>Estado / acções</th>
                                </tr><?php foreach ($projects as $p): ?><tr>
                                    <td><?php if ($p['image_url']): ?><img class="thumb"
                                            src="<?= admin_escape(str_starts_with($p['image_url'], 'uploads/') ? '../' . $p['image_url'] : $p['image_url']) ?>"
                                            alt=""><?php endif; ?></td>
                                    <td><b><?= admin_escape($p['title']) ?></b><br><?= admin_escape($p['location']) ?>
                                    </td>
                                    <td><?= admin_escape($p['status']) ?><br><span
                                            class="muted"><?= (int)$p['gallery_count'] ?> imagem(ns) na
                                            galeria</span><br>
                                        <details>
                                            <summary>Editar</summary>
                                            <form method="post" enctype="multipart/form-data">
                                                <?php edit_form('save_project', (int)$p['id'], $csrf); ?><label>Nome<input
                                                        name="title" required
                                                        value="<?= admin_escape($p['title']) ?>"></label><label>Localização<input
                                                        name="location" required
                                                        value="<?= admin_escape($p['location']) ?>"></label><label>Categoria<input
                                                        name="category" required
                                                        value="<?= admin_escape($p['category']) ?>"></label><label>Descrição<textarea
                                                        name="description"><?= admin_escape($p['description']) ?></textarea></label><label>Substituir
                                                    imagem<input type="file" name="image_file"
                                                        accept="image/jpeg,image/png,image/webp"></label><label>URL
                                                    externa<input name="image_url" type="url"
                                                        value="<?= admin_escape(str_starts_with((string)$p['image_url'], 'uploads/') ? '' : $p['image_url']) ?>"></label><label>Adicionar
                                                    fotografias à galeria<input type="file" name="gallery_files[]"
                                                        accept="image/jpeg,image/png,image/webp" multiple></label><small
                                                    class="muted">As novas fotografias serão acrescentadas às existentes
                                                    (máximo 10 por envio).</small><label>Estado<select
                                                        name="status"><?php foreach (['Concluído','Em andamento','Planeado'] as $st): ?>
                                                        <option <?= $p['status'] === $st ? 'selected' : '' ?>><?= $st ?>
                                                        </option><?php endforeach; ?></select></label><button
                                                    class="btn btn-primary">Guardar edição</button></form>
                                        </details>
                                        <form method="post" class="inline-form"
                                            onsubmit="return confirm('Tem a certeza de que pretende eliminar este projecto?')">
                                            <?php edit_form('delete_project', (int)$p['id'], $csrf); ?><button
                                                class="btn danger-btn">Eliminar</button></form>
                                    </td>
                                </tr><?php endforeach; ?>
                            </table>
                        </div>
                    </div>
                </section><?php endif; ?>
                <?php if ($page === 'servicos'): ?><section class="panel" id="servicos">
                    <h2>Serviços</h2>
                    <div class="two-col">
                        <div>
                            <h3>Adicionar serviço</h3>
                            <form method="post"><?php edit_form('save_service', 0, $csrf); ?><label>Nome<input
                                        name="title" required></label><label>Descrição<textarea name="description"
                                        rows="3" required></textarea></label><label>Ícone Bootstrap (ex.:
                                    bi-building)<input name="icon" value="bi-building"></label><button
                                    class="btn btn-primary">Adicionar serviço</button></form>
                        </div>
                        <div>
                            <h3>Serviços existentes</h3>
                            <table>
                                <tr>
                                    <th>Serviço</th>
                                    <th>Acções</th>
                                </tr><?php foreach ($services as $s): ?><tr>
                                    <td><b><?= admin_escape($s['title']) ?></b><br><?= admin_escape($s['description']) ?>
                                    </td>
                                    <td>
                                        <details>
                                            <summary>Editar</summary>
                                            <form method="post">
                                                <?php edit_form('save_service', (int)$s['id'], $csrf); ?><label>Nome<input
                                                        name="title" required
                                                        value="<?= admin_escape($s['title']) ?>"></label><label>Descrição<textarea
                                                        name="description"
                                                        required><?= admin_escape($s['description']) ?></textarea></label><label>Ícone<input
                                                        name="icon"
                                                        value="<?= admin_escape($s['icon']) ?>"></label><button
                                                    class="btn btn-primary">Guardar</button></form>
                                        </details>
                                        <form method="post" class="inline-form"
                                            onsubmit="return confirm('Eliminar este serviço?')">
                                            <?php edit_form('delete_service', (int)$s['id'], $csrf); ?><button
                                                class="btn danger-btn">Eliminar</button></form>
                                    </td>
                                </tr><?php endforeach; ?>
                            </table>
                        </div>
                    </div>
                </section><?php endif; ?>
                <?php if ($page === 'testemunhos'): ?><section class="panel" id="testemunhos">
                    <h2>Testemunhos de clientes</h2>
                    <div class="two-col">
                        <div>
                            <h3>Adicionar testemunho</h3>
                            <form method="post"><?php edit_form('save_testimonial', 0, $csrf); ?><label>Nome do
                                    cliente<input name="client_name" required></label><label>Testemunho<textarea
                                        name="message" rows="4" required></textarea></label><button
                                    class="btn btn-primary">Adicionar testemunho</button></form>
                        </div>
                        <div>
                            <h3>Testemunhos existentes</h3>
                            <table>
                                <tr>
                                    <th>Cliente / testemunho</th>
                                    <th>Acções</th>
                                </tr><?php foreach ($testimonials as $t): ?><tr>
                                    <td><b><?= admin_escape($t['client_name']) ?></b><br><?= admin_escape($t['message']) ?>
                                    </td>
                                    <td>
                                        <details>
                                            <summary>Editar</summary>
                                            <form method="post">
                                                <?php edit_form('save_testimonial', (int)$t['id'], $csrf); ?><label>Nome<input
                                                        name="client_name" required
                                                        value="<?= admin_escape($t['client_name']) ?>"></label><label>Testemunho<textarea
                                                        name="message"
                                                        required><?= admin_escape($t['message']) ?></textarea></label><button
                                                    class="btn btn-primary">Guardar</button></form>
                                        </details>
                                        <form method="post" class="inline-form"
                                            onsubmit="return confirm('Eliminar este testemunho?')">
                                            <?php edit_form('delete_testimonial', (int)$t['id'], $csrf); ?><button
                                                class="btn danger-btn">Eliminar</button></form>
                                    </td>
                                </tr><?php endforeach; ?>
                            </table>
                        </div>
                    </div>
                </section><?php endif; ?>
                <?php if ($page === 'pedidos'): ?><section class="panel" id="pedidos">
                    <h2>Pedidos de orçamento</h2>
                    <table>
                        <tr>
                            <th>Data</th>
                            <th>Cliente</th>
                            <th>Contacto</th>
                            <th>Serviço / detalhe</th>
                            <th>Estado</th>
                        </tr><?php foreach ($requests as $r): ?><tr>
                            <td><?= admin_escape($r['created_at']) ?></td>
                            <td><?= admin_escape($r['name']) ?><br><?= admin_escape($r['email']) ?></td>
                            <td><?= admin_escape($r['phone']) ?></td>
                            <td><?= admin_escape($r['service']) ?><br><?= admin_escape($r['details']) ?></td>
                            <td>
                                <form method="post"><?php edit_form('update_request', (int)$r['id'], $csrf); ?><select
                                        name="status"><?php foreach (['Novo','Em análise','Respondido','Concluído'] as $st): ?>
                                        <option <?= $r['status'] === $st ? 'selected' : '' ?>><?= $st ?></option>
                                        <?php endforeach; ?></select><button class="btn btn-primary">Actualizar</button>
                                </form>
                            </td>
                        </tr><?php endforeach; ?>
                    </table>
                </section><?php endif; ?>
            </main>
        </div>
    </div>
    <script src="../assets/js/admin.js" defer></script>
</body>

</html>