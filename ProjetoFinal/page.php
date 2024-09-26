<!DOCTYPE html>
<html>
<head>
        <meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
        <title>GymHub</title>

        <link rel="stylesheet" type="text/css" href="css/style.css">

        <script type="text/javascript" src="scripts/forms.js">
        </script>    
    </head>

    <body>
    
    <div accesskey="" id="containerDiv">

        <div id="headerDiv">
                <?php include_once( "header.php" ) ?>
        </div>

        <div class="main-content">
                <div id="tagsDiv" style="height:60%;width:10%;float:left;margin: left 40px">
                        <?php include_once( "tags_sidebar.php" ) ?>
                </div>

                <div class="posts-wrapper">
                        <div class="posts">
                                <?php
                                require_once("Post.php");
                                require_once("Lib/lib.php");
                                require_once("Lib/db.php");
                                //error_reporting(E_ALL);
                               // ini_set('display_errors', 1);
                               $tag = isset($_GET['tag']) ? $_GET['tag'] : '';
                                $searchQuery = isset($_POST['searchQuery']) ? $_POST['searchQuery'] : '';
                                if (!empty($searchQuery)) {
                                        $posts = fetchPostsBySearch($searchQuery);
                                }
                                elseif(!empty($tag)){
                                        $posts = fetchPostsByTag($tag);
                                }
                                else {
                                        $posts = fetchPosts();
                                }
                                if (!empty($posts)) {
                                        foreach ($posts as $post) {
                                                displayPost($post);
                                }
                                } else {
                                echo "<p>No posts available</p>";
                                }
                                
                                ?>
                                <div class="fill"></div>
                        </div>
                </div>

                <div id="usersDiv" style="height:60%;width:10%;float:right;margin: right 40px;">
                                <?php include_once( "suggested_users_sidebar.php" ) ?>
                </div>    
        </div>
        


        <div id="footerDiv"style="height:10%;">
                <?php include_once( "footer.php" ) ?>
        </div>
    </div>
</body>
</html>