<?php
    require_once( "Lib/lib.php" );
    require_once( "Lib/db.php" );

    function fetchTags()
    {
        // Connect to the database
        dbConnect(ConfigFile);
        $dataBaseName = $GLOBALS['configDataBase']->db;
        mysqli_select_db($GLOBALS['ligacao'], $dataBaseName);
    
        // Fetch tags
        $query = "SELECT name FROM Tags";
        $result = mysqli_query($GLOBALS['ligacao'], $query);
    
        $tags = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $tags[] = $row['name'];
            }
            mysqli_free_result($result);
        } else {
            echo "Error fetching tags: " . mysqli_error($GLOBALS['ligacao']);
        }
    
        // dbDisconnect();
        return $tags;
    }
    
    
    ?>
    
    <!DOCTYPE html>
    
    <head>
        <link rel="stylesheet" type="text/css" href="css/sidebars.css">
    </head>
    
    <!-- tags_sidebar.php -->
    <div class="sidebar sidebar-tags">
        <h2>Tags</h2>
        <?php
        $tags = fetchTags();
        if (!empty($tags)) {
            foreach ($tags as $tag) {
                echo "<a href='page.php?tag=" . urlencode($tag) . "'>" . htmlspecialchars($tag) . "</a><br>";
            }
        } else {
            echo "<p>No tags</p>";
        }
        ?>
    </div>