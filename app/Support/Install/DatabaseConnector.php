<?php

namespace App\Support\Install;

use Illuminate\Support\Facades\DB;
use PDO;
use Throwable;

class DatabaseConnector
{
    /**
     * Ensure the SQLite file exists and make it the default connection.
     *
     * @return array<string, string>
     */
    public function configureSqlite(string $path): array
    {
        if ($path !== ':memory:' && ! file_exists($path)) {
            touch($path);
        }

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => $path,
        ]);

        try {
            DB::connection('sqlite')->rollBack();
        } catch (Throwable) {
        }

        DB::purge('sqlite');

        return ['DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => $path];
    }

    /**
     * Make the connection the default, proving it works and creating the
     * database when it is missing but the server is reachable.
     *
     * @param  array{host: string, port: string, database: string, username: string, password: string}  $params
     *
     * @throws Throwable when the server cannot be reached
     */
    public function ensureConnection(string $driver, array $params): void
    {
        $this->applyRuntimeConfig($driver, $params);

        try {
            DB::connection($driver)->getPdo();
        } catch (Throwable) {
            $this->createDatabase($driver, $params);
            $this->applyRuntimeConfig($driver, $params);
            DB::connection($driver)->getPdo();
        }
    }

    /**
     * @param  array{host: string, port: string, database: string, username: string, password: string}  $params
     */
    private function applyRuntimeConfig(string $driver, array $params): void
    {
        config([
            'database.default' => $driver,
            "database.connections.{$driver}.host" => $params['host'],
            "database.connections.{$driver}.port" => $params['port'],
            "database.connections.{$driver}.database" => $params['database'],
            "database.connections.{$driver}.username" => $params['username'],
            "database.connections.{$driver}.password" => $params['password'],
        ]);
        DB::purge($driver);
    }

    /**
     * Connect to the server's maintenance database and create the target one.
     *
     * @param  array{host: string, port: string, database: string, username: string, password: string}  $params
     */
    private function createDatabase(string $driver, array $params): void
    {
        $dsn = $driver === 'pgsql'
            ? "pgsql:host={$params['host']};port={$params['port']};dbname=postgres"
            : "mysql:host={$params['host']};port={$params['port']}";

        $pdo = new PDO($dsn, $params['username'], $params['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        $name = $params['database'];
        $quoted = $driver === 'pgsql'
            ? '"'.str_replace('"', '""', $name).'"'
            : '`'.str_replace('`', '``', $name).'`';
        $pdo->exec('CREATE DATABASE '.$quoted);
    }
}
