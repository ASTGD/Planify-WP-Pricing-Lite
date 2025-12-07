#!/usr/bin/env node
const path = require('node:path');
const fs = require('node:fs/promises');
const { execFile } = require('node:child_process');
const { promisify } = require('node:util');
const { chromium } = require('@playwright/test');

const run = promisify(execFile);
const root = path.resolve(__dirname, '..');
const phpScript = path.join(__dirname, 'render-template-previews.php');
const thumbsDir = path.join(root, 'assets', 'admin', 'img', 'wizard-thumbs');
const tmpDir = path.join(__dirname, '.tmp');

async function ensureDirs() {
    await fs.mkdir(thumbsDir, { recursive: true });
    await fs.mkdir(tmpDir, { recursive: true });
}

async function listTemplates() {
    const { stdout } = await run('php', [phpScript, 'list'], { cwd: root });
    return JSON.parse(stdout);
}

async function renderTemplateHtml(templateId) {
    const htmlPath = path.join(tmpDir, `${templateId}.html`);
    await run('php', [phpScript, templateId, htmlPath], { cwd: root });
    return htmlPath;
}

async function captureScreenshot(browser, templateId, htmlPath) {
    const page = await browser.newPage({ viewport: { width: 1200, height: 900 } });
    await page.goto(`file://${htmlPath}`, { waitUntil: 'load' });
    await page.waitForTimeout(200);
    const target = page.locator('.pwpl-table');
    const screenshotPath = path.join(thumbsDir, `${templateId}.png`);
    await target.screenshot({
        path: screenshotPath,
    });
    await page.close();
    return screenshotPath;
}

async function main() {
    await ensureDirs();
    const templates = await listTemplates();
    if (!templates.length) {
        console.error('No templates available.');
        process.exit(1);
    }
    const browser = await chromium.launch();
    try {
        for (const tpl of templates) {
            const htmlPath = await renderTemplateHtml(tpl.id);
            await captureScreenshot(browser, tpl.id, htmlPath);
            console.log(`Captured thumbnail for ${tpl.id}`);
        }
    } finally {
        await browser.close();
        await fs.rm(tmpDir, { recursive: true, force: true });
    }
}

main().catch((err) => {
    console.error(err);
    process.exit(1);
});
