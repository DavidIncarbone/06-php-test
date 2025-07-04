<?php

require_once  __DIR__ .  '../../config.php';
require_once  __DIR__ .  '../../functions.php';
require_once  __DIR__ .  '/../../../vendor/autoload.php';

use \Respect\Validation\Validator as v;
use \Respect\Validation\Exceptions\NestedValidationException;




function handlePost($pdo)
{

    $validator = v::key('name', v::stringType()->notEmpty())->key('description', v::stringType()->notEmpty())->key('pegi_id', v::numericVal()->positive()->notEmpty())->key('price', v::numericVal()->positive()->notEmpty())->key('year_of_publication', v::numericVal()->positive()->between(1980, 2025)->notEmpty())->key('publisher', v::stringType()->notEmpty())->key(
        'genre_ids',
        v::arrayType()->each(v::numericVal()->notEmpty())
    );

    try {
        $validator->assert($_POST);
    } catch (NestedValidationException $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessages()]);
        return;
    }

    if (array_key_exists("cover", $_FILES)) {

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
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Cover non presente']);
        return;
    }


    $slug = generateUniqueSlug($pdo, 'videogames', 'slug', $_POST["name"]);

    try {
        $stmt = $pdo->prepare("INSERT INTO videogames (pegi_id, name, price, year_of_publication, cover, description, publisher, slug) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([$_POST['pegi_id'], $_POST['name'], $_POST['price'], $_POST['year_of_publication'], $targetPath, $_POST['description'], $_POST['publisher'], $slug]);
        echo json_encode(['message' => 'Videogioco creato', 'id' => $pdo->lastInsertID()]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }

    // PIVOT

    $genreIds = $_POST["genre_ids"];
    $videogameId = $pdo->lastInsertId();

    $stmt = $pdo->prepare("INSERT INTO genre_videogame (videogame_id, genre_id) VALUES (?,?)");

    foreach ($genreIds as $genreId) {

        $stmt->execute([$videogameId, $genreId]);
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

            $stmt = $pdo->prepare("INSERT INTO screenshots (videogame_id,url,slug) VALUES (?,?,?)");
            $stmt->execute([$videogameId, $targetPath, $screenshotSlug]);
            echo json_encode(['message' => 'screenshot salvato nel database', 'file' => $targetPath]);
        }
    }
}
