<?php
session_start();
$returnUrl = isset($_GET['returnUrl']) ? (string)$_GET['returnUrl'] : 'page.php';

// Habilitar exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir arquivos necessários
require_once( "Lib/lib.php" );
require_once( "Lib/db.php" );

// Obter foto do usuário
$userPhoto = 'images/pfp.jpg'; // Foto padrão
if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];
    dbConnect(ConfigFile);
    $dataBaseName = $GLOBALS['configDataBase']->db;
    mysqli_select_db($GLOBALS['ligacao'], $dataBaseName);

    $query = "SELECT photo FROM `User` WHERE `username`='$username'";
    $result = mysqli_query($GLOBALS['ligacao'], $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        $photo = $user['photo'];
        if (!empty($photo)) {
            $userPhoto = 'data:image/jpeg;base64,' . base64_encode($photo);
        }
    }

    mysqli_free_result($result);
    dbDisconnect();
}

// Tratar submissão do formulário
function fetchTags() {
    dbConnect(ConfigFile);
    $dataBaseName = $GLOBALS['configDataBase']->db;
    mysqli_select_db($GLOBALS['ligacao'], $dataBaseName);

    $query = "SELECT id, name FROM Tags";
    $result = mysqli_query($GLOBALS['ligacao'], $query);

    $tags = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $tags[] = $row;
    }

    mysqli_free_result($result);
    dbDisconnect();
    return $tags;
}

$tags = fetchTags();

// Tratar submissão do formulário
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userId = $_SESSION['user_id'] ?? 0;
    $text = $_POST['text'] ?? '';
    $tagId = $_POST['tagId'] ?? null;
    $image = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $image = file_get_contents($_FILES['image']['tmp_name']);
    }

    addPost($userId, $text, $image, null, $tagId, $returnUrl);
}

function addPost($userId, $text, $image, $video, $tagId, $returnUrl)
{
    dbConnect(ConfigFile);
    $dataBaseName = $GLOBALS['configDataBase']->db;
    mysqli_select_db($GLOBALS['ligacao'], $dataBaseName);

    $query = "INSERT INTO Post (user_id, text, image, video, datetime) VALUES (?, ?, ?, ?, NOW())";
    if ($stmt = mysqli_prepare($GLOBALS['ligacao'], $query)) {
        mysqli_stmt_send_long_data($stmt, 2, $image);
        mysqli_stmt_send_long_data($stmt, 3, $video);

        mysqli_stmt_bind_param($stmt, "isss", $userId, $text, $image, $video);
        if (mysqli_stmt_execute($stmt)) {
            $postId = mysqli_insert_id($GLOBALS['ligacao']);
            mysqli_stmt_close($stmt);

            if ($tagId) {
                $queryTag = "INSERT INTO PostTags (post_id, tag_id) VALUES (?, ?)";
                if ($stmtTag = mysqli_prepare($GLOBALS['ligacao'], $queryTag)) {
                    mysqli_stmt_bind_param($stmtTag, "ii", $postId, $tagId);
                    if (!mysqli_stmt_execute($stmtTag)) {
                        echo "<script>alert('Erro ao executar a consulta de tag: " . mysqli_stmt_error($stmtTag) . "');</script>";
                        error_log("Erro ao executar a consulta de tag: " . mysqli_stmt_error($stmtTag));
                    }
                    mysqli_stmt_close($stmtTag);
                } else {
                    echo "<script>alert('Erro ao preparar a consulta de tag: " . mysqli_error($GLOBALS['ligacao']) . "');</script>";
                    error_log("Erro ao preparar a consulta de tag: " . mysqli_error($GLOBALS['ligacao']));
                }
            }

            header("Location: $returnUrl");
            exit();
        } else {
            echo "<script>alert('Erro ao executar a consulta: " . mysqli_stmt_error($stmt) . "');</script>";
            error_log("Erro ao executar a consulta: " . mysqli_stmt_error($stmt));
        }
    } else {
        echo "<script>alert('Erro ao preparar a consulta: " . mysqli_error($GLOBALS['ligacao']) . "');</script>";
        error_log("Erro ao preparar a consulta: " . mysqli_error($GLOBALS['ligacao']));
    }

    dbDisconnect();
}
?>

<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adicionar Postagem</title>
    <link rel="stylesheet" href="css/addPost.css">
</head>

<body>
    <div class="add-post-container">
        <div class="add-post-header">
            <button class="close-btn" onclick="closePage()">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data" id="postForm" onsubmit="return submitForm()">
            <div class="add-post-body">
                <div class="user-avatar">
                    <a href="profilePage.php">
                        <div class="user-icon">
                            <img src="<?php echo $userPhoto; ?>" class="pfp" alt="Avatar do Usuário">
                        </div>
                    </a>
                </div>
                <textarea class="post-input" name="text" placeholder="O que está acontecendo?!"></textarea>
                <div class="tag-selection">
                    <select name="tagId" required>
                        <option value="" disabled selected>Escolha uma tag</option>
                        <?php
                        foreach ($tags as $tag) {
                            echo "<option value='" . htmlspecialchars($tag['id']) . "'>" . htmlspecialchars($tag['name']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="add-post-footer">
                <div class="reply-settings">
                    <span>Todos podem responder</span>
                </div>
                <div class="post-actions">
                    <input type="file" name="image" id="image" style="display: none;">
                    <button type="button" class="action-btn" onclick="document.getElementById('image').click();"><img src="images/fundocaptch.png" alt="Imagem"></button>
                    
                </div>
                <button type="submit" class="post-btn">Post</button>
            </div>
        </form>
    </div>
    <script>
        function closePage() {
            const returnUrl = "<?php echo $returnUrl; ?>";
            window.location.href = returnUrl;
        }

        function submitForm(event) {
            event.preventDefault();

            var formData = new FormData(document.getElementById('postForm'));
            var xhr = new XMLHttpRequest();
            xhr.open('POST', window.location.href);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    window.location.href = "<?php echo $returnUrl; ?>";
                } else {
                    alert('Erro ao enviar o formulário: ' + xhr.statusText);
                }
            };
            xhr.onerror = function() {
                alert('Erro ao enviar o formulário: Erro de conexão');
            };
            xhr.send(formData);

            return false;
        }
    </script>
</body>

</html>
