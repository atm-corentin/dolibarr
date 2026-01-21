<?php
/* Copyright (C) 2016      Garcia MICHEL <garcia@soamichel.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */


dol_include_once('/gestionnotifs/class/gt_notifcs.class.php');
dol_include_once('/gestionnotifs/class/gt_comments.class.php');

class Actionsgestionnotifs{
	protected $db;
	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();


	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;

	/**
	 * @var array Errors
	 */
	public $errors = array();

	function Actionsgestionnotifcs($db){
		$this->db = $db;
	}

	function completeTabsHead($parameters, &$object, &$action, $hookmanager){
		
		global $langs, $db, $conf, $user, $head_gestionnotifs_showed;

		$head = $parameters['head'];
		$object = $parameters['object'];

		if(!$user->rights->gestionnotifs->lire || empty($object->id)) return 0;
		
		$name_attr = [
			'member' => 'rowid',
			'societe' => 'socid',
			'project' => 'id',
			'product' => 'id',
		];
		
		if(!isset($head_gestionnotifs_showed)) $head_gestionnotifs_showed = array();

		$arrayelements = ['contact', 'facture', 'invoice_supplier', 'member', 'order_supplier', 'product', 'project', 'propal', 'societe', 'commande', 'shipping', 'delivery', 'expensereport', 'reception', 'reception_free', 'payment', 'payment_supplier', 'action', 'project_task'];

		if(isset($object->element) && !isset($head_gestionnotifs_showed[$object->element]) && in_array($object->element, $arrayelements)) {

			$head_gestionnotifs_showed[$object->element] = 1;

			$comment = new gt_comments($db);
			$notif   = new gt_notifcs($db);

			// $notif->upgradeModuleGtNotif();

			$namemod = (isset($object->element) && $object->element == 'delivery') ? 'shipping' : ((isset($object->element)) ? $object->element : '');

			$objectid = (isset($object->id)) ? $object->id : '';

			$name_module = $namemod;

			// $filternotif = ' AND ((fk_module ='.$objectid.' AND (name_module ="'.$namemod.'" OR name_module ="project_task")) OR (fk_module  IN (select rowid from '.MAIN_DB_PREFIX.'projet_task WHERE fk_projet='.$objectid.'))) ';
			$filternotif = ' AND (fk_module ='.$objectid.' AND name_module ="'.$namemod.'" )';

			$nbNote = $notif->fetchAll('','',0,0,$filternotif);

			$liercomments = !empty($conf->global->GESTIONNOTIFS_LIER_COMMENT_EVENET_COMMENT_THIRDPARTY) ? 1 : 0;

			$filtre = '';
			if(!empty($liercomments) && $object->element == 'action'){
				$filtre .= ' AND ((name_module ="action" AND fk_module ='.$objectid.') OR (name_module ="societe" AND fk_module ='.intval($object->socid).'))';
				$nbComment = $comment->fetchAll('','',0,0, $filtre);
			
			}
			elseif(!empty($liercomments) && $object->element == 'societe'){
				$filtre .= ' AND ((name_module ="societe" AND fk_module ='.$objectid.') OR (name_module ="action" AND action_socid ='.intval($objectid).'))';
				$nbComment = $comment->fetchAll('','',0,0, $filtre);
			}
			else{
				$nbComment = $comment->fetchAll('','',0,0,' AND name_module ="'.$namemod.'" AND fk_module ='.$objectid);
			}

			
			// --------------------------------------------------------------------------------------------------------------
			$elementtask = ($object->element == 'project_task') ? 'task' : '';

			?>
			<script type="text/javascript">
				$(document).ready(function() {
					var element = '<?php echo '#tab_notification'.$elementtask; ?>';
				    if ($(element).length){
				        $(element).append('<span class="badge marginleftonlyshort"><?php echo dol_escape_js($nbNote); ?></span>').addClass('loaded');
				    }

					var element = '<?php echo '#tab_commentaire'.$elementtask; ?>';
				    if ($(element).length){
				        $(element).append('<span class="badge marginleftonlyshort"><?php echo dol_escape_js($nbComment); ?></span>').addClass('loaded');
				    }
			   	});
			</script>
		    <?php
		}

	    // return 0;
	}

	// function doActions($parameters, &$object, &$action, $hookmanager){
	// 	global $langs, $user, $confirm;

	// 	$params = explode(':',$parameters['context']);
	// 	$element = $object->element;
	// 	foreach ($params as $key => $value) {
	// 		if($value != 'main'){
	// 			// print '<script>';
	// 			// 	print '$(document).ready(function(){
	// 			// 		console.log("fggggg");
	// 			// 	})';
	// 			// print '<script>';
	// 		}
	// 	}

	// 	// d($parameters,false);
	// 	// print_r($object);
		
	// }


	/**
	 * Overloading the interface function : replacing the parent's function with the one below
	 *
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          &$action        Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function doActionInterface($parameters, &$object, &$action, $hookmanager)
	{
	    $error = 0; // Error counter
	    global $langs, $db, $conf, $user;
	    
	    return 0;
	}
	

	/**
	 * @param bool $parameters
	 * @return int
	 */
	public function printTopRightMenu($parameters=false)
	{
		global $conf, $db, $user, $array_count_object_comments;

		if(!$user->rights->gestionnotifs->lire) return 0;

		$sql = '';

		// $tmpvar = 'GTNOTIF_LAST_TIME_VIEWED_COMMENTS_BY_USER_'.$user->id;
		// $last_comment_viewed = isset($conf->global->$tmpvar) ? $conf->global->$tmpvar : '';
		$gropby = ', obj.tms, v.viewed_at, v.fk_user, obj.rowid';
		$sql = 'SELECT obj.fk_module, obj.name_module, COUNT(obj.rowid) as total_unreded, obj.tms, v.viewed_at, v.fk_user';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'gt_comments as obj';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'gt_comments_viewedusers as v ON (obj.fk_module = v.fk_module AND obj.name_module = v.name_module AND '.$user->id.' = v.fk_user)';
		$sql .= " WHERE obj.entity IN (0,".(int) $conf->entity.")";

		// if ($last_comment_viewed) {
		// 	$sql .= " AND obj.tms >= '".$last_comment_viewed."'";
		// }

		$sql .= " AND obj.fk_user != '".$user->id."'";

		$searchinuser = " ( users_affected = ".$user->id;
		$searchinuser .= " OR users_affected LIKE '".$user->id.",%'";
		$searchinuser .= " OR users_affected LIKE '%,".$user->id.",%'";
		$searchinuser .= " OR users_affected LIKE '%,".$user->id. "')";

		$sql .= ' AND ((obj.comment_type = '.gt_comments::PRIVATE.' AND '.$searchinuser.')';
		$sql .= ' OR (comment_type = '.gt_comments::PRIVATE.' AND obj.fk_user = '.$user->id.')';
		$sql .= ' OR (obj.comment_type = '.gt_comments::PUBLIC_WITH_MAIL.' OR comment_type = '.gt_comments::PUBLIC.'))';

		$sql .= ' AND ((v.fk_user = '.$user->id.' AND v.viewed_at IS NOT NULL AND obj.tms > v.viewed_at) OR (v.fk_user is NULL AND v.viewed_at is NULL))';
		$sql .= ' GROUP BY obj.name_module, obj.fk_module'.$gropby;

		$resql = $db->query($sql);

		// echo $sql;

		$total_unreded = 0;
		$array_count_object_comments = array();

		if ($resql) {
			$i = 0;
			$num = $db->num_rows($resql);
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				$array_count_object_comments[(int)$obj->fk_module.'::'.$obj->name_module] = 1;
				$total_unreded += $obj->total_unreded;
				$i++;
			}
		}
		$out = '<div class="inline-block notifs gestionnotifs_toprightmenu">';
			$out .= '<div class="classfortooltip inline-block login_block_elem inline-block">';
				$out .= '<a href="'.dol_buildpath('/gestionnotifs/comments.php', 1).'">';
				// $out .= '<span class="fa fa-bell atoplogin valignmiddle"></span>';
				$out .= '<span class="fa fa-comment atoplogin valignmiddle"></span>';
					if($total_unreded){
						$out .= '<span class="gestionnotifs_calc_unreded_comments">';
						$out .= ($total_unreded < 99) ? $total_unreded : '+99';
						$out .= '</span>';
					}
				$out .= '</a>';
			$out .= '</div>';
		$out .= '</div>';

		// $out .= '<div class="inline-block notifs">';
		// 	$out .= '<div class="classfortooltip inline-block login_block_elem inline-block">';
		// 		$out .= '<a class="get_notif"><span class="fa fa-bell atoplogin valignmiddle"></span></a>';
		// 	$out .= '</div>';
		// $out .= '</div>';

		// $out .= '<div class="inline-block notifs"><div class="inline-block"><div class="classfortooltip inline-block login_block_elem inline-block" style="padding: 0px; padding: 0px; padding-right: 3px !important;" title=""><a class="get_notif"><span class="fa fa-bell atoplogin valignmiddle"></span></a></div></div></div>';

		$this->resprints = $out;
	}
}
