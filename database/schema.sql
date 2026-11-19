CREATE DATABASE IF NOT EXISTS obraforte_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE obraforte_db;

CREATE TABLE IF NOT EXISTS admins (
 id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(120) NOT NULL, email VARCHAR(190) NOT NULL UNIQUE,
 password VARCHAR(255) NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS projects (
 id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(180) NOT NULL, location VARCHAR(180) NOT NULL,
 category VARCHAR(100) NOT NULL, description TEXT, image_url VARCHAR(500),
 status ENUM('Concluído','Em andamento','Planeado') DEFAULT 'Concluído',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS services (
 id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(160) NOT NULL, description TEXT,
 icon VARCHAR(80) DEFAULT 'bi-building', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS testimonials (
 id INT AUTO_INCREMENT PRIMARY KEY, client_name VARCHAR(140) NOT NULL, message TEXT NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS quote_requests (
 id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(150) NOT NULL, phone VARCHAR(40) NOT NULL,
 email VARCHAR(190), service VARCHAR(160), location VARCHAR(180), details TEXT NOT NULL,
 status ENUM('Novo','Em análise','Respondido','Concluído') DEFAULT 'Novo',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS visits (
 id BIGINT AUTO_INCREMENT PRIMARY KEY, visitor_key CHAR(64) NOT NULL,
 visit_date DATE NOT NULL, page_path VARCHAR(255) NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_visit_date (visit_date), INDEX idx_visitor_date (visitor_key, visit_date)
) ENGINE=InnoDB;

INSERT INTO services (title,description,icon)
SELECT 'Construção civil','Construção de edifícios residenciais, comerciais e institucionais.','bi-buildings'
WHERE NOT EXISTS (SELECT 1 FROM services);
INSERT INTO services (title,description,icon)
SELECT 'Reabilitação e remodelação','Renovação de espaços, reabilitação de edifícios e acabamentos.','bi-hammer'
WHERE (SELECT COUNT(*) FROM services)=1;
INSERT INTO services (title,description,icon)
SELECT 'Fiscalização de obras','Acompanhamento técnico, controlo de qualidade e cumprimento de prazos.','bi-clipboard-check'
WHERE (SELECT COUNT(*) FROM services)=2;
INSERT INTO services (title,description,icon)
SELECT 'Projectos e consultoria','Apoio técnico, planeamento e consultoria para projectos de construção.','bi-rulers'
WHERE (SELECT COUNT(*) FROM services)=3;

INSERT INTO projects (title,location,category,description,image_url,status)
SELECT 'Edifício residencial','Maputo, Moçambique','Construção','Exemplo demonstrativo de projecto. Substitua pelos trabalhos reais da empresa.','https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=1000&q=85','Concluído'
WHERE NOT EXISTS (SELECT 1 FROM projects);
INSERT INTO projects (title,location,category,description,image_url,status)
SELECT 'Obra comercial','Matola, Moçambique','Edifícios comerciais','Exemplo demonstrativo de projecto. Substitua pelos trabalhos reais da empresa.','https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=1000&q=85','Em andamento'
WHERE (SELECT COUNT(*) FROM projects)=1;
