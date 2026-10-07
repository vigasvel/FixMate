<?php
/**
 * FIXMATE - Database Configuration & Connection Engine
 * Connects to MySQL via PHP PDO (PHP Data Objects)
 */

if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', 'fixmate_db');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('DB_PORT')) define('DB_PORT', '3306');
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

class Database {
    private static ?PDO $instance = null;

    public static function connect(): PDO {
        if (self::$instance === null) {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                // If database doesn't exist yet, show friendly guidance
                $errorMsg = htmlspecialchars($e->getMessage());
                die("
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 25px; border: 1px solid #FECACA; background: #FEF2F2; border-radius: 8px; color: #991B1B;'>
                    <h3 style='margin-top:0;'>⚠️ FixMate Database Connection Notice</h3>
                    <p>Unable to connect to MySQL database <strong>" . DB_NAME . "</strong>.</p>
                    <p style='font-size: 0.9em; background: #FFFFFF; padding: 10px; border-radius: 4px; border: 1px solid #FCA5A5;'><strong>Error details:</strong> {$errorMsg}</p>
                    <h4>Quick Resolution Steps for XAMPP:</h4>
                    <ol style='font-size: 0.9em; line-height: 1.6;'>
                        <li>Ensure <strong>Apache</strong> and <strong>MySQL</strong> are started in XAMPP Control Panel.</li>
                        <li>Open <a href='http://localhost/phpmyadmin' target='_blank' style='color:#2563EB;'>phpMyAdmin</a>.</li>
                        <li>Import the file <code>database/fixmate_db.sql</code> to create the database and sample records.</li>
                        <li>Refresh this page.</li>
                    </ol>
                </div>
                ");
            }
        }
        return self::$instance;
    }
}

// Global helper function for concise queries
function getDB(): PDO {
    return Database::connect();
}
