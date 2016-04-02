<?php

echo "Welcome";

$conn = new mysqli('ftp.iosnewbies.com', 'watersaver_2', 'Rutvij_22', 'watersaver');

        // Check for database connection error
        if (mysqli_connect_errno()) {
            echo "Failed to connect to MySQL: " . mysqli_connect_error();
        }
        else
        {
        	echo "Connetection Established!!";
        }

?>