@props([
    'alt' => 'Logo',
    'class' => '',
    'height' => null,
    'width' => null,
    'style' => '',
])

@php
    $logoSource = null;

    // 1. Logo do tenant (white-label), se existir no disco
    $tenant = tenant();
    if ($tenant) {
        $logoPath = $tenant->logoFilePath('logo');
        if ($logoPath) {
            $logoSource = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
        }
    }

    // 2. Fallback legado (imagem fixa do repositório)
    if (! $logoSource) {
        $legacyPath = public_path('images/logo-comav-transportes.png');
        $logoSource = file_exists($legacyPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($legacyPath))
            : asset('images/logo-comav-transportes.png');
    }

    $inlineStyle = trim($style);

    if ($height) {
        $h = (is_numeric($height) ? $height . 'px' : $height);
        $inlineStyle = trim($inlineStyle . '; height:' . $h . ';');
    }

    if ($width) {
        $w = (is_numeric($width) ? $width . 'px' : $width);
        $inlineStyle = trim($inlineStyle . '; width:' . $w . ';');
    }
@endphp

<img src="{{ $logoSource }}" alt="{{ $alt }}" class="{{ trim(($class . ' max-w-full h-auto')) }}" style="{{ $inlineStyle }}">