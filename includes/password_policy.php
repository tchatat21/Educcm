<?php
function isStrongPassword($password): bool
{
    if (!is_string($password)) {
        return false;
    }

    $character_count = preg_match_all('/./us', $password, $matches);
    if ($character_count === false || $character_count < 12) {
        return false;
    }

    return preg_match('/\p{Lu}/u', $password) === 1
        && preg_match('/\p{Ll}/u', $password) === 1
        && preg_match('/\p{N}/u', $password) === 1
        && preg_match('/[\p{P}\p{S}]/u', $password) === 1;
}