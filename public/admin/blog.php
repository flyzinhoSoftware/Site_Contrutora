<?php
declare(strict_types=1);
require_once __DIR__ . '/../../app/admin.php';
require_once __DIR__ . '/../../app/blog-notifications.php';
admin_require_login();
$pdo=db();$error='';$settings=[];foreach($pdo->query('SELECT setting_key,setting_value FROM site_settings')->fetchAll() as $settingRow)$settings[$settingRow['setting_key']]=$settingRow['setting_value'];
function blog_redirect(string $tab): never { header('Location: blog.php?tab='.rawurlencode($tab)); exit; }
if($_SERVER['REQUEST_METHOD']==='POST'){
 admin_verify_csrf();$action=(string)($_POST['action']??'');
 try{
  if($action==='save_category'){$name=trim((string)($_POST['name']??''));$slug=trim((string)($_POST['slug']??''));if($name==='')throw new RuntimeException('Indique o nome da categoria.');if($slug==='')$slug=$name;$slug=trim(preg_replace('/[^a-z0-9]+/','-',strtolower(iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$slug))),'-');$s=$pdo->prepare('INSERT INTO blog_categories(name,slug) VALUES(?,?) ON DUPLICATE KEY UPDATE name=VALUES(name)');$s->execute([$name,$slug]);blog_redirect('categorias');}
  if($action==='save_post'){
   $id=(int)($_POST['id']??0);$title=trim((string)($_POST['title']??''));$excerpt=trim((string)($_POST['excerpt']??''));$content=admin_sanitize_blog_html(trim((string)($_POST['content']??'')));$category=(int)($_POST['category_id']??0);$status=(string)($_POST['status']??'draft');$slug=trim((string)($_POST['slug']??''));
   if($title===''||$excerpt===''||$content==='')throw new RuntimeException('Preencha título, resumo e conteúdo.');
   if(!in_array($status,['draft','published'],true))throw new RuntimeException('Estado inválido.');
   if($slug==='')$slug=$title;$slug=trim(preg_replace('/[^a-z0-9]+/','-',strtolower(iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$slug))),'-');$slug=substr($slug,0,220);
   $old=null;$wasPublished=false;
   if($id){$s=$pdo->prepare('SELECT cover_image,status FROM blog_posts WHERE id=?');$s->execute([$id]);$old=$s->fetch();if(!$old)throw new RuntimeException('Artigo não encontrado.');$wasPublished=$old['status']==='published';}
   $image=is_array($old)&&is_string($old['cover_image'])?$old['cover_image']:null;
   if(isset($_FILES['cover'])&&($_FILES['cover']['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE)$image=admin_upload_image('cover',$image);
   $published=$status==='published'?date('Y-m-d H:i:s'):null;
   if($id){$s=$pdo->prepare('UPDATE blog_posts SET category_id=?,title=?,slug=?,excerpt=?,content=?,cover_image=?,status=?,published_at=COALESCE(published_at,?) WHERE id=?');$s->execute([$category?:null,$title,$slug,$excerpt,$content,$image,$status,$published,$id]);}
   else{$s=$pdo->prepare('INSERT INTO blog_posts(category_id,author_id,title,slug,excerpt,content,cover_image,status,published_at) VALUES(?,?,?,?,?,?,?,?,?)');$s->execute([$category?:null,$_SESSION['admin_id']??null,$title,$slug,$excerpt,$content,$image,$status,$published]);$id=(int)$pdo->lastInsertId();}
   if($status==='published'&&!$wasPublished){
    try{$deliveries=blog_send_new_post_notifications($pdo,['title'=>$title,'excerpt'=>$excerpt],SITE_URL.'/blog/post.php?slug='.rawurlencode($slug));}
    catch(Throwable $notificationError){error_log('Blog article was published but notification dispatch failed: '.$notificationError->getMessage());throw new RuntimeException('Artigo publicado, mas não foi possível processar as notificações. Verifique a migração da base de dados e a configuração de SMTP/VAPID.');}
    $failed=(int)$deliveries['email_failed']+(int)$deliveries['push_failed'];
    if($failed>0)throw new RuntimeException('Artigo publicado, mas '.$failed.' notificação(ões) falharam. Verifique a configuração SMTP/VAPID e os registos do servidor.');
   }
   blog_redirect('artigos');
  }
  if($action==='delete_post'){$id=(int)($_POST['id']??0);$s=$pdo->prepare('SELECT cover_image FROM blog_posts WHERE id=?');$s->execute([$id]);$img=$s->fetchColumn();$pdo->prepare('DELETE FROM blog_posts WHERE id=?')->execute([$id]);if(is_string($img))admin_delete_local_image($img);blog_redirect('artigos');}
  if($action==='comment_status'){$status=(string)($_POST['status']??'');if(!in_array($status,['approved','rejected','pending'],true))throw new RuntimeException('Estado de comentário inválido.');$pdo->prepare('UPDATE blog_comments SET status=? WHERE id=?')->execute([$status,(int)($_POST['id']??0)]);blog_redirect('comentarios');}
  if($action==='delete_comment'){$pdo->prepare('DELETE FROM blog_comments WHERE id=?')->execute([(int)($_POST['id']??0)]);blog_redirect('comentarios');}
 }catch(Throwable $e){$error=$e instanceof PDOException?'Não foi possível guardar. Verifique se o slug já existe.':$e->getMessage();}
}
$tab=(string)($_GET['tab']??'artigos');$categories=$pdo->query('SELECT * FROM blog_categories ORDER BY name')->fetchAll();$posts=$pdo->query("SELECT p.*,c.name category,(SELECT COUNT(*) FROM blog_post_views v WHERE v.post_id=p.id) views,(SELECT COUNT(*) FROM blog_post_reactions r WHERE r.post_id=p.id) reactions FROM blog_posts p LEFT JOIN blog_categories c ON c.id=p.category_id ORDER BY p.created_at DESC")->fetchAll();$comments=$pdo->query('SELECT c.*,p.title post_title,u.name user_name,u.email FROM blog_comments c JOIN blog_posts p ON p.id=c.post_id JOIN comment_users u ON u.id=c.user_id ORDER BY c.created_at DESC')->fetchAll();$csrf=admin_csrf_token();
function be($v):string{return admin_escape($v);}
function bf(string $action,int $id,string $csrf):void{echo '<input type="hidden" name="csrf_token" value="'.be($csrf).'"><input type="hidden" name="action" value="'.be($action).'"><input type="hidden" name="id" value="'.$id.'">';}
?><!doctype html>
<html lang="pt">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Gestão do Blog | Moz Construções</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    body{background:#f3f6f9;color:#34495e}.wrap{max-width:1200px;margin:30px auto;padding:0 18px}.panel{background:#fff;border:1px solid #e2e9ef;padding:24px;margin:18px 0;border-radius:12px;box-shadow:0 8px 24px rgba(18,59,101,.04)}
    .tabs{display:flex;flex-wrap:wrap;gap:8px;margin:0 0 20px}.tabs a{display:inline-flex;align-items:center;gap:7px;padding:10px 14px;color:#174c7c;background:#fff;border:1px solid #dce6ef;border-radius:7px;text-decoration:none;font-size:11px;font-weight:700}.tabs a:hover,.tabs a[aria-current=page]{background:#eaf3fb;border-color:#b9d5eb;color:#145c94}
    .cols{display:grid;grid-template-columns:1fr 1fr;gap:22px}label{display:block;font-size:13px;margin:10px 0}input,textarea,select{width:100%;padding:12px;border:1px solid #dce4eb;border-radius:7px;font:inherit}textarea{line-height:1.6}table{width:100%;border-collapse:collapse;font-size:13px}td,th{text-align:left;padding:10px;border-bottom:1px solid #e5ebf0;vertical-align:top}.thumb{max-width:120px;max-height:80px;object-fit:cover;border-radius:6px}.muted{color:#748392;font-size:13px}.btn{display:inline-block;padding:11px 16px;border:0;border-radius:7px;cursor:pointer}.btn-primary{background:#1769aa;color:#fff}.error{background:#fff0f0;padding:12px;color:#922}.muted{color:#748392;font-size:12px}@media(max-width:760px){.cols{grid-template-columns:1fr}table{display:block;overflow:auto}}
  </style>
</head>
<body class="admin-blog">
<div class="admin-layout blog-admin-layout">
<?php $adminPage=in_array($tab,['novo','editar'],true)?'blog_create':'blog'; include __DIR__ . '/sidebar.php'; ?>
<div class="admin-main-area">
  <header class="adminbar"><button type="button" class="mobile-menu-toggle" id="adminMenuToggle" aria-label="Abrir menu" aria-expanded="false"><i class="bi bi-list"></i></button><div class="container"><b><?= admin_escape($settings['company_name']??'Moz Construções') ?> · GESTÃO DO BLOG</b><span>Olá, <?= admin_escape($_SESSION['admin_name']??'Administrador') ?></span></div></header>
  <main class="adminmain">
    <div class="page-heading"><div><span class="eyebrow-admin">Publicações</span><h1>Gestão de conteúdos do Blog</h1><p>Crie artigos, organize categorias e modere comentários.</p></div><a class="back-link" href="dashboard.php"><i class="bi bi-arrow-left"></i> Painel principal</a></div>
    <?php if($error): ?><p class="error"><?= be($error) ?></p><?php endif; ?>
    <nav class="tabs" aria-label="Gestão do Blog">
      <a href="?tab=artigos" <?= $tab==='artigos'?'aria-current="page"':'' ?>><i class="bi bi-journal-text"></i>Artigos</a>
      <a href="?tab=novo" <?= $tab==='novo'?'aria-current="page"':'' ?>><i class="bi bi-plus-square"></i>Novo artigo</a>
      <a href="?tab=categorias" <?= $tab==='categorias'?'aria-current="page"':'' ?>><i class="bi bi-tags"></i>Categorias</a>
      <a href="?tab=comentarios" <?= $tab==='comentarios'?'aria-current="page"':'' ?>><i class="bi bi-chat-dots"></i>Comentários</a>
      <a href="../blog/" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right"></i>Ver blog</a>
    </nav>
<?php if($tab==='novo'||$tab==='editar'):$id=(int)($_GET['id']??0);$edit=null;if($id){$s=$pdo->prepare('SELECT * FROM blog_posts WHERE id=?');$s->execute([$id]);$edit=$s->fetch();} ?>
<div class="blog-compose-heading">
  <div><span class="compose-kicker">GESTÃO DE CONTEÚDOS</span><h2><?= $edit?'Editar artigo':'Criar artigo' ?></h2><p>Preencha os detalhes, escreva o conteúdo e escolha as opções de publicação.</p></div>
  <a class="admin-table-action" href="?tab=artigos"><i class="bi bi-arrow-left"></i> Voltar aos artigos</a>
</div>
<form class="blog-compose-form" data-blog-editor method="post" enctype="multipart/form-data">
  <?php bf('save_post',$id,$csrf); ?>
  <div class="blog-compose-grid">
    <div class="blog-compose-main">
      <section class="panel compose-card">
        <div class="compose-card-heading"><span class="compose-card-icon"><i class="bi bi-card-text"></i></span><div><h3>Detalhes do artigo</h3><p>Informações principais que serão apresentadas aos leitores.</p></div></div>
        <label>Título do artigo<input name="title" required maxlength="220" value="<?= be($edit['title']??'') ?>" placeholder="Escreva um título claro e apelativo"></label>
        <label>Slug (URL)<input name="slug" value="<?= be($edit['slug']??'') ?>" placeholder="gerado-a-partir-do-titulo"><small class="compose-help">Deixe vazio para gerar automaticamente a partir do título.</small></label>
        <label>Resumo<textarea name="excerpt" rows="3" maxlength="500" required placeholder="Apresente brevemente o tema do artigo."><?= be($edit['excerpt']??'') ?></textarea></label>
      </section>
      <section class="panel compose-card editor-card">
        <div class="compose-card-heading"><span class="compose-card-icon"><i class="bi bi-pencil-square"></i></span><div><h3>Conteúdo</h3><p>Use a barra de ferramentas para formatar o artigo.</p></div></div>
        <div class="editor-shell">
          <div class="editor-toolbar" role="toolbar" aria-label="Formatação do conteúdo">
            <button type="button" class="editor-tool" data-editor-command="bold" aria-label="Negrito" title="Negrito"><i class="bi bi-type-bold"></i></button>
            <button type="button" class="editor-tool" data-editor-command="italic" aria-label="Itálico" title="Itálico"><i class="bi bi-type-italic"></i></button>
            <span class="editor-toolbar-separator"></span>
            <select class="editor-block-select" data-editor-block aria-label="Estilo do parágrafo"><option value="p">Parágrafo</option><option value="h2">Título</option><option value="h3">Subtítulo</option><option value="h4">Título pequeno</option><option value="blockquote">Citação</option></select>
            <span class="editor-toolbar-separator"></span>
            <button type="button" class="editor-tool" data-editor-command="insertUnorderedList" aria-label="Lista com marcadores" title="Lista com marcadores"><i class="bi bi-list-ul"></i></button>
            <button type="button" class="editor-tool" data-editor-command="insertOrderedList" aria-label="Lista numerada" title="Lista numerada"><i class="bi bi-list-ol"></i></button>
            <span class="editor-toolbar-separator"></span>
            <button type="button" class="editor-tool" data-editor-command="undo" aria-label="Anular" title="Anular"><i class="bi bi-arrow-counterclockwise"></i></button>
            <button type="button" class="editor-tool" data-editor-command="redo" aria-label="Refazer" title="Refazer"><i class="bi bi-arrow-clockwise"></i></button>
          </div>
          <div class="rich-text-editor" id="blogContentEditor" contenteditable="true" role="textbox" aria-label="Conteúdo do artigo" aria-multiline="true"></div>
          <textarea class="editor-source" id="blogContentSource" name="content" tabindex="-1" aria-hidden="true"><?= be(admin_sanitize_blog_html((string)($edit['content']??''))) ?></textarea>
        </div>
        <p class="editor-help">Formatação permitida: parágrafos, títulos, listas, citações, negrito e itálico. Conteúdo não permitido é removido ao guardar.</p>
        <p class="editor-error" id="blogEditorError" role="alert" hidden>Escreva o conteúdo do artigo antes de guardar.</p>
      </section>
    </div>
    <aside class="blog-compose-side">
      <section class="panel compose-card">
        <div class="compose-card-heading"><span class="compose-card-icon"><i class="bi bi-send"></i></span><div><h3>Publicação</h3><p>Defina a visibilidade do artigo.</p></div></div>
        <label>Estado<select name="status"><option value="draft" <?= ($edit['status']??'draft')==='draft'?'selected':'' ?>>Rascunho</option><option value="published" <?= ($edit['status']??'')==='published'?'selected':'' ?>>Publicado</option></select></label>
        <label>Categoria<select name="category_id"><option value="0">Sem categoria</option><?php foreach($categories as $c): ?><option value="<?= $c['id'] ?>" <?= (int)($edit['category_id']??0)===(int)$c['id']?'selected':'' ?>><?= be($c['name']) ?></option><?php endforeach; ?></select></label>
      </section>
      <section class="panel compose-card">
        <div class="compose-card-heading"><span class="compose-card-icon"><i class="bi bi-image"></i></span><div><h3>Imagem de capa</h3><p>JPG, PNG ou WEBP até 5 MB.</p></div></div>
        <label>Escolher imagem<input type="file" name="cover" accept="image/jpeg,image/png,image/webp"></label>
        <?php if(!empty($edit['cover_image'])):?><img class="compose-cover-preview" src="../<?= be($edit['cover_image']) ?>" alt="Capa actual"><?php endif; ?>
      </section>
      <div class="compose-actions"><button class="btn btn-primary" type="submit"><i class="bi bi-check2-circle"></i> Guardar artigo</button><a class="admin-table-action" href="?tab=artigos">Cancelar</a></div>
    </aside>
  </div>
</form>
<?php elseif($tab==='categorias'): ?><section class="panel"><h2>Categorias</h2><form method="post"><?php bf('save_category',0,$csrf); ?><label>Nome da categoria<input name="name" required></label><label>Slug (opcional)<input name="slug"></label><button class="btn btn-primary">Adicionar categoria</button></form><ul><?php foreach($categories as $c): ?><li><?= be($c['name']) ?> <span class="muted">(<?= be($c['slug']) ?>)</span></li><?php endforeach; ?></ul></section>
<?php elseif($tab==='comentarios'): ?><section class="panel"><h2>Moderação de comentários</h2><table><tr><th>Visitante</th><th>Artigo / comentário</th><th>Estado e acções</th></tr><?php foreach($comments as $c): ?><tr><td><?= be($c['user_name']) ?><br><span class="muted"><?= be($c['email']) ?></span></td><td><b><?= be($c['post_title']) ?></b><p><?= nl2br(be($c['comment'])) ?></p><span class="muted"><?= be($c['created_at']) ?></span></td><td><?= be($c['status']) ?><form method="post"><?php bf('comment_status',(int)$c['id'],$csrf); ?><select name="status"><option value="pending">Pendente</option><option value="approved">Aprovar</option><option value="rejected">Rejeitar</option></select><button class="btn btn-primary">Actualizar</button></form><form method="post" onsubmit="return confirm('Eliminar este comentário?')"><?php bf('delete_comment',(int)$c['id'],$csrf); ?><button class="btn danger-btn">Eliminar</button></form></td></tr><?php endforeach; ?></table><?php if(!$comments): ?><p>Não existem comentários.</p><?php endif; ?></section>
<?php else: ?><section class="panel"><h2>Artigos existentes</h2><table><tr><th>Artigo</th><th>Categoria</th><th>Estado</th><th>Leitura</th><th>Acções</th></tr><?php foreach($posts as $p): ?><tr><td><b><?= be($p['title']) ?></b><br><span class="muted"><?= be($p['slug']) ?></span></td><td><?= be($p['category']??'—') ?></td><td><?= $p['status']==='published'?'Publicado':'Rascunho' ?></td><td><span class="muted"><i class="bi bi-eye"></i> <?= (int)$p['views'] ?> visualizações<br><i class="bi bi-emoji-smile"></i> <?= (int)$p['reactions'] ?> reacções</span></td><td><a class="admin-table-action" href="?tab=editar&id=<?= $p['id'] ?>">Editar</a><a class="admin-table-action" href="../blog/post.php?slug=<?= rawurlencode($p['slug']) ?>" target="_blank" rel="noopener">Ver</a><form method="post" onsubmit="return confirm('Eliminar este artigo e os comentários associados?')"><?php bf('delete_post',(int)$p['id'],$csrf); ?><button class="btn danger-btn">Eliminar</button></form></td></tr><?php endforeach; ?></table><?php if(!$posts): ?><p>Ainda não existem artigos.</p><?php endif; ?></section><?php endif; ?></main>
</div></div>
<script src="../assets/js/admin.js" defer></script>
</body></html>
