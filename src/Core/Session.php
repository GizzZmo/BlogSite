<?php
// src/Core/Session.php

// Ensure the main configuration file is loaded.
// This can provide settings for session management in the future.
require_once dirname(__DIR__) . '/config/config.php';

class Session {
    // Default session cookie parameters (can be overridden by config later)
    private const DEFAULT_LIFETIME = 0; // Expires when browser closes
    private const DEFAULT_PATH = '/';
    private const DEFAULT_DOMAIN = ''; // Current domain
    private const DEFAULT_SECURE = false; // Send only over HTTPS (set to true in production)
    private const DEFAULT_HTTPONLY = true; // Prevent JavaScript access to session cookie
    private const DEFAULT_SAMESITE = 'Lax'; // CSRF protection

    /**
     * Starts a new session or resumes an existing one with secure settings.
     *
     * @param array $options Associative array of session cookie parameters.
     * Keys: 'lifetime', 'path', 'domain', 'secure', 'httponly', 'samesite'
     * @return bool True on success, false on failure.
     */
    public static function start(array $options = []): bool {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return true; // Session already active
        }

        // Get session cookie parameters, falling back to defaults
        $lifetime = $options['lifetime'] ?? self::DEFAULT_LIFETIME;
        $path = $options['path'] ?? self::DEFAULT_PATH;
        $domain = $options['domain'] ?? ($_ENV['APP_COOKIE_DOMAIN'] ?? self::DEFAULT_DOMAIN); // Allow .env override
        $secure = $options['secure'] ?? ($_ENV['APP_ENV'] === 'production' ? true : self::DEFAULT_SECURE); // Secure in production
        $httponly = $options['httponly'] ?? self::DEFAULT_HTTPONLY;
        $samesite = $options['samesite'] ?? self::DEFAULT_SAMESITE;

        // Set session cookie parameters
        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
            'httponly' => $httponly,
            'samesite' => $samesite
        ]);

        // Start the session
        if (session_start()) {
            // Regenerate session ID periodically to prevent session fixation
            // This is a basic implementation; more robust timing could be added.
            if (!self::has('session_last_regenerated_time')) {
                self::set('session_last_regenerated_time', time());
            } else {
                // Regenerate every 30 minutes (1800 seconds) for example
                if (time() - self::get('session_last_regenerated_time', 0) > 1800) {
                    self::regenerate();
                    self::set('session_last_regenerated_time', time());
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Sets a session variable.
     *
     * @param string $key The key of the session variable.
     * @param mixed $value The value to store.
     */
    public static function set(string $key, mixed $value): void {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            self::start(); // Ensure session is started
        }
        $_SESSION[$key] = $value;
    }

    /**
     * Gets a session variable.
     *
     * @param string $key The key of the session variable.
     * @param mixed $default The default value to return if the key is not found.
     * @return mixed The value of the session variable, or the default value.
     */
    public static function get(string $key, mixed $default = null): mixed {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            self::start();
        }
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Checks if a session variable exists.
     *
     * @param string $key The key of the session variable.
     * @return bool True if the key exists, false otherwise.
     */
    public static function has(string $key): bool {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            self::start();
        }
        return isset($_SESSION[$key]);
    }

    /**
     * Removes a session variable.
     *
     * @param string $key The key of the session variable to remove.
     */
    public static function unset(string $key): void {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            self::start();
        }
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }

    /**
     * Destroys the current session.
     * This removes all session data and invalidates the session cookie.
     */
    public static function destroy(): void {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            self::start(); // Ensure session is started before trying to destroy
        }

        // Unset all session variables
        $_SESSION = [];

        // Delete the session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000, // Set to a past time
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        // Finally, destroy the session
        session_destroy();
    }

    /**
     * Regenerates the session ID.
     * This is important for security to prevent session fixation attacks.
     *
     * @param bool $deleteOldSession Whether to delete the old session file.
     */
    public static function regenerate(bool $deleteOldSession = true): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id($deleteOldSession);
        }
    }

    /**
     * Sets a flash message that will be available on the next request only.
     *
     * @param string $key The key for the flash message.
     * @param mixed $message The message to store.
     */
    public static function setFlash(string $key, mixed $message): void {
        self::set('_flash_' . $key, $message);
    }

    /**
     * Gets a flash message and then removes it.
     *
     * @param string $key The key for the flash message.
     * @param mixed $default The default value if the flash message is not found.
     * @return mixed The flash message or default value.
     */
    public static function getFlash(string $key, mixed $default = null): mixed {
        $flashKey = '_flash_' . $key;
        $message = self::get($flashKey, $default);
        if (self::has($flashKey)) {
            self::unset($flashKey);
        }
        return $message;
    }

    /**
     * Checks if a flash message exists.
     *
     * @param string $key The key for the flash message.
     * @return bool True if the flash message exists, false otherwise.
     */
    public static function hasFlash(string $key): bool {
        return self::has('_flash_' . $key);
    }
}
