<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class InternetConnectionCheckerService
{
    private $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function isConnected(): bool
    {
        try 
        {
            $response = $this->httpClient->request('GET', 'https://www.google.com', [
                'timeout' => 5, // Temps maximum pour la requête
            ]);
            return $response->getStatusCode() === 200;
        } catch (\Exception $e) 
        {
            return false;
        }
    }
}