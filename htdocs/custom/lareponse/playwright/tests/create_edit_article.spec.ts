import { expect, test } from "@playwright/test";
import * as dotenv from "dotenv";
dotenv.config({ path: require("find-config")(".env") });

// Génération d'un titre d'article suivi dans nombre aléatoire
let articleTitle = "[PlayWright] Article N°" + Math.floor(Math.random() * 3712478493);
let articleTitleReverse = articleTitle.split("").reverse().join("");

function delay(time) {
  return new Promise(function (resolve) {
    setTimeout(resolve, time);
  });
}

/*
 * STEP 1 : Authentication with Admin acccount
 */
async function authenticateAdmin(page) {
  await page.goto(`${process.env.DOLIBARR_URL_BACKEND}`);
  await page
    .getByPlaceholder("Identifiant")
    .fill(`${process.env.USER_BACKEND_ID_ADMIN}`);
  await page.getByPlaceholder("Mot de passe").click();
  await page
    .getByPlaceholder("Mot de passe")
    .fill(`${process.env.USER_BACKEND_PASSWORD_ADMIN}`);
  page.on("console", (msg) => {
    console.log(msg);
  });

  await page.getByPlaceholder("Code sécurité").click();
}

/*
 * STEP 2 : Create a Article
 */
async function createArticle(page) {
  await delay(3000);
  await page.locator("#mainmenua_lareponse").click();
  await page.getByRole("link", { name: "Nouvel article" }).click();
  await page.locator("#title").fill(articleTitle);


  await page.getByRole("button", { name: "Créer" }).click();
  await page.getByRole("button", { name: "Enregistrer" }).click();
}

/*
 * STEP 3 : Logout
 */
async function logout(page) {
  await delay(3000);
  await page.goto(`${process.env.DOLIBARR_URL_BACKEND}/user/logout.php`);
}

/*
 * STEP 4 : authentication with User account
 */
async function authenticateUser(page) {
  await page.goto(`${process.env.DOLIBARR_URL_BACKEND}`);
  await page
    .getByPlaceholder("Identifiant")
    .fill(`${process.env.USER_BACKEND_ID_USER}`);
  await page.getByPlaceholder("Mot de passe").click();
  await page
    .getByPlaceholder("Mot de passe")
    .fill(`${process.env.USER_BACKEND_PASSWORD_USER}`);
  page.on("console", (msg) => {
    console.log(msg);
  });

  await page.getByPlaceholder("Code sécurité").click();
}

/*
 * STEP 5 : Select Article
 */
async function selectArticle(page) {
  await delay(3000);
  await page.locator("#mainmenua_lareponse").click();
  await page.getByRole('link', { name: ' Liste articles' }).click()
  await page.getByPlaceholder('Recherche dans les titres ou dans le contenu').fill(articleTitle);
  await page.getByRole('button', { name: '' }).click();

  await page.getByRole('link', { name: ' '+articleTitle }).click();
}

/*
 * STEP 6 : Select Article
 */
async function editArticle(page) {
  await delay(3000);
 
  await page.getByRole('link', { name: ' Modifier' }).click();

  await page.frameLocator('iframe[title="Éditeur de texte enrichi, content"]').locator('html').click();
  await page.frameLocator('iframe[title="Éditeur de texte enrichi, content"]').locator('body').fill(articleTitleReverse);

  await page.getByRole('button', { name: 'Enregistrer' }).click();
}

/*
 * STEP 7 : Verification
 */
async function verification(page) {
  await delay(3000);
  await page.locator('//*[@id="id-right"]/div/div[2]/div[1]/div/div[2]/table/tbody/tr[2]/td[1]/a/div[2]').highlight();
}


test("login et creation article", async ({ page }) => {
  test.setTimeout(120000);
  await authenticateAdmin(page);
  await createArticle(page);

  await page.getByText(`${articleTitle}`).highlight();
});

test("login, creation et modification d'un article", async ({ page }) => {
  test.setTimeout(120000);
  await authenticateAdmin(page);
  await createArticle(page);
  await logout(page);
  await authenticateUser(page);
  await selectArticle(page);
  await editArticle(page);
  await verification(page);
});

