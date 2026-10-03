<?php

namespace App\Services\Localization;

use App\Services\Localization\Contracts\TranslatableModuleInterface;
use App\Services\Localization\Modules\CommonModule;
use App\Services\Localization\Modules\MenuModule;
use App\Services\Localization\Modules\OrdersModule;

class ModuleRegistry
{
    /**
     * @var array<string, TranslatableModuleInterface>
     */
    protected array $modules = [];

    public function __construct()
    {
        $this->registerDefaultModules();
    }

    protected function registerDefaultModules(): void
    {
        $this->register(new CommonModule);
        $this->register(new MenuModule);
        $this->register(new OrdersModule);
    }

    public function register(TranslatableModuleInterface $module): self
    {
        $this->modules[$module->getNamespace()] = $module;

        return $this;
    }

    /**
     * @return array<string, TranslatableModuleInterface>
     */
    public function all(): array
    {
        return $this->modules;
    }

    public function get(string $namespace): ?TranslatableModuleInterface
    {
        return $this->modules[$namespace] ?? null;
    }

    public function has(string $namespace): bool
    {
        return isset($this->modules[$namespace]);
    }

    /**
     * Run audit across all registered modules.
     *
     * @return array<string, array{is_valid: bool, missing_files: array, missing_keys: array, placeholder_errors: array, model_warnings: array}>
     */
    public function auditAll(): array
    {
        $results = [];
        foreach ($this->modules as $namespace => $module) {
            $results[$namespace] = $module->audit();
        }

        return $results;
    }
}
