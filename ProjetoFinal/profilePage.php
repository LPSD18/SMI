<?php
session_start();

// if (isset($_SESSION['user_id'])) {
//     $username = $_SESSION['username'];
//     echo "<p>Its working: $username</p>";
// }
?>

<!DOCTYPE html>
<html>
<head>
    <meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
    <title>GymHub</title>
    <link rel="stylesheet" type="text/css" href="css/style.css">
    <script type="text/javascript" src="./scripts/forms.js"></script>    
</head>

<body>

<div accesskey="" id="containerDiv">

    <div id="headerDiv">
        <?php include_once("profileheader.php") ?>
    </div>

    <div class="main-content">
        <div id="tagsDiv" style="height:60%;width:10%;float:left;margin: left 40px">
            <?php include_once("tags_sidebar.php") ?>
        </div>

        <div class="posts-wrapper">
            <?php include_once("profilePosts.php");?>
        </div>

        <div id="usersDiv" style="height:60%;width:10%;float:right;margin: right 40px;">
            <?php include_once("suggested_users_sidebar.php") ?>
        </div>    
    </div>

    <div id="footerDiv"style="height:10%;">
        <?php include_once("footer.php") ?>
    </div>
</div>
</body>
</html>