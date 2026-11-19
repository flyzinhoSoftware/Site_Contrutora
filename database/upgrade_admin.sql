USE obraforte_db;
CREATE TABLE IF NOT EXISTS site_settings (
  setting_key VARCHAR(100) PRIMARY KEY,
  setting_value TEXT NOT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO site_settings (setting_key, setting_value) VALUES
('company_name','Moz Construções'),
('company_tagline','CONSTRUÇÃO & ENGENHARIA'),
('company_description','Somos uma empresa orientada para soluções de construção, engenharia e reabilitação, comprometida com a qualidade e a satisfação dos nossos clientes.'),
('hero_title','Construímos hoje o futuro que imagina.'),
('hero_description','Da ideia à concretização, entregamos soluções de construção com qualidade, rigor técnico e compromisso.'),
('about_title','Mais do que construir, criamos confiança.'),
('about_text','Aliamos conhecimento técnico, planeamento e acompanhamento próximo para transformar necessidades em espaços funcionais, seguros e duradouros.'),
('city','Maputo, Moçambique'),
('phone','+258 84 000 0000'),
('email','contacto@horizonte.co.mz'),
('whatsapp','258840000000'),
('address','Maputo, Moçambique'),
('opening_hours','Segunda – Sexta | 08:00 – 17:00'),
('hero_image',''),
('about_image','')
ON DUPLICATE KEY UPDATE setting_key=VALUES(setting_key);

UPDATE site_settings SET setting_value='Moz Construções' WHERE setting_key='company_name';
