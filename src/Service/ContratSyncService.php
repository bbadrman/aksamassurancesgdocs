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
    public function syncAndGetClients(): array
    {
        $response = $this->httpClient->request('GET', 'https://ddev-aksamtest2-web/api/contrats', [
            'verify_peer' => false,
            'verify_host' => false,
            'headers' => [
                'Host'   => 'aksamtest2.ddev.site',
                'Accept' => 'application/json',
            ],
        ]);

        $data = json_decode($response->getContent(false), true);
        $externalClients = $data['member'] 
            ?? $data['hydra:member'] 
            ?? (array_is_list($data) ? $data : []);

        foreach ($externalClients as $externalClient) {
            $this->syncClient($externalClient);
        }

        $this->em->flush();

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
        $client->setNom($data['nom']    ?? '');
        $client->setPrenom($data['prenom'] ?? ''); 
    }
}