import { Page, expect, test } from "@playwright/test";
import * as dotenv from "dotenv";
dotenv.config({ path: require("find-config")(".env") });
import BasicFunctions from "../dolibarr_functions/dolibarr_function";

const deleteArticle: number = 1; // CHANGE THIS IF YOU WANT (1 to delete articles, 0 to keep articles)
const numberOfTest = Math.floor(Math.random() * 5672);
let article_title1 = "[PlayWright] Test N°" + numberOfTest +" Article 1 N°" + Math.floor(Math.random() * 3712478493);
let article_title2 = "[PlayWright] Test N°" + numberOfTest +" Article 2 N°" + Math.floor(Math.random() * 3712478493);
let article_title3 = "[PlayWright] Test N°" + numberOfTest +" Article 3 N°" + Math.floor(Math.random() * 3712478493);
let article_title4 = "[PlayWright] Test N°" + numberOfTest +" Article 4 N°" + Math.floor(Math.random() * 3712478493);
let expectedBadgeAdminValue = 3; // Remplacez par la valeur attendue
let expectedBadgeUserValue = 2; // Remplacez par la valeur attendue

function delay(time) {
  return new Promise(function (resolve) {
    setTimeout(resolve, time);
  });
}

/*
 * STEP 2 : Admin create an article with Product 1 tag and Product 2 tag
 */
async function CreateArticleTwoTagAdmin(page) {
  await delay(1500);
  await page.locator("#mainmenua_lareponse").click();
  await page.getByRole("link", { name: "Nouvel article" }).click();
  await page.locator("#title").fill(article_title1);
  await page.click('input.button[name="add"]');
  await page.click('div#list_tag_chosen');
  await page.click('div#list_tag_chosen ul.chosen-results li[data-option-array-index="2"]');
  await delay(1000);
  await page.click('div#list_tag_chosen');
  await page.click('div#list_tag_chosen ul.chosen-results li[data-option-array-index="3"]');
  await page.click('input.button[name="save"]');
  await page.click('a#article.tab');
  await delay(1500);
  let badgeElement = await page.$('.badge.marginleftonlyshort');
  let badgeValue = await badgeElement.textContent();
  await BasicFunctions.showTemporaryMessage(page, `Nous avons de base ${parseInt(badgeValue) - 1} articles associés pour ces tags avant création de cet article. On rappelle que l'on veut ${expectedBadgeAdminValue} articles associés pour l'utilisateur ${process.env.USER_BACKEND_ID_ADMIN} avec le numéro de test ${numberOfTest}.`);

}

/*
 * STEP 3 : Admin create an article with Product Tag "Produit 1"
 */
async function CreateArticleTagOneAdmin(page) {
  await delay(1500);
  await page.getByRole("link", { name: "Nouvel article" }).click();
  await page.locator("#title").fill(article_title2);
  await page.click('input.button[name="add"]');

  await page.click('span#select2-private-container');
  const optionIndex = 1;
  await page.click(`.select2-results__option:nth-child(${optionIndex + 1})`);

  await page.getByRole('list').first().click();
  await page.click('div#list_tag_chosen ul.chosen-results li[data-option-array-index="2"]');
  await BasicFunctions.showTemporaryMessage(page, `Cet Article ne devrait être vu que par ${process.env.USER_BACKEND_ID_ADMIN} puisque l'on as mis le statut PRIVATE. Cet article aura que deux articles associés avec un Test n°${numberOfTest}.`);
  await delay(2000);

  await page.click('input.button[name="save"]');
  await page.click('a#article.tab');

}

/*
 * STEP 4 : Admin create an article with Product Tag "Produit 2"
 */
async function CreateArticleTagTwoAdmin(page) {
  await delay(1500);
  await page.getByRole("link", { name: "Nouvel article" }).click();
  await page.locator("#title").fill(article_title3);
  await page.click('input.button[name="add"]');

  await page.click('span#select2-private-container');
  const optionIndex = 1;
  await page.click(`.select2-results__option:nth-child(${optionIndex + 1})`);

  await page.getByRole('list').first().click();
  await page.click('div#list_tag_chosen ul.chosen-results li[data-option-array-index="3"]');
  await BasicFunctions.showTemporaryMessage(page, `Cet Article ne devrait être vu que par ${process.env.USER_BACKEND_ID_ADMIN} puisque l'on as mis le statut PRIVATE. Cet article aura que deux articles associés avec un Test n°${numberOfTest}.`);
  await delay(2000);

  await page.click('input.button[name="save"]');
  await page.click('a#article.tab');
}

/*
 * STEP 7 : User create an article
 */
async function CreateArticleTagsUser(page) {
  await delay(1500);
  await page.locator("#mainmenua_lareponse").click();
  await page.getByRole("link", { name: "Nouvel article" }).click();
  await page.locator("#title").fill(article_title4);
  await page.click('input.button[name="add"]');

  await page.click('span#select2-private-container');
  const optionIndex = 1;
  await page.click(`.select2-results__option:nth-child(${optionIndex + 1})`);
  await page.getByRole('list').first().click();
  await page.click('div#list_tag_chosen ul.chosen-results li[data-option-array-index="2"]');
  await page.click('input.button[name="save"]');
  await page.click('a#article.tab');

}

/*
 * STEP 8 : Display the article that User can see
 */
async function CreateArticlesForUser(page) {
  await delay(1500);
  await page.locator("#mainmenua_lareponse").click();
  await page.getByRole("link", { name: "Liste articles" }).click();
  await page.getByRole('link', { name: ' ' + article_title1 }).click();
  await page.click('a#article.tab');
  await delay(1500);
  let badgeElement = await page.$('.badge.marginleftonlyshort');
  let badgeValue = await badgeElement.textContent();
  await BasicFunctions.showTemporaryMessage(page, `Nous avons de base ${parseInt(badgeValue) - 1} articles associés pour ces tags avant création de cet article. On rappelle que l'on veut ${expectedBadgeUserValue} articles associés pour l'utilisateur ${process.env.USER_BACKEND_ID_USER} avec le numéro de test ${numberOfTest}.`);
}


async function getArticle(page) {
  await delay(1500);
  let badgeElement = await page.$('.badge.marginleftonlyshort');
  let badgeValue = await badgeElement.textContent();
  return parseInt(badgeValue.trim()) - 1;

}

/*
 * STEP 9 : Verification of associated articles for Admin Account
 */
async function verificationAdminArticle(page, count) {
  await delay(1500);
  await page.locator("#mainmenua_lareponse").click();
  await page.getByRole("link", { name: "Liste articles" }).click();
  await page.getByRole('link', { name: ' ' + article_title1 }).click();
  await page.click('a#article.tab');
  await delay(1500);
  let badgeElement = await page.$('.badge.marginleftonlyshort');
  let badgeValue = await badgeElement.textContent();
  let countList = count + expectedBadgeAdminValue;

  await expect(badgeValue.trim()).toBe(countList.toString());
  await expect(parseInt(badgeValue.trim()) - count).toEqual(expectedBadgeAdminValue);
  if (countList - count == expectedBadgeAdminValue) {
    await BasicFunctions.showTemporaryMessage(page, `Nous avons bien ${expectedBadgeAdminValue} articles associés pour l'utilisateur ${process.env.USER_BACKEND_ID_ADMIN} avec le numéro de test ${numberOfTest}.`);
  } else {
    await BasicFunctions.showTemporaryMessage(page, `Nous devrions avoir ${expectedBadgeAdminValue} articles associés pour l'utilisateur ${process.env.USER_BACKEND_ID_ADMIN}, mais nous en avons ${countList - count} avec le numéro de test ${numberOfTest}.`);
  }
}

/*
 * STEP 10 : Verification of associated articles for User Account
 */
async function verificationUserArticle(page, count) {
  await delay(1500);
  let badgeElement = await page.$('.badge.marginleftonlyshort');
  let badgeValue = await badgeElement.textContent();
  let countList = count + expectedBadgeUserValue;
  await expect(parseInt(badgeValue.trim())).toBe(countList);
  await expect(parseInt(badgeValue.trim()) - count).toEqual(expectedBadgeUserValue);
  if (parseInt(badgeValue.trim()) - count == expectedBadgeUserValue) {
    await BasicFunctions.showTemporaryMessage(page, `Nous avons bien ${expectedBadgeUserValue} articles associés pour l'utilisateur ${process.env.USER_BACKEND_ID_USER} avec le numéro de test ${numberOfTest}.`);
  } else {
    await BasicFunctions.showTemporaryMessage(page, `Nous devrions avoir ${expectedBadgeUserValue} articles associés pour l'utilisateur ${process.env.USER_BACKEND_ID_USER}, mais nous en avons ${countList - count} avec le numéro de test ${numberOfTest}.`);
  }
}

/*
 * STEP 11 : Delete All articles
 */
async function deleteAllArticles(page) {
  await page.locator("#mainmenua_lareponse").click();
  await page.getByRole("link", { name: "Liste articles" }).click();
  await page.locator('#checkforselects').check();
  await page.click('span#select2-massaction-container');
  const optionIndex = 1;
  await page.click(`.select2-results__option:nth-child(${optionIndex + 1})`);
  const button1 = await page.waitForSelector('input[name="confirmmassaction"]');
  await button1.click();
  await page.selectOption('select[name="confirm"]', 'yes');
  const button2 = await page.waitForSelector('input.button.valignmiddle.confirmvalidatebutton.small');
  await button2.click();
}


test("Verification articles associés et badge", async ({ page }) => {
  test.setTimeout(120000);
  await BasicFunctions.authenticate(page, process.env.DOLIBARR_URL_BACKEND, process.env.USER_BACKEND_ID_ADMIN, process.env.USER_BACKEND_PASSWORD_ADMIN); // On se connecte avec Admin
  await CreateArticleTwoTagAdmin(page); // On crée un article 1 avec deux tags numéro 1 et numéro 2 avec le statut INTERNAL
  let count1 = await getArticle(page); // Sur ce meme article, on va GET le nombre d'article associés avant la création de cet article pour avoir le nombre n - 1 

  await CreateArticleTagOneAdmin(page); // On crée un article 2, avec un tag numéro 1 avec le statut PRIVATE
  await CreateArticleTagTwoAdmin(page); // On crée un article 3, avec un tag numéro 2 avec le statut PRIVATE
  await BasicFunctions.logout(page); // Admin se déconnecte

  await BasicFunctions.authenticate(page, process.env.DOLIBARR_URL_BACKEND, process.env.USER_BACKEND_ID_USER, process.env.USER_BACKEND_PASSWORD_USER); // On se connecte avec User
  await CreateArticlesForUser(page);  // On va sur le listing d'un article et normalement il y a un Article 1 qui est associé
  let count2 = await getArticle(page); // Sur ce meme article, on va GET le nombre d'article associés avant la création de cet article pour avoir le nombre n - 1 
  await CreateArticleTagsUser(page); // On crée un article 4, avec un tag numéro 1 avec le statut PRIVATE

  await verificationUserArticle(page, count2); // On vérifié qu'on as bien le nombre d'articles associés pour ce test qui doit être de 2 pour User
  await BasicFunctions.logout(page); // User se déconnecte

  await BasicFunctions.authenticate(page, process.env.DOLIBARR_URL_BACKEND, process.env.USER_BACKEND_ID_ADMIN, process.env.USER_BACKEND_PASSWORD_ADMIN); // On se connecte avec Admin
  await verificationAdminArticle(page, count1); // On vérifié qu'on as bien le nombre d'articles associés pour ce test qui doit être de 3 pour Admin
  if (deleteArticle == 1) { // Si la valeur est de 1, alors on supprime tout les articles pour Admin et User
    await deleteAllArticles(page); // On supprime tout les articles pour Admin
    await BasicFunctions.logout(page);  // Admin se déconnecte
    await BasicFunctions.authenticate(page, process.env.DOLIBARR_URL_BACKEND, process.env.USER_BACKEND_ID_USER, process.env.USER_BACKEND_PASSWORD_USER); // On se connecte avec User
    await deleteAllArticles(page); // On supprime tout les articles pour User
  }

});


