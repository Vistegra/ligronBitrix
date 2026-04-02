<?php

//require_once __DIR__ . '/../../const.php';

const DB_REMOTE_CONFIG = [
  'dbHost'     => 'mariadb-10.3',
  'dbName'     => 'ligron_user',
  'dbUsername' => 'root',
  'dbPass'     => ''
];

class DatabaseImporter
{
    private PDO $pdo;
    private string $sqlFile;
    private string $backupDir;
    private string $logDir;
    private string $logFile;

    public function __construct()
    {
        $this->sqlFile = __DIR__ . '/ligron.sql';
        $this->backupDir = __DIR__ . '/backup';
        $this->logDir = __DIR__ . '/logs';
    }

    public function run(array $argv): int
    {
        $options = $this->parseArgs($argv);
        
        $this->initLogFile();
        $this->log("=== Database import started ===");
        $this->log("SQL file: {$this->sqlFile}");
        
        if (!$this->connect()) {
            return 1;
        }

        if (!isset($options['no-backup'])) {
            if (!$this->createBackup()) {
                $this->log("Backup creation failed", 'ERROR');
                return 1;
            }
        }

        if (!$this->dropTables()) {
            $this->log("Failed to drop tables", 'ERROR');
            return 1;
        }

        if (!$this->importSql()) {
            $this->log("SQL import failed", 'ERROR');
            return 1;
        }

        $this->log("=== Import completed successfully! ===");
        return 0;
    }

    private function parseArgs(array $argv): array
    {
        $options = [];
        for ($i = 1; $i < count($argv); $i++) {
            $arg = $argv[$i];
            if (strpos($arg, '--') === 0) {
                if ($arg === '--force') {
                    $options['force'] = true;
                } elseif (strpos($arg, '--no-backup') === 0) {
                    $options['no-backup'] = true;
                } elseif (strpos($arg, '--log=') === 0) {
                    $options['log'] = substr($arg, 6);
                }
            }
        }
        return $options;
    }

    private function connect(): bool
    {
        $db = DB_REMOTE_CONFIG;

        try {
            $this->log("Connecting to database...");
            $dsn = "mysql:host=" . $db['dbHost'] . ";dbname=" . $db['dbName'] . ";charset=utf8mb4";
            $this->pdo = new PDO($dsn, $db['dbUsername'], $db['dbPass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            $this->log("Database connection: OK", 'SUCCESS');
            return true;
        } catch (PDOException $e) {
            $this->log("Connection error: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }

    private function createBackup(): bool
    {
        $this->log("Creating backup...");
        
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }

        $timestamp = date('Ymd_His');
        $backupFile = $this->backupDir . "/ligron_{$timestamp}.sql";

        try {
            $tables = $this->pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

            if (empty($tables)) {
                $this->log("No tables found for backup", 'WARNING');
                return true;
            }

            $backupContent = "-- Backup created: {$timestamp}\n-- Database: " . DB_REMOTE_CONFIG['dbName'] . "\n\n";
            $backupContent .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

            foreach ($tables as $table) {
                $create = $this->pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_ASSOC);
                $backupContent .= "DROP TABLE IF EXISTS `{$table}`;\n";
                $backupContent .= $create['Create Table'] . ";\n\n";
                
                $rows = $this->pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($rows as $row) {
                    $values = array_map(function($val) {
                        return $val === null ? 'NULL' : $this->pdo->quote($val);
                    }, array_values($row));
                    $columns = implode('`, `', array_keys($row));
                    $backupContent .= "INSERT INTO `{$table}` (`{$columns}`) VALUES (" . implode(', ', $values) . ");\n";
                }
                $backupContent .= "\n";
            }

            $backupContent .= "SET FOREIGN_KEY_CHECKS = 1;\n";
            
            file_put_contents($backupFile, $backupContent);
            $this->log("Backup created: {$backupFile}", 'SUCCESS');
            return true;
        } catch (PDOException $e) {
            $this->log("Backup error: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }

    private function dropTables(): bool
    {
        try {
            $this->log("Dropping existing tables...");
            
            $tables = $this->pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            
            if (empty($tables)) {
                $this->log("No tables to drop", 'WARNING');
                return true;
            }

            $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            
            foreach ($tables as $table) {
                $this->pdo->exec("DROP TABLE IF EXISTS `{$table}`");
            }
            
            $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            
            $this->log("Dropped tables: " . count($tables), 'SUCCESS');
            return true;
        } catch (PDOException $e) {
            $this->log("Error dropping tables: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }

    private function importSql(): bool
    {
        try {
            $this->log("Importing data from SQL file...");
            
            $sql = file_get_contents($this->sqlFile);
            if (!$sql) {
                $this->log("Failed to read file: {$this->sqlFile}", 'ERROR');
                return false;
            }

            $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            
            $statements = $this->splitSqlStatements($sql);
            $total = count($statements);
            
            foreach ($statements as $i => $statement) {
                $statement = trim($statement);
                if (!empty($statement)) {
                    $this->pdo->exec($statement);
                }
                $this->printProgress($i + 1, $total);
            }
            
            $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            
            $this->log("");
            $this->log("Imported statements: {$total}", 'SUCCESS');
            return true;
        } catch (PDOException $e) {
            $this->log("");
            $this->log("Import error: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }

    private function splitSqlStatements(string $sql): array
    {
        $sql = preg_replace('/--[^\n]*\n/', '', $sql);
        $sql = preg_replace('/\/\*[^*]*\*+(?:[^\/*][^*]*\*+)*\//', '', $sql);
        
        $statements = [];
        $current = '';
        $inString = false;
        $stringChar = '';
        
        for ($i = 0; $i < strlen($sql); $i++) {
            $char = $sql[$i];
            
            if (!$inString && ($char === '"' || $char === "'")) {
                $inString = true;
                $stringChar = $char;
            } elseif ($inString && $char === $stringChar && ($i === 0 || $sql[$i - 1] !== '\\')) {
                $inString = false;
            }
            
            if (!$inString && $char === ';') {
                $statements[] = $current;
                $current = '';
                continue;
            }
            
            $current .= $char;
        }
        
        if (trim($current)) {
            $statements[] = $current;
        }
        
        return $statements;
    }

    private function printProgress(int $current, int $total): void
    {
        $width = 40;
        $percent = $current / $total;
        $filled = (int)($width * $percent);
        $empty = $width - $filled;
        
        $bar = str_repeat('█', $filled) . str_repeat('░', $empty);
        $percentStr = round($percent * 100);
        
        echo "\r[{$bar}] {$percentStr}% ({$current}/{$total})";
    }

    private function initLogFile(): void
    {
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
        
        $timestamp = date('Ymd_His');
        $this->logFile = $this->logDir . "/import_{$timestamp}.log";
    }

    private function log(string $message, string $level = 'INFO'): void
    {
        $time = date('H:i:s');
        $logMessage = "[{$time}] [{$level}] {$message}\n";
        
        echo $logMessage;
        
        if ($this->logFile) {
            file_put_contents($this->logFile, $logMessage, FILE_APPEND);
        }
    }
}

$importer = new DatabaseImporter();
exit($importer->run($argv));
