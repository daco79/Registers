<?php
declare(strict_types=1);

function registers_db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = getenv('REGISTERS_DB_HOST') ?: 'localhost';
    $db = getenv('REGISTERS_DB_NAME') ?: 'Registers';
    $user = getenv('REGISTERS_DB_USER') ?: 'root';
    $pass = getenv('REGISTERS_DB_PASS') ?: '';
    $socket = getenv('REGISTERS_DB_SOCKET') ?: '/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock';

    $dsn = "mysql:unix_socket={$socket};dbname={$db};charset=utf8mb4";
    if (!is_readable($socket) && !file_exists($socket)) {
        $dsn = "mysql:host={$host};dbname={$db};charset=utf8mb4";
    }

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}

