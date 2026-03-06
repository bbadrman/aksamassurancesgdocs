<?php

namespace App\Controller\Admin;

use App\Repository\UserRepository;
use App\Service\UserSyncService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/externe/users')]
class UserExternController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private UserSyncService $userSyncService,
        private HttpClientInterface $httpClient,
    ) {}

    #[Route('', name: 'externe_users_index')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Users locaux docmanager
        $localUsers = $this->userRepository->findAll();

        // Users externes aksamtest2
        $externalUsers = [];
        try {
            $response = $this->httpClient->request('GET', 'https://ddev-aksamtest2-web/api/users', [
                'verify_peer' => false,
                'verify_host' => false,
                'headers' => [
                    'Host'   => 'aksamtest2.ddev.site',
                    'Accept' => 'application/ld+json',
                ],
            ]);
            $data = json_decode($response->getContent(false), true);
            $rawUsers = $data['member'] ?? $data['hydra:member'] ?? [];

            // Normaliser les données pour le template
            $externalUsers = array_map(fn(array $u) => [
                'id'        => $u['id'] ?? (int) basename($u['@id'] ?? '0'),
                'username'  => $u['username'] ?? null,
                'firstname' => $u['firstname'] ?? null,
                'lastname'  => $u['lastname'] ?? null,
                'contrats'  => array_filter(
                    $u['contrats'] ?? [],
                    fn($c) => is_string($c) // garder uniquement les IRI strings
                ),
            ], $rawUsers);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Impossible de charger les users externes : ' . $e->getMessage());
        }

        return $this->render('admin/externe/index.html.twig', [
            'users'         => $localUsers,
            'externalUsers' => $externalUsers,
        ]);
    }

    #[Route('/{id}/link/{externalId}', name: 'externe_users_link', methods: ['POST'])]
    public function link(int $id, int $externalId, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = $this->userRepository->find($id);
        if ($user) {
            $user->setExternalId($externalId);
            $em->flush();

            // Synchroniser les contrats maintenant que le lien est établi
            try {
                $this->userSyncService->syncAndGetUsers();
                $this->addFlash('success', 'User lié et contrats synchronisés.');
            } catch (\Exception $e) {
                $this->addFlash('success', 'User lié avec succès.');
            }
        }

        return $this->redirectToRoute('externe_users_index');
    }

    #[Route('/{id}/unlink', name: 'externe_users_unlink', methods: ['POST'])]
    public function unlink(int $id, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = $this->userRepository->find($id);
        if ($user) {
            $user->setExternalId(null);
            $em->flush();
            $this->addFlash('success', 'Lien supprimé.');
        }

        return $this->redirectToRoute('externe_users_index');
    }
}
