<?php
/**
 * Custom Router for Event Management System
 * Lightweight, dependency-free routing system
 */



/*
This Router file contains the functions that define how every HTTP request is handled in your application.

This router:

Looks at the URL

Finds which route matches

Runs middleware

Executes the correct function or controller

Loads a view

One-line definition:

👉 These functions describe what should happen when a specific URL is requested.

*/

class Router {
    private $routes = [];
    private $middleware = [];
    private $currentRoute = null;
    private $params = [];

    /**
     * Add a route
     * @param string $method HTTP method (GET, POST, PUT, DELETE)
     * @param string $pattern URL pattern with parameters like /users/{id}
     * @param callable|string $handler Controller method or closure
     * @param array $middleware Middleware to apply to this route
     */
    public function add($method, $pattern, $handler, $middleware = []) {
        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $pattern,
            'handler' => $handler,
            'middleware' => $middleware
        ];
    }

    /**
     * Add GET route
     */
    public function get($pattern, $handler, $middleware = []) {
        $this->add('GET', $pattern, $handler, $middleware);
    }

    /**
     * Add POST route
     */
    public function post($pattern, $handler, $middleware = []) {
        $this->add('POST', $pattern, $handler, $middleware);
    }

    /**
     * Add PUT route
     */
    public function put($pattern, $handler, $middleware = []) {
        $this->add('PUT', $pattern, $handler, $middleware);
    }

    /**
     * Add DELETE route
     */
    public function delete($pattern, $handler, $middleware = []) {
        $this->add('DELETE', $pattern, $handler, $middleware);
    }

    /**
     * Register middleware
     */
    public function middleware($name, $callback) {
        $this->middleware[$name] = $callback;
    }

    /**
     * Dispatch the request
     */
    public function dispatch() {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'];

        // Remove base path if exists
        $basePath = '/event-reports';
        if (strpos($uri, $basePath) === 0) {
            $uri = substr($uri, strlen($basePath));
        }

        // Normalize URI
        $uri = trim($uri, '/');

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method && $route['method'] !== 'ANY') {
                continue;
            }

            if ($this->matchRoute($route['pattern'], $uri, $params)) {
                $this->currentRoute = $route;
                $this->params = $params;
                
                // Apply middleware
                if (!$this->applyMiddleware($route['middleware'])) {
                    return;
                }

                // Execute handler
                $this->executeHandler($route['handler']);
                return;
            }
        }

        // No route found
        $this->handleNotFound();
    }

    /**
     * Match route pattern against URI
     */
    private function matchRoute($pattern, $uri, &$params) {
        // Normalize pattern (remove leading/trailing slashes)
        $pattern = trim($pattern, '/');

        // Special case: both pattern and uri are empty (root)
        if ($pattern === '' && $uri === '') {
            $params = [];
            return true;
        }

        // If pattern is empty but URI is not, no match
        if ($pattern === '' && $uri !== '') {
            return false;
        }

        // If URI is empty but pattern is not, no match
        if ($pattern !== '' && $uri === '') {
            return false;
        }

        // Convert {param} to named regex groups
        $regex = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';

        if (preg_match($regex, $uri, $matches)) {
            // Extract named parameters
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            return true;
        }
        return false;
    }

    /**
     * Apply middleware to route
     */
    private function applyMiddleware($middlewareNames) {
    foreach ($middlewareNames as $middlewareName) {
        if (isset($this->middleware[$middlewareName])) {

            // PASS ROUTE PARAMS HERE 👇
            $result = call_user_func(
                $this->middleware[$middlewareName],
                $this->params
            );

            if ($result === false) {
                return false;
            }
        }
    }
    return true;
}

    /**
     * Execute the route handler
     */
    private function executeHandler($handler) {
        if (is_callable($handler)) {
            call_user_func_array($handler, $this->params);
        } 
        elseif (is_string($handler) && strpos($handler, '@') !== false) {
            list($controllerClass, $method) = explode('@', $handler, 2);
            
            if (class_exists($controllerClass)) {
                global $pdo;  // ← this is now actually used
                $controller = new $controllerClass($pdo);   // ← pass $pdo here
                
                if (method_exists($controller, $method)) {
                    // Set named parameters to $_GET for backward compatibility
                    if (!empty($this->params)) {
                        foreach ($this->params as $key => $value) {
                            $_GET[$key] = $value;
                        }
                    }
                    
                    // Check if method accepts parameters
                    $reflection = new ReflectionMethod($controller, $method);
                    $parameters = $reflection->getParameters();
                    
                    // If method has parameters, pass them; otherwise don't pass any
                    if (count($parameters) > 0) {
                        call_user_func_array([$controller, $method], $this->params);
                    } else {
                        call_user_func([$controller, $method]);
                    }
                } else {
                    $this->handleNotFound();
                }
            } else {
                $this->handleNotFound();
            }
        } 
        else {
            $this->handleNotFound();
        }
    }
    /**
     * Handle 404 Not Found
     */
    private function handleNotFound() {
        http_response_code(404);
        $this->renderView('errors/404');
    }

    /**
     * Handle 405 Method Not Allowed
     */
    private function handleMethodNotAllowed() {
        http_response_code(405);
        header('Allow: ' . implode(', ', array_column($this->routes, 'method')));
        $this->renderView('errors/405');
    }

    /**
     * Render a view
     */
    private function renderView($view, $data = []) {
        extract($data);
        
        $viewPath = __DIR__ . '/../views/' . $view . '.php';
        if (file_exists($viewPath)) {
            require_once $viewPath;
        } else {
            echo "<h1>View not found: $view</h1>";
        }
    }

    /**
     * Get current route parameters
     */
    public function getParams() {
        return $this->params;
    }

    /**
     * Get current route
     */
    public function getCurrentRoute() {
        return $this->currentRoute;
    }
}