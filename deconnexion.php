<?php

require_once 'bibli_erestou.php';

// démarrage ou reprise de la session
// pas besoin de démarrer la bufferisation des sorties
session_start();

sessionExit();

// vérification de la page d'origine
if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {

    
    // redirection vers la page d'origine
    header('Location: ' . $_SERVER['HTTP_REFERER']);
} else {
    // redirection vers la page menu.php par défaut
    header('Location: menu.php');
}
exit();

?>