<?php
/**
 * AgriSphere - PHP client for the persistent C++ backend
 * =====================================================================
 * The C++ service (backend/bin/agrisphere_backend.exe) owns the Singleton
 * DatabaseManager and all of the business logic that was moved out of PHP.
 * This class is the only place in the PHP tier that knows how to reach it.
 *
 *   PHP page  ->  AgriSphereBackend::call()  ->  HTTP/JSON on 127.0.0.1
 *             ->  C++ service  ->  DatabaseManager::getInstance()  ->  MySQL
 *
 * Requests are plain JSON over loopback with a shared key header, so the same
 * endpoints can later be consumed by an Android client without any change on
 * the C++ side.
 */

if (!defined('AGRISPHERE')) {
    define('AGRISPHERE', true);
}

require_once __DIR__ . '/config.php';

class BackendUnavailableException extends RuntimeException
{
}

class AgriSphereBackend
{
    /** @var string|null Cached "backend is down" message for this request. */
    private static $lastError = null;

    /**
     * Calls a backend endpoint and returns the decoded JSON body.
     *
     * @param string $path   e.g. '/api/cart/add'
     * @param array  $params request payload
     * @return array
     * @throws BackendUnavailableException when the service cannot be reached
     */
    public static function call($path, array $params = [])
    {
        $url = rtrim(BACKEND_URL, '/') . $path;
        $payload = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => BACKEND_CONNECT_TIMEOUT,
            CURLOPT_TIMEOUT        => BACKEND_TIMEOUT,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'X-Agri-Key: ' . BACKEND_API_KEY,
                'Content-Length: ' . strlen($payload),
            ],
        ]);

        $body = curl_exec($ch);
        $errorNumber = curl_errno($ch);
        $errorText = curl_error($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errorNumber !== 0) {
            self::$lastError = $errorText;
            throw new BackendUnavailableException(
                'Cannot reach the AgriSphere C++ backend at ' . BACKEND_URL .
                ' (' . $errorText . '). Start backend\\start_backend.bat and try again.'
            );
        }

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new BackendUnavailableException(
                'The AgriSphere C++ backend returned an unreadable response (HTTP ' . $status . ').'
            );
        }

        return $decoded;
    }

    /**
     * Same as call() but never throws: on failure it returns
     * ['success' => false, 'error' => '...', 'backend_down' => true].
     * Used by read paths where a hard failure would blank out a whole page.
     */
    public static function tryCall($path, array $params = [])
    {
        try {
            return self::call($path, $params);
        } catch (BackendUnavailableException $e) {
            return [
                'success'      => false,
                'error'        => $e->getMessage(),
                'backend_down' => true,
            ];
        }
    }

    /**
     * True when the service answers its health probe. Used by the
     * "backend is not running" notice.
     */
    public static function isUp()
    {
        $ch = curl_init(rtrim(BACKEND_URL, '/') . '/health');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 1,
            CURLOPT_TIMEOUT        => 2,
        ]);
        $body = curl_exec($ch);
        $ok = ($body !== false && curl_errno($ch) === 0);
        curl_close($ch);
        return $ok;
    }

    public static function lastError()
    {
        return self::$lastError;
    }

    /**
     * Renders a clear message when the backend is not running, then stops.
     * Called by the action handlers, which cannot do anything useful without it.
     */
    public static function fail(BackendUnavailableException $e)
    {
        if (function_exists('setFlashMessage')) {
            setFlashMessage('error', $e->getMessage());
        }
        http_response_code(503);
        echo '<!DOCTYPE html><html><head><meta charset="utf-8">'
            . '<title>Backend unavailable | AgriSphere</title>'
            . '<style>body{font-family:Inter,Segoe UI,Arial,sans-serif;background:#f6f8f6;'
            . 'margin:0;padding:48px;color:#1f2d20}.box{max-width:640px;margin:0 auto;'
            . 'background:#fff;border:1px solid #dbe5db;border-radius:12px;padding:32px}'
            . 'h1{margin:0 0 12px;font-size:20px}code{background:#f0f4f0;padding:2px 6px;'
            . 'border-radius:4px}</style></head><body><div class="box">'
            . '<h1>The AgriSphere C++ backend is not running</h1><p>'
            . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
            . '</p><p>Start it by running <code>backend\\start_backend.bat</code>, '
            . 'then go back and retry.</p></div></body></html>';
        exit();
    }
}
