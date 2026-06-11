# 🎯 AI Prompt Template - Copy & Paste

**Use this template for EVERY AI interaction**  
**Prevents code violations + documentation chaos**

---

## 📋 The Complete Template

```
I need to [DESCRIBE YOUR TASK].

🔥 MANDATORY CODE RULES (from ARCHITECTURE.md):

1. NO database enums → use PHP enums
2. ALL queries MUST include where('store_id', $storeId)
3. DTOs REQUIRED, storeId MUST be first parameter
4. Controllers MUST be thin (10-15 lines)
5. Authorization ONLY in Policies (via $this->authorize())
6. Actions MUST NEVER check auth or call Auth::user()
7. Database access ONLY via Repositories
8. Business logic ONLY in Actions
9. Validation ONLY in FormRequests
10. ALL messages MUST use __() localization
11. Responses MUST use ApiResponserTrait
12. Folder structure: Domain BEFORE type (app/Actions/{Domain}/)
13. Error codes MUST use ErrorCode enum

🚫 DOCUMENTATION RULES:

1. NO files outside laratenant-backend/docs/
2. NO files in project root (laratenant-backend/*.md)
3. NO files in code folders (app/**/*.md)
4. ONLY create in docs/[category]/ folders
5. ASK before creating ANY documentation
6. Use UPPERCASE_NAMING.md format

Available categories:
- docs/fixes/         → Bug fixes
- docs/features/      → Feature docs
- docs/implementation/→ Implementation guides
- docs/reports/       → Status reports
- docs/sessions/      → Session logs
- docs/testing/       → Test results

DEFAULT: Don't create documentation unless explicitly asked.

📝 TASK DETAILS:
- Domain: [YOUR DOMAIN]
- Endpoint: [YOUR ENDPOINT IF APPLICABLE]
- Requirements:
  • [REQUIREMENT 1]
  • [REQUIREMENT 2]
  • [REQUIREMENT 3]

🔄 PROCESS:
1. FIRST: Confirm domain structure and files to create
2. THEN: Show implementation plan
3. WAIT for my approval
4. THEN: Implement following Golden Path
5. IF documentation needed: ASK where to put it

NO EXCEPTIONS. FOLLOW STRICTLY.

Begin now.
```

---

## 🚀 Quick Examples

### Example 1: Feature Implementation (No Docs)

```
I need to add a discount code feature to orders.

[PASTE FULL TEMPLATE ABOVE]

TASK DETAILS:
- Domain: Order
- Endpoint: POST /api/v1/merchant/stores/{store}/orders/{order}/apply-discount
- Requirements:
  • Validate discount code exists
  • Apply discount to order
  • Update order total

DOCUMENTATION: None needed, just code.

Begin now.
```

---

### Example 2: Bug Fix (With Docs)

```
I need to fix the login timeout bug.

[PASTE FULL TEMPLATE ABOVE]

TASK DETAILS:
- Domain: Auth
- Endpoint: POST /api/v1/auth/login
- Requirements:
  • Fix session timeout
  • Add proper error message
  • Test with existing tokens

DOCUMENTATION: Yes, create in docs/fixes/FIX_LOGIN_TIMEOUT.md

Begin now.
```

---

### Example 3: Just Asking Questions (No Code/Docs)

```
Explain how the store scoping works in this project.

🚫 RULES:
- NO code changes
- NO file creation
- NO documentation
- ONLY explain

Begin now.
```

---

## 💡 Customization Tips

### When You DON'T Want Documentation:
```
DOCUMENTATION: None needed, just code.
```

### When You DO Want Documentation:
```
DOCUMENTATION: Yes, create in docs/[category]/[FILENAME].md
```

### When Unsure About Documentation:
```
DOCUMENTATION: Ask me after implementation if needed.
```

---

## 📊 What Each Section Does

| Section | Purpose |
|---------|---------|
| **Code Rules** | Ensures architecture compliance |
| **Documentation Rules** | Prevents file chaos |
| **Task Details** | Gives AI context |
| **Process** | Controls AI behavior |
| **Documentation** | Specifies doc requirements |

---

## ✅ Verification Checklist

After AI responds, check:

**Code Compliance:**
- [ ] No database enums
- [ ] Store scoping present
- [ ] DTO structure correct
- [ ] Controller is thin
- [ ] Authorization in Policy

**Documentation Compliance:**
- [ ] No files in project root
- [ ] No files in code folders
- [ ] If docs created, in correct category
- [ ] Filename is descriptive and UPPERCASE

**If any check fails**: Reject and demand fix!

---

## 🎯 Pro Tips

1. **Save this template** in a text file for quick access
2. **Customize** the task details section for each use
3. **Be explicit** about documentation needs
4. **Always verify** AI output
5. **Enforce consistently** - no exceptions

---

## 📚 Related Documents

- **Full Code Rules**: [AI_RULES_ENFORCEMENT_SYSTEM.md](./AI_RULES_ENFORCEMENT_SYSTEM.md)
- **Documentation Rules**: [AI_DOCUMENTATION_RULES.md](./AI_DOCUMENTATION_RULES.md)
- **Architecture**: [ARCHITECTURE.md](./ARCHITECTURE.md)
- **Entry Point**: [00-START-HERE.md](./00-START-HERE.md)

---

## 🚀 Quick Start

1. **Copy** the template above
2. **Fill in** task details
3. **Paste** into AI chat
4. **Verify** output
5. **Done!**

---

**Created**: June 7, 2026  
**Purpose**: Ready-to-use AI prompt template  
**Status**: Copy & paste into every AI interaction

---

**Remember**: Consistency is key! Use this template EVERY time you interact with AI for code tasks. 💪
