# 🗃️ Repositories - Camada de Acesso a Dados

Esta pasta contém a abstração de consultas complexas ao banco de dados, mantendo os models limpos.

### Destaque:
- **RankingRepository.php**: O componente mais complexo desta pasta. 
  - `getTopMonthly()`: Busca os melhores do mês. Possui um sistema de *fallback* inteligente: se o ranking consolidado ainda não foi gerado, ele calcula em tempo real a partir dos certificados emitidos.
  - `calculateFallbackScore()`: Implementa a lógica de pontuação baseada em tempo de início, dias para conclusão e tentativas no quiz.
  - `enrichRowsWithPeriodStats()`: Adiciona metadados de performance (quantidade de conteúdos e último título assistido) aos resultados.

### Por que usar Repositories aqui?
Como o sistema de Ranking exige cálculos pesados e consultas em múltiplas tabelas (`certificates`, `trainings`, `user_progress`), o Repository evita que essa lógica "suje" o Controller.