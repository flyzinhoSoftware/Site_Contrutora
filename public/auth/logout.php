<?php
require_once __DIR__ . '/../../app/session.php';unset($_SESSION['comment_user'],$_SESSION['comment_csrf']);header('Location: ../blog/index.php');exit;
