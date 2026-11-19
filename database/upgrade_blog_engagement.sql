USE obraforte_db;

-- Regista uma visualização única por visitante e artigo.
CREATE TABLE IF NOT EXISTS blog_post_views (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    visitor_hash CHAR(64) NOT NULL,
    viewed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_blog_view_visitor (post_id, visitor_hash),
    KEY idx_blog_views_post (post_id),
    CONSTRAINT fk_blog_view_post FOREIGN KEY (post_id)
        REFERENCES blog_posts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cada visitante pode manter uma reacção activa por artigo. Uma nova escolha
-- substitui a anterior; seleccionar novamente a mesma reacção remove-a.
CREATE TABLE IF NOT EXISTS blog_post_reactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    visitor_hash CHAR(64) NOT NULL,
    reaction ENUM('like','love','useful','insightful') NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_blog_reaction_visitor (post_id, visitor_hash),
    KEY idx_blog_reaction_post_type (post_id, reaction),
    CONSTRAINT fk_blog_reaction_post FOREIGN KEY (post_id)
        REFERENCES blog_posts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
