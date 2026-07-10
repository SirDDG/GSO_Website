<?php
require_once "../config/db.php";
require_once "../includes/avatar_helper.php";

$user_id = $_SESSION['user_id'];

require_once "../includes/notification_helper.php";

/* GET UNREAD COUNT */
$unreadCount = getUnreadNotificationCount($pdo, $user_id);

/* GET LATEST NOTIFICATIONS */
$notifications = getNotifications($pdo, $user_id, 5);

$avatar = getUserAvatarSource($pdo, (int) $user_id, "../assets/css/img/default.png");

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

if (!function_exists("gsoSafeNotificationLink")) {
    function gsoSafeNotificationLink(?string $link): string
    {
        $link = trim((string) $link);

        if ($link === "" || preg_match("#^(javascript:|data:|https?://)#i", $link)) {
            return "#";
        }

        return $link;
    }
}
?>

<div class="topbar">
    <div class="topbar-clock" data-live-datetime>
        <i class="fa-regular fa-clock"></i>
        <span>Loading date and time...</span>
    </div>

    <div class="topbar-right">
        <div class="quick-actions-wrapper">
            <button type="button" class="topbar-icon-btn quick-action-trigger" data-toggle-quick-actions aria-label="Open quick actions">
                <i class="fa-solid fa-bolt"></i>
                <span>Quick</span>
            </button>

            <div class="quick-actions-dropdown" id="quickActionsDropdown">
                <h4>Quick Actions</h4>
                <a href="browse.php"><i class="fa-solid fa-magnifying-glass"></i><span>Browse Resources</span></a>
                <a href="my_requests.php"><i class="fa-solid fa-clipboard-list"></i><span>Track Requests</span></a>
                <a href="my_borrowed.php"><i class="fa-solid fa-box-open"></i><span>Active Borrowings</span></a>
                <a href="history.php"><i class="fa-solid fa-clock-rotate-left"></i><span>View History</span></a>
            </div>
        </div>
                <!-- 🔔 NOTIFICATION BELL -->
        <div class="notification-wrapper">

            <div class="notification-bell" onclick="toggleNotif()">
                <i class="fa-solid fa-bell"></i>

                <?php if($unreadCount > 0): ?>
                    <span class="notif-badge"><?= $unreadCount ?></span>
                <?php endif; ?>
            </div>

            <!-- DROPDOWN -->
            <div class="notification-dropdown" id="notifDropdown">

                <h4>Notifications</h4>

                <?php if(empty($notifications)): ?>
                    <p class="no-notif">No notifications</p>
                <?php else: ?>
                    <?php foreach($notifications as $notif): ?>

                    <?php
                    $icon = "fa-bell";
                    $bg = "#eff6ff";
                    $color = "#1d4ed8";

                    if($notif['type'] == 'request'){
                        $icon = "fa-file";
                        $bg = "#eff6ff";
                        $color = "#1d4ed8";
                    }
                    elseif($notif['type'] == 'approved'){
                        $icon = "fa-circle-check";
                        $bg = "#dbeafe";
                        $color = "#1e40af";
                    }
                    elseif($notif['type'] == 'rejected'){
                        $icon = "fa-circle-xmark";
                        $bg = "#dbeafe";
                        $color = "#1e3a8a";
                    }
                    elseif($notif['type'] == 'user'){
                        $icon = "fa-user";
                        $bg = "#eff6ff";
                        $color = "#2563eb";
                    }
                    elseif($notif['type'] == 'system'){
                        $icon = "fa-gear";
                        $bg = "#f8fbff";
                        $color = "#1e3a8a";
                    }
                    ?>

                    <a href="<?= htmlspecialchars(gsoSafeNotificationLink($notif['link'] ?? '#'), ENT_QUOTES, 'UTF-8') ?>" class="notif-item" data-notification-id="<?= (int) $notif['notification_id'] ?>">

                        <div class="notif-icon" style="background: <?= $bg ?>; color: <?= $color ?>;">
                            <i class="fa-solid <?= $icon ?>"></i>
                        </div>

                        <div class="notif-content">
                            <strong><?= htmlspecialchars($notif['title']) ?></strong>
                            <p><?= htmlspecialchars($notif['message']) ?></p>
                        </div>

                    </a>

                    <?php endforeach; ?>
                <?php endif; ?>

            </div>
        </div>
                    <div class="topbar-user" onclick="toggleUserMenu()">

                <div class="topbar-avatar">
                    <img src="<?php echo htmlspecialchars($avatar, ENT_QUOTES, "UTF-8"); ?>" alt="User">
                </div>

                <div class="topbar-user-info">
                    <strong><?php echo htmlspecialchars($_SESSION["full_name"]); ?></strong>
                    <small><?php echo htmlspecialchars($_SESSION["role"]); ?></small>
                </div>

                <i class="fa-solid fa-chevron-down dropdown-icon"></i>

                <!-- DROPDOWN -->
                <div class="user-dropdown" id="userDropdown">
                    <div class="user-dropdown-header">
                        <strong><?php echo htmlspecialchars($_SESSION["full_name"]); ?></strong>
                        <p><?php echo htmlspecialchars($_SESSION["email"] ?? "admin@gso.gov"); ?></p>
                    </div>

                    <a href="profile.php" class="logout-btn profile-menu-link">
                        <i class="fa-solid fa-user"></i> Profile
                    </a>

                    <a href="../auth/logout.php" class="logout-btn">
                        <i class="fa-solid fa-right-from-bracket"></i> Logout
                    </a>
                </div>

            </div>
    </div>
</div>

<script>
function toggleNotif(){
    document.getElementById("notifDropdown").classList.toggle("show");
}
</script>
<script>
function toggleUserMenu(){
    const menu = document.getElementById("userDropdown");
    const icon = document.querySelector(".dropdown-icon");

    menu.classList.toggle("show");

    if(menu.classList.contains("show")){
        icon.style.transform = "rotate(180deg)";
    } else {
        icon.style.transform = "rotate(0deg)";
    }
}
</script>
<script>
document.addEventListener("click", function(e){
    const notifItem = e.target.closest(".notif-item");
    if (!notifItem) {
        return;
    }

    const notifId = notifItem.dataset.notificationId;
    if (!notifId) {
        return;
    }

    const payload = new URLSearchParams({
        notification_id: notifId,
        csrf_token: "<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8"); ?>"
    });
    if (navigator.sendBeacon) {
        navigator.sendBeacon('../includes/mark_read.php', payload);
        return;
    }

    fetch('../includes/mark_read.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: payload
    }).catch(() => {});
});
</script>
