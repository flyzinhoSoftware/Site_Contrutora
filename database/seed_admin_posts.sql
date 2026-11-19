-- Moz Construcoes: administrador inicial e 10 artigos demonstrativos do Blog
-- Base de dados: obraforte_db
-- Execute depois de schema.sql, upgrade_admin.sql e upgrade_blog.sql.
-- Palavra-passe temporaria: Mudar@Moz2026! (altere-a imediatamente apos o primeiro acesso).

USE obraforte_db;

-- Administrador inicial. Se o email ja existir, nao substitui a palavra-passe actual.
INSERT INTO admins (name, email, password)
VALUES (
  'Administrador Moz Construcoes',
  'admin@mozconstrucoes.co.mz',
  '$2y$12$J9J/W7qEY86/j.ttiwldQOnXeM9u0wgrhqVcpEu5dNt3litUW4xIy'
)
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Categorias adicionais; a migracao do Blog ja cria as tres categorias base.
INSERT INTO blog_categories (name, slug) VALUES
 ('Materiais e qualidade', 'materiais-e-qualidade'),
 ('Obras e infra-estruturas', 'obras-e-infraestruturas'),
 ('Manutencao e sustentabilidade', 'manutencao-e-sustentabilidade')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Artigo 1
INSERT INTO blog_posts (category_id, author_id, title, slug, excerpt, content, status, published_at)
SELECT c.id, a.id,
 'Como preparar uma obra antes de iniciar a construção',
 'preparar-obra-antes-iniciar-construcao',
 'Um bom resultado começa muito antes da primeira actividade no terreno. Conheça os passos essenciais de preparação de uma obra em Moçambique.',
 '<p>Iniciar uma construção sem preparação suficiente pode provocar atrasos, custos inesperados e alterações ao projecto. Antes de mobilizar trabalhadores e materiais, é importante organizar a informação técnica, administrativa e financeira da obra.</p><h2>1. Definir as necessidades</h2><p>O primeiro passo consiste em esclarecer a finalidade do edifício, o número de utilizadores, os espaços necessários e as condições de utilização. Estas informações ajudam a orientar o projecto de arquitectura e as especialidades.</p><h2>2. Conhecer o terreno</h2><p>Deve ser feita uma avaliação do terreno, dos acessos, do relevo, das condições de drenagem e das características do solo. Quando necessário, um técnico habilitado deverá recomendar estudos geotécnicos e levantamentos topográficos.</p><h2>3. Preparar os projectos e autorizações</h2><p>Os desenhos e peças técnicas devem ser preparados por profissionais habilitados e submetidos às entidades competentes, de acordo com os procedimentos aplicáveis ao local. Não se deve iniciar a obra sem confirmar as licenças e aprovações necessárias.</p><h2>4. Planear custos e prazos</h2><p>O orçamento deve considerar materiais, mão-de-obra, transporte, equipamentos, segurança, fiscalização e uma reserva para imprevistos. O cronograma deve reflectir a sequência realista das actividades.</p><h2>5. Organizar a execução</h2><p>Antes do arranque, devem estar definidos os responsáveis, os meios de comunicação, o armazenamento de materiais, o acesso ao estaleiro e as medidas de segurança.</p><p><strong>Conclusão:</strong> preparar bem uma obra permite tomar decisões com antecedência e acompanhar melhor a qualidade, o prazo e o custo. Cada projecto deve ser validado por técnicos competentes e pelas autoridades aplicáveis.</p>',
 'published', NOW() - INTERVAL 10 DAY
FROM blog_categories c JOIN admins a ON a.email='admin@mozconstrucoes.co.mz'
WHERE c.slug='construcao-civil' AND NOT EXISTS (SELECT 1 FROM blog_posts p WHERE p.slug='preparar-obra-antes-iniciar-construcao');

-- Artigo 2
INSERT INTO blog_posts (category_id, author_id, title, slug, excerpt, content, status, published_at)
SELECT c.id, a.id,
 'Escolha de materiais de construção: qualidade antes do preço',
 'escolha-materiais-construcao-qualidade-preco',
 'Cimento, aço, blocos e agregados devem ser seleccionados de acordo com a sua aplicação, origem e conformidade técnica.',
 '<p>O preço é um factor importante na compra de materiais, mas não deve ser o único critério. Um material inadequado pode comprometer o desempenho da construção e aumentar os custos de reparação.</p><h2>Verificar a origem e a identificação</h2><p>Sempre que possível, compre a fornecedores identificáveis e solicite informação técnica, comprovativos de origem e documentação de conformidade aplicável. Observe a embalagem, o estado de conservação e as recomendações do fabricante.</p><h2>Armazenar correctamente</h2><p>O cimento deve ser protegido da humidade e colocado sobre estrados, em local coberto e ventilado. O aço deve ser organizado de forma a evitar contaminação e condições que acelerem a corrosão. Os agregados devem ser separados para reduzir a mistura com terra e resíduos.</p><h2>Comprar de acordo com o projecto</h2><p>As dimensões, classes e características dos materiais devem corresponder às especificações do projecto e às indicações do técnico responsável. Não substitua materiais estruturais por alternativas apenas com base no preço.</p><h2>Controlar a recepção</h2><p>Na entrega, confira quantidades, estado físico e documentação. Materiais danificados, húmidos ou sem identificação suficiente devem ser avaliados antes da utilização.</p><p><strong>Conclusão:</strong> a escolha correcta combina adequação técnica, qualidade, disponibilidade e custo total. Em caso de dúvida, consulte o projectista ou a fiscalização.</p>',
 'published', NOW() - INTERVAL 9 DAY
FROM blog_categories c JOIN admins a ON a.email='admin@mozconstrucoes.co.mz'
WHERE c.slug='materiais-e-qualidade' AND NOT EXISTS (SELECT 1 FROM blog_posts p WHERE p.slug='escolha-materiais-construcao-qualidade-preco');

-- Artigo 3
INSERT INTO blog_posts (category_id, author_id, title, slug, excerpt, content, status, published_at)
SELECT c.id, a.id,
 'Construir em zonas propensas a cheias: o que deve ser avaliado',
 'construir-zonas-propensas-cheias-avaliacoes',
 'A implantação, a drenagem e o conhecimento do terreno são factores essenciais em locais sujeitos a inundações.',
 '<p>Em diferentes regiões de Moçambique, a época chuvosa pode trazer precipitação intensa, subida dos níveis de água e alagamentos. Por isso, a análise do risco deve fazer parte da preparação de qualquer projecto situado numa zona vulnerável.</p><h2>Conhecer o histórico do local</h2><p>É importante recolher informação sobre inundações anteriores, caminhos naturais da água, proximidade de rios e valas, níveis do terreno e condições de acesso durante as chuvas.</p><h2>Estudar a implantação</h2><p>A posição e a cota da construção devem resultar de avaliação técnica. A solução não deve bloquear linhas naturais de escoamento nem transferir o problema de drenagem para os terrenos vizinhos.</p><h2>Projectar a drenagem</h2><p>O sistema de drenagem deve ser dimensionado para as condições locais e mantido livre de resíduos. Canais, caleiras, tubos de queda e pontos de descarga precisam de acessibilidade para inspecção e limpeza.</p><h2>Escolher soluções adequadas</h2><p>Materiais, fundações e detalhes construtivos devem ser definidos por profissionais habilitados, considerando o solo, a água e as solicitações previstas. Não existe uma solução única válida para todos os terrenos.</p><p><strong>Conclusão:</strong> construir com atenção ao risco de cheias começa no estudo do local e continua na execução e manutenção. Em áreas de risco elevado, procure orientação técnica e confirme as restrições de ocupação.</p>',
 'published', NOW() - INTERVAL 8 DAY
FROM blog_categories c JOIN admins a ON a.email='admin@mozconstrucoes.co.mz'
WHERE c.slug='engenharia' AND NOT EXISTS (SELECT 1 FROM blog_posts p WHERE p.slug='construir-zonas-propensas-cheias-avaliacoes');

-- Artigo 4
INSERT INTO blog_posts (category_id, author_id, title, slug, excerpt, content, status, published_at)
SELECT c.id, a.id,
 'Drenagem de águas pluviais: um elemento essencial nas construções',
 'drenagem-aguas-pluviais-construcoes',
 'Uma drenagem bem concebida ajuda a proteger edifícios, quintais, vias de acesso e áreas envolventes durante a época chuvosa.',
 '<p>A água da chuva precisa de um percurso seguro desde a cobertura e o terreno até ao ponto de descarga autorizado. Quando esse percurso não é previsto, podem surgir infiltrações, erosão, acumulação de água e danos nos acabamentos.</p><h2>Começar pela cobertura</h2><p>As coberturas devem encaminhar a água para caleiras e tubos de queda adequados. As ligações precisam de ser verificadas para evitar fugas junto às paredes.</p><h2>Dar atenção ao terreno</h2><p>As pendentes exteriores devem afastar a água das fundações e conduzi-la para sistemas de recolha. A solução deve respeitar os níveis do lote e não prejudicar propriedades vizinhas.</p><h2>Dimensionar e inspeccionar</h2><p>O dimensionamento depende da área contribuinte, intensidade de chuva, declives e capacidade do sistema receptor. Caixas de visita e grelhas devem permitir limpeza e inspecção periódica.</p><h2>Evitar obstruções</h2><p>Folhas, sacos plásticos, areia e resíduos reduzem a capacidade de escoamento. A limpeza antes e durante a época chuvosa ajuda a prevenir problemas.</p><p><strong>Conclusão:</strong> a drenagem deve ser considerada desde o projecto, executada com controlo técnico e mantida ao longo da vida útil do edifício.</p>',
 'published', NOW() - INTERVAL 7 DAY
FROM blog_categories c JOIN admins a ON a.email='admin@mozconstrucoes.co.mz'
WHERE c.slug='obras-e-infraestruturas' AND NOT EXISTS (SELECT 1 FROM blog_posts p WHERE p.slug='drenagem-aguas-pluviais-construcoes');

-- Artigo 5
INSERT INTO blog_posts (category_id, author_id, title, slug, excerpt, content, status, published_at)
SELECT c.id, a.id,
 'Fiscalização de obras: como acompanhar qualidade, custos e prazos',
 'fiscalizacao-obras-qualidade-custos-prazos',
 'A fiscalização ajuda a verificar se os trabalhos correspondem ao projecto, às especificações e às condições acordadas.',
 '<p>A fiscalização não deve ser vista apenas como uma visita ao estaleiro. É um processo organizado de verificação, registo e comunicação durante as diferentes fases da construção.</p><h2>Conferir os documentos</h2><p>O acompanhamento começa pela consulta dos desenhos aprovados, especificações, medições, cronograma e condições contratuais. A equipa deve trabalhar com versões actualizadas dos documentos.</p><h2>Inspeccionar etapas importantes</h2><p>Implantação, fundações, armaduras, betonagens, instalações embutidas, impermeabilização e acabamentos são exemplos de fases que podem exigir verificação antes de serem cobertas ou prosseguirem.</p><h2>Registar ocorrências</h2><p>As observações devem ser registadas com data, localização, descrição e indicação da medida correctiva. Fotografias e relatórios ajudam a manter um histórico claro.</p><h2>Controlar alterações</h2><p>Qualquer alteração ao projecto, material ou método de execução deve seguir o procedimento de aprovação aplicável. Decisões verbais não devem substituir os registos formais necessários.</p><p><strong>Conclusão:</strong> uma fiscalização organizada melhora a comunicação entre dono da obra, empreiteiro e técnicos, contribuindo para reduzir erros e assegurar a conformidade.</p>',
 'published', NOW() - INTERVAL 6 DAY
FROM blog_categories c JOIN admins a ON a.email='admin@mozconstrucoes.co.mz'
WHERE c.slug='engenharia' AND NOT EXISTS (SELECT 1 FROM blog_posts p WHERE p.slug='fiscalizacao-obras-qualidade-custos-prazos');

-- Artigo 6
INSERT INTO blog_posts (category_id, author_id, title, slug, excerpt, content, status, published_at)
SELECT c.id, a.id,
 'Betão em obra: cuidados essenciais durante a execução',
 'betao-obra-cuidados-execucao',
 'A qualidade do betão depende dos materiais, da preparação, da colocação, da compactação e da cura, sempre segundo as especificações.',
 '<p>O betão é utilizado em diversos elementos estruturais e exige controlo em todas as fases. A aparência da mistura, por si só, não permite confirmar a sua resistência ou adequação.</p><h2>Preparar antes da betonagem</h2><p>As cofragens, armaduras, espaçadores e elementos embutidos devem ser verificados antes da colocação. A equipa deve confirmar acessos, equipamentos e sequência de trabalho.</p><h2>Respeitar a composição especificada</h2><p>A dosagem e a relação água/cimento devem obedecer ao projecto e às orientações técnicas. Adicionar água sem controlo para facilitar a colocação pode prejudicar o desempenho do betão.</p><h2>Colocar e compactar correctamente</h2><p>A colocação deve evitar segregação e vazios. Os métodos de compactação devem ser adequados ao elemento e executados por trabalhadores preparados.</p><h2>Garantir a cura</h2><p>A cura ajuda a controlar a perda de humidade e favorece o desenvolvimento das propriedades do betão. O método e a duração devem seguir as especificações técnicas e as condições ambientais.</p><h2>Controlar e registar</h2><p>Quando previsto, devem ser realizados ensaios e recolhidas amostras por procedimentos adequados. Registe datas, localização, fornecimento e resultados.</p><p><strong>Conclusão:</strong> a execução do betão deve ser acompanhada por responsável técnico. Este artigo é informativo e não substitui o projecto estrutural nem as especificações da obra.</p>',
 'published', NOW() - INTERVAL 5 DAY
FROM blog_categories c JOIN admins a ON a.email='admin@mozconstrucoes.co.mz'
WHERE c.slug='materiais-e-qualidade' AND NOT EXISTS (SELECT 1 FROM blog_posts p WHERE p.slug='betao-obra-cuidados-execucao');

-- Artigo 7
INSERT INTO blog_posts (category_id, author_id, title, slug, excerpt, content, status, published_at)
SELECT c.id, a.id,
 'Como reduzir desperdícios de materiais no estaleiro',
 'reduzir-desperdicios-materiais-estaleiro',
 'Planeamento de compras, armazenamento e controlo de quantidades ajudam a reduzir perdas e melhorar a organização da obra.',
 '<p>O desperdício de materiais afecta o orçamento, a limpeza do estaleiro e a eficiência da equipa. Muitas perdas podem ser reduzidas com procedimentos simples e consistentes.</p><h2>Planear as quantidades</h2><p>As compras devem partir de medições e mapas de quantidades actualizados. É importante comparar o material previsto com o efectivamente recebido e aplicado.</p><h2>Organizar o armazenamento</h2><p>Defina áreas próprias para cimento, aço, madeira, cerâmicos e agregados. Proteja os materiais sensíveis à água, ao sol e à contaminação.</p><h2>Controlar entradas e saídas</h2><p>Registe fornecedores, quantidades, datas e responsáveis pela recepção. A distribuição aos sectores de trabalho deve ser acompanhada para evitar perdas e compras duplicadas.</p><h2>Reaproveitar com critério</h2><p>Alguns materiais podem ser reaproveitados quando isso é tecnicamente permitido e não compromete a qualidade. Elementos estruturais ou materiais com desempenho duvidoso nunca devem ser reutilizados sem avaliação técnica.</p><h2>Separar resíduos</h2><p>Organize a recolha de embalagens, madeira, metal, entulho e resíduos comuns, encaminhando-os de forma adequada.</p><p><strong>Conclusão:</strong> reduzir desperdícios exige planeamento, disciplina e participação de toda a equipa. O objectivo é utilizar os recursos de forma responsável sem reduzir a qualidade da construção.</p>',
 'published', NOW() - INTERVAL 4 DAY
FROM blog_categories c JOIN admins a ON a.email='admin@mozconstrucoes.co.mz'
WHERE c.slug='manutencao-e-sustentabilidade' AND NOT EXISTS (SELECT 1 FROM blog_posts p WHERE p.slug='reduzir-desperdicios-materiais-estaleiro');

-- Artigo 8
INSERT INTO blog_posts (category_id, author_id, title, slug, excerpt, content, status, published_at)
SELECT c.id, a.id,
 'Manutenção preventiva: como prolongar a vida útil de um edifício',
 'manutencao-preventiva-vida-util-edificio',
 'Inspecções regulares e pequenas intervenções ajudam a identificar problemas antes de se transformarem em reparações dispendiosas.',
 '<p>A entrega de um edifício não encerra as responsabilidades de conservação. A exposição ao sol, chuva, humidade, utilização diária e falta de limpeza provocam desgaste ao longo do tempo.</p><h2>Inspeccionar a cobertura</h2><p>Verifique telhas, chapas, fixações, caleiras, rufos e pontos de entrada de água, especialmente antes da época chuvosa.</p><h2>Observar paredes e tectos</h2><p>Manchas de humidade, fissuras, descascamento de pintura e sinais de infiltração devem ser registados e avaliados. Nem toda fissura tem a mesma origem ou gravidade.</p><h2>Manter instalações</h2><p>As redes eléctricas, canalizações, equipamentos sanitários e sistemas de bombagem devem ser inspeccionados por profissionais qualificados, respeitando as recomendações dos fabricantes.</p><h2>Criar um plano</h2><p>Um plano simples pode indicar o elemento, frequência de inspecção, responsável, ocorrência encontrada e intervenção realizada. Guarde facturas, garantias e desenhos finais.</p><p><strong>Conclusão:</strong> a manutenção preventiva melhora a conservação do edifício e permite programar intervenções. Problemas estruturais ou eléctricos devem ser avaliados por profissionais habilitados.</p>',
 'published', NOW() - INTERVAL 3 DAY
FROM blog_categories c JOIN admins a ON a.email='admin@mozconstrucoes.co.mz'
WHERE c.slug='manutencao-e-sustentabilidade' AND NOT EXISTS (SELECT 1 FROM blog_posts p WHERE p.slug='manutencao-preventiva-vida-util-edificio');

-- Artigo 9
INSERT INTO blog_posts (category_id, author_id, title, slug, excerpt, content, status, published_at)
SELECT c.id, a.id,
 'Segurança no estaleiro: responsabilidades de toda a equipa',
 'seguranca-estaleiro-responsabilidade-equipa',
 'A prevenção de acidentes depende de organização, formação, equipamentos adequados e supervisão contínua.',
 '<p>Um estaleiro organizado reduz a exposição a riscos e contribui para um ambiente de trabalho mais produtivo. A segurança deve ser considerada desde a preparação do local até à conclusão dos trabalhos.</p><h2>Identificar os riscos</h2><p>Antes de cada actividade, a equipa deve reconhecer os perigos associados ao local, aos equipamentos, à circulação e à tarefa. As medidas preventivas devem ser definidas por responsáveis competentes.</p><h2>Organizar a circulação</h2><p>As zonas de passagem, armazenamento e operação de equipamentos devem estar identificadas e, sempre que necessário, separadas. O acesso de visitantes deve ser controlado.</p><h2>Usar protecção adequada</h2><p>Os equipamentos de protecção individual devem ser seleccionados conforme os riscos e utilizados de acordo com a formação recebida. Capacetes, calçado, luvas, protecção ocular e outros equipamentos não substituem medidas de protecção colectiva.</p><h2>Manter o local organizado</h2><p>Cabos, ferramentas, materiais e resíduos não devem obstruir passagens. As escavações, aberturas e zonas de risco devem ter protecção e sinalização adequadas.</p><h2>Comunicar incidentes</h2><p>Condições inseguras, quase-acidentes e acidentes devem ser comunicados e analisados para corrigir as causas e prevenir repetição.</p><p><strong>Conclusão:</strong> a segurança é uma responsabilidade partilhada entre empregador, supervisão e trabalhadores, respeitando a legislação e os procedimentos aplicáveis.</p>',
 'published', NOW() - INTERVAL 2 DAY
FROM blog_categories c JOIN admins a ON a.email='admin@mozconstrucoes.co.mz'
WHERE c.slug='construcao-civil' AND NOT EXISTS (SELECT 1 FROM blog_posts p WHERE p.slug='seguranca-estaleiro-responsabilidade-equipa');

-- Artigo 10
INSERT INTO blog_posts (category_id, author_id, title, slug, excerpt, content, status, published_at)
SELECT c.id, a.id,
 'Construção sustentável em Moçambique: decisões práticas para cada projecto',
 'construcao-sustentavel-mocambique-decisoes-praticas',
 'Soluções adaptadas ao clima, gestão eficiente da água e energia e escolha responsável de materiais podem melhorar o desempenho dos edifícios.',
 '<p>Construir de forma sustentável não significa aplicar uma solução cara ou igual em todos os locais. Significa considerar o clima, os recursos disponíveis, o uso do edifício e os impactos ao longo do seu ciclo de vida.</p><h2>Projectar de acordo com o clima</h2><p>A orientação do edifício, a ventilação natural, o sombreamento e a protecção contra chuva devem ser analisados desde a concepção. As soluções dependem da localização e da finalidade da construção.</p><h2>Usar água com responsabilidade</h2><p>Instalações eficientes, reparação de fugas e aproveitamento de água pluvial quando tecnicamente adequado podem reduzir o consumo de água potável. A qualidade e o uso da água armazenada devem ser avaliados.</p><h2>Melhorar o desempenho energético</h2><p>A iluminação eficiente, ventilação adequada e selecção criteriosa de equipamentos ajudam a reduzir necessidades energéticas. O conforto térmico deve ser considerado em conjunto com a arquitectura.</p><h2>Escolher materiais com critério</h2><p>Considere durabilidade, disponibilidade local, manutenção, transporte e desempenho técnico. A origem local pode ser uma vantagem, desde que o material cumpra os requisitos do projecto.</p><h2>Planear a manutenção</h2><p>Uma solução sustentável deve ser possível de inspeccionar, reparar e manter com recursos acessíveis ao longo do tempo.</p><p><strong>Conclusão:</strong> a construção sustentável resulta de decisões integradas de arquitectura, engenharia, execução e utilização. Cada solução deve ser dimensionada e validada por profissionais competentes.</p>',
 'published', NOW() - INTERVAL 1 DAY
FROM blog_categories c JOIN admins a ON a.email='admin@mozconstrucoes.co.mz'
WHERE c.slug='dicas-e-projectos' AND NOT EXISTS (SELECT 1 FROM blog_posts p WHERE p.slug='construcao-sustentavel-mocambique-decisoes-praticas');

-- Fim: o script pode ser executado novamente sem duplicar o administrador nem os artigos.
