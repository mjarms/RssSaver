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
        //file_put_contents("files/".$url['nom'].".xml", $contents);
    }

/* ------------------------------------------------------------ */

$repertoire = dirname(__FILE__)."/files";
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

    echo "Traitement du fichier".$cheminFichier."\n";

    // Vérifier s'il s'agit d'un fichier XML
    if (is_file($cheminFichier) && pathinfo($cheminFichier, PATHINFO_EXTENSION) === 'xml') {
        
        // Vérification de la structure XML
        echo "Vérification de la structure XML\n";
        foreach($urls as $url){
            if($url['nom'] == explode('.',$file)[0]) {
                $structure = $url['structure'];
                echo "Structure trouvée : $structure\n";
                break;
            }
        }

        $chaine = implode('->',explode(',',$structure));
        var_dump($chaine);
        // Retourne propriété1->propriété2 selon la structure

        $xml = simplexml_load_file($cheminFichier);

        foreach ($xml->$chaine as $item) {
            // Accéder aux éléments de l'item
            $title = $item->title;
            $description = $item->description;
            $link = $item->link;
            $pubDate = $item->pubDate;

            //echo $title."\n";
        
            // Recherche du mot en ignorant la casse (regex)
            if (preg_match('/' . $keyword . '/i', $title) or preg_match('/' . $keyword . '/i', $description)) {

                // Vérification de doublon
                $checkreq = "SELECT * FROM `flux` WHERE title='".$title."'";
                $res = $conn->query($checkreq);
                if($res->num_rows > 0) {
                    echo "Article déjà existant\n";
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

echo "Fin de la récupération des flux RSS\n";

/* ------------------------------------------------------------ */

echo "Création du fichier XML final\n";

// Requête pour récupérer les données de la base
    $sqlflux = "SELECT * FROM  `flux`";
    $result = $conn->query($sqlflux);

// Création du document XML
    $xml = new DOMDocument('1.0', 'utf-8');
    $rss = $xml->createElement('rss');
    $rss->setAttribute('version', '2.0');
    $xml->appendChild($rss);

// Création de l'élément channel
    $channel = $xml->createElement('channel');
    $rss->appendChild($channel);

// Boucle pour parcourir les données et créer les éléments item
while ($row = $result->fetch_assoc()) {
    $item = $xml->createElement('item');
    $channel->appendChild($item);

    // Création des éléments title, description, etc. (à adapter selon les données de votre base)
    $title = $xml->createElement('title', $row['title']);
    $item->appendChild($title);

    $description = $xml->createElement('description', $row['description']);
    $item->appendChild($description);

    $link = $xml->createElement('link', $row['link']);
    $item->appendChild($link);

    $pubDate = $xml->createElement('pubDate', $row['pubDate']);
    $item->appendChild($pubDate);
}

// Enregistrement du document XML dans un fichier
$xml->formatOutput = true;
$xml->save('output.xml');

echo "Fin du script\n";