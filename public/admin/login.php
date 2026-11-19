<?php
declare(strict_types=1);
require_once __DIR__ . '/../../app/admin.php';
if (!empty($_SESSION['admin_id'])) { header('Location: dashboard.php'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_verify_csrf();
    $email = filter_var(trim((string)($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
    $password = (string)($_POST['password'] ?? '');
    if ($email) {
        $stmt = db()->prepare('SELECT id, name, password FROM admins WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $admin = $stmt->fetch();
        if ($admin && password_verify($password, $admin['password'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int)$admin['id'];
            $_SESSION['admin_name'] = $admin['name'];
            $_SESSION['last_activity'] = time();
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            header('Location: dashboard.php'); exit;
        }
    }
    $error = 'E-mail ou palavra-passe incorrectos.';
}
$csrf = admin_csrf_token();
?>
<!doctype html><html lang="pt"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Administração | Moz Construções</title><link rel="stylesheet" href="../assets/css/style.css"><style>
body{background:#eef3f8;min-height:100vh;display:grid;place-items:center}.login{background:#fff;padding:38px;width:min(420px,calc(100% - 30px));box-shadow:0 15px 50px #123b6515}.login h1{color:#123b65}.login input{width:100%;padding:13px;margin:8px 0 16px;border:1px solid #dce4eb}.login label{font-size:12px;color:#536779}.login button{width:100%}.err{color:#a22;font-size:12px}.notice{color:#8b5a00;font-size:12px}
</style></head><body><form class="login" method="post"><a class="brand" href="../index.php"><span class="brand-mark">M</span><span>MOZ CONSTRUÇÕES<small>ADMINISTRAÇÃO</small></span></a><h1>Entrar no painel</h1><p>Introduza as suas credenciais.</p><?php if(isset($_GET['expired'])): ?><p class="notice">A sessão expirou por inactividade. Entre novamente.</p><?php endif; ?><?php if($error): ?><p class="err"><?= admin_escape($error) ?></p><?php endif; ?><input type="hidden" name="csrf_token" value="<?= admin_escape($csrf) ?>"><label>E-mail<input type="email" name="email" required autocomplete="username"></label><label>Palavra-passe<input type="password" name="password" required autocomplete="current-password"></label><button class="btn btn-primary" type="submit">Iniciar sessão</button></form></body></html>
