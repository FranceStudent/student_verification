# Module de vérification des étudiants

Ce module est conçu pour le système WHMCS et permet de vérifier le statut d'étudiant des clients.

## Fonctionnalités

- Vérification automatique du statut d'étudiant lors de l'inscription d'un client
- Intégration avec une base de données d'établissements d'enseignement pour la validation des informations
- Génération de rapports sur les clients vérifiés et non vérifiés

## Installation

1. Téléchargez le zip directement sur le repository GitHub.
2. Décompressez le fichier téléchargé.
3. Copiez le dossier `student_verification` dans le répertoire `modules/addons` de votre installation WHMCS.
4. Connectez-vous à l'interface d'administration de WHMCS.
5. Accédez à **Configuration > Modules > Modules Addons**.
6. Activez le module de vérification des étudiants.
7. Configurez les paramètres du module selon vos besoins.

## Configuration

Pour configurer le module, suivez les étapes suivantes :

1. Accédez à **Configuration > Modules > Modules Addons**.
2. Cliquez sur le bouton de configuration du module de vérification des étudiants.
3. Entrez le chemin du dossier dans lequel les documents envoyés seront stockés
4. Ajoutez les deux variables dans les fichiers .tpl indiqués

## Utilisation

Une fois le module installé et configuré, il demandera aux utilisateurs d'envoyer un document pour confirmer leur statut d'étudiant. Une fois envoyé, un administrateur devra accepter ou refuser la demande via l'onglet de l'addon.

## Support

Si vous rencontrez des problèmes avec le module de vérification des étudiants, veuillez contacter notre équipe sur notre serveur discord : https://discord.gg/francestudent
