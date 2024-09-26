<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="css/header.css">

    <title>GymHub</title>
</head>

<body>
    <header>
        <div class="nav-container">
            <a href="page.php">
                <div class="logo-div">
                    <img src="images/GymHub.jpg" alt="GymHub Logo" class="logo">
                </div>
            </a>
            <div class="search-bar">
                <input type="text" placeholder="Search...">
                <button type="button">Search</button>
            </div>
            <?php 
            if($_SESSION['userType']==='admin'){
                echo'
                <div class="admin-button">
                    <a href="adminPage.php">
                        <button type="button"> Admin</button>
                    </a>
                </div>
                ';
            }
            ?>
            <div class="followers">
                <a href="followers.php">
                    <button id="seeFoll">Followers</button>
                </a>
            </div>
            <div class="add-post">
                <a href="addPost.php">
                    <button id="addPostBtn">Add Post</button>
                </a>
            </div>
            <div class="change-prof">
                <a href="changeProfile.php">
                    <button type="button">Change Profile</button>
                </a>
            </div>

            <?php
            session_start();

            // Sign-out logic
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
                session_unset();
                session_destroy();
                header("Location: page.php");
                exit();
            }

            require_once("Lib/lib.php");
            require_once("Lib/db.php");

            if (isset($_SESSION['user_id'])) {
                $username = $_SESSION['username'];

                // Conectar à base de dados
                dbConnect(ConfigFile);
                $dataBaseName = $GLOBALS['configDataBase']->db;
                mysqli_select_db($GLOBALS['ligacao'], $dataBaseName);

                // Consulta para obter a foto do usuário
                $query = "SELECT photo FROM `User` WHERE `username`='$username'";
                $result = mysqli_query($GLOBALS['ligacao'], $query);

                if ($result && mysqli_num_rows($result) > 0) {
                    // O usuário tem uma foto, exiba-a
                    $user = mysqli_fetch_assoc($result);
                    $photo = $user['photo'];

                    // Verifique se há uma foto disponível
                    if (!empty($photo)) {
                        echo '<a href="profilePage.php">
                                <div class="user-icon">
                                    <img src="data:image/jpeg;base64,' . base64_encode($photo) . '" class="pfp" alt="User Icon">
                                    <span class="username">' . htmlspecialchars($username) . '</span>
                                </div>
                              </a>';
                    } else {
                        // Caso contrário, exiba o ícone padrão
                        echo '<a href="profilePage.php">
                                <div class="user-icon">
                                    <img src="images/pfp.jpg" class="pfp" alt="User Icon">
                                </div>
                              </a>';
                    }
                } else {
                    // Caso contrário, exiba o ícone padrão
                    echo '<a href="profilePage.php">
                            <div class="user-icon">
                                <img src="images/pfp.jpg" class="pfp" alt="User Icon">
                            </div>
                          </a>';
                }

                mysqli_free_result($result);
                dbDisconnect();

                echo '<div class="sign-out">
                        <form action="" method="POST" style="display:inline;">
                            <button type="submit" name="logout">Sign-out</button>
                        </form>
                      </div>';
            } else {
                // O usuário não está logado, exiba o ícone padrão
                echo '<a href="login.php">
                        <div class="user-icon">
                            <img src="images/pfp.jpg" class="pfp" alt="User Icon">
                        </div>
                      </a>';
            }
            ?>
        </div>
    </header>

</body>

</html>