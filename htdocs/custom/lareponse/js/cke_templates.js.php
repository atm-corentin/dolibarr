<?php
/* Copyright (C) 2018 SuperAdmin
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
 *
 * Library javascript to enable Browser notifications
 */

if (!defined('NOREQUIREUSER')) define('NOREQUIREUSER', '1');
if (!defined('NOREQUIREDB')) define('NOREQUIREDB', '1');
if (!defined('NOREQUIRESOC')) define('NOREQUIRESOC', '1');
//if (!defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN', '1');
if (!defined('NOCSRFCHECK')) define('NOCSRFCHECK', 1);
if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1);
if (!defined('NOLOGIN')) define('NOLOGIN', 1);
if (!defined('NOREQUIREMENU')) define('NOREQUIREMENU', 1);
if (!defined('NOREQUIREHTML')) define('NOREQUIREHTML', 1);
if (!defined('NOREQUIREAJAX')) define('NOREQUIREAJAX', '1');

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
// Try main.inc.php into web root detected using web root caluclated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];$tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1)) . "/main.inc.php";
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/../main.inc.php")) $res = @include substr($tmp, 0, ($i + 1)) . "/../main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");


// Define js type
header('Content-Type: application/javascript');
// Important: Following code is to cache this file to avoid page request by browser at each Dolibarr page access.
// You can use CTRL+F5 to refresh your browser cache.
if (empty($dolibarr_nocache)) header('Cache-Control: max-age=3600, public, must-revalidate');
else header('Cache-Control: no-cache');

global $langs;
$langs->load("lareponse@lareponse");
?>

$(document).ready(function () {
	CKEDITOR.config.templates_files = []; // clean template files to replace by ours

	CKEDITOR.addTemplates("default", {
		imagesPath: CKEDITOR.getUrl(CKEDITOR.plugins.getPath("templates") + "templates/images/"),
		templates: [
			{
				title: "<?php echo $langs->trans('LaReponseCreateCustomTemplateTitle')?>",
				image: "template1.gif",
				description: "<?php echo $langs->trans('LaReponseCreateCustomTemplateDesc')?>",
				html: `<p>Merci d’avoir recours au module LaRéponse.</p>
					   <p>Vous avez des suggestions ou souhaitez concevoir un modèle spécifique pour vos publications ?</p>
					   <a href='mailto:contact@code42.fr'>Écrivez-nous à contact@code42.fr</a>
					  `
			},
			{
				title: "<?php echo $langs->trans('LaReponseTemplateUpVersionTitleTitleDesc')?>",
				image: "template1.gif",
				description: "<?php echo $langs->trans('LaReponseTemplateUpVersionTitleTitleDesc')?>",
				html: `<h1><strong>[Nom du projet] &rarr; vXX-YY-ZZ</strong></h1>`
			},
			{
				title: "<?php echo $langs->trans('LaReponseTemplateUpVersionTextImageTitleDesc')?>",
				image: "template1.gif",
				description: "<?php echo $langs->trans('LaReponseTemplateUpVersionTextImageTitleDesc')?>",
				html: `<table border="0" cellpadding="0px" cellspacing="16px" style="background:var(--colorbackvmenu1, #eee); border-radius:1em; color:var(--colortextbackvmenu, black); width:100%">
						<tbody>
							<tr>
								<td style="width:50%"><strong><span style="font-size:16px"><span style="background-color:var(--top-info-success, green); color:var(--top-info-success--text, white)">&nbsp;NOUVEAU&nbsp;!&nbsp;</span></span></strong>

								<h2><strong>Texte</strong></h2>
								<span style="font-size:16px">Texte</span><br />
								&nbsp;</td>
								<td style="width:50%"><img alt="" src="https://uizard.io/static/cloud-sharing-web-app-cover-deea5d25e75c44f560ebb21add40ba53.png" style="border-radius:0.5em; width:100%" /></td>
							</tr>
						</tbody>
					</table>`
			},
			{
				title: "<?php echo $langs->trans('LaReponseTemplateUpVersion2ColTextImageTitleDesc')?>",
				image: "template1.gif",
				description: "<?php echo $langs->trans('LaReponseTemplateUpVersion2ColTextImageTitleDesc')?>",
				html: `<table border="0" cellpadding="0" cellspacing="16px" style="width:100%">
						<tbody>
							<tr>
								<td style="width:50%">&nbsp;
								<table border="0" cellpadding="0px" cellspacing="16px" style="background:var(--colorbackvmenu1, #eee); border-radius:1em; color:var(--colortextbackvmenu, black); width:100%">
									<tbody>
										<tr>
											<td><img alt="" src="https://uizard.io/static/cloud-sharing-web-app-cover-deea5d25e75c44f560ebb21add40ba53.png" style="border-radius:0.5em; width:100%" /></td>
										</tr>
										<tr>
											<td>
											<h2><strong>Texte</strong></h2>
											</td>
										</tr>
										<tr>
											<td><span style="font-size:16px">Texte</span></td>
										</tr>
									</tbody>
								</table>
								</td>
								<td style="width:50%">&nbsp;
								<table border="0" cellpadding="0px" cellspacing="16px" style="background:var(--colorbackvmenu1, #eee); border-radius:1em; color:var(--colortextbackvmenu, black); width:100%">
									<tbody>
										<tr>
											<td><img alt="" src="https://uizard.io/static/cloud-sharing-web-app-cover-deea5d25e75c44f560ebb21add40ba53.png" style="border-radius:0.5em; width:100%" /></td>
										</tr>
										<tr>
											<td>
											<h2><strong>Texte</strong></h2>
											</td>
										</tr>
										<tr>
											<td><span style="font-size:16px">Texte</span></td>
										</tr>
									</tbody>
								</table>
								</td>
							</tr>
						</tbody>
					</table>`
			},
			{
				title: "<?php echo $langs->trans('LaReponseTemplateUpVersion3ColTextImageTitleDesc')?>",
				image: "template1.gif",
				description: "<?php echo $langs->trans('LaReponseTemplateUpVersion3ColTextImageTitleDesc')?>",
				html: `<table border="0" cellpadding="0" cellspacing="16px" style="width:100%">
						<tbody>
							<tr>
								<td style="width:33%">&nbsp;
								<table border="0" cellpadding="0px" cellspacing="16px" style="background:var(--colorbackvmenu1, #eee); border-radius:1em; color:var(--colortextbackvmenu, black); width:100%">
									<tbody>
										<tr>
											<td><img src="https://uizard.io/static/cloud-sharing-web-app-cover-deea5d25e75c44f560ebb21add40ba53.png" style="width:100%" /></td>
										</tr>
										<tr>
											<td>
											<h2><strong>Texte</strong></h2>
											</td>
										</tr>
										<tr>
											<td><span style="font-size:16px">Texte</span></td>
										</tr>
									</tbody>
								</table>
								</td>
								<td style="width:33%">&nbsp;
								<table border="0" cellpadding="0px" cellspacing="16px" style="background:var(--colorbackvmenu1, #eee); border-radius:1em; color:var(--colortextbackvmenu, black); width:100%">
									<tbody>
										<tr>
											<td><img src="https://uizard.io/static/cloud-sharing-web-app-cover-deea5d25e75c44f560ebb21add40ba53.png" style="width:100%" /></td>
										</tr>
										<tr>
											<td>
											<h2><strong>Texte</strong></h2>
											</td>
										</tr>
										<tr>
											<td><span style="font-size:16px">Texte</span></td>
										</tr>
									</tbody>
								</table>
								</td>
								<td style="width:33%">&nbsp;
								<table border="0" cellpadding="0px" cellspacing="16px" style="background:var(--colorbackvmenu1, #eee); border-radius:1em; color:var(--colortextbackvmenu, black); width:100%">
									<tbody>
										<tr>
											<td><img alt="" src="https://uizard.io/static/cloud-sharing-web-app-cover-deea5d25e75c44f560ebb21add40ba53.png" style="border-radius:0.5em; width:100%" /></td>
										</tr>
										<tr>
											<td>
											<h2><strong>Texte</strong></h2>
											</td>
										</tr>
										<tr>
											<td>Texte</td>
										</tr>
									</tbody>
								</table>
								</td>
							</tr>
						</tbody>
					</table>`
			},
			{
				title: "<?php echo $langs->trans('LaReponseTemplateUpVersion2ColTextTitleDesc')?>",
				image: "template1.gif",
				description: "<?php echo $langs->trans('LaReponseTemplateUpVersion2ColTextTitleDesc')?>",
				html: `<table border="0" cellpadding="0" cellspacing="16px" style="width:100%">
						<tbody>
							<tr>
								<td style="vertical-align:top; width:50%">
								<h2><strong>Texte</strong></h2>

								<hr />
								<h3><strong>Texte</strong></h3>

								<ul>
									<li>Texte</li>
									<li>Texte</li>
								</ul>

								<h3><strong>Texte</strong></h3>

								<ul>
									<li>Texte</li>
								</ul>
								</td>
								<td style="vertical-align:top; width:50%">
								<h2><strong>Texte</strong></h2>

								<hr />
								<h3><strong>Texte</strong></h3>

								<ul>
									<li>
									<p>Texte</p>
									</li>
									<li>
									<p>Texte</p>
									</li>
								</ul>

								<h3><strong>Texte</strong></h3>

								<ul>
									<li>Texte</li>
								</ul>
								</td>
							</tr>
						</tbody>
					</table>`
			},
			{
				title: "<?php echo $langs->trans('LaReponseTemplateUpVersionCTAOrange')?>",
				image: "template1.gif",
				description: "<?php echo $langs->trans('LaReponseTemplateUpVersionCTAOrange')?>",
				html: `<table border="0" cellpadding="16px" cellspacing="0" style="background:#e67e22; border-radius:1em; color:white; width:100%">
						<tbody>
							<tr>
								<td>
								<p style="text-align:center"><span style="font-size:18px"><span style="color:#000000"><strong>Texte</strong><br />
								Texte</span></span></p>
								</td>
							</tr>
						</tbody>
					</table>`
			}
		]
	});
});