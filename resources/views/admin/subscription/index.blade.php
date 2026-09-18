@extends('layouts.app')

@section('content')
<div class="page-header" style="margin-bottom: 1.5rem;">
    <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--text-main); margin-bottom: 0.25rem;">
        <i class="fa-solid fa-file-invoice-dollar" style="color: var(--primary);"></i> Իմ Բաժանորդագրությունը & Վճարումների Պատմությունը
    </h2>
    <p style="color: var(--text-muted); font-size: 0.88rem;">Դիտեք Ձեր ընթացիկ բաժանորդագրության կարգավիճակը, հնարավորությունները և վճարումների պատմությունը։</p>
</div>

<!-- Subscription Status Overview Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem; margin-bottom: 2rem;">
    <!-- Current Plan Card -->
    <div class="glass-card" style="padding: 1.5rem; border-top: 4px solid var(--primary);">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
            <div>
                <span style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Ընթացիկ Փաթեթ</span>
                <h3 style="font-size: 1.5rem; font-weight: 800; color: var(--text-main); margin-top: 0.2rem;">
                    {{ $vendor->plan?->name ?? strtoupper($vendor->subscription_plan) }}
                </h3>
            </div>
            @if($vendor->isExpired())
                <span class="badge" style="background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);">
                    🔴 Ավարտված / Անջատված
                </span>
            @elseif($vendor->isTrialing())
                <span class="badge" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3);">
                    ⏳ Փորձնական (14 օր)
                </span>
            @else
                <span class="badge" style="background: rgba(34, 197, 94, 0.15); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.3);">
                    🟢 Ակտիվ
                </span>
            @endif
        </div>

        <div style="font-size: 1.25rem; font-weight: 800; color: var(--primary); margin-bottom: 0.5rem;">
            {{ $vendor->plan?->formatted_price ?? '—' }}
        </div>

        <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1rem;">
            @if($vendor->isTrialing())
                <div>⏱ Փորձնական շրջանի ավարտ՝ <strong>{{ $vendor->trial_ends_at ? $vendor->trial_ends_at->format('d.m.Y H:i') : '—' }}</strong></div>
            @else
                <div>📅 Բաժանորդագրության ավարտ՝ <strong>{{ $vendor->subscription_expires_at ? $vendor->subscription_expires_at->format('d.m.Y') : 'Անսահմանափակ' }}</strong></div>
            @endif
        </div>

        <div style="background: var(--bg-main); border: 1px solid var(--border-color); padding: 0.75rem 1rem; border-radius: 12px; display: flex; justify-content: space-between; align-items: center;">
            <span style="font-size: 0.85rem; color: var(--text-muted);">Մնացած ժամկետ՝</span>
            <span style="font-size: 1.1rem; font-weight: 800; color: {{ $vendor->daysLeft() <= 3 ? '#ef4444' : '#22c55e' }};">
                {{ $vendor->daysLeft() }} օր
            </span>
        </div>
    </div>

    <!-- Quick Upgrade / Contact Card -->
    <div class="glass-card" style="padding: 1.5rem; display: flex; flex-direction: column; justify-content: space-between;">
        <div>
            <h3 style="font-size: 1.1rem; font-weight: 800; color: var(--text-main); margin-bottom: 0.5rem;">
                🚀 Փոխե՞լ կամ Երկարաձգե՞լ Փաթեթը
            </h3>
            <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.5;">
                Ցանկանո՞ւմ եք անցնել ավելի բարձր փաթեթի (Basic -> Pro / Business / Custom) կամ երկարաձգել Ձեր ընթացիկ բաժանորդագրությունը։ Կապ հաստատեք ադմինիստրացիայի հետ։
            </p>
        </div>

        <div style="margin-top: 1.25rem;">
            <a href="mailto:support@qrmenu.local?subject=Subscription%20Upgrade%20Request%20-%20{{ urlencode($vendor->name) }}" class="btn btn-primary" style="width: 100%; justify-content: center;">
                <i class="fa-solid fa-headset"></i> Կապ Հաստատել Ադմինի Հետ
            </a>
        </div>
    </div>
</div>

<!-- Included Features Section -->
<div class="glass-card" style="padding: 1.5rem; margin-bottom: 2rem;">
    <h3 style="font-size: 1.1rem; font-weight: 800; color: var(--text-main); margin-bottom: 1rem;">
        ✨ Ձեր Փաթեթում Ներառված Հնարավորությունները
    </h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 1rem;">
        @foreach($vendor->plan?->features ?? ['QR մենյու', 'Ապրանքների, նկարների և գների կառավարում', 'Բազմալեզու մենյու', 'QR կոդերի ստեղծում', 'Հիմնական վիճակագրություն'] as $feat)
            <div style="display: flex; align-items: center; gap: 0.6rem; background: var(--bg-main); border: 1px solid var(--border-color); padding: 0.75rem 1rem; border-radius: 10px; font-size: 0.85rem; color: var(--text-main);">
                <i class="fa-solid fa-circle-check" style="color: #22c55e;"></i>
                <span>{{ $feat }}</span>
            </div>
        @endforeach
    </div>
</div>

<!-- Available Plans Comparison -->
<div style="margin-bottom: 2rem;">
    <h3 style="font-size: 1.2rem; font-weight: 800; color: var(--text-main); margin-bottom: 1rem;">
        📋 Բաժանորդագրությունների Բոլոր Փաթեթները
    </h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 1.25rem;">
        @foreach($allPlans as $plan)
            <div class="glass-card" style="padding: 1.25rem; position: relative; {{ $vendor->subscription_plan_id == $plan->id ? 'border: 2px solid var(--primary);' : '' }}">
                @if($vendor->subscription_plan_id == $plan->id)
                    <span style="position: absolute; top: -10px; right: 15px; background: var(--primary); color: #fff; font-size: 0.7rem; font-weight: 800; padding: 0.15rem 0.6rem; border-radius: 20px;">
                        ԸՆԹԱՑԻԿ
                    </span>
                @endif
                <h4 style="font-size: 1.1rem; font-weight: 800; color: var(--text-main);">{{ $plan->name }}</h4>
                <div style="font-size: 1.25rem; font-weight: 800; color: var(--primary); margin: 0.4rem 0;">
                    {{ $plan->formatted_price }}
                </div>
                <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.75rem;">
                    {{ $plan->description }}
                </p>
                <ul style="list-style: none; padding: 0; margin: 0; font-size: 0.78rem; color: var(--text-muted);">
                    @foreach($plan->features ?? [] as $f)
                        <li style="margin-bottom: 0.3rem;">✓ {{ $f }}</li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>
</div>

<!-- Payment History Table -->
<div class="glass-card" style="padding: 1.5rem;">
    <h3 style="font-size: 1.1rem; font-weight: 800; color: var(--text-main); margin-bottom: 1rem;">
        🧾 Վճարումների Պատմություն (Payment History)
    </h3>

    @if($vendor->payments->count() > 0)
        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Հաշիվ #</th>
                        <th>Ամսաթիվ</th>
                        <th>Փաթեթ</th>
                        <th>Գումար</th>
                        <th>Ժամանակահատված</th>
                        <th>Եղանակ</th>
                        <th>Կարգավիճակ</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($vendor->payments as $p)
                        <tr>
                            <td style="font-weight: 700; color: var(--text-main);">{{ $p->invoice_number ?? '—' }}</td>
                            <td style="font-size: 0.85rem; color: var(--text-muted);">{{ $p->created_at->format('d.m.Y H:i') }}</td>
                            <td>{{ $p->plan?->name ?? 'Standard' }}</td>
                            <td style="font-weight: 700; color: #22c55e;">{{ number_format($p->amount, 0, '.', ' ') }} {{ $p->currency }}</td>
                            <td style="font-size: 0.8rem; color: var(--text-muted);">
                                {{ $p->period_start ? $p->period_start->format('d.m.Y') : '—' }} - {{ $p->period_end ? $p->period_end->format('d.m.Y') : '—' }}
                            </td>
                            <td style="font-size: 0.85rem;">{{ ucfirst(str_replace('_', ' ', $p->payment_method)) }}</td>
                            <td>
                                <span class="badge" style="background: {{ $p->status == 'paid' ? 'rgba(34, 197, 94, 0.15)' : 'rgba(245, 158, 11, 0.15)' }}; color: {{ $p->status == 'paid' ? '#22c55e' : '#f59e0b' }};">
                                    {{ ucfirst($p->status) }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div style="text-align: center; padding: 2rem; color: var(--text-muted);">
            <i class="fa-solid fa-receipt" style="font-size: 2.5rem; opacity: 0.4; margin-bottom: 0.5rem;">
                <div>Վճարումների պատմություն դեռ առկա չէ։</div>
                <small>Փորձնական 14-օրյա անվճար շրջանում եք։</small>
            </i>
        </div>
    @endif
</div>
@endsection
