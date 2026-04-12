<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * HomeController
 *
 * Contrôleur principal du blog Megan Thee Stallion.
 * Récupère les données de tournée depuis l'API Ticketmaster Discovery v2
 * et les transmet à la vue Twig.
 *
 * Dépendances :
 *   - symfony/http-client  (composer require symfony/http-client)
 *   - Variable d'env TICKETMASTER_API_KEY dans .env.local
 */
final class HomeController extends AbstractController
{
    /**
     * @param HttpClientInterface $httpClient Injecté automatiquement par Symfony
     */
    public function __construct(
        private readonly HttpClientInterface $httpClient
    ) {}

    /**
     * Page principale — affiche la biographie, la discographie
     * et les dates de tournée récupérées depuis Ticketmaster.
     */
    #[Route('/home', name: 'app_home')]
    public function index(): Response
    {
        $events = $this->fetchTicketmasterEvents('Megan Thee Stallion');

        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'tour_events'     => $events,
        ]);
    }

    /**
     * Interroge l'API Ticketmaster Discovery v2 pour récupérer
     * les événements à venir d'un artiste donné.
     *
     * @param string $artistName Nom de l'artiste à rechercher
     * @param int    $size       Nombre maximum de résultats (défaut : 10)
     *
     * @return array<int, array{
     *   name: string,
     *   date: string,
     *   city: string,
     *   venue: string,
     *   country: string,
     *   url: string
     * }> Liste des événements normalisés, tableau vide en cas d'erreur
     */
    private function fetchTicketmasterEvents(string $artistName, int $size = 10): array
    {
        try {
            $response = $this->httpClient->request('GET', 'https://app.ticketmaster.com/discovery/v2/events.json', [
                'query' => [
                    'apikey'          => $_ENV['TICKETMASTER_API_KEY'],
                    'keyword'         => $artistName,
                    'classificationName' => 'music',
                    'sort'            => 'date,asc',    // Trier par date croissante
                    'size'            => $size,
                ],
            ]);

            $data = $response->toArray();

            // Aucun événement trouvé
            if (empty($data['_embedded']['events'])) {
                return [];
            }

            // Normalisation des données brutes en tableau simple
            return array_map(
                fn(array $event) => $this->normalizeEvent($event),
                $data['_embedded']['events']
            );

        } catch (\Throwable $e) {
            // Log l'erreur sans faire planter la page
            // En production, utiliser un logger : $this->logger->error(...)
            return [];
        }
    }

    /**
     * Normalise un événement brut de l'API Ticketmaster
     * en un tableau structuré exploitable dans Twig.
     *
     * @param array $event Données brutes d'un événement Ticketmaster
     *
     * @return array{
     *   name: string,
     *   date: string,
     *   time: string,
     *   city: string,
     *   venue: string,
     *   country: string,
     *   url: string
     * }
     */
    private function normalizeEvent(array $event): array
    {
        // Accès défensif aux champs optionnels avec des valeurs par défaut
        $venue   = $event['_embedded']['venues'][0] ?? [];
        $dates   = $event['dates']['start'] ?? [];

        return [
            'name'    => $event['name']                         ?? 'Événement sans nom',
            'date'    => $dates['localDate']                    ?? 'Date inconnue',
            'time'    => $dates['localTime']                    ?? '',
            'city'    => $venue['city']['name']                 ?? 'Ville inconnue',
            'venue'   => $venue['name']                         ?? 'Salle inconnue',
            'country' => $venue['country']['name']              ?? '',
            'url'     => $event['url']                          ?? '#',
        ];
    }
}