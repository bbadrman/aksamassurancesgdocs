<?php

namespace App\Security;

 
use App\Entity\Contrat;
use App\Entity\User;
use App\Enum\Permission;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Voter for Client permissions
 * 
 * Supports granular permission keys:
 * - contrats.view_list: Access /contrats list
 * - contrats.view_details: Access /contrats/{id}
 * - contrats.create: Create new contrats
 * - contrats.edit: Edit contrats
 * - contrats.delete: Delete contrats
 * - contrats.view_documents_column: See Documents column in table
 * - contrats.view_actions_column: See Actions column in table
 * - contrats.view_view_button: See View button
 */
final class ClientVoter extends Voter
{
    // Permission constants - mapped to Permission enum
    protected function supports(string $attribute, mixed $subject): bool
    {
        // If subject is a Contrat, we're checking for specific contrat access
        if ($subject instanceof Contrat) {
            return in_array($attribute, [
                Permission::CONTRATS_VIEW_DETAILS,
                Permission::CONTRATS_EDIT,
                Permission::CONTRATS_DELETE,
            ]);
        }
        
        // Only allow general permission checks (without subject)
        // Reject any other object type
        if ($subject !== null) {
            return false;
        }
        
        // Support both old-style and new permission keys
        $supportedAttributes = [
            // New granular permissions
            Permission::CONTRATS_VIEW_LIST,
            Permission::CONTRATS_VIEW_DETAILS,
            Permission::CONTRATS_CREATE,
            Permission::CONTRATS_EDIT,
            Permission::CONTRATS_DELETE,
            Permission::CONTRATS_VIEW_DOCUMENTS_COLUMN,
            Permission::CONTRATS_VIEW_ACTIONS_COLUMN,
            Permission::CONTRATS_VIEW_BUTTON,
            // Legacy aliases for backward compatibility
            'CONTRAT_VIEW',
            'CONTRAT_CREATE',
            'CONTRAT_EDIT',
            'CONTRAT_DELETE',
        ];
        
        return in_array($attribute, $supportedAttributes);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }

        // Admin ALWAYS has full access - bypass all permission checks
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        // Map legacy attributes to new permissions
        $permission = $this->mapLegacyAttribute($attribute);
        
        // Use User entity's hasPermission method (handles admin check internally)
        if ($user instanceof User) {
            return $user->hasPermission($permission);
        }
        
        return false;
    }
    
    /**
     * Map legacy attribute names to new Permission enum values
     */
    private function mapLegacyAttribute(string $attribute): string
    {
        return match ($attribute) {
            // Legacy mappings
            'CLIENT_VIEW' => Permission::CONTRATS_VIEW_LIST,
            'CLIENT_CREATE' => Permission::CONTRATS_CREATE,
            'CLIENT_EDIT' => Permission::CONTRATS_EDIT,
            'CLIENT_DELETE' => Permission::CONTRATS_DELETE,
            // New permissions - return as is
            default => $attribute,
        };
    }
}
