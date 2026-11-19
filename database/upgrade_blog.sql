USE obraforte_db;

CREATE TABLE IF NOT EXISTS blog_categories (
 id INT AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(120) NOT NULL,
 slug VARCHAR(150) NOT NULL UNIQUE,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS blog_posts (
 id INT AUTO_INCREMENT PRIMARY KEY,
 category_id INT NULL,
 author_id INT NULL,
 title VARCHAR(220) NOT NULL,
 slug VARCHAR(240) NOT NULL UNIQUE,
 excerpt VARCHAR(500) NOT NULL,
 content LONGTEXT NOT NULL,
 cover_image VARCHAR(500) NULL,
 status ENUM('draft','published') NOT NULL DEFAULT 'draft',
 published_at DATETIME NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 INDEX idx_blog_published(status,published_at),
 CONSTRAINT fk_blog_category FOREIGN KEY(category_id) REFERENCES blog_categories(id) ON DELETE SET NULL,
 CONSTRAINT fk_blog_admin FOREIGN KEY(author_id) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS comment_users (
 id BIGINT AUTO_INCREMENT PRIMARY KEY,
 google_sub VARCHAR(255) NOT NULL UNIQUE,
 name VARCHAR(180) NOT NULL,
 email VARCHAR(190) NOT NULL,
 avatar_url VARCHAR(500) NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS blog_comments (
 id BIGINT AUTO_INCREMENT PRIMARY KEY,
 post_id INT NOT NULL,
 user_id BIGINT NOT NULL,
 comment TEXT NOT NULL,
 status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_comment_post_status(post_id,status),
 CONSTRAINT fk_comment_post FOREIGN KEY(post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
 CONSTRAINT fk_comment_user FOREIGN KEY(user_id) REFERENCES comment_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO blog_categories(name,slug) VALUES
('Construção civil','construcao-civil'),('Engenharia','engenharia'),('Dicas e projectos','dicas-e-projectos')
ON DUPLICATE KEY UPDATE name=VALUES(name);
