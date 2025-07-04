<?php

// SLUG 

function slugify(string $name): string
{
    $slug = strtolower(trim($name));
    $slug = preg_replace('/[^a-z0-9-]+/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    return trim($slug, '-');
}

function slugExists(PDO $pdo, string $table, string $column, string $slug): bool
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE $column = ?");
    $stmt->execute([$slug]);
    return $stmt->fetchColumn() > 0;
}

function generateUniqueSlug(PDO $pdo, string $table, string $column, string $name): string
{
    $baseSlug = slugify($name);
    $slug = $baseSlug;
    $i = 1;

    while (slugExists($pdo, $table, $column, $slug)) {
        $slug = $baseSlug . '-' . $i;
        $i++;
    }

    return $slug;
}

function loadEnv($path)
{
    if (!file_exists($path)) return;

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;

        list($key, $value) = explode('=', $line, 2);
        putenv(trim($key) . '=' . trim($value));
    }
}
