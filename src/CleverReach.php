<?php

class CleverReach
{
    private $apiKey;
    private $apiEndpoint = 'https://rest.cleverreach.com/v1/';

    public $verifySsl = true;

    private $requestSuccessful = false;

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function success()
    {
        return $this->requestSuccessful;
    }

    public function delete($method, $args = array(), $timeout = 10)
    {
        return $this->makeRequest('delete', $method, $args, $timeout);
    }

    public function get($method, $args = array(), $timeout = 10)
    {
        return $this->makeRequest('get', $method, $args, $timeout);
    }

    public function patch($method, $args = array(), $timeout = 10)
    {
        return $this->makeRequest('patch', $method, $args, $timeout);
    }

    public function post($method, $args = array(), $timeout = 10)
    {
        return $this->makeRequest('post', $method, $args, $timeout);
    }

    public function put($method, $args = array(), $timeout = 10)
    {
        return $this->makeRequest('put', $method, $args, $timeout);
    }

    private function makeRequest($http_verb, $method, $args = array(), $timeout = 10)
    {
        if (!function_exists('curl_init') || !function_exists('curl_setopt')) {
            throw new Exception("cURL support is required, but can't be found.");
        }

        $url = $this->apiEndpoint . '/' . $method;

        $this->requestSuccessful = false;
        $response                 = array('headers' => null, 'body' => null);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ));
        curl_setopt($ch, CURLOPT_USERAGENT, 'jlemberg/php-cleverreach/1.0 (github.com/jlemberg/php-cleverreach)');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->verifySsl);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);

        switch ($http_verb) {
            case 'post':
                curl_setopt($ch, CURLOPT_POST, true);
                $this->attachRequestPayload($ch, $args);
                break;

            case 'get':
                $query = http_build_query($args);
                curl_setopt($ch, CURLOPT_URL, $url . '?' . $query);
                break;

            case 'delete':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;

            case 'patch':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
                $this->attachRequestPayload($ch, $args);
                break;

            case 'put':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                $this->attachRequestPayload($ch, $args);
                break;
        }

        $response['body']    = curl_exec($ch);
        $response['headers'] = curl_getinfo($ch);

        curl_close($ch);

        return $this->formatResponse($response);
    }

    private function attachRequestPayload(&$ch, $data)
    {
        $encoded = json_encode($data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $encoded);
    }

    private function formatResponse($response)
    {
        if (!empty($response['body'])) {

            $d = json_decode($response['body'], true);

            if(isset($d['error'])) {
                $this->requestSuccessful = false;
            }

            return $d;
        }

        return false;
    }
}