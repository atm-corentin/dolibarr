<?php
/* Copyright (C) 2010-2021	Regis Houssin	<regis.houssin@inodbox.com>
 * Copyright (C) 2021		Antonin MARCHAL	<antonin@letempledujeu.fr>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *      \file       /multicompany/core/triggers/interface_50_modMulticompany_MulticompanyPMP.class.php
 *      \ingroup    multicompany
 *      \brief      Trigger file to calculate PMP per entity
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';

/**
 *      \class      InterfaceMulticompanyWorkflow
 *      \brief      Classe des fonctions triggers des actions personnalisees du module multicompany
 */

class InterfaceMulticompanyPMP extends DolibarrTriggers
{
    public $family = 'multicompany';

    public $description = "Trigger to calculate PMP per entity";

    /**
     * Version of the trigger
     *
     * @var string
     */
    public $version = self::VERSION_DOLIBARR;

    /**
     *
     * @var string Image of the trigger
     */
    public $picto = 'fa-globe';

	/**
	 * Function called when a Dolibarrr business event is done.
	 * All functions "runTrigger" are triggered if file is inside directory htdocs/core/triggers or htdocs/module/core/triggers (and declared)
	 *
	 * Following properties may be set before calling trigger. The may be completed by this trigger to be used for writing the event into database:
	 * $object->id (id of entity)
	 * $object->element (element type of object)
	 *
	 * 	@param		string		$action		Event action code
	 * 	@param		Object		$object		Object
	 * 	@param		User		$user		Object user
	 * 	@param		Translate	$langs		Object langs
	 * 	@param		conf		$conf		Object conf
	 * 	@return		int						<0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		if (!isModEnabled('multicompany')) {
			return 0; // Module not active, we do nothing
		}

		if ($action == 'STOCK_MOVEMENT') {

			$fk_product  = $object->product_id;
			$product = new Product($this->db);

			$ret = $product->fetch($fk_product);
			if ($ret < 1) {
				$this->errors = $product->errors;
				return -1;
			}

			$sql = "SELECT pmp FROM ".MAIN_DB_PREFIX."product_perentity";
			$sql.= " WHERE fk_product = ".$product->id;
			$sql.= " AND entity = ".$conf->entity;

			$resql = $this->db->query($sql);

			if ($resql) {

				$num = $this->db->num_rows($resql);

				if ($num == 0) {

					$newpmp = $product->pmp;

					$sql2 = "INSERT INTO ".MAIN_DB_PREFIX."product_perentity (";
					$sql2.= "entity";
					$sql2.= ", fk_product";
					$sql2.= ", pmp";
					$sql2.= ") VALUES (";
					$sql2.= $conf->entity;
					$sql2.= ", ".$product->id;
					$sql2.= ", ".$newpmp;
					$sql2.= ")";

					$resql2 = $this->db->query($sql2);
					if (empty($resql2)) {
						$this->errors[] = $this->db->lasterror();
						return -1;
					}

				} else {

					$oldpmp = $this->db->fetch_object($ret)->pmp;

					$newstock = intval($product->stock_reel); // var $newstock not use !
					$oldstock = intval($product->stock_reel) + (intval($object->qty) * (-1));
					$newpmp = 0;

					if ($object->type == 0 || $object->type == 3) {

						if ($object->price > 0 || (!empty($conf->global->STOCK_UPDATE_AWP_EVEN_WHEN_ENTRY_PRICE_IS_NULL) && $object->price == 0 && in_array($object->origin_type, array('order_supplier', 'invoice_supplier')))) {

							$oldqtytouse = ($oldstock >= 0 ? $oldstock : 0);

							// We make a test on oldpmp > 0 to avoid to use normal rule on old data with no pmp field defined
							if ($oldpmp > 0 && $oldqtytouse > 0) {
								$newpmp = price2num((($oldqtytouse * $oldpmp) + (intval($object->qty) * $object->price)) / ($oldqtytouse + intval($object->qty)), 'MU');
							} else {
								$newpmp = $object->price; // For this product, PMP was not yet set. We set it to input price.
							}

						} else {
							$newpmp = $oldpmp;
						}

					} elseif ($object->type == 1 || $object->type == 2) {

						// After a stock decrease, we don't change value of the AWP/PMP of a product.
						$newpmp = $oldpmp;

					} else {

						// Type of movement unknown
						$newpmp = $oldpmp;
					}

					$newpmp = price2num($newpmp, 'MU');
				}

				$this->db->free($resql);

			} else {
				$this->errors[] = $this->db->lasterror();
				return -1;
			}

			$sql = "UPDATE ".MAIN_DB_PREFIX."product_perentity SET pmp = ".$newpmp;
			$sql.= " WHERE entity = ".$conf->entity;
			$sql.= " AND fk_product = ".$product->id;

			$resql = $this->db->query($sql);
			if (empty($resql)) {
				$this->errors[] = $this->db->lasterror();
				return -1;
			}

		}

		return 0;
	}

}
