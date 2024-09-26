<?php
    require_once( "Lib/lib.php" );
    require_once( "Lib/db.php" );
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['deletePostId'])) {
    $postId = $_POST['deletePostId'] ?? 0;
    $userId = $_SESSION['user_id'] ?? 0;

    if ($postId && $userId) {
        deletePost($postId, $userId);
    } else {
        echo "ID da postagem ou ID do usuário não fornecido.";
    }
}


function fetchMyPosts() {
    dbConnect(ConfigFile);
    $dataBaseName = $GLOBALS['configDataBase']->db;
    mysqli_select_db($GLOBALS['ligacao'], $dataBaseName);

    $userId = $_SESSION['user_id'] ?? 0;
    if ($userId == 0) {
        echo "User ID not found in session.";
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
            Post.user_id = ?
        ORDER BY 
            Post.datetime DESC
    ";
    $stmt = mysqli_prepare($GLOBALS['ligacao'], $query);
    $posts = [];

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $posts[] = $row;
            }
            mysqli_free_result($result);
        } else {
            echo "Não foi possível buscar os resultados: " . mysqli_error($GLOBALS['ligacao']);
        }
        mysqli_stmt_close($stmt);
    } else {
        echo "Erro ao preparar a consulta: " . mysqli_error($GLOBALS['ligacao']);
    }

    dbDisconnect();
    return $posts;
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
    <title>Posts</title>
    <link rel="stylesheet" type="text/css" href="css/style.css">
</head>
<body>
    <div class="posts">
        <?php

        require_once("Post.php");
        $posts = fetchMyPosts();
        if (!empty($posts)) {
            foreach ($posts as $post) {
                displayPost($post);
            }
        } else {
            echo "<p>No posts available</p>";
        }
        ?>
    </div>
</body>
</html>
