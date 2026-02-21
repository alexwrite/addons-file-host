<?php

/*
 * FileHost Addon for ClientXCMS NextGen
 * Author: Corentin WebSite
 * Year: 2026
 * License: Proprietary
 *
 * Script de service autonome — court-circuite Laravel complètement.
 * Sert les fichiers hébergés même en mode maintenance du site.
 *
 * SÉCURITÉ : ce fichier NE DOIT PAS afficher d'erreurs PHP ni de chemins
 * serveur en production. Toutes les erreurs sont silencieuses.
 */

// ─── Sécurité : masquer toutes les erreurs PHP ────────────────────────────────
error_reporting(0);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

// ─── Extensions de fichiers interdites à l'exécution ────────────────────────
const BLOCKED_EXTENSIONS = [
    'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phar',
    'asp', 'aspx', 'cfm', 'jsp', 'pl', 'py', 'rb', 'sh', 'bash',
    'bat', 'cmd', 'exe', 'htaccess', 'htpasswd', 'env',
];

// ─── Trouver la racine Laravel ─────────────────────────────────────────────
$laravelRoot = dirname(__DIR__);

// Vérifier que le bootstrap existe (sécurité : ne pas tourner hors contexte Laravel)
if (!file_exists($laravelRoot . '/vendor/autoload.php') || !file_exists($laravelRoot . '/bootstrap/app.php')) {
    http_response_code(503);
    exit;
}

// ─── Extraire et valider le chemin de la requête ─────────────────────────────
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$path       = parse_url($requestUri, PHP_URL_PATH);
$path       = ltrim($path ?? '', '/');

// Supprimer les null bytes
$path = str_replace("\0", '', $path);

// Bloquer les path traversal AVANT tout traitement
if (str_contains($path, '..') || str_contains($path, "\0")) {
    http_response_code(400);
    exit;
}

// Séparer le préfixe de l'UUID (ex: "drive/abc123.png" → uuid = "abc123.png")
$slashPos = strpos($path, '/');
if ($slashPos === false) {
    http_response_code(404);
    exit;
}

$uuid = substr($path, $slashPos + 1);
$uuid = str_replace("\0", '', $uuid); // double check null bytes

if (empty($uuid) || strlen($uuid) > 500) {
    http_response_code(404);
    exit;
}

// Bloquer les UUIDs avec des extensions dangereuses
$ext = strtolower(pathinfo($uuid, PATHINFO_EXTENSION));
if (in_array($ext, BLOCKED_EXTENSIONS, true)) {
    http_response_code(403);
    exit;
}

// ─── Bootstrap minimal de Laravel (DB uniquement, pas de middleware) ──────────
try {
    require $laravelRoot . '/vendor/autoload.php';
    $app = require_once $laravelRoot . '/bootstrap/app.php';
    // Boot minimal : uniquement le kernel Console (pas HTTP → pas de maintenance)
    $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
} catch (\Throwable $e) {
    http_response_code(503);
    exit;
}

// ─── Chercher le fichier en base via le query builder Laravel ────────────────
try {
    $db   = app('db');
    $file = $db->table('file_hosts')->where('uuid', $uuid)->first();

    if (!$file) {
        http_response_code(404);
        exit;
    }

    // ─── Valider le chemin physique du fichier ────────────────────────────────
    $storageBase = realpath($laravelRoot . '/storage/app');
    if ($storageBase === false) {
        http_response_code(500);
        exit;
    }

    $filePath = $laravelRoot . '/storage/app/' . $file->file_path;
    $realPath = realpath($filePath);

    // Path traversal : vérifier que le chemin résolu reste dans storage/app
    if ($realPath === false || !str_starts_with($realPath, $storageBase . DIRECTORY_SEPARATOR)) {
        http_response_code(403);
        exit;
    }

    if (!is_file($realPath) || !is_readable($realPath)) {
        http_response_code(404);
        exit;
    }

    // ─── Vérifier l'extension finale du fichier sur disque ───────────────────
    $diskExt = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));
    if (in_array($diskExt, BLOCKED_EXTENSIONS, true)) {
        http_response_code(403);
        exit;
    }

    // ─── Incrémenter les vues ─────────────────────────────────────────────────
    try {
        $db->table('file_hosts')->where('uuid', $uuid)->increment('views');
    } catch (\Throwable $e) {
        // Non bloquant — le compteur n'est pas critique
    }

    // ─── Préparer les headers de sécurité ─────────────────────────────────────
    $mimeType    = $file->mime_type ?: 'application/octet-stream';
    $disposition = 'inline';

    // Forcer le téléchargement pour les types qui pourraient exécuter du code en navigateur
    if (in_array($mimeType, ['text/html', 'image/svg+xml', 'text/xml', 'application/xml', 'application/xhtml+xml'], true)) {
        $disposition = 'attachment';
        $mimeType    = 'application/octet-stream';
    }

    // Sanitiser le nom de fichier pour le header (bloquer injection CRLF)
    $safeName = preg_replace('/[\x00-\x1F\x7F"\\\\]/', '', $file->original_name ?? 'fichier');
    $safeName = mb_substr(trim($safeName) ?: 'fichier', 0, 255);

    $fileSize = filesize($realPath);

    // ─── Envoyer les headers ──────────────────────────────────────────────────
    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: ' . $disposition . '; filename="' . $safeName . '"');
    header('Content-Length: ' . $fileSize);
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: no-referrer');
    header('Cache-Control: private, max-age=3600');
    // Empêcher l'indexation par les moteurs de recherche
    header('X-Robots-Tag: noindex, nofollow');

    // ─── Envoyer le fichier ───────────────────────────────────────────────────
    if (ob_get_length()) {
        ob_clean();
    }
    flush();
    readfile($realPath);
    exit;

} catch (\Throwable $e) {
    // Silencieux : ne pas exposer d'informations serveur
    http_response_code(500);
    exit;
}
