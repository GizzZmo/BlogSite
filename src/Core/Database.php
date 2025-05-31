<?php
// src/Core/Database.php

// Ensure the main configuration file is loaded.
// This is crucial as it defines DB_HOST, DB_NAME, etc.
// In a real application, an autoloader and a single entry point (like public/index.php)
// would handle including necessary files. For now, we'll include it directly.
require_once dirname(__DIR__) . '/config/config.php';

class Database {
    private static ?PDO $instance = null; // Static property to hold the single instance (Singleton pattern)
    private PDO $connection; // Holds the PDO connection object

    /**
     * Private constructor to prevent direct object creation.
     * Establishes the database connection.
     */
    private function __construct() {
        // Data Source Name (DSN) for PDO connection
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

        // PDO options
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on errors
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch associative arrays by default
            PDO::ATTR_EMULATE_PREPARES   => false,                  // Disable emulation of prepared statements for security
        ];

        try {
            // Attempt to create a new PDO instance
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // If connection fails, display an error message.
            // In a production environment, you would log this error and show a generic message to the user.
            if (DEBUG_MODE) {
                // More detailed error for development
                throw new PDOException("Database Connection Error: " . $e->getMessage() . "<br>DSN: " . $dsn, (int)$e->getCode());
            } else {
                // Generic error for production
                // Log the detailed error: error_log("Database Connection Error: " . $e->getMessage() . " DSN: " . $dsn);
                throw new PDOException("Could not connect to the database. Please try again later.", (int)$e->getCode());
            }
        }
    }

    /**
     * Gets the single instance of the Database class.
     * If an instance doesn't exist, it creates one.
     * This is part of the Singleton pattern to ensure only one database connection.
     *
     * @return PDO The PDO connection object.
     */
    public static function getInstance(): PDO {
        if (self::$instance === null) {
            // Create a new instance of this Database class, which calls the private constructor
            $dbInstance = new self();
            // Store the actual PDO connection object from the newly created Database object
            self::$instance = $dbInstance->connection;
        }
        return self::$instance;
    }

    /**
     * A helper method to directly get the PDO connection object from an instance.
     * This can be useful if you somehow have an instance of Database and need the PDO object.
     * However, the primary way to get the connection is via the static `getInstance()` method.
     *
     * @return PDO
     */
    public function getConnection(): PDO {
        return $this->connection;
    }


    // --- Convenience Methods for Common Queries (Optional but Recommended) ---

    /**
     * Prepares and executes a SQL query with optional parameters.
     * This is a fundamental method for interacting with the database.
     *
     * @param string $sql The SQL query string.
     * @param array $params An associative array of parameters to bind to the query.
     * @return PDOStatement|false The PDOStatement object on success, or false on failure.
     */
    public static function query(string $sql, array $params = []): PDOStatement|false {
        try {
            $stmt = self::getInstance()->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            // Handle query errors
            if (DEBUG_MODE) {
                error_log("Database Query Error: " . $e->getMessage() . " | SQL: " . $sql . " | Params: " . print_r($params, true));
                // Optionally re-throw or handle more gracefully
            }
            // Depending on your error handling strategy, you might return false or throw an exception.
            return false;
        }
    }

    /**
     * Fetches a single row from the database.
     *
     * @param string $sql The SQL query string.
     * @param array $params An associative array of parameters.
     * @param int $fetchStyle The PDO fetch style.
     * @return mixed The result row, or false if no row is found or an error occurs.
     */
    public static function fetchOne(string $sql, array $params = [], int $fetchStyle = PDO::FETCH_ASSOC): mixed {
        $stmt = self::query($sql, $params);
        return $stmt ? $stmt->fetch($fetchStyle) : false;
    }

    /**
     * Fetches all rows from the database that match the query.
     *
     * @param string $sql The SQL query string.
     * @param array $params An associative array of parameters.
     * @param int $fetchStyle The PDO fetch style.
     * @return array An array of result rows, or an empty array if no rows are found or an error occurs.
     */
    public static function fetchAll(string $sql, array $params = [], int $fetchStyle = PDO::FETCH_ASSOC): array {
        $stmt = self::query($sql, $params);
        return $stmt ? $stmt->fetchAll($fetchStyle) : [];
    }

    /**
     * Executes an INSERT, UPDATE, or DELETE statement and returns the number of affected rows.
     *
     * @param string $sql The SQL query string.
     * @param array $params An associative array of parameters.
     * @return int The number of affected rows. Returns 0 if no rows were affected or an error occurred.
     */
    public static function execute(string $sql, array $params = []): int {
        $stmt = self::query($sql, $params);
        return $stmt ? $stmt->rowCount() : 0;
    }

    /**
     * Gets the ID of the last inserted row or sequence value.
     *
     * @param string|null $name Name of the sequence object from which the ID should be returned (for some drivers).
     * @return string|false The ID of the last inserted row, or false on failure.
     */
    public static function lastInsertId(string $name = null): string|false {
        try {
            return self::getInstance()->lastInsertId($name);
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                error_log("Error getting lastInsertId: " . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * Private clone method to prevent cloning of the instance.
     */
    private function __clone() {}

    /**
     * Private unserialize method to prevent unserializing of the instance.
     */
    public function __wakeup() {}
}
