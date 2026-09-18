@extends('layouts.app')

@section('content')
<div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <div>
        <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--text-main); margin-bottom: 0.25rem;">
            <i class="fa-solid fa-box-archive" style="color: var(--primary);"></i> Բաժանորդագրությունների Փաթեթներ (Subscription Plans)
        </h2>
        <p style="color: var(--text-muted); font-size: 0.88rem;">Կառավարեք 4 հիմնական (Basic, Pro, Business, Custom) և անհատական բաժանորդագրությունների փաթեթները, գները և հնարավորությունները։</p>
    </div>
    <button class="btn btn-primary" onclick="document.getElementById('createPlanModal').style.display='flex'">
        <i class="fa-solid fa-plus"></i> Ավելացնել Նոր Փաթեթ
    </button>
</div>

<!-- Plans Grid -->
<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
    @foreach($plans as $plan)
        <div class="glass-card" style="padding: 1.5rem; display: flex; flex-direction: column; justify-content: space-between; border-top: 4px solid {{ $plan->slug == 'basic' ? '#64748b' : ($plan->slug == 'pro' ? '#f59e0b' : ($plan->slug == 'business' ? '#06b6d4' : '#a855f7')) }};">
            <div>
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem;">
                    <div>
                        <h3 style="font-size: 1.25rem; font-weight: 800; color: var(--text-main);">{{ $plan->name }}</h3>
                        <span style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">{{ $plan->slug }}</span>
                    </div>
                    <form action="{{ route('superadmin.plans.toggle', $plan->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn" style="padding: 0.2rem 0.6rem; font-size: 0.75rem; background: {{ $plan->is_active ? 'rgba(34, 197, 94, 0.15)' : 'rgba(239, 68, 68, 0.15)' }}; color: {{ $plan->is_active ? '#22c55e' : '#ef4444' }}; border: 1px solid {{ $plan->is_active ? 'rgba(34, 197, 94, 0.3)' : 'rgba(239, 68, 68, 0.3)' }};">
                            {{ $plan->is_active ? 'Ակտիվ' : 'Անջատված' }}
                        </button>
                    </form>
                </div>

                <div style="margin-bottom: 1rem;">
                    <div style="font-size: 1.5rem; font-weight: 800; color: var(--primary);">
                        {{ $plan->formatted_price }}
                    </div>
                    <div style="font-size: 0.8rem; color: var(--text-muted);">
                        ⏱ Տևողություն՝ {{ $plan->duration_days }} օր | Փորձնական՝ {{ $plan->trial_days }} օր
                    </div>
                </div>

                <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1rem; line-height: 1.4;">
                    {{ $plan->description }}
                </p>

                <hr style="border-color: var(--border-color); margin: 1rem 0;">

                <h4 style="font-size: 0.85rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.5rem;">Ներառված ֆունկցիաներ (`{{ count($plan->features ?? []) }}`)</h4>
                <ul style="list-style: none; padding: 0; margin: 0 0 1.25rem 0; font-size: 0.82rem; color: var(--text-muted);">
                    @foreach($plan->features ?? [] as $feat)
                        <li style="margin-bottom: 0.35rem; display: flex; align-items: center; gap: 0.4rem;">
                            <i class="fa-solid fa-check" style="color: #22c55e; font-size: 0.75rem;"></i> {{ $feat }}
                        </li>
                    @endforeach
                </ul>
            </div>

            <div>
                <div style="font-size: 0.78rem; color: var(--text-muted); margin-bottom: 0.75rem;">
                    🏢 Օգտագործող վենդորներ՝ <strong>{{ $plan->vendors_count }}</strong>
                </div>
                <div style="display: flex; gap: 0.5rem;">
                    <button class="btn btn-secondary" style="flex: 1; font-size: 0.8rem;" onclick="openEditModal({{ json_encode($plan) }})">
                        <i class="fa-solid fa-pen-to-square"></i> Խմբագրել
                    </button>
                    @if($plan->vendors_count == 0)
                        <form action="{{ route('superadmin.plans.destroy', $plan->id) }}" method="POST" onsubmit="return confirm('Վստա՞հ եք, որ ցանկանում եք ջնջել այս փաթեթը։');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn" style="background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); padding: 0.5rem;">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>

<!-- Create Plan Modal -->
<div id="createPlanModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.75); backdrop-filter: blur(8px); z-index: 1000; align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: var(--bg-card); border: 1px solid var(--border-color); box-shadow: 0 25px 60px rgba(0,0,0,0.6); width: 100%; max-width: 600px; padding: 1.75rem; border-radius: 20px; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
            <h3 style="font-weight: 800; font-size: 1.2rem; color: var(--text-main);">Ավելացնել Նոր Փաթեթ</h3>
            <button onclick="document.getElementById('createPlanModal').style.display='none'" style="background:none; border:none; color:var(--text-main); cursor:pointer; font-size:1.2rem;">✕</button>
        </div>

        <form action="{{ route('superadmin.plans.store') }}" method="POST">
            @csrf
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label class="form-label">Փաթեթի Անվանում *</label>
                    <input type="text" name="name" class="form-input" required placeholder="օր․ Ultra Business">
                </div>
                <div>
                    <label class="form-label">Գին (AMD / ամիս) *</label>
                    <input type="number" name="price" step="100" min="0" class="form-input" required placeholder="0 = Պայմանագրային">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label class="form-label">Վճարման պարբերություն</label>
                    <select name="billing_interval" class="form-select">
                        <option value="monthly">Ամսական (Monthly)</option>
                        <option value="yearly">Տարեկան (Yearly)</option>
                        <option value="custom">Անհատական (Custom)</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Տևողություն (օրեր)</label>
                    <input type="number" name="duration_days" value="30" min="1" class="form-input" required>
                </div>
                <div>
                    <label class="form-label">Փորձնական (օրեր)</label>
                    <input type="number" name="trial_days" value="14" min="0" class="form-input" required>
                </div>
            </div>

            <div style="margin-bottom: 1rem;">
                <label class="form-label">Նկարագրություն</label>
                <input type="text" name="description" class="form-input" placeholder="Փաթեթի համառոտ նկարագրություն...">
            </div>

            <div style="margin-bottom: 1.25rem;">
                <label class="form-label">Ֆունկցիոնալության ցանկ (յուրաքանչյուրը նոր տողում)</label>
                <textarea name="features" rows="5" class="form-textarea" placeholder="QR մենյու&#10;Օնլայն պատվերներ&#10;Սեղանի համարով պատվիրում"></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('createPlanModal').style.display='none'">Չեղարկել</button>
                <button type="submit" class="btn btn-primary">Պահպանել Փաթեթը</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Plan Modal -->
<div id="editPlanModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.75); backdrop-filter: blur(8px); z-index: 1000; align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: var(--bg-card); border: 1px solid var(--border-color); box-shadow: 0 25px 60px rgba(0,0,0,0.6); width: 100%; max-width: 600px; padding: 1.75rem; border-radius: 20px; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
            <h3 style="font-weight: 800; font-size: 1.2rem; color: var(--text-main);">Խմբագրել Փաթեթը</h3>
            <button onclick="document.getElementById('editPlanModal').style.display='none'" style="background:none; border:none; color:var(--text-main); cursor:pointer; font-size:1.2rem;">✕</button>
        </div>

        <form id="editPlanForm" method="POST">
            @csrf
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label class="form-label">Փաթեթի Անվանում *</label>
                    <input type="text" id="edit_name" name="name" class="form-input" required>
                </div>
                <div>
                    <label class="form-label">Գին (AMD / ամիս) *</label>
                    <input type="number" id="edit_price" name="price" step="100" min="0" class="form-input" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label class="form-label">Վճարման պարբերություն</label>
                    <select id="edit_billing_interval" name="billing_interval" class="form-select">
                        <option value="monthly">Ամսական (Monthly)</option>
                        <option value="yearly">Տարեկան (Yearly)</option>
                        <option value="custom">Անհատական (Custom)</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Տևողություն (օրեր)</label>
                    <input type="number" id="edit_duration_days" name="duration_days" min="1" class="form-input" required>
                </div>
                <div>
                    <label class="form-label">Փորձնական (օրեր)</label>
                    <input type="number" id="edit_trial_days" name="trial_days" min="0" class="form-input" required>
                </div>
            </div>

            <div style="margin-bottom: 1rem;">
                <label class="form-label">Նկարագրություն</label>
                <input type="text" id="edit_description" name="description" class="form-input">
            </div>

            <div style="margin-bottom: 1.25rem;">
                <label class="form-label">Ֆունկցիոնալության ցանկ (յուրաքանչյուրը նոր տողում)</label>
                <textarea id="edit_features" name="features" rows="5" class="form-textarea"></textarea>
            </div>

            <div style="margin-bottom: 1.25rem;">
                <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; color: var(--text-main); cursor: pointer;">
                    <input type="checkbox" id="edit_is_active" name="is_active" value="1">
                    <span>Ակտիվ փաթեթ (հասանելի է գրանցումների համար)</span>
                </label>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('editPlanModal').style.display='none'">Չեղարկել</button>
                <button type="submit" class="btn btn-primary">Թարմացնել Փաթեթը</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditModal(plan) {
        document.getElementById('editPlanForm').action = '/superadmin/plans/' + plan.id;
        document.getElementById('edit_name').value = plan.name;
        document.getElementById('edit_price').value = plan.price;
        document.getElementById('edit_billing_interval').value = plan.billing_interval || 'monthly';
        document.getElementById('edit_duration_days').value = plan.duration_days || 30;
        document.getElementById('edit_trial_days').value = plan.trial_days || 14;
        document.getElementById('edit_description').value = plan.description || '';
        document.getElementById('edit_features').value = (plan.features || []).join('\n');
        document.getElementById('edit_is_active').checked = plan.is_active;

        document.getElementById('editPlanModal').style.display = 'flex';
    }
</script>
@endsection
