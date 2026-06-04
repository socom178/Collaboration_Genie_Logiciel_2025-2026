<?php
require_once __DIR__ . '/../metier/Etudiant.php';

class EtudiantDAO {
    private PDO $pdo;

   

    public function __construct($db) {
        $this->pdo = $db;
    }
    // Trouver un étudiant par matricule (connexion)
    public function findByMatricule(string $matricule): ?Etudiant {
        $stmt = $this->pdo->prepare("
            SELECT p.*, e.id as etudiant_id, e.matricule, e.type
            FROM personne p
            INNER JOIN etudiant e ON e.personne_id = p.id
            WHERE e.matricule = :matricule
        ");
        $stmt->execute([':matricule' => $matricule]);
        $row = $stmt->fetch();
        if (!$row) return null;
        return $this->hydrater($row);
    }

    // Trouver par ID personne
    public function findByPersonneId(int $personne_id): ?Etudiant {
        $stmt = $this->pdo->prepare("
            SELECT p.*, e.id as etudiant_id, e.matricule, e.type
            FROM personne p
            INNER JOIN etudiant e ON e.personne_id = p.id
            WHERE p.id = :personne_id
        ");
        $stmt->execute([':personne_id' => $personne_id]);
        $row = $stmt->fetch();
        if (!$row) return null;
        return $this->hydrater($row);
    }

    // Lister tous les étudiants
    public function findAll(): array {
        $stmt = $this->pdo->query("
            SELECT p.*, e.id as etudiant_id, e.matricule, e.type
            FROM personne p
            INNER JOIN etudiant e ON e.personne_id = p.id
            ORDER BY p.nom, p.prenom
        ");
        $etudiants = [];
        while ($row = $stmt->fetch()) {
            $etudiants[] = $this->hydrater($row);
        }
        return $etudiants;
    }

    // Lister les diplômés
    public function findDiplomes(): array {
        $stmt = $this->pdo->prepare("
            SELECT p.*, e.id as etudiant_id, e.matricule, e.type
            FROM liste_etudiants_csv p
            INNER JOIN etudiant e ON e.matricule = p.matricule
            WHERE p.statut = 'diplome'
            ORDER BY p.nom, p.prenom
        ");
        $stmt->execute();
        $etudiants = [];
        while ($row = $stmt->fetch()) {
            $etudiants[] = $this->hydrater($row);
        }
        return $etudiants;
    }

    public function reinitialiserMotsDePasse(): array{
        $resultats = [];
        
        $sql = "
            SELECT
            p.id,
            p.nom,
            p.prenom,
            p.email,
            p.role,
            COALESCE(e.matricule, '') AS matricule
        FROM personne p
        LEFT JOIN etudiant e ON e.personne_id = p.id
        ";

        $stmt = $this->pdo->query($sql);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

            $nouveauPassword = substr(
                str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'),
                0,
                8
            );
            
            $stmtUpdate = $this->pdo->prepare("
                UPDATE personne
                SET password = :password
                WHERE id = :id
            ");

            $stmtUpdate->execute([
                ':password' => password_hash($nouveauPassword, PASSWORD_BCRYPT),
                ':id'       => $row['id']
            ]);
            $resultats[] = [
                'matricule' => $row['matricule'],
                'nom'       => $row['nom'],
                'prenom'    => $row['prenom'],
                'email'     => $row['email'],
                'password'  => $nouveauPassword
            ];
        }

        return $resultats;
    }
    // Créer un étudiant (personne + etudiant)
    public function create(Etudiant $e): bool {
        $this->pdo->beginTransaction();
        try {
            // Insérer dans personne
            $stmt = $this->pdo->prepare("
                INSERT INTO personne (nom, prenom, date_nais, email, tel, sexe, password, role)
                VALUES (:nom, :prenom, :date_nais, :email, :tel, :sexe, :password, 'etudiant')
            ");
            $stmt->execute([
                ':nom'       => $e->getNom(),
                ':prenom'    => $e->getPrenom(),
                ':date_nais' => $e->getDateNais(),
                ':email'     => $e->getEmail(),
                ':tel'       => $e->getTel(),
                ':sexe'      => $e->getSexe(),
                ':password'  => password_hash($e->getPassword(), PASSWORD_BCRYPT),
            ]);
            $personne_id = (int)$this->pdo->lastInsertId();

            // Insérer dans etudiant
            $stmt2 = $this->pdo->prepare("
                INSERT INTO etudiant (personne_id, matricule, type)
                VALUES (:personne_id, :matricule, :type)
            ");
            $stmt2->execute([
                ':personne_id' => $personne_id,
                ':matricule'   => $e->getMatricule(),
                ':type'        => $e->getType(),
            ]);

            $this->pdo->commit();
            return true;
        } catch (Exception $ex) {
            $this->pdo->rollBack();
            echo $ex->getMessage();
            return false;
        }
    }

    // Upgrade vers diplômé
    public function upgraderStatut(string $matricule): bool {
        // Vérifier dans liste_etudiants_csv
        $stmt = $this->pdo->prepare("
            SELECT * FROM liste_etudiants_csv
            WHERE matricule = :matricule AND statut = 'diplome'
        ");
        $stmt->execute([':matricule' => $matricule]);
        $trouve = $stmt->fetch();
        if (!$trouve) return false;

        // Mettre à jour le statut
        $stmt2 = $this->pdo->prepare("
            UPDATE etudiant SET type = 'diplome' WHERE matricule = :matricule
        ");
        $stmt2->execute([':matricule' => $matricule]);
        return true;
    }

    // Supprimer un étudiant
    public function delete(int $personne_id): bool {
        // Récupérer le matricule avant suppression
        $stmt = $this->pdo->prepare("
            SELECT e.matricule FROM etudiant e WHERE e.personne_id = :personne_id
        ");
        $stmt->execute([':personne_id' => $personne_id]);
        $row = $stmt->fetch();

        // Supprimer dans personne (cascade supprime etudiant automatiquement)
        $stmt2 = $this->pdo->prepare("DELETE FROM personne WHERE id = :id");
        $ok = $stmt2->execute([':id' => $personne_id]);

        // Supprimer aussi dans liste_etudiants_csv
        if ($ok && $row) {
            $stmt3 = $this->pdo->prepare("DELETE FROM liste_etudiants_csv WHERE matricule = :matricule");
            $stmt3->execute([':matricule' => $row['matricule']]);
        }

        return $ok;
    }
    // Compter tous les étudiants
    public function compter(): int {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM etudiant")->fetchColumn();
    }

    // Compter les diplômés
    public function compterDiplomes(): int {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM liste_etudiants_csv WHERE statut ='diplome'")->fetchColumn();
    }

    // Import CSV — générer les comptes étudiants
    public function importerCSV(string $fichier): array {
    $resultats = ['succes' => 0, 'erreurs' => 0, 'messages' => [], 'comptes' => []]; 

    if (!file_exists($fichier)) {
        $resultats['messages'][] = "Fichier introuvable.";
        return $resultats;
    }

    $handle = fopen($fichier, 'r');
    if (!$handle) {
        $resultats['messages'][] = "Impossible d'ouvrir le fichier.";
        return $resultats;
    }
    fgetcsv($handle, 0, ';');

    while (($ligne = fgetcsv($handle, 0, ';')) !== false) {

        if (count($ligne) < 5) {
            $resultats['erreurs']++;
            $resultats['messages'][] = "✘ Ligne invalide ignorée.";
            continue;
        }

        [$matricule, $nom, $prenom, $filiere, $annee, $email] = array_map('trim', $ligne);

        if (!$matricule || !$nom || !$prenom || !$email) {
            $resultats['erreurs']++;
            $resultats['messages'][] = "✘ Données incomplètes pour $matricule.";
            continue;
        }

        // Insérer dans liste_etudiants_csv
        $stmt = $this->pdo->prepare("
            INSERT IGNORE INTO liste_etudiants_csv (matricule, nom, prenom, filiere, annee)
            VALUES (:matricule, :nom, :prenom, :filiere, :annee)
        ");
        $stmt->execute([
            ':matricule' => $matricule,
            ':nom'       => $nom,
            ':prenom'    => $prenom,
            ':filiere'   => $filiere,
            ':annee'     => $annee
        ]);

        // Générer mot de passe
        $passwordPlain = bin2hex(random_bytes(4));
        $passwordHash  = password_hash($passwordPlain, PASSWORD_BCRYPT);

        // Créer le compte étudiant
        $etudiant = new Etudiant(
            0, $nom, $prenom, '2000-01-01',
            $email, '', '', $passwordPlain,
            '', 0, $matricule, 'simple'
        );

        $ok = $this->create($etudiant);

        if ($ok) {
            $resultats['succes']++;
            $resultats['comptes'][] = [ // ← ajouter ça
                'matricule' => $matricule,
                'nom'       => $nom,
                'prenom'    => $prenom,
                'email'     => $email,
                'password'  => $passwordPlain
            ];
        
            // Envoyer le mail
            require_once __DIR__ . '/../service/MailerService.php';
            MailerService::sendMail(
                $email,
                "Vos identifiants de connexion — UATM Mémoires",
                "
                    <h2>Bonjour $prenom $nom 👋</h2>
                    <p>Un compte a été créé pour vous sur la plateforme de gestion des mémoires soutenus de l'UATM.</p>
                    <p>Voici vos identifiants de connexion :</p>
                    <p><b>Email :</b> $email</p>
                    <p><b>Matricule :</b> $matricule</p>
                    <p><b>Mot de passe :</b> $passwordPlain</p>
                    <p>Veuillez changer votre mot de passe après votre première connexion.</p>
                "
            );

            $resultats['succes']++;
            $resultats['messages'][] = "✔ $matricule — $prenom $nom — Mail envoyé à $email";
        } else {
            $resultats['erreurs']++;
            $resultats['messages'][] = "✘ $matricule — déjà existant ou erreur.";
        }
    }

    fclose($handle);
    return $resultats;
}
    // Hydrater
    private function hydrater(array $row): Etudiant {
        return new Etudiant(
            $row['id'] ?? 0,          
            $row['nom'] ?? '',
            $row['prenom'] ?? '',
            $row['date_nais'] ?? '',
            $row['email'] ?? '',
            $row['tel'] ?? '',
            $row['sexe'] ?? '',
            $row['password'] ?? '',
            $row['created_at'] ?? '',
            $row['etudiant_id'] ?? 0,  
            $row['matricule'] ?? '',
            $row['type'] ?? 'simple'
            );
    }
}
