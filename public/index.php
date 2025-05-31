<?php
// public/index.php

// --- Define a base path constant for easier file includes ---
// This ensures that paths are correct regardless of where this script is included from.
define('BASE_PATH', dirname(__DIR__));

// --- Error Reporting (from config, but good to have a fallback here too) ---
// We'll rely on config.php for this, but ensure it's loaded.
// If config.php isn't loaded yet, this provides a basic level of error display.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- Autoloading (Composer) ---
// This line assumes you have run `composer install` in your project root.
// It loads all the dependencies and your own classes if configured in composer.json.
if (file_exists(BASE_PATH . '/vendor/autoload.php')) {
    require_once BASE_PATH . '/vendor/autoload.php';
} else {
    // Fallback or error message if Composer autoload is not found
    // This is critical for the application to run.
    echo "<h1>Composer Autoload Not Found</h1>";
    echo "<p>Please run 'composer install' in your project root directory.</p>";
    echo "<p>Attempted to load: " . htmlspecialchars(BASE_PATH . '/vendor/autoload.php') . "</p>";
    // For now, we'll manually require core files if autoload isn't there,
    // but this is not a long-term solution.
    require_once BASE_PATH . '/src/config/config.php'; // Loads .env variables if phpdotenv is used
    require_once BASE_PATH . '/src/Core/Session.php';
    require_once BASE_PATH . '/src/Core/Router.php';
    // Add other core classes here if not using Composer yet
}


// --- Environment Variables (using phpdotenv) ---
// The .env file should be in the project root (BASE_PATH).
// This needs to be loaded BEFORE config.php if config.php relies on $_ENV.
// The `vlucas/phpdotenv` library is typically loaded via Composer.
try {
    if (class_exists('Dotenv\Dotenv') && file_exists(BASE_PATH . '/.env')) {
        $dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
        $dotenv->load();
    } elseif (!class_exists('Dotenv\Dotenv')) {
        // This message is helpful during setup if phpdotenv is missing
        // echo "<p><strong>Notice:</strong> phpdotenv library not found. Consider running 'composer require vlucas/phpdotenv'. Falling back to constants defined in config.php if $_ENV is not populated by web server.</p>";
    }
} catch (\Dotenv\Exception\InvalidPathException $e) {
    // Handle error if .env file is not found or path is invalid
    // echo "<p><strong>Error:</strong> Could not load .env file. " . $e->getMessage() . "</p>";
    // Application can continue if config.php has defaults or $_ENV is set by server
}


// --- Load Core Application Files ---
// config.php should be loaded after Dotenv to ensure $_ENV variables are available.
// If not using Composer, ensure these are required. With Composer, they might be autoloaded.
if (!defined('DB_HOST')) { // Check if config constants are already defined
    require_once BASE_PATH . '/src/config/config.php';
}
if (!class_exists('Session')) {
    require_once BASE_PATH . '/src/Core/Session.php';
}
if (!class_exists('Router')) {
    require_once BASE_PATH . '/src/Core/Router.php';
}
// Later, you might have a View class, Model base class, etc.
// require_once BASE_PATH . '/src/Core/View.php';


// --- Initialize Session ---
// Session::start() should be called early, especially if using flash messages or auth.
// The Session class itself handles checking if a session is already active.
Session::start([
    // Example: Override session cookie settings if needed
    // 'lifetime' => 3600, // 1 hour
    // 'secure' => ($_ENV['APP_ENV'] ?? 'development') === 'production', // True in production
    // 'samesite' => 'Strict'
]);


// --- Initialize Router ---
$router = new Router();


// --- Define Routes ---
// This is where you will list all the routes for your application.
// We'll use a separate file for routes later to keep this clean (e.g., src/config/routes.php).

// Example: Homepage route
$router->get('/', function () {
    // For now, just echo. Later, this will call a controller method.
    // e.g., return (new App\Controllers\PageController())->home();
    echo "<h1>Welcome to the Blog Platform!</h1>";
    echo "<p>Current App URL: " . htmlspecialchars(APP_URL) . "</p>";
    echo "<p><a href='" . APP_URL . "/public/test-page'>Test Page</a></p>";
    echo "<p><a href='" . APP_URL . "/public/user/123'>User Profile (ID: 123)</a></p>";
    echo "<p><a href='" . APP_URL . "/public/posts/my-example-post'>Example Post</a></p>";
    echo "<p><a href='" . APP_URL . "/public/non-existent-page'>Non Existent Page (404)</a></p>";

    // Test flash message
    Session::setFlash('success', 'Homepage loaded successfully!');
});

// Example: A simple test page
$router->get('/test-page', function () {
    echo "<h1>Test Page</h1>";
    if (Session::hasFlash('success')) {
        echo "<p style='color:green;'>" . htmlspecialchars(Session::getFlash('success')) . "</p>";
    }
    echo "<p><a href='" . APP_URL . "/public/'>Back to Home</a></p>";
});

// Example: Route with a parameter
$router->get('/user/{id}', function ($id) {
    // Later: (new App\Controllers\UserController())->show($id);
    echo "<h1>User Profile</h1><p>User ID: " . htmlspecialchars($id) . "</p>";
});

// Example: Route with a string parameter (slug)
$router->get('/posts/{slug}', function ($slug) {
    // Later: (new App\Controllers\PostController())->show($slug);
    echo "<h1>Blog Post</h1><p>Post Slug: " . htmlspecialchars($slug) . "</p>";
});

// --- More routes will be added here ---
// $router->get('/register', [App\Controllers\AuthController::class, 'showRegistrationForm']);
// $router->post('/register', [App\Controllers\AuthController::class, 'register']);
// $router->get('/login', [App\Controllers\AuthController::class, 'showLoginForm']);
// $router->post('/login', [App\Controllers\AuthController::class, 'login']);
// $router->get('/logout', [App\Controllers\AuthController::class, 'logout']);


// --- Dispatch the Request ---
// The router will match the current URL and method to a defined route
// and execute its handler (Closure or controller method).
$requestMethod = Router::getRequestMethod();
$requestUri = Router::getRequestUri();

// The APP_URL might contain a subdirectory if the app is not in the web root.
// We need to strip this subdirectory from the request URI before passing it to the router.
$appUrlPath = parse_url(APP_URL, PHP_URL_PATH) ?? '';
$basePathForRouter = rtrim($appUrlPath, '/'); // e.g., /php-multi-user-blog

if ($basePathForRouter !== '' && strpos($requestUri, $basePathForRouter) === 0) {
    $uriForRouter = substr($requestUri, strlen($basePathForRouter));
    if (empty($uriForRouter)) {
        $uriForRouter = '/'; // Ensure it's at least '/'
    }
} else {
    $uriForRouter = $requestUri;
}


$router->dispatch($requestMethod, $uriForRouter);

