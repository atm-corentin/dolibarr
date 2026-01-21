<?php

/**
 * Get the html select for item per page
 *
 * @param  string $selected Pre selected value
 * @return string                        HTML to display
 */
function selectItemPerPage($selected)
{
	global $conf;

	if (!empty($conf->global->MAIN_PAGESIZE_CHOICES)) {
		$values = array();

		// This const is formated as follow => 10:10,15:15,20:20,30:30
		$entries = explode(',', $conf->global->MAIN_PAGESIZE_CHOICES);
		if ($entries) {
			foreach ($entries as $entry) {
				$values[] = explode(':', $entry)[0];
			}
		}
	} else {
		$values = ['5', '15', '25', '50', '100', '500', '1000', '5000'];
	}

	// We keep the $conf->liste_limit in possible values because it's our fallback value
	if (!in_array($conf->liste_limit, $values)) {
		$values[] = strval($conf->liste_limit);
	}

	// We sort values to keep in correct order
	sort($values);

	$ret = '<li class="pagination"><select id="selectPerPage" class="flat selectlimit">';
	foreach ($values as $value) {
		$ret .= '<option value="' . $value . '" ' . ($selected == $value ? 'selected' : '') . '>' . $value . '</option>';
	}
	$ret .= '</select></li>';

	return $ret;
}

/**
 * Get the html for pagination system
 *
 * @param  int $currentpage Current page
 * @param  int $prevPage    Previous page
 * @param  int $nextPage    Next page
 * @return string                                HTML to display
 */
function paginationList($currentpage = 1, $prevPage = 1, $nextPage = 1)
{
	$ret = '<li class="pagination paginationpage paginationpageleft">';
	$ret .= '<a href="#" data-prev="'.$prevPage.'" title="currentpage" id="previouspage" class="paginationprev"><i class="fa fa-chevron-left" aria-hidden="true"></i></a>';
	$ret .= '</li>';
	$ret .= '<li class="pagination">';
	$ret .= '<span id="currentpage">' . $currentpage . '</span>';
	$ret .= '</li>';
	$ret .= " / ";
	$ret .= '<li class="pagination">';
	$ret .= '<span id="lastpage"><i class="fas fa-spinner fa-spin"></i></span>';
	$ret .= '</li>';
	$ret .= '<li class="pagination paginationpage paginationpageright">';
	$ret .= '<a href="#" data-next="'.$nextPage.'" "title="currentpage" id="nextpage" class="paginationnext"><i class="fa fa-chevron-right" aria-hidden="true"></i></a>';
	$ret .= '</li>';

	return $ret;
}

/**
 * Format values into the ajax request to transform it into printable values.
 * For example, dico value will be translated
 *
 * @param  array        $values        Values from the query builder
 * @param  CommonObject $object        Object instantiated
 * @param  ExtraFields  $extrafields   Extrafield object corresponding to $object->table_element
 * @param  string       $originContext Listing context
 * @return array                            Values formated
 */
function formatValueForDisplay($values, &$object, &$extrafields, $originContext)
{
	global $hookmanager;

	$hookmanager->initHooks(array('h2g2defaultlist'));

	$valuesFormated = array();
	foreach ($values as $array) {
		$rowFormated = array();

		foreach ($array as $key => $val) {
			$parameters = array(
				'key' => $key,
				'value' => $val,
				'origin_context' => $originContext
			);
			$reshook = $hookmanager->executeHooks('h2g2FormatDefaultListingValue', $parameters, $object);

			if (empty($reshook)) {
				if (strpos($key, 'options_') !== false) { // Extrafield
					$formated = $extrafields->showOutputField(explode('options_', $key)[1], $val, '', $object->table_element);
				} else { // Object field
					$object->$key = $val;
					switch ($key) {
						case 'status':
						case 'fk_statut':
							$object->statut = $object->status = $val;
							$formated = $object->getLibStatut(5);
						break;
						case 'title':
						case 'ref':
							$object->statut = $object->status = (property_exists($array, 'fk_statut') ? $array->fk_statut : $array->status);
							$formated = $object->getNomUrl(1);
						break;
						case 'id':
							$formated = $val;
						break;
						default:
							$formated = $object->showOutputField($object->fields[$key], $key, $object->$key, '');
						break;
					}
				}
			} else {
				if (!empty($hookmanager->resArray)) {
					$key = $hookmanager->resArray['key'];
					$formated = $hookmanager->resArray['value'];
				} else {
					$formated = $hookmanager->resPrint;
				}
			}
			$rowFormated[$key] = $formated;
		}

		$valuesFormated[] = $rowFormated;
	}

	return $valuesFormated;
}

/**
 * Get the list of fields that can be selected for display
 *
 * @param  string       $context     Listing context
 * @param  CommonObject $object      Object
 * @param  ExtraFields  $extrafields Extrafield of object
 * @return array                                    List of fields
 */
function getArrayFieldsForListing($context, $object, $extrafields) // $extrafields is used in the template. Do not remove it
{
	global $conf, $hookmanager; // Used in the template

	$hookmanager->initHooks(array($context, 'h2g2defaultlist'));

	// Definition of array of fields for columns
	$arrayfields = array();
	foreach ($object->fields as $key => $val) {
		// If $val['visible']==0, then we never show the field
		if (!empty($val['visible'])) {
			$visible = (int) dol_eval($val['visible'], 1);

			$arrayfields['t.'.$key] = array(
			'label'=>$val['label'],
			'checked'=>(($visible < 0) ? 0 : 1),
			'enabled'=>($visible != 3 && dol_eval($val['enabled'], 1)),
			'position'=>$val['position'],
			'help'=>$val['help']
			);
		}
	}
	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_array_fields.tpl.php';

	// Hook to add more fields into the arrayfields
	$parameters = array(
		'origin_context' => $context
	);
	$reshook = $hookmanager->executeHooks('h2g2CompleteDefaultListingArrayFields', $parameters, $object);
	if (!empty($reshook)) {
		$arrayfields = array_merge($arrayfields, $hookmanager->resArray);
	}

	return dol_sort_array($arrayfields, 'position');
}

/**
 * Display the table loader
 *
 * @param  array $arrayfields Array of fields to display
 * @return string                                HTML to display
 */
function displayLoader($arrayfields)
{
	// Calculate colspan
	$colspan = 1;
	foreach ($arrayfields as $key => $val) { if (!empty($val['checked'])) { $colspan++;
	}
	}
	$out = '<tr id="loaderContainer"><td colspan="'.$colspan.'"><div id="loader" class="loader">';
	//$out.= '<img src="'.dol_buildpath('/supercotrolia/img/loader.gif', 1).'"/>';
	$out.= '<script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>';
	$out.= '<lottie-player src="https://assets6.lottiefiles.com/packages/lf20_ynlkstie.json" background="transparent" style="height: 200px; width: 200px;" speed="1" loop autoplay></lottie-player>';
	$out.= '</div></td></tr>';

	return $out;
}

/**
 * Generate select HTML to choose massaction
 *
 * @param  string $selected      Value auto selected when at least one record is selected. Not a preselected value. Use '0' by default.
 * @param  array  $arrayofaction array('code'=>array('label', 'withPopup'), ...). The code is the key stored into the GETPOST('massaction') when submitting action.
 * @param  int    $alwaysvisible 1=select button always visible
 * @param  string $name          Name for massaction
 * @param  string $cssclass      CSS class used to check for select
 * @return string|void                Select list
 */
function selectDefaultListMassAction($selected, $arrayofaction, $alwaysvisible = 0, $name = 'massaction', $cssclass = 'checkforselect')
{
	global $conf, $langs, $hookmanager;

	$disabled = 0;
	$ret = '<div class="centpercent center">';
	$ret .= '<select class="flat'.(empty($conf->use_javascript_ajax) ? '' : ' hideobject').' '.$name.' '.$name.'select valignmiddle alignstart" id="'.$name.'" name="'.$name.'"'.($disabled ? ' disabled="disabled"' : '').'>';

	// Complete list with data from external modules. THe module can use $_SERVER['PHP_SELF'] to know on which page we are, or use the $parameters['currentcontext'] completed by executeHooks.
	$parameters = array();
	$reshook = $hookmanager->executeHooks('addMoreMassActions', $parameters); // Note that $action and $object may have been modified by hook
	// check if there is a mass action
	if (count($arrayofaction) == 0 && empty($hookmanager->resPrint)) { return;
	}
	if (empty($reshook)) {
		$ret .= '<option value="0"'.($disabled ? ' disabled="disabled"' : '').'>-- '.$langs->trans("SelectAction").' --</option>';
		foreach ($arrayofaction as $code => $elem) {
			$ret .= '<option value="'.$code.'"'.($disabled ? ' disabled="disabled"' : '').' data-html="'.dol_escape_htmltag($elem['label']).'"';
			if ($elem['withPopup']) { $ret .= ' data-withpopup="1"';
			}
			$ret .= '>'.$elem['label'].'</option>';
		}
	}
	$ret .= $hookmanager->resPrint;

	$ret .= '</select>';

	if (empty($conf->dol_optimize_smallscreen)) { $ret .= ajax_combobox('.'.$name.'select');
	}

	// Warning: if you set submit button to disabled, post using 'Enter' will no more work if there is no another input submit. So we add a hidden button
	$ret .= '<input type="submit" name="confirmmassactioninvisible" style="display: none" tabindex="-1">'; // Hidden button BEFORE so it is the one used when we submit with ENTER.
	$ret .= '<input type="submit" disabled name="confirmmassaction" class="button'.(empty($conf->use_javascript_ajax) ? '' : ' hideobject').' '.$name.' '.$name.'confirmed" value="'.dol_escape_htmltag($langs->trans("Confirm")).'">';
	$ret .= '</div>';

	if (!empty($conf->use_javascript_ajax)) {
		$ret .= '<!-- JS CODE TO ENABLE mass action select -->
    		<script>
                        function initCheckForSelect(mode, name, cssclass)	/* mode is 0 during init of page or click all, 1 when we click on 1 checkboxi, "name" refers to the class of the massaction button, "cssclass" to the class of the checkfor select boxes */
        		{
        			atleastoneselected=0;
                                jQuery("."+cssclass).each(function( index ) {
    	  				/* console.log( index + ": " + $( this ).text() ); */
    	  				if ($(this).is(\':checked\')) atleastoneselected++;
    	  			});

					console.log("initCheckForSelect mode="+mode+" name="+name+" cssclass="+cssclass+" atleastoneselected="+atleastoneselected);

    	  			if (atleastoneselected || '.$alwaysvisible.')
    	  			{
                                    jQuery("."+name).show();
        			    '.($selected ? 'if (atleastoneselected) { jQuery("."+name+"select").val("'.$selected.'").trigger(\'change\'); jQuery("."+name+"confirmed").prop(\'disabled\', false); }' : '').'
        			    '.($selected ? 'if (! atleastoneselected) { jQuery("."+name+"select").val("0").trigger(\'change\'); jQuery("."+name+"confirmed").prop(\'disabled\', true); } ' : '').'
    	  			}
    	  			else
    	  			{
                                    jQuery("."+name).hide();
                                    jQuery("."+name+"other").hide();
    	            }
        		}

        	jQuery(document).ready(function () {
                    initCheckForSelect(0, "' . $name.'", "'.$cssclass.'");
                    jQuery(".' . $cssclass.'").click(function() {
                        initCheckForSelect(1, "'.$name.'", "'.$cssclass.'");
                    });
                        jQuery(".' . $name.'select").change(function() {
        			var massaction = $( this ).val();
        			var urlform = $( this ).closest("form").attr("action").replace("#show_files","");
        			if (massaction == "builddoc")
                    {
                        urlform = urlform + "#show_files";
    	            }
        			$( this ).closest("form").attr("action", urlform);
                    console.log("we select a mass action name='.$name.' massaction="+massaction+" - "+urlform);
        	        /* Warning: if you set submit button to disabled, post using Enter will no more work if there is no other button */
        			if ($(this).val() != \'0\')
    	  			{
                                        jQuery(".' . $name.'confirmed").prop(\'disabled\', false);
										jQuery(".' . $name.'other").hide();	/* To disable if another div was open */
                                        jQuery(".' . $name.'"+massaction).show();
    	  			}
    	  			else
    	  			{
                                        jQuery(".' . $name.'confirmed").prop(\'disabled\', true);
										jQuery(".' . $name.'other").hide();	/* To disable any div open */
    	  			}
    	        });
        	});
    		</script>
        	';
	}

	return $ret;
}

/**
 * Display the table header (title with number of elements + pagination + new button)
 *
 * @param  string $title               Listing title
 * @param  string $newBtnUrl           Url to redirect after new object click
 * @param  string $permissiontoadd     Permission to add with the new btn
 * @param  array  $arrayofmassactions  array('code'=>array('label', 'withPopup'), ...). The code is the key stored into the GETPOST('massaction') when submitting action.
 * @param  string $picto               Picto to display
 * @param  bool   $massActionWithPopup Add a mass action with a popup
 * @return string                                HTML to display
 */
function listHeader($title, $newBtnUrl, $permissiontoadd, $arrayofmassactions, $picto, $massActionWithPopup = false)
{
	global $db, $langs, $conf;

	$form = new Form($db);

	// Display new button + pagination + Number of elements
	$newBtn = '';
	if ($newBtnUrl) {
		$newBtn = dolGetButtonTitle($langs->trans('New'), '', 'fa fa-plus-circle', $newBtnUrl, '', $permissiontoadd);
	}
	$selectPerPage = selectItemPerPage($conf->liste_limit);
	$pagination = paginationList();
	$morehtmlright = '<div class="pagination"><ul>' . $selectPerPage . $pagination . $newBtn.'</ul></div>';
	$titleTotal = '<span id="listTotal" class="opacitymedium colorblack paddingleft"><i class="fas fa-spinner fa-spin"></i></span>';
	if ($massActionWithPopup) {
		$morehtmlcenter = selectDefaultListMassAction('', $arrayofmassactions);
	} else {
		$morehtmlcenter = $form->selectMassAction('', $arrayofmassactions);
	}
	return load_fiche_titre($title.$titleTotal, $morehtmlright, $picto, 0, '', '', $morehtmlcenter);
}
