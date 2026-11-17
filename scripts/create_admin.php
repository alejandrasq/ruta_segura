<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/database.php';

function prompt(string $label): string
{
    fwrite(STDOUT, $label);
    return trim((string) fgets(STDIN));
}

$email = trim($argv[1] ?? prompt('Correo electrónico del administrador: '));
if (strlen($email) > 80 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
    fwrite(STDERR, "Ingresa un correo electrónico válido de hasta 80 caracteres.\n");
    exit(1);
}

$password = prompt('Contraseña (mínimo 12 caracteres): ');
$confirmation = prompt('Confirma la contraseña: ');
if (strlen($password) < 12 || !hash_equals($password, $confirmation)) {
    fwrite(STDERR, "La contraseña debe tener al menos 12 caracteres y ambas entradas deben coincidir.\n");
    exit(1);
}

try {
    $db = getDB();
    $stmt = $db->prepare(
        'INSERT INTO admin_users (username, password_hash) VALUES (:username, :password_hash)'
    );
    $stmt->execute([
        ':username' => $email,
        ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
    ]);

    fwrite(STDOUT, "Cuenta de administrador creada: {$email}\n");
} catch (PDOException $e) {
    fwrite(STDERR, "No se pudo crear la cuenta. Verifica la base de datos y que el usuario no exista.\n");
    exit(1);
}
