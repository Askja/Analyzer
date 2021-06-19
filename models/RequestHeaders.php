<?php

namespace models;

class RequestHeaders
{
    const methodParam = '_method';
    /**
     * @param $key
     * @param null $prefix
     * @return string|null
     */
    public static function getRequestHeader($key, $prefix = null): ?string
    {
        $requestHeaders = self::getAllHeaders();
        $header = null;
        if (!is_null($key)) {
            $headers = (isset($requestHeaders[$key])) ? $requestHeaders[$key] : null;
            $parts = explode(',', $headers);
            if (is_array($parts)) {
                foreach ($parts as $part) {
                    if (str_contains($part, $prefix . ' ')) {
                        $header = trim($part);
                        break;
                    }
                }
            }
        }
        return $header;
    }

    /**
     * @param $header
     * @return string|null
     */
    public static function getHeaderValue($header): ?string
    {
        $parts = explode(' ', $header);
        return array_pop($parts);
    }

    /**
     * @return bool|array|string
     */
    public static function getAllHeaders(): bool|array|string
    {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $sub = substr($name, 5);
                $uc = ucwords(strtolower(str_replace('_', ' ', $sub)));
                $headers[str_replace(' ', '-', $uc)] = $value;
            }
        }
        if (function_exists('apache_request_headers')) {
            return array_merge($headers, apache_request_headers());
        }

        return $headers;
    }

    public static function getMethod(): string
    {
        if (
            isset($_POST[self::methodParam])
            && !in_array(strtoupper($_POST[self::methodParam]), ['GET', 'HEAD', 'OPTIONS'], true)
        ) {
            return strtoupper($_POST[self::methodParam]);
        }

        if (isset($_SERVER['REQUEST_METHOD'])) {
            return strtoupper($_SERVER['REQUEST_METHOD']);
        }

        return 'GET';
    }
}