<?php

namespace Drivejob\Core;

use Drivejob\Core\Exceptions\DatabaseException;
use Drivejob\Core\Exceptions\ValidationException;
use Drivejob\Core\Exceptions\AuthException;
use Drivejob\Core\Exceptions\FileException;
use Drivejob\Core\Exceptions\ApiException;
use Drivejob\Core\Exceptions\NotFoundException;
use Drivejob\Core\Exceptions\ForbiddenException;
use Drivejob\Core\Exceptions\BadRequestException;
use Drivejob\Core\Exceptions\ServerErrorException;
use Drivejob\Core\Exceptions\BaseException;
use Throwable;

/**
 * Κλάση για τον χειρισμό σφαλμάτων
 * 
 * Παρέχει μεθόδους για τον συνεπή χειρισμό σφαλμάτων σε όλη την εφαρμογή
 */
class ErrorHandler
{
    /**
     * Ο τύπος της απάντησης (html, json, xml)
     *
     * @var string
     */
    private static $responseType = 'html';

    /**
     * Αν είναι ενεργοποιημένη η λεπτομερής καταγραφή σφαλμάτων
     *
     * @var bool
     */
    private static $debug = false;

    /**
     * Αρχικοποίηση του χειριστή σφαλμάτων
     *
     * @param string $responseType Ο τύπος της απάντησης (html, json, xml)
     * @param bool $debug Αν είναι ενεργοποιημένη η λεπτομερής καταγραφή σφαλμάτων
     */
    public static function init(string $responseType = 'html', bool $debug = false)
    {
        self::$responseType = $responseType;
        self::$debug = $debug;

        // Ορισμός των χειριστών σφαλμάτων
        \set_error_handler([self::class, 'handleError']);
        \set_exception_handler([self::class, 'handleException']);
        \register_shutdown_function([self::class, 'handleShutdown']);
    }

    /**
     * Χειρισμός σφαλμάτων PHP
     *
     * @param int $errno Ο κωδικός σφάλματος
     * @param string $errstr Το μήνυμα σφάλματος
     * @param string $errfile Το αρχείο στο οποίο συνέβη το σφάλμα
     * @param int $errline Η γραμμή στην οποία συνέβη το σφάλμα
     * @return bool Αν το σφάλμα έχει χειριστεί
     */
    public static function handleError(int $errno, string $errstr, string $errfile, int $errline)
    {
        // Αγνόηση των σφαλμάτων που έχουν απενεργοποιηθεί με το @
        if (!(\error_reporting() & $errno)) {
            return false;
        }

        // Καταγραφή του σφάλματος
        Logger::error('PHP Error', [
            'errno' => $errno,
            'errstr' => $errstr,
            'errfile' => $errfile,
            'errline' => $errline
        ]);

        // Μετατροπή του σφάλματος σε εξαίρεση
        throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
    }

    /**
     * Χειρισμός εξαιρέσεων
     *
     * @param Throwable $exception Η εξαίρεση που συνέβη
     */
    public static function handleException(Throwable $exception)
    {
        // Καταγραφή της εξαίρεσης
        Logger::error('Exception', [
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);

        // Καθορισμός του κωδικού HTTP
        $httpCode = 500;
        $errorType = 'Server Error';
        $errorMessage = 'Υπήρξε ένα σφάλμα στον διακομιστή.';
        $errorDetails = null;

        // Καθορισμός του τύπου σφάλματος και του μηνύματος
        if ($exception instanceof ValidationException) {
            $httpCode = 400;
            $errorType = 'Validation Error';
            $errorMessage = $exception->getMessage();
            $errorDetails = $exception->getContext();
        } elseif ($exception instanceof DatabaseException) {
            $httpCode = 500;
            $errorType = 'Database Error';
            $errorMessage = 'Υπήρξε ένα σφάλμα στη βάση δεδομένων.';
            $errorDetails = self::$debug ? $exception->getContext() : null;
        } elseif ($exception instanceof AuthException) {
            $httpCode = 401;
            $errorType = 'Authentication Error';
            $errorMessage = $exception->getMessage();
        } elseif ($exception instanceof FileException) {
            $httpCode = 500;
            $errorType = 'File Error';
            $errorMessage = $exception->getMessage();
            $errorDetails = self::$debug ? $exception->getContext() : null;
        } elseif ($exception instanceof ApiException) {
            $httpCode = 500;
            $errorType = 'API Error';
            $errorMessage = $exception->getMessage();
            $errorDetails = self::$debug ? $exception->getContext() : null;
        } elseif ($exception instanceof NotFoundException) {
            $httpCode = 404;
            $errorType = 'Not Found';
            $errorMessage = $exception->getMessage();
        } elseif ($exception instanceof ForbiddenException) {
            $httpCode = 403;
            $errorType = 'Forbidden';
            $errorMessage = $exception->getMessage();
        } elseif ($exception instanceof BadRequestException) {
            $httpCode = 400;
            $errorType = 'Bad Request';
            $errorMessage = $exception->getMessage();
        } elseif ($exception instanceof ServerErrorException) {
            $httpCode = 500;
            $errorType = 'Server Error';
            $errorMessage = $exception->getMessage();
            $errorDetails = self::$debug ? $exception->getContext() : null;
        } elseif ($exception instanceof BaseException) {
            $httpCode = $exception->getHttpCode();
            $errorType = $exception->getErrorType();
            $errorMessage = $exception->getMessage();
            $errorDetails = self::$debug ? $exception->getContext() : null;
        }

        // Αποστολή της κατάλληλης απάντησης
        self::sendResponse($httpCode, $errorType, $errorMessage, $errorDetails, $exception);
    }

    /**
     * Χειρισμός τερματισμού της εφαρμογής
     */
    public static function handleShutdown()
    {
        $error = \error_get_last();
        if ($error !== null && \in_array($error['type'], [\E_ERROR, \E_PARSE, \E_CORE_ERROR, \E_COMPILE_ERROR])) {
            // Καταγραφή του σφάλματος
            Logger::error('Fatal Error', [
                'type' => $error['type'],
                'message' => $error['message'],
                'file' => $error['file'],
                'line' => $error['line']
            ]);

            // Αποστολή της κατάλληλης απάντησης
            self::sendResponse(
                500,
                'Fatal Error',
                'Υπήρξε ένα κρίσιμο σφάλμα στον διακομιστή.',
                self::$debug ? $error : null,
                null
            );
        }
    }

    /**
     * Αποστολή της απάντησης
     *
     * @param int $httpCode Ο κωδικός HTTP
     * @param string $errorType Ο τύπος του σφάλματος
     * @param string $errorMessage Το μήνυμα του σφάλματος
     * @param array|null $errorDetails Λεπτομέρειες του σφάλματος
     * @param Throwable|null $exception Η εξαίρεση που συνέβη
     */
    private static function sendResponse(int $httpCode, string $errorType, string $errorMessage, ?array $errorDetails, ?Throwable $exception)
    {
        // Καθορισμός του κωδικού HTTP
        \http_response_code($httpCode);

        // Αποστολή της κατάλληλης απάντησης
        switch (self::$responseType) {
            case 'json':
                self::sendJsonResponse($httpCode, $errorType, $errorMessage, $errorDetails, $exception);
                break;
            case 'xml':
                self::sendXmlResponse($httpCode, $errorType, $errorMessage, $errorDetails, $exception);
                break;
            case 'html':
            default:
                self::sendHtmlResponse($httpCode, $errorType, $errorMessage, $errorDetails, $exception);
                break;
        }

        exit();
    }

    /**
     * Αποστολή απάντησης σε μορφή JSON
     *
     * @param int $httpCode Ο κωδικός HTTP
     * @param string $errorType Ο τύπος του σφάλματος
     * @param string $errorMessage Το μήνυμα του σφάλματος
     * @param array|null $errorDetails Λεπτομέρειες του σφάλματος
     * @param Throwable|null $exception Η εξαίρεση που συνέβη
     */
    private static function sendJsonResponse(int $httpCode, string $errorType, string $errorMessage, ?array $errorDetails, ?Throwable $exception)
    {
        \header('Content-Type: application/json; charset=utf-8');

        $response = [
            'success' => false,
            'error' => [
                'type' => $errorType,
                'message' => $errorMessage,
                'code' => $httpCode
            ]
        ];

        if ($errorDetails !== null) {
            $response['error']['details'] = $errorDetails;
        }

        if (self::$debug && $exception !== null) {
            $response['error']['debug'] = [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => \explode("\n", $exception->getTraceAsString())
            ];
        }

        echo \json_encode($response, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE);
    }

    /**
     * Αποστολή απάντησης σε μορφή XML
     *
     * @param int $httpCode Ο κωδικός HTTP
     * @param string $errorType Ο τύπος του σφάλματος
     * @param string $errorMessage Το μήνυμα του σφάλματος
     * @param array|null $errorDetails Λεπτομέρειες του σφάλματος
     * @param Throwable|null $exception Η εξαίρεση που συνέβη
     */
    private static function sendXmlResponse(int $httpCode, string $errorType, string $errorMessage, ?array $errorDetails, ?Throwable $exception)
    {
        \header('Content-Type: application/xml; charset=utf-8');

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><response></response>');
        $xml->addChild('success', 'false');
        $error = $xml->addChild('error');
        $error->addChild('type', $errorType);
        $error->addChild('message', $errorMessage);
        $error->addChild('code', $httpCode);

        if ($errorDetails !== null) {
            $details = $error->addChild('details');
            self::arrayToXml($errorDetails, $details);
        }

        if (self::$debug && $exception !== null) {
            $debug = $error->addChild('debug');
            $debug->addChild('file', $exception->getFile());
            $debug->addChild('line', $exception->getLine());
            $trace = $debug->addChild('trace');
            $traceLines = \explode("\n", $exception->getTraceAsString());
            foreach ($traceLines as $index => $line) {
                $trace->addChild('line' . $index, $line);
            }
        }

        echo $xml->asXML();
    }

    /**
     * Μετατροπή πίνακα σε XML
     *
     * @param array $array Ο πίνακας προς μετατροπή
     * @param \SimpleXMLElement $xml Το αντικείμενο XML
     */
    private static function arrayToXml(array $array, \SimpleXMLElement $xml)
    {
        foreach ($array as $key => $value) {
            if (\is_array($value)) {
                if (\is_numeric($key)) {
                    $key = 'item' . $key;
                }
                $subnode = $xml->addChild($key);
                self::arrayToXml($value, $subnode);
            } else {
                if (\is_numeric($key)) {
                    $key = 'item' . $key;
                }
                $xml->addChild($key, (string) $value);
            }
        }
    }

    /**
     * Αποστολή απάντησης σε μορφή HTML
     *
     * @param int $httpCode Ο κωδικός HTTP
     * @param string $errorType Ο τύπος του σφάλματος
     * @param string $errorMessage Το μήνυμα του σφάλματος
     * @param array|null $errorDetails Λεπτομέρειες του σφάλματος
     * @param Throwable|null $exception Η εξαίρεση που συνέβη
     */
    private static function sendHtmlResponse(int $httpCode, string $errorType, string $errorMessage, ?array $errorDetails, ?Throwable $exception)
    {
        \header('Content-Type: text/html; charset=utf-8');

        // Φόρτωση του προτύπου σφάλματος
        $errorTemplate = ROOT_DIR . '/src/Views/errors/error.php';
        if (\file_exists($errorTemplate)) {
            \include $errorTemplate;
        } else {
            // Αν δεν υπάρχει το πρότυπο, εμφάνιση απλού μηνύματος σφάλματος
            echo '<!DOCTYPE html>
            <html>
            <head>
                <meta charset="utf-8">
                <title>Error - ' . \htmlspecialchars($errorType) . '</title>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px; }
                    .error-container { max-width: 800px; margin: 0 auto; background: #f8f8f8; border: 1px solid #ddd; padding: 20px; border-radius: 5px; }
                    h1 { color: #d9534f; margin-top: 0; }
                    .error-details { background: #fff; border: 1px solid #ddd; padding: 15px; margin-top: 20px; border-radius: 3px; }
                    .error-details pre { margin: 0; white-space: pre-wrap; }
                    .back-link { display: inline-block; margin-top: 20px; color: #337ab7; text-decoration: none; }
                    .back-link:hover { text-decoration: underline; }
                </style>
            </head>
            <body>
                <div class="error-container">
                    <h1>' . \htmlspecialchars($errorType) . ' (' . $httpCode . ')</h1>
                    <p>' . \htmlspecialchars($errorMessage) . '</p>';

            if ($errorDetails !== null) {
                echo '<div class="error-details">
                        <h3>Error Details:</h3>
                        <pre>' . \htmlspecialchars(\print_r($errorDetails, true)) . '</pre>
                    </div>';
            }

            if (self::$debug && $exception !== null) {
                echo '<div class="error-details">
                        <h3>Debug Information:</h3>
                        <p><strong>File:</strong> ' . \htmlspecialchars($exception->getFile()) . '</p>
                        <p><strong>Line:</strong> ' . $exception->getLine() . '</p>
                        <pre>' . \htmlspecialchars($exception->getTraceAsString()) . '</pre>
                    </div>';
            }

            echo '<a href="' . (\isset($_SERVER['HTTP_REFERER']) ? \htmlspecialchars($_SERVER['HTTP_REFERER']) : '/') . '" class="back-link">Go Back</a>
                </div>
            </body>
            </html>';
        }
    }
}
