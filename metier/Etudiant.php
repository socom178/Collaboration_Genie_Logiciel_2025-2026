<?php

require_once __DIR__ . '/Personne.php';

class Etudiant extends Personne {
    private int    $etudiant_id;
    private string $matricule;
    private string $type; 

    public function __construct(
        int    $id = 0,
        string $nom = '',
        string $prenom = '',
        string $date_nais = '',
        string $email = '',
        string $tel = '',
        string $sexe = '',
        string $password = '',
        string $created_at = '',
        int    $etudiant_id = 0,
        string $matricule = '',
        string $type = 'simple'
    ) {
        parent::__construct($id, $nom, $prenom, $date_nais, $email, $tel, $sexe, $password, 'etudiant', $created_at);
        $this->etudiant_id = $etudiant_id;
        $this->matricule   = $matricule;
        $this->type        = $type;
    }

    // Getters
    public function getEtudiantId(): int    { return $this->etudiant_id; }
    public function getMatricule():  string { return $this->matricule; }
    public function getType():       string { return $this->type; }

    // Setters
    public function setEtudiantId(int $id):    void { $this->etudiant_id = $id; }
    public function setMatricule(string $m):   void { $this->matricule = $m; }
    public function setType(string $type):     void { $this->type = $type; }

    public function estDiplome(): bool {
        return $this->type === 'diplome';
    }

    public function demanderUpgrade(): string {
        return $this->matricule;
    }
}
