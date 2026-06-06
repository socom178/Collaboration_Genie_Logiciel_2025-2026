<?php

class Commentaire {
    private int    $id;
    private string $contenu;
    private string $date_envoie;
    private int    $personne_id;
    private int    $memoire_id;

    public function __construct(
        int    $id = 0,
        string $contenu = '',
        string $date_envoie = '',
        int    $personne_id = 0,
        int    $memoire_id = 0
    ) {
        $this->id          = $id;
        $this->contenu     = $contenu;
        $this->date_envoie = $date_envoie;
        $this->personne_id = $personne_id;
        $this->memoire_id  = $memoire_id;
    }

    // Getters
    public function getId():          int    { return $this->id; }
    public function getContenu():     string { return $this->contenu; }
    public function getDateEnvoie():  string { return $this->date_envoie; }
    public function getPersonneId():  int    { return $this->personne_id; }
    public function getMemoireId():   int    { return $this->memoire_id; }

    // Setters
    public function setId(int $id):              void { $this->id = $id; }
    public function setContenu(string $contenu): void { $this->contenu = $contenu; }
    public function setDateEnvoie(string $d):    void { $this->date_envoie = $d; }
    public function setPersonneId(int $id):      void { $this->personne_id = $id; }
    public function setMemoireId(int $id):       void { $this->memoire_id = $id; }
}
