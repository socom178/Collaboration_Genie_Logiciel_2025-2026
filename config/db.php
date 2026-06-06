<?php
function getDb() {
    static $db = null;
    if ($db === null) {
        $db = new PDO(
            'mysql:host=sql7.freesqldatabase.com;dbname=sql7829562;charset=utf8mb4',
            'sql7829562',
            'Ke1Hiz6VAR',
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
        $db->exec("SET NAMES utf8mb4");
    }
    return $db;

}
$base_url = '';

?>