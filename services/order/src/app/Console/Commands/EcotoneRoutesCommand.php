<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\Finder\Finder;

/**
 * Scans App\ namespace for Ecotone attributes and prints a routing map.
 *
 * This is a developer tool — it does not depend on Ecotone's internal API,
 * only on the public attributes.
 */
final class EcotoneRoutesCommand extends Command
{
    protected $signature = 'ecotone:routes
                            {--json : Output as JSON instead of a table}';

    protected $description = 'Print Ecotone routing map (handler attributes → message types)';

    /** @var array<int, array{type: string, class: string, method: string, message: string, routingKey: string|null}> */
    private array $routes = [];

    public function handle(): int
    {
        $this->scanDirectory(app_path());

        if ($this->routes === []) {
            $this->warn('No Ecotone handlers found.');

            return self::SUCCESS;
        }

        usort($this->routes, fn (array $a, array $b): int => [$a['type'], $a['message']] <=> [$b['type'], $b['message']]);

        if ($this->option('json')) {
            $this->line(json_encode($this->routes, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->renderTable();

        return self::SUCCESS;
    }

    private function scanDirectory(string $path): void
    {
        $finder = new Finder();
        $finder->files()->in($path)->name('*.php');

        foreach ($finder as $file) {
            $class = $this->classFromFile($file->getRealPath());

            if ($class === null || ! class_exists($class)) {
                continue;
            }

            $this->inspectClass($class);
        }
    }

    private function classFromFile(string $file): ?string
    {
        $contents = file_get_contents($file);

        if ($contents === false || ! preg_match('/namespace\s+([^;]+);/', $contents, $ns)) {
            return null;
        }

        if (! preg_match('/class\s+(\w+)/', $contents, $class)) {
            return null;
        }

        return $ns[1] . '\\' . $class[1];
    }

    private function inspectClass(string $class): void
    {
        $reflection = new ReflectionClass($class);

        if ($reflection->isAbstract() || $reflection->isInterface()) {
            return;
        }

        $classAttributes = $reflection->getAttributes();

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            foreach ($method->getAttributes() as $attribute) {
                $this->handleAttribute($attribute->getName(), $method, $classAttributes);
            }
        }
    }

    /**
     * @param  array<int, \ReflectionAttribute>  $classAttributes
     */
    private function handleAttribute(string $attributeName, ReflectionMethod $method, array $classAttributes): void
    {
        $short = $this->shortName($attributeName);

        $type = match ($short) {
            'CommandHandler' => 'Command',
            'EventHandler' => 'Event',
            'QueryHandler' => 'Query',
            default => null,
        };

        if ($type === null) {
            return;
        }

        $isDistributed = $this->hasAttribute($classAttributes, 'Distributed')
            || $this->hasAttribute($method->getAttributes(), 'Distributed');

        $messageType = $this->resolveMessageType($method);

        $routingKey = $this->resolveRoutingKey($attributeName, $method);

        $this->routes[] = [
            'type' => $isDistributed ? "Distributed {$type}" : $type,
            'class' => $method->getDeclaringClass()->getShortName(),
            'method' => $method->getName(),
            'message' => $messageType,
            'routingKey' => $routingKey,
        ];
    }

    private function resolveMessageType(ReflectionMethod $method): string
    {
        foreach ($method->getParameters() as $parameter) {
            $type = $parameter->getType();

            if ($type instanceof \ReflectionNamedType && ! $type->isBuiltin()) {
                return $this->shortName($type->getName());
            }
        }

        return '—';
    }

    private function resolveRoutingKey(string $attributeName, ReflectionMethod $method): ?string
    {
        $attributes = $method->getAttributes($attributeName);

        if ($attributes === []) {
            return null;
        }

        $arguments = $attributes[0]->getArguments();

        if ($arguments === []) {
            return null;
        }

        // #[EventHandler('order.created')] or #[CommandHandler('payment.capture')]
        return is_string($arguments[0] ?? null) ? $arguments[0] : null;
    }

    /**
     * @param  array<int, \ReflectionAttribute>  $attributes
     */
    private function hasAttribute(array $attributes, string $shortName): bool
    {
        foreach ($attributes as $attribute) {
            if ($this->shortName($attribute->getName()) === $shortName) {
                return true;
            }
        }

        return false;
    }

    private function shortName(string $fqcn): string
    {
        $parts = explode('\\', $fqcn);

        return end($parts);
    }

    private function renderTable(): void
    {
        $rows = array_map(
            fn (array $r): array => [
                $r['type'],
                $r['class'] . '::' . $r['method'] . '()',
                $r['message'],
                $r['routingKey'] ?? '',
            ],
            $this->routes,
        );

        $this->newLine();
        $this->table(
            ['Type', 'Handler', 'Message', 'Routing key'],
            $rows,
        );
        $this->line(sprintf('  Total: %d handlers', count($this->routes)));
        $this->newLine();
    }
}
