<?php
    require_once( "Lib/lib.php" );
    require_once( "Lib/db.php" );
session_start();

if (!isset($_POST['user_id'])) {
    echo "User ID is not specified.";
    exit;
}

$user_id = intval($_POST['user_id']);
$current_user_id = $_SESSION['user_id'];

if ($user_id <= 0 || $current_user_id <= 0) {
    echo "Invalid User ID.";
    exit;
}

dbConnect(ConfigFile);
$dataBaseName = $GLOBALS['configDataBase']->db;
mysqli_select_db($GLOBALS['ligacao'], $dataBaseName);

// Check if already following
$query = "SELECT * FROM Followers WHERE user_id = ? AND follower_id = ?";
$stmt = mysqli_prepare($GLOBALS['ligacao'], $query);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $current_user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $is_following = mysqli_fetch_assoc($result) ? true : false;
    mysqli_stmt_close($stmt);
}

if ($is_following) {
    // Unfollow the user
    $query = "DELETE FROM Followers WHERE user_id = ? AND follower_id = ?";
} else {
    // Follow the user
    $query = "INSERT INTO Followers (user_id, follower_id) VALUES (?, ?)";
}

$stmt = mysqli_prepare($GLOBALS['ligacao'], $query);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $current_user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

dbDisconnect();

header("Location: ViewProfile.php?user_id=" . $user_id);
exit;
?>
