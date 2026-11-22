<?php

/*
 * Custom router for PHP's built-in server (used by `php artisan serve`).
 *
 * Malformed path combinations such as ///.../// are rejected by PHP's built-in
 * server before Laravel can handle them. Catch them here and return to the page
 * the combination was appended to; fall back to the referring page or the
 * dashboard. Everything else is passed to Laravel's front controller.
 */

$publicPath = getcwd();

/*
 * Use the raw request path. parse_url() treats a leading // as a
 * scheme-relative URL and returns an empty path for bare combos such as
 * /////....///, which would otherwise slip past the malformed-path check.
 */
$uri = urldecode(explode('?', $_SERVER['REQUEST_URI'], 2)[0]);

if (preg_match('#/{2,}|\.{2,}#', $uri)) {
    // Keep the clean part of the link, e.g. /products//////.......////// -> /products.
    $clean = preg_split('#(/{2,}|\.{2,})#', $uri, 2)[0] ?? '';
    $clean = rtrim($clean, '/');

    $pages = [
        'dashboard', 'inventory', 'products', 'purchase-orders', 'sales-orders',
        'categories', 'brands', 'users', 'activity-logs', 'transactions', 'reports',
    ];
    $firstSegment = explode('/', ltrim($clean, '/'))[0] ?? '';

    if ($clean !== '' && in_array($firstSegment, $pages, true)) {
        $target = $clean;
    } else {
        $scheme = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? '127.0.0.1:8001';
        $base = $scheme.'://'.$host;
        $referer = $_SERVER['HTTP_REFERER'] ?? '';

        $target = (filter_var($referer, FILTER_VALIDATE_URL)
            && ($referer === $base || str_starts_with($referer, $base.'/')))
            ? $referer
            : '/dashboard';
    }

    header('Location: '.$target, true, 302);
    exit;
}

if ($uri !== '/' && file_exists($publicPath.$uri)) {
    return false;
}

$formattedDateTime = date('D M j H:i:s Y');

$requestMethod = $_SERVER['REQUEST_METHOD'];
$remoteAddress = $_SERVER['REMOTE_ADDR'].':'.$_SERVER['REMOTE_PORT'];

file_put_contents('php://stdout', "[$formattedDateTime] $remoteAddress [$requestMethod] URI: $uri\n");

require_once $publicPath.'/index.php';
