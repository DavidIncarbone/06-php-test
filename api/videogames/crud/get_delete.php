<?php

require_once  '../../../config.php';
require_once  '../../../functions.php';

function handleGet($pdo)
{
    $id = $_GET["id"] ?? null;

    try {
        if ($id) {
            $stmt = $pdo->prepare("SELECT * FROM videogames WHERE id = ?");
            $stmt->execute([$id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user) {
                echo json_encode($user);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Videogioco non trovato']);
            }
        } else {
            $stmt = $pdo->query("SELECT * FROM videogames");
            $utenti = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($utenti);
        };
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}


function handleDelete($pdo)
{
    $id = $_GET["id"] ?? null;

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'ID mancante']);
        return;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM videogames WHERE id = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount()) {
            echo json_encode(['message' => 'Videogioco eliminato']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Videogioco non trovato']);
        };
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
