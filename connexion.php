<?php
// chargement des bibliothèques de fonctions
require_once('bibli_erestou.php');
require_once('bibli_generale.php');

// bufferisation des sorties
ob_start();

// démarrage ou reprise de la session
session_start();
if (isset($_SERVER['HTTP_REFERER'])) {
    $_SESSION['previous_page'] = $_SERVER['HTTP_REFERER'];
}

if (isset($_POST['login']) && isset($_POST['password'])) {
    
    $login = $_POST['login'];
    $password = $_POST['password'];

    // Vérification de l'authentification
    $usID = verif_authentification($login, $password);
    
        // Authentification échouée
    $error_msg = "Echec de l'authentification. utilisateur inconnu ou mot de passe incorrect.";
    
}

// affichage de l'entête
affEntete('Connexion');
// affichage de la barre de navigation
affNav();

echo '<h3>Formulaire de Connexion</h3>';
echo '<p>Pour vous authentifier, remplissez le formulaire ci-dessous</p>';
if (isset($error_msg)) {
    echo '<p class="error">' . $error_msg . '</p>';
}


// Formulaire de connexion
echo '<form method="post" action="connexion.php">',
    '<table>';

affLigneInput('Login :', array('type' => 'text', 'name' => 'login', 'value' => (isset($login) ? $login : ''), 'id' => 'login', 'required' => null));
affLigneInput('Mot de passe :', array('type' => 'password', 'name' => 'password', 'id' => 'password', 'required' => null));

echo '<tr>',
        '<td colspan="2">',
            '<input type="submit" name="submit" value="Se connecter">',
            '<input type="reset" name="reset" value="Annuler">',
        '</td>',
    '</tr>',
    '<tr>',
        '<td colspan="2">',
        '</td>',
    '</tr>',
    '</table>',
    '<p>Pas encore inscrit? N\'attendez pas <a href="inscription.php">inscrivez-vous!</a></p>',

    '<input type="hidden" name="referer" value="' . (isset($_SERVER['HTTP_REFERER']) ? htmlspecialchars($_SERVER['HTTP_REFERER']) : '') . '">',

'</form>';
//montrer un message vous etes bien connecté si l'authentification est réussie
if (isset($usID)) {
    echo '<p>Vous êtes bien connecté.</p>';
}




// affichage du pied de page
affPiedDePage();

// fin du script --> envoi de la page 
ob_end_flush();

function verif_authentification($login, $password) {
    // Vérifier l'authentification avec la base de données
    $bd = bdConnect();
    
    if (!$bd) {
        echo "Erreur de connexion à la base de données.";
        return false;
    }
    $login = mysqli_real_escape_string($bd, $login);

    $sql = 'SELECT usID, usPasse, usLogin  FROM usager WHERE usLogin = "' . $login . '"';
    $res =  bdSendRequest($bd, $sql);


    if (!$res) {
        echo "Erreur lors de l'exécution de la requête SQL.";
        mysqli_close($bd);
        return false;
    }

    $T = mysqli_fetch_assoc($res);
    mysqli_free_result($res);
    mysqli_close($bd);

    if ($T && password_verify($password, $T['usPasse'])) {
        // Authentification réussie ou non
        $_SESSION['usLogin'] = $T['usLogin'];
        $_SESSION['usID'] = $T['usID'];
        // Rediriger vers la page d'où l'utilisateur vient, ou à défaut vers la page menu.php
        if (isset($_POST['referer'])) {
            header('Location: ' . $_POST['referer']);
        } else {
            header('Location: menu.php');
        }

        return $T['usID'];
    }
}
