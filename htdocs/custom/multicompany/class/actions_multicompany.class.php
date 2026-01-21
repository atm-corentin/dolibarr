<?php
/* Copyright (C) 2009-2025	Regis Houssin	<regis.houssin@inodbox.com>
 * Copyright (C) 2011		Herve Prot		<herve.prot@symeos.com>
 * Copyright (C) 2014		Philippe Grand	<philippe.grand@atoo-net.com>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *	\file       /multicompany/class/actions_multicompany.class.php
 *	\ingroup    multicompany
 *	\brief      File Class multicompany
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
dol_include_once('/multicompany/class/dao_multicompany.class.php', 'DaoMulticompany');
dol_include_once('/multicompany/lib/multicompany.lib.php');

/**
 *	Class Actions of the module multicompany
 */
#[\AllowDynamicProperties]
class ActionsMulticompany
{
	/** @var DoliDB */
	public $db;
	/** @var DaoMulticompany */
	public $dao;

	public $mesg;
	public $error;
	public $errors = array();
	//! Numero de l'erreur
	public $errno = 0;

	public $template_dir;
	public $template;

	public $label;
	public $description;
	public $url;

	public $referent;

	public $sharings = array();
	public $options = array();
	public $entities = array();
	public $dict = array();
	public $tpl = array();

	public $addzero = array();
	public $sharingelements = array();
	public $sharingmodulename = array();
	public $sharingnamecompatibility = array();
	public $sharingobjects = array();
	public $customdicts = array();

	public $proposalnumber_referent;
	public $ordernumber_referent;
	public $invoicenumber_referent;
	public $projectnumber_referent;
	public $ticketnumber_referent;

	private $config = array();

	// For Hookmanager return
	public $resprints;
	public $results = array();


	/**
	 *	Constructor
	 *
	 *	@param	DoliDB	$db		Database handler
	 */
	public function __construct($db)
	{
		global $conf;

		$this->db = $db;

		$this->addzero = array(
			'user',
			'usergroup',
			'cronjob',
			'c_email_templates',
			'email_template',
			'default_values',
			'overwrite_trans'
		);

		// Default sharing elements
		$this->sharingelements = array(
			'thirdparty' => array(
				'type' => 'element',
				'icon' => 'building',
				'active' => true,	// for setEntity() function
				'disable' => true,	// disable in options tab
			),
			'member' => array(
				'type' => 'element',
				'icon' => 'address-card',
				'input' => array(
					'global' => array(
						'showhide' => true,
						'hide' => true,
						'del' => true
					)
				)
			),
			'member_type' => array(
				'type' => 'element',
				'icon' => 'address-card',
				'display' => '!empty($conf->global->MULTICOMPANY_MEMBER_SHARING_ENABLED)',
				'input' => array(
					'global' => array(
						'hide' => true,
						'del' => true
					),
					'member' => array(
						'showhide' => true,
						'del' => true
					)
				)
			),
			'product' => array(
				'type' => 'element',
				'icon' => 'cube',
				'tooltip' => 'ProductSharingInfo',
				'enable' => '(!empty($conf->product->enabled) || !empty($conf->service->enabled))',
				'input' => array(
					'global' => array(
						'showhide' => true,
						'hide' => true,
						'del' => true
					)
				),
				'active' => true
			),
			'productprice' => array(
				'type' => 'element',
				'icon' => 'money',
				'tooltip' => 'ProductPriceSharingInfo',
				'enable' => '(!empty($conf->product->enabled) || !empty($conf->service->enabled))',
				'display' => '!empty($conf->global->MULTICOMPANY_PRODUCT_SHARING_ENABLED)',
				'input' => array(
					'global' => array(
						'hide' => true,
						'del' => true
					),
					'product' => array(
						'showhide' => true,
						'del' => true
					)
				)
			),
			'productsupplierprice' => array(
				'type' => 'element',
				'icon' => 'money',
				'enable' => '((!empty($conf->product->enabled) || !empty($conf->service->enabled)) && !empty($conf->fournisseur->enabled))',
				'display' => '!empty($conf->global->MULTICOMPANY_PRODUCT_SHARING_ENABLED)',
				'input' => array(
					'global' => array(
						'hide' => true,
						'del' => true
					),
					'product' => array(
						'showhide' => true,
						'del' => true
					)
				),
			),
			'stock' => array(
				'type' => 'element',
				'icon' => 'cubes',
				//'tooltip' => 'SharingStockInfo',
				'enable' => '(!empty($conf->stock->enabled) && (!empty($conf->product->enabled) || !empty($conf->service->enabled)))',
				'display' => '!empty($conf->global->MULTICOMPANY_PRODUCT_SHARING_ENABLED)',
				'input' => array(
					'global' => array(
						'hide' => true,
						'del' => true
					),
					'product' => array(
						'showhide' => true,
						'del' => true
					)
				)
			),
			'category' => array(
				'type' => 'element',
				'icon' => 'paperclip',
				'input' => array(
					'global' => array(
						'showhide' => true,
						'hide' => true,
						'del' => true
					)
				)
			),
			'agenda' => array(
				'type' => 'element',
				'icon' => 'calendar',
				'input' => array(
					'global' => array(
						'showhide' => true,
						'hide' => true,
						'del' => true
					)
				)
			),
			'bankaccount' => array(
				'type' => 'element',
				'icon' => 'university',
				'input' => array(
					'global' => array(
						'showhide' => true,
						'hide' => true,
						'del' => true
					)
				)
			),
			'expensereport' => array(
				'type' => 'element',
				'icon' => 'edit',
				'input' => array(
					'global' => array(
						'showhide' => true,
						'hide' => true,
						'del' => true
					)
				)
			),
			'holiday' => array(
				'type' => 'element',
				'icon' => 'umbrella-beach',
				'input' => array(
					'global' => array(
						'showhide' => true,
						'hide' => true,
						'del' => true
					)
				)
			),
			'project' => array(
				'type' => 'element',
				'icon' => 'project-diagram',
				'input' => array(
					'global' => array(
						'showhide' => true,
						'hide' => true,
						'del' => true
					)
				)
			),
		    'ticket' => array(
		        'type' => 'element',
		        'icon' => 'ticket-alt',
		        'input' => array(
		            'global' => array(
		                'showhide' => true,
		                'hide' => true,
		                'del' => true
		            )
		        )
		    ),

			// Object

			'proposal' => array(
				'type' => 'object',
				'icon' => 'file-signature',
				'mandatory' => 'thirdparty',
				'enable' => '(!empty($conf->propal->enabled) && !empty($conf->societe->enabled))',
				'display' => '!empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_ENABLED)',
				'input' => array(
					'global' => array(
						'hide' => true,
						'del' => true
					),
					'thirdparty' => array(
						'showhide' => true,
						'hide' => true,
						'del' => true
					)
				),
				'active' => true
			),
			'proposalnumber' => array(
				'type' => 'objectnumber',
				'icon' => 'cogs',
				//'mandatory' => 'thirdparty',
				'tooltip' => 'ElementNumberSharingInfo',
				'enable' => '(!empty($conf->propal->enabled) && !empty($conf->societe->enabled))',
				//'display' => '!empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_ENABLED)',
				'constants' => array(
					'PROPALE_ADDON',
					'PROPALE_SAPHIR_MASK',
					'PROPALE_ADDON_PDF',
					'PROPALE_ADDON_PDF_ODT_PATH'
				),
				'input' => array(
					'global' => array(
						'hide' => true,
						'del' => true
					),
					/*'thirdparty' => array(
						'showhide' => true,
						'hide' => true,
						'del' => true
					)*/
				)
			),
			'order' => array(
				'type' => 'object',
				'icon' => 'file-invoice',		// Font Awesome icon
				'mandatory' => 'thirdparty',
				'enable' => '(!empty($conf->commande->enabled) && !empty($conf->societe->enabled))',
				'display' => '!empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_ENABLED)',
				'input' => array(
					'global' => array(
						'hide' => true,
						'del' => true
					),
					'thirdparty' => array(
						'showhide' => true,
						'hide' => true,
						'del' => true
					)
				),
				'active' => true,
				//'disable' => true			// Disable if not stable
			),
			'ordernumber' => array(
				'type' => 'objectnumber',
				'icon' => 'cogs',
				//'mandatory' => 'thirdparty',
				'tooltip' => 'ElementNumberSharingInfo',
				'enable' => '(!empty($conf->commande->enabled) && !empty($conf->societe->enabled))',
				//'display' => '!empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_ENABLED)',
				'constants' => array(
					'COMMANDE_ADDON',
					'COMMANDE_SAPHIR_MASK',
					'COMMANDE_ADDON_PDF',
					'COMMANDE_ADDON_PDF_ODT_PATH'
				),
				'input' => array(
					'global' => array(
						'hide' => true,
						'del' => true
					),
					/*'thirdparty' => array(
						'showhide' => true,
						'hide' => true,
						'del' => true
					)*/
				),
				//'disable' => true
			),
			'invoice' => array(
				'type' => 'object',
				'icon' => 'file-invoice-dollar',
				'mandatory' => 'thirdparty',
				'enable' => '(!empty($conf->facture->enabled) && !empty($conf->societe->enabled))',
				'display' => '!empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_ENABLED)',
				'input' => array(
					'global' => array(
						'hide' => true,
						'del' => true
					),
					'thirdparty' => array(
						'showhide' => true,
						'hide' => true,
						'del' => true
					)
				),
				'active' => true
			),
			'invoicenumber' => array(
				'type' => 'objectnumber',
				'icon' => 'cogs',
				//'mandatory' => 'thirdparty',
				'tooltip' => 'ElementNumberSharingInfo',
				'enable' => '(!empty($conf->facture->enabled) && !empty($conf->societe->enabled))',
				//'display' => '!empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_ENABLED)',
				'constants' => array(
					'FACTURE_ADDON',
					'FACTURE_MERCURE_MASK_INVOICE',
					'FACTURE_MERCURE_MASK_CREDIT',
					'FACTURE_MERCURE_MASK_DEPOSIT',
					'FACTURE_MERCURE_MASK_REPLACEMENT',
					'FACTURE_ADDON_PDF',
					'FACTURE_ADDON_PDF_ODT_PATH'
				),
				'input' => array(
					'global' => array(
						'hide' => true,
						'del' => true
					),
					/*'thirdparty' => array(
						'showhide' => true,
						'hide' => true,
						'del' => true
					)*/
				)
			),
			'contract' => array(
				'type' => 'object',
				'mandatory' => 'thirdparty',
				'icon' => 'suitcase',
				'enable' => '(!empty($conf->societe->enabled) && !empty($conf->societe->enabled))',
				'display' => '!empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_ENABLED)',
				'input' => array(
					'global' => array(
						'hide' => true,
						'del' => true
					),
					'thirdparty' => array(
						'showhide' => true,
						'del' => true
					)
				)
			),
			'supplier_proposal' => array(
				'type' => 'object',
				'icon' => 'file-signature',
				'mandatory' => 'thirdparty',
				'enable' => '(!empty($conf->supplierproposal->enabled) && !empty($conf->societe->enabled))',
				'display' => '!empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_ENABLED)',
				'input' => array(
					'global' => array(
						'hide' => true,
						'del' => true
					),
					'thirdparty' => array(
						'showhide' => true,
						'hide' => true,
						'del' => true
					)
				)
			),
			'supplier_order' => array(
				'type' => 'object',
				'icon' => 'file-invoice',
				'mandatory' => 'thirdparty',
				'enable' => '(!empty($this->supplier_order->enabled) && !empty($conf->societe->enabled))',
				'display' => '!empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_ENABLED)',
				'input' => array(
					'global' => array(
						'hide' => true,
						'del' => true
					),
					'thirdparty' => array(
						'showhide' => true,
						'hide' => true,
						'del' => true
					)
				)
			),
			'supplier_invoice' => array(
				'type' => 'object',
				'icon' => 'file-invoice-dollar',
				'mandatory' => 'thirdparty',
				'enable' => '(!empty($this->supplier_invoice->enabled) && !empty($conf->societe->enabled))',
				'display' => '!empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_ENABLED)',
				'input' => array(
					'global' => array(
						'hide' => true,
						'del' => true
					),
					'thirdparty' => array(
						'showhide' => true,
						'hide' => true,
						'del' => true
					)
				)
			),
			'projectnumber' => array(
				'type' => 'objectnumber',
				'icon' => 'cogs',
				//'mandatory' => 'project',
				'tooltip' => 'ElementNumberSharingInfo',
				'enable' => '!empty($conf->project->enabled)',
				//'display' => '!empty($conf->global->MULTICOMPANY_PROJECT_SHARING_ENABLED)',
				'constants' => array(
					'PROJECT_ADDON',
					'PROJECT_UNIVERSAL_MASK',
					'PROJECT_ADDON_PDF',
					'PROJECT_ADDON_PDF_ODT_PATH'
				),
				'input' => array(
					'global' => array(
						'hide' => true,
						'del' => true
					),
					/*'project' => array(
						'showhide' => true,
						'hide' => true,
						'del' => true
					)*/
				)
			),
			'ticketnumber' => array(
				'type' => 'objectnumber',
				'icon' => 'cogs',
				//'mandatory' => 'ticket',
				'tooltip' => 'ElementNumberSharingInfo',
				'enable' => '!empty($conf->ticket->enabled)',
				//'display' => '!empty($conf->global->MULTICOMPANY_TICKET_SHARING_ENABLED)',
				'input' => array(
					'global' => array(
						'hide' => true,
						'del' => true
					),
					/*'ticket' => array(
					 'showhide' => true,
					 'hide' => true,
					 'del' => true
					 )*/
				)
			),
			'intervention' => array(
				'type' => 'object',
				'icon' => 'ambulance',
				'mandatory' => 'thirdparty',
				'enable' => '!empty($conf->ficheinter->enabled)',
				'display' => '!empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_ENABLED)',
				'input' => array(
					'global' => array(
						'hide' => true,
						'del' => true
					),
					'thirdparty' => array(
						'showhide' => true,
						'hide' => true,
						'del' => true
					)
				)
			)
		);

		// Module name if different of object name (eg $conf->commande->enabled)
		$this->sharingmodulename = array(
			'thirdparty'			=> 'societe',
			'proposal'				=> 'propal',
			'proposalnumber'		=> 'propal',
			'order'					=> 'commande',
			'ordernumber'			=> 'commande',
			'invoice'				=> 'facture',
			'invoicenumber'			=> 'facture',
			'supplier_proposal'		=> 'supplierproposal',
			'supplier_order'		=> 'fournisseur',
			'supplier_invoice'		=> 'fournisseur',
			'product'				=> (empty($conf->product->enabled) && !empty($conf->service->enabled) ? 'service' : 'product'),
			'productprice'			=> 'product',
			'productsupplierprice'	=> 'product',
			'project'				=> 'projet',
			'projectnumber'			=> 'projet',
			'ticketnumber'			=> 'ticket',
			'member'				=> 'adherent',
			'membertype'			=> 'adherent',
			'member_type'			=> 'adherent',			// deprecated
			//'membertype'			=> 'member_type',		// deprecated
			'intervention'			=> 'ficheinter',
			'category'				=> 'categorie',
			'bankaccount'			=> 'banque',
			'bank_account'			=> 'banque',			// deprecated
			//'bankaccount'			=> 'banque_account',	// deprecated
		);

		// If getEntity value is different of sharing name
		$this->sharingnamecompatibility = array(
			'societe'					=> 'thirdparty',
			'socpeople'					=> 'thirdparty',
			'contact'					=> 'thirdparty',
			'adherent'					=> 'member',
			'bank_account'				=> 'bankaccount',
			'adherent_type'				=> 'member_type',
			'categorie'					=> 'category',
			'propal'					=> 'proposal',
			'commande'					=> 'order',
			'facture'					=> 'invoice',
			'contrat'					=> 'contract',
			'entrepot'					=> 'stock',
			'commande_fournisseur'		=> 'supplier_order',
			'facture_fourn'				=> 'supplier_invoice',
			'productfournisseurprice'	=> 'productsupplierprice',
			'product_fournisseur_price'	=> 'productsupplierprice',
			'ficheinter'				=> 'intervention',
			'fichinter'					=> 'intervention'
		);

		$this->customdicts = array(
			'c_tva' => array(
				'type' => 'dictionary',
				'icon' => 'money-check-alt',
				'transkey' => 'DictionaryVAT',
				'foreignkeys' => array()
			),
			'accounting_journal' => array(
				'type' => 'dictionary',
				'icon' => 'search-dollar',
				'transkey' => 'AccountingJournal',
				'foreignkeys' => array()
			),
			'c_accounting_category' => array(
				'type' => 'dictionary',
				'icon' => 'search-dollar',
				'transkey' => 'AccountingCategory',
				'foreignkeys' => array()
			),
			'c_socialnetworks' => array(
				'type' => 'dictionary',
				'icon' => 'project-diagram',
				'transkey' => 'SocialNetworks',
				'foreignkeys' => array()
			),
			'c_type_container' => array(
				'type' => 'dictionary',
				'icon' => 'globe-americas',
				'transkey' => 'TypeContainer',
				'disabled' => (empty($conf->website->enabled)),
				'foreignkeys' => array()
			),
			'c_ticket_type' => array(
				'type' => 'dictionary',
				'icon' => 'ticket-alt',
				'transkey' => 'TicketDictType',
				'foreignkeys' => array()
			),
			'c_ticket_severity' => array(
				'type' => 'dictionary',
				'icon' => 'ticket-alt',
				'transkey' => 'TicketDictSeverity',
				'foreignkeys' => array()
			),
			'c_ticket_category' => array(
				'type' => 'dictionary',
				'icon' => 'ticket-alt',
				'transkey' => 'TicketDictCategory',
				'foreignkeys' => array()
			),
			'c_ticket_resolution' => array(
				'type' => 'dictionary',
				'icon' => 'ticket-alt',
				'transkey' => 'TicketDictResolution',
				'disabled' => true,
				'foreignkeys' => array()
			),
			'c_holiday_types' => array(
				'type' => 'dictionary',
				'icon' => 'umbrella-beach',
				'transkey' => 'HolidayDictType',
				'foreignkeys' => array()
			),
			'c_hrm_public_holiday' => array(
				'type' => 'dictionary',
				'icon' => 'umbrella-beach',
				'transkey' => 'HolidayDictPublic',
				'disabled' => true,
				'foreignkeys' => array()
			),
			'c_paiement' => array(
				'type' => 'dictionary',
				'transkey' => 'Paiement',
				'disabled' => true,
				'foreignkeys' => array(
					'societe' => array(
						'mode_reglement',
						'mode_reglement_supplier'
					),
					'propal'				=> 'fk_mode_reglement',
					'commande'				=> 'fk_mode_reglement',
					'facture'				=> 'fk_mode_reglement',
					'facture_rec'			=> 'fk_mode_reglement',
					'commande_fournisseur'	=> 'fk_mode_reglement',
					'facture_fourn'			=> 'fk_mode_reglement',
					'supplier_proposal'		=> 'fk_mode_reglement',
					'chargesociales'		=> 'fk_mode_reglement',
					'don'					=> 'fk_payment',
					'paiement'				=> 'fk_paiement',
					'paiementfourn'			=> 'fk_paiement',
					'paiement_facture'		=> 'fk_paiement',
					'expensereport'			=> 'fk_c_paiement',
					'paiementcharge'		=> 'fk_typepaiement',
					'tva'					=> 'fk_typepayment',
					'payment_various'		=> 'fk_typepayment',
					'payment_salary'		=> 'fk_typepayment',
					'payment_expensereport'	=> 'fk_typepayment',
					'payment_donation'		=> 'fk_typepayment',
					'loan_schedule'			=> 'fk_typepayment',
					'payment_loan'			=> 'fk_typepayment'
				)
			),
			'c_payment_term' => array(
				'type' => 'dictionary',
				'transkey' => 'PaymentTerm',
				'disabled' => true,
				'foreignkeys' => array(
					'societe' => array(
						'cond_reglement',
						'cond_reglement_supplier'
					),
					'propal'				=> 'fk_cond_reglement',
					'commande'				=> 'fk_cond_reglement',
					'facture'				=> 'fk_cond_reglement',
					'facture_rec'			=> 'fk_cond_reglement',
					'commande_fournisseur'	=> 'fk_cond_reglement',
					'facture_fourn'			=> 'fk_cond_reglement',
					'supplier_proposal'		=> 'fk_cond_reglement'
				)
			)
		);

		if (!empty($conf->global->MULTICOMPANY_EXTERNAL_MODULES_SHARING)) {
			$externalmodules = json_decode($conf->global->MULTICOMPANY_EXTERNAL_MODULES_SHARING, true);
			if (is_array($externalmodules) && !empty($externalmodules)) {
				foreach($externalmodules as $params) {
					if (is_array($params) && !empty($params)) {
						if (isset($params['addzero']) && is_array($params['addzero']) && !empty($params['addzero'])) {
							array_push($this->addzero, $params['addzero']);
						}
						if (isset($params['sharingelements']) && is_array($params['sharingelements']) && !empty($params['sharingelements'])) {
							$this->sharingelements = array_merge($this->sharingelements, $params['sharingelements']);
						}
						if (isset($params['sharingmodulename']) && is_array($params['sharingmodulename']) && !empty($params['sharingmodulename'])) {
							$this->sharingmodulename = array_merge($this->sharingmodulename, $params['sharingmodulename']);
						}
						if (isset($params['dictionary']) && is_array($params['dictionary']) && !empty($params['dictionary'])) {
							$this->customdicts = array_merge($this->customdicts, $params['dictionary']);
						}
					}
				}
			}
		}
	}

	/**
	 * Instantiation of DAO class
	 *
	 * @return	void
	 */
	private function getInstanceDao()
	{
		if (!is_object($this->dao)){
			$this->dao = new DaoMulticompany($this->db);
		}
	}

	/**
	 * setHtmlTitle
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 */
	public function setHtmlTitle($parameters=false)
	{
		global $conf;

		if (empty($conf->global->MULTICOMPANY_HIDE_HTML_TITLE)) {
			$this->resprints = ' + multicompany';
		}

		return 0;
	}

	/**
	 *
	 * @param boolean $parameters
	 * @param unknown $object
	 * @param string $action
	 * @return number
	 */
	public function doUpgradeBefore($parameters=false, &$object, &$action='')
	{
		global $conf, $mc;

		if (empty($conf->multicompany->enabled)) {
			return 0;
		}

		if (is_array($parameters) && !empty($parameters)) {
			foreach($parameters as $key => $value) {
				$$key = $value;
			}
		}

		$currentcontext = explode(':', $parameters['context']);

		if ($action == 'upgrade') {
			if (version_compare($versionto, '15.0.9', '>')) {
				$this->results = array_keys($mc->getEntitiesList(true, false, false, true));
			}
		}

		return 0;
	}

	/**
	 *
	 * @param boolean $parameters
	 * @param unknown $object
	 * @param string $action
	 * @return number
	 */
	public function doUpgradeAfterDB($parameters=false, &$object, &$action='')
	{
	    global $conf, $langs, $user;
	    global $mc;

	    if (empty($conf->multicompany->enabled)) {
	    	return 0;
	    }

	    if (is_array($parameters) && !empty($parameters)) {
	        foreach($parameters as $key => $value) {
	            $$key = $value;
	        }
	    }

	    if (version_compare($versionto, '15.0.9', '>') && $conf->entity != 1) {
	    	return 0;
	    }

	    $langs->loadLangs(array('multicompany@multicompany'));

	    $currentcontext = explode(':', $parameters['context']);

	    if ($action == 'upgrade') {

	    	if (version_compare($versionto, '13.0.9', '>')) {

	    		$entities = $mc->getEntitiesList(true, false, false, true);

	    		/*
	    		 * Update missing user boxes
	    		 */
	    		print '<tr class="trforrunsql"><td colspan="4">';
	    		print '<b>'.$langs->trans('MulticompanyUpdateMissingBoxesPerEntity')."</b><br>\n";

	    		$err=0;
	    		$boxes = array('box_lastlogin.php', 'box_birthdays.php', 'box_dolibarr_state_board.php');

	    		foreach ($entities as $key => $label) {
	    			echo $label;
	    			foreach ($boxes as $file) {
	    				echo ' '.$file;
	    				// Search if boxes def already present
	    				$sql = "SELECT count(*) as nb FROM ".MAIN_DB_PREFIX."boxes_def";
	    				$sql.= " WHERE file = '".$this->db->escape($file)."'";
	    				$sql.= " AND entity = ".$key;

	    				$result = $this->db->query($sql);
	    				if ($result) {
	    					$obj = $this->db->fetch_object($result);
	    					if ($obj->nb == 0) {
	    						$this->db->begin();

	    						$sql = "INSERT INTO ".MAIN_DB_PREFIX."boxes_def (file, entity)";
	    						$sql .= " VALUES ('".$this->db->escape($file)."', ";
	    						$sql .= ((int) $key);
	    						$sql .= ")";

	    						$resql = $this->db->query($sql);
	    						if (!$resql) {
	    							$err++;
	    						}

	    						if (!$err) {
	    							$this->db->commit();
	    						} else {
	    							$this->error = $this->db->lasterror();
	    							$this->db->rollback();
	    						}
	    					} else {
	    						print ": ".$langs->trans("NothingToDo")."<br>\n";
	    					}
	    				} else {
	    					$this->error = $this->db->lasterror();
	    					return -1;
	    				}
	    			}
	    			print "<br>\n";
	    		}

	    		print '</td></tr>';

	    		/*
	    		 * Update llx_product_perentity
	    		 */
	    		print '<tr class="trforrunsql"><td colspan="4">';
	    		print '<b>'.$langs->trans('MulticompanyUpdateProductPMPPerEntity')."</b><br>\n";

	    		foreach ($entities as $key => $label) {
	    			echo $label.' ';

	    			$sql = "SELECT p.rowid, p.pmp";
	    			$sql.= " FROM ".MAIN_DB_PREFIX."product as p";
	    			$sql.= " WHERE p.entity = ".$key;
	    			$sql.= " AND NOT EXISTS (";
	    			$sql.= " SELECT pe.fk_product";
	    			$sql.= " FROM ".MAIN_DB_PREFIX."product_perentity as pe";
	    			$sql.= " WHERE pe.entity = ".$key;
	    			$sql.= " AND p.rowid = pe.fk_product)";

	    			$resql = $this->db->query($sql);
	    			if ($resql) {
	    				$num = $this->db->num_rows($resql);
	    				if ($num) {
	    					$i = 0;

	    					while ($i < $num) {
	    						$obj = $this->db->fetch_object($resql);

	    						$sql2 = "INSERT INTO ".MAIN_DB_PREFIX."product_perentity (";
	    						$sql2.= "entity";
	    						$sql2.= ", fk_product";
	    						$sql2.= ", pmp";
	    						$sql2.= ") VALUES (";
	    						$sql2.= $key;
	    						$sql2.= ", ".$obj->rowid;
	    						$sql2.= ", ".$obj->pmp;
	    						$sql2.= ")";

	    						$resql2 = $this->db->query($sql2);
	    						if (empty($resql2)) {
	    							$this->error = $this->db->lasterror;
	    							return -1;
	    						}

	    						print ". ";

	    						$i++;
	    					}
	    					print "<br>\n";
	    				} else {
	    					print ": ".$langs->trans("NothingToDo")."<br>\n";
	    				}

	    				$this->db->free($resql);

	    			} else {
	    				$this->error = $this->db->lasterror;
	    				return -1;
	    			}
	    		}

	    		print '</td></tr>';

	    		/*
	    		 * Update dictionaries by entities
	    		 */
	    		if (!empty($this->customdicts)) {
	    			foreach($this->customdicts as $dict => $params) {
	    				if (isset($params['disabled']) && !empty($params['disabled'])) {
	    					continue;
	    				}
	    				$transkey = 'MulticompanyUpdate'.(!empty($params['transkey']) ? $params['transkey'] : ucfirst($dict)).'PerEntity';
	    				print '<tr class="trforrunsql"><td colspan="4">';
	    				print '<b>'.$langs->trans($transkey)."</b>\n";

	    				if (isset($params['filepath']) && !empty($params['filepath'])) {
	    					$filepath = dol_buildpath($params['filepath']);
	    				} else {
	    					$filepath = getSqlInitFilePath($dict);
	    				}

	    				if (file_exists($filepath)) {
	    					$i=0;
	    					foreach ($entities as $key => $label) {
	    						echo ($i == 0 ? '<br>' : '').$label.' ';

	    						$sql = "SELECT rowid";
	    						$sql.= " FROM ".MAIN_DB_PREFIX.$dict;
	    						$sql.= " WHERE entity = ".$key;

	    						$resql = $this->db->query($sql);
	    						if ($resql) {
	    							$num = $this->db->num_rows($resql);
	    							if ($num) {
	    								print ": ".$langs->trans("NothingToDo")."<br>\n";
	    							} else {
	    								$result = run_sql($filepath, 1, $key);
	    								print ': <span class="ok">OK</span><br>'."\n";
	    							}
	    							$this->db->free($resql);
	    						} else {
	    							$this->error = $this->db->lasterror;
	    							return -1;
	    						}
	    						$i++;
	    					}
	    				} else {
	    					print ' <span class="error">KO</span><br>'."\n";
	    				}

	    				print '</td></tr>';
	    			}
	    		}
	    	}
	    }

	    return 1;
	}

	/**
	 *
	 * @param boolean $parameters
	 * @param unknown $object
	 * @param string $action
	 * @return number
	 */
	public function doUpgrade2($parameters=false, &$object, &$action='')
	{
		return $this->doUpgradeAfterDB($parameters, $object, $action);
	}

	/**
	 *
	 * @param boolean $parameters
	 * @param unknown $object
	 * @param string $action
	 */
	public function restrictedArea($parameters = false, &$object, &$action = '')
	{
		global $conf;

		if (empty($conf->multicompany->enabled) || empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED) || empty($conf->global->MULTICOMPANY_SHARING_BYELEMENT_ENABLED)) {
			return 0;
		}

		if (is_array($parameters) && !empty($parameters)) {
			foreach($parameters as $key=>$value) {
				$$key=$value;
			}
		}

		// Features/modules to check
		$featuresarray = array($features);
		if (preg_match('/&/', $features)) {
			$featuresarray = explode("&", $features);
		} elseif (preg_match('/\|/', $features)) {
			$featuresarray = explode("|", $features);
		}

		if (!empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_ENABLED)
			&& !empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_BYELEMENT_ENABLED)
			&& in_array('societe', $featuresarray) && !empty($objectid)) {
			$staticobject = new Societe($this->db);
			$ret = $staticobject->fetch($objectid);
			if ($ret > 0) {
				// check only if entity of element is different of current entity
				if ($staticobject->entity != $conf->entity) {
					$this->getSharingsByElement('thirdparty', $staticobject->id, true);	// Populate $this->config['sharedwith']
					if (!empty($this->config['sharedwith']['thirdparty'][$staticobject->id])
						&& in_array($conf->entity, $this->config['sharedwith']['thirdparty'][$staticobject->id])) {
							$this->results['result'] = 1;
						} else {
							$this->results['result'] = 0;
						}
				}
			}
		} elseif (!empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_ENABLED)
			&& !empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_BYELEMENT_ENABLED)
			&& !empty($conf->global->MULTICOMPANY_CONTACT_SHARING_BYELEMENT_ENABLED)
			&& in_array('contact', $featuresarray) && !empty($objectid)) {
			$staticobject = new Contact($this->db);
			$ret = $staticobject->fetch($objectid);
			if ($ret > 0) {
				// check only if entity of element is different of current entity
				if ($staticobject->entity != $conf->entity) {
					$this->getSharingsByElement($staticobject->element, $staticobject->id, true);	// Populate $this->config['sharedwith']
					if (!empty($this->config['sharedwith'][$staticobject->element][$staticobject->id])
						&& in_array($conf->entity, $this->config['sharedwith'][$staticobject->element][$staticobject->id])) {
							$this->results['result'] = 1;
						} else {
							$this->results['result'] = 0;
						}
				}
			}
		} elseif (!empty($conf->global->MULTICOMPANY_PRODUCT_SHARING_ENABLED)
			&& !empty($conf->global->MULTICOMPANY_PRODUCT_SHARING_BYELEMENT_ENABLED)
			&& (in_array('produit', $featuresarray) || in_array('service', $featuresarray)) && !empty($objectid)) {
			$staticobject = new Product($this->db);
			$ret = $staticobject->fetch($objectid);
			if ($ret > 0) {
				// check only if entity of element is different of current entity
				if ($staticobject->entity != $conf->entity) {
					$this->getSharingsByElement($staticobject->element, $staticobject->id, true);	// Populate $this->config['sharedwith']
					if (!empty($this->config['sharedwith'][$staticobject->element][$staticobject->id])
					&& in_array($conf->entity, $this->config['sharedwith'][$staticobject->element][$staticobject->id])) {
						$this->results['result'] = 1;
					} else {
						$this->results['result'] = 0;
					}
				}
			}
		}

		return 0;
	}

	/**
	 * 	Enter description here ...
	 *
	 * 	@param	string	$action		Action type
	 */
	public function doAdminActions(&$action = '')
	{
		global $conf, $user, $langs;

		$this->getInstanceDao();

		$id				= GETPOST('id','int');
		$label			= GETPOST('label','alphanohtml');
		$name			= GETPOST('name','alphanohtml');
		$description	= GETPOST('description','alpha');
		$url			= GETPOST('url', 'custom', 0, FILTER_SANITIZE_URL);
		$cancel			= GETPOST('cancel', 'alpha');
		$addandstay		= GETPOST('addandstay', 'alpha');
		$cancelandstay	= GETPOST('cancelandstay', 'alpha');
		$updateandstay	= GETPOST('updateandstay', 'alpha');
		$template		= (empty($conf->global->MULTICOMPANY_TEMPLATE_MANAGEMENT) ? null : GETPOST('template', 'int'));
		$usetemplate	= (empty($conf->global->MULTICOMPANY_TEMPLATE_MANAGEMENT) ? null : GETPOST('usetemplate', 'int'));
		$visible		= GETPOST('visible', 'int');
		$active			= GETPOST('active', 'int');

		if ($action === 'add' && empty($cancel) && !empty($user->admin) && empty($user->entity)) {
			$error=0;

			if (empty($label)) {
				$error++;
				setEventMessage($langs->trans("ErrorFieldRequired", $langs->transnoentities("Label")), 'errors');
				$action = 'create';
			} elseif (empty($name)) {
				$error++;
				setEventMessage($langs->trans("ErrorFieldRequired", $langs->transnoentities("EntityCompanyName")), 'errors');
				$action = 'create';
			}

			// Verify if label already exist in database
			if (empty($error)) {
				$this->dao->getEntities();
				if (!empty($this->dao->entities)) {
					foreach($this->dao->entities as $entity) {
						if (strtolower($entity->label) == strtolower($label)) $error++;
					}
					if (!empty($error)) {
						setEventMessage($langs->trans("ErrorEntityLabelAlreadyExist"), 'errors');
						$action = 'create';
					}
				}
			}

			if (empty($error)) {
				$this->db->begin();

				$this->dao->label		= $label;
				$this->dao->description	= $description;
				$this->dao->url			= $url;
				$this->dao->visible		= ((!empty($template) && empty($usetemplate)) ? 2 : ((!empty($visible) || !empty($conf->global->MULTICOMPANY_VISIBLE_BY_DEFAULT)) ? 1 : 0));
				$this->dao->active		= ((!empty($active) || !empty($conf->global->MULTICOMPANY_ACTIVE_BY_DEFAULT))?1:0);

				if (!empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED)) {
					foreach ($this->sharingelements as $element => $params) {
						if (($params['type'] === 'object' && !isset($params['disable']))
							&& (empty($conf->societe->enabled) || empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_ENABLED))) {
								continue;
						}
						$uppername = strtoupper($element);
						$constname = 'MULTICOMPANY_' . $uppername . '_SHARING_ENABLED';
						if (!empty($conf->global->$constname)) {
							$shareentities = GETPOST($element.'_to', 'array');
							$shareentities = array_unique($shareentities); sort($shareentities);
							$this->dao->options['sharings'][$element] = (!empty($shareentities) ? $shareentities : null);
							if (!empty($conf->global->MULTICOMPANY_TEMPLATE_MANAGEMENT)) {
								$this->dao->options['addtoallother'][$element] = GETPOST('addtoallother_'.$element, 'int');
							}
							if ($params['type'] === 'objectnumber') {
								$this->dao->options[$element.'_referent'] = (GETPOSTISSET($element.'_referring_entity') ? GETPOSTINT($element.'_referring_entity') : null);
							}
						}
					}
				}

				if (!empty($conf->accounting->enabled) && GETPOSTISSET('cloneaccountingaccount')) {
					$this->dao->options['cloneaccountingaccount'] = GETPOSTINT('cloneaccountingaccount');
				}

				$extrafields = new ExtraFields($this->db);
				$extralabels = $extrafields->fetch_name_optionals_label($this->dao->table_element, true);
				$extrafields->setOptionalsFromPost($extralabels, $this->dao);

				$id = $this->dao->create($user);
				if ($id <= 0) {
					$error++;
					$errors=($this->dao->error ? array($this->dao->error) : $this->dao->errors);
					setEventMessage($errors, 'errors');
					$action = 'create';
				}

				if (empty($error) && $id > 0) {
					$newDao = new DaoMulticompany($this->db);
					$newDao->id = $id;

					if (!empty($usetemplate) && is_numeric($usetemplate)) {
						require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
						$const = $this->dao->getEntityConfig($usetemplate);
						$modulesdir = dolGetModulesDirs();

						// Reload modules in the new entity
						$currEntity = $conf->entity;
						$conf->entity = $id;
						foreach ($modulesdir as $dir) {
							dol_syslog("Scan directory ".$dir." for module descriptor files (modXXX.class.php)");
							$handle = @opendir($dir);
							if (is_resource($handle)) {
								while (($file = readdir($handle)) !== false) {
									if (is_readable($dir.$file) && substr($file, 0, 3) == 'mod' && substr($file, dol_strlen($file) - 10) == '.class.php') {
										$modName = substr($file, 0, dol_strlen($file) - 10);

										if ($modName) {
											try {
												$res = include_once $dir.$file; // A class already exists in a different file will send a non catchable fatal error.
												if (class_exists($modName)) {
													try {
														$objMod = new $modName($this->db);
														$const_name = 'MAIN_MODULE_'.strtoupper(preg_replace('/^mod/i', '', get_class($objMod)));
														if(!isset($const[$const_name])) continue;

														$objMod->init('forceactivate');
													} catch (Exception $e) {
														dol_syslog("Failed to load ".$dir.$file." ".$e->getMessage(), LOG_ERR);
													}
												} else {
													print "actions_multicompany.php Warning bad descriptor file : ".$dir.$file." (Class ".$modName." not found into file)<br>";
												}
											} catch (Exception $e) {
												dol_syslog("Failed to load ".$dir.$file." ".$e->getMessage(), LOG_ERR);
											}
										}
									}
								}
								closedir($handle);
							} else {
								dol_syslog("actions_multicompany.php: Failed to open directory ".$dir.". See permission and open_basedir option.", LOG_WARNING);
							}
						}
						$conf->entity = $currEntity;

						// Copy const from the template entity
						if (!empty($const)) {
							// Overwrite with template
							foreach ($const as $key => $value) {
								dolibarr_set_const($this->db, $key, $value, 'chaine', 0, '', $id);
							}
						}

						$templateEntity = array_filter($this->dao->entities, function($el) use ($usetemplate) {
							return $el->id === $usetemplate;
						});
						if (!empty($templateEntity)) {
							// Copy dictionaries from template entity
							$dictionaries = reset($templateEntity)->getDictionaries();
							if (count($dictionaries)) {
								foreach ($dictionaries as $dictionary => $values) {
									if (!empty($values['rows'])) {
										$isInsertedDictionary = $newDao->addDictionary($dictionary, $values['columnsName'], $values['rows']);
										if ($isInsertedDictionary < 0) {
											$error++;
											$errors = ($this->dao->error ? array($this->dao->error) : $this->dao->errors);
											setEventMessage($errors, 'errors');
											$action = 'create';
										}
									}
								}
							}

							// Copy document models from the template entity
							$documentModels = reset($templateEntity)->getDocumentModels();
							if (count($documentModels)) {
								$isInsertedDocumentModels = $newDao->addDocumentModels($documentModels);
								if ($isInsertedDocumentModels < 0) {
									$error++;
									$errors = ($this->dao->error ? array($this->dao->error) : $this->dao->errors);
									setEventMessage($errors, 'errors');
									$action = 'create';
								}
							}
						}

						// Add entity to all others entities
						if (!empty($this->dao->options['addtoallother'])) {
							$this->dao->getEntities(true, array($id), false);
							foreach ($this->dao->entities as $entity) {
								if (!is_array($entity->options)) $entity->options = array();
								if (!is_array($entity->options['sharings'])) $entity->options['sharings'] = array();
								foreach($this->dao->options['sharings'] as $element => $values) {
									if (!is_array($entity->options['sharings'][$element])) {
										$entity->options['sharings'][$element] = array();
									}
									if ($this->dao->options['addtoallother'][$element] == '1') {
										array_push($entity->options['sharings'][$element], (string) $id);
										$entity->update($entity->id, $user);
									}
								}
							}
						}

						// Copy groups permissions if users are managed in the main entity
						$listofgroups = $this->dao->getListOfGroups();
						if (!empty($listofgroups)) {
							foreach($listofgroups as $groupid) {
								// Duplicate usergroup rights
								$ret = $this->duplicateUserGroupRights($groupid, $usetemplate, array($id));
								if ($ret < 0) {
									$error++;
								}
								// Add users to groups if linked with template
								$userslist = $this->dao->getListOfUsersInGroupByTemplate($groupid, $usetemplate);
								if ($userslist < 0) {
									$error++;
								} elseif (!empty($userslist)) {
									foreach($userslist as $usertemplate) {
										$result = $usertemplate->SetInGroup($groupid, $id);
										if ($result < 0) {
											$error++;
											break;
										}
									}
								}
							}
						}
					} else {
						// Load llx_const.sql
						$fullpath = getSqlInitFilePath('const');
						if (file_exists($fullpath)) {
							$result=run_sql($fullpath, 1, $id);
						}
						// Load llx_boxes_def.sql
						$fullpath = getSqlInitFilePath('boxes_def');
						if (file_exists($fullpath)) {
							$result=run_sql($fullpath, 1, $id);
						}
						// Load llx_*.sql declared in $this->customdicts
						foreach($this->customdicts as $dict => $params) {
							if (isset($params['disabled']) && !empty($params['disabled'])) {
								continue;
							}
							if (isset($params['filepath']) && !empty($params['filepath'])) {
								$filepath = dol_buildpath($params['filepath']);
							} else {
								$filepath = getSqlInitFilePath($dict);
							}
							if (file_exists($filepath)) {
								$result = run_sql($filepath, 1, $id);
							}
						}
					}

					$country_id		= GETPOST('country_id', 'int');
					$country		= getCountry($country_id, 'all');
					$country_code	= $country['code'];
					$country_label	= $country['label'];

					dolibarr_set_const($this->db, "MAIN_INFO_SOCIETE_COUNTRY", $country_id.':'.$country_code.':'.$country_label,'chaine',0,'',$id);
					dolibarr_set_const($this->db, "MAIN_INFO_SOCIETE_NOM",$name,'chaine',0,'',$id);
					dolibarr_set_const($this->db, "MAIN_INFO_SOCIETE_ADDRESS",GETPOST('address', 'alpha'),'chaine',0,'',$id);
					dolibarr_set_const($this->db, "MAIN_INFO_SOCIETE_TOWN",GETPOST('town', 'alpha'),'chaine',0,'',$id);
					dolibarr_set_const($this->db, "MAIN_INFO_SOCIETE_ZIP",GETPOST('zipcode', 'alpha'),'chaine',0,'',$id);
					dolibarr_set_const($this->db, "MAIN_INFO_SOCIETE_STATE",GETPOST('departement_id', 'int'),'chaine',0,'',$id);
					dolibarr_set_const($this->db, "MAIN_MONNAIE",GETPOST('currency_code', 'alpha'),'chaine',0,'',$id);
					dolibarr_set_const($this->db, "MAIN_LANG_DEFAULT",GETPOST('main_lang_default', 'alpha'),'chaine',0,'',$id);

                }

                if (empty($error)) {
                    $this->db->commit();
                } else {
                    $this->db->rollback();
                }

                if (!empty($addandstay)) {
                    header("Location: " . $_SERVER['PHP_SELF'].'?action=edit&id='.$id);
                    exit;
                }
            } else {
                $this->db->rollback();
            }
		} elseif ($action === 'edit' && !empty($user->admin) && empty($user->entity)) {
			$error=0;

			if (!empty($cancel)) {
				header("Location: " . $_SERVER['PHP_SELF']);
				exit;
			}

			if ($this->dao->fetch($id) < 0) {
				$error++;
				setEventMessage($langs->trans("ErrorEntityIsNotValid"), 'errors');
				$action = '';
			}
		} elseif ($action === 'update' && empty($cancel) && $id > 0 && !empty($user->admin) && empty($user->entity)) {
			$error=0;

			if (!empty($cancelandstay)) {
				$action = 'edit';
				return;
			}

			$ret = $this->dao->fetch($id);
			if ($ret < 0) {
				$error++;
				setEventMessage($langs->trans("ErrorEntityIsNotValid"), 'errors');
				$action = '';
			} elseif (empty($label)) {
				$error++;
				setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentities("Label")), 'errors');
				$action = 'edit';
			} elseif (empty($name)) {
				$error++;
				setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentities("EntityCompanyName")), 'errors');
				$action = 'edit';
			}

			// Verify if label already exist in database
			if (empty($error)) {
				$this->dao->getEntities();
				if (!empty($this->dao->entities)) {
					foreach($this->dao->entities as $entity) {
						if ($entity->id == $this->dao->id) continue;
						if (strtolower($entity->label) == strtolower($label)) $error++;
					}
					if ($error) {
						setEventMessage($langs->trans("ErrorEntityLabelAlreadyExist"), 'errors');
						$action = 'edit';
					}
				}
			}

			if (empty($error)) {
				$this->db->begin();

				$this->dao->label = $label;
				$this->dao->description	= $description;
				$this->dao->url	= $url;

				if (!empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED)) {
					foreach ($this->sharingelements as $element => $params) {
						if (($params['type'] === 'object' && !isset($params['disable']))
							&& (empty($conf->societe->enabled) || empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_ENABLED))) {
								continue;
						}
						$uppername = strtoupper($element);
						$constname = 'MULTICOMPANY_' . $uppername . '_SHARING_ENABLED';
						if (!empty($conf->global->$constname)) {
							$shareentities = GETPOST($element.'_to', 'array');
							$shareentities = array_unique($shareentities); sort($shareentities);
							$this->dao->options['sharings'][$element]	= (!empty($shareentities) ? $shareentities : null);
							if (!empty($conf->global->MULTICOMPANY_TEMPLATE_MANAGEMENT)) {
								$this->dao->options['addtoallother'][$element] = GETPOST('addtoallother_'.$element, 'int');
							}
							if ($params['type'] === 'objectnumber') {
								$this->dao->options[$element.'_referent'] = (GETPOSTISSET($element.'_referring_entity') ? GETPOSTINT($element.'_referring_entity') : null);
							}
						}
					}
				}

				if (!empty($conf->accounting->enabled) && GETPOSTISSET('cloneaccountingaccount')) {
					$this->dao->options['cloneaccountingaccount'] = GETPOSTINT('cloneaccountingaccount');
				}

				$extrafields = new ExtraFields($this->db);
				$extralabels = $extrafields->fetch_name_optionals_label($this->dao->table_element, true);
				$extrafields->setOptionalsFromPost($extralabels, $this->dao);

				$ret = $this->dao->update($this->dao->id, $user);
				if ($ret <= 0) {
					$error++;
					$errors=($this->dao->error ? array($this->dao->error) : $this->dao->errors);
					setEventMessage($errors, 'errors');
					$action = 'edit';
				}

				if (empty($error) && $ret > 0) {
					$country_id		= GETPOST('country_id', 'int');
					$country		= getCountry($country_id, 'all');
					$country_code	= $country['code'];
					$country_label	= $country['label'];

					dolibarr_set_const($this->db, "MAIN_INFO_SOCIETE_COUNTRY", $country_id.':'.$country_code.':'.$country_label,'chaine',0,'',$this->dao->id);
					dolibarr_set_const($this->db, "MAIN_INFO_SOCIETE_NOM",$name,'chaine',0,'',$this->dao->id);
					dolibarr_set_const($this->db, "MAIN_INFO_SOCIETE_ADDRESS",GETPOST('address', 'alpha'),'chaine',0,'',$this->dao->id);
					dolibarr_set_const($this->db, "MAIN_INFO_SOCIETE_TOWN",GETPOST('town', 'alpha'),'chaine',0,'',$this->dao->id);
					dolibarr_set_const($this->db, "MAIN_INFO_SOCIETE_ZIP",GETPOST('zipcode', 'alpha'),'chaine',0,'',$this->dao->id);
					dolibarr_set_const($this->db, "MAIN_INFO_SOCIETE_STATE",GETPOST('departement_id', 'int'),'chaine',0,'',$this->dao->id);
					dolibarr_set_const($this->db, "MAIN_MONNAIE",GETPOST('currency_code', 'alpha'),'chaine',0,'',$this->dao->id);
					dolibarr_set_const($this->db, "MAIN_LANG_DEFAULT",GETPOST('main_lang_default', 'alpha'),'chaine',0,'',$this->dao->id);

					$this->db->commit();

					if (!empty($updateandstay)) {
						$action = 'edit';
					}
				} else {
					$this->db->rollback();
				}
			}
		}
	}

	/**
	 * 	Return action of hook
	 * 	@param		object			Linked object
	 */
	public function doActions($parameters = false, &$object, &$action = '')
	{
		global $conf, $langs, $user;
		global $mc;

		if (empty($conf->multicompany->enabled)) {
			return 0;
		}

		$langs->loadLangs(array('multicompany@multicompany'));

		if (is_array($parameters) && !empty($parameters)) {
			foreach($parameters as $key => $value) {
				$$key = $value;
			}
		}

		$error = 0;

		$currentcontext = explode(':', $parameters['context']);

		// Clear constants cache after company infos update
		if (is_array($currentcontext)) {
			if ((in_array('admincompany', $currentcontext) || in_array('adminihm', $currentcontext)) && ($action == 'update' || $action == 'updateedit')) {
				clearCache('mc_entity_' . $conf->entity);
				clearCache('mc_constants_' . $conf->entity);
			} else if ((in_array('groupcard', $currentcontext) || in_array('groupperms', $currentcontext)) && $object->element == 'usergroup') {
				global $entity;

				// Users/Groups management only in master entity if transverse mode
				if (!empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE) && ($conf->entity > 1 || ($conf->entity == 1 && !empty($user->entity)))) {
					accessforbidden($langs->trans('ErrorOnlySuperadminCanManageUserAdnGroup'), 1, 1, 1);
				}

				if (!empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE)) {
					$entity = (GETPOST('entity','int') ? GETPOST('entity','int') : $conf->entity);
				} else {
					$entity = (!empty($object->entity) ? $object->entity : $conf->entity);
				}

				// Add/Remove user into group
				if (!empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE)
					&& in_array('groupcard', $currentcontext)
					&& ($action == 'adduser' || $action =='removeuser')
					&& $caneditperms
				) {
					if (empty($userid) || $userid < 0) {
						setEventMessages($langs->trans("ErrorUserOrEntitiesNotSelected"), null, 'errors');
						return 1;
					}

					if ($action == 'adduser') {
						$entities = GETPOST("entities", "array", 3);

						if (is_array($entities) && !empty($entities)) {
							$error = 0;

							foreach ($entities as $entity_id) {
								$object->fetch($id);
								$object->oldcopy = clone $object;

								$edituser = new User($this->db);
								$edituser->fetch($userid);
								$result = $edituser->SetInGroup($object->id, $entity_id);
								if ($result < 0) {
									$error++;
									break;
								}
							}
							if (empty($error)) {
								header("Location: " . $_SERVER['PHP_SELF'] . '?id=' . $object->id);
								exit;
							} else {
								$this->error = $edituser->error;
								$this->errors = $edituser->errors;
								return -1;
							}
						} else {
							setEventMessages($langs->trans("ErrorGroupOrEntitiesNotSelected"), null, 'errors');
						}
					} elseif ($action == 'removeuser') {
						$object->fetch($id);
						$object->oldcopy = clone $object;

						$edituser = new User($this->db);
						$edituser->fetch($userid);
						$result = $edituser->RemoveFromGroup($object->id, $entity);

						if ($result > 0) {
							header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $object->id);
							exit;
						} else	{
							$this->error = $object->error;
							$this->errors = $object->errors;
							return -1;
						}
					}
					return 1;
				}
			} elseif ((in_array('usercard', $currentcontext) || in_array('userperms', $currentcontext)) && $object->element == 'user')	{
				global $entity, $caneditperms;

				// With transverse mode, only superadmin can create Users/Groups in master entity
				if (!empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE) && ($conf->entity > 1 || ($conf->entity == 1 && !empty($user->entity)))) {
					if (in_array('usercard', $currentcontext) && ($action == 'create' || $action == 'adduserldap')) {
						accessforbidden($langs->trans('ErrorOnlySuperadminCanManageUserAdnGroup'), 1, 1, 1);
					} elseif (in_array('userperms', $currentcontext)) {
						$caneditperms = false;
					}
				}

				if (!empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE)) {
					if (GETPOSTISSET('entity')) {

						$entity = GETPOST('entity','int');

					} else {

						$entity = $conf->entity;

						// Check usergroup if user not in master entity
						if (in_array('userperms', $currentcontext) && !empty($user->admin) && empty($user->entity) && $conf->entity == 1) {
							require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
							$group = new UserGroup($this->db);
							$ret = $group->listGroupsForUser($object->id, false);
							if (!empty(current($ret)->usergroup_entity)) {
								sort(current($ret)->usergroup_entity);
								if (current($ret)->usergroup_entity[0] > 1) {
									$entity = current($ret)->usergroup_entity[0];
								}
							}
						}
					}
				} else {
					$entity = (!empty($object->entity) ? $object->entity : $conf->entity);
				}

				// Action add usergroup
				if (!empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE)
					&& in_array('usercard', $currentcontext)
					&& ($action == 'addgroup' || $action == 'removegroup')
					&&  $caneditgroup
				) {
					if (empty($group) || $group < 0) {
						setEventMessages($langs->trans("ErrorGroupOrEntitiesNotSelected"), null, 'errors');
						return 1;
					}

					if ($action == 'addgroup') {

						$entities = GETPOST("entities", "array", 3);

						if (is_array($entities) && !empty($entities)) {
							$error = 0;

							foreach ($entities as $entity_id) {
								$object->fetch($id);
								$result = $object->SetInGroup($group, $entity_id);
								if ($result < 0) {
									$error++;
									break;
								}
							}
							if ($error)	{
								$this->error = $object->error;
								$this->errors = $object->errors;
								return -1;
							} else {
								header("Location: " . $_SERVER['PHP_SELF'] . '?id=' . $object->id);
								exit;
							}
						} else {
							setEventMessages($langs->trans("ErrorGroupOrEntitiesNotSelected"), null, 'errors');
						}
					} else if ($action == 'removegroup') {
						$object->fetch($id);
						$result = $object->RemoveFromGroup($group, $entity);
						if ($result > 0) {
							header("Location: " . $_SERVER['PHP_SELF'] . '?id=' . $object->id);
							exit;
						} else {
							$this->error = $object->error;
							$this->errors = $object->errors;
							return -1;
						}
					}
					return 1;
				}
			} elseif (in_array('productcard', $currentcontext) && ($object->element == 'product' || $object->element == 'service')) {
				if ($action != 'create' && $action != 'add') {
					if (!empty($object->entity) && $object->entity != $conf->entity) {
						global $usercanread, $usercancreate, $usercandelete;

						/*if (empty($user->rights->multicompany->product->read)) {
							$usercanread = false;
						}*/
						if (empty($user->rights->multicompany->product->write)) {
							$usercancreate = false;
						}
						if (empty($user->rights->multicompany->product->delete)) {
							$usercandelete = false;
						}
					}
				}
			} elseif (!empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_ENABLED)
				&& !empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_BYELEMENT_ENABLED)
				&& in_array('thirdpartylist', $currentcontext)
				&& $this->checkIfElementIsSharedByEntity('thirdparty') === true
				&& $action == 'confirmsharebyelement'
				&& !empty(GETPOST('toselect', 'array', 2))
				&& GETPOST('confirm', 'aZ09') == 'yes'
			) {

				$objEntity = new DaoMulticompany($this->db);

				if (empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARE_ALL_BY_DEFAULT)) {
					$entities = GETPOST('thirdparty_to', 'array', 2); // limit to POST
				} else {
					$entities = GETPOST('from', 'array', 2); // limit to POST
				}

				$elementlist = GETPOST('toselect', 'array', 2); // limit to POST
				$error = 0;

				foreach ($elementlist as $elementid) {
					$ret = $object->fetch($elementid);
					if ($ret > 0) {
						$contactlist = $object->contact_array();
						if (!empty($contactlist)) {
							foreach($contactlist as $key => $name) {
								$ret = $objEntity->setSharingsByElement('contact', $key, $entities);
								if ($ret < 0) {
									$this->error = $ret->error;
									$error++;
									break;
								}
							}
						}
						if (!$error) {
							$retupdate = $objEntity->setSharingsByElement('thirdparty', $object->id, $entities);
							if ($retupdate < 0) {
								$this->error = $retupdate->error;
								$error++;
								break;
							}
						}
					}
				}

				if (!$error) {
					setEventMessage('MassActionThirdpartySharingByElementIsOk');
					return 1;
				} else {
					return -1;
				}

			} elseif (!empty($conf->global->MULTICOMPANY_PRODUCT_SHARING_ENABLED)
				&& !empty($conf->global->MULTICOMPANY_PRODUCT_SHARING_BYELEMENT_ENABLED)
				&& in_array('productservicelist', $currentcontext)
				&& $this->checkIfElementIsSharedByEntity('product') === true
				&& $action == 'confirmsharebyelement'
				&& !empty(GETPOST('toselect', 'array'))
				&& GETPOST('confirm', 'aZ09') == 'yes'
			) {

				$objEntity = new DaoMulticompany($this->db);

				if (empty($conf->global->MULTICOMPANY_PRODUCT_SHARE_ALL_BY_DEFAULT)) {
					$entities = GETPOST('product_to', 'array', 2); // limit to POST
				} else {
					$entities = GETPOST('from', 'array', 2); // limit to POST
				}

				$elementlist = GETPOST('toselect', 'array', 2); // limit to POST

				foreach ($elementlist as $elementid) {
					$ret = $object->fetch($elementid);
					if ($ret > 0) {
						$retupdate = $objEntity->setSharingsByElement('product', $object->id, $entities);
						if ($retupdate < 0) {
							$this->error = $retupdate->error;
							$error++;
							break;
						}
					}
				}

				if (!$error) {
					setEventMessage('MassActionProductSharingByElementIsOk');
					return 1;
				} else {
					return -1;
				}

			} elseif (!empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED) && in_array('accountancyadminaccount', $currentcontext) && $object->element == 'accounting_account' && !empty($permissiontoadd)) {
				$this->getInfo($conf->entity);
				if  (isset($this->options['cloneaccountingaccount']) && $this->options['cloneaccountingaccount'] == 1 && GETPOST('valid_change_chart', 'alpha') && $chartofaccounts > 0) {
					// Get language code for this $chartofaccounts
					$sql = 'SELECT c.code, a.pcg_version FROM '.MAIN_DB_PREFIX.'c_country as c, '.MAIN_DB_PREFIX.'accounting_system as a';
					$sql .= ' WHERE c.rowid = a.fk_country AND a.rowid = '.(int) $chartofaccounts;
					$resql = $this->db->query($sql);
					if ($resql) {
						$obj = $this->db->fetch_object($resql);
						$country_code = $obj->code;
						$pcg_version = $obj->pcg_version;
					} else {
						dol_print_error($this->db);
					}

					// Try to load sql file
					if (!empty($country_code)) {
						$sqlfile = DOL_DOCUMENT_ROOT.'/install/mysql/data/llx_accounting_account_'.strtolower($country_code).'.sql';

						$offsetforchartofaccount = 0;
						$offsetforchartofaccounttoclone = 0;
						// Get the comment line '-- ADD CCCNNNNN to rowid...' to find CCCNNNNN (CCC is country num, NNNNN is id of accounting account)
						// and pass CCCNNNNN + (num of company * 100 000 000) as offset to the run_sql as a new parameter to say to update sql on the fly to add offset to rowid and account_parent value.
						// This is to be sure there is no conflict for each chart of account, whatever is country, whatever is company when multicompany is used.
						$tmp = file_get_contents($sqlfile);
						$reg = array();
						if (preg_match('/-- ADD (\d+) to rowid/ims', $tmp, $reg)) {
							$offsetforchartofaccount += $reg[1];
							$offsetforchartofaccounttoclone += $reg[1];
						}
						$offsetforchartofaccount += ($conf->entity * 100000000);
						$offsetforchartofaccounttoclone += (GETPOST('entitytoclone', 'int') * 100000000);

						$sqldel = "DELETE FROM ".MAIN_DB_PREFIX."accounting_account WHERE entity = ".$conf->entity." AND fk_pcg_version = '".$pcg_version."'";
						$resqldel = $this->db->query($sqldel);

						$sql = "SELECT * FROM ".MAIN_DB_PREFIX."accounting_account WHERE entity = ".GETPOST('entitytoclone', 'int')." AND fk_pcg_version = '".$pcg_version."'";
						$resql = $this->db->query($sql);
						if (!empty($resql)) {
							$i = 0;
							$num_rows = $this->db->num_rows($resql);
							while ($i < $num_rows) {
								$array = $this->db->fetch_array($resql);
								foreach($array as $key => $value){
									unset($array['tms']);
									if (is_int($key) || empty($value)) {
										unset($array[$key]);
									}
								}
								$array['entity'] = $conf->entity;
								$array['rowid'] -= $offsetforchartofaccounttoclone;
								$array['rowid'] += $offsetforchartofaccount;
								if (isset($array['account_parent']) && $array['account_parent'] > 0) {
									$array['account_parent'] -= $offsetforchartofaccounttoclone;
									$array['account_parent'] += $offsetforchartofaccount;
								}

								$sqladd = "INSERT INTO ".MAIN_DB_PREFIX."accounting_account (";
								$sqladd.= implode(',', array_keys($array));
								$sqladd.= ") VALUES (";
								foreach($array as $key => $value) {
									if (is_string($value)) {
										$sqladd.= "'".$this->db->escape($value)."'";
									} else {
										$sqladd.= (int) $value;
									}
									if (end(array_keys($array)) != $key) {
										$sqladd.= ",";
									}
								}
								$sqladd.= ")";

								//echo $sqladd.'<br>';

								if (!$this->db->query($sqladd)) {
									$error++;
									$this->error.= $this->db->lasterror();
									dol_syslog(get_class($this)."::Add accounting_account erreur -1 ".$this->error, LOG_ERR);
								}

								$i++;
							}
						}

						if (!$error) {
							setEventMessages($langs->trans("ChartLoaded"), null, 'mesgs');
						} else {
							setEventMessages($langs->trans("ErrorDuringChartLoad"), null, 'warnings');
						}
					}

					if (!dolibarr_set_const($this->db, 'CHARTOFACCOUNTS', $chartofaccounts, 'chaine', 0, '', $conf->entity)) {
						$error++;
					}

					return 1;
				}

			} else {
				if (!empty($object->entity) && $object->entity != $conf->entity && in_array('globalcard', $currentcontext)) {
					foreach ($this->sharingelements as $element => $params) {
						if (array_key_exists('type', $params) && $params['type'] === 'objectnumber' && empty($params['disable']) && $object->element == $this->sharingmodulename[$element]) {
							if (is_array($params['constants']) && !empty($params['constants'])) {
								$this->getInstanceDao();
								// Check if current entity not use the objectnumber sharing
								$constnumber = 'MULTICOMPANY_'.strtoupper($element).'_SHARING_ENABLED';
								if (empty($conf->global->$constnumber) || empty($mc->sharings[$element])) {
									foreach ($params['constants'] as $constname) {
										$res = $this->dao->getEntityConfig($object->entity, $constname);
										if (!empty($res[$constname])) {
											$conf->global->$constname = $res[$constname]; // override current entity config with object entity config
										}
									}
								} else {
									$varname = $element.'_referent';
									$referent = (!empty($mc->$varname) ? $mc->$varname : 1);
									foreach ($params['constants'] as $constname) {
										$res = $this->dao->getEntityConfig($referent, $constname);
										if (!empty($res[$constname])) {
											$conf->global->$constname = $res[$constname]; // override current entity config with referent config
										}
									}
								}
							}
						} elseif (array_key_exists('type', $params) && $params['type'] === 'object' && empty($params['disable'])
							&& ((isset($this->sharingmodulename[$element]) && $object->element == $this->sharingmodulename[$element]) || $object->element == $element)) {
							$templatepath = dol_buildpath('/multicompany/core/tpl/override_'.$element.'_userrights.tpl.php', 0, 1);
							if (!empty($templatepath)) {
								include $templatepath;
							}
						}
					}
				}
			}
		}

		//var_dump($_POST);
/*
		if (empty($_SESSION['dol_tables_list_fk_soc']))
		{
			$_SESSION['dol_tables_list_fk_soc'] = getTablesWithField('fk_soc', array());
		}
		var_dump($_SESSION['dol_tables_list_fk_soc']);
*/
		//$include=false;
		//$exclude=false;
/*
		$exclude = array(
			MAIN_DB_PREFIX . 'user',
			MAIN_DB_PREFIX . 'user_employment',
			MAIN_DB_PREFIX . 'user_param',
			MAIN_DB_PREFIX . 'user_rib',
			MAIN_DB_PREFIX . 'user_rights',
			MAIN_DB_PREFIX . 'usergroup',
			MAIN_DB_PREFIX . 'usergroup_rights',
			MAIN_DB_PREFIX . 'usergroup_user',
			MAIN_DB_PREFIX . 'rights_def',
		);
*/
		//$exclude = '/(const|user|rights\_def)+/';
		//$include = '/(const|user|rights\_def)+/';

		//if (empty($_SESSION['dol_tables_list_entity']))
/*		{
			$_SESSION['dol_tables_list_entity'] = getTablesWithField('entity', $exclude, $include);
		}

		var_dump($_SESSION['dol_tables_list_entity']);
*/
/*
		if (!empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED) && !empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_ENABLED) && !empty($mc->sharings['thirdparty']))
		{
			if (in_array($contextpage, $this->thirdpartycontextlist) || in_array($contextpage, $this->contactcontextlist))
			{
				if (GETPOST('confirmmassaction') && GETPOST('massaction') == 'modify_entity')
				{
					var_dump($_POST['toselect']);
				}
			}
		}
*/
		return 0;
	}

	/**
	 *
	 * @param boolean $parameters
	 * @param unknown $object
	 * @param string $action
	 * @return number
	 */
	public function commonGenerateDocument($parameters=false, &$object, &$action='')
	{
		global $conf, $mysoc;

		if (empty($conf->multicompany->enabled)) {
			return 0;
		}
		if (!is_object($object)) {
			return 0;
		}

		// Override $mysoc with data of object entity
		if (!empty($object->entity) && $object->entity != $conf->entity) {
			foreach ($this->sharingelements as $element => $params) {
				if ($params['type'] === 'object' && empty($params['disable']) && ($object->element == $this->sharingmodulename[$element] || $object->element == $element )) {
					$this->getInstanceDao();

					$current = $this->dao->getEntityConfig($conf->entity, 'MAIN_INFO_*');
					if (!empty($current)) {
						foreach($current as $constname => $value) {
							unset($conf->global->$constname);
						}
					}

					$other = $this->dao->getEntityConfig($object->entity, 'MAIN_INFO_*');
					if (!empty($other)) {
						foreach($other as $constname => $value) {
							$conf->global->$constname = $value; // override current entity config with referent config
						}
					}

					$mysoc->setMysoc($conf);

					break;
				}
			}
		}

		return 0;
	}

	/**
	 *
	 * @param boolean $parameters
	 * @param unknown $object
	 * @param string $action
	 * @return number
	 */
	public function showLinkedObjectBlock($parameters=false, &$object, &$action='')
	{
		global $conf, $user, $langs;
		global $mc;

		if (empty($conf->multicompany->enabled)) {
			return 0;
		}

		if (is_array($parameters) && !empty($parameters)) {
			foreach($parameters as $key=>$value) {
				$$key=$value;
			}
		}

		$currentcontext = explode(':', $parameters['context']);

		//var_dump($object->linkedObjects);

		foreach($object->linkedObjects as $objecttype => $objects) {
			foreach($objects as $key => $tmpobj) {
				if (empty($tmpobj->entity)) {
					continue; // for debug
				}

				if ($tmpobj->entity != $conf->entity) {
					$element = $objecttype;
					if ($objecttype == 'propal') {
						$element = 'proposal';
					} elseif ($objecttype == 'commande') {
						$element = 'order';
					} elseif ($objecttype == 'facture') {
						$element = 'invoice';
					}

					//var_dump($element);var_dump($mc->sharings[$element]);
					//var_dump($object->linkedObjects[$objecttype][$key]);

					if (!empty($mc->sharings[$element]) && in_array($tmpobj->entity, $mc->sharings[$element])) {
						//nothing
					} else {
						unset($object->linkedObjects[$objecttype][$key]);
					}
				}
			}
		}

		return 0;
	}

	/**
	 *
	 * @param boolean $parameters
	 * @param unknown $object
	 * @param string $action
	 * @return number
	 */
	public function showLinkToObjectBlock($parameters=false, &$object, &$action='')
	{
		global $conf, $user;

		if (empty($conf->multicompany->enabled)) {
			return 0;
		}

		if (is_array($parameters) && !empty($parameters)) {
			foreach($parameters as $key=>$value) {
				$$key=$value;
			}
		}

		$currentcontext = explode(':', $parameters['context']);

		if (!empty($object->entity) && $object->entity != $conf->entity) {
			if (empty($possiblelinks)) { // old method
				$propalperms = 1;
				$orderperms = 1;
				$invoiceperms = 1;

				if (in_array('propalcard', $currentcontext) && $object->element == 'propal') {
					$propalperms = !empty($user->rights->multicompany->propal->write);
				} elseif (in_array('ordercard', $currentcontext) && $object->element == 'commande') {
					$orderperms = !empty($user->rights->multicompany->order->write);
				} elseif (in_array('invoicecard', $currentcontext) && $object->element == 'facture') {
					$invoiceperms = !empty($user->rights->multicompany->invoice->write);
				}

				$this->results = array(
					'propal' => array(
						'enabled' => $conf->propal->enabled,
						'perms' => $propalperms,
						'label' => 'LinkToProposal',
						'sql' => "SELECT s.rowid as socid, s.nom as name, s.client, t.rowid, t.ref, t.ref_client, t.total_ht FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."propal as t WHERE t.fk_soc = s.rowid AND t.fk_soc IN (".$listofidcompanytoscan.') AND t.entity IN ('.getEntity('propal').')'),
					'order' => array(
						'enabled' => $conf->commande->enabled,
						'perms' => $orderperms,
						'label' => 'LinkToOrder',
						'sql' => "SELECT s.rowid as socid, s.nom as name, s.client, t.rowid, t.ref, t.ref_client, t.total_ht FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."commande as t WHERE t.fk_soc = s.rowid AND t.fk_soc IN (".$listofidcompanytoscan.') AND t.entity IN ('.getEntity('commande').')'),
					'invoice' => array(
						'enabled' => $conf->facture->enabled,
						'perms' => $invoiceperms,
						'label' => 'LinkToInvoice',
						'sql' => "SELECT s.rowid as socid, s.nom as name, s.client, t.rowid, t.ref, t.ref_client, t.total_ht FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."facture as t WHERE t.fk_soc = s.rowid AND t.fk_soc IN (".$listofidcompanytoscan.') AND t.entity IN ('.getEntity('invoice').')')
				);

				return 0;

			} else {

				if (!empty($conf->global->MULTICOMPANY_PROPOSAL_SHARING_ENABLED) && in_array('propalcard', $currentcontext) && $object->element == 'propal') {
					$possiblelinks['propal']['perms'] = !empty($user->rights->multicompany->propal->write);
				} elseif (!empty($conf->global->MULTICOMPANY_ORDER_SHARING_ENABLED) && in_array('ordercard', $currentcontext) && $object->element == 'commande') {
					$possiblelinks['order']['perms'] = !empty($user->rights->multicompany->order->write);
				} elseif (!empty($conf->global->MULTICOMPANY_INVOICE_SHARING_ENABLED) && in_array('invoicecard', $currentcontext) && $object->element == 'facture') {
					$possiblelinks['invoice']['perms'] = !empty($user->rights->multicompany->invoice->write);
				}

				$this->results = $possiblelinks;

				return 1;
			}
		}

		return 0;
	}

	/**
	 *
	 * @param boolean $parameters
	 * @param unknown $object
	 * @param string $action
	 * @return number
	 */
	public function addMoreActionsButtons($parameters=false, &$object, &$action='')
	{
		global $conf, $user, $langs;

		if (empty($conf->multicompany->enabled)) {
			return 0;
		}

		if (is_array($parameters) && !empty($parameters)) {
			foreach($parameters as $key=>$value) {
				$$key=$value;
			}
		}

		$currentcontext = explode(':', $parameters['context']);

		/*if (in_array('productcard', $currentcontext) && ($object->element == 'product' || $object->element == 'service'))
		{
			if ($object->entity != $conf->entity)
			{
				$user->rights->produit->creer = 0;
				$user->rights->produit->supprimer = 0;
				$user->rights->service->creer = 0;
				$user->rights->service->supprimer = 0;

				//return 1;
			}
		}*/

		return 0;
	}

	/**
	 *
	 * @param boolean $parameters
	 * @param unknown $object
	 * @param string $action
	 * @return number
	 */
	public function printUserPasswordField($parameters=false, &$object, &$action='')
	{
		global $conf, $langs;

		if (!isModEnabled('multicompany')) {
			return 0;
		}
		if (!checkMulticompanyAutentication() || checkMulticompanyAutentication() > 1) {
			return 0;
		}

		$langs->load('multicompany@multicompany');

		if (is_array($parameters) && !empty($parameters)) {
			foreach($parameters as $key => $value) {
				$$key = $value;
			}
		}

		$currentcontext = explode(':', $parameters['context']);

		$this->resprints = "\n".'<!-- BEGIN multicompany printUserPasswordField -->'."\n";

		if (in_array('usercard', $currentcontext) && $object->element == 'user') {
			if ($action == 'create') {
				$this->resprints.= '<input class="minwidth300 maxwidth400 widthcentpercentminusx" maxlength="128" type="text" id="password" name="password" value="'.dol_escape_htmltag($password).'" autocomplete="new-password">';
				if (!empty($conf->use_javascript_ajax)) {
					$this->resprints.= img_picto((getDolGlobalString('USER_PASSWORD_GENERATED') === 'none' ? $langs->transnoentities('NoPasswordGenerationRuleConfigured') : $langs->transnoentities('Generate')), 'refresh', 'id="generate_password" class="paddingleft'.(getDolGlobalString('USER_PASSWORD_GENERATED') === 'none' ? ' opacitymedium' : ' linkobject').'"');
				}
			} elseif ($action == 'edit') {
				if (!empty($caneditpasswordandsee)) {
					$this->resprints.= '<input maxlength="128" type="password" class="flat" id="password" name="password" value="'.dol_escape_htmltag($object->pass).'" autocomplete="new-password">';
					if (!empty($conf->use_javascript_ajax)) {
						$this->resprints.= img_picto((getDolGlobalString('USER_PASSWORD_GENERATED') === 'none' ? $langs->transnoentities('NoPasswordGenerationRuleConfigured') : $langs->transnoentities('Generate')), 'refresh', 'id="generate_password" class="paddingleft'.(getDolGlobalString('USER_PASSWORD_GENERATED') === 'none' ? ' opacitymedium' : ' linkobject').'"');
					}
				} else {
					$this->resprints.= preg_replace('/./i','*', $object->pass);
				}
			}
		}

		$this->resprints.= '<!-- END multicompany printUserPasswordField -->'."\n";

		return 0;
	}

	/**
	 *
	 * @param boolean $parameters
	 * @param unknown $object
	 * @param string $action
	 * @return number
	 */
	public function formConfirm($parameters = false, &$object, &$action = '')
	{
		global $conf, $langs;
		global $mc, $form;

		if (empty($conf->multicompany->enabled)) {
			return 0;
		}

		$langs->load('multicompany@multicompany');

		if (is_array($parameters) && !empty($parameters)) {
			foreach($parameters as $key => $value) {
				$$key = $value;
			}
		}

		$return = 0;
		$currentcontext = explode(':', $parameters['context']);

		$this->resprints = "\n".'<!-- BEGIN multicompany formConfirm -->'."\n";

		if (in_array('propalcard', $currentcontext) && $object->element == 'propal') {
			if ($action == 'clone' && !empty($mc->entities['proposal'])) {
				// Create an array for form
				$filter = '(s.client:IN:1,2,3)';
				$formquestion = array(
					array('type' => 'other', 'name' => 'socid', 'label' => $langs->trans("SelectThirdParty"), 'value' => $form->select_company($object->socid, 'socid', $filter, '', 0, 0, null, 0, 'maxwidth300')),
					array('type' => 'other', 'name' => 'entity', 'label' => $langs->trans("SelectEntity"), 'value' => $mc->select_entities($object->entity, 'entity', '', false, false, false, explode(',', $mc->entities['proposal']))),
					array('type' => 'checkbox', 'name' => 'update_prices', 'label' => $langs->trans('PuttingPricesUpToDate'), 'value' => 0),
					array('type' => 'checkbox', 'name' => 'update_desc', 'label' => $langs->trans('PuttingDescUpToDate'), 'value' => 0),
				);
				if (getDolGlobalInt('PROPAL_CLONE_DATE_DELIVERY') && !empty($object->delivery_date)) {
					$formquestion[] = array('type' => 'date', 'name' => 'date_delivery', 'label' => $langs->trans("DeliveryDate"), 'value' => $object->delivery_date);
				}
				$this->resprints = "\n".'<!-- BEGIN multicompany formConfirm -->'."\n"; // to avoid conflict with hook of selectThirdpartyListWhere
				// Incomplete payment. We ask if reason = discount or other
				$this->resprints.= $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmClonePropal', $object->ref), 'confirm_clone', $formquestion, 'yes', 1, 0, 650);
				$return++;
			}
			// Dolibarr v22
		} elseif (in_array('ordercard', $currentcontext) && $object->element == 'commande') {
			if ($action == 'clone' && !empty($mc->entities['order'])) {
				$filter = '(s.client:IN:1,2,3)';
				// Create an array for form
				$formquestion = array(
					array('type' => 'other', 'name' => 'socid', 'label' => $langs->trans("SelectThirdParty"), 'value' => $form->select_company($object->socid, 'socid', $filter, '', 0, 0, null, 0, 'maxwidth300')),
					array('type' => 'other', 'name' => 'entity', 'label' => $langs->trans("SelectEntity"), 'value' => $mc->select_entities($object->entity, 'entity', '', false, false, false, explode(',', $mc->entities['proposal'])))
				);
				$this->resprints.= $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneOrder', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
				$return++;
			}
		} elseif (in_array('invoicecard', $currentcontext) && $object->element == 'facture') {
			if ($action == 'clone' && !empty($mc->entities['invoice'])) {
				// Create an array for form
				$filter = '(s.client:IN:1,2,3)';
				$formquestion = array(
					array('type' => 'other', 'name' => 'socid', 'label' => $langs->trans("SelectThirdParty"), 'value' => $form->select_company($object->socid, 'socid', $filter, 1)),
					array('type' => 'other', 'name' => 'entity', 'label' => $langs->trans("SelectEntity"), 'value' => $mc->select_entities($object->entity, 'entity', '', false, false, false, explode(',', $mc->entities['invoice']))),
					array('type' => 'date', 'name' => 'newdate', 'label' => $langs->trans("Date"), 'value' => dol_now())
				);
				$this->resprints = "\n".'<!-- BEGIN multicompany formConfirm -->'."\n"; // to avoid conflict with hook of selectThirdpartyListWhere
				// Incomplete payment. We ask if reason = discount or other
				$this->resprints.= $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneInvoice', $object->ref), 'confirm_clone', $formquestion, 'yes', 1, 250);
				$return++;
			}
		}

		$this->resprints.= '<!-- END multicompany formConfirm -->'."\n";

		return $return;
	}

	/**
	 *
	 * @param boolean $parameters
	 * @param unknown $object
	 * @param string $action
	 * @return number
	 */
	public function formObjectOptions($parameters=false, &$object, &$action='')
	{
		global $conf, $user, $langs;
		global $form;

		if (empty($conf->multicompany->enabled)) {
			return 0;
		}

		$langs->load('multicompany@multicompany');

		if (is_array($parameters) && !empty($parameters)) {
			foreach($parameters as $key=>$value) {
				$$key=$value;
			}
		}

		$currentcontext = explode(':', $parameters['context']);

		$this->resprints = "\n".'<!-- BEGIN multicompany formObjectOptions -->'."\n";

		if (in_array('usercard', $currentcontext) && $object->element == 'user') {
			if ($action == 'edit') {
				// TODO check if user not linked with the current entity before change entity (thirdparty, invoice, etc.) !!
				if (empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE) && $conf->entity == 1 && !empty($user->admin) && empty($user->entity)) {
					$this->resprints.= '<tr><td>'.$langs->trans("Entity").'</td>';
					$this->resprints.= '<td>'.$this->select_entities($object->entity);
					$this->resprints.= "</td></tr>\n";
				} else {
					$this->resprints.= '<input type="hidden" name="entity" value="'.$conf->entity.'" />';
				}
			} elseif ($action == 'create') {
				if (empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE) && $conf->entity == 1 && !empty($user->admin) && empty($user->entity)) {
					$this->resprints.= '<tr><td>'.$langs->trans("Entity").'</td>';
					$this->resprints.= '<td>'.$this->select_entities($conf->entity);
					$this->resprints.= "</td></tr>\n";
				} else {
					$this->resprints.= '<input type="hidden" name="entity" value="'.$conf->entity.'" />';
				}
			} elseif ($action != 'adduserldap') {
				if (empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE) && $conf->entity == 1 && !empty($user->admin) && empty($user->entity)) {
					$this->resprints.= '<tr><td>'.$langs->trans("Entity").'</td><td>';
					if (empty($object->entity)) {
						$this->resprints.= $langs->trans("AllEntities");
					} else {
						$staticentity = new self($this->db);
						$staticentity->getInfo($object->entity);
						$this->resprints.= $staticentity->label;
					}
					$this->resprints.= "</td></tr>\n";
				}
			}
		} elseif (!empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED)) {
			if (!empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_ENABLED)
				&& in_array('thirdpartycard', $currentcontext) && $object->element == 'societe'
				&& !empty($user->admin) && empty($user->entity)) {
					if (empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_BYELEMENT_ENABLED)) {
						if ($action == 'create') {
							$this->resprints.= '<tr><td>'.fieldLabel('Entity','entity').'</td><td colspan="3" class="maxwidthonsmartphone">';
							$s = $this->select_entities($conf->entity);
							$this->resprints.= $form->textwithpicto($s,$langs->trans("ThirdpartyEntityDesc"),1);
							$this->resprints.= '</td></tr>'."\n";
						}
					} elseif (!empty($conf->global->MULTICOMPANY_SHARING_BYELEMENT_ENABLED) && $this->checkIfElementIsSharedByEntity('thirdparty') === true) {
						if ($action == 'create' || $action == 'edit') {
							if ($action == 'edit') {
								// We can define the granularity of the sharing of the element only in the origin entity of this element
								if (!empty($object->entity) && $object->entity != $conf->entity) {
									return 0;
								}
							}
							$this->resprints.= '<tr><td>'.fieldLabel('MulticompanySharingElementByEntity','entity').'</td><td colspan="3" class="maxwidthonsmartphone">'."\n";
							$this->resprints.= $this->formSelectSharingByElement($object, 'thirdparty');		// "thirdparty" is used instead element name "societe" (for compatibility)
							$this->resprints.= '</td></tr>'."\n";
						} else {
							// We can define the granularity of the sharing of the element only in the origin entity of this element
							if (!empty($object->entity) && $object->entity != $conf->entity) {
								return 0;
							}
							$this->getSharingsByElement('thirdparty', $object->id);	// Populate $this->config['sharedwith']
							if (!empty($this->config['sharedwith']['thirdparty'][$object->id])) {
								$this->resprints.= '<tr><td>'.$langs->trans("MulticompanySharedWithThisEntities").'</td><td>';
								foreach ($this->config['sharedwith']['thirdparty'][$object->id] as $sharedwithentity) {
									if ($sharedwithentity == $object->entity) {
										continue;
									}
									$otherentity = new self($this->db);
									$otherentity->getInfo($sharedwithentity);
									$this->resprints.= '<div class="refidno multicompany-entity-card-container">';
									$this->resprints.= '<span class="fa fa-globe"></span><span class="multiselect-selected-title-text">'.$otherentity->label.'</span>';
									$this->resprints.= "</div>\n";
								}
								$this->resprints.= "</td></tr>\n";
							}
						}
				}
			} elseif (!empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_ENABLED)
				&& !empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_BYELEMENT_ENABLED)
				&& in_array('contactcard', $currentcontext) && $object->element == 'contact'
				&& !empty($user->admin) && empty($user->entity)) {
					if (empty($conf->global->MULTICOMPANY_CONTACT_SHARING_BYELEMENT_ENABLED)) {
						if ($action == 'create' || $action == 'edit') {	// Action create is managed by the trigger
							if ($action == 'edit') {
								// We can define the granularity of the sharing of the element only in the origin entity of this element
								if (!empty($object->entity) && $object->entity != $conf->entity) {
									return 0;
								}
								$this->getSharingsByElement($object->element, $object->id);	// Populate $this->config['sharedwith']
								if (!empty($this->config['sharedwith'][$object->element][$object->id])) {
									$this->resprints.= '<tr><td colspan="4">';
									$this->resprints.= '<input type="hidden" name="oldsharedwith" value="' . base64_encode(json_encode($this->config['sharedwith'][$object->element][$object->id])) . '">';
									$this->resprints.= '</td></tr>'."\n";
								}
							}
						} else {
							$this->getSharingsByElement($object->element, $object->id);	// Populate $this->config['sharedwith']
							if (!empty($this->config['sharedwith'][$object->element][$object->id])) {
								$this->resprints.= '<tr><td>'.$langs->trans("MulticompanySharedWithThisEntities").'</td><td>';
								foreach ($this->config['sharedwith'][$object->element][$object->id] as $sharedwithentity) {
									if ($sharedwithentity == $object->entity) {
										continue;
									}
									$otherentity = new self($this->db);
									$otherentity->getInfo($sharedwithentity);
									$this->resprints.= '<div class="refidno multicompany-entity-card-container">';
									$this->resprints.= '<span class="fa fa-globe"></span><span class="multiselect-selected-title-text">'.$otherentity->label.'</span>';
									$this->resprints.= "</div>\n";
								}
								$this->resprints.= "</td></tr>\n";
							}
						}
					} elseif (!empty($conf->global->MULTICOMPANY_SHARING_BYELEMENT_ENABLED) && $this->checkIfElementIsSharedByEntity('thirdparty') === true) {
						if ($action == 'create' || $action == 'edit') {
							if ($action == 'edit') {
								// We can define the granularity of the sharing of the element only in the origin entity of this element
								if (!empty($object->entity) && $object->entity != $conf->entity) {
									return 0;
								}
							}
							$socid = null;
							$parentgranularity = null;
							if ($action == 'create' && is_object($objsoc) && !empty($objsoc->id)) {
								$socid = $objsoc->id;
							} elseif ($action == 'edit' && is_object($object) && !empty($object->socid)) {
								$socid = $object->socid;
							}
							if (!empty($socid)) {
								// Check if parents element have sharings
								$parentgranularity = $this->dao->getListOfSharingsByElement('thirdparty', $socid);
							}

							$visible = ((((isset($objsoc) && is_object($objsoc) && !empty($objsoc->id)) || !empty($object->socid)) && !empty($parentgranularity)) ? true : false);
							$this->resprints.= '<tr id="formselectsharingbyelement"'.($visible ? '' : ' style="display:none;"').'>';
							$this->resprints.= '<td>'.fieldLabel('MulticompanySharingElementByEntity','entity').'</td><td colspan="3" class="maxwidthonsmartphone">'."\n";
							$this->resprints.= $this->formSelectSharingByElement($object, null, 'thirdparty', $socid);
							$this->resprints.= '</td></tr>'."\n";
						} else {
							// We can define the granularity of the sharing of the element only in the origin entity of this element
							if (!empty($object->entity) && $object->entity != $conf->entity) {
								return 0;
							}
							$this->getSharingsByElement($object->element, $object->id);	// Populate $this->config['sharedwith']
							if (!empty($this->config['sharedwith'][$object->element][$object->id])) {
								$this->resprints.= '<tr><td>'.$langs->trans("MulticompanySharedWithThisEntities").'</td><td>';
								foreach ($this->config['sharedwith'][$object->element][$object->id] as $sharedwithentity) {
									if ($sharedwithentity == $object->entity) {
										continue;
									}
									$otherentity = new self($this->db);
									$otherentity->getInfo($sharedwithentity);
									$this->resprints.= '<div class="refidno multicompany-entity-card-container">';
									$this->resprints.= '<span class="fa fa-globe"></span><span class="multiselect-selected-title-text">'.$otherentity->label.'</span>';
									$this->resprints.= "</div>\n";
								}
								$this->resprints.= "</td></tr>\n";
							}
						}
					}
			} elseif (!empty($conf->global->MULTICOMPANY_PRODUCT_SHARING_ENABLED)
				&& in_array('productcard', $currentcontext) && $object->element == 'product'
				&& !empty($user->admin) && empty($user->entity)) {
					if (empty($conf->global->MULTICOMPANY_PRODUCT_SHARING_BYELEMENT_ENABLED)) {
						if ($action == 'create') {
							$this->resprints.= '<tr><td>'.fieldLabel('Entity','entity').'</td><td colspan="3" class="maxwidthonsmartphone">';
							$s = $this->select_entities($conf->entity);
							$this->resprints.= $form->textwithpicto($s, $langs->trans("ProductEntityDesc"), 1);
							$this->resprints.= '</td></tr>'."\n";
						}
					} elseif (!empty($conf->global->MULTICOMPANY_SHARING_BYELEMENT_ENABLED) && $this->checkIfElementIsSharedByEntity($object->element) === true) {
						if ($action == 'create' || $action == 'edit') {
							if ($action == 'edit') {
								// We can define the granularity of the sharing of the element only in the origin entity of this element
								if (!empty($object->entity) && $object->entity != $conf->entity) {
									return 0;
								}
							}
							$this->resprints.= '<tr><td>'.fieldLabel('MulticompanySharingElementByEntity','entity').'</td><td colspan="3" class="maxwidthonsmartphone">'."\n";
							$this->resprints.= $this->formSelectSharingByElement($object);
							$this->resprints.= '</td></tr>'."\n";
						} else {
							// We can define the granularity of the sharing of the element only in the origin entity of this element
							if (!empty($object->entity) && $object->entity != $conf->entity) {
								return 0;
							}
							$this->getSharingsByElement($object->element, $object->id);	// Populate $this->config['sharedwith']
							if (!empty($this->config['sharedwith'][$object->element][$object->id])) {
								$this->resprints.= '<tr><td>'.$langs->trans("MulticompanySharedWithThisEntities").'</td><td>';
								foreach ($this->config['sharedwith'][$object->element][$object->id] as $sharedwithentity) {
									if ($sharedwithentity == $object->entity) {
										continue;
									}
									$otherentity = new self($this->db);
									$otherentity->getInfo($sharedwithentity);
									$this->resprints.= '<div class="refidno multicompany-entity-card-container">';
									$this->resprints.= '<span class="fa fa-globe"></span><span class="multiselect-selected-title-text">'.$otherentity->label.'</span>';
									$this->resprints.= "</div>\n";
								}
								$this->resprints.= "</td></tr>\n";
							}
						}
					}
			} elseif (!empty($conf->global->MULTICOMPANY_CATEGORY_SHARING_ENABLED)
				&& in_array('categorycard', $currentcontext) && $object->element == 'category'
				&& !empty($user->admin) && empty($user->entity)) {
					if (empty($conf->global->MULTICOMPANY_CATEGORY_SHARING_BYELEMENT_ENABLED)) {
						if ($action == 'create') {
							$this->resprints.= '<tr><td>'.fieldLabel('Entity','entity').'</td><td colspan="3" class="maxwidthonsmartphone">';
							$s = $this->select_entities($conf->entity);
							$this->resprints.= $form->textwithpicto($s, $langs->trans("CategoryEntityDesc"), 1);
							$this->resprints.= '</td></tr>'."\n";
						}
					} elseif (!empty($conf->global->MULTICOMPANY_SHARING_BYELEMENT_ENABLED) && $this->checkIfElementIsSharedByEntity($object->element) === true) {
						$editpage = strpos($_SERVER['SCRIPT_NAME'], 'edit.php'); // TODO add $action
						if ($action == 'create' || ($editpage !== false || $action == 'edit')) {
							if ($editpage !== false || $action == 'edit') {
								// We can define the granularity of the sharing of the element only in the origin entity of this element
								if (!empty($object->entity) && $object->entity != $conf->entity) {
									return 0;
								}
							}
							$this->resprints.= '<tr><td>'.fieldLabel('MulticompanySharingElementByEntity','entity').'</td><td colspan="3" class="maxwidthonsmartphone">'."\n";
							$this->resprints.= $this->formSelectSharingByElement($object);
							$this->resprints.= '</td></tr>'."\n";
						} else {
							// We can define the granularity of the sharing of the element only in the origin entity of this element
							if (!empty($object->entity) && $object->entity != $conf->entity) {
								return 0;
							}
							$this->getSharingsByElement($object->element, $object->id);	// Populate $this->config['sharedwith']
							if (!empty($this->config['sharedwith'][$object->element][$object->id])) {
								$this->resprints.= '<tr><td>'.$langs->trans("MulticompanySharedWithThisEntities").'</td><td>';
								foreach ($this->config['sharedwith'][$object->element][$object->id] as $sharedwithentity) {
									if ($sharedwithentity == $object->entity) {
										continue;
									}
									$otherentity = new self($this->db);
									$otherentity->getInfo($sharedwithentity);
									$this->resprints.= '<div class="refidno multicompany-entity-card-container">';
									$this->resprints.= '<span class="fa fa-globe"></span><span class="multiselect-selected-title-text">'.$otherentity->label.'</span>';
									$this->resprints.= "</div>\n";
								}
								$this->resprints.= "</td></tr>\n";
							}
						}
					}
			} elseif (!empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_ENABLED)
				&& ((!empty($conf->global->MULTICOMPANY_PROPOSAL_SHARING_ENABLED) && in_array('propalcard', $currentcontext) && $object->element == 'propal')
					|| (!empty($conf->global->MULTICOMPANY_ORDER_SHARING_ENABLED) && in_array('ordercard', $currentcontext) && $object->element == 'commande')
					|| (!empty($conf->global->MULTICOMPANY_INVOICE_SHARING_ENABLED) && in_array('invoicecard', $currentcontext) && $object->element == 'facture')
					/*|| (!empty($conf->global->MULTICOMPANY_INTERVENTION_SHARING_ENABLED) && in_array('interventioncard', $currentcontext) && $object->element == 'fichinter')*/)) {
						if ($action == 'create') {
							$this->resprints.= '<tr><td>'.$langs->trans("Entity").'</td>';
							$this->resprints.= '<td>'.$this->select_entities($conf->entity);
							$this->resprints.= "</td></tr>\n";
						}
			} elseif (in_array('accountancyadminaccount', $currentcontext) && $object->element == 'accounting_account' && !empty($permissiontoadd)) {
				$this->getInfo($conf->entity);
				if ($this->options['cloneaccountingaccount'] == 1) {
					$this->resprints.= $langs->trans("SelectEntityToCloneAccounts");
					$this->resprints.= $this->select_entities('', 'entitytoclone', '', false, array($conf->entity), true, '', '', 'minwidth200imp');
					$this->resprints.= '
					<script type="text/javascript">
						$(document).ready(function() {
							$("#chartofaccounts").prop("disabled", true);
							$("#change_chart").prop("disabled", true).val("'.$langs->trans("Duplicate").'");
							$( "#searchFormList" ).on("change", "#entitytoclone", function() {
								if ($(this).val() > 0) {
									$.post( "'.dol_buildpath('/multicompany/core/ajax/functions.php',1).'", {
										"action": "getChartOfAccountsOfEntity",
										"id": $(this).val(),
	                                    "token": "'.currentToken().'"
										},
										function (result) {
											if (result.status == "success") {
												$("#chartofaccounts").val(result.chartofaccounts).change();
												$("#change_chart").prop("disabled", false)
											}
										}
									);
								} else {
									$("#chartofaccounts").val(-1).change();
									$("#change_chart").prop("disabled", true);
								}
							});
							$("#change_chart").on("click", function (e) {
								console.log("chartofaccounts seleted = "+$("#chartofaccounts").val());
								// reload page
								window.location.href = "'.$_SERVER["PHP_SELF"].'?valid_change_chart=1&chartofaccounts="+$("#chartofaccounts").val()+"&entitytoclone="+$("#entitytoclone").val();
				    		});
						});
					</script>
					';
				}
			}
		}

		//$this->resprints.= '<br>';
		$this->resprints.= '<!-- END multicompany formObjectOptions -->'."\n";

		return 0;
	}

	/**
	 *
	 * @param boolean $parameters
	 * @param unknown $object
	 * @param string $action
	 * @return number
	 */
	public function formCreateThirdpartyOptions($parameters=false, &$object, &$action='')
	{
		global $conf, $user, $langs;
		global $form;

		if (empty($conf->multicompany->enabled)) {
			return 0;
		}

		$langs->load('multicompany@multicompany');

		if (is_array($parameters) && !empty($parameters)) {
			foreach($parameters as $key=>$value) {
				$$key=$value;
			}
		}

		//echo 'OK';

		//$this->resprints = 'OK';

		return 0;
	}

	/**
	 *
	 * @param boolean $parameters
	 * @param unknown $object
	 * @param string $action
	 * @return number
	 */
	public function formAddUserToGroup($parameters=false, &$object, &$action='')
	{
		global $conf, $user, $langs;
		global $form, $mc, $exclude;

		if (empty($conf->multicompany->enabled)) {
			return 0;
		}

		$langs->load('multicompany@multicompany');

		if (is_array($parameters) && !empty($parameters)) {
			foreach($parameters as $key=>$value) {
				$$key=$value;
			}
		}

		$currentcontext = explode(':', $parameters['context']);

		$this->resprints = '';

		if (is_array($currentcontext)) {
			if (in_array('usercard', $currentcontext) && $object->element == 'user') {
				if ($action != 'edit' && $action != 'presend' && !empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE)) {
					$this->resprints = "\n".'<!-- BEGIN multicompany formAddUserToGroup -->'."\n";

					if (!empty($groupslist)) {
						$excludev = array();
					}

					if ($caneditgroup) {
						$this->resprints.= '<form action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'" method="POST">'."\n";
						$this->resprints.= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'" />';
						$this->resprints.= '<input type="hidden" name="action" value="addgroup" />';
					}
					$this->resprints.= '<table class="noborder centpercent">'."\n";

					$this->resprints.= '<tr class="liste_titre"><th class="liste_titre">'.$langs->trans("Groups").'</th>'."\n";
					if (!empty($user->admin)) {
						$this->resprints.= '<th class="liste_titre">'.$langs->trans("Entity").'</th>';
					}
					$this->resprints.= '<th class="liste_titre" align="right">';
					if ($caneditgroup && empty($user->entity)) {
						// Users/Groups management only in master entity if transverse mode
						if ($conf->entity == 1) {
							$selected = ($action == 'addgroup' && GETPOSTINT('group') ? GETPOSTINT('group') : '');
							$this->resprints.= $form->select_dolgroups($selected, 'group', $langs->transnoentities("SelectGroup"), $exclude, 0, '', '', $object->entity);
							$this->resprints.= ' &nbsp; ';
							if ($conf->entity == 1) {
								$entities = $this->getEntitiesList();
								$this->resprints.= $form->multiselectarray('entities', $entities, GETPOST('entities', 'array'), 0, 0, '', 0, '40%', '', '', $langs->transnoentities("SelectOneOrMoreEntities"));
							} else {
								$this->resprints.= '<input type="hidden" name="entity" value="'.$conf->entity.'" />';
							}
							$this->resprints.= '<input type="submit" class="button" value="'.$langs->trans("Add").'" />';
						}
					}

					/*
					 * Groups assigned to user
					 */
					if (!empty($groupslist)) {
						foreach($groupslist as $group) {
							$this->resprints.= '<tr class="oddeven">';
							$this->resprints.= '<td>';
							if ($caneditgroup) {
								$this->resprints.= '<a href="'.DOL_URL_ROOT.'/user/group/card.php?id='.$group->id.'">'.img_object($langs->trans("ShowGroup"),"group").' '.$group->name.'</a>';
							} else {
								$this->resprints.= img_object($langs->trans("ShowGroup"),"group").' '.$group->name;
							}
							$this->resprints.= '</td>';
							if (!empty($user->admin)) {
								$this->resprints.= '<td class="valeur">';
								if (!empty($group->usergroup_entity)) {
									sort($group->usergroup_entity, SORT_NUMERIC); // sort by entity id
									foreach($group->usergroup_entity as $group_entity) {
										$mc->getInfo($group_entity);
										if (empty($conf->global->MULTICOMPANY_TEMPLATE_MANAGEMENT) && $mc->visible == 2) continue;

										$this->resprints.= '<span class="multicompany-entity-container">';
										if ($mc->visible == 2) {
											$this->resprints.= '<span id="template_' . $mc->id . '" class="fa fa-clone multicompany-button-template" title="'.$langs->trans("TemplateOfEntity").'"></span>';
										} else {
											$this->resprints.= '<span id="template_' . $mc->id . '" class="fa fa-globe multicompany-button-template" title="'.$langs->trans("Entity").'"></span>';
										}
										$this->resprints.= $mc->label . (empty($mc->active) ? ' ('.$langs->trans('Disabled').')' : ($mc->visible == 2 ? ' ('.$langs->trans('Template').')' : (empty($mc->visible) ? ' ('.$langs->trans('Hidden').')' : '')) );
										if ($conf->entity == 1 && empty($user->entity)) {
											$this->resprints.= '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=removegroup&group='.$group->id.'&entity='.$group_entity.'&token='.currentToken().'">';
											$this->resprints.= img_picto($langs->trans("RemoveFromGroup"), 'unlink', 'class="multicompany-remove-from-link"');
											$this->resprints.= '</a>';
										}
										$this->resprints.= '</span>';
									}
								}
							}
							$this->resprints.= '<td align="right">&nbsp;</td></tr>'."\n";
						}
					} else {
						$this->resprints.= '<tr class="oddeven"><td colspan="3" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
					}

					$this->resprints.= "</table>";

					if (!empty($caneditgroup)) {
						$this->resprints.= '</form>';
					}

					$this->resprints.= '<!-- END multicompany formAddUserToGroup -->'."\n";

					return 1;
				}
			} else if (in_array('groupcard', $currentcontext) && $object->element == 'usergroup') {
				if ($action != 'edit' && !empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE)) {
					$this->resprints = "\n".'<!-- BEGIN multicompany formAddUserToGroup -->'."\n";

					if (!empty($object->members)) {
						$exclude = array();
					}

					if ($caneditperms && empty($user->entity)) {
						$this->resprints.= '<form action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'" method="POST">'."\n";
						$this->resprints.= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
						$this->resprints.= '<input type="hidden" name="action" value="adduser">';
						$this->resprints.= '<table class="noborder" width="100%">'."\n";
						$this->resprints.= '<tr class="liste_titre"><td class="titlefield liste_titre">'.$langs->trans("NonAffectedUsers").'</td>'."\n";
						$this->resprints.= '<th class="liste_titre">';
						$selected = ($action == 'adduser' && GETPOSTINT('user') ? GETPOSTINT('user') : '');
						$this->resprints.= $form->select_dolusers($selected, 'user', $langs->transnoentities("SelectUser"), $exclude, 0, '', '', $object->entity);
						$this->resprints.= ' &nbsp; ';
						if ($conf->entity == 1) {
							$entities = $this->getEntitiesList(false, false, false, (empty($conf->global->MULTICOMPANY_TEMPLATE_MANAGEMENT) ? false : true));
							$this->resprints.= $form->multiselectarray('entities', $entities, GETPOST('entities', 'array'), 0, 0, '', 0, '30%', '', '', $langs->transnoentities("SelectOneOrMoreEntities"));
						} else {
							$this->resprints.= '<input type="hidden" name="entity" value="'.$conf->entity.'" />';
						}
						$this->resprints.= '<input type="submit" class="button" value="'.$langs->trans("Add").'">';
						$this->resprints.= '</th></tr>'."\n";
						$this->resprints.= '</table></form>'."\n";
						$this->resprints.= '<br>';
					}

					/*
					 * Group members
					 */
					$this->resprints.= '<table class="noborder" width="100%">';
					$this->resprints.= '<tr class="liste_titre">';
					$this->resprints.= '<td class="liste_titre">'.$langs->trans("Login").'</td>';
					$this->resprints.= '<td class="liste_titre">'.$langs->trans("Lastname").'</td>';
					$this->resprints.= '<td class="liste_titre">'.$langs->trans("Firstname").'</td>';
					if ($conf->entity == 1) {
						$this->resprints.= '<td class="liste_titre">'.$langs->trans("Entity").'</td>';
					}
					$this->resprints.= '<td class="liste_titre" width="5" align="center">'.$langs->trans("Status").'</td>';
					$this->resprints.= '<td class="liste_titre" width="5" align="right">&nbsp;</td>';
					$this->resprints.= "</tr>\n";

					if (!empty($object->members)) {
						foreach($object->members as $useringroup) {
							$this->resprints.= '<tr class="oddeven">';
							$this->resprints.= '<td>';
							$this->resprints.= $useringroup->getNomUrl(-1, '', 0, 0, 24, 0, 'login');
							if (!empty($useringroup->admin)  && empty($useringroup->entity)) {
								$this->resprints.= img_picto($langs->trans("SuperAdministrator"),'redstar');
							} elseif (!empty($useringroup->admin)) {
								$this->resprints.= img_picto($langs->trans("Administrator"),'star');
							}
							$this->resprints.= '</td>';
							$this->resprints.= '<td>'.$useringroup->lastname.'</td>';
							$this->resprints.= '<td>'.$useringroup->firstname.'</td>';
							if ($conf->entity == 1 && !empty($user->admin)) {
								$this->resprints.= '<td class="valeur">';
								if (!empty($useringroup->usergroup_entity)) {
									sort($useringroup->usergroup_entity, SORT_NUMERIC); // sort by entity id
									foreach($useringroup->usergroup_entity as $group_entity) {
										$mc->getInfo($group_entity);
										if (empty($conf->global->MULTICOMPANY_TEMPLATE_MANAGEMENT) && $mc->visible == 2) {
											continue;
										}
										$this->resprints.= '<span class="multicompany-entity-container">';
										if ($mc->visible == 2) {
											$this->resprints.= '<span id="template_' . $mc->id . '" class="fa fa-clone multicompany-button-template" title="'.$langs->trans("TemplateOfEntity").'"></span>';
										} else {
											$this->resprints.= '<span id="template_' . $mc->id . '" class="fa fa-globe multicompany-button-template" title="'.$langs->trans("Entity").'"></span>';
										}
										$this->resprints.= $mc->label . (empty($mc->active) ? ' ('.$langs->trans('Disabled').')' : ($mc->visible == 2 ? ' ('.$langs->trans('Template').')' : (empty($mc->visible) ? ' ('.$langs->trans('Hidden').')' : '')) );
										if (empty($user->entity)) {
											$this->resprints.= '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=removeuser&user='.$useringroup->id.'&entity='.$group_entity.'&token='.currentToken().'">';
											$this->resprints.= img_picto($langs->trans("RemoveFromGroup"), 'unlink', 'class="multicompany-remove-from-link"');
											$this->resprints.= '</a>';
										}
										$this->resprints.= '</span>';
									}
								}
								$this->resprints.= '</td>';
							}
							$this->resprints.= '<td align="center">'.$useringroup->getLibStatut(3).'</td>';
							$this->resprints.= '<td align="right">';
							$this->resprints.= "-";
							$this->resprints.= "</td></tr>\n";
						}
					} else {
						$this->resprints.= '<tr><td colspan="6" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
					}
					$this->resprints.= "</table>";

					$this->resprints.= '<!-- END multicompany formAddUserToGroup -->'."\n";

					return 1;
				}
			}
		}

		return 0;
	}

	/**
	 *
	 * @param boolean $parameters
	 * @param unknown $object
	 * @param string $action
	 * @return number
	 */
	public function moreHtmlRef($parameters=false, &$object, &$action='')
	{
		global $conf, $user, $langs;
		global $mc;

		if (empty($conf->multicompany->enabled)) {
			return 0;
		}

		if (is_array($parameters) && !empty($parameters)) {
			foreach($parameters as $key=>$value) {
				$$key=$value;
			}
		}

		$currentcontext = explode(':', $parameters['context']);

		$this->resprints = "\n".'<!-- BEGIN multicompany moreHtmlRef -->'."\n";

		if ($object->element == 'user' || $object->element == 'usergroup') {
			if (empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE) && $conf->entity == 1 && !empty($user->admin) && empty($user->entity)) {
				if ($object->entity > 0) {
					$this->getInfo($object->entity);
				}
				$label = (!empty($this->label) ? $this->label : $langs->trans('AllEntities'));
				$this->resprints.= '<br><div class="refidno multicompany-entity-card-container">';
				$this->resprints.= '<span class="fa fa-globe"></span><span class="multiselect-selected-title-text">'.$label.'</span>';
				$this->resprints.= '</div>';
			}
		}

		// if global sharings is enabled
		if (!empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED)) {
		    $this->getInfo(!empty($object->entity) ? $object->entity : $conf->entity);

			// if third party sharing is enabled (is mandatory for some sharings)
			if (!empty($conf->societe->enabled) && !empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_ENABLED)) {
				if ($object->element == 'societe') {
					if (empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_BYELEMENT_ENABLED)) {
						if (!empty($mc->sharings['thirdparty'])) {
							if (in_array('thirdpartycard', $currentcontext)) {
								if ($action != 'create' && $action != 'edit') {
									if ($object->isObjectUsed($object->id) === 0
										&& ((!empty($user->admin) && empty($user->entity)) || !empty($user->rights->multicompany->thirdparty->write))) {
											$selectEntities = $this->getModifyEntityDialog('thirdparty', 'modifyEntity', $object);

											if (!empty($selectEntities)) {
												$this->resprints.= '<div id="modify-entity-thirdparty" class="refidno modify-entity multicompany-entity-card-container" data-tooltip="'.$langs->trans('ThirdpartyModifyEntity').'" data-tooltip-position="bottom">';
												$this->resprints.= '<span class="fa fa-globe"></span><span class="multiselect-selected-title-text">'.$this->label.'</span>';
												$this->resprints.= '</div>';
												$this->resprints.= $selectEntities;
											} else {
												$this->resprints.= '<div class="refidno modify-entity-disabled multicompany-entity-card-container" data-tooltip="'.$langs->trans("NoOtherEntityAvailable").'" data-tooltip-position="bottom">';
												$this->resprints.= '<span class="fa fa-globe"></span><span class="multiselect-selected-title-text">'.$this->label.'</span>';
												$this->resprints.= '</div>';
											}
										} else {
											$this->resprints.= '<div class="refidno modify-entity-disabled multicompany-entity-card-container" data-tooltip="'.$langs->trans("ModifyEntityNotAllowed").'" data-tooltip-position="bottom">';
											$this->resprints.= '<span class="fa fa-globe"></span><span class="multiselect-selected-title-text">'.$this->label.'</span>';
											$this->resprints.= '</div>';
										}
								}
							} else {
								$this->resprints.= '<div class="refidno multicompany-entity-card-container">';
								$this->resprints.= '<span class="fa fa-globe"></span><span class="multiselect-selected-title-text">'.$this->label.'</span>';
								$this->resprints.= '</div>';
							}
						}
					} else {
						if ($this->id != $conf->entity && !empty($mc->sharings['thirdparty'])) {
							$granularity = $this->dao->getListOfSharingsByElement('thirdparty', $object->id, null, true);
							if (!empty($granularity) && in_array($conf->entity, $granularity[$object->id])) {
								$this->resprints.= '<div class="refidno multicompany-entity-card-container" data-tooltip="'.$langs->trans("MulticompanyOriginatingEntity").'" data-tooltip-position="bottom">';
								$this->resprints.= '<span class="fa fa-globe"></span><span class="multiselect-selected-title-text">'.$this->label;
								$this->resprints.= '</span>';
								$this->resprints.= '</div>';
							}
						} else {
							if (1==2 && $this->checkIfElementIsSharedByEntity('thirdparty') === true) {
								$this->getSharingsByElement('thirdparty', $object->id);	// Populate $this->config['sharedwith']
								if (!empty($this->config['sharedwith']['thirdparty'][$object->id])) {
									foreach ($this->config['sharedwith']['thirdparty'][$object->id] as $sharedwithentity) {
										$otherentity = new self($this->db);
										$otherentity->getInfo($sharedwithentity);
										$this->resprints.= '<div class="refidno multicompany-entity-card-container multicompany-margin-right-5" data-tooltip="'.$langs->trans("MulticompanySharedWithThisEntity").'" data-tooltip-position="bottom">';
										$this->resprints.= '<span class="fa fa-globe"></span><span class="multiselect-selected-title-text">'.$otherentity->label.'</span>';
										$this->resprints.= '</div>';
									}
								}
							}
						}
					}
				} elseif ($object->element == 'contact') {
					if (empty($conf->global->MULTICOMPANY_CONTACT_SHARING_BYELEMENT_ENABLED)) {
						if (empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_BYELEMENT_ENABLED)) {
							if (!empty($mc->sharings['thirdparty'])) {
								if (in_array('contactcard', $currentcontext)) {
									if ($action != 'create' && $action != 'edit') {
										if (empty($object->socid) && ((!empty($user->admin) && empty($user->entity)) || !empty($user->rights->multicompany->contact->write))) {
											$selectEntities = $this->getModifyEntityDialog($object->element, 'modifyEntity', $object);
											if (!empty($selectEntities)) {
												$this->resprints.= '<div id="modify-entity-contact" class="refidno modify-entity multicompany-entity-card-container" data-tooltip="'.$langs->trans('ContactModifyEntity').'" data-tooltip-position="bottom">';
												$this->resprints.= '<span class="fa fa-globe"></span><span class="multiselect-selected-title-text">'.$this->label.'</span>';
												$this->resprints.= '</div>';
												$this->resprints.= $selectEntities;
											} else {
												$this->resprints.= '<div class="refidno modify-entity-disabled multicompany-entity-card-container" data-tooltip="'.$langs->trans("NoOtherEntityAvailable").'" data-tooltip-position="bottom">';
												$this->resprints.= '<span class="fa fa-globe"></span><span class="multiselect-selected-title-text">'.$this->label.'</span>';
												$this->resprints.= '</div>';
											}
										} else {
											$this->resprints.= '<div class="refidno modify-entity-disabled multicompany-entity-card-container" data-tooltip="'.$langs->trans("ModifyEntityNotAllowed").'" data-tooltip-position="bottom">';
											$this->resprints.= '<span class="fa fa-globe"></span><span class="multiselect-selected-title-text">'.$this->label.'</span>';
											$this->resprints.= '</div>';
										}
									}
								} else {
									$this->resprints.= '<div class="refidno multicompany-entity-card-container">';
									$this->resprints.= '<span class="fa fa-globe"></span><span class="multiselect-selected-title-text">'.$this->label.'</span>';
									$this->resprints.= '</div>';
								}
							}
						} else {
							if (1==2 && $this->checkIfElementIsSharedByEntity('thirdparty') === true) {
								$this->getSharingsByElement($object->element, $object->id);	// Populate $this->config['sharedwith']
								if (!empty($this->config['sharedwith'][$object->element][$object->id])) {
									foreach ($this->config['sharedwith'][$object->element][$object->id] as $sharedwithentity) {
										$otherentity = new self($this->db);
										$otherentity->getInfo($sharedwithentity);
										$this->resprints.= '<div class="refidno multicompany-entity-card-container multicompany-margin-right-5" data-tooltip="'.$langs->trans("MulticompanySharedWithThisEntity").'" data-tooltip-position="bottom">';
										$this->resprints.= '<span class="fa fa-globe"></span><span class="multiselect-selected-title-text">'.$otherentity->label.'</span>';
										$this->resprints.= '</div>';
									}
								}
							}
						}
					} else {
						if ($this->id != $conf->entity && !empty($mc->sharings['thirdparty'])) {
							$granularity = $this->dao->getListOfSharingsByElement($object->element, $object->id, null, true);
							if (!empty($granularity) && in_array($conf->entity, $granularity[$object->id])) {
								$this->resprints.= '<div class="refidno multicompany-entity-card-container" data-tooltip="'.$langs->trans("MulticompanyOriginatingEntity").'" data-tooltip-position="bottom">';
								$this->resprints.= '<span class="fa fa-globe"></span><span class="multiselect-selected-title-text">'.$this->label;
								$this->resprints.= '</span>';
								$this->resprints.= '</div>';
							}
						} else {
							if (1==2 && $this->checkIfElementIsSharedByEntity('thirdparty') === true) {
								$this->getSharingsByElement($object->element, $object->id);	// Populate $this->config['sharedwith']
								if (!empty($this->config['sharedwith'][$object->element][$object->id])) {
									foreach ($this->config['sharedwith'][$object->element][$object->id] as $sharedwithentity) {
										$otherentity = new self($this->db);
										$otherentity->getInfo($sharedwithentity);
										$this->resprints.= '<div class="refidno multicompany-entity-card-container multicompany-margin-right-5" data-tooltip="'.$langs->trans("MulticompanySharedWithThisEntity").'" data-tooltip-position="bottom">';
										$this->resprints.= '<span class="fa fa-globe"></span><span class="multiselect-selected-title-text">'.$otherentity->label.'</span>';
										$this->resprints.= '</div>';
									}
								}
							}
						}
					}
				}
			}

			if ((!empty($conf->product->enabled) || !empty($conf->service->enabled)) && $object->element == 'product' && !empty($conf->global->MULTICOMPANY_PRODUCT_SHARING_ENABLED)) {
				if ($action != 'create' && $action != 'edit') {
					if (empty($conf->global->MULTICOMPANY_PRODUCT_SHARING_BYELEMENT_ENABLED)) {
						if ($this->id != $conf->entity && !empty($mc->sharings[$object->element])) {
							$this->resprints.= '<div class="refidno multicompany-entity-card-container" data-tooltip="'.$langs->trans("MulticompanyOriginatingEntity").'" data-tooltip-position="bottom">';
							$this->resprints.= '<span class="fa fa-globe"></span><span class="multiselect-selected-title-text">'.$this->label;
							$this->resprints.= '</span>';
							$this->resprints.= '</div>';
						}
					} else {
						if ($this->id != $conf->entity && !empty($mc->sharings[$object->element])) {
							$granularity = $this->dao->getListOfSharingsByElement($object->element, $object->id, null, true);
							if (!empty($granularity) && in_array($conf->entity, $granularity[$object->id])) {
								$this->resprints.= '<div class="refidno multicompany-entity-card-container" data-tooltip="'.$langs->trans("MulticompanyOriginatingEntity").'" data-tooltip-position="bottom">';
								$this->resprints.= '<span class="fa fa-globe"></span><span class="multiselect-selected-title-text">'.$this->label;
								$this->resprints.= '</span>';
								$this->resprints.= '</div>';
							}
						} else {
							if (1==2 && $this->checkIfElementIsSharedByEntity($object->element) === true) {
								$this->getSharingsByElement($object->element, $object->id);	// Populate $this->config['sharedwith']
								if (!empty($this->config['sharedwith'][$object->element][$object->id])) {
									foreach ($this->config['sharedwith'][$object->element][$object->id] as $sharedwithentity) {
										$otherentity = new self($this->db);
										$otherentity->getInfo($sharedwithentity);
										$this->resprints.= '<div class="refidno multicompany-entity-card-container multicompany-margin-right-5" data-tooltip="'.$langs->trans("MulticompanySharedWithThisEntity").'" data-tooltip-position="bottom">';
										$this->resprints.= '<span class="fa fa-globe"></span><span class="multiselect-selected-title-text">'.$otherentity->label.'</span>';
										$this->resprints.= '</div>';
									}
								}
							}
						}
					}
				}
			} elseif (!empty($conf->categorie->enabled) && $object->element == 'category' && !empty($conf->global->MULTICOMPANY_CATEGORY_SHARING_ENABLED)) {
				if ($action != 'create' && $action != 'edit') {
					if (empty($conf->global->MULTICOMPANY_CATEGORY_SHARING_BYELEMENT_ENABLED)) {
						if ($this->id != $conf->entity && !empty($mc->sharings[$object->element])) {
							$this->resprints.= '<div class="refidno multicompany-entity-card-container" data-tooltip="'.$langs->trans("MulticompanyOriginatingEntity").'" data-tooltip-position="bottom">';
							$this->resprints.= '<span class="fa fa-globe"></span><span class="multiselect-selected-title-text">'.$this->label;
							$this->resprints.= '</span>';
							$this->resprints.= '</div>';
						}
					} else {
						if ($this->id != $conf->entity && !empty($mc->sharings[$object->element])) {
							$granularity = $this->dao->getListOfSharingsByElement($object->element, $object->id, null, true);
							if (!empty($granularity) && in_array($conf->entity, $granularity[$object->id])) {
								$this->resprints.= '<div class="refidno multicompany-entity-card-container" data-tooltip="'.$langs->trans("MulticompanyOriginatingEntity").'" data-tooltip-position="bottom">';
								$this->resprints.= '<span class="fa fa-globe"></span><span class="multiselect-selected-title-text">'.$this->label;
								$this->resprints.= '</span>';
								$this->resprints.= '</div>';
							}
						} else {
							if (1==2 && $this->checkIfElementIsSharedByEntity($object->element) === true) {
								$this->getSharingsByElement($object->element, $object->id);	// Populate $this->config['sharedwith']
								if (!empty($this->config['sharedwith'][$object->element][$object->id])) {
									foreach ($this->config['sharedwith'][$object->element][$object->id] as $sharedwithentity) {
										$otherentity = new self($this->db);
										$otherentity->getInfo($sharedwithentity);
										$this->resprints.= '<div class="refidno multicompany-entity-card-container multicompany-margin-right-5" data-tooltip="'.$langs->trans("MulticompanySharedWithThisEntity").'" data-tooltip-position="bottom">';
										$this->resprints.= '<span class="fa fa-globe"></span><span class="multiselect-selected-title-text">'.$otherentity->label.'</span>';
										$this->resprints.= '</div>';
									}
								}
							}
						}
					}
				}
			} elseif (!empty($conf->project->enabled) && $object->element == 'project' && !empty($conf->global->MULTICOMPANY_PROJECT_SHARING_ENABLED) && !empty($mc->sharings['project'])) {
				if (in_array('projectcard', $currentcontext)) {
					if ($action != 'create' && $action != 'edit') {
						if (empty($object->socid) && ((!empty($user->admin) && empty($user->entity)) || !empty($user->rights->projet->creer))) {
							$selectEntities = $this->getModifyEntityDialog('project', 'modifyEntity', $object);

							if (!empty($selectEntities)) {
								$this->resprints.= '<div id="modify-entity-project" class="refidno modify-entity multicompany-entity-card-container" data-tooltip="'.$langs->trans('ProjectModifyEntity').'" data-tooltip-position="bottom">';
								$this->resprints.= '<span class="fa fa-globe"></span><span class="multiselect-selected-title-text">'.$this->label.'</span>';
								$this->resprints.= '</div>';
								$this->resprints.= $selectEntities;
							} else {
								$this->resprints.= '<div class="refidno modify-entity-disabled multicompany-entity-card-container" data-tooltip="'.$langs->trans("NoOtherEntityAvailable").'" data-tooltip-position="bottom">';
								$this->resprints.= '<span class="fa fa-globe"></span><span class="multiselect-selected-title-text">'.$this->label.'</span>';
								$this->resprints.= '</div>';
							}
						} else {
							$this->resprints.= '<div class="refidno modify-entity-disabled multicompany-entity-card-container" data-tooltip="'.$langs->trans("ModifyEntityNotAllowed").'" data-tooltip-position="bottom">';
							$this->resprints.= '<span class="fa fa-globe"></span><span class="multiselect-selected-title-text">'.$this->label.'</span>';
							$this->resprints.= '</div>';
						}
					}
				} else {
					$this->resprints.= '<div class="refidno multicompany-entity-card-container">';
					$this->resprints.= '<span class="fa fa-globe"></span><span class="multiselect-selected-title-text">'.$this->label.'</span>';
					$this->resprints.= '</div>';
				}

			} elseif ((!empty($conf->propal->enabled) && $object->element == 'propal' && in_array('propalcard', $currentcontext) && !empty($conf->global->MULTICOMPANY_PROPOSAL_SHARING_ENABLED) && !empty($mc->sharings['proposal']))
				|| (!empty($conf->commande->enabled) && $object->element == 'commande' && in_array('ordercard', $currentcontext) && !empty($conf->global->MULTICOMPANY_ORDER_SHARING_ENABLED) && !empty($mc->sharings['order']))
				|| (!empty($conf->invoice->enabled) && $object->element == 'facture' && in_array('invoicecard', $currentcontext) && !empty($conf->global->MULTICOMPANY_INVOICE_SHARING_ENABLED) && !empty($mc->sharings['invoice']))
			    || (!empty($conf->stock->enabled) && $object->element == 'stock' && in_array('warehousecard', $currentcontext) && !empty($conf->global->MULTICOMPANY_STOCK_SHARING_ENABLED) && !empty($mc->sharings['stock']))
				|| (!empty($conf->ficheinter->enabled) && $object->element == 'fichinter' && in_array('interventioncard', $currentcontext) && !empty($conf->global->MULTICOMPANY_INTERVENTION_SHARING_ENABLED) && !empty($mc->sharings['intervention']))
			    || (!empty($conf->member->enabled) && $object->element == 'member' && in_array('membercard', $currentcontext) && !empty($conf->global->MULTICOMPANY_MEMBER_SHARING_ENABLED) && !empty($mc->sharings['member']))) {

				$this->resprints.= '<div class="refidno multicompany-entity-card-container">';
				$this->resprints.= '<span class="fa fa-globe"></span><span class="multiselect-selected-title-text">'.$this->label.'</span>';
				$this->resprints.= '</div>';
			}
		}

		$this->resprints.= '<!-- END multicompany moreHtmlRef -->'."\n";

		return 0;
	}

	/**
	 *
	 * @param boolean $parameters
	 * @param unknown $object
	 * @param string $action
	 * @return number
	 */
	public function moreHtmlStatus($parameters=false, &$object, &$action='')
	{
		global $conf;
		/*
		if (empty($conf->multicompany->enabled)) return 0;

		if (is_array($parameters) && !empty($parameters)) {
			foreach($parameters as $key=>$value) {
				$$key=$value;
			}
		}

		$this->resprints.= 'OK';
		*/

		return 0;
	}

	/**
	 *
	 * @param boolean $parameters
	 * @return number
	 */
	public function printUserListWhere($parameters=false)
	{
		global $conf, $user;

		if (empty($conf->multicompany->enabled)) {
			return 0;
		}

		/*if (is_array($parameters) && !empty($parameters)) {
			foreach($parameters as $key=>$value) {
				$$key=$value;
			}
		}*/

		$this->resprints = '';

		if (!empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE)) {
			if (!empty($user->admin) && empty($user->entity) && $conf->entity == 1) {
				$this->resprints.= " WHERE u.entity IS NOT NULL"; // Show all users
			} else {
				$this->resprints.= ",".MAIN_DB_PREFIX."usergroup_user as ug";
				$this->resprints.= " WHERE ((ug.fk_user = u.rowid";
				$this->resprints.= " AND ug.entity IN (".getEntity('usergroup')."))";
				$this->resprints.= " OR u.entity = 0)"; // Show always superadmin
			}
			return 1;
		}

		return 0;
	}

	/**
	 *
	 * @param boolean $parameters
	 * @return number
	 */
	public function addMoreMassActions($parameters=false)
	{
		global $conf, $langs;
		global $mc;

		if (empty($conf->multicompany->enabled) || empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED)) {
			return 0;
		}

		$langs->load('multicompany@multicompany');

		if (is_array($parameters) && !empty($parameters)) {
			foreach($parameters as $key=>$value) {
				$$key=$value;
			}
		}

		$currentcontext = explode(':', $parameters['context']);

		if (empty($conf->global->MULTICOMPANY_SHARING_BYELEMENT_ENABLED)) {
			if (!empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_ENABLED)
				&& (in_array('thirdpartylist', $currentcontext) || in_array('contactlist', $currentcontext) || in_array('prospectlist', $currentcontext) || in_array('customerlist', $currentcontext) || in_array('supplierlist', $currentcontext) || in_array('poslist', $currentcontext))
				&& !empty($mc->sharings['thirdparty'])) {
					$label = img_picto('', 'website', 'class="pictofixedwidth"').$langs->trans('ModifyEntity');
					$this->resprints = '<option value="modify_entity" disabled="disabled" data-html="'.dol_escape_htmltag($label).'">'.$label.'</option>';
			}
		} else {
			if (!empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_ENABLED)
				&& !empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_BYELEMENT_ENABLED)
				&& (in_array('thirdpartylist', $currentcontext) || in_array('contactlist', $currentcontext) || in_array('prospectlist', $currentcontext) || in_array('customerlist', $currentcontext) || in_array('supplierlist', $currentcontext) || in_array('poslist', $currentcontext))
				&& $this->checkIfElementIsSharedByEntity('thirdparty') === true) {
					$label = img_picto('', 'website', 'class="pictofixedwidth"').$langs->trans('ShareByElement');
					$this->resprints = '<option value="sharebyelement" data-html="'.dol_escape_htmltag($label).'">'.$label.'</option>';
			} elseif (!empty($conf->global->MULTICOMPANY_PRODUCT_SHARING_ENABLED)
				&& !empty($conf->global->MULTICOMPANY_PRODUCT_SHARING_BYELEMENT_ENABLED)
				&& in_array('productservicelist', $currentcontext)
				&& $this->checkIfElementIsSharedByEntity('product') === true) {
					$label = img_picto('', 'website', 'class="pictofixedwidth"').$langs->trans('ShareByElement');
					$this->resprints = '<option value="sharebyelement" data-html="'.dol_escape_htmltag($label).'">'.$label.'</option>';
			}
		}

		return 0;
	}

	/**
	 *
	 * @param boolean $parameters
	 * @param unknown $object
	 * @param unknown $action
	 * @return number
	 */
	public function doPreMassActions($parameters=false, &$object, &$action)
	{
		global $conf, $langs;
		global $form, $massaction, $mc;

		if (empty($conf->multicompany->enabled) || empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED)) {
			return 0;
		}

		$langs->load('multicompany@multicompany');

		if (is_array($parameters) && !empty($parameters)) {
			foreach($parameters as $key=>$value) {
				$$key=$value;
			}
		}

		$currentcontext = explode(':', $parameters['context']);

		if (!empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_ENABLED)
			&& !empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_BYELEMENT_ENABLED)
			&& (in_array('thirdpartylist', $currentcontext) || in_array('contactlist', $currentcontext) || in_array('prospectlist', $currentcontext) || in_array('customerlist', $currentcontext) || in_array('supplierlist', $currentcontext) || in_array('poslist', $currentcontext))
			&& $this->checkIfElementIsSharedByEntity('thirdparty') === true
			&& $massaction == 'sharebyelement') {
				$formquestion[] = array('type' => 'other',
					'name' => 'sharebyelement',
					'value' => '<br>'.$this->formSelectSharingByElement($object, 'thirdparty').'<br>'
				);
				$this->resprints = $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans("ConfirmMassThirdpartySharingByElement"), $langs->trans("ConfirmMassThirdpartySharingByElementQuestion", count($toselect)), "confirmsharebyelement", $formquestion, '', 0, 200, 500, 1);
		} elseif (!empty($conf->global->MULTICOMPANY_PRODUCT_SHARING_ENABLED)
			&& !empty($conf->global->MULTICOMPANY_PRODUCT_SHARING_BYELEMENT_ENABLED)
			&& in_array('productservicelist', $currentcontext)
			&& $this->checkIfElementIsSharedByEntity('product') === true
			&& $massaction == 'sharebyelement') {
				$formquestion[] = array('type' => 'other',
					'name' => 'sharebyelement',
					'value' => '<br>'.$this->formSelectSharingByElement($object, 'product').'<br>'
				);
				$this->resprints = $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans("ConfirmMassProductSharingByElement"), $langs->trans("ConfirmMassProductSharingByElementQuestion", count($toselect)), "confirmsharebyelement", $formquestion, '', 0, 200, 500, 1);
		}

		return 0;
	}

	/**
	 *
	 * @param boolean $parameters
	 * @return number
	 */
	public function printFieldListSelect($parameters=false)
	{
		global $conf;
		global $mc;

		if (empty($conf->multicompany->enabled)) {
			return 0;
		}

		if (is_array($parameters) && !empty($parameters)) {
			foreach($parameters as $key=>$value) {
				$$key=$value;
			}
		}

		$currentcontext = explode(':', $parameters['context']);

		if (!empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED)) {
			// Thirdparty sharing is mandatory to share document (propal, etc...)
			if (!empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_ENABLED) && !empty($mc->sharings['thirdparty'])) {
				if (in_array('thirdpartylist', $currentcontext) || in_array('contactlist', $currentcontext) || in_array('prospectlist', $currentcontext) || in_array('customerlist', $currentcontext) || in_array('supplierlist', $currentcontext) || in_array('poslist', $currentcontext)) {
					//if (!empty($arrayfields['s.entity']['checked']))
					{
						$this->resprints = ", s.entity";
					}
				} elseif (in_array('contactlist', $currentcontext)) {
					//if (!empty($arrayfields['p.entity']['checked']))
					{
						$this->resprints = ", p.entity";
					}
				} elseif (in_array('propallist', $currentcontext) && !empty($conf->global->MULTICOMPANY_PROPOSAL_SHARING_ENABLED) && !empty($mc->sharings['proposal'])) {
					//if (!empty($arrayfields['p.entity']['checked']))
					{
						$this->resprints = ", p.entity";
					}
				} elseif (in_array('orderlist', $currentcontext) && !empty($conf->global->MULTICOMPANY_ORDER_SHARING_ENABLED) && !empty($mc->sharings['order'])) {
					//if (!empty($arrayfields['p.entity']['checked']))
					{
						$this->resprints = ", c.entity";
					}
				} elseif (in_array('invoicelist', $currentcontext) && !empty($conf->global->MULTICOMPANY_INVOICE_SHARING_ENABLED) && !empty($mc->sharings['invoice'])) {
					//if (!empty($arrayfields['p.entity']['checked']))
					{
						$this->resprints = ", f.entity";
					}
				} elseif (in_array('interventionlist', $currentcontext) && !empty($conf->global->MULTICOMPANY_INTERVENTION_SHARING_ENABLED) && !empty($mc->sharings['intervention'])) {
					//if (!empty($arrayfields['p.entity']['checked']))
					{
						$this->resprints = ", f.entity";
					}
				}
			}

			if (in_array('memberlist', $currentcontext) && !empty($conf->global->MULTICOMPANY_MEMBER_SHARING_ENABLED) && !empty($mc->sharings['member'])) {
				//if (!empty($arrayfields['p.entity']['checked']))
				{
					$this->resprints = ", d.entity";
				}
			} elseif (in_array('productservicelist', $currentcontext) && !empty($conf->global->MULTICOMPANY_PRODUCT_SHARING_ENABLED) && !empty($mc->sharings['product'])) {
				//if (!empty($arrayfields['p.entity']['checked']))
				{
					$this->resprints = ", p.entity";
				}
			} elseif (in_array('projectlist', $currentcontext) && !empty($conf->global->MULTICOMPANY_PROJECT_SHARING_ENABLED) && !empty($mc->sharings['project'])) {
				//if (!empty($arrayfields['p.entity']['checked']))
				{
					$this->resprints = " , p.entity";
				}
			}
		}

		return 0;
	}

	/**
	 *
	 * @param boolean $parameters
	 * @param unknown $object
	 * @return number
	 */
	public function printFieldListFrom($parameters = false, &$object)
	{
		global $conf;

		if (empty($conf->multicompany->enabled)) {
			return 0;
		}

		if (is_array($parameters) && !empty($parameters)) {
			foreach($parameters as $key => $value) {
				$$key = $value;
			}
		}

		$currentcontext = explode(':', $parameters['context']);

		if (!empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED)) {
			if (!empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_ENABLED)
				&& !empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_BYELEMENT_ENABLED)
				&& !empty($conf->global->MULTICOMPANY_CONTACT_SHARING_BYELEMENT_ENABLED)
				&& !empty($object->element) && $object->element == 'societe' && !empty($contacttab)) {
					$this->resprints.= ", ".MAIN_DB_PREFIX."societe as s";
			}
		}

		return 0;
	}

	/**
	 *
	 * @param boolean $parameters
	 * @param unknown $object
	 * @return number
	 */
	public function printFieldListWhere($parameters=false, &$object)
	{
		global $conf;
		global $mc;

		if (empty($conf->multicompany->enabled)) {
			return 0;
		}

		if (is_array($parameters) && !empty($parameters)) {
			foreach($parameters as $key=>$value) {
				$$key=$value;
			}
		}

		$currentcontext = explode(':', $parameters['context']);

		$this->resprints = '';

		if (!empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED)) {
			$search_entity = GETPOST('search_entity','int');

			// All tests are required to be compatible with all browsers
			if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')) {
				$search_entity = '';
			}

			if (!empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_ENABLED)) {
				// for element next/previous function for thirdparty element
				if (!empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_BYELEMENT_ENABLED)
					&& is_object($object) && $object->element == 'societe' && !empty($object->id && !in_array('thirdpartylist', $currentcontext))
					//&& !in_array('commercialindex', $currentcontext)
					//&& !in_array('thirdpartycontact', $currentcontext)
					&& !empty($showrefnav)) {
						$this->getInstanceDao();
						$granularity = $this->dao->getListOfSharingsByElement('thirdparty', null, $conf->entity);
						if (is_array($granularity) && !empty($granularity)) {
							$method = 'IN';
							if (isSharingAllByDefault('thirdparty')) {
								$method = 'NOT IN';
							}
							$this->resprints.= " AND (te.rowid ".$method." (" . implode(",", array_keys($granularity)) . ") OR te.entity = " . $conf->entity . ")";
						}
				// for element next/previous function for contact element
				} elseif (!empty($conf->global->MULTICOMPANY_CONTACT_SHARING_BYELEMENT_ENABLED)
					&& is_object($object) && $object->element == 'contact' && !empty($object->id) && !empty($showrefnav) && !in_array('contactlist', $currentcontext)) {
						$this->getInstanceDao();
						$granularity = $this->dao->getListOfSharingsByElement($object->element, null, $conf->entity);
						if (is_array($granularity) && !empty($granularity)) {
							$method = 'IN';
							if (isSharingAllByDefault($object->element)) {
								$method = 'NOT IN';
							}
							$this->resprints.= " AND (te.rowid ".$method." (" . implode(",", array_keys($granularity)) . ") OR te.entity = " . $conf->entity . ")";
						}
				// for element list, load_state_board and boxes
				} elseif (!empty($mc->sharings['thirdparty'])) {
					if ((in_array('thirdpartylist', $currentcontext)
						|| in_array('thirdpartiesindex', $currentcontext)
						|| in_array('commercialindex', $currentcontext)
						|| in_array('index', $currentcontext)
						|| in_array('prospectlist', $currentcontext)
						|| in_array('customerlist', $currentcontext)
						|| in_array('poslist', $currentcontext)
						|| in_array('supplierlist', $currentcontext)
						|| (!empty($boxcode) && ($boxcode == 'lastprospects' || $boxcode == 'lastcustomers' || $boxcode == 'lastsuppliers')))
						&& $object->element == 'societe') {
							if (!empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_BYELEMENT_ENABLED)) {
								$this->getInstanceDao();
								$granularity = $this->dao->getListOfSharingsByElement('thirdparty', null, $conf->entity);
								if (is_array($granularity) && !empty($granularity)) {
									$method = 'IN';
									if (isSharingAllByDefault('thirdparty')) {
										$method = 'NOT IN';
									}
									$this->resprints.= " AND (s.rowid ".$method." (" . implode(",", array_keys($granularity)) . ") OR s.entity = " . $conf->entity . ")";
								}
							}
							//if (!empty($arrayfields['s.entity']['checked']))
							{
								if ($search_entity > 0) {
									$this->resprints.= " AND s.entity = " . $search_entity;
								}
							}
					} elseif (in_array('contactlist', $currentcontext)) {
						if (!empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_BYELEMENT_ENABLED)) {
							$this->getInstanceDao();
							$granularity = $this->dao->getListOfSharingsByElement('contact', null, $conf->entity);
							if (is_array($granularity) && !empty($granularity)) {
								$method = 'IN';
								if (isSharingAllByDefault('contact')) {
									$method = 'NOT IN';
								}
								$this->resprints.= " AND (p.rowid ".$method." (" . implode(",", array_keys($granularity)) . ") OR p.entity = " . $conf->entity . ")";
							}
						}
						//if (!empty($arrayfields['p.entity']['checked']))
						{
							if ($search_entity > 0)	{
								$this->resprints = " AND p.entity = " . $search_entity;
							}
						}
					} elseif (!empty($object->element) && $object->element == 'societe' && !empty($contacttab)) {
						if (!empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_BYELEMENT_ENABLED)) {
							$this->getInstanceDao();
							$granularity = $this->dao->getListOfSharingsByElement('contact', null, $conf->entity);
							$this->resprints.= " AND p.fk_soc = s.rowid";
							if (is_array($granularity) && !empty($granularity)) {
								$method = 'IN';
								if (isSharingAllByDefault('contact')) {
									$method = 'NOT IN';
								}
								$this->resprints.= " AND (p.rowid ".$method." (" . implode(",", array_keys($granularity)) . ") OR s.entity = " . $conf->entity . ")";
							}
						}
					// For contact load_state_board and boxes
					} elseif ((in_array('index', $currentcontext) || (!empty($boxcode) && $boxcode == 'lastcontacts'))
						&& $object->element == 'contact'
						&& !empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_BYELEMENT_ENABLED)) {
							$this->getInstanceDao();
							$granularity = $this->dao->getListOfSharingsByElement($object->element, null, $conf->entity);
							if (is_array($granularity) && !empty($granularity)) {
								$method = 'IN';
								if (isSharingAllByDefault($object->element)) {
									$method = 'NOT IN';
								}
								$this->resprints.= " AND (sp.rowid ".$method." (" . implode(",", array_keys($granularity)) . ") OR sp.entity = " . $conf->entity . ")";
							}
					// for thirdparty contact list tab
					} elseif (in_array('thirdpartycontact', $currentcontext) && $object->element == 'societe') {
						if (!empty($conf->global->MULTICOMPANY_CONTACT_SHARING_BYELEMENT_ENABLED)) {
							$this->getInstanceDao();
							$granularity = $this->dao->getListOfSharingsByElement('contact', null, $conf->entity);
							if (is_array($granularity) && !empty($granularity)) {
								$method = 'IN';
								if (isSharingAllByDefault('contact')) {
									$method = 'NOT IN';
								}
								$this->resprints.= " AND (t.rowid ".$method." (" . implode(",", array_keys($granularity)) . ") OR t.entity = " . $conf->entity . ")";
							}
						}
					} elseif (in_array('propallist', $currentcontext) && !empty($conf->global->MULTICOMPANY_PROPOSAL_SHARING_ENABLED) && !empty($mc->sharings['proposal'])) {
						//if (!empty($arrayfields['p.entity']['checked']))
						{
							if ($search_entity > 0) {
								$this->resprints = " AND p.entity = " . $search_entity;
							}
						}
					} elseif (in_array('orderlist', $currentcontext) && !empty($conf->global->MULTICOMPANY_ORDER_SHARING_ENABLED) && !empty($mc->sharings['order'])) {
						//if (!empty($arrayfields['p.entity']['checked']))
						{
							if ($search_entity > 0) {
								$this->resprints = " AND c.entity = " . $search_entity;
							}
						}
					} elseif (in_array('invoicelist', $currentcontext) && !empty($conf->global->MULTICOMPANY_INVOICE_SHARING_ENABLED) && !empty($mc->sharings['invoice'])) {
						//if (!empty($arrayfields['f.entity']['checked']))
						{
							if ($search_entity > 0) {
								$this->resprints = " AND f.entity = " . $search_entity;
							}
						}
					} elseif (in_array('interventionlist', $currentcontext) && !empty($conf->global->MULTICOMPANY_INTERVENTION_SHARING_ENABLED) && !empty($mc->sharings['intervention'])) {
						//if (!empty($arrayfields['f.entity']['checked']))
						{
							if ($search_entity > 0) {
								$this->resprints = " AND f.entity = " . $search_entity;
							}
						}
					}
				}
			}

			// for product/service next/previous function
			if (!empty($conf->global->MULTICOMPANY_PRODUCT_SHARING_ENABLED)
				&& !empty($conf->global->MULTICOMPANY_PRODUCT_SHARING_BYELEMENT_ENABLED)
				&& is_object($object) && $object->element == 'product' && !empty($object->id)
				&& !in_array('productservicelist', $currentcontext) && !in_array('productstatsinvoice', $currentcontext) && in_array('searchform', $currentcontext)) {
					$this->getInstanceDao();
					$granularity = $this->dao->getListOfSharingsByElement($object->element, null, $conf->entity);
					if (is_array($granularity) && !empty($granularity)) {
						$method = 'IN';
						if (isSharingAllByDefault($object->element)) {
							$method = 'NOT IN';
						}
						$this->resprints.= " AND (te.rowid ".$method." (" . implode(",", array_keys($granularity)) . ") OR te.entity = " . $conf->entity . ")";
					}
			// for product/service list, load_state_board and boxes
			} elseif ((in_array('productservicelist', $currentcontext)
				|| in_array('productreassortlist', $currentcontext)
				|| in_array('productindex', $currentcontext)
				|| (in_array('index', $currentcontext) && $object->element == 'product')
				||(!empty($boxcode) && ($boxcode == 'lastproducts' || $boxcode == 'productsalertstock')))
				&& !empty($conf->global->MULTICOMPANY_PRODUCT_SHARING_ENABLED) && !empty($mc->sharings['product'])) {
				if (!empty($conf->global->MULTICOMPANY_PRODUCT_SHARING_BYELEMENT_ENABLED)) {
					$this->getInstanceDao();
					$granularity = $this->dao->getListOfSharingsByElement('product', null, $conf->entity);
					if (is_array($granularity) && !empty($granularity)) {
						$method = 'IN';
						if (isSharingAllByDefault('product')) {
							$method = 'NOT IN';
						}
						$this->resprints.= " AND (p.rowid ".$method." (" . implode(",", array_keys($granularity)) . ") OR p.entity = " . $conf->entity . ")";
					}
				}
				//if (!empty($arrayfields['p.entity']['checked']))
				{
					if ($search_entity > 0) {
						$this->resprints = " AND p.entity = " . $search_entity;
					}
				}
			} elseif (in_array('memberlist', $currentcontext) && !empty($conf->global->MULTICOMPANY_MEMBER_SHARING_ENABLED) && !empty($mc->sharings['member'])) {
				//if (!empty($arrayfields['p.entity']['checked']))
				{
					if ($search_entity > 0) {
						$this->resprints = " AND d.entity = " . $search_entity;
					}
				}
            } elseif (in_array('stocklist', $currentcontext) && !empty($conf->global->MULTICOMPANY_STOCK_SHARING_ENABLED) && !empty($mc->sharings['stock'])) {
                //if (!empty($arrayfields['p.entity']['checked']))
                {
                    if ($search_entity > 0) {
                        $this->resprints = " AND t.entity = " . $search_entity;
                    }
                }
            } elseif (in_array('projectlist', $currentcontext) && !empty($conf->global->MULTICOMPANY_PROJECT_SHARING_ENABLED) && !empty($mc->sharings['project'])) {
				//if (!empty($arrayfields['p.entity']['checked']))
				{
					if ($search_entity > 0) {
						$this->resprints = " AND p.entity = " . $search_entity;
					}
				}
			}
        }

        return 0;
	}

	/**
	 *
	 * @param boolean $parameters
	 * @return number
	 */
	public function printFieldListOption($parameters=false)
	{
		global $conf;
		global $mc;

		if (empty($conf->multicompany->enabled)) {
			return 0;
		}

		if (is_array($parameters) && !empty($parameters)) {
			foreach($parameters as $key=>$value) {
				$$key=$value;
			}
		}

		$currentcontext = explode(':', $parameters['context']);

		if (!empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED)) {
			$search_entity = GETPOST('search_entity','int');

			// All tests are required to be compatible with all browsers
			if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')) {
				$search_entity = '';
			}

			if (!empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_ENABLED) && !empty($mc->sharings['thirdparty'])) {
				if (in_array('thirdpartylist', $currentcontext) || in_array('contactlist', $currentcontext) || in_array('prospectlist', $currentcontext) || in_array('customerlist', $currentcontext) || in_array('supplierlist', $currentcontext) || in_array('poslist', $currentcontext)) {
					//if (!empty($arrayfields['s.entity']['checked']) || !empty($arrayfields['p.entity']['checked']))
					{
						// Entity
						$this->resprints = '<td class="liste_titre maxwidthonsmartphone" align="center">';
						$this->resprints.= $this->select_entities($search_entity,'search_entity','',false,false,true,explode(",", $mc->entities['thirdparty']),'','minwidth100imp');
						$this->resprints.= '</td>';
					}
				} elseif (in_array('propallist', $currentcontext) && !empty($conf->global->MULTICOMPANY_PROPOSAL_SHARING_ENABLED) && !empty($mc->sharings['proposal'])) {
					//if (!empty($arrayfields['p.entity']['checked']))
					{
						// Entity
						$this->resprints = '<td class="liste_titre maxwidthonsmartphone" align="center">';
						$this->resprints.= $this->select_entities($search_entity,'search_entity','',false,false,true,explode(",", $mc->entities['proposal']),'','minwidth100imp');
						$this->resprints.= '</td>';
					}
				} elseif (in_array('orderlist', $currentcontext) && !empty($conf->global->MULTICOMPANY_ORDER_SHARING_ENABLED) && !empty($mc->sharings['order'])) {
					//if (!empty($arrayfields['p.entity']['checked']))
					{
						// Entity
						$this->resprints = '<td class="liste_titre maxwidthonsmartphone" align="center">';
						$this->resprints.= $this->select_entities($search_entity,'search_entity','',false,false,true,explode(",", $mc->entities['order']),'','minwidth100imp');
						$this->resprints.= '</td>';
					}
				} elseif (in_array('invoicelist', $currentcontext) && !empty($conf->global->MULTICOMPANY_INVOICE_SHARING_ENABLED) && !empty($mc->sharings['invoice'])) {
					//if (!empty($arrayfields['f.entity']['checked']))
					{
						// Entity
						$this->resprints = '<td class="liste_titre maxwidthonsmartphone" align="center">';
						$this->resprints.= $this->select_entities($search_entity,'search_entity','',false,false,true,explode(",", $mc->entities['invoice']),'','minwidth100imp');
						$this->resprints.= '</td>';
					}
				} elseif (in_array('interventionlist', $currentcontext) && !empty($conf->global->MULTICOMPANY_INTERVENTION_SHARING_ENABLED) && !empty($mc->sharings['intervention'])) {
					//if (!empty($arrayfields['f.entity']['checked']))
					{
						// Entity
						$this->resprints = '<td class="liste_titre maxwidthonsmartphone" align="center">';
						$this->resprints.= $this->select_entities($search_entity,'search_entity','',false,false,true,explode(",", $mc->entities['intervention']),'','minwidth100imp');
						$this->resprints.= '</td>';
					}
				}
			}

			if (in_array('memberlist', $currentcontext) && !empty($conf->global->MULTICOMPANY_MEMBER_SHARING_ENABLED) && !empty($mc->sharings['member'])) {
			    //if (!empty($arrayfields['p.entity']['checked']))
			    {
			        // Entity
			        $this->resprints = '<td class="liste_titre maxwidthonsmartphone" align="center">';
			        $this->resprints.= $this->select_entities($search_entity,'search_entity','',false,false,true,explode(",", $mc->entities['member']),'','minwidth100imp');
			        $this->resprints.= '</td>';
			    }
			} elseif (in_array('productservicelist', $currentcontext) && !empty($conf->global->MULTICOMPANY_PRODUCT_SHARING_ENABLED) && !empty($mc->sharings['product'])){
				//if (!empty($arrayfields['p.entity']['checked']))
				{
					// Entity
					$this->resprints = '<td class="liste_titre maxwidthonsmartphone" align="center">';
					$this->resprints.= $this->select_entities($search_entity,'search_entity','',false,false,true,explode(",", $mc->entities['product']),'','minwidth100imp');
					$this->resprints.= '</td>';
				}
            } elseif (in_array('stocklist', $currentcontext) && !empty($conf->global->MULTICOMPANY_STOCK_SHARING_ENABLED) && !empty($mc->sharings['stock'])) {
                //if (!empty($arrayfields['p.entity']['checked']))
                {
                    // Entity
                    $this->resprints = '<td class="liste_titre maxwidthonsmartphone" align="center">';
                    $this->resprints.= $this->select_entities($search_entity,'search_entity','',false,false,true,explode(",", $mc->entities['stock']),'','minwidth100imp');
                    $this->resprints.= '</td>';
                }
            } elseif (in_array('projectlist', $currentcontext) && !empty($conf->global->MULTICOMPANY_PROJECT_SHARING_ENABLED) && !empty($mc->sharings['project'])) {
				//if (!empty($arrayfields['p.entity']['checked']))
				{
					// Entity
					$this->resprints = '<td class="liste_titre maxwidthonsmartphone" align="center">';
					$this->resprints.= $this->select_entities($search_entity,'search_entity','',false,false,true,explode(",", $mc->entities['project']),'','minwidth100imp');
					$this->resprints.= '</td>';
				}
			}
		}

		return 0;
	}

	/**
	 *
	 * @param boolean $parameters
	 * @return number
	 */
	public function printFieldListTitle($parameters=false)
	{
		global $conf;
		global $mc;

		if (empty($conf->multicompany->enabled)) {
			return 0;
		}

		if (is_array($parameters) && !empty($parameters)) {
			foreach($parameters as $key=>$value) {
				$$key=$value;
			}
		}

		$currentcontext = explode(':', $parameters['context']);

		if (!empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED)) {
			if (!empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_ENABLED) && !empty($mc->sharings['thirdparty'])) {
				if (in_array('thirdpartylist', $currentcontext) || in_array('contactlist', $currentcontext) || in_array('prospectlist', $currentcontext) || in_array('customerlist', $currentcontext) || in_array('supplierlist', $currentcontext) || in_array('poslist', $currentcontext)) {
					//if (!empty($arrayfields['s.entity']['checked']))
					{
						$this->resprints = getTitleFieldOfList('Entity',0,$_SERVER["PHP_SELF"],"s.entity","",$param,'align="center"',$sortfield,$sortorder);
					}
				} elseif (in_array('contactlist', $currentcontext)) {
					//if (!empty($arrayfields['p.entity']['checked']))
					{
						$this->resprints = getTitleFieldOfList('Entity',0,$_SERVER["PHP_SELF"],"p.entity","",$param,'align="center"',$sortfield,$sortorder);
					}
				} elseif (in_array('propallist', $currentcontext) && !empty($conf->global->MULTICOMPANY_PROPOSAL_SHARING_ENABLED) && !empty($mc->sharings['proposal'])) {
					//if (!empty($arrayfields['p.entity']['checked']))
					{
						$this->resprints = getTitleFieldOfList('Entity',0,$_SERVER["PHP_SELF"],"p.entity","",$param,'align="center"',$sortfield,$sortorder);
					}
				} elseif (in_array('orderlist', $currentcontext) && !empty($conf->global->MULTICOMPANY_ORDER_SHARING_ENABLED) && !empty($mc->sharings['order'])) {
					//if (!empty($arrayfields['p.entity']['checked']))
					{
						$this->resprints = getTitleFieldOfList('Entity',0,$_SERVER["PHP_SELF"],"c.entity","",$param,'align="center"',$sortfield,$sortorder);
					}
				} elseif (in_array('invoicelist', $currentcontext) && !empty($conf->global->MULTICOMPANY_INVOICE_SHARING_ENABLED) && !empty($mc->sharings['invoice'])) {
					//if (!empty($arrayfields['f.entity']['checked']))
					{
						$this->resprints = getTitleFieldOfList('Entity',0,$_SERVER["PHP_SELF"],"f.entity","",$param,'align="center"',$sortfield,$sortorder);
					}
				} elseif (in_array('interventionlist', $currentcontext) && !empty($conf->global->MULTICOMPANY_INTERVENTION_SHARING_ENABLED) && !empty($mc->sharings['intervention'])) {
					//if (!empty($arrayfields['f.entity']['checked']))
					{
						$this->resprints = getTitleFieldOfList('Entity',0,$_SERVER["PHP_SELF"],"f.entity","",$param,'align="center"',$sortfield,$sortorder);
					}
				}
			}

			if (in_array('memberlist', $currentcontext) && !empty($conf->global->MULTICOMPANY_MEMBER_SHARING_ENABLED) && !empty($mc->sharings['member'])) {
			    //if (!empty($arrayfields['p.entity']['checked']))
			    {
			        $this->resprints = getTitleFieldOfList('Entity',0,$_SERVER["PHP_SELF"],"p.entity","",$param,'align="center"',$sortfield,$sortorder);
			    }
			} elseif (in_array('productservicelist', $currentcontext) && !empty($conf->global->MULTICOMPANY_PRODUCT_SHARING_ENABLED) && !empty($mc->sharings['product'])) {
				//if (!empty($arrayfields['p.entity']['checked']))
				{
					$this->resprints = getTitleFieldOfList('Entity',0,$_SERVER["PHP_SELF"],"p.entity","",$param,'align="center"',$sortfield,$sortorder);
				}
			} elseif (in_array('stocklist', $currentcontext) && !empty($conf->global->MULTICOMPANY_STOCK_SHARING_ENABLED) && !empty($mc->sharings['stock'])) {
				//if (!empty($arrayfields['p.entity']['checked']))
				{
					$this->resprints = getTitleFieldOfList('Entity',0,$_SERVER["PHP_SELF"],"t.entity","",$param,'align="center"',$sortfield,$sortorder);
				}
			} elseif (in_array('projectlist', $currentcontext) && !empty($conf->global->MULTICOMPANY_PROJECT_SHARING_ENABLED) && !empty($mc->sharings['project'])) {
				//if (!empty($arrayfields['p.entity']['checked']))
				{
					$this->resprints = getTitleFieldOfList('Entity',0,$_SERVER["PHP_SELF"],"p.entity","",$param,'align="center"',$sortfield,$sortorder);
				}
			}
		}

        return 0;
	}

	/**
	 *
	 * @param boolean $parameters
	 * @return number
	 */
	public function printFieldListValue($parameters=false)
	{
		global $conf;
		global $totalarray;
		global $mc;

		if (empty($conf->multicompany->enabled)) {
			return 0;
		}

		if (is_array($parameters) && !empty($parameters)) {
			foreach($parameters as $key=>$value) {
				$$key=$value;
			}
		}

		$currentcontext = explode(':', $parameters['context']);

		$this->resprints = '';

		if (!empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED)) {
			if (!empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_ENABLED) && !empty($mc->sharings['thirdparty'])) {
				if (in_array('thirdpartylist', $currentcontext) || in_array('contactlist', $currentcontext) || in_array('prospectlist', $currentcontext) || in_array('customerlist', $currentcontext) || in_array('supplierlist', $currentcontext) || in_array('poslist', $currentcontext)) {
					$this->getInfo($obj->entity);
					//if (!empty($arrayfields['s.entity']['checked']) || !empty($arrayfields['p.entity']['checked']))
					{
						$this->resprints = '<td align="center">'."\n";
						$this->resprints.= '<div class="refidno multicompany-entity-card-container">';
						$this->resprints.= '<span class="fa fa-globe"></span><span class="multiselect-selected-title-text">'.$this->label.'</span>';
						$this->resprints.= "</div></td>\n";
					}
				} elseif (in_array('propallist', $currentcontext) && !empty($conf->global->MULTICOMPANY_PROPOSAL_SHARING_ENABLED) && !empty($mc->sharings['proposal'])) {
					$this->getInfo($obj->entity);
					//if (!empty($arrayfields['p.entity']['checked']))
					{
						$this->resprints = '<td align="center">'."\n";
						$this->resprints.= '<div class="refidno multicompany-entity-card-container">';
						$this->resprints.= '<span class="fa fa-globe"></span><span class="multiselect-selected-title-text">'.$this->label.'</span>';
						$this->resprints.= "</div></td>\n";
						if (empty($i)) {
							$totalarray['nbfield']++;
						}
					}
				} elseif (in_array('orderlist', $currentcontext) && !empty($conf->global->MULTICOMPANY_ORDER_SHARING_ENABLED) && !empty($mc->sharings['order'])) {
					$this->getInfo($obj->entity);
					//if (!empty($arrayfields['p.entity']['checked']))
					{
						$this->resprints = '<td align="center">'."\n";
						$this->resprints.= '<div class="refidno multicompany-entity-card-container">';
						$this->resprints.= '<span class="fa fa-globe"></span><span class="multiselect-selected-title-text">'.$this->label.'</span>';
						$this->resprints.= "</div></td>\n";
						if (empty($i)) {
							$totalarray['nbfield']++;
						}
					}
				} elseif (in_array('invoicelist', $currentcontext) && !empty($conf->global->MULTICOMPANY_INVOICE_SHARING_ENABLED) && !empty($mc->sharings['invoice'])) {
					$this->getInfo($obj->entity);
					//if (!empty($arrayfields['f.entity']['checked']))
					{
						$this->resprints = '<td align="center">'."\n";
						$this->resprints.= '<div class="refidno multicompany-entity-card-container">';
						$this->resprints.= '<span class="fa fa-globe"></span><span class="multiselect-selected-title-text">'.$this->label.'</span>';
						$this->resprints.= "</div></td>\n";
						if (empty($i)) {
							$totalarray['nbfield']++;
						}
					}
				} elseif (in_array('interventionlist', $currentcontext) && !empty($conf->global->MULTICOMPANY_INTERVENTION_SHARING_ENABLED) && !empty($mc->sharings['intervention'])) {
					$this->getInfo($obj->entity);
					//if (!empty($arrayfields['f.entity']['checked']))
					{
						$this->resprints = '<td align="center">'."\n";
						$this->resprints.= '<div class="refidno multicompany-entity-card-container">';
						$this->resprints.= '<span class="fa fa-globe"></span><span class="multiselect-selected-title-text">'.$this->label.'</span>';
						$this->resprints.= "</div></td>\n";
						if (empty($i)) {
							$totalarray['nbfield']++;
						}
					}
				}
			}

			if (in_array('productservicelist', $currentcontext) && !empty($conf->global->MULTICOMPANY_PRODUCT_SHARING_ENABLED) && !empty($mc->sharings['product'])) {
				$this->getInfo($obj->entity);
				//if (!empty($arrayfields['p.entity']['checked']))
				{
					$this->resprints = '<td align="center">';
					$this->resprints.= '<div class="refidno multicompany-entity-card-container">';
					$this->resprints.= '<span class="fa fa-globe"></span><span class="multiselect-selected-title-text">'.$this->label.'</span>';
					$this->resprints.= '</div></td>';
				}
			} elseif (in_array('stocklist', $currentcontext) && !empty($conf->global->MULTICOMPANY_STOCK_SHARING_ENABLED) && !empty($mc->sharings['stock'])) {
				$this->getInfo($obj->entity);
				//if (!empty($arrayfields['p.entity']['checked']))
				{
					$this->resprints = '<td align="center">';
					$this->resprints.= '<div class="refidno multicompany-entity-card-container">';
					$this->resprints.= '<span class="fa fa-globe"></span><span class="multiselect-selected-title-text">'.$this->label.'</span>';
					$this->resprints.= '</div></td>';
				}
			} elseif (in_array('memberlist', $currentcontext) && !empty($conf->global->MULTICOMPANY_MEMBER_SHARING_ENABLED) && !empty($mc->sharings['member'])) {
				$this->getInfo($obj->entity);
				//if (!empty($arrayfields['p.entity']['checked']))
				{
					$this->resprints = '<td align="center">';
					$this->resprints.= '<div class="refidno multicompany-entity-card-container">';
					$this->resprints.= '<span class="fa fa-globe"></span><span class="multiselect-selected-title-text">'.$this->label.'</span>';
					$this->resprints.= '</div></td>';
				}
			} elseif (in_array('projectlist', $currentcontext) && !empty($conf->global->MULTICOMPANY_PROJECT_SHARING_ENABLED) && !empty($mc->sharings['project'])) {
				$this->getInfo($obj->entity);
				//if (!empty($arrayfields['p.entity']['checked']))
				{
					$this->resprints = '<td align="center">';
					$this->resprints.= '<div class="refidno multicompany-entity-card-container">';
					$this->resprints.= '<span class="fa fa-globe"></span><span class="multiselect-selected-title-text">'.$this->label.'</span>';
					$this->resprints.= '</div></td>';
				}
			}
		}

		return 0;
	}

	/**
	 *
	 * @param boolean $parameters
	 * @return number
	 */
	public function selectThirdpartyListWhere($parameters=false)
	{
		global $conf;

		if (empty($conf->multicompany->enabled) && !empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_ENABLED)) {
			return 0;
		}

		if (is_array($parameters) && !empty($parameters)) {
			foreach($parameters as $key=>$value) {
				$$key=$value;
			}
		}

		$currentcontext = explode(':', $parameters['context']);

		$this->resprints = '';

		if (!empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_BYELEMENT_ENABLED)) {
			$this->getInstanceDao();
			$granularity = $this->dao->getListOfSharingsByElement('thirdparty', null, $conf->entity);
			if (is_array($granularity) && !empty($granularity)) {
				$method = 'IN';
				if (isSharingAllByDefault('thirdparty')) {
					$method = 'NOT IN';
				}
				$this->resprints.= " AND (s.rowid ".$method." (" . implode(",", array_keys($granularity)) . ") OR s.entity = " . $conf->entity . ")";
			}
		}

		return 0;
	}

	/**
	 *
	 * @param boolean $parameters
	 * @return number
	 */
	public function selectContactListWhere($parameters=false)
	{
		global $conf;

		if (empty($conf->multicompany->enabled) && !empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_ENABLED)) {
			return 0;
		}

		if (is_array($parameters) && !empty($parameters)) {
			foreach($parameters as $key=>$value) {
				$$key=$value;
			}
		}

		$currentcontext = explode(':', $parameters['context']);

		$this->resprints = '';

		if (!empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_BYELEMENT_ENABLED)) {
			$this->getInstanceDao();
			$granularity = $this->dao->getListOfSharingsByElement('contact', null, $conf->entity);
			if (is_array($granularity) && !empty($granularity)) {
				$method = 'IN';
				if (isSharingAllByDefault('contact')) {
					$method = 'NOT IN';
				}
				$this->resprints.= " AND (sp.rowid ".$method." (" . implode(",", array_keys($granularity)) . ") OR sp.entity = " . $conf->entity . ")";
			}
		}

		return 0;
	}

	/**
	 *
	 * @param boolean $parameters
	 * @return number
	 */
	public function selectCompaniesForNewContactListWhere($parameters=false)
	{
		global $conf;

		if (empty($conf->multicompany->enabled) && !empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_ENABLED)) {
			return 0;
		}

		if (is_array($parameters) && !empty($parameters)) {
			foreach($parameters as $key=>$value) {
				$$key=$value;
			}
		}

		$currentcontext = explode(':', $parameters['context']);

		$this->resprints = '';

		if (!empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_BYELEMENT_ENABLED)) {
			$this->getInstanceDao();
			$granularity = $this->dao->getListOfSharingsByElement('thirdparty', null, $conf->entity);
			if (is_array($granularity) && !empty($granularity)) {
				$method = 'IN';
				if (isSharingAllByDefault('thirdparty')) {
					$method = 'NOT IN';
				}
				$this->resprints.= " AND (s.rowid ".$method." (" . implode(",", array_keys($granularity)) . ") OR s.entity = " . $conf->entity . ")";
			}
		}

		return 0;
	}

	/**
	 *
	 * @param boolean $parameters
	 * @return number
	 */
	public function selectProductsListWhere($parameters=false)
	{
		global $conf;

		if (empty($conf->multicompany->enabled) && !empty($conf->global->MULTICOMPANY_PRODUCT_SHARING_ENABLED)) {
			return 0;
		}

		if (is_array($parameters) && !empty($parameters)) {
			foreach($parameters as $key=>$value) {
				$$key=$value;
			}
		}

		$currentcontext = explode(':', $parameters['context']);

		$this->resprints = '';

		if (!empty($conf->global->MULTICOMPANY_PRODUCT_SHARING_BYELEMENT_ENABLED)) {
			$this->getInstanceDao();
			$granularity = $this->dao->getListOfSharingsByElement('product', null, $conf->entity);
			if (is_array($granularity) && !empty($granularity)) {
				$method = 'IN';
				if (isSharingAllByDefault('product')) {
					$method = 'NOT IN';
				}
				$this->resprints.= " AND (p.rowid ".$method." (" . implode(",", array_keys($granularity)) . ") OR p.entity = " . $conf->entity . ")";
			}
		}

		return 0;
	}

	/**
	 *
	 * @param boolean $parameters
	 * @param unknown $object
	 * @param string $action
	 * @return number
	 */
	public function insertExtraHeader($parameters = false, &$object, &$action = '')
	{
		global $conf, $user, $langs;

		if (empty($conf->multicompany->enabled)) {
			return 0;
		}

		if (is_array($parameters) && !empty($parameters)) {
			foreach($parameters as $key => $value) {
				$$key = $value;
			}
		}

		$currentcontext = explode(':', $parameters['context']);

		if (!empty($user->admin) && empty($user->entity) && $conf->entity == 1 && !empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE)) {
			if (in_array('userperms', $currentcontext) || in_array('groupperms', $currentcontext)) {
				$this->getInstanceDao();

				if ($object->element == 'user') {
					//$aEntities=array_keys($permsgroupbyentity);

					// Check usergroup if user not in master entity
					//if (empty($aEntities) || !array_key_exists(1, $permsgroupbyentity)) {
						require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
						$aEntities = array();
						$group = new UserGroup($this->db);
						$ret = $group->listGroupsForUser($object->id, false);
						if (!empty($ret)) {
							foreach($ret as $data) {
								$aEntities = array_merge($aEntities, $data->usergroup_entity);
							}
							sort($aEntities);
						}
					//}

					if (!empty($aEntities)) {
						$entity = (GETPOST('entity', 'int') ? GETPOST('entity', 'int') : $aEntities[0]);
						$head = entity_prepare_head($object, $aEntities);
						$title = $langs->trans("Entities");
						dol_fiche_head($head, $entity, $title, 1, 'fa-sliders');
					} else {
						print get_htmloutput_mesg(img_warning('default') . ' ' . $langs->trans("ErrorLinkUserGroupEntity"), '', 'mc-upgrade-alert', 1);
					}
				} elseif ($object->element == 'usergroup') {
					$this->dao->getEntities();
					$aEntities = array();

					foreach ($this->dao->entities as $objEntity) {
						$aEntities[] = $objEntity->id;
					}

					$entity = (GETPOST('entity', 'int') ? GETPOST('entity', 'int') : $aEntities[0]);
					$head = entity_prepare_head($object, $aEntities);
					$title = $langs->trans("Entities");
					dol_fiche_head($head, $entity, $title, 1, 'fa-sliders');

					if (!empty($conf->global->MULTICOMPANY_TEMPLATE_MANAGEMENT)) {
						print '
							<div id="dialog-duplicate" title="'.$langs->trans('DuplicateRights').'" class="hideobject">
								<p>'.img_info().' '.$langs->transnoentities('DuplicateRightsInfo').'</p>
								<p>'.$langs->transnoentities('SelectRightsOfEntityToDuplicate').' '.$this->select_entities('', 'template', '', false, false, false, false, '', 'minwidth200imp', true, true).'</p>
								<p>'.$langs->transnoentities('SelectEntitiesToOverride').'</p>
								<p><div class="bootstrap-iso">
									<div class="row">
							           	<div class="col-sm-5">
							           		<div class="multiselect-selected-title"><span class="fa fa-globe"></span><span class="multiselect-selected-title-text">'.$langs->transnoentities("EntitiesSelected").'</span></div>
							           		'.$this->multiselectEntitiesToOverride('overrideentities', true).'
							           	</div>
										<div class="col-xs-2 multiselect-menu">
											<!-- <button type="button" id="multiselect_overrideentities_undo" class="btn btn-primary btn-block">'.$langs->trans("Undo").'</button> -->
											<button type="button" id="multiselect_overrideentities_leftAll" class="btn btn-block multiselect-menu-btn-color"><i class="glyphicon glyphicon-backward"></i></button>
											<button type="button" id="multiselect_overrideentities_leftSelected" class="btn btn-block multiselect-menu-btn-color"><i class="glyphicon glyphicon-chevron-left"></i></button>
											<button type="button" id="multiselect_overrideentities_rightSelected" class="btn btn-block multiselect-menu-btn-color"><i class="glyphicon glyphicon-chevron-right"></i></button>
											<button type="button" id="multiselect_overrideentities_rightAll" class="btn btn-block multiselect-menu-btn-color"><i class="glyphicon glyphicon-forward"></i></button>
											<!-- <button type="button" id="multiselect_overrideentities_redo" class="btn btn-warning btn-block">'.$langs->trans("Redo").'</button> -->
										</div>
										<div class="col-xs-5">
											<div class="multiselect-available-title"><span class="fa fa-globe"></span><span class="multiselect-available-title-text">'.$langs->transnoentities("EntitiesAvailable").'</span></div>
											'.$this->multiselectEntitiesToOverride('overrideentities', false).'
										</div>
									</div>
								</div></p>
							</div>
							<script type="text/javascript">
							(function(){
								var newlink = document.createElement("link");
								newlink.type = "text/css";
								newlink.rel = "stylesheet";
								newlink.href = "'.dol_buildpath("/multicompany/inc/multiselect/css/bootstrap-iso.min.css", 1).'";
			  					(document.getElementsByTagName("head")[0]||document.getElementsByTagName("body")[0]).appendChild(newlink);
								var newscript = document.createElement("script");
								newscript.type = "text/javascript";
								newscript.async = true;
								newscript.src = "'.dol_buildpath("/multicompany/inc/multiselect/js/multiselect.min.js", 1).'";
			  					(document.getElementsByTagName("head")[0]||document.getElementsByTagName("body")[0]).appendChild(newscript);
							})();
							$(document).ready(function() {
								$( "span.multicompany-button-clonerights" ).parent().css( "background", "none" ).css( "border", "none" );
								$( "span.multicompany-button-clonerights" ).parent().parent().css( "float", "right" ).css( "padding-top", "15px" );
								$("#clonerights").on("click", function() {
									$("#dialog-duplicate").dialog({
										resizable: false,
										height: 450,
										width: 700,
										modal: true,
										open: function() {
											$(".ui-dialog-buttonset > button:first").button("disable");
											$(".ui-dialog-buttonset > button:last").focus();
										},
										buttons: {
											"'.$langs->transnoentities('Validate').'": function() {
												$(this).dialog("close");
												var selections = [];
												$("#multiselect_overrideentities_to option").each(function() {
													selections.push( $(this).val() );
												});
												//console.log(selections);
												$.post( "'.dol_buildpath('/multicompany/core/ajax/functions.php',1).'", {
													"action": "duplicateUserGroupRights",
													"id": "'.$object->id.'",
													"template": $("#template").val(),
													"entities": JSON.stringify(selections),
                                                    "token": "'.currentToken().'"
													},
													function (result) {
														if (result.status == "success") {
															$.jnotify("'.$langs->transnoentities("ConfirmedDuplicateRights").'", "ok");
														} else {
															$.jnotify("'.$langs->transnoentities("ErrorDuplicateRights").'", "error", true);
														}
													}
												);
											},
											"'.$langs->transnoentities('Cancel').'": function() {
												$(this).dialog("close");
											}
										}
									});
								});
								$("#multiselect_overrideentities").multiselect({
									keepRenderingSort: true,
									right: "#multiselect_to_overrideentities",
							        rightAll: "#multiselect_overrideentities_leftAll",
							        rightSelected: "#multiselect_overrideentities_leftSelected",
							        leftSelected: "#multiselect_overrideentities_rightSelected",
							        leftAll: "#multiselect_overrideentities_rightAll",
							        search: {
							            left: \'<input type="text" name="q" class="form-control" placeholder="'.$langs->transnoentities("Search")."...".'" />\',
							            right: \'<input type="text" name="q" class="form-control" placeholder="'.$langs->transnoentities("Search")."...".'" />\',
							        },
							        fireSearch: function(value) {
							            return value.length > 2;
							        },
							        afterMoveToRight: function($left, $right, $options) {
										$(".ui-dialog-buttonset > button:first").button("enable");
										$("#multiselect_overrideentities").html( $("#multiselect_overrideentities option").sort(function(x, y) {
								            return $(x).val() < $(y).val() ? -1 : 1;
								        }));
									},
									afterMoveToLeft: function($left, $right, $options) {
										if ($("#multiselect_overrideentities_to option").length == 0) {
											$(".ui-dialog-buttonset > button:first").button("disable");
										}
										$("#multiselect_overrideentities").html( $("#multiselect_overrideentities option").sort(function(x, y) {
								            return $(x).val() < $(y).val() ? -1 : 1;
								        }));
									}
								});
							});
							</script>
						';
					}
				}

				// Check if advanced perms is enabled for current object entity
				$res = $this->dao->getEntityConfig($entity, 'MAIN_USE_ADVANCED_PERMS');
				if (empty($res['MAIN_USE_ADVANCED_PERMS'])) {
					unset($conf->global->MAIN_USE_ADVANCED_PERMS);
				}
			}
		}

		return 0;
	}

	/**
	 *
	 * @param boolean $parameters
	 * @param unknown $object
	 * @param string $action
	 * @return number
	 */
	public function insertExtraFooter($parameters=false, &$object, &$action='')
	{
		global $conf, $user;

		if (empty($conf->multicompany->enabled)) {
			return 0;
		}

		if (is_array($parameters) && !empty($parameters)) {
			foreach($parameters as $key=>$value) {
				$$key=$value;
			}
		}

		$currentcontext = explode(':', $parameters['context']);

		if (!empty($user->admin) && empty($user->entity) && $conf->entity == 1 && !empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE)) {
			if (in_array('userperms', $currentcontext) || in_array('groupperms', $currentcontext)) {
				// Restore advanced perms if enabled for current entity
				$this->getInstanceDao();
				$res = $this->dao->getEntityConfig($conf->entity, 'MAIN_USE_ADVANCED_PERMS');
				if (!empty($res['MAIN_USE_ADVANCED_PERMS'])) {
					$conf->global->MAIN_USE_ADVANCED_PERMS = $res['MAIN_USE_ADVANCED_PERMS'];
				}
			}
		}

		return 0;
	}

	/**
	 *	Return combo list of entities.
	 *
	 *	@param	int		$selected	Preselected entity
	 *	@param	int		$htmlname	Name
	 *	@param	string	$option		Option
	 *	@param	boolean	$login		If use in login page or not
	 *  @param	array	$exclude	Exclude
	 *  @param	boolean	$emptyvalue Emptyvalue
	 *  @param	boolean	$only		Only
	 *  @param	string	$all		Add 'All entities' value in combo list
	 *  @param	string	$cssclass	specific css class. eg 'minwidth150imp mycssclass'
	 *  @param	bool	$ajax		Enable ajax combobox
	 *  @param	bool	$template	Show template of entities
	 *	@return	string
	 */
	public function select_entities($selected = '', $htmlname = 'entity', $option = '', $login = false, $exclude = false, $emptyvalue = false, $only = false, $all = '', $cssclass = 'minwidth150imp', $ajax = true, $template = false)
	{
		global $conf, $user, $langs;

		$this->getInstanceDao();

		$this->dao->getEntities($login, $exclude);

		$out='';
		$entities=array();

		foreach ($this->dao->entities as $entity) {
			if (empty($conf->global->MULTICOMPANY_TEMPLATE_MANAGEMENT) && $entity->visible == 2) {
				continue;
			}
			if ($template === 'only' && $entity->visible != 2) {
				continue;
			}
			if (empty($template) && $entity->visible == 2) {
				continue;
			}
			if (empty($entity->active)) {
				continue;
			}
		    $entities[] = $entity;
		}

		if (!empty($entities)) {
			$out.= '<select class="flat maxwidth200 multicompany_select '.$cssclass.'" id="'.$htmlname.'" name="'.$htmlname.'"'.$option.'>';

			if ($emptyvalue) {
				$out.= '<option value="-1">&nbsp;</option>';
			}
			if ($all) {
				$out.= '<option value="0">'.$langs->trans("AllEntities").'</option>';
			}

			foreach ($entities as $entity) {
				if ($entity->active == 1 && ($entity->visible == 1 || (!empty($user->admin) && empty($user->entity)))) {
					if (is_array($only) && !empty($only) && !in_array($entity->id, $only)) {
						continue;
					}
					if (!empty($user->login) && !empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE)
						&& !empty($user->entity) && $this->checkRight($user->id, $entity->id) < 0) {
						continue;
					}

					$out.= '<option value="'.$entity->id.'"';
					if ($selected == $entity->id) {
						$out.= ' selected="selected"';
					}
					$out.= '>';
					$out.= dol_htmlentities($entity->label);
					if (empty($entity->visible)) {
						$out.= ' ('.$langs->trans('Hidden').')';
					} elseif ($entity->visible == 2) {
						$out.= ' ('.$langs->trans('Template').')';
					}
					$out.= '</option>';
				}
			}

			$out.= '</select>';

			// Make select dynamic
			if ($ajax) {
				include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
				$out.= ajax_combobox($htmlname);
			}
		}

		return $out;
	}

	/**
	 *	Return multiselect list of entities.
	 *
	 *	@param	string			$htmlname			Name of select
	 *	@param	DaoMulticompany	$current			Current entity to manage
	 *	@param	bool			$onlyselected		true: show only selected, false: hide only selected
	 *	@return	string
	 */
	public function multiselect_entities($htmlname, $current, $onlyselected=false)
	{
		global $langs;

		$this->getInstanceDao();
		$this->dao->getEntities();

		$selectname = ($onlyselected ? $htmlname.'_to[]' : 'from[]');
		$selectid = ($onlyselected ? 'multiselect_shared_'.$htmlname.'_to' : 'multiselect_shared_'.$htmlname);

		$return = '<select name="'.$selectname.'" id="'.$selectid.'" class="form-control multiselect-select multiselect-min-height" size="6" multiple="multiple">';
		if (is_array($this->dao->entities)) {
			foreach ($this->dao->entities as $entity) {
				if ($entity->visible == 2) continue;
				if (is_object($current) && $current->id != $entity->id && $entity->active == 1) {
				    $sharings = ((!empty($current->options['sharings'][$htmlname]) && is_array($current->options['sharings'][$htmlname])) ? $current->options['sharings'][$htmlname] : null);
				    if ((empty($onlyselected) && (empty($sharings) || ! in_array($entity->id, $sharings)))	// All unselected
				    	|| ($onlyselected && !empty($sharings) && in_array($entity->id, $sharings))) {	// All selected
						$return.= '<option class="oddeven multiselect-option" value="'.$entity->id.'">';
						$return.= $entity->label;
						if (empty($entity->visible)) {
							$return.= ' ('.$langs->trans('Hidden').')';
						}
						$return.= '</option>';
					}
				}
			}
		}
		$return.= '</select>';

		return $return;
	}

	/**
	 *	Return form for ganularity of sharings by element.
	 *
	 *  @param	object	$object				Object of element
	 *  @param	string	$element			To force element name for compatibility
	 *  @param	string	$parentelement		To force element name to check sharings of parents element (eg thirdparty for contact element)
	 *  @param	string	$parentelementid	Id of parents element to preload shared values
	 *	@return string
	 */
	public function formSelectSharingByElement($object, $element = null, $parentelement = null, $parentelementid = null)
	{
		global $conf, $langs;

		$selected = null;
		$element = (empty($element) ? $object->element : $element);
		$elementid = (!empty($object->id) ? $object->id : null);
		$action = GETPOST('action', 'alpha');
		$massaction = GETPOST('massaction', 'alpha');

		// To avoid loose preselected values during create and edit
		if ($action == 'add' || $action == 'update' || $action == 'create' || $massaction == 'sharebyelement') {
			$preselected = array();
			if (($action == 'create' && isSharingAllByDefault($element)) || $massaction == 'sharebyelement') {
				$allentities = new DaoMulticompany($this->db);
				$allentities->getEntities(true, array($conf->entity), true);
				$newelement = (!empty($parentelement) ? $parentelement : $element);
				foreach ($allentities->entities as $otherentity) {
					if (isset($otherentity->options['sharings'][$newelement]) && in_array($conf->entity, $otherentity->options['sharings'][$newelement])) {
						$preselected[] = $otherentity->id;
					}
				}
				sort($preselected);
			}
			$selected = (GETPOSTISSET($parentelement.'_to') ? GETPOST($parentelement.'_to', 'array', 2) : (GETPOSTISSET($element.'_to') ? GETPOST($element.'_to', 'array', 2) : $preselected));
		}

		$out = '<div class="bootstrap-iso"><div class="row">'."\n";

		$out.= '<div class="col-sm-5">'."\n";
		$out.= '<div class="multiselect-selected-title"><span class="fa fa-globe"></span>'."\n";
		$out.= '<span class="multiselect-selected-title-text">'.$langs->trans("EntitiesSelected").'</span></div>'."\n";
		$out.= $this->multiselectEntitiesForGranularity($element, $elementid, true, $parentelement, $parentelementid, $selected);
		$out.= '</div>'."\n";

		$out.= '<div class="col-xs-2 multiselect-menu">'."\n";
		$out.= '<button type="button" id="multiselect_shared_'.$element.'_undo" class="btn btn-primary btn-block">'.$langs->trans("Undo").'</button>'."\n";
		$out.= '<button type="button" id="multiselect_shared_'.$element.'_leftAll" class="btn btn-block multiselect-menu-btn-color"><i class="glyphicon glyphicon-backward"></i></button>'."\n";
		$out.= '<button type="button" id="multiselect_shared_'.$element.'_leftSelected" class="btn btn-block multiselect-menu-btn-color"><i class="glyphicon glyphicon-chevron-left"></i></button>'."\n";
		$out.= '<button type="button" id="multiselect_shared_'.$element.'_rightSelected" class="btn btn-block multiselect-menu-btn-color"><i class="glyphicon glyphicon-chevron-right"></i></button>'."\n";
		$out.= '<button type="button" id="multiselect_shared_'.$element.'_rightAll" class="btn btn-block multiselect-menu-btn-color"><i class="glyphicon glyphicon-forward"></i></button>'."\n";
		$out.= '<button type="button" id="multiselect_shared_'.$element.'_redo" class="btn btn-warning btn-block">'.$langs->trans("Redo").'</button>'."\n";
		$out.= '</div>'."\n";

		$out.= '<div class="col-xs-5">'."\n";
		$out.= '<div class="multiselect-available-title"><span class="fa fa-globe"></span>'."\n";
		$out.= '<span class="multiselect-available-title-text">'.$langs->trans("EntitiesAvailable").'</span></div>'."\n";
		$out.= $this->multiselectEntitiesForGranularity($element, $elementid, false, $parentelement, $parentelementid, $selected);
		$out.= '</div></div></div>'."\n";

		if (!empty($elementid)) {
			$this->getSharingsByElement($element, $elementid);	// Populate $this->config['sharedwith']
			if (!empty($this->config['sharedwith'][$element][$elementid])) {
				$out.= '<input type="hidden" name="oldsharedwith" value="' . base64_encode(json_encode($this->config['sharedwith'][$element][$elementid])) . '">';
			}
		}

		$conf->global->MAIN_HTML_FOOTER = (!empty($conf->global->MAIN_HTML_FOOTER) ? $conf->global->MAIN_HTML_FOOTER : '');
		$conf->global->MAIN_HTML_FOOTER .= '<link rel="stylesheet" type="text/css" href="'.dol_buildpath("/multicompany/inc/multiselect/css/bootstrap-iso.min.css", 1).'">'."\n";
		$conf->global->MAIN_HTML_FOOTER .= '<script src="'.dol_buildpath("/multicompany/inc/multiselect/js/multiselect.min.js", 1).'"></script>'."\n";

		$out.='
			<script type="text/javascript">
			$(document).ready(function () {
				$("#multiselect_shared_'.$element.'").multiselect({
					keepRenderingSort: true,
					right: "#multiselect_to_'.$element.'",
			        rightAll: "#multiselect_shared_'.$element.'_leftAll",
			        rightSelected: "#multiselect_shared_'.$element.'_leftSelected",
			        leftSelected: "#multiselect_shared_'.$element.'_rightSelected",
			        leftAll: "#multiselect_shared_'.$element.'_rightAll",
			        search: {
			            left: \'<input type="text" name="q" class="form-control" placeholder="'.$langs->trans("Search").'..." />\',
			            right: \'<input type="text" name="q" class="form-control" placeholder="'.$langs->trans("Search").'..." />\',
			        },
			        fireSearch: function(value) {
			            return value.length > 2;
			        },
			';
		// ask if update sharing of contacts
		if (!empty($elementid) && $element == 'thirdparty' && $action == 'update') { // ask if update sharing of contacts
			$out.='
					beforeMoveToRight: function($left, $right, $options) {
						return true;
					},
				';
		}
		$out.='
					afterMoveToRight: function($left, $right, $options) {
				    	$("#multiselect_shared_'.$element.'_to").html($("#multiselect_shared_'.$element.'_to option").sort(function(x, y) {
				            return $(x).val() < $(y).val() ? -1 : 1;
				        }));
					},
			';
		// "contact" element have no child tables
		if (!empty($elementid) && $element != 'contact') {
				$out.='
					beforeMoveToLeft: function($left, $right, $options) {
						var entities = [];
						var bool = false;
						$.each($options, function( key, entity ) {
							entities.push(entity.value);
						});
						//console.log(entities);
						$.ajax({
							type: "POST",
							async: false,
							url: "'.dol_buildpath('/multicompany/core/ajax/functions.php', 1).'",
							data: {
								"action": "checkIfElementIsUsed",
								"id": "'.$elementid.'",
								"element": "'.$element.'",
								"entities": JSON.stringify(entities),
                            	"token": "'.currentToken().'"
        					},
							success: function(result) {
								if (result.status == "success") {
									bool = true;
									if (result.element == "thirdparty") {
										$.jnotify("'.$langs->trans("InfoRemoveThisShareFromContacts").'", "ok");
									}
								} else {
									$.jnotify(result.error, "error", true);
								}
							}
						});
						return bool;
					},
				';
			}
			$out.='
					afterMoveToLeft: function($left, $right, $options) {
						$("#multiselect_shared_'.$element.'").html($("#multiselect_shared_'.$element.' option").sort(function(x, y) {
				            return $(x).val() < $(y).val() ? -1 : 1;
				        }));
					}
				});
			});
			</script>
		'."\n";

		return $out;
	}

	/**
	 *	Return multiselect list of entities for ganularity of sharings by element.
	 *
	 *  @param	string	$element			Name of element (thirdparty, product, ...)
	 *  @param	int		$elementid			Id of current element for sharing granularity
	 *	@param	bool	$onlyselected		true: show only selected, false: hide only selected
	 *	@param	string	$parentelement		To force element name to check sharings of parents element (eg thirdparty for contact element)
	 *	@param	string	$parentelementid	Id of parents element to preload shared values
	 *	@param	array	$selected			For avoid loose selected values during create/update
	 *	@return	string
	 */
	public function multiselectEntitiesForGranularity($element, $elementid = null, $onlyselected = false, $parentelement = null, $parentelementid = null, $selected = null)
	{
		global $conf, $langs;

		$parentelement = (empty($parentelement) ? $element : $parentelement);

		$currententity = new self($this->db);
		$currententity->getInfo($conf->entity);

		$this->getInstanceDao();
		$this->dao->getEntities(false, array($currententity->id), true);

		if (!empty($parentelementid) && $parentelement == 'thirdparty') {
			$parentgranularity = $this->dao->getListOfSharingsByElement('thirdparty', $parentelementid);
		}

		$granularity = array();
		if (is_null($selected)) {
			if (!empty($elementid)) {
				$granularity = $this->dao->getListOfSharingsByElement($element, $elementid);
			} elseif (!empty($parentelementid) && $parentelement == 'thirdparty') {
				$elementid = $parentelementid;
				$granularity = $parentgranularity;
			}
		} else {
			$elementid = (empty($elementid) ? 0 : $elementid);
			if (!empty($onlyselected) && !empty($selected)) {
				foreach ($selected as $shareid) {
					$granularity[$elementid][] = $shareid;
				}
			}
		}

		$selectname = ($onlyselected ? $element.'_to[]' : 'from[]');
		$selectid = ($onlyselected ? 'multiselect_shared_'.$element.'_to' : 'multiselect_shared_'.$element);

		$return = '<select name="'.$selectname.'" id="'.$selectid.'" class="form-control multiselect-select multiselect-byelement-min-height" size="6" multiple="multiple">';
		if (is_array($this->dao->entities)) {
			foreach ($this->dao->entities as $otherentity) {
				if ($otherentity->visible == 2) {
					continue;	// hide templates of entity
				}
				if ($currententity->id == $otherentity->id) {
					continue;	// hide current entity
				}
				$sharings = ((!empty($otherentity->options['sharings'][$parentelement]) && is_array($otherentity->options['sharings'][$parentelement])) ? $otherentity->options['sharings'][$parentelement] : array());
				if (!in_array($currententity->id, $sharings)) {
					continue; // Only if current entity share element
				}
				if (empty($onlyselected) && !empty($parentelementid) && $parentelement == 'thirdparty' && !empty($parentgranularity[$parentelementid]) && !in_array($otherentity->id, $parentgranularity[$parentelementid])) {
					continue; // Hide entity if not shared by parents element
				}

				if (empty($onlyselected) && (empty($granularity) || !in_array($otherentity->id, $granularity[$elementid]))	// All unselected
					|| (!empty($onlyselected) && (!empty($granularity) && in_array($otherentity->id, $granularity[$elementid])))) {	// All selected
						$return.= '<option class="oddeven multiselect-option" value="'.$otherentity->id.'">';
						$return.= $otherentity->label;
						if (empty($otherentity->visible)) {
							$return.= ' ('.$langs->trans('Hidden').')';
						}
						$return.= '</option>';
				}
			}
		}
		$return.= '</select>';

		return $return;
	}

	/**
	 *	Check if element is shared globally at least once between entities
	 *
	 *	@param	string	$element	Name of element (thirdparty, product, ...)
	 *	return bool
	 */
	private function checkIfElementIsSharedByEntity($element)
	{
		if (!empty($this->config['validforsharing'][$element]) && $this->config['validforsharing'][$element] === true) {
			return true;
		}

		global $conf;

		$this->getInstanceDao();
		$this->dao->getEntities(false, array($conf->entity), true);

		$validforsharing = false;

		if (is_array($this->dao->entities) && !empty($this->dao->entities)) {
			foreach ($this->dao->entities as $otherentity) {
				if ($otherentity->visible == 2) {
					continue;	// hide templates of entity
				}
				if ($conf->entity == $otherentity->id) {
					continue;	// hide current entity
				}

				if (!empty($otherentity->options['sharings'])) {
					$sharings = ((isset($otherentity->options['sharings'][$element]) && is_array($otherentity->options['sharings'][$element]) && !empty($otherentity->options['sharings'][$element])) ? $otherentity->options['sharings'][$element] : array());
					if (!empty($sharings) && in_array($conf->entity, $sharings)) { // Only if current entity sharing element with at least one entity
						$this->config['validforsharing'][$element] = true;
						$validforsharing = true;
						break;
					}
				}
			}
		}

		return $validforsharing;
	}

	/**
	 *	Return multiselect list of entities to override.
	 *
	 *	@param	string			$htmlname			Name of select
	 *	@param	bool			$onlyselected		true: show only selected, false: hide only selected
	 *	@return	string
	 */
	public function multiselectEntitiesToOverride($htmlname, $onlyselected=false)
	{
		global $langs;

		$this->getInstanceDao();
		$this->dao->getEntities();

		$selectname = ($onlyselected ? $htmlname.'_to[]' : 'from[]');
		$selectid = ($onlyselected ? 'multiselect_'.$htmlname.'_to' : 'multiselect_'.$htmlname);

		$out = '<select name="'.$selectname.'" id="'.$selectid.'" class="form-control multiselect-select multiselect-min-height" size="6" multiple="multiple">';
		if (empty($onlyselected) && is_array($this->dao->entities)) {
			foreach ($this->dao->entities as $entity) {
				if ($entity->active == 1) {
					$out.= '<option class="oddeven multiselect-option" value="'.$entity->id.'">';
					$out.= $entity->label;
					if (empty($entity->visible)) {
						$out.= ' ('.$langs->trans('Hidden').')';
					} elseif ($entity->visible == 2) {
						$out.= ' ('.$langs->trans('Template').')';
					}
					$out.= '</option>';
				}
			}
		}
		$out.= '</select>';

		return $out;
	}

	/**
	 *	Return multiselect list of entities.
	 *
	 *	@param	string	$htmlname	Name of select
	 *	@param	array	$selected	Entities already selected
	 *	@param	string	$option		Option
	 *	@return	string
	 */
	public function multiSelectEntities($htmlname, $selected=null, $option=null)
	{
		global $langs;

		$this->getInstanceDao();
		$this->dao->getEntities();

		$return = '<select id="'.$htmlname.'" class="multiselect" multiple="multiple" name="'.$htmlname.'[]" '.$option.'>';
		if (is_array($this->dao->entities)) {
			foreach ($this->dao->entities as $entity) {
				if ($entity->visible == 2) {
					continue;
				}
				$return.= '<option value="'.$entity->id.'" ';
				if (is_array($selected) && in_array($entity->id, $selected)) {
					$return.= 'selected="selected"';
				}
				$return.= '>';
				$return.= $entity->label;
				if (empty($entity->visible)) {
					$return.= ' ('.$langs->trans('Hidden').')';
				}
				$return.= '</option>';
			}
		}
		$return.= '</select>';

		return $return;
	}

	/**
	 *    Switch to another entity.
	 *
	 *    @param int $id        User id
	 *    @param int $entity    Entity id
	 *    @return int
	 */
	public function checkRight($id, $entity)
	{
		global $user;

		$this->getInstanceDao();

		if ($this->dao->fetch($entity) > 0) {
			// Controle des droits sur le changement
			if ($this->dao->verifyRight($entity, $id) || !empty($user->admin)) {
				return 1;
			} else {
				return -2;
			}
		} else {
			return -1;
		}
	}

	/**
	 *    Switch to another entity.
	 *    @param    int $id Id of the destination entity
	 *    @param    int $userid
	 *    @return int
	 */
	public function switchEntity($id, $userid=null)
	{
		global $conf, $user;

		$this->getInstanceDao();

		if (!empty($userid)) {
			$user=new User($this->db);
			$user->fetch($userid);
		}

		if ($this->dao->fetch($id) > 0 && !empty($this->dao->active)) { // check if the entity is still active
			// Controle des droits sur le changement
			if (!empty($conf->global->MULTICOMPANY_HIDE_LOGIN_COMBOBOX)
			|| (!empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE) && $this->dao->verifyRight($id, $user->id))
			|| !empty($user->admin)) {
				$_SESSION['dol_entity'] = $id;
				//$conf = new Conf(); FIXME some constants disappear
				$conf->entity = $id;
				$conf->setValues($this->db);
				return 1;
			} else {
				//var_dump($conf->global->MULTICOMPANY_HIDE_LOGIN_COMBOBOX);
				//var_dump($conf->global->MULTICOMPANY_TRANSVERSE_MODE);
				//var_dump($this->dao->verifyRight($id, $user->id));
				return -2;
			}
		} else {
			return -1;
		}
	}

	/**
	 * 	Get entity info
	 * 	@param	int		$id			Entity id
	 *  @param	boolean	$getconfig	Get entity config (constants) true or false
	 *  @param	string	$constname	Get specific contant
	 */
	public function getInfo($id, $getconfig = false, $constname = null, $url = null)
	{
		$this->getInstanceDao();
		$this->dao->fetch($id, $url);

		$this->id				= $this->dao->id;
		$this->label			= $this->dao->label;
		$this->url				= $this->dao->url;
		$this->country_id		= $this->dao->country_id;
		$this->country_code		= $this->dao->country_code;
		$this->currency_code	= $this->dao->currency_code;
		$this->language_code	= $this->dao->language_code;
		$this->description		= $this->dao->description;
		$this->options			= $this->dao->options;
		$this->active			= $this->dao->active;
		$this->visible			= $this->dao->visible;
		$this->constants		= ($getconfig === true ? $this->dao->getEntityConfig($id, $constname) : array());
	}

	/**
	 * 	Get sharings by element
	 *
	 * 	@param	string	$element			Name of element (thirdparty, product, ...)
	 *  @param	int		$elementid			Id of current element for check sharing granularity
	 */
	public function getSharingsByElement($element, $elementid = null, $checkaccess = false)
	{
		$this->getInstanceDao();

		$entities = $this->dao->getListOfSharingsByElement($element, $elementid, null, $checkaccess);
		if (is_array($entities)) {
			$this->config['sharedwith'][$element] = $entities;
		}
	}

	/**
	 *    Get action title
	 *    @param string $action Type of action
	 *    @return string
	 */
	public function getTitle($action='')
	{
		global $langs;

		if ($action == 'create') {
			return $langs->trans("AddEntity");
		} elseif ($action == 'edit') {
			return $langs->trans("EditEntity");
		} else {
			return $langs->trans("EntitiesManagement");
		}
	}

	/**
	 *    Assigne les valeurs pour les templates
	 *    @param string $action     Type of action
	 *    @deprecated
	 */
	public function assign_values($action = 'view')
	{
		$this->assignTemplateValues($action);
	}

	/**
	 *    Assigne les valeurs pour les templates
	 *    @param string $action     Type of action
	 */
	public function assignTemplateValues($action='view')
	{
		global $conf, $langs, $user;
		global $form, $formcompany, $formadmin;

		require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';

		$this->tpl['extrafields'] = new ExtraFields($this->db);
		// fetch optionals attributes and labels
		$this->tpl['extralabels'] = $this->tpl['extrafields']->fetch_name_optionals_label('entity');

		$this->getInstanceDao();

		$this->template_dir = dol_buildpath('/multicompany/admin/tpl/');
		$this->template = 'list.tpl.php';

		if ($action == 'create' || $action == 'edit') {
			$this->template = 'card.tpl.php';

			if ($action == 'edit' && GETPOSTISSET('id')) {
				$ret = $this->dao->fetch(GETPOST('id', 'int'));
			}

			if (!empty($conf->global->MULTICOMPANY_TEMPLATE_MANAGEMENT)) {
				$templatevalue = (GETPOSTISSET('template') ? GETPOST('template', 'int') : ($this->dao->visible === '2' ? 1 : 0));

				if ($action == 'create') {
					$this->tpl['template'] = $form->selectyesno('template', $templatevalue, 1);
					$this->tpl['select_template'] = $this->select_entities('','usetemplate','',false,false,true,'','','minwidth200imp',true,'only');
				} elseif ($templatevalue === 1) {
					$this->tpl['template'] = $templatevalue;
				}
			}

			// action
			$this->tpl['action'] = $action;

			// id
			$this->tpl['id'] = (GETPOSTISSET('id') ? GETPOSTINT('id') : null);

			// Label
			$this->tpl['label'] = (GETPOSTISSET('label') ? GETPOST('label', 'alphanohtml') : $this->dao->label);

			// Description
			$description = (GETPOSTISSET('description') ? GETPOST('description', 'alphanohtml') : (!empty($this->dao->description) ? $this->dao->description : ''));
			$this->tpl['description'] = $description;

			// URL
			if (!empty($conf->global->MULTICOMPANY_OPEN_NEW_SESSION_AFTER_SWITCHING)) {
				$url = (GETPOSTISSET('url') ? trim(GETPOST('url', 'custom', 0, FILTER_SANITIZE_URL)) : (!empty($this->dao->url) ? $this->dao->url : ''));
				$this->tpl['url'] = $url;
			}

			// Company name
			$this->tpl['name'] = (GETPOSTISSET('name') ? GETPOST('name', 'alphanohtml') : $this->dao->name);

			// Address
			$address = (GETPOSTISSET('address') ? GETPOST('address', 'alphanohtml') : (!empty($this->dao->address) ? $this->dao->address : ''));
			$this->tpl['address'] = $address;

			// Zip
			$zipcode = (GETPOSTISSET('zipcode') ? GETPOST('zipcode', 'alphanohtml') : (!empty($this->dao->zip) ? $this->dao->zip : ''));
			$this->tpl['select_zip'] = $formcompany->select_ziptown($zipcode, 'zipcode', array('town', 'selectcountry_id', 'departement_id'), 6);

            // Town
			$town = (GETPOSTISSET('town') ? GETPOST('town', 'alphanohtml') : (!empty($this->dao->town) ? $this->dao->town : ''));
			$this->tpl['select_town'] = $formcompany->select_ziptown($town, 'town', array('zipcode', 'selectcountry_id', 'departement_id'), 40);

            if (!empty($user->admin)) $this->tpl['info_admin'] = info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);

            // Option to add possibility to clone accounting account from another entity
            if (!empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED) && !empty($conf->accounting->enabled)) {
            	$cloneaccountingaccount = (GETPOSTISSET('cloneaccountingaccount') ? GETPOSTINT('cloneaccountingaccount') : (empty($this->dao->options['cloneaccountingaccount']) ? '' : $this->dao->options['cloneaccountingaccount']));
            	$this->tpl['cloneaccountingaccount'] = $form->selectyesno('cloneaccountingaccount', $cloneaccountingaccount, 1);
            }


			// We define country_id
			if (GETPOSTISSET('country_id')) {
				$country_id = GETPOSTINT('country_id');
			} elseif (!empty($this->dao->country_id)) {
				$country_id = $this->dao->country_id;
			} else if (!empty($conf->global->MAIN_INFO_SOCIETE_COUNTRY)) {
				$tmp = explode(':', $conf->global->MAIN_INFO_SOCIETE_COUNTRY);
				$country_id = $tmp[0];
			} else {
				$country_id = 0;
			}

			$this->tpl['select_country']	= $form->select_country($country_id,'country_id');
			$state_id = (GETPOSTISSET('departement_id') ? GETPOSTINT('departement_id') : (!empty($this->dao->state_id) ? $this->dao->state_id : 0));
			$this->tpl['select_state']		= $formcompany->select_state($state_id, $country_id, 'departement_id');

			$currency = (GETPOSTISSET('currency_code') ? GETPOST('currency_code', 'alpha') : (!empty($this->dao->currency_code) ? $this->dao->currency_code : $conf->currency));
			$this->tpl['select_currency']	= $form->selectCurrency($currency, 'currency_code');

			$language = (GETPOSTISSET('main_lang_default') ? GETPOST('main_lang_default', 'alphanohtml') : (!empty($this->dao->language_code) ? $this->dao->language_code : $conf->global->MAIN_LANG_DEFAULT));
			$this->tpl['select_language']	= $formadmin->select_language($language, 'main_lang_default', 1);

			if (!empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED)) {
				foreach ($this->sharingelements as $element => $params) {
					if (($params['type'] === 'object' && !isset($params['disable']))
						&& (empty($conf->societe->enabled) || empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_ENABLED))) {
							continue;
					}
					$uppername = strtoupper($element);
					$constname = 'MULTICOMPANY_' . $uppername . '_SHARING_ENABLED';
					if (!empty($conf->global->$constname)) {
						$this->tpl['multiselect_from_' . $element]	= $this->multiselect_entities($element, $this->dao, false);
						$this->tpl['multiselect_to_' . $element]	= $this->multiselect_entities($element, $this->dao, true);
						if ($params['type'] === 'objectnumber') {
							$this->tpl['select_'.$element.'_entity'] = $this->select_entities($this->dao->options[$element.'_referent'], $element.'_referring_entity');
						}
						$addtoallother = (GETPOSTISSET('addtoallother_' . $element) ? GETPOST('addtoallother_' . $element, 'int') : (!empty($this->dao->options['addtoallother']) ? $this->dao->options['addtoallother'][$element] : 0));
						$this->tpl['addtoallother_' . $element] = $form->selectyesno('addtoallother_' . $element, $addtoallother, 1);
					}
				}
			}
		}
	}

	/**
	 *    Display the template
	 */
	public function display()
	{
		global $conf, $langs;
		global $form, $object;

		include $this->template_dir.$this->template;
	}

	/**
	 * 	Set values of global conf for multicompany
	 *
	 * 	@param	Conf		$conf	Object conf
	 * 	@return void
	 */
	public function setValues(&$conf)
	{
		if (!empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED)) {
			$this->getInstanceDao();
			$this->dao->fetch($conf->entity);

			$this->sharings	= (isset($this->dao->options['sharings'])?$this->dao->options['sharings']:'');

			foreach ($this->sharingelements as $element => $params) {
				if (array_key_exists('type', $params) && $params['type'] === 'objectnumber' && !isset($params['disable'])) {
					$varname = $element.'_referent';
					$this->$varname = (isset($this->dao->options[$varname]) ? $this->dao->options[$varname] : '');
				}
			}

			// Load shared elements
			$this->loadSharedElements();

			// Define output dir for others entities
			$this->setMultiOutputDir($conf);
		}

		if (!empty($this->customdicts)) {
			foreach($this->customdicts as $dict => $params) {
				if (isset($params['disabled']) && !empty($params['disabled'])) {
					continue;
				}
				$constname = 'MULTICOMPANY_'.strtoupper($dict).'_CUSTOM_ENABLED';
				if (!empty($conf->global->$constname)) {
					$this->dict[$dict] = true;
				}
			}
		}
	}

	/**
	 *	Set status of an entity
	 *
	 *	@param	int		$id			Id of entity
	 *	@param	string	$type		Type of status (visible or active)
	 *	@param	string	$value		Value of status (0: disable, 1: enable)
	 *	@return	int
	 */
	public function setStatus($id, $type='active', $value)
	{
		global $user;

		if (!empty($user->admin) && empty($user->entity)) {
			$this->getInstanceDao();
			return $this->dao->setEntity($id, $type, $value);
		} else {
			return -1;
		}
	}

	/**
	 *	Delete an entity
	 *
	 *	@param	int	$id		Id of entity
	 *	@return	int
	 */
	public function deleteEntity($id)
	{
		global $user;

		if (!empty($user->admin) && empty($user->entity) && $id != 1) {
			$this->getInstanceDao();
			return $this->dao->delete($id);
		}
		else {
			return -1;
		}
	}

	/**
	 * 	Get list of entity id to use.
	 *
	 * 	@param	string	$element		Current element
	 *									'societe', 'socpeople', 'actioncomm', 'agenda', 'resource',
	 *									'product', 'productprice', 'stock',
	 *									'propal', 'supplier_proposal', 'invoice', 'facture_fourn', 'payment_various',
	 *									'categorie', 'bank_account', 'bank_account', 'adherent', 'user',
	 *									'commande', 'commande_fournisseur', 'expedition', 'intervention', 'survey',
	 *									'contract', 'tax', 'expensereport', 'holiday', 'multicurrency', 'project',
	 *									'email_template', 'event', 'donation'
	 *									'c_paiement', 'c_payment_term', ...
	 * 	@param	int		$shared			0=Return id of current entity only,
	 * 									1=Return id of current entity + shared entities (default)
	 *  @param	object	$currentobject	Current object if needed
	 * 	@return	mixed					Entity id(s) to use ( eg. entity IN ('.getEntity(elementname).')' )
	 */
	public function getEntity($element=false, $shared=1, $currentobject=null)
	{
		global $conf, $user;

		$element = str_replace(MAIN_DB_PREFIX, '', $element);

		if (in_array($element, $this->addzero)) {
			if (($element == 'user') && !empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE)) {
				return '0,1';				// In transverse mode all users except superadmin and groups are in entity 1
			} else {
				if (($element == 'user' || $element == 'usergroup') && $conf->entity == 1 && empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE) && !empty($user->admin) && empty($user->entity)) {
					return '0,'.implode(',', array_keys($this->getEntitiesList(true, false, true))); // Superadmin can always edit users and groups of another entities
				}
				return '0,'.$conf->entity;
			}
		}

		// Custom dictionaries
		if (array_key_exists($element, $this->customdicts)) {
			if (!empty($this->dict[$element])) {
				return $conf->entity;
			} else {
				return 1; // Master entity
			}
		}

		// Check object entity when number sharing is disabled
		if (is_object($currentobject)) {
			foreach ($this->sharingelements as $elementname => $params) {
				if ($params['type'] === 'objectnumber' && !isset($params['disable'])) {
					$constname = 'MULTICOMPANY_'.strtoupper($element).'_SHARING_ENABLED';
					if ($element === $elementname && empty($conf->global->$constname)) {
						// Use object entity ID
						$entity = ((isset($currentobject->entity) && is_numeric($currentobject->entity)) ? $currentobject->entity : $conf->entity);
						return $entity;
					}
				}
			}
		}

		if (!empty($element)) {
			if (!empty($this->sharingnamecompatibility[$element])) {
				$element = $this->sharingnamecompatibility[$element];
			}
			if (!empty($this->entities[$element])) {
				if (!empty($shared)) {
					return $this->entities[$element];
				}
			}
		}

		return $conf->entity;
	}

	/**
	 * 	Set entity id to use when to create an object
	 *
	 * 	@param	object	$currentobject	Current object
	 * 	@return	int						Entity id to use
	 */
	public function setEntity($currentobject)
	{
		global $conf;

		$entity = $conf->entity;

		if (is_object($currentobject) && !empty($currentobject->element)) {
			$element = array_search($currentobject->element, $this->sharingmodulename);
			$element = (!empty($element) ? $element : $currentobject->element);
			$constname = 'MULTICOMPANY_'.strtoupper($element).'_SHARING_ENABLED';
			$newentity = $entity;
			if (isset($this->sharingelements[$element])	&& isset($this->sharingelements[$element]['active']) && !empty($conf->global->$constname)) {
				if (GETPOSTISSET('entity') && GETPOST('entity', 'int', 2)) {
					$newentity = GETPOST('entity', 'int', 2);
				} elseif (isset($currentobject->entity) && is_numeric($currentobject->entity)) {
					$newentity = $currentobject->entity;
				}
				if (isset($this->sharings[$element]) && in_array($newentity, $this->sharings[$element])) {
					$entity = $newentity;
				}
			} else {
				$entity = $currentobject->entity > 0 ? $currentobject->entity : $conf->entity;
			}
		}

		return $entity;
	}

	/**
	 * 	Get entities list
	 *
	 *	@param		int		$login			If use in login page or not
	 *	@param		array	$exclude		Entity ids to exclude
	 *	@param		bool	$onlyactive		sort only active entities
	 *	@param		bool	$showtemplate	Show or not templates
	 * 	@return		array					Array of entities (id => label)
	 */
	public function getEntitiesList($login = false, $exclude = false, $onlyactive = false, $showtemplate = false)
	{
		global $langs;

		$this->getInstanceDao();
		$this->dao->getEntities($login, $exclude, $onlyactive);

		$entities=array();

		foreach ($this->dao->entities as $entity) {
			if (empty($showtemplate) && $entity->visible == 2) {
				continue;
			}
			$info = (empty($entity->active) ? ' ('.$langs->transnoentities('Disabled').')' : ($entity->visible == 2 ? ' ('.$langs->transnoentities('Template').')' : (empty($entity->visible) ? ' ('.$langs->transnoentities('Hidden').')' : '')));
			$entities[$entity->id] = dol_html_entity_decode($entity->label, null) . $info;
		}

		return $entities;
	}

	/**
	 * 	Set object documents directory to use
	 *
	 *	@param	Conf	$conf		Object Conf
	 * 	@return	void
	 */
	public function setMultiOutputDir(&$conf)
	{
		if (!empty($this->entities)) {
			foreach($this->entities as $element => $shares) {
				if ($element == 'thirdparty') {
					$element = 'societe';
				} elseif ($element == 'member') {
					$element = 'adherent';
				} elseif ($element == 'proposal') {
					$element = 'propal';
				} elseif ($element == 'order') {
					$element = 'commande';
				} elseif ($element == 'invoice') {
					$element = 'facture';
				} elseif ($element == 'intervention') {
					$element = 'ficheinter';
				}

				if (!empty($conf->$element->enabled) && isset($conf->$element->multidir_output) && isset($conf->$element->multidir_temp)) {
					$elementpath = $element;
					if ($element == 'product') {
						$elementpath = 'produit';
					} elseif ($element == 'category') {
						$elementpath = 'categorie';
					} elseif ($element == 'propal') {
						$elementpath = 'propale';
					} elseif ($element == 'project') {
						$elementpath = 'projet';
					}

					$entities = explode(",", $shares);
					$dir_output = array();
					$dir_temp = array();
					foreach($entities as $entity) {
						if (!array_key_exists($entity, $conf->$element->multidir_output)) {
							$path = ($entity > 1 ? "/".$entity : '');

							$dir_output[$entity] = DOL_DATA_ROOT.$path."/".$elementpath;
							$dir_temp[$entity] = DOL_DATA_ROOT.$path."/".$elementpath."/temp";

							$conf->$element->multidir_output += $dir_output;
							$conf->$element->multidir_temp += $dir_temp;
						}
						if (in_array($element, array('propal', 'commande', 'facture'))) {
							if (!isset($conf->mycompany->multidir_output) || !is_array($conf->mycompany->multidir_output)) {
								$conf->mycompany->multidir_output = array();
							}
							if (!isset($conf->mycompany->multidir_temp) || !is_array($conf->mycompany->multidir_temp)) {
								$conf->mycompany->multidir_temp = array();
							}
							if (!array_key_exists($entity, $conf->mycompany->multidir_output)) {
								$path = ($entity > 1 ? "/".$entity : '');

								$dir_output[$entity] = DOL_DATA_ROOT.$path."/mycompany";
								$dir_temp[$entity] = DOL_DATA_ROOT.$path."/mycompany/temp";

								$conf->mycompany->multidir_output += $dir_output;
								$conf->mycompany->multidir_temp += $dir_temp;
							}
						}
					}
				}
			}
		}
	}

	/**
	 * @param bool $parameters
	 * @return int
	 */
	public function printTopRightMenu($parameters = false)
	{
		echo $this->getTopRightMenu();

		return 0;
	}

	/**
	 *
	 */
	public function printBugtrackInfo($parameters = false)
	{
		global $conf;

		if (!empty($conf->multicompany->enabled) && !empty($conf->global->MAIN_BUGTRACK_ENABLELINK) && $conf->global->MAIN_BUGTRACK_ENABLELINK == "github") {
			$this->resprints = urlencode("- **Multicompany**: " . $conf->global->MULTICOMPANY_MAIN_VERSION . "\n");
		}

		return 0;
	}

	/**
	 * @param bool $parameters
	 * @return int
	 */
	public function updateSession($parameters = false)
	{
		global $conf;

		if (empty($conf->multicompany->enabled)) {
			return 0;
		}

		// Switch to another entity
		if (GETPOST('action','aZ') == 'switchentity') {
			if ($this->switchEntity(GETPOST('entity','int')) > 0) {
				$requesturi = '/';
				if (!empty($conf->global->MULTICOMPANY_SWITCHENTITY_ACTION_KEPT_REQUEST_URI)) {
					$requesturi = removeParam($_SERVER['REQUEST_URI'], 'action');
				}
				header("Location: ".DOL_URL_ROOT.$requesturi);
				exit;
			}
		} elseif (GETPOSTISSET('switchentity')) {
			if ($this->switchEntity(GETPOST('switchentity','int')) > 0) {
				header("Location: ".DOL_URL_ROOT.removeParam($_SERVER['REQUEST_URI'], 'switchentity'));
				exit;
			}
		}

		return 0;
	}

	/**
	 *
	 */
	public function getLoginPageOptions($parameters = false)
	{
		global $conf, $langs;

		if (empty($conf->multicompany->enabled)) {
			return 0;
		}

		if (is_array($parameters) && !empty($parameters)) {
			foreach($parameters as $key => $value) {
				$$key = $value;
			}
		}

		$currentcontext = explode(':', $parameters['context']);

		if ((in_array('login', $currentcontext) || in_array('cashdeskloginpage', $currentcontext)) && !empty($conf->global->MULTICOMPANY_HIDE_LOGIN_COMBOBOX)) {
			return 0;
		}
		if (in_array('passwordforgottenpage', $currentcontext) && !empty($conf->global->MULTICOMPANY_HIDE_LOGIN_COMBOBOX)) {
			return 0;
		}

		// Entity combobox
		$opennewsession = (getDolGlobalInt('MULTICOMPANY_FEATURES_LEVEL') && getDolGlobalInt('MULTICOMPANY_OPEN_NEW_SESSION_AFTER_SWITCHING'));
		if (!empty($opennewsession) && !empty($_SERVER['HTTP_HOST'])) {
			$this->getInfo(null, false, null, $_SERVER['HTTP_HOST']);
		}
		$entity = (!empty($this->id) ? $this->id : 1);
		$lastentity = (!empty($conf->global->MULTICOMPANY_FORCE_ENTITY) ? $conf->global->MULTICOMPANY_FORCE_ENTITY : $entity);

		if (in_array('cashdeskloginpage', $currentcontext)) {
			$select_entity = $this->select_entities($lastentity, 'entity', ' tabindex="3"', true, false, false, false, '', 'minwidth100imp');

			$tableformat = '<tr>';
			$tableformat.= '<td class="label1">'.$langs->trans("Entity").'</td>';
			$tableformat.= '<td>'.$select_entity.'</td>';
			$tableformat.= '</tr>';

			$this->resprints = $tableformat;
		} else {
			$select_entity = $this->select_entities($lastentity, 'entity', ' tabindex="3"', true, false, false, false, '', 'login-entity minwidth180');

			$divformat = "\n".'<div class="trinputlogin multicompany-trinputlogin">'."\n";
			$divformat.= '<div class="tagtd nowrap text-align-left valignmiddle tdinputlogin">'."\n";
			$divformat.= '<span class="fa fa-globe">'.$select_entity.'</span>'."\n";
			$divformat.= '</div></div>';

			if (!empty($conf->global->MULTICOMPANY_LOGIN_LOGO_BY_ENTITY)) {
				$divformat.= '
					<script type="text/javascript">
					$(document).ready(function() {
						var entityId = $("#entity").val();
						if (entityId > 1) {
							checkEntity();
						}
						$("#login_right").on("change", "#entity", function() {
							checkEntity();
						});
						function checkEntity() {
							$.post( "'.dol_buildpath('/multicompany/core/ajax/functions.php', 1).'", {
								action: \'getEntityLogo\',
								id: $("#entity").val(),
                                token: $(\'input[name="token"]\').val()
							},
							function(result) {
                                $(\'input[name="token"]\').val(result.token);
								if (result.status == "success") {
									if (result.unsplashimg != "") {
										var xhr;
										var _orgAjax = jQuery.ajaxSettings.xhr;
										jQuery.ajaxSettings.xhr = function () {
										  xhr = _orgAjax();
										  return xhr;
										};
										$.ajax(result.unsplashimg, {
											success: function() {
												$("body").css("background-color", "#ccc");
												changeImgBg(xhr.responseURL);
												changeImgLogo(result.urllogo);
											},
											error: function() {
												if (result.bgimg != "") {
													changeImgBg(result.bgimg);
												}
												changeImgLogo(result.urllogo);
											}
										});
									} else if (result.bgimg != "") {
										changeImgBg(result.bgimg);
										changeImgLogo(result.urllogo);
									} else {
										changeImgLogo(result.urllogo);
									}
								}
							});
						};
						function changeImgBg(bgimg) {
							$("body").fadeTo(300,0.01, function() {
								$("body").css("background-image", "url(" + bgimg + ")");
							}).fadeTo(300,1);
						};
						function changeImgLogo(urllogo) {
							$("#img_logo").fadeTo(400,0.01, function() {
								$("#img_logo").attr("src", urllogo);
							}).fadeTo(400,1);
						};
					});
					</script>';
			}

			$this->resprints = $divformat;
		}

		return 0;
	}

	/**
	 *
	 * @param boolean $parameters
	 * @return number
	 */
	public function getPasswordForgottenPageOptions($parameters = false)
	{
		return $this->getLoginPageOptions($parameters);
	}

	/**
	 *
	 * @param boolean $parameters
	 * @return number
	 */
	public function getPasswordForgottenPageExtraOptions($parameters = false)
	{
		global $conf, $langs, $dolibarr_main_authentication;
		global $disabled, $captcha, $captcha_refresh;

		if (empty($conf->multicompany->enabled)) {
			return 0;
		} elseif (!preg_match('/mc/', $dolibarr_main_authentication)) {
			return 0;
		}

		$disabled = false;
		if (function_exists("imagecreatefrompng")) {
			$captcha = 1;
			$captcha_refresh = img_picto($langs->trans("Refresh"), 'refresh', 'id="captcha_refresh_img"');
		}
	}

	/**
	 * Add all entities default dictionnaries in database
	 */
	public function addAllEntitiesDefaultDicts()
	{
		if (!empty($this->customdicts)) {
			$this->getInstanceDao();
			$this->dao->getEntities();

			foreach($this->customdicts as $dict => $params) {
				if (isset($params['disabled']) && !empty($params['disabled'])) {
					continue;
				}
				if (isset($params['filepath']) && !empty($params['filepath'])) {
					$filepath = dol_buildpath($params['filepath']);
				} else {
					$filepath = getSqlInitFilePath($dict);
				}
				if (file_exists($filepath)) {
					foreach ($this->dao->entities as $entity) {
						if ($entity->id == 1) {
							continue;
						}

						$result = run_sql($filepath, 1, $entity->id);
					}
				}
			}
		}
	}

	/**
	 *  Load shared elements
	 *
	 *  @return void
	 */
	private function loadSharedElements()
	{
		global $conf;

		if (!empty($this->sharings)) {
			$this->getInstanceDao();

			foreach($this->sharings as $element => $ids) {
				$modulesharingenabled = 'MULTICOMPANY_'.strtoupper($element).'_SHARING_ENABLED';

				$module = ((isset($this->sharingmodulename[$element]) && !empty($this->sharingmodulename[$element])) ? $this->sharingmodulename[$element] : $element);

				if (!empty($conf->$module->enabled) && !empty($conf->global->$modulesharingenabled)) {
					$entities=array();

					if (!empty($ids)) {
						foreach ($ids as $id) {
							$ret=$this->dao->fetch($id);
							if ($ret > 0 && $this->dao->active) {
								$entities[] = $id;
							}
						}

						$this->entities[$element] = (!empty($entities) ? implode(",", $entities) : 0);
						$this->entities[$element].= ','.$conf->entity;
					}
				}
			}
		}
		//var_dump($this->entities);
	}

	/**
	 * 	Get modify entity dialog
	 */
	private function getModifyEntityDialog($htmlname, $action, $object)
	{
		global $langs;

		$langs->loadLangs(array('errors','multicompany@multicompany'));

		$selectEntities = $this->select_entities('', 'entity' . $htmlname, '', false, array($object->entity));

		$out = '';

		if (!empty($selectEntities)) {

			$out.= '<!-- BEGIN MULTICOMPANY AJAX TEMPLATE -->';

			$out.= '
			<script type="text/javascript">
			$(document).ready(function() {
				$( "#modify-entity-'.$htmlname.'" ).click(function() {
					$( "#dialog-modify-'.$htmlname.'" ).dialog({
						modal: true,
						resizable: false,
						width: 400,
						height: 200,
						open: function() {
							$(".ui-dialog-buttonset > button:last").focus();
						},
						buttons: {
							\''.$langs->trans('Validate').'\': function() {
								$.post( "'.dol_buildpath('/multicompany/core/ajax/functions.php',1).'", {
									action: \''.$action.'\',
									element: \''.$object->element.'\',
									fk_element: \''.$object->id.'\',
									id: $( "#entity'.$htmlname.'" ).val(),
                                    token: "'.currentToken().'"
								},
								function(result) {
									if (result.status == "success") {
										$.jnotify("'.$langs->trans(ucfirst($htmlname) . "ModifyEntitySuccess").'", "ok");
										$( "#dialog-modify-'.$htmlname.'" ).dialog( "close" );
										window.location.href = "'.$_SERVER["PHP_SELF"].'?'.($htmlname == 'thirdparty'?'socid':'id').'='.$object->id.'";
									} else {
										$.jnotify("'.$langs->trans("Error" . ucfirst($htmlname) . "ModifyEntity").'", "error", true);
										if (result.error) {
											if (result.error == "ErrorCustomerCodeAlreadyUsed") {
												$.jnotify("'.$langs->trans("ErrorCustomerCodeAlreadyUsed").'", "error", true);
											}
										}
									}
								});
							},
							\''.$langs->trans('Cancel').'\': function() {
								$(this).dialog( "close" );
							}
						}
					});
				});
			});
			</script>';

			$out.= '<div id="dialog-modify-' . $htmlname . '" class="hideobject" title="' . $langs->trans(ucfirst($htmlname) . 'ModifyEntity') . '">'."\n";
			$out.= '<p>' . img_warning() . ' ' . $langs->trans(ucfirst($htmlname) . 'ModifyEntityDescription') . '</p>'."\n";
			$out.= '<div>' . $langs->trans('SelectAnEntity');
			$out.= $selectEntities . '</div>'."\n";
			$out.= '</div>'."\n";

			$out.= '<!-- END MULTICOMPANY AJAX TEMPLATE -->';
		}

		return $out;
	}

	/**
	 * 	Show entity info
	 */
	private function getTopRightMenu()
	{
		global $conf, $user, $langs;

		$langs->loadLangs(array('languages','admin','multicompany@multicompany'));

		$out='';

		if (!empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE) || !empty($user->admin)) {
			$usedropdownmenu = false;
			if (empty($conf->global->MULTICOMPANY_DROPDOWN_MENU_DISABLED)) {
				// For theme test link
				if (GETPOSTISSET('theme')) {
					if (GETPOST('theme', 'aZ', 1) === 'eldy') {
						$usedropdownmenu = true;
					}
				} else {
					// For user theme
					if (!empty($user->conf->MAIN_THEME)) {
						if ($user->conf->MAIN_THEME === 'eldy') {
							$usedropdownmenu = true;
						}
					// For global theme
					} elseif ($conf->global->MAIN_THEME === 'eldy') {
						$usedropdownmenu = true;
					}
				}
			}
			if ($usedropdownmenu) {
				$out.= $this->getDropdownMenu();
			} else {
				$form=new Form($this->db);

				$this->getInfo($conf->entity);

				$selected = $conf->entity;
				$exclude = false;
				if (getDolGlobalInt('MULTICOMPANY_HIDE_CURRENT_ENTITY_IN_COMBOBOX')) {
					$selected = '';
					$exclude = array($conf->entity);
				}
				$selectEntities = $this->select_entities($selected, 'changeentity', '', false, $exclude, false, false, '', 'minwidth200imp', true, true);

				$eldytheme = false;
				// For theme test link
				if (GETPOSTISSET('theme')) {
					if (GETPOST('theme', 'aZ', 1) === 'eldy') {
						$eldytheme = true;
					}
				} else {
					// For user theme
					if (!empty($user->conf->MAIN_THEME)) {
						if ($user->conf->MAIN_THEME === 'eldy') {
							$eldytheme = true;
						}
						// For global theme
					} elseif ($conf->global->MAIN_THEME === 'eldy') {
						$eldytheme = true;
					}
				}

				$text ='<span class="fa fa-globe atoplogin switchentity'.($eldytheme ? '' : ' padding-top3').'">';
				if ($eldytheme && empty($conf->global->MULTICOMPANY_NO_TOP_MENU_ENTITY_LABEL)) {
					$text.= '<span class="topmenu-mc-label">'.$this->label.'</span>';
				}
				$text.= '</span>';

				if ($cache = getCache('mc_country_' . $this->country_id)) {
					$country = $cache;
				} else {
					$country = getCountry($this->country_id);
					setCache('mc_country_' . $this->country_id, $country);
				}
				$imgCountry=picto_from_langcode($this->country_code, 'class="multicompany-flag-country"');
				$imgLang=picto_from_langcode($this->language_code, 'class="multicompany-flag-language"');

				$htmltext ='<u>'.$langs->trans("Entity").'</u>'."\n";
				$htmltext.= '<br>';
				$htmltext.='<br><b>'.$langs->trans("Label").'</b>: '.$this->label."\n";
				$htmltext.='<br><b>'.$langs->trans("Country").'</b>: '. ($imgCountry?$imgCountry.' ':'') . $country."\n";
				$htmltext.='<br><b>'.$langs->trans("Currency").'</b>: '. currency_name($this->currency_code) . ' (' . $langs->getCurrencySymbol($this->currency_code) . ')'."\n";
				$htmltext.='<br><b>'.$langs->trans("Language").'</b>: '. ($imgLang?$imgLang.' ':'') . ($this->language_code=='auto'?$langs->trans("AutoDetectLang"):$langs->trans("Language_".$this->language_code));
				if (!empty($this->description)) {
					$htmltext.='<br><b>'.$langs->trans("Description").'</b>: '.$this->description."\n";
				}
				$htmltext.= '<br><br>';

				$out.= '<div class="inline-block nowrap">';
				$out.= $form->textwithtooltip('', $htmltext, 2, 1, $text, 'login_block_elem multicompany_block', 2);
				$out .= '</div>';

				if (!empty($selectEntities)) {
					$out.= '
					<script type="text/javascript">
					$(document).ready(function() {
						$(".switchentity").click(function() {
							$( "#dialog-switchentity" ).dialog({
								modal: true,
								width: '.($conf->dol_optimize_smallscreen ? 300 : 400).',
								buttons: {
									\''.$langs->trans('Ok').'\': function() {
										$.post("'.dol_buildpath('/multicompany/core/ajax/functions.php', 1).'", {
											action: \'switchEntity\',
											id: $( "#changeentity" ).val(),
                                            token: "'.currentToken().'"
										},
										function(content) {
											$("#dialog-switchentity").dialog( "close" );
											var url = window.location.pathname;
											var queryString = window.location.href.split("?")[1];
											if (queryString) {
												var params = parseQueryString(queryString);
												delete params.action;
												delete params.switchentityautoopen;
												delete params.valid_change_chart;
												delete params.chartofaccounts;
												delete params.entitytoclone;
												delete params.uploadform;
												url = url + "?" + jQuery.param(params);
											}
											location.href=url;
										});
									},
									\''.$langs->trans('Cancel').'\': function() {
										$(this).dialog( "close" );
									}
								}
							});
						});
						var parseQueryString = function( queryString ) {
							var params = {}, queries, temp, i, l;
							// Split into key/value pairs
							queries = queryString.split("&");
							// Convert the array of strings into an object
							for ( i = 0, l = queries.length; i < l; i++ ) {
								temp = queries[i].split("=");
								temp[1] = temp[1].replace(/#builddoc/gi, "");
								params[temp[0]] = temp[1];
							}
							return params;
						};
					';
					if (GETPOST('switchentityautoopen','int')) {
						$out.='$(".switchentity").click();'."\n";
					}
					$out.= '
				});
				</script>';

					$out.= '<div id="dialog-switchentity" class="hideobject" title="'.$langs->trans('SwitchToAnotherEntity').'">'."\n";
					$out.= '<br>'.$langs->trans('SelectAnEntity');
					$out.= $selectEntities;
					$out.= '</div>'."\n";
				} else {

				}
			}
		}

		if (($level = checkMultiCompanyVersion()) === -2) {
			$msg = get_htmloutput_mesg(img_warning('default') . ' ' . $langs->trans("MultiCompanyUpgradeIsNeeded"), '', 'mc-upgrade-alert', 1);
			$out.= '
			<script type="text/javascript">
			$(document).ready(function() {
				$( "#id-right .fiche" ).before( \'' . $msg . '\' );
			});
			</script>';
		}

		$this->resprints = $out;
	}

	/**
	 *
	 * @return string
	 */
	private function getDropdownMenu()
	{
		global $conf, $user, $langs;

		$this->getInfo($conf->entity);

		if ($cache = getCache('mc_country_' . $this->country_id)) {
			$country = $cache;
		} else {
			$country = getCountry($this->country_id);
			setCache('mc_country_' . $this->country_id, $country);
		}
		$imgCountry=picto_from_langcode($this->country_code, 'class="multicompany-flag-country"');
		$imgLang=picto_from_langcode($this->language_code, 'class="multicompany-flag-language"');

		$dropdownBody = '';
		$dropdownBody.= '<span id="topmenumcmoreinfo-btn"><i class="fa fa-caret-right"></i> '.$langs->trans("ShowMoreInfos").'</span>';
		$dropdownBody.= '<div id="topmenumcmoreinfo" >';
		$dropdownBody.= '<br><u>'.$langs->trans("Entity").'</u>'."\n";
		$dropdownBody.= '<br><b>'.$langs->trans("ID").'</b>: '.$this->id."\n";
		$dropdownBody.= '<br><b>'.$langs->trans("Label").'</b>: '.$this->label."\n";
		$dropdownBody.= '<br><b>'.$langs->trans("Country").'</b>: '. ($imgCountry?$imgCountry.' ':'') . $country."\n";
		$dropdownBody.= '<br><b>'.$langs->trans("Currency").'</b>: '. currency_name($this->currency_code) . ' (' . $langs->getCurrencySymbol($this->currency_code) . ')'."\n";
		$dropdownBody.= '<br><b>'.$langs->trans("Language").'</b>: '. ($imgLang?$imgLang.' ':'') . ($this->language_code=='auto'?$langs->trans("AutoDetectLang"):$langs->trans("Language_".$this->language_code));
		if (!empty($this->description)) {
			$dropdownBody.= '<br><b>'.$langs->trans("Description").'</b>: '.$this->description."\n";
		}
		$dropdownBody.= '</div>';

		$selected = $conf->entity;
		$exclude = false;
		if (getDolGlobalInt('MULTICOMPANY_HIDE_CURRENT_ENTITY_IN_COMBOBOX')) {
			$selected = '';
			$exclude = array($conf->entity);
		}
		$selectEntities = $this->select_entities($selected, 'changeentity', '', false, $exclude, false, false, '', 'minwidth200imp', true, true);

		$entitySwitchLink ='<div id="switchentity-menu" class="button-top-menu-dropdown'.(empty($selectEntities) ? ' button-not-allowed' : '').'"><i class="fa fa-random"></i> '.$langs->trans("SwitchEntity").'</div>';
		$entityConfigLink ='<a class="button-top-menu-dropdown" href="'.dol_buildpath('multicompany/admin/multicompany.php', 1).'?action=edit&id='.$conf->entity.'"><i class="fa fa-cogs"></i>  '.$langs->trans("Setup").'</a>';

		$out = '<div class="inline-block nowrap">';
		$out.= '<div class="inline-block login_block_elem login_block_elem_name float-left" style="padding: 0px;">';

		$out.= '<div id="topmenu-mc-dropdown" class="atoplogin mcdropdown mc-menu">';
		$out.= '<span class="fa fa-globe atoplogin mc-dropdown-toggle" data-toggle="mcdropdown" id="mc-dropdown-icon" title="'.$this->label.'">';

		if (empty($conf->global->MULTICOMPANY_NO_TOP_MENU_ENTITY_LABEL)) {
			$out .= '<span class="topmenu-mc-label">' . $this->label . '</span>';
		}
		$out.= '<span class="fa fa-chevron-down padding-left5" id="mc-dropdown-icon-down"></span>';
		$out.= '<span class="fa fa-chevron-up padding-left5 hidden" id="mc-dropdown-icon-up"></span>';
		$out.= '</span>';

		$out.= '<div class="mc-dropdown-menu">';

		$out.= '<div class="mc-header">';
		$out.= '<div class="fa fa-globe dropdown-mc-image"></div>';

		if (getDolGlobalInt('MULTICOMPANY_NO_TOP_MENU_ENTITY_LABEL')) {
			$out .= '<br><span class="topmenu-mc-header-label">'.$this->label.'</span>';
		}

		if (!empty($selectEntities)) {
			$out.= '<br><br>'.$langs->trans('SelectAnEntity');
			$out.= $selectEntities;
		} else {
			$out.= '<br><br>'.$langs->trans('NoOtherEntityAvailable');
		}

		$out.= '</div>';

		$out.= '<div class="mc-body">'.$dropdownBody.'</div>';

		$out.= '<div class="mc-footer">';
		$out.= '<div class="pull-left">';
		if (!empty($user->admin) && empty($user->entity)) {
			$out.= $entityConfigLink;
		}
		$out.= '</div>';

		$out.= '<div class="pull-right">';
		$out.= $entitySwitchLink;
		$out.= '</div>';

		$out.= '<div style="clear:both;"></div>';

		$out.= '</div>';
		$out.= '</div>';
		$out.= '</div>';

		$out.= '</div></div>';

		$out.= '
			<script type="text/javascript">
			$(document).ready(function() {
				$(document).on("click", function(event) {
					if (!$(event.target).closest("#topmenu-mc-dropdown").length) {
						// hide the menu
						$("#topmenu-mc-dropdown").removeClass("open");
						$("#mc-dropdown-icon-down").show();
						$("#mc-dropdown-icon-up").hide();
					}
				});
				$("#tmenu_tooltip").css("padding-right",parseFloat($("#tmenu_tooltip").css("padding-right")) + $("#topmenu-mc-dropdown").width());
				$("#topmenu-mc-dropdown .mc-dropdown-toggle").on("click", function(event) {
					$("#topmenu-mc-dropdown").toggleClass("open");
					$("#mc-dropdown-icon-down").toggle();
					$("#mc-dropdown-icon-up").toggle();
				});
				$("#topmenumcmoreinfo-btn").on("click", function() {
					$("#topmenumcmoreinfo").slideToggle();
				});
		';
		if (getDolGlobalInt('MULTICOMPANY_FEATURES_LEVEL') && getDolGlobalInt('MULTICOMPANY_OPEN_NEW_SESSION_AFTER_SWITCHING')) {
			$out.= '
				$("#switchentity-menu").on("click",function() {
					$.post("'.dol_buildpath('/multicompany/core/ajax/functions.php', 1).'", {
						action: \'getEntityUrl\',
						id: $("#changeentity").val(),
	                    token: "'.currentToken().'"
					},
					function(data) {
						if (data.status == "success") {
							var url = "'.$_SERVER['REQUEST_SCHEME'].'://";
							url += data.url + "'.dol_buildpath("/multicompany/core/ajax/redirect.php", 1).'?username='.$user->login.'&entity=" + data.id + "&securitycode=" + data.securitycode;
							// hide the menu
							$("#topmenu-mc-dropdown").removeClass("open");
							$("#mc-dropdown-icon-down").show();
							$("#mc-dropdown-icon-up").hide();
							// open the new tab
							window.open(url, "_blank").focus();
						} else {
							switchEntity(data.id);
						}
					});
				});
			';
		} else {
			$out.= '
				$("#switchentity-menu").on("click",function() {
					switchEntity($("#changeentity").val());
				});
			';
		}
		$out.= '
			var switchEntity = function(id) {
				$.post("'.dol_buildpath('/multicompany/core/ajax/functions.php', 1).'", {
					action: \'switchEntity\',
					id: id,
					token: "'.currentToken().'"
				},
				function(data) {
					var url = window.location.pathname;
					var queryString = window.location.href.split("?")[1];
					if (queryString) {
						var params = parseQueryString(queryString);
						delete params.action;
						delete params.switchentityautoopen;
						delete params.valid_change_chart;
						delete params.chartofaccounts;
						delete params.entitytoclone;
						url = url + "?" + jQuery.param(params);
					}
					location.href=url;
				});
			};
			var parseQueryString = function(queryString) {
				var params = {}, queries, temp, i, l;
				// Split into key/value pairs
				queries = queryString.split("&");
				// Convert the array of strings into an object
				for ( i = 0, l = queries.length; i < l; i++ ) {
					temp = queries[i].split("=");
					temp[1] = temp[1].replace(/#builddoc/gi, "");
					params[temp[0]] = temp[1];
				}
				return params;
			};
		';
		if (GETPOST('switchentityautoopen','int')) {
			$out.= '$("#switchentity").click();'."\n";
		}
		$out.= '
			});
			</script>
		';

		return $out;
	}

	/**
	 *	Set parameters with referent entity
	 *
	 * @param Conf $conf
	 * @param string $element
	 * @deprecated
	 */
	public function setConstant(&$conf, $element)
	{
		if (!empty($this->config)) {
			$constants=array();

			if ($element == 'thirdparty') {
				$constants = array(
						'SOCIETE_CODECLIENT_ADDON',
						'COMPANY_ELEPHANT_MASK_CUSTOMER',
						'COMPANY_ELEPHANT_MASK_SUPPLIER',
						'SOCIETE_IDPROF1_UNIQUE',
						'SOCIETE_IDPROF2_UNIQUE',
						'SOCIETE_IDPROF3_UNIQUE',
						'SOCIETE_IDPROF4_UNIQUE'
				);
			}

			if (!empty($constants)) {
				foreach($constants as $name) {
					if (!empty($this->config[$name])) {
						$conf->global->$name = $this->config[$name];
					}
				}
			}
		}
	}

	/**
	 *
	 * @param int $groupid
	 * @param int $template
	 * @param array $entities
	 * @return number
	 */
	public function duplicateUserGroupRights($groupid, $template, $entities)
	{
		require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';

		dol_syslog(get_class($this)."::duplicateUserGroupRights groupid=".$groupid." template=".$template." entities=".implode(",", $entities), LOG_DEBUG);

		$error=0;

		$groupstatic = new UserGroup($this->db);
		$ret = $groupstatic->fetch($groupid, '', false);
		if ($ret > 0) {
			$this->getInstanceDao();
			$permsgroupbyentity = $this->dao->getGroupRightsByEntity($groupid, $template);

			if (!empty($entities)) {
				foreach($entities as $entity) {
					if ($error > 0) {
						break;
					}

					$ret = $groupstatic->delrights('', 'allmodules', '', $entity);
					if ($ret < 0) {
						$error++;
						break;
					}

					foreach($permsgroupbyentity as $rid) {
						$ret = $groupstatic->addrights($rid, '', '', $entity);
						if ($ret < 0) {
							$error++;
							break;
						}
					}
				}

				if (empty($error)) {
					return 1;
				} else {
					return -1;
				}
			} else {
				return -2;
			}
		} else {
			return -3;
		}
	}

}
