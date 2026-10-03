<?php

namespace Tests\Unit;

use App\Services\Localization\TranslationValidator;
use PHPUnit\Framework\TestCase;

class TranslationValidatorTest extends TestCase
{
    protected TranslationValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new TranslationValidator;
    }

    public function test_passes_when_placeholders_match_exactly(): void
    {
        $source = 'Hello :name, you have :count items.';
        $translation = 'Բարև :name, դուք ունեք :count ապրանք:';

        $result = $this->validator->validate($source, $translation, 'en', 'hy');

        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->errors);
    }

    public function test_fails_when_laravel_placeholder_is_missing(): void
    {
        $source = 'Order #:order_id has been confirmed.';
        $translation = 'Պատվերը հաստատվել է:'; // Missing :order_id

        $result = $this->validator->validate($source, $translation, 'en', 'hy');

        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('Missing placeholder(s): :order_id', $result->firstError());
    }

    public function test_fails_when_curly_brace_placeholder_is_missing(): void
    {
        $source = 'Table {table_number} is reserved.';
        $translation = 'Սեղանը ամրագրված է:';

        $result = $this->validator->validate($source, $translation, 'en', 'hy');

        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('Missing template variable(s): {table_number}', $result->firstError());
    }

    public function test_fails_when_printf_placeholder_count_mismatches(): void
    {
        $source = 'Found %d results for %s.';
        $translation = 'Գտնվել է արդյունք %s-ի համար:'; // missing %d

        $result = $this->validator->validate($source, $translation, 'en', 'hy');

        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('Mismatch in printf format specifiers count', $result->firstError());
    }

    public function test_fails_when_html_tags_are_dropped(): void
    {
        $source = 'Please <b>wash your hands</b> before eating.';
        $translation = 'Խնդրում ենք լվանալ ձեռքերը ուտելուց առաջ:';

        $result = $this->validator->validate($source, $translation, 'en', 'hy');

        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('Missing required HTML tag(s): <b>', $result->firstError());
    }

    public function test_fails_when_translation_is_empty_for_non_empty_source(): void
    {
        $source = 'Delicious Burger';
        $translation = '   ';

        $result = $this->validator->validate($source, $translation, 'en', 'hy');

        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('The translation output is empty.', $result->firstError());
    }
}
