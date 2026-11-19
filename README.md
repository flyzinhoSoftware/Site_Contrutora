# Website — Moz Construções

Website institucional em PHP 8.2 + MySQL/MariaDB, preparado para XAMPP.

## Funcionalidades incluídas
- Página institucional responsiva em azul e branco.
- Banner de construção, secções sobre, serviços, projectos e contactos.
- Integração de contacto WhatsApp por ligação `wa.me`.
- Contador aproximado de visitantes únicos por dia.
- Formulário de pedido de orçamento com validação, PDO e token CSRF.
- Painel administrativo com autenticação, gestão completa de serviços, projectos e testemunhos.
- Edição de textos institucionais, contactos, horário e imagens do banner/secção Sobre.
- Upload de imagens JPG, PNG e WEBP com validação MIME, dimensões e limite de 5 MB.
- Protecção CSRF nas operações administrativas, confirmação antes de eliminar, sessão com expiração por inactividade e regeneração do ID após login.
- Blog editorial com feed de notícias, ticker de destaques, newslider automático, layout em grelha, pesquisa, filtro por categoria, paginação, artigos relacionados e secção de mais lidas.
- Painel administrativo reorganizado em ecrãs separados para conteúdos, projectos, serviços, testemunhos, pedidos e Blog.
- Criação e edição de artigos no painel, com resumo, categoria, slug, imagem de capa, rascunho/publicação e sanitização de conteúdo HTML permitido.
- Aviso de cookies com aceitação, recusa e personalização; cookies de análise só são activados após consentimento.
- Login de visitantes com Google Identity Services para comentar, com verificação de ID token no servidor e aprovação manual dos comentários.
- Notificações opcionais de novos artigos por e-mail e Web Push, com consentimento independente e gestão pelo próprio visitante.
- Interacção nos artigos: visualizações únicas por visitante, botão de gosto e reacções (Adorei, Útil e Interessante), com contagens actualizadas sem recarregar a página.

## Instalação no XAMPP
1. Copie a pasta `obraforte_site` para `C:\xampp\htdocs\`.
2. Inicie Apache e MySQL.
3. Abra phpMyAdmin e importe `database/schema.sql`.
4. Importe também `database/upgrade_admin.sql`, `database/upgrade_blog.sql`, `database/upgrade_project_gallery.sql`, `database/upgrade_blog_engagement.sql` e `database/upgrade_blog_notifications.sql` para activar conteúdos institucionais, Blog, galeria, visualizações, reacções e preferências de notificações.
5. Execute `composer install` na pasta do projecto para instalar as bibliotecas do login Google, e-mail e Web Push.
6. Configure `GOOGLE_CLIENT_ID` no ambiente PHP/Apache com o ID de cliente OAuth do tipo Aplicação Web criado no Google Cloud Console. Adicione `http://localhost` aos origens JavaScript autorizados e o domínio real quando publicar.
7. Para instalar o administrador e os 10 artigos de demonstração, importe `database/seed_admin_posts.sql` no phpMyAdmin, depois de executar as migrações. Em alternativa, execute `database/create_admin.php` uma única vez para criar apenas o administrador inicial. Não é necessário usar os dois métodos.
8. Aceda a `http://localhost/obraforte_site/public/admin/login.php`.
9. Se utilizou o SQL de demonstração, entre com `admin@mozconstrucoes.co.mz` e a palavra-passe temporária `Mudar@Moz2026!`. Altere-a imediatamente após o primeiro acesso.
10. Se utilizou `create_admin.php`, utilize as credenciais indicadas nesse ficheiro. Elimine `database/create_admin.php` do servidor depois da criação do administrador.
11. No painel, actualize nome, telefone, e-mail, endereço e WhatsApp (WhatsApp apenas com dígitos e indicativo do país).
12. Aceda a `http://localhost/obraforte_site/public/`.

## Cookies e privacidade
- Os cookies essenciais mantêm o funcionamento das sessões. Os cookies de análise só são activados quando o visitante aceita essa finalidade.
- A preferência é guardada durante 180 dias. O visitante pode recusar ou personalizar a utilização de cookies de análise no aviso apresentado no website.
- As notificações de novos artigos exigem uma adesão separada por e-mail e/ou navegador; a escolha não activa cookies de análise e pode ser retirada nas preferências do Blog.
- Antes de publicar, crie uma página de Política de Privacidade com os dados reais do responsável pelo website, finalidades, prazos de conservação e contactos para pedidos dos titulares.

## Configuração do login Google
- Crie um OAuth Client ID do tipo Web Application no Google Cloud Console.
- No XAMPP, abra `apache/conf/httpd.conf` e, dentro da configuração do directório do projecto, acrescente `SetEnv GOOGLE_CLIENT_ID "SEU_CLIENT_ID.apps.googleusercontent.com"`; reinicie o Apache. Não coloque segredos privados no JavaScript. O fluxo Google Identity Services usa o Client ID público e valida o token no servidor.
- Para produção, configure HTTPS e os domínios/origens exactos autorizados no Google Cloud.
- Comentários são guardados como pendentes e só aparecem depois de aprovação no painel em Gestão do Blog → Comentários.

## Notificações de novos artigos
- O login Google valida a identidade do visitante; não concede acesso à caixa de correio nem activa notificações automaticamente. O visitante tem de aderir explicitamente, escolhendo e-mail, navegador ou ambos. Pode retirar a autorização no mesmo painel do artigo.
- Só são enviados avisos quando um artigo passa de rascunho para publicado. Alterações a artigos já publicados e comentários não geram notificações.
- Importe `database/upgrade_blog_notifications.sql` e execute `composer install`. Configure as variáveis abaixo no ambiente do PHP/Apache; no XAMPP, podem ser adicionadas como `SetEnv NOME "valor"` na configuração Apache do projecto e aplicadas após reiniciar o serviço:
  - `SITE_URL`: URL público base do website, por exemplo `https://exemplo.co.mz`.
  - `SMTP_HOST`, `SMTP_PORT`, `SMTP_USERNAME`, `SMTP_PASSWORD`: dados do serviço SMTP. Use `465` para SMTPS ou `587` para STARTTLS.
  - `SMTP_FROM_EMAIL`, `SMTP_FROM_NAME`: endereço e nome remetentes aprovados pelo serviço de e-mail.
  - `VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY`, `VAPID_SUBJECT`: chaves Web Push e contacto do responsável, por exemplo `mailto:privacidade@exemplo.co.mz`.
- Para gerar as chaves VAPID após `composer install` no XAMPP, execute no PowerShell: `$env:OPENSSL_CONF='C:\xampp\apache\conf\openssl.cnf'` e depois `C:\xampp\php\php.exe -r "require 'vendor/autoload.php'; var_export(\Minishlink\WebPush\VAPID::createVapidKeys());"`. Guarde a chave pública e a privada apenas nas variáveis do servidor. Nunca publique a chave privada no JavaScript ou no repositório.
- Web Push requer HTTPS em produção (localhost é aceite para desenvolvimento). O pedido de permissão do navegador só aparece depois de o visitante activar esse canal.
- O envio ocorre quando o administrador publica um artigo. Se um envio falhar, o painel informa que o artigo foi publicado e que há notificações por verificar; consulte os registos do servidor e confirme a configuração SMTP/VAPID.

## Personalização necessária
- Nome institucional definido como “Moz Construções”.
- Actualize telefone, e-mail, endereço e número de WhatsApp na secção “Conteúdos e contactos” do painel administrativo.
- Substitua imagens demonstrativas e projectos de exemplo por fotografias autorizadas da empresa.
- Actualize os textos institucionais.

## Notas
- O contador é aproximado: usa uma chave derivada do IP e do navegador e conta uma visita por dia. Reveja a política de privacidade e a base legal antes de publicar.
- A integração WhatsApp abre uma conversa pré-preenchida. Não é um chatbot de IA nem utiliza a API oficial WhatsApp Business.
- Configure HTTPS, credenciais fortes, backups e permissões adequadas antes de alojar publicamente.
- Este pacote é uma base funcional inicial; não inclui gestão de utilizadores, recuperação de palavra-passe, nem registo de auditoria das alterações administrativas. A segurança de uploads via `.htaccess` deve ser complementada com configuração equivalente se o servidor usar Nginx.


## Galeria de obras em andamento

Para activar a galeria de fotografias por projecto, importe `database/upgrade_project_gallery.sql` na base de dados `obraforte_db` (phpMyAdmin → seleccionar a base de dados → Importar). Esta migração cria a tabela `project_images` sem apagar os projectos existentes.

No painel administrativo, abra **Projectos**. Ao criar ou editar um projecto, seleccione o estado **Em andamento** e envie várias fotografias no campo de galeria (JPG, PNG ou WEBP; até 10 imagens por envio e 5 MB por imagem). As fotografias serão apresentadas na página inicial, agrupadas por projecto, na secção **Obras em andamento**. Projectos sem galeria cadastrada recebem quatro imagens ilustrativas locais provenientes do Unsplash, identificadas como ilustrativas; no projecto de demonstração, elas também aparecem quando existe apenas uma fotografia. Ao cadastrar uma galeria real com várias fotos, as imagens ilustrativas deixam de ser mostradas. Clique numa fotografia para a ampliar numa galeria navegável; use as setas do teclado para avançar ou recuar e `Esc` para fechar.

## Feed, visualizações e reacções do Blog

Depois de importar `database/upgrade_blog_engagement.sql`, o Blog apresenta um feed editorial com notícias em destaque, slider automático, grelha de artigos, categorias e conteúdos mais lidos. Na página de cada artigo, as visualizações são contadas uma única vez por visitante e artigo. Os leitores podem escolher uma reacção (Gosto, Adorei, Útil ou Interessante); uma nova escolha substitui a anterior e clicar novamente na mesma reacção remove-a. O identificador do visitante é aleatório, guardado num cookie funcional de primeira parte; apenas o seu hash é armazenado na base de dados. A contagem de visualizações representa visitantes únicos por artigo, não cada recarregamento.

## Actualização visual e boas práticas do Blog

O Blog mantém a estrutura editorial do primeiro layout (cabeçalho, destaques, slider, grelha de notícias e barra lateral), agora com identidade visual reforçada, navegação mais clara, chamadas de acção e botões criativos de regresso ao website institucional. Foram acrescentados atalhos de partilha para WhatsApp, Facebook, LinkedIn e X. Estes são links de partilha de conteúdo e não representam páginas oficiais da empresa; os perfis institucionais devem ser configurados quando os respectivos endereços forem disponibilizados.

A implementação segue boas práticas técnicas para websites e publicações digitais:
- HTML semântico, hierarquia de títulos e navegação identificável;
- layout responsivo para computadores, tablets e telemóveis;
- estados de foco visíveis para navegação por teclado e ligação para saltar directamente ao conteúdo;
- respeito pela preferência do sistema por movimento reduzido e controlo para pausar o slider automático;
- imagens com texto alternativo e carregamento diferido nas imagens dos cartões;
- metadados de descrição e Open Graph para partilha dos artigos;
- links externos de partilha com `rel="noopener noreferrer"`;
- manutenção do aviso de cookies e das preferências de privacidade já existentes.

Estas medidas são alinhadas com princípios de acessibilidade (incluindo WCAG), usabilidade, SEO e privacidade. Não constituem, por si só, certificação de conformidade legal ou auditoria WCAG; antes da publicação, devem ser confirmados os dados reais da empresa, a política de privacidade, os perfis sociais e os requisitos legais aplicáveis em Moçambique.
