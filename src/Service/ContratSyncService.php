<?php

namespace App\Service;

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
    $session = $this->requestStack->getSession();
    $lastSync = $session->get('contrat_last_sync', 0);

    if (time() - $lastSync < 1800) {
        return $this->contratRepository->findAll();
    }

    $conn = $this->em->getConnection();
    $page = 1;
    $synced = 0;

    while (true) {
        $response = $this->httpClient->request('GET', 'https://aksam.azurewebsites.net/api/contrats', [
    'verify_peer' => false,
    'verify_host' => false,
    'headers'     => ['Accept' => 'application/ld+json'],  // ← changer
    'query'       => [
        'page'  => $page,
        'limit' => 100,
    ],
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
            $this->upsertContrat($conn, $item);
            $synced++;
        }

        error_log("[ContratSync] Page $page — $synced contrats");

        // L'API utilise 'view' (pas 'hydra:view')
        $hasNext = isset($data['view']['next'])
            || isset($data['hydra:view']['hydra:next']);

        if (!$hasNext || $page >= 200) {
            break;
        }

        $page++;
    }

    error_log('[DEBUG] Clés: ' . implode(', ', array_keys($data ?? [])));
error_log('[DEBUG] member count: ' . count($data['member'] ?? []));
$items = $data['member'] ?? $data['hydra:member'] ?? [];
    $session->set('contrat_last_sync', time());

    return $this->contratRepository->findAll();
}

    private function getNextPage(array $data, int $currentPage, int $itemCount): ?int
    {
        // Cas 1 : hydra:view avec hydra:next
        if (isset($data['hydra:view']['hydra:next'])) {
            return $currentPage + 1;
        }

        // Cas 2 : view avec next
        if (isset($data['view']['next'])) {
            return $currentPage + 1;
        }

        // Cas 3 : calculer via totalItems
        $total = (int) ($data['hydra:totalItems'] ?? $data['totalItems'] ?? 0);
        if ($total > 0 && ($currentPage * $itemCount) < $total) {
            return $currentPage + 1;
        }

        return null;
    }

    private function upsertContrat(\Doctrine\DBAL\Connection $conn, array $data): void
    {
        $externalId = (int) $data['id'];
        $nom        = mb_substr($data['nom'] ?? '', 0, 20);
        $prenom     = mb_substr($data['prenom'] ?? '', 0, 20);

        // Verification de l'existence pour eviter les doublons
        $existingId = $conn->fetchOne(
            'SELECT id FROM contrat WHERE external_id = ?',
            [$externalId]
        );

        if ($existingId) {
            $conn->executeStatement(
                'UPDATE contrat SET nom = ?, prenom = ? WHERE id = ?',
                [$nom, $prenom, $existingId]
            );
        } else {
            $conn->executeStatement(
                'INSERT INTO contrat (nom, prenom, raison_sociale, external_id, created_at, user_id)
                 VALUES (?, ?, NULL, ?, NOW(), NULL)',
                [$nom, $prenom, $externalId]
            );
        }
    }
}
