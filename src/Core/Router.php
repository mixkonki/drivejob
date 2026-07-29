<?php

namespace Drivejob\Core;

class Router
{
    private $routes = [];
    private $notFoundCallback;
    private $baseUrl;
    private $middlewares = [];
    private $routeMiddlewares = [];
    private $container;
    private $namedRoutes = [];
    private $currentRouteName = null;
    private $currentPrefix = '';
    private $currentMiddlewares = [];

    public function __construct($baseUrl = '', $container = null)
    {
        $this->baseUrl = $baseUrl;
        $this->container = $container ?? Container::getInstance();
    }

    /**
     * Προσθέτει μια διαδρομή GET
     *
     * @param string $path Η διαδρομή URL
     * @param mixed $callback Η συνάρτηση callback ή "Controller@method"
     * @param array $middlewares Προαιρετικά middlewares για αυτή τη διαδρομή
     * @return $this
     */
    public function get($path, $callback, $middlewares = [])
    {
        $this->addRoute('GET', $path, $callback, $middlewares);
        return $this;
    }

    /**
     * Προσθέτει μια διαδρομή POST
     *
     * @param string $path Η διαδρομή URL
     * @param mixed $callback Η συνάρτηση callback ή "Controller@method"
     * @param array $middlewares Προαιρετικά middlewares για αυτή τη διαδρομή
     * @return $this
     */
    public function post($path, $callback, $middlewares = [])
    {
        $this->addRoute('POST', $path, $callback, $middlewares);
        return $this;
    }

    /**
     * Προσθέτει μια διαδρομή PUT
     *
     * @param string $path Η διαδρομή URL
     * @param mixed $callback Η συνάρτηση callback ή "Controller@method"
     * @param array $middlewares Προαιρετικά middlewares για αυτή τη διαδρομή
     * @return $this
     */
    public function put($path, $callback, $middlewares = [])
    {
        $this->addRoute('PUT', $path, $callback, $middlewares);
        return $this;
    }

    /**
     * Προσθέτει μια διαδρομή DELETE
     *
     * @param string $path Η διαδρομή URL
     * @param mixed $callback Η συνάρτηση callback ή "Controller@method"
     * @param array $middlewares Προαιρετικά middlewares για αυτή τη διαδρομή
     * @return $this
     */
    public function delete($path, $callback, $middlewares = [])
    {
        $this->addRoute('DELETE', $path, $callback, $middlewares);
        return $this;
    }

    /**
     * Προσθήκη μιας διαδρομής για οποιαδήποτε μέθοδο
     */
    private function addRoute($method, $path, $callback, $middlewares = [])
    {
        // Προσθήκη του τρέχοντος προθέματος στη διαδρομή
        if (!empty($this->currentPrefix)) {
            $path = rtrim($this->currentPrefix, '/') . '/' . ltrim($path, '/');
        }

        $this->routes[$method][$path] = $callback;

        // Συνδυασμός των middlewares της διαδρομής με τα τρέχοντα middlewares
        if (!empty($this->currentMiddlewares) || !empty($middlewares)) {
            $allMiddlewares = array_merge($this->currentMiddlewares, $middlewares);
            $this->routeMiddlewares[$method][$path] = $allMiddlewares;
        }

        $this->currentRouteName = null;
        return $this;
    }

    /**
     * Ορίζει ένα όνομα για την τελευταία διαδρομή που προστέθηκε
     *
     * @param string $name Το όνομα της διαδρομής
     * @return $this
     */
    public function name($name)
    {
        if ($this->currentRouteName === null) {
            // Βρίσκουμε την τελευταία διαδρομή που προστέθηκε
            $lastMethod = array_key_last($this->routes);
            if ($lastMethod !== null) {
                $lastPath = array_key_last($this->routes[$lastMethod]);
                if ($lastPath !== null) {
                    $this->namedRoutes[$name] = [
                        'method' => $lastMethod,
                        'path' => $lastPath
                    ];
                    $this->currentRouteName = $name;
                }
            }
        }
        return $this;
    }

    /**
     * Δημιουργεί ένα URL για μια ονομαστική διαδρομή
     *
     * @param string $name Το όνομα της διαδρομής
     * @param array $params Οι παράμετροι για τη διαδρομή
     * @return string Το URL
     * @throws \Exception Αν η διαδρομή δεν βρεθεί
     */
    public function url($name, $params = [])
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new \Exception("Route with name '{$name}' not found");
        }

        $path = $this->namedRoutes[$name]['path'];

        // Αντικατάσταση των παραμέτρων στη διαδρομή
        foreach ($params as $paramName => $paramValue) {
            $path = str_replace("{{$paramName}}", $paramValue, $path);
        }

        // Προσθήκη του βασικού URL
        return $this->baseUrl . $path;
    }

    /**
     * Ορίζει ένα καθολικό middleware
     *
     * @param callable $middleware Η συνάρτηση middleware
     * @return $this
     */
    public function middleware($middleware)
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    /**
     * Ορίζει το callback για τη σελίδα 404
     *
     * @param callable $callback Η συνάρτηση callback
     * @return $this
     */
    public function notFound($callback)
    {
        $this->notFoundCallback = $callback;
        return $this;
    }

    /**
     * Επίλυση του τρέχοντος αιτήματος
     *
     * @return mixed Η επιστροφή του callback
     */
    public function resolve()
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        // Υποστήριξη για PUT και DELETE μέσω form με _method
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }

        $path = $this->getPath();
        // Εκτέλεση καθολικών middlewares
        foreach ($this->middlewares as $middleware) {
            $response = call_user_func($middleware);
            if ($response !== null) {
                return $response;
            }
        }

        // Εύρεση διαδρομής
        $routeInfo = $this->findRoute($method, $path);
        if ($routeInfo) {
            $callback = $routeInfo['callback'];
            $params = $routeInfo['params'];

            // Εύρεση της διαδρομής για τα middlewares
            $routePath = null;
            foreach ($this->routes[$method] as $route => $cb) {
                if ($cb === $callback) {
                    $routePath = $route;
                    break;
                }
            }

            // Εκτέλεση των middlewares της διαδρομής
            if ($routePath && isset($this->routeMiddlewares[$method][$routePath])) {
                foreach ($this->routeMiddlewares[$method][$routePath] as $middleware) {
                    $response = call_user_func($middleware, $params);
                    if ($response !== null) {
                        return $response;
                    }
                }
            }

            return $this->executeCallback($callback, $params);
        }

        // Αν δεν βρέθηκε καμία διαδρομή
        if ($this->notFoundCallback) {
            return call_user_func($this->notFoundCallback);
        }

        // Προεπιλεγμένη συμπεριφορά για 404
        header("HTTP/1.0 404 Not Found");
        return $this->renderNotFound();
    }

    /**
     * Λήψη του τρέχοντος path από το URL
     *
     * @return string Το καθαρισμένο path
     */
    private function getPath()
    {
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        if (!isset($_SERVER['REQUEST_URI'])) {
            return '/';
        }
        $position = strpos($path, '?');
        if ($position !== false) {
            $path = substr($path, 0, $position);
        }

        // Αφαίρεση του βασικού path της εφαρμογής - διόρθωση για το WAMP
        $basePath = '/drivejob/public';
        if (strpos($path, $basePath) === 0) {
            $path = substr($path, strlen($basePath));
        }

        // Καθαρισμός του path
        $path = trim($path, '/');
        $path = '/' . $path;

        // Καταγραφή του path για αποσφαλμάτωση
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log("Router::getPath - Original URI: " . $_SERVER['REQUEST_URI']);
            error_log("Router::getPath - Processed path: " . $path);
        }

        // Προσθήκη αποσφαλμάτωσης
        error_log("Router::getPath - Original URI: " . $_SERVER['REQUEST_URI']);
        error_log("Router::getPath - Processed path: " . $path);

        return $path ?: '/';
    }

    /**
     * Έλεγχος αν υπάρχει διαδρομή που ταιριάζει με το δοσμένο path
     *
     * @param string $method Η μέθοδος HTTP
     * @param string $path Το path προς έλεγχο
     * @return bool|array Επιστρέφει false αν δεν βρεθεί διαδρομή, αλλιώς επιστρέφει πίνακα με το callback και τις παραμέτρους
     */
    private function findRoute($method, $path)
    {
        // Έλεγχος αν υπάρχει ακριβής διαδρομή
        if (isset($this->routes[$method][$path])) {
            return [
                'callback' => $this->routes[$method][$path],
                'params' => []
            ];
        }

        // Έλεγχος αν υπάρχει διαδρομή με ή χωρίς κάθετο στο τέλος
        $pathWithSlash = rtrim($path, '/') . '/';
        $pathWithoutSlash = rtrim($path, '/');

        if (isset($this->routes[$method][$pathWithSlash])) {
            return [
                'callback' => $this->routes[$method][$pathWithSlash],
                'params' => []
            ];
        }

        if (isset($this->routes[$method][$pathWithoutSlash]) && $pathWithoutSlash !== '') {
            return [
                'callback' => $this->routes[$method][$pathWithoutSlash],
                'params' => []
            ];
        }

        // Έλεγχος για παραμετροποιημένες διαδρομές
        foreach ($this->routes[$method] ?? [] as $route => $callback) {
            $pattern = $this->convertRouteToRegex($route);
            if (preg_match($pattern, $path, $matches)) {
                // Αφαίρεση του πρώτου στοιχείου (ολόκληρο το ταίριασμα)
                array_shift($matches);
                // Εύρεση των ονομάτων παραμέτρων
                $paramNames = [];
                preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $route, $paramMatches);
                if (!empty($paramMatches[1])) {
                    $paramNames = $paramMatches[1];
                }

                // Συνδυασμός ονομάτων με τιμές
                $params = [];
                foreach ($matches as $index => $value) {
                    if (isset($paramNames[$index])) {
                        $params[$paramNames[$index]] = $value;
                    } else {
                        $params[] = $value;
                    }
                }

                return [
                    'callback' => $callback,
                    'params' => $params
                ];
            }
        }

        return false;
    }

    /**
     * Μετατροπή διαδρομής σε κανονική έκφραση
     *
     * @param string $route Η διαδρομή
     * @param bool $caseInsensitive Αν το ταίριασμα θα είναι case-insensitive
     * @return string Η κανονική έκφραση
     */
    private function convertRouteToRegex($route, $caseInsensitive = true)
    {
        // Αντικατάσταση παραμέτρων της μορφής {id} με ομάδες regex
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $route);
        // Προσθήκη ^ και $ για ακριβές ταίριασμα και προετοιμασία για preg_match
        $flags = $caseInsensitive ? 'i' : '';
        return "#^{$pattern}$" . ($flags ? "#{$flags}" : "#");
    }

    /**
     * Εκτέλεση του callback με τις παραμέτρους
     *
     * @param mixed $callback Η συνάρτηση callback
     * @param array $params Οι παράμετροι
     * @return mixed Η επιστροφή του callback
     */
    private function executeCallback($callback, $params = [])
    {
        if (is_callable($callback)) {
            return call_user_func_array($callback, $params);
        }

        // Αν το callback είναι array [controller, method]
        if (is_array($callback)) {
            [$controller, $method] = $callback;
            if (is_string($controller)) {
                // Έλεγχος αν ο controller έχει ήδη namespace
                if (strpos($controller, '\\') === 0 || strpos($controller, 'Drivejob\\Controllers\\') === 0) {
                    // Αν έχει ήδη πλήρες namespace, χρησιμοποιούμε το ως έχει
                    $controllerClass = $controller;
                } else {
                    // Αλλιώς προσθέτουμε το namespace
                    $controllerClass = "\\Drivejob\\Controllers\\$controller";
                }

                // Χρησιμοποιούμε το PDO από το container αν ο controller το χρειάζεται
                if (
                    method_exists($controllerClass, '__construct') &&
                    (new \ReflectionMethod($controllerClass, '__construct'))->getNumberOfParameters() > 0
                ) {
                    $controller = new $controllerClass($this->container->get('pdo'));
                } else {
                    $controller = new $controllerClass();
                }
            }

            return call_user_func_array([$controller, $method], $params);
        }

        // Αν το callback είναι string "Controller@method"
        if (is_string($callback) && strpos($callback, '@') !== false) {
            [$controller, $method] = explode('@', $callback, 2);
            // Έλεγχος αν δόθηκε πλήρες namespace
            if (strpos($controller, '\\') === false) {
                $controller = "\\Drivejob\\Controllers\\$controller";
            }

            // Χρησιμοποιούμε το PDO από το container αν ο controller το χρειάζεται
            if (
                method_exists($controller, '__construct') &&
                (new \ReflectionMethod($controller, '__construct'))->getNumberOfParameters() > 0
            ) {
                $controller = new $controller($this->container->get('pdo'));
            } else {
                $controller = new $controller();
            }

            return call_user_func_array([$controller, $method], $params);
        }

        throw new \Exception("Invalid callback");
    }

    /**
     * Εμφάνιση τυπικής σελίδας 404
     */
    private function renderNotFound()
    {
        echo '<div style="text-align: center; margin-top: 50px; font-family: Arial, sans-serif;">';
        echo '<h1>404 - Η σελίδα δεν βρέθηκε</h1>';
        echo '<p>Η σελίδα που ζητήσατε δεν βρέθηκε. Παρακαλώ επιστρέψτε στην <a href="/">αρχική σελίδα</a>.</p>';
        echo '</div>';
        return null;
    }

    /**
     * Ορίζει όλες τις διαδρομές από πίνακα
     *
     * @param array $routes Πίνακας διαδρομών
     * @return $this
     */
    public function addRoutes($routes)
    {
        foreach ($routes as $route) {
            $method = $route['method'] ?? 'GET';
            $path = $route['path'] ?? '/';
            $callback = $route['callback'] ?? null;
            $middlewares = $route['middlewares'] ?? [];
            $this->addRoute($method, $path, $callback, $middlewares);
        }

        return $this;
    }

    /**
     * Λήψη όλων των καταχωρημένων διαδρομών
     *
     * @return array Οι διαδρομές
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * Ομαδοποίηση διαδρομών με κοινά χαρακτηριστικά
     *
     * @param array $attributes Τα κοινά χαρακτηριστικά των διαδρομών
     * @param callable $callback Η συνάρτηση που ορίζει τις διαδρομές
     * @return $this
     */
    public function group(array $attributes, callable $callback)
    {
        // Αποθήκευση των τρεχόντων χαρακτηριστικών
        $previousAttributes = [
            'prefix' => $this->getCurrentPrefix(),
            'middlewares' => $this->getCurrentMiddlewares()
        ];

        // Εφαρμογή των νέων χαρακτηριστικών
        $this->applyGroupAttributes($attributes);

        // Εκτέλεση του callback για τον ορισμό των διαδρομών
        $callback($this);

        // Επαναφορά των προηγούμενων χαρακτηριστικών
        $this->resetGroupAttributes($previousAttributes);

        return $this;
    }

    /**
     * Λήψη του τρέχοντος προθέματος
     *
     * @return string Το τρέχον πρόθεμα
     */
    private function getCurrentPrefix()
    {
        return $this->currentPrefix ?? '';
    }

    /**
     * Λήψη των τρεχόντων middlewares
     *
     * @return array Τα τρέχοντα middlewares
     */
    private function getCurrentMiddlewares()
    {
        return $this->currentMiddlewares ?? [];
    }

    /**
     * Εφαρμογή των χαρακτηριστικών της ομάδας
     *
     * @param array $attributes Τα χαρακτηριστικά της ομάδας
     */
    private function applyGroupAttributes(array $attributes)
    {
        // Εφαρμογή του προθέματος
        if (isset($attributes['prefix'])) {
            $this->currentPrefix = $this->getCurrentPrefix() . '/' . trim($attributes['prefix'], '/');
        }

        // Εφαρμογή των middlewares
        if (isset($attributes['middleware'])) {
            $middlewares = is_array($attributes['middleware']) ? $attributes['middleware'] : [$attributes['middleware']];
            $this->currentMiddlewares = array_merge($this->getCurrentMiddlewares(), $middlewares);
        }
    }

    /**
     * Επαναφορά των χαρακτηριστικών της ομάδας
     *
     * @param array $attributes Τα χαρακτηριστικά της ομάδας
     */
    private function resetGroupAttributes(array $attributes)
    {
        $this->currentPrefix = $attributes['prefix'];
        $this->currentMiddlewares = $attributes['middlewares'];
    }
}
