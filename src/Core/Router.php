<?php
// src/Core/Router.php

require_once dirname(__DIR__) . '/config/config.php';
// We will likely need Session for route protection later
// require_once __DIR__ . '/Session.php';

class Router {
    protected array $routes = []; // Stores all defined routes
    protected array $routeParams = []; // Stores parameters extracted from the URL

    /**
     * Adds a route to the routing table.
     *
     * @param string $method The HTTP method (GET, POST, etc.).
     * @param string $route The URL pattern (e.g., /users/{id}).
     * @param mixed $handler The handler for the route (e.g., ['ControllerClass', 'methodName'] or a Closure).
     * @param array $middleware Optional middleware to run before the handler.
     */
    public function addRoute(string $method, string $route, mixed $handler, array $middleware = []): void {
        // Normalize route: remove leading/trailing slashes, ensure one leading slash
        $route = '/' . trim($route, '/');
        $this->routes[strtoupper($method)][$route] = ['handler' => $handler, 'middleware' => $middleware];
    }

    // Convenience methods for common HTTP verbs
    public function get(string $route, mixed $handler, array $middleware = []): void {
        $this->addRoute('GET', $route, $handler, $middleware);
    }

    public function post(string $route, mixed $handler, array $middleware = []): void {
        $this->addRoute('POST', $route, $handler, $middleware);
    }

    public function put(string $route, mixed $handler, array $middleware = []): void {
        $this->addRoute('PUT', $route, $handler, $middleware);
    }

    public function delete(string $route, mixed $handler, array $middleware = []): void {
        $this->addRoute('DELETE', $route, $handler, $middleware);
    }

    /**
     * Matches the current request URI against defined routes.
     *
     * @param string $method The HTTP method of the current request.
     * @param string $uri The URI of the current request.
     * @return array|false The matched route details (handler, middleware, params) or false if no match.
     */
    protected function match(string $method, string $uri): array|false {
        $method = strtoupper($method);
        $uri = '/' . trim($uri, '/'); // Normalize URI

        if (!isset($this->routes[$method])) {
            return false; // No routes for this HTTP method
        }

        foreach ($this->routes[$method] as $routePattern => $routeDetails) {
            // Convert route pattern to a regex: /users/{id} -> #^/users/([^/]+)$#
            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $routePattern);
            $regex = '#^' . $pattern . '$#';

            if (preg_match($regex, $uri, $matches)) {
                array_shift($matches); // Remove the full match
                $this->routeParams = $matches; // Store extracted parameters

                // Extract parameter names from the route pattern
                preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $routePattern, $paramNames);
                $paramNames = $paramNames[1]; // Get the captured group

                $namedParams = [];
                foreach ($paramNames as $index => $name) {
                    if (isset($this->routeParams[$index])) {
                        $namedParams[$name] = $this->routeParams[$index];
                    }
                }
                $this->routeParams = $namedParams; // Store named parameters

                return [
                    'handler' => $routeDetails['handler'],
                    'middleware' => $routeDetails['middleware'],
                    'params' => $this->routeParams
                ];
            }
        }
        return false; // No route matched
    }

    /**
     * Dispatches the request to the matched route's handler.
     *
     * @param string $method The HTTP request method.
     * @param string $uri The request URI.
     */
    public function dispatch(string $method, string $uri): void {
        $parsedUri = parse_url($uri, PHP_URL_PATH);
        $uriPath = rtrim($parsedUri ?: '/', '/'); // Ensure it's never empty, defaults to '/'
         if (empty($uriPath)) {
            $uriPath = '/';
        }


        $matchedRoute = $this->match($method, $uriPath);

        if ($matchedRoute === false) {
            // Handle 404 Not Found
            $this->handleNotFound();
            return;
        }

        $handler = $matchedRoute['handler'];
        $params = $matchedRoute['params'];
        // $middleware = $matchedRoute['middleware']; // Middleware handling to be added

        // TODO: Implement middleware execution here
        // foreach ($middleware as $mw) {
        //     call_user_func([new $mw(), 'handle']);
        // }

        if (is_callable($handler)) {
            // If handler is a Closure
            call_user_func_array($handler, $params);
        } elseif (is_array($handler) && count($handler) === 2) {
            // If handler is ['ControllerClass', 'methodName']
            $controllerName = $handler[0];
            $methodName = $handler[1];

            // Construct full controller class name with namespace (assuming a convention)
            $controllerClass = 'App\\Controllers\\' . $controllerName;

            if (class_exists($controllerClass)) {
                $controllerInstance = new $controllerClass();
                if (method_exists($controllerInstance, $methodName)) {
                    // Pass route parameters to the controller method
                    call_user_func_array([$controllerInstance, $methodName], $params);
                } else {
                    // Handle 500 Internal Server Error (method not found in controller)
                    $this->handleInternalError("Method {$methodName} not found in controller {$controllerClass}.");
                }
            } else {
                // Handle 500 Internal Server Error (controller class not found)
                $this->handleInternalError("Controller class {$controllerClass} not found.");
            }
        } else {
            // Handle 500 Internal Server Error (invalid handler)
            $this->handleInternalError("Invalid route handler configured.");
        }
    }

    /**
     * Handles 404 Not Found errors.
     */
    protected function handleNotFound(): void {
        http_response_code(404);
        // In a real app, you'd render a nice 404 page
        // For now, just echo a message.
        // You could load a view: View::render('errors/404');
        echo "<h1>404 Not Found</h1><p>The page you requested could not be found.</p>";
        exit;
    }

    /**
     * Handles 500 Internal Server Errors.
     * @param string $message Optional error message.
     */
    protected function handleInternalError(string $message = "An internal server error occurred."): void {
        http_response_code(500);
        // In a real app, you'd log this and render a generic error page.
        if (DEBUG_MODE) {
            echo "<h1>500 Internal Server Error</h1><p>{$message}</p>";
        } else {
            echo "<h1>500 Internal Server Error</h1><p>We are experiencing technical difficulties. Please try again later.</p>";
            // error_log("Internal Server Error: " . $message);
        }
        exit;
    }

    /**
     * Helper to get the current request URI.
     *
     * @return string
     */
    public static function getRequestUri(): string {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        // Remove query string from URI (e.g., /path?query=value -> /path)
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }
        return rawurldecode($uri);
    }

    /**
     * Helper to get the current request method.
     *
     * @return string
     */
    public static function getRequestMethod(): string {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }
}
