-- MySQL bootstrap for production fallback
-- Run this only if artisan seeding did not execute on the server.

INSERT INTO roles (nome, descricao, created_at, updated_at)
VALUES
    ('super_admin', 'Super Administrador - Acesso total ao sistema', NOW(), NOW())
ON DUPLICATE KEY UPDATE
    descricao = VALUES(descricao),
    updated_at = VALUES(updated_at);

INSERT INTO users (
    nome,
    cpf,
    email,
    password,
    tipo_usuario,
    status,
    role_id,
    telefone,
    setor,
    cargo,
    created_at,
    updated_at
)
SELECT
    'Super Admin',
    '10178415430',
    'superadmin@dss.com',
    '$2b$10$Te7G9E614AtfX9/qcPE.GOzBlGtAi1xudWyqmasSM2ph5FHI6UXGG',
    'funcionario',
    'ativo',
    r.id,
    '(11) 99999-9999',
    'Gestão',
    'Super Administrador',
    NOW(),
    NOW()
FROM roles r
WHERE r.nome = 'super_admin'
ON DUPLICATE KEY UPDATE
    nome = VALUES(nome),
    email = VALUES(email),
    password = VALUES(password),
    tipo_usuario = VALUES(tipo_usuario),
    status = VALUES(status),
    role_id = VALUES(role_id),
    telefone = VALUES(telefone),
    setor = VALUES(setor),
    cargo = VALUES(cargo),
    updated_at = VALUES(updated_at);

SELECT id, nome, cpf, email
FROM users
WHERE cpf = '10178415430';
