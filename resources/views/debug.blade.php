<!DOCTYPE html>
<html>
<head>
    <title>Debug</title>
</head>
<body>
    <h1>Debug Page</h1>
    <p>Se você vê isto, a página foi renderizada.</p>
    <p>Tipo de vídeo testado: {{ $training->tipo_video ?? 'NENHUM' }}</p>
    
    <script>
        console.log('🐛 DEBUG SCRIPT RODANDO');
        console.log('Tipo:', '{{ $training->tipo_video ?? "sem dados" }}');
    </script>
</body>
</html>
