import { Page, expect, test } from "@playwright/test";
import * as dotenv from "dotenv";
import * as fs from 'fs';

dotenv.config({ path: require("find-config")(".env") });
const numberOfTest = Math.floor(Math.random() * 5672);
let article_title2 = "[PlayWright] Test N°" + numberOfTest + " Article 2 N°" + Math.floor(Math.random() * 3712478493);
const headedMode: number = 1;

const DolibarrFunction = {
  /*
  * Delay Function.
  */
  async delay(time) {
    return new Promise(function (resolve) {
      setTimeout(resolve, time);
    });
  },
  /*
  * Authenticate Function.
  */
  async authenticate(page, url, user, password) {
    await page.goto(`${url}`);
    await page.fill('#username', `${user}`);
    await page.fill('#password', `${password}`);
    page.on("console", (msg) => {
      console.log(msg);
    });
    await page.locator('#login-submit-wrapper').click();
  },


  /*
   * Logout Function
   */
  async logout(page) {
    await DolibarrFunction.delay(1000);
    await page.goto(`${process.env.DOLIBARR_URL_BACKEND}/user/logout.php`);
  },


  /*
  * Display a message on screen.
  */
  async showTemporaryMessage(page: Page, message: string) {
    const messageOverlay = await page.evaluateHandle((msg) => {
      const overlay = document.createElement('div');
      overlay.style.position = 'fixed';
      overlay.style.top = '4%';
      overlay.style.left = '60%';
      overlay.style.width = '200px';
      overlay.style.height = '20px';
      overlay.style.transform = 'translate(-50%, -50%)';
      overlay.style.background = 'rgba(0, 0, 0, 0.7)';
      overlay.style.color = 'white';
      overlay.style.padding = '10px';
      overlay.style.borderRadius = '10px';
      overlay.style.zIndex = '9999';
      overlay.style.fontSize = '20px';
      overlay.innerText = msg;

      document.body.appendChild(overlay);

      return overlay;
    }, message);

    await page.waitForTimeout(2500);
    await messageOverlay.dispose();
  },

  /*
  * Function to check whether there's a 'Warning' error on the page you're on. If there's an error, the test closes and returns an error.
  */
  async checkError(page) {
    await page.waitForLoadState('domcontentloaded');
    const pageContent = await page.innerText('body');
    const warningText = 'Warning';
    const fatalErrorText = 'Fatal Error';
    const warningRegex = new RegExp('(.+?) on line (\\d+)', 'g');
    const fatalErrorRegex = new RegExp(`(.+?) on line (\\d+)`, 'g');
    
    let errorMessages: string[] = [];
    const warningMatches = pageContent.matchAll(warningRegex);
    const fatalErrorMatches = pageContent.matchAll(fatalErrorRegex);
    if (pageContent.includes(warningText) || pageContent.includes(fatalErrorText)) {
      
      if (pageContent.includes(fatalErrorText)) {
        for (const match of fatalErrorMatches) {
          const message = match[1];
          const lineNumber = match[2];
          errorMessages.push(` ${message} on line ${lineNumber}`);
        }
      }
      if (pageContent.includes(warningText)) {
        for (const match of warningMatches) {
          const message = match[1];
          const lineNumber = match[2];
          errorMessages.push(`${message} on line ${lineNumber}`);
        }
      }
      if (headedMode == 1) {
        await DolibarrFunction.showTemporaryMessage(page, `${errorMessages.length} erreurs`);
      }
      page.on("console", (msg) => {
        console.log(errorMessages.length);
      });
      
      fs.writeFile('./test-results/result.txt', errorMessages.join('\n'), (err) => {
        if (err) {
          console.log(err);
        }
        console.log("File saved!");
      });
      page.close();
      throw new Error('Le test a échoué car une erreur a été détectée.');
    } else {
      if (headedMode == 1) {
        await DolibarrFunction.showTemporaryMessage(page, `Pas d'erreur`);
      }
    }
  },

};

export default DolibarrFunction;
