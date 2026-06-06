<?php
require_once __DIR__ . '/../metier/Memoire.php';

class MemoireDAO {
    private PDO $pdo;

    public function __construct($db) {
        $this->pdo = $db;
    }
    public function findByProfesseur(int $professeur_id): array {
        $stmt = $this->pdo->prepare("
            SELECT * FROM memoire 
            WHERE professeur_id = :professeur_id 
            AND statut = 'en_attente'
            ORDER BY date_soumission DESC
        ");
        $stmt->execute([':professeur_id' => $professeur_id]);
        return $this->hydraterListe($stmt);
    }
    public function findAll(): array {
        $stmt = $this->pdo->query("SELECT * FROM memoire ORDER BY date_soumission DESC");
        return $this->hydraterListe($stmt);
    }

    public function findById(int $id): ?Memoire {
        $stmt = $this->pdo->prepare("SELECT * FROM memoire WHERE id_memoir = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if (!$row) return null;
        return $this->hydrater($row);
    }

    public function findByStatut(string $statut): array {
        $stmt = $this->pdo->prepare("SELECT * FROM memoire WHERE statut = :statut ORDER BY date_soumission DESC");
        $stmt->execute([':statut' => $statut]);
        return $this->hydraterListe($stmt);
    }

    public function findByEtudiant(int $etudiant_id): array {
        $stmt = $this->pdo->prepare("SELECT * FROM memoire WHERE etudiant_id = :id ORDER BY date_soumission DESC");
        $stmt->execute([':id' => $etudiant_id]);
        return $this->hydraterListe($stmt);
    }

    // Recherche par thème, mots-clés, année
    public function rechercher(string $q): array {
        $q = '%' . $q . '%';
        $stmt = $this->pdo->prepare("
            SELECT * FROM memoire
            WHERE statut = 'valide'
            AND (theme LIKE :q OR mots_cle LIKE :q OR resumer LIKE :q)
            ORDER BY date_soumission DESC
        ");
        $stmt->execute([':q' => $q]);
        return $this->hydraterListe($stmt);
    }
    // Créer un mémoire pour anciens mémoires
    public function createDirect(Memoire $m): int {
        $stmt = $this->pdo->prepare("
            INSERT INTO memoire (theme, resumer, fichier_pdf, date_soumission, statut, mots_cle, annee, etudiant_id)
            VALUES (:theme, :resumer, :fichier_pdf, :date_soumission, 'valide', :mots_cle, :annee, :etudiant_id)
        ");
        $stmt->execute([
            ':theme'           => $m->getTheme(),
            ':resumer'         => $m->getResumer(),
            ':fichier_pdf'     => $m->getFichierPdf(),
            ':date_soumission' => $m->getDateSoumission(),
            ':mots_cle'        => $m->getMotsCle(),
            ':annee'           => $m->getAnnee(),
            ':etudiant_id'     => $m->getEtudiantId(),
        ]);
        return (int)$this->pdo->lastInsertId();
    }
    // Soumettre un mémoire
    public function create(Memoire $m): int {
        $stmt = $this->pdo->prepare("
            INSERT INTO memoire (theme, resumer, fichier_pdf, date_soumission, statut, mots_cle, annee, etudiant_id, professeur_id)
            VALUES (:theme, :resumer, :fichier_pdf, :date_soumission, 'en_attente', :mots_cle, :annee, :etudiant_id, :professeur_id)
        ");
        $stmt->execute([
            ':theme'           => $m->getTheme(),
            ':resumer'         => $m->getResumer(),
            ':fichier_pdf'     => $m->getFichierPdf(),
            ':date_soumission' => $m->getDateSoumission(),
            ':mots_cle'        => $m->getMotsCle(),
            ':annee'           => $m->getAnnee(),
            ':etudiant_id'     => $m->getEtudiantId(),
            ':professeur_id'   => $m->getProfesseurId(), 
        ]);
        $id = (int)$this->pdo->lastInsertId();

        if ($id) {
            require_once __DIR__ . '/../service/MailerService.php';

            // Récupérer les infos de l'étudiant
            $stmt2 = $this->pdo->prepare("
                SELECT p.email, p.nom, p.prenom
                FROM personne p
                INNER JOIN etudiant e ON e.personne_id = p.id
                WHERE e.id = :etudiant_id
            ");
            $stmt2->execute([':etudiant_id' => $m->getEtudiantId()]);
            $etudiant = $stmt2->fetch();

            // Mail à l'étudiant pour confirmation de soumission
            if ($etudiant) {
                MailerService::sendMail(
                    $etudiant['email'],
                    "Confirmation de soumission — UATM Mémoires",
                    "
                        <h2>Bonjour {$etudiant['prenom']} {$etudiant['nom']} 👋</h2>
                        <p>Votre mémoire <b>\"{$m->getTheme()}\"</b> a été soumis avec succès.</p>
                        <p>Il est actuellement en attente de validation par un enseignant.</p>
                        <p>Vous recevrez un mail dès qu'une décision sera prise.</p>
                    "
                );
            }

            // Mail à tous les professeurs pour avertissement
            $stmt3 = $this->pdo->query("SELECT p.email, p.nom, p.prenom FROM personne p WHERE p.role = 'professeur'");
            $professeurs = $stmt3->fetchAll();
            foreach ($professeurs as $prof) {
                MailerService::sendMail(
                    $prof['email'],
                    "Nouveau mémoire soumis — UATM Mémoires",
                    "
                        <h2>Bonjour {$prof['prenom']} {$prof['nom']} 👋</h2>
                        <p>Un nouveau mémoire a été soumis et attend votre validation.</p>
                        <p><b>Thème :</b> {$m->getTheme()}</p>
                        <p><b>Soumis le :</b> {$m->getDateSoumission()}</p>
                        <p>Connectez-vous à la plateforme pour l'examiner.</p>
                    "
                );
            }
        }

        return $id;
    }

    public function existeMemoire(int $etudiant_id, string $annee): bool {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM memoire 
            WHERE etudiant_id = :etudiant_id 
            AND annee = :annee
        ");
        $stmt->execute([
            ':etudiant_id' => $etudiant_id,
            ':annee'       => $annee
        ]);
        return (int)$stmt->fetchColumn() > 0;
    }
// Valider un mémoire
public function valider(int $id, int $professeur_id): bool {
    $stmt = $this->pdo->prepare("
        UPDATE memoire SET statut = 'valide', professeur_id = :prof_id WHERE id_memoir = :id
    ");
    $ok = $stmt->execute([':prof_id' => $professeur_id, ':id' => $id]);

    if ($ok) {
        require_once __DIR__ . '/../service/MailerService.php';

        // Récupérer les infos du mémoire et de l'étudiant
        $stmt2 = $this->pdo->prepare("
            SELECT m.theme, p.email, p.nom, p.prenom
            FROM memoire m
            INNER JOIN etudiant e ON e.id = m.etudiant_id
            INNER JOIN personne p ON p.id = e.personne_id
            WHERE m.id_memoir = :id
        ");
        $stmt2->execute([':id' => $id]);
        $data = $stmt2->fetch();

        if ($data) {
            MailerService::sendMail(
                $data['email'],
                "Mémoire validé ✅ — UATM Mémoires",
                "
                    <h2>Bonjour {$data['prenom']} {$data['nom']} 👋</h2>
                    <p>Votre mémoire <b>\"{$data['theme']}\"</b> a été <b style='color:green'>validé</b> et publié sur la plateforme.</p>
                    <p>Félicitations !</p>
                "
            );
        }
    }

    return $ok;
}

// Refuser un mémoire
public function refuser(int $id, int $professeur_id, string $motif = ''): bool {
    $stmt = $this->pdo->prepare("
        UPDATE memoire SET statut = 'refuse', professeur_id = :prof_id WHERE id_memoir = :id
    ");
    $ok = $stmt->execute([':prof_id' => $professeur_id, ':id' => $id]);

    if ($ok) {
        require_once __DIR__ . '/../service/MailerService.php';

        // Récupérer les infos du mémoire et de l'étudiant
        $stmt2 = $this->pdo->prepare("
            SELECT m.theme, p.email, p.nom, p.prenom
            FROM memoire m
            INNER JOIN etudiant e ON e.id = m.etudiant_id
            INNER JOIN personne p ON p.id = e.personne_id
            WHERE m.id_memoir = :id
        ");
        $stmt2->execute([':id' => $id]);
        $data = $stmt2->fetch();

        if ($data) {
            MailerService::sendMail(
                $data['email'],
                "Mémoire refusé ❌ — UATM Mémoires",
                "
                    <h2>Bonjour {$data['prenom']} {$data['nom']} 👋</h2>
                    <p>Votre mémoire <b>\"{$data['theme']}\"</b> a été <b style='color:red'>refusé</b>.</p>
                    <p><b>Motif :</b> $motif</p>
                    <p>Veuillez contacter votre enseignant pour plus d'informations.</p>
                "
            );
        }
    }

    return $ok;
}

    // Supprimer
    public function delete(int $id): bool {
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        $stmt = $this->pdo->prepare("DELETE FROM memoire WHERE id_memoir = :id");
        $result = $stmt->execute([':id' => $id]);
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        return $result;
    }

    // Statistiques
    public function compter(): int {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM memoire WHERE statut='valide'")->fetchColumn();
    }

    public function compterEnAttente(): int {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM memoire WHERE statut='en_attente'")->fetchColumn();
    }

    private function hydraterListe(\PDOStatement $stmt): array {
        $memoires = [];
        while ($row = $stmt->fetch()) {
            $memoires[] = $this->hydrater($row);
        }
        return $memoires;
    }

    private function hydrater(array $row): Memoire {
        return new Memoire(
            $row['id_memoir'], $row['theme'], $row['resumer'] ?? '',
            $row['fichier_pdf'], $row['date_soumission'], $row['statut'],
            $row['mots_cle'] ?? '', $row['annee'], $row['etudiant_id'],
            $row['professeur_id'] ?? null, $row['created_at'] ?? ''
        );
    }
}
