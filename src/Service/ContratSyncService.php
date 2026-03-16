<?php
namespace App\Service;

use App\Entity\Contrat;
use App\Repository\ClientRepository;
use App\Repository\ContratRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ContratSyncService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private ContratRepository $contratRepository,
        private EntityManagerInterface $em,
    ) {}

    /**
     * Récupère les clients externes et les synchronise localement
     * Retourne la liste des clients locaux avec leurs documents
     */
    public function syncAndGetContrats(): array
    {
        $page = 1;
        $hasMore = true;

        while ($hasMore) {
            $response = $this->httpClient->request('GET', 'https://aksam.azurewebsites.net/api/contrats', [
                'verify_peer' => false,
                'verify_host' => false,
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'query' => [
                    'page' => $page,
                ],
            ]);

            $data = json_decode($response->getContent(false), true);
            $externalClients = $data['member'] 
                ?? $data['hydra:member'] 
                ?? (is_array($data) && array_is_list($data) ? $data : []);

            if (empty($externalClients)) {
                break;
            }

            foreach ($externalClients as $externalClient) {
                $this->syncClient($externalClient);
            }

            // Flush par page pour isoler les erreurs
            try {
                $this->em->flush();
                $this->em->clear(); // libère la mémoire
            } catch (\Exception $e) {
                // En cas d'erreur sur cette page, on continue avec la suivante
                $this->em->clear();
            }

            if (isset($data['hydra:view'])) {
                if (isset($data['hydra:view']['hydra:next'])) {
                    $page++;
                } else {
                    $hasMore = false;
                }
            } elseif (count($externalClients) > 0) {
                $page++;
            } else {
                $hasMore = false;
            }

            // Sécurité anti-boucle infinie (max 100 pages)
            if ($page > 100) {
                break;
            }
        }

        // Retourne les clients locaux avec leurs documents
        return $this->contratRepository->findAll();
    }

    private function syncClient(array $data): void
    {
        $externalId = $data['id'];

        // Chercher le client local par externalId
        $client = $this->contratRepository->findOneBy(['externalId' => $externalId]);

        if (!$client) {
            $client = new Contrat();
            $client->setExternalId($externalId);
            $this->em->persist($client);
        }

        // Mettre à jour les données depuis l'API externe
        $client->setNom(mb_substr($data['nom'] ?? '', 0, 20));
        $client->setPrenom(mb_substr($data['prenom'] ?? '', 0, 20));
    }
}