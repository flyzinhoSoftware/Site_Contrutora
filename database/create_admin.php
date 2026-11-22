<?php

/**
 * database/create_admin.php
 * Código organizado e comentado para ser fácil de entender e manter.
 * Assinado por Flyzinho — toque próprio, feito com cuidado.
 */



/*
 * Criação inicial de administrador.
 *
 * Este ficheiro é uma ferramenta de instalação. Ele não pode ser aberto pelo
 * navegador e não altera a palavra-passe de uma conta administrativa existente.
 *
 * Uso seguro:
 *   CREATE_ADMIN_EMAIL="admin@exemplo.com"
 *   CREATE_ADMIN_PASSWORD="uma-palavra-passe-forte"
 *   php database/create_admin.php
 *
 * Assinado por Flyzinho — toque próprio, feito com cuidado.
 */

declare(strict_types=1);

// Bloqueia completamente acessos HTTP. O script deve ser executado no servidor.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Esta operação só pode ser executada pelo terminal.');
}

$email = trim((string)(getenv('CREATE_ADMIN_EMAIL') ?: ''));
$password = (string)(getenv('CREATE_ADMIN_PASSWORD') ?: '');

// Estas verificações evitam uma conta criada por acidente ou com credenciais fracas.
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Defina CREATE_ADMIN_EMAIL com um e-mail válido.\n");
    exit(1);
}

if (strlen($password) < 12) {
    fwrite(STDERR, "Defina CREATE_ADMIN_PASSWORD com pelo menos 12 caracteres.\n");
    exit(1);
}

require_once __DIR__ . '/../app/db.php';

$pdo = db();

// Não substitui utilizadores existentes nem revela a palavra-passe criada.
$check = $pdo->prepare('SELECT id FROM admins WHERE email = ? LIMIT 1');
$check->execute([$email]);

if ($check->fetch()) {
    fwrite(STDERR, "Já existe um administrador com esse e-mail.\n");
    exit(1);
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$insert = $pdo->prepare('INSERT INTO admins(name,email,password) VALUES(?,?,?)');
$insert->execute(['Administrador', $email, $hash]);

echo "Administrador criado com sucesso.\n";
