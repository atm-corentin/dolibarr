<?php
/* Copyright (C) 2026 ATM Consulting <support@atm-consulting.fr>
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
 *	\file		admin/clichaumeil_documentation.php
 *	\ingroup	clichaumeil
 *	\brief		Documentation admin tab (GitBook-like layout) — generated from team-ai-config skeleton
 *
 *	PLACEHOLDERS to substitute when scaffolding:
 *	  clichaumeil          module slug (e.g. doc2project)
 *	  CliChaumeilDocumentation lang key of the module label (e.g. Module104250Name)
 *	  CliChaumeilDocumentation       page title lang key (e.g. MymoduleDocumentation)
 */

// Dolibarr environment
$res = @include "../../main.inc.php"; // From htdocs directory
if (! $res) {
	$res = @include "../../../main.inc.php"; // From "custom" directory
}
if (! $res) {
	die("Include of main fails");
}

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once __DIR__ . "/../lib/clichaumeil.lib.php";

// Translations
$langs->load("clichaumeil@clichaumeil");

// Access control
if (empty($user->admin)) {
	accessforbidden();
}

/*
 * View
 */
$pageName = "CliChaumeilDocumentation";

// External CSS / JS assets
$arrayofcss = array('/clichaumeil/css/clichaumeil_documentation.css');
$arrayofjs  = array('/clichaumeil/js/clichaumeil_documentation.js');

llxHeader('', $langs->trans($pageName), '', '', 0, 0, $arrayofjs, $arrayofcss);

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
	. $langs->trans("BackToModuleList") . '</a>';
print_fiche_titre($langs->trans($pageName), $linkback);

// Configuration header (tab strip — prepareHead lives in lib/clichaumeil.lib.php)
$head = clichaumeilAdminPrepareHead();
dol_fiche_head(
	$head,
	'documentation',
	$langs->trans("CliChaumeilDocumentation"),
	-1,
	"generic"
);

// Read and parse markdown source
$docFile = __DIR__ . '/../docs/configuration.md';
if (! file_exists($docFile)) {
	print '<div class="warning">' . $langs->trans("CliChaumeilDocumentationFileNotFound") . '</div>';
	dol_fiche_end(-1);
	llxFooter();
	$db->close();
	return;
}

$markdownContent = file_get_contents($docFile);
$parsed = clichaumeilParseMarkdown($markdownContent);
$toc = $parsed['toc'];
$sections = $parsed['sections'];

// Render layout
print '<div class="aidoc-wrapper">';

// Sidebar
print '<nav class="aidoc-sidebar" id="aidoc-sidebar">';
foreach ($toc as $entry) {
	if ($entry['level'] === 2) {
		print '<div class="aidoc-nav-title">' . htmlspecialchars($entry['text'], ENT_QUOTES, 'UTF-8') . '</div>';
	} else {
		print '<a href="#' . $entry['id'] . '" data-section="' . $entry['id'] . '">'
			. htmlspecialchars($entry['text'], ENT_QUOTES, 'UTF-8') . '</a>';
	}
}
print '</nav>';

// Content
print '<div class="aidoc-content" id="aidoc-content">';
foreach ($sections as $section) {
	print $section['html'];
}
print '</div>';

print '</div>';

dol_fiche_end(-1);
llxFooter();
$db->close();


/**
 * Parse markdown documentation into structured TOC and HTML sections.
 *
 * @param string $markdown Raw markdown content
 * @return array{toc: array, sections: array}
 */
function clichaumeilParseMarkdown(string $markdown): array
{
	$lines = explode("\n", $markdown);
	$toc = array();
	$sections = array();
	$currentSection = null;
	$buffer = array();

	foreach ($lines as $line) {
		$trimmed = trim($line);

		// Detect headers h2 or h3
		if (preg_match('/^(#{2,3})\s+(.+)$/', $trimmed, $matches)) {
			if ($currentSection !== null) {
				$currentSection['html'] .= clichaumeilRenderBuffer($buffer);
				$buffer = array();
				$sections[] = $currentSection;
			}

			$level = strlen($matches[1]);
			$text = $matches[2];
			$id = clichaumeilSlugify($text);

			$toc[] = array('level' => $level, 'text' => $text, 'id' => $id);

			$tag = ($level === 2) ? 'h2' : 'h3';
			$currentSection = array(
				'id' => $id,
				'level' => $level,
				'html' => '<div class="aidoc-section" id="' . $id . '"><' . $tag . '>'
					. htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</' . $tag . '>',
			);
			continue;
		}

		// Skip h1 and pre-first-section content (TOC area)
		if (preg_match('/^#\s+/', $trimmed)) {
			continue;
		}
		if ($currentSection === null) {
			continue;
		}

		$buffer[] = $line;
	}

	if ($currentSection !== null) {
		$currentSection['html'] .= clichaumeilRenderBuffer($buffer);
		$sections[] = $currentSection;
	}

	foreach ($sections as &$s) {
		$s['html'] .= '</div>';
	}
	unset($s);

	return array('toc' => $toc, 'sections' => $sections);
}

/**
 * Render a buffer of markdown lines into HTML (paragraphs, lists, notes, examples).
 *
 * @param string[] $lines Lines of markdown
 * @return string HTML
 */
function clichaumeilRenderBuffer(array $lines): string
{
	$html = '';
	$i = 0;
	$count = count($lines);

	while ($i < $count) {
		$trimmed = trim($lines[$i]);

		if ($trimmed === '') {
			$i++;
			continue;
		}

		// Blockquote (notes / warnings)
		if (preg_match('/^>\s*(.*)$/', $trimmed, $matches)) {
			$noteContent = $matches[1];
			$i++;
			while ($i < $count && preg_match('/^>\s*(.*)$/', trim($lines[$i]), $bqm)) {
				$noteContent .= ' ' . $bqm[1];
				$i++;
			}

			$cssClass = 'aidoc-note';
			if (stripos($noteContent, 'Attention') !== false || stripos($noteContent, 'Warning') !== false) {
				$cssClass .= ' aidoc-note-warning';
			} elseif (stripos($noteContent, 'Note') !== false || stripos($noteContent, 'Info') !== false) {
				$cssClass .= ' aidoc-note-info';
			}

			$html .= '<div class="' . $cssClass . '">' . clichaumeilInline($noteContent) . '</div>';
			continue;
		}

		// Unordered list
		if (preg_match('/^[-*]\s+/', $trimmed)) {
			$html .= '<ul>';
			while ($i < $count && preg_match('/^[-*]\s+(.+)$/', trim($lines[$i]), $lm)) {
				$html .= '<li>' . clichaumeilInline($lm[1]) . '</li>';
				$i++;
			}
			$html .= '</ul>';
			continue;
		}

		// Regular paragraph
		$html .= '<p>' . clichaumeilInline($trimmed) . '</p>';
		$i++;
	}

	return $html;
}

/**
 * Format inline markdown: bold, italic, code.
 *
 * @param string $text Raw text
 * @return string HTML
 */
function clichaumeilInline(string $text): string
{
	$text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
	$text = preg_replace('/`([^`]+)`/', '<code>$1</code>', $text);
	$text = preg_replace('/\*\*\*(.+?)\*\*\*/', '<strong><em>$1</em></strong>', $text);
	$text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
	$text = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $text);
	return $text;
}

/**
 * Generate a URL-safe slug from header text.
 *
 * @param string $text Header text
 * @return string Slug
 */
function clichaumeilSlugify(string $text): string
{
	$text = mb_strtolower($text, 'UTF-8');
	$text = str_replace(
		array('à', 'â', 'ä', 'é', 'è', 'ê', 'ë', 'î', 'ï', 'ô', 'ö', 'ù', 'û', 'ü', 'ç', 'ñ'),
		array('a', 'a', 'a', 'e', 'e', 'e', 'e', 'i', 'i', 'o', 'o', 'u', 'u', 'u', 'c', 'n'),
		$text
	);
	$text = str_replace(array("'", "\xe2\x80\x99", "\xe2\x80\x98"), '', $text);
	$text = preg_replace('/[^a-z0-9]+/', '-', $text);
	return trim($text, '-');
}
