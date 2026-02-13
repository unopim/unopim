<?php

namespace Webkul\Tenant\PHPStan;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Type\ObjectType;

/**
 * PHPStan rule that flags calls to withoutGlobalScope(TenantScope::class)
 * and withoutGlobalScopes() as warnings during CI analysis.
 *
 * @implements Rule<MethodCall>
 */
class TenantScopeRule implements Rule
{
    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /**
     * @param  MethodCall  $node
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! $node->name instanceof Node\Identifier) {
            return [];
        }

        $methodName = $node->name->name;

        if ($methodName === 'withoutGlobalScope') {
            return $this->checkWithoutGlobalScope($node, $scope);
        }

        if ($methodName === 'withoutGlobalScopes') {
            return $this->checkWithoutGlobalScopes($node, $scope);
        }

        return [];
    }

    private function checkWithoutGlobalScope(MethodCall $node, Scope $scope): array
    {
        $args = $node->getArgs();

        if (count($args) === 0) {
            return [];
        }

        $arg = $args[0]->value;

        // Check for TenantScope::class
        if ($arg instanceof Node\Expr\ClassConstFetch
            && $arg->class instanceof Node\Name
            && str_contains($arg->class->toString(), 'TenantScope')
            && $arg->name instanceof Node\Identifier
            && $arg->name->name === 'class'
        ) {
            return [
                'Removing TenantScope is a security-sensitive operation. Ensure this is intentional and audited.',
            ];
        }

        return [];
    }

    private function checkWithoutGlobalScopes(MethodCall $node, Scope $scope): array
    {
        $args = $node->getArgs();

        // withoutGlobalScopes() with no args removes ALL scopes including TenantScope
        if (count($args) === 0) {
            return [
                'Calling withoutGlobalScopes() removes TenantScope. This is a security-sensitive operation.',
            ];
        }

        return [];
    }
}
