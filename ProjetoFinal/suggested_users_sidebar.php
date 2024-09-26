<?php
    require_once( "Lib/lib.php" );
    require_once( "Lib/db.php" );

    function fetchMostFollowedUsers($currentUserId)
    {
        // Connect to the database
        dbConnect(ConfigFile);
        $dataBaseName = $GLOBALS['configDataBase']->db;
        mysqli_select_db($GLOBALS['ligacao'], $dataBaseName);
    
        // Fetch the most followed users excluding those already followed by the current user and the current user themselves
        $query = "
            SELECT 
                User.id, 
                User.username, 
                User.followers 
            FROM 
                User 
            WHERE 
                User.id NOT IN (
                    SELECT 
                        user_id 
                    FROM 
                        Followers 
                    WHERE 
                        follower_id = $currentUserId
                ) 
                AND User.id != $currentUserId
            ORDER BY 
                User.followers DESC 
            LIMIT 5"; // Adjust the limit as needed
    
        $result = mysqli_query($GLOBALS['ligacao'], $query);
    
        $users = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $users[] = $row;
            }
            mysqli_free_result($result);
        } else {
            echo "Error fetching users: " . mysqli_error($GLOBALS['ligacao']);
        }
    
        dbDisconnect();
        return $users;
    }
    ?>
    
    <!DOCTYPE html>
    <html>
    <head>
        <link rel="stylesheet" type="text/css" href="css/sidebars.css">
    </head>
    <body>
    
    <div class="sidebar sidebar-suggested-users">
        <h2>Suggested Users</h2>
        <?php
        session_start();
        // Assuming user ID is stored in the session
        $currentUserId = $_SESSION['user_id'];
        $mostFollowedUsers = fetchMostFollowedUsers($currentUserId);
    
        if (!empty($mostFollowedUsers)) {
            foreach ($mostFollowedUsers as $user) {
                echo "<a href='viewProfile.php?user_id=" . htmlspecialchars($user['id']) . "'>" . htmlspecialchars($user['username']) . "</a><br>";
            }
        } else {
            echo "<p>No suggested users</p>";
        }
        ?>
    </div>
    
    </body>
    </html>
