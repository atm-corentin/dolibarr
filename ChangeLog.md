# CHANGELOG MODULE CLICHAUMEIL FOR [DOLIBARR ERP CRM](https://www.dolibarr.org)


## Unreleased
- NEW : Materialize the global supplier RFA list into a yearly summary table with manual rebuild tool, nightly cron, and SQL-native filtering/sorting/pagination. *31/03/2026* - 1.13.0
- NEW : Aggregate subsidiary supplier turnover into the root parent company on the global RFA list, keep final filters/sorts correct, and disable the CA link on aggregated rows. *31/03/2026* - 1.12.0
- NEW : Add ACHT-5 scheduled RFA negotiation reminder cron with cloneable parameters and default email template seed. *31/03/2026* - 1.12.0

## 1.12
- NEW: Block proposal validation when at least one line has a cost price higher than the sale price, including proposal card UI, mass validation, and PROPAL_VALIDATE trigger guard. *30/03/2026* - 1.12.0
- NEW : Add packaging and transport rates to the product cost breakdown, update the R2 formula, display virtual total costs, realign import headers, and keep existing persisted `pa_fg` / `cost_price` values unchanged when overhead rate is empty. No mass recalculation is performed; the new formula applies on the next product change. *30/03/2026* - 1.12.0

## 1.12
- FIX : Replace subcontractor selection tick icon with a `Valider` button in the selection modal. *01/04/2026* - 1.12.1
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
