CREATE TABLE wp_sosyallift_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    log_type VARCHAR(50) NOT NULL,
    source VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    context LONGTEXT NULL,
    level VARCHAR(20) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY log_type (log_type),
    KEY source (source),
    KEY level (level),
    KEY created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
