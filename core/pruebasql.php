<?php
    $nombre = 'Juan';
    $apellido = 'Perez';
    $login = 'Perez';
    $password = 'contraseña123';
    $pass_crypted = sha1($password);
    $job = 'Gerencia2';
    $datec = date('Y-m-d H:i:s');
    $employee = 1;
    $fk_soc = 0;
    $responsable = 2;

    $contenido = "INSERT INTO khns_user (firstname, lastname, login, pass, job, employee, fk_soc, pass_crypted, datec) ";
    $contenido.= " VALUES ";
    $contenido.= " ('$nombre', '$apellido', '$login', '$password', '$job', $employee, $fk_soc, '$pass_crypted', '$datec');";

    $contenido.= "\r\n";

    // Obtener el ID del último usuario insertado
    $contenido.= "SET @last_id = LAST_INSERT_ID();";

    $contenido.= "\r\n";

    $contenido.= "INSERT INTO khns_user_extrafields (fk_object, responsable, fk_service) ";
    $contenido.= " VALUES ";
    $contenido.= " (@last_id, $responsable, 0);";

    $contenido.= "\r\n";
    $contenido.= "\r\n";

    // Enviar encabezados HTTP para indicar que se va a enviar un archivo para descargar
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="insert.sql"');

    echo $contenido;
?>