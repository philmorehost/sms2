<?php
require_once __DIR__ . '/app/bootstrap.php';

header('Content-Type: application/json');

$settings = get_settings();

$manifest = [
    'name' => $settings['site_name'] ?? 'Bulk SMS Platform',
    'short_name' => $settings['site_name'] ?? 'SMS App',
    'start_url' => '/login.php',
    'display' => 'standalone',
    'background_color' => $settings['pwa_background_color'] ?? '#ffffff',
    'theme_color' => $settings['pwa_theme_color'] ?? '#0d6efd',
    'icons' => [],
];

if (!empty($settings['pwa_icon_192'])) {
    $manifest['icons'][] = [
        'src' => SITE_URL . '/' . $settings['pwa_icon_192'],
        'sizes' => '192x192',
        'type' => 'image/png',
    ];
}

if (!empty($settings['pwa_icon_512'])) {
    $manifest['icons'][] = [
        'src' => SITE_URL . '/' . $settings['pwa_icon_512'],
        'sizes' => '512x512',
        'type' => 'image/png',
    ];
}

echo json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
?>
