# 🎥 Experiência de Treinamento

Pasta que contém a interface de consumo de conteúdo.

### O Player (`player.blade.php`):
- **Segurança**: Bloqueia o avanço manual da barra de tempo para garantir que o treinamento seja assistido.
- **Integração**: Detecta automaticamente se a URL é YouTube ou Vimeo para carregar o Iframe correto.
- **Progresso**: Envia via AJAX atualizações de tempo para o servidor a cada intervalo definido.

### Avaliação:
Contém a lógica de quiz ao final do vídeo. Só é liberada após o tempo mínimo de assistência ser atingido.