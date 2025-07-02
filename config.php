<?php
$host = 'localhost';
$dbname = 'db_videogames';
$user = 'root';
$password = 'Dekithedragon.91';

try {

    // Istanzio la classe PDO inserendo nel costruttore i dati necessari per la connessione al db
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connessione fallita " . $e->getMessage());
}
