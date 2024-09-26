<!DOCTYPE html>

<html>

<head>
  <meta ttp-equiv='Content-Type' content='text/html; charset=utf-8'>
  <title>Create DB</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

</head>

<body>

  <h1>Pagina Processar</h1>

  <?php




require_once( "Lib/lib.php" );
require_once( "Lib/db.php" );
  session_start();

  echo "<p>1</p>";

  $database = $_POST['database'];
  $db_user = $_POST['username'];
  $db_pass = $_POST['password'];

  $xml = new DOMDocument();
  $xml->load("Config/.htconfig.xml");
  echo "<p>2</p>";
  $xpath = new DOMXPath($xml);

  $nodes = $xpath->query('//DataBase/db');
  echo "<p>3</p>";
  foreach ($nodes as $node) {
    $node->nodeValue = $database;
  }
  $nodes = $xpath->query('//DataBase/username');
  foreach ($nodes as $node) {
    $node->nodeValue = $db_user;
  }
  $nodes = $xpath->query('//DataBase/password');
  foreach ($nodes as $node) {
    $node->nodeValue = $db_pass;
  }
  $xml->save("Config/.htconfig.xml");
  echo "<p>4</p>";

  if (isset($_POST['create'])) {
    echo "<p>5</p>";

    $servername = "localhost";  // Replace with your server name if necessary
    echo "<p>6</p>";
    // Create a connection
    $conn = new mysqli($servername, "root", "");
    // $conn = new mysqli($servername, $_SESSION['phpUser'], $_SESSION['phpPass']); //phpMyAdmin credenciais
    echo "<p>7</p>";
    // Check the connection
    if ($conn->connect_error) {
      die("Connection failed: " . $conn->connect_error);
    }

    echo "<p> USER " . $db_user . "</p>";
    echo "<p> PASS " . $db_pass . "</p>";

    $queryCreateDatabase = "CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8 COLLATE utf8_unicode_ci";

    // Create database
    if ($conn->query($queryCreateDatabase) === true) {
      echo "<p>8</p>";
      // Select the database
      $conn->select_db($database);

      // Create user
      $queryCreateUserLocal = "CREATE USER '{$db_user}'@'localhost' IDENTIFIED BY '{$db_pass}'";
      $queryCreateUserRemote = "CREATE USER '{$db_user}'@'%' IDENTIFIED BY '{$db_pass}'";

      if ($conn->query($queryCreateUserLocal) === true && $conn->query($queryCreateUserRemote) === true) {

        // Grant privileges
        $queryGrantPrivilegesLocal = "GRANT USAGE ON *.* TO '{$db_user}'@'localhost' IDENTIFIED BY '{$db_pass}' WITH MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0";
        $queryGrantPrivilegesRemote = "GRANT USAGE ON *.* TO '{$db_user}'@'%' IDENTIFIED BY '{$db_pass}' WITH MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0";
        $queryGrantAllPrivilegesLocal = "GRANT ALL PRIVILEGES ON `{$database}`.* TO '{$db_user}'@'localhost'";
        $queryGrantAllPrivilegesRemote = "GRANT ALL PRIVILEGES ON `{$database}`.* TO '{$db_user}'@'%'";
        echo "<p>111</p>";
        if (
          $conn->query($queryGrantPrivilegesLocal) === true &&
          $conn->query($queryGrantPrivilegesRemote) === true &&
          $conn->query($queryGrantAllPrivilegesLocal) === true &&
          $conn->query($queryGrantAllPrivilegesRemote) === true
        ) {
          echo "Database, User, and Privileges created successfully";
        } else {
          echo "Error creating Privileges: " . $conn->error;
        }
      } else {
        echo "Error creating User: " . $conn->error;
      }
    } else {
      echo "Error creating database: " . $conn->error;
    }


    //Auth tables
    $sql1 = "CREATE TABLE User (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255) NOT NULL,
            password VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            birthday DATE NOT NULL,
            photo LONGBLOB,
            followers INT DEFAULT 0,
            type ENUM('admin', 'default', 'premium', 'guest') NOT NULL
            ),
            active INT DEFAULT 0;";

    $sql2 = "CREATE TABLE Post (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            text TEXT,
            image LONGBLOB,
            video LONGBLOB,
            likes INT DEFAULT 0,
            datetime DATETIME NOT NULL,
            FOREIGN KEY (user_id) REFERENCES User(id)
            );";

    $sql3 = "CREATE TABLE Comment (
            id INT AUTO_INCREMENT PRIMARY KEY,
            post_id INT,
            user_id INT,
            text TEXT NOT NULL,
            likes INT DEFAULT 0,
            FOREIGN KEY (post_id) REFERENCES Post(id),
            FOREIGN KEY (user_id) REFERENCES User(id)
            );";


    $sql5 = "CREATE TABLE Messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sender_id INT,
            receiver_id INT,
            text TEXT,
            video LONGBLOB,
            image LONGBLOB,
            datetime DATETIME NOT NULL,
            FOREIGN KEY (sender_id) REFERENCES User(id),
            FOREIGN KEY (receiver_id) REFERENCES User(id)
            );";

    $sql6 = "CREATE TABLE Tags (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL
            );";

    //RSS tables
    $sql7 = "CREATE TABLE Followers (
            user_id INT,
            follower_id INT,
            PRIMARY KEY (user_id, follower_id),
            FOREIGN KEY (user_id) REFERENCES User(id),
            FOREIGN KEY (follower_id) REFERENCES User(id)
            );";

    $sql8 = "CREATE TABLE PostTags (
            post_id INT,
            tag_id INT,
            PRIMARY KEY (post_id, tag_id),
            FOREIGN KEY (post_id) REFERENCES Post(id),
            FOREIGN KEY (tag_id) REFERENCES Tags(id)
            );";

    $sql9 = "CREATE TABLE Interactions (
            user_id INT,
            post_id INT,
            PRIMARY KEY (user_id, post_id),
            FOREIGN KEY (user_id) REFERENCES User(id),
            FOREIGN KEY (post_id) REFERENCES Post(id)
            );";
    echo "<p>110</p>";
    if($conn->query($sql1)){
      echo "user created";
    }
    else{
      echo "fail creating";
    }
    if($conn->query($sql2)){
      echo "Post created";
    }
    else{
      echo "fail creating";
    }
    if($conn->query($sql3)){
      echo "Comment created";
    }
    else{
      echo "fail creating";
    }
    if($conn->query($sql5)){
      echo "Messages created";
    }
    else{
      echo "fail creating";
    }

    if($conn->query($sql6)){
      echo "Tags created";
    }
    else{
      echo "fail creating";
    }

    if($conn->query($sql7)){
      echo "Followers created";
    }
    else{
      echo "fail creating";
    }

    if($conn->query($sql8)){
      echo "PostTags created";
    }
    else{
      echo "fail creating";
    }

    if($conn->query($sql9)){
      echo "Interactions created";
    }
    else{
      echo "fail creating";
    }

    // if ($conn->multi_query($sql1 . ";" . $sql2 . ";" . $sql3 . ";" . $sql5 . ";" . $sql6 . ";" . $sql7 . ";" . $sql8 . ";" . $sql9)) {

    //   echo "Tables created successfully.";
    // } else {
    //   echo "Error creating tables: " . $conn->error;
    // }
    $conn->close();

    echo "10";


    // dbConnect(ConfigFile);
    header('Location: page.php');
    echo "11";
  }
  header('Location: page.php');
  ?>
</body>

</html>