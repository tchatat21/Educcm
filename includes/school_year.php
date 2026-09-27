<?php
/**
 * Retourne l'année scolaire en cours, avec une rentrée fixée au mois de septembre.
 * Exemple : septembre 2026 à août 2027 donne 2026-2027.
 */
function getCurrentSchoolYear(?DateTimeInterface $date = null): string
{
    $date = $date ?? new DateTimeImmutable('now', new DateTimeZone('Africa/Douala'));
    $year = (int)$date->format('Y');
    $month = (int)$date->format('n');
    $start_year = $month >= 9 ? $year : $year - 1;

    return $start_year . '-' . ($start_year + 1);
}