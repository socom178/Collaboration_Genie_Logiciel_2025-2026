<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../metier/Commentaire.php';

class CommentaireDAO {
    private PDO $pdo;

    public function __construct($db) {
        $this->pdo = $db;
    }

    public function findByMemoire(int $memoire_id): array {
        $stmt = $this->pdo->prepare("
            SELECT c.*, p.nom, p.prenom, p.role
            FROM commentaire c
            INNER JOIN personne p ON p.id = c.personne_id
            WHERE c.memoire_id = :memoire_id
            ORDER BY c.date_envoie DESC
        ");
        $stmt->execute([':memoire_id' => $memoire_id]);
        $commentaires = [];
        while ($row = $stmt->fetch()) {
            $c = new Commentaire($row['id'], $row['contenu'], $row['date_envoie'], $row['personne_id'], $row['memoire_id']);
            $c->auteur = $row['prenom'] . ' ' . $row['nom'];
            $c->role   = $row['role'];
            $commentaires[] = $c;
        }
        return $commentaires;
    }

    public function create(Commentaire $c): bool {
        $stmt = $this->pdo->prepare("
            INSERT INTO commentaire (contenu, personne_id, memoire_id)
            VALUES (:contenu, :personne_id, :memoire_id)
        ");
        return $stmt->execute([
            ':contenu'     => $c->getContenu(),
            ':personne_id' => $c->getPersonneId(),
            ':memoire_id'  => $c->getMemoireId(),
        ]);
    }

    public function delete(int $id): bool {
        $stmt = $this->pdo->prepare("DELETE FROM commentaire WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
