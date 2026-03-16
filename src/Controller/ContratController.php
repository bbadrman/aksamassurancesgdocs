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
    try {
        $this->contratSyncService->syncAndGetContrats();
    } catch (\Exception $e) {
        // Log l'erreur pour debug
        error_log('[ContratSync] ERREUR: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
        // fallback : on continue quand même
    }

    $page = max(1, $request->query->getInt('page', 1));
    $limit = 10;

    $contrats = $contratRepository->findPaginated($page, $limit);
    $totalContrats = $contratRepository->countAll();
    $totalPages = (int) ceil($totalContrats / $limit);

    return $this->render('contrat/index.html.twig', [
        'contrats' => $contrats,
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'totalContrats' => $totalContrats,
    ]);
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
