<?php
/* Copyright (C) 2022 Grégory Blémand <contact@atm-consulting.fr>
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    advancedhrm/class/actions_advancedhrm.class.php
 * \ingroup advancedhrm
 * \brief   Example hook overload.
 *
 * Put detailed description here.
 */

/**
 * Class ActionsAdvancedHRM
 */

require_once __DIR__.'/../backport/v19/core/class/commonhookactions.class.php';
class ActionsAdvancedHRM extends  \advancedhrm\RetroCompatCommonHookActions
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Error code (or message)
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
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}


	/**
	 * Execute action
	 *
	 * @param	array			$parameters		Array of parameters
	 * @param	CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param	string			$action      	'add', 'update', 'view'
	 * @return	int         					<0 if KO,
	 *                           				=0 if OK but we want to process standard actions too,
	 *                            				>0 if OK and we want to replace standard actions.
	 */
	public function getNomUrl($parameters, &$object, &$action) {
		global $db, $langs, $conf, $user, $dateTool, $mysoc;
		if(empty($object->fk_type)) $object->fk_type = 0;
		if(empty($dateTool)) {
			dol_include_once('/advancedhrm/class/datetool.class.php');
			$dateTool = new DateTool($db, $langs, $mysoc);
		}

		if(empty($dateTool->TColorType[$object->fk_type])) $dateTool->TColorType[$object->fk_type] = array('code' => '');

		$langs->load('advancedhrm@advancedhrm');
		$TContext = explode(':', $parameters['context']);

		// réécriture du tooltip de getNomUrl pour avoir en plus le type de d'absence
		if(in_array('planningUser', $TContext) && get_class($object) !== 'User' && $object->element == 'holiday') {
			$label = img_picto('', $object->picto).' <u class="paddingrightonly">'.$langs->trans("Holiday").'</u>';
			if(isset($object->statut)) {
				$label .= ' '.$object->getLibStatut(5);
			}
			$label .= '<br><b>'.$langs->trans('Ref').':</b> '.$object->ref;
			// ajout du type de congés dans la tooltip de l'absence
			$label .= '<br><b>'.$langs->trans('HolidayType').':</b> '.$langs->trans($dateTool->TColorType[$object->fk_type]['code']);
			$label .= in_array($object->statut, array(Holiday::STATUS_VALIDATED)) ? ' '.$langs->trans('toValidate') : ' '.$langs->trans('approuved');

			$url = DOL_URL_ROOT.'/holiday/card.php?id='.$object->id;

			$linkstart = '<a href="'.$url.'" target="_blank"  title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
			$linkend = '</a>';
			$notooltip = 0;
			$withpicto = 2;
			$result = $linkstart;
			if($withpicto) {
				$result .= img_object(($notooltip ? '' : $label), $object->picto, ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
			}
			$result .= $linkend;

			$this->resprints = $result;

			return 1;
		}

		return 0;
		// getNomUrl

	}

	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function doActions($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$error = 0; // Error counter
		$TContext = explode(':', $parameters['context']);


		if (in_array('dictionaryadmin', $TContext)) {
			if (in_array($parameters['id'], array(17, 28))) // 17 : expenseType dictionary, 28 :
			{
				global $tabsql, $tabfieldvalue, $tabfield, $tabhelp;

				// display vat link
				$sql = $tabsql[17];
				$sql = "SELECT c.id as rowid, c.code, c.label, c.accountancy_code, c.active, cev.fk_vat_tx as fk_tva, tva.taux FROM ".MAIN_DB_PREFIX."c_type_fees as c";
				$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."advancedhrm_expensetypevatlink cev ON cev.fk_expenseType = c.id";
				$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_tva as tva ON tva.rowid = cev.fk_vat_tx";

				$tabsql[17] = $sql;
				$tabfieldvalue[17] = "code,label,accountancy_code,fk_tva";
				$tabfield[17] = "code,label,accountancy_code,fk_tva";

				// color type congé
				$sql = 'SELECT h.rowid as rowid, h.code, h.label, h.affect, h.delay, h.newbymonth, h.fk_country as country_id, c.code as country_code, c.label as country, h.block_if_negative, h.active, chc.color
						FROM '.MAIN_DB_PREFIX.'c_holiday_types as h
						LEFT JOIN '.MAIN_DB_PREFIX.'c_country as c ON h.fk_country=c.rowid
						LEFT JOIN '.MAIN_DB_PREFIX.'advancedhrm_holidaytypecolor as chc ON chc.fk_holidayType=h.rowid';

				$tabsql[28] = $sql;
				$tabfieldvalue[28] = "code,label,affect,delay,newbymonth,country,block_if_negative,color";
				$tabfield[28] = "code,label,affect,delay,newbymonth,country_id,country,block_if_negative,color";

				if (GETPOST('actionmodify')) // modify entry
				{
					if($parameters['id'] == 17) { // TVA & can_invite_guests & is_type_meal

						dol_include_once('advancedhrm/class/expensetypevatlink.class.php');
						$exptvalink = new ExpenseTypeVATLink($this->db);
						$ret = $exptvalink->fetchAll('asc', 'rowid', '1', 0, array('t.fk_expenseType' => $parameters['rowid']));
						if (empty($ret)) {
							$exptvalink->fk_expenseType = $parameters['rowid'];
							$exptvalink->fk_vat_tx = GETPOST('fk_tva', 'int');
							$res = $exptvalink->create($user);

						} else {
							$ret[0]->fk_vat_tx = GETPOST('fk_tva', 'int');
							$ret[0]->update($user);
						}



					} elseif($parameters['id'] == 28) { // Holiday type color

						dol_include_once('advancedhrm/class/holidaytypecolor.class.php');
						$holidaytypecolor = new HolidayTypeColor($this->db);
						$ret = $holidaytypecolor->fetchAll('asc', 'rowid', '1', 0, array('t.fk_holidayType' => $parameters['rowid']));

						if (empty($ret)) {

							$holidaytypecolor->fk_holidayType = $parameters['rowid'];
							$holidaytypecolor->color = GETPOST('colortypeconges', 'alpha');
							$res = $holidaytypecolor->create($user);

						} else {
							$first_elmnt_key = $ret[key($ret)];
							$first_elmnt_key->color = GETPOST('colortypeconges', 'alpha');
							$first_elmnt_key->update($user);
						}

					}

				}
				else if (GETPOST('actionadd')) // add entry
				{

					$tabrowid = $parameters['tabrowid'];
					$tabname = $parameters['tabname'];

					$id = $parameters['id'];

					$newid = 0;
					$tab_name = $tabname[$id];
					if(strpos($tab_name, MAIN_DB_PREFIX) === false) $tab_name = MAIN_DB_PREFIX.$tabname[$id];
					$sql = "SELECT max(".$tabrowid[$id].") newid from ".$tab_name;
					$result = $this->db->query($sql);
					if ($result) {
						$obj = $this->db->fetch_object($result);
						$newid = ($obj->newid + 1);
					}

					if (!empty($newid))
					{
						if($parameters['id'] == 17) { // TVA & can_invite_guests

							dol_include_once('advancedhrm/class/expensetypevatlink.class.php');
							$exptvalink = new ExpenseTypeVATLink($this->db);


							$exptvalink->fk_expenseType = $newid;
							$exptvalink->fk_vat_tx = GETPOST('fk_tva', 'int');
							$res = $exptvalink->create($user);


						} elseif($parameters['id'] == 28) { // Holiday type color

							dol_include_once('advancedhrm/class/holidaytypecolor.class.php');
							$holidaytypecolor = new HolidayTypeColor($this->db);

							$holidaytypecolor->fk_holidayType = $newid;
							$holidaytypecolor->color = GETPOST('colortypeconges', 'alpha');
							$res = $holidaytypecolor->create($user);

						}
					}
				} elseif($action === 'confirm_delete') {

					if($parameters['id'] == 17) { // TVA & can_invite_guests & is_type_meal

						// TVA
						dol_include_once('advancedhrm/class/expensetypevatlink.class.php');
						$exptvalink = new ExpenseTypeVATLink($this->db);
						$ret = $exptvalink->fetchAll('asc', 'rowid', '1', 0, array('t.fk_expenseType' => $parameters['rowid']));
						if(!empty($ret)) {
							$obj_exptvalink = $ret[key($ret)];
							$obj_exptvalink->delete($user);
						}


					} elseif($parameters['id'] == 28) {
						dol_include_once('advancedhrm/class/holidaytypecolor.class.php');
						$holidaytypecolor = new HolidayTypeColor($this->db);
						$ret = $holidaytypecolor->fetchAll('asc', 'rowid', '1', 0, array('t.fk_holidayType' => $parameters['rowid']));

						if(!empty($ret)) {
							$obj_holidaytypecolor = $ret[key($ret)];
							$obj_holidaytypecolor->delete($user);
						}
					}

				}
			}
		}

		if (!$error) {

			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}

	/**
	 * Overloading the addMoreActionsButtons function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;
		$langs->load('advancedhrm@advancedhrm');
		$error = 0; // Error counter
		$TContext = explode(":", $parameters['context']);

		if (in_array('expensereportcard', $TContext))
		{

			if ($object->status == ExpenseReport::STATUS_DRAFT ) // fill vat field automatically
			{
				?>
				<script>
					$(document).ready(function(){



						$('#fk_c_type_fees').on('change', function(e){

							let expenseType = $(this).val();
							$.ajax({
								url: "<?php echo dol_buildpath('/advancedhrm/scripts/expenseReportInterface.php',2) ?>",
								data: {
									get: 'getInfos',
									expenseType: expenseType
								},
								dataType: 'json'
							}).done(function(response){
								if (response.success)
								{
									$('#vatrate').val(response.return);
								}
								else
								{
									$('#vatrate').val(0);
								}

							});

						});

					});
				</script>
				<?php
			}
		}

		if (!$error) {
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}



	/**
	 * modify dictionnary fieldList on creation
	 * @param $parameters
	 * @param $object
	 * @param $action
	 * @param $hookmanager
	 * @return int
	 */
	public function createDictionaryFieldlist(&$parameters, &$object, &$action, $hookmanager)
	{
		$TContext = explode(":", $parameters['context']);

		if (in_array('dictionaryadmin', $TContext))
		{
			if (strpos($parameters['tabname'], 'c_type_fees') !== false)
			{
				global $fieldlist;

				if (!in_array('fk_tva', $fieldlist)) $fieldlist[] = 'fk_tva';

			} elseif(strpos($parameters['tabname'], 'c_holiday_types') !== false) {

				// Affichage du color picker pour le dicitonnaire des types de congés
				$this->printColorPicker(GETPOST('action', 'alpha'));
				$this->printColorEachLine();

			}
		}

		return 0;
	}

	/**
	 * modify dictionnary fieldList on edit mode
	 * @param $parameters
	 * @param $object
	 * @param $action
	 * @param $hookmanager
	 * @return int
	 */
	public function editDictionaryFieldlist(&$parameters, &$object, &$action, $hookmanager)
	{
		$TContext = explode(":", $parameters['context']);

		if (in_array('dictionaryadmin', $TContext))
		{
			if (strpos($parameters['tabname'], 'c_type_fees') !== false)
			{
				global $fieldlist, $obj;

				if (!in_array('fk_tva', $fieldlist)) $fieldlist[] = 'fk_tva';

			} elseif(strpos($parameters['tabname'], 'c_holiday_types') !== false) {

				// Affichage du color picker pour le dicitonnaire des types de congés
				$this->printColorPicker(GETPOST('action', 'alpha'));
//				$this->printColorEachLine();

			}
		}
		return 0;
	}

	function printColorPicker($action='') {

		require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

		dol_include_once('advancedhrm/class/holidaytypecolor.class.php');
		$holidaytypecolor = new HolidayTypeColor($this->db);
		$ret = $holidaytypecolor->fetchAll('asc', 'rowid', '1', 0, array('t.fk_holidayType' => GETPOST('rowid', 'int')));
		if(!empty($ret) && $action === 'edit') {
			$color = $ret[key($ret)]->color;
		} else $color = '';

		$formother = new FormOther($this->db);
		print '<div style="display:none;" id="div_color_picker_conges">'.$formother->selectColor($color, 'colortypeconges').' </div>';

		?>

		<script>

			$(document).ready(function() {
				<?php if($action === 'edit') { ?>
					$("[name='color']").each(function() {

						if(typeof $(this).closest('tr').attr('id') !== 'undefined') {
							$(this).parent("td").html($("#div_color_picker_conges").show());
						}
					});
				<?php } else { ?>
					$("[name='color']").parent("td").html($("#div_color_picker_conges").show());
				<?php } ?>
			});

		</script>

		<?php

	}

	public function printColorEachLine() {

		// On récupère tous les types de congés avec leur couleur pour afficher
		$sql = 'SELECT type.rowid, c.color
				FROM '.MAIN_DB_PREFIX.'c_holiday_types type
				LEFT JOIN '.MAIN_DB_PREFIX.'advancedhrm_holidaytypecolor c ON c.fk_holidayType = type.rowid';

		$resql = $this->db->query($sql);
		if(!empty($resql)) {
			while($res = $this->db->fetch_object($resql)) {
				if(empty($res->color)) continue;
				?>
				<script>
					$(document).ready(function () {
						// Ici je parcoure toutes les TD pour mettre de la couleur en fond sur celle qui a pour contenu le code hexa de la couleur de ma ligne
						// J'ai pas le choix, car les TD ne sont pas identifiées
						$("#rowid-<?php print $res->rowid ?>").find('td').each(function() {

							if($(this).html() == "<?php print $res->color ?>") {
								$(this).attr('bgcolor', "#<?php print $res->color; ?>");
							}

						});
					});
				</script>
				<?php
			}
		}

	}

	/**
	 * modify dictionnary fieldList on view mode
	 * @param $parameters
	 * @param $object
	 * @param $action
	 * @param $hookmanager
	 * @return int
	 */
	public function viewDictionaryFieldlist(&$parameters, &$object, &$action, $hookmanager)
	{
		$TContext = explode(":", $parameters['context']);

		if (in_array('dictionaryadmin', $TContext))
		{
			if (strpos($parameters['tabname'], 'c_type_fees') !== false)
			{
				global $fieldlist, $form;

				if (!in_array('fk_tva', $fieldlist)) $fieldlist[] = 'fk_tva';
				if (empty($this->loadedVATLabels))
				{
					foreach ($form->cache_vatrates as &$vatTab)
					{
						$vatTab['libtva'] = $vatTab['label'];
					}
				}

//				var_dump($parameters, $object);
			}
		}

		return 0;
	}

	/**
	 * @param $parameters
	 * @param $object
	 * @param $action
	 * @param $hookmanager
	 * @return void
	 */
	public function formObjectOptions(&$parameters, &$object, &$action, $hookmanager){

		global $db, $conf, $form, $langs, $user, $mysoc;

		$TContext = explode(":", $parameters['context']);

		if (in_array('expensereportcard', $TContext) && $action !== 'create')
		{

			/** ***************************************************************************/
			/*********************Gestion des invités*************************************/
			/** ***************************************************************************/

			// Instanciation d'un tableau de users associés aux lignes de cette NDF
			$sql  =" SELECT det.rowid as id_line, u.rowid as user_id from ".MAIN_DB_PREFIX."expensereportdet_user as edu";
			$sql .=" INNER JOIN ".MAIN_DB_PREFIX."expensereport_det det ON (det.rowid = edu.fk_expensereportdet)";
			$sql .=" LEFT JOIN  ".MAIN_DB_PREFIX."user u ON u.rowid = edu.fk_user ";
			$sql .=" WHERE det.fk_expensereport=".((int)$object->id);

			$resql = $db->query($sql);
			$UsersByLine = array('ids' => array(), 'getNomUrl'=> array());
			if ($resql) {

				while ($ob = $db->fetch_object($resql)) {
					$id_line = $ob->id_line;

					$u = new User($db);
					$result = $u->fetch($ob->user_id);
					if ($result) {
						$UsersByLine['getNomUrl'][$id_line] .= $u->getNomUrl(1) . '<br>';
						$UsersByLine['ids'][$id_line][] = $u->id;
					}
				}
			}

			// Types de dépenses pour lesquels il peut y avoir une invitation d'utilisateurs
			$TTypeWithInvitationUsers = explode(',',getDolGlobalString('ADVANCEDHRM_NDF_TYPE_IS_INVITATIONAL'));

			// Instanciation du multiselect user
			print '<div id="select_users_guests">';
			$userlist = $form->select_dolusers('', '', 0, null, 0, '', '', 0, 0, 0, '', 0, '', '', 0, 1);
			//$arrayselected = GETPOST('commercial', 'array');

			print $form->multiselectarray('users', $userlist, !empty($UsersByLine['ids'][GETPOST('rowid', 'int')]) ? $UsersByLine['ids'][GETPOST('rowid', 'int')] : '', null, null, null, null, "300px;");
			print '</div>';

			// Affichage du titre de la colonne invités
			$this->printColonneInvites();

			// Affichage du multiselect user en mode vue et editline
			$colspan_plus=1; // initialisation du nombre de td à ajouter dans les colspan
			?>

			<script>

				$(document).ready(function() {

					let TTypeWithInvitationUsers = <?php echo json_encode($TTypeWithInvitationUsers); ?>;

					$('<td id="td_select_users_guests"></td>').insertAfter($("#fk_c_type_fees").parent('td'));
					$("#td_select_users_guests").append($("#select_users_guests"));


					// Réorganisation des colspan en fonction des ajouts de colonne du module
					let colspan_plus = <?php echo json_encode($colspan_plus); ?>;
					let class_trattachnewfilenow_colspan = $(".trattachnewfilenow").find('td').attr('colspan');
					let class_auploadnewfilenow_colspan = $(".auploadnewfilenow").parent().attr('colspan');
					$(".truploadnewfilenow").find('td').each(function() {
						if($(this).attr('colspan') !== undefined) {
							$(this).attr('colspan', parseInt($(this).attr('colspan'))+parseInt(colspan_plus))
						}
					});
					$(".trattachnewfilenow").find('td').attr('colspan', parseInt(class_trattachnewfilenow_colspan)+parseInt(colspan_plus));
					$(".auploadnewfilenow").parent().attr('colspan', parseInt(class_auploadnewfilenow_colspan)+parseInt(colspan_plus));


					// Quand on change manuellement le type de dépenses
					$("#fk_c_type_fees").change(function() {

						if(TTypeWithInvitationUsers.indexOf($(this).val()) > -1) { // C'est une dépense qui peut avoir des invités
							$("#select_users_guests").show();
						} else {
							$("#users").val(0).change(); // We clear selected guests if we change expense report type to one with we can't invite users
							$("#select_users_guests").hide();
						}
					});


					// Lorsqu'on arrive sur la page, on veut savoir si le type de dépense au début de la liste doit faire apparaître le sélecteur d'invités
					$("#fk_c_type_fees").change();


				});

			</script>

			<?php


			// Affichage des utilisateurs liés aux lignes
			if(!empty($object->lines)) {

				?>

					<script>

						$(document).ready(function() {

							let UsersByLine = <?php echo json_encode($UsersByLine['getNomUrl']); ?>;

							$("#tablelines").find(".linetr").each(function() {
								if (UsersByLine){
									if(typeof UsersByLine[$(this).data('id')] !== 'undefined'){
										$(this).find(".contenu_line_invite").append(UsersByLine[$(this).data('id')]);
									}
								}

							});

						});

					</script>

				<?php

			}

			/** ***************************************************************************/
			/*********************Fin gestion des invités*********************************/
			/** ***************************************************************************/

			/** *****************************************************************************/
			/**On masque le sélecteur de catégorie de véhicule si type dépense != Frais km**/
			/** *****************************************************************************/

			if(getDolGlobalString('MAIN_USE_EXPENSE_IK')) {

				$q = 'SELECT id FROM ' . MAIN_DB_PREFIX . 'c_type_fees WHERE code = "EX_KME"';
				$resql = $db->query($q);
				if (!empty($resql)) {

					$res = $db->fetch_object($resql);
					$id_depense_km = $res->id;

					?>

					<script>

						$(document).ready(function () {

							// Utilisation d'une sorte de namespace en JS
							var Advancehrmlineupdate = {};
							(function (o) {
								o.initSelect2ForIsLoaded = false;
								o.id_depense_km = <?php print json_encode($id_depense_km) ?>;
								o.cTypeFee = $('#fk_c_type_fees');
								o.IdForWrapToggleDisplayCatVehicule = 'toggle-display-cat-vehicule';
								o.select_type_vehicule = $("#select_fk_c_exp_tax_cat");
								o.ToggleDisplayCatVehicule = function () {
									let type_depense_id = o.cTypeFee.val();
									if (type_depense_id === o.id_depense_km) {
										$("#" + o.IdForWrapToggleDisplayCatVehicule).show();
									} else {
										$(o.select_type_vehicule).val(0).change();
										$("#" + o.IdForWrapToggleDisplayCatVehicule).hide();
									}
								};

								// Fonction permettant d'attendre le chargement du select2 avant d'exécuter le code qui affiche oucache le sélecteur de type de véhicule selon le type de dépense
								o.initSelect2For = function () {
									if (!o.initSelect2ForIsLoaded) {
										setTimeout(function () {
											if (o.select_type_vehicule.attr('data-select2-id') != undefined) {
												$(o.select_type_vehicule).parent('td').wrapInner("<div id='" + o.IdForWrapToggleDisplayCatVehicule + "'></div>"); // On place tout le contenu de la td dans une div pour faciliter la manipulation
												o.ToggleDisplayCatVehicule();
												o.initSelect2ForIsLoaded = true;
											} else {
												o.initSelect2For();
											}
										}, 10);
									}
								}
							})(Advancehrmlineupdate);


							// Quand on change manuellement le type de dépenses
							$(document).on('change', "#fk_c_type_fees", function () {
								Advancehrmlineupdate.ToggleDisplayCatVehicule();
							});
							Advancehrmlineupdate.initSelect2For();
						});

					</script>

					<?php

				}

			}
			/** *****************************************************************************/
			/****Fin bloc sélecteur de catégorie de véhicule si type dépense != Frais km****/
			/** *****************************************************************************/



			/** *****************************************************************************/
			/** GOOGLE MAP **/
            if(getDolGlobalString('ADVANCEDHRM_ENABLE_GOOGLEMAPS')) {
			/* 	https://developers.google.com/maps  connexion (via google credentials )
				https://console.cloud.google.com/google/maps-apis/overview
				creer un projet
				creer une api key pour le projet

				cliquer sur le lien de l'api pour le detail

				on peu definir une restriction pour un site ne particulier (en local un hosts est necessaire )  +  a2ensite / sinon l'adresse cliente webhost ...
				activer direction distance matrix et map dans le combo box  0 / n API

				ON VOIT LES API ACTIVES en dessous
				API sélectionnées :
				Directions API
				Distance Matrix API
				Maps JavaScript API

				on est ready to go

				attention ces apis ne sont gratuites que pour un certain nombre d'appels ...

			 */
			/** *****************************************************************************/


			require_once (__DIR__ . '/../tpl/map.default.tpl.php');
			require_once (__DIR__ . '/../tpl/restaurant_address.default.tpl.php');
			//require_once (__DIR__.'/../lib/calculate.lib.php');
			// selection de l'id des frais kilometrique EX_KME
			$sql = "SELECT c.id FROM ".MAIN_DB_PREFIX."c_type_fees as c WHERE c.code = 'EX_KME' AND c.active = 1";
			$resql = $db->query($sql);
			if ($resql)
			{
				if ($db->num_rows($resql) > 0)
				{
					$obj = $db->fetch_object($resql);
				}
			}

			// le user pour laquelle la note de frais est créée.
			$userId = $object->fk_user_author;
			$typeFeesId=$obj->id;


			print '<script src="'.dol_buildpath('/advancedhrm/js/GoogleConnector.class.js', 1).'"></script>';
			?>
			<link rel="stylesheet" type="text/css" href="<?php  echo dol_buildpath('/advancedhrm/css/advancedhrm.css',1);?>">
			<script>

				//fonction pour le calcul du trajet entre 2 villes (distance + affichage sur carte)
				/**
				 *
				 * @param s start position
				 * @param e end position
				 */
				function calculerTrajet (s,e, callBackFunc = 'callback') {
					var start = s;
					var end = e;
					var service = new google.maps.DistanceMatrixService();
					service.getDistanceMatrix({
						origins: [start],
						destinations: [end],
						travelMode: 'DRIVING',
					}, window[callBackFunc]);
				}


				/**
				 *
				 * @param response
				 * @param status
				 */
				function callback(response, status) {

					let advancedhrm_gmap_distanceFinale,  advancedhrm_gmap_distanceFinale_km= '';

					// See Parsing the Results for
					if (response.status="OK") {

						var google_status = response.rows[0].elements[0].status;
						var origins = response.originAddresses;
						var destinations = response.destinationAddresses;
						if(google_status=='OK') {

							var request = {
								origin: origins[0],
								destination: destinations[0],
								travelMode: 'DRIVING'
							};

							GC.directionsService.route(request, function(result, status) {
								if (status == 'OK') {
									GC.directionsRenderer.setDirections(result);
								}
							});


							// sinon valeur brut si stockage en metres
							advancedhrm_gmap_distanceFinale=response.rows[0].elements[0].distance.value;
							advancedhrm_gmap_distanceFinale_km=(advancedhrm_gmap_distanceFinale / 1000);
							advancedhrm_gmap_distanceFinale_km = Math.round(advancedhrm_gmap_distanceFinale_km * 100) / 100;

							if($('#allerRetour').prop('checked')){
								advancedhrm_gmap_distanceFinale_km *= 2;
							}
							let backAndForth = "<?php echo $langs->transnoentities("backAndForth");?>"
							let msgTrajet = '<?php echo $langs->transnoentities("Travel");?>'+ ' ' + origins + ' <?php echo $langs->transnoentities("And");?>'+ ' ' + destinations

							if($('#allerRetour').prop('checked')){
								msgTrajet += " (" +backAndForth+')';
							}
							let msg = '<?php echo $langs->transnoentities("DistBetween");?>'+ '</br></br> <b>' + origins + '</b> <?php echo $langs->transnoentities("And");?>'+ '</br><b> ' + destinations + '</b>'

							let dist =    '</br></br><b><h3>' + advancedhrm_gmap_distanceFinale_km + 'Km</h3>';
							if($('#allerRetour').prop('checked')){
								dist += ' (' + backAndForth + ")";
							}
							$("#presCalcul").html('<p>' + msg + " " + dist +'</p>');

							// stockage dans le Google connector pour utilisation ultérieure
							GC.distanceFinale_km =  advancedhrm_gmap_distanceFinale_km;
							GC.startTown = origins[0];
							GC.endTown = destinations[0];
							GC.msg = msgTrajet;


						} else if(google_status=="NOT_FOUND"){
							$("#presCalcul").html("<p><?php echo $langs->transnoentities('ErrorCannotFindAdress');?></p>");
						} else if(google_status=="ZERO_RESULTS"){
							$("#presCalcul").html("<p><?php echo $langs->transnoentities('ErrorCannotCalcIntinary');?></p>");
						} else{
							$("#presCalcul").html("<p><?php echo $langs->transnoentities('ErrorMessageStd');?></p>");

						}

					}
					else{
						$("#presCalcul").html("<p><?php echo $langs->transnoentities('ErrorMessageStd');?></p>");
					}
				}




				function calculDistanceFromGmap(response){


					let distanceInfo = {
						status: 0,
						distanceFinale : 0,
						distanceFinale_km : 0,
					};


					// See Parsing the Results for
					if (response.status="OK") {
						distanceInfo.status = response.rows[0].elements[0].status;

						// sinon valeur brut si stockage en metres
						if(typeof response.rows[0].elements[0].distance != 'undefined') {
							distanceInfo.distanceFinale = response.rows[0].elements[0].distance.value;
							distanceInfo.distanceFinale_km = (distanceInfo.distanceFinale / 1000);
							distanceInfo.distanceFinale_km = Math.round(distanceInfo.distanceFinale_km * 100) / 100;
						}
					}

					return distanceInfo;
				}


				/**
				 *
				 * @param response
				 * @param status
				 */
				function callbackDistanceRestaurantWork(response, status) {

					let distanceInfo = calculDistanceFromGmap(response);

					// See Parsing the Results for
					if (distanceInfo.status="OK") {


						let $buttonWithAttr = $('#advancedhrm_gmap_validerParcoursRestaurantAddress');

						$buttonWithAttr.attr('data-distance-work', distanceInfo.distanceFinale_km);
						// console.log('la');
						// console.log(distanceInfo.distanceFinale_km);
						// console.log($buttonWithAttr.attr('data-address-home'));
						// console.log('apres');
						calculerTrajet($('#restaurantAddress').val(), $buttonWithAttr.attr('data-address-home'), 'callbackDistanceRestaurantHome');
					}
					else{
						$("#presCalcul").html("<h3><?php echo $langs->transnoentities('ErrorMessageStd');?></h3>");
					}
				}


				/**
				 *
				 * @param response
				 * @param status
				 */
				function callbackDistanceRestaurantHome(response, status) {

					let distanceInfo = calculDistanceFromGmap(response);

					// See Parsing the Results for
					if (distanceInfo.status="OK") {

						let $buttonWithAttr = $('#advancedhrm_gmap_validerParcoursRestaurantAddress');

						let distanceWork = $buttonWithAttr.attr('data-distance-work');
						let minDistance = $buttonWithAttr.attr('data-min-distance');

						let distanceHome = distanceInfo.distanceFinale_km;
						$buttonWithAttr.attr('data-distance-home', distanceHome);

						var google_status = response.rows[0].elements[0].status;

						if(google_status=="NOT_FOUND"){
							$.jnotify($buttonWithAttr.attr('data-error-not-found-distance-message'), "error", true);
						} else {
							if (distanceHome > minDistance && distanceWork > minDistance) {
								$("[name=add]").prop('disabled', false);
								$("[name=add]").prop('title', '');
								$('#popupRestaurantAddress').dialog('close');
								$('textarea[name="comments"]').val($buttonWithAttr.attr('data-distance-message-for-desc') + ' ' + $("#restaurantAddress").val());
							} else {

								$.jnotify($buttonWithAttr.attr('data-error-distance-message').replace('kmR_D', distanceHome).replace('kmR_T', distanceWork), "error", true);

							}
						}

					}
					else{
						$("#presCalcul").html("<h3><?php echo $langs->transnoentities('ErrorMessageStd');?></h3>");
					}
				}


				/////////////////////////////////////GESTION DU DOCUMENT\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
				$(document).ready( function(){

					action = <?php echo json_encode($action); ?>;
					userId = <?php echo json_encode($user->id); ?>;
					typeFeeId = <?php echo json_encode($obj->id); ?>;
					GC = new GoogleConnector(typeFeeId,userId);

					addMarkerOnCard();

					/**
					 *
					 */
					function addMarkerOnCard(){
						let tdqty;
						//console.log('i m here');
						/*if (action == 'editline'){
							 tdqty = $('input[name="qty"]').parent();
						}else{
							 tdqty = $(".inputqty")
						}*/
						// td id uniformized on develop
						tdqty = $(".input_qty").parent("td");

						//console.log(tdqty)

						let idEX_KME = $("#fk_c_type_fees").find(":selected").val();
						console.log(idEX_KME == GC.ID_EX_KME);
						if (idEX_KME == GC.ID_EX_KME){
							tdqty.next().append('<a href="javascript:;" class="popGoogleMap" style="display:block"><i class="fa fa-map-marker" aria-hidden="true" style="color: #ee0000;font-size: large;margin: 5px;"></i></a>');

						}else{
							$(".popGoogleMap").remove();
						}
					}
					// Marker display on create form
					/**
					 * EVENT
					 */
					$(document).on('change', "#fk_c_type_fees", function () {
						console.log('change fee');
						addMarkerOnCard()

					});
					// click icon map popup
					/**
					 * EVENT
					 */
					$(document).on('click', ".popGoogleMap", function (e) {
						window.initMap = GC; //initializeGoogleMaps();
						opt = {
							title : '<?php echo $langs->transnoentities('JourneySelection') ?>',
							height: 720,
							width :1070
						};
						$('#popupCalculFraisKilometrique').dialog(opt).dialog('open');

					});

					// click icon favoris popup
					/**
					 * EVENT
					 */
					$(document).on('click', ".popFavoris", function (e) {

						opt = {
							title : '<?php echo $langs->trans('favoritesListe') ?>',
							height: 800,
							width :800
						};
						$('#popupFavoris').dialog(opt).dialog('open');
						listeTrajetsFavoris();
					});
					/**
					 * EVENT
					 */
					$(document).on('click', "#popupCalculFraisKilometrique #advancedhrm_gmap_validerParcours", function (e) {
						calculerTrajet($('#depart').val(),$('#arrivee').val())
						$("#advancedhrm_gmap_validerParcoursFinal_block").show();
					});
					/**
					 * EVENT
					 */
					$('#popupCalculFraisKilometrique #advancedhrm_gmap_validerParcoursFinal').click(function(){
						selectionnerTrajet();
					});
					/**
					 * EVENT
					 */
					$('#popupCalculFraisKilometrique #advancedhrm_gmap_validerParcoursFinal_Favoris').click(function(){
						selectionnerTrajet();
					});

					$('#popupCalculFraisKilometrique #advancedhrm_gmap_validerParcoursFinal').click(function(){
						if($('#advancedhrm_gmap_AjoutFavoris').prop('checked')){
							ajouterTrajetFavoris();
							selectionnerTrajet();
						}
					});


					$('#depart').change(function(){

						$("#presCalcul").html('');
					});



					$('#arrivee').change(function(){
						$("#presCalcul").html('');
					});



					/**
					 * close popup
					 * ajout qty  = distance kilometres total
					 * AJOUT COMMENTAIRE DE LIGNE
					 * calcul  TTC
					 *
					 */

					function selectionnerTrajet(){

						if(GC.distanceFinale_km > 0){
							let inputQyt;
							let inputComment;
							if (action == 'editline'){
								inputQyt = $('input[name="qty"]');
								inputComment = $('textarea[name="comments"]');
							}else{
								inputQyt = $(".inputqty>:input");
								inputComment = $(".inputcomment>:input");
							}
							inputQyt.val(GC.distanceFinale_km).trigger("keydown");
							inputComment.val(GC.msg);

							//setAddLineOrNot();
							$('#popupCalculFraisKilometrique').dialog('close');

							// selfmade function
							//calculate_pu_ht(GC.distanceFinale_km);
							//computeTTC(GC.distanceFinale_km);
							//changeTTC(null);


						}else {
							$("#presCalcul").html('<p><?php echo $langs->transnoentities('errorCalculateTrip');?> </p>');
						}
					};

					/** ************************************************************
					 * FAVORIS
					 ***************************************************************/
					/**
					 *
					 */
					listeTrajetsFavoris = function(){
						advancedhrm_gmap_fk_user=$('#fk_user').val();
						$.ajax({
							url: "<?php echo dol_buildpath('/advancedhrm/interface/interface.favoris.php', 1); ?>?fk_user="+advancedhrm_gmap_fk_user
							,async:false
							,dataType:'html'
							,success:function(tabFavoris) {
								$("#tabFavoris").html(tabFavoris);
							}
						});
					};
					/**
					 *
					 * @param depart
					 * @param arrivee
					 */
					selectFavoris = function(depart, arrivee){
						$('#depart').val(depart);
						$('#arrivee').val(arrivee);
						$('#popupFavoris').dialog('close');
						//calculerTrajet(depart,arrivee);
					};
					/**
					 *
					 */
					ajouterTrajetFavoris = function(){
						if(GC.distanceFinale_km > 0 ){
							console.log("<?php dol_buildpath('/advancedhrm/interface/interface.favoris.php', 1); ?>?origins="+  GC.startTown + "&destinations=" + GC.endTown + "&fk_user="+GC.fk_user+"&action=add")
							$.ajax({
								url: "<?php echo dol_buildpath('/advancedhrm/interface/interface.favoris.php', 1); ?>?origins="+  GC.startTown + "&destinations=" + GC.endTown + "&fk_user="+GC.fk_user+"&action=add"
								,async:false
								,dataType:"json"
								,success:function(result) {
									if (result == -1){
										console.log('error not created')
									}
								}
							});
						}else {
							$("#presCalcul").html('<p><?php $langs->trans('Errorjourney'); ?> </p>');
						}
					};
					/**
					 *
					 * @param trajet_id
					 */
					supprimerFavoris = function(trajet_id){
						$.ajax({
							url: "<?php echo dol_buildpath('/advancedhrm/interface/interface.favoris.php', 1); ?>?id="+trajet_id+"&action=delete"
							,async:false
							,dataType:'html'
							,success:function(result) {

							}
						});
						listeTrajetsFavoris();
					};
					/** ************************************************************
					 * FIN FAVORIS
					 ***************************************************************/



					/** *****************************************************************************/
					/***********Refus ajout ligne si distance inférieure à la configuration**********/
					/** *****************************************************************************/

					<?php
					if(getDolGlobalString('ADVANCEDHRM_CONSIDER_REGULATIONS_FOR_MEAL_TYPE') && getDolGlobalString('ADVANCEDHRM_MEAL_TYPE_MINIMUM_DISTANCE')) {

                        $TTypeMeal = explode(',', getDolGlobalString('ADVANCEDHRM_NDF_TYPE_IS_MEAL'));

						require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
						$user_ndf = new User($db);
						$user_ndf->fetch($object->fk_user_author);

						$adressePerso = trim($user_ndf->address.' '.$user_ndf->zip.' '.$user_ndf->town);
						$adresseSociete = $mysoc->address.' '.$mysoc->zip.' '.$mysoc->town;

						?>


							let TTypeMeal = <?php echo json_encode($TTypeMeal); ?>;

							let addressHome = <?php print json_encode($adressePerso); ?>;
							let addressWork = <?php print json_encode($adresseSociete); ?>;
							let minDistance = <?php print json_encode(getDolGlobalString('ADVANCEDHRM_MEAL_TYPE_MINIMUM_DISTANCE')); ?>;
							let errorDistanceMessage = <?php print json_encode($langs->trans('errorDistanceMessage', getDolGlobalString('ADVANCEDHRM_MEAL_TYPE_MINIMUM_DISTANCE'))); ?>;
							let errorDistanceMessageForDesc = <?php print json_encode($langs->transnoentities('RestaurantAddressForDesc')); ?>;
							let ErrorCannotCalcIntinary = <?php print json_encode($langs->transnoentities('ErrorCannotCalcIntinary')); ?>;
							let ErrorUserDoesntHaveAddress = <?php print json_encode($langs->transnoentities('ErrorUserDoesntHaveAddress')); ?>;
							let desc_line = <?php

								if(GETPOSTISSET('comments')) {
									print json_encode(strtr(GETPOST('comments'), array($langs->transnoentities('RestaurantAddressForDesc').' ' => '')));
								} elseif($action === 'editline') {
									foreach ($object->lines as $l) {
										if($l->id == GETPOST('rowid')) {
											print json_encode(strtr($l->comments, array($langs->transnoentities('RestaurantAddressForDesc').' ' => '')));
											break;
										}
									}
								} else {
									print json_encode('');
								}

							?>;

							$(document).on('change', "#fk_c_type_fees", function () {
                                if(TTypeMeal.indexOf($(this).val()) > -1) { // C'est une dépense de type repas
                                    $("[name=add]").prop('disabled', true);
                                    $("[name=comments]").prop('readonly', true);

									window.initMap = GC; //initializeGoogleMaps();
									opt = {
										title : <?php echo json_encode($langs->transnoentities('RestaurantAddressFill')) ?>,
										height: 250,
										width :800
									};
									console.log(addressHome);
									if(addressHome !== '') {
										$('#popupRestaurantAddress').dialog(opt).dialog('open');
										$('#restaurantAddress').focus();
									} else {
										$.jnotify(ErrorUserDoesntHaveAddress, "error", true);
									}
									$("[name=add]").prop('title', <?php echo json_encode($langs->transnoentities('restaurantAddressButtonDisabledMessage')); ?>);

								} else {
									$("[name=add]").prop('disabled', false);
									$("[name=comments]").prop('readonly', false);
									$("[name=add]").prop('title', '');
								}
							});

							$(document).on('click', "#advancedhrm_gmap_validerParcoursRestaurantAddress", function () {

								$(this).attr('data-address-home', addressHome);
								$(this).attr('data-address-work', addressWork);
								$(this).attr('data-min-distance', minDistance);
								$(this).attr('data-error-distance-message', errorDistanceMessage);
								$(this).attr('data-distance-message-for-desc', errorDistanceMessageForDesc);
								$(this).attr('data-error-not-found-distance-message', ErrorCannotCalcIntinary);

								calculerTrajet($('#restaurantAddress').val(), addressWork, 'callbackDistanceRestaurantWork');

							});

							// Déclenchemens systématique, car si on est en mode edit ça doit remettre le pop in
							$("#fk_c_type_fees").change();
							$('#restaurantAddress').val(desc_line);

					<?php
					} // fin bloc if(!empty($conf->global->ADVANCEDHRM_CONSIDER_REGULATIONS_FOR_MEAL_TYPE) && !empty($conf->global->ADVANCEDHRM_MEAL_TYPE_MINIMUM_DISTANCE))
					?>
					/** *****************************************************************************/
					/***********Fin refus ajout ligne si distance inférieure à la configuration******/
					/** *****************************************************************************/

				});
			</script>

			<?php


			/*******************************************************************************/
			/** FIN FRAIS KILOMETRIQUES GOOGLE MAP **/
			/*******************************************************************************/
			}
		}

		if (in_array($parameters['currentcontext'], array('holidaycard'))) {
			if (GETPOSTISSET('dol_openinpopup')) // context de la card planning ouverte via une popin
			{
				?>
                <script type="application/javascript">
                    $(document).ready(function () {
                        $('input[name="cancel"]').on('click', function () {
                            window.parent.jQuery('.ui-dialog-titlebar-close').click();
                        });
                    })
                </script>
				<?php
			}
		}

		// Inclusion de la classe compteurCongeManager
		require_once (__DIR__.'/compteurHolidayManager.php');  // Remplacez par le bon chemin

		if (in_array('usercardBank', $TContext)) {
			$compteurManager = new compteurHolidayManager();
			$compteurManager->addTableOfTypeHolidays();
		}
	}

	/**
	 * @param $parameters
	 * @param $object
	 * @param $action
	 * @param $hookmanager
	 * @return void
	 */
	public function printCommonFooter($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs, $delayedhtmlcontent;
		$TContext = explode(':', $parameters['context']);
		if(in_array('expensereportcard', $TContext) && getDolGlobalString('ADVANCEDHRM_ENABLE_GOOGLEMAPS')) $delayedhtmlcontent .=  '<script src="https://maps.googleapis.com/maps/api/js?key=' . getDolGlobalString('ADVANCEDHRM_KEYAPI_GOOGLEMAPS').'"></script>';

		return 0;
	}

    public function printUserListWhere($parameters, &$object, &$action, $hookmanager)
	{
		global $conf;
		$TContext = explode(':', $parameters['context']);

		if(in_array('planningUser', $TContext)) {
            if(!getDolGlobalString('ADVANCEDHRM_DISPLAY_ALL_USERS')) {
				$this->resprints =  "  WHERE u.entity IN (".getEntity('user').") AND (u.employee = 1) AND u.statut != 0 ";
			}
            return 1;
		}

		return 0;
	}
    public function addSQLWhereFilterOnSelectUsers($parameters, &$object, &$action, $hookmanager)
	{
		global $conf;
		$TContext = explode(':', $parameters['context']);
        $THooksAllowed = array('holidaycard', 'holidaylist','defineholidaylist', 'leavemovementlist');
        $intersect = array_intersect($THooksAllowed, $TContext);
		if(!empty($intersect)) {
            if(!getDolGlobalString('ADVANCEDHRM_DISPLAY_ALL_USERS')) {
				$this->resprints =  " AND (u.employee = 1)";
			}
            return 1;
		}

	}

	function printColonneInvites() {

		global $langs;

		$langs->load('advancedhrm@advancedhrm');
		?>

		<script>

			$(document).ready(function() {

				// Affichage du titre de la colonne
				let title = <?php echo json_encode($langs->trans('guests')); ?>;
				$('<td>'+title+'</td>').insertAfter($(".liste_titre").find(".expensereportcreatetype,.linecoltype"));

				// Affichage du td des lignes
				$('<td class="contenu_line_invite"></td>').insertAfter($(".linetr").find(".linecoltype"));

			});

		</script>

		<?php

	}

	public function addHtmlHeader($parameters, &$object, &$action, $hookmanager){
		$TContext = explode(':', $parameters['context']);
		if(in_array('expensereportcard', $TContext)) {
			print '<link rel="stylesheet" type="text/css" href="'.dol_buildpath("/advancedhrm/css/bootstrap.css",1).'">'."\n";
		}
	}
}
