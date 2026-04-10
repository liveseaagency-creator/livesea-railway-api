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

function config_payload(): array
{
    return [
        'APP_ENV' => (string) env_value('APP_ENV', 'production'),
        'APP_DEBUG' => (string) env_value('APP_DEBUG', 'false'),

        'DB_HOST' => (string) env_value('DB_HOST'),
        'DB_PORT' => (string) env_value('DB_PORT', '3306'),
        'DB_NAME' => (string) env_value('DB_NAME'),
        'DB_USER' => (string) env_value('DB_USER'),
        'DB_PASS' => (string) env_value('DB_PASS'),

        'DISCORD_CLIENT_ID' => (string) env_value('DISCORD_CLIENT_ID'),
        'DISCORD_CLIENT_SECRET' => (string) env_value('DISCORD_CLIENT_SECRET'),
        'DISCORD_REDIRECT_URI' => (string) env_value('DISCORD_REDIRECT_URI'),
        'DISCORD_BOT_TOKEN' => (string) env_value('DISCORD_BOT_TOKEN'),
        'DISCORD_GUILD_ID' => (string) env_value('DISCORD_GUILD_ID'),
        'DISCORD_REQUIRED_ROLE_ID' => (string) env_value('DISCORD_REQUIRED_ROLE_ID'),

        'TIKMV_USER_LOOKUP_URL' => (string) env_value('TIKMV_USER_LOOKUP_URL', 'https://www.tikwm.com/api/user/info?unique_id=@%s'),
        'TIKMV_HTTP_TIMEOUT' => (string) env_value('TIKMV_HTTP_TIMEOUT', '20'),

        'SITE_NAME' => (string) env_value('SITE_NAME', 'LiveSea Agency'),
        'SITE_SUPPORT_EMAIL' => (string) env_value('SITE_SUPPORT_EMAIL', 'contact@livesea-agency.fr'),
        'SITE_DEFAULT_MANAGER_NAME' => (string) env_value('SITE_DEFAULT_MANAGER_NAME', 'Équipe LiveSea'),
        'SITE_DEFAULT_MANAGER_EMAIL' => (string) env_value('SITE_DEFAULT_MANAGER_EMAIL', 'contact@livesea-agency.fr'),
        'SITE_DEFAULT_MANAGER_DISCORD' => (string) env_value('SITE_DEFAULT_MANAGER_DISCORD', '@livesea.support'),
        'SITE_DEFAULT_SUPPORT_DELAY' => (string) env_value('SITE_DEFAULT_SUPPORT_DELAY', 'Réponse sous 24h à 48h'),
        'SITE_DEFAULT_AGENCY_ROLE' => (string) env_value('SITE_DEFAULT_AGENCY_ROLE', 'Créateur TikTok LIVE'),
        'SITE_AGENCY_TIKTOK_URL' => (string) env_value('SITE_AGENCY_TIKTOK_URL', 'https://www.tiktok.com/t/ZMhfUEPPT/')
    ];
}

$action = $_GET['action'] ?? 'ping';

switch ($action) {
    case 'ping':
        json_response([
            'ok' => true,
            'service' => 'livesea-railway-api',
            'status' => 'online',
            'time' => gmdate('c'),
        ]);
        break;

    case 'config':
        require_api_key();
        json_response([
            'ok' => true,
            'config' => config_payload(),
        ]);
        break;

    case 'site-info':
        require_api_key();
        $config = config_payload();

        json_response([
            'ok' => true,
            'site_name' => $config['SITE_NAME'],
            'site_support_email' => $config['SITE_SUPPORT_EMAIL'],
            'site_default_manager_name' => $config['SITE_DEFAULT_MANAGER_NAME'],
            'site_default_manager_email' => $config['SITE_DEFAULT_MANAGER_EMAIL'],
            'site_default_manager_discord' => $config['SITE_DEFAULT_MANAGER_DISCORD'],
            'site_default_support_delay' => $config['SITE_DEFAULT_SUPPORT_DELAY'],
            'site_default_agency_role' => $config['SITE_DEFAULT_AGENCY_ROLE'],
            'site_agency_tiktok_url' => $config['SITE_AGENCY_TIKTOK_URL'],
        ]);
        break;

    case 'db-test':
        require_api_key();

        try {
            $config = config_payload();

            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $config['DB_HOST'],
                $config['DB_PORT'],
                $config['DB_NAME']
            );

            $pdo = new PDO($dsn, $config['DB_USER'], $config['DB_PASS'], [
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
