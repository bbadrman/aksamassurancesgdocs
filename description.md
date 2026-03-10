# Documentation Technique - DocManager

## Architecture des Composants

Ce document décrit l'implémentation des Events, Services et Voters dans l'application DocManager.

---

## 1. Events & Event Subscribers

### RefreshUserSubscriber

**Fichier:** `src/EventSubscriber/RefreshUserSubscriber.php`

**Description:** Écoute les requêtes HTTP pour rafraîchir l'entité User depuis la base de données, garantissant que les rôles et permissions sont toujours à jour dans la session.

**Fonctionnement:**
- S'abonne à l'événement `kernel.request`
- Ignore les requêtes API pour la performance
- Détecte et journalise les changements de rôles/permissions
- Rafraîchit automatiquement les données utilisateur à chaque requête

**Méthodes clés:**
```php
public static function getSubscribedEvents(): array
public function onKernelRequest(RequestEvent $event): void
```

---

## 2. Services

### 2.1 ActivityLogger

**Fichier:** `src/Service/ActivityLogger.php`

**Description:** Service d'audit qui trace les activités utilisateurs (uploads, suppressions, téléchargements, modifications).

**Méthodes:**
| Méthode | Description |
|---------|-------------|
| `log()` | Méthode principale pour journaliser une activité |
| `logUpload()` | Log d'upload de document |
| `logDelete()` | Log de suppression (corbeille) |
| `logPermanentDelete()` | Log de suppression définitive |
| `logDownload()` | Log de téléchargement |
| `logRestore()` | Log de restauration depuis la corbeille |
| `logEdit()` | Log de modification de document |
| `logContratCreate()` | Log de création de contrat |
| `logContratEdit()` | Log de modification de contrat |
| `logContratDelete()` | Log de suppression de contrat |

---

### 2.2 UserSyncService

**Fichier:** `src/Service/UserSyncService.php`

**Description:** Synchronise les utilisateurs et contrats depuis une API externe vers la base de données locale.

**Méthodes:**
| Méthode | Description |
|---------|-------------|
| `syncAndGetUsers()` | Récupère les users de l'API externe et les synchronise |
| `syncUser()` | Synchronise un utilisateur individuel |
| `fetchAndCreateContrat()` | Récupère et crée un contrat depuis l'API externe |

---

### 2.3 ClientSyncService

**Fichier:** `src/Service/ClientSyncService.php`

**Description:** Synchronise les données clients depuis une API externe.

**Méthodes:**
| Méthode | Description |
|---------|-------------|
| `syncAndGetClients()` | Récupère et synchronise les clients |
| `syncClient()` | Synchronise un client individuel (nom, email, téléphone, adresse) |

---

### 2.4 ContratSyncService

**Fichier:** `src/Service/ContratSyncService.php`

**Description:** Synchronise les données de contrats depuis une API externe.

**Méthodes:**
| Méthode | Description |
|---------|-------------|
| `syncAndGetClients()` | Récupère et synchronise les contrats |
| `syncClient()` | Synchronise un contrat individuel (nom, prénom) |

---

### 2.5 PasswordResetService

**Fichier:** `src/Service/PasswordResetService.php`

**Description:** Service complet de réinitialisation de mot de passe avec génération de token sécurisé, expiration, limitation de débit et notifications par email.

**Configuration:**
```php
TOKEN_LENGTH = 32 bytes
TOKEN_EXPIRY_MINUTES = 30 minutes
MAX_REQUESTS_PER_HOUR = 3 requests
```

**Méthodes:**
| Méthode | Description |
|---------|-------------|
| `requestReset()` | Demande de réinitialisation (prévient l'énumération d'emails) |
| `resetPassword()` | Réinitialise le mot de passe avec validation du token |
| `validateToken()` | Valide un token sans l'utiliser |
| `generateSecureToken()` | Génère un token cryptographiquement sécurisé |
| `sendResetEmail()` | Envoie l'email avec template Twig |
| `getRecentRequestCount()` | Implémente la limitation de débit |

**Sécurité:**
- Prévention de l'énumération d'emails
- Tokens hashés en SHA256 en base de données
- Expiration automatique après 30 minutes
- Maximum 3 demandes par heure

---

## 3. Security Voters

### 3.1 ClientVoter

**Fichier:** `src/Security/ClientVoter.php`

**Description:** Voter de sécurité pour les permissions clients avec contrôle granulaire.

**Permissions supportées:**
| Permission | Description |
|------------|-------------|
| `clients.view_list` | Accès à la liste des clients |
| `clients.view_details` | Voir les détails d'un client |
| `clients.create` | Créer un nouveau client |
| `clients.edit` | Modifier un client |
| `clients.delete` | Supprimer un client |
| `clients.view_documents_column` | Voir la colonne Documents |
| `clients.view_actions_column` | Voir la colonne Actions |
| `clients.view_button` | Voir le bouton View |

**Aliases legacy:** `CLIENT_VIEW`, `CLIENT_CREATE`, `CLIENT_EDIT`, `CLIENT_DELETE`

---

### 3.2 ContratVoter

**Fichier:** `src/Security/ContratVoter.php`

**Description:** Voter de sécurité pour les permissions contrats.

**Permissions supportées:**
| Permission | Description |
|------------|-------------|
| `contrats.view_list` | Accès à la liste des contrats |
| `contrats.view_details` | Voir les détails d'un contrat |
| `contrats.create` | Créer un nouveau contrat |
| `contrats.edit` | Modifier un contrat |
| `contrats.delete` | Supprimer un contrat |
| `contrats.view_documents_column` | Voir la colonne Documents |
| `contrats.view_actions_column` | Voir la colonne Actions |
| `contrats.view_button` | Voir le bouton View |

**Aliases legacy:** `CONTRAT_VIEW`, `CONTRAT_CREATE`, `CONTRAT_EDIT`, `CONTRAT_DELETE`

---

### 3.3 DocumentVoter

**Fichier:** `src/Security/DocumentVoter.php`

**Description:** Voter de sécurité pour les permissions documents.

**Permissions supportées:**
| Permission | Description |
|------------|-------------|
| `documents.view_list` | Accès à la liste des documents |
| `documents.view_details` | Voir les détails d'un document |
| `documents.create_upload` | Upload/créer des documents |
| `documents.edit` | Modifier un document |
| `documents.delete` | Supprimer un document |
| `documents.download` | Télécharger un document |

**Aliases legacy:** `DOCUMENT_VIEW`, `DOCUMENT_MANAGE`, `DOCUMENT_DOWNLOAD`, `DOCUMENT_UPLOAD`, `DOCUMENT_EDIT`, `DOCUMENT_DELETE`

---

## 4. Composants de Sécurité Additionnels

### 4.1 LoginFormAuthenticator

**Fichier:** `src/Security/LoginFormAuthenticator.php`

**Description:** Authentificateur pour le formulaire de connexion.

**Méthodes:**
- `authenticate()` - Traite les credentials et retourne un Passport
- `onAuthenticationSuccess()` - Redirige vers le dashboard
- `getLoginUrl()` - Retourne l'URL de connexion

---

### 4.2 AccessDeniedHandler

**Fichier:** `src/Handler/AccessDeniedHandler.php`

**Description:** Gère les exceptions d'accès refusé (403).

**Fonctionnement:**
- Journalise les refus d'accès pour l'audit
- Vérifie les permissions utilisateur
- Invalide la session si les permissions sont perdues
- Redirige vers la page appropriée

---

## Résumé

| Type | Nombre |
|------|--------|
| Event Subscribers | 1 |
| Services | 5 |
| Voters | 3 |
| Composants Sécurité | 2 |

**Patterns d'architecture utilisés:**
- Architecture événementielle avec RefreshUserSubscriber
- Couche service pour la logique métier
- Système de permissions granulaire via Voters
- Synchronisation API externe avec cache local
- Logging d'audit complet
- Flux de réinitialisation de mot de passe sécurisé
