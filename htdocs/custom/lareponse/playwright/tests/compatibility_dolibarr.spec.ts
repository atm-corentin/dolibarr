import { Page, expect, test } from "@playwright/test";
import * as dotenv from "dotenv";
dotenv.config({ path: require("find-config")(".env") });
import DolibarrFunctions from "../dolibarr_functions/dolibarr_function";
import LaReponseFunctions from "../lareponse_functions/lareponse_function";

/*
 * The purpose of this test is to check whether LR module works properly under Dolibarr 17, 18, 19. We'll check each page where LR is used to see if the module works without error.
 */
test("Verification compatibilité DOLIBARR 17, 18, 19", async ({ page }) => {
  test.setTimeout(1000000);
  await DolibarrFunctions.authenticate(page, process.env.DOLIBARR_URL_BACKEND, process.env.USER_BACKEND_ID_USER, process.env.USER_BACKEND_PASSWORD_USER);
  page.on("console", (msg) => {
    console.log('connecté');
  });
  await LaReponseFunctions.checkCompatibilityOnArticleObject(page); 
  await LaReponseFunctions.checkCompatibilityOnListTagMenu(page);
  await LaReponseFunctions.checkCompatibilityOnConfigurationMenu(page);
  await LaReponseFunctions.checkCompatibilityOndocumentationMenu(page);
  await LaReponseFunctions.checkCompatibilityOnDolibarrObject(page, 'contact');
  await LaReponseFunctions.checkCompatibilityOnDolibarrObject(page, 'product');
  await LaReponseFunctions.checkCompatibilityOnDolibarrObject(page, 'societe');
  await LaReponseFunctions.checkCompatibilityOnDolibarrObject(page, 'projet');
  await LaReponseFunctions.checkCompatibilityOnGestionParc(page, 'address');
  await LaReponseFunctions.checkCompatibilityOnGestionParc(page, 'device');
  await LaReponseFunctions.checkCompatibilityOnGestionParc(page, 'application');
  
  await DolibarrFunctions.delay(2000);

  /*await DolibarrFunctions.authenticate(page, process.env.DOLIBARR_URL_BACKEND, process.env.USER_BACKEND_ID_ADMIN, process.env.USER_BACKEND_PASSWORD_ADMIN);
  await page.goto('https://suiteservicedolibarr17.dev.code42.io/custom/gestionparc/dashboard.php?idmenu=4198&mainmenu=gestionparc&leftmenu=');
  await DolibarrFunctions.checkError(page);
  await LaReponseFunctions.checkCompatibilityOnArticleObject(page); 
  await LaReponseFunctions.checkCompatibilityOnListTagMenu(page);
  await LaReponseFunctions.checkCompatibilityOnConfigurationMenu(page);
  await LaReponseFunctions.checkCompatibilityOndocumentationMenu(page);
  await LaReponseFunctions.checkCompatibilityOnDolibarrObject(page, 'contact');
  await LaReponseFunctions.checkCompatibilityOnDolibarrObject(page, 'product');
  await LaReponseFunctions.checkCompatibilityOnDolibarrObject(page, 'societe');
  await LaReponseFunctions.checkCompatibilityOnDolibarrObject(page, 'projet');
  await LaReponseFunctions.checkCompatibilityOnGestionParc(page, 'address');
  await LaReponseFunctions.checkCompatibilityOnGestionParc(page, 'device');
  await LaReponseFunctions.checkCompatibilityOnGestionParc(page, 'application');
  await DolibarrFunctions.delay(2000);*/
  await page.close();
});

