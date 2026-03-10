# RAPPORT D'ANALYSE COMPLET - DocManager

## Informations Generales

| Element | Valeur |
|---------|--------|
| **Nom du Projet** | DocManager - Systeme de Gestion de Documents |
| **Framework** | Symfony 7.4 |
| **Version PHP** | >= 8.2 |
| **Base de donnees** | MariaDB 11.8.0 |
| **Branche actuelle** | `last` |
| **Branche principale** | `main` |

---

## 1. Structure du Projet

```
/home/badr/Server/GESTION ARCH/doc/
|
├── src/                           # Code source
│   ├── Controller/                # Controleurs HTTP
│   │   ├── Admin/                 # Controleurs admin
│   │   ├── Api/                   # Controleurs API REST
│   │   └── ...                    # Controleurs application
│   ├── Entity/                    # Entites Doctrine ORM
│   ├── Repository/                # Couche d'acces aux donnees
│   ├── Form/                      # Types de formulaires
│   ├── Service/                   # Services metier
│   ├── Security/                  # Authentification & Voters
│   ├── Enum/                      # Enumerations PHP
│   ├── Handler/                   # Gestionnaires d'erreurs
│   ├── EventSubscriber/           # Abonnes aux evenements
│   └── Kernel.php
|
├── templates/                     # Templates Twig
├── config/                        # Configuration Symfony
├── migrations/                    # Migrations de base de donnees
├── tests/                         # Tests unitaires
├── public/                        # Racine web
├── var/                           # Cache, logs, uploads
└── assets/                        # Assets frontend
```

---

## 2. Entites et Modele de Donnees

### 2.1 User (Utilisateur)

**Fichier:** `src/Entity/User.php`

| Champ | Type | Description |
|-------|------|-------------|
| id | int (PK) | Identifiant unique |
| email | string (180) | Email unique, identifiant de connexion |
| username | string (180) | Nom d'utilisateur optionnel |
| roles | array JSON | Roles (ROLE_USER, ROLE_ADMIN) |
| permissions | array JSON | Permissions granulaires |
| password | string | Mot de passe hashe |
| externalId | int | ID systeme externe (synchronisation) |

**Relations:**
- OneToMany -> Contrat (un utilisateur peut avoir plusieurs contrats)

**Methodes cles:**
- `hasPermission(string): bool` - Verifier une permission
- `isAdmin(): bool` - Verifier si admin
- `getPermissionLevel(): string` - Niveau d'acces (Admin, Full, Limited, Read-only, None)

---

### 2.2 Contrat

**Fichier:** `src/Entity/Contrat.php`

| Champ | Type | Description |
|-------|------|-------------|
| id | int (PK) | Identifiant unique |
| nom | string (20) | Nom |
| prenom | string (20) | Prenom |
| raisonSociale | string (50) | Raison sociale |
| externalId | int | ID API externe |
| createdAt | DateTimeImmutable | Date de creation |

**Relations:**
- OneToMany -> Document (un contrat peut avoir plusieurs documents)
- ManyToOne -> User (un contrat appartient a un utilisateur)

---

### 2.3 Document

**Fichier:** `src/Entity/Document.php`

| Champ | Type | Description |
|-------|------|-------------|
| id | int (PK) | Identifiant unique |
| title | string (180) | Titre du document |
| description | text | Description optionnelle |
| fileName | string (255) | Nom fichier genere (Vich) |
| originalName | string (255) | Nom original du fichier |
| mimeType | string (120) | Type MIME |
| size | int | Taille en octets |
| createdAt | DateTimeImmutable | Date de creation |
| updatedAt | DateTimeImmutable | Date de modification |
| deletedAt | DateTimeImmutable | Date de suppression (soft delete) |

**Relations:**
- ManyToOne -> Contrat
- ManyToOne -> User (owner)

**Methodes cles:**
- `softDelete()` / `restore()` - Gestion corbeille
- `isPdf()` / `isImage()` - Detection type fichier

---

### 2.4 ActivityLog (Journal d'activite)

**Fichier:** `src/Entity/ActivityLog.php`

| Champ | Type | Description |
|-------|------|-------------|
| id | int (PK) | Identifiant unique |
| action | string (50) | Type d'action |
| details | text | Details lisibles |
| createdAt | DateTimeImmutable | Date |

**Actions supportees:**
- `upload` - Telechargement de document
- `delete` - Suppression (corbeille)
- `permanent_delete` - Suppression definitive
- `download` - Telechargement
- `edit` - Modification
- `restore` - Restauration
- `contrat_create` / `contrat_edit` / `contrat_delete`

---

### 2.5 PasswordReset

**Fichier:** `src/Entity/PasswordReset.php`

| Champ | Type | Description |
|-------|------|-------------|
| id | int (PK) | Identifiant unique |
| tokenHash | string (64) | Token hashe SHA-256 |
| expiresAt | DateTimeImmutable | Expiration (30 min) |
| usedAt | DateTimeImmutable | Date d'utilisation |
| createdAt | DateTimeImmutable | Date de creation |

---

## 3. Controleurs et Routes

### 3.1 Controleurs d'Authentification

#### SecurityController
| Route | Methode | Nom | Description |
|-------|---------|-----|-------------|
| `/login` | GET/POST | `app_login` | Connexion |
| `/logout` | GET | `app_logout` | Deconnexion |

#### RegistrationController
| Route | Methode | Nom | Description |
|-------|---------|-----|-------------|
| `/register` | GET/POST | `app_register` | Inscription |

#### ForgotPasswordController
| Route | Methode | Nom | Description |
|-------|---------|-----|-------------|
| `/forgot-password` | GET/POST | `app_forgot_password` | Mot de passe oublie |

#### ResetPasswordController
| Route | Methode | Nom | Description |
|-------|---------|-----|-------------|
| `/reset-password/{token}` | GET/POST | `app_reset_password` | Reinitialisation |

---

### 3.2 Controleurs Application

#### HomeController
| Route | Methode | Nom | Description |
|-------|---------|-----|-------------|
| `/` | GET | `app_home` | Redirige vers contrats |

#### DashboardController
| Route | Methode | Nom | Description |
|-------|---------|-----|-------------|
| `/dashboard` | GET | `app_dashboard` | Tableau de bord |

#### ContratController
| Route | Methode | Nom | Permission |
|-------|---------|-----|------------|
| `/contrats` | GET | `app_contrat_index` | ROLE_USER |
| `/contrats/{id}` | GET | `app_contrat_show` | contrats.view_details |

#### DocumentController
| Route | Methode | Nom | Permission |
|-------|---------|-----|------------|
| `/documents` | GET | `app_document_index` | documents.view_list |
| `/documents/trash` | GET | `app_document_trash` | documents.delete |
| `/documents/{id}/restore` | POST | `app_document_restore` | documents.delete |
| `/documents/{id}/permanent-delete` | POST | `app_document_permanent_delete` | documents.delete |
| `/documents/contrat/{id}` | GET | `app_document_list` | documents.view_list |
| `/documents/contrat/{id}/new` | GET/POST | `app_document_new` | documents.create_upload |
| `/documents/{id}/show` | GET | `app_document_show` | documents.view_details |
| `/documents/{id}/download` | GET | `app_document_download` | documents.download |
| `/documents/{id}/delete` | POST | `app_document_delete` | documents.delete |
| `/documents/{id}/edit` | GET/POST | `app_document_edit` | documents.edit |

#### ProfileController
| Route | Methode | Nom | Description |
|-------|---------|-----|-------------|
| `/profile` | GET | `app_profile` | Voir profil |
| `/profile/edit` | GET/POST | `app_profile_edit` | Modifier profil |
| `/profile/password` | GET/POST | `app_profile_change_password` | Changer mot de passe |

---

### 3.3 Controleurs Admin

#### UserAdminController
| Route | Methode | Nom | Permission |
|-------|---------|-----|------------|
| `/admin/users` | GET | `admin_users_index` | ROLE_ADMIN |
| `/admin/users/{id}/roles` | GET/POST | `admin_users_roles` | ROLE_ADMIN |
| `/admin/users/{id}/password` | GET/POST | `admin_users_password` | ROLE_ADMIN |

---

### 3.4 API REST

#### ContratApiController
| Route | Methode | Nom | Description |
|-------|---------|-----|-------------|
| `/Contratapi` | GET | `api_client_index` | Liste JSON-LD |
| `/Contratapi/{id}` | GET | `api_client_show` | Detail JSON-LD |
| `/Contratapi` | POST | `api_client_create` | Creer |
| `/Contratapi/{id}` | PUT/PATCH | `api_client_update` | Modifier |
| `/Contratapi/{id}` | DELETE | `api_client_delete` | Supprimer |

---

## 4. Formulaires

| Fichier | Classe | Entite | Description |
|---------|--------|--------|-------------|
| `DocumentType.php` | DocumentType | Document | Upload/edition de documents |
| `RegistrationFormType.php` | RegistrationFormType | User | Inscription utilisateur |
| `ProfileType.php` | ProfileType | User | Edition profil |
| `AdminUserRolesType.php` | AdminUserRolesType | User | Gestion roles/permissions |
| `AdminChangePasswordType.php` | AdminChangePasswordType | - | Changement mot de passe admin |

### Types de documents supportes (DocumentType)
- Atest Non Sinistre
- Atest Non Assure
- Carte Grise Definitive
- Devi Signe
- Kbis
- Permis
- Devoir Conseil
- Condition Generale
- Mondat Sepa
- Bulletin d'Adhesion

### Contraintes fichiers
- **Taille max:** 10 MB
- **Types autorises:** PDF, Word, Excel, Images (JPEG, PNG, GIF, WebP), Texte

---

## 5. Systeme de Securite

### 5.1 Configuration

**Fichier:** `config/packages/security.yaml`

```yaml
Hashage: bcrypt (auto)
Provider: Entity User (email)
Firewall: main
Remember Me: 7 jours
```

### 5.2 Hierarchie des Roles

```
ROLE_ADMIN
   └── ROLE_USER
```

### 5.3 Controle d'Acces

| Route | Acces |
|-------|-------|
| `/login`, `/register`, `/forgot-password`, `/reset-password`, `/api/*` | Public |
| `/profile`, `/dashboard`, `/documents`, `/contrats` | ROLE_USER |
| `/admin/*` | ROLE_ADMIN |

---

## 6. Systeme de Permissions

### 6.1 Enum Permission

**Fichier:** `src/Enum/Permission.php`

#### Permissions Clients
| Permission | Description |
|------------|-------------|
| `clients.view_list` | Voir la liste |
| `clients.view_details` | Voir les details |
| `clients.create` | Creer |
| `clients.edit` | Modifier |
| `clients.delete` | Supprimer |
| `clients.view_documents_column` | Colonne documents |
| `clients.view_actions_column` | Colonne actions |

#### Permissions Contrats
| Permission | Description |
|------------|-------------|
| `contrats.view_list` | Voir la liste |
| `contrats.view_details` | Voir les details |
| `contrats.create` | Creer |
| `contrats.edit` | Modifier |
| `contrats.delete` | Supprimer |
| `contrats.view_documents_column` | Colonne documents |
| `contrats.view_actions_column` | Colonne actions |
| `contrats.view_view_button` | Bouton voir |

#### Permissions Documents
| Permission | Description |
|------------|-------------|
| `documents.view_list` | Voir la liste |
| `documents.view_details` | Voir les details |
| `documents.create_upload` | Uploader |
| `documents.edit` | Modifier |
| `documents.delete` | Supprimer |
| `documents.download` | Telecharger |

### 6.2 Niveaux de Permission

| Niveau | Description |
|--------|-------------|
| **Admin** | Acces total au systeme |
| **Full** | Acces complet (sans gestion utilisateurs) |
| **Limited** | Voir, uploader et modifier documents |
| **Read-only** | Consultation uniquement |
| **None** | Aucune permission |

---

## 7. Security Voters

### 7.1 ClientVoter
**Fichier:** `src/Security/ClientVoter.php`

Gere les permissions pour les clients.

### 7.2 ContratVoter
**Fichier:** `src/Security/ContratVoter.php`

Gere les permissions pour les contrats.

### 7.3 DocumentVoter
**Fichier:** `src/Security/DocumentVoter.php`

Gere les permissions pour les documents.

**Logique commune:**
- Admin a toujours acces
- Verification via `User::hasPermission()`
- Support des aliases legacy

---

## 8. Services

### 8.1 ActivityLogger

**Fichier:** `src/Service/ActivityLogger.php`

**Description:** Service d'audit pour tracer toutes les activites.

| Methode | Description |
|---------|-------------|
| `log()` | Journaliser une activite |
| `logUpload()` | Log upload |
| `logDelete()` | Log suppression (corbeille) |
| `logPermanentDelete()` | Log suppression definitive |
| `logDownload()` | Log telechargement |
| `logRestore()` | Log restauration |
| `logEdit()` | Log modification |
| `logContratCreate()` | Log creation contrat |
| `logContratEdit()` | Log modification contrat |
| `logContratDelete()` | Log suppression contrat |

---

### 8.2 PasswordResetService

**Fichier:** `src/Service/PasswordResetService.php`

**Description:** Reinitialisation securisee des mots de passe.

**Configuration:**
```php
TOKEN_LENGTH = 32 bytes
TOKEN_EXPIRY_MINUTES = 30
MAX_REQUESTS_PER_HOUR = 3
```

| Methode | Description |
|---------|-------------|
| `requestReset(email)` | Demander reinitialisation |
| `resetPassword(token, password)` | Reinitialiser |
| `validateToken(token)` | Valider token |

**Securite:**
- Tokens hashe SHA-256
- Protection enumeration emails
- Limitation 3 demandes/heure
- Expiration 30 minutes

---

### 8.3 UserSyncService

**Fichier:** `src/Service/UserSyncService.php`

**Description:** Synchronisation utilisateurs depuis API externe.

| Methode | Description |
|---------|-------------|
| `syncAndGetUsers()` | Synchroniser et retourner utilisateurs |

---

### 8.4 ContratSyncService

**Fichier:** `src/Service/ContratSyncService.php`

**Description:** Synchronisation contrats depuis API externe.

| Methode | Description |
|---------|-------------|
| `syncAndGetClients()` | Synchroniser et retourner contrats |

---

### 8.5 ClientSyncService

**Fichier:** `src/Service/ClientSyncService.php`

**Description:** Synchronisation clients depuis API externe.

---

## 9. Event Subscribers

### RefreshUserSubscriber

**Fichier:** `src/EventSubscriber/RefreshUserSubscriber.php`

**Description:** Rafraichit les donnees utilisateur a chaque requete.

**Evenement:** `kernel.request`

**Fonctionnement:**
- Ignore les requetes API
- Detecte changements de roles/permissions
- Journalise les modifications

---

## 10. Repositories

### 10.1 DocumentRepository

**Fichier:** `src/Repository/DocumentRepository.php`

| Methode | Description |
|---------|-------------|
| `findByContrat(Contrat)` | Documents d'un contrat |
| `findDeleted()` | Documents supprimes |
| `findWithFilters(...)` | Recherche avancee avec pagination |
| `findRecent(limit)` | Documents recents |
| `findRecentlyDeleted(limit)` | Recemment supprimes |
| `countAll()` | Compter tous |
| `countDeleted()` | Compter supprimes |
| `getTotalSize()` | Taille totale |

### 10.2 PasswordResetRepository

| Methode | Description |
|---------|-------------|
| `findValidToken(hash)` | Trouver token valide |
| `invalidateAllForUser(userId)` | Invalider tous tokens |

---

## 11. Gestionnaire d'Erreurs

### AccessDeniedHandler

**Fichier:** `src/Handler/AccessDeniedHandler.php`

**Description:** Gere les erreurs 403 (acces refuse).

**Fonctionnement:**
- Journalise les refus d'acces
- Verifie les permissions utilisateur
- Deconnecte si permissions perdues
- Redirige vers page appropriee

---

## 12. Configuration Stockage Fichiers

### Vich Uploader

**Fichier:** `config/packages/vich_uploader.yaml`

```yaml
Stockage: Local (par defaut)
Prefix URI: /uploads/documents
Destination: %kernel.project_dir%/var/uploads/documents
Nommage: SmartUniqueNamer
```

### Cloudflare R2 (Configure, non active)

**Fichier:** `config/packages/flysystem.yaml`

```yaml
Stockage: AWS S3 compatible
Endpoint: Cloudflare R2
Bucket: Configure via .env
```

---

## 13. Dependances Principales

### Framework
- symfony/framework-bundle: 7.4.*
- symfony/security-bundle: 7.4.*
- symfony/form: 7.4.*
- symfony/twig-bundle: 7.4.*

### Base de donnees
- doctrine/orm: ^3.6
- doctrine/doctrine-bundle: ^2.18
- doctrine/doctrine-migrations-bundle: ^3.7

### API
- api-platform/symfony: ^4.2
- nelmio/cors-bundle: ^2.6

### Fichiers
- vich/uploader-bundle: ^2.9
- league/flysystem-aws-s3-v3: ^3.32
- league/flysystem-bundle: ^3.6

### Frontend
- symfony/stimulus-bundle: ^2.32
- symfony/ux-turbo: ^2.32
- symfony/asset-mapper: 7.4.*

### Email
- symfony/mailer: 7.4.*
- symfony/mime: 7.4.*

---

## 14. Templates

```
templates/
├── base.html.twig              # Template de base
├── security/
│   └── login.html.twig         # Connexion
├── registration/
│   └── register.html.twig      # Inscription
├── dashboard/
│   └── index.html.twig         # Tableau de bord
├── contrat/
│   ├── index.html.twig         # Liste contrats
│   └── show.html.twig          # Detail contrat
├── document/
│   ├── index.html.twig         # Liste documents
│   ├── list.html.twig          # Documents par contrat
│   ├── new.html.twig           # Upload
│   ├── show.html.twig          # Detail
│   ├── edit.html.twig          # Edition
│   └── trash.html.twig         # Corbeille
├── admin/
│   └── users/
│       ├── index.html.twig     # Liste utilisateurs
│       ├── roles.html.twig     # Gestion roles
│       └── password.html.twig  # Changer mot de passe
├── profile/
│   └── index.html.twig         # Profil utilisateur
├── forgot_password/
│   └── index.html.twig         # Mot de passe oublie
├── reset_password/
│   └── index.html.twig         # Reinitialisation
└── emails/
    └── reset_password.html.twig # Email reinitialisation
```

---

## 15. Tests

**Repertoire:** `tests/`

| Fichier | Description |
|---------|-------------|
| `PasswordResetServiceTest.php` | Tests service reinitialisation |
| `PasswordValidationTest.php` | Tests validation mot de passe |
| `bootstrap.php` | Bootstrap PHPUnit |

**Framework:** PHPUnit ^12.5

---

## 16. Environnement

### Variables d'environnement (.env)

```env
APP_ENV=dev
APP_SECRET=<secret>
DEFAULT_URI=http://docmanager.ddev.site
DATABASE_URL=mysql://db:db@db:3306/db
MAILER_DSN=smtp://localhost:1025
MAILER_FROM=noreply@docmanager.ddev.site
MESSENGER_TRANSPORT_DSN=doctrine://default
CORS_ALLOW_ORIGIN=^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$
```

### DDEV Configuration

```
Projet: docmanager
Domaine: docmanager.ddev.site
Services: web, db, mailpit
```

---

## 17. Resume des Fonctionnalites

### Authentification & Autorisation
- Connexion par email
- Inscription avec validation mot de passe
- Reinitialisation mot de passe securisee
- Roles (ROLE_USER, ROLE_ADMIN)
- Permissions granulaires
- Voters de securite

### Gestion Documents
- Upload avec Vich Uploader
- Support PDF, Word, Excel, Images, Texte
- Limite 10 MB
- Soft delete avec corbeille
- Apercu documents
- Telechargement/visualisation
- Journalisation des actions

### Gestion Contrats
- CRUD complet
- Association documents
- Synchronisation API externe

### Administration
- Gestion utilisateurs
- Attribution roles/permissions
- Changement mot de passe

### Filtrage Avance
- Recherche par titre, nom, description
- Filtre par contrat
- Filtre par type de fichier
- Filtre par date
- Pagination

### Securite
- Protection CSRF
- Hashage bcrypt
- Tokens securises (random_bytes)
- Limitation de taux
- Protection enumeration emails
- Soft delete
- Protection admin

---

## 18. Historique Git Recent

| Commit | Message |
|--------|---------|
| `1160e64` | make view of user |
| `812abe5` | include usr id |
| `2c7100e` | replace client with contrat |
| `54c94bf` | include solution pour stocker les documents |
| `cdabc2a` | branch with aksam |

---

## 19. Conclusion

**DocManager** est une application complete de gestion de documents construite avec les meilleures pratiques Symfony:

- Architecture MVC bien structuree
- Systeme de permissions granulaire
- Securite robuste
- API REST
- Audit trail complet
- Synchronisation API externe
- Interface admin complete
- Tests unitaires

Le projet est pret pour la production avec une configuration de stockage cloud (Cloudflare R2) preparee pour l'hebergement sur Namecheap.
