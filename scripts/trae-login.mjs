#!/usr/bin/env node

import { spawn, execSync } from 'node:child_process';
import { chromium } from 'playwright';
import { readFileSync, readdirSync, statSync, existsSync } from 'node:fs';
import { homedir } from 'node:os';
import { join } from 'node:path';
import { setTimeout as sleep } from 'node:timers/promises';
import WebSocket from 'ws';

const USER_DATA_DIR = join(homedir(), '.config', 'Trae-atomic4');
const LOGS_DIR = join(USER_DATA_DIR, 'logs');
const JWT_FILE = join(USER_DATA_DIR, 'trae-jwt-token');
const DEBUG_PORT = 9222;
const CDP_HTTP = `http://127.0.0.1:${DEBUG_PORT}`;

const EMAIL = 'moayadalmidani4@atomicmail.io';
const PASSWORD = '0948806134@@m';

const LOG = '[trae-login]';
function info(...args) { console.log(LOG, ...args); }

async function waitFor(fn, timeout = 60000, interval = 500) {
  const start = Date.now();
  while (Date.now() - start < timeout) {
    const r = await fn();
    if (r !== undefined && r !== null && r !== false) return r;
    await sleep(interval);
  }
  throw new Error(`Timed out after ${timeout}ms`);
}

// === CDP helpers ===

function cdpCall(ws, method, params = {}) {
  return new Promise((resolve, reject) => {
    const id = Math.floor(Math.random() * 1e9);
    const handler = (data) => {
      const parsed = JSON.parse(data.toString());
      if (parsed.id === id) {
        ws.off('message', handler);
        if (parsed.error) reject(new Error(parsed.error.message));
        else resolve(parsed.result);
      }
    };
    ws.on('message', handler);
    ws.send(JSON.stringify({ id, method, params }));
    setTimeout(() => { ws.off('message', handler); reject(new Error('CDP timeout')); }, 10000);
  });
}

async function cdpEval(ws, expression) {
  const result = await cdpCall(ws, 'Runtime.evaluate', {
    expression,
    returnByValue: true,
    awaitPromise: true,
  });
  return result.result?.value;
}

async function findAndConnectWorkbench() {
  return waitFor(async () => {
    try {
      const resp = await fetch(`${CDP_HTTP}/json/list`, { signal: AbortSignal.timeout(2000) });
      const targets = await resp.json();
      for (const t of targets) {
        if (t.type === 'page' && t.webSocketDebuggerUrl) {
          // Try connecting and checking page content
          try {
            const ws = new WebSocket(t.webSocketDebuggerUrl);
            const connected = await new Promise((resolve, reject) => {
              ws.on('open', () => resolve(true));
              ws.on('error', reject);
              setTimeout(() => reject(new Error('WS timeout')), 3000);
            });
            if (!connected) continue;

            await cdpCall(ws, 'Runtime.enable');
            await cdpCall(ws, 'Page.enable');

            // Check if this is the workbench by looking for login button or title
            const title = await cdpEval(ws, 'document.title');
            if (title === 'Trae' || (t.url && t.url.includes('workbench'))) {
              info('Workbench target found via:', title || t.url);
              return ws;
            }

            ws.close();
          } catch { /* try next target */ }
        }
      }
    } catch {}
    return null;
  }, 90000, 1000);
}

async function waitForReady(ws) {
  await waitFor(async () => {
    const state = await cdpEval(ws, 'document.readyState');
    return state === 'complete';
  }, 30000, 500);
  info('Page fully loaded');
}

async function waitForLoginButton(ws) {
  return waitFor(async () => {
    const exists = await cdpEval(ws,
      'document.querySelector(".no-login-welcome__button") !== null'
    );
    return exists;
  }, 30000, 1000);
}

async function isLoggedIn(ws) {
  const btnExists = await cdpEval(ws,
    'document.querySelector(".no-login-welcome__button") !== null'
  );
  return !btnExists;
}

// === Log helpers ===

function getSessions() {
  if (!existsSync(LOGS_DIR)) return new Set();
  return new Set(readdirSync(LOGS_DIR).filter(d => !d.startsWith('.')));
}

function readLines(filePath, knownSize) {
  if (!existsSync(filePath)) return { lines: [], size: 0 };
  const stats = statSync(filePath);
  if (stats.size <= knownSize) return { lines: [], size: stats.size };
  const content = readFileSync(filePath, 'utf-8');
  return {
    lines: content.slice(knownSize).split('\n').filter(l => l.trim()),
    size: stats.size,
  };
}

// === Main ===

async function main() {
  info('=== Trae IDE Login Automation ===');

  // Kill process on port 9222 if it's not the atomic4 variant
  try {
    const pid = execSync(`fuser ${DEBUG_PORT}/tcp 2>/dev/null`, { encoding: 'utf-8' }).trim();
    if (pid) {
      const cmdline = execSync(`cat /proc/${pid}/cmdline 2>/dev/null | tr '\\0' ' '`, { encoding: 'utf-8' }).trim();
      if (!cmdline.includes('Trae-atomic4')) {
        info(`Killing process ${pid} on port ${DEBUG_PORT} (not atomic4)...`);
        execSync(`kill ${pid} 2>/dev/null`, { stdio: 'ignore' });
        await sleep(2000);
      } else {
        info('Trae-atomic4 already running on port 9222');
      }
    }
  } catch {}

  const beforeSessions = getSessions();

  info('Launching Trae...');
  const trae = spawn('trae', [
    `--user-data-dir=${USER_DATA_DIR}`,
    `--remote-debugging-port=${DEBUG_PORT}`,
  ], { stdio: 'ignore', detached: true });
  trae.unref();

  // Wait for CDP port
  info('Waiting for CDP port...');
  await waitFor(async () => {
    try {
      const r = await fetch(`${CDP_HTTP}/json/list`, { signal: AbortSignal.timeout(1000) });
      return r.ok;
    } catch { return false; }
  }, 90000, 1000);
  info('CDP ready');

  // Wait for log session
  info('Waiting for log session...');
  const session = await waitFor(() => {
    const cur = getSessions();
    const diff = [...cur].filter(d => !beforeSessions.has(d)).sort().reverse();
    return diff.length ? diff[0] : null;
  }, 30000, 500);
  const mainLog = join(LOGS_DIR, session, 'main.log');
  info('Session:', session);

  // Connect to workbench via CDP
  info('Connecting to workbench target...');
  const ws = await findAndConnectWorkbench();
  info('Connected to workbench');

  // Wait for page to be fully loaded
  await waitForReady(ws);
  await sleep(2000); // Extra settle time

  // Check login state by waiting for login button to appear (or timeout = already logged in)
  let hasLoginButton = false;
  try {
    hasLoginButton = await waitForLoginButton(ws);
    info('Login button found');
  } catch {
    info('Login button did not appear — assuming already logged in');
  }

  if (!hasLoginButton) {
    info('Already logged in. Verifying...');
    await verifyLog(mainLog);
    ws.close();
    info('Done.');
    return;
  }

  // Click login
  info('Clicking login button...');
  await cdpEval(ws,
    `document.querySelector('.no-login-welcome__button')?.dispatchEvent(
      new MouseEvent('click', {bubbles:true, cancelable:true, view:window})
    )`
  );
  info('Login button clicked');

  // Watch for OAuth URL in log
  let oauthUrl = null;
  let logSize = 0;

  await waitFor(async () => {
    const { lines, size } = readLines(mainLog, logSize);
    logSize = size;
    for (const line of lines) {
      if (line.includes('OAuthenticator# openLogin getLoginUrl')) {
        const m = line.match(/https?:\/\/[^\s"'<,]+/);
        if (m) { oauthUrl = m[0]; return true; }
      }
    }
    return false;
  }, 60000, 500);
  info('OAuth URL captured');

  // Close CDP to Trae
  ws.close();

  // Handle OAuth in a separate Playwright browser
  await handleOAuth(oauthUrl);

  // Final verification
  await verifyAll(mainLog);

  info('=== Login complete ===');
}

// === OAuth handling via Playwright ===

async function handleOAuth(url) {
  info('Opening OAuth URL in Playwright browser...');
  const browser = await chromium.launch({
    headless: false,
    args: ['--ignore-certificate-errors', '--disable-web-security'],
  });
  const context = await browser.newContext({ ignoreHTTPSErrors: true });
  const page = await context.newPage();

  page.on('console', msg => info('[OAuth]', msg.type(), msg.text()));

  await page.goto(url, { waitUntil: 'networkidle', timeout: 30000 });
  info('OAuth page loaded, url:', page.url());

  await handleSSO(page, browser);

  await sleep(2000);
  await browser.close();
}

async function handleSSO(page, browser) {
  let attempts = 0;

  await waitFor(async () => {
    await sleep(1500);
    attempts++;

    const url = page.url();
    const title = await page.title();
    info(`SSO check #${attempts}: ${title} | ${url}`);

    // === Check for consent page ===
    const consentBtn = page.locator('button:has-text("Log in and open TRAE"), button:has-text("Authorize"), button:has-text("允许")');
    if (await consentBtn.isVisible({ timeout: 1000 }).catch(() => false)) {
      info('Consent page — clicking authorize');
      await consentBtn.click();
      return true;
    }

    // === Check = if callback already made ===
    if (url.includes('/authorize') || url.includes('Login Successful') || url.includes('close the window')) {
      info('Callback already received');
      return true;
    }

    // === Take screenshot for debugging ===
    if (attempts === 1) {
      await page.screenshot({ path: '/tmp/trae-oauth.png' }).catch(() => {});
    }

    // === Handle login form (ByteDance SSO) ===
    // ByteDance uses a multi-step flow: email → next → password → sign in
    const emailInput = page.locator('input[type="email"], input[name="email"], input[autocomplete="email"]').first();
    const passwordInput = page.locator('input[type="password"]').first();

    if (await emailInput.isVisible({ timeout: 500 }).catch(() => false)) {
      const emailValue = await emailInput.inputValue().catch(() => '');
      if (!emailValue) {
        info('Filling email');
        await emailInput.fill(EMAIL);

        // Look for a "next" or "continue" button after email
        const nextBtn = page.locator('button:has-text("Next"), button:has-text("Continue"), button[type="submit"]').first();
        if (await nextBtn.isVisible({ timeout: 500 }).catch(() => false)) {
          await nextBtn.click();
          info('Clicked Next after email');
          await sleep(2000);
          return false;
        }
      }
    }

    if (await passwordInput.isVisible({ timeout: 500 }).catch(() => false)) {
      const pwValue = await passwordInput.inputValue().catch(() => '');
      if (!pwValue) {
        info('Filling password');
        await passwordInput.fill(PASSWORD);

        // Click sign in / log in
        const submitBtn = page.locator('button[type="submit"], button:has-text("Sign in"), button:has-text("Log in"), button:has-text("Login")').first();
        if (await submitBtn.isVisible({ timeout: 500 }).catch(() => false)) {
          await submitBtn.click();
          info('Clicked submit after password');
          await sleep(3000);
          return false;
        }
      }
    }

    // If we have email filled but no password field yet, wait for navigation
    const emailVal = await emailInput.inputValue().catch(() => '');
    if (emailVal && !(await passwordInput.isVisible().catch(() => false))) {
      info('Email filled, waiting for password field...');
      return false;
    }

    return false;
  }, 180000, 2000);
}

// === Verification ===

async function verifyLog(logPath) {
  let size = existsSync(logPath) ? statSync(logPath).size : 0;
  await waitFor(async () => {
    const { lines, size: newSize } = readLines(logPath, size);
    size = newSize;
    for (const line of lines) {
      if (line.includes('updateLocalCredential') && line.includes('userJwt')) {
        info('JWT found in log');
        return true;
      }
    }
    return false;
  }, 30000, 1000);
}

async function verifyAll(logPath) {
  info('=== Verification ===');

  // Check JWT file
  if (existsSync(JWT_FILE)) {
    const jwt = readFileSync(JWT_FILE, 'utf-8').trim();
    if (jwt.length > 0) info('JWT token file present');
  }

  // Check log for JWT
  await verifyLog(logPath);

  // Re-check via CDP that login button is gone
  try {
    const ws2 = await findAndConnectWorkbench();
    await cdpCall(ws2, 'Runtime.enable');
    const btnExists = await cdpEval(ws2,
      'document.querySelector(".no-login-welcome__button") !== null'
    );
    info(btnExists ? 'Login button still present' : 'Verified: login button gone');
    ws2.close();
  } catch {
    info('Could not verify via CDP');
  }

  info('Verification done');
}

process.on('unhandledRejection', (err) => {
  console.error(LOG, 'Fatal:', err);
  process.exit(1);
});

main();
