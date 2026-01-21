<?php
/* Copyright (C) 2017  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) ---Put here your own copyright and developer email---
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
 * \file        class/tag.class.php
 * \ingroup     lareponse
 * \brief       This file is a CRUD class file for tag (Create/Read/Update/Delete)
 */

require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
dol_include_once('/lareponse/lib/lareponse.lib.php');
dol_include_once('/lareponse/lib/lareponse_tag.lib.php');

/**
 * Class for tag
 */
class Tag extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'tag';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'categorie';

	/**
	 * @var int  Does tag support multicompany module ? 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	 */
	public $ismultientitymanaged = 1;

	/**
	 * @var int  Does object support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 0;

	/**
	 * @var string String with name of icon for tag. Must be the part after the 'object_' into object_tag.png
	 */
	public $picto = 'category';

	public $import_key;

	/**
	 * Constants for object's status
	 * Not used for now
	 */
	const STATUS_DRAFT = 0;
	const STATUS_VALIDATED = 1;
	const STATUS_CANCELED = 9;

	/**
	 * Constants for tag types
	 */
	const CATEGORIES_PRODUCT_TAG_TYPE = 0;
	const CATEGORIES_SUPPLIER_TAG_TYPE = 1;
	const CATEGORIES_CUSTOMER_TAG_TYPE = 2;
	const CATEGORIES_MEMBER_TAG_TYPE = 3;
	const CATEGORIES_CONTACT_TAG_TYPE = 4;
	const CATEGORIES_BKACCOUNT_TAG_TYPE = 5;
	const CATEGORIES_PROJECT_TAG_TYPE = 6;
	const CATEGORIES_USER_TAG_TYPE = 7;
	const CATEGORIES_BKLINE_TAG_TYPE = 8;
	const CATEGORIES_TICKET_TAG_TYPE = 12;
	const GESTIONPARC_TAG_TYPE = 42;
	const LAREPONSE_TAG_TYPE = 43;

	public static $MAP_TAG_TYPES = array(
		self::CATEGORIES_PRODUCT_TAG_TYPE => 'product',
		self::CATEGORIES_SUPPLIER_TAG_TYPE => 'supplier',
		self::CATEGORIES_CUSTOMER_TAG_TYPE => 'customer',
		self::CATEGORIES_MEMBER_TAG_TYPE => 'member',
		self::CATEGORIES_CONTACT_TAG_TYPE => 'contact',
		self::CATEGORIES_BKACCOUNT_TAG_TYPE => 'bank_account',
		self::CATEGORIES_PROJECT_TAG_TYPE => 'project',
		self::CATEGORIES_USER_TAG_TYPE => 'user',
		self::CATEGORIES_BKLINE_TAG_TYPE => 'bank_line',
		self::CATEGORIES_TICKET_TAG_TYPE => 'Ticket',
		self::GESTIONPARC_TAG_TYPE => 'GestionParc',
		self::LAREPONSE_TAG_TYPE => 'Lareponse'
	);

	/**
	 *  'type' if the field format ('integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter]]', 'varchar(x)', 'double(24,8)', 'real', 'price', 'text', 'html', 'date', 'datetime', 'timestamp', 'duration', 'mail', 'phone', 'url', 'password')
	 *         Note: Filter can be a string like "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.nature:is:NULL)"
	 *  'label' the translation key.
	 *  'enabled' is a condition when the field must be managed.
	 *  'position' is the sort order of field.
	 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
	 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). 5=Visible on list and view only (not create/not update). Using a negative value means field is not shown by default on list but can be selected for viewing)
	 *  'noteditable' says if field is not editable (1 or 0)
	 *  'default' is a default value for creation (can still be overwrote by the Setup of Default Values if field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created.
	 *  'index' if we want an index in database.
	 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommanded to name the field fk_...).
	 *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
	 *  'isameasure' must be set to 1 if you want to have a total on list for this field. Field type must be summable like integer or double(24,8).
	 *  'css' is the CSS style to use on field. For example: 'maxwidth200'
	 *  'help' is a string visible as a tooltip on field
	 *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
	 *  'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code.
	 *  'arraykeyval' to set list of value if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel")
	 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
	 *
	 *  Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor.
	 */

	// BEGIN MODULEBUILDER PROPERTIES
	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		'rowid' => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => 1, 'position' => 1, 'notnull' => 1, 'visible' => -1, 'noteditable' => '1', 'index' => 1, 'comment' => "Id"),
		'label' => array('type' => 'varchar(180)', 'label' => 'Label', 'enabled' => 1, 'position' => 30, 'notnull' => 1, 'visible' => 1, 'searchall' => 1,),
		'color' => array('type' => 'varchar(8)', 'label' => 'Color', 'enabled' => 1, 'position' => 40, 'notnull' => 1, 'visible' => 1,),
		'description' => array('type' => 'text', 'label' => 'Description', 'enabled' => 1, 'position' => 50, 'notnull' => 0, 'visible' => 1),
		'type' => array('type' => 'tinyint(4)', 'label' => 'Type', 'enabled' => 1, 'position' => 60, 'notnull' => 1, 'visible' => 2, 'default' => 43),
		'entity' => array('type' => 'integer', 'label' => 'Entity', 'enabled' => 1, 'visible' => 0, 'default' => 1, 'notnull' => 1, 'index' => 1, 'position' => 20)
	);
	public $rowid;
	public $label;
	public $color;
	public $type;
	public $description;

	// END MODULEBUILDER PROPERTIES


	// If this object has a subtable with lines

	/**
	 * @var int    Name of subtable line
	 */
	//public $table_element_line = 'lareponse_tagline';

	/**
	 * @var int    Field with ID of parent key if this field has a parent
	 */
	//public $fk_element = 'fk_tag';

	/**
	 * @var int    Name of subtable class that manage subtable lines
	 */
	//public $class_element_line = 'tagline';

	/**
	 * @var array    List of child tables. To test if we can delete object.
	 */
	//protected $childtables=array();

	/**
	 * @var array    List of child tables. To know object to delete on cascade.
	 */
	//protected $childtablesoncascade=array('lareponse_tagdet');

	/**
	 * @var tagLine[]     Array of subtable lines
	 */
	//public $lines = array();

	/**
	 * @var array Mother of table
	 */
	public $motherof = array();

	/**
	 * @var array Categories table in memory
	 */
	public $cats = array();

	/**
	 * Constructor
	 *
	 * @param  DoliDb    $db    Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $conf, $langs;

		$this->db = $db;

		if (empty($conf->global->MAIN_SHOW_TECHNICAL_ID) && isset($this->fields['rowid'])) $this->fields['rowid']['visible'] = 0;
		if (empty($conf->multicompany->enabled) && isset($this->fields['entity'])) $this->fields['entity']['enabled'] = 0;

		// Example to show how to set values of fields definition dynamically
		/*if ($user->rights->lareponse->tag->read) {
			$this->fields['myfield']['visible'] = 1;
			$this->fields['myfield']['noteditable'] = 0;
		}*/

		// Unset fields that are disabled
		foreach ($this->fields as $key => $val) {
			if (isset($val['enabled']) && empty($val['enabled'])) {
				unset($this->fields[$key]);
			}
		}

		// Translate some data of arrayofkeyval
		if (is_object($langs)) {
			foreach ($this->fields as $key => $val) {
				if (isset($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) {
					foreach ($val['arrayofkeyval'] as $key2 => $val2) {
						$this->fields[$key]['arrayofkeyval'][$key2] = $langs->trans($val2);
					}
				}
			}
		}
	}

	/**
	 * Get all tag in database
	 *
	 * @return array    array of tag [{label, color}, ...]
	 */
	public function getAllTag()
	{
		global $conf, $db;

		$tag = array("id" => '', "label" => '', "color" => '');
		$list = array();
		$sql = 'SELECT ';
		$sql .= 'c.rowid, c.label, c.color, c.type FROM ' . MAIN_DB_PREFIX . 'categorie AS c ';
		$sql .= 'WHERE c.entity IN (' . getEntityLareponse($this->table_element) . ')';
		// Tag type depends on lareponse configuration
		$sql .= ' AND c.type IN ' . getLareponseTagIdList(self::$MAP_TAG_TYPES, "sql");

		$resql = $this->db->query($sql);

		// We check const MAP TAG TYPES to avoid external tags in tag list
		while ($results = $db->fetch_object($resql)) {
			if (!empty($results->type) && !empty(self::$MAP_TAG_TYPES[$results->type])) {
				$tag['id'] = $results->rowid;
				$tag['label'] = $results->label;
				$tag['color'] = $results->color;
				$tag['type'] = $results->type;
				$list[] = $tag;
			}
		}
		return $list;
	}

	/**
	 * Link Article - Tag into db via article_tag table
	 *
	 * @param  integer    $id     Article id
	 * @param  array      $tag    array of tag id
	 * @return int          <0 if KO, Id of created object if OK
	 */
	public function linkArticleTag($id, $tag)
	{
		global $conf;
		$sql = 'INSERT ';
		$sql .= 'INTO ' . MAIN_DB_PREFIX . 'lareponse_article_tag (fk_article, fk_tag, entity) VALUES ';

		foreach ($tag as $tagid) {
			$req = $sql . '(' . $id . ', ' . $tagid . ', ' . $conf->entity . ')';
			$resql = $this->db->query($req);
			if ($resql == false) return -1;
		}
		return 1;
	}

	/**
	 * Get all tag linked to an Article
	 *
	 * @param  integer    $id    Article id
	 * @return array
	 */
	public function getArticleTag($id)
	{
		global $db;
		$tag = array("id" => '', "label" => '', "color" => '', "type" => '');
		$list = array();
		$sql = 'SELECT ';
		// Get tags elements
		$sql .= 'c.rowid, c.label, c.color, c.type FROM ' . MAIN_DB_PREFIX . 'categorie AS c ';
		// Get article elements
		$sql .= 'INNER JOIN ' . MAIN_DB_PREFIX . 'lareponse_article_tag AS t ';
		$sql .= 'ON t.fk_article=' . $id ;
		// Tag type depends on lareponse configuration
		$sql .= ' WHERE t.fk_tag = c.rowid';
		$sql .= ' AND c.type IN ' . getLareponseTagIdList(self::$MAP_TAG_TYPES, "sql");
		$resql = $this->db->query($sql);

		if ($resql) {
			// We check const MAP TAG TYPES to avoid external tags in tag list
			while ($results = $db->fetch_object($resql)) {
				if (!empty($results->type) && !empty(self::$MAP_TAG_TYPES[$results->type])) {
					$tag['id'] = $results->rowid;
					$tag['label'] = $results->label;
					$tag['color'] = $results->color;
					$tag['type'] = $results->type;
					$list[] = $tag;
				}
			}
		}
		return $list;
	}

	/**
	 *
	 * Get all tags in database
	 *
	 * @return array    array of tag [{label, color}, ...]
	 */
	public function getAllTags()
	{
		global $db, $langs, $conf;
		$tag = array();
		$list = array();
		// Get tags elements
		$sql = 'SELECT c.rowid, c.label, c.color, c.type FROM ' . MAIN_DB_PREFIX . 'categorie AS c ';
		// Tag type depends on lareponse configuration
		$sql .= ' WHERE c.type IN ' . getLareponseTagIdList(self::$MAP_TAG_TYPES, "sql");
		if (isset($conf->multicompany->enabled) && $conf->multicompany->enabled) $sql .= ' AND c.entity =' . $conf->entity;
		$sql .= " ORDER BY c.type ASC";

		$resql = $db->query($sql);
		if ($resql) {
			while ($results = $db->fetch_object($resql)) {
				// We check const MAP TAG TYPES to avoid external tags in selector
				if (isset($results->type) && !empty(self::$MAP_TAG_TYPES[$results->type])) {
					$tag[$results->rowid]['id'] = $results->rowid;
					$tag[$results->rowid]['label'] = $results->label;
					$tag[$results->rowid]['color'] = $results->color;
					$tag[$results->rowid]['type'] = $langs->trans(self::$MAP_TAG_TYPES[$results->type]);
					// [#242] Fixed order of tags
					switch ($results->type) {
						case 0:
							$tag[$results->rowid]['sort'] = 3;
							break; // Product
						case 1:
							$tag[$results->rowid]['sort'] = 5;
							break; // Supplier
						case 2:
							$tag[$results->rowid]['sort'] = 4;
							break; // Society
						case 42:
							$tag[$results->rowid]['sort'] = 2;
							break; // GestionParc
						case 43:
							$tag[$results->rowid]['sort'] = 1;
							break; // LaReponse
						default:
							$tag[$results->rowid]['sort'] = $results->type + 5;
							break; // By default, let old sort after the ones that we want
					}
				}
			}
			$this->load_motherof();

			foreach ($tag as $key => $val) {
				// $key = id of category
				$tag[$key]['fulllabel'] = ucfirst($val['label']);
				$i = 0;
				$cursorCateg = $key;
				while ($i < (count($tag) + 5) && !empty($this->motherof[$cursorCateg])) { // while there is tag (+5 to be sure to pass on all tag) and category parents
					$tag[$key]['fulllabel'] = ucfirst($tag[$this->motherof[$cursorCateg]]['label'] . ' >> ' . $tag[$key]['fulllabel']);
					$cursorCateg = $this->motherof[$cursorCateg];
					$i++;
				}
			}

			$list = dol_sort_array($tag, 'fulllabel', 'asc', true);// sort by full Label
			$list = dol_sort_array($list, 'sort'); //sort by "familly/type/module"
		}
		return $list;
	}

	/**
	 * Delete all tags link to an article
	 *
	 * @param  integer    $id    Article id
	 * @return int              <0 if KO, Id of created object if OK
	 */
	public function clearArticleTag($id)
	{
		$sql = 'DELETE ';
		$sql .= 'FROM ' . MAIN_DB_PREFIX . 'lareponse_article_tag ';
		$sql .= 'WHERE ' . MAIN_DB_PREFIX . 'lareponse_article_tag.fk_article=' . $id;

		$resql = $this->db->query($sql);
		if ($resql == false) return -1;
		return 1;
	}

	/**
	 * Update tags linked to an article
	 *
	 * @param  integer    $id     id Article
	 * @param  array      $tag    array of id for new tag to link
	 * @return int           <0 if KO, Id of created object if OK
	 */
	public function updateArticleTag($id, $tag)
	{
		$error = $this->clearArticleTag($id);
		if ($error < 0) return -1;
		$error = $this->linkArticleTag($id, $tag);
		if ($error < 0) return -1;
		return 1;
	}

	/**
	 * Print select box of tag
	 *
	 * @param  string    $htmlname             name of selector
	 * @param  array     $preSelectedTagIds    Array of tags
	 * @param  string    $placeholder          placeholder in select
	 * @param  int       $id                   Article id
	 * @param  int       $disabled             0 = active selector / 1 = disable selector
	 * @return void
	 */
	public function printSelectTag($htmlname, $preSelectedTagIds = array(), $placeholder = '', $id = -1, $disabled = 0)
	{
		global $user, $langs;

		$list_tag = $this->getAllTags();
		$article_tag = $this->getArticleTag($id);
		$tag_id = array();

		foreach ($article_tag as $tag) {
			$tag_id[] = $tag['id'];
		}

		print '<td>';
		print '<select data-placeholder="' . $placeholder . '" multiple="multiple" class="chosen-select" name="' . $htmlname . '[]" ' . ($disabled ? 'disabled' : '') . ' id="' . $htmlname . '">';
		foreach ($list_tag as $tag) {
			//$colorText = colorIsLight("#".$tag['color'] ? :;
			$id = intval($tag['id']);
			$obj = new Tag($this->db);
			$obj->fetch($id);
			$ways = $obj->print_all_ways(' &gt;&gt; ', dol_buildpath('/lareponse/article_list.php', 1));

			$tagColor = ($tag['color'] == 'ffffff' ? '#050505' : $tag['color']);
			$tagColor = (strpos($tagColor, '#') === false) ? '#' . $tagColor : $tagColor;
			$tagIds = array_merge($tag_id, $preSelectedTagIds);
			if (in_array($tag['id'], $tagIds)) print '<option selected data-color="' . $tagColor . '" value="' . $tag['id'] . '" style="color: ' . $tagColor . '">' . $ways[0] . '( ' . $tag['type'] . ')</option>';
			else print '<option data-color="' . $tagColor . '" value="' . $tag['id'] . '" style="color: ' . $tagColor . ';">' . $ways[0] . ' (' . $tag['type'] . ')</option>';
		}

		print '</select>';
		if (isset($user->rights->lareponse->tag->write) && $user->rights->lareponse->tag->write)
			print '<a class="btn-lareponse-tag" id="new-tag"><i class="fa fa-plus" aria-hidden="true"></i>' . $langs->trans('CreateTag') . '</a></td>';
		print '</tr>';

		print '<script>
                $(".chosen-select").chosen({ no_results_text: "Oops, pas de tag trouvé!" });

                $(".chosen-select option").each(function() { $(this).html($(this).text() + ` <span class="fas fa-square"></span>`); });
                $(".chosen-select").trigger("chosen:updated");

                </script>';

		print '<script src="' . dol_buildpath('/h2g2/js/sweetalert2.all.min.js', 1) . '"></script>';
		print '<script>
                $(\'#new-tag\').click(async() => {
                    const { value: formValues } = await Swal.fire({
                    title: \'' . $langs->trans('NewTag') . '\',
                    html:
                      \'<input id="tag-label" class="swal2-input">\' +
                      \'<input type="color" id="tag-color" class="swal2-input">\',
                    focusConfirm: false,
                    preConfirm: () => {
                      return [
                        document.getElementById(\'tag-label\').value,
                        document.getElementById(\'tag-color\').value
                      ]
                    }
                  });

                  if (formValues) {
                      $.ajax({
                        url: \'' . dol_buildpath('/lareponse/ajax/create_tag.php', 1) . '\',
                        type: \'POST\',
                        data: {
                            \'token\': \'' . newToken() . '\',
                            \'action\': \'create\',
                            \'label\': formValues[0],
                            \'color\': formValues[1],
                            \'article_id\': ' . $id . '
                        },
                        success: function(res, statut){
                            res = JSON.parse(res);
                            if (res[\'success\'] === true) {
                                $(".chosen-select").append(`<option selected style="color: ` + res[`color`] + `" value="`+ res[`id`] + `">` + res[`label`] + ` (Lareponse)</option>`);
                                $(".chosen-select option").each(function() { $(this).html($(this).text() + ` <span class="fas fa-square"></span>`); });
                                $(\'.chosen-select\').trigger("chosen:updated");
                                Swal.fire({
                                    icon: \'success\',
                                    title: \'' . $langs->trans('TagCreate') . '\',
                                });
                            } else {
                                Swal.fire({
                                    icon: \'error\',
                                    title: \'' . $langs->trans('ErrorTagCreate') . '\',
                                });
                            }
                            updateSelectedTagsColor();
                        },
                      });
                  }

                });
// Function needed to update the tags color
function updateSelectedTagsColor() {
    var chosen_choices_array = document.getElementsByClassName(\'chosen-choices\')[0];
    var search_choices = chosen_choices_array.getElementsByClassName(\'search-choice\');
    for (var i = 0; i < search_choices.length; i++) {
        var elementId = search_choices[i].getElementsByClassName(\'search-choice-close\')[0].getAttribute(\'data-option-array-index\');
        var elementColor = document.getElementsByClassName(\'chosen-select\')[0][elementId].style.color;
        search_choices[i].style.color = elementColor;
    }
}
$(document).ready(function () {
    updateSelectedTagsColor();
    document.getElementById("' . $htmlname . '_chosen").addEventListener("click", updateSelectedTagsColor);
    ' . ($disabled ? '$(`.chosen-select`).prop(`disabled`, true).trigger("chosen:updated");' : '') . '
});
</script>';
	}

	/**
	 * Create object into database
	 *
	 * @param  User    $user         User that creates
	 * @param  bool    $notrigger    false=launch triggers after, true=disable triggers
	 * @return int             negative if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = false)
	{
		return $this->createCommon($user, $notrigger);
	}

	/**
	 * Clone an object into another one
	 *
	 * @param  User    $user      User that creates
	 * @param  int     $fromid    Id of object to clone
	 * @return    mixed                New object created, <0 if KO
	 */
	public function createFromClone(User $user, $fromid)
	{
		global $langs, $extrafields;
		$error = 0;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$object = new self($this->db);

		$this->db->begin();

		// Load source object
		$result = $object->fetchCommon($fromid);
		if ($result > 0 && !empty($object->table_element_line)) $object->fetchLines();

		// get lines so they will be clone
		//foreach($this->lines as $line)
		//	$line->fetch_optionals();

		// Reset some properties
		unset($object->id);
		unset($object->fk_user_creat);
		unset($object->import_key);


		// Clear fields
		$object->ref = empty($this->fields['ref']['default']) ? "copy_of_" . $object->ref : $this->fields['ref']['default'];
		$object->label = empty($this->fields['label']['default']) ? $langs->trans("CopyOf") . " " . $object->label : $this->fields['label']['default'];
		$object->status = self::STATUS_DRAFT;
		// ...
		// Clear extrafields that are unique
		if (is_array($object->array_options) && count($object->array_options) > 0) {
			$extrafields->fetch_name_optionals_label($this->table_element);
			foreach ($object->array_options as $key => $option) {
				$shortkey = preg_replace('/options_/', '', $key);
				if (!empty($extrafields->attributes[$this->element]['unique'][$shortkey])) {
					unset($object->array_options[$key]);
				}
			}
		}

		// Create clone
		$object->context['createfromclone'] = 'createfromclone';
		$result = $object->createCommon($user);
		if ($result < 0) {
			$error++;
			$this->error = $object->error;
			$this->errors = $object->errors;
		}

		if (!$error) {
			// copy internal contacts
			if ($this->copy_linked_contact($object, 'internal') < 0) {
				$error++;
			}
		}

		if (!$error) {
			// copy external contacts if same company
			if (property_exists($this, 'socid') && $this->socid == $object->socid) {
				if ($this->copy_linked_contact($object, 'external') < 0)
					$error++;
			}
		}

		unset($object->context['createfromclone']);

		// End
		if (!$error) {
			$this->db->commit();
			return $object;
		} else {
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param  int       $id     Id object
	 * @param  string    $ref    Ref
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = null)
	{
		$result = $this->fetchCommon($id, $ref);
		if ($result > 0 && !empty($this->table_element_line)) $this->fetchLines();
		return $result;
	}

	/**
	 * Load object lines in memory from the database
	 *
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetchLines()
	{
		$this->lines = array();

		$result = $this->fetchLinesCommon();
		return $result;
	}


	/**
	 * Load list of objects in memory from the database.
	 *
	 * @param  string    $sortorder     Sort Order
	 * @param  string    $sortfield     Sort field
	 * @param  int       $limit         limit
	 * @param  int       $offset        Offset
	 * @param  array     $filter        Filter array. Example array('field'=>'valueforlike', 'customurl'=>...)
	 * @param  string    $filtermode    Filter mode (AND or OR)
	 * @return array|int                 int <0 if KO, array of pages if OK
	 */
	public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND')
	{
		global $conf;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$records = array();

		$sql = 'SELECT ';
		$sql .= $this->getFieldList();
		$sql .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element . ' as t';
		if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) $sql .= ' WHERE t.entity IN (' . getEntityLareponse($this->table_element) . ')';
		else $sql .= ' WHERE 1 = 1';
		// Manage filter
		$sqlwhere = array();
		if (count($filter) > 0) {
			foreach ($filter as $key => $value) {
				if ($key == 't.rowid') {
					$sqlwhere[] = $key . '=' . $value;
				} elseif (strpos($key, 'date') !== false) {
					$sqlwhere[] = $key . ' = \'' . $this->db->idate($value) . '\'';
				} elseif ($key == 'customsql') {
					$sqlwhere[] = $value;
				} else {
					$sqlwhere[] = $key . ' LIKE \'%' . $this->db->escape($value) . '%\'';
				}
			}
		}
		if (count($sqlwhere) > 0) {
			$sql .= ' AND (' . implode(' ' . $filtermode . ' ', $sqlwhere) . ')';
		}

		if (!empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}
		if (!empty($limit)) {
			$sql .= ' ' . $this->db->plimit($limit, $offset);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < min($limit, $num)) {
				$obj = $this->db->fetch_object($resql);

				$record = new self($this->db);
				$record->setVarsFromFetchObj($obj);

				$records[$record->id] = $record;

				$i++;
			}
			$this->db->free($resql);

			return $records;
		} else {
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);

			return -1;
		}
	}

	/**
	 * Update object into database
	 *
	 * @param  User    $user         User that modifies
	 * @param  bool    $notrigger    false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = false)
	{
		return $this->updateCommon($user, $notrigger);
	}

	/**
	 * Delete object in database
	 *
	 * @param  User    $user         User that deletes
	 * @param  bool    $notrigger    false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = false)
	{
		$res = $this->deleteCommon($user, $notrigger);
		if ($res > 0) {
			$sql = "DELETE FROM " . MAIN_DB_PREFIX . "categorie where fk_parent =" . $this->id;
			$this->db->query($sql);
		}
		//return $this->deleteCommon($user, $notrigger, 1);
	}

	/**
	 *  Delete a line of object in database
	 *
	 * @param  User    $user         User that delete
	 * @param  int     $idline       Id of line to delete
	 * @param  bool    $notrigger    false=launch triggers after, true=disable triggers
	 * @return int                >0 if OK, <0 if KO
	 */
	public function deleteLine(User $user, $idline, $notrigger = false)
	{
		if ($this->status < 0) {
			$this->error = 'ErrorDeleteLineNotAllowedByObjectStatus';
			return -2;
		}

		return $this->deleteLineCommon($user, $idline, $notrigger);
	}


	/**
	 *    Validate object
	 *
	 * @param  User    $user         User making status change
	 * @param  int     $notrigger    1=Does not execute triggers, 0= execute triggers
	 * @return    int                        <=0 if OK, 0=Nothing done, >0 if KO
	 */
	public function validate($user, $notrigger = 0)
	{
		global $conf, $langs;

		require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

		$error = 0;

		// Protection
		if ($this->status == self::STATUS_VALIDATED) {
			dol_syslog(get_class($this) . "::validate action abandonned: already validated", LOG_WARNING);
			return 0;
		}

		/*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->tag->create))
		 || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->tag->tag_advance->validate))))
		 {
		 $this->error='NotEnoughPermissions';
		 dol_syslog(get_class($this)."::valid ".$this->error, LOG_ERR);
		 return -1;
		 }*/

		$now = dol_now();

		$this->db->begin();

		// Define new ref
		if (!$error && (preg_match('/^[\(]?PROV/i', $this->ref) || empty($this->ref))) { // empty should not happened, but when it occurs, the test save life
			$num = $this->getNextNumRef();
		} else {
			$num = $this->ref;
		}
		$this->newref = $num;

		// Validate
		$sql = "UPDATE " . MAIN_DB_PREFIX . $this->table_element;
		$sql .= " SET ref = '" . $this->db->escape($num) . "',";
		$sql .= " status = " . self::STATUS_VALIDATED . ",";
		$sql .= " date_validation = '" . $this->db->idate($now) . "',";
		$sql .= " fk_user_valid = " . $user->id;
		$sql .= " WHERE rowid = " . $this->id;

		dol_syslog(get_class($this) . "::validate()", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_print_error($this->db);
			$this->error = $this->db->lasterror();
			$error++;
		}

		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('TAG_VALIDATE', $user);
			if ($result < 0) $error++;
			// End call triggers
		}

		if (!$error) {
			$this->oldref = $this->ref;

			// Rename directory if dir was a temporary ref
			if (preg_match('/^[\(]?PROV/i', $this->ref)) {
				// Now we rename also files into index
				$sql = 'UPDATE ' . MAIN_DB_PREFIX . "ecm_files set filename = CONCAT('" . $this->db->escape($this->newref) . "', SUBSTR(filename, " . (strlen($this->ref) + 1) . ")), filepath = 'tag/" . $this->db->escape($this->newref) . "'";
				$sql .= " WHERE filename LIKE '" . $this->db->escape($this->ref) . "%' AND filepath = 'tag/" . $this->db->escape($this->ref) . "' and entity = " . $conf->entity;
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++;
					$this->error = $this->db->lasterror();
				}

				// We rename directory ($this->ref = old ref, $num = new ref) in order not to lose the attachments
				$oldref = dol_sanitizeFileName($this->ref);
				$newref = dol_sanitizeFileName($num);
				$dirsource = $conf->lareponse->dir_output . '/tag/' . $oldref;
				$dirdest = $conf->lareponse->dir_output . '/tag/' . $newref;
				if (!$error && file_exists($dirsource)) {
					dol_syslog(get_class($this) . "::validate() rename dir " . $dirsource . " into " . $dirdest);

					if (@rename($dirsource, $dirdest)) {
						dol_syslog("Rename ok");
						// Rename docs starting with $oldref with $newref
						$listoffiles = dol_dir_list($conf->lareponse->dir_output . '/tag/' . $newref, 'files', 1, '^' . preg_quote($oldref, '/'));
						foreach ($listoffiles as $fileentry) {
							$dirsource = $fileentry['name'];
							$dirdest = preg_replace('/^' . preg_quote($oldref, '/') . '/', $newref, $dirsource);
							$dirsource = $fileentry['path'] . '/' . $dirsource;
							$dirdest = $fileentry['path'] . '/' . $dirdest;
							@rename($dirsource, $dirdest);
						}
					}
				}
			}
		}

		// Set new ref and current status
		if (!$error) {
			$this->ref = $num;
			$this->status = self::STATUS_VALIDATED;
		}

		if (!$error) {
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *    Set draft status
	 *
	 * @param  User    $user         Object user that modify
	 * @param  int     $notrigger    1=Does not execute triggers, 0=Execute triggers
	 * @return    int                        <0 if KO, >0 if OK
	 */
	public function setDraft($user, $notrigger = 0)
	{
		// Protection
		if ($this->status <= self::STATUS_DRAFT) {
			return 0;
		}

		/*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->lareponse->write))
		 || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->lareponse->lareponse_advance->validate))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_DRAFT, $notrigger, 'TAG_UNVALIDATE');
	}

	/**
	 *    Set cancel status
	 *
	 * @param  User    $user         Object user that modify
	 * @param  int     $notrigger    1=Does not execute triggers, 0=Execute triggers
	 * @return    int                        <0 if KO, 0=Nothing done, >0 if OK
	 */
	public function cancel($user, $notrigger = 0)
	{
		// Protection
		if ($this->status != self::STATUS_VALIDATED) {
			return 0;
		}

		/*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->lareponse->write))
		 || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->lareponse->lareponse_advance->validate))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_CANCELED, $notrigger, 'TAG_CLOSE');
	}

	/**
	 *    Set back to validated status
	 *
	 * @param  User    $user         Object user that modify
	 * @param  int     $notrigger    1=Does not execute triggers, 0=Execute triggers
	 * @return    int                        <0 if KO, 0=Nothing done, >0 if OK
	 */
	public function reopen($user, $notrigger = 0)
	{
		// Protection
		if ($this->status != self::STATUS_CANCELED) {
			return 0;
		}

		/*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->lareponse->write))
		 || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->lareponse->lareponse_advance->validate))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_VALIDATED, $notrigger, 'TAG_REOPEN');
	}

	/**
	 *  Return a link to the object card (with optionaly the picto)
	 *
	 * @param  int       $withpicto                Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
	 * @param  string    $option                   On what the link point to ('nolink', ...)
	 * @param  int       $notooltip                1=Disable tooltip
	 * @param  string    $morecss                  Add more css on link
	 * @param  int       $save_lastsearch_value    -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 * @return    string                              String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $morecss = '', $save_lastsearch_value = -1)
	{
		global $conf, $langs, $hookmanager;

		if (!empty($conf->dol_no_mouse_hover)) $notooltip = 1; // Force disable tooltips

		$result = '';

		$label = '<u>' . $langs->trans("tag") . '</u>';
		$label .= '<br>';
		$label .= '<b>' . $langs->trans('Ref') . ':</b> ' . $this->ref;
		$label .= '<br>';
		$label .= '<b>' . $langs->trans('Label') . ':</b> ' . $this->label;
		if (isset($this->status)) {
			$label .= '<br><b>' . $langs->trans("Status") . ":</b> " . $this->getLibStatut(5);
		}

		$url = dol_buildpath('/lareponse/article_list.php', 1) . '?search_category_tag_list[]=' . $this->id;

		$sql = "SELECT fk_parent FROM " . MAIN_DB_PREFIX . "categorie";
		$sql .= " WHERE rowid = " . $this->id;
		$res = $this->db->query($sql);
		if ($res) {
			$obj = $this->db->fetch_object($res);
			if ($obj->fk_parent > 0) $url .= '&search_category_tag_list[]=' . $obj->fk_parent . '&search_category_tag_operator=1';
		}
		$this->db->free($res);

		if ($option != 'nolink') {
			// Add param to save lastsearch_values or not
			$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
			if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) $add_save_lastsearch_values = 1;
			if ($add_save_lastsearch_values) $url .= '&save_lastsearch_values=1';
		}

		$linkclose = '';
		if (empty($notooltip)) {
			if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
				$label = $langs->trans("Showtag");
				$linkclose .= ' alt="' . dol_escape_htmltag($label, 1) . '"';
			}
			$linkclose .= ' title="' . dol_escape_htmltag($label, 1) . '"';
			$linkclose .= ' class="classfortooltip' . ($morecss ? ' ' . $morecss : '') . '"';
		} else $linkclose = ($morecss ? ' class="' . $morecss . '"' : '');

		// Color may switch if the background color is too brigth or too dark
		$forced_color = 'categtextwhite';
		if ($this->color) {
			if (colorIsLight($this->color)) $forced_color = 'categtextblack';
		}
		$linkstart = '<a href="' . $url . '" class="' . $forced_color . '"';
		$linkstart .= $linkclose . '>';
		$linkend = '</a>';

		$result .= $linkstart;
		if ($withpicto) $result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="' . (($withpicto != 2) ? 'paddingright ' : '') . 'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
		if ($withpicto != 2) $result .= $this->label;
		$result .= $linkend;
		//if ($withpicto != 2) $result.=(($addlabel && $this->label) ? $sep . dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');

		global $action, $hookmanager;
		$hookmanager->initHooks(array('tagdao'));
		$parameters = array('id' => $this->id, 'getnomurl' => $result);
		$reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) $result = $hookmanager->resPrint;
		else $result .= $hookmanager->resPrint;

		return $result;
	}

	/**
	 *  Return label of the status
	 *
	 * @param  int    $mode    0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 * @return    string                   Label of status
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->status, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps

	/**
	 *  Return the status
	 *
	 * @param  int    $status    Id status
	 * @param  int    $mode      0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 * @return string                   Label of status
	 */
	public function LibStatut($status, $mode = 0)
	{
		// phpcs:enable
		if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
			global $langs;
			//$langs->load("lareponse");
			$this->labelStatus[self::STATUS_DRAFT] = $langs->trans('Draft');
			$this->labelStatus[self::STATUS_VALIDATED] = $langs->trans('Enabled');
			$this->labelStatus[self::STATUS_CANCELED] = $langs->trans('Disabled');
			$this->labelStatusShort[self::STATUS_DRAFT] = $langs->trans('Draft');
			$this->labelStatusShort[self::STATUS_VALIDATED] = $langs->trans('Enabled');
			$this->labelStatusShort[self::STATUS_CANCELED] = $langs->trans('Disabled');
		}

		$statusType = 'status' . $status;
		//if ($status == self::STATUS_VALIDATED) $statusType = 'status1';
		if ($status == self::STATUS_CANCELED) $statusType = 'status6';

		if ((float) DOL_VERSION >= 11 && isset($this->labelStatus[$status]) && isset($this->labelStatusShort[$status]))
			return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
		else return '';
	}

	/**
	 *    Load the info information in the object
	 *
	 * @param  int    $id    Id of object
	 * @return    void
	 */
	public function info($id)
	{
		$sql = 'SELECT rowid, date_creation as datec, tms as datem,';
		$sql .= ' fk_user_creat, fk_user_modif';
		$sql .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element . ' as t';
		$sql .= ' WHERE t.rowid = ' . $id;
		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);
				$this->id = $obj->rowid;
				if ($obj->fk_user_author) {
					$cuser = new User($this->db);
					$cuser->fetch($obj->fk_user_author);
					$this->user_creation = $cuser;
				}

				if ($obj->fk_user_valid) {
					$vuser = new User($this->db);
					$vuser->fetch($obj->fk_user_valid);
					$this->user_validation = $vuser;
				}

				if ($obj->fk_user_cloture) {
					$cluser = new User($this->db);
					$cluser->fetch($obj->fk_user_cloture);
					$this->user_cloture = $cluser;
				}

				$this->date_creation = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->datem);
				$this->date_validation = $this->db->jdate($obj->datev);
			}

			$this->db->free($result);
		} else {
			dol_print_error($this->db);
		}
	}

	/**
	 * Initialise object with example values
	 * Id must be 0 if object instance is a specimen
	 *
	 * @return void
	 */
	public function initAsSpecimen()
	{
		$this->initAsSpecimenCommon();
	}

	/**
	 *    Create an array of lines
	 *
	 * @return array|int        array of lines if OK, <0 if KO
	 */
	public function getLinesArray()
	{
		$this->lines = array();

		$objectline = new tagLine($this->db);
		$result = $objectline->fetchAll('ASC', 'position', 0, 0, array('customsql' => 'fk_tag = ' . $this->id));

		if (is_numeric($result)) {
			$this->error = $this->error;
			$this->errors = $this->errors;
			return $result;
		} else {
			$this->lines = $result;
			return $this->lines;
		}
	}

	/**
	 *  Returns the reference to the following non used object depending on the active numbering module.
	 *
	 * @return string            Object free reference
	 */
	public function getNextNumRef()
	{
		global $langs, $conf;
		$langs->load("lareponse@tag");

		if (empty($conf->global->LAREPONSE_TAG_ADDON)) {
			$conf->global->LAREPONSE_TAG_ADDON = 'mod_mymobject_standard';
		}

		if (!empty($conf->global->LAREPONSE_TAG_ADDON)) {
			$mybool = false;

			$file = $conf->global->LAREPONSE_TAG_ADDON . ".php";
			$classname = $conf->global->LAREPONSE_TAG_ADDON;

			// Include file with class
			$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
			foreach ($dirmodels as $reldir) {
				$dir = dol_buildpath($reldir . "core/modules/lareponse/");

				// Load file with numbering class (if found)
				$mybool |= @include_once $dir . $file;
			}

			if ($mybool === false) {
				dol_print_error('', "Failed to include file " . $file);
				return '';
			}

			$obj = new $classname();
			$numref = $obj->getNextValue($this);

			if ($numref != "") {
				return $numref;
			} else {
				$this->error = $obj->error;
				//dol_print_error($this->db,get_class($this)."::getNextNumRef ".$obj->error);
				return "";
			}
		} else {
			print $langs->trans("Error") . " " . $langs->trans("Error_LAREPONSE_TAG_ADDON_NotDefined");
			return "";
		}
	}

	/**
	 *  Create a document onto disk according to template module.
	 *
	 * @param  string        $modele         Force template to use ('' to not force)
	 * @param  Translate     $outputlangs    objet lang a utiliser pour traduction
	 * @param  int           $hidedetails    Hide details of lines
	 * @param  int           $hidedesc       Hide description
	 * @param  int           $hideref        Hide ref
	 * @param  null|array    $moreparams     Array to provide more information
	 * @return     int                        0 if KO, 1 if OK
	 */
	public function generateDocument($modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0, $moreparams = null)
	{
		global $conf, $langs;

		$langs->load("lareponse@lareponse");

		if (!dol_strlen($modele)) {
			$modele = 'standard';

			if ($this->modelpdf) {
				$modele = $this->modelpdf;
			} elseif (!empty($conf->global->TAG_ADDON_PDF)) {
				$modele = $conf->global->TAG_ADDON_PDF;
			}
		}

		$modelpath = "core/modules/lareponse/doc/";

		return $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
	}

	/**
	 * Action executed by scheduler
	 * CAN BE A CRON TASK. In such a case, parameters come from the schedule job setup field 'Parameters'
	 *
	 * @return    int            0 if OK, <>0 if KO (this function is used also by cron so only 0 is OK)
	 */
	public function doScheduledJob()
	{
		global $conf, $langs;

		//$conf->global->SYSLOG_FILE = 'DOL_DATA_ROOT/dolibarr_mydedicatedlofile.log';

		$error = 0;
		$this->output = '';
		$this->error = '';

		dol_syslog(__METHOD__, LOG_DEBUG);

		$now = dol_now();

		$this->db->begin();

		// ...

		$this->db->commit();

		return $error;
	}

	/**
	 * Rebuilding the category tree as an array
	 * Return an array of table('id','id_mere',...) trie selon arbre et avec:
	 *                id = id de la categorie
	 *                id_mere = id de la categorie mere
	 *                id_children = tableau des id enfant
	 *                label = nom de la categorie
	 *                fulllabel = nom avec chemin complet de la categorie
	 *                fullpath = chemin complet compose des id
	 *
	 * @param  string              $type                        Type of categories ('customer', 'supplier', 'contact', 'product', 'member', ...)
	 * @param  int|string|array    $markafterid                 Keep only or removed all categories including the leaf $markafterid in category tree (exclude) or Keep only of category is inside the leaf starting with this id.
	 *                                                          $markafterid can be an :
	 *                                                          - int (id of category)
	 *                                                          - string (categories ids separated by comma)
	 *                                                          - array (list of categories ids)
	 * @param  int                 $include                     [=0] Removed or 1=Keep only
	 * @return  array|int               Array of categories. this->cats and this->motherof are set, -1 on error
	 */
	// @codingStandardsIgnoreStart
	public function getFullArbo($type, $markafterid = 0, $include = 0)
	{
		// @codingStandardsIgnoreEnd
		// phpcs:enable
		global $conf, $langs;

		if (is_string($markafterid)) {
			$markafterid = explode(',', $markafterid);
		} elseif (is_numeric($markafterid)) {
			if ($markafterid > 0) {
				$markafterid = array($markafterid);
			} else {
				$markafterid = array();
			}
		} elseif (!is_array($markafterid)) {
			$markafterid = array();
		}


		$this->cats = array();

		// Init this->motherof that is array(id_son=>id_parent, ...)
		$this->load_motherof();

		$current_lang = $langs->getDefaultLang();

		// Init $this->cats array
		$sql = "SELECT DISTINCT c.rowid, c.label, c.ref_ext, c.description, c.color, c.fk_parent, c.visible"; // Distinct reduce pb with old tables with duplicates
		if (!empty($conf->global->MAIN_MULTILANGS)) $sql .= ", t.label as label_trans, t.description as description_trans";
		$sql .= " FROM " . MAIN_DB_PREFIX . "categorie as c";
		if (!empty($conf->global->MAIN_MULTILANGS)) $sql .= " LEFT  JOIN " . MAIN_DB_PREFIX . "categorie_lang as t ON t.fk_category=c.rowid AND t.lang='" . $this->db->escape($current_lang) . "'";
		$sql .= " WHERE c.entity IN (" . getEntity('category') . ")";
		$sql .= " AND c.type = " . $type;

		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$this->cats[$obj->rowid]['rowid'] = $obj->rowid;
				$this->cats[$obj->rowid]['id'] = $obj->rowid;
				$this->cats[$obj->rowid]['fk_parent'] = $obj->fk_parent;
				$this->cats[$obj->rowid]['label'] = !empty($obj->label_trans) ? $obj->label_trans : $obj->label;
				$this->cats[$obj->rowid]['description'] = !empty($obj->description_trans) ? $obj->description_trans : $obj->description;
				$this->cats[$obj->rowid]['color'] = $obj->color;
				$this->cats[$obj->rowid]['visible'] = $obj->visible;
				$this->cats[$obj->rowid]['ref_ext'] = $obj->ref_ext;
			}
		} else {
			dol_print_error($this->db);
			return -1;
		}

		foreach ($this->cats as $key => $val) {
			//print 'key='.$key.'<br>'."\n";
			$this->build_path_from_id_categ($key, 0); // Process a branch from the root category key (this category has no parent)
		}

		if (count($markafterid) > 0) {
			$keyfiltercatid = '(' . implode('|', $markafterid) . ')';

			//print "Look to discard category ".$markafterid."\n";
			$keyfilter1 = '^' . $keyfiltercatid . '$';
			$keyfilter2 = '_' . $keyfiltercatid . '$';
			$keyfilter3 = '^' . $keyfiltercatid . '_';
			$keyfilter4 = '_' . $keyfiltercatid . '_';
			foreach ($this->cats as $key => $val) {
				$test = (preg_match('/' . $keyfilter1 . '/', $val['fullpath']) || preg_match('/' . $keyfilter2 . '/', $val['fullpath'])
					|| preg_match('/' . $keyfilter3 . '/', $val['fullpath']) || preg_match('/' . $keyfilter4 . '/', $val['fullpath']));

				if (($test && !$include) || (!$test && $include)) {
					unset($this->cats[$key]);
				}
			}
		}

		$this->cats = dol_sort_array($this->cats, 'fulllabel', 'asc', true, false);

		return $this->cats;
	}


	/**
	 *    Load the array this->motherof that is array(id_son=>id_parent, ...)
	 *
	 * @return        int        <0 if KO, >0 if OK
	 */
	/* @deprecated Name of this method must be in CamelCase */
	// @codingStandardsIgnoreStart
	public function load_motherof()
	{
		// @codingStandardsIgnoreEnd
		global $conf;

		// phpcs:enable
		$this->motherof = array();

		// Load array[child]=parent
		$sql = "SELECT fk_parent as id_parent, rowid as id_son";
		$sql .= " FROM " . MAIN_DB_PREFIX . "categorie";
		$sql .= " WHERE fk_parent != 0";
		$sql .= " AND entity IN (" . getEntity('category') . ")";
		$resql = $this->db->query($sql);


		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$this->motherof[$obj->id_son] = $obj->id_parent;
			}
			return 1;
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 *    For category id_categ and its childs available in $this->cats, define property fullpath and fulllabel.
	 *  It is called by getFullArbo()
	 *  This function is a memory scan only from $this->cats and $this->motherof, no database access must be done here.
	 *
	 * @param  int    $id_categ      id_categ entry to update
	 * @param  int    $protection    Deep counter to avoid infinite loop
	 * @return        void
	 * @see getFullArbo()
	 */
	/* @deprecated Name of this method must be in CamelCase */
	// @codingStandardsIgnoreStart
	public function build_path_from_id_categ($id_categ, $protection = 1000)
	{
		// @codingStandardsIgnoreEnd
		// Define fullpath and fulllabel
		$this->cats[$id_categ]['fullpath'] = '_' . $id_categ;
		$this->cats[$id_categ]['fulllabel'] = $this->cats[$id_categ]['label'];
		$i = 0;
		$cursor_categ = $id_categ;
		// print 'Work for id_categ='.$id_categ.'<br>'."\n";
		while ((empty($protection) || $i < $protection) && !empty($this->motherof[$cursor_categ])) {
			//print '&nbsp; cursor_categ='.$cursor_categ.' i='.$i.' '.$this->motherof[$cursor_categ].'<br>'."\n";
			$this->cats[$id_categ]['fullpath'] = '_' . $this->motherof[$cursor_categ] . $this->cats[$id_categ]['fullpath'];
			$this->cats[$id_categ]['fulllabel'] = $this->cats[$this->motherof[$cursor_categ]]['label'] . ' >> ' . $this->cats[$id_categ]['fulllabel'];
			$i++;
			$cursor_categ = $this->motherof[$cursor_categ];
		}

		// We count number of _ to have level
		$nbunderscore = substr_count($this->cats[$id_categ]['fullpath'], '_');
		$this->cats[$id_categ]['level'] = ($nbunderscore ? $nbunderscore : null);
		return;
	}

	/**
	 * Return list of fetched instance of elements having this category
	 *
	 * @param  string    $type         Type of category ('customer', 'supplier', 'contact', 'product', 'member', ...)
	 * @param  int       $onlyids      Return only ids of objects (consume less memory)
	 * @param  int       $limit        Limit
	 * @param  int       $offset       Offset
	 * @param  string    $sortfield    Sort fields
	 * @param  string    $sortorder    Sort order ('ASC' or 'DESC');
	 * @return  array|int                -1 if KO, array of instance of object if OK
	 * @see containsObject()
	 */
	public function getObjectsInCateg($type, $onlyids = 0, $limit = 0, $offset = 0, $sortfield = '', $sortorder = 'ASC')
	{
		global $user, $conf;
		$objs = array();

		$sql = "SELECT fk_parent";
		$sql .= " FROM " . MAIN_DB_PREFIX . "categorie";
		$sql .= " WHERE type = " . $type . " AND entity IN (" . getEntity($conf->entity) . ")";


		if ($limit > 0 || $offset > 0) $sql .= $this->db->plimit($limit + 1, $offset);
		$sql .= $this->db->order($sortfield, $sortorder);
		$resql = $this->db->query($sql);

		if ($resql) {
			while ($rec = $this->db->fetch_array($resql)) {
				if ($onlyids) {
					$objs[] = $rec['fk_parent'];
				} else {
					//$classnameforobj = $this->MAP_OBJ_CLASS[$type];
					$obj = new Tag($this->db);
					$obj->fetch($rec['fk_parent']);
					$objs[] = $obj;
				}
			}
			return $objs;
		} else {
			$this->error = $this->db->error() . ' sql=' . $sql;
			return -1;
		}
	}

	/**
	 *    Returns categories whose id or name match
	 *    add wildcards in the name unless $exact = true
	 *
	 * @param  int        $id       Id
	 * @param  string     $nom      Name
	 * @param  string     $type     Type of category ('member', 'customer', 'supplier', 'product', 'contact'). Old mode (0, 1, 2, ...) is deprecated.
	 * @param  boolean    $exact    Exact string search (true/false)
	 * @param  boolean    $case     Case sensitive (true/false)
	 * @return        Categorie[]|int            Array of Categorie, -1 if error
	 */
	/* @deprecated Name of this method must be in CamelCase and in English */
	// @codingStandardsIgnoreStart
	public function rechercher($id, $nom, $type, $exact = false, $case = false)
	{
		// @codingStandardsIgnoreEnd
		$cats = array();

		// Generation requete recherche
		$sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "categorie";
		$sql .= " WHERE type = 43";
		$sql .= " AND entity IN (" . getEntity('category') . ")";
		if ($nom) {
			if (!$exact)
				$nom = '%' . str_replace('*', '%', $nom) . '%';
			if (!$case)
				$sql .= " AND label LIKE '" . $this->db->escape($nom) . "'";
			else $sql .= " AND label LIKE BINARY '" . $this->db->escape($nom) . "'";
		}
		if ($id) {
			$sql .= " AND rowid = '" . $id . "'";
		}
		$res = $this->db->query($sql);
		if ($res) {
			while ($rec = $this->db->fetch_array($res)) {
				$cat = new Tag($this->db);
				$cat->fetch($rec['rowid']);
				$cats[] = $cat;
			}

			return $cats;
		} else {
			$this->error = $this->db->error() . ' sql=' . $sql;
			return -1;
		}
	}


	/**
	 * Returns the path of the category, with the names of the categories
	 * separated by $sep (" >> " by default)
	 *
	 * @param  string    $sep         Separator
	 * @param  string    $url         Url ('', 'none' or 'urltouse')
	 * @param  int       $nocolor     0
	 * @param  string    $addpicto    Add picto into link
	 * @return    array
	 */
	/* @deprecated Name of this method must be in CamelCase */
	// @codingStandardsIgnoreStart
	public function print_all_ways($sep = ' &gt;&gt; ', $url = '', $nocolor = 0, $addpicto = 0)
	{
		// @codingStandardsIgnoreEnd
		// phpcs:enable
		$ways = array();

		$allways = $this->get_all_ways(); // Load array of categories
		foreach ($allways as $way) {
			$w = array();
			$i = 0;
			$forced_color = '';
			foreach ($way as $cat) {
				$i++;

				if (empty($nocolor)) {
					$forced_color = 'toreplace';
					if ($i == count($way)) {
						// Check contrast with background and correct text color
						$forced_color = 'categtextwhite';
						if ($cat->color) {
							if (colorIsLight($cat->color)) $forced_color = 'categtextblack';
						}
					}
				}

				if ($url == '') {
					$link = '<a href="' . dol_buildpath('/lareponse/card_tag.php?id=' . $cat->id . '&type=' . $cat->type . '" class="' . $forced_color, 1) . '">';
					$linkend = '</a>';
					$w[] = $link . ($addpicto ? img_object('', 'category', 'class="paddingright"') : '') . $cat->label . $linkend;
				} elseif ($url == 'none') {
					$link = '<span class="' . $forced_color . '">';
					$linkend = '</span>';
					$w[] = $link . ($addpicto ? img_object('', 'category', 'class="paddingright"') : '') . $cat->label . $linkend;
				} else {
					$w[] = ($addpicto ? img_object('', 'category') : '') . $cat->label;
				}
			}
			$newcategwithpath = preg_replace('/toreplace/', $forced_color, implode($sep, $w));

			$ways[] = $newcategwithpath;
		}

		return $ways;
	}

	/**
	 *    Returns an array containing the list of parent categories
	 *
	 * @return    int|array <0 KO, array OK
	 */
	/* @deprecated Name of this method must be in CamelCase, in English and with a correct naming */
	// @codingStandardsIgnoreStart
	public function get_meres()
	{
		// @codingStandardsIgnoreEnd
		// phpcs:enable
		$parents = array();

		$sql = "SELECT fk_parent FROM " . MAIN_DB_PREFIX . "categorie";
		$sql .= " WHERE rowid = " . $this->id;

		$res = $this->db->query($sql);

		if ($res) {
			while ($rec = $this->db->fetch_array($res)) {
				if ($rec['fk_parent'] > 0) {
					$cat = new Categorie($this->db);
					$cat->fetch($rec['fk_parent']);
					$parents[] = $cat;
				}
			}
			return $parents;
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 *    Returns in a table all possible paths to get to the category
	 *    starting with the major categories represented by Tables of categories
	 *
	 * @return    array
	 */
	/* @deprecated Name of this method must be in CamelCase */
	// @codingStandardsIgnoreStart
	public function get_all_ways()
	{
		// @codingStandardsIgnoreEnd
		// phpcs:enable
		$ways = array();

		$parents = $this->get_meres();
		if (!empty($parents)) {
			foreach ($parents as $parent) {
				$allways = $parent->get_all_ways();
				foreach ($allways as $way) {
					$w = $way;
					$w[] = $this;
					$ways[] = $w;
				}
			}
		}

		if (count($ways) == 0)
			$ways[0][0] = $this;

		return $ways;
	}


	/**
	 * Return direct childs id of a category into an array
	 *
	 * @return    array|int   <0 KO, array ok
	 */
	// @codingStandardsIgnoreStart
	public function getFilles()
	{
		// @codingStandardsIgnoreEnd
		// phpcs:enable
		$sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "categorie";
		$sql .= " WHERE fk_parent = " . $this->id;
		$sql .= " AND entity IN (" . getEntity('category') . ")";

		$res = $this->db->query($sql);

		if ($res) {
			$cats = array();
			while ($rec = $this->db->fetch_array($res)) {
				$cat = new Tag($this->db);
				$cat->fetch($rec['rowid']);

				$cats[] = $cat;
			}
			return $cats;
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 * Function to get all tag Ids
	 *
	 * @param  array    $tagList    List of tags
	 * @return array
	 */
	public function getAllIdTag($tagList)
	{
		$tagListIds = array();

		foreach ($tagList as $tagListId) {
			$tagListIds[] = array('id' => $tagListId['id'], 'type' => $tagListId['type']);
		}
		return $tagListIds;
	}
}

/**
 * Class tagLine. You can also remove this and generate a CRUD class for lines objects.
 */
class tagLine
{
	// To complete with content of an object tagLine
	// We should have a field rowid, fk_tag and position
}
