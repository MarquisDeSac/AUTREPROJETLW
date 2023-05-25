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
        if (isset($_GET['meDate'])) {
            // Récupérer les données du formulaire
            $meDate = isset($_GET['meDate']) ? $_GET['meDate'] : null;
 
            
            // Afficher le formulaire de confirmation de la modification
            echo '<div class="centered-delete">',
            '<form class="delete-form" method="post" action="confirm_delete.php">',
                '<input type="hidden" name="meDate" value="', $meDate ,'">',
                '<p>Voulez-vous vraiment suprrimer le commentaire  ?</p>',
                '<input class="delete-confirm-btn" type="submit" name="btnConfirmer" value="Confirmer">',
                '<input class="delete-cancel-btn" type="submit" name="btnAnnuler" value="Annuler">',
            '</form>',
        '</div>';
        
        } 
    // Vérifier si le formulaire de confirmation de suppression a été soumis
    if (isset($_POST['btnConfirmer'])) {
        // Récupérer les données du formulaire
        $meDate = $_POST['meDate'];
        $coUsager = $_SESSION['usID'];
        // Supprimer le commentaire correspondant à la date du repas et à l'utilisateur
        $bd = $GLOBALS['bd'];
        $sql = "DELETE FROM commentaire WHERE coUsager = '$coUsager' AND coDateRepas = '$meDate'";
        $res = bdSendRequest($bd, $sql);

        // Vérifier si la suppression a réussi
        if ($res) {
            echo '<h2>Commentaire supprimé avec succès</h2>';
        } else {
            echo '<p>Erreur lors de la suppression du commentaire</p>';
        }
    } 
    // Vérifier si le bouton "Annuler" a été cliqué
    else if (isset($_POST['btnAnnuler'])) {
        // Rediriger vers la page précédente
        header('Location: menu.php');
        exit();
    }
}

?>
