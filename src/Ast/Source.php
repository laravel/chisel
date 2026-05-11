<?php

namespace Laravel\Chisel\Ast;

use Laravel\Chisel\Ast\Visitors\RemoveImportVisitor;
use Laravel\Chisel\Ast\Visitors\RemoveInterfaceVisitor;
use Laravel\Chisel\Ast\Visitors\RemoveTraitVisitor;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\CloningVisitor;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;

class Source
{
    /** @var array<NodeVisitorAbstract> */
    protected array $edits = [];

    protected bool $saved = false;

    public function __construct(protected string $path)
    {
        //
    }

    public function removeTrait(string $trait): static
    {
        $this->edits[] = new RemoveTraitVisitor($trait);

        return $this;
    }

    public function removeInterface(string $interface): static
    {
        $this->edits[] = new RemoveInterfaceVisitor($interface);

        return $this;
    }

    public function removeImport(string $class): static
    {
        $this->edits[] = new RemoveImportVisitor($class);

        return $this;
    }

    public function save(): void
    {
        $this->saved = true;

        if ($this->edits === [] || ! file_exists($this->path)) {
            return;
        }

        $code = file_get_contents($this->path);

        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $oldStmts = $parser->parse($code);
        $oldTokens = $parser->getTokens();

        $cloner = new NodeTraverser;
        $cloner->addVisitor(new CloningVisitor);
        $newStmts = $cloner->traverse($oldStmts);

        $traverser = new NodeTraverser;

        foreach ($this->edits as $edit) {
            $traverser->addVisitor($edit);
        }

        $newStmts = $traverser->traverse($newStmts);

        file_put_contents(
            $this->path,
            (new Standard)->printFormatPreserving($newStmts, $oldStmts, $oldTokens),
        );

        $this->edits = [];
    }

    public function __destruct()
    {
        if (! $this->saved) {
            $this->save();
        }
    }
}
