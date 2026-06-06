<?php
require_once __DIR__ . '/Personne.php';

class Professeur extends Personne {
    private int    $professeur_id;
    private string $grade;
    private string $date_embauche;

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
        int    $professeur_id = 0,
        string $grade = '',
        string $date_embauche = ''
    ) {
        parent::__construct($id, $nom, $prenom, $date_nais, $email, $tel, $sexe, $password, 'professeur', $created_at);
        $this->professeur_id = $professeur_id;
        $this->grade         = $grade;
        $this->date_embauche = $date_embauche;
    }

    // Getters
    public function getProfesseurId():  int    { return $this->professeur_id; }
    public function getGrade():         string { return $this->grade; }
    public function getDateEmbauche():  string { return $this->date_embauche; }

    // Setters
    public function setProfesseurId(int $id):       void { $this->professeur_id = $id; }
    public function setGrade(string $grade):        void { $this->grade = $grade; }
    public function setDateEmbauche(string $date):  void { $this->date_embauche = $date; }
}
