@props([
    'alt' => 'Logo',
    'class' => '',
    'height' => null,
    'width' => null,
    'style' => '',
])

@php
    $logoPath = public_path('images/logo-comav-transportes.png');
    $logoSource = file_exists($logoPath)
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
        : asset('images/logo-comav-transportes.png');

    $inlineStyle = trim($style);

    if ($height) {
        $inlineStyle = trim($inlineStyle . '; height:' . $height . ';');
    }

    if ($width) {
        $inlineStyle = trim($inlineStyle . '; width:' . $width . ';');
    }
@endphp

<img src="{{ $logoSource }}" alt="{{ $alt }}" class="{{ $class }}" style="{{ $inlineStyle }}">