# 📁 Host Drive — Addon ClientXCMS NextGen

> Module d'hébergement de fichiers pour **ClientXCMS V2** — hébergez des images, PDF, vidéos et tout autre fichier directement depuis votre panel d'administration, et partagez-les via un lien direct personnalisable.

---

## ✨ Fonctionnalités

- 📤 **Upload de fichiers** via glisser-déposer ou sélection (jusqu'à 50 Mo)
- 🔗 **Liens directs** personnalisables avec un préfixe d'URL configurable (ex: `/drive/mon-image.png`)
- 👁️ **Aperçu plein écran** pour les images, PDF et vidéos directement depuis l'admin
- 📊 **Compteur de vues** par fichier
- 🔒 **Sécurisé** — les fichiers sont hébergés en privé sur votre serveur
- 🛠️ **Résistant à la maintenance** — les fichiers restent accessibles même si votre site est en mode maintenance
- 🎨 **Interface moderne** intégrée au style de ClientXCMS

---

## 👤 Auteur

| Champ | Info |
|---|---|
| **Créateur** | Corentin WebSite |
| **Site** | [corentin.site](https://corentin.site) |
| **Contact** | hello@corentin.site |
| **Version** | 1.0.0 |
| **Licence** | Propriétaire — utilisation autorisée, distribution autorisée via ClientXCMS Marketplace |
| **Année** | 2026 |

---

## 📦 Installation

### ✅ Méthode automatique (recommandée)

1. Connectez-vous à votre panel d'administration **ClientXCMS**
2. Rendez-vous dans **Extensions → Marketplace**
3. Recherchez **"Host Drive"**
4. Cliquez sur **Installer**
5. ✅ L'addon est actif — le menu **Corentin WebSite Addons → Hébergement de Fichiers** apparaît dans votre admin

---

### 🔧 Méthode manuelle

1. **Téléchargez** l'archive de l'addon (dossier `file-host/`)
2. **Copiez** le dossier dans le répertoire des addons de votre installation ClientXCMS :
   ```
   /addons/file-host/
   ```
   La structure doit ressembler à ceci :
   ```
   /addons/
   └── file-host/
       ├── addon.json
       ├── composer.json
       ├── src/
       ├── views/
       ├── routes/
       ├── database/
       └── public/
   ```
3. **Videz le cache** de l'application :
   ```bash
   php artisan optimize:clear
   ```
4. ✅ L'addon se configure automatiquement au premier chargement (création des tables, installation des règles).

> **Note :** Aucune commande `migrate` manuelle n'est nécessaire — l'addon gère sa propre installation de base de données au démarrage.

---

## ⚙️ Configuration

Une fois installé, accédez à **Corentin WebSite Addons → Hébergement de Fichiers** dans votre panel admin.

| Paramètre | Description | Défaut |
|---|---|---|
| **Préfixe de l'URL** | La première partie des liens de téléchargement | `drive` |

Exemple avec le préfixe `drive` : `https://votre-site.com/drive/mon-image.png`

> ⚠️ Après avoir changé le préfixe, **actualisez la page** pour que les nouveaux liens soient actifs. Les anciens liens ne fonctionneront plus.

---

## 🔐 Sécurité

- Les extensions dangereuses (`.php`, `.sh`, `.exe`...) sont **bloquées à l'upload**
- Le type MIME réel est vérifié côté serveur (non falsifiable par le navigateur)
- Les tentatives de **path traversal** (`../`) sont bloquées
- Les fichiers HTML et SVG sont **forcés en téléchargement** pour prévenir les attaques XSS
- Les headers de sécurité sont ajoutés à chaque réponse (`X-Content-Type-Options`, `X-Frame-Options`...)

---

## 🧩 Compatibilité avec d'autres addons Corentin WebSite

Tous les addons Corentin WebSite (Host Drive, Plesk Automations, etc.) se regroupent automatiquement sous la même carte **"Corentin WebSite Addons"** dans le menu admin. Ils sont **indépendants** : chacun fonctionne seul, et ils se combinent proprement s'ils sont installés ensemble.

---

## 📄 Licence

Cet addon est distribué sous **licence propriétaire** avec les conditions suivantes :

| Action | Autorisé |
|---|---|
| ✅ Utiliser l'addon sur votre site ClientXCMS | **Oui** |
| ✅ Obtenir l'addon via le ClientXCMS Marketplace | **Oui** |
| ❌ Redistribuer l'addon gratuitement | **Non** |
| ❌ Revendre ou sous-licencier en dehors du ClientXCMS Marketplace | **Non** |
| ❌ Modifier et redistribuer le code source | **Non** |
| ❌ Décompiler ou faire de l'ingénierie inverse | **Non** |

> Toute distribution ou revente en dehors du **ClientXCMS Marketplace officiel** est strictement interdite et constitue une violation des droits d'auteur.

© 2026 Corentin WebSite — Tous droits réservés.
