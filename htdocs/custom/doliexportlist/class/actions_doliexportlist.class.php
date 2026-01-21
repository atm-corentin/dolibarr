<?php

/**
 * Class ActionsDoliExportList
 */
class ActionsDoliExportList
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
	 * printCommonFooter
	 *
	 * @param   array()		 $parameters	 Hook metadatas (context, etc...)
	 * @param   CommonObject	&$object		The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string		  &$action		Current action (if set). Generally create or edit or null
	 * @param   HookManager	 $hookmanager	Hook manager propagated to allow calling another hook
	 * @return  int							 < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function printCommonFooter($parameters, &$object, &$action, $hookmanager)
	{
        global $db, $langs, $conf, $user;

        $optioncss = GETPOST('optioncss', 'alpha');
        $is_list = strpos($parameters['context'], 'list') !== false && $optioncss != 'print';
	
	//fix for list with out hook
	$currurl = $_SERVER['PHP_SELF'];
        if(strpos($currurl,"compta/bank/various_payment/list.php")>=1){
                $is_list = true;
        }
	//fix for list with out hook
		
        $ignored_lists = (! empty($conf->global->LIST_EXPORT_IMPORT_IGNORED_LISTS)?explode(',', $conf->global->LIST_EXPORT_IMPORT_IGNORED_LISTS):array());
        foreach ($ignored_lists as $key => $list) {
            if (strpos($parameters['context'], $list) !== false) {
                $is_list = false;
                break;
            }
        }
	if(!$is_list && (strpos($parameters['context'], 'stockatdate') || in_array('thirdpartycard', explode(':', $parameters['context']))) && $optioncss != 'print'){
        	$is_list = true;
    	}

		if ($is_list)
		{
                    if ($user->rights->doliexportlist->export || $user->rights->doliexportlist->import)
                    {
                        $langs->load('doliexportlist@doliexportlist');

                        $pathtojs = array(
                                        dol_buildpath('/doliexportlist/js/FileSaver.min.js',1),
                                        dol_buildpath('/doliexportlist/js/listexport.js.php?v=2',1),
                                        dol_buildpath('/doliexportlist/js/listimport.js.php',1),
                                        dol_buildpath('/doliexportlist/js/jspdf.min.js',1),
                                        dol_buildpath('/doliexportlist/js/xlsx.full.min.js',1),
                                        dol_buildpath('/doliexportlist/js/jspdf.plugin.autotable.min.js',1),
                                        dol_buildpath('/doliexportlist/js/html2canvas.min.js',1)
                                    );
                        
                        $pathtocss = array();
                        
                        dol_include_once('doliexportlist/lib/doliexportlist.lib.php');
                        dol_include_once('doliexportlist/class/doliexportlist.class.php');
                        
                        $list = new DoliExportList($db);
                        
                        $more_buttons = array(
                                            array('picto' => 'sql_delete.png', 'title' => 'FreeList', 'alt' => 'free', 'class' => 'import', 'active' => ($conf->global->LIST_EXPORT_IMPORT_ENABLE_FREE_LIST && $user->admin))
                                        );
                        
                        if (1==2)
                        {
                            $pathtocss[] = dol_buildpath('/doliexportlist/css/doliexportlist.css.php',1);
                            
                            if ($user->rights->doliexportlist->export) {
                                $list->getFormats('export');
                                $download = '&nbsp;&nbsp;&nbsp;';
                                $download.= getCompactedButtons($list->formats, $langs->trans('ListExport'), dol_buildpath('/doliexportlist/img/export.png',1));
                            }
                            
                          
                        }
                        else
                        {
                            $download = '&nbsp;';
                            $list->getFormats();
                            
                            // List export/import formats buttons
                            foreach($list->formats as $format) {
                                if ($format->active) {
                                    if (($format->type == 'export' && $user->rights->doliexportlist->export) || ($format->type == 'import' && $user->rights->doliexportlist->import)) {
                                        $download.= '&nbsp;'.getButton(dol_buildpath('/doliexportlist/img/'.$format->picto, 1), $langs->trans($format->title), $format->format, $format->type);
                                    }
                                }
                            }
                            
                            // More buttons
                            foreach($more_buttons as $button) {
                                if ($button['active']) {
                                    if (($button['class'] == 'export' && $user->rights->doliexportlist->export) || ($button['class'] == 'import' && $user->rights->doliexportlist->import)) {
                                        $download.= '&nbsp;'.getButton(dol_buildpath('/doliexportlist/img/'.$button['picto'], 1), $langs->trans($button['title']), $button['alt'], $button['class']);
                                    }
                                }
                            }
                        }
                        
                        // add import file input
                        if ($user->rights->doliexportlist->import) {
                            $download.= '<input type="file" class="hidden" style="display: none;" id="import-file-input" accept=".sql"/>';
                        }
                        
                        $socid = GETPOST('socid');
                        if(empty($socid)) $socid = 0;
			
                        // Inclusion des fichiers CSS
                        foreach ($pathtocss as $css)
                        {
                            echo '<link rel="stylesheet" type="text/css" href="'.$css.'">'."\n";
                        }
                        // Inclusion des fichiers JS (bibliothèques)
                        foreach ($pathtojs as $js)
                        {
                            echo '<script type="text/javascript" language="javascript" src="'.$js.'"></script>'."\n";
                        }
			?>
			<script type="text/javascript" language="javascript">
			
			$(document).ready(function() {
                                var $form = $('div.fiche form').first(); // Les formulaire de liste n'ont pas tous les même name

                                // Case of task list into project
                                <?php if (strpos($parameters['context'], 'projecttasklist') !== false) { ?>
                                    $('#id-right > form#searchFormList div.titre').first().append('<?php echo $download; ?>');
                                <?php } else { ?>
                                    $('div.fiche div.titre').first().append('<?php echo $download; ?>'); // Il peut y avoir plusieurs titre dans la page
                                <?php } ?>

                                $(document).click(function() {
                                    $('.dropdown-click .dropdown-content').removeClass('show');
                                });

                                $('.drop-btn').click(function(e) {
                                    e.stopPropagation();
                                    $('.dropdown-click .dropdown-content').removeClass('show');
                                    $(this).next().addClass('show');
                                });
                                
                                $(".import").on('click', function(event) {
                                    var $self = $(this);
                                    var $format = $self.attr("title");

                                    if ($format == 'xlsx')
                                    {
                                        // xlsx import
                                        $('#import-file-input').attr("accept", ".xlsx").attr("name", "xlsx");
                                        $('#import-file-input').click();
                                    }
                                });
                                $('#import-file-input').change(function() {
                                    var fileinput = this;
                                    var filetype = $(fileinput).attr('name');
                                    var filename = $(fileinput).val();
                                    var $popup_message = '';
                                    
                                    switch (filetype)
                                    {
                                        case 'xlsx':
                                                $popup_message = '<?php echo $langs->trans('FileImportationInProgress', 'xlsx'); ?>';
                                                break;
                                       
                                    }
                                    
                                    $('#dialogforpopup').html($popup_message);
                                    $('#dialogforpopup').dialog({
                                            title: '<?php echo $langs->trans('ListImport'); ?>',
                                            buttons: {},
                                            open : function(event, ui) {
                                                    // Importation du fichier
                                                    var ajax_url = '<?php echo dol_buildpath('/doliexportlist/ajax/ajax.php', 1); ?>';
                                                    readFile(fileinput.files[0], filetype, $form.attr('action'), filename, ajax_url);
                                            }
                                    });
                                });
                                $(".export").on('click', function(event) {
                                        var $self = $(this);
                                        var $format = $self.attr("title");
                                        var $listname = $(document).find("title").text();
                                        var $filename = $listname != '' ? $listname : 'export';
                                        var $popup_message = '';
                                        
                                        // Get popup message & Add filename extension
                                        switch ($format)
                                        {
                                           
                                            case 'xlsx':
                                                $popup_message = '<?php echo $langs->trans('FileGenerationInProgress', 'xlsx'); ?>';
                                                $filename = $filename + '.xlsx';//$filename += '.csv';
                                                break;
                                            case 'pdf':
                                                $popup_message = '<?php echo $langs->trans('FileGenerationInProgress', 'PDF'); ?>';
                                                $filename = $filename + '.pdf';//$filename += '.pdf';
                                                break;
                                            default:
                                                $popup_message = '<?php echo $langs->trans('FileGenerationInProgress'); ?>';
                                        }
                                        
                                            // Récupération des données du formulaire de filtre et transformation en objet
                                            var data = objectifyForm($form.serializeArray());

                                            // Pas de limite, on veut télécharger la liste totale
                                            data.limit = 10000000;
                                            data.socid = <?php echo $socid; ?>;

                                            $('#dialogforpopup').html($popup_message);
                                            $('#dialogforpopup').dialog({
                                                    title: '<?php echo $langs->trans('ListExport'); ?>',
                                                    buttons: {},
                                                    open : function(event, ui) {
                                                            // Envoi de la requête HTTP en mode synchrone
                                                            $.ajax({
                                                                    url: $form.attr('action'),
                                                                    type: $form.attr('method'),
                                                                    data: data,
                                                                    async: false
                                                            }).done(function(html) {
                                                                    // Récupération de la table html qui nous intéresse
                                                                    var $table = $(html).find('table.liste,table#listtable');
                                                                    var has_search_button = $table.has('input[name="button_search"],th.maxwidthsearch').length;

                                                                    // Nettoyage de la table avant conversion en CSV
                                                                    // Suppression des filtres de la liste
                                                                    $table.find('tr.liste_titre_filter').remove(); // >= 6.0
                                                                    $table.find('tr:has(td.liste_titre)').remove(); // < 6.0
                                                                    
                                                                    // Suppression des éléments ignorés / à ne pas exporter
                                                                    $table.find('th.do_not_export, td.do_not_export').remove();

                                                                    // Suppression de la dernière colonne qui contient seulement les loupes des filtres
                                                                    if (has_search_button) {
                                                                        
                                                                        <?php if(empty(getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN', ''))){?>
                                                                        $table.find('th:last-child, td:last-child').each(function(index){
                                                                            $(this).find('dl').remove();
                                                                            if($(this).closest('table').hasClass('liste')) $(this).remove();
                                                                        });
                                                                        
                                                                        <?php } else{  ?>
                                                                            $table.find('th:first-child, td:first-child').each(function(index){
                                                                                $(this).find('dl').remove();
                                                                                if($(this).closest('table').hasClass('liste')) $(this).remove();
                                                                            });
                                                                        <?php } ?>
                                                                    }

                                                                    // Suppression de la ligne TOTAL en pied de tableau
                                                                    <?php if(empty($conf->global->LIST_EXPORT_IMPORT_DONT_REMOVE_TOTAL)) { ?>
                                                                   // $table.find('tr.liste_total').remove();
                                                                    <?php } ?>

                                                                    // Suppression des espaces pour les nombres
                                                                    <?php if(!empty($conf->global->LIST_EXPORT_IMPORT_DELETESPACEFROMNUMBER)) { ?>
                                                                        $table.find('td').each(function(e) {
                                                                            var nbWthtSpace = $(this).text().replace(/ /g,'').replace(/\xa0/g,'');
                                                                            var commaToPoint = nbWthtSpace.replace(',', '.');
                                                                            if($.isNumeric(commaToPoint)) $(this).html(nbWthtSpace);
                                                                        });
                                                                    <?php } ?>

                                                                    // Remplacement des sous-table par leur valeur text(), notamment pour la ref dans les listes de propales, factures...
                                                                    $table.find('td > table').map(function(i, cell) {
                                                                            $cell = $(cell);
                                                                            $cell.html($cell.text());
                                                                    });

                                                                    // Generation
                                                                    switch ($format)
                                                                    {
                                                                        case 'xlsx':
                                                                            // Transformation de la table liste en CSV + téléchargement
                                                                            var args = [$table.first(), $filename]; // .first() to avoid conflits with other tables like the volume calculator table for example
                                                                            exportTableToCSV.apply($self, args);
                                                                            break;
                                                                        case 'pdf':
                                                                            //exportTableToPDF($table);//, $filename);
                                                                            // Only pt supported (not mm or in)
                                                                            var doc = new jsPDF('l', 'pt'); // 'p' for a vertical orientation & 'l' for an horizontal orientation
                                                                            <?php if ($conf->global->LIST_EXPORT_IMPORT_PRINT_DATE_ON_PDF_EXPORT) { ?>
                                                                                var today = new Date();
                                                                                var date = 'd/m/Y'.replace('Y', today.getFullYear())
                                                                                                  .replace('m', today.getMonth()+1)
                                                                                                  .replace('d', today.getDate());
                                                                                var width = doc.internal.pageSize.width;
                                                                                doc.setFontSize(8);
                                                                                doc.text(width - 80, 30, date);
                                                                            <?php } ?>
                                                                            var res = doc.autoTableHtmlToJson($table.get(0));
                                                                            doc.autoTable(res.columns, res.data, {styles: {fontSize: 8, overflow: 'linebreak'} });
                                                                            //doc.output('dataurlnewwindow');
                                                                            doc.save($filename);
                                                                            break;
                                                                        /*case 'png':
                                                                            var args = [$table, $filename];
                                                                            exportTableToPNGFromHTML.apply($self, args);
                                                                            return;//break;*/
                                                                    }

                                                                    $('#dialogforpopup').dialog('close');
                                                            });
                                                    }
                                            });
                                        
				});
			});
			
			</script>
			<?php
                    } // end if ($user->rights->doliexportlist->export || $user->rights->doliexportlist->import)
		}

        return 0;
	}
}
