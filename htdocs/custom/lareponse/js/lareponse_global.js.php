<?php
/* Copyright (C) 2018 SuperAdmin
 * Copyright (C) 2025 Arthur Croix <arthur@code42.fr>
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

if (!defined('NOREQUIRESOC')) define('NOREQUIRESOC', '1');
if (!defined('NOCSRFCHECK')) define('NOCSRFCHECK', 1);
if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1);
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

global $langs, $user, $conf, $db;

$langs->load("lareponse@lareponse", "main");

$toc_opened_by_default = true;

if (isset($user->conf->LAREPONSE_TOC_FOLD_DEFAULT)) {
    $toc_opened_by_default = !$user->conf->LAREPONSE_TOC_FOLD_DEFAULT;
} else if (isset($conf->global->LAREPONSE_TOC_FOLD_DEFAULT)) {
    $toc_opened_by_default = !$conf->global->LAREPONSE_TOC_FOLD_DEFAULT;
}

$is_public_article = strpos($_SERVER['HTTP_REFERER'], "lareponse/public/public_article.php") !== false;
?>


/**
 * Generates a dynamic Table of Contents (ToC) based on heading tags (e.g., <h1> to <h6>)
 * within a given container, and inserts the ToC into the specified element.
 *
 * The function:
 * - Parses all heading tags inside the `querySelector` container.
 * - Adds unique anchor IDs to each heading.
 * - Builds a nested list structure (<ul><li>) reflecting heading hierarchy.
 * - Inserts the generated ToC HTML at the beginning of the target container (`tocInsert` or `querySelector`).
 *
 * @param {string} querySelector - The CSS selector for the content container containing headings.
 * @param {string} [tocInsert] - Optional CSS selector where the generated ToC should be inserted. Defaults to `querySelector`.
 */
function generateToC(querySelector, tocInsert) {
	var toc = "",
		level = 0;

	document.querySelector(querySelector).innerHTML = document
		.querySelector(querySelector)
		.innerHTML.replace(
			/<h([\d])[^>]*>(.*)<\/h([\d])>/gim, //catch all level of title with its content

			function (str, openLevel, titleText, closeLevel) {
                titleText = titleText.replace(/<img\b[^>]*>(?:<\/img>)?/gi, ''); // Removes img
                if (titleText == "") return ""; // Skip titles that only contains images
				if (openLevel != closeLevel) {
					return str;
				}

				if (openLevel > level) {
					toc += new Array(openLevel - level + 1).join("<ul>");
				} else if (openLevel < level) {
					toc += new Array(level - openLevel + 1).join("</ul>");
				}

				level = parseInt(openLevel);

				var anchor = titleText
					.replace(/<(?:|\/)[^>]*>/g, "") // Remove span
					.replace(/[^\d|^a-zA-Z]/g, "-") // Keep only alphanumeric
					.replace(/--+/g, "-");
				toc += '<li><a href="#' + anchor + '"><span>' + titleText + "</span></a></li>";

				return (
					"<h" +
					openLevel +
					' id="' +
					anchor +
					'"' +
					' class="ToC-head">' +
					titleText +
					'<a class="ToC-head--link" href="#' +
					anchor +
					'"></a>' +
					"</h" +
					closeLevel +
					">"
				);
			}
		);

	if (level) {
		toc += new Array(level + 1).join("</ul>");
	}

    if (toc !== "") {
        document
            .querySelector(tocInsert ? tocInsert : querySelector)
            .insertAdjacentHTML(
                "afterBegin",
                "<!--<span>Table des matières</span>-->" + toc + ""
            );

        const tocElement = document.querySelector(tocInsert);
        if ("<?php echo $toc_opened_by_default?>" == true) {
            tocElement.classList.remove("lareponse-toc-closed");
        } else {
            tocElement.classList.add("lareponse-toc-closed");
        }

        $(document).ready(function () {
            let mainNavLinks = document.querySelectorAll("#ToC a");
            let scrollable = "<?php echo $is_public_article ?>" ? window : document.querySelector('#id-right');

            scrollable.addEventListener("scroll", (event) => {
                mainNavLinks.forEach((link) => {
                    if (!link.hash) return;

                    let section = document.querySelector(link.hash);
                    let scrollY = "<?php echo $is_public_article ?>" ? window.scrollY : event.target.scrollTop;

                    if (
                        section.offsetTop <= scrollY + window.innerHeight &&
                        section.offsetTop + section.offsetHeight > scrollY
                    ) {
                        section.classList.add("current");
                        link.classList.add("current");
                    } else {
                        link.classList.remove("current");
                        section.classList.remove("current");
                    }
                });
            });
        });
    } else {
        document.querySelector(tocInsert ? tocInsert : querySelector).remove();
    }
}

$(document).ready(function () {
    $('#lareponse-toc-collapse-button').click(function () {
        const toc = $('#ToC');
        if (toc.hasClass("lareponse-toc-closed")) {
            toc.removeClass("lareponse-toc-closed");
        } else {
            toc.addClass("lareponse-toc-closed");
        }
    })
})
