<?php
// ============================================================
//  COUCHE MÉTIER — Personne (classe mère)
// ============================================================

class Personne {
    protected int    $id;
    protected string $nom;
    protected string $prenom;
    protected string $date_nais;
    protected string $email;
    protected string $tel;
    protected string $sexe;
    protected string $password;
    protected string $role;
    protected string $created_at;

    public function __construct(
        int    $id = 0,
        string $nom = '',
        string $prenom = '',
        string $date_nais = '',
        string $email = '',
        string $tel = '',
        string $sexe = '',
        string $password = '',
        string $role = '',
        string $created_at = ''
    ) {
        $this->id         = $id;
        $this->nom        = $nom;
        $this->prenom     = $prenom;
        $this->date_nais  = $date_nais;
        $this->email      = $email;
        $this->tel        = $tel;
        $this->sexe       = $sexe;
        $this->password   = $password;
        $this->role       = $role;
        $this->created_at = $created_at;
    }

    // Getters
    public function getId():        int    { return $this->id; }
    public function getNom():       string { return $this->nom; }
    public function getPrenom():    string { return $this->prenom; }
    public function getDateNais():  string { return $this->date_nais; }
    public function getEmail():     string { return $this->email; }
    public function getTel():       string { return $this->tel; }
    public function getSexe():      string { return $this->sexe; }
    public function getPassword():  string { return $this->password; }
    public function getRole():      string { return $this->role; }
    public function getCreatedAt(): string { return $this->created_at; }

    // Setters
    public function setId(int $id):               void { $this->id = $id; }
    public function setNom(string $nom):           void { $this->nom = $nom; }
    public function setPrenom(string $prenom):     void { $this->prenom = $prenom; }
    public function setDateNais(string $d):        void { $this->date_nais = $d; }
    public function setEmail(string $email):       void { $this->email = $email; }
    public function setTel(string $tel):           void { $this->tel = $tel; }
    public function setSexe(string $sexe):         void { $this->sexe = $sexe; }
    public function setPassword(string $password): void { $this->password = $password; }
    public function setRole(string $role):         void { $this->role = $role; }

    public function getNomComplet(): string {
        return $this->prenom . ' ' . $this->nom;
    }
}
