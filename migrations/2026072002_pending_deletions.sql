CREATE TABLE IF NOT EXISTS pending_deletions (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL DEFAULT 0,
    user_id INT NOT NULL,
    ticket_id INT NOT NULL,
    resource_type VARCHAR(30) NOT NULL,
    resource_id BIGINT NOT NULL,
    token_hash CHAR(64) NOT NULL,
    payload_json LONGTEXT NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_pending_deletion_token (token_hash),
    INDEX idx_pending_deletions_expiry (expires_at),
    INDEX idx_pending_deletions_ticket (ticket_id),
    INDEX idx_pending_deletions_user (user_id),
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
