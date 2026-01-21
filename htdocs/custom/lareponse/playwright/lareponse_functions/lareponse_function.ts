import { Page, expect, test } from "@playwright/test";
import * as dotenv from "dotenv";
import DolibarrFunction from "../dolibarr_functions/dolibarr_function";

dotenv.config({ path: require("find-config")(".env") });
const numberOfTest = Math.floor(Math.random() * 5672);
let article_title2 = "[PlayWright] Test N°" + numberOfTest +" Article 2 N°" + Math.floor(Math.random() * 3712478493);
let tagName = "Tag n°" + Math.floor(Math.random() * 2435);
let objectName = "Test n°" + Math.floor(Math.random() * 2435);

const laReponseFunctions = {

  /*
  * Function to check if LR works on the 'Article' object, and check on the tabs of an article's card. (Card, Related items and Attachments)  
  */
  async checkCompatibilityOnArticleObject(page) {
    await DolibarrFunction.delay(750);
    await page.locator("#mainmenutd_lareponse").click();
    //await DolibarrFunction.checkError(page);
    await page.getByRole("link", { name: "Nouvel article" }).click();
    await DolibarrFunction.checkError(page);
    await page.locator("#title").fill(article_title2);
    await DolibarrFunction.delay(750);
    await page.click('input.button[name="add"]');
    await page.click('div#list_tag_chosen');
    await page.click('div#list_tag_chosen ul.chosen-results li[data-option-array-index="1"]');
    await DolibarrFunction.checkError(page);
    await page.click('input.button[name="save"]');
    await DolibarrFunction.checkError(page);
    await page.click('a#article.tab');
    await DolibarrFunction.checkError(page);
    await page.click('a#document.tab');
    await DolibarrFunction.checkError(page);
    await page.getByRole("link", { name: "Nouvel import" }).click();
    await DolibarrFunction.checkError(page);
  
  },

  /*
  * Function to check if LR is working on the Configuration menu and their various tabs (Settings, About, Migration Management, Technical Information).
  */
  async checkCompatibilityOnConfigurationMenu(page) { 
    await DolibarrFunction.delay(1500);
    await page.locator("#mainmenutd_lareponse").click();
    await page.getByRole("link", { name: "Configuration" }).click();
    await DolibarrFunction.checkError(page);
    await page.click('a#about.tab');
    await DolibarrFunction.checkError(page);
    await page.click('a#migration.tab');
    await DolibarrFunction.checkError(page);
    await page.click('a#information.tab');
    await DolibarrFunction.checkError(page);
  },
  

  /*
  * Fonction pour checker si LR marche sur le menu Configuration et les différents onglet de ce menu (Réglages, A propos, Gestion des Migrations, Informations Techniques)
  * STEP 1: Create a Tag and Check errors.
  * STEP 2: Delete this object
  */
  async checkCompatibilityOnListTagMenu(page) {
    await DolibarrFunction.delay(750);
    await page.locator("#mainmenutd_lareponse").click();
    await page.getByRole("link", { name: "Liste Tag" }).click();
    await DolibarrFunction.checkError(page);
    await page.click("a.btnTitlePlus");
    await DolibarrFunction.checkError(page);
    await page.fill('#label', `${tagName}`);
    await page.click('input.button[name="creation"]');
    await DolibarrFunction.delay(500);
    await page.locator(`a:has-text("${tagName}")`).click();
    await DolibarrFunction.checkError(page);
    await page.click('a.butActionDelete');
    const firstYesButton = await page.waitForSelector('.ui-dialog-buttonset button:first-child');
    await firstYesButton.click();
  
  },
  
  /*
  * Function to check if LR works on the Documentation menu and the various tabs in this menu (User Documentation, News, Contact us).  
  */
  async checkCompatibilityOndocumentationMenu(page) { 
    await DolibarrFunction.delay(750);
    await page.getByRole("link", { name: "Documentation" }).click();
    await DolibarrFunction.checkError(page);
    await page.click('a#userdoc.tab');
    await DolibarrFunction.checkError(page);
    await page.click('a#changelog.tab');
    await DolibarrFunction.checkError(page);
    await page.click('a#contactus.tab');
  },
  
  /*
  * Function to check if LR works on a Dolibarr Object (Product, Project, Third Party, Contact)
  * STEP 1 : Create either a Product, Project, Third Party or Contact.
  * STEP 2 : When you create it, you get to the Dolibarr object file and go to the Item tab of LR.
  * STEP 3 : We delete this object
  */
  async checkCompatibilityOnDolibarrObject(page, object) { 
    await DolibarrFunction.delay(750);
    await page.goto(process.env.DOLIBARR_URL_BACKEND + object + '/card.php?leftmenu=' + object + '&action=create&type=0');
    if (object == 'product') {
      await page.fill('#ref', `${objectName}`);
      const inputField = await page.waitForSelector('input[name="label"]');
      await inputField.fill('test');
      await page.click('input.button[name="add"]');
    } else if (object == 'projet') {
      const inputField = await page.waitForSelector('input[name="title"]');
      await inputField.fill('test');
      await page.click('input.button[name="save"]');
    } else if (object == 'contact') {
      await page.fill('#lastname', `${objectName}`);
      await page.click('input.button[name="add"]');
    } else if (object == 'societe') {
      await page.fill('#name', `${objectName}`);
      const spanToClickOne = await page.$('span[data-select2-id="1"]');
      await spanToClickOne.click();
      await DolibarrFunction.delay(500);
      const liWithMinus0 = await page.$('li[id*="-0"]');
      await DolibarrFunction.delay(250);
      await liWithMinus0.click();
      await DolibarrFunction.delay(250);
      await page.click('input.button[name="save"]');
    }
  
    await DolibarrFunction.checkError(page);
    await page.click('a#lareponse_article.tab');
    await DolibarrFunction.checkError(page);
  
    if (object == 'product') {
      await page.click('a#card.tab');
      await DolibarrFunction.delay(300);
      page.locator('#action-delete').click();
  
    } else if (object == 'projet') {
      await page.click('a#project.tab');
      await DolibarrFunction.delay(300);
      const deleteLink = await page.waitForSelector('//a[@class="butActionDelete" and contains(@href, "action=delete")]');
      await deleteLink.click();
  
    } else if (object == 'contact') {
      await page.click('a#card.tab');
      await DolibarrFunction.delay(300);
      const deleteLink = await page.waitForSelector('//a[@class="butActionDelete" and contains(@href, "action=delete")]');
      await deleteLink.click();
    } else if (object == 'societe') {
      await page.click('a#card.tab');
      await DolibarrFunction.delay(300);
      page.locator('#action-delete').click();
    }
    await DolibarrFunction.delay(300);
    const firstYesButton = await page.waitForSelector('.ui-dialog-buttonset button:first-child');
    await DolibarrFunction.delay(500);
    await firstYesButton.click();
    await DolibarrFunction.delay(500);
  },
  
  /*
  * Fonction pour checker si LR marche sur GestionParc (Equipement, Application, Adresse)
  * STEP 1 : On crée soit un Equipement, Application, Adresse
  * STEP 2 : On check LR sur l'onglet de la fiche et des Articles Associés
  * STEP 3 : We delete this object
  */
  async checkCompatibilityOnGestionParc(page, object) { // Cette fonction a pour but d'aller sur tout les leftmenus de lareponse
    await DolibarrFunction.delay(750);
    if (object == 'device') {
      await page.goto(process.env.DOLIBARR_URL_BACKEND + 'custom/gestionparc/device_card.php?action=create&idmenu=2908&mainmenu=gestionparc&leftmenu=');
      await page.fill('#name', `${objectName}`);
      const boutonCible = await page.$('a[onclick="addType(\'device\')"]');
      await boutonCible?.click();
      const inputElement = await page.$('.swal2-input');
      await inputElement.type('Test');
      const boutonAjouter = await page.$('button.swal2-confirm');
      await boutonAjouter.click();
  
    } else if (object == 'application') {
      await page.goto(process.env.DOLIBARR_URL_BACKEND + 'custom/gestionparc/application_card.php?action=create&idmenu=2912&mainmenu=gestionparc&leftmenu=');
      await page.fill('#name', `${objectName}`);
  
    } else if (object == 'address') {
      await page.goto(process.env.DOLIBARR_URL_BACKEND + 'custom/gestionparc/address_card.php?action=create&idmenu=2914&mainmenu=gestionparc&leftmenu=');
      await page.fill('#name', '192.168.0.1');
    }
    await page.click('input.button[name="add"]');
    await DolibarrFunction.checkError(page);
    await page.click('a#lareponse_article.tab');
    await DolibarrFunction.checkError(page);
    await DolibarrFunction.delay(500);
    await page.click('a#card.tab');
    await page.click('a.butActionDelete');
    const firstYesButton = await page.waitForSelector('.ui-dialog-buttonset button:first-child');
    await DolibarrFunction.delay(500);
    await firstYesButton.click();
    await DolibarrFunction.delay(500);
  }

};

export default laReponseFunctions;
