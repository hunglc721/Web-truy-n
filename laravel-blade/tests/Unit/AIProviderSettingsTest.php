<?php

namespace Tests\Unit;

use App\Support\AIProviderSettings;
use PHPUnit\Framework\TestCase;

class AIProviderSettingsTest extends TestCase
{
    public function test_gemini_key_fills_endpoint_and_default_model(): void
    {
        $settings = AIProviderSettings::resolve(['api_key' => 'AIza-test-key']);

        $this->assertTrue($settings['gemini']);
        $this->assertSame('openai_compatible', $settings['provider']);
        $this->assertSame('AIza-test-key', $settings['api_key']);
        $this->assertSame(AIProviderSettings::GEMINI_BASE_URL, $settings['base_url']);
        $this->assertSame(AIProviderSettings::GEMINI_DEFAULT_MODEL, $settings['model']);
    }

    public function test_gemini_key_aliases_are_accepted(): void
    {
        foreach (['gemini_api_key', 'google_api_key'] as $alias) {
            $settings = AIProviderSettings::resolve([$alias => 'AIza-alias-key']);

            $this->assertTrue($settings['gemini'], $alias);
            $this->assertSame('AIza-alias-key', $settings['api_key'], $alias);
            $this->assertSame(AIProviderSettings::GEMINI_BASE_URL, $settings['base_url'], $alias);
        }
    }

    public function test_explicit_gemini_provider_uses_defaults_and_keeps_overrides(): void
    {
        $settings = AIProviderSettings::resolve([
            'provider' => 'gemini',
            'api_key' => 'not-an-aiza-key',
            'base_url' => 'https://proxy.example/v1',
            'model' => 'custom-model',
        ]);

        $this->assertTrue($settings['gemini']);
        $this->assertSame('openai_compatible', $settings['provider']);
        $this->assertSame('https://proxy.example/v1', $settings['base_url']);
        $this->assertSame('custom-model', $settings['model']);
    }

    public function test_gemini_model_override_is_used_when_ai_model_is_empty(): void
    {
        $settings = AIProviderSettings::resolve([
            'api_key' => 'AIza-test-key',
            'gemini_model' => 'gemini-custom-flash',
        ]);

        $this->assertSame('gemini-custom-flash', $settings['model']);
    }

    public function test_non_gemini_keys_keep_provider_settings_untouched(): void
    {
        $settings = AIProviderSettings::resolve([
            'provider' => 'openai_compatible',
            'api_key' => 'sk-test-key',
            'base_url' => 'https://api.example/v1',
            'model' => 'test-model',
        ]);

        $this->assertFalse($settings['gemini']);
        $this->assertSame('openai_compatible', $settings['provider']);
        $this->assertSame('https://api.example/v1', $settings['base_url']);
        $this->assertSame('test-model', $settings['model']);
    }

    public function test_explicit_base_url_disables_gemini_inference_for_aiza_keys(): void
    {
        $settings = AIProviderSettings::resolve([
            'api_key' => 'AIza-test-key',
            'base_url' => 'https://proxy.example/v1',
        ]);

        $this->assertFalse($settings['gemini']);
        $this->assertSame('https://proxy.example/v1', $settings['base_url']);
        $this->assertSame('', $settings['model']);
    }
}
