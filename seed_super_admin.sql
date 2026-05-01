DELETE FROM users WHERE email = 'super@admin.com';
INSERT INTO users (nome, cpf, email, password, tipo_usuario, status, role_id, created_at, updated_at) 
VALUES ('Super Admin', '00000000000', 'super@admin.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36P4/SlO', 'funcionario', 'ativo', 1, datetime('now'), datetime('now'));
SELECT id, nome, email FROM users WHERE email = 'super@admin.com';
