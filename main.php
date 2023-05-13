<?php
require_once('config.php');

// Connexion à la base de données
    $conn = mysqli_connect($servername, $username, $password, $dbname);
    if (!$conn) {
        die("La connexion a échoué : " . mysqli_connect_error()."\n");
    }
    echo "Connexion réussie à la base de données MySQLi \n";

// Récupération des URL depuis la BDD
    echo "Récupération des URL depuis la BDD\n";
    $sqlreq = 'SELECT * FROM `links`';
    $res = $conn->query($sqlreq);
    while ($data = mysqli_fetch_assoc($res)) {
        $urls[] = $data;
    }

// Récupération des RSS en xml
    echo "Création des fichiers XML\n";
    foreach($urls as $url) {
        $contents = file_get_contents($url['lien']);
        file_put_contents("files/".$url['nom'].".xml", $contents);
    }

/* ------------------------------------------------------------ */

$repertoire = dirname(__FILE__)."/files/";
$files = scandir($repertoire);
$keyword = "openssl";

// Traitement de chaque fichier
echo "Traitement de chaque fichier\n";
foreach ($files as $file) {
    // Ignorer les répertoires "." et ".."
    if ($file == '.' || $file == '..') {
        continue;
    }

    // Chemin complet du fichier
    $cheminFichier = $repertoire . '/' . $file;

    // Vérifier s'il s'agit d'un fichier XML
    if (is_file($cheminFichier) && pathinfo($cheminFichier, PATHINFO_EXTENSION) === 'xml') {
        $xml = simplexml_load_file($cheminFichier);
        foreach ($xml->channel->item as $item) {
            // Accéder aux éléments de l'item
            $title = $item->title;
            $description = $item->description;
            $link = $item->link;
            $pubDate = $item->pubDate;
        
            // Recherche du mot en ignorant la casse
            if (preg_match('/' . $keyword . '/i', $title) or preg_match('/' . $keyword . '/i', $description)) {

                // Vérification de doublon
                $checkreq = "SELECT * FROM `flux` WHERE title='".$title."'";
                $res = $conn->query($checkreq);
                if($res->num_rows > 0) {
                    continue;
                } else {
                    $sqlreq = "
                        INSERT INTO `flux`(`title`, `description`, `link`, `pubDate`) VALUES (
                            '".$title."',
                            '".$description."',
                            '".$link."',
                            '".$pubDate."')
                        ";
            
                    if ($conn->query($sqlreq) === TRUE) {
                        echo "Nouvelle entrée ajoutée\n";
                    } else {
                        echo "Error: " . $sqlreq . "<br>" . $conn->error."\n";
                    }
                }
                
            }
        }
    }
}