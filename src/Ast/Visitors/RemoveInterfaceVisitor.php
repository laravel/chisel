<?php

namespace Laravel\Chisel\Ast\Visitors;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeVisitorAbstract;

class RemoveInterfaceVisitor extends NodeVisitorAbstract
{
    use InteractsWithNodes;

    /** @var array<string> */
    protected array $interfaces;

    /** @param  string|array<string>  $interfaces */
    public function __construct(string|array $interfaces)
    {
        $this->interfaces = is_array($interfaces) ? $interfaces : [$interfaces];
    }

    public function enterNode(Node $node): void
    {
        if ($node instanceof Class_) {
            $this->removeInterfacesFromClass($node);
        }
    }

    protected function removeInterfacesFromClass(Class_ $class): void
    {
        $class->implements = array_values(array_filter($class->implements, fn (Name $interface): bool => ! in_array($this->simpleName($interface->toString()), $this->interfaces)));
    }
}
