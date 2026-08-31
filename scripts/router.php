<?php
// Router for PHP built-in server. Serves static files directly, otherwise
// lets PHP execute the requested .php script. Directory requests fall back to index.php.
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$root = __DIR__ . '/..';
$path = realpath($root . $uri);

// Static asset that exists on disk -> serve as-is.
if ($path !== false && is_file($path)) {
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if ($ext !== 'php') {
        return false; // let the built-in server serve the static file
    }
}

// Directory (or "/") -> use its index.php if present.
if ($uri === '/' || substr($uri, -1) === '/') {
    $index = $root . rtrim($uri, '/') . '/index.php';
    if (is_file($index)) { require $index; return true; }
}

// Existing .php file -> execute it.
if ($path !== false && is_file($path) && strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'php') {
    require $path;
    return true;
}

// Fallback to root index.php.
require $root . '/index.php';
return true;
