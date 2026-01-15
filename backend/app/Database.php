<?php

class Database
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function connect(?string $database = null): PDO
    {
        $host = $this->config['host'];
        $port = $this->config['port'];
        $charset = $this->config['charset'];
        $dbname = $database ?? $this->config['database'];
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $host, $port, $dbname, $charset);
        return new PDO($dsn, $this->config['username'], $this->config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    }

    public function connectServer(): PDO
    {
        $host = $this->config['host'];
        $port = $this->config['port'];
        $charset = $this->config['charset'];
        $dsn = sprintf('mysql:host=%s;port=%d;charset=%s', $host, $port, $charset);
        return new PDO($dsn, $this->config['username'], $this->config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    }

    public function ensureDatabaseExists(): void
    {
        $pdo = $this->connectServer();
        $database = $this->config['database'];
        $pdo->exec(sprintf('CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET %s', $database, $this->config['charset']));
    }
}
