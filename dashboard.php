<?php
session_start();
$pdo = require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';



if(!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true){
    header("location: /Omanido/secureprogramminglessons/index.php");
    exit;
}


$showOwnerNotice = !empty($_SESSION['owner_notified']);
unset($_SESSION['owner_notified']);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


// als button is ingedrukt
if($_SERVER["REQUEST_METHOD"] == "POST"){

    $ontvangerNaam = trim($_POST['ontvanger'] ?? '');


    if (empty($ontvangerNaam)) {
        $error = "Voer een ontvanger in.";
    }


    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        die("Ongeldige aanvraag.");
    }



    $bedrag = filter_input(INPUT_POST, 'bedrag', FILTER_VALIDATE_FLOAT);

    if ($bedrag === false || $bedrag <= 0) {
        $error = "Voer een geldig positief bedrag in.";
    } elseif ($bedrag > 5000) {
        $error = "Je kunt maximaal €5000 per transactie overmaken";
    }

$omschrijving = trim(filter_input(INPUT_POST, 'omschrijving', FILTER_SANITIZE_FULL_SPECIAL_CHARS));

    if (empty($omschrijving)) {
    $error = "Omschrijving is verplicht.";
} elseif (strlen($omschrijving) > 500) {
    $error = "Omschrijving mag maximaal 500 tekens bevatten.";
}


if (!isset($error)) {

    $stmt = $pdo->prepare("SELECT * FROM user WHERE username = ?");
    $stmt->execute([$ontvangerNaam]);
    $ontvanger = $stmt->fetch();

    // STOP immediately if user invalid
    if (!$ontvanger) {
        $error = "Deze gebruiker bestaat niet";
    } elseif ($ontvanger['id'] == $_SESSION['user_id']) {
        $error = "Je kunt geen geld naar jezelf overmaken";
    }

    // ONLY continue if still no error
    if (!isset($error)) {

        try {
            $pdo->beginTransaction();

            // lock sender balance
            $stmt = $pdo->prepare("SELECT balance FROM user WHERE id = ? FOR UPDATE");
            $stmt->execute([$_SESSION['user_id']]);
            $senderBalance = $stmt->fetchColumn();

            if ($senderBalance < $bedrag) {
                $error = "Je hebt niet genoeg saldo om dit bedrag over te maken";
                $pdo->rollBack();
            } else {

                // get receiver balance
                $stmt = $pdo->prepare("SELECT balance FROM user WHERE id = ?");
                $stmt->execute([$ontvanger['id']]);
                $receiverBalance = $stmt->fetchColumn();

                // update balances
                $stmt = $pdo->prepare("UPDATE user SET balance = ? WHERE id = ?");
                $stmt->execute([$receiverBalance + $bedrag, $ontvanger['id']]);

                $stmt = $pdo->prepare("UPDATE user SET balance = ? WHERE id = ?");
                $stmt->execute([$senderBalance - $bedrag, $_SESSION['user_id']]);

                // insert transaction
                $stmt = $pdo->prepare("
                    INSERT INTO transaction (sender, receiver, amount, description)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([
                    $_SESSION['user_id'],
                    $ontvanger['id'],
                    $bedrag,
                    $omschrijving
                ]);

                $pdo->commit();
                $success = "Het bedrag is succesvol overgemaakt";
            }

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Er is iets misgegaan tijdens de transactie.";
        }
    }
}


}




// Haal het saldo van de ingelogde gebruiker op
$stmt = $pdo->prepare("SELECT balance FROM user WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$saldo = $stmt->fetchColumn();





?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Omanido</title>
    <!-- Voeg Tailwind CSS toe via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <?php include 'includes/header.php'; ?>
    
    <?php if ($showOwnerNotice): ?>
    <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-800 p-4 mb-4 rounded">
        ⚠️ Er zijn meerdere mislukte inlogpogingen gedetecteerd. De eigenaar is op de hoogte gebracht.
    </div>
<?php endif; ?>

    <div class="container mx-auto p-4">
        <div class="flex flex-wrap -mx-2">


            <!-- Saldo Kaart -->
            <div class="w-full md:w-1/3 px-2 mb-4">
                <div class="bg-white p-6 rounded-lg shadow-md h-full flex flex-col justify-between">
                    <div>
                        <h3 class="font-bold text-xl mb-2">Mijn Saldo</h3>
                        <p class="text-sm text-gray-600 mb-4">Actueel Beschikbaar Saldo</p>
                    </div>
                    <p class="text-4xl font-bold mb-4 <?php echo $saldo >= 0 ? 'text-green-500' : 'text-red-500'; ?> self-center">
                        €<?php echo number_format($saldo, 2, ',', '.'); ?>
                    </p>
                    <div class="text-center">
                        <a href="transacties.php" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                            Transactieoverzicht
                        </a>
                    </div>
                </div>
            </div>

            


            <!-- Overdrachtsformulier Kaart -->
            <div class="w-full md:w-2/3 px-2 mb-4">
                <div class="bg-white p-6 rounded-lg shadow-md h-full"> <!-- Verhoogde padding van p-4 naar p-6 -->
                    <h3 class="font-bold text-xl mb-4">Geld Overmaken</h3>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) ?>" method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="mb-4">
                            <label for="ontvanger" class="block text-sm font-medium text-gray-700">Ontvanger:</label>
                            <input type="text" id="ontvanger" name="ontvanger" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
                        </div>
                        <div class="mb-4">
                            <label for="bedrag" class="block text-sm font-medium text-gray-700">Bedrag(€):</label>
                            <input type="number" id="bedrag" name="bedrag" step="0.01" min="0.01" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
                        </div>
                        <div class="mb-4">
                            <label for="omschrijving" class="block text-sm font-medium text-gray-700">Omschrijving:</label>
                            <input type="text" id="omschrijving" name="omschrijving" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
                        </div>
                        <input type="submit" value="Overmaken" class="w-full bg-blue-600 text-white font-bold py-2 px-4 rounded hover:bg-blue-700 focus:outline-none focus:shadow-outline">
                        <?php
                            if(isset($error)) {
                                echo '<p class="text-red-500 text-sm mt-2">' .
                                htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . 
                                '</p>';
                            }
                            if(isset($success)) {
                                echo '<p class="text-green-500 text-sm mt-2">' . 
                                htmlspecialchars($success, ENT_QUOTES, 'UTF-8') . 
                                '</p>';
                            }
                        ?>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
