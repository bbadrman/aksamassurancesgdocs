<?php

namespace App\Enum;

final class Permission
{
    // ==================== CLIENT PERMISSIONS ====================
    
    

    // ==================== CONTRAT PERMISSIONS ====================
    
    // List/View permissions
    public const CONTRATS_VIEW_LIST = 'contrats.view_list';
    public const CONTRATS_VIEW_DETAILS = 'contrats.view_details';
    
    // Action permissions
    public const CONTRATS_CREATE = 'contrats.create';
    public const CONTRATS_EDIT = 'contrats.edit';
    public const CONTRATS_DELETE = 'contrats.delete';
    
    // UI control permissions (for columns visibility)
    public const CONTRATS_VIEW_DOCUMENTS_COLUMN = 'contrats.view_documents_column';
    public const CONTRATS_VIEW_ACTIONS_COLUMN = 'contrats.view_actions_column';
    public const CONTRATS_VIEW_BUTTON = 'contrats.view_view_button';

    // ==================== DOCUMENT PERMISSIONS ====================
    
    // List/View permissions
    public const DOCUMENTS_VIEW_LIST = 'documents.view_list';
    public const DOCUMENTS_VIEW_DETAILS = 'documents.view_details';
    
    // Action permissions
    public const DOCUMENTS_CREATE_UPLOAD = 'documents.create_upload';
    public const DOCUMENTS_EDIT = 'documents.edit';
    public const DOCUMENTS_DELETE = 'documents.delete';
    public const DOCUMENTS_DOWNLOAD = 'documents.download';

    // ==================== USER MANAGEMENT PERMISSIONS ====================
    
    public const USERS_VIEW = 'users.view';
    public const USERS_CREATE = 'users.create';
    public const USERS_EDIT = 'users.edit';
    public const USERS_DELETE = 'users.delete';
    public const USERS_ASSIGN_ROLES = 'users.assign_roles';

    /**
     * Get all permissions grouped by category
     */
    public static function getGroups(): array
    {
        return [
            'Contrats' => [
                self::CONTRATS_VIEW_LIST => 'View Contrats List',
                self::CONTRATS_VIEW_DETAILS => 'View Contrat Details',
                self::CONTRATS_CREATE => 'Create Contrats',
                self::CONTRATS_EDIT => 'Edit Contrats',
                self::CONTRATS_DELETE => 'Delete Contrats',
                self::CONTRATS_VIEW_DOCUMENTS_COLUMN => 'See Documents Column',
                self::CONTRATS_VIEW_ACTIONS_COLUMN => 'See Actions Column',
                self::CONTRATS_VIEW_BUTTON => 'See View Button',
            ],
 
             
            'Documents' => [
                self::DOCUMENTS_VIEW_LIST => 'View Documents List',
                self::DOCUMENTS_VIEW_DETAILS => 'View Document Details',
                self::DOCUMENTS_CREATE_UPLOAD => 'Upload Documents',
                self::DOCUMENTS_EDIT => 'Edit Documents',
                self::DOCUMENTS_DELETE => 'Delete Documents',
                self::DOCUMENTS_DOWNLOAD => 'Download Documents',
            ],
            'User Management' => [
                self::USERS_VIEW => 'View Users',
                self::USERS_CREATE => 'Create Users',
                self::USERS_EDIT => 'Edit Users',
                self::USERS_DELETE => 'Delete Users',
                self::USERS_ASSIGN_ROLES => 'Assign Roles & Permissions',
            ],
        ];
    }

    /**
     * Get all permissions as a flat array
     */
    public static function getAll(): array
    {
        $permissions = [];
        foreach (self::getGroups() as $group => $perms) {
            $permissions = array_merge($permissions, array_keys($perms));
        }
        return $permissions;
    }

    /**
     * Get permissions for UI control (column visibility)
     */
    public static function getUIPermissions(): array
    {
        return [
            self::CONTRATS_VIEW_DOCUMENTS_COLUMN,
            self::CONTRATS_VIEW_ACTIONS_COLUMN,
            self::CONTRATS_VIEW_BUTTON,
        ];
    }

    /**
     * Check if a permission is a UI permission
     */
    public static function isUIPermission(string $permission): bool
    {
        return in_array($permission, self::getUIPermissions(), true);
    }

    // ==================== DEFAULT PERMISSION SETS ====================
    
    /**
     * Read-only: Can only view lists and details
     */
    public static function getReadOnlyPermissions(): array
    {
        return [
            self::CONTRATS_VIEW_LIST,
            self::CONTRATS_VIEW_DETAILS,
            self::CONTRATS_VIEW_DOCUMENTS_COLUMN,
            self::CONTRATS_VIEW_ACTIONS_COLUMN,
            self::CONTRATS_VIEW_BUTTON,
            self::DOCUMENTS_VIEW_LIST,
            self::DOCUMENTS_VIEW_DETAILS,
            self::DOCUMENTS_DOWNLOAD,
        ];
    }

    /**
     * contrat-view-only: Can view contrats and their documents
     */
    public static function getClientViewOnlyPermissions(): array
    {
        return [
            self::CONTRATS_VIEW_LIST,
            self::CONTRATS_VIEW_DETAILS,
            self::CONTRATS_VIEW_DOCUMENTS_COLUMN,
            self::CONTRATS_VIEW_ACTIONS_COLUMN,
            self::CONTRATS_VIEW_BUTTON,
            self::DOCUMENTS_VIEW_LIST,
            self::DOCUMENTS_VIEW_DETAILS,
            self::DOCUMENTS_DOWNLOAD,
        ];
    }

    /**
     * Limited: Can view and upload documents, but not edit/delete contrats
     */
    public static function getLimitedPermissions(): array
    {
        return [
            self::CONTRATS_VIEW_LIST,
            self::CONTRATS_VIEW_DETAILS,
            self::CONTRATS_VIEW_DOCUMENTS_COLUMN,
            self::CONTRATS_VIEW_ACTIONS_COLUMN,
            self::CONTRATS_VIEW_BUTTON,
            self::DOCUMENTS_VIEW_LIST,
            self::DOCUMENTS_VIEW_DETAILS,
            self::DOCUMENTS_CREATE_UPLOAD,
            self::DOCUMENTS_EDIT,
            self::DOCUMENTS_DOWNLOAD,
        ];
    }

    /**
     * Full: Admin-like but still not admin (can do everything except user management)
     */
    public static function getFullPermissions(): array
    {
        return [
            self::CONTRATS_VIEW_LIST,
            self::CONTRATS_VIEW_DETAILS,
            self::CONTRATS_CREATE,
            self::CONTRATS_EDIT,
            self::CONTRATS_DELETE,
            self::CONTRATS_VIEW_DOCUMENTS_COLUMN,
            self::CONTRATS_VIEW_ACTIONS_COLUMN,
            self::CONTRATS_VIEW_BUTTON,
            self::DOCUMENTS_VIEW_LIST,
            self::DOCUMENTS_VIEW_DETAILS,
            self::DOCUMENTS_CREATE_UPLOAD,
            self::DOCUMENTS_EDIT,
            self::DOCUMENTS_DELETE,
            self::DOCUMENTS_DOWNLOAD,
        ];
    }

    /**
     * Get permission sets for admin selection
     */
    public static function getPermissionSets(): array
    {
        return [
            'read_only' => [
                'label' => 'Read-only',
                'description' => 'Can view lists and details only',
                'permissions' => self::getReadOnlyPermissions(),
            ],
            'contrat_view_only' => [
                'label' => 'Contrat-view-only',
                'description' => 'Can view contrats and documents',
                'permissions' => self::getClientViewOnlyPermissions(),
            ],
            'limited' => [
                'label' => 'Limited',
                'description' => 'Can view and upload documents',
                'permissions' => self::getLimitedPermissions(),
            ],
            'full' => [
                'label' => 'Full',
                'description' => 'Full access (except user management)',
                'permissions' => self::getFullPermissions(),
            ],
            'custom' => [
                'label' => 'Custom',
                'description' => 'Choose individual permissions',
                'permissions' => [],
            ],
        ];
    }

    /**
     * Get default permissions for a role
     * Note: Admin gets all permissions automatically in the voter
     * 
     * DEFAULT BEHAVIOR: Users have FULL ACCESS by default (add contrat, documents, etc.)
     * Admin can restrict permissions by explicitly setting them.
     */
    public static function getDefaultsForRole(string $role): array
    {
        return match ($role) {
            'ROLE_ADMIN' => self::getAll(),
            // Default: Users have full access (can add contrat, documents, etc.)
            // Admin can restrict this by explicitly setting permissions
            'ROLE_USER' => self::getFullPermissions(),
            default => [],
        };
    }

    /**
     * Determine the permission level label for a user
     */
    public static function getPermissionLevel(array $permissions): string
    {
        if (empty($permissions)) {
            return 'None';
        }

        $hasFullContrats = in_array(self::CONTRATS_CREATE, $permissions, true) 
            && in_array(self::CONTRATS_EDIT, $permissions, true) 
            && in_array(self::CONTRATS_DELETE, $permissions, true);
            
        $hasFullDocs = in_array(self::DOCUMENTS_CREATE_UPLOAD, $permissions, true) 
            && in_array(self::DOCUMENTS_EDIT, $permissions, true) 
            && in_array(self::DOCUMENTS_DELETE, $permissions, true);

        if ($hasFullContrats && $hasFullDocs) {
            return 'Full';
        }

        $hasViewOnly = in_array(self::CONTRATS_VIEW_LIST, $permissions, true) 
            && !in_array(self::CONTRATS_CREATE, $permissions, true)
            && !in_array(self::CONTRATS_EDIT, $permissions, true)
            && !in_array(self::CONTRATS_DELETE, $permissions, true);

        if ($hasViewOnly) {
            return 'Read-only';
        }

        return 'Limited';
    }
}
