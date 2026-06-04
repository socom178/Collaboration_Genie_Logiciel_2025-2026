<?php
function getDb() {
    static $db = null;
    if ($db === null) {
        $db = new PDO(
            'mysql:host=127.0.0.1;dbname=gestion_memoires_uatm;charset=utf8',
            'root',
            '',
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }
    return $db;
}
$base_url = '/Collaboration_GL_2025-2026/Programme/projet';

?>