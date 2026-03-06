<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\AdminUserRolesType;
use App\Form\AdminChangePasswordType;
use App\Enum\Permission;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/admin/users')]
class UserAdminController extends AbstractController
{
    #[Route('', name: 'admin_users_index')]
    public function index(EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $users = $em->getRepository(User::class)->findAll();

        return $this->render('admin/users/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/{id}/roles', name: 'admin_users_roles', requirements: ['id' => '\d+'])]
    public function editRoles(
        User $user,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // SECURITY: Block editing admin users
        if ($user->isAdmin()) {
            $this->addFlash('error', '❌ Cannot edit admin users. Admin accounts have full access by default.');
            return $this->redirectToRoute('admin_users_index');
        }

        // SECURITY: Block editing self
        $currentUser = $this->getUser();
        if ($currentUser instanceof User && $currentUser->getId() === $user->getId()) {
            $this->addFlash('error', '❌ You cannot edit your own roles.');
            return $this->redirectToRoute('admin_users_index');
        }

        $form = $this->createForm(AdminUserRolesType::class, $user);
        
        // Pre-populate unmapped permission fields with user's current permissions
        $userPermissions = $user->getPermissions();
        foreach (Permission::getGroups() as $groupName => $permissions) {
            $fieldName = 'permissions_' . str_replace(' ', '_', $groupName);
            // Get permissions that belong to this group
            $groupPermissions = array_keys($permissions);
            $selectedPermissions = array_values(array_intersect($userPermissions, $groupPermissions));
            $form->get($fieldName)->setData($selectedPermissions);
        }
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Guard: Don't let user be without ROLE_USER
            if (!in_array('ROLE_USER', $user->getRoles(), true)) {
                $user->setRoles(array_merge($user->getRoles(), ['ROLE_USER']));
            }

            // Handle permissions from unmapped form fields - use form object for cleaner access
            $allPermissions = [];
            
            foreach (Permission::getGroups() as $groupName => $permissions) {
                $fieldName = 'permissions_' . str_replace(' ', '_', $groupName);
                $fieldData = $form->get($fieldName)->getData();
                if (!empty($fieldData) && is_array($fieldData)) {
                    $allPermissions = array_merge($allPermissions, $fieldData);
                }
            }
            
            $user->setPermissions($allPermissions);

            $em->flush();

            $this->addFlash('success', '✅ Roles and permissions updated successfully.');
            return $this->redirectToRoute('admin_users_index');
        }

        return $this->render('admin/users/roles.html.twig', [
            'u' => $user,
            'form' => $form->createView(),
            'permissionGroups' => Permission::getGroups(),
            'permission_sets' => Permission::getPermissionSets(),
        ]);
    }

    #[Route('/{id}/password', name: 'admin_users_password', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function changePassword(
        User $user,
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // SECURITY: Block editing self
        $currentUser = $this->getUser();
        if ($currentUser instanceof User && $currentUser->getId() === $user->getId()) {
            $this->addFlash('error', 'Vous ne pouvez pas modifier votre propre mot de passe ici. Utilisez votre profil.');
            return $this->redirectToRoute('admin_users_index');
        }

        $form = $this->createForm(AdminChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = $form->get('newPassword')->getData();

            $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);

            $em->flush();

            $this->addFlash('success', 'Mot de passe modifié avec succès pour ' . $user->getEmail());
            return $this->redirectToRoute('admin_users_index');
        }

        return $this->render('admin/users/password.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }
}
