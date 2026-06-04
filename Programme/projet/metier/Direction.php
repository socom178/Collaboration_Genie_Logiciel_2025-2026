<?php
// ============================================================
//  COUCHE MÉTIER — Direction des études
// ============================================================

class Direction {
    private int    $id;
    private string $user_name;
    private string $password;

    public function __construct(
        int    $id = 0,
        string $user_name = '',
        string $password = ''
    ) {
        $this->id        = $id;
        $this->user_name = $user_name;
        $this->password  = $password;
    }

    // Getters
    public function getId():       int    { return $this->id; }
    public function getUserName(): string { return $this->user_name; }
    public function getPassword(): string { return $this->password; }

    // Setters
    public function setId(int $id):            void { $this->id = $id; }
    public function setUserName(string $u):    void { $this->user_name = $u; }
    public function setPassword(string $p):    void { $this->password = $p; }
}
