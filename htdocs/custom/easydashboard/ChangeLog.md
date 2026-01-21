# CHANGELOG EASYDASHBOARD FOR <a href="https://www.dolibarr.org">DOLIBARR ERP CRM</a>

## 4.17
fix : PHP 8.2 comptability initialise $tabProjectionTreso as array
fix : show every project on list projectid even if not create in the period
fix : function sql, show sql query in return (for debugging purpose)
fix : Only show stats of open projects

## 4.16
fix : disable graph on project task because it is not compatible with v18

## 4.15
fix : hide PHP warning

## 4.14
fix : compatible with Dolibarr v17
fix : VAT value of expense reports was not included in VAT calculation
fix : If Loan module was not active, Easydashbord threw an error for not finding the table in database
fix : Export of CSV file was not working in some cases, we had some checks to secure the process

## 4.13
New : Check if ONLY_FULL_GROUP_BY is disabled
Fix : Now button last12Month will actually show the last 12 monthes

## 4.12
fix : Add some translation to config screen
fix : ORDER BY date (and not group) so the main graph works correctly
fix : on futur cash calculation, set $facturefournstatic->id + $facturestatic->id + $socialcontribstatic->id so getSommePaiement() methods work
fix : Add some translation (french) on admin screen
New : Pie graph profit by customer

## 4.11
new : Allow to not include some taxes as charges from setup pages

## 4.10
fix : Eliminate error SQL GROUP_BY

## 4.9
fix : Select table salary for Dolibarr version > 13 and table payment salary for older versions

## 4.8
fix : Select proyect created during perdio and all invoices of proyect

## 4.7
fix : use constant DOL_VERSION to select name of sql field

## 4.6
new : Add debug mode

## 4.5
FIX : Compatibility with Dolibarr v14

## 4.4
FIX : Some changes in Italian language file

## 4.3
NEW : italian language added

## 4.1
CHANGE : All the opened quotations are shown (not depending of projects anymore)

## 4.0
NEW : Add button (last 12 months)

## 3.9
FIX : When turnover = 0 error of division per 0
FIX : Only show project invoiced during the current period

## 3.8
FIX : Check if task are used before to get list of tasks

## 3.7
FIX : Bug on list of contracts

## 3.6
NEW : Setup allow to show or hide stat and graph of projects
FIX : Add input Token to allow : MAIN_SECURITY_CSRF_WITH_TOKEN

## 3.3
NEW : Teasury is available on  the main graph
NEW : Allow simple or advanced display mode (in module setup)
NEW : A new extrafield period is available in contracts so the projection function is more precise
FIX : Date start and date end generated some bugs, so the default date start is now the start month of comptability (config in setup->company)
FIX : social charge did not go in the good category (fix/variable) price
FIX : It is posible to limit the quantity of customer in graph


## 3.0
NEW : Projection function to show futur incom
NEW : Graph to see economic statistics of all proyects
NEW : List of leads
NEW : All the display is changed
NEW : The tooltips of every point of the graphs show many informations

## 2.1
FIX : Y Axis chart number format is like (1 000 000 instead of 1000000)

## 2.0
NEW : It is possible to define the beginning and the end of the period in the setup page
NEW : The setup page is easier to configure thanks to some list fields
FIX : Avoid division by 0 (when turnover = 0)
FIX : List of current order showed only current order in the selected period

## 1.2
FIX : Customer invoices are not selected by type
FIX : Customer invoices are not selected by statut
FIX : Vendor invoices are not selected by statut

## 1.1
It is possible to give a list of fixed cost projects
It is possible to select if a cost without project linked is fixed or variable
FIX : The end date on date picker show one more day
FIX : Multicompany module is not working

## 1.0
Initial version