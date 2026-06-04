<?php
require_once __DIR__ . '/../metier/Personne.php';

class PersonneDAO {
    private PDO $pdo;

    public function __construct($db) {
        $this->pdo = $db;
    }

    // Trouver une personne par email (pour la connexion)
    public function findByEmail(string $email): ?Personne {
        $stmt = $this->pdo->prepare("SELECT * FROM personne WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch();
        if (!$row) return null;
        return $this->hydrater($row);
    }
    public function getEtudiants(): array
    {
        $sql = "
            SELECT
                p.nom,
                p.prenom,
                p.email,
                e.matricule
            FROM personne p
            INNER JOIN etudiant e
                ON e.personne_id = p.id
        ";

        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getProfesseurs(): array
    {
        $sql = "
            SELECT
                p.nom,
                p.prenom,
                p.email
            FROM personne p
            WHERE p.role = 'professeur'
        ";

        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
    // Trouver par ID
    public function findById(int $id): ?Personne {
        $stmt = $this->pdo->prepare("SELECT * FROM personne WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if (!$row) return null;
        return $this->hydrater($row);
    }

    // Créer une personne
    public function create(Personne $p): int {
        $stmt = $this->pdo->prepare("
            INSERT INTO personne (nom, prenom, date_nais, email, tel, sexe, password, role)
            VALUES (:nom, :prenom, :date_nais, :email, :tel, :sexe, :password, :role)
        ");
        $stmt->execute([
            ':nom'       => $p->getNom(),
            ':prenom'    => $p->getPrenom(),
            ':date_nais' => $p->getDateNais(),
            ':email'     => $p->getEmail(),
            ':tel'       => $p->getTel(),
            ':sexe'      => $p->getSexe(),
            ':password'  => password_hash($p->getPassword(), PASSWORD_BCRYPT),
            ':role'      => $p->getRole(),
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    // Mettre à jour
    public function update(Personne $p): bool {
        $stmt = $this->pdo->prepare("
            UPDATE personne SET nom=:nom, prenom=:prenom, email=:email,
            tel=:tel, sexe=:sexe WHERE id=:id
        ");
        return $stmt->execute([
            ':nom'    => $p->getNom(),
            ':prenom' => $p->getPrenom(),
            ':email'  => $p->getEmail(),
            ':tel'    => $p->getTel(),
            ':sexe'   => $p->getSexe(),
            ':id'     => $p->getId(),
        ]);
    }

    // Supprimer
    public function delete(int $id): bool {
        $stmt = $this->pdo->prepare("DELETE FROM personne WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    // Vérifier le mot de passe
    public function verifierMotDePasse(string $email, string $password): ?Personne {
        $personne = $this->findByEmail($email);
        if (!$personne) return null;
        if (!($password == $personne->getPassword())) return null;
        return $personne;
    }

    // Hydrater un objet Personne depuis une ligne BD
    private function hydrater(array $row): Personne {
        return new Personne(
            $row['id'],
            $row['nom'],
            $row['prenom'],
            $row['date_nais'],
            $row['email'],
            $row['tel'] ?? '',
            $row['sexe'] ?? '',
            $row['password'],
            $row['role'],
            $row['created_at'] ?? ''
        );
    }

    // Authentifier la direction des études
    public function authentifierDirection(string $username, string $password): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM direction_etude WHERE user_name = :u");
        $stmt->execute([':u' => $username]);
        $admin = $stmt->fetch();
        if ($admin && ($password == $admin['password'])) {
            return $admin;
        }
        return null;
    }

    public function authentifierPersonne(string $email, string $password): ?array {
     
        $stmt = $this->pdo->prepare("SELECT * FROM personne WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return null;
    }
}
