<?php
declare(strict_types=1);

function sos_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $remoteAddress = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    $trustedProxyHttps = in_array($remoteAddress, ['127.0.0.1', '::1'], true)
        && strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
    $https = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443
        || $trustedProxyHttps;

    ini_set('session.use_strict_mode', '1');
    session_name('SOSCALCSESSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/calculos/',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function sos_csrf_token(): string
{
    sos_start_session();

    if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token']) || strlen($_SESSION['csrf_token']) !== 64) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function sos_validate_csrf(?string $provided): bool
{
    sos_start_session();
    $expected = $_SESSION['csrf_token'] ?? null;

    return is_string($expected)
        && strlen($expected) === 64
        && is_string($provided)
        && strlen($provided) === 64
        && hash_equals($expected, $provided);
}
