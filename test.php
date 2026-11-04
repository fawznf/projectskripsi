<?php

include "koneksi.php";

if($conn)
{
    echo "<h2>Koneksi Database Berhasil</h2>";
}
else
{
    echo "<h2>Koneksi Database Gagal</h2>";
}

?>