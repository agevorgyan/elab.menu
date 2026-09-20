@extends('layouts.app')

@section('title', __('Իմ Բաժանորդագրությունը') . ' - ' . $vendor->name)

@section('content')
<div class="page-header" style="margin-bottom: 2rem;">
    <h1 style="font-family: 'Outfit', sans-serif; font-size: clamp(1.4rem, 2.5vw, 1.85rem); font-weight: 800; color: var(--text-main); margin: 0; display: flex; align-items: center; gap: 0.75rem;">
        <span style="background: rgba(245, 158, 11, 0.15); color: var(--primary); width: 44px; height: 44px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0;">
            <i class="fa-solid fa-file-invoice-dollar"></i>
        </span>
        <span>{{ __('Բաժանորդագրություն & Վճարումներ') }}</span>
    </h1>
    <p style="color: var(--text-muted); font-size: 0.9rem; margin: 0.35rem 0 0; word-break: break-word;">
        {{ __('Դիտեք Ձեր ընթացիկ բաժանորդագրության կարգավիճակը, հնարավորությունները և վճարումների պատմությունը') }}
    </p>
</div>

<!-- Subscription Status Overview Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 300px), 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
    <!-- Current Plan Card -->
    <div class="card" style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 20px; padding: clamp(1.25rem, 3vw, 1.85rem); position: relative; overflow: hidden; box-shadow: var(--shadow-card); display: flex; flex-direction: column; justify-content: space-between;">
        <div style="position: absolute; top: 0; left: 0; right: 0; height: 4px; background: linear-gradient(90deg, var(--primary), #ea580c);"></div>

        <div>
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.75rem;">
                <div>
                    <span style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; letter-spacing: 0.04em;">{{ __('Ընթացիկ Փաթեթ') }}</span>
                    <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.6rem; font-weight: 800; color: var(--text-main); margin: 0.2rem 0 0 0; word-break: break-word;">
                        {{ $vendor->plan?->name ?? strtoupper($vendor->subscription_plan) }}
                    </h3>
                </div>
                @if($vendor->isExpired())
                    <span class="badge" style="background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 8px; padding: 0.3rem 0.65rem; font-size: 0.78rem; font-weight: 700;">
                        🔴 {{ __('Ավարտված / Անջատված') }}
                    </span>
                @elseif($vendor->isTrialing())
                    <span class="badge" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 8px; padding: 0.3rem 0.65rem; font-size: 0.78rem; font-weight: 700;">
                        ⏳ {{ __('Փորձնական (14 օր)') }}
                    </span>
                @else
                    <span class="badge" style="background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 8px; padding: 0.3rem 0.65rem; font-size: 0.78rem; font-weight: 700;">
                        🟢 {{ __('Ակտիվ') }}
                    </span>
                @endif
            </div>

            <div style="font-family: 'Outfit', sans-serif; font-size: 1.5rem; font-weight: 800; color: var(--primary); margin-bottom: 0.75rem;">
                {{ $vendor->plan?->formatted_price ?? '—' }}
            </div>

            <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.25rem;">
                @if($vendor->isTrialing())
                    <div>⏱ {{ __('Փորձնական շրջանի ավարտ՝') }} <strong>{{ $vendor->trial_ends_at ? $vendor->trial_ends_at->format('d.m.Y H:i') : '—' }}</strong></div>
                @else
                    <div>📅 {{ __('Բաժանորդագրության ավարտ՝') }} <strong>{{ $vendor->subscription_expires_at ? $vendor->subscription_expires_at->format('d.m.Y') : __('Անսահմանափակ') }}</strong></div>
                @endif
            </div>
        </div>

        <div style="background: var(--bg-body); border: 1px solid var(--border-color); padding: 0.85rem 1.15rem; border-radius: 14px; display: flex; justify-content: space-between; align-items: center;">
            <span style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;">{{ __('Մնացած ժամկետ՝') }}</span>
            <span style="font-family: 'Outfit', sans-serif; font-size: 1.2rem; font-weight: 800; color: {{ $vendor->daysLeft() <= 3 ? '#ef4444' : '#10b981' }};">
                {{ $vendor->daysLeft() }} {{ __('օր') }}
            </span>
        </div>
    </div>

    <!-- Quick Upgrade / Contact Card -->
    <div class="card" style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 20px; padding: clamp(1.25rem, 3vw, 1.85rem); position: relative; overflow: hidden; box-shadow: var(--shadow-card); display: flex; flex-direction: column; justify-content: space-between;">
        <div>
            <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.25rem; font-weight: 800; color: var(--text-main); margin: 0 0 0.5rem 0; display: flex; align-items: center; gap: 0.5rem;">
                <span>🚀</span> <span>{{ __('Փոխե՞լ կամ Երկարաձգե՞լ Փաթեթը') }}</span>
            </h3>
            <p style="font-size: 0.88rem; color: var(--text-muted); line-height: 1.6; margin: 0; word-break: break-word;">
                {{ __('Ցանկանո՞ւմ եք անցնել ավելի բարձր փաթեթի (Basic -> Pro / Business / Custom) կամ երկարաձգել Ձեր ընթացիկ բաժանորդագրությունը։ Կապ հաստատեք ադմինիստրացիայի հետ։') }}
            </p>
        </div>

        <div style="margin-top: 1.5rem;">
            <a href="mailto:support@qrmenu.local?subject=Subscription%20Upgrade%20Request%20-%20{{ urlencode($vendor->name) }}" class="btn btn-primary" style="width: 100%; box-sizing: border-box; justify-content: center; border-radius: 12px; font-weight: 700; padding: 0.8rem 1.5rem; box-shadow: 0 4px 14px rgba(245, 158, 11, 0.3);">
                <i class="fa-solid fa-headset"></i> {{ __('Կապ Հաստատել Ադմինիստրացիայի Հետ') }}
            </a>
        </div>
    </div>
</div>

<!-- Included Features Section -->
<div class="card" style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 20px; padding: clamp(1.25rem, 3vw, 1.85rem); margin-bottom: 2rem; box-shadow: var(--shadow-card);">
    <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.2rem; font-weight: 800; color: var(--text-main); margin: 0 0 1.25rem 0; display: flex; align-items: center; gap: 0.5rem;">
        <span style="color: var(--primary);"><i class="fa-solid fa-sparkles"></i></span>
        <span>{{ __('Ձեր Փաթեթում Ներառված Հնարավորությունները') }}</span>
    </h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 240px), 1fr)); gap: 0.85rem;">
        @foreach($vendor->plan?->features ?? ['QR մենյու', 'Ապրանքների, նկարների և գների կառավարում', 'Բազմալեզու մենյու', 'QR կոդերի ստեղծում', 'Հիմնական վիճակագրություն'] as $feat)
            <div style="display: flex; align-items: center; gap: 0.65rem; background: var(--bg-body); border: 1px solid var(--border-color); padding: 0.75rem 1rem; border-radius: 12px; font-size: 0.88rem; color: var(--text-main); font-weight: 600;">
                <i class="fa-solid fa-circle-check" style="color: #10b981; flex-shrink: 0;"></i>
                <span style="word-break: break-word;">{{ $feat }}</span>
            </div>
        @endforeach
    </div>
</div>

<!-- Available Plans Comparison -->
<div style="margin-bottom: 2rem;">
    <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.25rem; font-weight: 800; color: var(--text-main); margin: 0 0 1.25rem 0; display: flex; align-items: center; gap: 0.5rem;">
        <span style="color: var(--primary);"><i class="fa-solid fa-list-check"></i></span>
        <span>{{ __('Բաժանորդագրությունների Բոլոր Փաթեթները') }}</span>
    </h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 260px), 1fr)); gap: 1.25rem;">
        @foreach($allPlans as $plan)
            <div class="card" style="background: var(--bg-card); border: 1px solid {{ $vendor->subscription_plan_id == $plan->id ? 'var(--primary)' : 'var(--border-color)' }}; border-radius: 20px; padding: 1.5rem; position: relative; box-shadow: var(--shadow-card); display: flex; flex-direction: column; justify-content: space-between;">
                @if($vendor->subscription_plan_id == $plan->id)
                    <span style="position: absolute; top: -10px; right: 15px; background: var(--primary); color: #000; font-size: 0.7rem; font-weight: 800; padding: 0.2rem 0.7rem; border-radius: 9999px; letter-spacing: 0.04em;">
                        {{ __('ԸՆԹԱՑԻԿ') }}
                    </span>
                @endif
                <div>
                    <h4 style="font-family: 'Outfit', sans-serif; font-size: 1.2rem; font-weight: 800; color: var(--text-main); margin: 0;">{{ $plan->name }}</h4>
                    <div style="font-family: 'Outfit', sans-serif; font-size: 1.4rem; font-weight: 800; color: var(--primary); margin: 0.4rem 0;">
                        {{ $plan->formatted_price }}
                    </div>
                    <p style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 1rem; line-height: 1.5; word-break: break-word;">
                        {{ $plan->description }}
                    </p>
                    <ul style="list-style: none; padding: 0; margin: 0; font-size: 0.82rem; color: var(--text-muted); display: flex; flex-direction: column; gap: 0.4rem;">
                        @foreach($plan->features ?? [] as $f)
                            <li style="display: flex; align-items: flex-start; gap: 0.4rem; word-break: break-word;">
                                <i class="fa-solid fa-check" style="color: #10b981; margin-top: 0.2rem; flex-shrink: 0;"></i>
                                <span>{{ $f }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endforeach
    </div>
</div>

<!-- Payment History Table -->
<div class="card" style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 20px; padding: 0; overflow: hidden; box-shadow: var(--shadow-card);">
    <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border-color);">
        <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.15rem; font-weight: 800; color: var(--text-main); margin: 0; display: flex; align-items: center; gap: 0.5rem;">
            <span style="color: var(--primary);"><i class="fa-solid fa-receipt"></i></span>
            <span>{{ __('Վճարումների Պատմություն (Payment History)') }}</span>
        </h3>
    </div>

    @if($vendor->payments->count() > 0)
        <div class="responsive-table-wrapper">
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
                <thead>
                    <tr style="background: var(--bg-body); border-bottom: 1px solid var(--border-color); color: var(--text-muted); font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.04em;">
                        <th style="padding: 1rem 1.25rem;">{{ __('Հաշիվ #') }}</th>
                        <th style="padding: 1rem 1.25rem;">{{ __('Ամսաթիվ') }}</th>
                        <th style="padding: 1rem 1.25rem;">{{ __('Փաթեթ') }}</th>
                        <th style="padding: 1rem 1.25rem;">{{ __('Գումար') }}</th>
                        <th style="padding: 1rem 1.25rem;">{{ __('Ժամանակահատված') }}</th>
                        <th style="padding: 1rem 1.25rem;">{{ __('Եղանակ') }}</th>
                        <th style="padding: 1rem 1.25rem; text-align: right;">{{ __('Կարգավիճակ') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($vendor->payments as $p)
                        <tr style="border-bottom: 1px solid var(--border-color); color: var(--text-main); transition: background 0.15s ease;">
                            <td style="padding: 1rem 1.25rem; font-weight: 700; color: var(--text-main); font-family: 'Outfit', sans-serif;">{{ $p->invoice_number ?? '—' }}</td>
                            <td style="padding: 1rem 1.25rem; font-size: 0.85rem; color: var(--text-muted); white-space: nowrap;">{{ $p->created_at->format('d.m.Y H:i') }}</td>
                            <td style="padding: 1rem 1.25rem; font-weight: 600;">{{ $p->plan?->name ?? 'Standard' }}</td>
                            <td style="padding: 1rem 1.25rem; font-weight: 800; color: #10b981; font-family: 'Outfit', sans-serif; white-space: nowrap;">{{ number_format($p->amount, 0, '.', ' ') }} {{ $p->currency }}</td>
                            <td style="padding: 1rem 1.25rem; font-size: 0.82rem; color: var(--text-muted); white-space: nowrap;">
                                {{ $p->period_start ? $p->period_start->format('d.m.Y') : '—' }} - {{ $p->period_end ? $p->period_end->format('d.m.Y') : '—' }}
                            </td>
                            <td style="padding: 1rem 1.25rem; font-size: 0.85rem; font-weight: 600;">{{ ucfirst(str_replace('_', ' ', $p->payment_method)) }}</td>
                            <td style="padding: 1rem 1.25rem; text-align: right; white-space: nowrap;">
                                <span class="badge" style="background: {{ $p->status == 'paid' ? 'rgba(16, 185, 129, 0.15)' : 'rgba(245, 158, 11, 0.15)' }}; color: {{ $p->status == 'paid' ? '#10b981' : '#f59e0b' }}; border: 1px solid {{ $p->status == 'paid' ? 'rgba(16, 185, 129, 0.3)' : 'rgba(245, 158, 11, 0.3)' }}; padding: 0.25rem 0.6rem; border-radius: 8px; font-size: 0.78rem; font-weight: 700;">
                                    {{ ucfirst($p->status) }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div style="text-align: center; padding: 3.5rem 1rem; color: var(--text-muted);">
            <div style="width: 56px; height: 56px; border-radius: 16px; background: rgba(245, 158, 11, 0.1); color: var(--primary); display: inline-flex; align-items: center; justify-content: center; font-size: 1.6rem; margin-bottom: 0.85rem;">
                <i class="fa-solid fa-receipt"></i>
            </div>
            <div style="font-size: 1rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.35rem;">{{ __('Վճարումների պատմություն դեռ առկա չէ') }}</div>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0;">{{ __('Դուք այժմ գտնվում եք փորձնական 14-օրյա անվճար շրջանում:') }}</p>
        </div>
    @endif
</div>
@endsection
