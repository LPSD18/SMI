<?php

require_once("Lib/lib.php");
require_once("Lib/db.php");

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['userType'] !== 'admin') {
    echo "You do not have permission to access this page.";
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username']; // Assuming the username is stored in session as well
$type = $_SESSION['userType'];

function fetchAllNonAdminUsers($currentUserId)
{
    dbConnect(ConfigFile);
    $dataBaseName = $GLOBALS['configDataBase']->db;
    mysqli_select_db($GLOBALS['ligacao'], $dataBaseName);

    $query = "SELECT id, username, type, active FROM User WHERE id != ? AND type != 'admin'";
    $stmt = mysqli_prepare($GLOBALS['ligacao'], $query);
    mysqli_stmt_bind_param($stmt, "i", $currentUserId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $users = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }

    mysqli_stmt_close($stmt);
    dbDisconnect();
    return $users;
}

function updateUser($userId, $newType, $activeStatus)
{
    dbConnect(ConfigFile);
    $dataBaseName = $GLOBALS['configDataBase']->db;
    mysqli_select_db($GLOBALS['ligacao'], $dataBaseName);

    $query = "UPDATE User SET type = ?, active = ? WHERE id = ?";
    $stmt = mysqli_prepare($GLOBALS['ligacao'], $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sii", $newType, $activeStatus, $userId);
        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    } else {
        $result = false;
    }

    dbDisconnect();
    return $result;
}

function deleteUser($userId)
{
    dbConnect(ConfigFile);
    $dataBaseName = $GLOBALS['configDataBase']->db;
    mysqli_select_db($GLOBALS['ligacao'], $dataBaseName);

    // Start a transaction
    mysqli_begin_transaction($GLOBALS['ligacao']);

    try {
        // Delete user's interactions
        $query = "DELETE FROM Interactions WHERE user_id = ?";
        $stmt = mysqli_prepare($GLOBALS['ligacao'], $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $userId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        // Delete user's comments
        $query = "DELETE FROM Comment WHERE user_id = ?";
        $stmt = mysqli_prepare($GLOBALS['ligacao'], $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $userId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        // Delete user's posts (and related post tags)
        $query = "DELETE PostTags FROM PostTags INNER JOIN Post ON PostTags.post_id = Post.id WHERE Post.user_id = ?";
        $stmt = mysqli_prepare($GLOBALS['ligacao'], $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $userId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        $query = "DELETE FROM Post WHERE user_id = ?";
        $stmt = mysqli_prepare($GLOBALS['ligacao'], $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $userId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        // Delete user from followers and following lists
        $query = "DELETE FROM Followers WHERE user_id = ? OR follower_id = ?";
        $stmt = mysqli_prepare($GLOBALS['ligacao'], $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ii", $userId, $userId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        // Finally, delete the user
        $query = "DELETE FROM User WHERE id = ?";
        $stmt = mysqli_prepare($GLOBALS['ligacao'], $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $userId);
            $result = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        } else {
            $result = false;
        }

        // Commit transaction
        mysqli_commit($GLOBALS['ligacao']);
    } catch (Exception $e) {
        // Rollback transaction in case of error
        mysqli_rollback($GLOBALS['ligacao']);
        $result = false;
    }

    dbDisconnect();
    return $result;
}

$users = fetchAllNonAdminUsers($user_id);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['userId']) && isset($_POST['newType']) && isset($_POST['activeStatus'])) {
        $userId = $_POST['userId'];
        $newType = $_POST['newType'];
        $activeStatus = $_POST['activeStatus'];
        $updateResult = updateUser($userId, $newType, $activeStatus);
        if ($updateResult) {
            // Optionally add a success message here
        } else {
            echo "<p>Failed to update user type and active status.</p>";
        }
        // Refresh the users list after update
        $users = fetchAllNonAdminUsers($user_id);
    }

    if (isset($_POST['deleteUserId'])) {
        $userId = $_POST['deleteUserId'];
        $deleteResult = deleteUser($userId);
        if ($deleteResult) {
            // Optionally add a success message here
        } else {
            echo "<p>Failed to delete user.</p>";
        }
        // Refresh the users list after delete
        $users = fetchAllNonAdminUsers($user_id);
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
    <title>Admin</title>
    <link rel="stylesheet" type="text/css" href="css/style.css">
</head>

<body>
    <div id="containerDiv">
        <div id="headerDiv">
            <?php include_once("profileheader.php"); ?>
        </div>
        <div class="main-content">
            <h2>Manage User Types and Active Status</h2>
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Current Type</th>
                        <th>New Type</th>
                        <th>Active Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (empty($users)) {
                        echo "<tr><td colspan='5'>No users found.</td></tr>";
                    } else {
                        foreach ($users as $user) {
                            echo "<tr>
                                    <td>" . htmlspecialchars($user['username']) . "</td>
                                    <td>" . htmlspecialchars($user['type']) . "</td>
                                    <td>
                                        <form method='POST' action=''>
                                            <select name='newType' required>
                                                <option value='default'" . ($user['type'] == 'default' ? ' selected' : '') . ">Default</option>
                                                <option value='premium'" . ($user['type'] == 'premium' ? ' selected' : '') . ">Premium</option>
                                                <option value='guest'" . ($user['type'] == 'guest' ? ' selected' : '') . ">Guest</option>
                                                <option value='admin'" . ($user['type'] == 'admin' ? ' selected' : '') . ">Admin</option>
                                            </select>
                                            <input type='hidden' name='userId' value='" . htmlspecialchars($user['id']) . "'>
                                    </td>
                                    <td>
                                            <input type='radio' name='activeStatus' value='1'" . ($user['active'] == 1 ? ' checked' : '') . "> Active
                                            <input type='radio' name='activeStatus' value='0'" . ($user['active'] == 0 ? ' checked' : '') . "> Inactive
                                    </td>
                                    <td>
                                            <button type='submit'>Update</button>
                                        </form>
                                        <form method='POST' action='' onsubmit='return confirm(\"Are you sure you want to delete this user?\");'>
                                            <input type='hidden' name='deleteUserId' value='" . htmlspecialchars($user['id']) . "'>
                                            <button type='submit' class='delete-btn'>Delete</button>
                                        </form>
                                    </td>
                                  </tr>";
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <div id="footerDiv" style="height: 10%;">
            <?php include_once("footer.php"); ?>
        </div>
    </div>
</body>

</html>