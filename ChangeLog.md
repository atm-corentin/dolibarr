# CHANGELOG MODULE CLICHAUMEIL FOR [DOLIBARR ERP CRM](https://www.dolibarr.org)


## 1.9
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
