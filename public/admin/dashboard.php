<?php
declare(strict_types=1);
require_once __DIR__ . '/../../app/admin.php';
admin_require_login();
$pdo = db();
$error = '';
$page = (string)($_GET['page'] ?? 'inicio');
$allowedPages = ['inicio','configuracoes','projectos','servicos','testemunhos','pedidos'];
if (!in_array($page, $allowedPages, true)) $page = 'inicio';
$pageTitles = ['inicio'=>'Painel principal','configuracoes'=>'Conteúdos e contactos','projectos'=>'Gestão de projectos','servicos'=>'Gestão de serviços','testemunhos'=>'Testemunhos','pedidos'=>'Pedidos de orçamento'];

if (isset($_GET['logout'])) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy(); header('Location: login.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_verify_csrf();
    $action = (string)($_POST['action'] ?? '');
    try {
        if ($action === 'save_project') {
            $id = (int)($_POST['id'] ?? 0);
            $title = trim((string)($_POST['title'] ?? ''));
            $location = trim((string)($_POST['location'] ?? ''));
            $category = trim((string)($_POST['category'] ?? ''));
            $description = trim((string)($_POST['description'] ?? ''));
            $status = (string)($_POST['status'] ?? 'Planeado');
            if ($title === '' || $location === '' || $category === '' || !in_array($status, ['Concluído','Em andamento','Planeado'], true)) throw new RuntimeException('Preencha os campos obrigatórios do projecto.');
            $old = null;
            if ($id > 0) { $q=$pdo->prepare('SELECT image_url FROM projects WHERE id=?'); $q->execute([$id]); $old=$q->fetchColumn(); if ($old === false) throw new RuntimeException('Projecto não encontrado.'); }
            $uploadProvided = isset($_FILES['image_file']) && ($_FILES['image_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
            $externalImage = trim((string)($_POST['image_url'] ?? ''));
            if ($uploadProvided) {
                $image = admin_upload_image('image_file', is_string($old) ? $old : null);
            } elseif ($externalImage !== '') {
                if (!filter_var($externalImage, FILTER_VALIDATE_URL) || !in_array(strtolower((string)parse_url($externalImage, PHP_URL_SCHEME)), ['http','https'], true)) throw new RuntimeException('Indique um URL de imagem válido com HTTP ou HTTPS.');
                if (is_string($old)) admin_delete_local_image($old);
                $image = $externalImage;
            } else {
                $image = is_string($old) ? $old : null;
            }
            if ($id > 0) {
                $q=$pdo->prepare('UPDATE projects SET title=?,location=?,category=?,description=?,image_url=?,status=? WHERE id=?');
                $q->execute([$title,$location,$category,$description,$image,$status,$id]);
                $projectId = $id;
            } else {
                $q=$pdo->prepare('INSERT INTO projects(title,location,category,description,image_url,status) VALUES(?,?,?,?,?,?)');
                $q->execute([$title,$location,$category,$description,$image,$status]);
                $projectId = (int)$pdo->lastInsertId();
            }
            admin_upload_project_gallery('gallery_files', $projectId, $pdo);
            admin_redirect('projectos');
        }
        if ($action === 'delete_project') {
            $id=(int)($_POST['id'] ?? 0);
            $q=$pdo->prepare('SELECT image_url FROM projects WHERE id=?');$q->execute([$id]);$img=$q->fetchColumn();
            $gallery=$pdo->prepare('SELECT image_path FROM project_images WHERE project_id=?');$gallery->execute([$id]);
            foreach($gallery->fetchAll(PDO::FETCH_COLUMN) as $galleryImage) admin_delete_local_image((string)$galleryImage);
            $q=$pdo->prepare('DELETE FROM projects WHERE id=?');$q->execute([$id]);
            if(is_string($img)) admin_delete_local_image($img);
            admin_redirect('projectos');
        }
        if ($action === 'save_service') {
            $id=(int)($_POST['id'] ?? 0);$title=trim((string)($_POST['title'] ?? ''));$description=trim((string)($_POST['description'] ?? ''));$icon=trim((string)($_POST['icon'] ?? 'bi-building'));
            if($title==='' || $description==='') throw new RuntimeException('Indique o nome e a descrição do serviço.');
            if(!preg_match('/^bi-[a-z0-9-]{2,60}$/',$icon)) $icon='bi-building';
            if($id>0){$q=$pdo->prepare('UPDATE services SET title=?,description=?,icon=? WHERE id=?');$q->execute([$title,$description,$icon,$id]);}
            else{$q=$pdo->prepare('INSERT INTO services(title,description,icon) VALUES(?,?,?)');$q->execute([$title,$description,$icon]);}
            admin_redirect('servicos');
        }
        if ($action === 'delete_service') { $q=$pdo->prepare('DELETE FROM services WHERE id=?');$q->execute([(int)($_POST['id'] ?? 0)]);admin_redirect('servicos'); }
        if ($action === 'save_testimonial') {
            $id=(int)($_POST['id'] ?? 0);$name=trim((string)($_POST['client_name'] ?? ''));$message=trim((string)($_POST['message'] ?? ''));
            if($name==='' || $message==='') throw new RuntimeException('Preencha o nome do cliente e o testemunho.');
            if($id>0){$q=$pdo->prepare('UPDATE testimonials SET client_name=?,message=? WHERE id=?');$q->execute([$name,$message,$id]);}
            else{$q=$pdo->prepare('INSERT INTO testimonials(client_name,message) VALUES(?,?)');$q->execute([$name,$message]);}
            admin_redirect('testemunhos');
        }
        if ($action === 'delete_testimonial') { $q=$pdo->prepare('DELETE FROM testimonials WHERE id=?');$q->execute([(int)($_POST['id'] ?? 0)]);admin_redirect('testemunhos'); }
        if ($action === 'update_request') {
            $status=(string)($_POST['status'] ?? 'Novo');
            if(!in_array($status,['Novo','Em análise','Respondido','Concluído'],true)) throw new RuntimeException('Estado do pedido inválido.');
            $q=$pdo->prepare('UPDATE quote_requests SET status=? WHERE id=?');$q->execute([$status,(int)($_POST['id'] ?? 0)]);
            admin_redirect('pedidos');
        }
        if ($action === 'save_settings') {
            $allowed=['company_name','company_tagline','company_description','hero_title','hero_description','about_title','about_text','city','phone','email','whatsapp','address','opening_hours'];
            foreach($allowed as $key){$value=trim((string)($_POST[$key] ?? ''));if($key==='email' && $value!=='' && !filter_var($value,FILTER_VALIDATE_EMAIL)) throw new RuntimeException('O endereço de e-mail não é válido.');if($key==='whatsapp' && !preg_match('/^[0-9]{9,15}$/',$value)) throw new RuntimeException('O WhatsApp deve conter apenas 9 a 15 dígitos, incluindo o indicativo do país.');$q=$pdo->prepare('INSERT INTO site_settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)');$q->execute([$key,$value]);}
            foreach(['hero_image','about_image'] as $field){$oldQ=$pdo->prepare('SELECT setting_value FROM site_settings WHERE setting_key=?');$oldQ->execute([$field]);$old=$oldQ->fetchColumn();$new=admin_upload_image($field,is_string($old)?$old:null);if($new!==$old){$q=$pdo->prepare('INSERT INTO site_settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)');$q->execute([$field,$new??'']);}}
            admin_redirect('configuracoes');
        }
    } catch (Throwable $e) { $error = $e instanceof PDOException ? 'Ocorreu um erro ao guardar os dados. Verifique as relações e os dados introduzidos.' : $e->getMessage(); }
}

$stats=['visits'=>(int)$pdo->query('SELECT COUNT(*) FROM visits')->fetchColumn(),'projects'=>(int)$pdo->query('SELECT COUNT(*) FROM projects')->fetchColumn(),'services'=>(int)$pdo->query('SELECT COUNT(*) FROM services')->fetchColumn(),'requests'=>(int)$pdo->query('SELECT COUNT(*) FROM quote_requests')->fetchColumn()];
$projects=$pdo->query('SELECT p.*, (SELECT COUNT(*) FROM project_images pi WHERE pi.project_id=p.id) AS gallery_count FROM projects p ORDER BY p.id DESC')->fetchAll();
$services=$pdo->query('SELECT * FROM services ORDER BY id DESC')->fetchAll();
$testimonials=$pdo->query('SELECT * FROM testimonials ORDER BY id DESC')->fetchAll();
$requests=$pdo->query('SELECT * FROM quote_requests ORDER BY id DESC')->fetchAll();
$settings=[];foreach($pdo->query('SELECT setting_key,setting_value FROM site_settings')->fetchAll() as $row)$settings[$row['setting_key']]=$row['setting_value'];
$defaults=['company_name'=>'Moz Construções','company_tagline'=>'CONSTRUÇÃO & ENGENHARIA','company_description'=>'','hero_title'=>'Construímos hoje o futuro que imagina.','hero_description'=>'','about_title'=>'Mais do que construir, criamos confiança.','about_text'=>'','city'=>'Maputo, Moçambique','phone'=>'+258 84 000 0000','email'=>'contacto@horizonte.co.mz','whatsapp'=>'258840000000','address'=>'Maputo, Moçambique','opening_hours'=>'Segunda – Sexta | 08:00 – 17:00','hero_image'=>'','about_image'=>''];
$settings=array_merge($defaults,$settings);$csrf=admin_csrf_token();
function field_value(array $row,string $key):string{return admin_escape($row[$key]??'');}
function edit_form(string $action,int $id,string $token):void{echo '<input type="hidden" name="csrf_token" value="'.admin_escape($token).'"><input type="hidden" name="action" value="'.admin_escape($action).'"><input type="hidden" name="id" value="'.$id.'">';}
?>
<!doctype html><html lang="pt"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Painel administrativo | Moz Construções</title><link rel="stylesheet" href="../assets/css/style.css"><style>
:root{--navy:#123b65;--blue:#2878c8;--line:#e5ebf0}body{background:#f3f6f9;color:#34495e}.adminbar{background:var(--navy);color:#fff;padding:16px 0}.adminbar .container{display:flex;justify-content:space-between;align-items:center}.adminbar a{color:#d6eaff}.adminmain{padding:30px 0 60px}.admin-menu{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;margin:24px 0}.admin-menu a{display:block;background:#fff;border:1px solid var(--line);border-radius:10px;padding:22px;text-decoration:none;transition:.2s}.admin-menu a:hover{border-color:var(--blue);transform:translateY(-2px);box-shadow:0 10px 24px #123b6512}.admin-menu b{display:block;color:var(--navy);font-size:16px;margin-bottom:7px}.admin-menu span{display:block;color:#718293;font-size:13px;line-height:1.5}.back-link{color:var(--blue);font-weight:700}.adminmain h1{color:var(--navy);margin-bottom:4px}.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin:22px 0}.stat,.panel{background:#fff;padding:20px;border:1px solid var(--line);border-radius:5px}.stat small{display:block;color:#718293}.stat strong{font-size:28px;color:var(--navy)}.panel{margin-bottom:18px;scroll-margin-top:20px}.panel h2{font-size:18px;color:var(--navy);margin-top:0}.panel h3{font-size:14px;color:var(--navy)}.panel input,.panel textarea,.panel select{width:100%;padding:10px;border:1px solid #dce4eb;margin:5px 0 12px;border-radius:3px;font:12px 'DM Sans'}.panel label{font-size:11px;color:#536779;display:block}.panel textarea{resize:vertical}.panel table{width:100%;border-collapse:collapse;font-size:11px}.panel td,.panel th{text-align:left;padding:10px;border-bottom:1px solid #e8edf2;vertical-align:top}.panel th{color:var(--navy)}.panel .btn{padding:10px 14px;font-size:11px}.danger-btn{background:#fff0f0;color:#a12c2c;border:1px solid #f1cccc}.tabs{display:flex;gap:8px;flex-wrap:wrap;margin:20px 0}.tabs a{padding:9px 13px;background:#fff;border:1px solid var(--line);border-radius:4px;color:var(--navy);font-size:12px;font-weight:700}.tabs a:hover{background:#eaf3fb}.two-col{display:grid;grid-template-columns:1fr 1fr;gap:16px}.form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}.notice{padding:12px;background:#eaf3fb;color:#174c7c;margin:12px 0;font-size:12px}.error{padding:12px;background:#fff0f0;color:#9c2525;margin:12px 0;font-size:12px}.thumb{width:85px;height:58px;object-fit:cover;border-radius:3px}.inline-form{display:inline}.muted{color:#8795a2;font-size:11px}@media(max-width:750px){.admin-menu{grid-template-columns:repeat(2,minmax(0,1fr))}.stats{grid-template-columns:repeat(2,1fr)}.two-col,.form-row{grid-template-columns:1fr}.adminbar .container{align-items:flex-start;gap:12px;flex-direction:column}.panel{overflow-x:auto}.panel table{min-width:600px}}@media(max-width:500px){.admin-menu{grid-template-columns:1fr}}
</style></head><body>
<div class="adminbar"><div class="container"><b><?= admin_escape($settings['company_name']) ?> · PAINEL ADMINISTRATIVO</b><span>Olá, <?= admin_escape($_SESSION['admin_name'] ?? 'Administrador') ?> · <a href="?logout=1">Terminar sessão</a></span></div></div>
<main class="container adminmain"><h1><?= admin_escape($pageTitles[$page]) ?></h1><p class="muted">Gestão do website e dos conteúdos públicos.</p>
<?php if($error): ?><div class="error"><?= admin_escape($error) ?></div><?php endif; ?>
<?php if(isset($_GET['ok'])): ?><div class="notice">Operação concluída com sucesso.</div><?php endif; ?>
<?php if ($page === 'inicio'): ?><div class="stats"><?php foreach(['Visitas registadas'=>'visits','Projectos'=>'projects','Serviços'=>'services','Pedidos de orçamento'=>'requests'] as $label=>$key): ?><div class="stat"><small><?= admin_escape($label) ?></small><strong><?= $stats[$key] ?></strong></div><?php endforeach; ?></div><?php endif; ?>
<?php if ($page === 'inicio'): ?><div class="admin-menu">
<a href="?page=configuracoes"><b>Conteúdos e contactos</b><span>Identidade, textos e contactos do website</span></a>
<a href="?page=projectos"><b>Projectos</b><span>Adicionar, editar e remover projectos</span></a>
<a href="?page=servicos"><b>Serviços</b><span>Gerir serviços apresentados no website</span></a>
<a href="?page=testemunhos"><b>Testemunhos</b><span>Gerir avaliações e depoimentos</span></a>
<a href="?page=pedidos"><b>Pedidos de orçamento</b><span>Acompanhar e actualizar solicitações</span></a>
<a href="blog.php"><b>Gestão do Blog</b><span>Artigos, categorias e comentários</span></a>
<a href="blog.php?tab=novo"><b>Criar artigo</b><span>Publicar uma nova notícia ou orientação</span></a>
</div><?php else: ?><p><a class="back-link" href="dashboard.php">← Voltar ao painel principal</a></p><?php endif; ?>
<?php if ($page === 'configuracoes'): ?><section class="panel" id="configuracoes"><h2>Conteúdos institucionais e contactos</h2><form method="post" enctype="multipart/form-data"><?php edit_form('save_settings',0,$csrf); ?><div class="two-col"><div><label>Nome da empresa<input name="company_name" required maxlength="120" value="<?= field_value($settings,'company_name') ?>"></label><label>Assinatura / actividade<input name="company_tagline" maxlength="160" value="<?= field_value($settings,'company_tagline') ?>"></label><label>Descrição institucional<textarea name="company_description" rows="3"><?= field_value($settings,'company_description') ?></textarea></label><label>Título do banner<input name="hero_title" maxlength="220" value="<?= field_value($settings,'hero_title') ?>"></label><label>Texto do banner<textarea name="hero_description" rows="3"><?= field_value($settings,'hero_description') ?></textarea></label><label>Título da secção Sobre<input name="about_title" maxlength="220" value="<?= field_value($settings,'about_title') ?>"></label><label>Texto da secção Sobre<textarea name="about_text" rows="3"><?= field_value($settings,'about_text') ?></textarea></label></div><div><label>Cidade / localização<input name="city" value="<?= field_value($settings,'city') ?>"></label><label>Morada<input name="address" value="<?= field_value($settings,'address') ?>"></label><label>Telefone<input name="phone" value="<?= field_value($settings,'phone') ?>"></label><label>E-mail<input type="email" name="email" value="<?= field_value($settings,'email') ?>"></label><label>WhatsApp (apenas dígitos com indicativo)<input name="whatsapp" pattern="[0-9]{9,15}" value="<?= field_value($settings,'whatsapp') ?>"></label><label>Horário de atendimento<input name="opening_hours" value="<?= field_value($settings,'opening_hours') ?>"></label><label>Imagem do banner (JPG, PNG ou WEBP, máx. 5 MB)<input type="file" name="hero_image" accept="image/jpeg,image/png,image/webp"></label><?php if($settings['hero_image']): ?><img class="thumb" src="../<?= admin_escape($settings['hero_image']) ?>" alt="Banner actual"><?php endif; ?><label>Imagem da secção Sobre<input type="file" name="about_image" accept="image/jpeg,image/png,image/webp"></label><?php if($settings['about_image']): ?><img class="thumb" src="../<?= admin_escape($settings['about_image']) ?>" alt="Imagem actual"><?php endif; ?></div></div><button class="btn btn-primary">Guardar conteúdos e contactos</button></form></section><?php endif; ?>
<?php if ($page === 'projectos'): ?><section class="panel" id="projectos"><h2>Projectos publicados</h2><div class="two-col"><div><h3>Adicionar projecto</h3><form method="post" enctype="multipart/form-data"><?php edit_form('save_project',0,$csrf); ?><label>Nome do projecto<input name="title" required></label><label>Localização<input name="location" required></label><label>Categoria<input name="category" required></label><label>Descrição<textarea name="description" rows="3"></textarea></label><label>Imagem (JPG, PNG ou WEBP, máx. 5 MB)<input type="file" name="image_file" accept="image/jpeg,image/png,image/webp"></label><label>Ou URL externa da imagem<input name="image_url" type="url" placeholder="https://..."></label><label>Imagens adicionais para a galeria (até 10 por envio)<input type="file" name="gallery_files[]" accept="image/jpeg,image/png,image/webp" multiple></label><small class="muted">Pode seleccionar várias fotografias da obra. Cada imagem até 5 MB.</small><label>Estado<select name="status"><option>Concluído</option><option>Em andamento</option><option>Planeado</option></select></label><button class="btn btn-primary">Adicionar projecto</button></form></div><div><h3>Lista de projectos</h3><table><tr><th>Imagem</th><th>Projecto</th><th>Estado / acções</th></tr><?php foreach($projects as $p): ?><tr><td><?php if($p['image_url']): ?><img class="thumb" src="<?= admin_escape(str_starts_with($p['image_url'],'uploads/')?'../'.$p['image_url']:$p['image_url']) ?>" alt=""><?php endif; ?></td><td><b><?= admin_escape($p['title']) ?></b><br><?= admin_escape($p['location']) ?></td><td><?= admin_escape($p['status']) ?><br><span class="muted"><?= (int)$p['gallery_count'] ?> imagem(ns) na galeria</span><br><details><summary>Editar</summary><form method="post" enctype="multipart/form-data"><?php edit_form('save_project',(int)$p['id'],$csrf); ?><label>Nome<input name="title" required value="<?= admin_escape($p['title']) ?>"></label><label>Localização<input name="location" required value="<?= admin_escape($p['location']) ?>"></label><label>Categoria<input name="category" required value="<?= admin_escape($p['category']) ?>"></label><label>Descrição<textarea name="description"><?= admin_escape($p['description']) ?></textarea></label><label>Substituir imagem<input type="file" name="image_file" accept="image/jpeg,image/png,image/webp"></label><label>URL externa<input name="image_url" type="url" value="<?= admin_escape(str_starts_with((string)$p['image_url'],'uploads/')?'':$p['image_url']) ?>"></label><label>Adicionar fotografias à galeria<input type="file" name="gallery_files[]" accept="image/jpeg,image/png,image/webp" multiple></label><small class="muted">As novas fotografias serão acrescentadas às existentes (máximo 10 por envio).</small><label>Estado<select name="status"><?php foreach(['Concluído','Em andamento','Planeado'] as $st): ?><option <?= $p['status']===$st?'selected':'' ?>><?= $st ?></option><?php endforeach; ?></select></label><button class="btn btn-primary">Guardar edição</button></form></details><form method="post" class="inline-form" onsubmit="return confirm('Tem a certeza de que pretende eliminar este projecto?')"><?php edit_form('delete_project',(int)$p['id'],$csrf); ?><button class="btn danger-btn">Eliminar</button></form></td></tr><?php endforeach; ?></table></div></div></section><?php endif; ?>
<?php if ($page === 'servicos'): ?><section class="panel" id="servicos"><h2>Serviços</h2><div class="two-col"><div><h3>Adicionar serviço</h3><form method="post"><?php edit_form('save_service',0,$csrf); ?><label>Nome<input name="title" required></label><label>Descrição<textarea name="description" rows="3" required></textarea></label><label>Ícone Bootstrap (ex.: bi-building)<input name="icon" value="bi-building"></label><button class="btn btn-primary">Adicionar serviço</button></form></div><div><h3>Serviços existentes</h3><table><tr><th>Serviço</th><th>Acções</th></tr><?php foreach($services as $s): ?><tr><td><b><?= admin_escape($s['title']) ?></b><br><?= admin_escape($s['description']) ?></td><td><details><summary>Editar</summary><form method="post"><?php edit_form('save_service',(int)$s['id'],$csrf); ?><label>Nome<input name="title" required value="<?= admin_escape($s['title']) ?>"></label><label>Descrição<textarea name="description" required><?= admin_escape($s['description']) ?></textarea></label><label>Ícone<input name="icon" value="<?= admin_escape($s['icon']) ?>"></label><button class="btn btn-primary">Guardar</button></form></details><form method="post" class="inline-form" onsubmit="return confirm('Eliminar este serviço?')"><?php edit_form('delete_service',(int)$s['id'],$csrf); ?><button class="btn danger-btn">Eliminar</button></form></td></tr><?php endforeach; ?></table></div></div></section><?php endif; ?>
<?php if ($page === 'testemunhos'): ?><section class="panel" id="testemunhos"><h2>Testemunhos de clientes</h2><div class="two-col"><div><h3>Adicionar testemunho</h3><form method="post"><?php edit_form('save_testimonial',0,$csrf); ?><label>Nome do cliente<input name="client_name" required></label><label>Testemunho<textarea name="message" rows="4" required></textarea></label><button class="btn btn-primary">Adicionar testemunho</button></form></div><div><h3>Testemunhos existentes</h3><table><tr><th>Cliente / testemunho</th><th>Acções</th></tr><?php foreach($testimonials as $t): ?><tr><td><b><?= admin_escape($t['client_name']) ?></b><br><?= admin_escape($t['message']) ?></td><td><details><summary>Editar</summary><form method="post"><?php edit_form('save_testimonial',(int)$t['id'],$csrf); ?><label>Nome<input name="client_name" required value="<?= admin_escape($t['client_name']) ?>"></label><label>Testemunho<textarea name="message" required><?= admin_escape($t['message']) ?></textarea></label><button class="btn btn-primary">Guardar</button></form></details><form method="post" class="inline-form" onsubmit="return confirm('Eliminar este testemunho?')"><?php edit_form('delete_testimonial',(int)$t['id'],$csrf); ?><button class="btn danger-btn">Eliminar</button></form></td></tr><?php endforeach; ?></table></div></div></section><?php endif; ?>
<?php if ($page === 'pedidos'): ?><section class="panel" id="pedidos"><h2>Pedidos de orçamento</h2><table><tr><th>Data</th><th>Cliente</th><th>Contacto</th><th>Serviço / detalhe</th><th>Estado</th></tr><?php foreach($requests as $r): ?><tr><td><?= admin_escape($r['created_at']) ?></td><td><?= admin_escape($r['name']) ?><br><?= admin_escape($r['email']) ?></td><td><?= admin_escape($r['phone']) ?></td><td><?= admin_escape($r['service']) ?><br><?= admin_escape($r['details']) ?></td><td><form method="post"><?php edit_form('update_request',(int)$r['id'],$csrf); ?><select name="status"><?php foreach(['Novo','Em análise','Respondido','Concluído'] as $st): ?><option <?= $r['status']===$st?'selected':'' ?>><?= $st ?></option><?php endforeach; ?></select><button class="btn btn-primary">Actualizar</button></form></td></tr><?php endforeach; ?></table></section><?php endif; ?>
</main></body></html>
