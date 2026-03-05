<?php

namespace App\Controller;

 
use App\Entity\Contrat;
use App\Entity\Document;
use App\Enum\Permission;
use App\Form\DocumentType; 
use App\Repository\ContratRepository;
use App\Repository\DocumentRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Vich\UploaderBundle\Handler\DownloadHandler;

#[Route('/documents')]
final class DocumentController extends AbstractController
{
    /**
     * List all documents with search and filters
     */
    #[Route('', name: 'app_document_index', methods: ['GET'])]
    #[IsGranted(Permission::DOCUMENTS_VIEW_LIST)]
    public function index(
        Request $request,
        DocumentRepository $documentRepository,
        ContratRepository $contratRepository
    ): Response {
        $search = $request->query->get('search');
        $contratId = $request->query->get('contrat');
        $fileType = $request->query->get('fileType');
        $dateFrom = $request->query->get('dateFrom');
        $dateTo = $request->query->get('dateTo');
        $page = (int) $request->query->get('page', 1);

        $contrat = $contratId ? $contratRepository->find($contratId) : null;
        $dateFromObj = $dateFrom ? new \DateTime($dateFrom) : null;
        $dateToObj = $dateTo ? new \DateTime($dateTo) : null;

        $result = $documentRepository->findWithFilters(
            search: $search,
            contrat: $contrat,
            fileType: $fileType,
            dateFrom: $dateFromObj,
            dateTo: $dateToObj,
            page: $page,
            limit: 20
        );

        return $this->render('document/index.html.twig', [
            'documents' => $result['items'],
            'pagination' => [
                'page' => $result['page'],
                'totalPages' => $result['totalPages'],
                'total' => $result['total'],
            ],
            'filters' => [
                'search' => $search,
                'contrat' => $contrat,
                'fileType' => $fileType,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
            ],
            'contrats' => $contratRepository->findAll(),
        ]);
    }

    /**
     * Trash - view deleted documents
     */
    #[Route('/trash', name: 'app_document_trash', methods: ['GET'])]
    #[IsGranted(Permission::DOCUMENTS_DELETE)]
    public function trash(DocumentRepository $documentRepository): Response
    {
        $deletedDocuments = $documentRepository->findDeleted();

        return $this->render('document/trash.html.twig', [
            'documents' => $deletedDocuments,
        ]);
    }

    /**
     * Restore document from trash
     */
    #[Route('/{id}/restore', name: 'app_document_restore', methods: ['POST'])]
    #[IsGranted(Permission::DOCUMENTS_DELETE)]
    public function restore(Request $request, Document $document, EntityManagerInterface $em, ActivityLogger $activityLogger): Response
    {
        if ($this->isCsrfTokenValid('restore-document-' . $document->getId(), $request->request->get('_token'))) {
            $document->restore();
            $em->flush();

            $activityLogger->logRestore($this->getUser(), $document);

            $this->addFlash('success', 'Document restored from trash.');
        }

        return $this->redirectToRoute('app_document_trash');
    }

    /**
     * Permanently delete document
     */
    #[Route('/{id}/permanent-delete', name: 'app_document_permanent_delete', methods: ['POST'])]
    #[IsGranted(Permission::DOCUMENTS_DELETE)]
    public function permanentDelete(Request $request, Document $document, EntityManagerInterface $em, ActivityLogger $activityLogger): Response
    {
        if ($this->isCsrfTokenValid('permanent-delete-document-' . $document->getId(), $request->request->get('_token'))) {
            // Get file path before removing
            $filePath = null;
            if ($document->getFileName()) {
                $uploadDir = $this->getParameter('vich_uploader.document_file');
                $filePath = $uploadDir . '/' . $document->getFileName();
            }

            $activityLogger->logPermanentDelete($this->getUser(), $document);

            // Remove from database
            $em->remove($document);
            $em->flush();

            // Optionally delete the physical file
            if ($filePath && file_exists($filePath)) {
                unlink($filePath);
            }

            $this->addFlash('success', 'Document permanently deleted.');
        }

        return $this->redirectToRoute('app_document_trash');
    }

    /**
     * List all documents for a given contrat (legacy route).
     */
    #[Route('/contrat/{id}', name: 'app_document_list', methods: ['GET'])]
    #[IsGranted(Permission::DOCUMENTS_VIEW_LIST)]
    public function list(Contrat $contrat, DocumentRepository $documentRepository): Response
    {
        $documents = $documentRepository->findByContrat($contrat);

        return $this->render('document/list.html.twig', [
            'contrat' => $contrat,
            'documents' => $documents,
        ]);
    }

    /**
     * Upload a new document for a contrat.
     */
    #[Route('/contrat/{id}/new', name: 'app_document_new', methods: ['GET', 'POST'])]
    #[IsGranted(Permission::DOCUMENTS_CREATE_UPLOAD)]
    public function new(Request $request, Contrat $contrat, EntityManagerInterface $em, ActivityLogger $activityLogger): Response
    {
        $document = new Document();
        $document->setContrat($contrat);
        $document->setOwner($this->getUser());

        $form = $this->createForm(DocumentType::class, $document, ['is_new' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Capture original filename and metadata before Vich renames
            $uploadedFile = $form->get('file')->getData();
            if ($uploadedFile) {
                $document->setOriginalName($uploadedFile->getClientOriginalName());
                $document->setMimeType($uploadedFile->getMimeType());
                $document->setSize($uploadedFile->getSize());
            }

            $em->persist($document);
            $em->flush();

            $activityLogger->logUpload($this->getUser(), $document);

            $this->addFlash('success', 'Document uploaded successfully.');

            return $this->redirectToRoute('app_document_list', ['id' => $contrat->getId()]);
        }

        return $this->render('document/new.html.twig', [
            'form' => $form,
            'contrat' => $contrat,
        ]);
    }

    /**
     * Show document details with preview support.
     */
    #[Route('/{id}/show', name: 'app_document_show', methods: ['GET'])]
    #[IsGranted(Permission::DOCUMENTS_VIEW_DETAILS)]
    public function show(Document $document): Response
    {
        return $this->render('document/show.html.twig', [
            'document' => $document,
        ]);
    }

    /**
     * View document in separate page.
     */
    #[Route('/{id}/view', name: 'app_document_view', methods: ['GET'])]
    #[IsGranted(Permission::DOCUMENTS_VIEW_DETAILS)]
    public function view(Document $document): Response
    {
        // Check if document has a file to view
        if (!$document->getFileName()) {
            $this->addFlash('error', 'This document has no file to view.');
            return $this->redirectToRoute('app_document_show', ['id' => $document->getId()]);
        }

        $fileContent = null;
        if (str_starts_with($document->getMimeType(), 'text/')) {
            $uploadDir = $this->getParameter('kernel.project_dir') . '/var/uploads/documents';
            $filePath = $uploadDir . '/' . $document->getFileName();
            if (file_exists($filePath)) {
                $fileContent = file_get_contents($filePath);
            }
        }

        return $this->render('document/view.html.twig', [
            'document' => $document,
            'fileContent' => $fileContent,
        ]);
    }

    /**
     * Serve document file for viewing (inline).
     */
    #[Route('/{id}/serve', name: 'app_document_serve', methods: ['GET'])]
    #[IsGranted(Permission::DOCUMENTS_VIEW_DETAILS)]
    public function serve(Document $document, DownloadHandler $downloadHandler): Response
    {
        // Check if document has a file to serve
        if (!$document->getFileName()) {
            $this->addFlash('error', 'This document has no file to view.');
            return $this->redirectToRoute('app_document_show', ['id' => $document->getId()]);
        }

        // Serve file inline
        $response = $downloadHandler->downloadObject(
            $document,
            'file',
            null,
            null,
            false // inline (not attachment)
        );

        // Ensure correct content type for PDFs
        if ($document->isPdf()) {
            $response->headers->set('Content-Type', 'application/pdf');
        }

        // Ensure correct content type for images
        if ($document->isImage()) {
            $response->headers->set('Content-Type', $document->getMimeType());
        }

        // Ensure correct content type for text files
        if (str_starts_with($document->getMimeType(), 'text/')) {
            $response->headers->set('Content-Type', $document->getMimeType() . '; charset=utf-8');
        }

        return $response;
    }

    /**
     * Download a document file.
     */
    #[Route('/{id}/download', name: 'app_document_download', methods: ['GET'])]
    #[IsGranted(Permission::DOCUMENTS_DOWNLOAD)]
    public function download(Document $document, DownloadHandler $downloadHandler, ActivityLogger $activityLogger): Response
    {
        $activityLogger->logDownload($this->getUser(), $document);

        return $downloadHandler->downloadObject(
            $document,
            'file',
            null,
            $document->getOriginalName() ?? $document->getFileName(),
            true // attachment (force download)
        );
    }

    /**
     * Soft delete a document (move to trash).
     */
    #[Route('/{id}/delete', name: 'app_document_delete', methods: ['POST'])]
    #[IsGranted(Permission::DOCUMENTS_DELETE)]
    public function delete(Request $request, Document $document, EntityManagerInterface $em, ActivityLogger $activityLogger): Response
    {
        $contratId = $document->getContrat()?->getId();

        if ($this->isCsrfTokenValid('delete-document-' . $document->getId(), $request->request->get('_token'))) {
            // Soft delete instead of hard delete
            $document->softDelete();
            $em->flush();

            $activityLogger->logDelete($this->getUser(), $document);

            $this->addFlash('success', 'Document moved to trash.');
        }

        if ($contratId) {
            return $this->redirectToRoute('app_document_list', ['id' => $contratId]);
        }

        return $this->redirectToRoute('app_document_index');
    }

    /**
     * Edit document metadata (not the file).
     */
    #[Route('/{id}/edit', name: 'app_document_edit', methods: ['GET', 'POST'])]
    #[IsGranted(Permission::DOCUMENTS_EDIT)]
    public function edit(Request $request, Document $document, EntityManagerInterface $em, ActivityLogger $activityLogger): Response
    {
        $form = $this->createForm(DocumentType::class, $document, ['is_new' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $activityLogger->logEdit($this->getUser(), $document);

            $this->addFlash('success', 'Document updated successfully.');

            return $this->redirectToRoute('app_document_show', ['id' => $document->getId()]);
        }

        return $this->render('document/edit.html.twig', [
            'form' => $form,
            'document' => $document,
        ]);
    }
}
