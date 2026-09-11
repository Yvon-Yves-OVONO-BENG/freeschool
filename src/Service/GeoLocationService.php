<?php

namespace App\Service;

use App\Entity\Country;
use Symfony\Component\Intl\Countries;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeoLocationService
{
    private $client;
    private $entityManager;

    public function __construct(HttpClientInterface $client, EntityManagerInterface $entityManager)
    {
        $this->client = $client;
        $this->entityManager = $entityManager;
    }

    public function getCityAndCountry(string $ip): array
    {
        try {
            $response = $this->client->request(
                'GET',
                "http://ip-api.com/json/{$ip}?fields=status,country,city"
            );

            $data = $response->toArray();
            
            if ($data['status'] === 'success') 
            {
                $city = $data['city'] ?? null;
                $countryEn = $data['country'] ?? null;
                
                // Obtenir le code ISO
                $countryCode = array_search($countryEn, Countries::getNames('en'));
                $countryFr = $countryCode !== false ? Countries::getName($countryCode, 'fr') : null;
                
                // Vérifier dans la base si le pays existe
                $pays = null;
                if ($countryFr) {
                    $pays = $this->entityManager->getRepository(Country::class)
                        ->findOneBy(['country' => $countryFr]);
                }
                
                return [
                    'city' => $city,
                    'country' => $countryFr,
                    'country_exists' => $pays !== null,
                    'country' => $pays, // utile si tu veux récupérer l'objet complet
                ];
            }
        } catch (\Exception $e) 
        {
            // Log de l'erreur
        }

        return ['city' => null, 'country' => null, 'country_exists' => false];
    }
}