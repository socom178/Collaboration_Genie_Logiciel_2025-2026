<?php
require_once __DIR__ . '/../metier/Notification.php';

class NotificationDAO {
    private PDO $pdo;

    public function __construct($db) {
        $this->pdo = $db;
    }

    public function findByPersonne(int $personne_id): array {
        $stmt = $this->pdo->prepare("
            SELECT * FROM notification WHERE personne_id = :id ORDER BY date_envoie DESC
        ");
        $stmt->execute([':id' => $personne_id]);
        $notifs = [];
        while ($row = $stmt->fetch()) {
            $notifs[] = new Notification($row['id'], $row['titre'], $row['message'], $row['date_envoie'], (bool)$row['ouvert'], $row['personne_id']);
        }
        return $notifs;
    }

    public function compterNonLues(int $personne_id): int {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM notification WHERE personne_id = :id AND ouvert = 0");
        $stmt->execute([':id' => $personne_id]);
        return (int)$stmt->fetchColumn();
    }

    public function create(Notification $n): bool {
        $stmt = $this->pdo->prepare("
            INSERT INTO notification (titre, message, personne_id)
            VALUES (:titre, :message, :personne_id)
        ");
        return $stmt->execute([
            ':titre'       => $n->getTitre(),
            ':message'     => $n->getMessage(),
            ':personne_id' => $n->getPersonneId(),
        ]);
    }

    public function marquerLu(int $id): bool {
        $stmt = $this->pdo->prepare("UPDATE notification SET ouvert = 1 WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function marquerToutLu(int $personne_id): bool {
        $stmt = $this->pdo->prepare("UPDATE notification SET ouvert = 1 WHERE personne_id = :id");
        return $stmt->execute([':id' => $personne_id]);
    }
}
