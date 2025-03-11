<?php
session_start();

// Reset session variables when reset is triggered
if (isset($_POST['reset'])) {
  $_SESSION['guesses'] = 0;
  $_SESSION['alreadyGuessed'] = array();
  unset($_SESSION['quote']);
  header("Location: index.php");
  exit();
}

// Initialize session variables
if (!isset($_SESSION['alreadyGuessed'])) {
  $_SESSION['alreadyGuessed'] = array();
}

if (!isset($_SESSION['guesses'])) {
  $_SESSION['guesses'] = 0;
}

if (isset($_POST)) {
  $_SESSION = array_merge($_SESSION, $_POST);
}

// Database connection (improved error handling)
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "yogiedle";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
  // Display a user-friendly error message
  echo "Database connection failed: " . $conn->connect_error;
  exit();
}

// Fetch a random quote from the database with error handling
function getRandomQuote($conn) {
  $query = "SELECT quote, video, who, whichHalf FROM quotes ORDER BY RAND() LIMIT 1";
  $result = mysqli_query($conn, $query);

  if ($result === false) {
    die("Database query failed: " . mysqli_error($conn));
  }

  if (mysqli_num_rows($result) > 0) {
    return mysqli_fetch_assoc($result); // Returns associative array
  } else {
    return null; // No quotes found
  }
}

// Fetch all quotes for the dropdown
function getAllQuotes($conn) {
    $query = "SELECT * FROM quotes";
    $result = mysqli_query($conn, $query);
    
    if ($result === false) {
        die("Database query failed: " . mysqli_error($conn));
    }

    return mysqli_fetch_all($result, MYSQLI_ASSOC); // Fetch all quotes
}

// Store all quotes in a variable
$guessQuotes = getAllQuotes($conn);

// Get a random quote if not already set
if (!isset($_SESSION['quote'])) {
    $_SESSION['quote'] = getRandomQuote($conn);
}

// Check if a quote was successfully retrieved
$quote = $_SESSION['quote'] ?? null;

if (!$quote) {
    die("No quote found. Please add quotes to the database.");
}

// Debug: Display the current quote
var_dump($quote); // Check contents
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yogiedle</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-600 flex flex-col items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded shadow-md w-full max-w-lg">
        <h1 class="text-2xl font-bold mb-6 text-center">Yogiedle</h1>

        <p class='text-xl text-center mb-4'>Arvauksia: <?php echo $_SESSION['guesses']; ?></p>

        <div class="mb-6 text-center">
            <?php
            // Display hints based on the number of guesses
            if ($_SESSION['guesses'] >= 3) {
                echo "<p class='text-red-800 text-lg font-semibold mb-4'><strong>Vihje 1:</strong> Video: {$quote['video']}</p>";
            }
            if ($_SESSION['guesses'] >= 5) {
                echo "<p class='text-red-800 text-lg font-semibold mb-4'><strong>Vihje 2:</strong> Kuka: {$quote['who']}</p>";
            }
            if ($_SESSION['guesses'] >= 7) {
                echo "<p class='text-red-800 text-lg font-semibold mb-4'><strong>Vihje 3:</strong> Videon puolisko: {$quote['whichHalf']}</p>";
            }
            ?>
        </div>

        <form action="index.php" method="post" class="space-y-4">
            <div>
                <label for="quote" class="block text-sm font-medium text-gray-700">Päivän quote?</label>
                <select name="quote" id="quote" class="mt-1 block w-full pl-3 pr-10 py-3 text-base border border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md bg-white shadow-md hover:bg-gray-50">
                <?php
                foreach ($guessQuotes as $guessQuote) {
                    // Exclude already guessed quotes
                    if (!in_array($guessQuote['quote'], $_SESSION['alreadyGuessed'])) {
                        echo "<option class='text-gray-700 bg-white hover:bg-gray-100' value='{$guessQuote['quote']}'>{$guessQuote['quote']}</option>";
                    }
                }
                ?>
                </select>
            </div>
            <button type="submit" class="w-full bg-indigo-600 text-white py-2 px-4 rounded hover:bg-indigo-700">Arvaa</button>
        </form>
        <form action="index.php" method="post" class="mt-6 flex justify-center">
            <input type="hidden" name="reset" value="true">
            <button type="submit" class="w-full bg-red-800 text-white py-2 px-4 rounded hover:bg-red-900">Nollaa</button>
        </form>

        <?php
        // Handle guess submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $guess = strtolower(trim($_POST['quote']));

            // Check if the quote exists before comparing
            if (isset($_SESSION['quote'])) {
                $correct = strtolower($_SESSION['quote']['quote']);
                $_SESSION['alreadyGuessed'][] = $guess; // Add the guess to the already guessed array
                $_SESSION['guesses']++; // Increment guess count

                // Check if the guess is correct
                if ($guess === $correct) {
                    echo "<div class='mt-6 text-lg font-semibold text-center text-green-600'>Oikein! Päivän quote oli: \"{$quote['quote']}\"</div>";
                    // Reset game
                    session_unset(); // Clear session data to start a new game
                    header("Location: index.php?new_game=true"); // Redirect to start a new game
                    exit();
                } else {
                    echo "<div class='mt-6 text-lg font-semibold text-center text-red-600'>Väärin! Yritä uudelleen.</div>";

                    // Display the wrong guess feedback
                    echo "<div class='mt-4'>";
                    echo "<p class='text-red-600'>Väärät arvaukset:</p>";
                    foreach ($_SESSION['alreadyGuessed'] as $alreadyGuessed) {
                        // Skip the correct guess
                        if (strtolower($alreadyGuessed) === strtolower($quote['quote'])) {
                            continue;
                        }
                        echo "<p class='text-gray-700'>{$alreadyGuessed}</p>";

                        // Check for hints
                        if (strpos(strtolower($alreadyGuessed), strtolower($quote['video'])) !== false) {
                            echo "<p class='text-green-500'>Väärässä arvauksessa on video oikein!</p>";
                        }
                        if (strpos(strtolower($alreadyGuessed), strtolower($quote['who'])) !== false) {
                            echo "<p class='text-green-500'>Väärässä arvauksessa on kuka oikein!</p>";
                        }
                        if (strpos(strtolower($alreadyGuessed), strtolower($quote['whichHalf'])) !== false) {
                            echo "<p class='text-green-500'>Väärässä arvauksessa on videon puolisko oikein!</p>";
                        }
                    }
                    echo "</div>";
                }
            }
        }
        ?>
        
    </div>
</body>
</html>