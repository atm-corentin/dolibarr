<?php
/* Copyright (C) 2024 Ravi Trébuchet <ravi@code42.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file        core/substitutions
 * \ingroup     lareponse
 * \brief       This file adds substitutions for mail templates
 */

/**
 * This function adds substitution keys in email templates
 *
 * @param  array           $substitutionarray    The array to complete
 * @param  Translate       $outputlangs          object lang to use for translation
 * @param  CommonObject    $object               The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
 * @param  array           $parameters           Parameters to add
 * @return void
 */
function lareponse_completesubstitutionarray(&$substitutionarray, $outputlangs, $object = null, $parameters = null)
{
	global $conf;

	// Article object
	if (!empty($pbject) && get_class($object) == 'Article' && $conf->lareponse->enabled) {
		$substitutionarray['__LAREPONSE_ARTICLE_TITLE__'] = ($object->title ?? "");
		$substitutionarray['__LAREPONSE_ARTICLE_LINK__'] = dol_buildpath('/lareponse/article_card.php?id=' . $object->id, 3);
	} else {
		$substitutionarray['__LAREPONSE_ARTICLE_TITLE__'] = "";
		$substitutionarray['__LAREPONSE_ARTICLE_LINK__'] = "";
	}
	// List of updated articles
	$substitutionarray['__LAREPONSE_ARTICLE_LIST_LINKS__'] = "";
}
