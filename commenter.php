<?php
// chargement des bibliothèques de fonctions
require_once('bibli_erestou.php');
require_once('bibli_generale.php');
$GLOBALS['bd'] = bdConnect();
$GLOBALS['cbIndex']=0;
// bufferisation des sorties
ob_start();

// démarrage ou reprise de la session
session_start();
if (isset($_SERVER['HTTP_REFERER'])) {
    $_SESSION['previous_page'] = $_SERVER['HTTP_REFERER'];
}

// affichage de l'entête
affEntete('Commentaire');
// affichage de la barre de navigation
affNav();
// contenu de la page
affContent( $AdejaCommente = false );
//date d ajd
// fin du script --> envoi de la page 
ob_end_flush();
function afficherForm($err){
    if ($err == '') {
        echo '<p style="color: red;">', $err, '</p>';
    }
    echo   '<input type = "hidden" name = "MAX_FILE_SIZE" value = "1000000" />',
        '<input type="file" name="image"><br>';
}

function affContent( $AdejaCommente) {
    $meDate = ""; // Declare the variable here
    $err="";
    
    echo '<h3>Formulaire de Commentaire</h3>';
    if (isset($error_msg)) {
        echo '<p class="error">' . $error_msg . '</p>';
    }

    if(isset($_GET['meDate'])) {
        $meDate = $_GET['meDate'];
    } else {
        // Set a default value or handle the error appropriately
        $meDate = "default_value";
    }
    

    $meDate = isset($_GET['meDate']) ? $_GET['meDate'] : null;
    $date= date("YmdHi");
    $coDatePublication = $date;
    $coDateRepas = $meDate;
    $coUsager = $_SESSION['usID'];
    $coCommentaire = isset($_GET['coCommentaire']) ? $_GET['coCommentaire'] : '';
    $note = isset($_GET['note']) ? $_GET['note'] : null;
    //voir si l utilisateur a deja commente le restaurant
    $bd = $GLOBALS['bd'];
    $sql = "SELECT coUsager, coDatePublication, coDateRepas, meDate 
    FROM commentaire 
    INNER JOIN menu ON coDateRepas = meDate 
    WHERE coUsager = {$_SESSION['usID']} AND coDateRepas = '$meDate'";
    $res = bdSendRequest($bd, $sql);

    if(mysqli_num_rows($res) > 0){
        $AdejaCommente = true;
        echo "<em>Vous pouvez modifier  ou supprimer votre commentaire car vous avez déjà commenté </em>";
        echo '<form class="comment-form" method="post" action="confirm_update.php">',
            '<input type="hidden" name="meDate" value="', $meDate ,'">',
            '<input type="hidden" name="coDatePublication" value="', $coDatePublication ,'">',
            '<input type="hidden" name="coDateRepas" value="', $coDateRepas ,'">',
            '<input type="hidden" name="coUsager" value="', $coUsager ,'">',
            '<input type="hidden" name="coCommentaire" value="', $coCommentaire ,'">',
            '<input type="hidden" name="note" value="' . $note . '">',
        
            '<p class="form-info">Entrez votre commentaire ici pour le modifier :</p>',
            '<textarea class="form-textarea" name="coCommentaire" required maxlength="1000"></textarea><br>',
        
            '<p class="form-info">Sélectionnez votre image :</p>',
            '<input class="form-input" type="file" name="image"><br>',
            '<input class="form-confirm-btn" type="submit" name="btnConfimer" value="Modifier">',
            '<a class="form-delete-linky" href="confirm_delete.php?meDate=', $meDate, '">Supprimer</a>',
        '</form>';
        


// Lien pour la suppression de commentaire


    }else{
        $AdejaCommente = false;
        echo '<p>Vous n\'avez pas encore commenté ce restaurant</p>';
        echo '<form class="new-comment-form" method="post" action="commenter.php?meDate=',$meDate,'" enctype="multipart/form-data"',$_SERVER['PHP_SELF'],'>',
            '<input type="hidden" name="meDate" value="', $meDate ,'">',
            '<p class="new-form-info">Entrez votre commentaire ici :</p>',
        
            '<textarea class="new-form-textarea" name="comment" required maxlength="1000"></textarea><br>',
            '<p class="new-form-info">Entrez votre note :</p>',
            '<div class="new-form-radios">',
                '<input type="radio" name="note" value="1" checked> 1<br>',
                '<input type="radio" name="note" value="2"> 2<br>',
                '<input type="radio" name="note" value="3"> 3<br>',
                '<input type="radio" name="note" value="4"> 4<br>',
                '<input type="radio" name="note" value="5"> 5<br>',
            '</div>',
            '<p class="new-form-info">Sélectionnez votre image :</p>';
            afficherForm($err);
        echo '<input class="new-form-submit-btny" type="submit" name="submit_comment" value="Soumettre">',
        '</form>';
        

    }


    
    if($AdejaCommente == false){
        if(isset($_POST['submit_comment'])){

       /* if(! @is_uploaded_file($_FILES['image']['tmp_name'])) {
            afficherForm($err);
            exit("Erreur dans l'upload de l'image");

        }*/ 
        $uploadDir = '../upload/'; // Destination directory
        $uploadFile = $uploadDir . basename($_FILES['image']['name']);
        if( @move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile)) {
            $err= "Le fichier a été correctement uploadé";
        } else {
            $err= "Erreur lors de l'upload";
        }
        

     
        
        
            $meDate = isset($_POST['meDate']) ? $_POST['meDate'] : (isset($_GET['meDate']) ? $_GET['meDate'] : null);

            $comment = $_POST['comment'];
            $note = $_POST['note'];
            $usID = $_SESSION['usID'];
            $coDateRepas = $meDate;
            $coDatePublication = $date;

                $sql = "INSERT INTO commentaire (coUsager, coDatePublication, coDateRepas, coTexte, coNote) VALUES ('$usID', '$coDatePublication', '$coDateRepas', '$comment', '$note')";
                $res = bdSendRequest($bd, $sql);
                if($res){
                echo '<p>Commentaire ajouté avec succès</p>';
            }else{
                echo '<p>Erreur lors de l\'ajout du commentaire</p>';
            }
        }
        $AdejaCommente = true;
    }



    // affichage du pied de page
affPiedDePage();
}
