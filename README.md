# MyStock 📦

Gestion de stock multi-magasins (Laravel 12, modules, PostgreSQL) : référentiels, entrées,
sorties, transferts entre magasins, inventaires, lots et péremption, états et tableau de bord.
Le guide d'utilisation est dans [docs/GUIDE.md](docs/GUIDE.md).

## 🧩 Modules

| Module | Contenu |
| --- | --- |
| **Core** | Utilisateurs, rôles et permissions (Spatie), journal d'audit, commandes `cores:` |
| **Referentiel** | Magasins et rattachements, structures (directions, services, unités), catégories, articles, articles fréquents, **paramètres de l'application** |
| **Stock** | Entrées, sorties, transferts et réceptions, retours, annulations, inventaires, lots (FEFO), **bons imprimables**, fiche de comptage et PV d'inventaire |
| **Etats** | Stock par magasin / catégorie, journal, fiche de stock, transferts, péremptions ; analyses (consommation par service, couverture et rupture, articles dormants, écarts d'inventaire) ; **statistiques** et rapport d'activité ; exports Excel/PDF (aperçu en modale), tableau de bord |

## ⚙️ Paramètres (Administration > Paramètres)

| Paramètre | Effet | Défaut |
| --- | --- | --- |
| Nom de l'organisation | En-tête des états PDF | CHU-YO |
| Ancienneté maximale d'un mouvement | Nombre de jours de rétroactivité autorisés | 7 |
| Déroulement d'un transfert | `DEUX_ETAPES` (expédition puis réception), `UNE_ETAPE`, ou `UNE_ETAPE_SI_ACCES` (une étape si l'expéditeur peut aussi réceptionner au magasin destinataire) | Deux étapes |
| Quantité expédiée mais non reçue | Réintégrée au magasin source, ou comptée comme perte en transit | Réintégrée |
| Suivi des lots | Saisie n° de lot / péremption à l'entrée, sorties en FEFO | Désactivé |
| Alerte de péremption | Délai (jours) avant péremption pour l'alerte | 90 |

---

## 📋 Prérequis

Avant de commencer, assurez-vous d'avoir installé les éléments suivants :
* **PHP 8.3+** (extensions requises: `pdo_pgsql`, `pgsql`, `openssl`, `mbstring`, `xml`, `zip`)
* **PostgreSQL 15+**
* **Node.js 18+** & **npm**
* **Composer**

---

## ⚙️ Installation Rapide

Suivez ces étapes pour configurer le projet localement.

### 1. Cloner le Projet
Clonez ce dépôt et placez-vous dans son dossier racine :
```bash
git clone <url-du-depot>
cd MyStock
```

### 2. Configurer l'Environnement
Copiez le fichier de configuration exemple [.env.example](file:///home/ibrahim/projets/web/MyStock/.env.example) pour créer votre fichier `.env` :
```bash
cp .env.example .env
```
Ouvrez le fichier `.env` et ajustez les variables liées à votre base de données :
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=votre_base_de_donnees
DB_USERNAME=votre_utilisateur
DB_PASSWORD=votre_mot_de_passe
```

> [!NOTE]
> L'URL de l'application est configurée par défaut sur `https://mystock.local`. Si vous utilisez un nom de domaine ou un port local différent, mettez à jour la clé `APP_URL`.

---

### 3. Configurer et Initialiser la Base de Données 🗄️

Vous avez deux méthodes pour initialiser le schéma et les données initiales de la base de données.

#### Option A : Importer le script SQL (Recommandé pour un démarrage immédiat)
Un script de base de données complet et portable contenant la structure et les configurations est disponible dans [database/sql/init.sql](file:///home/ibrahim/projets/web/MyStock/database/sql/init.sql).

Créez votre base de données dans PostgreSQL, puis importez le fichier :
```bash
# Se connecter à PostgreSQL et créer la base de données
createdb -U votre_utilisateur -h 127.0.0.1 -p 5432 votre_base_de_donnees

# Importer le script SQL d'initialisation
psql -U votre_utilisateur -h 127.0.0.1 -p 5432 -d votre_base_de_donnees -f database/sql/init.sql
```

#### Option B : Exécuter les Migrations et Seeders de Laravel
Si vous préférez générer la structure directement via le code :
```bash
php artisan migrate --seed
```

---

### 4. Installer les Dépendances

#### Dépendances PHP
Installez les packages requis via Composer :
```bash
composer install
```

#### Dépendances Frontend & Compilation
Installez les packages Node.js et compilez les assets CSS/JS pour le développement ou la production :
```bash
# Installer les dépendances npm
npm install

# Compiler les assets pour la production
npm run build

# OU lancer le serveur de développement à chaud
npm run dev
```

---

### 5. Générer la Clé de Sécurité de l'Application
Générez la clé unique de chiffrement de Laravel :
```bash
php artisan key:generate
```

---

### 6. Synchroniser les Modules et Permissions
Initialisez le statut des modules et synchronisez leurs permissions respectives dans la base de données :
```bash
php artisan cores:sync
```

---

### 7. Configurer les Permissions d'Accès aux Fichiers
Assurez-vous que le serveur web a les permissions d'écriture sur les dossiers de cache et de stockage :
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

---

## 👤 Créer le Premier Utilisateur

Pour commencer le développement, vous devez créer un compte utilisateur de type administrateur. Le projet intègre des commandes interactives pour vous guider :

### Étape 1 : Créer l'utilisateur interactivement
Lancez la commande interactive [CreateUserCommand.php](file:///home/ibrahim/projets/web/MyStock/Modules/Core/app/Console/Commands/CreateUserCommand.php) pour saisir les informations de l'utilisateur et lui attribuer un rôle initial s'il existe :
```bash
php artisan cores:create-user
```

### Étape 2 : Promouvoir en Super Admin (Optionnel)
Pour attribuer toutes les permissions et l'accès global à tous les modules, attribuez le rôle de Super Admin via l'email de l'utilisateur :
```bash
php artisan cores:make-superadmin votre.email@exemple.com
```

---

## 🌐 Configuration du Virtual Host

Pour faire tourner le projet sous l'adresse locale `https://mystock.local`, configurez votre serveur web.

### 🌐 Ajouter la Résolution DNS Locale
Éditez votre fichier d'hôtes système (ex: `/etc/hosts` sous Linux/macOS ou `C:\Windows\System32\drivers\etc\hosts` sous Windows) et ajoutez :
```text
127.0.0.1    mystock.local
```

### Nginx (Recommandé)
Créez un fichier de configuration pour le site sous `/etc/nginx/sites-available/mystock.local` :
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name mystock.local;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name mystock.local;
    root /home/ibrahim/projets/web/MyStock/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    # Certificats SSL (remplacez par vos chemins vers vos certificats auto-signés locaux)
    ssl_certificate /etc/ssl/certs/ssl-cert-snakeoil.pem;
    ssl_certificate_key /etc/ssl/private/ssl-cert-snakeoil.key;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```
Activez la configuration et redémarrez Nginx :
```bash
sudo ln -s /etc/nginx/sites-available/mystock.local /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

### Apache
Générez un certificat auto-signé pour `mystock.local`, puis installez-le :
```bash
openssl req -x509 -nodes -newkey rsa:2048 -days 825 \
  -keyout mystock.local.key -out mystock.local.crt \
  -subj "/CN=mystock.local" -addext "subjectAltName=DNS:mystock.local"
sudo install -m 644 mystock.local.crt /etc/ssl/certs/mystock.local.crt
sudo install -m 600 mystock.local.key /etc/ssl/private/mystock.local.key
```

Créez l'hôte virtuel `/etc/apache2/sites-available/mystock.local.conf` (HTTP redirigé vers HTTPS) :
```xml
<VirtualHost *:80>
    ServerName mystock.local
    Redirect permanent / https://mystock.local/
</VirtualHost>

<VirtualHost *:443>
    ServerName mystock.local
    DocumentRoot /home/ibrahim/projets/web/MyStock/public

    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/mystock.local.crt
    SSLCertificateKeyFile /etc/ssl/private/mystock.local.key

    <Directory /home/ibrahim/projets/web/MyStock/public>
        Options FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/mystock_error.log
    CustomLog ${APACHE_LOG_DIR}/mystock_access.log combined
</VirtualHost>
```
Activez le site et les modules, puis rechargez Apache :
```bash
sudo a2ensite mystock.local.conf
sudo a2enmod ssl rewrite headers
sudo systemctl reload apache2
```

Apache tourne sous `www-data` : donnez-lui l'écriture sur `storage` et `bootstrap/cache` (ACL, sans changer de propriétaire) :
```bash
setfacl -R -m u:www-data:rwX -m d:u:www-data:rwX storage bootstrap/cache
```

---

## 🛠️ Commandes Utiles de la Console

Le module **Core** met à votre disposition plusieurs commandes artisan sous le namespace `cores:` :

| Commande | Rôle |
| --- | --- |
| `php artisan cores:sync` | Synchronise tous les modules du système de fichiers et leurs permissions associées. |
| `php artisan cores:create-user` | Crée interactivement (via Laravel Prompts) un nouvel utilisateur. |
| `php artisan cores:make-superadmin {email}` | Promut ou crée un utilisateur comme super-admin. |
| `php artisan cores:reset-user-password {user_name}` | Réinitialise le mot de passe d'un utilisateur par son nom d'utilisateur. |
| `php artisan cores:stats` | Affiche l'état d'activité, les versions et statistiques de chaque module. |
| `php artisan cores:user-permissions {user}` | Liste l'ensemble des rôles et permissions attribués à un utilisateur spécifique. |
| `php artisan cores:cleanup-permissions` | Nettoie les permissions orphelines de la base de données. |

---

## 🧪 Tests

```bash
php artisan test   # SQLite en mémoire, aucune base à préparer
```

