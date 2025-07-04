<?php

require_once __DIR__ . '/../../vendor/autoload.php';

$envPath = __DIR__ . '/../../';

$dotenv = Dotenv\Dotenv::createImmutable($envPath);
$dotenv->load();

$user = $_ENV['DB_USER'];
$host =  $_ENV['DB_HOST'];
$password = $_ENV['DB_PASS'];
$dbname = $_ENV['DB_NAME'];


try {

    // Istanzio la classe PDO inserendo nel costruttore i dati necessari per la connessione al db
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connessione fallita " . $e->getMessage());
}
