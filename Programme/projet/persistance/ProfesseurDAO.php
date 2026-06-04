<?php
require_once __DIR__ . '/../metier/Professeur.php';

class ProfesseurDAO {
    private PDO $pdo;

    public function __construct($db) {
        $this->pdo = $db;
    }

    public function findAll(): array {
        $stmt = $this->pdo->query("
            SELECT p.*, pr.id as professeur_id, pr.grade, pr.date_embauche
            FROM personne p
            INNER JOIN professeur pr ON pr.personne_id = p.id
            ORDER BY p.nom, p.prenom
        ");
        $profs = [];
        while ($row = $stmt->fetch()) {
            $profs[] = $this->hydrater($row);
        }
        return $profs;
    }
    public function findAl(): array {
    $stmt = $this->pdo->query("
        SELECT p.id, p.nom, p.prenom
        FROM personne p
        INNER JOIN professeur pr ON pr.personne_id = p.id
        ORDER BY p.nom, p.prenom
    ");

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
    public function findById(int $professeur_id): ?Professeur {
        $stmt = $this->pdo->prepare("
            SELECT p.*, pr.id as professeur_id, pr.grade, pr.date_embauche
            FROM personne p
            INNER JOIN professeur pr ON pr.personne_id = p.id
            WHERE pr.id = :id
        ");
        $stmt->execute([':id' => $professeur_id]);
        $row = $stmt->fetch();
        if (!$row) return null;
        return $this->hydrater($row);
    }

    public function findByEmail(string $email): ?Professeur {
        $stmt = $this->pdo->prepare("
            SELECT p.*, pr.id as professeur_id, pr.grade, pr.date_embauche
            FROM personne p
            INNER JOIN professeur pr ON pr.personne_id = p.id
            WHERE p.email = :email
        ");
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch();
        if (!$row) return null;
        return $this->hydrater($row);
    }

    public function create(Professeur $prof): bool {

        $this->pdo->beginTransaction();

        try {

            // 🔐 données utilisables
            $nom = $prof->getNom();
            $prenom = $prof->getPrenom();
            $email = $prof->getEmail();
            $tel = $prof->getTel();
            $sexe = $prof->getSexe();
            $grade = $prof->getGrade();
            $date_embauche = $prof->getDateEmbauche();

            // 🔐 mot de passe temporaire
            $passwordPlain = bin2hex(random_bytes(4));
            $passwordHash = password_hash($passwordPlain, PASSWORD_BCRYPT);

            // INSERT personne
            $stmt = $this->pdo->prepare("
                INSERT INTO personne (nom, prenom, date_nais, email, tel, sexe, password, role)
                VALUES (:nom, :prenom, :date_nais, :email, :tel, :sexe, :password, 'professeur')
            ");

            $stmt->execute([
                ':nom' => $nom,
                ':prenom' => $prenom,
                ':date_nais' => $prof->getDateNais(),
                ':email' => $email,
                ':tel' => $tel,
                ':sexe' => $sexe,
                ':password' => $passwordHash,
            ]);

            $personne_id = (int)$this->pdo->lastInsertId();

            // INSERT professeur
            $stmt2 = $this->pdo->prepare("
                INSERT INTO professeur (personne_id, grade, date_embauche)
                VALUES (:personne_id, :grade, :date_embauche)
            ");

            $stmt2->execute([
                ':personne_id' => $personne_id,
                ':grade' => $grade,
                ':date_embauche' => $date_embauche,
            ]);

            $this->pdo->commit();

            // ✉️ EMAIL (MAINTENANT OK)
            require_once __DIR__ . '/../service/MailerService.php';

            MailerService::sendMail(
                $email,
                "Compte enseignant créé",
                "
                    <h2>Bonjour M/Mme $nom $prenom 👋</h2>
                    <p>Un compte enseignant a été créé pour vous sur l'application de gestion des memoires soutenus a l'UATM GASA Formation. Voici vos identifiants :</p>

                    <p><b>Email :</b> $email</p>
                    <p><b>Mot de passe temporaire :</b> $passwordPlain</p>

                    <a>visiter le site</a>
                "
            );

            return true;

        } catch (Exception $ex) {
            $this->pdo->rollBack();
            return false;
        }
    }

    public function delete(int $personne_id): bool {
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        $stmt = $this->pdo->prepare("DELETE FROM personne WHERE id = :id");
        $result = $stmt->execute([':id' => $personne_id]);
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        return $result;
    }

    public function compter(): int {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM professeur")->fetchColumn();
    }

    private function hydrater(array $row): Professeur {
        return new Professeur(
            $row['id'], $row['nom'], $row['prenom'], $row['date_nais'],
            $row['email'], $row['tel'] ?? '', $row['sexe'] ?? '',
            $row['password'], $row['created_at'] ?? '',
            $row['professeur_id'], $row['grade'] ?? '', $row['date_embauche'] ?? ''
        );
    }
}
