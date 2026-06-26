<?php 
session_start();

$pdo = require_once __DIR__ . '/includes/db.php';

include 'includes/userTable.php';
include 'includes/transactionTable.php';

function checkPasswordStrength($password) {
    $score = 0;

    if (strlen($password) >= 12) $score++;
    if (strlen($password) >= 16) $score++;

    if (preg_match('/[A-Z]/', $password)) $score++;
    if (preg_match('/[a-z]/', $password)) $score++;
    if (preg_match('/[0-9]/', $password)) $score++;
    if (preg_match('/[^A-Za-z0-9]/', $password)) $score++;

    $common = ['123456', 'password', 'qwerty', 'welkom123', 'admin'];
    foreach ($common as $bad) {
        if (stripos($password, $bad) !== false) {
            $score -= 2;
        }
    }

    return $score;
}

if($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

if (empty($username) || empty($password)) {
    $_SESSION['login_error'] =
        "Vul een gebruikersnaam en wachtwoord in.";
    header("Location: index.php");
    exit;
}

$sql = "SELECT * FROM user WHERE username = :username";
$stmt = $pdo->prepare($sql);
$stmt->execute([':username' => $username]);

$user = $stmt->fetch();


if ($user && !empty($user['locked_until']) && strtotime($user['locked_until']) > time()) {
    $_SESSION['login_error'] = "Account is tijdelijk geblokkeerd. Probeer later opnieuw.";
    header("Location: index.php");
    exit;
}


if ($user) {

    $isHashed = password_get_info($user['password'])['algo'] !== 0;

    if ($isHashed && password_verify($password, $user['password'])) {

        $loginOk = true;
    } elseif (!$isHashed && $password === $user['password']) {

        $newHash = password_hash($password, PASSWORD_DEFAULT);

        $update = $pdo->prepare
        ("UPDATE user SET password = ? WHERE id = ?"
    );

        $update->execute([$newHash, $user['id']]);

        $loginOk = true;

    } else {

        $loginOk = false;
    }

    if ($loginOk) {

if ($user['username'] === 'owner') {
    $_SESSION['owner_login_notice'] = "De eigenaar is ingelogd.";
}
  session_regenerate_id(true);


$stmt = $pdo->prepare("
    UPDATE user
    SET failed_attempts = 0
    WHERE id = ?
");
$stmt->execute([$user['id']]);

  $score = checkPasswordStrength($password);

  if ($score < 4) {
    $_SESSION['weak_password_warning'] = true;
  }

    $_SESSION['loggedin'] = true;
    $_SESSION['username'] = $user['username'];
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['isAdmin'] = $user['isAdmin'];
   

    header ("Location: dashboard.php");
    exit;
    
} else {

    $update = $pdo->prepare("
    UPDATE user
    SET failed_attempts = failed_attempts + 1,
    last_failed_attempt = NOW()
    WHERE id = ?
    ");
    $update->execute([$user['id']]);


    $stmt = $pdo->prepare("SELECT failed_attempts, email FROM user WHERE id = ?");
    $stmt->execute([$user['id']]);
    $data = $stmt->fetch();

   if ($data['failed_attempts'] >= 5) {

    $_SESSION['login_error'] =
        "Te veel mislukte pogingen. De eigenaar is op de hoogte gebracht.";

    $_SESSION['owner_notified'] = true;

    try {
        $log = $pdo->prepare("
        INSERT INTO security_logs (user_id, message, created_at)
        VALUES (?, ?, NOW())
        ");
        $log->execute([
            $user['id'],
            '5 mislukte loginpogingen'
        ]);
    } catch (PDOException $e) {

    }



} else {
    $_SESSION['login_error'] =
        "Gebruikersnaam of wachtwoord is onjuist";
}

header("Location: index.php");
exit;



}

} else {
       $_SESSION['login_error'] =
        "Gebruikersnaam of wachtwoord is onjuist";

    header("Location: index.php");
    exit;
}

}


?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Omanido</title>
    <!-- Voeg Tailwind CSS toe via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <?php include 'includes/header.php'; ?>

    <div class="container mx-auto mt-20 p-6 bg-white max-w-sm shadow-md rounded-md">
        <div class="flex justify-center">
            <img src="img/Omanido1.png" alt="Omanido Logo" class="mb-6 w-1/2"> <!-- Aanpassen van de breedte naar 1/2 van de container -->
        </div>
        <h2 class="text-lg text-center font-bold mb-6">Inloggen bij Omanido</h2>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"], ENT_QUOTES, 'UTF-8');  ?>" method="post">

          <?php if (!empty($_SESSION['login_error'])) { ?>
            <p class="text-red-600 text-center mb-4">
                <?php
                echo $_SESSION['login_error'];
                ?>
            </p>
            <?php } ?>
            
          

            <div class="mb-4">
                <label for="username" class="block text-sm font-medium text-gray-700">Gebruikersnaam:</label>
                <input type="text" id="username" name="username" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div class="mb-6">
                <label for="password" class="block text-sm font-medium text-gray-700">Wachtwoord:</label>
                <input type="password" id="password" name="password" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <input type="submit" value="Inloggen" class="w-full bg-blue-600 text-white font-bold py-2 px-4 rounded hover:bg-blue-700 focus:outline-none focus:shadow-outline">
        </form>
        <a href="register.php" class="block text-center text-sm text-blue-600 hover:underline mt-4">Nog geen account? Registreer hier</a>
       
    </div>
    


    
</body>
</html>
