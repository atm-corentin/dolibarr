<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
 * Copyright (C) 2019      florian Dufourg	<florian.dufourg@outlook.fr>
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
 *	\file       easydashboard/easydashboardindex.php
 *	\ingroup    easydashboard
 *	\brief      Main page of easydashboard
 */


//***************************************************************************************************************************
// Get CA BY project
//***************************************************************************************************************************
if(substr(DOL_VERSION,0,2) > 13){
	$tabData['sum'] = ['fc.total_ht'];
	$orderBy = 'fc_total_ht DESC';
}else{
	$tabData['sum'] = ['fc.total'];
	$orderBy = 'fc_total DESC';
}

$listOfLabel = '';
$dateName = 'pr.datee';
$tabData['line'] = ['pr.title','pr.ref','pr.rowid'];
$from = MAIN_DB_PREFIX."projet as pr LEFT JOIN ".MAIN_DB_PREFIX."facture as fc ON pr.rowid = fc.fk_projet";

$where = "1 = 1";
$where = "fc.datef BETWEEN '".$datestartfiltre."' AND '".$dateendfiltre."'";
$where .= ' AND fc.fk_statut IN (1,2)';
$where .= ' AND fc.entity IN ('.getEntity('invoice').')';
if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS))	$where .= " AND fc.type IN (0,1,2,5)";
else $where .= " AND fc.type IN (0,1,2,3,5)";

$groupBy = 'pr.rowid';

$CAAllProjects = sqlQuery_byGroup($listOfLabel, $dateName, $tabData, $from, $where, $groupBy, $orderBy);

print '<pre>'; print_r($CAAllProjects); print '</pre>';


print '<div style="width: 1200px;">';

	print '<table class="border tableforfield" style="display: block; overflow-x: scroll; white-space: nowrap;">';
		print '<tbody style="display: table; width: 100%;">';
			print '<tr>';
				print '<td class="nobordernopadding valignmiddle col-title">';
					print '<span class="fa fa-file-invoice-dollar marginleftonly hideonsmartphone valignmiddle opacityhigh pictotitle widthpictotitle"></span>';
					print '<div class="titre inline-block"><b>'.$langs->trans("PROJECTS DASHBOARD").'</b></div>';
				print '</td>';
			print '</tr>';
			print '<tr>';
				print '<td>Projets</td>';

			foreach ($CAAllProjects['pr_title'] as $key => $value) {

				print '<td style="border-right: solid 1px #aaa; margin: 3px">';
				print $value;
				print "</td>";
			}

			print '</tr>';

			print '<tr>';
			print '<td>Chiffre d\'affaires</td>';

			foreach ($CAAllProjects['fc_total_ht'] as $key => $value) {

				print '<td style="border-right: solid 1px #aaa;">';
				print $value." €";
				print "</td>";
			}

			print '</tr>';
		print '</body>';
	print "</table>";
print "</div>";

