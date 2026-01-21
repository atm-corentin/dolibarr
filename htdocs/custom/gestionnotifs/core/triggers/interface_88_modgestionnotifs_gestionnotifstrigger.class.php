<?php
/* Copyright (C) 2005-2009	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2009-2017	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2014		Marcos García		<marcosgdf@gmail.com>
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

/**
 *  \file       htdocs/core/triggers/interface_20_all_Glpitodolibarr.class.php
 *  \ingroup    core
 *  \brief      Trigger file for
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/paiementfourn.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/subscription.class.php';

dol_include_once('/gestionnotifs/class/gt_notifcs.class.php');

/**
 *  Class of triggers 
 */
class Interfacegestionnotifstrigger extends DolibarrTriggers
{
    protected $db;

    /**
     * Constructor
     *
     *  @param      DoliDB      $db     Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
        $this->name = preg_replace('/^Interface/i', '', get_class($this));
        $this->family = "NextGestion";
        $this->description = "Triggers of this module are empty functions.";
        $this->version   = 'development';
        $this->picto = 'glpitodolibarr@glpitodolibarr';
    }

	/**
	 * Function called when a Dolibarrr security audit event is done.
	 * All functions "runTrigger" are triggered if file is inside directory htdocs/core/triggers or htdocs/module/code/triggers (and declared)
	 *
	 * @param string		$action		Event action code
	 * @param Object		$object     Object
	 * @param User			$user       Object user
	 * @param Translate		$langs      Object langs
	 * @param conf			$conf       Object conf
	 * @return int         				<0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
    {
        global $conf;

        $date = dol_now();
        $notifcs = new gt_notifcs($this->db);

        if($object->element && ((int) strpos($action, '_DELETE')) ){

            $liercomments = !empty($conf->global->GESTIONNOTIFS_LIER_COMMENT_EVENET_COMMENT_THIRDPARTY) ? 1 : 0;

            if((empty($liercomments)) || (!empty($liercomments) && $object->element != "action")){
                $sqldelete = "DELETE FROM ".MAIN_DB_PREFIX."gt_comments WHERE name_module = '".$object->element."' AND fk_module = '".$object->id."'";
                $resqldelete = $this->db->query($sqldelete);
            }

        }

        // Actions
        if ($action == 'MEMBER_CREATE')
        {   
            $this->create_notifmember($object,'member','creation');
        }
        elseif($action == 'MEMBER_MODIFY'){
            // print_r($object);die();
            // print_r($object->fields);die();
            $arrayfields = array(
                'ref' =>  "Ref",
                'civility' =>  "UserTitle",
                'lastname' =>  "Lastname",
                'firstname' =>  "Firstname",
                'gender' =>  "Gender",
                'company' =>  "Company",
                'fk_soc' =>  "fk_soc",
                'login' =>  "Login",
                'morphy' =>  "MemberNature",
                'email' =>  "EMail",
                'address' =>  "Address",
                'zip' =>  "Zip",
                'town' =>  "town",
                'phone' =>  "PhonePro",
                'phone_perso' =>  "PhonePerso",
                'phone_mobile' =>  "PhoneMobile",
                'state_id' =>  "State",
                'country_id' =>  "Country",
                'note_public' =>  "note_public",
                'note_private' =>  "note_public",
                'datefin' =>  "SubscriptionEndDate",
                'birth' =>  "DateToBirth",
                'statut' => "Status"
            );
            foreach ($object->fields as $key => $value) {
                if($object->$key != $object->oldcopy->$key){
                    $data[$key]['label'] = $value['label'];
                    $data[$key]['val'] = ($object->$key ? dol_escape_htmltag($object->$key) : $langs->trans("None"));
                    $data[$key]['oldval'] = ($object->oldcopy->$key ? dol_escape_htmltag($object->oldcopy->$key) : $langs->trans("None"));
                }
            }

            $notifcsarray = $notifcs->getExtraFields($object, '', 1, 0);
            if(!empty($notifcsarray) && !empty($data)){
                $data = array_merge($data,$notifcsarray);
            }elseif(!empty($notifcsarray)){
                $data = $notifcsarray;
            }
            
            if(is_array($data)){
                $champs = json_encode($data,JSON_UNESCAPED_SLASHES);
                $champs = str_replace("\\\'", "\'", $champs);
                $this->create_notifmember($object,'member','modification',$champs);
            }else{
                $this->create_notifmember($object,'member','modification');
            }
        }
        elseif($action == 'MEMBER_NEW_PASSWORD'){
            $this->create_notifmember($object,'member','modif_pw');
        }
        elseif($action == 'MEMBER_VALIDATE'){
            $this->create_notifmember($object,'member','validation');
        }
        elseif($action == 'MEMBER_DELETE'){
            $this->create_notifmember($object,'member','delete');
        }
        elseif($action == 'MEMBER_RESILIATE'){
            $this->create_notifmember($object,'member','resiliate');
        }
        elseif($action == 'MEMBER_SUBSCRIPTION_CREATE'){
            $this->create_notifmember($object,'member','create_cotisation','','subscription_'.$object->id,$object->fk_adherent);
        }
        elseif($action == 'MEMBER_SUBSCRIPTION_MODIFY'){
            // $filds = array(
            //     'fk_type' => 'Type',    
            //     'dateh' => 'DateSubscription',     
            //     'datef' => 'DateEndSubscription',    
            //     'amount' => 'Amount',     
            //     'note'  => 'Label',  
            // );
            
            // foreach ($filds as $key => $value) {
            //     if($object->$key != $oldobject->$key){
            //         $data[$key]['label'] = $value;
            //         $data[$key]['val'] = dol_escape_htmltag($oldobject->$key);
            //     }
            // }
            $this->create_notifmember($object,'member','modify_cotisation','','null',$object->fk_adherent);
        }
        elseif($action == 'MEMBER_SUBSCRIPTION_DELETE'){
            $id = GETPOST('rowid');
            $sub = new Subscription($this->db);
            $sub->fetch($id);
            $this->create_notifmember($object,'member','delete_cotisation','',$sub->note,$object->fk_adherent);
        }

        // Tier
        elseif($action == 'COMPANY_CREATE'){
            $this->create_notifmember($object,'societe','create_tier');
        }
        elseif($action == 'COMPANY_MODIFY'){
            // $listofproperties = array(
            //     'name' => 'ThirdPartyName',
            //     'name_alias' => 'AliasNames',
            //     'client' => 'ProspectCustomer',
            //     'code_client' => 'CustomerCode',
            //     'fournisseur' => 'Supplier',
            //     'code_fournisseur' => 'SupplierCode',
            //     'barcode' => 'Gencod',
            //     'status'  => 'Status',
            //     'address' => 'Address',
            //     'zip' => 'Zip',
            //     'country_id' => 'Country',
            //     'town' => 'Town',
            //     'state_id' => 'State',
            //     'email' => 'EMail',
            //     'phone' => 'Phone',
            //     'fax' => 'Fax',
            //     'url' => 'Web',
            //     'tva_assuj' => 'VATIsUsed',
            //     'tva_intra' => 'VATIntra',
            //     // 'localtax1_assuj' => 'LocalTax1IsUsed',
            //     // 'localtax2_assuj' => 'LocalTax2IsUsed',

            //     // 'localtax1_value' => 'localtax1_value',
            //     // 'localtax2_value' => 'localtax2_value',
            //     'typent_id' => 'ThirdPartyType',
            //     'forme_juridique_code' => 'JuridicalStatus',
            //     'effectif_id' => 'Staff',
            //     'capital' => 'Capital',
            //     'default_lang' => 'DefaultLang',
            //     'fk_incoterms' => 'IncotermLabel',
            //     'socialnetworks' => 'socialnetworks',
            //     'idprof1' => 'idprof1',
            //     'idprof2' => 'idprof2',
            //     'idprof3' => 'idprof3',
            //     'idprof4' => 'idprof4',
            //     'idprof5' => 'idprof5',
            //     'idprof6' => 'idprof6',
            //     'logo' => 'Logo',
            //     'webservices_url' => 'WebServiceURL',
            //     'webservices_key' => 'WebServiceKey',
            //     'multicurrency_code' => 'Currency',
            // );
            $listofproperties = $object->fields;
            foreach ($listofproperties as $key => $value) {

                if($object->oldcopy->$key !=-1 && $object->$key !=-1 && $object->$key != $object->oldcopy->$key){
                    $data[$key]['label'] = $value['label'];
                    $data[$key]['val'] = ($object->$key ? dol_escape_htmltag($object->$key) : $langs->trans("None"));
                    $data[$key]['oldval'] = ($object->oldcopy->$key ? dol_escape_htmltag($object->oldcopy->$key) : $langs->trans("None"));
                }
            }

            $notifcsarray = $notifcs->getExtraFields($object, '', 1, 0);
            if(!empty($notifcsarray) && !empty($data)){
                $data = array_merge($data,$notifcsarray);
            }elseif(!empty($notifcsarray)){
                $data = $notifcsarray;
            }
            if(is_array($data)){
                $champs = json_encode($data,JSON_UNESCAPED_SLASHES);
                $champs = str_replace("\\\'", "\'", $champs);
                $this->create_notifmember($object,'societe','edit_tier',$champs);
            }else{
                $this->create_notifmember($object,'societe','edit_tier');
            }


            // $this->create_notifmember($object,'societe','edit_tier');
        }
        elseif($action == 'COMPANY_DELETE'){
            $this->create_notifmember($object,'societe','delete_tier');
        }
        // elseif($action == 'COMPANY_MODIFY'){
        //     $this->create_notifmember($object,'societe','edit_tier');
        // }
        elseif($action == 'COMPANY_RIB_CREATE'){
            $this->create_notifmember($object,'societe','','create_rib_tier');
        }
        elseif($action == 'CONTACT_CREATE'){
            $socid = GETPOST('socid');
            $this->create_notifmember($object,'societe','create_contact_tier','','Contact_'.$object->id,$socid);
        }
        elseif($action == 'CONTACT_UPDATE'){
            $this->create_notifmember($object,'societe','update_contact_tier','','Contact');
        }
        elseif($action == 'CONTACT_DELETE'){
            // print_r($object->id);die();

            // $sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'socpeople WHERE rowid ='.$object->id;
            // $resql = $this->db->query($sql);
            // print_r($resql);
            // // if($resql){
            //     while ($obj = $this->db->fetch_object($resql)) {
            //         $contactid=$obj->firstname.' '.$obj->lastname;
            //     }
            // // }
            // print_r($contactid);die();
            $this->create_notifmember($object,'societe','supprimer_contact_tier','delete','Contact',$object->socid);
        }

        // Product
        elseif($action == 'PRODUCT_CREATE'){
            $this->create_notifmember($object,'product','create_product');
        }
        elseif($action == 'PRODUCT_MODIFY'){
            $listofproperties = array(
                'ref' => 'Ref',
                'label' => 'Label',
                'status' => 'Status',
                'status_buy' => 'Status',
                'status_batch' => 'ManageLotSerial',
                'barcode' => 'BarcodeValue',
                'barcode_type' => 'BarcodeType',
                'description' => 'description',
                'url' => 'PublicUrl',
                'fk_default_warehouse' => 'DefaultWarehouse',
                'seuil_stock_alerte' => 'StockLimit',
                'duration_value' => 'Duration',
                'finished' => 'Nature',
                'duration' => 'Duration',
                'duration_unit'=>'Duration',
                'weight' => 'Weight',
                'weight_units' => 'weight_units',
                'length' => 'Length',
                'length_units' => 'length_units',
                'width' => 'width',
                'width_units' => 'width_units',
                'height' => 'Height',
                'height_units' => 'height_units',
                'surface' => 'Surface',
                'surface_units' => 'surface_units',
                'volume' => 'Volume',
                'volume_units' => 'volume_units',
                'fk_unit' => 'DefaultUnitToShow',
                'net_measure' => 'NetMeasure',
                'net_measure_units' => 'NetMeasure',
                'note_private' => 'NoteNotVisibleOnBill',
                'note' => 'NoteNotVisibleOnBill', 
                'type' => 'fk_product_type',
                'customcode' => 'CustomCode',
                'country_id' => 'CountryOrigin',
                'price' => 'price',
                'price_ttc' => 'price_ttc',
                'price_min' => 'price_min',
                'price_min_ttc' => 'price_min_ttc',
                'price_base_type' => 'price_base_type',
                'cost_price' => 'cost_price',
                'default_vat_code' => 'default_vat_code',
                'tva_tx' => 'tva_tx',
                //! French VAT NPR
                'tva_npr' => 'tva_npr',
                'recuperableonly' => 'tva_npr', // For backward compatibility
                //! Local taxes


                'accountancy_code_buy' => 'ProductAccountancyBuyCode',
                'accountancy_code_sell' => 'ProductAccountancySellCode',
                'accountancy_code_sell_intra' => 'ProductAccountancySellIntraCode',
                'accountancy_code_sell_export' => 'ProductAccountancySellExportCode',

                'desiredstock' => 'desiredstock',
                'stock_reel' => 'stock',
                'pmp' => 'pmp',

                'fk_price_expression' => 'fk_price_expression',
                'price_autogen' => 'price_autogen',

            );
            $i = 0;
            foreach ($listofproperties as $key=>$value) {
                if($object->oldcopy->$key !=-1 && $object->$key !=-1 && $object->$key != $object->oldcopy->$key){
                    $i++;
                    if($key == 'type'){
                        $data = array('type' => ['label'=>$value,'val'=>$object->type]);
                        break;
                    }else{
                        if($object->$key || $object->oldcopy->$key){
                            $data[$key]['label'] = $value;
                            $data[$key]['val'] = ($object->$key ? dol_escape_htmltag($object->$key) : $langs->trans("None"));
                            $data[$key]['oldval'] = ($object->oldcopy->$key ? dol_escape_htmltag($object->oldcopy->$key) : $langs->trans("None"));
                        }
                    }
                }
            }
            $notifcsarray = $notifcs->getExtraFields($object, '', 1, 0);
            if(!empty($notifcsarray) && !empty($data)){
                $data = array_merge($data,$notifcsarray);
            }elseif(!empty($notifcsarray)){
                $data = $notifcsarray;
            }

            if(is_array($data)){

                $champs = json_encode($data,JSON_UNESCAPED_SLASHES);
                $champs = str_replace("\\\'", "\'", $champs);

                $this->create_notifmember($object,'product','edit_product',$champs);
            }else{
                $this->create_notifmember($object,'product','edit_product');
            }
        }
        elseif($action == 'PRODUCT_PRICE_MODIFY'){
            $champs= json_encode(['prix_vente'=>$object->price]);
            $this->create_notifmember($object,'product','product_change_pris',$champs);
        }
        elseif($action == 'SUPPLIER_PRODUCT_BUYPRICE_UPDATE'){
            $this->create_notifmember($object,'product','create_prix_achat','','prixachat_'.$object->product_fourn_price_id,$object->id);
        }elseif($action == 'STOCK_MOVEMENT'){
            $this->create_notifmember($object,'product','add_stok','','stock_'.$object->id,$object->product_id);
        }

        // Facture
        elseif($action == 'BILL_CREATE'){
            $champs= json_encode(['prix_vente'=>$object->price]);
            $this->create_notifmember($object,'facture','facture_change_pris',$champs);
        }
        elseif($action == 'BILL_CREATE'){
            $champs= json_encode(['prix_vente'=>$object->price]);
            $this->create_notifmember($object,'facture','facture_change_pris',$champs);
        }
        elseif($action == 'BILL_CREATE'){
            $this->create_notifmember($object,'facture','create_facture');
        }
        elseif($action == 'BILL_MODIFY'){

            $fields = $object->fields;
            foreach ($fields as $key=>$value) {
                if(isset($object->$key) && isset($object->oldcopy->$key) && $object->$key != $object->oldcopy->$key ){
                    $data[$key]['label'] = $value['label'];
                    $data[$key]['val'] = dol_escape_htmltag($object->$key);
                }
            }

            $notifcsarray = $notifcs->getExtraFields($object, '', 1, 0);
            if(!empty($notifcsarray) && !empty($data)){
                $data = array_merge($data,$notifcsarray);
            }elseif(!empty($notifcsarray)){
                $data = $notifcsarray;
            }

            if(!empty($data) && is_array($data)){
                $champs = json_encode($data,JSON_UNESCAPED_SLASHES);
                $champs = str_replace("\\\'", "\'", $champs);
                $this->create_notifmember($object,'facture','edit_facture',$champs);
            }else{
                $this->create_notifmember($object,'facture','edit_facture');
            }
        }
        elseif($action == 'BILL_PAYED'){
            $this->create_notifmember($object,'facture','payed_facture');
        }
        elseif($action == 'BILL_UNPAYED'){
            $this->create_notifmember($object,'facture','unpayed_facture');
        }
        elseif($action == 'BILL_CANCEL'){
            $this->create_notifmember($object,'facture','annule_facture');
        }
        elseif($action == 'BILL_VALIDATE'){
            $this->create_notifmember($object,'facture','valider_facture');
        }
        elseif($action == 'BILL_UNVALIDATE'){
            $this->create_notifmember($object,'facture','unvalid_facture');
        }
        elseif($action == 'LINEBILL_INSERT'){
            $product = new Product($this->db);
            if($object->fk_product){
                $product->fetch($object->fk_product);
                $descrp = addslashes($product->label);
            }else{
                $descrp =addslashes($object->description);
            }
            $filed = $descrp.':'.$object->qty.':'.$object->total_ttc;
            $this->create_notifmember($object,'facture','inset_line_facture','',$filed,$object->fk_facture);
        }

        elseif($action == 'FACTURE_ADD_CONTACT'){

            $insert_id=$this->db->last_insert_id(MAIN_DB_PREFIX.'element_contact');
            $sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'element_contact WHERE rowid ='.$insert_id;
            $sql .= ' AND entity='.$conf->entity;
            $resql = $this->db->query($sql);
            if($resql){
                while ($obj = $this->db->fetch_object($resql)) {
                    $contactid=$obj->fk_socpeople;
                    $id=$obj->element_id;
                }
                // $id = (GETPOST('id', 'int') ? GETPOST('id', 'int') : GETPOST('facid', 'int'));
                $this->create_notifmember($object,'facture','create_contact_facture','','Contact_'.$contactid,$id);
            }

        }
        elseif($action == 'FACTURE_DELETE_CONTACT'){
            $proj_id = GETPOST('lineid');
            $sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'element_contact WHERE rowid ='.$proj_id;
            $sql .= ' AND entity='.$conf->entity;
            $resql = $this->db->query($sql);
            if($resql){
                while ($obj = $this->db->fetch_object($resql)) {
                    $contactid=$obj->fk_socpeople;
                }
                $id = GETPOST('id');
            $this->create_notifmember($object,'facture','delete_contact_facture','','Contact_'.$contactid,$id);
            }
        }
        elseif($action == 'PAYMENT_CUSTOMER_CREATE'){
            $amount = 0;
            $id_fact = GETPOST('facid');
            $sql = 'SELECT fk_facture, amount FROM '.MAIN_DB_PREFIX.'paiement_facture WHERE fk_paiement='.$object->id;
            $sql .= ' AND entity='.$conf->entity;
            $resql =$this->db->query($sql);
            if($resql){
                while ($obj = $this->db->fetch_object($resql)) {
                    $id_fact = $obj->fk_facture;
                    continue;
                }
            }
            foreach ($object->amounts as $key => $value) {
                $amount += $value;
            }

            $filed = $object->ref.':'.$amount;
            $this->create_notifmember($object,'facture','create_reglement_facture','',$filed,$id_fact);
        }
        elseif($action == 'PAYMENT_CUSTOMER_DELETE'){
            $sql = 'SELECT fk_facture FROM '.MAIN_DB_PREFIX.'paiement_facture WHERE fk_paiement='.$object->id;
            $sql .= ' AND entity='.$conf->entity;
            $resql =$this->db->query($sql);
            $id_fact = GETPOST('facid') ? GETPOST('facid') : GETPOST('id');

            if($resql){
                while ($obj = $this->db->fetch_object($resql)) {
                    $id_fact = $obj->fk_facture;
                    continue;
                }
            }
            $filed = $object->ref;
            $this->create_notifmember($object,'facture','supprime_reglement_facture','',$filed,$id_fact);
        }

        // Projet
        elseif($action == 'PROJECT_CREATE'){
            $this->create_notifmember($object,'project','create_project');
        }
        elseif($action == 'PROJECT_MODIFY'){
            $fields = array(
                'ref' => 'ref',
                'title' => 'title',
                'description' => 'description',
                'date_start' => 'DateStart',
                'date_end' => 'DateEnd',
                'socid' => 'fk_soc',
                'public' => 'Visibility',
                'statut' => 'Status',
                'opp_status' => 'OpportunityStatus',
                'opp_amount  ' => 'OpportunityAmount',
                'opp_percent' => 'OpportunityProbability',
                'budget_amount' => 'Budget',
                'usage_opportunity' => 'Usage'
            );
            $fields = $object->fields;
            foreach ($fields as $key=>$value) {
                
                if($object->$key != $object->oldcopy->$key){

                    // $data[$key]['label'] = $value;
                    // $data[$key]['oldval'] = dol_escape_htmltag($object->oldcopy->$key);
                    // $data[$key]['val'] = dol_escape_htmltag($object->$key);

                    $data[$key]['label'] = trim($value['label']);

                    $val = is_numeric($object->$key) ? $object->$key : trim($object->$key);
                    $oldval = is_numeric($object->oldcopy->$key) ? $object->oldcopy->$key : trim($object->oldcopy->$key);

                    $data[$key]['val'] = !empty($val) ? $val : trim($langs->trans("None"));
                    $data[$key]['oldval'] = !empty($oldval) ? $oldval : trim($langs->trans("None"));
                }
            }

            $notifcsarray = $notifcs->getExtraFields($object, '', 1, 0);
            if(!empty($notifcsarray) && !empty($data)){
                $data = array_merge($data,$notifcsarray);
            }elseif(!empty($notifcsarray)){
                $data = $notifcsarray;
            }

            if(is_array($data)){
                $champs = json_encode($data,JSON_UNESCAPED_SLASHES);
                $champs = str_replace("\\\'", "\'", $champs);
                $this->create_notifmember($object,'project','edit_project',$champs);
            }else{
                $this->create_notifmember($object,'project','edit_project');
            }
        }

        elseif($action == 'PROJECT_VALIDATE'){
            $this->create_notifmember($object,'project','valider_project');
        }
        elseif($action == 'TASK_CREATE'){
            $filed = $object->ref.'-'.$object->label;
            $this->create_notifmember($object,'projet_task','create_task_project','',$filed,$object->id);
        }
        elseif($action == 'TASK_DELETE'){
            $filed = $object->ref.'-'.$object->label;
            $this->create_notifmember($object,'projet_task','supprime_task_project','',$filed,$object->id);
        }
        elseif($action == 'TASK_TIMESPENT_CREATE'){
            $this->create_notifmember($object,'project','add_time_project','',$object->timespent_id,$object->fk_project);
        }
        elseif($action == 'TASK_TIMESPENT_DELETE'){
            $task = new Task($this->db);
            $task->fetch($object->id);
            $this->create_notifmember($object,'project','delete_time_project','',$object->id.'_'.$object->timespent_duration,$task->fk_project);
        }
        elseif($action == 'TASK_MODIFY'){

            $fileds = array(
                'ref' => 'Ref',
                'fk_task_parent' => 'ChildOfProjectTask',
                'label' => 'Label',
                'description' => 'Description',
                'duration_effective' => 'duration_effective',
                'planned_workload' => 'PlannedWorkload',
                'date_start' => 'DateStart',
                'date_end'             => 'DateEnd',
                'budget_amount' => 'Budget',
                'progress' => 'ProgressDeclared'
                
            );
            foreach ($fileds as $key => $value) {
                if(isset($object->$key) && isset($object->oldcopy->$key) && $object->$key != $object->oldcopy->$key ){
                    $data[$key]['label'] = $value;
                    $data[$key]['val'] = ($object->$key ? dol_escape_htmltag($object->$key) : $langs->trans("None"));
                    $data[$key]['oldval'] = ($object->oldcopy->$key ? dol_escape_htmltag($object->oldcopy->$key) : $langs->trans("None"));
                }
            }
            $filed = $object->id;

            $notifcsarray = $notifcs->getExtraFields($object, '', 1, 0);
            if(!empty($notifcsarray) && !empty($data)){
                $data = array_merge($data,$notifcsarray);
            }elseif(!empty($notifcsarray)){
                $data = $notifcsarray;
            }

            if(is_array($data)){
                $champs = json_encode($data,JSON_UNESCAPED_SLASHES);
                $champs = str_replace("\\\'", "\'", $champs);
                $this->create_notifmember($object,'projet_task','edit_task_project',$champs,$filed,$object->id);
            }else{
                $this->create_notifmember($object,'projet_task','edit_task_project','',$filed,$object->id);
            }
        }
        elseif($action == 'PROJECT_ADD_CONTACT'){
            $insertid =  $this->db->last_insert_id(MAIN_DB_PREFIX.'element_contact');
            $sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'element_contact WHERE rowid ='.$insertid;
            $sql .= ' AND entity='.$conf->entity;
            $resql = $this->db->query($sql);
            if($resql){
                while ($obj = $this->db->fetch_object($resql)) { 
                    $contactid=$obj->fk_socpeople;
                }
            }
            $id = GETPOST('id');
            if($contactid)
                $this->create_notifmember($object,'project','create_contact_project','','Contact_'.$contactid,$id);
        }
        elseif($action == 'PROJECT_DELETE_CONTACT'){

            $this->create_notifmember($object,'project','delete_contact_project','','Contact_'.$contactid,$id);
        }

        // Propal
        elseif($action == 'PROPAL_CREATE'){
            $this->create_notifmember($object,'propal','create_propal');
        }
        elseif($action == 'PROPAL_MODIFY'){
            $fields = array(
                'socid'=>"ThirdParty",              
                'fk_project'=>"Project",           
                'note'=>"Notes",      
                'note_private'=>"NotePrivate",         
                'note_public'=>'NotePublic',         
                'statut'=>"statut",        

                'datec'=>"DateCreation",                
                // 'date'=>"DatePropal",                
                'datep'=>"DatePropal",               
                'fin_validite'=>"DateEnd",       
                'date_livraison'=>"DeliveryDate",       
                'shipping_method_id'=>"SendingMethod",   
                'demand_reason_id'=>"Source",     

                'mode_reglement_id'=>"PaymentMode",    
                'fk_account'=>"BankAccount",          
                'cond_reglement_id'=>"PaymentConditionsShort",    


                'fk_incoterms'=>"IncotermLabel",

                'multicurrency_code'=>'Currency', 
                'multicurrency_tx'=>'CurrencyRate',         
                'multicurrency_total_ht'=>'MulticurrencyAmountHT', 
                'multicurrency_total_tva'=>'MulticurrencyAmountVAT',  
                'multicurrency_total_ttc'=>'MulticurrencyAmountTTC'  

            );


            foreach ($fields as $key => $value) {
                
                if($object->$key != $object->oldcopy->$key){
                    $data[$key]['label'] = $value;
                    $data[$key]['oldval'] = !empty($object->oldcopy->$key) ? $object->oldcopy->$key : $langs->trans('None'); 
                    $data[$key]['val'] = !empty($object->$key) ? $object->$key : $langs->trans('None');
                }
            }

            $notifcsarray = $notifcs->getExtraFields($object, '', 1, 0);
            if(!empty($notifcsarray) && !empty($data)){
                $data = array_merge($data,$notifcsarray);
            }elseif(!empty($notifcsarray)){
                $data = $notifcsarray;
            }

            if(!empty($data) && is_array($data)){
                $champs = json_encode($data,JSON_UNESCAPED_SLASHES);
                $champs = str_replace("\\\'", "\'", $champs);
                $this->create_notifmember($object,'propal','edit_propal',$champs);
            }else{
                $this->create_notifmember($object,'propal','edit_propal');
            }
        }
        elseif($action == 'PROPAL_VALIDATE'){
            $filed = $object->ref;
            if($object->label)
                $filed .= '-'.$object->label;
            $this->create_notifmember($object,'propal','valid_propal','',$filed,$object->id);
        }
        elseif($action == 'LINEPROPAL_INSERT'){
            $product = new Product($this->db);
            if($object->fk_product){
                $product->fetch($object->fk_product);
                $descrp = addslashes($product->label);
            }else{
                $descrp =addslashes($object->desc);
            }
            $filed = $descrp.':'.$object->qty.':'.$object->total_ttc;
            $this->create_notifmember($object,'propal','inset_line_propal','',$filed,$object->fk_propal);
        }
        elseif($action == 'PROPAL_ADD_CONTACT'){
            $insert_id=$this->db->last_insert_id(MAIN_DB_PREFIX.'element_contact');

            $sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'element_contact WHERE rowid ='.$insert_id;
            $sql .= ' AND entity='.$conf->entity;
            $resql = $this->db->query($sql);
            if($resql){
                while ($obj = $this->db->fetch_object($resql)) {
                    $contactid=$obj->fk_socpeople;
                }
                $id = GETPOST('id');
                $this->create_notifmember($object,'propal','create_contact_propal','','contact_'.$contactid,$id);
            }
        }
        elseif($action == 'PROPAL_DELETE_CONTACT'){
            $proj_id = GETPOST('lineid');
            $sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'element_contact WHERE rowid ='.$proj_id;
            $sql .= ' AND entity='.$conf->entity;
            $resql = $this->db->query($sql);
            if($resql){

                while ($obj = $this->db->fetch_object($resql)) {
                    $contactid=$obj->fk_socpeople;
                }
                $id = GETPOST('id');
                $this->create_notifmember($object,'propal','delete_contact_propal','','Contact_'.$contactid,$id);
            }
        }
       
        // Action - Evenement
        elseif($action == 'ACTION_CREATE'){
            $this->create_notifmember($object,'action','create_action');
        }
        elseif($action == 'ACTION_MODIFY'){
            $fields = array(
                'label'=>"Title",              
                'datep'=>"DateStart",     
                'datef'=>"DateEnd",     
                'location'=>"location",     
                'percentage'=>"StatusPercentage",     
                'socid'=>"ActionOnCompany",     
                'fk_project'=>"Project",     
                // 'fk_element'=>"LinkedObject",     
                'note_private'=>"Description",  
                'userassigned'=>"ActionAssignedTo",
                'socpeopleassigned'=>"ActionOnContact",
                'id'=>"Categories",
            );

            foreach ($fields as $key => $value) {
                if($object->$key != $object->oldcopy->$key){
                    if(!is_array($object->$key) && $object->$key !=-1){
                        $data[$key]['label'] = $value;
                        $data[$key]['oldval'] = !empty($object->oldcopy->$key) ? $object->oldcopy->$key : $langs->trans('None');
                        $data[$key]['val'] = (!empty($object->$key) && $object->$key !=-1) ? $object->$key : $langs->trans('None');
                    }
                    $key_val = is_array($object->$key) ? array_keys($object->$key) : [];
                    $key_oldval = is_array($object->oldcopy->$key) ? array_keys($object->oldcopy->$key) : [];

                    if($key_val || $key_oldval){
                        $array = array_diff($key_val, $key_oldval);
                        if(is_array($object->$key)){
                            $val = !empty($object->$key) ? json_encode($object->$key,JSON_UNESCAPED_SLASHES) : '';
                            $val = str_replace("\\\'", "\'", $val);
                        }
                        if(is_array($object->oldcopy->$key)){
                            $valold = !empty($object->oldcopy->$key) ? json_encode($object->oldcopy->$key,JSON_UNESCAPED_SLASHES) : '';
                            $valold = str_replace("\\\'", "\'", $valold);
                        }
                        if(!empty($array) || !empty($key_val) && empty($key_oldval) || empty($key_val) && !empty($key_oldval)){
                            if(is_array($object->$key)){
                                $data[$key]['label'] = $value;
                                $data[$key]['val'] = $val;
                            }
                            if(is_array($object->oldcopy->$key)){
                                $data[$key]['label'] = $value;
                                $data[$key]['oldval'] = $valold;
                            }
                        }
                        
                    }
                }
                
            }

            $notifcsarray = $notifcs->getExtraFields($object, '', 1, 0);
            if(!empty($notifcsarray) && !empty($data)){
                $data = array_merge($data,$notifcsarray);
            }elseif(!empty($notifcsarray)){
                $data = $notifcsarray;
            }
// die;    
            if(!empty($data) && is_array($data)){
                $champs = json_encode($data,JSON_UNESCAPED_SLASHES);
                $champs = str_replace("\\\'", "\'", $champs);
                $this->create_notifmember($object,'action','edit_action',$champs);
            }else{
                $this->create_notifmember($object,'action','edit_action');
            }
            // die;
        }



        // Contact
        elseif($action == 'CONTACT_CREATE'){
            $this->create_notifmember($object,'contact','create_contact');
        }
        elseif($action == 'CONTACT_MODIFY'){
            // d($object);
            $fields = array(

                'rowid' =>'TechnicalID',
                'entity' =>'Entity',
                'ref_ext' =>'Ref ext',
                'civility_code' =>'Civility',
                'lastname' =>'Lastname',
                'firstname' =>'Firstname',
                'poste' =>'PostOrFunction',
                'address'=>'Address',
                'zip' =>'Zip',
                'town' =>'Town',
                'fk_departement'=>'Fk departement',
                'fk_pays'=>'Fk pays',
                'socid' =>'ThirdParty',
                'birthday' =>'Birthday',
                'phone'=>'Phone',
                'phone_perso' =>'PhonePerso',
                'phone_mobile'=>'PhoneMobile',
                'fax' =>'Fax',
                'email' =>'Email',
                'socialnetworks'=>'SocialNetworks',
                'photo' =>'Photo',
                'priv' =>'ContactVisibility',
                'fk_stcommcontact' =>'ProspectStatus',
                'fk_prospectlevel' =>'ProspectLevel',
                'no_email' =>'No_Email',
                'note_private' =>'NotePrivate',
                'note_public' =>'NotePublic',
                'default_lang' =>'Default lang',
                'canvas' =>'Canvas',
                'datec' =>'DateCreation',
                'tms' =>'DateModification',
                'fk_user_creat' =>'UserAuthor',
                'fk_user_modif'=>'UserModif',
                'statut'=>'Status',
                'import_key' =>'ImportId'

            );

            foreach ($fields as $key => $value) {
                if($object->$key != $object->oldcopy->$key){
                    $data[$key]['label'] = $value;
                    $data[$key]['oldval'] = !empty($object->oldcopy->$key) ? $object->oldcopy->$key : $langs->trans("None");
                    $data[$key]['val'] = !empty($object->$key) ? $object->$key : $langs->trans("None");
                }
            }

            $notifcsarray = $notifcs->getExtraFields($object, '', 1, 0);
            if(!empty($notifcsarray) && !empty($data)){
                $data = array_merge($data,$notifcsarray);
            }elseif(!empty($notifcsarray)){
                $data = $notifcsarray;
            }
            if(is_array($data)){
                $champs = json_encode($data,JSON_UNESCAPED_SLASHES);
                $champs = str_replace("\\\'", "\'", $champs);
                $this->create_notifmember($object,'contact','edit_contact',$champs);
            }else{
                $this->create_notifmember($object,'contact','edit_contact');
            }
        }

         elseif($action == 'CONTACT_ENABLEDISABLE'){
            if($object->statut == 1){
                $messags = 'enable_contact';
            }else{
                $messags = 'disable_contact';
            }
            $this->create_notifmember($object,'contact',$messags);
        }





        ####################################################################################### EXPENSE_REPORT
        $tmpaction = '';
        if($object->element == 'expensereport') {
            if($action == 'EXPENSE_REPORT_CREATE'){
                $tmpaction = 'created';
            }
            elseif($action == 'EXPENSE_REPORT_UPDATE'){
                $tmpaction = 'edited';
            }
            elseif(strpos($action, 'EXPENSE_REPORT_') === 0) {
                $tmpaction = $action;
            }
            if($tmpaction) $this->create_notifmember($object, 'expensereport', $tmpaction);
        }


        ####################################################################################### ORDER
        $tmpaction = '';
        if($object->element == 'commande') {
            if($action == 'ORDER_CREATE'){
                $tmpaction = 'created';
            }
            elseif($action == 'ORDER_MODIFY'){
                $tmpaction = 'edited';
            }
            elseif(strpos($action, 'ORDER_') === 0) {
                $tmpaction = $action;
            }
            if($tmpaction) $this->create_notifmember($object, 'commande', $tmpaction);
        }
        elseif($object->element == 'commandedet') {
            if(strpos($action, 'LINEORDER_') === 0){
                $tmpaction = 'edited';
                $tmpobject = new Commande($this->db);
                $tmpfk_eleme = $tmpobject->fk_element;
                $res = $tmpobject->fetch($object->$tmpfk_eleme);
                if($res) $this->create_notifmember($tmpobject, 'commande', $tmpaction);
            }
        }


        ####################################################################################### ORDER Supplier
        $tmpaction = '';
        if($object->element == 'order_supplier') {
            if($action == 'ORDER_SUPPLIER_CREATE'){
                $tmpaction = 'created';
            }
            elseif($action == 'ORDER_SUPPLIER_MODIFY'){
                $tmpaction = 'edited';
            }
            elseif(strpos($action, 'ORDER_SUPPLIER_') === 0) {
                $tmpaction = $action;
            }
            if($tmpaction) $this->create_notifmember($object, 'order_supplier', $tmpaction);
        }
        elseif($object->element == 'commande_fournisseurdet') {
            if(strpos($action, 'LINEORDER_SUPPLIER_') === 0){
                $tmpaction = 'edited';
                $tmpobject = new CommandeFournisseur($this->db);
                $tmpfk_eleme = $tmpobject->fk_element;
                $res = $tmpobject->fetch($object->$tmpfk_eleme);
                if($res) $this->create_notifmember($tmpobject, 'order_supplier', $tmpaction);
            }
        }


        ####################################################################################### Invoice Supplier
        $tmpaction = '';
        if($object->element == 'invoice_supplier') {
            if($action == 'BILL_SUPPLIER_CREATE'){
                $tmpaction = 'created';
            }
            elseif($action == 'BILL_SUPPLIER_UPDATE'){
                $tmpaction = 'edited';
            }
            elseif(strpos($action, 'BILL_SUPPLIER_') === 0) {
                $tmpaction = $action;
            }
            if($tmpaction) $this->create_notifmember($object, 'invoice_supplier', $tmpaction);
        }
        elseif($object->element == 'facture_fourn_det') {
            if(strpos($action, 'LINEBILL_SUPPLIER_') === 0){
                $tmpaction = 'edited';
                $tmpobject = new FactureFournisseur($this->db);
                $tmpfk_eleme = $tmpobject->fk_element;
                $res = $tmpobject->fetch($object->$tmpfk_eleme);
                if($res) $this->create_notifmember($tmpobject, 'invoice_supplier', $tmpaction);
            }
        }


        ####################################################################################### Expedition
        $tmpaction = '';
        if($object->element == 'shipping') {
            if($action == 'SHIPPING_CREATE'){
                $tmpaction = 'created';
            }
            elseif($action == 'SHIPPING_MODIFY'){
                $tmpaction = 'edited';
            }
            elseif(strpos($action, 'SHIPPING_') === 0) {
                $tmpaction = $action;
            }
            if($tmpaction) $this->create_notifmember($object, 'shipping', $tmpaction);
        }
        elseif($object->element == 'expeditiondet') {
            if(strpos($action, 'LINESHIPPING_') === 0){
                $tmpaction = 'edited';
                $tmpobject = new Expedition($this->db);
                $tmpfk_eleme = $tmpobject->fk_element;
                $res = $tmpobject->fetch($object->$tmpfk_eleme);
                if($res) $this->create_notifmember($tmpobject, 'shipping', $tmpaction);
            }
        }


        ####################################################################################### Reception
        $tmpaction = '';
        if($object->element == 'reception') {
            if($action == 'RECEPTION_CREATE'){
                $tmpaction = 'created';
            }
            elseif($action == 'RECEPTION_MODIFY'){
                $tmpaction = 'edited';
            }
            elseif(strpos($action, 'RECEPTION_') === 0) {
                $tmpaction = $action;
            }
            if($tmpaction) $this->create_notifmember($object, 'reception', $tmpaction);
        }


        ####################################################################################### Reception FREE
        $tmpaction = '';
        if($object->element == 'reception_free') {
            if($action == 'RECEPTION_FREE_CREATE'){
                $tmpaction = 'created';
            }
            elseif($action == 'RECEPTION_FREE_MODIFY'){
                $tmpaction = 'edited';
            }
            elseif(strpos($action, 'RECEPTION_FREE_') === 0) {
                $tmpaction = $action;
            }
            if($tmpaction) $this->create_notifmember($object, 'reception_free', $tmpaction);
        }




    }

    // public function edited_trigger_object($object, $element = '') {
    //     global $langs;

    //     if($object->oldcopy) {
    //         $fields = $object->fields;
    //         foreach ($fields as $key=>$value) {
    //             if($object->$key && $object->$key != $object->oldcopy->$key ){

    //                 $data[$key]['label'] = trim($value['label']);

    //                 $val = is_numeric($object->$key) ? $object->$key : trim($object->$key);
    //                 $oldval = is_numeric($object->oldcopy->$key) ? $object->oldcopy->$key : trim($object->oldcopy->$key);

    //                 $data[$key]['val'] = $val ? $val : trim($langs->trans("None"));
    //                 $data[$key]['oldval'] = $oldval ? $oldval : trim($langs->trans("None"));
    //             }
    //         }

    //     }

    //     if(count($data) > 0){
    //         $champs = json_encode($data,JSON_UNESCAPED_SLASHES);
    //         $champs = str_replace("\\\'", "\'", $champs);
    //         $result = $this->create_notifmember($object, $element,'edited', $champs);
    //     }else{
    //         $result = $this->create_notifmember($object, $element,'edited');
    //     }

    //     return $result;
    // }

    public function create_notifmember($object,$module,$action,$champs='',$filed='',$id_module=0){
        global $user, $conf, $langs;
      
        $notif = new gt_notifcs($this->db);
        if(!empty($filed)){
            $data = [
                'fk_user'   => $user->id,
                'fk_module' => $id_module,
                'date'      => date('Y-m-d H:i:s'),
                'action'    => $action,
                'name_module' => $module,
                'champs' => $this->db->escape($champs), 
                'entity' => $conf->entity, 
            ];
            if($action=='supprimer_contact_tier'){
                $data['modul_child'] = $this->db->escape($object->lastname).' '.$this->db->escape($object->firstname); 
            }else{
                $data['modul_child'] = $this->db->escape($filed);
            }
        }else{

            if(!$champs && $action == 'edited') {
                $data = array();
                if($object->oldcopy) {
                    $fields = $object->fields;
                    foreach ($fields as $key=>$value) {
                        if(isset($object->$key) && isset($object->oldcopy->$key) && $object->$key != $object->oldcopy->$key ){

                            $data[$key]['label'] = trim($value['label']);

                            $val = is_numeric($object->$key) ? $object->$key : trim($object->$key);
                            $oldval = is_numeric($object->oldcopy->$key) ? $object->oldcopy->$key : trim($object->oldcopy->$key);

                            $data[$key]['val'] = $val ? $val : trim($langs->trans("None"));
                            $data[$key]['oldval'] = $oldval ? $oldval : trim($langs->trans("None"));
                        }
                    }

                }
                if(count($data) > 0){
                    $champs = json_encode($data,JSON_UNESCAPED_SLASHES);
                    $champs = str_replace("\\\'", "\'", $champs);
                }
            }

            $data =[
                'fk_user'   => $user->id,
                'fk_module' => $object->id,
                'date'      => date('Y-m-d H:i:s'),
                'action'    => $action,
                'name_module' => $module,
                'champs' => $this->db->escape($champs), 
                'entity' => $conf->entity, 
            ];
        }
        $result = $notif->create(1,$data);
    }

}
