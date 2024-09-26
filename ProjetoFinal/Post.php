<?php
    require_once( "Lib/lib.php" );
    require_once( "Lib/db.php" );

session_start();


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['deletePostId'])) {
        $postId = $_POST['deletePostId'] ?? 0;
        $userId = $_SESSION['user_id'] ?? 0;

        if ($postId && $userId) {
            deletePost($postId, $userId);
        } else {
            echo "ID da postagem ou ID do usuário não fornecido.";
        }
    } elseif (isset($_POST['commentPostId']) && isset($_POST['commentText'])) {
        $postId = $_POST['commentPostId'];
        $userId = $_SESSION['user_id'];
        $commentText = $_POST['commentText'];

        if ($postId && $userId && $commentText) {
            commentOnPost($postId, $userId, $commentText);
        } else {
            echo "Erro ao enviar comentário.";
        }
    } elseif (isset($_POST['postId'])) {
        $postId = $_POST['postId'];
        $userId = $_SESSION['user_id'];

        if ($postId && $userId) {
            toggleLikePost($postId, $userId);

            // Fetch the updated likes count and user liked status
            dbConnect(ConfigFile);
            $dataBaseName = $GLOBALS['configDataBase']->db;
            mysqli_select_db($GLOBALS['ligacao'], $dataBaseName);

            $query = "SELECT likes FROM Post WHERE id = ?";
            $stmt = mysqli_prepare($GLOBALS['ligacao'], $query);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $postId);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_bind_result($stmt, $likes);
                mysqli_stmt_fetch($stmt);
                mysqli_stmt_close($stmt);

                $userLiked = checkIfUserLiked($postId, $userId);

                echo json_encode(['likes' => $likes, 'userLiked' => $userLiked]);
            }
            dbDisconnect();
            exit;
        } else {
            echo "Erro ao curtir a postagem.";
        }
    }
}

function deletePost($postId, $userId) {
    dbConnect(ConfigFile);
    $dataBaseName = $GLOBALS['configDataBase']->db;
    mysqli_select_db($GLOBALS['ligacao'], $dataBaseName);

    // Check if the user is an admin or the owner of the post
    $query = "SELECT User.type, Post.user_id FROM User 
              JOIN Post ON Post.user_id = User.id WHERE User.id = ? AND Post.id = ?";
    $stmt = mysqli_prepare($GLOBALS['ligacao'], $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ii", $userId, $postId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $userType, $postUserId);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        if ($_SESSION['userType'] === 'admin' || $postUserId == $userId) {
            // Delete likes associated with the post
            $query = "DELETE FROM Interactions WHERE post_id = ?";
            $stmt = mysqli_prepare($GLOBALS['ligacao'], $query);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $postId);
                if (!mysqli_stmt_execute($stmt)) {
                    echo "Error deleting interactions: " . mysqli_stmt_error($stmt);
                    mysqli_stmt_close($stmt);
                    dbDisconnect();
                    return;
                }
                mysqli_stmt_close($stmt);
            } else {
                echo "Error preparing query to delete interactions: " . mysqli_error($GLOBALS['ligacao']);
                dbDisconnect();
                return;
            }

            // Delete comments associated with the post
            $query = "DELETE FROM Comment WHERE post_id = ?";
            $stmt = mysqli_prepare($GLOBALS['ligacao'], $query);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $postId);
                if (!mysqli_stmt_execute($stmt)) {
                    echo "Error deleting comments: " . mysqli_stmt_error($stmt);
                    mysqli_stmt_close($stmt);
                    dbDisconnect();
                    return;
                }
                mysqli_stmt_close($stmt);
            } else {
                echo "Error preparing query to delete comments: " . mysqli_error($GLOBALS['ligacao']);
                dbDisconnect();
                return;
            }

            // Delete post tags associated with the post
            $query = "DELETE FROM PostTags WHERE post_id = ?";
            $stmt = mysqli_prepare($GLOBALS['ligacao'], $query);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $postId);
                if (!mysqli_stmt_execute($stmt)) {
                    echo "Error deleting post tags: " . mysqli_stmt_error($stmt);
                    mysqli_stmt_close($stmt);
                    dbDisconnect();
                    return;
                }
                mysqli_stmt_close($stmt);
            } else {
                echo "Error preparing query to delete post tags: " . mysqli_error($GLOBALS['ligacao']);
                dbDisconnect();
                return;
            }

            // Delete the post itself
            $query = "DELETE FROM Post WHERE id = ?";
            $stmt = mysqli_prepare($GLOBALS['ligacao'], $query);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $postId);
                if (mysqli_stmt_execute($stmt)) {
                } else {
                    echo "Error deleting post: " . mysqli_stmt_error($stmt);
                }
                mysqli_stmt_close($stmt);
            } else {
                echo "Error preparing query to delete post: " . mysqli_error($GLOBALS['ligacao']);
            }
        } else {
            echo "You do not have permission to delete this post.";
        }
    } else {
        echo "Error preparing query to check user type and post owner: " . mysqli_error($GLOBALS['ligacao']);
    }

    dbDisconnect();
}


function displayPost($post) {
    $userPhoto = $post['user_photo'] ? 'data:image/jpeg;base64,' . base64_encode($post['user_photo']) : 'images/pfp.jpg';
    $postImage = $post['post_image'] ? '<img src="data:image/jpeg;base64,' . base64_encode($post['post_image']) . '" class="post-image" alt="Post Image">' : '';
    $postVideo = $post['post_video'] ? '<video controls><source src="data:video/mp4;base64,' . base64_encode($post['post_video']) . '" type="video/mp4">Your browser does not support the video tag.</video>' : '';

    // Add delete button if the current user owns the post or is an admin
    $deleteButton = '';
    
    if ($_SESSION['user_id'] === $post['user_id'] || $_SESSION['userType'] === 'admin') {
        $deleteButton = "<form method='POST' action='' onsubmit='return confirm(\"Are you sure you want to delete this post?\");'>
                            <input type='hidden' name='deletePostId' value='" . htmlspecialchars($post['post_id']) . "'>
                            <button type='submit' class='button delete-btn'>Delete</button>
                         </form>";
    }

    // Check if the user has liked the post
    $userLiked = isset($_SESSION['user_id']) ? checkIfUserLiked($post['post_id'], $_SESSION['user_id']) : false;

    // Fetch comments for this post
    $comments = fetchComments($post['post_id']);
    $commentsHtml = '';
    foreach ($comments as $comment) {
        $commentsHtml .= "<div class='comment'>
                            <div class='comment-user'>" . htmlspecialchars($comment['username']) . "</div>
                            <div class='comment-text'>" . htmlspecialchars($comment['text']) . "</div>
                          </div>";
    }

    $likeButton = isset($_SESSION['user_id']) ? "<button onclick='toggleLike(" . htmlspecialchars($post['post_id']) . ")' id='like-btn-" . htmlspecialchars($post['post_id']) . "' class='button'>" . ($userLiked ? 'Dislike' : 'Like') . "</button>" : '';

    $commentForm = isset($_SESSION['user_id']) ? "<form method='POST' action=''>
                                                    <input type='hidden' name='commentPostId' value='" . htmlspecialchars($post['post_id']) . "'>
                                                    <textarea name='commentText' required></textarea>
                                                    <button type='submit' class='button'>Add Comment</button>
                                                  </form>" : '';

    echo "
    <div class='post'>
        <div class='user-info'>
            <div class='user-photo'>
                <img src='$userPhoto' class='pfp' alt='Profile Picture'>
            </div>
            <div class='username'>
                <a href='viewProfile.php?user_id=" . htmlspecialchars($post['user_id']) . "'>" . htmlspecialchars($post['username']) . "</a>
            </div>
        </div>
        <p>" . htmlspecialchars($post['post_text']) . "</p>
        
        $postImage
        $postVideo
        <div class='post-likes'>Likes: <span id='likes-count-" . htmlspecialchars($post['post_id']) . "'>" . htmlspecialchars($post['post_likes']) . "</span></div>
        <div class='post-datetime'>" . htmlspecialchars($post['post_datetime']) . "</div>
        $deleteButton
        $likeButton
        <div class='comments-section'>
            $commentsHtml
            $commentForm
        </div>
    </div>";
}



function fetchPosts()
{
    dbConnect(ConfigFile);
    $dataBaseName = $GLOBALS['configDataBase']->db;
    mysqli_select_db($GLOBALS['ligacao'], $dataBaseName);

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
        ORDER BY 
            Post.datetime DESC
    ";
    $result = mysqli_query($GLOBALS['ligacao'], $query);

    $posts = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $posts[] = $row;
        }
        mysqli_free_result($result);
    } else {
        echo "Não foi possivel buscar os resultados " . mysqli_error($GLOBALS['ligacao']);
    }
    dbDisconnect();
    return $posts;
}



function fetchPostsBySearch($searchQuery)
{
    dbConnect(ConfigFile);
    $dataBaseName = $GLOBALS['configDataBase']->db;
    mysqli_select_db($GLOBALS['ligacao'], $dataBaseName);

    $query = "
        SELECT 
            Post.id AS post_id,
            Post.text AS post_text,
            Post.image AS post_image,
            Post.video AS post_video,
            Post.likes AS post_likes,
            Post.datetime AS post_datetime,
            User.username AS username,
            User.photo AS user_photo
        FROM 
            Post
        INNER JOIN 
            User ON Post.user_id = User.id
        LEFT JOIN
            PostTags ON Post.id = PostTags.post_id
        LEFT JOIN
            Tags ON PostTags.tag_id = Tags.id
        WHERE 
            Post.text LIKE ? 
            OR User.username LIKE ? 
            OR Tags.name LIKE ?
        GROUP BY 
            Post.id
        ORDER BY 
            Post.datetime DESC
    ";

    $stmt = mysqli_prepare($GLOBALS['ligacao'], $query);
    if ($stmt) {
        $likeQuery = "%$searchQuery%";
        mysqli_stmt_bind_param($stmt, "sss", $likeQuery, $likeQuery, $likeQuery);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $posts = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $posts[] = $row;
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
    return $posts;
}

function fetchPostsByTag($tag)
{
    dbConnect(ConfigFile);
    $dataBaseName = $GLOBALS['configDataBase']->db;
    mysqli_select_db($GLOBALS['ligacao'], $dataBaseName);

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
        INNER JOIN 
            PostTags ON Post.id = PostTags.post_id
        INNER JOIN 
            Tags ON PostTags.tag_id = Tags.id
        WHERE 
            Tags.name = ?
        ORDER BY 
            Post.datetime DESC
    ";

    $stmt = mysqli_prepare($GLOBALS['ligacao'], $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $tag);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $posts = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $posts[] = $row;
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
    return $posts;
}


function commentOnPost($postId, $userId, $commentText)
{
    dbConnect(ConfigFile);
    $dataBaseName = $GLOBALS['configDataBase']->db;
    mysqli_select_db($GLOBALS['ligacao'], $dataBaseName);

    $query = "INSERT INTO Comment (post_id, user_id, text) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($GLOBALS['ligacao'], $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "iis", $postId, $userId, $commentText);
        if (mysqli_stmt_execute($stmt)) {
        } else {
            echo "Erro ao adicionar comentário: " . mysqli_stmt_error($stmt);
        }
        mysqli_stmt_close($stmt);
    } else {
        echo "Erro ao preparar a consulta: " . mysqli_error($GLOBALS['ligacao']);
    }

    dbDisconnect();
}

function fetchComments($postId)
{
    dbConnect(ConfigFile);
    $dataBaseName = $GLOBALS['configDataBase']->db;
    mysqli_select_db($GLOBALS['ligacao'], $dataBaseName);

    $query = "
        SELECT 
            Comment.text AS text,
            User.username AS username
        FROM 
            Comment
        INNER JOIN 
            User ON Comment.user_id = User.id
        WHERE 
            Comment.post_id = ?
        ORDER BY 
            Comment.id ASC
    ";

    $stmt = mysqli_prepare($GLOBALS['ligacao'], $query);
    $comments = [];
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $postId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $comments[] = $row;
            }
            mysqli_free_result($result);
        } else {
            echo "Não foi possível buscar os comentários " . mysqli_error($GLOBALS['ligacao']);
        }
        mysqli_stmt_close($stmt);
    } else {
        echo "Error preparing the statement: " . mysqli_error($GLOBALS['ligacao']);
    }

    dbDisconnect();
    return $comments;
}

function toggleLikePost($postId, $userId) {
    dbConnect(ConfigFile);
    $dataBaseName = $GLOBALS['configDataBase']->db;
    mysqli_select_db($GLOBALS['ligacao'], $dataBaseName);

    // Check if the user already liked the post
    $query = "SELECT * FROM Interactions WHERE user_id = ? AND post_id = ?";
    $stmt = mysqli_prepare($GLOBALS['ligacao'], $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ii", $userId, $postId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            // User already liked the post, so unlike it
            mysqli_stmt_close($stmt);
            $query = "DELETE FROM Interactions WHERE user_id = ? AND post_id = ?";
            $stmt = mysqli_prepare($GLOBALS['ligacao'], $query);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "ii", $userId, $postId);
                if (mysqli_stmt_execute($stmt)) {
                    // Decrement the likes count
                    $query = "UPDATE Post SET likes = likes - 1 WHERE id = ?";
                    $stmt = mysqli_prepare($GLOBALS['ligacao'], $query);
                    if ($stmt) {
                        mysqli_stmt_bind_param($stmt, "i", $postId);
                        mysqli_stmt_execute($stmt);
                    }
                    mysqli_stmt_close($stmt);
                }
            }
        } else {
            // User has not liked the post, so like it
            mysqli_stmt_close($stmt);
            $query = "INSERT INTO Interactions (user_id, post_id) VALUES (?, ?)";
            $stmt = mysqli_prepare($GLOBALS['ligacao'], $query);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "ii", $userId, $postId);
                if (mysqli_stmt_execute($stmt)) {
                    // Increment the likes count
                    $query = "UPDATE Post SET likes = likes + 1 WHERE id = ?";
                    $stmt = mysqli_prepare($GLOBALS['ligacao'], $query);
                    if ($stmt) {
                        mysqli_stmt_bind_param($stmt, "i", $postId);
                        mysqli_stmt_execute($stmt);
                    }
                    mysqli_stmt_close($stmt);
                }
            }
        }
    }
    dbDisconnect();
}
function checkIfUserLiked($postId, $userId) {
    dbConnect(ConfigFile);
    $dataBaseName = $GLOBALS['configDataBase']->db;
    mysqli_select_db($GLOBALS['ligacao'], $dataBaseName);

    $query = "SELECT * FROM Interactions WHERE user_id = ? AND post_id = ?";
    $stmt = mysqli_prepare($GLOBALS['ligacao'], $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ii", $userId, $postId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        $liked = mysqli_stmt_num_rows($stmt) > 0;
        mysqli_stmt_close($stmt);
        dbDisconnect();
        return $liked;
    } else {
        echo "Erro ao preparar a consulta para verificar a interação: " . mysqli_error($GLOBALS['ligacao']);
        dbDisconnect();
        return false;
    }
}
?>
<script>
function toggleLike(postId) {
    const likeButton = document.getElementById('like-btn-' + postId);

    const xhr = new XMLHttpRequest();
    xhr.open('POST', '', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    xhr.onload = function () {
        if (xhr.status === 200) {
            const response = JSON.parse(xhr.responseText);
            const likesCountElement = document.getElementById('likes-count-' + postId);
            likesCountElement.textContent = response.likes;
            likeButton.textContent = response.userLiked ? 'Dislike' : 'Like';
        }
    };

    xhr.send('postId=' + postId);
}

</script>


<style>
.button {
    padding: 5px 10px;
    margin: 5px;
    font-size: 14px;
    cursor: pointer;
    background-color: #007bff;
    border: none;
    color: white;
    border-radius: 5px;
    transition: background-color 0.3s ease;
}

.button:hover {
    background-color: #0056b3;
}

.delete-btn {
    background-color: #dc3545;
}

.delete-btn:hover {
    background-color: #c82333;
}
</style>