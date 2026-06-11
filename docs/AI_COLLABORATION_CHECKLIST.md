# ✅ AI Collaboration Checklist

**Use this checklist for EVERY AI interaction**  
**Ensures code quality + prevents chaos**  
**Date**: June 7, 2026

---

## 📋 Pre-Interaction Checklist

**Before asking AI to do anything, prepare:**

- [ ] I know which domain this belongs to (Order, Product, Auth, etc.)
- [ ] I have ARCHITECTURE.md rules fresh in mind
- [ ] I have the AI prompt template ready
- [ ] I know if I want documentation or not
- [ ] I know where documentation should go (if needed)

---

## 🤖 Prompt Checklist

**Include in EVERY prompt to AI:**

### ✅ Code Rules Section
- [ ] Included: "NO database enums"
- [ ] Included: "Store scoping mandatory"
- [ ] Included: "DTOs with storeId first"
- [ ] Included: "Thin controllers (10-15 lines)"
- [ ] Included: "Authorization ONLY in Policies"
- [ ] Included: "Actions NEVER check auth"
- [ ] Included: "Repository for DB access"
- [ ] Included: "Business logic in Actions"
- [ ] Included: "Validation in FormRequests"
- [ ] Included: "Localization with __()"
- [ ] Included: "ApiResponserTrait for responses"
- [ ] Included: "Domain-first folder structure"
- [ ] Included: "ErrorCode enum"

### ✅ Documentation Rules Section
- [ ] Included: "NO files outside docs/"
- [ ] Included: "NO files in project root"
- [ ] Included: "NO files in code folders"
- [ ] Included: "ASK before creating docs"
- [ ] Included: "Use UPPERCASE_NAMING.md"
- [ ] Specified: Whether docs are needed or not

### ✅ Task Details Section
- [ ] Specified: Domain name
- [ ] Specified: Endpoint (if applicable)
- [ ] Listed: Clear requirements
- [ ] Included: "Confirm before implementing"

---

## 🔍 AI Response Verification

**When AI responds, verify:**

### Architecture Compliance
- [ ] AI confirmed domain structure
- [ ] AI listed files to create
- [ ] AI showed implementation plan
- [ ] AI is waiting for approval
- [ ] Plan follows Golden Path
- [ ] No architecture violations visible

### Documentation Handling
- [ ] AI asked about documentation (if ambiguous)
- [ ] AI proposed correct location (if creating docs)
- [ ] AI used UPPERCASE_NAMING format
- [ ] No mention of files in wrong locations

---

## 💻 Code Output Verification

**After AI provides code, check:**

### Database Layer
- [ ] No `enum()` in migrations
- [ ] String columns for enum-backed fields
- [ ] Foreign keys defined
- [ ] Indexes on store_id where needed

### Models
- [ ] Enum casting in $casts
- [ ] No business logic
- [ ] Relationships defined
- [ ] Fillable/guarded set

### Enums
- [ ] PHP enum created (not database)
- [ ] String or int backed
- [ ] values() method available

### DTOs
- [ ] storeId is FIRST parameter
- [ ] All properties typed
- [ ] fromRequest() factory provided
- [ ] Immutable structure

### Repositories
- [ ] ALL queries have where('store_id', $storeId)
- [ ] Only database access
- [ ] No business logic
- [ ] Returns Models or Collections

### Actions
- [ ] Single responsibility
- [ ] Accepts DTO
- [ ] NO Auth::user() or auth()
- [ ] NO authorization logic
- [ ] Uses repositories
- [ ] Returns Model or value

### Controllers
- [ ] Thin (10-15 lines)
- [ ] Uses FormRequest
- [ ] Calls $this->authorize()
- [ ] Creates DTO from request + route
- [ ] Calls Action
- [ ] Uses ApiResponserTrait
- [ ] In Api/ subfolder

### Policies
- [ ] Authorization logic here
- [ ] Store scoping checked
- [ ] Returns boolean
- [ ] Called by controller

### FormRequests
- [ ] Validation rules defined
- [ ] Enums validated correctly
- [ ] Messages localized
- [ ] authorize() returns true

### Resources
- [ ] Transforms model to API
- [ ] No business logic
- [ ] Consistent structure

### Error Handling
- [ ] Uses ErrorCode enum
- [ ] Custom exceptions extend BaseApiException
- [ ] Messages localized
- [ ] Proper HTTP codes

### Localization
- [ ] All messages use __()
- [ ] Keys in lang/en/ and lang/ar/
- [ ] Keys follow convention

### Folder Structure
- [ ] Files in domain folders
- [ ] Domain BEFORE type
- [ ] Correct subfolder (Admin, etc.)

---

## 📁 File Location Verification

**Check no random files created:**

```bash
# Run this command to find misplaced files
find laratenant-backend -name "*.md" -not -path "*/docs/*" -not -name "README.md" -not -name "composer.json"
```

**Should return EMPTY or only expected root files.**

- [ ] No .md files in project root (except README.md)
- [ ] No .md files in app/
- [ ] No .md files in database/
- [ ] No .md files in routes/
- [ ] All docs in docs/[category]/

---

## 📝 Documentation Verification

**If AI created documentation:**

- [ ] File is in docs/[category]/ folder
- [ ] Category is correct (fixes, features, etc.)
- [ ] Filename is UPPERCASE_WITH_UNDERSCORES.md
- [ ] Filename is descriptive (not generic)
- [ ] Content is professional
- [ ] No temporary names (temp.md, notes.md)

---

## 🚫 Violation Response Checklist

**If you find ANY violation:**

- [ ] Point out the specific violation
- [ ] Reference the rule from ARCHITECTURE.md
- [ ] Demand immediate correction
- [ ] DO NOT accept "it's close enough"
- [ ] Make AI fix before proceeding
- [ ] Add clearer rules to next prompt

**Example violation response:**
```
❌ VIOLATION: You created app/Actions/CreateProduct.php
instead of app/Actions/Product/CreateProductAction.php

RULE: Domain-first structure is MANDATORY.
Files must be in app/Actions/{Domain}/ folders.

FIX REQUIRED:
1. Delete: app/Actions/CreateProduct.php
2. Create: app/Actions/Product/CreateProductAction.php

Do this now before continuing.
```

---

## ✅ Acceptance Checklist

**Only accept AI's work when ALL these are true:**

### Code Quality
- [ ] All architecture rules followed
- [ ] Store scoping present everywhere
- [ ] Authorization only in Policies
- [ ] DTOs structured correctly
- [ ] Controllers are thin
- [ ] Folder structure correct
- [ ] Localization used
- [ ] Error codes used

### Documentation
- [ ] No files in wrong locations
- [ ] All docs in correct categories
- [ ] Filenames follow convention
- [ ] Content is professional

### Completeness
- [ ] All requested features implemented
- [ ] No shortcuts taken
- [ ] No "TODO" comments
- [ ] No placeholder code
- [ ] Ready for testing

---

## 🔄 Post-Acceptance Actions

**After accepting AI's work:**

- [ ] Test the implementation
- [ ] Run relevant tests
- [ ] Check for regressions
- [ ] Verify in browser (if applicable)
- [ ] Document any issues found
- [ ] Update team if significant change

---

## 📊 Session Summary Checklist

**At end of AI collaboration session:**

- [ ] All tasks completed
- [ ] All violations corrected
- [ ] No random files created
- [ ] Code is architecture-compliant
- [ ] Documentation (if any) is properly placed
- [ ] Tests passing (if applicable)
- [ ] Ready to commit/deploy

---

## 🎯 Quick Reference

### When AI Creates File in Wrong Location:
```
❌ STOP
Point out violation
Reference rule
Demand correction
Add rule to next prompt
```

### When AI Violates Architecture:
```
❌ REJECT
Show the violation
Show the correct way
Make AI fix
Continue only after fix
```

### When Unsure:
```
✋ PAUSE
Check ARCHITECTURE.md
Check AI_RULES_ENFORCEMENT_SYSTEM.md
Ask AI to explain approach
Verify against rules
Then proceed
```

---

## 📚 Quick Links

**Keep these open during AI collaboration:**

- **Architecture Rules**: [ARCHITECTURE.md](./ARCHITECTURE.md)
- **Prompt Template**: [AI_PROMPT_TEMPLATE.md](./AI_PROMPT_TEMPLATE.md)
- **Enforcement Guide**: [AI_RULES_ENFORCEMENT_SYSTEM.md](./AI_RULES_ENFORCEMENT_SYSTEM.md)
- **Documentation Rules**: [AI_DOCUMENTATION_RULES.md](./AI_DOCUMENTATION_RULES.md)

---

## 💡 Pro Tips

1. **Print this checklist** - Keep it visible while working with AI
2. **Check off items** as you verify them
3. **Be strict** - No exceptions to rules
4. **Be consistent** - Use checklist every time
5. **Train AI** - Point out violations immediately

---

## 🎓 Success Criteria

You're successfully collaborating with AI when:

- [ ] AI always confirms before implementing
- [ ] AI never violates architecture rules
- [ ] AI asks about documentation location
- [ ] No random files are created
- [ ] Code quality is consistently high
- [ ] You catch violations immediately
- [ ] You enforce rules strictly

---

## 📈 Track Your Progress

**Session Tracking:**
```
Date: ___________
Task: ___________
Violations Found: ___
Documentation Created: ___
Architecture Compliance: ☐ Yes ☐ No
Files in Wrong Location: ☐ Yes ☐ No
Overall Quality: ☐ Excellent ☐ Good ☐ Needs Work
```

---

## 🎯 Remember

### The Golden Rules:
1. **Always use the prompt template**
2. **Always verify output**
3. **Always reject violations**
4. **Always be consistent**
5. **Never compromise on quality**

### The Result:
**High-quality code + Clean organization + Happy team** 🚀

---

**Created**: June 7, 2026  
**Purpose**: Systematic AI collaboration checklist  
**Status**: Use for every AI interaction  
**Authority**: ARCHITECTURE.md + All AI guides

---

**Print this, keep it visible, check items off, maintain quality!** ✅
