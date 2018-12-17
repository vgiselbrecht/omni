<?php

function connection()
{
    $mysqlhost="localhost"; // MySQL-Host angeben

    $mysqluser="root"; // MySQL-User angeben

    $mysqlpwd=""; // Passwort angeben

    $connection = mysql_connect($mysqlhost, $mysqluser, $mysqlpwd) or die ("Verbindungsversuch fehlgeschlagen");
    @mysql_select_db ("omni", $connection);  
    mysql_set_charset('utf8', $connection);
}

?>
