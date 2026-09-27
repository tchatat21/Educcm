<?php
function getSchoolName(mysqli $conn): string
{
    $result = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'school_name' LIMIT 1");
    if ($result && $row = $result->fetch_assoc()) {
        $name = trim((string)$row['setting_value']);
        if ($name !== '') {
            return $name;
        }
    }

    return 'EDUC.CM';
}

function getSchoolDisplayName(mysqli $conn): string
{
    $initial_name = 'EDUC.CM';
    $configured_name = getSchoolName($conn);

    if (strcasecmp($configured_name, $initial_name) === 0) {
        return $initial_name;
    }

    return $initial_name . ' - ' . $configured_name;
}

function getSchoolLogoDataUri(mysqli $conn): string
{
    $result = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'school_logo_data' LIMIT 1");
    if ($result && $row = $result->fetch_assoc()) {
        $logo_data = (string)$row['setting_value'];
        if (preg_match('~^data:image/(png|jpeg|webp);base64,[A-Za-z0-9+/=]+$~', $logo_data)) {
            return $logo_data;
        }
    }

    return '';
}