<?php declare(strict_types=1);

if (!function_exists('array_is_list')) {
    /**
     * Checks whether a given array is a list
     * @link https://www.php.net/manual/en/function.array-is-list.php#126794
     * @source https://www.php.net/manual/en/function.array-is-list.php#126794
     *
     * @param array $array The array being evaluated. 
     * @return boolean Returns true if array is a list, false otherwise. 
     */
    function array_is_list(array $array): bool
    {
        $i = 0;
        foreach ($array AS $key => $val) {
            if ($key !== $i++) {
                return false;
            }
        }
        return true;
    }
}


if (!function_exists('getallheaders')) {
    /**
     * Get all HTTP headers as associative array.
     * @link https://www.php.net/manual/en/function.getallheaders.php#118820
     * @source https://github.com/ralouphie/getallheaders/blob/develop/src/getallheaders.php
     *
     * @return array
     */
    function getallheaders(): array
    {
        $headers = array();

        $copy_server = array(
            'CONTENT_TYPE'   => 'Content-Type',
            'CONTENT_LENGTH' => 'Content-Length',
            'CONTENT_MD5'    => 'Content-Md5',
        );

        foreach ($_SERVER as $key => $value) {
            if (substr($key, 0, 5) === 'HTTP_') {
                $key = substr($key, 5);
                if (!isset($copy_server[$key]) || !isset($_SERVER[$key])) {
                    $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', $key))));
                    $headers[$key] = $value;
                }
            } elseif (isset($copy_server[$key])) {
                $headers[$copy_server[$key]] = $value;
            }
        }

        if (!isset($headers['Authorization'])) {
            if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
                $headers['Authorization'] = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
            } elseif (isset($_SERVER['PHP_AUTH_USER'])) {
                $basic_pass = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : '';
                $headers['Authorization'] = 'Basic ' . base64_encode($_SERVER['PHP_AUTH_USER'] . ':' . $basic_pass);
            } elseif (isset($_SERVER['PHP_AUTH_DIGEST'])) {
                $headers['Authorization'] = $_SERVER['PHP_AUTH_DIGEST'];
            }
        }

        return $headers;
    }
}
