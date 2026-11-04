<?php

include "koneksi.php";

if(isset($_GET['db']) && isset($_GET['status'])){

    $nilai_db = $_GET['db'];
    $status   = $_GET['status'];

    $query = "INSERT INTO data_kebisingan (nilai_db, status)
              VALUES ('$nilai_db','$status')";

    if(mysqli_query($conn,$query)){
        echo "Data berhasil disimpan";
    }else{
        echo "Gagal menyimpan data";
    }

}else{

    echo "Parameter tidak lengkap";

}

?>