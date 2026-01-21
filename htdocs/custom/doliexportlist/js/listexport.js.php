<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) <year>  <name of author>
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
 * \file    js/myjs.js.php
 * \ingroup mymodule
 * \brief   Example JavaScript.
 *
 * Put detailed description here.
 */

//if (! defined('NOREQUIREUSER')) define('NOREQUIREUSER','1');  // Not disabled cause need to load personalized language
//if (! defined('NOREQUIREDB'))   define('NOREQUIREDB','1');
if (! defined('NOREQUIRESOC'))    define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN')) define('NOREQUIRETRAN','1');  // Not disabled cause need to do translations
if (! defined('NOCSRFCHECK'))     define('NOCSRFCHECK',1);
if (! defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL',1);
if (! defined('NOLOGIN'))         define('NOLOGIN',1);
if (! defined('NOREQUIREMENU'))   define('NOREQUIREMENU',1);
if (! defined('NOREQUIREHTML'))   define('NOREQUIREHTML',1);
if (! defined('NOREQUIREAJAX'))   define('NOREQUIREAJAX','1');

session_cache_limiter(FALSE);

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

// Define javascript type
top_httphead('text/javascript; charset=UTF-8');
// Important: Following code is to avoid page request by browser and PHP CPU at each Dolibarr page access.
if (empty($dolibarr_nocache)) header('Cache-Control: max-age=3600, public, must-revalidate');
else header('Cache-Control: no-cache');

global $conf;

//if (! empty($conf->use_javascript_ajax))
//{

?>

/**
 * Returns a clone of the jquery collection $elem with all invisible (display:none) elements removed
 * @param $elem  A jQuery collection
 * @returns cloned jQuery collection
 */
function stripInvisible($elem) {
    // https://stackoverflow.com/a/28963556/11987795
    var $clone = $elem.clone();
    $('body').append($clone);
    $clone.find('*:not(:visible)').remove();
    $clone.remove();
    return $clone;
}

// Function found here : https://stackoverflow.com/questions/16078544/export-to-csv-using-jquery-and-html
function exportTableToCSV($table, filename) {
	console.log($table);		
	var $rows = stripInvisible($table).find('tr:has(th),tr:has(td)'),

	// Temporary delimiter characters unlikely to be typed by keyboard
	// This is to avoid accidentally splitting the actual contents
	tmpColDelim = String.fromCharCode(11), // vertical tab character
	tmpRowDelim = String.fromCharCode(0), // null character

	// actual delimiter characters for CSV format
	colDelim = '||',
	rowDelim = '@@_@@';
	
	

	// Grab text from table into CSV formatted string
	csv = ($rows.map(function(i, row) {
		var $row = $(row),
		$cols = $row.find('th,td');

		return $cols.map(function(j, col) {
			var $col = $(col);

			var text = "";
			if ($col.find("span.linkobject:not(.hideobject)").length > 0) {
				// Fix sur liste produit si conf MAIN_DIRECT_STATUS_UPDATE active
				text = $col.find("span.linkobject:not(.hideobject)").children().first().attr('title').trim();
			} else if ($col.find('a').length > 0 && $col.find('a')[0].href.indexOf('mailto:') == 0) {
                // Fix mails tronqués dans les listes par dol_trunc dans la fonction dol_print_email
                var link = $col.find('a')[0].href;
                text = link.substr(7);
            } else {
                text = $col.text().trim();
            }

            // Spécifique pour "nettoyer" les données
			// Si texte vide, on cherche une image et on prend le title
			if(text == '' && $col.find('img').length > 0) {
                var imgtitle = $col.find('img').attr('title');
                if (imgtitle != undefined) text = imgtitle.trim();
            }

			return text.replace(/"/g, '""'); // escape double quotes

		}).get().join(tmpColDelim);

	}).get().join(tmpRowDelim)
	.split(tmpRowDelim).join(rowDelim)
	.split(tmpColDelim).join(colDelim) + '');
	
	var excel = csv.split('@@_@@');
	for(line in excel ){
		excel[line] = excel[line].split('||');
	}
	
	//console.log(excel);
	
	
	var wb = XLSX.utils.book_new();
        wb.Props = {
                Title: "Dolibarr Sheet",
                Subject: "Dolibarr Sheet",
                Author: "Dolibarr Sheet",
                //CreatedDate: new Date(2017,12,19)
        };
        
        wb.SheetNames.push("Sheet 1");
        var ws_data = excel;
        var ws = XLSX.utils.aoa_to_sheet(ws_data);

        for(var cell in ws){
			if(ws[cell].v != undefined && (ws[cell].v).length > 0){
				var vali = ws[cell].v;
				if(vali.length > 3){
					if(vali[vali.length - 3] == ","){
						vali = vali.replaceAll('.' , '');
						vali = vali.replaceAll(' ' , '');
						vali = vali.replaceAll(',' , '.');
					}
					else{
						vali = vali.replaceAll(',' , '');
					}
				}
				var codep = false;
				if(vali[0]=="0" && vali.length > 3){ //Puede ser un codigo postal u otro código si inicia con cero y no tiene decimales no lo formateo como número
					if(vali[vali.length - 3] != "."){
						codep = true;
					}
				}
				if((isInt(vali) || isFloat(vali)) && !codep ){
					ws[cell].v = vali;
					if(vali.length > 3){ // only is decimal
						if(vali[vali.length - 3] == "."){
							ws[cell].z = "0.00";
						}
					}
       				ws[cell].t = "n" ; // delete old formatted text if it exists
				}
	        	//isInt(vali) || isFloat(vali) ? console.log("numero" + vali + "==") : console.log("no"+vali);
       		}
			
        }
        
        wb.Sheets["Sheet 1"] = ws;

        var wbout = XLSX.write(wb, {bookType:'xlsx',  type: 'binary'});
        function s2ab(s) {
  
                var buf = new ArrayBuffer(s.length);
                var view = new Uint8Array(buf);
                for (var i=0; i<s.length; i++) view[i] = s.charCodeAt(i) & 0xFF;
                return buf;
                
        }
        saveAs(new Blob([s2ab(wbout)],{type:"application/octet-stream"}), filename);
        $("#button-a").click(function(){
                saveAs(new Blob([s2ab(wbout)],{type:"application/octet-stream"}), filename);
        });

	// download file
	//downloadFile(this, filename, csv, 'text/csv;charset=utf-8', 'data:application/csv;charset=utf-8,');
}

function isInt(n){
    return n !== undefined && isNumeric(n) && n % 1 === 0 && n.trim() !== "";
}

function isFloat(n){
	
    return n !== undefined && isNumeric(n) &&  n % 1 !== 0 && n.trim() !== "";
}

function isNumeric(str) {
  if (typeof str != "string") return false // we only process strings!  
  return !isNaN(str) && // use type coercion to parse the _entirety_ of the string (`parseFloat` alone does not do this)...
         !isNaN(parseFloat(str)) // ...and ensure strings of whitespace fail
}

/*
 function exportTableToCSV($table, filename) {
					
	var $rows = stripInvisible($table).find('tr:has(th),tr:has(td)'),

	// Temporary delimiter characters unlikely to be typed by keyboard
	// This is to avoid accidentally splitting the actual contents
	tmpColDelim = String.fromCharCode(11), // vertical tab character
	tmpRowDelim = String.fromCharCode(0), // null character

	// actual delimiter characters for CSV format
	colDelim = '"<?php echo (! empty($conf->global->EXPORT_CSV_SEPARATOR_TO_USE)?$conf->global->EXPORT_CSV_SEPARATOR_TO_USE:';'); ?>"',
	rowDelim = '"\r\n"',

	// Grab text from table into CSV formatted string
	csv = '"' + $rows.map(function(i, row) {
		var $row = $(row),
		$cols = $row.find('th,td');

		return $cols.map(function(j, col) {
			var $col = $(col);

			var text = "";
			if ($col.find("span.linkobject:not(.hideobject)").length > 0) {
				// Fix sur liste produit si conf MAIN_DIRECT_STATUS_UPDATE active
				text = $col.find("span.linkobject:not(.hideobject)").children().first().attr('title').trim();
			} else if ($col.find('a').length > 0 && $col.find('a')[0].href.indexOf('mailto:') == 0) {
                // Fix mails tronqués dans les listes par dol_trunc dans la fonction dol_print_email
                var link = $col.find('a')[0].href;
                text = link.substr(7);
            } else {
                text = $col.text().trim();
            }

            // Spécifique pour "nettoyer" les données
			// Si texte vide, on cherche une image et on prend le title
			if(text == '' && $col.find('img').length > 0) {
                var imgtitle = $col.find('img').attr('title');
                if (imgtitle != undefined) text = imgtitle.trim();
            }

			return text.replace(/"/g, '""'); // escape double quotes

		}).get().join(tmpColDelim);

	}).get().join(tmpRowDelim)
	.split(tmpRowDelim).join(rowDelim)
	.split(tmpColDelim).join(colDelim) + '"';

	// download file
	downloadFile(this, filename, csv, 'text/csv;charset=utf-8', 'data:application/csv;charset=utf-8,');
}

 */

//serialize data function
function objectifyForm(formArray) {
	var returnArray = {};
	for (var i = 0; i < formArray.length; i++) {
		if (formArray[i]['name'].substring(formArray[i]['name'].length - 2) == "[]") {
            var name = formArray[i]['name'].substring(0, formArray[i]['name'].length - 2);
            if (!returnArray.hasOwnProperty(name)) returnArray[name] = [];
            returnArray[name].push(formArray[i]['value']);
        }
        else returnArray[formArray[i]['name']] = formArray[i]['value'];
	}
	return returnArray;
}

//function: download file
function downloadFile(self, filename, data, type, type2)
{
    try {
        blob = new Blob([data], {type: type});
        saveAs(blob, filename);
    }
    catch (e) {
        // Deliberate 'false', see comment below
	if (false && window.navigator.msSaveBlob) {

		var blob = new Blob([decodeURIComponent(data)], {
			type: type
		});

		// Crashes in IE 10, IE 11 and Microsoft Edge
		// See MS Edge Issue #10396033
		// Hence, the deliberate 'false'
		// This is here just for completeness
		// Remove the 'false' at your own risk
		window.navigator.msSaveBlob(blob, filename);

	} else if (window.Blob && window.URL) {
		// HTML5 Blob
		var blob = new Blob([data], {
			type: type
		});
		var url = URL.createObjectURL(blob);

		$(self).attr({
			'download': filename,
			'href': url
		});
	} else {
		// Data URI
		var newData = type2 + encodeURIComponent(data);

		$(self).attr({
			'download': filename,
			'href': newData,
			'target': '_blank'
		});
	}
    }
}

// Function found here : https://github.com/kayalshri/tableExport.jquery.plugin/blob/master/tableExport.js
/*
function exportTableToPDF($table, filename='') {
        var doc = new jsPDF('p','pt', 'a4', true);
        var defaults = {pdfFontSize:8, pdfLeftMargin:20, htmlContent:'false', escape:'false'};
        var startColPosition = defaults.pdfLeftMargin;
        var width = 80;
        
        doc.setFontSize(defaults.pdfFontSize);
        
        // Row Vs Column
        var startRowPosition = 20; var page =1;var rowPosition=0;
        $table.find('tbody').find('tr').each(function(index,data) {
                rowCalc = index+1;
                
                if (rowCalc % 26 == 0){
                        doc.addPage();
                        page++;
                        startRowPosition=startRowPosition+10;
                }
                rowPosition=(startRowPosition + (rowCalc * 10)) - ((page -1) * 280);
                
                $(this).find('th,td').each(function(index,data) {
                        if ($(this).css('display') != 'none'){
                                var colPosition = startColPosition+ (index * width);									
                                doc.text(colPosition,rowPosition, parseString($(this), defaults));
                        }
                });
        });
        
        // Output
        if (filename == '')
        {
            // Output as Data URI
            doc.output('dataurlnewwindow');
        }
        else
        {
            doc.save(filename);
        }
}

//parse string function
function parseString(data, defaults){
				
        if(defaults.htmlContent == 'true'){
                content_data = data.html().trim();
        }else{
                content_data = data.text().trim();
        }

        if(defaults.escape == 'true'){
                content_data = escape(content_data);
        }
        
        return content_data;
}
*/

// Function: export to file
function exportTableToFile(filetype, data, filename) {
        if (filetype == 'sql') {
            exportTableToSQL(data, filename);
        }
        else if (filetype == 'csvfromdb') {
            // download file
            downloadFile(this, filename, data, 'text/csv;charset=utf-8', 'data:application/csv;charset=utf-8,');
        }
}

// Function: export to sql
function exportTableToSQL(data, filename) {
        // download file
	downloadFile(this, filename, data, 'text/plain;charset=utf-8', 'data:application/sql;charset=utf-8,');
}

// Function: export to png
function exportTableToPNG(tablesel, filename='') {
        var self = this;
        var $table = $(tablesel);
        /*
        // hide filter row
        $table.find('tr.liste_titre_filter').hide();//.attr("data-html2canvas-ignore", "true"); // >= 6.0
        $table.find('tr:has(td.liste_titre)').hide();//.attr("data-html2canvas-ignore", "true"); // < 6.0
        */
        html2canvas($table.get(0), {
            onrendered: function (canvas) {
                var image = canvas.toDataURL("image/png");
                var byteString = atob(image.substring(22)); // remove data stuff
                var buffer     = new ArrayBuffer(byteString.length);
                var intArray   = new Uint8Array(buffer);
                
                for ( var i = 0; i < byteString.length; i++ ) {
                    intArray[i] = byteString.charCodeAt(i);
                }
                
                if (filename == '')
                {
                    window.open(image);
                    return;
                }
                /*
                // show filter row
                $table.find('tr.liste_titre_filter').show(); // >= 6.0
                $table.find('tr:has(td.liste_titre)').show(); // < 6.0
                */
                
                $('#dialogforpopup').dialog('close');
                
                // download file
                downloadFile(self, filename, buffer, 'image/png', 'data:image/png,');
            }
        });
}

/*
// Function: export to png from html
function exportTableToPNGFromHTML(table, filename='') {
        var iframe = document.createElement('iframe');
        iframe.width = "100%"; // set iframe width
        $('body').append($(iframe));
        
        setTimeout(function(){
            var iframedoc = iframe.contentDocument||iframe.contentWindow.document;
            $('body',$(iframedoc)).html(table);
            var $head = $(iframedoc).contents().find("head");
            $head.append($("<link/>", { rel: "stylesheet", href: "<?php echo DOL_URL_ROOT.$conf->css; ?>", type: "text/css" }));
            
            var self = this;
            
            //html2canvas(iframedoc.body, {
            //  onrendered: function (canvas) {
            html2canvas(iframedoc.body, {background: '#fff'}).then(
            function (canvas) {
                    var image = canvas.toDataURL();
                    var byteString = atob(image.substring(22)); // remove data stuff
                    var buffer     = new ArrayBuffer(byteString.length);
                    var intArray   = new Uint8Array(buffer);

                    for ( var i = 0; i < byteString.length; i++ ) {
                        intArray[i] = byteString.charCodeAt(i);
                    }
                    
                    // remove iframe
                    $(iframe).remove();
                    
                    // close export dialog
                    $('#dialogforpopup').dialog('close');
                    
                    if (filename == '')
                    {
                        window.open(image);
                        return;
                    }
                    
                    // download file
                    downloadFile(self, filename, buffer, 'image/png', 'data:image/png,');
              //}
            });
        }, 10);
}
*/

<?php

//}
