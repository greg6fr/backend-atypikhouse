# backend-atypikhouse

==============================
🚀 Déploiement local d’un projet Symfony avec JWT
==============================

Ce projet présente une procédure complète de déploiement d'une API Symfony en local avec le serveur web intégré et une authentification JWT.

---

1. Prérequis :

- PHP ≥ 8.1
- Composer
- Symfony CLI
- WampServer ou équivalent
- MySQL ≥ 8.0
- OpenSSL

---

2. Installation de WampServer :

- Téléchargez et installez WampServer
- Assurez-vous que PHP ≥ 8.1
- Vérifiez que MySQL fonctionne

---

3. Installation de Symfony CLI :
   Linux/macOS :
   curl -sS https://get.symfony.com/cli/installer | bash
   Windows :
   Téléchargez depuis https://symfony.com/download

---

4. Configuration du projet :
   Créer un fichier `.env.local` à la racine ou mettre à jour `.env` se trouvant à la racine :

DATABASE_URL="mysql://root:@127.0.0.1:3306/atypikhouse?serverVersion=8.0&charset=utf8mb4"
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=votre_passphrase_ici

---

5. Création de la base de données :
   php bin/console doctrine:database:create
   php bin/console doctrine:schema:create

---

6. Installation des dépendances :
   composer install

---

7. Génération des clés JWT :

Avec passphrase :
rm -rf config/jwt/\*.pem
mkdir -p config/jwt
openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096
openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout

Sans passphrase (développement uniquement) :
rm -rf config/jwt/\*.pem
mkdir -p config/jwt
openssl genrsa -out config/jwt/private.pem 4096
openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem

Mettre à jour `.env.local` ou `.env` :
JWT_PASSPHRASE=

---

8. Chargement des fixtures (optionnel) :
   php bin/console doctrine:fixtures:load

---

9. Vider le cache :
   php bin/console cache:clear

---

10. Lancer le serveur Symfony :
    symfony serve --port=8000 --no-tls

---

11. Accéder à l’API :
    https://127.0.0.1:8000/api (HTTPS)
    http://127.0.0.1:8000/api (HTTP)

---

Auteur : Déployé avec ❤️ par [GROUPE-1]
