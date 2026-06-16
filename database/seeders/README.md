# 🌱 Seeders - Dados Iniciais e Testes

Responsáveis por popular o banco de dados para desenvolvimento e primeiro uso.

### Arquivos:
- **RoleSeeder.php**: Cria os perfis essenciais (Super Admin, Admin, Usuario).
- **UserSeeder.php**: Cria as contas de teste padrão (CPF 00000000000, etc) para facilitar o login após a instalação.
- **TrainingSeeder.php**: Insere exemplos de DSS e Treinamentos reais para demonstrar as funcionalidades de vídeo.
- **DatabaseSeeder.php**: O orquestrador que chama todos os outros na ordem correta.

### Como usar:
Execute `php artisan migrate --seed` para resetar o banco e carregar esses dados.