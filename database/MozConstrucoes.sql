-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 15, 2026 at 06:08 PM
-- Server version: 10.11.16-MariaDB
-- PHP Version: 8.5.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `obraforte_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `name` varchar(120) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `name`, `email`, `password`, `created_at`) VALUES
(1, 'Administrador', 'admin@horizonte.co.mz', '$2y$12$fOMYbU1pIDS7PQW9a6O2beNZ4Fa6/MglJcfHXozQrpkm029E1kRg2', '2026-11-15 15:40:02'),
(2, 'Administrador Moz Construcoes', 'admin@mozconstrucoes.co.mz', '$2y$12$J9J/W7qEY86/j.ttiwldQOnXeM9u0wgrhqVcpEu5dNt3litUW4xIy', '2026-11-15 15:51:47');

-- --------------------------------------------------------

--
-- Table structure for table `blog_categories`
--

CREATE TABLE `blog_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(120) NOT NULL,
  `slug` varchar(150) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `blog_categories`
--

INSERT INTO `blog_categories` (`id`, `name`, `slug`, `created_at`) VALUES
(1, 'Construção civil', 'construcao-civil', '2026-11-15 15:30:50'),
(2, 'Engenharia', 'engenharia', '2026-11-15 15:30:50'),
(3, 'Dicas e projectos', 'dicas-e-projectos', '2026-11-15 15:30:50'),
(10, 'Materiais e qualidade', 'materiais-e-qualidade', '2026-11-15 15:51:47'),
(11, 'Obras e infra-estruturas', 'obras-e-infraestruturas', '2026-11-15 15:51:47'),
(12, 'Manutencao e sustentabilidade', 'manutencao-e-sustentabilidade', '2026-11-15 15:51:47');

-- --------------------------------------------------------

--
-- Table structure for table `blog_comments`
--

CREATE TABLE `blog_comments` (
  `id` bigint(20) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `comment` text NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blog_posts`
--

CREATE TABLE `blog_posts` (
  `id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `author_id` int(11) DEFAULT NULL,
  `title` varchar(220) NOT NULL,
  `slug` varchar(240) NOT NULL,
  `excerpt` varchar(500) NOT NULL,
  `content` longtext NOT NULL,
  `cover_image` varchar(500) DEFAULT NULL,
  `status` enum('draft','published') NOT NULL DEFAULT 'draft',
  `published_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `blog_posts`
--

INSERT INTO `blog_posts` (`id`, `category_id`, `author_id`, `title`, `slug`, `excerpt`, `content`, `cover_image`, `status`, `published_at`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 'Como preparar uma obra antes de iniciar a construção', 'preparar-obra-antes-iniciar-construcao', 'Um bom resultado começa muito antes da primeira actividade no terreno. Conheça os passos essenciais de preparação de uma obra em Moçambique.', '<p>Iniciar uma construção sem preparação suficiente pode provocar atrasos, custos inesperados e alterações ao projecto. Antes de mobilizar trabalhadores e materiais, é importante organizar a informação técnica, administrativa e financeira da obra.</p><h2>1. Definir as necessidades</h2><p>O primeiro passo consiste em esclarecer a finalidade do edifício, o número de utilizadores, os espaços necessários e as condições de utilização. Estas informações ajudam a orientar o projecto de arquitectura e as especialidades.</p><h2>2. Conhecer o terreno</h2><p>Deve ser feita uma avaliação do terreno, dos acessos, do relevo, das condições de drenagem e das características do solo. Quando necessário, um técnico habilitado deverá recomendar estudos geotécnicos e levantamentos topográficos.</p><h2>3. Preparar os projectos e autorizações</h2><p>Os desenhos e peças técnicas devem ser preparados por profissionais habilitados e submetidos às entidades competentes, de acordo com os procedimentos aplicáveis ao local. Não se deve iniciar a obra sem confirmar as licenças e aprovações necessárias.</p><h2>4. Planear custos e prazos</h2><p>O orçamento deve considerar materiais, mão-de-obra, transporte, equipamentos, segurança, fiscalização e uma reserva para imprevistos. O cronograma deve reflectir a sequência realista das actividades.</p><h2>5. Organizar a execução</h2><p>Antes do arranque, devem estar definidos os responsáveis, os meios de comunicação, o armazenamento de materiais, o acesso ao estaleiro e as medidas de segurança.</p><p><strong>Conclusão:</strong> preparar bem uma obra permite tomar decisões com antecedência e acompanhar melhor a qualidade, o prazo e o custo. Cada projecto deve ser validado por técnicos competentes e pelas autoridades aplicáveis.</p>', NULL, 'published', '2026-11-05 17:51:48', '2026-11-15 15:51:48', '2026-11-15 15:51:48'),
(2, 10, 2, 'Escolha de materiais de construção: qualidade antes do preço', 'escolha-materiais-construcao-qualidade-preco', 'Cimento, aço, blocos e agregados devem ser seleccionados de acordo com a sua aplicação, origem e conformidade técnica.', '<p>O preço é um factor importante na compra de materiais, mas não deve ser o único critério. Um material inadequado pode comprometer o desempenho da construção e aumentar os custos de reparação.</p><h2>Verificar a origem e a identificação</h2><p>Sempre que possível, compre a fornecedores identificáveis e solicite informação técnica, comprovativos de origem e documentação de conformidade aplicável. Observe a embalagem, o estado de conservação e as recomendações do fabricante.</p><h2>Armazenar correctamente</h2><p>O cimento deve ser protegido da humidade e colocado sobre estrados, em local coberto e ventilado. O aço deve ser organizado de forma a evitar contaminação e condições que acelerem a corrosão. Os agregados devem ser separados para reduzir a mistura com terra e resíduos.</p><h2>Comprar de acordo com o projecto</h2><p>As dimensões, classes e características dos materiais devem corresponder às especificações do projecto e às indicações do técnico responsável. Não substitua materiais estruturais por alternativas apenas com base no preço.</p><h2>Controlar a recepção</h2><p>Na entrega, confira quantidades, estado físico e documentação. Materiais danificados, húmidos ou sem identificação suficiente devem ser avaliados antes da utilização.</p><p><strong>Conclusão:</strong> a escolha correcta combina adequação técnica, qualidade, disponibilidade e custo total. Em caso de dúvida, consulte o projectista ou a fiscalização.</p>', NULL, 'published', '2026-11-06 17:51:48', '2026-11-15 15:51:48', '2026-11-15 15:51:48'),
(3, 2, 2, 'Construir em zonas propensas a cheias: o que deve ser avaliado', 'construir-zonas-propensas-cheias-avaliacoes', 'A implantação, a drenagem e o conhecimento do terreno são factores essenciais em locais sujeitos a inundações.', '<p>Em diferentes regiões de Moçambique, a época chuvosa pode trazer precipitação intensa, subida dos níveis de água e alagamentos. Por isso, a análise do risco deve fazer parte da preparação de qualquer projecto situado numa zona vulnerável.</p><h2>Conhecer o histórico do local</h2><p>É importante recolher informação sobre inundações anteriores, caminhos naturais da água, proximidade de rios e valas, níveis do terreno e condições de acesso durante as chuvas.</p><h2>Estudar a implantação</h2><p>A posição e a cota da construção devem resultar de avaliação técnica. A solução não deve bloquear linhas naturais de escoamento nem transferir o problema de drenagem para os terrenos vizinhos.</p><h2>Projectar a drenagem</h2><p>O sistema de drenagem deve ser dimensionado para as condições locais e mantido livre de resíduos. Canais, caleiras, tubos de queda e pontos de descarga precisam de acessibilidade para inspecção e limpeza.</p><h2>Escolher soluções adequadas</h2><p>Materiais, fundações e detalhes construtivos devem ser definidos por profissionais habilitados, considerando o solo, a água e as solicitações previstas. Não existe uma solução única válida para todos os terrenos.</p><p><strong>Conclusão:</strong> construir com atenção ao risco de cheias começa no estudo do local e continua na execução e manutenção. Em áreas de risco elevado, procure orientação técnica e confirme as restrições de ocupação.</p>', NULL, 'published', '2026-11-07 17:51:48', '2026-11-15 15:51:48', '2026-11-15 15:51:48'),
(4, 11, 2, 'Drenagem de águas pluviais: um elemento essencial nas construções', 'drenagem-aguas-pluviais-construcoes', 'Uma drenagem bem concebida ajuda a proteger edifícios, quintais, vias de acesso e áreas envolventes durante a época chuvosa.', '<p>A água da chuva precisa de um percurso seguro desde a cobertura e o terreno até ao ponto de descarga autorizado. Quando esse percurso não é previsto, podem surgir infiltrações, erosão, acumulação de água e danos nos acabamentos.</p><h2>Começar pela cobertura</h2><p>As coberturas devem encaminhar a água para caleiras e tubos de queda adequados. As ligações precisam de ser verificadas para evitar fugas junto às paredes.</p><h2>Dar atenção ao terreno</h2><p>As pendentes exteriores devem afastar a água das fundações e conduzi-la para sistemas de recolha. A solução deve respeitar os níveis do lote e não prejudicar propriedades vizinhas.</p><h2>Dimensionar e inspeccionar</h2><p>O dimensionamento depende da área contribuinte, intensidade de chuva, declives e capacidade do sistema receptor. Caixas de visita e grelhas devem permitir limpeza e inspecção periódica.</p><h2>Evitar obstruções</h2><p>Folhas, sacos plásticos, areia e resíduos reduzem a capacidade de escoamento. A limpeza antes e durante a época chuvosa ajuda a prevenir problemas.</p><p><strong>Conclusão:</strong> a drenagem deve ser considerada desde o projecto, executada com controlo técnico e mantida ao longo da vida útil do edifício.</p>', NULL, 'published', '2026-11-08 17:51:48', '2026-11-15 15:51:48', '2026-11-15 15:51:48'),
(5, 2, 2, 'Fiscalização de obras: como acompanhar qualidade, custos e prazos', 'fiscalizacao-obras-qualidade-custos-prazos', 'A fiscalização ajuda a verificar se os trabalhos correspondem ao projecto, às especificações e às condições acordadas.', '<p>A fiscalização não deve ser vista apenas como uma visita ao estaleiro. É um processo organizado de verificação, registo e comunicação durante as diferentes fases da construção.</p><h2>Conferir os documentos</h2><p>O acompanhamento começa pela consulta dos desenhos aprovados, especificações, medições, cronograma e condições contratuais. A equipa deve trabalhar com versões actualizadas dos documentos.</p><h2>Inspeccionar etapas importantes</h2><p>Implantação, fundações, armaduras, betonagens, instalações embutidas, impermeabilização e acabamentos são exemplos de fases que podem exigir verificação antes de serem cobertas ou prosseguirem.</p><h2>Registar ocorrências</h2><p>As observações devem ser registadas com data, localização, descrição e indicação da medida correctiva. Fotografias e relatórios ajudam a manter um histórico claro.</p><h2>Controlar alterações</h2><p>Qualquer alteração ao projecto, material ou método de execução deve seguir o procedimento de aprovação aplicável. Decisões verbais não devem substituir os registos formais necessários.</p><p><strong>Conclusão:</strong> uma fiscalização organizada melhora a comunicação entre dono da obra, empreiteiro e técnicos, contribuindo para reduzir erros e assegurar a conformidade.</p>', NULL, 'published', '2026-11-09 17:51:48', '2026-11-15 15:51:48', '2026-11-15 15:51:48'),
(6, 10, 2, 'Betão em obra: cuidados essenciais durante a execução', 'betao-obra-cuidados-execucao', 'A qualidade do betão depende dos materiais, da preparação, da colocação, da compactação e da cura, sempre segundo as especificações.', '<p>O betão é utilizado em diversos elementos estruturais e exige controlo em todas as fases. A aparência da mistura, por si só, não permite confirmar a sua resistência ou adequação.</p><h2>Preparar antes da betonagem</h2><p>As cofragens, armaduras, espaçadores e elementos embutidos devem ser verificados antes da colocação. A equipa deve confirmar acessos, equipamentos e sequência de trabalho.</p><h2>Respeitar a composição especificada</h2><p>A dosagem e a relação água/cimento devem obedecer ao projecto e às orientações técnicas. Adicionar água sem controlo para facilitar a colocação pode prejudicar o desempenho do betão.</p><h2>Colocar e compactar correctamente</h2><p>A colocação deve evitar segregação e vazios. Os métodos de compactação devem ser adequados ao elemento e executados por trabalhadores preparados.</p><h2>Garantir a cura</h2><p>A cura ajuda a controlar a perda de humidade e favorece o desenvolvimento das propriedades do betão. O método e a duração devem seguir as especificações técnicas e as condições ambientais.</p><h2>Controlar e registar</h2><p>Quando previsto, devem ser realizados ensaios e recolhidas amostras por procedimentos adequados. Registe datas, localização, fornecimento e resultados.</p><p><strong>Conclusão:</strong> a execução do betão deve ser acompanhada por responsável técnico. Este artigo é informativo e não substitui o projecto estrutural nem as especificações da obra.</p>', NULL, 'published', '2026-11-10 17:51:48', '2026-11-15 15:51:48', '2026-11-15 15:51:48'),
(7, 12, 2, 'Como reduzir desperdícios de materiais no estaleiro', 'reduzir-desperdicios-materiais-estaleiro', 'Planeamento de compras, armazenamento e controlo de quantidades ajudam a reduzir perdas e melhorar a organização da obra.', '<p>O desperdício de materiais afecta o orçamento, a limpeza do estaleiro e a eficiência da equipa. Muitas perdas podem ser reduzidas com procedimentos simples e consistentes.</p><h2>Planear as quantidades</h2><p>As compras devem partir de medições e mapas de quantidades actualizados. É importante comparar o material previsto com o efectivamente recebido e aplicado.</p><h2>Organizar o armazenamento</h2><p>Defina áreas próprias para cimento, aço, madeira, cerâmicos e agregados. Proteja os materiais sensíveis à água, ao sol e à contaminação.</p><h2>Controlar entradas e saídas</h2><p>Registe fornecedores, quantidades, datas e responsáveis pela recepção. A distribuição aos sectores de trabalho deve ser acompanhada para evitar perdas e compras duplicadas.</p><h2>Reaproveitar com critério</h2><p>Alguns materiais podem ser reaproveitados quando isso é tecnicamente permitido e não compromete a qualidade. Elementos estruturais ou materiais com desempenho duvidoso nunca devem ser reutilizados sem avaliação técnica.</p><h2>Separar resíduos</h2><p>Organize a recolha de embalagens, madeira, metal, entulho e resíduos comuns, encaminhando-os de forma adequada.</p><p><strong>Conclusão:</strong> reduzir desperdícios exige planeamento, disciplina e participação de toda a equipa. O objectivo é utilizar os recursos de forma responsável sem reduzir a qualidade da construção.</p>', NULL, 'published', '2026-11-11 18:28:20', '2026-11-15 16:28:20', '2026-11-15 16:28:20'),
(8, 12, 2, 'Manutenção preventiva: como prolongar a vida útil de um edifício', 'manutencao-preventiva-vida-util-edificio', 'Inspecções regulares e pequenas intervenções ajudam a identificar problemas antes de se transformarem em reparações dispendiosas.', '<p>A entrega de um edifício não encerra as responsabilidades de conservação. A exposição ao sol, chuva, humidade, utilização diária e falta de limpeza provocam desgaste ao longo do tempo.</p><h2>Inspeccionar a cobertura</h2><p>Verifique telhas, chapas, fixações, caleiras, rufos e pontos de entrada de água, especialmente antes da época chuvosa.</p><h2>Observar paredes e tectos</h2><p>Manchas de humidade, fissuras, descascamento de pintura e sinais de infiltração devem ser registados e avaliados. Nem toda fissura tem a mesma origem ou gravidade.</p><h2>Manter instalações</h2><p>As redes eléctricas, canalizações, equipamentos sanitários e sistemas de bombagem devem ser inspeccionados por profissionais qualificados, respeitando as recomendações dos fabricantes.</p><h2>Criar um plano</h2><p>Um plano simples pode indicar o elemento, frequência de inspecção, responsável, ocorrência encontrada e intervenção realizada. Guarde facturas, garantias e desenhos finais.</p><p><strong>Conclusão:</strong> a manutenção preventiva melhora a conservação do edifício e permite programar intervenções. Problemas estruturais ou eléctricos devem ser avaliados por profissionais habilitados.</p>', NULL, 'published', '2026-11-12 18:28:20', '2026-11-15 16:28:20', '2026-11-15 16:28:20'),
(9, 1, 2, 'Segurança no estaleiro: responsabilidades de toda a equipa', 'seguranca-estaleiro-responsabilidade-equipa', 'A prevenção de acidentes depende de organização, formação, equipamentos adequados e supervisão contínua.', '<p>Um estaleiro organizado reduz a exposição a riscos e contribui para um ambiente de trabalho mais produtivo. A segurança deve ser considerada desde a preparação do local até à conclusão dos trabalhos.</p><h2>Identificar os riscos</h2><p>Antes de cada actividade, a equipa deve reconhecer os perigos associados ao local, aos equipamentos, à circulação e à tarefa. As medidas preventivas devem ser definidas por responsáveis competentes.</p><h2>Organizar a circulação</h2><p>As zonas de passagem, armazenamento e operação de equipamentos devem estar identificadas e, sempre que necessário, separadas. O acesso de visitantes deve ser controlado.</p><h2>Usar protecção adequada</h2><p>Os equipamentos de protecção individual devem ser seleccionados conforme os riscos e utilizados de acordo com a formação recebida. Capacetes, calçado, luvas, protecção ocular e outros equipamentos não substituem medidas de protecção colectiva.</p><h2>Manter o local organizado</h2><p>Cabos, ferramentas, materiais e resíduos não devem obstruir passagens. As escavações, aberturas e zonas de risco devem ter protecção e sinalização adequadas.</p><h2>Comunicar incidentes</h2><p>Condições inseguras, quase-acidentes e acidentes devem ser comunicados e analisados para corrigir as causas e prevenir repetição.</p><p><strong>Conclusão:</strong> a segurança é uma responsabilidade partilhada entre empregador, supervisão e trabalhadores, respeitando a legislação e os procedimentos aplicáveis.</p>', NULL, 'published', '2026-11-13 18:28:20', '2026-11-15 16:28:20', '2026-11-15 16:28:20'),
(10, 3, 2, 'Construção sustentável em Moçambique: decisões práticas para cada projecto', 'construcao-sustentavel-mocambique-decisoes-praticas', 'Soluções adaptadas ao clima, gestão eficiente da água e energia e escolha responsável de materiais podem melhorar o desempenho dos edifícios.', '<p>Construir de forma sustentável não significa aplicar uma solução cara ou igual em todos os locais. Significa considerar o clima, os recursos disponíveis, o uso do edifício e os impactos ao longo do seu ciclo de vida.</p><h2>Projectar de acordo com o clima</h2><p>A orientação do edifício, a ventilação natural, o sombreamento e a protecção contra chuva devem ser analisados desde a concepção. As soluções dependem da localização e da finalidade da construção.</p><h2>Usar água com responsabilidade</h2><p>Instalações eficientes, reparação de fugas e aproveitamento de água pluvial quando tecnicamente adequado podem reduzir o consumo de água potável. A qualidade e o uso da água armazenada devem ser avaliados.</p><h2>Melhorar o desempenho energético</h2><p>A iluminação eficiente, ventilação adequada e selecção criteriosa de equipamentos ajudam a reduzir necessidades energéticas. O conforto térmico deve ser considerado em conjunto com a arquitectura.</p><h2>Escolher materiais com critério</h2><p>Considere durabilidade, disponibilidade local, manutenção, transporte e desempenho técnico. A origem local pode ser uma vantagem, desde que o material cumpra os requisitos do projecto.</p><h2>Planear a manutenção</h2><p>Uma solução sustentável deve ser possível de inspeccionar, reparar e manter com recursos acessíveis ao longo do tempo.</p><p><strong>Conclusão:</strong> a construção sustentável resulta de decisões integradas de arquitectura, engenharia, execução e utilização. Cada solução deve ser dimensionada e validada por profissionais competentes.</p>', NULL, 'published', '2026-11-14 18:28:20', '2026-11-15 16:28:20', '2026-11-15 16:28:20');

-- --------------------------------------------------------

--
-- Table structure for table `blog_post_reactions`
--

CREATE TABLE `blog_post_reactions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `post_id` int(11) NOT NULL,
  `visitor_hash` char(64) NOT NULL,
  `reaction` enum('like','love','useful','insightful') NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `blog_post_reactions`
--

INSERT INTO `blog_post_reactions` (`id`, `post_id`, `visitor_hash`, `reaction`, `created_at`, `updated_at`) VALUES
(1, 10, '01a7e42380ca8bbe9772d96025766efd91d5a983aa3e6219847ddfc3f16dfed6', 'like', '2026-11-15 18:44:45', '2026-11-15 18:44:45');

-- --------------------------------------------------------

--
-- Table structure for table `blog_post_views`
--

CREATE TABLE `blog_post_views` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `post_id` int(11) NOT NULL,
  `visitor_hash` char(64) NOT NULL,
  `viewed_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `blog_post_views`
--

INSERT INTO `blog_post_views` (`id`, `post_id`, `visitor_hash`, `viewed_at`) VALUES
(1, 10, '01a7e42380ca8bbe9772d96025766efd91d5a983aa3e6219847ddfc3f16dfed6', '2026-11-15 18:44:38'),
(3, 6, '01a7e42380ca8bbe9772d96025766efd91d5a983aa3e6219847ddfc3f16dfed6', '2026-11-15 18:54:48');

-- --------------------------------------------------------

--
-- Table structure for table `comment_users`
--

CREATE TABLE `comment_users` (
  `id` bigint(20) NOT NULL,
  `google_sub` varchar(255) NOT NULL,
  `name` varchar(180) NOT NULL,
  `email` varchar(190) NOT NULL,
  `avatar_url` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int(11) NOT NULL,
  `title` varchar(180) NOT NULL,
  `location` varchar(180) NOT NULL,
  `category` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `status` enum('Concluído','Em andamento','Planeado') DEFAULT 'Concluído',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `title`, `location`, `category`, `description`, `image_url`, `status`, `created_at`) VALUES
(1, 'Edifício residencial', 'Maputo, Moçambique', 'Construção', 'Exemplo demonstrativo de projecto. Substitua pelos trabalhos reais da empresa.', 'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=1000&q=85', 'Concluído', '2026-11-15 15:30:10'),
(2, 'Obra comercial', 'Matola, Moçambique', 'Edifícios comerciais', 'Exemplo demonstrativo de projecto. Substitua pelos trabalhos reais da empresa.', 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=1000&q=85', 'Em andamento', '2026-11-15 15:30:10');

-- --------------------------------------------------------

--
-- Table structure for table `project_images`
--

CREATE TABLE `project_images` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `image_path` varchar(500) NOT NULL,
  `caption` varchar(180) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quote_requests`
--

CREATE TABLE `quote_requests` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `phone` varchar(40) NOT NULL,
  `email` varchar(190) DEFAULT NULL,
  `service` varchar(160) DEFAULT NULL,
  `location` varchar(180) DEFAULT NULL,
  `details` text NOT NULL,
  `status` enum('Novo','Em análise','Respondido','Concluído') DEFAULT 'Novo',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `quote_requests`
--

INSERT INTO `quote_requests` (`id`, `name`, `phone`, `email`, `service`, `location`, `details`, `status`, `created_at`) VALUES
(1, 'Edilson Artur', '+258 84 000 0000', 'fyzinhocompany@gmail.com', 'Reabilitação e remodelação', 'Quelimane', 'quero reabilitar meu apartamento e tornalo mais moderno', 'Novo', '2026-11-15 15:49:20');

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `title` varchar(160) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(80) DEFAULT 'bi-building',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `title`, `description`, `icon`, `created_at`) VALUES
(1, 'Construção civil', 'Construção de edifícios residenciais, comerciais e institucionais.', 'bi-buildings', '2026-11-15 15:30:10'),
(2, 'Reabilitação e remodelação', 'Renovação de espaços, reabilitação de edifícios e acabamentos.', 'bi-hammer', '2026-11-15 15:30:10'),
(3, 'Fiscalização de obras', 'Acompanhamento técnico, controlo de qualidade e cumprimento de prazos.', 'bi-clipboard-check', '2026-11-15 15:30:10'),
(4, 'Projectos e consultoria', 'Apoio técnico, planeamento e consultoria para projectos de construção.', 'bi-rulers', '2026-11-15 15:30:10');

-- --------------------------------------------------------

--
-- Table structure for table `site_settings`
--

CREATE TABLE `site_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `site_settings`
--

INSERT INTO `site_settings` (`setting_key`, `setting_value`, `updated_at`) VALUES
('about_image', '', '2026-11-15 15:30:31'),
('about_text', 'Aliamos conhecimento técnico, planeamento e acompanhamento próximo para transformar necessidades em espaços funcionais, seguros e duradouros.', '2026-11-15 15:30:31'),
('about_title', 'Mais do que construir, criamos confiança.', '2026-11-15 15:30:31'),
('address', 'Av Herois de Libertação Nacional, 17 de Setembro, Quelimane, Zambézia, Moçambique', '2026-11-15 15:44:25'),
('city', 'Quelimane, Zambézia, Moçambique', '2026-11-15 15:44:25'),
('company_description', 'Somos uma empresa orientada para soluções de construção, engenharia e reabilitação, comprometida com a qualidade e a satisfação dos nossos clientes.', '2026-11-15 15:30:31'),
('company_name', 'Moz Construções', '2026-11-15 15:30:31'),
('company_tagline', 'CONSTRUÇÃO & ENGENHARIA', '2026-11-15 15:30:31'),
('email', 'contacto@mozcontrucoes.co.mz', '2026-11-15 15:44:25'),
('hero_description', 'Da ideia à concretização, entregamos soluções de construção com qualidade, rigor técnico e compromisso.', '2026-11-15 15:30:31'),
('hero_image', '', '2026-11-15 15:30:31'),
('hero_title', 'Construímos hoje o futuro que imagina.', '2026-11-15 15:30:31'),
('opening_hours', 'Segunda – Sexta | 08:00 – 17:00', '2026-11-15 15:30:31'),
('phone', '+258 84 000 0000', '2026-11-15 15:30:31'),
('whatsapp', '258840000000', '2026-11-15 15:30:31');

-- --------------------------------------------------------

--
-- Table structure for table `testimonials`
--

CREATE TABLE `testimonials` (
  `id` int(11) NOT NULL,
  `client_name` varchar(140) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `visits`
--

CREATE TABLE `visits` (
  `id` bigint(20) NOT NULL,
  `visitor_key` char(64) NOT NULL,
  `visit_date` date NOT NULL,
  `page_path` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `visits`
--

INSERT INTO `visits` (`id`, `visitor_key`, `visit_date`, `page_path`, `created_at`) VALUES
(1, 'b075a1034ed484231d2a5537114629a4795ad9896c4a3cd1cbcbc1b88f553db9', '2026-11-15', '/1/1/obraforte_site/public/', '2026-11-15 15:33:36'),
(2, 'a827fbb32fab0d55ef58e5679990247655cdd5fb5a99a40723431ba9ebfad087', '2026-11-15', '/1/1/obraforte_site/public/', '2026-11-15 15:58:45'),
(3, 'c2caccbc13e50a138d60edf75ceebd041424b8c2e4654c3cf8f4fa13c6cb1270', '2026-11-15', '/1/2/obraforte_site/public/', '2026-11-15 16:07:20');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `blog_categories`
--
ALTER TABLE `blog_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `blog_comments`
--
ALTER TABLE `blog_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_comment_post_status` (`post_id`,`status`),
  ADD KEY `fk_comment_user` (`user_id`);

--
-- Indexes for table `blog_posts`
--
ALTER TABLE `blog_posts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_blog_published` (`status`,`published_at`),
  ADD KEY `fk_blog_category` (`category_id`),
  ADD KEY `fk_blog_admin` (`author_id`);

--
-- Indexes for table `blog_post_reactions`
--
ALTER TABLE `blog_post_reactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_blog_reaction_visitor` (`post_id`,`visitor_hash`),
  ADD KEY `idx_blog_reaction_post_type` (`post_id`,`reaction`);

--
-- Indexes for table `blog_post_views`
--
ALTER TABLE `blog_post_views`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_blog_view_visitor` (`post_id`,`visitor_hash`),
  ADD KEY `idx_blog_views_post` (`post_id`);

--
-- Indexes for table `comment_users`
--
ALTER TABLE `comment_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `google_sub` (`google_sub`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `project_images`
--
ALTER TABLE `project_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_project_images_order` (`project_id`,`sort_order`,`id`);

--
-- Indexes for table `quote_requests`
--
ALTER TABLE `quote_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `testimonials`
--
ALTER TABLE `testimonials`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `visits`
--
ALTER TABLE `visits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_visit_date` (`visit_date`),
  ADD KEY `idx_visitor_date` (`visitor_key`,`visit_date`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `blog_categories`
--
ALTER TABLE `blog_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `blog_comments`
--
ALTER TABLE `blog_comments`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `blog_posts`
--
ALTER TABLE `blog_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `blog_post_reactions`
--
ALTER TABLE `blog_post_reactions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `blog_post_views`
--
ALTER TABLE `blog_post_views`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `comment_users`
--
ALTER TABLE `comment_users`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `project_images`
--
ALTER TABLE `project_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quote_requests`
--
ALTER TABLE `quote_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `testimonials`
--
ALTER TABLE `testimonials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `visits`
--
ALTER TABLE `visits`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `blog_comments`
--
ALTER TABLE `blog_comments`
  ADD CONSTRAINT `fk_comment_post` FOREIGN KEY (`post_id`) REFERENCES `blog_posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_comment_user` FOREIGN KEY (`user_id`) REFERENCES `comment_users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `blog_posts`
--
ALTER TABLE `blog_posts`
  ADD CONSTRAINT `fk_blog_admin` FOREIGN KEY (`author_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_blog_category` FOREIGN KEY (`category_id`) REFERENCES `blog_categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `blog_post_reactions`
--
ALTER TABLE `blog_post_reactions`
  ADD CONSTRAINT `fk_blog_reaction_post` FOREIGN KEY (`post_id`) REFERENCES `blog_posts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `blog_post_views`
--
ALTER TABLE `blog_post_views`
  ADD CONSTRAINT `fk_blog_view_post` FOREIGN KEY (`post_id`) REFERENCES `blog_posts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `project_images`
--
ALTER TABLE `project_images`
  ADD CONSTRAINT `fk_project_images_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
