<?php 
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php'; 
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php'; 
dol_include_once('/gestionnotifs/class/gt_comments.class.php');

class gt_notifcs extends Commonobject{ 

	public $errors = array();
	public $rowid;
	public $fk_user;
	public $action;
	public $date;
	public $fk_module;
	public $name_module;
	public $modul_child;
	public $champs;

	public $element='gt_notifcs';
	public $table_element='gt_notifcs';

	public function __construct($db){ 
		$this->db = $db;
		return 1;
    }

	public function create($echo_sql=0,$insert)
	{

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

		$sql .= substr($sql_column, 2)." ) VALUES ( ".substr($sql_value, 2)." )";
		$resql = $this->db->query($sql);

		// die($sql);
		if (!$resql) {
			$this->db->rollback();
			$this->errors[] = 'Error '.get_class($this).' '. $this->db->lasterror();
			echo '<pre>';print_r($this->errors);echo '</pre>';
			die($sql);
			
			return 0;
		} 
		// return $this->db->db->insert_id;
		return $this->db->last_insert_id(MAIN_DB_PREFIX.'gt_notifcs');
	}

	public function update($id, array $data,$echo_sql=0)
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		if (!$id || $id <= 0)
			return false;

        $sql = 'UPDATE ' . MAIN_DB_PREFIX .get_class($this). ' SET ';

        if (count($data) && is_array($data))
            foreach ($data as $key => $val) {
                $val = is_numeric($val) ? $val : '"'. $val .'"';
                $val = ($val == '') ? 'NULL' : $val;
                $sql .= ''. $key. ' = '. $val .',';
            }

        $sql  = substr($sql, 0, -1);
        $sql .= ' WHERE rowid = ' . $id;

        $resql = $this->db->query($sql);

		if (!$resql) {
			$this->db->rollback();
			$this->errors[] = 'Error '.get_class($this).' : '. $this->db->lasterror();
			echo '<pre>';print_r($this->errors);echo '</pre>';
			die($sql);
			return -1;
		} 
		return 1;
	}

	public function delete($echo_sql=0)
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$sql 	= 'DELETE FROM ' . MAIN_DB_PREFIX .get_class($this).' WHERE rowid = ' . $this->rowid;
		$resql 	= $this->db->query($sql);
		
		if (!$resql) {
			$this->db->rollback();
			$this->errors[] = 'Error '.get_class($this).' : '.$this->db->lasterror();
			return -1;
		} 

		return 1;
	}

    
	public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, $filter = '', $filtermode = 'AND')
	{
	    global $conf;

		dol_syslog(__METHOD__, LOG_DEBUG);
		$sql = "SELECT * FROM ";
		$sql .= MAIN_DB_PREFIX .get_class($this);
		$sql .= " WHERE entity=".$conf->entity;

		if (!empty($filter)) {
			$sql .= " ".$filter;
		}
		if (!empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}
		if (!empty($limit)) {
			if($offset==1)
				$sql .= " limit ".$limit;
			else
				$sql .= " limit ".$offset.",".$limit;				
		}
		// echo $sql.'<br>';
		$this->rows = array();
		$resql = $this->db->query($sql);

		if ($resql) {
			$num = $this->db->num_rows($resql);

			while ($obj = $this->db->fetch_object($resql)) {
				$line = new stdClass;
                $line->id    	     =  $obj->rowid;
				$line->rowid 	     =  $obj->rowid;
				$line->fk_user 	     =  $obj->fk_user;
				$line->date          =  $obj->date;
				$line->action        =  $obj->action;
				$line->fk_module     =  $obj->fk_module;
				$line->modul_child   =  $obj->modul_child;
				$line->name_module   =  $obj->name_module;
				$line->champs        =  $obj->champs;
				$line->entity        =  $obj->entity;

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
				$this->action 	    =  $obj->action;
				$this->fk_module 	=  $obj->fk_module;
				$this->modul_child 	=  $obj->modul_child;
				$this->name_module 	=  $obj->name_module;
				$this->champs 	    =  $obj->champs;
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
                $result.= '<img height="16" src="'.dol_buildpath('/gestionnotifs/img/icon_gt_notifcs.png',2).'" >&nbsp;';
            if ($withpicto != 2) $result.= $ref;
        }

        $result .= $linkend;

        return $result;
    }

    public function getcountrows(){
	    global $conf;

        $tot = 0;
        $sql = "SELECT COUNT(rowid) as tot FROM ".MAIN_DB_PREFIX.get_class($this);
		$sql .= " WHERE entity=".$conf->entity;
        $resql = $this->db->query($sql);

        if($resql){
            while ($obj = $this->db->fetch_object($resql)) 
            {
                $tot = $obj->tot;
            }
        }
        return $tot;
    }
    public function getExtraFields($object="", $champs="", $triggr=0, $notif=0){
	    global $conf, $langs;

	     $extrafields = new ExtraFields($this->db);
            $extrafields->fetch_name_optionals_label($object->table_element);
            if(!empty($triggr)){
	            if(!empty($extrafields->attributes[$object->table_element]['label'])){
	                foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $value) {
			            if($object->array_options['options_'.$key] != $object->oldcopy->array_options['options_'.$key] ){
			                $data['options_'.$key]['label'] = $value;
			                $data['options_'.$key]['oldval'] = !empty($object->oldcopy->array_options['options_'.$key]) ? $object->oldcopy->array_options['options_'.$key] : $langs->trans('None');
			                $data['options_'.$key]['val'] = !empty($object->array_options['options_'.$key]) ? $object->array_options['options_'.$key] : $langs->trans('None');
			            }
	                }
	            }
	           
        		return $data;
            }

            if(!empty($notif)){
				if($champs){

		            if(!empty($extrafields->attributes[$object->table_element]['label'])){
		                foreach ($extrafields->attributes[$object->table_element]['label'] as $fields => $field) {
							
							$keyfield = 'options_'.$fields;

							if(isset($champs->$keyfield)){
								$oldval = $champs->$keyfield->oldval;
								$newval = $champs->$keyfield->val;
								$namefield = $champs->$keyfield->label;

								$val = str_replace('options_', '', $keyfield);
								if($keyfield){
									$messag .= '<span style="color:#888; padding-right:5px;margin-left:20px;" >';
									if($extrafields->attributes[$object->table_element]['type'][$val] == 'date'){
										$oldfield = ($oldval>0) ? dol_print_date($oldval, 'day') : $langs->trans('None');
										$newfield = ($newval>0) ? dol_print_date($newval, 'day') : $langs->trans('None');
										if($oldfield == $newfield){
											$messag .= "";
										}else{
											$messag .= $langs->trans($namefield);
										}
									}else{
										$messag .= $langs->trans($namefield);
									}
									$messag .= '</span>';
								}

								if($extrafields->attributes[$object->table_element]['type'][$fields] == 'date'){
									$oldfield = ($oldval>0) ? dol_print_date($oldval, 'day') : $langs->trans('None');
									$newfield = ($newval>0) ? dol_print_date($newval, 'day') : $langs->trans('None');
									if($oldfield == $newfield){
										$oldfield = "";
										$newfield = "";
									}else{
										$messag .= $oldfield .' => '.$newfield.'</br>';

									}
								}else{
									// if($extrafields->attributes[$object->table_element]['type'][$val] != 'date'){
									$oldfield = (!empty($oldval) && ($oldval != $langs->trans('None'))) ? $extrafields->showOutputField($fields, $oldval, '', $object->table_element) : $langs->trans('None');
									$newfield = (!empty($newval) && ($newval != $langs->trans('None'))) ? $extrafields->showOutputField($fields, $newval, '', $object->table_element) : $langs->trans('None');

									$messag .= $oldfield .' => '.$newfield.'</br>';
									// }
								}

							}
		                }
		            }
	            }
        	return $messag;
            }

    }
	public function upgradeModuleGtNotif()
    {
        global $conf, $langs;

		dol_include_once('/gestionnotifs/core/modules/modgestionnotifs.class.php');

        $modcore = new modgestionnotifs($this->db);
        
        $lastversion    = $modcore->version;
        $currentversion = dolibarr_get_const($this->db, 'GTNOTIF_LAST_VERSION_OF_MODULE', $conf->entity);

        $error = 0;
        if (!$currentversion || ($currentversion && $lastversion != $currentversion)){
        	$error += $modcore->delete_tabs();
        	$error += $modcore->insert_tabs();
            $res = $this->InitGtNotif();
            if($res)
                dolibarr_set_const($this->db, 'GTNOTIF_LAST_VERSION_OF_MODULE', $lastversion, 'chaine', 0, '', $conf->entity);
            return 1;
        }
        return 0;
    }

	public function InitGtNotif()
	{
		global $conf;

		$sql = "CREATE TABLE IF NOT EXISTS ".MAIN_DB_PREFIX."gt_notifcs (
		  	rowid int NOT NULL AUTO_INCREMENT PRIMARY KEY,
		  	fk_user int NULL,
		  	date date NULL,
		  	action varchar(300) NULL,
		  	fk_module int NULL,
		  	name_module varchar(255) NULL,
		  	modul_child varchar(355) NULL,
  			entity int NOT NULL DEFAULT ".$conf->entity."
		);";	
		$resql = $this->db->query($sql);
		$resql = $this->db->query("ALTER TABLE ".MAIN_DB_PREFIX."gt_notifcs ADD entity int NOT NULL DEFAULT ".$conf->entity);

		$sql = "CREATE TABLE IF NOT EXISTS ".MAIN_DB_PREFIX."gt_comments (
		  	rowid int NOT NULL AUTO_INCREMENT PRIMARY KEY,
		  	fk_user int NULL,
		  	date datetime NULL,
		  	fk_module int NULL,
		  	name_module varchar(255) NULL,
		  	fk_comment int NULL,
		  	comment text NULL,
		  	users_affected text NULL,
		  	comment_type SMALLINT(1) NULL DEFAULT ".gt_comments::PUBLIC_WITH_MAIL.",
		  	tms timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  			entity int NOT NULL DEFAULT ".$conf->entity."
		);";	
		$resql = $this->db->query($sql);
		$resql = $this->db->query("ALTER TABLE ".MAIN_DB_PREFIX."gt_comments ADD entity int NOT NULL DEFAULT ".$conf->entity);
	  	$resql = $this->db->query("ALTER TABLE ".MAIN_DB_PREFIX."gt_comments ADD users_affected text NULL");
	  	$resql = $this->db->query("ALTER TABLE ".MAIN_DB_PREFIX."gt_comments ADD comment_type SMALLINT(1) NULL DEFAULT ".gt_comments::PUBLIC_WITH_MAIL);
		$resql = $this->db->query('ALTER TABLE '.MAIN_DB_PREFIX.'gt_comments ADD tms timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

		$sql = "ALTER TABLE ".MAIN_DB_PREFIX."gt_notifcs ADD champs text NULL";
		$resql = $this->db->query($sql);

		$sql = "ALTER TABLE ".MAIN_DB_PREFIX."gt_notifcs ADD modul_child text NULL";
		$resql = $this->db->query($sql);

		$sql = "ALTER TABLE ".MAIN_DB_PREFIX."gt_notifcs MODIFY modul_child text NULL";
		$resql = $this->db->query($sql);

		$sql = "ALTER TABLE ".MAIN_DB_PREFIX."gt_notifcs MODIFY date datetime NULL";
		$resql = $this->db->query($sql);
		$sql = "ALTER TABLE ".MAIN_DB_PREFIX."gt_notifcs MODIFY date gt_comments NULL";
		$resql = $this->db->query($sql);

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."c_email_templates (entity,module,type_template,label,lang,position,topic,joinfiles,content) VALUES (0, 'gestionnotifs', 'gestionnotifs_mail_send', '(NewAvailableComment)', '', 100, '[__[MAIN_INFO_SOCIETE_NOM]__] - __(NewAvailableComment)__ #__GESTIONNOTIFS_OBJECT_REF__', 1, '<body>\n <p>__(Hello)__,<br><br>\n__(DefaultContentMailRespondComment)__</p>\n<br />\n\n<br />\n\n __(Sincerely)__ <br />\n __[MAIN_INFO_SOCIETE_NOM]__ <br />\n </body>\n');";
        $resql = $this->db->query($sql);

     	$resql = $this->db->query("ALTER TABLE ".MAIN_DB_PREFIX."gt_comments ADD CONSTRAINT rowidfk_comment FOREIGN KEY (fk_comment) REFERENCES ".MAIN_DB_PREFIX."gt_comments (rowid) ON DELETE CASCADE;");

     	$sql = "ALTER TABLE ".MAIN_DB_PREFIX."gt_comments ADD action_socid int NULL";
		$resql = $this->db->query($sql);

     	// Comments viewed users
        $sql = "CREATE TABLE IF NOT EXISTS ".MAIN_DB_PREFIX."gt_comments_viewedusers (
            rowid         int(99) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            fk_module 	int NULL,
		  	name_module 	varchar(20) NULL,
            fk_user       int NOT NULL,
            viewed_at     datetime NULL,
            tms           timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $resql = $this->db->query($sql);
        $resql = $this->db->query("ALTER TABLE ".MAIN_DB_PREFIX."gt_comments_viewedusers ADD UNIQUE INDEX uk_module_fk_user (fk_module, name_module, fk_user);");

		return 1;
	}
} 


?>