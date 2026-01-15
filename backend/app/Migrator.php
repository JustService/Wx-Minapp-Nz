<?php

class Migrator
{
    private Database $database;
    private string $schemaPath;

    public function __construct(Database $database, string $schemaPath)
    {
        $this->database = $database;
        $this->schemaPath = $schemaPath;
    }

    public function migrate(): void
    {
        $this->database->ensureDatabaseExists();
        $pdo = $this->database->connect();
        $schemaSql = file_get_contents($this->schemaPath);
        if (!$schemaSql) {
            return;
        }
        $schemaSql = preg_replace('/DROP TABLE IF EXISTS[^;]+;/', '', $schemaSql);
        $schemaSql = preg_replace('/CREATE TABLE\s+`?([\w_]+)`?/i', 'CREATE TABLE IF NOT EXISTS `$1`', $schemaSql);
        $statements = array_filter(array_map('trim', explode(';', $schemaSql)));
        foreach ($statements as $statement) {
            if ($statement === '' || str_starts_with($statement, '--')) {
                continue;
            }
            $pdo->exec($statement);
        }
    }
}
