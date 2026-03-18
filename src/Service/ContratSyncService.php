<?php

namespace App\Service;

use App\Entity\Contrat;
use App\Repository\ContratRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ContratSyncService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private ContratRepository $contratRepository,
        private EntityManagerInterface $em,
        private RequestStack $requestStack,
    ) {}

    public function syncAndGetContrats(): array
    {
        $session  = $this->requestStack->getSession();
        $lastSync = $session->get('contrat_last_sync', 0);

        if (time() - $lastSync < 1800) {
            return $this->contratRepository->findAll();
        }

        $page   = 1;
        $synced = 0;

        while (true) {
            $response = $this->httpClient->request('GET', 'https://aksam.azurewebsites.net/api/contrats', [
                'verify_peer' => false,
                'verify_host' => false,
                'headers'     => ['Accept' => 'application/ld+json'],
                'query'       => ['page' => $page, 'limit' => 100],
            ]);

            $data = json_decode($response->getContent(false), true);

            if (!is_array($data)) {
                break;
            }

            $items = $data['member'] ?? $data['hydra:member'] ?? [];

            if (empty($items)) {
                break;
            }

            foreach ($items as $item) {
                $this->syncContrat($item); // ← Doctrine au lieu de SQL brut
                $synced++;
            }

            // Flush par batch pour éviter une surcharge mémoire
            $this->em->flush();
            $this->em->clear();

            error_log("[ContratSync] Page $page — $synced contrats traités");

            $hasNext = isset($data['view']['next'])
                || isset($data['hydra:view']['hydra:next']);

            if (!$hasNext || $page >= 200) {
                break;
            }

            $page++;
        }

        $session->set('contrat_last_sync', time());

        return $this->contratRepository->findAll();
    }

    private function syncContrat(array $data): void
{
    $externalId = (int) $data['id'];  // "id": 1  → externalId local

    $contrat = $this->contratRepository->findOneBy(['externalId' => $externalId]);

    if (!$contrat) {
        $contrat = new Contrat();
        $contrat->setExternalId($externalId);
        $this->em->persist($contrat);
    }

    $contrat->setNom(mb_substr($data['nom'] ?? '', 0, 20));
    $contrat->setPrenom(mb_substr($data['prenom'] ?? '', 0, 20));

    // "user": "/api/users/37"  →  externalUserId = 37
    if (!empty($data['user']) && is_string($data['user'])) {
        $contrat->setExternalUserId((int) basename($data['user']));
    }
}
}
 