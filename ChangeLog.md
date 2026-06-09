# CHANGELOG MODULE CLICHAUMEIL FOR [DOLIBARR ERP CRM](https://www.dolibarr.org)


## Unreleased

- NEW : ACHT-2-ANTALIS: Add a generic supplier purchase-price synchronisation engine (Config, Connector contract, Repository, Service, Report, Mailer, abstract cron) with ANTALIS as the first SOAP connector. A daily cron queries the ANTALIS `customerPricesCheck` service for every buyable ANTALIS product and reconciles its price grid against Dolibarr: it updates the buy price via `update_buyprice` (preserving history) only when it differs — rounding the supplier price to Dolibarr's stored precision (`price2num 'MU'`) so re-runs are idempotent — and reactivates resynced lines. Prices are reconciled by price unit (sheet, ream, lot, m²…), not by quantity: the connector emits every ANTALIS threshold labelled by its `personalPriceUnit`, and the service refreshes the matching line in its own unit, never changing it (a line whose unit is not returned is warned and left untouched). The negotiated price (`personalUnitPrice / personalPriceQty`) is synchronised; `PAK` is deliberately left unmapped pending ANTALIS confirmation. ANTALIS is update-only (`supportsTierDiscovery=false`). Resilience and safeguards: SOAP faults retried once (3 s backoff), a circuit-breaker stops the run after 5 consecutive failing batches, a heartbeat logged every 25 batches, a configurable closure guard caps the share of lines a single run may close (protects against a partial API response), and a dry-run mode plus a product-limit knob allow validation on real data without writing. The supplier HTTP password is stored reversibly encrypted (`dolEncrypt`). *09/06/2026* - 1.17.0
- NEW : ACHT-2-ANTALIS: Add an "API connections" admin page and a production-grade sync report. The page presents each connector as a card with a configured / to-configure status badge, plain-language field labels with info-tooltips, a dedicated encrypted-password field (never pre-filled, with a state placeholder), a read-only "Test connection" probe and a "Test mail" button visually distinguished from the save button, a dry-run banner, a link to the scheduled job, and the last run shown as coloured badges; the notification policy and report recipients are shared settings. The report surfaces anomalies first, shows each price change's variation percentage, records the run timestamp and duration, and is emailed as a structured HTML body (summary table, coloured errors/warnings, products linked to their card, subject splitting errors and warnings, footer linking back to the configuration). An in-page collapsible operating guide / go-live runbook is provided. *09/06/2026* - 1.17.0
- NEW : R2-VT-12: Display a red bold warning in proposal and order validation modals when at least one line is below the configured Discountrules minimum margin/mark rate. *08/06/2026* - 1.17.0
- NEW : R1-ST-8: Trigger PROPOSAL_SUPPLIER_SIGN to run the subcontractor selection workflow (create validated CF, refuse other proposals, send email) when a supplier proposal is signed from the standard Dolibarr acceptance button, not only from the custom choose-subcontractor button. *27/05/2026* - 1.16.0
- FIX : Split the single module settings page into dedicated tabs (General, Products, Contracts, Subcontracting) with reformulated labels and tooltips for contract revision parameters. *26/05/2026* - 1.15.1
- FIX : Remove tooltip from RFA getNomUrl links and clean up resulting dead code. *26/05/2026* - 1.15.1
- FIX : R5-DIV: Ensure the shared-proposal clone button and action work correctly by registering clichaumeil for the 'main' hook context (priority 40) so it intercepts before multicompany (priority 50) erases propal.creer. *26/05/2026* - 1.15.1
- NEW : R5-DIV: Allow cloning a shared (cross-entity) proposal even when multicompany has blocked write access, provided the user holds propal.creer — the clone is forced into the current entity. *26/05/2026* - 1.15.1
- NEW : ACHT-7: Add file fee rate and amount to product cost breakdown calculation. *28/04/2026* - 1.15.0
- NEW : Replace the eight commission coefficient setup constants with a dedicated Dolibarr dictionary, seed the default rows on module activation without duplicates on reactivation, migrate legacy values when available, and keep categories/groups on the commissions setup page. *30/04/2026* - 1.15.0
- FIX : Stop injecting the generic `__CHECK_READ__` mass-mail tracking pixel into ST-8 transactional supplier-order emails, leaving the placeholder empty instead of calling `public/emailing/mailing-read.php` with an undefined tag. *28/04/2026* - 1.15.0
- FIX : Correct the ST-8 CLI non-regression script so it loads real user rights before execution and asserts the persisted supplier-proposal/order link instead of transient in-memory origin fields. *27/04/2026* - 1.15.0
- FIX : Reuse the standard supplier-order PDF model fallback (`COMMANDE_SUPPLIER_ADDON_PDF`) in the ST-8 workflow so automatic PDF generation no longer fails when `model_pdf` is empty on the newly created order. *27/04/2026* - 1.15.0
- FIX : Harden the ST-8 supplier-order mail workflow and replace the initial CLI checker with a real case-based non-regression script featuring dry-run SQL rollback, branch assertions, and agenda verification. *27/04/2026* - 1.15.0
- NEW : Extend subcontractor selection to convert the chosen supplier proposal into a validated supplier order, generate its PDF, send it by email with a dedicated configurable template, and trace the successful send in the agenda. *27/04/2026* - 1.15.0

## 1.14
- FIX : Prevent supplier portal line prices from being saved when mandatory attachment validation fails, both on AJAX price updates and final response submission. *28/04/2026* - 1.14.1
- NEW : Add a supplier response date extrafield on supplier proposals and populate it when a supplier submits a response from the portal. *23/04/2026* - 1.14.0
- FIX : Sort supplier portal request-for-quotation list by reference in descending order by default. *17/04/2026* - 1.14.0
- FIX : Reverse supplier portal discussion history order on supplier proposals so the most recent event is always displayed first, like a chat conversation. *17/04/2026* - 1.14.0
- NEW : Document the extrafield-based protected-line rules in setup, hide protected proposal lines from MassAction UI, and reject MassAction split/copy on protected proposal lines without the dedicated manage right. *16/04/2026* - 1.14.0
- NEW : Add entity-scoped default proposal products/services with automatic injection on creation/clone, protected proposal lines, dedicated manage right, and backend guards for delete/edit actions. *15/04/2026* - 1.14.0

## 1.13
- NEW : Send an email to the internal follow-up manager when a supplier submits a response from the external portal, and link the notification directly to the supplier proposal card. *13/04/2026* - 1.13.0
- NEW : Materialize the global supplier RFA list into a yearly summary table with manual rebuild tool, nightly cron, and SQL-native filtering/sorting/pagination. *31/03/2026* - 1.13.0

## 1.12
- NEW : Aggregate subsidiary supplier turnover into the root parent company on the global RFA list, keep final filters/sorts correct, and disable the CA link on aggregated rows. *31/03/2026* - 1.12.0
- NEW : Add ACHT-5 scheduled RFA negotiation reminder cron with cloneable parameters and default email template seed. *31/03/2026* - 1.12.0
- NEW: Block proposal validation when at least one line has a cost price higher than the sale price, including proposal card UI, mass validation, and PROPAL_VALIDATE trigger guard. *30/03/2026* - 1.12.0
- NEW : Add packaging and transport rates to the product cost breakdown, update the R2 formula, display virtual total costs, realign import headers, and keep existing persisted `pa_fg` / `cost_price` values unchanged when overhead rate is empty. No mass recalculation is performed; the new formula applies on the next product change. *30/03/2026* - 1.12.0
- NEW : Add Print Management commission configuration with dedicated coefficients and group on the commissions setup page. *01/04/2026* - 1.12.0
- FIX : Replace subcontractor selection tick icon with a `Valider` button in the selection modal. *01/04/2026* - 1.12.0
- NEW : Add read-only display of standard line units on the external supplier portal; no change to the standard conversion flow. *01/04/2026* - 1.12.0

## 1.11
- FIX : Load supplier proposal lines on portal by using `ref_fourn` supplier reference column in SQL join. *30/03/2026* - 1.11.1
- NEW: Add script to add ref_supplier column on supplier proposals *28/01/2026* - 1.11.0
- NEW : Supplier portal proposal timeline now shows sent-by-mail messages (AC_PROPOSAL_SUPPLIER_SENTBYMAIL) with mail header (subject/from/to/cc) and safe HTML rendering. Email attachments are copied to the agenda event to make them downloadable on the portal. *23/01/2026* - 1.11.0
- FIX : External supplier proposal list/card now uses ref_supplier alias for label display - *20/01/2026* - 1.11.0
- NEW : External supplier proposal list/card show supplier label (ref_fourn) and hydration updated *19/01/2026* -1.11.0
- NEW : Add script to add ref_fourn column to supplier proposals *19/01/2026* - 1.11.0

## 1.10
- FIX: Load additional language file and adjust supplier proposal status handling *15/01/2026* - 1.10.0
- NEW: Add user contact assignment to supplier proposals in triggers *15/01/2026* - 1.10.0
- NEW: Implement commission configuration and automatic customer category management *14/01/2026* - 1.10.0

## 1.9
- FIX : Add attachment on supplier portal proposal form *19/12/2025* - 1.9.1
- NEW : Replace setup select categorie by multiselect *16/12/2025* - 1.9.0 
- FIX : Product creation no longer blocks on "% Frais généraux" and purchase-tab composition fields on services now save correctly. *16/12/2025* - 1.9.0
- FIX : Supplier status auto-populates when creating a supplier request from a customer proposal. *16/12/2025* - 1.9.0
- FIX : Supplier portal UX — price button renamed to “Enregistrer les prix”, titles and free-text lines are visible (subtotal lines stay hidden), labels updated, and a red reminder warns that attaching the quote file is mandatory. *16/12/2025* - 1.9.0
- 
## 1.8
- New : display and edit CliChaumeil cost breakdown extrafields directly on supplier price tab, recomputing cost price, and extend calculator support to service products - *12/12/2025* - 1.8.0

## 1.7
- NEW : Add new button to a new modal on proposal/order card to select on supplier proposal - *10/12/2025* - 1.7.0

## 1.6
- FIX : External supplier proposal UX — form above discussion, public messages only, anchor after attachment/message, subtotal handling, no draft regression on send, merged file+message button, labels updated to “Send message/reply” and “Supplier response received”. *09/12/2025* - 1.6.2
- FIX : Supplier status no longer auto-fills when creating a request for quotation from a customer proposal. *05/12/2025* - 1.6.1
- NEW : Add Extrafield units on propal/command line *01/12/2025* - 1.6.0

## 1.5
- FIX : Extrafields visibilities without line propal/commande. *28/11/2025* - 1.5.2
- FIX : Upload file in some case when we upload file in session. *26/11/2025* - 1.5.1
- NEW : Add conf (category product)to display extrafields (Length & height) on propal/command line *24/11/2025* - 1.5.0
- NEW : Add calcul cost_price from extrafields on product. *20/11/2025* - 1.5.0

## 1.4
- NEW : Add supplier proposal in externalaccess. *06/11/2025* - 1.3.0

## 1.3
- NEW : Add RFA list by fourn (Thirdparties Menu Left) *15/10/2025* - 1.3.0 

## 1.2
- NEW : Warning icon displayed for negative margin on propal lines. *30/09/2025* - 1.2.0
- NEW : Update total cost of bom with general expenses extrafield - *01/10/2025* - 1.2.0
- NEW : add Extrafield thirdparty ref required(boolean) use on trigger order_validate & massaction *24/09/2025* - 1.2.0
- NEW : Surface calculation from length and height on order/propal lines *24/09/2025* - 1.2.0

## 1.1
- NEW : automated contract tarif revision feature that updates contracts line prices on a scheduled date and notifies relevant users of the change  - 1.1.0

## 1.0
- NEW: Add management of year-end rebates (RFA) for suppliers *19/09/2025* - 1.1.0
- NEW : Create/Clean clichaumeil module : - Initial version *09/09/2025* - 1.0.0
