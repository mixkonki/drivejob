<?php
// Φυλάξτε αυτό ως src/Core/Router.php

namespace Drivejob\Core;

class Router
{
    private $routes = [];
    private $notFoundCallback;

    public function get($path, $callback)
    {
        $this->routes['GET'][$path] = $callback;
        return $this;
    }

    public function post($path, $callback)
    {
        $this->routes['POST'][$path] = $callback;
        return $this;
    }

    public function notFound($callback)
    {
        $this->notFoundCallback = $callback;
        return $this;
    }

    public function resolve()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = $this->getPath();
        
        // Έλεγχος αν υπάρχει ακριβής διαδρομή
        if (isset($this->routes[$method][$path])) {
            $callback = $this->routes[$method][$path];
            return $this->executeCallback($callback);
        }

        // Έλεγχος για παραμετροποιημένες διαδρομές
        foreach ($this->routes[$method] as $route => $callback) {
            $pattern = $this->convertRouteToRegex($route);
            
            if (preg_match($pattern, $path, $matches)) {
                // Αφαίρεση του πρώτου στοιχείου (ολόκληρο το ταίριασμα)
                array_shift($matches);
                
                return $this->executeCallback($callback, $matches);
            }
        }

        // Αν δεν βρέθηκε καμία διαδρομή
        if ($this->notFoundCallback) {
            return call_user_func($this->notFoundCallback);
        }
        
        // Προεπιλεγμένη συμπεριφορά για 404
        header("HTTP/1.0 404 Not Found");
        echo '404 Page Not Found';
        return null;
    }

    private function getPath()
    {
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        $position = strpos($path, '?');
        
        if ($position !== false) {
            $path = substr($path, 0, $position);
        }
        
        // Αφαίρεση του script path από το URI
        $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
        $scriptDir = $scriptDir === '/' ? '' : $scriptDir;
        
        if (strpos($path, $scriptDir) === 0) {
            $path = substr($path, strlen($scriptDir));
        }
        
        // Καθαρισμός του path
        $path = trim($path, '/');
        $path = '/' . $path;
        
        return $path ?: '/';
    }

    private function convertRouteToRegex($route)
    {
        // Αντικατάσταση παραμέτρων της μορφής {id} με ομάδες regex
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $route);
        
        // Προσθήκη ^ και $ για ακριβές ταίριασμα και προετοιμασία για preg_match
        return "#^{$pattern}$#";
    }

    private function executeCallback($callback, $params = [])
    {
        if (is_callable($callback)) {
            return call_user_func_array($callback, $params);
        }
        
        // Αν το callback είναι array [controller, method]
        if (is_array($callback)) {
            [$controller, $method] = $callback;
            
            if (is_string($controller)) {
                $controller = new $controller();
            }
            
            return call_user_func_array([$controller, $method], $params);
        }
        
        // Αν το callback είναι string "Controller@method"
        if (is_string($callback) && strpos($callback, '@') !== false) {
            [$controller, $method] = explode('@', $callback, 2);
            $controller = new $controller();
            
            return call_user_func_array([$controller, $method], $params);
        }
        
        throw new \Exception("Invalid callback");
    }
}