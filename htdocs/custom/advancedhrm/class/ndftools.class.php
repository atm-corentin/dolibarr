<?php

class NdfTools {
	/**
	 * List of types
	 *
	 * @param int $active Active or not
	 * @return  array
	 */
	public static function listOfTypes($active = 1) {
		global $langs, $db;
		$ret = array();
		$sql = 'SELECT id, code, label';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'c_type_fees';
		$sql .= ' WHERE active = '.((int) $active);
		$result = $db->query($sql);
		if($result) {
			$num = $db->num_rows($result);
			$i = 0;
			while($i < $num) {
				$obj = $db->fetch_object($result);
				$ret[$obj->id] = (($langs->transnoentitiesnoconv($obj->code) != $obj->code) ? $langs->transnoentitiesnoconv($obj->code) : $obj->label);
				$i++;
			}
		}
		else {
			dol_print_error($db);
		}

		return $ret;
	}
}