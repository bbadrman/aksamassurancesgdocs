<?php
namespace App\Service;

use App\Entity\Contrat;
use App\Entity\User; 
use App\Repository\ContratRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class UserSyncService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private UserRepository $userRepository,
        private ContratRepository $contratRepository,
        private EntityManagerInterface $em,
    ) {}

    public function syncAndGetUsers(): array
    {
        $response = $this->httpClient->request('GET', 'https://ddev-aksamtest2-web/api/users', [
            'verify_peer' => false,
            'verify_host' => false,
            'headers' => [
                'Host'   => 'aksamtest2.ddev.site',
                'Accept' => 'application/ld+json',
            ],
        ]);

        $data = json_decode($response->getContent(false), true);
        $externalUsers = $data['member'] 
            ?? $data['hydra:member'] 
            ?? (array_is_list($data) ? $data : []);

        foreach ($externalUsers as $externalUser) {
            $this->syncUser($externalUser);
        }

        $this->em->flush();

        // Retourne uniquement les users locaux qui ont des contrats
        return $this->userRepository->findAll();
    }

    private function syncUser(array $data): void
{
    $externalId  = $data['id'];
    $contratIris = $data['contrats'] ?? [];

    if (empty($contratIris)) {
        return;
    }

    // Chercher le user docmanager qui a déjà cet externalId
    $user = $this->userRepository->findOneBy(['externalId' => $externalId]);

    // Si pas trouvé → on ne crée pas, on ne peut pas matcher automatiquement
    if (!$user) {
        // Log pour l'admin : cet user aksamtest2 n'est pas encore lié
        return;
    }

    // Synchroniser les contrats
    foreach ($contratIris as $iri) {
        $externalContratId = (int) basename($iri);
        $contrat = $this->contratRepository->findOneBy(['externalId' => $externalContratId]);

        if (!$contrat) {
            $this->fetchAndCreateContrat($externalContratId, $user);
        } else {
            $contrat->setUser($user);
        }
    }
}

    private function fetchAndCreateContrat(int $externalContratId, User $user): Contrat
    {
        try {
            $response = $this->httpClient->request(
                'GET', 
                "https://ddev-aksamtest2-web/api/contrats/{$externalContratId}", 
                [
                    'verify_peer' => false,
                    'verify_host' => false,
                    'headers' => [
                        'Host'   => 'aksamtest2.ddev.site',
                        'Accept' => 'application/json',
                    ],
                ]
            );

            $data = json_decode($response->getContent(false), true);

        } catch (\Exception $e) {
            $data = [];
        }

        $contrat = new Contrat();
        $contrat->setExternalId($externalContratId);
        $contrat->setNom($data['nom'] ?? null);
        $contrat->setPrenom($data['prenom'] ?? null);
        $contrat->setRaisonSociale($data['raisonSociale'] ?? null);
        $contrat->setUser($user);

        $this->em->persist($contrat);

        return $contrat;
    }
}
 