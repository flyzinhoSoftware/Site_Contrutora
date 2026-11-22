<?php

/**
 * public/index.php
 * Código organizado e comentado para ser fácil de entender e manter.
 * Assinado por Flyzinho — toque próprio, feito com cuidado.
 */


require_once __DIR__ . '/../app/session.php';
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/partners.php';
$pdo = db();
$settings = [];

// Estas preferências podem ser alteradas pelo painel administrativo.

try {
    foreach ($pdo->query('SELECT setting_key, setting_value FROM site_settings')->fetchAll() as $setting) {
        $settings[$setting['setting_key']] = $setting['setting_value'];
    }
} catch (Throwable $ignored) {
}
$setting = static function (string $key, string $default = '') use (&$settings): string {
    return (string)($settings[$key] ?? $default);
};

// A contagem de visitas só acontece após o consentimento do visitante.
$analyticsAllowed = ($_COOKIE['moz_analytics'] ?? '') === '1';

if ($analyticsAllowed) {
    $visitorId = (string)($_COOKIE['moz_visitor_id'] ?? '');

    if (!preg_match('/^[a-f0-9]{32}$/', $visitorId)) {
        $visitorId = bin2hex(random_bytes(16));
        $secureCookie = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        setcookie('moz_visitor_id', $visitorId, ['expires' => time() + 15552000,'path' => '/','secure' => $secureCookie,'httponly' => true,'samesite' => 'Lax']);
    }
    $visitorKey = hash('sha256', $visitorId);
    $today = date('Y-m-d');
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $check = $pdo->prepare('SELECT id FROM visits WHERE visitor_key=? AND visit_date=? LIMIT 1');
    $check->execute([$visitorKey,$today]);

    if (!$check->fetch()) {
        $ins = $pdo->prepare('INSERT INTO visits(visitor_key,visit_date,page_path) VALUES(?,?,?)');
        $ins->execute([$visitorKey,$today,$path]);
    }
}

// Os dados abaixo alimentam as secções públicas da página inicial.
$services = $pdo->query('SELECT * FROM services ORDER BY id DESC LIMIT 8')->fetchAll();
$projects = $pdo->query('SELECT * FROM projects ORDER BY id DESC LIMIT 6')->fetchAll();
$ongoingProjects = [];
$galleryByProject = [];
$illustrativeGalleryImages = [
    ['image_path' => 'assets/imagem/galeria/obra-ilustrativa-01.jpg','caption' => 'Imagem ilustrativa · Acompanhamento da estrutura'],
    ['image_path' => 'assets/imagem/galeria/obra-ilustrativa-02.jpg','caption' => 'Imagem ilustrativa · Trabalho no local da obra'],
    ['image_path' => 'assets/imagem/galeria/obra-ilustrativa-03.jpg','caption' => 'Imagem ilustrativa · Arquitectura contemporânea'],
    ['image_path' => 'assets/imagem/galeria/obra-ilustrativa-04.jpg','caption' => 'Imagem ilustrativa · Edifícios comerciais'],
];

try {
    $ongoingProjects = $pdo->query("SELECT * FROM projects WHERE status='Em andamento' ORDER BY id DESC")->fetchAll();
    $galleryRows = $pdo->query('SELECT pi.project_id, pi.image_path, pi.caption FROM project_images pi INNER JOIN projects p ON p.id=pi.project_id WHERE p.status=\'Em andamento\' ORDER BY pi.project_id DESC, pi.sort_order, pi.id')->fetchAll();

    foreach ($galleryRows as $galleryRow) {
        $galleryByProject[(int)$galleryRow['project_id']][] = $galleryRow;
    }
} catch (Throwable $ignored) { /* A migração da galeria pode ainda não ter sido aplicada. */
}
$testimonials = $pdo->query('SELECT * FROM testimonials ORDER BY id DESC LIMIT 6')->fetchAll();
$blogPosts = [];

try {
    $blogPosts = $pdo->query("SELECT title,slug,excerpt,cover_image,published_at FROM blog_posts WHERE status='published' AND published_at<=NOW() ORDER BY published_at DESC LIMIT 3")->fetchAll();
} catch (Throwable $ignored) {
}
$visitors = (int)$pdo->query('SELECT COUNT(DISTINCT visitor_key,visit_date) FROM visits')->fetchColumn();

$sent = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_type'] ?? '') === 'quote') {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        $error = 'Não foi possível validar o formulário. Actualize a página e tente novamente.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $service = trim($_POST['service'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $details = trim($_POST['details'] ?? '');

        if ($name === '' || $phone === '' || $details === '') {
            $error = 'Preencha o nome, telefone e descrição do projecto.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO quote_requests(name,phone,email,service,location,details) VALUES(?,?,?,?,?,?)');
            $stmt->execute([$name,$phone,$email,$service,$location,$details]);
            $sent = true;
        }
    }
}
$_SESSION['csrf'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf'];
/**
 * Protege um valor antes de o colocar dentro da página HTML.
 */
function e($v)
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="pt">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= e($setting('company_name', 'Moz Construções')) ?> | Construímos confiança</title>
    <meta name="description"
        content="Construção civil, reabilitação, fiscalização e consultoria. Transformamos projectos em realidade.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <div class="topbar">
        <div class="container topbar-inner"><span><i class="bi bi-geo-alt"></i>
                <?= e($setting('city', 'Maputo, Moçambique')) ?></span><span><i class="bi bi-envelope"></i>
                <?= e($setting('email', 'contacto@horizonte.co.mz')) ?> <b class="top-sep">|</b> <i
                    class="bi bi-telephone"></i> <?= e($setting('phone', '+258 84 000 0000')) ?></span></div>
    </div>
    <header class="header">
        <div class="container nav-wrap">
            <a class="brand" href="#inicio"><span class="brand-mark"><i
                        class="bi bi-buildings"></i></span><span><?= e($setting('company_name', 'Moz Construções')) ?><small><?= e($setting('company_tagline', 'CONSTRUÇÃO & ENGENHARIA')) ?></small></span></a>
            <button class="menu-toggle" id="menuToggle" aria-label="Abrir menu"><i class="bi bi-list"></i></button>
            <nav id="mainNav"><a href="#inicio">Início</a><a href="#sobre">Sobre nós</a><a
                    href="#servicos">Serviços</a><a href="#projectos">Projectos</a>
                <!-- <a href="#galeria">Obras em andamento</a> -->
                <a href="blog/">Blog</a><a href="#contactos">Contactos</a><a class="nav-cta" href="#orcamento">Solicitar
                    orçamento <i class="bi bi-arrow-up-right"></i></a>
            </nav>
        </div>
    </header>
    <main>
        <section class="hero" id="inicio"
            <?= $setting('hero_image') ? 'style="background-image:url(\'' . e($setting('hero_image')) . '\')"' : '' ?>>
            <div class="hero-shade"></div>
            <div class="container hero-content"><span class="eyebrow light"><i></i> ENGENHARIA • CONSTRUÇÃO •
                    CONFIANÇA</span>
                <h1><?= e($setting('hero_title', 'Construímos hoje o futuro que imagina.')) ?></h1>
                <p><?= e($setting('hero_description', 'Da ideia à concretização, entregamos soluções de construção com qualidade, rigor técnico e compromisso.')) ?>
                </p>
                <div class="hero-actions"><a class="btn btn-primary" href="#orcamento">Peça um orçamento <i
                            class="bi bi-arrow-right"></i></a><a class="btn btn-outline" href="#projectos">Conheça os
                        projectos</a></div>
                <div class="hero-note"><i class="bi bi-shield-check"></i> Qualidade, segurança e confiança em cada
                    etapa.</div>
            </div>
            <div class="hero-counter"><strong><?= number_format($visitors, 0, ',', '.') ?></strong><span>visitas
                    registadas</span></div>
        </section>

        <section class="trust-strip">
            <div class="container trust-grid">
                <div><i class="bi bi-award"></i><span><b>Qualidade</b><small>Excelência nos detalhes</small></span>
                </div>
                <div><i class="bi bi-shield-check"></i><span><b>Segurança</b><small>Trabalho responsável</small></span>
                </div>
                <div><i class="bi bi-clock-history"></i><span><b>Compromisso</b><small>Respeito pelos
                            prazos</small></span></div>
                <div><i class="bi bi-people"></i><span><b>Proximidade</b><small>O cliente em primeiro
                            lugar</small></span></div>
            </div>
        </section>

        <section class="section" id="sobre">
            <div class="container about-grid">
                <div class="about-visual"><img src="assets/imagem/about_image.jpg"
                        alt="Arquitectura e construção moderna">
                    <div class="experience-card"><strong>100%</strong><span>Dedicação em cada projecto</span></div>
                </div>
                <div class="about-copy"><span class="eyebrow">SOBRE A EMPRESA</span>
                    <h2><?= e($setting('about_title', 'Mais do que construir, criamos confiança.')) ?></h2>
                    <p class="lead">
                        <?= e($setting('company_description', 'Somos uma empresa orientada para soluções de construção, engenharia e reabilitação, comprometida com a qualidade e a satisfação dos nossos clientes.')) ?>
                    </p>
                    <p><?= e($setting('about_text', 'Aliamos conhecimento técnico, planeamento e acompanhamento próximo para transformar necessidades em espaços funcionais, seguros e duradouros.')) ?>
                    </p>
                    <div class="about-points">
                        <div><i class="bi bi-check-circle-fill"></i> Acompanhamento técnico</div>
                        <div><i class="bi bi-check-circle-fill"></i> Materiais e execução de qualidade</div>
                        <div><i class="bi bi-check-circle-fill"></i> Soluções ajustadas a cada cliente</div>
                    </div><a class="text-link" href="#contactos">Fale connosco <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </section>

        <section class="section section-soft company-principles" id="empresa">
            <div class="container">
                <div class="section-heading">
                    <div><span class="eyebrow">A NOSSA IDENTIDADE</span>
                        <h2>Os princípios que<br><em>orientam o nosso trabalho.</em></h2>
                    </div>
                    <p>Mais do que construir, queremos criar valor duradouro para os nossos clientes, parceiros e
                        comunidades.</p>
                </div>
                <div class="principle-grid">
                    <article class="principle-card">
                        <div class="principle-icon"><i class="bi bi-bullseye"></i></div><span class="principle-label">01
                            · PROPÓSITO</span>
                        <h3>Missão</h3>
                        <p>Executar projectos de construção e engenharia com qualidade, segurança e responsabilidade,
                            transformando as necessidades dos nossos clientes em soluções duradouras.</p>
                    </article>
                    <article class="principle-card">
                        <div class="principle-icon"><i class="bi bi-eye"></i></div><span class="principle-label">02 ·
                            FUTURO</span>
                        <h3>Visão</h3>
                        <p>Ser uma empresa de referência no sector da construção, reconhecida pela confiança, excelência
                            técnica e contributo para o desenvolvimento sustentável.</p>
                    </article>
                    <article class="principle-card">
                        <div class="principle-icon"><i class="bi bi-gem"></i></div><span class="principle-label">03 ·
                            COMPROMISSOS</span>
                        <h3>Valores</h3>
                        <ul class="principle-values">
                            <li><i class="bi bi-check2"></i> Integridade e transparência</li>
                            <li><i class="bi bi-check2"></i> Qualidade e rigor técnico</li>
                            <li><i class="bi bi-check2"></i> Segurança e responsabilidade</li>
                            <li><i class="bi bi-check2"></i> Respeito pelas pessoas e pelo ambiente</li>
                        </ul>
                    </article>
                </div>
            </div>
        </section>

        <?php
$partnerEntries = [];
$partnerSetting = trim($setting('partners'));

if ($partnerSetting === '') {
    $partnerSetting = default_site_partners();
}

foreach (preg_split('/\R/', $partnerSetting) ?: [] as $partnerLine) {
    $partnerLine = trim($partnerLine);

    if ($partnerLine === '') {
        continue;
    }
    $parts = array_map('trim', explode('|', $partnerLine));
    $partnerEntries[] = [
        'name' => $parts[0] ?? '',
        'logo' => $parts[1] ?? '',
        'url' => $parts[2] ?? '',
    ];
}

if (!$partnerEntries) {
    $partnerEntries = [['name' => 'Seja nosso parceiro', 'logo' => '', 'url' => '#orcamento']];
}
?>
        <section class="partners-section" id="parceiros" aria-labelledby="partnersHeading">
            <div class="container">
                <div class="partners-heading">
                    <div><span class="eyebrow">REDE DE COLABORAÇÃO</span>
                        <h2 id="partnersHeading">Os nossos <em>parceiros.</em></h2>
                    </div>
                    <div class="partners-controls" aria-label="Controles do carrossel">
                        <button type="button" id="partnersPrevious" aria-label="Parceiro anterior"><i
                                class="bi bi-arrow-left"></i></button>
                        <button type="button" id="partnersNext" aria-label="Próximo parceiro"><i
                                class="bi bi-arrow-right"></i></button>
                    </div>
                </div>
                <div class="partners-carousel" id="partnersCarousel">
                    <div class="partners-track">
                        <?php foreach ($partnerEntries as $partner):
            $partnerName = trim((string)$partner['name']);
            $partnerLogo = trim((string)$partner['logo']);
            $partnerUrl = trim((string)$partner['url']);
            $partnerKey = mb_strtolower($partnerName, 'UTF-8');
            $isFeaturedPartner = in_array($partnerKey, ['flyzinho', 'computer service'], true);
            $partnerInitials = '';

            foreach (array_slice(preg_split('/\s+/', $partnerName, -1, PREG_SPLIT_NO_EMPTY), 0, 3) as $word) {
                $partnerInitials .= mb_substr($word, 0, 1, 'UTF-8');
            }
            $tag = $partnerUrl !== '' ? 'a' : 'div';
            ?>
                        <<?= $tag ?>
                            class="partner-card<?= $isFeaturedPartner ? ' partner-featured' : '' ?><?= $partnerKey === 'flyzinho' ? ' partner-flyzinho' : '' ?>"
                            <?= $tag === 'a' ? ' href="' . e($partnerUrl) . '"' . (($partnerUrl !== '#orcamento') ? ' target="_blank" rel="noopener"' : '') : '' ?>>
                            <?php if ($partnerLogo !== ''): ?>
                            <img<?= $partnerKey === 'flyzinho' ? ' class="partner-logo-flyzinho"' : '' ?>
                                src="<?= e($partnerLogo) ?>" alt="Logótipo de <?= e($partnerName) ?>">
                                <?php else: ?>
                                <span class="partner-monogram"><?= e(mb_strtoupper($partnerInitials, 'UTF-8')) ?></span>
                                <?php endif; ?>
                                <strong><?= e($partnerName) ?></strong>
                                <?php if ($isFeaturedPartner): ?><small class="partner-featured-label">Parceiro em
                                    destaque</small><?php endif; ?>
                        </<?= $tag ?>>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>

        <section class="section section-soft" id="servicos">
            <div class="container">
                <div class="section-heading">
                    <div><span class="eyebrow">O QUE FAZEMOS</span>
                        <h2>Soluções completas<br><em>para construir melhor.</em></h2>
                    </div>
                    <p>Serviços pensados para acompanhar cada etapa do seu projecto, desde o planeamento até à entrega.
                    </p>
                </div>
                <div class="service-grid">
                    <?php foreach ($services as $s): ?><article class="service-card">
                        <div class="service-icon"><i class="bi <?= e($s['icon']) ?>"></i></div>
                        <h3><?= e($s['title']) ?></h3>
                        <p><?= e($s['description']) ?></p><a href="#orcamento"
                            aria-label="Solicitar <?= e($s['title']) ?>">Solicitar serviço <i
                                class="bi bi-arrow-up-right"></i></a>
                    </article><?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="section projects-section" id="projectos">
            <div class="container">
                <div class="section-heading">
                    <div><span class="eyebrow">PORTFÓLIO</span>
                        <h2>Projectos que falam<br><em>pelo nosso trabalho.</em></h2>
                    </div>
                    <p>Conheça alguns exemplos de trabalhos e soluções. As informações podem ser actualizadas pela
                        administração.</p>
                </div>
                <div class="project-grid">
                    <?php foreach ($projects as $p): ?><article class="project-card">
                        <div class="project-image"><img
                                src="<?= e($p['image_url'] ?: 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=900&q=80') ?>"
                                alt="<?= e($p['title']) ?>"><span><?= e($p['status']) ?></span></div>
                        <div class="project-info"><small><?= e($p['category']) ?> · <?= e($p['location']) ?></small>
                            <h3><?= e($p['title']) ?></h3>
                            <p><?= e($p['description']) ?></p>
                        </div>
                    </article><?php endforeach; ?>
                </div>
            </div>
        </section>

        <?php if ($ongoingProjects): ?>
        <section class="section section-soft ongoing-gallery" id="galeria" aria-labelledby="ongoingHeading">
            <div class="container">
                <div class="section-heading">
                    <div>
                        <span class="eyebrow">ACOMPANHE O NOSSO TRABALHO</span>
                        <h2 id="ongoingHeading">Obras em andamento<br><em>em imagens.</em></h2>
                    </div>
                    <p>Veja registos visuais dos projectos actualmente em execução. As fotografias são actualizadas pela
                        nossa equipa.</p>
                </div>
                <div class="ongoing-project-list">
                    <?php foreach ($ongoingProjects as $op):
          $images = $galleryByProject[(int)$op['id']] ?? [];
          $isDemoProject = stripos((string)($op['description'] ?? ''), 'Exemplo demonstrativo') !== false;
          $isIllustrative = !$images || ($isDemoProject && count($images) === 1);

          if (!$images && !empty($op['image_url'])) {
              $images[] = ['image_path' => $op['image_url'],'caption' => $op['title']];
          }

          if ($isIllustrative) {
              $images = array_merge($images, $illustrativeGalleryImages);
          }
          ?>
                    <article class="ongoing-project">
                        <div class="ongoing-project-heading">
                            <div class="ongoing-project-title">
                                <span class="project-status"><i></i> Em andamento</span>
                                <h3><?= e($op['title']) ?></h3>
                                <div class="ongoing-project-meta">
                                    <?php if (!empty($op['location'])): ?><span><i
                                            class="bi bi-geo-alt"></i><?= e($op['location']) ?></span><?php endif; ?>
                                    <?php if (!empty($op['category'])): ?><span><i
                                            class="bi bi-hammer"></i><?= e($op['category']) ?></span><?php endif; ?>
                                </div>
                            </div>
                            <span class="photo-count"><i class="bi bi-images"></i><?= count($images) ?>
                                <?= count($images) === 1 ? 'fotografia' : 'fotografias' ?></span>
                        </div>
                        <?php if (!empty($op['description'])): ?><p class="ongoing-description">
                            <?= e($op['description']) ?></p><?php endif; ?>
                        <?php if ($isIllustrative): ?><p class="gallery-illustrative-note"><i
                                class="bi bi-info-circle"></i><span>Inclui 4 imagens ilustrativas; adicione fotografias
                                reais pelo painel administrativo.</span></p><?php endif; ?>
                        <div class="ongoing-photo-grid" data-count="<?= count($images) ?>">
                            <?php foreach ($images as $photo):
              $photoSrc = (string)$photo['image_path'];
              $photoCaption = trim((string)($photo['caption'] ?? '')) ?: (string)$op['title'];
              ?>
                            <a class="ongoing-photo" href="<?= e($photoSrc) ?>" target="_blank" rel="noopener"
                                data-gallery-item data-project="<?= e($op['title']) ?>"
                                data-caption="<?= e($photoCaption) ?>" aria-haspopup="dialog"
                                aria-label="Ampliar fotografia: <?= e($photoCaption) ?>">
                                <img loading="lazy" decoding="async" src="<?= e($photoSrc) ?>"
                                    alt="<?= e($photoCaption) ?>">
                                <span class="ongoing-photo-overlay"><span
                                        class="ongoing-photo-caption"><?= e($photoCaption) ?></span><span
                                        class="ongoing-photo-action"><i class="bi bi-arrows-fullscreen"></i>
                                        Ampliar</span></span>
                            </a>
                            <?php endforeach; ?>
                            <?php if (!$images): ?><div class="gallery-empty"><i class="bi bi-camera"></i><span>As
                                    fotografias deste projecto serão adicionadas em breve.</span></div><?php endif; ?>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="gallery-lightbox" id="galleryLightbox" aria-hidden="true" hidden>
                <div class="gallery-lightbox-backdrop" data-gallery-close></div>
                <section class="gallery-lightbox-panel" role="dialog" aria-modal="true"
                    aria-labelledby="galleryLightboxProject" tabindex="-1">
                    <header class="gallery-lightbox-header">
                        <div><span>GALERIA DO PROJECTO</span>
                            <h2 id="galleryLightboxProject"></h2>
                        </div>
                        <button type="button" class="gallery-lightbox-close" data-gallery-close
                            aria-label="Fechar galeria"><i class="bi bi-x-lg"></i></button>
                    </header>
                    <div class="gallery-lightbox-image-wrap"><img id="galleryLightboxImage" alt=""></div>
                    <div class="gallery-lightbox-footer">
                        <p id="galleryLightboxCaption"></p>
                        <div class="gallery-lightbox-controls">
                            <button type="button" id="galleryPrevious" aria-label="Fotografia anterior"><i
                                    class="bi bi-arrow-left"></i></button>
                            <span id="galleryLightboxCount" aria-live="polite"></span>
                            <button type="button" id="galleryNext" aria-label="Fotografia seguinte"><i
                                    class="bi bi-arrow-right"></i></button>
                        </div>
                    </div>
                </section>
            </div>
        </section>
        <?php endif; ?>

        <section class="why-section">
            <div class="container why-grid">
                <div><span class="eyebrow light">O NOSSO COMPROMISSO</span>
                    <h2>Uma parceria sólida<br>do início ao fim.</h2>
                    <p>O seu projecto merece atenção, transparência e execução responsável. Trabalhamos para que cada
                        decisão tenha um propósito e cada etapa avance com segurança.</p><a class="btn btn-white"
                        href="#orcamento">Vamos conversar <i class="bi bi-arrow-right"></i></a>
                </div>
                <div class="why-items">
                    <div><b>01</b><span><strong>Planeamento rigoroso</strong><small>Organização e acompanhamento em
                                todas as fases.</small></span></div>
                    <div><b>02</b><span><strong>Equipa especializada</strong><small>Competência técnica orientada para
                                resultados.</small></span></div>
                    <div><b>03</b><span><strong>Transparência</strong><small>Comunicação clara e proximidade com o
                                cliente.</small></span></div>
                </div>
            </div>
        </section>

        <?php if ($testimonials): ?><section class="section section-soft">
            <div class="container">
                <div class="section-heading">
                    <div><span class="eyebrow">TESTEMUNHOS</span>
                        <h2>O que dizem<br><em>os nossos clientes.</em></h2>
                    </div>
                </div>
                <div class="testimonial-grid"><?php foreach ($testimonials as $t): ?><blockquote>
                        <div class="stars">★★★★★</div>
                        <p>“<?= e($t['message']) ?>”</p><cite><?= e($t['client_name']) ?></cite>
                    </blockquote><?php endforeach; ?></div>
            </div>
        </section><?php endif; ?>

        <section class="section quote-section" id="orcamento">
            <div class="container quote-grid">
                <div><span class="eyebrow light">FALE SOBRE O SEU PROJECTO</span>
                    <h2>Tem um projecto<br><em>em mente?</em></h2>
                    <p>Conte-nos o que pretende construir. A nossa equipa poderá entrar em contacto para compreender
                        melhor as suas necessidades.</p>
                    <div class="contact-line"><i class="bi bi-whatsapp"></i><span><small>Atendimento
                                directo</small><strong>WhatsApp da empresa</strong></span></div>
                </div>
                <form class="quote-form" method="post" action="#orcamento">
                    <h3>Solicitar orçamento</h3>
                    <p>Preencha os dados e entraremos em contacto.</p>
                    <?php if ($sent): ?><div class="alert success">Pedido enviado com sucesso. Obrigado pelo contacto!
                    </div><?php elseif ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
                    <input type="hidden" name="form_type" value="quote"><input type="hidden" name="csrf"
                        value="<?= e($csrf) ?>">
                    <div class="form-row"><label>Nome completo<input name="name" required maxlength="150"
                                placeholder="O seu nome"></label><label>Telefone<input name="phone" required
                                maxlength="40" placeholder="+258 ..."></label></div>
                    <div class="form-row"><label>E-mail (opcional)<input name="email" type="email" maxlength="190"
                                placeholder="email@exemplo.com"></label><label>Serviço<select name="service">
                                <option value="">Seleccione</option><?php foreach ($services as $s): ?><option>
                                    <?= e($s['title']) ?></option><?php endforeach; ?>
                            </select></label></div>
                    <label>Localização do projecto<input name="location" maxlength="180"
                            placeholder="Cidade / bairro"></label>
                    <label>Descreva o seu projecto<textarea name="details" required rows="4"
                            placeholder="Explique brevemente o que pretende..."></textarea></label>
                    <button class="btn btn-primary btn-full" type="submit">Enviar pedido <i
                            class="bi bi-send"></i></button><small class="privacy-note">Os seus dados serão utilizados
                        apenas para responder ao pedido.</small>
                </form>
            </div>
        </section>
        <?php if ($blogPosts): ?><section class="section section-soft">
            <div class="container">
                <div class="section-heading">
                    <div><span class="eyebrow">BLOG</span>
                        <h2>Conhecimento que<br><em>constrói valor.</em></h2>
                    </div>
                    <p>Artigos, dicas e novidades sobre construção civil e engenharia.</p>
                </div>
                <div class="project-grid"><?php foreach ($blogPosts as $bp): ?><article class="project-card">
                        <div class="project-image"><?php if ($bp['cover_image']): ?><img
                                src="<?= e($bp['cover_image']) ?>" alt="<?= e($bp['title']) ?>"><?php endif; ?></div>
                        <div class="project-info"><small><?= e(date('d/m/Y', strtotime($bp['published_at']))) ?></small>
                            <h3><?= e($bp['title']) ?></h3>
                            <p><?= e($bp['excerpt']) ?></p><a class="text-link"
                                href="blog/post.php?slug=<?= rawurlencode($bp['slug']) ?>">Ler artigo <i
                                    class="bi bi-arrow-right"></i></a>
                        </div>
                    </article><?php endforeach; ?></div>
                <p style="text-align:center;margin-top:25px"><a class="btn btn-primary" href="blog/">Ver todos os
                        artigos</a></p>
            </div>
        </section><?php endif; ?>
    </main>
    <footer class="footer" id="contactos">
        <div class="container footer-grid">
            <div class="footer-brand"><a class="brand brand-light" href="#inicio"><span class="brand-mark"><i
                            class="bi bi-buildings"></i></span><span><?= e($setting('company_name', 'Moz Construções')) ?><small><?= e($setting('company_tagline', 'CONSTRUÇÃO & ENGENHARIA')) ?></small></span></a>
                <p>Construção, engenharia e soluções que criam valor e confiança.</p>
            </div>
            <div>
                <h4>Links rápidos</h4><a href="#sobre">Sobre nós</a><a href="#servicos">Serviços</a><a
                    href="#projectos">Projectos</a><a href="#galeria">Obras em andamento</a><a href="blog/">Blog</a><a
                    href="#orcamento">Solicitar orçamento</a>
            </div>
            <div>
                <h4>Contactos</h4><span><i class="bi bi-geo-alt"></i>
                    <?= e($setting('address', 'Maputo, Moçambique')) ?></span><span><i class="bi bi-telephone"></i>
                    <?= e($setting('phone', '+258 84 000 0000')) ?></span><span><i class="bi bi-envelope"></i>
                    <?= e($setting('email', 'contacto@horizonte.co.mz')) ?></span>
            </div>
            <div>
                <h4>Horário</h4><span><?= e($setting('opening_hours', 'Segunda – Sexta | 08:00 – 17:00')) ?></span><a
                    href="admin/login.php">Área administrativa</a>
            </div>
        </div>
        <div class="container copyright"><span>© <?= date('Y') ?> Moz Construções. Todos os direitos reservados.
                <strong>Design by Flyzinho</strong></span><span>Construímos confiança. </span></div>
    </footer>

    <div class="wa-widget">
        <div class="wa-pop" id="waPop"><strong>Olá! Como podemos ajudar?</strong>
            <p>Escolha um assunto para iniciar a conversa.</p><a data-msg="Olá! Gostaria de solicitar um orçamento."
                href="#">Solicitar orçamento</a><a data-msg="Olá! Gostaria de conhecer os vossos serviços."
                href="#">Conhecer serviços</a><a data-msg="Olá! Pretendo falar com um representante." href="#">Falar com
                representante</a>
        </div><button id="waButton" aria-label="Abrir WhatsApp"><i class="bi bi-whatsapp"></i></button>
    </div>
    <script>
    window.WA_NUMBER = "<?= e($setting('whatsapp','258840000000')) ?>";
    </script>
    <script src="assets/js/main.js"></script>
    <?php include __DIR__ . '/../app/cookie-consent.php'; ?>
</body>

</html>