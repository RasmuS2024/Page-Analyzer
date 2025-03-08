<?php

namespace WPA;

final class Connection
{
    private static ?Connection $conn = null;

    public function connect()
    {
        $databaseUrl = parse_url($_ENV['DATABASE_URL']);
        $username = $databaseUrl['user'];
        $password = $databaseUrl['pass'];
        $host = $databaseUrl['host'];
        $dbName = ltrim($databaseUrl['path'], '/');
        $pdo = new \PDO("pgsql:host=$host;dbname=$dbName", $username, $password);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        return $pdo;
    }

    public static function get()
    {
        if (null === static::$conn) {
            static::$conn = new self();
        }
        return static::$conn;
    }
}
