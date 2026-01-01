<?php
/**
 * DiabetaCare - HTTP Router
 * 
 * Simple regex-based router with middleware support.
 * Routes are matched in order of registration.
 */

declare(strict_types=1);

namespace DiabetaCare\Core;

class Router
{
    private array $routes = [];
    private array $middleware = [];
    private array $groupMiddleware = [];
    private string $groupPrefix = '';

    /**
     * Register GET route
     */
    public function get(string $path, callable|array $handler): self
    {
        return $this->addRoute('GET', $path, $handler);
    }

    /**
     * Register POST route
     */
    public function post(string $path, callable|array $handler): self
    {
        return $this->addRoute('POST', $path, $handler);
    }

    /**
     * Register PUT route
     */
    public function put(string $path, callable|array $handler): self
    {
        return $this->addRoute('PUT', $path, $handler);
    }

    /**
     * Register DELETE route
     */
    public function delete(string $path, callable|array $handler): self
    {
        return $this->addRoute('DELETE', $path, $handler);
    }

    /**
     * Add route with method
     */
    private function addRoute(string $method, string $path, callable|array $handler): self
    {
        $fullPath = $this->groupPrefix . $path;
        
        // Convert path parameters to regex
        $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $fullPath);
        $pattern = '#^' . $pattern . '$#';

        $this->routes[] = [
            'method' => $method,
            'path' => $fullPath,
            'pattern' => $pattern,
            'handler' => $handler,
            'middleware' => array_merge($this->middleware, $this->groupMiddleware),
        ];

        return $this;
    }

    /**
     * Add global middleware
     */
    public function middleware(callable|string $middleware): self
    {
        $this->middleware[] = $middleware;
        return $this;
    }

    /**
     * Create route group with prefix and middleware
     */
    public function group(string $prefix, array $middleware, callable $callback): void
    {
        $previousPrefix = $this->groupPrefix;
        $previousMiddleware = $this->groupMiddleware;

        $this->groupPrefix = $previousPrefix . $prefix;
        $this->groupMiddleware = array_merge($previousMiddleware, $middleware);

        $callback($this);

        $this->groupPrefix = $previousPrefix;
        $this->groupMiddleware = $previousMiddleware;
    }

    /**
     * Dispatch request to matching route
     */
    public function dispatch(Request $request): Response
    {
        $method = $request->getMethod();
        $path = $request->getPath();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $path, $matches)) {
                // Extract named parameters
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $request->setParams($params);

                // Run middleware chain
                $handler = $route['handler'];
                $middlewareStack = $route['middleware'];

                return $this->runMiddlewareChain($request, $middlewareStack, $handler);
            }
        }

        return Response::notFound("Endpoint not found: {$method} {$path}");
    }

    /**
     * Run middleware chain and then handler
     */
    private function runMiddlewareChain(Request $request, array $middleware, callable|array $handler): Response
    {
        if (empty($middleware)) {
            return $this->callHandler($handler, $request);
        }

        $current = array_shift($middleware);
        
        // Resolve middleware class
        if (is_string($current)) {
            $current = new $current();
        }

        // Call middleware with next callback
        $next = function (Request $req) use ($middleware, $handler): Response {
            return $this->runMiddlewareChain($req, $middleware, $handler);
        };

        return $current->handle($request, $next);
    }

    /**
     * Call route handler
     */
    private function callHandler(callable|array $handler, Request $request): Response
    {
        // Handle [ControllerClass, 'method'] format
        if (is_array($handler) && count($handler) === 2) {
            [$class, $method] = $handler;
            
            if (is_string($class)) {
                $class = new $class();
            }
            
            return $class->$method($request);
        }

        // Handle callable
        return $handler($request);
    }
}
