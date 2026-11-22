<?php

/**
 * public/auth/logout.php
 * Código organizado e comentado para ser fácil de entender e manter.
 * Assinado por Flyzinho — toque próprio, feito com cuidado.
 */



require_once __DIR__ . '/../../app/session.php';
unset($_SESSION['comment_user'],$_SESSION['comment_csrf']);
header('Location: ../blog/index.php');
exit;
