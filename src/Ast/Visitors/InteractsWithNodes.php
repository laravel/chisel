<?php

namespace Laravel\Chisel\Ast\Visitors;

use PhpParser\Node;

trait InteractsWithNodes
{
    /**
     * @param  array<Node>  $ast
     * @return array<Node>
     */
    protected function getStatements(array $ast): array
    {
        if (count($ast) === 1 && $ast[0] instanceof Node\Stmt\Namespace_) {
            return $ast[0]->stmts;
        }

        return $ast;
    }

    /**
     * @param  array<Node>  $ast
     * @param  array<Node>  $statements
     * @return array<Node>
     */
    protected function withStatements(array $ast, array $statements): array
    {
        if (count($ast) === 1 && $ast[0] instanceof Node\Stmt\Namespace_) {
            $ast[0]->stmts = $statements;

            return $ast;
        }

        return $statements;
    }

    protected function simpleName(string $name): string
    {
        $parts = explode('\\', $name);

        return end($parts);
    }
}
