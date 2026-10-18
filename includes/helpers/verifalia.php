<?php
// This file contains helper functions for verifying email addresses using the Verifalia API. This requires a business account with Verifalia and the username and password must be set in the environment variables in config/env.php. The function verifyCymEmailWithVerifalia() returns an array with a 'status' key that can be 'verified', 'invalid' or 'unavailable'. If the status is 'verified' or 'invalid', there is also a 'classification' key that contains the classification returned by Verifalia, like "Deliverable" or "Undeliverable".
require_once __DIR__ . '/../../config/env.php';

function verifyCymEmailWithVerifalia(string $email): array
{
    // add username and password to the environment variables in config/env.php
    $username = getenv('VERIFALIA_USERNAME');
    $password = getenv('VERIFALIA_PASSWORD');

    if (!$username || !$password) {
        error_log('CYM email verification: Verifalia credentials are not configured');
        return ['status' => 'unavailable'];
    }

    if (!function_exists('curl_init')) {
        error_log('CYM email verification: the PHP cURL extension is unavailable');
        return ['status' => 'unavailable'];
    }

    $deadline = microtime(true) + 27;
    $request = function (string $url, ?string $body, int $waitTime, int $timeout) use ($username, $password): array {
        $headers = [];
        $curl = curl_init($url);
        if ($curl === false) {
            return [
                'body' => false,
                'statusCode' => 0,
                'location' => '',
                'curlError' => 'could not initialize cURL',
            ];
        }

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => $username . ':' . $password,
            CURLOPT_HTTPHEADER => ['Accept: application/json', 'Content-Type: application/json'],
            CURLOPT_HEADERFUNCTION => function ($curl, string $line) use (&$headers): int {
                $length = strlen($line);
                if (stripos($line, 'Location:') === 0) {
                    $headers['location'] = trim(substr($line, strlen('Location:')));
                }
                return $length;
            },
        ]);

        if ($body !== null) {
            curl_setopt_array($curl, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $body,
            ]);
        }

        $separator = strpos($url, '?') === false ? '?' : '&';
        $url .= $separator . 'waitTime=' . $waitTime;
        curl_setopt($curl, CURLOPT_URL, $url);

        $responseBody = curl_exec($curl);
        $statusCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);

        return [
            'body' => $responseBody,
            'statusCode' => $statusCode,
            'location' => $headers['location'] ?? '',
            'curlError' => $curlError,
        ];
    };

    $payload = json_encode(['entries' => [['inputData' => $email]]]);
    if ($payload === false) {
        error_log('CYM email verification: could not encode the Verifalia request');
        return ['status' => 'unavailable'];
    }

    $response = $request('https://api.verifalia.com/v2.7/email-validations', $payload, 10000, 12);
    $jobUrl = $response['location'];

    while ($response['statusCode'] === 202 && microtime(true) < $deadline) {
        $parts = parse_url($jobUrl);
        if (($parts['scheme'] ?? '') !== 'https' || ($parts['host'] ?? '') !== 'api.verifalia.com') {
            error_log('CYM email verification: Verifalia returned an unexpected job URL');
            return ['status' => 'unavailable'];
        }

        $remaining = (int) ceil($deadline - microtime(true));
        if ($remaining <= 0) {
            break;
        }

        $response = $request($jobUrl, null, 5000, min(7, $remaining));
        $jobUrl = $response['location'] ?: $jobUrl;
    }

    if ($response['statusCode'] !== 200 || !is_string($response['body'])) {
        error_log(
            'CYM email verification: Verifalia request failed with HTTP ' .
            $response['statusCode'] .
            ($response['curlError'] !== '' ? ' (' . $response['curlError'] . ')' : '')
        );
        return ['status' => 'unavailable'];
    }

    $result = json_decode($response['body'], true);
    $classification = $result['entries'][0]['classification'] ?? null;
    if (!is_string($classification)) {
        error_log('CYM email verification: Verifalia returned no email classification');
        return ['status' => 'unavailable'];
    }

    return [
        'status' => strtolower($classification) === 'deliverable' ? 'verified' : 'invalid',
        'classification' => $classification,
    ];
}
