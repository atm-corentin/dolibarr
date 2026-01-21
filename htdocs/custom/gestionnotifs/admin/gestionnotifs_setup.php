<?php
$res=0;
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");       // For root directory
if (! $res && file_exists("../../../../main.inc.php")) $res=@include("../../../../main.inc.php"); // For "custom"

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

dol_include_once('/core/class/html.form.class.php');
dol_include_once('/gestionnotifs/lib/gestionnotifs.lib.php');

$langs->load('admin');
$langs->load('gestionnotifs@gestionnotifs');
$langs->loadLangs(array("companies", "admin", "members", "categories"));

$modname = $langs->trans("ModuleSetup").' '.$langs->trans("gestionnotifs");

if (!$user->admin) accessforbidden();

$action = GETPOST('action','alpha');
global $db;
$form = new Form($db);

if ($action == 'enablesendmail') {
    $name = GETPOST ( 'name', 'text' ); 
    $value = GETPOST ( 'value', 'int' );

    $error = 0;
    if ($value){
        $res = dolibarr_set_const($db, $name, 1, 'chaine', 0, '', $conf->entity);
    }else{
        $res = dolibarr_set_const($db, $name, 0, 'chaine', 0, '', $conf->entity);
    }

    if (! $res > 0) $error ++;

    if (! $error) {
        // activateModule('modCron');
        setEventMessage($langs->trans("SetupSaved"), 'mesgs');
    } else {
        setEventMessage($langs->trans("Error"), 'errors');
    }
}

if ($action == 'auteurpardefaut') {
    $name = GETPOST ( 'name', 'text' ); 
    $value = GETPOST ( 'value', 'int' );

    $error = 0;
    if ($value){
        $res = dolibarr_set_const($db, $name, 1, 'chaine', 0, '', $conf->entity);
    }else{
        $res = dolibarr_set_const($db, $name, 0, 'chaine', 0, '', $conf->entity);
    }

    if (! $res > 0) $error ++;

    if (! $error) {
        // activateModule('modCron');
        setEventMessage($langs->trans("SetupSaved"), 'mesgs');
    } else {
        setEventMessage($langs->trans("Error"), 'errors');
    }

}

if ($action == 'liercomments') {
    $name = GETPOST ( 'name', 'text' ); 
    $value = GETPOST ( 'value', 'int' );

    $error = 0;
    if ($value){
        $res = dolibarr_set_const($db, $name, 1, 'chaine', 0, '', $conf->entity);
    }else{
        $res = dolibarr_set_const($db, $name, 0, 'chaine', 0, '', $conf->entity);
    }

    if (! $res > 0) $error ++;

    if (! $error) {
        // activateModule('modCron');
        setEventMessage($langs->trans("SetupSaved"), 'mesgs');
    } else {
        setEventMessage($langs->trans("Error"), 'errors');
    }

}
if(!empty($action)){
    header('Location: '.$_SERVER['PHP_SELF']);
    exit;
}

// elseif ($action == 'update_configuration') {
//     $error = 0;

//     $value = GETPOST('monthlysalarycycle', 'restricthtml');
//     $res = dolibarr_set_const($db, 'GESTIONNOTIFS_EMAIL_CONTENT_WHEN_REPLY_TO_COMMENT', $value,'chaine',0,'',$conf->entity);
//     if($res) $error++;

//     $action = '';

//     if ($error){
//         setEventMessage($langs->trans("SetupSaved"), 'mesgs');
//     } else {
//         setEventMessage($langs->trans("Error"), 'errors');
//     }
// }
// if ($action == 'otherconf') {
//     $error = 0;

//     $prefix  = GETPOST('GESTIONNOTIFS_ENABLE_SEND_EMAIL_WHEN_REPLY_TO_COMMENT');

//     $res = dolibarr_set_const($db, 'GESTIONNOTIFS_ENABLE_SEND_EMAIL_WHEN_REPLY_TO_COMMENT', $prefix, 'chaine', 0, '', $conf->entity);
//     if (! $res > 0) $error ++;
    
//     if (! $error) {
//         setEventMessage($langs->trans("SetupSaved"), 'mesgs');
//     } else {
//         setEventMessage($langs->trans("Error"), 'errors');
//     }
// }

llxHeader('', $modname);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($modname,$linkback,'title_setup');



// $head = gestionnotifs_admin_prepare_head();
$h = 0;
$head[$h][0] = dol_buildpath("/gestionnotifs/admin/gestionnotifs_setup.php", 1);
$head[$h][1] = $langs->trans("Setup");
$head[$h][2] = 'configuration';
$h++;
print dol_get_fiche_head($head, 'configuration', $langs->trans("Setup"), 0, "");

$datenow = dol_now();
$datenow = dol_print_date($datenow,'day');


print '<form id="col4-form" method="post" action="admin_btp.php">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="otherconf">';
print '<table class="border" width="100%">';

print '<tr>';
    print '<td class="" width="60%">';
        print $langs->trans("EnableSendEmailWhenReply");
    print '</td>';
    print '<td>';
        $name1 = 'GESTIONNOTIFS_ENABLE_SEND_EMAIL_WHEN_REPLY_TO_COMMENT';
        if (!empty($conf->global->GESTIONNOTIFS_ENABLE_SEND_EMAIL_WHEN_REPLY_TO_COMMENT)) {
            print '<td class=""><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=enablesendmail&name='.$name1.'&value=0&token='.$_SESSION['newtoken'].'">';
            print img_picto($langs->trans("Activated"), 'switch_on');
            print '</a></td>';
        } else {
            print '<td class=""><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=enablesendmail&name='.$name1.'&value=1&token='.$_SESSION['newtoken'].'">';
            print img_picto($langs->trans("Disabled"), 'switch_off');
            print '</a></td>';
        }
    print '</td>';
print '</tr>';

print '<tr>';
    print '<td>';
        print $langs->trans("SelectionnerParDefautAuteur");
    print '</td>';
    print '<td>';
        $name1 = 'GESTIONNOTIFS_SELECTIONNER_PAR_DEFAUT_AUTEUR';
        if (!empty($conf->global->GESTIONNOTIFS_SELECTIONNER_PAR_DEFAUT_AUTEUR)) {
            print '<td class=""><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=auteurpardefaut&name='.$name1.'&value=0">';
            print img_picto($langs->trans("Activated"), 'switch_on');
            print '</a></td>';
        } else {
            print '<td class=""><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=auteurpardefaut&name='.$name1.'&value=1">';
            print img_picto($langs->trans("Disabled"), 'switch_off');
            print '</a></td>';
        }
    print '</td>';
print '</tr>';

print '<tr>';
    print '<td>';
        print $langs->trans("LierCommentEventCommentThirdParty");
    print '</td>';
    print '<td>';
        $name1 = 'GESTIONNOTIFS_LIER_COMMENT_EVENET_COMMENT_THIRDPARTY';
        if (!empty($conf->global->GESTIONNOTIFS_LIER_COMMENT_EVENET_COMMENT_THIRDPARTY)) {
            print '<td class=""><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=liercomments&name='.$name1.'&value=0">';
            print img_picto($langs->trans("Activated"), 'switch_on');
            print '</a></td>';
        } else {
            print '<td class=""><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=liercomments&name='.$name1.'&value=1">';
            print img_picto($langs->trans("Disabled"), 'switch_off');
            print '</a></td>';
        }
    print '</td>';
print '</tr>';

print '</table>';
print '</form>';

// if (!empty($conf->global->GESTIONNOTIFS_ENABLE_SEND_EMAIL_WHEN_REPLY_TO_COMMENT)) {
//     print '<form id="col0-form" method="post" action="'.$_SERVER["PHP_SELF"].'">';
//         print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
//         print '<br>';

//         print '<input type="hidden" name="action" value="update_configuration">';
//         print '<div class="div-table-responsive-no-min">';
//             print '<table class="noborder centpercent" width="100%">';
//                 print '<tr class="liste_titre">';
//                     print '<td colspan="2">'.$langs->trans("Description").'</td>';
//                 print '</tr>';
//                 print '<tr>';
//                     print '<td class="width100 maxwidth100">';
//                         print $langs->trans("Object");
//                     print '</td>';
//                     print '<td>';
//                         $mail_object = $conf->global->GESTIONNOTIFS_EMAIL_CONTENT_WHEN_REPLY_TO_COMMENT;
//                         $mail_object = $mail_object ? $mail_object : '';
//                         print '<input name="mail_object" class="quatrevingtpercent" value="'.$mail_object.'"/>';
//                     print '</td>';
//                 print '</tr>';
//                 print '<tr>';
//                     print '<td class="">';
//                         print $langs->trans("Content");
//                     print '</td>';
//                     print '<td>';
//                         $mail_content = $conf->global->GESTIONNOTIFS_EMAIL_CONTENT_WHEN_REPLY_TO_COMMENT;
//                         $mail_content = $mail_content ? $mail_content : '';
//                         print '<textarea name="mail_content" class="quatrevingtpercent" rows="8">'.$mail_content.'</textarea>';
//                     print '</td>';
//                 print '</tr>';
                
//             print '</table>';
//             print '<br><div class="center"><input type="submit" class="butAction" value="'.$langs->trans("Validate").'"></div>';
//         print '</div>';

//     print '</form>';
// }


llxFooter();
$db->close();
