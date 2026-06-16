# 📊 Dashboard Views

As telas principais de acompanhamento do sistema.

### Organização:
- **super_admin.blade.php**: Visão global de saúde do sistema (total de usuários, treinamentos ativos, certificados hoje).
- **admin.blade.php**: Focado na gestão da equipe. Inclui o sistema de **Abas** para separar a visualização de "Gerenciamento" (gráficos) de "Participação" (se o admin também for aluno).
- **usuario.blade.php**: Interface de gamificação do colaborador. Mostra o progresso atual, treinamentos recomendados e o Ranking.

### Componentes Visuais:
- Utiliza **Tailwind CSS** para cards responsivos.
- Ícones do **FontAwesome** para representar status de conclusão.