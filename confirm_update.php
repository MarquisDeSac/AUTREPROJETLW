<?php

// chargement des bibliothèques de fonctions
require_once('bibli_erestou.php');
require_once('bibli_generale.php');
$GLOBALS['bd'] = bdConnect();

// bufferisation des sorties
ob_start();

// démarrage ou reprise de la session
session_start();
if (isset($_SERVER['HTTP_REFERER'])) {
    $_SESSION['previous_page'] = $_SERVER['HTTP_REFERER'];
}

// affichage de l'entête
affEntete('Confirmation de la suppression');
// affichage de la barre de navigation
affNav();
// contenu de la page
affContent();
// affichage du pied de page
affPiedDePage();

// facultatif car fait automatiquement par PHP
ob_end_flush();

// fonction pour afficher le contenu
function affContent() {
    // Vérifier si les données du formulaire sont présentes
    if (isset($_POST['meDate'], $_POST['coDatePublication'], $_POST['coDateRepas'], $_POST['coUsager'], $_POST['coCommentaire'])) {
        // Récupérer les données du formulaire
        $meDate = $_POST['meDate'];
        $coDatePublication = $_POST['coDatePublication'];
        $coDateRepas = $_POST['coDateRepas'];
        $coUsager = $_POST['coUsager'];
        $coCommentaire = $_POST['coCommentaire'];
        $note = isset($_POST['note']) ? $_POST['note'] : null;
        
        // Afficher le formulaire de confirmation de la modification
        echo '<div class="centered-form">',
        '<form class="my-form" method="post" action="confirm_update.php">',
            '<input type="hidden" name="meDate" value="', $meDate ,'">',
            '<input type="hidden" name="coDatePublication" value="', $coDatePublication ,'">',
            '<input type="hidden" name="coDateRepas" value="', $coDateRepas ,'">',
            '<input type="hidden" name="coUsager" value="', $coUsager ,'">',
            '<input type="hidden" name="coCommentaire" value="', $coCommentaire ,'">',
            '<p>Voulez-vous vraiment modifier le commentaire suivant ?</p>',
            '<p>Date du repas : ', $coDateRepas, '</p>',
            '<p>Commentaire : ', $coCommentaire, '</p>',
            '<input type="submit" name="btnConfirmer" value="Confirmer">',
            '<input type="submit" name="btnAnnuler" value="Annuler">',
        '</form>',
    '</div>';
    
    
    } 
    
    if (isset($_POST['btnConfirmer'])) {
        // Récupérer les données du formulaire de modification
        $comment = $_POST['coCommentaire'];
        $note = isset($_POST['note']) ? $_POST['note'] : null;
        $coUsager = $_POST['coUsager'];
        $coDateRepas = $_POST['coDateRepas'];

        // Effectuer la mise à jour du commentaire (par exemple, enregistrer les données dans la base de données)
        $bd = $GLOBALS['bd'];

        // Construire la requête de mise à jour du commentaire
        $sql = "UPDATE commentaire SET coTexte = '$comment' WHERE coUsager = '$coUsager' AND coDateRepas = '$coDateRepas'";

        // Exécuter la requête
        $res = bdSendRequest($bd, $sql);
        
        header('Location: menu.php');
        if ($res) {
            echo '<p>Commentaire modifié avec succès</p>';
        } else {
            echo '<p>Erreur lors de la modification du commentaire</p>';
        }
    } 
    // Rediriger vers la page précédente
    if (isset($_POST['btnAnnuler'])) {
        // Rediriger vers menu.php
        header('Location: menu.php');
        exit();
        

    }
    

}
