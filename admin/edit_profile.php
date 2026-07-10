<?php
require_once "../includes/admin_check.php";
require_once "../config/db.php";

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$pageTitle = "Edit Profile";
$activePage = "profile";
$cssFile = "../assets/css/admin.css";

$user_id = (int) $_SESSION["user_id"];
$message = "";
$messageType = "";

/*
|--------------------------------------------------------------------------
| Load current admin data
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT
        user_id,
        full_name,
        department,
        university_id,
        email,
        username,
        role,
        account_status
    FROM users
    WHERE user_id = :user_id
      AND role = 'Admin'
    LIMIT 1
");
$stmt->execute([
    ":user_id" => $user_id
]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    redirectWithFlash("profile.php", "Admin profile not found.");
}

/*
|--------------------------------------------------------------------------
| Handle update
|--------------------------------------------------------------------------
*/
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $csrfToken = $_POST["csrf_token"] ?? "";

    if (!hash_equals($_SESSION["csrf_token"], $csrfToken)) {
        $message = "Invalid request token.";
        $messageType = "error";
    } else {
        $full_name = trim($_POST["full_name"] ?? "");
        $department = trim($_POST["department"] ?? "");
        $university_id = trim($_POST["university_id"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $username = trim($_POST["username"] ?? "");

        if ($full_name === "" || $email === "" || $username === "") {
            $message = "Full name, email, and username are required.";
            $messageType = "error";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "Please enter a valid email address.";
            $messageType = "error";
        } else {
            $checkStmt = $pdo->prepare("
                SELECT user_id
                FROM users
                WHERE (email = :email OR username = :username)
                  AND user_id <> :user_id
                LIMIT 1
            ");
            $checkStmt->execute([
                ":email" => $email,
                ":username" => $username,
                ":user_id" => $user_id
            ]);

            if ($checkStmt->fetch()) {
                $message = "Email or username is already being used by another account.";
                $messageType = "error";
            } else {
                $updateStmt = $pdo->prepare("
                    UPDATE users
                    SET
                        full_name = :full_name,
                        department = :department,
                        university_id = :university_id,
                        email = :email,
                        username = :username
                    WHERE user_id = :user_id
                      AND role = 'Admin'
                    LIMIT 1
                ");

                $success = $updateStmt->execute([
                    ":full_name" => $full_name,
                    ":department" => $department !== "" ? $department : null,
                    ":university_id" => $university_id !== "" ? $university_id : null,
                    ":email" => $email,
                    ":username" => $username,
                    ":user_id" => $user_id
                ]);

                if ($success) {
                    $_SESSION["full_name"] = $full_name;
                    $_SESSION["email"] = $email;
                    $_SESSION["username"] = $username;

                    $message = "Profile updated successfully.";
                    $messageType = "success";

                    $stmt->execute([
                        ":user_id" => $user_id
                    ]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    $message = "Failed to update profile.";
                    $messageType = "error";
                }
            }
        }
    }
}

require_once "../includes/header.php";
require_once "../includes/admin_sidebar.php";
?>

<div class="main-content">
    <?php require_once "../includes/admin_topbar.php"; ?>

    <main class="page-content">
        <div class="card">
            <h2>Edit Profile</h2>
            <p>Update your admin account information.</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="flash-message <?php echo $messageType === 'success' ? 'flash-success' : 'flash-error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <form method="POST" class="request-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["csrf_token"]); ?>">

                <div class="form-row">
                    <label for="full_name">Full Name</label>
                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        value="<?php echo htmlspecialchars($user["full_name"] ?? ""); ?>"
                        required
                    >
                </div>

                <div class="form-row">
                    <label for="department">Department</label>
                    <input
                        type="text"
                        id="department"
                        name="department"
                        value="<?php echo htmlspecialchars($user["department"] ?? ""); ?>"
                    >
                </div>

                <div class="form-row">
                    <label for="university_id">University ID</label>
                    <input
                        type="text"
                        id="university_id"
                        name="university_id"
                        value="<?php echo htmlspecialchars($user["university_id"] ?? ""); ?>"
                    >
                </div>

                <div class="form-row">
                    <label for="email">Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?php echo htmlspecialchars($user["email"] ?? ""); ?>"
                        required
                    >
                </div>

                <div class="form-row">
                    <label for="username">Username</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        value="<?php echo htmlspecialchars($user["username"] ?? ""); ?>"
                        required
                    >
                </div>

                <div class="form-row">
                    <label>Role</label>
                    <input
                        type="text"
                        value="<?php echo htmlspecialchars($user["role"] ?? "Admin"); ?>"
                        disabled
                    >
                </div>

                <div class="form-row">
                    <label>Account Status</label>
                    <input
                        type="text"
                        value="<?php echo htmlspecialchars($user["account_status"] ?? "N/A"); ?>"
                        disabled
                    >
                </div>

                <div class="profile-actions">
                    <button type="submit" class="admin-btn primary-btn">Save Changes</button>
                    <a href="profile.php" class="admin-btn info-btn">Back to Profile</a>
                </div>
            </form>
        </div>
    </main>
</div>

