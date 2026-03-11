<?php
class Database {
    private static $instance = null;
    private $pdo;
    private $mode; // 'pdo_sqlite' or 'pdo_mysql'

    private function __construct(){
        // Choose driver based on DB_DRIVER: prefer MySQL, fallback to SQLite if explicitly set
        if (!class_exists('PDO')) {
            throw new \RuntimeException('PDO is required. Enable the PDO extension in php.ini.');
        }
        $driverRequested = defined('DB_DRIVER') ? DB_DRIVER : 'mysql';
        $drivers = PDO::getAvailableDrivers();

        if ($driverRequested === 'mysql') {
            if (!in_array('mysql', $drivers, true)) {
                throw new \RuntimeException('PDO MySQL driver not available. Enable pdo_mysql in php.ini.');
            }
            $this->mode = 'pdo_mysql';
            $host = defined('DB_HOST') ? DB_HOST : '127.0.0.1';
            $port = defined('DB_PORT') ? DB_PORT : '3306';
            $dbname = defined('DB_NAME') ? DB_NAME : 'natiora_radio';
            $user = defined('DB_USER') ? DB_USER : 'root';
            $pass = defined('DB_PASS') ? DB_PASS : '';
            $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
            // Connect without specifying dbname to allow CREATE DATABASE in import scripts
            $this->pdo = new PDO($dsn, $user, $pass);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // If the requested database exists, reconnect using the database name so queries work without explicit schema
            try {
                $stmt = $this->pdo->prepare('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = :db');
                $stmt->execute([':db' => $dbname]);
                $found = $stmt->fetchColumn();
                if ($found) {
                    $dsnDb = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
                    $this->pdo = new PDO($dsnDb, $user, $pass);
                    $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                }
            } catch (\Exception $e) {
                // ignore and keep server-level connection
            }
            return;
        }

        // Fallback to SQLite when explicitly requested
        if ($driverRequested === 'sqlite') {
            if (!in_array('sqlite', $drivers, true)) {
                throw new \RuntimeException('PDO SQLite driver not available. Enable pdo_sqlite in php.ini.');
            }
            $this->mode = 'pdo_sqlite';
            $dsn = 'sqlite:' . DB_PATH;
            $this->pdo = new PDO($dsn);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return;
        }
        throw new \RuntimeException('Unsupported DB_DRIVER: ' . $driverRequested);
    }

    public static function getInstance(){
        if(self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    public function isPdo(){
        return strpos($this->mode, 'pdo_') === 0;
    }

    public function getConnection(){
        return $this->pdo;
    }
    public function init(){
        if ($this->mode === 'pdo_sqlite') {
            // SQLite schema
            $sql = "CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email TEXT UNIQUE NOT NULL,
                password TEXT NOT NULL,
                name TEXT,
                role TEXT DEFAULT 'user',
                created_at TEXT,
                bio TEXT,
                avatar TEXT,
                phone TEXT
            )";
            $this->pdo->exec($sql);

            $sql2 = "CREATE TABLE IF NOT EXISTS user_actions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                action TEXT,
                details TEXT,
                ip_address TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )";
            $this->pdo->exec($sql2);
            // played_logs: store real playback history (timestamped)
            $sql3 = "CREATE TABLE IF NOT EXISTS played_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT,
                artist TEXT,
                source TEXT,
                duration INTEGER,
                user_id INTEGER,
                played_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )";
            $this->pdo->exec($sql3);
            // messages table: simple user <-> admin messages
            $sql4 = "CREATE TABLE IF NOT EXISTS messages (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                sender_id INTEGER,
                recipient_id INTEGER,
                subject TEXT,
                body TEXT,
                is_read INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )";
            $this->pdo->exec($sql4);
            // Ensure sender_email column exists for messaging features
            try {
                $cols = [];
                $stmt = $this->pdo->query("PRAGMA table_info(messages)");
                $info = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($info as $c) $cols[] = $c['name'];
                if (!in_array('sender_email', $cols)) {
                    $this->pdo->exec("ALTER TABLE messages ADD COLUMN sender_email TEXT");
                }
                if (!in_array('context_type', $cols)) {
                    $this->pdo->exec("ALTER TABLE messages ADD COLUMN context_type TEXT");
                }
                if (!in_array('context_id', $cols)) {
                    $this->pdo->exec("ALTER TABLE messages ADD COLUMN context_id INTEGER");
                }
            } catch (Throwable $e) {
                // ignore if PRAGMA unsupported or other error
            }
                // Emissions table (schedule)
                $sql5 = "CREATE TABLE IF NOT EXISTS emissions (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    day TEXT,
                    time TEXT,
                    title TEXT NOT NULL,
                    presenter TEXT,
                    duration TEXT,
                    level TEXT,
                    category TEXT,
                    src TEXT,
                    description TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME
                )";
                $this->pdo->exec($sql5);
                // Playlists
                $sql6 = "CREATE TABLE IF NOT EXISTS playlists (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    title TEXT NOT NULL,
                    description TEXT,
                    cover TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME
                )";
                $this->pdo->exec($sql6);
                // Playlist songs (many-to-many simple mapping)
                $sql7 = "CREATE TABLE IF NOT EXISTS playlist_songs (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    playlist_id INTEGER NOT NULL,
                    song_title TEXT NOT NULL,
                    position INTEGER
                )";
                $this->pdo->exec($sql7);
        } elseif ($this->mode === 'pdo_mysql') {
            // MySQL schema compatible with rn.sql
            $sql = "CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                name VARCHAR(255),
                bio TEXT,
                avatar VARCHAR(500),
                phone VARCHAR(20),
                role ENUM('user','admin') DEFAULT 'user',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            $this->pdo->exec($sql);

            $sql2 = "CREATE TABLE IF NOT EXISTS user_actions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                action VARCHAR(100),
                details TEXT,
                ip_address VARCHAR(45),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            $this->pdo->exec($sql2);
            // played_logs table for MySQL
            $sql3 = "CREATE TABLE IF NOT EXISTS played_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255),
                artist VARCHAR(255),
                source VARCHAR(1000),
                duration INT,
                user_id INT,
                played_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            $this->pdo->exec($sql3);
            // messages table for MySQL
            $sql4 = "CREATE TABLE IF NOT EXISTS messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                sender_id INT,
                recipient_id INT,
                subject VARCHAR(255),
                body TEXT,
                is_read TINYINT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            $this->pdo->exec($sql4);
            // Ensure sender_email column exists for messaging features (MySQL)
            try {
                $res = $this->pdo->query("SHOW COLUMNS FROM messages LIKE 'sender_email'");
                $found = $res->fetchAll(PDO::FETCH_ASSOC);
                if (empty($found)) {
                    $this->pdo->exec("ALTER TABLE messages ADD COLUMN sender_email VARCHAR(255) NULL");
                }
                $res2 = $this->pdo->query("SHOW COLUMNS FROM messages LIKE 'context_type'");
                $found2 = $res2->fetchAll(PDO::FETCH_ASSOC);
                if (empty($found2)) {
                    $this->pdo->exec("ALTER TABLE messages ADD COLUMN context_type VARCHAR(100) NULL");
                }
                $res3 = $this->pdo->query("SHOW COLUMNS FROM messages LIKE 'context_id'");
                $found3 = $res3->fetchAll(PDO::FETCH_ASSOC);
                if (empty($found3)) {
                    $this->pdo->exec("ALTER TABLE messages ADD COLUMN context_id INT NULL");
                }
            } catch (Throwable $e) {
                // ignore permission errors
            }
        }
    }
}
