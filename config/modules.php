<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Catálogo de módulos
    |--------------------------------------------------------------------------
    |
    | Fonte única dos módulos do sistema: usado na liberação por tenant
    | (tenant_modules), na RBAC (role_permissions) e na UI de configuração.
    |
    | Módulos novos (app/Modules/<Nome>, ver docs/modulos/GUIA_MODULOS.md)
    | devem adicionar uma entrada aqui para aparecerem na matriz de permissões
    | e no painel da plataforma.
    |
    */

    'users' => [
        'label' => 'Usuários',
        'description' => 'Cadastro e gestão de usuários',
    ],
    'trainings' => [
        'label' => 'Treinamentos',
        'description' => 'Catálogo de treinamentos e materiais',
    ],
    'certificates' => [
        'label' => 'Certificados',
        'description' => 'Emissão, validação e relatórios de certificados',
    ],
    'rankings' => [
        'label' => 'Ranking',
        'description' => 'Gamificação, pontuação e rankings',
    ],
    'splash' => [
        'label' => 'Splash',
        'description' => 'Avisos exibidos após o login',
    ],
    'social' => [
        'label' => 'Rede Social',
        'description' => 'Feed, curtidas e perfil social',
    ],
    'epi' => [
        'label' => 'EPI',
        'description' => 'Catálogo, estoque, entregas e assinaturas de EPIs',
    ],
    'projeto_pedagogico' => [
        'label' => 'Projeto Pedagógico',
        'description' => 'Projetos pedagógicos NR-01',
    ],
    'folgas' => [
        'label' => 'Banco de Folgas',
        'description' => 'Controle de folgas e domingos',
    ],
    'rewatch' => [
        'label' => 'Reassistir Treinamentos',
        'description' => 'Liberação de conteúdo para reassistir',
    ],
    'auditoria' => [
        'label' => 'Auditoria',
        'description' => 'Trilha de ações, acessos e alterações do sistema',
    ],
    'permissions' => [
        'label' => 'Perfis e Permissões',
        'description' => 'Gestão de perfis de acesso (somente plataforma)',
    ],
];
