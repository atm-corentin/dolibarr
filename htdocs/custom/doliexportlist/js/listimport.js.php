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
if (false === (@include '../../main.inc.php')) { // From htdocs directory
    require '../../../main.inc.php'; // From "custom" directory
}

// Define javascript type
top_httphead('text/javascript; charset=UTF-8');
// Important: Following code is to avoid page request by browser and PHP CPU at each Dolibarr page access.
if (empty($dolibarr_nocache)) header('Cache-Control: max-age=3600, public, must-revalidate');
else header('Cache-Control: no-cache');

global $conf, $langs;

$langs->load('doliexportlist@doliexportlist');

//if (! empty($conf->use_javascript_ajax))
//{

?>

// function to read file in client side
function readFile(file, type, url, filename, ajax_url)
{
        if (window.File && window.FileReader && window.FileList && window.Blob)
        {
            var reader = new FileReader();
            reader.onload = function(e) {
                var filecontent = e.target.result;
                importFrom(type, url, filename, filecontent, ajax_url);
            }
            reader.readAsText(file);
        }
        else
        {
            if (window.XMLHttpRequest)
            {// code for IE7+, Firefox, Chrome, Opera, Safari
                xmlhttp=new XMLHttpRequest();
            }
            else
            {// code for IE6, IE5
                xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
            }
            xmlhttp.open("GET",file,false);
            xmlhttp.send();
            xmlDoc=xmlhttp.responseText;
            
            var filecontent = xmlDoc;
            
            importFrom(type, url, filename, filecontent, ajax_url);
        }
}

// function : import from (type)
function importFrom(type, url, filename, filecontent, ajax_url)
{
    if (type == 'csv') {
        importFromCSV(url, filename, filecontent, ajax_url);
    }
    else {
        importFromSQL(url, filename, filecontent, ajax_url);
    }
}

// function: import from sql string
function importFromSQL(url, filename, sql, ajax_url)
{
    data = {};
    data.action = 'import_sql';
    data.url = url;
    data.filename = filename;
    data.sql = escapeSQL(sql);
    
    $.ajax({
            url: ajax_url,
            type: 'post',
            data: data,
            async: false
    }).done(function(response) {
            //console.log(response);
            if (response.length > 0 && response != 'success') {
                $('#dialogforpopup').dialog('close');
                alert(response);
            }
            else if (url.length > 0) { location.href = url; }

            //$('#dialogforpopup').dialog('close');
    });
}

// function: escape sql
// use this function to escape SQL injection detection in dolibarr
function escapeSQL(sql)
{
    var result = sql;
    var escape_words = ['INSERT', 'INTO', 'VALUES'];
    var reversed_words = ['TRESNI', 'OTNI', 'SEULAV'];
    
    var i, value;
    for (i = 0; i < escape_words.length; i++) {
        //value = escape_words[i];
        //result = result.replace(value, reverse(value));
        result = result.replace(new RegExp(escape_words[i], 'g'), reversed_words[i]);
    }
    
    //console.log(result);
    return result;
}

// function: reverse
function reverse(s) {
  var o = '';
  for (var i = s.length - 1; i >= 0; i--)
    o += s[i];
  return o;
}

// function: import from csv
function importFromCSV(url, filename, csv, ajax_url)
{
    data = {};
    data.action = 'import_csv';
    data.url = url;
    data.filename = filename;
    data.csv = escapeCSV(csv);
    
    $.ajax({
            url: ajax_url,
            type: 'post',
            data: data,
            async: false
    }).done(function(result) {
            $('#dialogforpopup').dialog('close');
            
            if (result == 'wrongfile')
            {
                alert("<?php echo $langs->trans('WrongFileExt', 'CSV'); ?>");
            }
            else if (result.length > 0)
            {
                $('#dialogforpopup').html(result);
                $('#dialogforpopup').dialog({
                        title: "<?php echo $langs->trans('ConfirmImport'); ?>",
                        autoOpen: true,
                        open: function() {
                            $(this).parent().find("button.ui-button:eq(2)").focus();
                        },
                        resizable: false,
                        height: "200",
                        width: "500",
                        modal: true,
                        closeOnEscape: false,
                        buttons: {
                            "Yes": function() {
                                    // Envoi de la requête HTTP en mode synchrone
                                    data.action = 'import_sql';
                                    data.filename = 'generated_sql.sql';
                                    data.sql = escapeSQL(result);
                                    $.ajax({
                                            url: ajax_url,
                                            type: 'post',
                                            data: data,
                                            async: false
                                    }).done(function(response) {
                                            //console.log(response);
                                            if (response.length > 0 && response != 'success') {
                                                $('#dialogforpopup').dialog('close');
                                                alert(response);
                                            }
                                            else {
                                                var options="";
                                                var pageyes = url;
                                                var urljump = pageyes + (pageyes.indexOf("?") < 0 ? "?" : "") + options;
                                                //alert(urljump);
                                                if (pageyes.length > 0) { location.href = urljump; }
                                                else {  $('#dialogforpopup').dialog('close'); }
                                            }
                                    });
                            },
                            "No": function() {
                                    var options = "";
                                    var pageno="";
                                    var urljump=pageno + (pageno.indexOf("?") < 0 ? "?" : "") + options;
                                    //alert(urljump);
                                    if (pageno.length > 0) { location.href = urljump; }
                                    $('#dialogforpopup').dialog('close');
                            }
                        }
                });
            }
            else
            {
                alert("<?php echo $langs->trans('ImportFailed'); ?>");
            }
    });
}

// function: escape csv
function escapeCSV(csv)
{
    return csv.replace(new RegExp('"', 'g'), ''); // remove all '"'
}

<?php

//}
