<?php
declare(strict_types=1);

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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once __DIR__.'/CliChaumeilSupplierOrderConfig.class.php';

/**
 * Resolve the supplier order email recipients according to ST-8 rules.
 */
class CliChaumeilSupplierOrderRecipientResolver
{
	/**
	 * Resolve recipients for one supplier order.
	 *
	 * Priority:
	 * 1. order contacts of type CUSTOMER
	 * 2. supplier thirdparty contacts of type CUSTOMER
	 * 3. supplier main email
	 *
	 * @param CommandeFournisseur $supplierOrder Supplier order.
	 * @return array{emails:array<int,string>,contact_ids:array<int,int>,source_used:string}
	 */
	public function resolve(CommandeFournisseur $supplierOrder): array
	{
		$this->ensureSupplierLoaded($supplierOrder);

		$orderContacts = $supplierOrder->liste_contact(
			-1,
			CliChaumeilSupplierOrderConfig::SUPPLIER_ORDER_CONTACT_SOURCE,
			0,
			CliChaumeilSupplierOrderConfig::SUPPLIER_ORDER_CONTACT_CODE,
			1
		);
		if (!is_array($orderContacts)) {
			throw new RuntimeException('Unable to load supplier order contacts.');
		}

		$orderRecipients = $this->extractRecipientsFromContacts($orderContacts);
		if (!empty($orderRecipients['emails'])) {
			return array(
				'emails' => $orderRecipients['emails'],
				'contact_ids' => $orderRecipients['contact_ids'],
				'source_used' => 'order_contacts',
			);
		}

		$supplierContacts = $supplierOrder->thirdparty->getContacts(
			0,
			CliChaumeilSupplierOrderConfig::SUPPLIER_ORDER_CONTACT_CODE,
			'order_supplier'
		);
		if (!is_array($supplierContacts) && $supplierContacts !== -1) {
			throw new RuntimeException('Unexpected supplier contact payload while resolving recipients.');
		}
		if ($supplierContacts === -1) {
			throw new RuntimeException('Unable to load supplier follow-up contacts.');
		}

		$supplierRecipients = $this->extractRecipientsFromContacts($supplierContacts);
		if (!empty($supplierRecipients['emails'])) {
			return array(
				'emails' => $supplierRecipients['emails'],
				'contact_ids' => $supplierRecipients['contact_ids'],
				'source_used' => 'supplier_contacts',
			);
		}

		$fallbackEmail = trim((string) $supplierOrder->thirdparty->email);
		if ($fallbackEmail !== '' && isValidEmail($fallbackEmail)) {
			return array(
				'emails' => array($fallbackEmail),
				'contact_ids' => array(),
				'source_used' => 'thirdparty_email',
			);
		}

		return array(
			'emails' => array(),
			'contact_ids' => array(),
			'source_used' => 'none',
		);
	}

	/**
	 * Ensure the order supplier thirdparty is loaded.
	 *
	 * @param CommandeFournisseur $supplierOrder Supplier order.
	 * @return void
	 */
	private function ensureSupplierLoaded(CommandeFournisseur $supplierOrder): void
	{
		if (!empty($supplierOrder->thirdparty) && !empty($supplierOrder->thirdparty->id)) {
			return;
		}

		$result = $supplierOrder->fetch_thirdparty();
		if ($result <= 0 || empty($supplierOrder->thirdparty) || empty($supplierOrder->thirdparty->id)) {
			throw new RuntimeException('Unable to load supplier thirdparty for the supplier order.');
		}
	}

	/**
	 * Convert Dolibarr contact arrays into a de-duplicated recipient list.
	 *
	 * @param array<int,array<string,mixed>> $contacts Contact payload.
	 * @return array{emails:array<int,string>,contact_ids:array<int,int>}
	 */
	private function extractRecipientsFromContacts(array $contacts): array
	{
		$emails = array();
		$contactIds = array();
		$seenEmails = array();

		foreach ($contacts as $contact) {
			$email = trim((string) ($contact['email'] ?? ''));
			if ($email === '' || !isValidEmail($email)) {
				continue;
			}

			$normalizedEmail = dol_strtolower($email);
			if (isset($seenEmails[$normalizedEmail])) {
				continue;
			}

			$seenEmails[$normalizedEmail] = true;
			$emails[] = $email;
			if (!empty($contact['id'])) {
				$contactIds[] = (int) $contact['id'];
			}
		}

		return array(
			'emails' => $emails,
			'contact_ids' => array_values(array_unique($contactIds)),
		);
	}
}
