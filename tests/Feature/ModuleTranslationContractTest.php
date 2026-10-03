<?php

namespace Tests\Feature;

use App\Services\Localization\Contracts\TranslatableModuleInterface;
use App\Services\Localization\ModuleRegistry;
use App\Services\Localization\Modules\AbstractTranslatableModule;
use Tests\TestCase;

class ModuleTranslationContractTest extends TestCase
{
    public function test_module_registry_registers_core_modules(): void
    {
        $registry = new ModuleRegistry;

        $this->assertTrue($registry->has('common'));
        $this->assertTrue($registry->has('menu'));
        $this->assertTrue($registry->has('orders'));

        $menuModule = $registry->get('menu');
        $this->assertInstanceOf(TranslatableModuleInterface::class, $menuModule);
        $this->assertEquals(['hy', 'en', 'ru'], $menuModule->getRequiredLocales());
    }

    public function test_existing_modules_pass_localization_audit(): void
    {
        $registry = new ModuleRegistry;
        $audits = $registry->auditAll();

        foreach ($audits as $namespace => $audit) {
            $this->assertTrue($audit['is_valid'], "Module '{$namespace}' failed localization audit: ".json_encode($audit));
            $this->assertEmpty($audit['missing_files'], "Module '{$namespace}' has missing files");
            $this->assertEmpty($audit['missing_keys'], "Module '{$namespace}' has missing keys");
            $this->assertEmpty($audit['placeholder_errors'], "Module '{$namespace}' has placeholder errors");
        }
    }

    public function test_audit_flags_module_with_missing_keys(): void
    {
        $failingModule = new class extends AbstractTranslatableModule
        {
            public function getNamespace(): string
            {
                return 'common';
            }

            public function getName(): string
            {
                return 'Mock Failing Module';
            }

            public function getExpectedKeys(): array
            {
                return ['non_existent_key_12345'];
            }
        };

        $audit = $failingModule->audit();
        $this->assertFalse($audit['is_valid']);
        $this->assertNotEmpty($audit['missing_keys']);
        $this->assertContains('non_existent_key_12345', $audit['missing_keys']['en']);
    }

    public function test_i18n_check_artisan_command_passes(): void
    {
        $this->artisan('i18n:check')
            ->expectsOutputToContain('ALL MODULES COMPLIANT')
            ->assertSuccessful();
    }
}
