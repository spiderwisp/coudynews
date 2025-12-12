const puppeteer = require('puppeteer');

async function scrapeDockets(county, startDate, endDate) {
    const browser = await puppeteer.launch({
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox'],
        protocolTimeout: 180000
    });
    
    try {
        const page = await browser.newPage();
        await page.setViewport({ width: 1920, height: 1080 });
        
        // Navigate to search page
        await page.goto('https://ujsportal.pacourts.us/CaseSearch', {
            waitUntil: 'networkidle2',
            timeout: 60000
        });
        
        // Wait for page to fully load
        await new Promise(resolve => setTimeout(resolve, 3000));
        
        // Find and select "Date Filed" using Puppeteer's native select method
        await page.evaluate(() => {
            const selects = document.querySelectorAll('select');
            for (let select of selects) {
                for (let option of select.options) {
                    if (option.text.includes('Date Filed')) {
                        select.value = option.value;
                        select.dispatchEvent(new Event('change', { bubbles: true }));
                        return;
                    }
                }
            }
        });
        
        await new Promise(resolve => setTimeout(resolve, 2000));
        
        // Check "Advanced Search" using evaluate (more reliable)
        await page.evaluate(() => {
            const checkbox = document.querySelector('input[name="AdvanceSearch"]');
            if (checkbox && !checkbox.checked) {
                checkbox.click();
            }
        });
        
        await new Promise(resolve => setTimeout(resolve, 2000));
        
        // Fill dates using evaluate (more reliable)
        await page.evaluate((startDate, endDate) => {
            const startInput = document.querySelector('input[name="FiledStartDate"]');
            if (startInput) {
                startInput.value = startDate;
                startInput.dispatchEvent(new Event('input', { bubbles: true }));
                startInput.dispatchEvent(new Event('change', { bubbles: true }));
            }
            
            const endInput = document.querySelector('input[name="FiledEndDate"]');
            if (endInput) {
                endInput.value = endDate;
                endInput.dispatchEvent(new Event('input', { bubbles: true }));
                endInput.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }, startDate, endDate);
        
        // Select county
        await page.evaluate((countyName) => {
            const selects = document.querySelectorAll('select');
            for (let select of selects) {
                for (let option of select.options) {
                    if (option.text.toLowerCase().includes(countyName.toLowerCase()) && 
                        (option.text.includes('Potter') || option.text.includes('Tioga') || option.text.includes('McKean'))) {
                        select.value = option.value;
                        select.dispatchEvent(new Event('change', { bubbles: true }));
                        return;
                    }
                }
            }
        }, county);
        
        await new Promise(resolve => setTimeout(resolve, 2000));
        
        // Find and click the search button using Puppeteer's native methods
        // First, find the button by evaluating
        const buttonSelector = await page.evaluate(() => {
            const buttons = Array.from(document.querySelectorAll('button, input[type="submit"]'));
            for (let button of buttons) {
                const text = (button.textContent || button.value || '').toLowerCase();
                if (text.includes('search') && !text.includes('clear')) {
                    // Return a selector for this button
                    if (button.id) return '#' + button.id;
                    if (button.name) return `[name="${button.name}"]`;
                    if (button.className) return '.' + button.className.split(' ')[0];
                    // Return a unique selector
                    const index = Array.from(button.parentElement.children).indexOf(button);
                    return `${button.tagName.toLowerCase()}:nth-child(${index + 1})`;
                }
            }
            return null;
        });
        
        if (buttonSelector) {
            try {
                await page.waitForSelector(buttonSelector, { timeout: 5000 });
                await page.click(buttonSelector);
                console.error('Clicked search button using selector:', buttonSelector);
            } catch (e) {
                console.error('Failed to click button, trying evaluate method:', e.message);
                // Fallback to evaluate
                await page.evaluate(() => {
                    const buttons = Array.from(document.querySelectorAll('button, input[type="submit"]'));
                    for (let button of buttons) {
                        const text = (button.textContent || button.value || '').toLowerCase();
                        if (text.includes('search') && !text.includes('clear')) {
                            button.click();
                            return;
                        }
                    }
                });
            }
        } else {
            // Fallback: submit form directly
            await page.evaluate(() => {
                const form = document.querySelector('form');
                if (form) form.submit();
            });
        }
        
        // Wait for AJAX to complete - the form submits via AJAX, not full page reload
        await new Promise(resolve => setTimeout(resolve, 5000));
        
        // Wait for results - check multiple times
        let pdfLinks = [];
        const maxWaitIterations = 60;
        
        // Initial wait for page to process
        await new Promise(resolve => setTimeout(resolve, 5000));
        
        for (let i = 0; i < maxWaitIterations; i++) {
            await new Promise(resolve => setTimeout(resolve, 2000));
            
            // Check page state
            const pageState = await page.evaluate(() => {
                const table = document.getElementById('caseSearchResultGrid');
                return {
                    hasTable: table !== null,
                    tableVisible: table && table.offsetParent !== null,
                    url: window.location.href,
                    hasForm: document.querySelector('form') !== null,
                };
            });
            
            // Search for PDF links
            pdfLinks = await page.evaluate(() => {
                const links = [];
                const allLinks = document.querySelectorAll('a');
                allLinks.forEach(link => {
                    const href = link.getAttribute('href');
                    if (href && href.includes('MdjDocketSheet')) {
                        links.push(href);
                    }
                });
                return links;
            });
            
            if (pdfLinks.length > 0) {
                console.error('Found ' + pdfLinks.length + ' PDF links after ' + (i + 1) + ' iterations');
                break;
            }
            
            // If table exists but no links yet, wait longer
            if (pageState.hasTable && pdfLinks.length === 0) {
                await new Promise(resolve => setTimeout(resolve, 5000));
                pdfLinks = await page.evaluate(() => {
                    const links = [];
                    const allLinks = document.querySelectorAll('a');
                    allLinks.forEach(link => {
                        const href = link.getAttribute('href');
                        if (href && href.includes('MdjDocketSheet')) {
                            links.push(href);
                        }
                    });
                    return links;
                });
                if (pdfLinks.length > 0) {
                    console.error('Found ' + pdfLinks.length + ' PDF links after additional wait');
                    break;
                }
            }
            
            // Log progress every 10 iterations
            if ((i + 1) % 10 === 0) {
                console.error('Still waiting... iteration ' + (i + 1) + ', hasTable: ' + pageState.hasTable + ', url: ' + pageState.url);
            }
        }
        
        if (pdfLinks.length === 0) {
            const finalState = await page.evaluate(() => {
                return {
                    url: window.location.href,
                    hasTable: document.getElementById('caseSearchResultGrid') !== null,
                    allLinks: Array.from(document.querySelectorAll('a')).map(a => a.href).filter(h => h.includes('MdjDocketSheet')).length,
                    bodyText: document.body.innerText.substring(0, 500)
                };
            });
            console.error('No PDF links found. Final state:', JSON.stringify(finalState));
        }
        
        // Extract docket numbers from links
        const dockets = [];
        const uniqueDockets = new Set();
        
        for (const href of pdfLinks) {
            const match = href.match(/docketNumber=([^&]+)/);
            if (match) {
                const docketNumber = decodeURIComponent(match[1]);
                if (!uniqueDockets.has(docketNumber)) {
                    uniqueDockets.add(docketNumber);
                    const fullUrl = href.startsWith('http') ? href : `https://ujsportal.pacourts.us${href}`;
                    dockets.push({
                        docket_number: docketNumber,
                        pdf_url: fullUrl
                    });
                }
            }
        }
        
        return dockets;
        
    } catch (error) {
        console.error('Scraping error:', error.message);
        return [];
    } finally {
        await browser.close();
    }
}

// Get arguments from command line
const args = process.argv.slice(2);
if (args.length < 3) {
    console.error('Usage: node scraper.js <county> <startDate> <endDate>');
    process.exit(1);
}

const [county, startDate, endDate] = args;

scrapeDockets(county, startDate, endDate)
    .then(dockets => {
        console.log(JSON.stringify(dockets));
    })
    .catch(error => {
        console.error('Error:', error);
        process.exit(1);
    });
