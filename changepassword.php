<?php
session_start();
require 'includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


function checkPasswordStrength($password)
{
    $score = 0;

    if (strlen($password) >= 12) $score++;
    if (strlen($password) >= 16) $score++;

    if (preg_match('/[A-Z]/', $password)) $score++;
    if (preg_match('/[a-z]/', $password)) $score++;
    if (preg_match('/[0-9]/', $password)) $score++;
    if (preg_match('/[^A-Za-z0-9]/', $password)) $score++;

    $common = [
        '123456',
        'password',
        'qwerty',
        'welkom123',
        'admin'
    ];

    foreach ($common as $bad) {
        if (stripos($password, $bad) !== false) {
            $score -= 2;
        }
    }

    return $score;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($newPassword !== $confirmPassword) {

        $error = "De nieuwe wachtwoorden komen niet overeen.";

    } elseif ($currentPassword === $newPassword) {

        $error = "Het nieuwe wachtwoord moet verschillen van het huidige wachtwoord.";

    } else {

        $strength = checkPasswordStrength($newPassword);

        if ($strength < 4) {

            $error = "Het nieuwe wachtwoord is niet sterk genoeg. Gebruik minimaal 12 tekens, hoofdletters, kleine letters, cijfers en speciale tekens.";

        } else {

           $username = $_POST['username'] ?? '';

    $stmt = $pdo->prepare("
    SELECT id, password
    FROM user
    WHERE username = ?
");

$stmt->execute([$username]);
$user = $stmt->fetch();

            if (!$user) {
                $error = "Gebruiker niet gevonden.";

            } elseif (!password_verify($currentPassword, $user['password'])) {

                $error = "Huidig wachtwoord is onjuist.";

            } else {

                $newHash = password_hash(
                    $newPassword,
                    PASSWORD_DEFAULT
                );

                $stmt = $pdo->prepare("
                    UPDATE user
                    SET password = ?,
                        password_changed_at = NOW()
                    WHERE id = ?
                ");

                $stmt->execute([
                    $newHash,
                    $user['id']
                ]);

                $success = "Wachtwoord succesvol gewijzigd.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wachtwoord wijzigen</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

<?php include 'includes/header.php'; ?>

<div class="container mx-auto mt-20 p-6 bg-white max-w-sm shadow-md rounded-md">

    <div class="flex justify-center">
        <img
            src="img/Omanido1.png"
            alt="Omanido Logo"
            class="mb-6 w-1/2"
        >
    </div>

    <h2 class="text-lg text-center font-bold mb-6">
        Wachtwoord wijzigen
    </h2>

    <?php if (!empty($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form method="post">

    <div class="mb-4">
        <label for="username" class="block text-sm font-medium text-gray-700">
            Gebruikersnaam
        </label>

        <input
        type="text"
        id="username"
        name="username"
        required
        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3"
        >
    </div>

        <div class="mb-4">
            <label
                for="current_password"
                class="block text-sm font-medium text-gray-700"
            >
                Huidig wachtwoord
            </label>

            <input
                type="password"
                id="current_password"
                name="current_password"
                required
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3"
            >
        </div>

        <div class="mb-4">
            <label
                for="new_password"
                class="block text-sm font-medium text-gray-700"
            >
                Nieuw wachtwoord
            </label>

            <input
                type="password"
                id="new_password"
                name="new_password"
                required
                minlength="12"
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3"
            >
        </div>

        <div class="mb-6">
            <label
                for="confirm_password"
                class="block text-sm font-medium text-gray-700"
            >
                Herhaal nieuw wachtwoord
            </label>

            <input
                type="password"
                id="confirm_password"
                name="confirm_password"
                required
                minlength="12"
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3"
            >
        </div>

        <button
            type="submit"
            class="w-full bg-blue-600 text-white font-bold py-2 px-4 rounded hover:bg-blue-700"
        >
            Wachtwoord wijzigen
        </button>

    </form>

</div>

</body>
</html>