<?php
// Set header untuk manifest
header('Content-Type: application/manifest+json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Access-Control-Allow-Origin: *');

// Output manifest JSON
echo json_encode([
    "name" => "Sistem Presensi Kampus",
    "short_name" => "Presensi",
    "description" => "Aplikasi Sistem Presensi Kampus berbasis Web",
    "start_url" => "./index.php",
    "scope" => "./",
    "display" => "standalone",
    "background_color" => "#0066cc",
    "theme_color" => "#0066cc",
    "orientation" => "portrait",
    "categories" => ["education", "utilities"],
    "prefer_related_applications" => false,
    "icons" => [
        [
            "src" => "./includes/icon-192.png",
            "sizes" => "192x192",
            "type" => "image/png",
            "purpose" => "any"
        ],
        [
            "src" => "./includes/icon-192.png",
            "sizes" => "192x192",
            "type" => "image/png",
            "purpose" => "maskable"
        ],
        [
            "src" => "./includes/icon-512.png",
            "sizes" => "512x512",
            "type" => "image/png",
            "purpose" => "any"
        ],
        [
            "src" => "./includes/icon-512.png",
            "sizes" => "512x512",
            "type" => "image/png",
            "purpose" => "maskable"
        ]
    ]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
