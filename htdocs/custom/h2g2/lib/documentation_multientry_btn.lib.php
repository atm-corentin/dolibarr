<?php

/**
 * Get the example for a basic font awesome example
 *
 * @return string                   HTML to display
 */
function getBasicFontAwesomeExample()
{
	global $langs;

	$ret = '';

	$mainBtn = array(
		'href' => '#',
		'label' => $langs->trans('Modify'),
		'picto' => '<i class="fas fa-pen"></i>',
		'disabled' => 0,
		'title' => 'Ceci est un test de tooltip',
	);

	$entries = array(
		array(
			'href' => '#',
			'label' => $langs->trans('Validate'),
			'picto' => '<i class="fas fa-check"></i>',
			'disabled' => 0,
			'title' => 'Ceci est un test de tooltip',
		),
		array(
			'href' => '#',
			'label' => $langs->trans('Close'),
			'picto' => '<i class="fas fa-times"></i>',
			'disabled' => 0,
			'title' => 'Fermer l\'objet le rendra indisponible dans les listings',
		),
	);

	$ret .= '<div class="example">';
	$ret .= '<div class="example__code">';
	$ret .= '<pre>';
	$ret .= '<div class="example__code-header">';
	$ret .= '<div class="example__code-header_title">' . $langs->trans('H2G2MEBBasicFAExample') . '</div>';
	$ret .= '<div class="example__code-header_copy"><button>' . $langs->trans('H2G2Copy') . '</button></div>';
	$ret .= '</div>';
	$ret .= '<div class="example__code-content">';
	$ret .= '<code data-language="php">// Define main button
$mainBtn = array(
    \'href\' => $_SERVER[\'PHP_SELF\'].\'?id=\'.$id.\'&action=edit\',
    \'label\' => $langs->trans(\'Modify\'),
    \'picto\' => \'<i class="fas fa-pen"></i>\',
    \'disabled\' => 0,
    \'title\' => \'\',
);

// Define other entries for the button
$entries = array(
    array(
        \'href\' => $_SERVER[\'PHP_SELF\'].\'?id=\'.$id.\'&action=validate\',
        \'label\' => $langs->trans(\'Validate\'),
        \'picto\' => \'<i class="fas fa-check"></i>\',
        \'disabled\' => 0,
        \'title\' => \'\',
    ),
    array(
        \'href\' => $_SERVER[\'PHP_SELF\'].\'?id=\'.$id.\'&action=close\',
        \'label\' => $langs->trans(\'Close\'),
        \'picto\' => \'<i class="fas fa-times"></i>\',
        \'disabled\' => 0,
        \'title\' => \'\',
    ),
);

// Display the button
print buildMultiEntriesButton($mainBtn, $entries);
</code>';
	$ret .= '</div>';
	$ret .= '</pre>';
	$ret .= '</div>';

	$ret .= '<div class="example__preview">';
	$ret .= buildMultiEntriesButton($mainBtn, $entries);
	$ret .= '</div>';
	$ret .= '</div>';

	return $ret;
}

/**
 * Get the example for a basic material icon example
 *
 * @return string                   HTML to display
 */
function getBasicMaterialIconsExample()
{
	global $langs;

	$ret = '';

	$mainBtn = array(
		'href' => '#',
		'label' => $langs->trans('Modify'),
		'picto' => '<span class="material-icons">edit</span>',
		'disabled' => 0,
		'title' => '',
	);

	$entries = array(
		array(
			'href' => '#',
			'label' => $langs->trans('Validate'),
			'picto' => '<span class="material-icons">done</span>',
			'disabled' => 0,
			'title' => '',
		),
		array(
			'href' => '#',
			'label' => $langs->trans('Close'),
			'picto' => '<span class="material-icons">close</span>',
			'disabled' => 0,
			'title' => '',
		),
	);

	$ret .= '<div class="example">';
	$ret .= '<div class="example__code">';
	$ret .= '<pre>';
	$ret .= '<div class="example__code-header">';
	$ret .= '<div class="example__code-header_title">' . $langs->trans('H2G2MEBBasicMIExample') . '</div>';
	$ret .= '<div class="example__code-header_copy"><button>' . $langs->trans('H2G2Copy') . '</button></div>';
	$ret .= '</div>';
	$ret .= '<div class="example__code-content">';
	$ret .= '<code data-language="php">// Define main button
$mainBtn = array(
    \'href\' => $_SERVER[\'PHP_SELF\'].\'?id=\'.$id.\'&action=edit\',
    \'label\' => $langs->trans(\'Modify\'),
    \'picto\' => \'<span class="material-icons">edit</span>\',
    \'disabled\' => 0,
    \'title\' => \'\',
);

// Define other entries for the button
$entries = array(
    array(
        \'href\' => $_SERVER[\'PHP_SELF\'].\'?id=\'.$id.\'&action=validate\',
        \'label\' => $langs->trans(\'Validate\'),
        \'picto\' => \'<span class="material-icons">done</span>\',
        \'disabled\' => 0,
        \'title\' => \'\',
    ),
    array(
        \'href\' => $_SERVER[\'PHP_SELF\'].\'?id=\'.$id.\'&action=close\',
        \'label\' => $langs->trans(\'Close\'),
        \'picto\' => \'<span class="material-icons">close</span>\',
        \'disabled\' => 0,
        \'title\' => \'\',
    ),
);

// Display the button
print buildMultiEntriesButton($mainBtn, $entries);
</code>';
	$ret .= '</div>';
	$ret .= '</pre>';
	$ret .= '</div>';

	$ret .= '<div class="example__preview">';
	$ret .= buildMultiEntriesButton($mainBtn, $entries);
	$ret .= '</div>';
	$ret .= '</div>';

	return $ret;
}

/**
 * Get the example for the without action button
 *
 * @return string                   HTML to display
 */
function getWithoutActionExample()
{
	global $langs;
	$ret = '';

	$ret .= '<div class="example">';
	$ret .= '<div class="example__code">';
	$ret .= '<pre>';
	$ret .= '<div class="example__code-header">';
	$ret .= '<div class="example__code-header_title">' . $langs->trans('H2G2WithoutActionExample') . '</div>';
	$ret .= '<div class="example__code-header_copy"><button>' . $langs->trans('H2G2Copy') . '</button></div>';
	$ret .= '</div>';
	$ret .= '<div class="example__code-content">';
	$ret .= '<code data-language="php">// Define main button
$mainBtn = array(
    \'href\' => \'#\',
    \'withoutAction\' => true,
    \'label\' => \'Outils\',
    \'picto\' => \'<span class="material-icons">home_repair_service</span>\',
    \'disabled\' => 0,
    \'title\' => \'Accédez aux outils d\\\'import / export\',
);

// Define other entries for the button
$entries = array(
    array(
        \'href\' => $_SERVER[\'PHP_SELF\'].\'?action=add_ticket\',
        \'label\' => \'Exporter\',
        \'picto\' => \'<i class="fas fa-file-export" style="color: #8c4446"></i>\',
        \'disabled\' => $user->rights->export,
        \'title\' => \'Exporter l\\\'article\',
    ),
    array(
        \'href\' => $_SERVER[\'PHP_SELF\'].\'?action=add_inter\',
        \'label\' => \'Importer\',
        \'picto\' => \'<i class="fas fa-file-import" style="color: #2b4161"></i>\',
        \'disabled\' => $user->rights->import,
        \'title\' => \'Importer un article\',
    ),
    array(
        \'href\' => $_SERVER[\'PHP_SELF\'].\'?action=add_device\',
        \'label\' => \'Publier\',
        \'picto\' => \'<span class="material-icons" style="color: #717dac">publish</span>\',
        \'disabled\' => $user->rights->publish,
        \'title\' => \'Publier l\\\'article\',
    ),
);

// Display the button
print buildMultiEntriesButton($mainBtn, $entries);
</code>';
	$ret .= '</div>';
	$ret .= '</pre>';
	$ret .= '</div>';

	$ret .= '<div class="example__preview">';

	// Enabled button
	$mainBtn = array(
		'href' => '#',
		'withoutAction' => true,
		'label' => 'Outils',
		'picto' => '<span class="material-icons">home_repair_service</span>',
		'disabled' => 0,
		'title' => 'Accédez aux outils d\'import / export',
	);

	$entries = array(
		array(
			'href' => $_SERVER['PHP_SELF'] . '?action=add_ticket',
			'label' => 'Exporter',
			'picto' => '<i class="fas fa-file-export" style="color: #8c4446"></i>',
			'disabled' => 0,
			'title' => 'Exporter l\'article',
		),
		array(
			'href' => $_SERVER['PHP_SELF'] . '?action=add_inter',
			'label' => 'Importer',
			'picto' => '<i class="fas fa-file-import" style="color: #2b4161"></i>',
			'disabled' => 0,
			'title' => 'Importer un article',
		),
		array(
			'href' => $_SERVER['PHP_SELF'] . '?action=add_device',
			'label' => 'Publier',
			'picto' => '<span class="material-icons" style="color: #717dac">publish</span>',
			'disabled' => 0,
			'title' => 'Publier l\'article',
		),
	);
	$ret .= buildMultiEntriesButton($mainBtn, $entries);

	// Disabled button
	$mainBtn = array(
		'href' => '#',
		'withoutAction' => true,
		'label' => 'Outils',
		'picto' => '<span class="material-icons">home_repair_service</span>',
		'disabled' => 1,
		'title' => '<span class="material-icons" style="color: #8c4446; font-size: 18px">priority_high</span> Vous n\'avez pas les droits pour accéder aux outils',
	);
	$ret .= buildMultiEntriesButton($mainBtn, $entries);


	$ret .= '</div>';
	$ret .= '</div>';
	return $ret;
}

/**
 * Get the example for a gestion parc button implementation
 *
 * @return string                   HTML to display
 */
function getGPExample()
{
	global $langs;

	$ret = '<div class="example">';
	$ret .= '<div class="example__code">';
	$ret .= '<pre>';
	$ret .= '<div class="example__code-header">';
	$ret .= '<div class="example__code-header_title">' . $langs->trans('H2G2GPExample') . '</div>';
	$ret .= '<div class="example__code-header_copy"><button>' . $langs->trans('H2G2Copy') . '</button></div>';
	$ret .= '</div>';
	$ret .= '<div class="example__code-content">';
	$ret .= '<code data-language="php">// Define main button
$mainBtn = array(
    \'href\' => dol_buildpath(\'/ticket/card.php?action=create\'),
    \'label\' => $langs->trans(\'CreateTicket\'),
    \'picto\' => \'<i class="fas fa-plus-circle"></i>\',
    \'disabled\' => $user->rights->ticket->write,
    \'title\' => \'\',
);

// Define other entries for the button
$entries = array(
    array(
        \'href\' => dol_buildpath(\'/ticket/card.php?action=create\'),
        \'label\' =>  $langs->trans(\'CreateTicket\'),
        \'picto\' => \'<i class="fas fa-ticket-alt"></i>\',
        \'disabled\' => $user->rights->ticket->write,
        \'title\' => \'\',
    ),
    array(
        \'href\' => dol_buildpath(\'/fichinter/card.php?action=create\'),
        \'label\' =>  $langs->trans(\'CreateInter\'),
        \'picto\' => \'<span class="material-icons">local_shipping</span>\',
        \'disabled\' => $user->rights->fichinter->creer,
        \'title\' => \'\',
    ),
    array(
        \'href\' => dol_buildpath(\'/comm/action/card.php?action=create\'),
        \'label\' =>  $langs->trans(\'CreateEvent\'),
        \'picto\' => \'<span class="material-icons">event</span>\',
        \'disabled\' => $user->rights->agenda->myactions->create,
        \'title\' => \'\',
    ),
);

// Display the button
print buildMultiEntriesButton($mainBtn, $entries);
</code>';
	$ret .= '</div>';
	$ret .= '</pre>';
	$ret .= '</div>';

	$ret .= '<div class="example__preview">';
	$mainBtn = array(
		'href' => '#',
		'label' => 'Créer un ticket',
		'picto' => '<i class="fas fa-ticket-alt"></i>',
		'disabled' => 0,
		'title' => '',
	);

	$entries = array(
		array(
			'href' => $_SERVER['PHP_SELF'] . '?action=add_ticket',
			'label' => 'Créer un ticket',
			'picto' => '<i class="fas fa-ticket-alt"></i>',
			'disabled' => 0,
			'title' => '',
		),
		array(
			'href' => $_SERVER['PHP_SELF'] . '?action=add_inter',
			'label' => 'Créer une intervention',
			'picto' => '<span class="material-icons">local_shipping</span>',
			'disabled' => 0,
			'title' => '',
		),
		array(
			'href' => $_SERVER['PHP_SELF'] . '?action=add_device',
			'label' => 'Créer un évènement',
			'picto' => '<span class="material-icons">event</span>',
			'disabled' => 0,
			'title' => '',
		),
	);
	$ret .= buildMultiEntriesButton($mainBtn, $entries);

	$mainBtn = array(
		'href' => '#',
		'label' => 'Créer un ticket',
		'picto' => '<i class="fas fa-ticket-alt"></i>',
		'disabled' => 1,
		'title' => '',
	);
	$ret .= buildMultiEntriesButton($mainBtn, $entries);

	$ret .= '</div>';
	$ret .= '</div>';
	return $ret;
}


/**
 * Get the example for an html button implementation
 *
 * @return string                   HTML to display
 */
function getHTMLExample()
{
	global $langs;

	$ret = '<div class="example">';
	$ret .= '<div class="example__code">';
	$ret .= '<pre>';
	$ret .= '<div class="example__code-header">';
	$ret .= '<div class="example__code-header_title">Bouton avec du html</div>';
	$ret .= '<div class="example__code-header_copy"><button>' . $langs->trans('H2G2Copy') . '</button></div>';
	$ret .= '</div>';
	$ret .= '<div class="example__code-content">';
	$ret .= '<code data-language="php">// Define main button
$mainBtn = array(
    \'href\' => \'#\',
    \'withoutAction\' => true,
    \'label\' => \'Exemples\',
    \'picto\' => \'<span class="material-icons">inventory_2</span>\',
    \'disabled\' => 0,
    \'title\' => \'Voir des exemples contenant de l\\\'HTML\',
);

// Define other entries for the button
$entries = array(
    array(
        \'href\' => \'#\',
        \'label\' => \'Envoyer comme <b>Nouveau</b>\',
        \'picto\' => \'<span style="background: orange; width: 15px; height: 15px; border-radius: 2px"></span>\',
        \'disabled\' => 1,
        \'title\' => \'Vous n\\\'avez pas les droits\',
    ),
    array(
        \'href\' => \'#\',
        \'label\' => \'Envoyer comme <b>Ouvert</b>\',
        \'picto\' => \'<span style="background: green; width: 15px; height: 15px; border-radius: 2px"></span>\',
        \'disabled\' => 0,
        \'title\' => \'\',
    ),
    array(
        \'href\' => \'#\',
        \'label\' => \'Envoyer comme <b>En attente</b>\',
        \'picto\' => \'<span style="background: #0B63A2; width: 15px; height: 15px; border-radius: 2px"></span>\',
        \'disabled\' => 0,
        \'title\' => \'\',
    ),
    array(
        \'href\' => \'#\',
        \'label\' => \'Ping rouge\',
        \'picto\' => \'<span style="background: #8c4446; width: 10px; height: 10px; border-radius: 5px"></span>\',
        \'disabled\' => 0,
        \'title\' => \'\',
    )
);

// Display the button
print buildMultiEntriesButton($mainBtn, $entries);
</code>';
	$ret .= '</div>';
	$ret .= '</pre>';
	$ret .= '</div>';

	$ret .= '<div class="example__preview">';
	$mainBtn = array(
		'href' => '#',
		'withoutAction' => true,
		'label' => 'Exemples',
		'picto' => '<span class="material-icons">inventory_2</span>',
		'disabled' => 0,
		'title' => 'Voir des exemples contenant de l\'HTML',
	);

	$entries = array(
		array(
			'href' => '#',
			'label' => 'Envoyer comme <b>Nouveau</b>',
			'picto' => '<span style="background: orange; width: 15px; height: 15px; border-radius: 2px"></span>',
			'disabled' => 1,
			'title' => 'Vous n\'avez pas les droits',
		),
		array(
			'href' => '#',
			'label' => 'Envoyer comme <b>Ouvert</b>',
			'picto' => '<span style="background: green; width: 15px; height: 15px; border-radius: 2px"></span>',
			'disabled' => 0,
			'title' => '',
		),
		array(
			'href' => '#',
			'label' => 'Envoyer comme <b>En attente</b>',
			'picto' => '<span style="background: #0B63A2; width: 15px; height: 15px; border-radius: 2px"></span>',
			'disabled' => 0,
			'title' => '',
		),
		array(
			'href' => '#',
			'label' => 'Ping rouge',
			'picto' => '<span style="background: #8c4446; width: 10px; height: 10px; border-radius: 5px"></span>',
			'disabled' => 0,
			'title' => '',
		)
	);
	$ret .= buildMultiEntriesButton($mainBtn, $entries);

	$ret .= '</div>';
	$ret .= '</div>';
	return $ret;
}

/**
 * Get the example for a colored example
 *
 * @return string                   HTML to display
 */
function getColoredExample()
{
	global $langs;

	$ret = '';

	$mainBtn = array(
		'href' => '#',
		'label' => $langs->trans('Modify'),
		'picto' => '<i class="fas fa-pen"></i>',
		'colorbtn' => 'orange',
		'disabled' => 0,
		'title' => 'Ceci est un test de tooltip',
	);

	$entries = array(
		array(
			'href' => '#',
			'label' => $langs->trans('Validate'),
			'picto' => '<i class="fas fa-check"></i>',
			'pictocolor' => 'green',
			'disabled' => 0,
			'title' => 'Ceci est un test de tooltip',
		),
		array(
			'href' => '#',
			'label' => $langs->trans('Close'),
			'picto' => '<i class="fas fa-times"></i>',
			'pictocolor' => 'red',
			'disabled' => 0,
			'title' => 'Fermer l\'objet le rendra indisponible dans les listings',
		),
	);

	$ret .= '<div class="example">';
	$ret .= '<div class="example__code">';
	$ret .= '<pre>';
	$ret .= '<div class="example__code-header">';
	$ret .= '<div class="example__code-header_title">' . $langs->trans('H2G2MEBColoredExample') . '</div>';
	$ret .= '<div class="example__code-header_copy"><button>' . $langs->trans('H2G2Copy') . '</button></div>';
	$ret .= '</div>';
	$ret .= '<div class="example__code-content">';
	$ret .= '<code data-language="php">// Define main button
$mainBtn = array(
    \'href\' => $_SERVER[\'PHP_SELF\'].\'?id=\'.$id.\'&action=edit\',
    \'label\' => $langs->trans(\'Modify\'),
    \'colorbtn\' => \'orange\',
    \'picto\' => \'<i class="fas fa-pen"></i>\',
    \'disabled\' => 0,
    \'title\' => \'\',
);

// Define other entries for the button
$entries = array(
    array(
        \'href\' => $_SERVER[\'PHP_SELF\'].\'?id=\'.$id.\'&action=validate\',
        \'label\' => $langs->trans(\'Validate\'),
        \'picto\' => \'<i class="fas fa-check"></i>\',
        \'pictocolor\' => \'green\',
        \'disabled\' => 0,
        \'title\' => \'\',
    ),
    array(
        \'href\' => $_SERVER[\'PHP_SELF\'].\'?id=\'.$id.\'&action=close\',
        \'label\' => $langs->trans(\'Close\'),
        \'picto\' => \'<i class="fas fa-times"></i>\',
        \'pictocolor\' => \'red\',
        \'disabled\' => 0,
        \'title\' => \'\',
    ),
);

// Display the button
print buildMultiEntriesButton($mainBtn, $entries);
</code>';
	$ret .= '</div>';
	$ret .= '</pre>';
	$ret .= '</div>';

	$ret .= '<div class="example__preview">';
	$ret .= buildMultiEntriesButton($mainBtn, $entries);
	$ret .= '</div>';
	$ret .= '</div>';

	return $ret;
}

/**
 * Get the example for a up/down button
 *
 * @return string                   HTML to display
 */
function getUpDownExample()
{
	global $langs;

	$ret = '';

	$mainBtn = array(
		'href' => '#',
		'label' => $langs->trans('Modify'),
		'picto' => '<i class="fas fa-pen"></i>',
		'disabled' => 0,
		'title' => 'Ceci est un test de tooltip',
	);

	$entries = array(
		array(
			'href' => '#',
			'label' => $langs->trans('Validate'),
			'picto' => '<i class="fas fa-check"></i>',
			'disabled' => 0,
			'title' => 'Ceci est un test de tooltip',
		),
		array(
			'href' => '#',
			'label' => $langs->trans('Close'),
			'picto' => '<i class="fas fa-times"></i>',
			'disabled' => 0,
			'title' => 'Fermer l\'objet le rendra indisponible dans les listings',
		),
	);

	$ret .= '<div class="example">';
	$ret .= '<div class="example__code">';
	$ret .= '<pre>';
	$ret .= '<div class="example__code-header">';
	$ret .= '<div class="example__code-header_title">' . $langs->trans('H2G2MEBUpDownExample') . '</div>';
	$ret .= '<div class="example__code-header_copy"><button>' . $langs->trans('H2G2Copy') . '</button></div>';
	$ret .= '</div>';
	$ret .= '<div class="example__code-content">';
	$ret .= '<code data-language="php">// Define main button
$mainBtn = array(
    \'href\' => $_SERVER[\'PHP_SELF\'].\'?id=\'.$id.\'&action=edit\',
    \'label\' => $langs->trans(\'Modify\'),
    \'picto\' => \'<i class="fas fa-pen"></i>\',
    \'disabled\' => 0,
    \'title\' => \'\',
);

// Define other entries for the button
$entries = array(
    array(
        \'href\' => $_SERVER[\'PHP_SELF\'].\'?id=\'.$id.\'&action=validate\',
        \'label\' => $langs->trans(\'Validate\'),
        \'picto\' => \'<i class="fas fa-check"></i>\',
        \'disabled\' => 0,
        \'title\' => \'\',
    ),
    array(
        \'href\' => $_SERVER[\'PHP_SELF\'].\'?id=\'.$id.\'&action=close\',
        \'label\' => $langs->trans(\'Close\'),
        \'picto\' => \'<i class="fas fa-times"></i>\',
        \'disabled\' => 0,
        \'title\' => \'\',
    ),
);

// Display the button
print buildMultiEntriesButton($mainBtn, $entries, true);
print buildMultiEntriesButton($mainBtn, $entries, false);
</code>';
	$ret .= '</div>';
	$ret .= '</pre>';
	$ret .= '</div>';

	$ret .= '<div class="example__preview">';
	$ret .= buildMultiEntriesButton($mainBtn, $entries, true);
	$ret .= buildMultiEntriesButton($mainBtn, $entries, false);
	$ret .= '</div>';
	$ret .= '</div>';

	return $ret;
}

/**
 * Get the example for a create button
 *
 * @return string                   HTML to display
 */
function getCreateExample()
{
	global $langs;

	$ret = '';

	$mainBtn = array(
		'href' => '#',
		'rights' => 0
	);

	$entries = array(
		array(
			'href' => '#',
			'rights' => 0
		),
		array(
			'href' => '#',
			'rights' => 0
		)
	);

	$ret .= '<div class="example">';
	$ret .= '<div class="example__code">';
	$ret .= '<pre>';
	$ret .= '<div class="example__code-header">';
	$ret .= '<div class="example__code-header_title">' . $langs->trans('H2G2MEBCreateExample') . '</div>';
	$ret .= '<div class="example__code-header_copy"><button>' . $langs->trans('H2G2Copy') . '</button></div>';
	$ret .= '</div>';
	$ret .= '<div class="example__code-content">';
	$ret .= '<code data-language="php">// Define main button
$mainBtn = array(
    \'href\' => $_SERVER[\'PHP_SELF\'].\'?id=\'.$id.\'&action=add\',
    \'rights\' => 0,
);

// Define other entries for the button
$entries = array(
    array(
        \'href\' => $_SERVER[\'PHP_SELF\'].\'?id=\'.$id.\'&action=modify\',
        \'rights\' => 0,
    ),
	array(
        \'href\' => $_SERVER[\'PHP_SELF\'].\'?id=\'.$id.\'&action=save\',
        \'rights\' => 0,
    ),
);

// Display the button
print buildStandarMultiEntriesButton(\'create\',$mainBtn, $entries);
</code>';
	$ret .= '</div>';
	$ret .= '</pre>';
	$ret .= '</div>';

	$ret .= '<div class="example__preview">';
	$ret .= buildStandarMultiEntriesButton('create', $mainBtn, $entries);
	$ret .= '</div>';
	$ret .= '</div>';

	return $ret;
}

/**
 * Get the example for a delete button
 *
 * @return string                   HTML to display
 */
function getDeleteExample()
{
	global $langs;

	$ret = '';

	$mainBtn = array(
		'href' => '#',
		'rights' => 1
	);

	$cancelbtn = array(
		'href' => '#',
		'rights' => 1
	);

	$ret .= '<div class="example">';
	$ret .= '<div class="example__code">';
	$ret .= '<pre>';
	$ret .= '<div class="example__code-header">';
	$ret .= '<div class="example__code-header_title">' . $langs->trans('H2G2MEBDeleteExample') . '</div>';
	$ret .= '<div class="example__code-header_copy"><button>' . $langs->trans('H2G2Copy') . '</button></div>';
	$ret .= '</div>';
	$ret .= '<div class="example__code-content">';
	$ret .= '<code data-language="php">// Define main button
$mainBtn = array(
    \'href\' => $_SERVER[\'PHP_SELF\'].\'?id=\'.$id.\'&action=delete\',
    \'rights\' => 1,
);

// Define other entries for the button
$cancelbtn = array(
        \'href\' => $_SERVER[\'PHP_SELF\'].\'?id=\'.$id.\'&action=cancel\',
        \'rights\' => 1,
	);

// Display the button
print buildStandarMultiEntriesButton(\'delete\',$mainBtn, $cancelbtn);
</code>';
	$ret .= '</div>';
	$ret .= '</pre>';
	$ret .= '</div>';

	$ret .= '<div class="example__preview">';
	$ret .= buildStandarMultiEntriesButton('delete', $mainBtn, $cancelbtn);
	$ret .= '</div>';
	$ret .= '</div>';

	return $ret;
}
