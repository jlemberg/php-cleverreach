<?php

class RestApiClient
{
    private $apiKey;
    private $apiEndpoint;

    public $verifySsl = true;

    private $requestSuccessful = false;

    public function __construct($apiEndpoint, $apiKey = null)
    {
        $this->apiEndpoint = $apiEndpoint;
        $this->apiKey = $apiKey;
    }

    public function success()
    {
        return $this->requestSuccessful;
    }

    public function delete($method, $args = array())
    {
        return $this->makeRequest('delete', $method, $args);
    }

    public function get($method, $args = array())
    {
        return $this->makeRequest('get', $method, $args);
    }

    public function post($method, $args = array())
    {
        return $this->makeRequest('post', $method, $args);
    }

    public function put($method, $args = array())
    {
        return $this->makeRequest('put', $method, $args);
    }

    private function makeRequest($http_verb, $method, $args = array())
    {
        if (!function_exists('curl_init') || !function_exists('curl_setopt')) {
            throw new Exception("no curl.");
        }

        $url = $this->apiEndpoint . '/' . $method;

        $this->requestSuccessful = false;
        $response                 = array('headers' => null, 'body' => null);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        $headers = array(
            'Accept: application/json',
            'Content-Type: application/json'
        );
        if($this->apiKey !== null) {
            $headers[] = 'Authorization: Bearer ' . $this->apiKey;
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_USERAGENT, 'jlemberg/rest-api-client/1.0 (github.com/jlemberg/php-cleverreach)');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
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

            if(!isset($d['error'])) {
                $this->requestSuccessful = true;
            }

            return $d;
        }

        return false;
    }
}
