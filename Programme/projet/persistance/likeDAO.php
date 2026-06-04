<?php
class LikeDAO {
    private PDO $pdo;

    public function __construct($db) {
        $this->pdo = $db;
    }

    // Liker ou unliker
    public function toggleLike(int $personne_id, int $memoire_id): string {
        // Vérifier si déjà liké
        $stmt = $this->pdo->prepare("
            SELECT id FROM like_memoire 
            WHERE personne_id = :personne_id AND memoire_id = :memoire_id
        ");
        $stmt->execute([':personne_id' => $personne_id, ':memoire_id' => $memoire_id]);
        $existe = $stmt->fetch();

        if ($existe) {
            // Unlike
            $stmt2 = $this->pdo->prepare("
                DELETE FROM like_memoire 
                WHERE personne_id = :personne_id AND memoire_id = :memoire_id
            ");
            $stmt2->execute([':personne_id' => $personne_id, ':memoire_id' => $memoire_id]);
            return 'unlike';
        } else {
            // Like
            $stmt2 = $this->pdo->prepare("
                INSERT INTO like_memoire (personne_id, memoire_id) 
                VALUES (:personne_id, :memoire_id)
            ");
            $stmt2->execute([':personne_id' => $personne_id, ':memoire_id' => $memoire_id]);
            return 'like';
        }
    }

    // Compter les likes d'un mémoire
    public function compter(int $memoire_id): int {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM like_memoire WHERE memoire_id = :memoire_id
        ");
        $stmt->execute([':memoire_id' => $memoire_id]);
        return (int)$stmt->fetchColumn();
    }

    // Vérifier si l'utilisateur a déjà liké
    public function aLike(int $personne_id, int $memoire_id): bool {
        $stmt = $this->pdo->prepare("
            SELECT id FROM like_memoire 
            WHERE personne_id = :personne_id AND memoire_id = :memoire_id
        ");
        $stmt->execute([':personne_id' => $personne_id, ':memoire_id' => $memoire_id]);
        return (bool)$stmt->fetch();
    }
}