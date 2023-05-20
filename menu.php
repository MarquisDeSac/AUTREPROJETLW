<?php

// chargement des bibliothèques de fonctions
require_once('bibli_erestou.php');
require_once('bibli_generale.php');
$GLOBALS['bd'] = bdConnect();
$GLOBALS['cbIndex'] = 1;

// bufferisation des sorties
ob_start();

// démarrage ou reprise de la session
session_start();
//
if (isset($_POST['btnCommander'])) {
    traitementMenuL();
}
else{
    $erreurs = null;
}
// affichage de l'entête
affEntete('Menus et repas');
// affichage de la barre de navigation
affNav();

// contenu de la page 
affContenuL();


// fin du script --> envoi de la page 
ob_end_flush();


//_______________________________________________________________
/**
 * Vérifie la validité des paramètres reçus dans l'URL, renvoie la date affichée ou l'erreur détectée
 *
 * La date affichée est initialisée avec la date courante ou actuelle.
 * Les éventuels paramètres jour, mois, annee, reçus dans l'URL, permettent respectivement de modifier le jour, le mois, et l'année de la date affichée.
 *
 * @return int|string      string en cas d'erreur, int représentant la date affichée au format AAAAMMJJ sinon
 */
function dateConsulteeL() : int|string {
    if (!parametresControle('GET', [], ['jour', 'mois', 'annee'])){
        return 'Nom de paramètre invalide détecté dans l\'URL.';
    }
   
    // date d'aujourd'hui
    list($jour, $mois, $annee) = getJourMoisAnneeFromDate(DATE_AUJOURDHUI);
    // vérification si les valeurs des paramètres reçus sont des chaînes numériques entières
    foreach($_GET as $cle => $val){
        if (! estEntier($val)){
            return 'Valeur de paramètre non entière détectée dans l\'URL.';
        }
        // modification du jour, du mois ou de l'année de la date affichée
        $$cle = (int)$val;
    }

    if ($annee < 1000 || $annee > 9999){
        return 'La valeur de l\'année n\'est pas sur 4 chiffres.';
    }
    if (!checkdate($mois, $jour, $annee)) {
        return "La date demandée \"$jour/$mois/$annee\" n'existe pas.";
    }
    if ($annee < ANNEE_MIN){
        return 'L\'année doit être supérieure ou égale à '.ANNEE_MIN.'.';
    }
    if ($annee > ANNEE_MAX){
        return 'L\'année doit être inférieure ou égale à '.ANNEE_MAX.'.';
    }
    return $annee*10000 + $mois*100 + $jour;
}

//_______________________________________________________________
/**
 * Génération de la navigation entre les dates
 *
 * @param  int     $date   date affichée
 *
 * @return void
 */
function affNavigationDateL(int $date): void{
    list($jour, $mois, $annee) = getJourMoisAnneeFromDate($date);

    // on détermine le jour précédent (ni samedi, ni dimanche)
    $jj = 0;
    do {
        $jj--;
        $dateVeille = getdate(mktime(12, 0, 0, $mois, $jour+$jj, $annee));
    } while ($dateVeille['wday'] == 0 || $dateVeille['wday'] == 6);
    // on détermine le jour suivant (ni samedi, ni dimanche)
    $jj = 0;
    do {
        $jj++;
        $dateDemain = getdate(mktime(12, 0, 0, $mois, $jour+$jj, $annee));
    } while ($dateDemain['wday'] == 0 || $dateDemain['wday'] == 6);

    $dateJour = getdate(mktime(12, 0, 0, $mois, $jour, $annee));
    $jourSemaine = array('Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi');

    // affichage de la navigation pour choisir le jour affiché
    echo '<h2>',
            $jourSemaine[$dateJour['wday']], ' ',
            $jour, ' ',
            getTableauMois()[$dateJour['mon']-1], ' ',
            $annee,
        '</h2>',

        // on utilise un formulaire qui renvoie sur la page courante avec une méthode GET pour faire apparaître les 3 paramètres sur l'URL
        '<form id="navDate" action="menu.php" method="GET">',
            '<a href="menu.php?jour=', $dateVeille['mday'], '&amp;mois=', $dateVeille['mon'], '&amp;annee=',  $dateVeille['year'], '">Jour précédent</a>',
            '<a href="menu.php?jour=', $dateDemain['mday'], '&amp;mois=', $dateDemain['mon'], '&amp;annee=', $dateDemain['year'], '">Jour suivant</a>',
            'Date : ';

    affListeNombre('jour', 1, 31, 1, $jour);
    affListeMois('mois', $mois);
    affListeNombre('annee', ANNEE_MIN, ANNEE_MAX, 1, $annee);

    echo    '<input type="submit" value="Consulter">',
        '</form>';
        // le bouton submit n'a pas d'attribut name. Par conséquent, il n'y a pas d'élément correspondant transmis dans l'URL lors de la soumission
        // du formulaire. Ainsi, l'URL de la page a toujours la même forme (http://..../php/menu.php?jour=7&mois=3&annee=2023) quel que soit le moyen
        // de navigation utilisé (formulaire avec bouton 'Consulter', ou lien 'précédent' ou 'suivant')
}

//_______________________________________________________________
/**
 * Récupération du menu de la date affichée
 *
 * @param int       $date           date affichée
 * @param array     $menu           menu de la date affichée (paramètre de sortie)
 *
 * @return bool                     true si le restoU est ouvert, false sinon
 */
function bdMenuL(int $date, array &$menu) : bool {

    // ouverture de la connexion à la base de données
    $bd = $GLOBALS['bd'];

    // Récupération des plats qui sont proposés pour le menu (boissons incluses, divers exclus)
    $sql = "SELECT plID, plNom, plCategorie, plCalories, plCarbone
            FROM plat LEFT JOIN menu ON (plID=mePlat AND meDate=$date)
            WHERE mePlat IS NOT NULL OR plCategorie = 'boisson'";

    // envoi de la requête SQL
    $res = bdSendRequest($bd, $sql);

    // Quand le resto U est fermé, la requête précédente renvoie tous les enregistrements de la table Plat de
    // catégorie boisson : il y en a NB_CAT_BOISSON
    if (mysqli_num_rows($res) <= NB_CAT_BOISSON) {
        // libération des ressources
        mysqli_free_result($res);
        // fermeture de la connexion au serveur de base de  données
        return false; // ==> fin de la fonction bdMenuL()
    }


    // tableau associatif contenant les constituants du menu : un élément par section
    $menu = array(  'entrees'           => array(),
                    'plats'             => array(),
                    'accompagnements'   => array(),
                    'desserts'          => array(),
                    'boissons'          => array()
                    );
    

    // parcours des ressources :
    while ($tab = mysqli_fetch_assoc($res)) {
        switch ($tab['plCategorie']) {
            case 'entree':
                $menu['entrees'][] = $tab;
                break;
            case 'viande':
            case 'poisson':
                $menu['plats'][] = $tab;
                break;
            case 'accompagnement':
                $menu['accompagnements'][] = $tab;
                break;
            case 'dessert':
            case 'fromage':
                $menu['desserts'][] = $tab;
                break;
            case 'boisson':
                $menu['boissons'][] = $tab;
                break;
                default:

                $menu['suppléments'][] = $tab;
                break;
 
        }
    }
    // libération des ressources
    mysqli_free_result($res);
    // fermeture de la connexion au serveur de base de  données
    return true;
}








//_______________________________________________________________
/**
 * Affichage d'un des constituants du menu.
 *
 * @param  array       $p      tableau associatif contenant les informations du plat en cours d'affichage
 * @param  string      $catAff catégorie d'affichage du plat
 *
 * @return void
 */
$estConnecter =estAuthentifie();

function affPlatL(array $p, string $catAff,bool $estConnecter,array $idCommande = [] ): void {
    $AdejaCommander = true;
    if ($catAff == 'suppléments' ){ //number
        $id = $name = "nb{$p['plID']}";
        $type = 'number';
    }

    if ($catAff != 'accompagnements'){ //radio bonton
        $name = "rad$catAff";
        $id = "{$name}{$p['plID']}";
        $type = 'radio';
    }
    
    
    else { //checkbox
        $id = $name = "cb{$GLOBALS['cbIndex']}  ";
        $GLOBALS['cbIndex'] = $GLOBALS['cbIndex'] + 1;
     
        $type = 'checkbox';
     
    }

    //connecter et date changer le disabled
    if($estConnecter && dateConsulteeL() == DATE_AUJOURDHUI){
        $disabled = '';
    }
    else{
        $disabled = 'disabled';
    }

    $check ='';
    if (in_array($p['plID'], $idCommande)){
        $check = 'checked';
        
    }
    //checker les plat deja commander
    if(isset($_POST['btnCommander'])){
        if(isset($_POST['radentrees'])){
            if($_POST['radentrees'] == $p['plID']){
                $check = 'checked';
            }
        }
        if(isset($_POST['radplats'])){
            if($_POST['radplats'] == $p['plID']){
                $check = 'checked';
            }
        }
        if(isset($_POST['raddesserts'])){
            if($_POST['raddesserts'] == $p['plID']){
                $check = 'checked';
            }
        }
        if(isset($_POST['radboissons'])){
            if($_POST['radboissons'] == $p['plID']){
                $check = 'checked';
            }
        }
        //checker les accompagnement
        foreach($_POST as $cle => $val){
            if (substr($cle, 0, 2) == 'cb'){
                if($val == $p['plID']){
                    $check = 'checked';
                }
            }
        }

    }

      
    // protection des sorties contre les attaques XSS
    $p['plNom'] = htmlProtegerSorties($p['plNom']);


    echo    '<input id="', $id, '" name="', $name, '" type="', $type, '" value="', $p['plID'], '" ', $disabled,' ', $check, '>',
    '<label for="', $id,'">',
        '<img src="../images/repas/', $p['plID'], '.jpg" alt="', $p['plNom'], '" title="', $p['plNom'], '">',
        $p['plNom'], '<br>', '<span>', $p['plCarbone'],'kg eqCO2 / ', $p['plCalories'], 'kcal</span>', 
    '</label>';

}

//_______________________________________________________________
/**
 * la demande doit contenir au minimum un accompagnement et une boisson
 *il est possible de choisir plusieurs accompagnements

 * @return void
 */

 function traitementMenuL(): array {
    $erreurs = [];

    // Vérification de la validité des paramètres reçus dans le formulaire
    //la demande doit contenir au minimum un accompagnement et une boisson

    if (isset($_POST['btnCommander'])) {
        if(!isset($_POST['radboissons'])){
            $erreurs[] = 'Vous devez choisir une boisson.';
        }
       //verifier les accompagnement il faut au moins 1
        $nbACOMP = 0;
        foreach($_POST as $cle => $val){
            if (substr($cle, 0, 2) == 'cb'){
                $nbACOMP++;
            }
        }
        if($nbACOMP == 0){
            $erreurs[] = 'Vous devez choisir au moins un accompagnement.';
        }
   
    }

    return $erreurs;
}


 

//recuperer les commentaires
function affCommentaires(int $date) : array {
    $bd = $GLOBALS['bd'];
    $sql = "SELECT usID, usNom, usPrenom, coDateRepas, coDatePublication, coUsager, coTexte, coNote
            FROM commentaire JOIN usager ON (coUsager = usID)
            WHERE coDateRepas = $date
            ORDER BY coDatePublication DESC, coUsager DESC;
            ";
    $res = bdSendRequest($bd, $sql);
    $commentaires = [];
    $nombreCommentaires = 0;
    $noteMoyenne = 0;
    while ($tab = mysqli_fetch_assoc($res)) {
        $commentaires[] = $tab;
        $noteMoyenne += $tab['coNote'];
        $nombreCommentaires++;

    }
    if($nombreCommentaires != 0){
        $noteMoyenne = $noteMoyenne / $nombreCommentaires;
    }
    else{
        $noteMoyenne = 0;
    }
    if($nombreCommentaires == 0){
        echo '<p>Il n\'y a pas encore de commentaire pour ce jour.</p>';
    }
    else{
        echo '<h4> Commenataires sur ce menu </h4>';
        echo '<p>La note moyenne est de ', $noteMoyenne, ' / 5. sur la base de ',$nombreCommentaires,' commentaires</p>';
    }
    foreach($commentaires as $commentaire){
        //date
        list($min,$heure,$jour,$mois,$annee) = getMinuteHeureJourMoisAnneeFromDate($commentaire['coDatePublication']);
        //ajouter une image pour le commentaire Celle-ci est donc située dans un dossier particulier nommé upload,
        $Image= '../upload/'.$commentaire['coDateRepas'].'_'.$commentaire['usID'].'.jpg';
        echo '<article class="comment" id="comment_'.$commentaire['usID'].'">';
        if(is_file($Image)){
          echo '<img src="',$Image,'" alt="',$commentaire['usNom'],'" title="',$commentaire['usNom'],'">';
        }
        //protection des sorties contre les attaques XSS
        $commentaire['usNom'] = htmlProtegerSorties($commentaire['usNom']);
        $commentaire['usPrenom'] = htmlProtegerSorties($commentaire['usPrenom']);
        $commentaire['coTexte'] = htmlProtegerSorties($commentaire['coTexte']);
        $commentaire['coNote'] = htmlProtegerSorties($commentaire['coNote']);
        //exemple commentaire de ERIC MErlet le 8 mars 2023 à 12h30
        echo '<h2>Commentaire de ', $commentaire['usPrenom'], ' ', $commentaire['usNom'], ', publié le ', $jour, ' ', $mois, ' ', $annee, ' à ', $heure, 'h', $min, '</h2>';
        echo'<p class = "italique">',$commentaire['coTexte'],'</p>';
        echo'<footer> NOTE: ',$commentaire['coNote'],'/5</footer>';    
        echo '</article>';
    }
    
    mysqli_free_result($res);
    return $commentaires;

}



//_______________________________________________________________
/**
 * Génère le contenu de la page.
 *
 * @return void
 */
function affContenuL(): void {
    $estConnecter =estAuthentifie();
    $date = dateConsulteeL();
    // si dateConsulteeL() renvoie une erreur
    if (is_string($date)){
        echo    '<h4 class="center nomargin">Erreur</h4>',
                '<p>', $date, '</p>',
                (strpos($date, 'URL') !== false) ?
                '<p>Il faut utiliser une URL de la forme :<br>http://..../php/menu.php?jour=7&mois=3&annee=2023</p>':'';
        return; // ==> fin de la fonction affContenuL()
    }
    // si on arrive à ce point de l'exécution, alors la date est valide
    
    // Génération de la navigation entre les dates 
    affNavigationDateL($date);
    //date d aujourhui
    list($jour, $mois, $annee) = getJourMoisAnneeFromDate(DATE_AUJOURDHUI);

    $dateAUJ = DATE_AUJOURDHUI;
    $AdejaCommander = true;
    $peutCommander = false;
    $idCommande = [];

    if ($estConnecter) {
        $bd =$GLOBALS['bd'];
        if ($date < $dateAUJ) {
            $sql = "SELECT * FROM `repas` WHERE `reDate` = '" . $date . "' AND `reUsager` = '" . $_SESSION['usID'] . "'";
            $res = bdSendRequest($bd, $sql);
            if (mysqli_num_rows($res) > 0) {
                $AdejaCommander = true;
            } 
            while ($tab = mysqli_fetch_assoc($res)) {
                $idCommande[] = $tab['rePlat'];
            
            }
            mysqli_free_result($res);
        }
        if ($date == $dateAUJ && $AdejaCommander == false) {
            $peutCommander = true;
        }

    }
    
    // menu du jour
    $menu = [];

    $restoOuvert = bdMenuL($date, $menu);

    if (! $restoOuvert){
        echo '<p>Aucun repas n\'est servi ce jour.</p>';
        return; // ==> fin de la fonction affContenuL()
    }
    
    // titre h3 des sections à afficher
    $h3 = array('entrees'           => 'Entrée',
                'plats'             => 'Plat', 
                'accompagnements'   => 'Accompagnement(s)',
                'desserts'          => 'Fromage/dessert', 
                'boissons'          => 'Boisson'
                );
   
    // affichage du menu
    //affichage de la notice
    if($estConnecter && dateConsulteeL() == DATE_AUJOURDHUI && !$peutCommander ){
    echo "<div class = 'notice'>
    <img  src = '../images/notice.png' class = 'noticeimage' alt = 'notice'>
        <div class = 'text'>
            Tous les plateaux sont composés avec un verre, un couteau, une fouchette et une petite cuillère.      
        <form action='menu.php' method='POST'>
        </div>
    </div>";
    }

    //affichage des erreurs
 
    $erreurs = traitementMenuL();

if(count($erreurs) > 0){
    echo    '<div class="error">Les erreurs suivantes ont été rencontrées durant le traitement de votre commande :',
    '<ul>';
    echo '<li> choix de plat incorrect </li>';
        
    foreach($erreurs as $e){
        echo '<li>', $e, '</li>';
    }
    
    echo '</ul></div>';
}else{
    if(isset($_POST['btnCommander'])){
        echo '<div class="success">Votre commande a été enregistrée avec succès.</div>';
        //enregistrer la commande dans la base de données
        $portions = 1;
        $reDate = dateConsulteeL();
        $reUsager = $_SESSION['usID'];
    
        if(isset($_POST['nbPains'])){
            $portions += $_POST['nbPains'];
        }
        if(isset($_POST['nbServiettes'])){
            $portions += $_POST['nbServiettes'];
        }
        $bd=$GLOBALS['bd'];
        
        foreach($_POST as $cle => $val){
            if (substr($cle, 0, 2) == 'cb'){
                $sql = "SELECT 1 FROM repas WHERE reDate = '$reDate' AND rePlat = $val AND reUsager = $reUsager";
                $res = bdSendRequest($bd, $sql);
                if (mysqli_num_rows($res) == 0){
                    $sql = "INSERT INTO repas (reDate, rePlat, reUsager, reNbPortions) VALUES  ('$reDate', $val, $reUsager, $portions)";
                    $res = bdSendRequest($bd, $sql);
                }
            }
        }
        $categorieArr = array('radentrees', 'radplats', 'raddesserts', 'radboissons');
        foreach($categorieArr as $categorie){
            if(isset($_POST[$categorie])){
                $sql = "SELECT 1 FROM repas WHERE reDate = '$reDate' AND rePlat = '{$_POST[$categorie]}' AND reUsager = $reUsager";
                $res = bdSendRequest($bd, $sql);
                if (mysqli_num_rows($res) == 0){
                    $sql = "INSERT INTO repas (reDate, rePlat, reUsager, reNbPortions) VALUES  ('$reDate', '{$_POST[$categorie]}', $reUsager, $portions)";
                    $res = bdSendRequest($bd, $sql);
                }
            }
        }

    }
    
}

    

    
    //affichage des plats


    foreach($menu as $key => $value){
        
        echo '<section class="bcChoix"><h3>', $h3[$key], '</h3>';
        //afficher pas de plat pour ENTREE plat et dessert en utilisant un switch
        if(!$peutCommander  && $estConnecter && dateConsulteeL() == DATE_AUJOURDHUI){
            switch($key)    {
                case 'entrees':
                    echo '<input type = "radio" name = "radentrees" value="aucune" id="id0" >',
                     '<label for = "id0" class="click"> <img src=  "../images/repas/0.jpg" alt="0">Pas d\'entrée</label>';
            break;
                case 'plats':
                    echo '<input type = "radio" name = "radplats" value="aucun" id="id1" >',
                     '<label for = "id1" class="click"> <img src=  "../images/repas/0.jpg" alt="0">Pas de plat</label>';
            break;
                case 'desserts':
                    echo '<input type = "radio" name = "raddesserts" value="aucun" id="id2" >',
                     '<label for = "id2" class="click"> <img src=  "../images/repas/0.jpg" alt="0">Pas de dessert</label>';
            break;
                    
            }
            

        
        }
        foreach ($value as $p) {
            affPlatL($p, $key, $estConnecter,$idCommande,$AdejaCommander);
        }
        echo '</section>';
        
    }
    
    
    //affichage des suppléments
    if($estConnecter && dateConsulteeL() == DATE_AUJOURDHUI  && $AdejaCommander ){
        echo '<h3>Supplément</h3>',
        '<div class="supplement-container">',
            '<div class="supplement">',
                '<label for="id18"><img src="../images/repas/38.jpg" alt="41">Pain </label>',
                '<input type="number" name="nbPains" value="0"  id="id18" min="0" max="2">',
                
            '</div>',
           ' <div class="supplement">',
                '<label for="id19"><img src="../images/repas/39.jpg" alt="42">Serviette en papier</label>',

                '<input type="number" name="nbServiettes" value="1"  id="id19" min="1" max="5">',
            '</div>',
        '</div>',
        '<h3>Validation</h3>',
        '<div class ="validation-container">',
        '<p id="validation"><img src="../images/attention.png" alt="attention" > Attention, une fois la commande réalisée, il n"est pas possible de la modifier.',
        'Toute commande non-récupérée sera majorée d"une somme forfaitaire de 10 euros.',
        '</p>',
        '</div>',
        
    '<div class="submit-container">',
        '<input type="submit" name="btnCommander" value="Commander" class="submit-btn click">',

        '<input type="submit" value="Annuler" class="submit-btn click">',
    '</div>';
  
   
    }else{
         //affichage des commentaires
         echo '<h3>Commentaires</h3>';
         $commentaires = affCommentaires($date);
  
    echo '</form>',
    '</div>';


 }
}

affPiedDePage();



    


