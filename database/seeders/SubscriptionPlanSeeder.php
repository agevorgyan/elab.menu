<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Basic',
                'slug' => 'basic',
                'price' => 9900,
                'currency' => 'AMD',
                'billing_interval' => 'monthly',
                'duration_days' => 30,
                'trial_days' => 14,
                'description' => 'Թվային մենյու փոքր ռեստորանների և սրճարանների համար',
                'features' => [
                    'QR մենյու',
                    'Ապրանքների, նկարների և գների կառավարում',
                    'Բազմալեզու մենյու',
                    'QR կոդերի ստեղծում',
                    'Հիմնական վիճակագրություն',
                ],
                'is_active' => true,
                'is_custom' => false,
                'sort_order' => 1,
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'price' => 19900,
                'currency' => 'AMD',
                'billing_interval' => 'monthly',
                'duration_days' => 30,
                'trial_days' => 14,
                'description' => 'QR Menu + օնլայն պատվերներ սեղաններից',
                'features' => [
                    'Basic-ի բոլոր հնարավորությունները',
                    'Օնլայն պատվերներ',
                    'Սեղանի համարով պատվիրում',
                    'Պատվերների կառավարման վահանակ',
                    'Պատվերի կարգավիճակներ',
                    'QR կոդեր՝ ըստ սեղանների',
                    'Վաճառքի և պատվերների վիճակագրություն',
                ],
                'is_active' => true,
                'is_custom' => false,
                'sort_order' => 2,
            ],
            [
                'name' => 'Business',
                'slug' => 'business',
                'price' => 34900,
                'currency' => 'AMD',
                'billing_interval' => 'monthly',
                'duration_days' => 30,
                'trial_days' => 14,
                'description' => 'Բազմամասնաճյուղ և ընդլայնված կառավարում ցանցերի համար',
                'features' => [
                    'Pro-ի բոլոր հնարավորությունները',
                    'Մի քանի մասնաճյուղի կառավարում',
                    'Աշխատակիցների դերեր',
                    'Ընդլայնված հաշվետվություններ',
                    'Առաջնահերթ աջակցություն',
                    'Ինտեգրացիաների հնարավորություն',
                ],
                'is_active' => true,
                'is_custom' => false,
                'sort_order' => 3,
            ],
            [
                'name' => 'Custom',
                'slug' => 'custom',
                'price' => 0,
                'currency' => 'AMD',
                'billing_interval' => 'custom',
                'duration_days' => 30,
                'trial_days' => 14,
                'description' => 'Պայմանագրային գին՝ կախված հաճախորդի ցանկություններից և ֆունկցիոնալությունից',
                'features' => [
                    'Անհատական ֆունկցիոնալություն',
                    'Անհատական ինտեգրացիաներ',
                    'Առանձնացված սերվերային ռեսուրսներ',
                    '24/7 Անհատական մենեջեր',
                ],
                'is_active' => true,
                'is_custom' => true,
                'sort_order' => 4,
            ],
        ];

        foreach ($plans as $p) {
            SubscriptionPlan::updateOrCreate(['slug' => $p['slug']], $p);
        }
    }
}
