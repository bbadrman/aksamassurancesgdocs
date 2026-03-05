<?php

namespace App\Controller\Api;

use App\Entity\Client;
use App\Entity\Contrat; 
use App\Repository\ContratRepository; 
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/Contratapi', name: 'api_client_')]
class ContratApiController extends AbstractController
{
    public function __construct(
        private ContratRepository $contratRepository,
        private EntityManagerInterface $em,
        private readonly HttpClientInterface $httpClient, 
    ) {}

    // GET /api/clients
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $contrats = $this->contratRepository->findAll();

        $data = array_map(fn(Contrat $contrat) => $this->serialize($contrat), $contrats);

        // Format JSON-LD attendu par le frontend (hydra:member)
        return $this->json([
            '@context'     => '/api/contexts/Contrat',
            '@id'          => '/api/contrats',
            '@type'        => 'hydra:Collection',
            'hydra:member' => $data,
            'hydra:totalItems' => count($data),
        ]);
    }

  #[Route('/clientsApi', name: 'app_client_index', methods: ['GET'])]
public function getApiclient(): Response
{
    try {
        $response = $this->httpClient->request('GET', 'https://ddev-aksamtest2-web/api/contrats', [
            'verify_peer' => false,
            'verify_host' => false,
            'headers' => [
                'Host'   => 'aksamtest2.ddev.site',
                'Accept' => 'application/json',
            ],
        ]);

        $data = json_decode($response->getContent(false), true);

        $rawClients = $data['member'] 
            ?? $data['hydra:member'] 
            ?? (array_is_list($data) ? $data : []);

        // Mapper les clés API vers les clés attendues par le template
        $clients = array_map(fn(array $c) => [
            'id'        => $c['id']           ?? null,
            'nom' => $c['nom']           ?? '',   // template: client.firstName
            'prenom'  => $c['prenom']        ?? '',   // template: client.prenom
            'email'     => $c['email']         ?? null,
            'phone'     => $c['phone']         ?? null,
            'documents' => $c['contrats']      ?? [],   // template: client.documents|length
            'createdAt' => isset($c['creatAt']) 
                ? new \DateTimeImmutable($c['creatAt']) 
                : null,
        ], $rawClients);

    } catch (\Exception $e) {
        $this->addFlash('error', 'Impossible de charger les clients : ' . $e->getMessage());
        $clients = [];
    }

    return $this->render('client/index.html.twig', [
        'clients' => $clients,
    ]);
}
    // GET /api/clients/{id}
    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): JsonResponse
    {
        $client = $this->contratRepository->find($id);

        if (!$client) {
            return $this->json(['error' => 'Client non trouvé'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->serialize($client));
    }

    // POST /api/clients
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Données invalides'], Response::HTTP_BAD_REQUEST);
        }

        $contrat = new Contrat();
        $this->hydrate($contrat, $data);

        $this->em->persist($contrat);
        $this->em->flush();

        return $this->json($this->serialize($contrat), Response::HTTP_CREATED);
    }

    // PUT /api/clients/{id}
    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $contrat = $this->contratRepository->find($id);

        if (!$contrat) {
            return $this->json(['error' => 'Contrat non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        $this->hydrate($contrat, $data);
        $this->em->flush();

        return $this->json($this->serialize($contrat));
    }

    // DELETE /api/clients/{id}
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $contrat = $this->contratRepository->find($id);

        if (!$contrat) {
            return $this->json(['error' => 'Contrat non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $this->em->remove($contrat);
        $this->em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    // --- Helpers ---

    private function serialize(Contrat $contrat): array
    {
        return [
            'id'           => $contrat->getId(),
            'nom'          => $contrat->getNom(),
            'prenom'       => $contrat->getPrenom(), 
        ];
    }

    private function hydrate(Contrat $contrat, array $data): void
    {
        if (isset($data['nom']))       $contrat->setNom($data['nom']);
        if (isset($data['prenom']))    $contrat->setPrenom($data['prenom']); 
    }
}