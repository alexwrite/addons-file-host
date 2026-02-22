# 📁 Host Drive — Addon pour ClientXCMS NextGen

> Hébergez, gérez et partagez vos fichiers directement depuis votre panel ClientXCMS V2.  
> Images, PDF, vidéos — tout est centralisé, sécurisé et accessible via un lien direct personnalisable.

---

## ✨ Fonctionnalités

- **Upload simplifié** — Glissez-déposez vos fichiers ou sélectionnez-les manuellement (jusqu'à 50 Mo).
- **Liens directs personnalisables** — Configurez un préfixe d'URL sur mesure (ex : `/drive/mon-image.png`).
- **Aperçu intégré** — Prévisualisez images, PDF et vidéos en plein écran depuis l'interface d'administration.
- **Statistiques de consultation** — Suivez le nombre de vues par fichier.
- **Hébergement privé** — Vos fichiers restent sur votre serveur, sous votre contrôle.
- **Disponibilité garantie** — Les fichiers restent accessibles même en mode maintenance via Laravel.
- **Design natif** — Interface moderne, parfaitement intégrée au style ClientXCMS.

---

## 👤 Auteur

| Info | Détails |
|---|---|
| **Auteur** | Corentin WebSite |
| **Site** | [corentin.site](https://corentin.site) |
| **Contact** | hello@corentin.site |
| **Version** | 1.1.0 |
| **Licence** | Propriétaire — utilisation autorisée, distribution autorisée via ClientXCMS Marketplace |
| **Année** | 2026 |

---

## 🔐 Sécurité

Host Drive applique plusieurs couches de protection pour garantir l'intégrité de votre hébergement :

- **Filtrage des extensions dangereuses** — Les fichiers `.php`, `.sh`, `.exe` et similaires sont bloqués à l'upload.
- **Vérification MIME côté serveur** — Le type réel du fichier est contrôlé indépendamment du navigateur.
- **Protection contre le path traversal** — Toute tentative d'accès via `../` est rejetée.
- **Prévention XSS** — Les fichiers HTML et SVG sont forcés en téléchargement.
- **Headers de sécurité** — Chaque réponse inclut `X-Content-Type-Options`, `X-Frame-Options`, etc.

---

## 📦 Installation

### ✅ Méthode automatique (recommandée)

1. Connectez-vous à votre panel d'administration **ClientXCMS**
2. Rendez-vous dans **Extensions**
3. Cliquez sur **Personnalisation**
4. Recherchez **"file-host"** dans la liste des extensions disponibles
5. Cliquez sur **Installer**
6. ✅ L'addon est actif — le menu **Corentin WebSite Addons → Hébergement de Fichiers** apparaît dans votre admin


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
       └── database/
   ```
3. **Videz le cache** de l'application :
   ```bash
   php artisan optimize:clear
   ```
4. ✅ L'addon est actif.

---

## ⚙️ Configuration

Une fois installé, accédez à **Corentin WebSite Addons → Hébergement de Fichiers** dans votre panel admin.

| Paramètre | Description | Défaut |
|---|---|---|
| **Préfixe de l'URL** | La première partie des liens de téléchargement | `drive` |

Exemple avec le préfixe `drive` : `https://votre-site.com/drive/mon-image.png`

> ⚠️ Après avoir changé le préfixe, **actualisez la page** pour que les nouveaux liens soient actifs.

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