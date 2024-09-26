<?php
session_start();

// if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['searchQuery'])) {
//     $searchQuery = $_POST['searchQuery'];
//     //header("Location: page.php?search=" . urlencode($searchQuery));
//     header("Location: page.php");
//     exit();
// }
?>
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
            <form method="POST" action="page.php" id="searchForm">
                <input type="text" id="searchQuery" name="searchQuery" placeholder="Search...">
                <button type="submit">Search</button>
            </form>
        </div>
        <div class="add-post">
            <a href="addPost.php">
                <button id="addPostBtn">Add Post</button>
            </a>
        </div>


        <?php
            require_once( "Lib/lib.php" );
            require_once( "Lib/db.php" );

        if (isset($_SESSION['user_id'])) {
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
                    echo '<a href="profilePage.php">
                            <div class="user-icon">
                                <img src="data:image/jpeg;base64,'.base64_encode($photo).'" class="pfp" alt="User Icon">
                            </div>
                          </a>';
                } else {
                    echo '<a href="profilePage.php">
                            <div class="user-icon">
                                <img src="images/pfp.jpg" class="pfp" alt="User Icon">
                            </div>
                          </a>';
                }
            } else {
                echo '<a href="profilePage.php">
                        <div class="user-icon">
                            <img src="images/pfp.jpg" class="pfp" alt="User Icon">
                        </div>
                      </a>';
            }

            mysqli_free_result($result);
            dbDisconnect();
        } else {
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
