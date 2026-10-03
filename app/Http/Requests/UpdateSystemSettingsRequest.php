<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSystemSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return (bool) $this->user()?->isSuperAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Contacts
            'contact_phone' => 'required|string|max:50',
            'contact_whatsapp' => 'nullable|string|max:50',
            'contact_telegram' => 'nullable|string|max:100',
            'contact_email' => 'required|email|max:100',

            // Social Media
            'social_facebook' => 'nullable|string|max:255',
            'social_instagram' => 'nullable|string|max:255',

            // Landing & System Defaults (CMS)
            'demo_vendor_slug' => 'nullable|string|max:100',
            'trial_days' => 'required|integer|min:1|max:90',

            // Hero CMS
            'hero_badge_hy' => 'nullable|string|max:255',
            'hero_badge_en' => 'nullable|string|max:255',
            'hero_title_hy' => 'nullable|string|max:255',
            'hero_title_en' => 'nullable|string|max:255',
            'hero_subtitle_hy' => 'nullable|string|max:1000',
            'hero_subtitle_en' => 'nullable|string|max:1000',
            'hero_cta_primary_hy' => 'nullable|string|max:255',
            'hero_cta_primary_en' => 'nullable|string|max:255',
            'hero_cta_secondary_hy' => 'nullable|string|max:255',
            'hero_cta_secondary_en' => 'nullable|string|max:255',
            'hero_trust_badge1_hy' => 'nullable|string|max:255',
            'hero_trust_badge1_en' => 'nullable|string|max:255',
            'hero_trust_badge2_hy' => 'nullable|string|max:255',
            'hero_trust_badge2_en' => 'nullable|string|max:255',
            'hero_trust_badge3_hy' => 'nullable|string|max:255',
            'hero_trust_badge3_en' => 'nullable|string|max:255',

            // Comparison CMS
            'vs_badge_hy' => 'nullable|string|max:255',
            'vs_badge_en' => 'nullable|string|max:255',
            'vs_title_hy' => 'nullable|string|max:255',
            'vs_title_en' => 'nullable|string|max:255',
            'vs_subtitle_hy' => 'nullable|string|max:1000',
            'vs_subtitle_en' => 'nullable|string|max:1000',
            'vs_paper_title_hy' => 'nullable|string|max:255',
            'vs_paper_title_en' => 'nullable|string|max:255',
            'vs_paper_badge_hy' => 'nullable|string|max:255',
            'vs_paper_badge_en' => 'nullable|string|max:255',
            'vs_qr_title_hy' => 'nullable|string|max:255',
            'vs_qr_title_en' => 'nullable|string|max:255',
            'vs_qr_badge_hy' => 'nullable|string|max:255',
            'vs_qr_badge_en' => 'nullable|string|max:255',

            // AI Waiter CMS
            'ai_section_badge_hy' => 'nullable|string|max:255',
            'ai_section_badge_en' => 'nullable|string|max:255',
            'ai_section_title_hy' => 'nullable|string|max:255',
            'ai_section_title_en' => 'nullable|string|max:255',
            'ai_section_subtitle_hy' => 'nullable|string|max:1000',
            'ai_section_subtitle_en' => 'nullable|string|max:1000',
            'ai_feature1_title_hy' => 'nullable|string|max:255',
            'ai_feature1_title_en' => 'nullable|string|max:255',
            'ai_feature1_desc_hy' => 'nullable|string|max:1000',
            'ai_feature1_desc_en' => 'nullable|string|max:1000',
            'ai_feature2_title_hy' => 'nullable|string|max:255',
            'ai_feature2_title_en' => 'nullable|string|max:255',
            'ai_feature2_desc_hy' => 'nullable|string|max:1000',
            'ai_feature2_desc_en' => 'nullable|string|max:1000',
            'ai_feature3_title_hy' => 'nullable|string|max:255',
            'ai_feature3_title_en' => 'nullable|string|max:255',
            'ai_feature3_desc_hy' => 'nullable|string|max:1000',
            'ai_feature3_desc_en' => 'nullable|string|max:1000',

            // ROI Calculator CMS
            'calc_badge_hy' => 'nullable|string|max:255',
            'calc_badge_en' => 'nullable|string|max:255',
            'calc_title_hy' => 'nullable|string|max:255',
            'calc_title_en' => 'nullable|string|max:255',
            'calc_subtitle_hy' => 'nullable|string|max:1000',
            'calc_subtitle_en' => 'nullable|string|max:1000',
            'calc_cta_hy' => 'nullable|string|max:255',
            'calc_cta_en' => 'nullable|string|max:255',

            // Features Grid CMS
            'features_badge_hy' => 'nullable|string|max:255',
            'features_badge_en' => 'nullable|string|max:255',
            'features_title_hy' => 'nullable|string|max:255',
            'features_title_en' => 'nullable|string|max:255',
            'features_subtitle_hy' => 'nullable|string|max:1000',
            'features_subtitle_en' => 'nullable|string|max:1000',

            // Pricing CMS
            'pricing_badge_hy' => 'nullable|string|max:255',
            'pricing_badge_en' => 'nullable|string|max:255',
            'pricing_title_hy' => 'nullable|string|max:255',
            'pricing_title_en' => 'nullable|string|max:255',
            'pricing_subtitle_hy' => 'nullable|string|max:1000',
            'pricing_subtitle_en' => 'nullable|string|max:1000',

            // FAQ CMS
            'faq_badge_hy' => 'nullable|string|max:255',
            'faq_badge_en' => 'nullable|string|max:255',
            'faq_title_hy' => 'nullable|string|max:255',
            'faq_title_en' => 'nullable|string|max:255',
            'faq_subtitle_hy' => 'nullable|string|max:1000',
            'faq_subtitle_en' => 'nullable|string|max:1000',

            // Final CTA Banner CMS
            'cta_banner_title_hy' => 'nullable|string|max:255',
            'cta_banner_title_en' => 'nullable|string|max:255',
            'cta_banner_subtitle_hy' => 'nullable|string|max:1000',
            'cta_banner_subtitle_en' => 'nullable|string|max:1000',
            'cta_banner_btn_text_hy' => 'nullable|string|max:255',
            'cta_banner_btn_text_en' => 'nullable|string|max:255',

            // Telegram Notifications
            'telegram_bot_token' => 'nullable|string|max:255',
            'telegram_admin_chat_id' => 'nullable|string|max:255',

            // Branding & Identity
            'site_name' => 'nullable|string|max:100',
            'site_tagline' => 'nullable|string|max:255',
            'site_logo_light' => 'nullable|string|max:500',
            'site_logo_dark' => 'nullable|string|max:500',
            'site_favicon' => 'nullable|string|max:500',
            'logo_light_file' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
            'logo_dark_file' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
            'favicon_file' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg,webp,ico|max:2048',

            // SEO & OpenGraph Social Sharing
            'seo_title' => 'nullable|string|max:255',
            'seo_title_en' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:1000',
            'seo_description_en' => 'nullable|string|max:1000',
            'seo_keywords' => 'nullable|string|max:500',
            'seo_og_image' => 'nullable|string|max:500',
            'og_image_file' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
            'footer_copyright' => 'nullable|string|max:255',
        ];
    }
}
