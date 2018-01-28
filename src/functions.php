<?php declare(strict_types=1);

namespace DaveRandom\Resume;

/**
 * Get the value of a header in the current request context
 *
 * @param string $name Name of the header
 * @return string|null Returns null when the header was not sent or cannot be retrieved
 * @codeCoverageIgnore
 */
function get_request_header(string $name)
{
    $name = \strtoupper($name);

    // IIS/Some Apache versions and configurations
    if (isset($_SERVER['HTTP_' . $name])) {
        return \trim($_SERVER['HTTP_' . $name]);
    }

    // Various other SAPIs
    if (\function_exists('apache_request_headers')) {
        foreach (\apache_request_headers() as $headerName => $value) {
            if (\strtoupper($headerName) === $name) {
                return \trim($value);
            }
        }
    }

    return null;
}
