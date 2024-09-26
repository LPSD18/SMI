<?php
    require_once( "Lib/lib.php" );
    require_once( "Lib/db.php" );
session_start();

if (!isset($_SESSION['user_id'])) {
    echo "User is not logged in.";
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username']; // Assuming the username is stored in session as well



function fetchFollowers($user_id) {
    dbConnect(ConfigFile);
    $dataBaseName = $GLOBALS['configDataBase']->db;
    mysqli_select_db($GLOBALS['ligacao'], $dataBaseName);

    $query = "
        SELECT 
            User.id AS user_id,
            User.username AS username,
            User.photo AS user_photo
        FROM 
            Followers
        INNER JOIN 
            User ON Followers.follower_id = User.id
        WHERE 
            Followers.user_id = ?
    ";
    $stmt = mysqli_prepare($GLOBALS['ligacao'], $query);
    $followers = [];
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $followers[] = $row;
            }
            mysqli_free_result($result);
        } else {
            echo "Não foi possível buscar os resultados " . mysqli_error($GLOBALS['ligacao']);
        }
        mysqli_stmt_close($stmt);
    } else {
        echo "Error preparing the statement: " . mysqli_error($GLOBALS['ligacao']);
    }
    dbDisconnect();
    return $followers;
}
$followers = fetchFollowers($user_id);
?>


<!DOCTYPE html>
<html>
<head>
    <meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
    <title>Followers of <?php echo htmlspecialchars($username); ?></title>
    <link rel="stylesheet" type="text/css" href="css/style.css">
</head>
<body>

<div id="containerDiv">

    <div id="headerDiv">
        <?php include_once("header.php") ?>
    </div>

    <div class="main-content">
        <h2>Followers of <?php echo htmlspecialchars($username); ?></h2>
        <div class="followers-list">
            <?php
            if (empty($followers)) {
                echo "<p>No followers found.</p>";
            } else {
                foreach ($followers as $follower) {
                    $userPhoto = $follower['user_photo'] ? 'data:image/jpeg;base64,' . base64_encode($follower['user_photo']) : 'images/pfp.jpg';
                    echo "
                    <div class='follower'>
                        <div class='user-photo'>
                            <img src='$userPhoto' class='pfp' alt='Profile Picture'>
                        </div>
                        <div class='username'>
                            <a href='viewProfile.php?user_id=" . htmlspecialchars($follower['user_id']) . "'>" . htmlspecialchars($follower['username']) . "</a>
                        </div>
                    </div>
                    ";
                }
            }
            ?>
        </div>
    </div>

    <div id="footerDiv" style="height:10%;">
        <?php include_once("footer.php") ?>
    </div>
</div>

</body>
</html>