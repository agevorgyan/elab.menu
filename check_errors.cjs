const puppeteer = require('puppeteer');

(async () => {
  const browser = await puppeteer.launch();
  const page = await browser.newPage();
  
  page.on('pageerror', error => {
    console.log('PageError:', error.message);
  });
  
  page.on('error', error => {
    console.log('Error:', error.message);
  });

  // Login
  await page.goto('http://127.0.0.1:8000/login');
  await page.type('input[name="email"]', 'admin@example.com'); // change if needed
  await page.type('input[name="password"]', 'password');
  await page.click('button[type="submit"]');
  await page.waitForNavigation();

  // Go to orders
  await page.goto('http://127.0.0.1:8000/admin/orders');
  
  // Wait to capture errors
  await new Promise(r => setTimeout(r, 2000));
  await browser.close();
})();
