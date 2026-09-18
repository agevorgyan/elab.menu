@extends('layouts.app')

@section('content')
<div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <div>
        <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--text-main); margin-bottom: 0.25rem;">
            <i class="fa-solid fa-credit-card" style="color: var(--primary);"></i> Վենդորների Բաժանորդագրություններ & Վճարումներ
        </h2>
        <p style="color: var(--text-muted); font-size: 0.88rem;">Վերահսկեք վենդորների բաժանորդագրության կարգավիճակները, վերջնաժամկետները, երկարաձգումները և վճարումների պատմությունը։</p>
    </div>
</div>

<div class="glass-card" style="padding: 1.25rem;">
    <div style="overflow-x: auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Վենդոր</th>
                    <th>Փաթեթ</th>
                    <th>Կարգավիճակ</th>
                    <th>Փորձնական / Վերջնաժամկետ</th>
                    <th>Մնացած օրեր</th>
                    <th>Վերջին Վճարում</th>
                    <th style="text-align: right;">Գործողություններ</th>
                </tr>
            </thead>
            <tbody>
                @foreach($vendors as $v)
                    <tr>
                        <td>
                            <div style="font-weight: 700; color: var(--text-main);">{{ $v->name }}</div>
                            <div style="font-size: 0.78rem; color: var(--text-muted);">{{ $v->email }} | {{ $v->phone ?? 'Հեռ․ չկա' }}</div>
                        </td>
                        <td>
                            <span class="badge" style="background: rgba(99, 102, 241, 0.15); color: #6366f1; border: 1px solid rgba(99, 102, 241, 0.3);">
                                {{ $v->plan?->name ?? strtoupper($v->subscription_plan ?? 'PRO') }}
                            </span>
                            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.2rem;">
                                {{ $v->plan?->formatted_price ?? '—' }}
                            </div>
                        </td>
                        <td>
                            @if($v->isExpired())
                                <span class="badge" style="background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);">
                                    🔴 Ավարտված (Անջատված)
                                </span>
                            @elseif($v->isTrialing())
                                <span class="badge" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3);">
                                    ⏳ Փորձնական (14 օր)
                                </span>
                            @else
                                <span class="badge" style="background: rgba(34, 197, 94, 0.15); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.3);">
                                    🟢 Ակտիվ
                                </span>
                            @endif
                        </td>
                        <td style="font-size: 0.85rem; color: var(--text-main);">
                            @if($v->isTrialing())
                                <div>Trial ավարտ՝ <strong>{{ $v->trial_ends_at ? $v->trial_ends_at->format('d.m.Y H:i') : '—' }}</strong></div>
                            @else
                                <div>Ավարտ՝ <strong>{{ $v->subscription_expires_at ? $v->subscription_expires_at->format('d.m.Y') : 'Անսահմանափակ' }}</strong></div>
                            @endif
                        </td>
                        <td>
                            @if($v->isExpired())
                                <span style="color: #ef4444; font-weight: 700;">0 օր</span>
                            @else
                                <span style="color: {{ $v->daysLeft() <= 3 ? '#f59e0b' : '#22c55e' }}; font-weight: 700;">
                                    {{ $v->daysLeft() }} օր
                                </span>
                            @endif
                        </td>
                        <td>
                            @if($v->payments->first())
                                <div style="font-weight: 700; color: #22c55e; font-size: 0.85rem;">
                                    {{ number_format($v->payments->first()->amount, 0, '.', ' ') }} {{ $v->payments->first()->currency }}
                                </div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">
                                    {{ $v->payments->first()->created_at->format('d.m.Y') }} ({{ $v->payments->first()->payment_method }})
                                </div>
                            @else
                                <span style="font-size: 0.8rem; color: var(--text-muted);">Վճարում չկա</span>
                            @endif
                        </td>
                        <td style="text-align: right;">
                            <div style="display: flex; gap: 0.4rem; justify-content: flex-end;">
                                <button class="btn btn-secondary" style="font-size: 0.78rem; padding: 0.35rem 0.6rem;" title="Կարգավորել բաժանորդագրությունը" onclick="openSubModal({{ json_encode($v) }})">
                                    <i class="fa-solid fa-pen-to-square"></i> Փոխել
                                </button>
                                <button class="btn btn-primary" style="font-size: 0.78rem; padding: 0.35rem 0.6rem;" title="Գրանցել Վճարում" onclick="openPaymentModal({{ json_encode($v) }})">
                                    <i class="fa-solid fa-receipt"></i> + Վճարում
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Subscription Modal -->
<div id="subModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.75); backdrop-filter: blur(8px); z-index: 1000; align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: var(--bg-card); border: 1px solid var(--border-color); box-shadow: 0 25px 60px rgba(0,0,0,0.6); width: 100%; max-width: 500px; padding: 1.75rem; border-radius: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
            <h3 style="font-weight: 800; font-size: 1.2rem; color: var(--text-main);" id="subModalTitle">Կարգավորել Բաժանորդագրությունը</h3>
            <button onclick="document.getElementById('subModal').style.display='none'" style="background:none; border:none; color:var(--text-main); cursor:pointer; font-size:1.2rem;">✕</button>
        </div>

        <form id="subForm" method="POST">
            @csrf
            <div style="margin-bottom: 1rem;">
                <label class="form-label">Բաժանորդագրության Փաթեթ *</label>
                <select id="sub_plan_id" name="subscription_plan_id" class="form-select" required>
                    @foreach($plans as $p)
                        <option value="{{ $p->id }}">{{ $p->name }} — {{ $p->formatted_price }}</option>
                    @endforeach
                </select>
            </div>

            <div style="margin-bottom: 1rem;">
                <label class="form-label">Կարգավիճակ (Status) *</label>
                <select id="sub_status" name="subscription_status" class="form-select" required>
                    <option value="trialing">Փորձնական (14 օր Trial)</option>
                    <option value="active">Ակտիվ (Active)</option>
                    <option value="expired">Ավարտված / Անջատված (Expired)</option>
                    <option value="cancelled">Չեղարկված (Cancelled)</option>
                </select>
            </div>

            <div style="margin-bottom: 1rem;">
                <label class="form-label">Վերջնաժամկետ (Expires At)</label>
                <input type="date" id="sub_expires_at" name="subscription_expires_at" class="form-input">
            </div>

            <div style="margin-bottom: 1.25rem;">
                <label class="form-label">Անհատական Նշումներ (Custom Plan Notes)</label>
                <textarea id="sub_custom_notes" name="custom_plan_notes" rows="3" class="form-textarea" placeholder="օր․ Անհատական պայմանավորվածություն..."></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('subModal').style.display='none'">Չեղարկել</button>
                <button type="submit" class="btn btn-primary">Պահպանել Փոփոխությունները</button>
            </div>
        </form>
    </div>
</div>

<!-- Record Payment Modal -->
<div id="paymentModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.75); backdrop-filter: blur(8px); z-index: 1000; align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: var(--bg-card); border: 1px solid var(--border-color); box-shadow: 0 25px 60px rgba(0,0,0,0.6); width: 100%; max-width: 500px; padding: 1.75rem; border-radius: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
            <h3 style="font-weight: 800; font-size: 1.2rem; color: var(--text-main);" id="payModalTitle">Գրանցել Վճարում</h3>
            <button onclick="document.getElementById('paymentModal').style.display='none'" style="background:none; border:none; color:var(--text-main); cursor:pointer; font-size:1.2rem;">✕</button>
        </div>

        <form id="payForm" method="POST">
            @csrf
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label class="form-label">Գումար (AMD) *</label>
                    <input type="number" id="pay_amount" name="amount" step="100" min="0" class="form-input" required placeholder="19900">
                </div>
                <div>
                    <label class="form-label">Վճարման եղանակ</label>
                    <select name="payment_method" class="form-select">
                        <option value="bank_transfer">Բանկային Փոխանցում</option>
                        <option value="card">Բանկային Քարտ</option>
                        <option value="cash">Կանխիկ</option>
                        <option value="custom">Այլ / Պայմանագրային</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label class="form-label">Ժամանակահատված Սկիզբ *</label>
                    <input type="date" name="period_start" class="form-input" required value="{{ date('Y-m-d') }}">
                </div>
                <div>
                    <label class="form-label">Ժամանակահատված Ավարտ *</label>
                    <input type="date" name="period_end" class="form-input" required value="{{ date('Y-m-d', strtotime('+30 days')) }}">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label class="form-label">Հաշիվ-Ապրանքագիր #</label>
                    <input type="text" name="invoice_number" class="form-input" placeholder="INV-10045">
                </div>
                <div>
                    <label class="form-label">Վճարման Կարգավիճակ</label>
                    <select name="status" class="form-select">
                        <option value="paid">Վճարված (Paid)</option>
                        <option value="pending">Սպասման մեջ (Pending)</option>
                        <option value="failed">Չհաջողված (Failed)</option>
                    </select>
                </div>
            </div>

            <div style="margin-bottom: 1.25rem;">
                <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; color: var(--text-main); cursor: pointer;">
                    <input type="checkbox" name="extend_subscription" value="1" checked>
                    <span>Ավտոմատ երկարաձգել վենդորի բաժանորդագրությունը մինչև Ավարտի ամսաթիվը</span>
                </label>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('paymentModal').style.display='none'">Չեղարկել</button>
                <button type="submit" class="btn btn-primary">Գրանցել Վճարումը</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openSubModal(vendor) {
        document.getElementById('subModalTitle').innerText = 'Կարգավորել՝ ' + vendor.name;
        document.getElementById('subForm').action = '/superadmin/subscriptions/' + vendor.id;
        document.getElementById('sub_plan_id').value = vendor.subscription_plan_id || '';
        document.getElementById('sub_status').value = vendor.subscription_status || 'active';
        document.getElementById('sub_expires_at').value = vendor.subscription_expires_at ? vendor.subscription_expires_at.split('T')[0] : '';
        document.getElementById('sub_custom_notes').value = vendor.custom_plan_notes || '';

        document.getElementById('subModal').style.display = 'flex';
    }

    function openPaymentModal(vendor) {
        document.getElementById('payModalTitle').innerText = 'Վճարում՝ ' + vendor.name;
        document.getElementById('payForm').action = '/superadmin/subscriptions/' + vendor.id + '/payments';
        if (vendor.plan) {
            document.getElementById('pay_amount').value = vendor.plan.price;
        }

        document.getElementById('paymentModal').style.display = 'flex';
    }
</script>
@endsection
