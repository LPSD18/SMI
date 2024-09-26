<?php
    require_once( "Lib/lib.php" );
    require_once( "Lib/db.php" );

function updateUser($id, $email, $password, $name, $photo)
{
    dbConnect(ConfigFile);
    $dataBaseName = $GLOBALS['configDataBase']->db;
    mysqli_select_db($GLOBALS['ligacao'], $dataBaseName);

    // Fetch the current user data
    $query = "SELECT email, password, username, photo FROM User WHERE id = ?";
    if ($stmt = mysqli_prepare($GLOBALS['ligacao'], $query)) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $currentEmail, $currentPassword, $currentName, $currentPhoto);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
    }

    // Update the fields that have been provided, otherwise keep the current value
    $email = $email ?: $currentEmail;
    $password = $password ? password_hash($password, PASSWORD_DEFAULT) : $currentPassword;
    $name = $name ?: $currentName;
    $photoData = $photo['tmp_name'] ? file_get_contents($photo['tmp_name']) : $currentPhoto;

    // Prepare the SQL update statement
    $query = "
        UPDATE User SET 
            email = ?,
            password = ?,
            username = ?,
            photo = ?
        WHERE id = ?
    ";

    // Prepare the statement
    if ($stmt = mysqli_prepare($GLOBALS['ligacao'], $query)) {
        // Bind the parameters
        mysqli_stmt_bind_param($stmt, "ssssi", $email, $password, $name, $photoData, $id);

        // Execute the statement
        if (mysqli_stmt_execute($stmt)) {
            // Redirect to profile page after successful update
            header("Location: profilePage.php");
            exit();
        } else {
            echo "Error updating user: " . mysqli_error($GLOBALS['ligacao']);
        }

        // Close the statement
        mysqli_stmt_close($stmt);
    } else {
        echo "Error preparing the statement: " . mysqli_error($GLOBALS['ligacao']);
    }

    // Close the connection
    dbDisconnect();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Assuming the user is logged in and their ID is stored in the session
    session_start();
    $userId = $_SESSION['user_id'];

    $email = $_POST['email'] ?? null;
    $password = $_POST['password'] ?? null;
    $name = $_POST['name'] ?? null;
    $photo = $_FILES['photo'] ?? null;

    updateUser($userId, $email, $password, $name, $photo);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update User</title>
    <link rel="stylesheet" type="text/css" href="css/profile.css">
    <link rel="stylesheet" type="text/css" href="css/style.css">
</head>

<body>

    <div id="containerDiv">

        <div id="headerDiv">
            <?php include_once("header.php") ?>
        </div>

        <div id="user-info">
            <div class="user-profile">
                <div class="user-avatar">
                    <?php
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
                    ?>


                </div>
                <h2><?php echo $username; ?></h2>
            </div>

            <form class="update-form" action="changeProfile.php" method="POST" enctype="multipart/form-data">
                <div class="update-section">
                    <label for="email">Update Email</label>
                    <input type="email" id="email" name="email" placeholder="New Email">
                </div>
                <div class="update-section">
                    <label for="password">Update Password</label>
                    <input type="password" id="password" name="password" placeholder="New Password">
                </div>
                <div class="update-section">
                    <label for="name">Update Name</label>
                    <input type="text" id="name" name="name" placeholder="New Name">
                </div>
                <div class="update-section">
                    <label for="photo">Update Photo</label>
                    <input type="file" id="photo" name="photo">
                </div>
                <a href="profilePage.php">
                    <button type="submit" class="update-button">Update</button>
                </a>
            </form>
        </div>

        <div id="footerDiv">
            <?php include_once("footer.php") ?>
        </div>
    </div>

</body>

</html>

