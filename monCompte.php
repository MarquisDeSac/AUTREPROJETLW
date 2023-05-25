<?php

// chargement des bibliothèques de fonctions
require_once('bibli_erestou.php');
require_once('bibli_generale.php');
$GLOBALS['bd'] = bdConnect();
ob_start();

// démarrage ou reprise de la session
session_start();
// bufferisation des sorties


// génération de la page
affEntete('Modification de mon compte');
affNav();

affContent();
affPiedDePage();

// facultatif car fait automatiquement par PHP
ob_end_flush();

function affContent(){
    $bd = $GLOBALS['bd'];
    $usID = $_SESSION['usID'];

    $sql = "SELECT usID, usNom,usPrenom,usLogin	,usPasse, usMail From usager WHERE usID = $usID";
    $res = bdSendRequest($bd, $sql);
    $T = mysqli_fetch_assoc($res);
    $usNom = $T['usNom'];
    $usPrenom = $T['usPrenom'];
    $usLogin = $T['usLogin'];   
    $usPasse = $T['usPasse'];
    $usMail = $T['usMail'];


    // Personal information form
    echo '<section><h3>Modification des informations personnelles</h3>',
    '<form method="post" action="monCompte.php" style="max-width: 400px; margin: 0 auto; padding: 20px; background: #f5f5f5; border: 1px solid #ccc; border-radius: 5px;">',
    '<input type="hidden" name="usID" value="', $usID ,'">',
    '<p>Nom : <input type="text" name="usNom" value="', $usNom ,'"></p>',
    '<p>Prénom : <input type="text" name="usPrenom" value="', $usPrenom ,'"></p>',
    '<p>Mail : <input type="text" name="usMail" value="', $usMail ,'"></p>',
    '<input type="submit" name="btnModifierInfo" value="Modifier">',
    '</form></section>';

    // Login settings form
    echo '<section><h3>Modification des paramètres de connexion</h3>',
    '<form method="post" action="monCompte.php" style="max-width: 400px; margin: 0 auto; padding: 20px; background: #f5f5f5; border: 1px solid #ccc; border-radius: 5px;">',
    '<input type="hidden" name="usID" value="', $usID ,'">',
    '<p>Login : <input type="text" name="usLogin" value="', $usLogin ,'"></p>',
    '<p>Nouveau mot de passe : <input type="password" name="usPasse1" placeholder="Nouveau mot de passe"></p>',
    '<p>Confirmer le nouveau mot de passe : <input type="password" name="usPasse2" placeholder="Confirmer le nouveau mot de passe"></p>',
    '<input type="submit" name="btnModifierLogin" value="Modifier">',
    '</form></section>';

    // Processing personal information changes
    if (isset($_POST['btnModifierInfo'])) {
        $usID = $_POST['usID'];
        $usNom = $_POST['usNom'];
        $usPrenom = $_POST['usPrenom'];
        $usMail = $_POST['usMail'];

        $sql = "UPDATE usager SET usNom = '$usNom', usPrenom = '$usPrenom', usMail = '$usMail' WHERE usID = $usID";
        $res = bdSendRequest($bd, $sql);


        if ($res) {
            echo '<h2>Modification réussie des informations personnelles</h2>';
        } else {
            echo '<h2>Modification échouée des informations personnelles</h2>';
        }
    }

    // Processing login settings changes
    if (isset($_POST['btnModifierLogin'])) {
        $usID = $_POST['usID'];
        $usLogin = $_POST['usLogin'];
        $usPasse1 = $_POST['usPasse1'];
        $usPasse2 = $_POST['usPasse2'];

        if ($usPasse1 != $usPasse2) {
            echo '<h2>Les mots de passe ne correspondent pas</h2>';
        } else {
            $usPasse = password_hash($usPasse1, PASSWORD_DEFAULT);
            $sql = "UPDATE usager SET usLogin = '$usLogin', usPasse = '$usPasse' WHERE usID = $usID";
            $res = bdSendRequest($bd, $sql);

            if ($res) {
                echo '<h2>Modification réussie des paramètres de connexion</h2>';
            } else {
                echo '<h2>Modification échouée des paramètres de connexion</h2>';
            }
        }
    }
    echo '<h3>Information du compte</h3>';
    //calcul du nombre de repas pris
    $sql = "SELECT COUNT(*) AS nbRepas FROM repas WHERE reUsager = $usID";
    $res = bdSendRequest($bd, $sql);
    $T = mysqli_fetch_assoc($res);
    $nbRepas = $T['nbRepas'];
    echo '<p>Nombre de repas pris : ', $nbRepas, '</p>';
    //calcul du nombre de repas commentés
    $sql = "SELECT COUNT(*) AS nbRepasCommentes FROM commentaire WHERE coUsager = $usID";
    $res = bdSendRequest($bd, $sql);
    $T = mysqli_fetch_assoc($res);
    $nbRepasCommentes = $T['nbRepasCommentes'];
    echo '<p>Nombre de repas commentés : ', $nbRepasCommentes, '</p>';
    //la note moyenne des repas commentés
    $sql = "SELECT AVG(coNote) AS noteMoyenne FROM commentaire WHERE coUsager = $usID";
    $res = bdSendRequest($bd, $sql);
    $T = mysqli_fetch_assoc($res);
    $noteMoyenne = $T['noteMoyenne'];
    echo '<p>Note moyenne des repas commentés : ', $noteMoyenne, '</p>';
    //le pourcentage de repas commentés par rapport au nombre de repas pris
    $sql = "SELECT COUNT(*) AS nbRepasCommentes FROM commentaire WHERE coUsager = $usID";
    $res = bdSendRequest($bd, $sql);
    $T = mysqli_fetch_assoc($res);
    $nbRepasCommentes = $T['nbRepasCommentes'];
    $pourcentageRepasCommentes = ($nbRepasCommentes / $nbRepas) * 100;
    echo '<p>Pourcentage de repas commentés : ', $pourcentageRepasCommentes, '%</p>';
    //l'apport énergétique moyen, et l'empreinte carbone moyenne des repas pris


}


