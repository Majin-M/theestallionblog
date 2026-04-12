<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Routing\Annotation\Route;

class TourController extends AbstractController
{
    #[Route('/home', name: 'tour_dates')]
    public function getTourDates(HttpClientInterface $client): JsonResponse
    {
        // 1. Récupérer la clé API depuis les variables d'environnement (sécurité)
        $apiKey = $_ENV['8Nbl6P3Hp9RwRleHEqZAw9UHj3294LT9'] ?? null;

        if (!$apiKey) {
            return $this->json([
                'error' => 'La clé API Ticketmaster est manquante. Ajoutez TICKETMASTER_API_KEY dans votre fichier .env'
            ], 500);
        }

        // 2. Configuration de l'URL Ticketmaster Discovery API
        // On utilise 'keyword' car trouver l'ID interne de l'artiste nécessite une étape supplémentaire
        $url = "https://app.ticketmaster.com/discovery/v2/events.json";
        
        $query = [
            'apikey' => $apiKey,
            'keyword' => 'Megan Thee Stallion', // Recherche par mot clé
            'classificationName' => 'Music', // Filtre pour éviter les événements sportifs si le nom est ambigu
            'sort' => 'date,asc',          // Trier par date croissante
            'size' => 10,                  // Limiter aux 10 prochains événements
            'locale' => '*'
        ];

        try {
            $response = $client->request('GET', $url, [
                'query' => $query
            ]);

            if ($response->getStatusCode() !== 200) {
                return $this->json([
                    'error' => 'Erreur API Ticketmaster',
                    'details' => $response->getContent(false)
                ], 500);
            }

            $data = $response->toArray();
            $events = $data['_embedded']['events'] ?? [];

            // 3. Normalisation des données
            // Ticketmaster renvoie une structure très imbriquée, on la simplifie pour le JS
            $formattedEvents = [];

            foreach ($events as $event) {
                $venue = $event['_embedded']['venues'][0] ?? null;

                // Création d'une date valide (Ticketmaster donne "YYYY-MM-DD")
                $dateObj = new \DateTime($event['dates']['start']['localDate']);

                $formattedEvents[] = [
                    'datetime' => $dateObj->format('Y-m-d'), // Format ISO pour JS
                    'name' => $event['name'],
                    'url' => $event['url'],
                    'venue' => [
                        'name' => $venue ? $venue['name'] : 'TBD',
                        'city' => $venue ? $venue['city']['name'] : 'TBD',
                        'country' => $venue ? $venue['country']['name'] : 'TBD'
                    ]
                ];
            }

            return $this->json($formattedEvents);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur de connexion à Ticketmaster',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}