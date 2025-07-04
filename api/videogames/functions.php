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
