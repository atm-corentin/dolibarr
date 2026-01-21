<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2015 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    class/actions_marque.class.php
 * \ingroup marque
 * \brief   This file is an example hook overload class file
 *          Put some comments here
 */

/**
 * Class ActionsMarque
 */
require_once __DIR__.'/../backport/v19/core/class/commonhookactions.class.php';

class ActionsMarque extends marque\RetroCompatCommonHookActions
{
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

	/**
	 * Constructor
	 */
	public function __construct()
	{
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
		$TContext = explode(':', $parameters['context']);

		if (in_array('agefodd', $TContext) && $parameters['location'] == 'document_trainee')
		{
			global $conf;

			if(( $action=='create' || $action == 'refresh')
				&& !empty($object->array_options['options_entity_marque'])
				&& $object->array_options['options_entity_marque'] > 0
				&& $object->array_options['options_entity_marque'] != $conf->entity) {
				$this->setMySocByEntity($object->array_options['options_entity_marque']);
			}
		}
	}

	function beforePDFCreation(&$parameters, &$object, &$action, $hookmanager) {
		global $conf;

		if(!empty($object->array_options['options_entity_marque'])
			&& $object->array_options['options_entity_marque'] > 0
			&& $object->array_options['options_entity_marque'] != $conf->entity) {

			$this->setMySocByEntity($object->array_options['options_entity_marque']);
		}
	}

	function afterPDFCreation(&$parameters, &$null, &$action, $hookmanager) {
		global $db,$conf,$mysoc,$original_mysoc,$original_conf;

		if(!empty($original_mysoc)) {
			$mysoc = unserialize($original_mysoc); // étragement un clone ne change le pointeur mémoire que du premier niveau...
			$conf = unserialize($original_conf);
			$mysoc->db = $db;
		}
	}

	/**
	 * @param int $entity
	 * @throws Exception
	 */
	function setMySocByEntity($entity) {
		global $db, $conf, $mysoc, $original_conf, $original_mysoc;

		$original_mysoc = serialize($mysoc);
		$original_conf = serialize($conf);
		$confBackup = unserialize($original_conf);

		$sourcecompany = &$mysoc;
		$sourceconf = &$conf;

        $fk_entity_origin = $sourceconf->entity;
		$sourceconf->entity = $entity;
		foreach ($sourceconf->global as $attr => &$value)
		{
			// Recherche des globals liées au info de l'entity, car si non renseignées sur l'entity secondaire (non existante dans llx_const)
			// alors l'objet conserve les valeurs d'origines (et on ne veut pas de l'email par exemple de l'entité d'origine)
			if (substr($attr, 0, 10) == 'MAIN_INFO_') $value = '';
		}

		$sourceconf->setValues($db);

		// RESTORE MULTIDIR OUTPUT FOR PRODUCT
		$rootfordata = DOL_DATA_ROOT;

		// If multicompany module is enabled, we redefine the root of data
		if (isModEnabled('multicompany') && !empty($conf->entity) && $conf->entity > 1)
		{
			$rootfordata .= '/'.$conf->entity;
		}
		// Set standard temporary folder name or global override
		$rootfortemp = getDolGlobalString('MAIN_TEMP_DIR', $rootfordata);


		// Module product/service
		$conf->product->multidir_output 		+= $confBackup->product->multidir_output;
		$conf->product->multidir_temp			+= $confBackup->product->multidir_temp;
		$conf->service->multidir_output			+= $confBackup->service->multidir_output;
		$conf->service->multidir_temp			+= $confBackup->service->multidir_temp;

		$sourcecompany->setMysoc($sourceconf);

		$sourceconf->entity = $fk_entity_origin; // Dolibarr <= 7.0 ; un fetchObjectLinked() essaye pour les propals de faire un fetch filtré sur l'entité
	}

	function formObjectOptions(&$parameters, &$null, &$action, $hookmanager)
	{
		global $conf,$user,$langs,$db,$mysoc,$object;

		$TContext = explode(':', $parameters['context']);
		if (in_array('globalcard',$TContext))
		{
			if(!empty(getDolGlobalString('MARQUE_ENTITIES_LINKED_'.$conf->entity)) && (GETPOST('attribute') === 'entity_marque' || $action=='edit' || $action=='create') ) {
				?>
				<script type="text/javascript">
				$(document).ready( function () {
					var TMarqueEntitiesAllowed = [<?php echo getDolGlobalString('MARQUE_ENTITIES_LINKED_'.$conf->entity) ?>];

					$('#options_entity_marque option').each(function(i,item) {
						$item = $(item);

						var entid = parseInt($item.val());
						if(entid>0 && $.inArray(entid, TMarqueEntitiesAllowed) == -1 ) {
							$item.remove();
						}
					});
				});
				</script>
				<?php
			}
		}
	}
}
