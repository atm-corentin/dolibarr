# Import Bank statement and generation for <a href="https://www.dolibarr.org">DOLIBARR ERP CRM</a>

## Features

This module is a simple dashboard that show you some of the most importante informations to manage your business (turnover, expenses, fixed costs, variable costs and more).

Have a look on the wiki page form more informations :

https://wiki.dolibarr.org/index.php/Module_easy_dashboard


## How to use
**1) Module Configuration**

Configure the module :
Before to use the dashboard you setup the module on the module setup page

**1) Module menu**

You can see this module in the left menu of the home top menu.

## Translations

This module is translates in English, French and Spanish

<!--
This module contains also a sample configuration for Transifex, under the hidden directory [.tx](.tx), so it is possible to manage translation using this service. 

For more informations, see the [translator's documentation](https://wiki.dolibarr.org/index.php/Translator_documentation).

There is a [Transifex project](https://transifex.com/projects/p/dolibarr-module-template) for this module.
-->


<!--

## Installation

### From the ZIP file and GUI interface

- If you get the module in a zip file (like when downloading it from the market place [Dolistore](https://www.dolistore.com)), go into
menu ```Home - Setup - Modules - Deploy external module``` and upload the zip file.


Note: If this screen tell you there is no custom directory, check your setup is correct: 

- In your Dolibarr installation directory, edit the ```htdocs/conf/conf.php``` file and check that following lines are not commented:

    ```php
    //$dolibarr_main_url_root_alt ...
    //$dolibarr_main_document_root_alt ...
    ```

- Uncomment them if necessary (delete the leading ```//```) and assign a sensible value according to your Dolibarr installation

    For example :

    - UNIX:
        ```php
        $dolibarr_main_url_root_alt = '/custom';
        $dolibarr_main_document_root_alt = '/var/www/Dolibarr/htdocs/custom';
        ```

    - Windows:
        ```php
        $dolibarr_main_url_root_alt = '/custom';
        $dolibarr_main_document_root_alt = 'C:/My Web Sites/Dolibarr/htdocs/custom';
        ```
        
### From a GIT repository

- Clone the repository in ```$dolibarr_main_document_root_alt/importfrombankcsv```

```sh
cd ....../custom
git clone git@github.com:gitlogin/importfrombankcsv.git importfrombankcsv
```

### <a name="final_steps"></a>Final steps

From your browser:

  - Log into Dolibarr as a super-administrator
  - Go to "Setup" -> "Modules"
  - You should now be able to find and enable the module


-->


## Licenses

**Main code**

GPLv3. See file COPYING for more information.

**Documentation**

All texts and readmes are licensed under GFDL.
