<?php

namespace App\Controller;

use App\Entity\Contrat;
use App\Enum\Permission;
use App\Repository\ContratRepository;
use App\Service\ActivityLogger;
use App\Service\ContratSyncService; 
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route; 
use Symfony\Component\Security\Http\Attribute\IsGranted; 


#[Route('/contrats')]
#[IsGranted('ROLE_USER')]
final class ContratController extends AbstractController
{

 public function __construct(
        
        private ContratRepository $contratRepository,
        private ContratSyncService $contratSyncService,
    ) {}

   #[Route('', name: 'app_contrat_index', methods: ['GET'])]
public function index(Request $request, ContratRepository $contratRepository): Response
{
    // Synchronisation desactivee - utiliser: php bin/console app:sync-contrats

    $page = max(1, $request->query->getInt('page', 1));
    $limit = 10;
    $search = $request->query->get('search', '');
    $search = trim($search) ?: null;

    $contrats = $contratRepository->findPaginatedWithSearch($page, $limit, $search);
    $totalContrats = $contratRepository->countAll($search);
    $totalPages = (int) ceil($totalContrats / $limit);

    return $this->render('contrat/index.html.twig', [
        'contrats' => $contrats,
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'totalContrats' => $totalContrats,
        'search' => $search ?? '',
    ]);
}

  

    #[Route('/sync', name: 'app_contrat_sync', methods: ['POST'])]
    public function sync(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('sync-contrats', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide');
            return $this->redirectToRoute('app_contrat_index');
        }

        try {
            $contrats = $this->contratSyncService->syncAndGetContrats();
            $this->addFlash('success', sprintf('Synchronisation reussie : %d contrats', count($contrats)));
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur de synchronisation : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_contrat_index');
    }

    #[Route('/{id}', name: 'app_contrat_show', methods: ['GET'])]
    #[IsGranted(Permission::CONTRATS_VIEW_DETAILS)]
    public function show(Contrat $contrat): Response
    {
        return $this->render('contrat/show.html.twig', [
            'contrat' => $contrat,
        ]);
    }
}
