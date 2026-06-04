<?php
// ============================================================
//  COUCHE MÉTIER — Notification
// ============================================================

class Notification {
    private int    $id;
    private string $titre;
    private string $message;
    private string $date_envoie;
    private bool   $ouvert;
    private int    $personne_id;

    public function __construct(
        int    $id = 0,
        string $titre = '',
        string $message = '',
        string $date_envoie = '',
        bool   $ouvert = false,
        int    $personne_id = 0
    ) {
        $this->id          = $id;
        $this->titre       = $titre;
        $this->message     = $message;
        $this->date_envoie = $date_envoie;
        $this->ouvert      = $ouvert;
        $this->personne_id = $personne_id;
    }

    // Getters
    public function getId():          int    { return $this->id; }
    public function getTitre():       string { return $this->titre; }
    public function getMessage():     string { return $this->message; }
    public function getDateEnvoie():  string { return $this->date_envoie; }
    public function isOuvert():       bool   { return $this->ouvert; }
    public function getPersonneId():  int    { return $this->personne_id; }

    // Setters
    public function setId(int $id):            void { $this->id = $id; }
    public function setTitre(string $titre):   void { $this->titre = $titre; }
    public function setMessage(string $msg):   void { $this->message = $msg; }
    public function setDateEnvoie(string $d):  void { $this->date_envoie = $d; }
    public function setOuvert(bool $ouvert):   void { $this->ouvert = $ouvert; }
    public function setPersonneId(int $id):    void { $this->personne_id = $id; }

    public function marquerLu(): void { $this->ouvert = true; }
}
