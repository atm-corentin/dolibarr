<?php
/* Copyright (C) 2007-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2017-2022 Open-DSI             <support@open-dsi.fr>
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
 *   	\file       htdocs/extraitcompteclient/class/action_extraitcompteclient.class.php
 *		\ingroup    extraitcompteclient
 *		\brief
 */

class ActionsExtraitCompteClient
{
    /**
     * @var DoliDB Database handler.
     */
    public $db;
    /**
     * @var string Error
     */
    public $error = '';
    /**
     * @var array Errors
     */
    public $errors = array();

    /**
     * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
     */
    public $results = array();

    /**
     * @var string String displayed by executeHook() immediately after return
     */
    public $resprints;

    /**
     * Constructor
     *
     * @param        DoliDB $db Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Overloading the doActions function : replacing the parent's function with the one below
     *
     * @param   array()         $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          &$action        Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    function doActions($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $user, $langs;
        $contexts = explode(':', $parameters['context']);

        if (in_array('thirdpartycard', $contexts)) {
            $confirm = GETPOST('confirm');
            $langs->load('extraitcompteclient@extraitcompteclient');

            if ($action == 'builddoc' && GETPOSTISSET('model')) {
                $model = GETPOST('model', 'alpha');
                $account_statut_models = ['account_statut', 'account_statut_csv', 'InfraSPlus_account_statut'];
                if (in_array($model, $account_statut_models)) { // document generated from the documents models list. See formObjectOptions
                    return 1;
                }

                return 0;
            }

            if ($action == 'confirm_extraitcompteclient_generate_account_statut' && $confirm != 'yes') {
                $action = '';
            }
            if ($action == 'confirm_extraitcompteclient_generate_account_statut' && $confirm == 'yes' && $user->rights->extraitcompteclient->societe->generate_account_statut) {
                if (!isset($object->id)) $object->fetch($parameters['id']);
                $model = GETPOST('extraitcompteclient_generate_account_statut_model');
                if (empty($model) || $model < 0) $model = 'account_statut';
                $export_type = GETPOST('extraitcompteclient_generate_account_statut_type');
                if (empty($export_type)) $export_type = 'Customer';
                $date_start = dol_mktime(0, 0, 0, GETPOST('extraitcompteclient_generate_account_statut_date_start_month'), GETPOST('extraitcompteclient_generate_account_statut_date_start_day'), GETPOST('extraitcompteclient_generate_account_statut_date_start_year'));
                $date_end = dol_mktime(0, 0, 0, GETPOST('extraitcompteclient_generate_account_statut_date_end_month'), GETPOST('extraitcompteclient_generate_account_statut_date_end_day'), GETPOST('extraitcompteclient_generate_account_statut_date_end_year'));
                $show_subsidiaries = GETPOST('extraitcompteclient_generate_account_statut_add_subsidiaries') ? 1 : 0;
                $show_invoice_payed = GETPOST('extraitcompteclient_generate_account_statut_invoice_payed') ? 1 : 0;
                $show_payment_deadline = GETPOST('extraitcompteclient_generate_account_statut_payment_deadline') ? 1 : 0;
                $show_invoice_abandoned = GETPOST('extraitcompteclient_generate_account_statut_invoice_abandoned') ? 1 : 0;
                $show_payment_details = GETPOST('extraitcompteclient_generate_account_statut_payment_details') ? 1 : 0;
                $show_ref_supplier = !empty($conf->global->EXTRAITCOMPTECLIENT_DEFAULT_REF_SUPPLIER) ? $conf->global->EXTRAITCOMPTECLIENT_DEFAULT_REF_SUPPLIER : 0;
				$add_product_tags = GETPOST('extraitcompteclient_generate_account_statut_add_product_tags') ? 1 : 0;
				$show_thirdparty_ref = GETPOST('extraitcompteclient_generate_account_statut_thirdparty_ref') ? 1 : 0;
				$add_multicurrency = GETPOST('extraitcompteclient_generate_account_statut_add_multicurrency') ? 1 : 0;
                if (!isset($object->context)) $object->context = array();
                $object->context['account_statut']	= array('date_start'				=> $date_start,
															'date_end'					=> $date_end,
															'export_type'				=> $export_type,
															'show_subsidiaries'			=> $show_subsidiaries,
															'show_invoice_payed'		=> $show_invoice_payed,
															'show_invoice_abandoned'	=> $show_invoice_abandoned,
															'show_payment_details'		=> $show_payment_details,
															'show_payment_deadline'		=> $show_payment_deadline,
															'show_ref_supplier'			=> $show_ref_supplier,
															'add_product_tags'			=> $add_product_tags,
															'show_thirdparty_ref'		=> $show_thirdparty_ref,
															'add_multicurrency'			=> $add_multicurrency);

                // Generate account statut PDF / CSV
                $result = $object->generateDocument($model, $langs, 0, 0, 0);
                if ($result < 0) {
                    dol_print_error($this->db, $result);
                    $this->errors[] = 'Error generate account statut PDF / CSV';
                    return -1;
                }
            }
        }

        return 0;
    }

    /**
     * Overloading the addMoreActionsButtons function : replacing the parent's function with the one below
     *
     * @param   array()         $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          &$action        Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $db, $langs, $user;
        $error = 0; // Error counter
        $myvalue = 1; // A result value

        if (in_array('thirdpartycard', explode(':', $parameters['context']))) {
            $form = new Form($db);
            $langs->load('extraitcompteclient@extraitcompteclient');

            $month = isset($conf->global->SOCIETE_FISCAL_MONTH_START) ? intval($conf->global->SOCIETE_FISCAL_MONTH_START) : 1;
            $year = intval(date('n')) < $month ? intval(date('Y')) - 1 : intval(date('Y'));
            $date_start = new DateTime(sprintf('%04d-%02d-%02d', $year, $month, 1));
            $date_end = new DateTime(sprintf('%04d-%02d-%02d', $year, $month, 1));
            $date_end->add(new DateInterval('P1Y'));
            $date_end->sub(new DateInterval('P1D'));

            $transPDF           = trim($langs->trans('ExtraitCompteClientGenerateAccountStatutModelsPDF'));
            $transCSV           = trim($langs->trans('ExtraitCompteClientGenerateAccountStatutModelsCSV'));
        
        
            // Load array model_docs with activated templates
            $model_docs = array();
            $sql = "SELECT nom";
            $sql .= " FROM ".MAIN_DB_PREFIX."document_model";
            $sql .= " WHERE type = 'company'";
            $sql .= " AND entity = ".$conf->entity;
            $resql = $db->query($sql);
            if ($resql) {
                $i = 0;
                $num_rows = $db->num_rows($resql);
                while ($i < $num_rows) {
                    $array = $db->fetch_array($resql);
                    array_push($model_docs, $array[0]);
                    $i++;
                }
            } else {
                $error = 1;
            }

			$models = [];
            if (in_array('account_statut', $model_docs)) {
                $models['account_statut'] = $transPDF;
            }

            if (in_array('account_statut_csv', $model_docs)) {
                $models['account_statut_csv'] = $transCSV;
            }

            if (!empty(isModEnabled('infraspackplus')) && in_array('InfraSPlus_account_statut', $model_docs)) {
				$langs->load('infraspackplus@infraspackplus');
				$transInfraS	= trim($langs->trans('PDFInfraSPlusGenerateAccountStatutModelsPDF'));
				$models['InfraSPlus_account_statut'] = $transInfraS;
			}

            $transcustomer          = trim($langs->trans('ExtraitCompteClientGenerateAccountStatutTypeCustomer'));
            $transsupplier          = trim($langs->trans('ExtraitCompteClientGenerateAccountStatutTypeSupplier'));
            $transcustomersupplier  = trim($langs->trans('ExtraitCompteClientGenerateAccountStatutTypeCustomerSupplier'));
            if(! empty($object->client) && empty($object->fournisseur)) {
                $export_type = array(
                    'Customer' => $transcustomer,
                );
                $export_default = 'Customer';
            } else if(empty($object->client) && ! empty($object->fournisseur)) {
                $export_type = array(
                    'Supplier' => $transsupplier,
                );
                $export_default = 'Supplier';
            } else {
                $export_type = array(
                    'Customer' => $transcustomer,
                    'Supplier' => $transsupplier,
                    //'Customer/Supplier' => $transcustomersupplier,
                );
                $export_default = 'Customer';
            }

            // Define height of the popup for data extract
            $popupHeight = "430";

            // Define confirmation messages
            $formquestionclone = array(
                array('type' => 'date', 'name' => 'extraitcompteclient_generate_account_statut_date_start_', 'label' => $langs->trans("ExtraitCompteClientGenerateAccountStatutDateStart"), 'value' => $date_start->format('Y-m-d')),
                array('type' => 'date', 'name' => 'extraitcompteclient_generate_account_statut_date_end_', 'label' => $langs->trans("ExtraitCompteClientGenerateAccountStatutDateEnd"), 'value' => $date_end->format('Y-m-d')),
                array('type' => 'select', 'name' => 'extraitcompteclient_generate_account_statut_type', 'label' => $langs->trans("ExtraitCompteClientGenerateAccountStatutType"), 'values' => $export_type, 'default' => $export_default),
                array('type' => 'checkbox', 'name' => 'extraitcompteclient_generate_account_statut_add_subsidiaries', 'label' => $langs->trans("ExtraitCompteClientGenerateAccountStatutAddSubsidiaries"), 'value' => (!empty($conf->global->EXTRAITCOMPTECLIENT_DEFAULT_ADD_SUBSIDIARIES) ? 'true' : 'false')),
                array('type' => 'checkbox', 'name' => 'extraitcompteclient_generate_account_statut_invoice_payed', 'label' => $langs->trans("ExtraitCompteClientGenerateAccountStatutInvoicePayed"), 'value' => (!empty($conf->global->EXTRAITCOMPTECLIENT_DEFAULT_INVOICE_PAYED) ? 'true' : 'false')),
				array('type' => 'checkbox', 'name' => 'extraitcompteclient_generate_account_statut_payment_deadline', 'label' => $langs->trans("ExtraitCompteClientGenerateAccountStatutPaymentDeadline"), 'value' => (!empty($conf->global->EXTRAITCOMPTECLIENT_DEFAULT_PAYMENT_DEADLINE) ? 'true' : 'false')),
                array('type' => 'checkbox', 'name' => 'extraitcompteclient_generate_account_statut_invoice_abandoned', 'label' => $langs->trans("ExtraitCompteClientGenerateAccountStatutInvoiceAbandoned"), 'value' => (!empty($conf->global->EXTRAITCOMPTECLIENT_DEFAULT_INVOICE_ABANDONED) ? 'true' : 'false')),
                array('type' => 'checkbox', 'name' => 'extraitcompteclient_generate_account_statut_payment_details', 'label' => $langs->trans("ExtraitCompteClientGenerateAccountStatutAddPaymentDetails"), 'value' => (!empty($conf->global->EXTRAITCOMPTECLIENT_DEFAULT_PAYMENT_DETAILS) ? 'true' : 'false')),
                array('type' => 'checkbox', 'name' => 'extraitcompteclient_generate_account_statut_add_product_tags', 'label' => $langs->trans("ExtraitCompteClientGenerateAccountStatutAddProductTags"), 'value' => (!empty($conf->global->EXTRAITCOMPTECLIENT_DEFAULT_ADD_PRODUCT_TAGS) ? 'true' : 'false')),
                array('type' => 'checkbox', 'name' => 'extraitcompteclient_generate_account_statut_thirdparty_ref', 'label' => $langs->trans("ExtraitCompteClientGenerateAccountStatutThirdpartyRef"), 'value' => (!empty($conf->global->EXTRAITCOMPTECLIENT_THIRDPARTY_REF) ? 'true' : 'false')),
            );
			if (!empty($conf->multicurrency->enabled)) {
                $popupHeight = $popupHeight + 30;
				$formquestionclone[] = array('type' => 'checkbox', 'name' => 'extraitcompteclient_generate_account_statut_add_multicurrency', 'label' => $langs->trans("ExtraitCompteClientGenerateAccountStatutAddMulticurrency"), 'value' => (!empty($conf->global->EXTRAITCOMPTECLIENT_DEFAULT_ADD_MULTICURRENCY) ? 'true' : 'false'));
            }
			$formquestionclone[] = array('type' => 'select', 'name' => 'extraitcompteclient_generate_account_statut_model', 'label' => $langs->trans("ExtraitCompteClientGenerateAccountStatutModel"), 'values' => $models, 'default' => 'account_statut');

            // Generate account statut confirmation
            if (($action == 'extraitcompteclient_generate_account_statut' && (empty($conf->use_javascript_ajax) || !empty($conf->dol_use_jmobile)))        // Output when action = extraitcompteclient_generate_account_statut if jmobile or no js
                || (!empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile))
            )   // Always output when not jmobile nor js
            {
                print $form->formconfirm($_SERVER["PHP_SELF"] . '?socid=' . $object->id, $langs->trans('ExtraitCompteClientGenerateAccountStatutTitle'), $langs->trans('ExtraitCompteClientGenerateAccountStatutConfirmLabel', $object->ref), 'confirm_extraitcompteclient_generate_account_statut', $formquestionclone, 'yes', 'action-extraitcompteclient_generate_account_statut', $popupHeight, 700);
            }

            if ($user->rights->extraitcompteclient->societe->generate_account_statut) {
                if (!empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile)) {
                    print '<div class="inline-block divButAction"><span id="action-extraitcompteclient_generate_account_statut" class="butAction">' . $langs->trans('ExtraitCompteClientGenerateAccountStatut') . '</span></div>' . "\n";
                } else {
                    print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?action=extraitcompteclient_generate_account_statut&amp;socid=' . $object->id . '">' . $langs->trans("ExtraitCompteClientGenerateAccountStatut") . '</a></div>';
                }
            }
        }

        if (!$error) {
            $this->results = array('myreturn' => $myvalue);
            $this->resprints = '';
            return 0; // or return 1 to replace standard code
        } else {
            $this->errors[] = 'Error ExtraitCompteClient module hook addMoreActionsButtons';
            return -1;
        }
    }

    /**
     * Overloading the formObjectOptions function : replacing the parent's function with the one below
     *
     * @param   array()         $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          &$action        Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    function formObjectOptions($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $db, $langs, $user;

        if (in_array('thirdpartycard', explode(':', $parameters['context']))) {
            if ($action == 'builddoc' && GETPOSTISSET('model') && (!empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile))) {
                // document generated from the documents models list. See formObjectOptions
                $model = GETPOST('model', 'alpha');
                $model_docs = array();

                $sql = "SELECT nom";
                $sql .= " FROM ".MAIN_DB_PREFIX."document_model";
                $sql .= " WHERE type = 'company'";
                $sql .= " AND entity = ".$conf->entity;
                $resql = $this->db->query($sql);
                if (!$resql) {
                    $this->errors[] = 'Error generate account statut PDF / CSV';
                    return -1;
                }
                $i = 0;
                $num_rows = $this->db->num_rows($resql);
                while ($i < $num_rows) {
                    $array = $this->db->fetch_array($resql);
                    array_push($model_docs, $array[0]);
                    $i++;
                }
                $models = [];
                if (in_array('account_statut', $model_docs)) {
                    $models[] = 'account_statut';
                }
                if (in_array('account_statut_csv', $model_docs)) {
                    $models[] = 'account_statut_csv';
                }
                if (!empty(isModEnabled('infraspackplus')) && in_array('InfraSPlus_account_statut', $model_docs)) {
                    $langs->load('infraspackplus@infraspackplus');
                    $models[] = 'InfraSPlus_account_statut';
                }
                if (in_array($model, $models)) {
                    $outJS = <<<SCRIPT
                        <script type="text/javascript">
                            window.onload = function() {
                                $("#action-extraitcompteclient_generate_account_statut").trigger("click"); // click btn generate account
                                $("#extraitcompteclient_generate_account_statut_model").select2("destroy");
                                $("#extraitcompteclient_generate_account_statut_model").val("$model"); // set document model selected in the models select
                                $("#extraitcompteclient_generate_account_statut_model").select2();
                            };
                    </script>
SCRIPT;
                    print $outJS;
                    return 0;
                }
            }
        }
        return 0;
    }
}