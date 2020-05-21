<?php

//Función para conectar con la bd
function connectDb(){
    $dbInfo = json_decode(file_get_contents("../db_info.json"));
    return mysqli_connect($dbInfo->host, $dbInfo->user, $dbInfo->password, $dbInfo->database);
}

//Función para comprobar si un url yaestá en la base de datos
function onDataBase($link){
    $conexion = connectDb();

    $sql = "select * from `noticias`";
    $resultado = $conexion->query($sql);
    while ($fila = mysqli_fetch_array($resultado)) {
        if($link == $fila['link']){
            return true;
        }else{
            return false;
        }
    }
}

function updateUrl($url){
    $html = file_get_contents_curl($url);
    $doc = new DOMDocument();
    @$doc->loadHTML($html);
    $nodes = $doc->getElementsByTagName('title');
    $title = limpiarString($nodes->item(0)->nodeValue);
    $metas = $doc->getElementsByTagName('meta');
    for ($i = 0; $i < $metas->length; $i++) :
        $meta = $metas->item($i);
        if ($meta->getAttribute('name') == 'description')
            $description = limpiarString($meta->getAttribute('content'));
        if ($meta->getAttribute('name') == 'keywords')
            $keywords = limpiarString($meta->getAttribute('content'));
        else
            $keywords = 'Esta página no tiene keywords';
        if ($meta->getAttribute('name') == 'date')
            $date = limpiarString($meta->getAttribute('content'));
        else
            $date = 'Esta Página no tiene fecha';
    endfor;

    $conexion = $conexion = connectDb();
    if (!$conexion) {
        die("Conexión fallida: " . mysqli_connect_error());
    }
    
    $sql_id = "select noticias.id from noticias where title='$title'";
    $resultado = mysqli_query($conexion, $sql_id);
    $fila = mysqli_fetch_row($resultado);
    $id = trim($fila[0]);

    $sql = "UPDATE `noticias` SET title='$title',date='$date',keywords='$keywords',description='$description' WHERE noticias.id='$id'";
    
    if (mysqli_query($conexion, $sql)) {
        echo '<div class="container my-5 bg-dark text-white d-block" id="addLinkContainer">
        <h5>¡Las páginas se actualizaron correctamente!</h5>
        </div>';
    } else {
        echo '<div class="container my-5 bg-dark text-white d-block" id="addLinkContainer">
        <h6>No se pudieron actualizar las noticias, intente más tarde :(</h6>
        </div>';
    }
}

/* Función para almacenar en la base de datos */
function saveOnDb($url){

    $html = file_get_contents_curl($url);
    $doc = new DOMDocument();
    @$doc->loadHTML($html);
    $nodes = $doc->getElementsByTagName('title');
    $title = limpiarString($nodes->item(0)->nodeValue);
    $metas = $doc->getElementsByTagName('meta');
    for ($i = 0; $i < $metas->length; $i++) :
        $meta = $metas->item($i);
        if ($meta->getAttribute('name') == 'description')
            $description = limpiarString($meta->getAttribute('content'));
        if ($meta->getAttribute('name') == 'keywords')
            $keywords = limpiarString($meta->getAttribute('content'));
        else
            $keywords = 'Esta página no tiene keywords';
        if ($meta->getAttribute('name') == 'date')
            $date = limpiarString($meta->getAttribute('content'));
        else
            $date = 'Esta Página no tiene fecha';
    endfor;
    
    $conexion = $conexion = connectDb();
    if (!$conexion) {
        die("Conexión fallida: " . mysqli_connect_error());
    }

    $sql = "INSERT INTO noticias (title, date, description, link, keywords) VALUES ( \"" . $title . "\", \"" . $date . "\",
    \"" . $description . "\",\"" . $url . "\",\"" . $keywords . "\")";

    if (mysqli_query($conexion, $sql)) {
        echo '<div class="container my-5 bg-dark text-white d-block" id="addLinkContainer">
        <h5>¡Las páginas se almacenaron correctamente!</h5>
        </div>';
    } else {
        echo "<p>No se pudieron actualizar las noticias, intente más tarde :(</p>";
    }
}

/*Función que sirve para indexar a nivel 1*/
function recrusivity_level1($url){
    $html = file_get_contents_curl($url);
    $doc = new DOMDocument();
    @$doc->loadHTML($html);
    $links = $doc->getElementsByTagName('a');
    for ($i = 0; $i < $links->length; $i++) :
        $oneLink = $links->item($i);
        $info = $oneLink->getAttribute('href');
        $info = $url . $info;
        if(onDataBase($info)){
            updateUrl($info);
        }else{
            saveOnDb($info);
        }
        //echo '<p>' . $info . '</p>'; //Esto es únicamente para visualizar las urls
    endfor;
}

// FUNCIONES PARA EL SCRAPPING
/* Función para obtener el contenido de una url */
function file_get_contents_curl($url){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

/* Función para quitarle caracteres innecesarios a los strings */
function limpiarString($String){
    $String = str_replace(array("|", "|", "[", "^", "´", "`", "¨", "~", "]", "'", "#", "{", "}", ".", ""), "", $String);
    return $String;
}

?>