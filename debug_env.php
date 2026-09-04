<?php
// Fichier de diagnostic Railway - À SUPPRIMER après résolution du problème
// Accès : https://votre-app.railway.app/debug_env.php

// Sécurité minimale : clé secrète dans l'URL
$secret = getEnvVar('DEBUG_SECRET') ?: 'railway_debug_2024';
if (!isset($_GET['key']) || $_GET['key'] !== $secret) {
    http_response_code(403);
    die("Accès refusé. Ajoutez ?key=VOTRE_DEBUG_SECRET");
}

function getEnvVar($key, $default = null)
{
    if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') return $_SERVER[$key];
    if (isset($_ENV[$key]) && $_ENV[$key] !== '') return $_ENV[$key];
    $val = getenv($key);
    if ($val !== false && $val !== '') return $val;
    return $default;
}

function maskValue($value) {
    if (empty($value)) return '<span style="color:red">❌ VIDE / NON DÉFINI</span>';
    $len = strlen($value);
    if ($len <= 4) return '<span style="color:orange">⚠️ Défini (valeur courte)</span>';
    return '<span style="color:green">✅ Défini (' . $len . ' caractères)</span>';
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Diagnostic Railway</title>
<style>
body { font-family: monospace; padding: 20px; background: #1a1a2e; color: #eee; }
table { border-collapse: collapse; width: 100%; }
th, td { border: 1px solid #444; padding: 8px 12px; text-align: left; }
th { background: #16213e; }
tr:nth-child(even) { background: #0f3460; }
h2 { color: #e94560; }
</style>
</head>
<body>
<h1>🔍 Diagnostic Variables d'Environnement Railway</h1>

<h2>PHP Info</h2>
<table>
<tr><th>Paramètre</th><th>Valeur</th></tr>
<tr><td>Version PHP</td><td><?= phpversion() ?></td></tr>
<tr><td>variables_order</td><td><?= ini_get('variables_order') ?> <?= (str_contains(strtoupper(ini_get('variables_order')), 'E')) ? '<span style="color:green">✅ E présent</span>' : '<span style="color:red">❌ E absent — $_ENV vide !</span>' ?></td></tr>
<tr><td>auto_globals_jit</td><td><?= ini_get('auto_globals_jit') ?: 'Off' ?></td></tr>
<tr><td>SAPI</td><td><?= php_sapi_name() ?></td></tr>
</table>

<h2>Variables MySQL Railway</h2>
<table>
<tr><th>Variable</th><th>$_SERVER</th><th>$_ENV</th><th>getenv()</th></tr>
<?php
$vars = ['MYSQLHOST', 'MYSQLUSER', 'MYSQLPASSWORD', 'MYSQLDATABASE', 'MYSQLPORT', 'DATABASE_URL', 'MYSQL_URL', 'MYSQL_PUBLIC_URL'];
foreach ($vars as $var):
    $srv = isset($_SERVER[$var]) && $_SERVER[$var] !== '' ? $_SERVER[$var] : null;
    $env = isset($_ENV[$var]) && $_ENV[$var] !== '' ? $_ENV[$var] : null;
    $get = getenv($var) ?: null;
?>
<tr>
    <td><strong><?= $var ?></strong></td>
    <td><?= maskValue($srv) ?></td>
    <td><?= maskValue($env) ?></td>
    <td><?= maskValue($get) ?></td>
</tr>
<?php endforeach; ?>
</table>

<h2>Test de connexion</h2>
<?php
$host = getEnvVar('MYSQLHOST');
$user = getEnvVar('MYSQLUSER');
$pass = getEnvVar('MYSQLPASSWORD');
$db   = getEnvVar('MYSQLDATABASE');
$port = getEnvVar('MYSQLPORT', 3306);

if (empty($host)) {
    $databaseUrl = getEnvVar('DATABASE_URL') ?: getEnvVar('MYSQL_URL');
    if (!empty($databaseUrl)) {
        $parsed = parse_url($databaseUrl);
        $host = $parsed['host'] ?? null;
        $user = $parsed['user'] ?? null;
        $pass = $parsed['pass'] ?? null;
        $port = $parsed['port'] ?? 3306;
        $db   = isset($parsed['path']) ? ltrim($parsed['path'], '/') : null;
        echo "<p>ℹ️ Variables lues depuis DATABASE_URL</p>";
    }
}

if (!empty($host)) {
    $conn = @new mysqli($host, $user, $pass, $db, (int)$port);
    if ($conn->connect_error) {
        echo "<p style='color:red'>❌ Connexion échouée : " . htmlspecialchars($conn->connect_error) . "</p>";
    } else {
        echo "<p style='color:green'>✅ Connexion à la base de données réussie !</p>";
        $conn->close();
    }
} else {
    echo "<p style='color:red'>❌ Impossible de tenter la connexion : MYSQLHOST introuvable.</p>";
}
?>

<p style="color:#888; margin-top:40px">⚠️ Supprimez ce fichier (<code>debug_env.php</code>) une fois le problème résolu.</p>
</body>
</html>
