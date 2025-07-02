<?php

require_once '../../config.php';


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

function handlePost($pdo)
{
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['name']) || !isset($data['description'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Dati incompleti']);
        return;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO videogames (pegi_id, name, price, year_of_publication, cover, description, publisher, slug) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([$data['pegi_id'], $data['name'], $data['price'], $data['year_of_publication'], $data["cover"], $data['description'], $data['publisher'], $data['slug'],]);
        echo json_encode(['message' => 'Videogioco creato', 'id' => $pdo->lastInsertID()]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handlePut($pdo)
{
    $id = $_GET["id"] ?? null;

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'ID mancante']);
        return;
    }

    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data["name"]) || !isset($data["description"])) {
        http_response_code(400);
        echo json_encode(['error' => 'Dati incompleti']);
        return;
    }

    try {
        $stmt = $pdo->prepare("UPDATE videogames SET pegi_id=?, name=?,price=?,year_of_publication=?,cover=?,description=?,publisher=?,slug=? WHERE id = ?");
        $stmt->execute([$data['pegi_id'], $data['name'], $data['price'], $data['year_of_publication'], $data["cover"], $data['description'], $data['publisher'], $data['slug'], $id]);
        if ($stmt->rowCount()) {
            echo json_encode(['message' => 'Videogioco aggiornato']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Videogioco non trovato o nessuna modifica effettuata']);
        }
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
