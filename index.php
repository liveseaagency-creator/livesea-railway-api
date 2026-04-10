<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

function env_value(string $key, mixed $default = null): mixed
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

    if ($value === false || $value === null || $value === '') {
        return $default;
    }

    return $value;
}

function json_response(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function require_api_key(): void
{
    $expected = (string) env_value('INTERNAL_API_KEY', '');
    $header   = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

    if ($expected === '') {
        json_response([
            'ok' => false,
            'error' => 'INTERNAL_API_KEY manquante sur Railway'
        ], 500);
    }

    if ($header !== 'Bearer ' . $expected) {
        json_response([
            'ok' => false,
            'error' => 'Unauthorized'
        ], 401);
    }
}

$action = $_GET['action'] ?? 'ping';

if ($action !== 'ping') {
    require_api_key();
}

switch ($action) {
    case 'ping':
        json_response([
            'ok' => true,
            'service' => 'livesea-railway-api',
            'status' => 'online',
            'time' => gmdate('c'),
        ]);
        break;

    case 'site-info':
        json_response([
            'ok' => true,
            'site_name' => env_value('SITE_NAME'),
            'site_support_email' => env_value('SITE_SUPPORT_EMAIL'),
            'site_default_manager_name' => env_value('SITE_DEFAULT_MANAGER_NAME'),
            'site_default_manager_email' => env_value('SITE_DEFAULT_MANAGER_EMAIL'),
            'site_default_manager_discord' => env_value('SITE_DEFAULT_MANAGER_DISCORD'),
            'site_default_support_delay' => env_value('SITE_DEFAULT_SUPPORT_DELAY'),
            'site_default_agency_role' => env_value('SITE_DEFAULT_AGENCY_ROLE'),
            'site_agency_tiktok_url' => env_value('SITE_AGENCY_TIKTOK_URL'),
            'discord_client_id' => env_value('DISCORD_CLIENT_ID'),
            'discord_guild_id' => env_value('DISCORD_GUILD_ID'),
            'discord_required_role_id' => env_value('DISCORD_REQUIRED_ROLE_ID'),
        ]);
        break;

    case 'db-test':
        try {
            $host = (string) env_value('DB_HOST');
            $port = (string) env_value('DB_PORT', '3306');
            $name = (string) env_value('DB_NAME');
            $user = (string) env_value('DB_USER');
            $pass = (string) env_value('DB_PASS');

            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $host,
                $port,
                $name
            );

            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            $row = $pdo->query('SELECT 1 AS ok')->fetch();

            json_response([
                'ok' => true,
                'database' => 'connected',
                'result' => $row,
            ]);
        } catch (Throwable $e) {
            json_response([
                'ok' => false,
                'database' => 'failed',
                'error' => $e->getMessage(),
            ], 500);
        }
        break;

    default:
        json_response([
            'ok' => false,
            'error' => 'Action inconnue'
        ], 404);
}
