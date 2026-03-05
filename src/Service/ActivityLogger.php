<?php

namespace App\Service;

use App\Entity\ActivityLog; 
use App\Entity\Contrat;
use App\Entity\Document;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Service to log user activities (audit log)
 */
class ActivityLogger
{
    public function __construct(
        private readonly EntityManagerInterface $em
    ) {}

    /**
     * Log an activity
     */
    public function log(
        UserInterface $user,
        string $action,
        ?Document $document = null,
        ?Contrat $contrat = null,
        ?string $details = null
    ): ActivityLog {
        $activityLog = new ActivityLog();
        $activityLog->setUser($user instanceof User ? $user : null);
        $activityLog->setAction($action);
        $activityLog->setDocument($document);
        $activityLog->setContrat($contrat);
        $activityLog->setDetails($details);

        $this->em->persist($activityLog);
        $this->em->flush();

        return $activityLog;
    }

    /**
     * Log document upload
     */
    public function logUpload(UserInterface $user, Document $document): ActivityLog
    {
        return $this->log(
            $user,
            ActivityLog::ACTION_UPLOAD,
            $document,
            $document->getContrat(),
            sprintf('Uploaded: %s', $document->getTitle())
        );
    }

    /**
     * Log document soft delete (move to trash)
     */
    public function logDelete(UserInterface $user, Document $document): ActivityLog
    {
        return $this->log(
            $user,
            ActivityLog::ACTION_DELETE,
            $document,
            $document->getContrat(),
            sprintf('Moved to trash: %s', $document->getTitle())
        );
    }

    /**
     * Log document permanent delete
     */
    public function logPermanentDelete(UserInterface $user, Document $document): ActivityLog
    {
        return $this->log(
            $user,
            ActivityLog::ACTION_PERMANENT_DELETE,
            $document,
            $document->getContrat(),
            sprintf('Permanently deleted: %s', $document->getTitle())
        );
    }

    /**
     * Log document download
     */
    public function logDownload(UserInterface $user, Document $document): ActivityLog
    {
        return $this->log(
            $user,
            ActivityLog::ACTION_DOWNLOAD,
            $document,
            $document->getContrat(),
            sprintf('Downloaded: %s', $document->getTitle())
        );
    }

    /**
     * Log document restore from trash
     */
    public function logRestore(UserInterface $user, Document $document): ActivityLog
    {
        return $this->log(
            $user,
            ActivityLog::ACTION_RESTORE,
            $document,
            $document->getContrat(),
            sprintf('Restored from trash: %s', $document->getTitle())
        );
    }

    /**
     * Log document edit
     */
    public function logEdit(UserInterface $user, Document $document): ActivityLog
    {
        return $this->log(
            $user,
            ActivityLog::ACTION_EDIT,
            $document,
            $document->getContrat(),
            sprintf('Edited: %s', $document->getTitle())
        );
    }

    /**
     * Log contrat create
     */
    public function logContratCreate(UserInterface $user, Contrat $contrat): ActivityLog
    {
        return $this->log(
            $user,
            ActivityLog::ACTION_CONTRAT_CREATE,
            null,
            $contrat,
            sprintf('Created contrat: %s', $contrat->__toString())
        );
    }

    /**
     * Log contrat edit
     */
    public function logContratEdit(UserInterface $user, Contrat $contrat): ActivityLog
    {
        return $this->log(
            $user,
            ActivityLog::ACTION_CONTRAT_EDIT,
            null,
            $contrat,
            sprintf('Edited contrat: %s', $contrat->__toString())
        );
    }

    /**
     * Log contrat delete
     */
    public function logContratDelete(UserInterface $user, Contrat $contrat): ActivityLog
    {
        return $this->log(
            $user,
            ActivityLog::ACTION_CONTRAT_DELETE,
            null,
            $contrat,
            sprintf('Deleted contrat: %s', $contrat->__toString())
        );
    }
}
