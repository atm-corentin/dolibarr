<?php 
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php'; 
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';

require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT.'/reception/class/reception.class.php';
require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/paiementfourn.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
	
dol_include_once('/gestionnotifs/class/gt_notifcs.class.php');

// if (!empty($conf->doliutils->enabled)) {
	dol_include_once('/doliutils/class/receptionfree.class.php');
// }

class gt_comments extends Commonobject{ 

	public $errors = array();
	public $rowid;
	public $fk_user;
	public $fk_comment;
	public $comment;
	public $fk_module;
	public $name_module;
	public $date;

	public $element='gt_comments';
	public $table_element='gt_comments';

	public $picto = 'list';
	
	public $name_modules = array();
	public $class_modules = array();
	

	const PUBLIC = 1;
    const PRIVATE = 2;
    const PUBLIC_WITH_MAIL = 3;

	public $comment_types = array();

	public function __construct($db){ 
		global $user, $langs;

		$this->db = $db;

		// if(!$user->rights->gestionnotifs->lire) {
		// 	accessforbidden();
		// }

		$this->name_modules['contact']     		= $langs->trans('Contact');
		$this->name_modules['expensereport']	= $langs->trans('ExpenseReport');
		$this->name_modules['facture']     		= $langs->trans('InvoiceCustomer');
		$this->name_modules['invoice_supplier']	= $langs->trans('SupplierInvoice');
		$this->name_modules['member']     		= $langs->trans('Member');
		$this->name_modules['commande']			= $langs->trans('Order');
		$this->name_modules['order_supplier']	= $langs->trans('SupplierOrder');
		$this->name_modules['product']			= $langs->trans('Product');
		$this->name_modules['project']			= $langs->trans('Project');
		$this->name_modules['propal']			= $langs->trans('Proposal');
		$this->name_modules['reception']		= $langs->trans('Reception');
		$this->name_modules['reception_free']	= $langs->trans('ReceptionFree');
		$this->name_modules['shipping']			= $langs->trans('Shipment');
		$this->name_modules['societe']			= $langs->trans('ThirdParty');
		$this->name_modules['payment']          = $langs->trans('Payment');
        $this->name_modules['payment_supplier'] = $langs->trans('SupplierPayment');
        $this->name_modules['project_task']     = $langs->trans('Task');
        $this->name_modules['action']     		= $langs->trans('Event');

		$this->class_modules['contact']     	= 'Contact';
		$this->class_modules['expensereport']	= 'ExpenseReport';
		$this->class_modules['facture']     	= 'Facture';
		$this->class_modules['invoice_supplier']= 'FactureFournisseur';
		$this->class_modules['member']     		= 'Adherent';
		$this->class_modules['commande']		= 'Commande';
		$this->class_modules['order_supplier']	= 'CommandeFournisseur';
		$this->class_modules['product']			= 'Product';
		$this->class_modules['project']			= 'Project';
		$this->class_modules['propal']			= 'Propal';
		$this->class_modules['reception']		= 'Reception';
		$this->class_modules['reception_free']	= 'Receptionfree';
		$this->class_modules['shipping']		= 'Expedition';
		$this->class_modules['societe']			= 'Societe';
		$this->class_modules['payment']         = 'Paiement';
        $this->class_modules['payment_supplier']= 'PaiementFourn';
        $this->class_modules['project_task']    = 'Task';
        $this->class_modules['action']    		= 'ActionComm';


		$descrthirdradio = $langs->trans("SendMail").' '.$langs->trans("For").' '.$langs->trans("All").' '.$langs->trans("Users");
		$descrthirdradio = $descrthirdradio;
			
		$this->comment_types[$this::PUBLIC] = $langs->trans("Public").' <span class="small opacitymedium txtlowercase">('.$langs->trans('NoEMail').')</span>';
	    $this->comment_types[$this::PRIVATE] = $langs->trans("Private");
	    $this->comment_types[$this::PUBLIC_WITH_MAIL] = $langs->trans("Public").' <span class="small opacitymedium txtlowercase">('.$descrthirdradio.')</span>';

		// $sqldelete = "DELETE FROM ".MAIN_DB_PREFIX."gt_comments WHERE name_module = 'contact' AND fk_module NOT IN (SELECT rowid FROM ".MAIN_DB_PREFIX."socpeople);";
		// $resql = $this->db->query($sqldelete);
		// $sqldelete = "DELETE FROM ".MAIN_DB_PREFIX."gt_comments WHERE name_module = 'expensereport' AND fk_module NOT IN (SELECT rowid FROM ".MAIN_DB_PREFIX."expensereport);";
		// $resql = $this->db->query($sqldelete);
		// $sqldelete = "DELETE FROM ".MAIN_DB_PREFIX."gt_comments WHERE name_module = 'facture' AND fk_module NOT IN (SELECT rowid FROM ".MAIN_DB_PREFIX."facture);";
		// $resql = $this->db->query($sqldelete);
		// $sqldelete = "DELETE FROM ".MAIN_DB_PREFIX."gt_comments WHERE name_module = 'invoice_supplier' AND fk_module NOT IN (SELECT rowid FROM ".MAIN_DB_PREFIX."facture_fourn);";
		// $resql = $this->db->query($sqldelete);
		// $sqldelete = "DELETE FROM ".MAIN_DB_PREFIX."gt_comments WHERE name_module = 'member' AND fk_module NOT IN (SELECT rowid FROM ".MAIN_DB_PREFIX."adherent);";
		// $resql = $this->db->query($sqldelete);
		// $sqldelete = "DELETE FROM ".MAIN_DB_PREFIX."gt_comments WHERE name_module = 'commande' AND fk_module NOT IN (SELECT rowid FROM ".MAIN_DB_PREFIX."commande);";
		// $resql = $this->db->query($sqldelete);
		// $sqldelete = "DELETE FROM ".MAIN_DB_PREFIX."gt_comments WHERE name_module = 'order_supplier' AND fk_module NOT IN (SELECT rowid FROM ".MAIN_DB_PREFIX."commande_fournisseur);";
		// $resql = $this->db->query($sqldelete);
		// $sqldelete = "DELETE FROM ".MAIN_DB_PREFIX."gt_comments WHERE name_module = 'product' AND fk_module NOT IN (SELECT rowid FROM ".MAIN_DB_PREFIX."product);";
		// $resql = $this->db->query($sqldelete);
		// $sqldelete = "DELETE FROM ".MAIN_DB_PREFIX."gt_comments WHERE name_module = 'project' AND fk_module NOT IN (SELECT rowid FROM ".MAIN_DB_PREFIX."projet);";
		// $resql = $this->db->query($sqldelete);
		// $sqldelete = "DELETE FROM ".MAIN_DB_PREFIX."gt_comments WHERE name_module = 'propal' AND fk_module NOT IN (SELECT rowid FROM ".MAIN_DB_PREFIX."propal);";
		// $resql = $this->db->query($sqldelete);
		// $sqldelete = "DELETE FROM ".MAIN_DB_PREFIX."gt_comments WHERE name_module = 'reception' AND fk_module NOT IN (SELECT rowid FROM ".MAIN_DB_PREFIX."reception);";
		// $resql = $this->db->query($sqldelete);
		// $sqldelete = "DELETE FROM ".MAIN_DB_PREFIX."gt_comments WHERE name_module = 'reception_free' AND fk_module NOT IN (SELECT rowid FROM ".MAIN_DB_PREFIX."reception_free);";
		// $resql = $this->db->query($sqldelete);
		// $sqldelete = "DELETE FROM ".MAIN_DB_PREFIX."gt_comments WHERE name_module = 'shipping' AND fk_module NOT IN (SELECT rowid FROM ".MAIN_DB_PREFIX."expedition);";
		// $resql = $this->db->query($sqldelete);
		// $sqldelete = "DELETE FROM ".MAIN_DB_PREFIX."gt_comments WHERE name_module = 'societe' AND fk_module NOT IN (SELECT rowid FROM ".MAIN_DB_PREFIX."societe);";	
		// $resql = $this->db->query($sqldelete);



	    // global $sqlchildcommerciauxfilter;

        // if(empty($sqlchildcommerciauxfilter)) {
        //     $childcommerciauxids = $user->getAllChildIds(0);

        //     if($childcommerciauxids) {
        //         // $usersids = implode(',', $childcommerciauxids);
        //         $sqlchildcommerciauxfilter = implode(',', $childcommerciauxids);
        //     }
        //     else {
        //     	$sqlchildcommerciauxfilter = 'NONE';
        //     }
        // }


		return 1;
    }

	public function create($echo_sql=0,$insert)
	{
		global $conf, $user;

		$sql_column = '';
		$sql_value = '';
		
		$sql  = "INSERT INTO " . MAIN_DB_PREFIX .get_class($this)." ( ";
		foreach ($insert as $column => $value) {
			$alias = (is_numeric($value)) ? "" : "'";
			if($value != ""){
				$sql_column .= " , ".$column."";
				$sql_value .= " , ".$alias.$value.$alias;
			}
		}

		$sql_column  .= " , tms ";
		$sql_value  .= ", '".$this->db->idate(dol_now())."'";

		$sql .= substr($sql_column, 2)." ) VALUES ( ".substr($sql_value, 2)." )";
		$resql = $this->db->query($sql);

		if (!$resql) {
			$this->db->rollback();
			$this->errors[] = 'Error '.get_class($this).' '. $this->db->lasterror();
			echo '<pre>';print_r($this->errors);echo '</pre>';
			die($sql);
			
			return 0;
		} 

		$lastinsert = $this->db->last_insert_id(MAIN_DB_PREFIX.'gt_comments');

		$this->saveLastUsersSendEMail($lastinsert, $insert);

		// return $this->db->db->insert_id;
		return $lastinsert;
	}

	public function update($id, array $data,$echo_sql=0)
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		if (!$id || $id <= 0)
			return false;

		global $user;

		if(!$user->admin && $user->id != $this->fk_user) {
			accessforbidden();
		}


        $sql = 'UPDATE ' . MAIN_DB_PREFIX .get_class($this). ' SET ';

        if (count($data) && is_array($data))
            foreach ($data as $key => $val) {
                $val = is_numeric($val) ? $val : '"'. $val .'"';
                $val = ($val == '') ? 'NULL' : $val;
                $sql .= ''. $key. ' = '. $val .',';
            }

        $sql  = substr($sql, 0, -1);
        $sql  .= " , tms = '".$this->db->idate(dol_now())."'";

        $sql .= ' WHERE rowid = ' . $id;

        $resql = $this->db->query($sql);

		if (!$resql) {
			$this->db->rollback();
			$this->errors[] = 'Error '.get_class($this).' : '. $this->db->lasterror();
			echo '<pre>';print_r($this->errors);echo '</pre>';
			die($sql);
			return -1;
		}

		$this->saveLastUsersSendEMail($id, $data);

		return $id;
	}

	public function delete($echo_sql=0)
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		global $user;

		if(!$user->rights->gestionnotifs->supprimer || ($user->rights->gestionnotifs->supprimer && $user->id != $this->fk_user && !$user->admin)) {
			accessforbidden();
		}
		
		$sql = 'DELETE FROM ' . MAIN_DB_PREFIX .get_class($this).' WHERE rowid = ' . $this->rowid;
		$resql 	= $this->db->query($sql);
		
		if (!$resql) {
			$this->db->rollback();
			$this->errors[] = 'Error '.get_class($this).' : '.$this->db->lasterror();

			return -1;
		} 

		return 1;
	}

    
	public function fetchAll($sortorder = 'ASC', $sortfield = 'date', $limit = 0, $offset = 0, $filter = '', $filtermode = 'AND')
	{
	    global $conf, $user, $array_count_object_comments;

		dol_syslog(__METHOD__, LOG_DEBUG);
		$sql = "SELECT * FROM ";
		$sql .= MAIN_DB_PREFIX .get_class($this);
		$sql .= " WHERE entity=".$conf->entity;

		if (!empty($filter)) {
			$sql .= " ".$filter;
		}

		$sql .= $this->filtebyCommentType($srch_comment_type = -2);

		// echo $sql.'<br>';

		$sortorder = 'ASC';

		if (!empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}


		if (!empty($limit)) {
			if($offset==1)
				$sql .= " limit ".$limit;
			else
				$sql .= " limit ".$offset.",".$limit;				
		}
		$this->rows = array();
		$resql = $this->db->query($sql);

		$testexist = 0;

		if ($resql) {
			$num = $this->db->num_rows($resql);

			while ($obj = $this->db->fetch_object($resql)) {
				$line = new stdClass;
                $line->id    	     =  $obj->rowid;
				$line->rowid 	     =  $obj->rowid;
				$line->fk_user 	     =  $obj->fk_user;
				$line->date          =  $obj->date;
				$line->tms           =  $obj->tms;
				$line->fk_comment    =  $obj->fk_comment;
				$line->comment       =  $obj->comment;
				$line->fk_module     =  $obj->fk_module;
				$line->name_module   =  $obj->name_module;
				$line->users_affected   =  $obj->users_affected;
				$line->comment_type   =  $obj->comment_type;
				$line->action_socid   =  $obj->action_socid;
				$line->entity        =  $obj->entity;

				$line->date = $line->tms;

				// if(!$testexist && isset($array_count_object_comments[(int)$obj->fk_module.'::'.$obj->name_module])) {
				// 	$testexist = 1;
				// }

				$this->rows[] 	= $line;
			}
			$this->db->free($resql);

			return $num;
		} else {
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);

			return -1;
		}
	}


	public function fetch($id)
	{
	    global $conf;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$sql = 'SELECT * FROM ' . MAIN_DB_PREFIX .get_class($this). ' WHERE rowid = ' . $id;
		$sql .= " AND entity=".$conf->entity;
		$resql = $this->db->query($sql);
		if ($resql) {
			$numrows = $this->db->num_rows($resql);
			
			if ($numrows) {
				$obj 			    =  $this->db->fetch_object($resql);
                $this->id           =  $obj->rowid;
                $this->rowid        =  $obj->rowid;
				$this->fk_user 	    =  $obj->fk_user;
				$this->date         =  $obj->date;
				$this->fk_comment 	=  $obj->fk_comment;
				$this->comment 	    =  $obj->comment;
				$this->fk_module 	=  $obj->fk_module;
				$this->name_module 	=  $obj->name_module;
				$this->users_affected =  $obj->users_affected;
				$this->comment_type =  $obj->comment_type;
				$this->action_socid =  $obj->action_socid;
				$this->entity 	    =  $obj->entity;
			}

			$this->db->free($resql);

			if ($numrows) {
				return 1 ;
			} else {
				return 0;
			}
		} else {
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);
			return -1;
		}
	}

	public function select_with_filter($selected=0,$name='select_',$showempty=1,$val="rowid",$id='',$attr=''){

	    global $conf;

	    $moreforfilter = '';
	    $nodatarole = '';
	    $id = (!empty($id)) ? $id : $name;

	    $moreforfilter.='<select width="100%" '.$attr.' class="flat" id="select_'.$id.'" name="'.$name.'">';
	    if ($showempty) $moreforfilter.='<option value="0">&nbsp;</option>';

    	$sql = "SELECT * FROM ".MAIN_DB_PREFIX.get_class($this);
		$sql .= " WHERE entity=".$conf->entity;
		//echo $sql."<br>";
    	$resql = $this->db->query($sql);

		if ($resql) {
			$num = $this->db->num_rows($resql);

			while ($obj = $this->db->fetch_object($resql)) {
				$moreforfilter.='<option value="'.$obj->rowid.'"';
	            if ($obj->$val == $selected) $moreforfilter.=' selected';
	            $moreforfilter.='>'.$obj->$rowid.'</option>';
			}
			$this->db->free($resql);
		}

	    $moreforfilter.='</select>';
	    $moreforfilter.='<style>#s2id_select_'.$name.'{ width: 100% !important;}</style>';
	    return $moreforfilter;
	}

    function getNomUrl($withpicto=0, $option='', $get_params='', $notooltip=0, $save_lastsearch_value=-1)
    {
        global $langs, $conf, $user;
        if (! empty($conf->dol_no_mouse_hover)) $notooltip=1;   // Force disable tooltips

        $result='';
        $label='';
        $url = dol_buildpath('/gestionnotifs/card.php?id='.$this->id,2);

        // if ($user->rights->propal->lire){}

        $linkclose='';
        if (empty($notooltip))
        {
            $linkclose.= ' title="'.dol_escape_htmltag($label, 1).'"';
            $linkclose.=' class="classfortooltip"';
        }
        $linkstart = "";
        $linkend = "";
        $result = "";
    	$ref=$this->rowid;
        if ($ref) {
            $linkstart = '<a href="'.$url.'"';
            $linkstart.=$linkclose.'>';
            $linkend='</a>';

            $result .= $linkstart;
            if ($withpicto) 
                $result.= '<img height="16" src="'.dol_buildpath('/gestionnotifs/img/icon_gt_comments.png',2).'" >&nbsp;';
            if ($withpicto != 2) $result.= $ref;
        }

        $result .= $linkend;

        return $result;
    }

    public function getcountrows($filter){
    	global $conf;
        $tot = 0;
        $sql = "SELECT COUNT(rowid) as tot FROM ".MAIN_DB_PREFIX.get_class($this);
		$sql .= " WHERE entity=".$conf->entity;
        if (!empty($filter)) {
			$sql .= " ".$filter;
        	$sql .= $filter;
		}
		// echo $sql;
        $resql = $this->db->query($sql);

        if($resql){
            while ($obj = $this->db->fetch_object($resql)) 
            {
                $tot = $obj->tot;
            }
        }
        return $tot;
    }


    public function selectNameModules($selected='', $name="type", $empty=0)
    {
        $select = '<select name="'.$name.'" class="selecttype minwidth150">';
        if($empty) $select .= '<option></option>';
        foreach ($this->name_modules as $key => $value) {
            $slctd = ($key == $selected) ? 'selected' : '';
            $select .= '<option value="'.$key.'" '.$slctd.'>'.$value.'</option>';
        }
        $select .= '</select>';

        return $select;
    }

	public function getCommentContent($onlycomment = true)
	{
		global $user;

		$returned = array();

		$comment 	= GETPOST('txtcomment');
		$comment_type = (int) GETPOST("comment_type", 'int');
		$users_affected = GETPOST('users_affected_');

		foreach($_POST as $key => $value) {
		    if (strpos($key, 'users_affected') === 0) {
		    	$users_affected = GETPOST($key, 'array');
		    }
		    elseif (strpos($key, 'txtcomment') === 0) {
		    	$comment = dol_html_entity_decode(GETPOST($key, 'restricthtml'), ENT_QUOTES | ENT_HTML5);
		    }
		}

		$returned['comment'] = $comment;
		$returned['users_affected'] = ($users_affected && $comment_type == $this::PRIVATE) ? implode(",", $users_affected) : 'NULL';

		if($onlycomment)
			return $comment;

		return $returned;
    }

	public function saveLastUsersSendEMail($comm_id = 0, $data = array())
	{
		global $conf, $user;

		$usersidsent = '';

		$returned = $this->getCommentContent(false);
		if(isset($returned['users_affected']) && $returned['users_affected']) {
			$usersidsent = $returned['users_affected'];
		}

		$res = dolibarr_set_const($this->db, 'LAST_USERS_RECIEVE_MAIL_FROM_'.$user->id, ($usersidsent ? $usersidsent : ''), 'chaine', 0, '', $conf->entity);


		$name_module = $data['name_module'];
		$usercancreate = $user->rights->gestionnotifs->creer || $user->admin;
		$permissiontoadd = $usercancreate;

		$files_deleted 	= $_POST['files_deleted'];

		$dire_file = $conf->gestionnotifs->dir_output.'/comments/'.$name_module.'/'.$comm_id.'/';

		if($files_deleted){
	        $files_deleted = explode(',', $files_deleted);
	        foreach ($files_deleted as $d) {
	            @unlink($dire_file.$d);
	        }
	    }

		if(isset($_FILES['files'])) {  

		    $result = dol_mkdir($dire_file);

		    $names = array();


			foreach ($_FILES["files"]["name"] as $key => $value) {
	            $tmp_name = $_FILES["files"]["tmp_name"][$key];
	            $name     = $_FILES["files"]["name"][$key];

	            $info     = pathinfo($dire_file.'/'.$name);

	            $destfull = $info['dirname'].'/'.dol_sanitizeFileName($info['filename'].($info['extension'] != '' ? ('.'.strtolower($info['extension'])) : ''));

             	$return = move_uploaded_file( $tmp_name, $destfull );
		    }
	    }

		// $upload_dir = $conf->gestionnotifs->dir_output.'/'.$name_module.'/'.$comm_id;
		// include DOL_DOCUMENT_ROOT.'/core/actions_linkedfiles.inc.php';

	}
   
	public function countByCommentType()
	{
		global $user;

		$cc = array();

		$cc['all'] = 0;
		$cc['private'] = 0;
		$cc['public'] = 0;
		$cc['public_private'] = 0;


		$searchinuser = " ( users_affected = ".$user->id;
		$searchinuser .= " OR users_affected LIKE '".$user->id.",%'";
		$searchinuser .= " OR users_affected LIKE '%,".$user->id.",%'";
		$searchinuser .= " OR users_affected LIKE '%,".$user->id. "')";

		$sqladminprivate = ' OR (comment_type = '.$this::PRIVATE.' AND fk_user = '.$user->id.')';

		$private = ' ((comment_type = '.$this::PRIVATE.' AND '.$searchinuser.') '.$sqladminprivate.')';

		$public = ' (comment_type = '.$this::PUBLIC_WITH_MAIL.' OR comment_type = '.$this::PUBLIC.')';

		$public_private = ' (';
		$public_private .= ' (comment_type = '.$this::PRIVATE.' AND '.$searchinuser.')';
		$public_private .= $sqladminprivate;
		$public_private .= ' OR (comment_type = '.$this::PUBLIC_WITH_MAIL.' OR comment_type = '.$this::PUBLIC.')';
		$public_private .= ' )';

		$all_comments = '1';
		// $all_comments = $user->admin ? ;

		$sql = ' SELECT ';
		    $sql .= ' COUNT( CASE WHEN '.$all_comments.' THEN 1 END ) AS all_comments,';
		    $sql .= ' COUNT( CASE WHEN '.$private.' THEN 1 END ) AS private,';
		    $sql .= ' COUNT( CASE WHEN '.$public.' THEN 1 END ) AS public,';
		    $sql .= ' COUNT( CASE WHEN '.$public_private.' THEN 1 END ) AS public_private';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'gt_comments ';

		// echo $sql;

		$resql = $this->db->query($sql);

		$tmpusers = '';

		if($resql){
			while ($obj = $this->db->fetch_object($resql)) {
				$cc['all'] = $obj->all_comments;
				$cc['private'] = $obj->private;
				$cc['public'] = $obj->public;
				$cc['public_private'] = $obj->public_private;
			}
		}

		return $cc;
	}
   
	public function filtebyCommentType($srch_comment_type)
	{
		global $user;

		$sql = '';


		if(!$user->admin && empty($user->rights->gestionnotifs->canseeallcomments)) {

        	global $gestionnotifs_idsofauthorusers, $gestionnotifs_sqlcommercialfilter;
			$this->gestionnotifssqlFilterElements();

			$searchinuser = " ( users_affected = ".$user->id;
			$searchinuser .= " OR users_affected LIKE '".$user->id.",%'";
			$searchinuser .= " OR users_affected LIKE '%,".$user->id.",%'";
			$searchinuser .= " OR users_affected LIKE '%,".$user->id. "')";



			$sqladminprivate = ' OR ';

			$sqladminprivate .= ' ( ';
				$sqladminprivate .= ' comment_type = '.$this::PRIVATE;
				$sqladminprivate .= ' AND fk_user IN ('.$gestionnotifs_idsofauthorusers.')';
			$sqladminprivate .= ' )';


			if($gestionnotifs_sqlcommercialfilter) {
				$sqladminprivate .= " OR (".$gestionnotifs_sqlcommercialfilter.")";
			}



			if ($srch_comment_type == $this::PRIVATE) {
				$sql .= ' AND (';
				$sql .= ' (comment_type = '.$this::PRIVATE.' AND '.$searchinuser.')';
				$sql .= $sqladminprivate;
				$sql .= ' )';
			}

			elseif ($srch_comment_type == $this::PUBLIC) {
				$sql .= ' AND (comment_type = '.$this::PUBLIC_WITH_MAIL.' OR comment_type = '.$this::PUBLIC.')';
			}

			elseif($srch_comment_type < 0) {
				$sql .= ' AND (';
				$sql .= ' (comment_type = '.$this::PRIVATE.' AND '.$searchinuser.')';
				$sql .= $sqladminprivate;
				$sql .= ' OR (comment_type = '.$this::PUBLIC_WITH_MAIL.' OR comment_type = '.$this::PUBLIC.')';
				$sql .= ' )';
			}
		}

		// echo $sql;

		// if(!$user->admin) $sql .= " AND fk_user != ".$user->id;

		return $sql;
	}

    public function gestionnotifssqlFilterElements()
    {
        global $conf, $user;

        global $gestionnotifs_idsofauthorusers, $gestionnotifs_sqlcommercialfilter;

		global $name_module;

		$module = $name_module;

        $gestionnotifs_idsofauthorusers = $user->id;

        if(!empty($user->rights->gestionnotifs->canseecommentslinkedtohissubordinates)) {
            require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
            $childcommerciauxids = $user->getAllChildIds(0);
            $sqlchildusers = $childcommerciauxids ? implode(',', $childcommerciauxids) : 'NONE';

            if(!empty($sqlchildusers) && $sqlchildusers != 'NONE') {
                $gestionnotifs_idsofauthorusers .= ', '.$sqlchildusers;
            }
        }



        // ------------------------------------------------------------------------------------------------------------------------



        if(!$gestionnotifs_sqlcommercialfilter && !empty($user->rights->gestionnotifs->canseecommentslinktohistiers)) {

            $commercsql = "SELECT GROUP_CONCAT(fk_soc ORDER BY fk_soc) AS societeids FROM ".MAIN_DB_PREFIX."societe_commerciaux WHERE fk_user IN (".$gestionnotifs_idsofauthorusers.") ";
            $rescommsql = $this->db->query($commercsql);

            $gestionnotifs_societecommercsocids = '';
            if ($rescommsql) {
                $num = $this->db->num_rows($rescommsql);
                $z = 0;
                while ($z < $num) {
                    $obj = $this->db->fetch_object($rescommsql);
                    $gestionnotifs_societecommercsocids = $obj->societeids;
                    $z++;
                }
            }

            // d($gestionnotifs_societecommercsocids,0);


            if($gestionnotifs_societecommercsocids) {
                $s = '';
                $s .= " ( ";

                $s .= " (1<0) ";

                // propal
                if(!$module || $module == 'propal') {
                    $s .= " OR (name_module = 'propal' AND fk_module IN (SELECT rowid FROM ".MAIN_DB_PREFIX."propal WHERE fk_soc IN (".$gestionnotifs_societecommercsocids.")) ) ";
                }

                // facture
                if(!$module || $module == 'facture') {
                    $s .= " OR (name_module = 'facture' AND fk_module IN (SELECT rowid FROM ".MAIN_DB_PREFIX."facture WHERE fk_soc IN (".$gestionnotifs_societecommercsocids.")) ) ";
                }

                // invoice_supplier
                if(!$module || $module == 'invoice_supplier') {
                    $s .= " OR (name_module = 'invoice_supplier' AND fk_module IN (SELECT rowid FROM ".MAIN_DB_PREFIX."facture_fourn WHERE fk_soc IN (".$gestionnotifs_societecommercsocids.")) ) ";
                }

                // project
                if(!$module || $module == 'project') {
                    $s .= " OR (name_module = 'project' AND fk_module IN (SELECT rowid FROM ".MAIN_DB_PREFIX."projet WHERE fk_soc IN (".$gestionnotifs_societecommercsocids.")) ) ";
                }

                // societe
                if(!$module || $module == 'societe') {
                    $s .= " OR (name_module = 'societe' AND fk_module IN (".$gestionnotifs_societecommercsocids.") ) ";
                }

                // commande
                if(!$module || $module == 'commande') {
                    $s .= " OR (name_module = 'commande' AND fk_module IN (SELECT rowid FROM ".MAIN_DB_PREFIX."commande WHERE fk_soc IN (".$gestionnotifs_societecommercsocids.")) ) ";
                }

                // order_supplier
                if(!$module || $module == 'order_supplier') {
                    $s .= " OR (name_module = 'order_supplier' AND fk_module IN (SELECT rowid FROM ".MAIN_DB_PREFIX."commande_fournisseur WHERE fk_soc IN (".$gestionnotifs_societecommercsocids.")) ) ";
                }

                // member
                if(!$module || $module == 'member') {
                    $s .= " OR (name_module = 'member' AND fk_module IN (SELECT rowid FROM ".MAIN_DB_PREFIX."adherent WHERE fk_soc IN (".$gestionnotifs_societecommercsocids.")) ) ";
                }

                // contact
                if(!$module || $module == 'contact') {
                    $s .= " OR (name_module = 'contact' AND fk_module IN (SELECT rowid FROM ".MAIN_DB_PREFIX."socpeople WHERE fk_soc IN (".$gestionnotifs_societecommercsocids.")) ) ";
                }

                // shipping (delivery)
                if(!$module || $module == 'shipping') {
                    $s .= " OR (name_module = 'shipping' AND fk_module IN (SELECT rowid FROM ".MAIN_DB_PREFIX."expedition WHERE fk_soc IN (".$gestionnotifs_societecommercsocids.")) ) ";
                }

                // reception
                if(!$module || $module == 'reception') {
                    $s .= " OR (name_module = 'reception' AND fk_module IN (SELECT rowid FROM ".MAIN_DB_PREFIX."reception WHERE fk_soc IN (".$gestionnotifs_societecommercsocids.")) ) ";
                }

                // payment
                if(!$module || $module == 'payment') {
                    $s .= " OR (
                        name_module = 'payment' AND fk_module IN (
                            SELECT rowid FROM ".MAIN_DB_PREFIX."paiement_facture WHERE fk_facture IN (
                                SELECT rowid FROM ".MAIN_DB_PREFIX."facture WHERE fk_soc IN (".$gestionnotifs_societecommercsocids.") 
                            )
                        )
                    ) ";
                }

                // payment_supplier
                if(!$module || $module == 'payment_supplier') {
                    $s .= " OR (
                        name_module = 'payment_supplier' AND fk_module IN (
                            SELECT rowid FROM ".MAIN_DB_PREFIX."paiementfourn_facturefourn WHERE fk_facturefourn IN (
                                SELECT rowid FROM ".MAIN_DB_PREFIX."facture_fourn WHERE fk_soc IN (".$gestionnotifs_societecommercsocids.") 
                            )
                        )
                    ) ";
                }

                $s .= " ) ";

                $gestionnotifs_sqlcommercialfilter = $s;
                // echo $gestionnotifs_sqlcommercialfilter;
            }
        }
    }
   
	public function inputRadioCommentType($selected, $name = 'comment_type')
	{
		global $conf, $user, $langs;

		$cssforoptions = !empty($conf->societedealeco->enabled) ? 'style="display:none;"' : '';

		$selectbytype = '<div class="gestionnotifs_commentstype" '.$cssforoptions.'>';

		$selectbytype .= '<label class="marginrightonly"><input type="radio" name="'.$name.'" value="'.$this::PUBLIC.'"> '.$this->comment_types[$this::PUBLIC].'</label>';
	 	
	 	$selectbytype .= '<label class="marginrightonly"><input type="radio" name="'.$name.'" value="'.$this::PRIVATE.'"> '.$this->comment_types[$this::PRIVATE].'</label>';
		
 		$selectbytype .= '<label class="marginrightonly"><input type="radio" name="'.$name.'" value="'.$this::PUBLIC_WITH_MAIL.'"> '.$this->comment_types[$this::PUBLIC_WITH_MAIL].'</label>';


	 	$selectbytype = str_replace('value="'.$selected.'"', ' value="'.$selected.'" checked', $selectbytype);

	 	return $selectbytype;
	}
   
	public function dolEditorWithSelectUsers($objcomment = '', $name = '', $value = '', $row_id = '', $defaultuserid = 0, $objectmod ="")
	{
		global $conf, $langs, $form, $user;

		$langs->load('admin');

		$gt_notifcs =new gt_notifcs($this->db);
		
		$html = '';

		$selectedusers = array();
		$selectedusers[0] = $defaultuserid;

		if($objcomment && isset($objcomment->users_affected)) {
			$selectedusers = explode(",", $objcomment->users_affected);
		}elseif(isset($conf->global->GESTIONNOTIFS_SELECTIONNER_PAR_DEFAUT_AUTEUR) && empty($conf->global->GESTIONNOTIFS_SELECTIONNER_PAR_DEFAUT_AUTEUR)) {
			

			$tmpvar = 'LAST_USERS_RECIEVE_MAIL_FROM_'.$user->id;

			$usersidsent = isset($conf->global->$tmpvar) ? $conf->global->$tmpvar : '';
			if($usersidsent) {
				$tmparr = explode(",", $usersidsent);
				$selectedusers = array_merge($tmparr, $selectedusers);
			}
		}

		$selectedtype = ($objcomment && isset($objcomment->comment_type)) ? $objcomment->comment_type : $this::PRIVATE;

	 	$html .= $this->inputRadioCommentType($selectedtype);

	 	$hiddenclass = ($selectedtype == $this::PRIVATE) ? '' : 'hidden';

		$html .= '</div>';
		if(!empty($conf->global->GESTIONNOTIFS_ENABLE_SEND_EMAIL_WHEN_REPLY_TO_COMMENT)) {
			$html .= '<div class="gestionnotifsselectusers '.$hiddenclass.'">';
				$html .= $langs->trans('Emailrecipient');
				$tmpnameuser = str_replace("txtcomment", "", $name);
				$request_author = (!empty($objectmod->user_author_id) ? $objectmod->user_author_id : (!empty($objectmod->fk_user_author) ? $objectmod->fk_user_author : $objectmod->user_creation_id));
				$excluds = ($request_author != $user->id) ? [$user->id] : [];
				$html .= $form->select_dolusers($selectedusers, 'users_affected_'.$tmpnameuser.$row_id, 1, $excluds, $disabled = 0, $include = '', $enableonly = '', $force_entity = '0', $maxlength = 0, $showstatus = 0, $morefilter = " ", $show_every = 0, $enableonlytext = '', $morecss = 'width80p', $noactive = 1, $outputmode = 0, $multiple = true);
				// $html .= $form->select_dolusers($selectedusers, 'users_affected_'.$tmpnameuser.$row_id, 1, [$user->id], $disabled = 0, $include = '', $enableonly = '', $force_entity = '0', $maxlength = 0, $showstatus = 0, $morefilter = " AND u.email !='' ", $show_every = 0, $enableonlytext = '', $morecss = 'width80p', $noactive = 1, $outputmode = 0, $multiple = true);
			$html .= '</div>';
		}

		$doleditor = new DolEditor($name.$row_id, $value, '', 150, 'dolibarr_mailings', 'In', 0, false, true, ROWS_4, '90%');
		$html .= $doleditor->Create(1);


		// $filesdiv = '';

		// $foldername = ($objcomment->rowid) ? '/comments/'.$objcomment->name_module.'/'.$objcomment->rowid.'/' : '';
		// $dire = ($objcomment->rowid) ? $conf->gestionnotifs->dir_output.$foldername : '';

		// $entity = $object->entity ? $object->entity : $conf->entity;

		// $filesdiv .= '<div class="files_joints edit">';
		// if ($objcomment->rowid && file_exists($dire)){
		// 	$images = scandir($dire);
		// 	$filesdiv .= '<div class="editfiles">';
		// 	$filesdiv .= '<ul class="list_joints">';

		// 	foreach ($images as $img) {
		// 	    if (!in_array($img,array(".",".."))) 
		// 	    { 
		// 	        $ext = explode(".", $img);
		// 	        $filename = explode("_uplodnc_", $img);
		// 	        $ext = $ext[count($ext) - 1];
		// 	        $picto = dol_buildpath('/gestionnotifs/img/extension/'.$ext.'.png',2);
		// 	        $nopicto = dol_buildpath('/gestionnotifs/img/extension/file.png',2);
		// 	        $minifile = getImageFileNameForSize($img,'');
		// 	        $dt_files = getAdvancedPreviewUrl('gestionnotifs', $foldername.$minifile, 1,'&entity='.$entity);

		// 	        if ($ext == "pdf") {
		// 	            $filesdiv .= '<li>';
		// 	                $filesdiv .= '<a href="'.DOL_URL_ROOT.'/document.php?modulepart=gestionnotifs&attachment=0&file='.$foldername.$minifile.'" datafile="'.$img.'" class="delete_file documentpreview" onclick="to_delete_file(this,event,'.$objcomment->rowid.')"  title="'.$filename[1].'"><span><i class="fa fa-times"></i></span><img src="'.$picto.'" /></a>';
		// 	            $filesdiv .= '</li>';
		// 	        }elseif (strtolower($ext) == "png" || strtolower($ext) == "jpg" || strtolower($ext) == "jpeg") {
		// 	            $filesdiv .= '<li class="png">';
		// 	                $filesdiv .= '<a href="'.$dt_files['url'].'" datafile="'.$img.'" class="delete_file documentpreview" onclick="to_delete_file(this,event,'.$objcomment->rowid.')"  title="'.$filename[1].'"><span><i class="fa fa-times"></i></span><img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=gestionnotifs&entity='.$entity.'&file='.$foldername.$minifile.'&perm=download" /></a>';
		// 	            $filesdiv .= '</li>';
		// 	        }else{
		// 	            $filesdiv .= '<li>';
		// 	            if (file_exists(dol_buildpath('/gestionnotifs/img/extension/'.$ext.'.png'))) {
		// 	                $filesdiv .= '<a href="'.DOL_URL_ROOT.'/document.php?modulepart=gestionnotifs&attachment=0&file='.$foldername.$minifile.'" datafile="'.$img.'" class="delete_file documentpreview" onclick="to_delete_file(this,event,'.$objcomment->rowid.')"  title="'.$filename[1].'"><span><i class="fa fa-times"></i></span><img src="'.$picto.'" /></a>';
		// 	            }else{
		// 	                $filesdiv .= '<a href="'.DOL_URL_ROOT.'/document.php?modulepart=gestionnotifs&attachment=0&file='.$foldername.$minifile.'" datafile="'.$img.'" class="delete_file documentpreview" onclick="to_delete_file(this,event,'.$objcomment->rowid.')"  title="'.$filename[1].'"><span><i class="fa fa-times"></i></span><img src="'.$nopicto.'" /></a>';
		// 	            }
		// 	            $filesdiv .= '</li>';
		// 	        }
		// 	    }
		// 	}

		// 	$filesdiv .= '</ul>';
		// 	$filesdiv .= '<input type="hidden" name="files_deleted" class="files_deleted" />';
		// 	$filesdiv .= '<div style="clear:both;"></div>';
		// 	$filesdiv .= '</div>';

		// }

		// $filesdiv .= '<div class="one_file">';
		// 	$filesdiv .= '<span class="add_joint" onclick="trigger_upload_file(this)"><i class="fa fa-paperclip"></i></span>';
		// 	$filesdiv .= '<input class="add_photo" type="file" name="files[]" onchange="change_upload_file(this)"/>';
		// $filesdiv .= '</div>';

		// $filesdiv .= '<span class="add_plus" onclick="new_input_joint(this)"><i class="fa fa-plus"></i></span>';
		// $filesdiv .= '</div>';

		// $html .= $filesdiv;

		return $html;
    }

   
	public function getJoinedFiles($objcomment = '')
	{
		global $conf, $langs, $user, $gt_comments_already_viewed;

		$filesdiv = '';

		if(isset($this->rows) && count($this->rows) > 0) {

        	$name_module = $this->rows[0]->name_module;
			$fk_module = $this->rows[0]->fk_module;

			if(!isset($gt_comments_already_viewed[$name_module.'-'.$fk_module])) {
				$this->setViewedComment($name_module, $fk_module);
			}
		}

		$foldername = ($objcomment->rowid) ? '/comments/'.$objcomment->name_module.'/'.$objcomment->rowid.'/' : '';
		$dire = ($objcomment->rowid) ? $conf->gestionnotifs->dir_output.$foldername : '';

		$entity = $objcomment->entity ? $objcomment->entity : $conf->entity;

		if(isset($objcomment->comment_type) && $objcomment->comment_type == $this::PRIVATE && $objcomment->users_affected) {

			$userstatic = new User($this->db);

			$sql = "SELECT DISTINCT u.rowid, u.lastname, u.firstname, u.admin, u.fk_soc, u.login, u.office_phone, u.user_mobile, u.email, u.api_key, u.accountancy_code, u.gender, u.employee, u.photo,";
			$sql .= " u.statut, u.entity";
			$sql .= " FROM ".MAIN_DB_PREFIX."user as u";
			$sql .= " WHERE u.rowid IN (".$objcomment->users_affected.")";
			$sql .= $this->db->order('lastname', 'ASC');

			$resql = $this->db->query($sql);

			$tmpusers = '';

			if($resql){
				while ($obj = $this->db->fetch_object($resql)) {
					$userstatic->id = $obj->rowid;
					$userstatic->admin = $obj->admin;
					$userstatic->ref = $obj->rowid;
					$userstatic->login = $obj->login;
					$userstatic->statut = $obj->statut;
					$userstatic->office_phone = $obj->office_phone;
					$userstatic->user_mobile = $obj->user_mobile;
					$userstatic->email = $obj->email;
					$userstatic->gender = $obj->gender;
					$userstatic->socid = $obj->fk_soc;
					$userstatic->firstname = $obj->firstname;
					$userstatic->lastname = $obj->lastname;
					$userstatic->employee = $obj->employee;
					$userstatic->photo = $obj->photo;

					$li = $userstatic->getNomUrl(-1, '', 0, 0, 24, 1, '', '', 1);

					$tmpusers .= $tmpusers ? ' | '.$li : $li;
				}
			}

			if($tmpusers) {
				$filesdiv .= '<div class="private_users_msg show clear warning">';
					$filesdiv .= '<span class="private_message_for ">';
						$filesdiv .= $this->comment_types[$this::PRIVATE].': ';
					$filesdiv .= '</span>';
					$filesdiv .= $tmpusers;
				$filesdiv .= '</div>';
			}
			

		}

		$filesdiv .= '<div class="files_joints edit">';

		if ($objcomment->rowid && file_exists($dire)){
			$images = scandir($dire);
			$filesdiv .= '<div class="editfiles">';
			$filesdiv .= '<ul class="list_joints">';

			foreach ($images as $img) {
			    if (!in_array($img,array(".",".."))) 
			    { 
			        $ext = explode(".", $img);
			        $filename = explode("_uplodnc_", $img);
			        $ext = $ext[count($ext) - 1];
			        $picto = dol_buildpath('/gestionnotifs/img/extension/'.$ext.'.png',2);
			        $nopicto = dol_buildpath('/gestionnotifs/img/extension/file.png',2);
			        $minifile = getImageFileNameForSize($img,'');
			        $dt_files = getAdvancedPreviewUrl('gestionnotifs', $foldername.$minifile, 1,'&entity='.$entity);

			        $mime = ' mime="'.$dt_files['mime'].'" ';

			        if ($ext == "pdf") {
			            $filesdiv .= '<li>';
			                $filesdiv .= '<a href="'.DOL_URL_ROOT.'/document.php?modulepart=gestionnotifs&attachment=0&file='.$foldername.$minifile.'" datafile="'.$img.'" class="delete_file documentpreview" '.$mime.' onclick="to_delete_file(this,event,'.$objcomment->rowid.')"  title="'.(isset($filename[1]) ? $filename[1] : '').'"><span class="onlyforedit"><i class="fa fa-times"></i></span><img src="'.$picto.'" /></a>';
			            $filesdiv .= '</li>';
			        }elseif (strtolower($ext) == "png" || strtolower($ext) == "jpg" || strtolower($ext) == "jpeg") {
			            $filesdiv .= '<li class="png">';
			                $filesdiv .= '<a href="'.$dt_files['url'].'" datafile="'.$img.'" class="delete_file documentpreview" '.$mime.' onclick="to_delete_file(this,event,'.$objcomment->rowid.')"  title="'.(isset($filename[1]) ? $filename[1] : '').'"><span class="onlyforedit"><i class="fa fa-times"></i></span><img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=gestionnotifs&entity='.$entity.'&file='.$foldername.$minifile.'&perm=download" /></a>';
			            $filesdiv .= '</li>';
			        }else{
			            $filesdiv .= '<li>';
			            if (file_exists(dol_buildpath('/gestionnotifs/img/extension/'.$ext.'.png'))) {
			                $filesdiv .= '<a href="'.DOL_URL_ROOT.'/document.php?modulepart=gestionnotifs&attachment=0&file='.$foldername.$minifile.'" datafile="'.$img.'" class="delete_file documentpreview" '.$mime.' onclick="to_delete_file(this,event,'.$objcomment->rowid.')"  title="'.(isset($filename[1]) ? $filename[1] : '').'"><span class="onlyforedit"><i class="fa fa-times"></i></span><img src="'.$picto.'" /></a>';
			            }else{
			                $filesdiv .= '<a href="'.DOL_URL_ROOT.'/document.php?modulepart=gestionnotifs&attachment=0&file='.$foldername.$minifile.'" datafile="'.$img.'" class="delete_file documentpreview" '.$mime.' onclick="to_delete_file(this,event,'.$objcomment->rowid.')"  title="'.(isset($filename[1]) ? $filename[1] : '').'"><span class="onlyforedit"><i class="fa fa-times"></i></span><img src="'.$nopicto.'" /></a>';
			            }
			            $filesdiv .= '</li>';
			        }
			    }
			}

			$filesdiv .= '</ul>';
			$filesdiv .= '<input type="hidden" name="files_deleted" class="files_deleted" />';
			$filesdiv .= '<div style="clear:both;"></div>';
			$filesdiv .= '</div>';

		}


		$filesdiv .= '<div class="one_file onlyforedit">';
			$filesdiv .= '<span class="add_joint" onclick="trigger_upload_file(this)"><i class="fa fa-paperclip"></i></span>';
			$filesdiv .= '<input class="add_photo" type="file" name="files[]" onchange="change_upload_file(this)"/>';
		$filesdiv .= '</div>';

		$filesdiv .= '<span class="add_plus onlyforedit" onclick="new_input_joint(this)"><i class="fa fa-plus"></i></span>';
			
		$filesdiv .= '</div>';

		return $filesdiv;
		
    }

   
	public function sendMailWhenReplyComment($object, $id_comment, $linkobj)
	{
		global $conf, $langs, $user;

		if(!empty($conf->societedealeco->enabled)) return 0;
		
		// return 0; // ONLY FOR TESTING

		$form = new Form($this->db);

		$comment_type = (int) GETPOST("comment_type", 'int');

		if(empty($conf->global->GESTIONNOTIFS_ENABLE_SEND_EMAIL_WHEN_REPLY_TO_COMMENT) || $comment_type == $this::PUBLIC) return 0;

		// $objcom = new gt_comments($this->db);

		// if($fk_comment) {
		// 	$objcom->fetch($fk_comment);
		// }


        $returned = $this->getCommentContent(false);
        $comment = $returned['comment'];

        $usersids = array();

        if($comment_type == $this::PUBLIC_WITH_MAIL) {
        	$usersarr = $form->select_dolusers(array(), '', 0, [$user->id], $disabled = 0, $include = '', $enableonly = '', $force_entity = '0', $maxlength = 0, $showstatus = 0, $morefilter = " AND u.email !='' ", $show_every = 0, $enableonlytext = '', $morecss = 'width80p', $noactive = 1, $outputmode = 1, $multiple = true);
        	if(is_array($usersarr) && count($usersarr) > 0) {
        		$usersids = array_keys($usersarr);
        	}
        } else {
			$usersids = isset($returned['users_affected']) ? explode(',', $returned['users_affected']) : array();
        }

		$res = 0;

		$linkobj = $linkobj.'&cid='.$id_comment;
		if(count($usersids) > 0) {
			foreach ($usersids as $key => $usertosent) {
				$user_ = new User($this->db);
		        $user_->fetch($usertosent);

				if(empty($user_->email) || ($usertosent == $user->id)) continue;

				include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
		        $formmail   = new FormMail($this->db);

				// Template Mail : Get message template
		        $arraydefaultmessage = $formmail->getEMailTemplate($this->db, 'gestionnotifs_mail_send', $user, $langs, 0);

		        // Is the message in html
		        $msgishtml = -1; // Unknown by default
		        if (preg_match('/[\s\t]*<html>/i', $arraydefaultmessage->content)) {
		            $msgishtml = 1;
		        }

				$sendto = str_replace(',', ' ', dolGetFirstLastname($user_->firstname, $user_->lastname))." <".$user_->email.">";

		        $substitutionarray = array();

		        $substitutionarray['__GESTIONNOTIFS_OBJECT_REF__'] 		= isset($object->ref) ? $object->ref : '';
		        $substitutionarray['__GESTIONNOTIFS_COMMENT_LINK__'] 	= '<a href="'.$linkobj.'" target="_blank">'.$linkobj.'</a>';
		        // $substitutionarray['__GESTIONNOTIFS_YOUR_COMMENT__'] 	= $objcom->comment;
		        $substitutionarray['__GESTIONNOTIFS_COMMENT_AUTHOR__']	= dolGetFirstLastname($user->firstname, $user->lastname);
		        $substitutionarray['__GESTIONNOTIFS_COMMENT_EMAIL__']	= $user->email;
		        $substitutionarray['__GESTIONNOTIFS_COMMENT_CONTENT__']	= $comment;

		        $parameters = array(
		            'mode' => 'formemail'
		        );
		        complete_substitutions_array($substitutionarray, $langs, $object, $parameters);

		        $defaultmessage = $arraydefaultmessage->content;
		        $message = make_substitutions($defaultmessage, $substitutionarray);

		        $defaultopic = $arraydefaultmessage->topic;
		        $subject = make_substitutions($defaultopic, $substitutionarray);

		        // d($subject, false);
		        // d($message);

		        $moreinheader = '';

		        $arr_file = array();
		        $arr_mime = array();
		        $arr_name = array();
		        $arr_css  = array();

		        // $upload_dir = $conf->gestionnotifs->dir_output.'/'.$obj->ref;
		        // $trackid = 'gestionnotifs'.'-'.$obj->rowid;

		        // $listofpaths = dol_dir_list($upload_dir, 'all', 0, '', '', 'name', SORT_ASC, 0);
		        // if (count($listofpaths)) {
		        //     foreach ($listofpaths as $key => $val) {
		        //         $arr_file[] = $listofpaths[$key]['fullname'];
		        //         $arr_mime[] = dol_mimetype($listofpaths[$key]['name']);
		        //         $arr_name[] = $listofpaths[$key]['name'];
		        //     }
		        // }
		        	
		        $res = 1;

		        $from       = $user->email ? $user->email : $conf->global->MAIN_MAIL_EMAIL_FROM;
		        $replyto    = '';

		        // Mail making
		        $trackid = 'gestionnotifs-'.$object->id;
		        include_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
		        $mail = new CMailFile($subject, $sendto, $from, $message, $arr_file, $arr_mime, $arr_name, '', '', 0, $msgishtml, $errorsto, $arr_css, $trackid, $moreinheader, 'emailing');

		        if ($mail->error) {
		            $res = 0;
		        }

		        // Send mail
		        if ($res) {
		            $res = $mail->sendfile();
		        }

		        if ($mail->error) {
		        	setEventMessages($mail->error, null, 'errors');
		            return 0;
		        }

			}
		}

        return $res;
    }

   
	public function getcommentschild($module, $fk_module, $fk_comment, $champ="id", $srch_comment = '', &$newcommentfiles, $onlyshow = 0)
	{
		global $langs, $user, $conf, $object;
		$html = '';
		$sql = "SELECT * FROM ".MAIN_DB_PREFIX.get_class($this);
		// $sql .= " WHERE name_module='".$module."' AND fk_module=".$fk_module." AND fk_comment=".$fk_comment;
		$sql .=" WHERE 1>0";
		$sql .= " AND entity=".$conf->entity;

		$liercomments = !empty($conf->global->GESTIONNOTIFS_LIER_COMMENT_EVENET_COMMENT_THIRDPARTY) ? 1 : 0;

		if($liercomments && $module == 'action'){
			$sql .= ' AND (name_module ="action" AND fk_module ='.$fk_module.' AND fk_comment='.$fk_comment.') OR (name_module ="societe" AND fk_module ='.intval($object->socid).' AND fk_comment='.$fk_comment.')';
		}
		elseif($liercomments && $module == 'societe'){
			$sql .= ' AND (name_module ="societe" AND fk_module ='.$fk_module.' AND fk_comment='.$fk_comment.') OR (name_module ="action" AND (action_socid ='.intval($fk_module).') AND fk_comment='.$fk_comment.')';
		}
		else{
			$sql .= " AND name_module='".$module."' AND fk_module=".$fk_module." AND fk_comment=".$fk_comment;
		}

		$sql .= $this->filtebyCommentType($module, $srch_comment_type = -2);

		if($srch_comment) $sql .= ' AND comment like "%'.$srch_comment.'%"';

		$sql .= $this->db->order('date', 'ASC');
		// echo ($sql);
		$resql = $this->db->query($sql);

		if(!$newcommentfiles)
			$newcommentfiles = $this->getJoinedFiles($this);

		if($resql){
			while ($item = $this->db->fetch_object($resql)) {

				// d($item,0);
				$html .= '<div class="comment_fild">';
					$html .= '<div class="gestionnotifscommentdiv">';
						$user_ = new User($this->db);
						$user_->fetch($item->fk_user);

						$html .= '<span class="gestionnotifspictouser">';
						$html .= $user_->getNomUrl(-3, '', 0, 0, 0, 0, '', 'paddingright valigntextbottom');
						$html .= '</span>';

						$html .= '<span ><b>'.$user_->firstname.' '.$user_->lastname.'</b></span>';
						$html .= '<span class="date_comment">'.date('d/m/Y H:i',strtotime($item->tms)).'</span>';
						if($user->id == $item->fk_user){
							$html .= '<a class="editcomment" id="editcomment_'.$item->rowid.'" data-id="'.$item->rowid.'">'.img_edit($langs->trans('Edit')).'</a>';
							$html .= '<a class="annulcomment" id="annulcomment_'.$item->rowid.'" data-id="'.$item->rowid.'">';
								$html .= '<span class="fa fa-remove" title="'.$langs->trans('Cancel').'"></span>';
							$html .= '</a>';
							$html .= '<a href="commentaire.php?'.$champ.'='.$fk_module.'&id_delete='.$item->rowid.'&action=delete" class="removecomment">'.img_delete($langs->trans('Delete')).'</a>';
						}
						$html .= '<a class="repond" id="repond_'.$item->rowid.'" data-id="'.$item->rowid.'">'.$langs->trans("repond").'</a>';
						$html .= '<br>';
						$html .= '<div class="gestionnotifscommenttext">';
							// $html .= '<span id="text_comment_'.$item->rowid.'" class="text_comment">'.dol_string_onlythesehtmltags($item->comment).'</span>';
							$html .= '<span id="text_comment_'.$item->rowid.'" class="text_comment">'.dol_string_onlythesehtmltags($item->comment);
							$filesdiv = $this->getJoinedFiles($item);
							$html .= $filesdiv;
							$html .= '</span>';

							$html .= '<form method="post" action="'.$_SERVER["PHP_SELF"].'" enctype="multipart/form-data">';
								$html .= '<input type="hidden" name="action" value="update" />';
							    $html .= '<input type="hidden" name="'.$champ.'" value="'.$fk_module.'" />';
							    $html .= '<input type="hidden" class="id_edit" name="id_edit" value="'.$item->rowid.'" />';
								$html .= '<input type="hidden" name="page" value="0" />';
								$html .= '<div id="edit_comment_'.$item->rowid.'" class="edit_comment" >';
									if(!$onlyshow) {
										$html .= $this->dolEditorWithSelectUsers($item, 'txtcomment_update_', $item->comment, $item->rowid, $item->fk_user);
									}
									$html .= $filesdiv;
									// $html .= '<textarea class="comment" name="comment" placeholder="'.$langs->trans('your_comment').'">'.nl2br($item->comment).'</textarea>';
									$html .= '<input type="submit" value="'.$langs->trans("sauvg").'" class="sauvg">';
								$html .= '</div>';
							$html .= '</form>';

							$html .= '<form method="post" action="'.$_SERVER["PHP_SELF"].'" enctype="multipart/form-data">';

								$html .= '<input type="hidden" name="action" value="reponder" />';
							    $html .= '<input type="hidden" name="'.$champ.'" value="'.$fk_module.'" />';
							    $html .= '<input type="hidden" class="fk_comment" name="fk_comment" value="'.$item->rowid.'" />';

								$html .= '<div id="repond_comment_'.$item->rowid.'" class="repond_comment">';
									$html .= '<div class="gestionnotifspictouser">';
									$html .= $user->getNomUrl(-3, '', 0, 0, 0, 0, '', 'paddingright valigntextbottom');
									$html .= '</div>';
									if(!$onlyshow)
										$html .= $this->dolEditorWithSelectUsers($this, 'txtcomment_reponder_', '', $item->rowid, $item->fk_user);
									$html .= $newcommentfiles;
									// $html .= '<textarea class="comment" name="comment" placeholder="'.$langs->trans('your_comment').'"></textarea>';
									$html .= '<div class="action_repond"> ';
										$html .= '<input type="submit" value="'.$langs->trans("sauvg").'" class="btnAction">';
										$html .= '<a class="bntAction cancel">Annuler</a>';
									$html .= '</div>';
								$html .= '</div>';
							$html .= '</form>';
						$html .= '</div>';
					$html .= '</div>';
					$html .= $this->getcommentschild($module, $fk_module, $item->rowid, $champ, $srch_comment, $newcommentfiles, $onlyshow);
				$html .= '</div>';
			}
		}
		return $html;
	}

	public function setViewedComment($name_module = '', $fk_module)
    {
        global $user, $gt_comments_already_viewed;

        if(empty($name_module) || empty($fk_module)) return 0;

        $error = 0;
        $now = dol_now();

        if(isset($gt_comments_already_viewed[$name_module.'-'.$fk_module])) return 0;


        $sql = "INSERT INTO ".MAIN_DB_PREFIX."gt_comments_viewedusers (";
        $sql .= " fk_module, name_module, fk_user, viewed_at";
        $sql .= ")";

        $sql .= " VALUES (";

        $sql .= " ".(int) $fk_module;
        $sql .= ", '".$name_module."'";
        $sql .= ", ".(int) $user->id;
        $sql  .= ", '".$this->db->idate($now)."'";

        $sql .= ")";

        $sql .= " ON DUPLICATE KEY UPDATE";
        $sql .= " viewed_at = '".$this->db->idate($now)."'";
        // d($sql);

        $resql = $this->db->query($sql);


        // // Second method (to prevent AUTO_INCREMENT ON DUPLICATE KEY UPDATE)
        // UPDATE ".$tmptable."
        // SET viewed_at = '".$tmpdate."'
        // WHERE name = 'project' ;

        // INSERT INTO ".$tmptable." (name_module, fk_module, fk_user, viewed_at)
        // SELECT 
        //       'project' AS name
        //     , '".$tmpdate."' AS viewed_at 
        // FROM dual 
        // WHERE NOT EXISTS
        //       ( SELECT *
        //         FROM ".$tmptable." p
        //         WHERE p.name = 'project'
        //       ) ;

        if(!$resql) {
            $error = 1;
        }

        $gt_comments_already_viewed[$name_module.'-'.$fk_module] = 1;

        return $error ? 1 : 0;
    }

	public function NbCount($value='')
	{
		# code...
	}
	
} 


?>