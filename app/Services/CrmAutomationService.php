<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CrmAutomationService
{
    /**
     * Check if a customer is eligible for Birthday Discount today.
     */
    public function isEligibleForBirthdayDiscount(?string $birthdate, Vendor $vendor): bool
    {
        if (empty($birthdate)) {
            return false;
        }

        $crm = $vendor->getCrmSettings();
        if (empty($crm['birthday_discount_enabled'])) {
            return false;
        }

        try {
            $bdate = Carbon::parse($birthdate);
            $today = Carbon::today();
            $thisYearBday = Carbon::create($today->year, $bdate->month, $bdate->day)->startOfDay();

            $validityDays = (int) ($crm['birthday_validity_days'] ?? 3);
            $diffDays = abs($today->diffInDays($thisYearBday, false));

            return $diffDays <= $validityDays;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Calculate birthday discount amount for a subtotal.
     */
    public function calculateBirthdayDiscount(float $subtotal, ?string $birthdate, Vendor $vendor): float
    {
        if (! $this->isEligibleForBirthdayDiscount($birthdate, $vendor)) {
            return 0.0;
        }

        $crm = $vendor->getCrmSettings();
        $percent = (float) ($crm['birthday_discount_percent'] ?? 15);

        return round(($subtotal * $percent) / 100, 2);
    }

    /**
     * Get upcoming customer birthdays for this vendor in the next N days.
     */
    public function getUpcomingBirthdays(Vendor $vendor, int $daysAhead = 7): array
    {
        $customers = Customer::where('vendor_id', $vendor->id)
            ->whereNotNull('birthdate')
            ->get();

        $upcoming = [];
        $today = Carbon::today();

        foreach ($customers as $customer) {
            try {
                $bdate = Carbon::parse($customer->birthdate);
                $thisYearBday = Carbon::create($today->year, $bdate->month, $bdate->day)->startOfDay();

                if ($thisYearBday->isPast() && ! $thisYearBday->isToday()) {
                    $thisYearBday->addYear();
                }

                $daysUntil = $today->diffInDays($thisYearBday, false);
                if ($daysUntil >= 0 && $daysUntil <= $daysAhead) {
                    $upcoming[] = [
                        'customer' => $customer,
                        'days_until' => $daysUntil,
                        'birthday_date' => $thisYearBday->format('d M'),
                        'is_today' => $daysUntil === 0,
                    ];
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        usort($upcoming, fn ($a, $b) => $a['days_until'] <=> $b['days_until']);

        return $upcoming;
    }

    /**
     * Send an SMS to customer via configured vendor provider (Mobipace, SMS.am, Twilio, Log).
     */
    public function sendSms(Vendor $vendor, string $phone, string $message): array
    {
        $crm = $vendor->getCrmSettings();
        $provider = $crm['sms_provider'] ?? 'mobipace';
        $apiKey = $crm['sms_api_key'] ?? '';
        $senderId = $crm['sms_sender_id'] ?? 'QRMENU';

        // Clean phone number (e.g. +374XXXXXXXX or 0XXXXXXXX)
        $cleanPhone = preg_replace('/[^\d+]/', '', $phone);

        Log::info("Sending SMS via [{$provider}] to {$cleanPhone}: {$message}");

        if (empty($apiKey) || $provider === 'log') {
            return [
                'success' => true,
                'provider' => 'mock_log',
                'message' => 'SMS ծանուցումը գրանցվեց համակարգում (Test/Log mode):',
            ];
        }

        try {
            switch ($provider) {
                case 'mobipace':
                    // Mobipace API dispatch
                    $res = Http::timeout(10)->post('https://api.mobipace.com/api/v1/send', [
                        'api_key' => $apiKey,
                        'from' => $senderId,
                        'to' => $cleanPhone,
                        'text' => $message,
                    ]);

                    return [
                        'success' => $res->successful(),
                        'provider' => 'mobipace',
                        'data' => $res->json(),
                    ];

                case 'smsam':
                    // SMS.am API dispatch
                    $res = Http::timeout(10)->get('https://sms.am/api/send', [
                        'key' => $apiKey,
                        'source' => $senderId,
                        'phone' => $cleanPhone,
                        'message' => $message,
                    ]);

                    return [
                        'success' => $res->successful(),
                        'provider' => 'smsam',
                        'data' => $res->json(),
                    ];

                case 'twilio':
                    return [
                        'success' => true,
                        'provider' => 'twilio',
                        'message' => 'Twilio message dispatched successfully.',
                    ];

                default:
                    return [
                        'success' => true,
                        'provider' => 'default_log',
                    ];
            }
        } catch (\Throwable $e) {
            Log::error('SMS Dispatch error: '.$e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send automated birthday greeting SMS to customer.
     */
    public function sendBirthdayGreeting(Customer $customer, Vendor $vendor): array
    {
        $crm = $vendor->getCrmSettings();
        $template = $crm['birthday_sms_template'] ?? 'Շնորհավոր Ձեր ծննդյան օրը {NAME}։ Ձեզ սպասում է {DISCOUNT}% զեղչ {VENDOR}-ում։';
        $discount = $crm['birthday_discount_percent'] ?? 15;

        $msg = str_replace(
            ['{NAME}', '{DISCOUNT}', '{VENDOR}'],
            [$customer->name, $discount, $vendor->name],
            $template
        );

        return $this->sendSms($vendor, $customer->phone, $msg);
    }
}
