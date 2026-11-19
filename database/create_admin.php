<?php
// Execute uma única vez no navegador/local: http://localhost/obraforte_site/database/create_admin.php
// Depois de criar o administrador, elimine este ficheiro do servidor.
require_once __DIR__ . '/../app/db.php';
$email = 'admin@horizonte.co.mz';
$password = 'MudeEstaSenha123!';
$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = db()->prepare("INSERT INTO admins(name,email,password) VALUES(?,?,?) ON DUPLICATE KEY UPDATE password=VALUES(password)");
$stmt->execute(['Administrador', $email, $hash]);
echo "Administrador criado/actualizado.<br>Email: ".htmlspecialchars($email)."<br>Senha inicial: ".htmlspecialchars($password)."<br>Altere a senha após o primeiro acesso e elimine este ficheiro.";
