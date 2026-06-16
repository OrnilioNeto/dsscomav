# 🏗️ Migrations - Arquitetura do Banco de Dados

Define a evolução das tabelas do sistema.

### Tabelas Principais:
- **users**: Armazena dados de colaboradores com campos específicos para Motoristas (CNH), Terceirizados e Funcionários. Inclui suporte a fotos de perfil.
- **roles**: Define os níveis de acesso (Super Admin, Admin, Usuário).
- **trainings**: Catálogo de vídeos e documentos. Suporta metadados de vídeos externos (YouTube/Vimeo).
- **user_progress**: Rastreia cada segundo assistido e status da avaliação.
- **certificates**: Registro oficial de conclusão com códigos de autenticidade.
- **ranking_monthly_scores**: Tabela de performance para armazenamento dos resultados da gamificação.

### Evolução do Sistema:
As migrações mais recentes (2026) focaram em **Auditoria**, adicionando campos para registrar exatamente quando o usuário iniciou e terminou um vídeo, além de contar as tentativas de quiz.