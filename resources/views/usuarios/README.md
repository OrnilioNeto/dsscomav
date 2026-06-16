# 👥 Usuários e Perfil

Gerenciamento de identidade e dados pessoais.

### Funcionalidades:
- **index/create/edit**: CRUD administrativo. O formulário de edição é dinâmico: mostra campos de CNH para motoristas e Empresa para terceirizados.
- **edit-profile.blade.php**: Tela onde o próprio usuário gerencia seus dados.
- **Módulo de Foto**: Implementa a captura via câmera (Webcam API) ou upload de galeria, com redimensionamento automático para 300x300px via JavaScript/Intervention Image.

### Iniciais/Avatares:
Se o usuário não possui foto, o sistema utiliza o componente `user-avatar.blade.php` para gerar um círculo colorido com as iniciais do nome.