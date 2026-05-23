<?php

declare(strict_types=1);

namespace App\Services\Governance;

use App\Services\Authorization\PolicyOwnershipRegistry;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;

class PolicyGovernanceEnforcer
{
    private PolicyOwnershipRegistry $registry;
    private string $policiesPath;

    public function __construct(PolicyOwnershipRegistry $registry)
    {
        $this->registry = $registry;
        $this->policiesPath = app_path('Policies');
    }

    public function generateReport(): array
    {
        $policyFiles = File::allFiles($this->policiesPath);
        $unregistered = [];
        $actorBlind = [];
        $escalationCapable = [];
        $unsupportedActorUsage = [];
        $implicitAssumptions = [];
        $directGateUsage = [];

        foreach ($policyFiles as $file) {
            $className = $this->getClassNameFromFile($file->getRealPath());
            if (!$className || !class_exists($className)) {
                continue;
            }

            // 1. Check if registered
            $metadata = $this->registry->get($className);
            if (!$metadata) {
                $unregistered[] = $className;
                continue;
            }

            $content = file_get_contents($file->getRealPath());
            $reflection = new ReflectionClass($className);

            // 2. Detect actor-blind policies (no isMerchant, isCustomer, or getActorContext checks)
            if (!$this->checksActorContext($content)) {
                $actorBlind[] = $className;
            }

            // 3. Detect implicit escalation paths (before method with logic other than super admin)
            if ($reflection->hasMethod('before')) {
                if ($this->hasRiskyBeforeMethod($reflection->getMethod('before'))) {
                    $escalationCapable[] = $className;
                }
            }

            // 4. Detect policies missing supported actor declarations in logic
            // (e.g. registry says platform is supported but policy doesn't check for it)
            if ($this->hasMissingActorLogic($className, $metadata, $content)) {
                $unsupportedActorUsage[] = $className;
            }

            // 5. Detect generic request->user() or Auth::user() assumptions
            if (Str::contains($content, ['request()->user()', 'Auth::user()', 'auth()->user()'])) {
                $implicitAssumptions[] = $className;
            }

            // 6. Detect store membership without actor validation
            if ($this->hasMembershipWithoutActorValidation($content)) {
                $implicitAssumptions[] = $className . ' (membership without actor check)';
            }
        }

        // 7. Detect direct Gate::allowIf shortcuts outside approved domains
        $directGateUsage = $this->scanForDirectGateUsage();

        return [
            'unregistered_policies' => $unregistered,
            'actor_blind_policies' => $actorBlind,
            'escalation_capable_policies' => $escalationCapable,
            'unsupported_actor_usage' => $unsupportedActorUsage,
            'implicit_assumptions' => $implicitAssumptions,
            'direct_gate_usage' => $directGateUsage,
            'policy_registry_drift' => count($unregistered) > 0,
        ];
    }

    private function getClassNameFromFile(string $path): ?string
    {
        $content = file_get_contents($path);
        if (preg_match('/namespace\s+(.+);/i', $content, $matches)) {
            $namespace = $matches[1];
            if (preg_match('/class\s+(\w+)/i', $content, $matches)) {
                return $namespace . '\\' . $matches[1];
            }
        }
        return null;
    }

    private function checksActorContext(string $content): bool
    {
        return Str::contains($content, ['isMerchant', 'isCustomer', 'getActorContext', 'ActorContextEnum']);
    }

    private function hasRiskyBeforeMethod(ReflectionMethod $method): bool
    {
        // For simplicity, we check if it contains logic other than just SUPER_ADMIN check
        // In a real scenario, we'd use a parser, but here we can look for keywords
        $fileName = $method->getFileName();
        $startLine = $method->getStartLine() - 1;
        $endLine = $method->getEndLine();
        $lines = array_slice(file($fileName), $startLine, $endLine - $startLine);
        $body = implode('', $lines);

        // If it does more than just checking SUPER_ADMIN role, it's risky
        $cleanBody = preg_replace('/\s+/', ' ', $body);
        if (Str::contains($cleanBody, 'return true') && !Str::contains($cleanBody, 'SUPER_ADMIN')) {
            return true;
        }

        return false;
    }

    private function hasMissingActorLogic(string $className, array $metadata, string $content): bool
    {
        $supported = $metadata['supported_actor_domains'];
        
        // If PLATFORM is supported but no PLATFORM check exists in code
        if (in_array('platform', $supported) && !Str::contains($content, ['PLATFORM', 'SUPPORT_AGENT', 'PLATFORM_SYSTEM'])) {
            return true;
        }

        return false;
    }

    private function hasMembershipWithoutActorValidation(string $content): bool
    {
        // Look for isMember() calls that aren't preceded by isMerchant() in the same expression
        // This is a heuristic check
        if (Str::contains($content, 'isMember') && !Str::contains($content, 'isMerchant')) {
            return true;
        }
        return false;
    }

    private function scanForDirectGateUsage(): array
    {
        $results = [];
        $paths = [app_path('Http/Controllers'), app_path('Services')];
        
        foreach ($paths as $path) {
            if (!File::exists($path)) continue;
            
            $files = File::allFiles($path);
            foreach ($files as $file) {
                $content = file_get_contents($file->getRealPath());
                if (Str::contains($content, ['Gate::allowIf', 'Gate::authorize', 'Gate::check'])) {
                    $results[] = str_replace(base_path() . '/', '', $file->getRealPath());
                }
            }
        }
        
        return $results;
    }
}
