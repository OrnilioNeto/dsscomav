# 📈 Relatórios e Auditoria

Telas focadas em extração de dados e conformidade (Compliance).

### Tipos de Relatórios:
- **auditoria.blade.php**: Relatório executivo com gráficos de evolução mensal de certificados e participações.
- **treinamentos.blade.php**: Focado no conteúdo. Mostra quem assistiu cada vídeo e a taxa de sucesso nas avaliações.
- **usuarios.blade.php**: Focado no colaborador. Mostra todo o histórico de um funcionário específico.

### Diferencial:
Todos os relatórios possuem o botão de **Download PDF**, que utiliza o `CertificateManagementController` para gerar documentos formatados em A4 paisagem, ideais para serem apresentados em auditorias de segurança (como SASSMAQ ou ISO).