# 🚀 Deployment Checklist - Hero Banner Gradient Feature

## Pre-Deployment Verification ✅

### Backend Verification
- [x] HeroBanner model has gradient fields (`visual_type`, `gradient_from`, `gradient_to`)
- [x] StorefrontRuntimeService maps gradient fields to API response
- [x] Nullsafe operator used for `visual_type?->value`
- [x] Database contains hero banner with gradient data
- [x] Backend tests pass (if applicable)

### Frontend Verification
- [x] RuntimeHeroSection.vue has template ref (`ref="heroSection"`)
- [x] `onMounted()` lifecycle hook implemented
- [x] Post-hydration style fix with `Object.assign()` present
- [x] Gradient type handling logic present
- [x] Value sanitization (String + trim) implemented
- [x] TypeScript types correct and no compilation errors
- [x] Frontend builds successfully

### Testing Verification
- [x] Gradient appears on initial SSR page load
- [x] Gradient persists through client-side navigation
- [x] Gradient appears after hard refresh
- [x] No console errors or hydration warnings
- [x] Cross-browser tested (Chrome, Firefox, Safari)
- [x] Mobile responsive

### Documentation Verification
- [x] Implementation documentation created
- [x] SSR hydration fix documented
- [x] Testing guide available
- [x] Quick reference created

## Deployment Steps

### Step 1: Backup Current State
```bash
# Backup database
cd laratenant-backend
php artisan backup:run  # if backup package installed
# or manually backup database

# Create git branch for rollback
git checkout -b backup-before-gradient-deploy
git add .
git commit -m "Backup before gradient hero banner deployment"
git checkout main
```

### Step 2: Deploy Backend Changes
```bash
cd laratenant-backend

# Pull latest changes (if using git deployment)
git pull origin main

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Run migrations if needed
php artisan migrate --force

# Restart queue workers if applicable
php artisan queue:restart
```

### Step 3: Deploy Frontend Changes
```bash
cd ../justshop-frontend

# Pull latest changes
git pull origin main

# Install dependencies (if package.json changed)
npm ci --production

# Build for production
npm run build

# Restart/deploy application
# This depends on your deployment method:
# - PM2: pm2 restart justshop-frontend
# - Docker: docker-compose restart frontend
# - Vercel/Netlify: Automatic on git push
# - Manual: restart your Node.js server
```

### Step 4: Clear CDN/Edge Caches (if applicable)
```bash
# CloudFlare
curl -X POST "https://api.cloudflare.com/client/v4/zones/{zone_id}/purge_cache" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  --data '{"purge_everything":true}'

# Or use your CDN provider's purge mechanism
```

### Step 5: Verify Deployment
```bash
# Test API endpoint
curl -H "X-Tenant-Key: test" \
     -H "X-Locale: en" \
     -H "X-Storefront-Version: 2026-05-28" \
     https://your-domain.com/api/v1/storefront/runtime/page/home | jq '.data.page.sections[0].props.items[0]'

# Expected: Should see visualType, gradientFrom, gradientTo fields
```

## Post-Deployment Monitoring

### Immediate (First 10 minutes)
- [ ] Visit homepage in production - verify gradient shows immediately
- [ ] Test in incognito/private mode (fresh session)
- [ ] Check browser console for errors
- [ ] Verify no 500 errors in application logs
- [ ] Test client-side navigation (home → shop → home)

### Short-term (First Hour)
- [ ] Monitor error tracking (Sentry/Bugsnag/etc.) for new errors
- [ ] Check application logs for hydration warnings
- [ ] Monitor API response times (should be unchanged)
- [ ] Test on mobile devices
- [ ] Test on different browsers

### Medium-term (First 24 Hours)
- [ ] Review user feedback/support tickets
- [ ] Monitor performance metrics (Core Web Vitals)
- [ ] Check page load times (should be similar to before)
- [ ] Verify SEO/meta tags still correct
- [ ] Monitor server resource usage

## Performance Benchmarks

### Before Deployment (Baseline)
Record these metrics for comparison:
- [ ] Homepage Time to First Byte (TTFB): _____ ms
- [ ] First Contentful Paint (FCP): _____ ms
- [ ] Largest Contentful Paint (LCP): _____ ms
- [ ] Cumulative Layout Shift (CLS): _____
- [ ] Server CPU usage: _____ %
- [ ] Server memory usage: _____ MB

### After Deployment (Target)
These should be within 10% of baseline:
- [ ] Homepage TTFB: _____ ms (±10% of baseline)
- [ ] FCP: _____ ms (±10% of baseline)
- [ ] LCP: _____ ms (±10% of baseline)
- [ ] CLS: _____ (no change)
- [ ] CPU usage: _____ % (±5% of baseline)
- [ ] Memory usage: _____ MB (±10% of baseline)

## Rollback Plan

If issues occur, execute rollback:

### Quick Rollback (Frontend Only)
```bash
cd justshop-frontend
git checkout backup-before-gradient-deploy
npm run build
# Restart frontend application
```

### Full Rollback (Backend + Frontend)
```bash
# Backend
cd laratenant-backend
git checkout backup-before-gradient-deploy
php artisan cache:clear
php artisan config:clear

# Frontend
cd ../justshop-frontend
git checkout backup-before-gradient-deploy
npm run build
# Restart applications
```

### Database Rollback (if migrations were run)
```bash
cd laratenant-backend
php artisan migrate:rollback --step=1
```

## Common Issues & Solutions

### Issue 1: Gradient not showing in production
**Solution**:
```bash
# Clear all caches
php artisan cache:clear
# Clear CDN cache
# Hard refresh browser (Ctrl+Shift+R)
```

### Issue 2: Hydration warnings in console
**Solution**: 
- Check that both files are deployed
- Verify template ref is present
- Check onMounted hook exists

### Issue 3: Performance degradation
**Solution**:
- Enable OpCache on backend
- Enable frontend SSR caching
- Use CDN for static assets

### Issue 4: Different appearance on SSR vs Client
**Solution**:
- Check API returns consistent data
- Verify no environment-specific configs
- Test with DEBUG=nuxt:* to see SSR logs

## Success Criteria

Deployment is successful when:
- ✅ Gradient appears immediately on first page load
- ✅ No increase in error rate (< 0.1% change)
- ✅ No performance degradation (< 10% change in metrics)
- ✅ No console errors or warnings
- ✅ Works across all target browsers
- ✅ Mobile responsive
- ✅ SEO unchanged
- ✅ No user complaints in first 24 hours

## Team Communication

### Before Deployment
- [ ] Notify team of deployment window
- [ ] Assign monitoring responsibilities
- [ ] Prepare rollback procedure
- [ ] Document who to contact if issues arise

### During Deployment
- [ ] Post status updates in team chat
- [ ] Confirm each step completion
- [ ] Report any anomalies immediately

### After Deployment
- [ ] Confirm successful deployment
- [ ] Share monitoring results
- [ ] Document any issues encountered
- [ ] Schedule post-mortem if needed

## Sign-off

### Pre-Deployment Approval
- [ ] Developer: ________________ Date: ________
- [ ] Tech Lead: ________________ Date: ________
- [ ] QA: ________________ Date: ________

### Post-Deployment Confirmation
- [ ] Deployment completed: ________________ Date/Time: ________
- [ ] Verification passed: ________________ Date/Time: ________
- [ ] Monitoring active: ________________ Date/Time: ________

## Additional Notes

Add any deployment-specific notes here:
_____________________________________________________________
_____________________________________________________________
_____________________________________________________________

## Contact Information

If issues arise during deployment:
- Developer on call: ________________
- Backend support: ________________
- Frontend support: ________________
- DevOps/Infrastructure: ________________

---

**Status**: Ready for deployment ✅

**Last Updated**: 2026-06-05
**Version**: 1.0.0
