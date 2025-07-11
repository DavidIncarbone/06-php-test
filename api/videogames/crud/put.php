<?php

require_once  __DIR__ .  '../../config.php';
require_once  __DIR__ .  '../../functions.php';
require_once  __DIR__ .  '/../../../vendor/autoload.php';

use \Respect\Validation\Validator as v;
use \Respect\Validation\Exceptions\NestedValidationException;

function handlePut($pdo)
{
    $id = $_GET["id"] ?? null;

    // Recupero la cover attuale dal DB

    echo json_encode($_POST);

    try {
        $stmt = $pdo->prepare("SELECT cover FROM videogames WHERE id = ?");
        $stmt->execute([$id]);
        $oldCover = $stmt->fetchColumn();
    } catch (PDOException $e) {
        http_response_code(400);
        echo json_encode(["error" => $e->getMessage()]);
        return;
    }

    // Validazione
    $validator = v::key('name', v::stringType()->notEmpty())->key('description', v::stringType()->notEmpty())->key('pegi_id', v::numericVal()->positive()->notEmpty())->key('price', v::numericVal()->positive()->notEmpty())->key('year_of_publication', v::numericVal()->positive()->between(1980, 2025)->notEmpty())->key('publisher', v::stringType()->notEmpty())->key('genre_ids', v::arrayType()->each(v::numericVal()->notEmpty()));

    try {
        $validator->assert($_POST);
    } catch (NestedValidationException $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessages()]);
        return;
    }

    if (array_key_exists("cover", $_FILES)) {
        // Salvo la nuova cover sul server
        $cover = $_FILES['cover'];
        $uploadDir = __DIR__ . '/uploads/cover/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filename = uniqid() . '_' . basename(str_replace(' ', '_', $cover['name']));
        $targetPath = $uploadDir . $filename;
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $cover['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedMimeTypes)) {
            http_response_code(400);
            echo json_encode(['error' => 'Formato file non valido. Sono accettate solo immagini']);
            return;
        }

        $maxSize = 5 * 1024 * 1024;

        if ($cover['size'] > $maxSize) {
            http_response_code(400);
            echo json_encode(['error' => 'Il file supera la dimensione massima di 5 MB']);
            return;
        }

        if (move_uploaded_file($cover['tmp_name'], $targetPath)) {
            echo json_encode(['message' => 'cover caricata', 'file' => 'uploads/cover/' . $filename]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Errore nel salvataggio del file']);
            return;
        }

        // Cancello la vecchia cover

        if (file_exists($oldCover)) {
            unlink($oldCover);
        }
    }

    $slug = generateUniqueSlug($pdo, 'videogames', 'slug', $_POST["name"]);

    // Salvo nel Database
    $stmtId = $pdo->prepare("SELECT * FROM videogames WHERE id = ?");
    $stmtId->execute([$id]);
    $videogameId = $stmtId->fetchColumn();
    if (!$videogameId) {
        http_response_code(400);
        echo json_encode(['error' => 'Videogioco non trovato']);
        return;
    }
    try {

        $stmt = $pdo->prepare("UPDATE videogames 
    SET pegi_id = ?, 
    name = ?, 
    price = ?, 
    year_of_publication = ?, 
    cover = ?, 
    description = ?, 
    publisher = ?, 
    slug = ?
    WHERE id = ?");
        $stmt->execute([$_POST['pegi_id'], $_POST['name'], $_POST['price'], $_POST['year_of_publication'], $targetPath ?? $oldCover, $_POST['description'], $_POST['publisher'], $slug, $id]);
        echo json_encode(['message' => 'Videogioco modificato', 'id' => $id]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }

    //  PIVOT

    $genreIds = $_POST["genre_ids"];

    // Faccio una transazione per garantire un rollback in caso di errore
    $pdo->beginTransaction();

    try {
        $stmtDelete = $pdo->prepare("DELETE FROM genre_videogame WHERE videogame_id = ?");
        $stmtDelete->execute([$id]);

        $stmt = $pdo->prepare("INSERT INTO genre_videogame (videogame_id, genre_id) VALUES (?,?)");
        foreach ($genreIds as $genreId) {
            $stmt->execute([$id, $genreId]);
        }

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Errore durante l\'aggiornamento dei generi']);
    }

    // SCHEENSHOTS

    if (array_key_exists('screenshots', $_FILES)) {

        $screenshots = $_FILES['screenshots'];
        $uploadDir = __DIR__ . '/uploads/screenshots/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        foreach ($screenshots["name"] as $key => $value) {

            $filename = uniqid() . '_' . basename(str_replace(' ', '_', $value));
            $targetPath = $uploadDir . $filename;
            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $tmpName = $screenshots["tmp_name"][$key];
            $mimeType = finfo_file($finfo, $tmpName);
            finfo_close($finfo);

            if (!in_array($mimeType, $allowedMimeTypes)) {
                http_response_code(400);
                echo json_encode(['error' => 'Formato file non valido. Sono accettate solo immagini']);
                return;
            }

            $size = $screenshots["size"][$key];

            $maxSize = 5 * 1024 * 1024;

            if ($size > $maxSize) {
                http_response_code(400);
                echo json_encode(['error' => 'Il file supera la dimensione massima di 5 MB']);
                return;
            }
            if (move_uploaded_file($tmpName, $targetPath)) {
                echo json_encode(['message' => 'screenshot caricato nel server', 'file' => 'uploads/screenshots/' . $filename]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Errore nel salvataggio del file']);
                return;
            }

            $screenshotSlug = generateUniqueSlug($pdo, 'screenshots', 'slug', $screenshots["name"][$key]);

            // Recupero gli screenshots attuali dal DB

            try {
                $stmt = $pdo->prepare("SELECT url FROM screenshots WHERE videogame_id = ?");
                $stmt->execute([$id]);
                $oldScreenshots = $stmt->fetchAll(PDO::FETCH_COLUMN);

                echo JSON_ENCODE($oldScreenshots);

                foreach ($oldScreenshots as $oldScreenshot) {
                    if (file_exists($oldScreenshot)) {
                        unlink($oldScreenshot);
                    }
                }
            } catch (PDOException $e) {
                http_response_code(400);
                echo json_encode(["error" => $e->getMessage()]);
                return;
            }
            $pdo->beginTransaction();
            try {
                $stmtDelete = $pdo->prepare("DELETE FROM screenshots WHERE videogame_id = ?");
                $stmtDelete->execute([$id]);
                $screenshotSlug = generateUniqueSlug($pdo, 'screenshots', 'slug', $screenshots["name"][$key]);
                $stmt = $pdo->prepare("INSERT INTO screenshots (videogame_id,url,slug) VALUES (?,?,?)");
                $stmt->execute([$id, $targetPath, $screenshotSlug]);
                echo json_encode(['message' => 'screenshot modificato nel database', 'file' => $targetPath]);
                $pdo->commit();
            } catch (PDOException $e) {
                $pdo->rollBack();
                http_response_code(500);
                echo json_encode(['error' => 'Errore durante l\'aggiornamento dei generi']);
            }
        }
    }
}
