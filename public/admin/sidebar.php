<?php

/**
 * public/admin/sidebar.php
 * Código organizado e comentado para ser fácil de entender e manter.
 * Assinado por Flyzinho — toque próprio, feito com cuidado.
 */


$adminPage = $adminPage ?? 'inicio';
$adminBlogPage = in_array($adminPage, ['blog', 'blog_create'], true);
$adminCompanyName = isset($settings) && is_array($settings) ? (string)($settings['company_name'] ?? 'Moz Construções') : 'Moz Construções';
?>
<aside class="admin-sidebar" aria-label="Navegação administrativa">
    <a class="sidebar-brand" href="dashboard.php"><span class="sidebar-brand-mark"><i
                class="bi bi-buildings"></i></span><span><b><?= admin_escape($adminCompanyName) ?></b><small>PAINEL
                ADMINISTRATIVO</small></span></a>
    <span class="sidebar-label">Menu principal</span>
    <nav class="sidebar-nav">
        <?php foreach ([
      ['inicio','Painel principal','bi-grid-1x2'],
      ['projectos','Projectos','bi-buildings'],
      ['servicos','Serviços','bi-tools'],
      ['testemunhos','Testemunhos','bi-chat-quote'],
      ['pedidos','Pedidos de orçamento','bi-inbox'],
      ['configuracoes','Conteúdos e contactos','bi-gear'],
    ] as [$navPage,$navLabel,$navIcon]): ?>
        <a href="<?= $navPage === 'inicio' ? 'dashboard.php' : 'dashboard.php?page=' . $navPage ?>"
            <?= $adminPage === $navPage ? 'aria-current="page"' : '' ?>><i
                class="bi <?= admin_escape($navIcon) ?>"></i><?= admin_escape($navLabel) ?></a>
        <?php endforeach; ?>
    </nav>
    <span class="sidebar-label">Publicações</span>
    <nav class="sidebar-nav">
        <a href="blog.php" <?= $adminBlogPage && $adminPage === 'blog' ? 'aria-current="page"' : '' ?>><i
                class="bi bi-journal-richtext"></i>Gestão do Blog</a>
        <a href="blog.php?tab=novo" <?= $adminPage === 'blog_create' ? 'aria-current="page"' : '' ?>><i
                class="bi bi-plus-square"></i>Criar artigo</a>
    </nav>
    <div class="sidebar-footer"><span>Olá, <?= admin_escape($_SESSION['admin_name'] ?? 'Administrador') ?></span><a
            href="dashboard.php?logout=1"><i class="bi bi-box-arrow-left"></i>Terminar sessão</a></div>
</aside>
<button type="button" class="sidebar-backdrop" aria-label="Fechar menu"></button>