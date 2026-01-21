# CUSTOMISECARD FOR [DOLIBARR ERP CRM](https://www.dolibarr.org)

## Features

This module add the possibility to customise the different card view of Dolibarr.<br>
When you are on a customisable view an eye icon appear on the top right of your screen, click on it to enter on edition mode.<br>
You have the possibility to share the customise configuration between entity by unable the option on the setup page, while unable each current and futur modification of main entity will be apply on every entity.<br>

For developpers :<br>
If you want to call this module on your pages execute this hook  ```$hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action);```<br>



<!--
![Screenshot customisecard](img/screenshot_customisecard.png?raw=true "CustomiseCard"){imgmd}
-->

Other modules are available on [Dolistore.com](https://www.dolistore.com).

## Translations

Translations can be define manually by editing files into directories *langs*.

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

- Clone the repository in ```$dolibarr_main_document_root_alt/customisecard```

```sh
cd ....../custom
git clone git@github.com:gitlogin/customisecard.git customisecard
```

### <a name="final_steps"></a>Final steps

From your browser:

  - Log into Dolibarr as a super-administrator
  - Go to "Setup" -> "Modules"
  - You should now be able to find and enable the module

-->

## Licenses

### Main code

GPLv3 or (at your option) any later version. See file COPYING for more information.

### Documentation

All texts and readmes are licensed under GFDL.
