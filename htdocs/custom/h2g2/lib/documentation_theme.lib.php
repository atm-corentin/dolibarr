<?php

/**
 * Get the Content of the file
 *
 * @param $filename string File's Name | ex : Changelog.md or version.txt
 * @return void
 */
function displayThemeFileContent($filename)
{
	global $langs;
	require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

	$pathoffile = dol_buildpath('/themequarantedeux/'.$filename);
	if (class_exists('modThemeQuaranteDeux') && dol_is_file($pathoffile)) {     // Mostly for external modules
		$ret = file_get_contents($pathoffile);
		require_once DOL_DOCUMENT_ROOT . '/core/lib/parsemd.lib.php';
		$ret = dolMd2Html($ret, 'parsedown');
	} else {
		$ret = $langs->trans('H2G2NoFileFound', $filename);
	}
	print $ret;
}
