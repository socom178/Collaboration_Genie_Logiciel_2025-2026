<?php

class Memoire {
    private int    $id_memoir;
    private string $theme;
    private string $resumer;
    private string $fichier_pdf;
    private string $date_soumission;
    private string $statut; 
    private string $mots_cle;
    private string $annee;
    private int    $etudiant_id;
    private ?int   $professeur_id;
    private string $created_at;

    public function __construct(
        int    $id_memoir = 0,
        string $theme = '',
        string $resumer = '',
        string $fichier_pdf = '',
        string $date_soumission = '',
        string $statut = 'en_attente',
        string $mots_cle = '',
        string $annee = '',
        int    $etudiant_id = 0,
        ?int   $professeur_id = null,
        string $created_at = ''
    ) {
        $this->id_memoir       = $id_memoir;
        $this->theme           = $theme;
        $this->resumer         = $resumer;
        $this->fichier_pdf     = $fichier_pdf;
        $this->date_soumission = $date_soumission;
        $this->statut          = $statut;
        $this->mots_cle        = $mots_cle;
        $this->annee           = $annee;
        $this->etudiant_id     = $etudiant_id;
        $this->professeur_id   = $professeur_id;
        $this->created_at      = $created_at;
    }

    // Getters
    public function getIdMemoir():       int     { return $this->id_memoir; }
    public function getTheme():          string  { return $this->theme; }
    public function getResumer():        string  { return $this->resumer; }
    public function getFichierPdf():     string  { return $this->fichier_pdf; }
    public function getDateSoumission(): string  { return $this->date_soumission; }
    public function getStatut():         string  { return $this->statut; }
    public function getMotsCle():        string  { return $this->mots_cle; }
    public function getAnnee():          string  { return $this->annee; }
    public function getEtudiantId():     int     { return $this->etudiant_id; }
    public function getProfesseurId():   ?int    { return $this->professeur_id; }
    public function getCreatedAt():      string  { return $this->created_at; }

    // Setters
    public function setIdMemoir(int $id):            void { $this->id_memoir = $id; }
    public function setTheme(string $theme):         void { $this->theme = $theme; }
    public function setResumer(string $resumer):     void { $this->resumer = $resumer; }
    public function setFichierPdf(string $f):        void { $this->fichier_pdf = $f; }
    public function setDateSoumission(string $d):    void { $this->date_soumission = $d; }
    public function setStatut(string $statut):       void { $this->statut = $statut; }
    public function setMotsCle(string $mots_cle):    void { $this->mots_cle = $mots_cle; }
    public function setAnnee(string $annee):         void { $this->annee = $annee; }
    public function setEtudiantId(int $id):          void { $this->etudiant_id = $id; }
    public function setProfesseurId(?int $id):       void { $this->professeur_id = $id; }

    public function estEnAttente(): bool { return $this->statut === 'en_attente'; }
    public function estValide():    bool { return $this->statut === 'valide'; }
    public function estRefuse():    bool { return $this->statut === 'refuse'; }

    public function publier(): void  { $this->statut = 'valide'; }
    public function rejeter(): void  { $this->statut = 'refuse'; }
}
