# Prompt: Automate Trae IDE Login (trae-atomic4)

## Task

Log into **Trae IDE** using account credentials below. Trae blocks AI features until authenticated.

## Credentials

- Email: `moayadalmidani4@atomicmail.io`
- Password: `0948806134@@m`

## Architecture

Trae is an Electron app. It uses an OAuth flow:

1. User clicks "Log in to start" button in the IDE
2. Renderer → IPC → main process constructs an OAuth URL and opens it via `shell.openExternal(url)` → OS browser
3. The OS browser completes ByteDance SSO → redirects to `http://127.0.0.1:{port}/authorize?originCredential={JWT}`
4. Trae's local HTTP server (already listening on that port) captures the JWT → login complete

**Critical**: You cannot capture the URL from CDP alone because `shell.openExternal` is native Electron, not `window.open`. But Trae **logs the URL** in its main process log.

## Instructions

### 1. Launch Trae with Remote Debugging

```bash
trae --user-data-dir ~/.config/Trae-atomic4 --remote-debugging-port=9222
```

Wait for the window to fully load (the workbench page).

### 2. Connect CDP and Click Login Button

- Fetch targets from `http://127.0.0.1:9222/json/list`
- Find the page where URL contains `workbench.html` (NOT the Playwright browser — this is the actual Trae window)
- Connect to its `webSocketDebuggerUrl`
- Enable Runtime domain, then evaluate:

```js
document.querySelector('.no-login-welcome__button')?.dispatchEvent(
  new MouseEvent('click', {bubbles: true, cancelable: true, view: window})
);
```

### 3. Extract OAuth URL from Main Log

Trae writes to `~/.config/Trae-atomic4/logs/{latest_session}/main.log`

Search for: `OAuthenticator# openLogin getLoginUrl`

The line contains the full login URL:
```
https://www.trae.ai/authorization?login_version=1&auth_from=trae&login_channel=native_ide&plugin_version=2.3.29372&auth_type=local&client_id=ono9krqynydwx5&redirect=0&login_trace_id=<uuid>&auth_callback_url=http://127.0.0.1:<port>/authorize&machine_id=<hash>&device_id=<id>&...
```

**Important**: The callback port (e.g. `41301`) is already listening — it's Trae's internal OAuth HTTP server. Keep the URL exactly as-is, including the `auth_callback_url` pointing to that port.

### 4. Open URL in Playwright (NOT OS Browser) — Incognito Mode

Use Playwright (Chromium) to navigate to that exact URL. Do NOT let it open in the OS default browser.

**Critical**: Launch Chromium in **incognito/private mode** (a fresh isolated browser context with no cached cookies or sessions). In Playwright this means using `browser.newContext()` (not `browser.newContext({ storageState: ... })`) — do NOT load any existing storage state. This ensures the login page shows the full sign-in options (GitHub/Google/Email) instead of being auto-authenticated by stale cookies.

### 5. Handle the SSO Page

Take a page snapshot after navigation. The `www.trae.ai/authorization` URL redirects to a login page offering multiple sign-in methods.

**CRITICAL — This is a GitHub account.** Do NOT fill email/password directly on the TRAE login page. Click the **"Continue with GitHub"** button instead.

The login page shows buttons like:
- Continue with **GitHub**
- Continue with **Google**
- Continue with **Email**

→ Click **"Continue with GitHub"**.

After clicking GitHub, the browser redirects to `github.com/login/oauth/authorize`. A GitHub login form appears:

| Field | Value |
|-------|-------|
| Username or email address | `moayadalmidani4@atomicmail.io` |
| Password | `0948806134@@m` |

Fill in both fields and submit. GitHub may also ask for 2FA or to authorize the TRAE OAuth app — authorize it if prompted.

After GitHub OAuth completes, the browser redirects back to the TRAE callback URL (`http://127.0.0.1:<port>/authorize?originCredential=...`). The page should then show **"Login Successful"** / "You can now close the window and go back to use TRAE."

**If** the page shows a consent screen ("Log in to TRAE Desktop App" with "Log in and open TRAE") without asking for GitHub login, simply click that button — it means the Playwright session already has GitHub cookies and the SSO was handled transparently.

### 6. Verify

After clicking consent:

- The Playwright page shows **"Login Successful"** / "You can now close the window and go back to use TRAE."
- Check `main.log` for:
  - `OAuthLocalServer#[Server] Received url request: /authorize`
  - `[updateLocalCredential]` with `userJwt` containing a signed JWT
  - `[updateUserInfo]` with `userId`, tokens, user region
- Via CDP on workbench:
  - `.no-login-welcome__button` should be `null`
  - User avatar element should exist in DOM

## Expected Outcome

- IDE shows user avatar instead of login button
- AI chat, CodeGeeX, Agent features all become functional
- Login persists across restarts (JWT stored in `trae-jwt-token` and localStorage)

## Key File Paths

| What | Path |
|------|------|
| Main process log | `~/.config/Trae-atomic4/logs/{session}/main.log` |
| Renderer log | `~/.config/Trae-atomic4/logs/{session}/window1/renderer.log` |
| JWT token store | `~/.config/Trae-atomic4/trae-jwt-token` |
| User data | `~/.config/Trae-atomic4/User/` |
| Product config | `/usr/share/trae/resources/app/product.json` |
