<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class WelcomePageSettingsService
{
    private const DB_KEY = 'welcome_page_content';
    private const STORAGE_DIR = 'welcome_page';

    public function settings(): array
    {
        return array_merge($this->defaults(), $this->storedOverrides());
    }

    public function imageUrls(): array
    {
        $settings = $this->settings();
        $urls = [];

        foreach (array_keys($this->imageFields()) as $key) {
            $urls[$key] = $this->resolveImageUrl((string) ($settings[$key] ?? ''));
        }

        return $urls;
    }

    public function textSections(): array
    {
        return [
            'Meta & Hero' => [
                ['key' => 'meta_title', 'label' => 'Browser Title'],
                ['key' => 'meta_description', 'label' => 'Meta Description', 'type' => 'textarea', 'rows' => 3],
                ['key' => 'hero_title_primary', 'label' => 'Hero Primary Line', 'type' => 'textarea', 'rows' => 2],
                ['key' => 'hero_title_secondary', 'label' => 'Hero Secondary Line'],
                ['key' => 'hero_badge_text', 'label' => 'Hero Badge Text'],
                ['key' => 'trusted_network_label', 'label' => 'Trusted Network Label'],
            ],
            'Access & Merchant Story' => [
                ['key' => 'auth_title', 'label' => 'Access Card Title'],
                ['key' => 'auth_chip_text', 'label' => 'Access Chip Text'],
                ['key' => 'auth_description', 'label' => 'Access Description', 'type' => 'textarea', 'rows' => 3],
                ['key' => 'mrd_title', 'label' => 'Merchant Story Card Title'],
                ['key' => 'mrd_status', 'label' => 'Merchant Story Card Status'],
                ['key' => 'mrd_description', 'label' => 'Merchant Story Description', 'type' => 'textarea', 'rows' => 3],
                ['key' => 'order_grid_text', 'label' => 'Order Grid Text', 'type' => 'textarea', 'rows' => 3],
            ],
            'Slack & Renewable Card' => [
                ['key' => 'slack_title', 'label' => 'Slack Card Title'],
                ['key' => 'slack_description', 'label' => 'Slack Card Description', 'type' => 'textarea', 'rows' => 3],
                ['key' => 'slack_cta_text', 'label' => 'Slack CTA Button Text'],
                ['key' => 'slack_contact_name', 'label' => 'Slack Contact Name'],
                ['key' => 'slack_contact_description', 'label' => 'Slack Contact Description', 'type' => 'textarea', 'rows' => 2],
                ['key' => 'renewable_gif_title', 'label' => 'Renewable GIF Title'],
                ['key' => 'renewable_gif_text', 'label' => 'Renewable GIF Text', 'type' => 'textarea', 'rows' => 3],
            ],
            'Workflow & Case Study' => [
                ['key' => 'workflow_eyebrow', 'label' => 'Workflow Eyebrow'],
                ['key' => 'workflow_title', 'label' => 'Workflow Title'],
                ['key' => 'workflow_step_1_title', 'label' => 'Workflow Step 1 Title'],
                ['key' => 'workflow_step_1_text', 'label' => 'Workflow Step 1 Text', 'type' => 'textarea', 'rows' => 2],
                ['key' => 'workflow_step_2_title', 'label' => 'Workflow Step 2 Title'],
                ['key' => 'workflow_step_2_text', 'label' => 'Workflow Step 2 Text', 'type' => 'textarea', 'rows' => 2],
                ['key' => 'workflow_step_3_title', 'label' => 'Workflow Step 3 Title'],
                ['key' => 'workflow_step_3_text', 'label' => 'Workflow Step 3 Text', 'type' => 'textarea', 'rows' => 2],
                ['key' => 'workflow_step_4_title', 'label' => 'Workflow Step 4 Title'],
                ['key' => 'workflow_step_4_text', 'label' => 'Workflow Step 4 Text', 'type' => 'textarea', 'rows' => 2],
                ['key' => 'workflow_success_eyebrow', 'label' => 'Workflow Success Eyebrow'],
                ['key' => 'workflow_success_text', 'label' => 'Workflow Success Text', 'type' => 'textarea', 'rows' => 2],
                ['key' => 'tapless_button_text', 'label' => 'Tapless Button Text'],
                ['key' => 'case_study_eyebrow', 'label' => 'Case Study Eyebrow'],
                ['key' => 'case_study_title', 'label' => 'Case Study Title'],
                ['key' => 'case_study_p1', 'label' => 'Case Study Paragraph 1', 'type' => 'textarea', 'rows' => 3],
                ['key' => 'case_study_p2', 'label' => 'Case Study Paragraph 2', 'type' => 'textarea', 'rows' => 3],
                ['key' => 'case_study_p3', 'label' => 'Case Study Paragraph 3', 'type' => 'textarea', 'rows' => 3],
            ],
            'FAQ & Merchant Onboarding' => [
                ['key' => 'faq_eyebrow', 'label' => 'FAQ Eyebrow'],
                ['key' => 'faq_title', 'label' => 'FAQ Title'],
                ['key' => 'faq_question_1', 'label' => 'FAQ Question 1', 'type' => 'textarea', 'rows' => 2],
                ['key' => 'faq_answer_1', 'label' => 'FAQ Answer 1', 'type' => 'textarea', 'rows' => 3],
                ['key' => 'faq_question_2', 'label' => 'FAQ Question 2', 'type' => 'textarea', 'rows' => 2],
                ['key' => 'faq_answer_2', 'label' => 'FAQ Answer 2', 'type' => 'textarea', 'rows' => 3],
                ['key' => 'merchant_onboarding_eyebrow', 'label' => 'Merchant Onboarding Eyebrow'],
                ['key' => 'merchant_onboarding_title', 'label' => 'Merchant Onboarding Title'],
                ['key' => 'merchant_onboarding_chip', 'label' => 'Merchant Onboarding Chip'],
                ['key' => 'merchant_step_1_title', 'label' => 'Merchant Step 1 Title'],
                ['key' => 'merchant_step_1_text', 'label' => 'Merchant Step 1 Text', 'type' => 'textarea', 'rows' => 2],
                ['key' => 'merchant_step_2_title', 'label' => 'Merchant Step 2 Title'],
                ['key' => 'merchant_step_2_text', 'label' => 'Merchant Step 2 Text', 'type' => 'textarea', 'rows' => 2],
                ['key' => 'merchant_step_3_title', 'label' => 'Merchant Step 3 Title'],
                ['key' => 'merchant_step_3_text', 'label' => 'Merchant Step 3 Text', 'type' => 'textarea', 'rows' => 2],
                ['key' => 'merchant_step_4_title', 'label' => 'Merchant Step 4 Title'],
                ['key' => 'merchant_step_4_text', 'label' => 'Merchant Step 4 Text', 'type' => 'textarea', 'rows' => 2],
                ['key' => 'merchant_onboarding_footer', 'label' => 'Merchant Onboarding Footer', 'type' => 'textarea', 'rows' => 2],
            ],
        ];
    }

    public function imageSections(): array
    {
        return [
            'Page Images' => array_values($this->imageFields()),
        ];
    }

    public function validationRules(): array
    {
        $rules = [];

        foreach ($this->textSections() as $fields) {
            foreach ($fields as $field) {
                $rules[$field['key']] = ['nullable', 'string', 'max:10000'];
            }
        }

        foreach (array_keys($this->imageFields()) as $key) {
            $rules[$key] = ['nullable', 'string', 'max:2048'];
            $rules[$key . '_file'] = ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif,svg', 'max:10240'];
        }

        return $rules;
    }

    public function update(Request $request, array $validated): void
    {
        $settings = $this->settings();

        foreach ($this->textSections() as $fields) {
            foreach ($fields as $field) {
                $key = $field['key'];
                $settings[$key] = trim((string) ($validated[$key] ?? $settings[$key] ?? ''));
            }
        }

        foreach (array_keys($this->imageFields()) as $key) {
            $incomingPath = trim((string) ($validated[$key] ?? $settings[$key] ?? ''));
            if ($incomingPath !== '') {
                $settings[$key] = $incomingPath;
            }

            if ($request->hasFile($key . '_file')) {
                $previous = (string) ($settings[$key] ?? '');
                $stored = $request->file($key . '_file')->store(self::STORAGE_DIR, 'public');
                $settings[$key] = $stored;
                $this->deleteManagedImage($previous);
            }
        }

        $this->persist($settings);
    }

    public function resolveImageUrl(?string $path): ?string
    {
        $value = trim((string) $path);
        if ($value === '') {
            return null;
        }

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://') || str_starts_with($value, 'data:')) {
            return $value;
        }

        if (str_starts_with($value, '/')) {
            return url($value);
        }

        if (str_starts_with($value, self::STORAGE_DIR . '/')) {
            return Storage::disk('public')->url($value);
        }

        return asset($value);
    }

    private function storedOverrides(): array
    {
        $raw = DB::table('settings')->where('key', self::DB_KEY)->value('value');
        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function persist(array $settings): void
    {
        $payload = [
            'value' => json_encode($settings, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ];

        if (Schema::hasColumn('settings', 'category')) {
            $payload['category'] = 'welcome_page';
        }

        DB::table('settings')->updateOrInsert(
            ['key' => self::DB_KEY],
            $payload
        );
    }

    private function deleteManagedImage(string $path): void
    {
        if ($path !== '' && str_starts_with($path, self::STORAGE_DIR . '/')) {
            Storage::disk('public')->delete($path);
        }
    }

    private function imageFields(): array
    {
        return [
            'og_image' => ['key' => 'og_image', 'label' => 'Open Graph Image'],
            'hero_background_image' => ['key' => 'hero_background_image', 'label' => 'Hero Background Image'],
            'shopping_card_image' => ['key' => 'shopping_card_image', 'label' => 'Shopping Card Image'],
            'slack_contact_image' => ['key' => 'slack_contact_image', 'label' => 'Slack Contact Image'],
            'fuel_preview_image' => ['key' => 'fuel_preview_image', 'label' => 'Fuel Preview Image'],
            'africa_card_image' => ['key' => 'africa_card_image', 'label' => 'Africa Card Image'],
            'renewable_gif_image' => ['key' => 'renewable_gif_image', 'label' => 'Renewable GIF Image'],
            'story_feature_image' => ['key' => 'story_feature_image', 'label' => 'Story Feature Image'],
            'tapless_modal_image' => ['key' => 'tapless_modal_image', 'label' => 'Tapless Modal Image'],
            'faq_question_avatar' => ['key' => 'faq_question_avatar', 'label' => 'FAQ Question Avatar'],
            'faq_answer_avatar' => ['key' => 'faq_answer_avatar', 'label' => 'FAQ Answer Avatar'],
            'merchant_pos_image' => ['key' => 'merchant_pos_image', 'label' => 'Merchant POS Image'],
            'countries_image' => ['key' => 'countries_image', 'label' => 'Merchant Preview Image'],
            'cups_image' => ['key' => 'cups_image', 'label' => 'Cups/Kiosk Image'],
        ];
    }

    private function defaults(): array
    {
        return [
            'meta_title' => 'Bwiser Fuel Buy Now Pay Later',
            'meta_description' => 'Bwiser is a South African fuel finance and payments platform for drivers, stations, vouchers, and settlements.',
            'hero_title_primary' => 'Fuel Infrastructure Finance and Voucher Payments,',
            'hero_title_secondary' => 'easy as padel',
            'hero_badge_text' => 'Built for Real-Time Operations',
            'trusted_network_label' => 'Trusted Retail Network',
            'auth_title' => 'Choose your Bwiser path',
            'auth_chip_text' => 'driver or merchant',
            'auth_description' => 'Start with registration, jump into merchant onboarding, or sign in if you already have access.',
            'mrd_title' => 'Mr D Special',
            'mrd_status' => 'Bwiser shoppers • Pay in 4',
            'mrd_description' => 'Built for Africa on the move—split your checkout into 4 easy repayments with trusted drivers and voucher-linked fuel access.',
            'order_grid_text' => 'PLACE' . "\n\n" . 'ORDER' . "\n\n" . 'HERE',
            'slack_title' => 'Request your place in the Bwiser merchant Slack',
            'slack_description' => 'Merchants can request early access to the Bwiser Slack workspace for rollout updates, onboarding help, and support coordination.',
            'slack_cta_text' => 'Request Slack Access',
            'slack_contact_name' => 'Finance Lead',
            'slack_contact_description' => 'Talk to Tony about funding, settlements, and repayments.',
            'renewable_gif_title' => 'Renewable vouchers. Leasing made simple.',
            'renewable_gif_text' => 'Bwiser helps fleets and merchants plan energy with voucher payments and leasing options—so renewable energy stays accessible, trackable, and ready for real operations.',
            'workflow_eyebrow' => 'How Bwiser works',
            'workflow_title' => 'From voucher to repayment',
            'workflow_step_1_title' => 'Create voucher',
            'workflow_step_1_text' => 'Issue a Bwiser voucher for a driver or lease with the amount, station, and due items, then share the voucher ID or QR.',
            'workflow_step_2_title' => 'Validate at station',
            'workflow_step_2_text' => 'The station validates the voucher with QR or USSD geofence on the POS, prints a receipt, and settles the payment.',
            'workflow_step_3_title' => 'Repayment runs',
            'workflow_step_3_text' => 'Repayments appear on the driver dashboard, and auto-pay can settle balances on due dates while sending confirmation emails.',
            'workflow_step_4_title' => 'Track performance',
            'workflow_step_4_text' => 'Monitor settlement reporting, due items, and performance across drivers, leases, and stations from one process.',
            'workflow_success_eyebrow' => 'Success',
            'workflow_success_text' => 'Fuel payments stay tracked end-to-end with simple vouchers, predictable repayments, and clean reporting.',
            'tapless_button_text' => 'Tapless payments',
            'case_study_eyebrow' => 'Case study',
            'case_study_title' => 'A founder story behind tapless payments',
            'case_study_p1' => 'After a difficult experience with an earlier payments initiative that Tlhologelo Mabitle felt left him unfairly treated, he decided to build something new for the Flowdosi Merchant Group instead of walking away from the problem.',
            'case_study_p2' => 'That decision led to a novel approach: USSD geofenced tapless payments designed for merchant environments, turning a frustrating setback into an unexpected breakthrough for Bwiser.',
            'case_study_p3' => 'In many ways, it was a happy accident: a reaction to a tough moment that opened the path to a first-of-its-kind payment experience.',
            'faq_eyebrow' => 'FAQ',
            'faq_title' => 'How USSD tapless payments work',
            'faq_question_1' => 'How do USSD tapless payments work if there is no physical card tap?',
            'faq_answer_1' => 'The driver or merchant starts the payment process with USSD, and Bwiser checks the voucher, the user, and the geofenced station context before the transaction is approved.',
            'faq_question_2' => 'So what makes it secure?',
            'faq_answer_2' => 'Security comes from combining voucher validation, location awareness, merchant rules, and repayment tracking instead of relying only on a plastic card tap.',
            'merchant_onboarding_eyebrow' => 'Merchant onboarding',
            'merchant_onboarding_title' => 'Get live in 4 quick steps',
            'merchant_onboarding_chip' => 'Fast setup',
            'merchant_step_1_title' => 'Register',
            'merchant_step_1_text' => 'Capture merchant, station, and business details to start onboarding.',
            'merchant_step_2_title' => 'Verify',
            'merchant_step_2_text' => 'Submit KYC and onboarding documents so the account can be approved.',
            'merchant_step_3_title' => 'Install',
            'merchant_step_3_text' => 'Set up the POS, train staff, and configure voucher validation for the site.',
            'merchant_step_4_title' => 'Go live',
            'merchant_step_4_text' => 'Start accepting Bwiser vouchers with reporting and support already in place.',
            'merchant_onboarding_footer' => 'Merchants move from signup to live voucher acceptance in one guided process.',
            'og_image' => 'images/NalediTsunke.png',
            'hero_background_image' => 'images/tennis.jpg',
            'shopping_card_image' => 'images/shopping.jpg',
            'slack_contact_image' => 'images/tony.jpg',
            'fuel_preview_image' => 'images/bwiserpngvoucher.png',
            'africa_card_image' => 'images/afrobwiser.png',
            'renewable_gif_image' => 'images/Bwiser_2.gif',
            'story_feature_image' => 'images/NalediTsunke.png',
            'tapless_modal_image' => 'images/BWISER.jpg',
            'faq_question_avatar' => 'images/ask.jpg',
            'faq_answer_avatar' => 'images/ans.jpg',
            'merchant_pos_image' => 'images/posbox1.jpg',
            'countries_image' => 'images/pos6.jpg',
            'cups_image' => 'images/cups.png',
        ];
    }
}
