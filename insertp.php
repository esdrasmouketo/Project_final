
<?php

// php code to Insert data into mysql database from input text

    $hostname = "localhost";
    $username = "root";
    $password = "";
    $databaseName = "ardbd";
    
    // get values form input text and number

    $temperature = $_GET['temperature'];
    $humidite = $_GET['humidite'];
    $co2 = $_GET['co2'];
    $lum = $_GET['lumiere'];
    
    
    // connect to mysql database using mysqli

    $connect = mysqli_connect($hostname, $username, $password, $databaseName);
    
    // mysql query to insert data

    $query = "INSERT INTO table_capteurs(`id`,`luminosite`, `co2`, `temeprature`, ) VALUES (NULL, $lum, $humdite, $co2,$temperature )";
    
    $result = mysqli_query($connect,$query);
    
    // check if mysql query successful

    if($result)
    {
        echo 'Data Inserted';
    }
    
    else{
        echo 'Data Not Inserted';
    }
    
    mysqli_free_result($result);
    mysqli_close($connect);

?>