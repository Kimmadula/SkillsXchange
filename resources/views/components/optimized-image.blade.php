@props([
'src',
'alt' => '',
'class' => '',
'size' => 'medium', // small, medium, large, xl
'type' => 'general' // logo, user, feature, chat, hero, card
])

@php
$sizeClasses = [
'small' => 'w-8 h-8',
'medium' => 'w-12 h-12',
'large' => 'w-16 h-16',
'xl' => 'w-24 h-24'
];

$typeClasses = [
'logo' => 'logo-image',
'user' => 'user-photo',
'feature' => 'feature-icon',
'chat' => 'chat-image',
'hero' => 'hero-image',
'card' => 'card-image',
'general' => ''
];

$baseClass = '';
$sizeClass = '';

if (isset($type) && isset($typeClasses[$type])) {
$baseClass = $typeClasses[$type];
}

if (isset($size) && isset($sizeClasses[$size])) {
$sizeClass = $sizeClasses[$size];
}

$combinedClass = trim("$baseClass $sizeClass $class");
@endphp

<img src="{{ $src }}" alt="{{ $alt }}" class="{{ $combinedClass }}" loading="lazy" {{ $attributes }}>