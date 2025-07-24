<?php
/**
 * login.php
 *
 * Handles user authentication for the Business First English Center application.
 * - Accepts POST requests with username and password.
 * - Verifies credentials securely against the database.
 * - Implements session-based brute force protection.
 * - Uses secure cookie settings and regenerates session ID on login.
 * - Returns JSON responses for all outcomes (success, failure, lockout).
 *
 * PHP version 7+
 *
 * @package    BusinessFirstEnglishCenter
 * @author     Jonathan Ray Hendrix <jrhendrixdev@gmail.com>
 * @license    MIT License
 */

// ===================== SECURITY: SESSION COOKIE SETTINGS =====================
ini_set('session.cookie_httponly', 1);           // Prevents JS access to session cookie
ini_set('session.cookie_secure', 1);             // Set to 1 only if using HTTPS

session_set_cookie_params([
    'lifetime' => 0,                             // Session cookie expires on browser close
    'path'     => '/',
    'secure'   => true,                          // Enable for HTTPS
    'httponly' => true,
    'samesite' => 'Lax'                          // Helps mitigate CSRF
]);

session_start();

// ===================== DEPENDENCIES & HEADERS =====================
require_once __DIR__ . '/../src/models/Database.php';
header('Content-Type: application/json');

// ===================== FUNCTIONS =====================

/**
 * Sends a JSON response and exits the script.
 *
 * @param array $data JSON-encodable associative array.
 * @return void
 */
function send_json_response(array $data): void {
    echo json_encode($data);
    exit;
}

/**
 * Main login logic with brute force protection.
 *
 * Limits login attempts to 5 tries within 5 minutes.
 * After a successful login, resets brute force counters.
 *
 * @return void
 */
function handle_login(): void {
    $max_attempts = 5;   // Maximum failed attempts allowed
    $lockout_time = 300; // Lockout duration in seconds (5 minutes)

    // Initialize tracking session variables
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
        $_SESSION['last_attempt_time'] = 0;
    }

    // Lockout check
    if ($_SESSION['login_attempts'] >= $max_attempts) {
        $elapsed = time() - $_SESSION['last_attempt_time'];
        if ($elapsed < $lockout_time) {
            $wait = $lockout_time - $elapsed;
            send_json_response([
                'success' => false,
                'message' => "Demasiados intentos fallidos. Intenta de nuevo en $wait segundos.",
                'wait'    => $wait
            ]);
        } else {
            $_SESSION['login_attempts'] = 0;
        }
    }

    // Check for required POST params
    if (empty($_POST['username']) || empty($_POST['password'])) {
        send_json_response([
            'success' => false,
            'message' => 'Datos incompletos'
        ]);
    }

    // === Database lookup ===
    $con = Database::connect();
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $con->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    // === Verify Password ===
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['pword'])) {
            // SUCCESS: Reset brute force tracking
            $_SESSION['login_attempts'] = 0;
            $_SESSION['last_attempt_time'] = 0;

            // Regenerate session ID to prevent fixation
            session_regenerate_id(true);

            // Store user session data
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user']    = $user['username'];
            $_SESSION['lvl']     = $user['ulevel'];
            $_SESSION['curso']   = $user['class'];
            $_SESSION['login']   = true;

            if ($user['ulevel'] == 2) {
                $_SESSION['curso'] = $user['class'];
            }

            send_json_response(['success' => true]);
        }
    }

    // === Failed login ===
    $_SESSION['login_attempts'] += 1;
    $_SESSION['last_attempt_time'] = time();

    send_json_response([
        'success' => false,
        'message' => 'Usuario o contraseña incorrectos'
    ]);
}

// ===================== MAIN EXECUTION =====================
handle_login();

// === Optional login expiration redirect (only applies in session expiration scenarios) ===
// This block may never run because handle_login() ends with `exit()`, but kept for safety.
//if (!handle_login()) {
//    header("Location: login.php?expired=1");
//    exit();
//}
