# Relatórios e Auditoria

Telas focadas em extração de dados, conformidade (Compliance) e tomada de decisão.

### Tipos de relatórios

- **auditoria.blade.php**: Relatório de auditoria gerencial — conformidade por usuário × conteúdo,
  ausências justificadas (férias, folgas, atestados, licenças, isenções e prazos estendidos),
  evolução mensal, trilha de ações (`audit_logs`) e exportações CSV/PDF com SHA-256 do recorte.
  Backend: `App\Http\Controllers\Admin\RelatorioAuditoriaController` + `App\Services\RelatorioAuditoriaService`.
- **avaliacoes.blade.php**: Relatório de avaliações e notas — nota por usuário × treinamento,
  resultado, tentativas, data/hora de início e conclusão, certificado e histórico de tentativas
  (`training_logs`). Backend: `App\Http\Controllers\Admin\RelatorioAvaliacoesController`.
- **treinamentos.blade.php**: Focado no conteúdo. Mostra quem assistiu cada vídeo e a taxa de sucesso nas avaliações.
- **usuarios.blade.php**: Focado no colaborador. Mostra todo o histórico de um funcionário específico.

### Base de cálculo

Os relatórios de auditoria e avaliações excluem **somente** `super_admin` e usuários marcados
como teste — a exclusão é declarada na tela e nos arquivos exportados. Demais grupos (admins,
inativos, afastados) permanecem visíveis e podem ser isolados por filtros.

### Exportações

- **PDF** A4 paisagem (TCPDF), com data/hora, autor, filtros aplicados e SHA-256 do recorte —
  ideais para auditorias de segurança (SASSMAQ, ISO).
- **CSV** com os mesmos metadados no topo (`# ...`), no padrão `;` com BOM UTF-8.

Documentação funcional completa: `docs/modulos/DOCUMENTACAO_RELATORIO_AUDITORIA.md`.
