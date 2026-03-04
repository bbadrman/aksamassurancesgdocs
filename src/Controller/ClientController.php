<?php

namespace App\Controller;

use App\Entity\Client;
use App\Enum\Permission;
use App\Form\ClientType;
use App\Repository\ClientRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted; 

#[Route('/clients')]
#[IsGranted('ROLE_USER')]
final class ClientController extends AbstractController
{
    #[Route('', name: 'app_client_index', methods: ['GET'])]
    #[IsGranted(Permission::CLIENTS_VIEW_LIST)]
    public function index(ClientRepository $clientRepository): Response
    {
        return $this->render('client/index.html.twig', [
            'clients' => $clientRepository->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

   
    #[Route('/new', name: 'app_client_new', methods: ['GET', 'POST'])]
    #[IsGranted(Permission::CLIENTS_CREATE)]
    public function new(Request $request, EntityManagerInterface $em, ActivityLogger $activityLogger): Response
    {
        $client = new Client();
        $form = $this->createForm(ClientType::class, $client);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($client);
            $em->flush();

            $activityLogger->logClientCreate($this->getUser(), $client);

            $this->addFlash('success', 'Client created successfully.');

            return $this->redirectToRoute('app_client_show', ['id' => $client->getId()]);
        }

        return $this->render('client/new.html.twig', [
            'form' => $form,
            'client' => $client,
        ]);
    }

    #[Route('/{id}', name: 'app_client_show', methods: ['GET'])]
    #[IsGranted(Permission::CLIENTS_VIEW_DETAILS)]
    public function show(Client $client): Response
    {
        return $this->render('client/show.html.twig', [
            'client' => $client,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_client_edit', methods: ['GET', 'POST'])]
    #[IsGranted(Permission::CLIENTS_EDIT)]
    public function edit(Request $request, Client $client, EntityManagerInterface $em, ActivityLogger $activityLogger): Response
    {
        $form = $this->createForm(ClientType::class, $client);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $activityLogger->logClientEdit($this->getUser(), $client);

            $this->addFlash('success', 'Client updated successfully.');

            return $this->redirectToRoute('app_client_show', ['id' => $client->getId()]);
        }

        return $this->render('client/edit.html.twig', [
            'form' => $form,
            'client' => $client,
        ]);
    }

   

    #[Route('/{id}/delete', name: 'app_client_delete', methods: ['POST'])]
    #[IsGranted(Permission::CLIENTS_DELETE)]
    public function delete(Request $request, Client $client, EntityManagerInterface $em, ActivityLogger $activityLogger): Response
    {
        if ($this->isCsrfTokenValid('delete-client-' . $client->getId(), $request->request->get('_token'))) {
            $activityLogger->logClientDelete($this->getUser(), $client);

            $em->remove($client);
            $em->flush();

            $this->addFlash('success', 'Client deleted successfully.');
        }

        return $this->redirectToRoute('app_client_index');
    }

    #[Route('/api/external', name: 'app_client_apiext', methods: ['GET'])]
    public function apiext(HttpClientInterface $httpClient): JsonResponse
    {
        $response = $httpClient->request('GET', 'http://aksamtest2.ddev.site/api/clients');

        $data = $response->toArray();

        return new JsonResponse($data);
    }
}
