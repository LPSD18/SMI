<?php
    require_once( "Lib/lib.php" );
    require_once( "Lib/db.php" );
session_start();

if (!isset($_GET['user_id'])) {
    echo "User ID is not specified.";
    exit;
}

$user_id = intval($_GET['user_id']);
if ($user_id <= 0) {
    echo "Invalid User ID.";
    exit;
}

$current_user_id = $_SESSION['user_id']; // Assuming you have the current user's ID stored in the session

dbConnect(ConfigFile);
$dataBaseName = $GLOBALS['configDataBase']->db;
mysqli_select_db($GLOBALS['ligacao'], $dataBaseName);

// Fetch user information
$query = "SELECT * FROM User WHERE id = ?";
$stmt = mysqli_prepare($GLOBALS['ligacao'], $query);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $profileUser = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

if (!$profileUser) {
    echo "User not found.";
    dbDisconnect();
    exit;
}

// Fetch followers count
$query = "SELECT COUNT(*) as followers_count FROM Followers WHERE user_id = ?";
$stmt = mysqli_prepare($GLOBALS['ligacao'], $query);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $followers_data = mysqli_fetch_assoc($result);
    $followers_count = $followers_data['followers_count'];
    mysqli_stmt_close($stmt);
}

// Check if current user is following this user
$is_following = false;
if ($current_user_id != $user_id) {
    $query = "SELECT * FROM Followers WHERE user_id = ? AND follower_id = ?";
    $stmt = mysqli_prepare($GLOBALS['ligacao'], $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ii", $user_id, $current_user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $is_following = mysqli_fetch_assoc($result) ? true : false;
        mysqli_stmt_close($stmt);
    }
}

dbDisconnect();

function fetchPostsByUserId($user_id) {
    dbConnect(ConfigFile);
    $dataBaseName = $GLOBALS['configDataBase']->db;
    mysqli_select_db($GLOBALS['ligacao'], $dataBaseName);

    $user_id = intval($user_id);
    if ($user_id <= 0) {
        echo "Invalid User ID for fetching posts.";
        return [];
    }

    $query = "
        SELECT 
            Post.id AS post_id,
            Post.text AS post_text,
            Post.image AS post_image,
            Post.video AS post_video,
            Post.likes AS post_likes,
            Post.datetime AS post_datetime,
            User.id AS user_id,
            User.username AS username,
            User.photo AS user_photo
        FROM 
            Post
        INNER JOIN 
            User ON Post.user_id = User.id
        WHERE 
            User.id = ?
        ORDER BY 
            Post.datetime DESC
    ";
    $stmt = mysqli_prepare($GLOBALS['ligacao'], $query);
    $posts = [];
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $posts[] = $row;
                }
                mysqli_free_result($result);
            } else {
                echo "Error fetching results: " . mysqli_error($GLOBALS['ligacao']);
            }
        } else {
            echo "Error executing statement: " . mysqli_error($GLOBALS['ligacao']);
        }
        mysqli_stmt_close($stmt);
    } else {
        echo "Error preparing the statement: " . mysqli_error($GLOBALS['ligacao']);
    }
    dbDisconnect();
    return $posts;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
    <title>Profile of <?php echo htmlspecialchars($profileUser['username']); ?></title>
    <link rel="stylesheet" type="text/css" href="css/style.css">
    <script type="text/javascript" src="./scripts/forms.js"></script>
</head>
<body>
    <div id="containerDiv">
        <div id="headerDiv">
            <?php include_once("header.php") ?>
        </div>
        <div class="user-info">
            <div class="pfp">
                <?php if (!empty($profileUser['photo'])): ?>
                    <img src="data:image/jpeg;base64,<?php echo base64_encode($profileUser['photo']); ?>" alt="Profile Picture" class="pfp">
                <?php else: ?>
                    <img src="images/pfp.jpg" alt="Default Profile Picture" class="pfp">
                <?php endif; ?>
            </div>
            <div class="username"><?php echo htmlspecialchars($profileUser['username']); ?></div>
            <div class="followers">Followers: <?php echo $followers_count; ?></div>
            <?php if ($current_user_id != $user_id): ?>
                <form action="followUser.php" method="post">
                    <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                    <button type="submit"><?php echo $is_following ? 'Unfollow' : 'Follow'; ?></button>
                </form>
            <?php endif; ?>
        </div>
        <div class="main-content">
            <div id="tagsDiv">
                <?php include_once("tags_sidebar.php") ?>
            </div>
            <div class="posts-wrapper">
                <div class="posts">
                    <?php
                    require_once("Post.php");
                    $posts = fetchPostsByUserId($user_id);
                    foreach ($posts as $post) {
                        displayPost($post);
                    }
                    ?>
                </div>
            </div>
            <div id="usersDiv">
                <?php include_once("suggested_users_sidebar.php") ?>
            </div>
        </div>
        <div id="footerDiv">
            <?php include_once("footer.php") ?>
        </div>
    </div>
</body>
</html>