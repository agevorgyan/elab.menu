@extends('layouts.app')

@section('title', 'Customers & CRM - ' . $vendor->name)

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-family: 'Outfit', sans-serif; font-size: 1.75rem; font-weight: 700;">
            <i class="fa-solid fa-users-gear" style="color: var(--primary);"></i> Customer Database & CRM
        </h1>
        <p style="color: var(--text-muted); font-size: 0.9rem;">Manage client directory, track order history, consent status, and export customer database.</p>
    </div>
    <div style="display: flex; gap: 0.75rem;">
        <a href="{{ route('admin.customers.export') }}" class="btn btn-secondary" style="font-size: 0.85rem;">
            <i class="fa-solid fa-file-excel" style="color: #10b981;"></i> Export to Excel / CSV
        </a>
        <button class="btn btn-primary" onclick="id('addCustomerModal').style.display='flex'" style="font-size: 0.85rem;">
            <i class="fa-solid fa-user-plus"></i> + Add Customer
        </button>
    </div>
</div>

<!-- Metrics Overview Cards -->
<div class="grid-4" style="margin-bottom: 1.5rem;">
    <div class="card" style="display: flex; align-items: center; gap: 1.25rem;">
        <div style="width: 50px; height: 50px; border-radius: 14px; background: rgba(79, 70, 229, 0.15); color: #4f46e5; display: flex; align-items: center; justify-content: center; font-size: 1.3rem;">
            <i class="fa-solid fa-users"></i>
        </div>
        <div>
            <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Total Customers</div>
            <div style="font-family: 'Outfit'; font-size: 1.5rem; font-weight: 800; color: var(--text-main);">{{ number_format($totalCustomers) }}</div>
        </div>
    </div>

    <div class="card" style="display: flex; align-items: center; gap: 1.25rem;">
        <div style="width: 50px; height: 50px; border-radius: 14px; background: rgba(16, 185, 129, 0.15); color: #10b981; display: flex; align-items: center; justify-content: center; font-size: 1.3rem;">
            <i class="fa-solid fa-bullhorn"></i>
        </div>
        <div>
            <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Marketing Consented</div>
            <div style="font-family: 'Outfit'; font-size: 1.5rem; font-weight: 800; color: #10b981;">{{ number_format($optedInCustomers) }}</div>
        </div>
    </div>

    <div class="card" style="display: flex; align-items: center; gap: 1.25rem;">
        <div style="width: 50px; height: 50px; border-radius: 14px; background: rgba(245, 158, 11, 0.15); color: #f59e0b; display: flex; align-items: center; justify-content: center; font-size: 1.3rem;">
            <i class="fa-solid fa-coins"></i>
        </div>
        <div>
            <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Total Customer LTV</div>
            <div style="font-family: 'Outfit'; font-size: 1.5rem; font-weight: 800; color: #f59e0b;">{{ number_format($totalRevenue) }} {{ $vendor->currency }}</div>
        </div>
    </div>
</div>

<!-- Search & Filters -->
<div class="card" style="padding: 1rem; margin-bottom: 1.5rem;">
    <form method="GET" action="{{ route('admin.customers.index') }}" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
        <div style="flex: 1; position: relative; min-width: 240px;">
            <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
            <input type="text" name="search" value="{{ $search }}" placeholder="Search by Name, Phone or Email..." style="width: 100%; padding: 0.6rem 1rem 0.6rem 2.5rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-main); outline: none;">
        </div>

        <select name="marketing" onchange="this.form.submit()" style="padding: 0.6rem 0.9rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-main); font-size: 0.85rem; outline: none;">
            <option value="">All Marketing Preferences</option>
            <option value="yes" {{ $consentFilter == 'yes' ? 'selected' : '' }}>Opted-In Only</option>
            <option value="no" {{ $consentFilter == 'no' ? 'selected' : '' }}>Opted-Out Only</option>
        </select>

        <button type="submit" class="btn btn-secondary" style="font-size: 0.85rem;">
            <i class="fa-solid fa-filter"></i> Filter
        </button>
        @if($search || $consentFilter)
            <a href="{{ route('admin.customers.index') }}" class="btn btn-secondary" style="font-size: 0.85rem;">Clear</a>
        @endif
    </form>
</div>

<!-- Customer Directory Table -->
<div class="card" style="padding: 0; overflow: hidden;">
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
            <thead>
                <tr style="background: var(--input-bg); border-bottom: 1px solid var(--border-color); color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase;">
                    <th style="padding: 1rem;">Customer Name</th>
                    <th style="padding: 1rem;">Phone</th>
                    <th style="padding: 1rem;">Email</th>
                    <th style="padding: 1rem;">Orders</th>
                    <th style="padding: 1rem;">Total Spent</th>
                    <th style="padding: 1rem;">Marketing Consent</th>
                    <th style="padding: 1rem;">Last Order</th>
                    <th style="padding: 1rem; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $c)
                    <tr style="border-bottom: 1px solid var(--border-color); color: var(--text-main);">
                        <td style="padding: 1rem; font-weight: 700;">
                            <a href="{{ route('admin.customers.show', $c->id) }}" style="color: var(--text-main); text-decoration: none;">
                                <i class="fa-solid fa-user-circle" style="color: var(--primary); margin-right: 0.35rem;"></i> {{ $c->name ?? 'Guest' }}
                            </a>
                        </td>
                        <td style="padding: 1rem; color: var(--text-muted);">
                            {{ $c->phone ?? '-' }}
                        </td>
                        <td style="padding: 1rem; color: var(--text-muted);">
                            {{ $c->email ?? '-' }}
                        </td>
                        <td style="padding: 1rem;">
                            <span style="background: var(--input-bg); border: 1px solid var(--border-color); padding: 0.2rem 0.6rem; border-radius: 6px; font-weight: 700;">
                                {{ $c->total_orders_count }} orders
                            </span>
                        </td>
                        <td style="padding: 1rem; font-weight: 800; color: #10b981; font-family: 'Outfit';">
                            {{ number_format($c->total_spent) }} {{ $vendor->currency }}
                        </td>
                        <td style="padding: 1rem;">
                            @if($c->marketing_opt_in)
                                <span style="background: rgba(16, 185, 129, 0.15); color: #10b981; padding: 0.2rem 0.5rem; border-radius: 6px; font-size: 0.75rem; font-weight: 700;">
                                    <i class="fa-solid fa-check"></i> Opted-In
                                </span>
                            @else
                                <span style="background: rgba(239, 68, 68, 0.15); color: #ef4444; padding: 0.2rem 0.5rem; border-radius: 6px; font-size: 0.75rem; font-weight: 700;">
                                    <i class="fa-solid fa-xmark"></i> Opted-Out
                                </span>
                            @endif
                        </td>
                        <td style="padding: 1rem; font-size: 0.8rem; color: var(--text-muted);">
                            {{ $c->last_order_at ? $c->last_order_at->diffForHumans() : '-' }}
                        </td>
                        <td style="padding: 1rem; text-align: right;">
                            <div style="display: flex; gap: 0.4rem; justify-content: flex-end;">
                                <a href="{{ route('admin.customers.show', $c->id) }}" class="btn btn-secondary" style="padding: 0.35rem 0.65rem; font-size: 0.75rem;" title="View Timeline">
                                    <i class="fa-solid fa-timeline"></i> Timeline
                                </a>
                                <button class="btn btn-secondary" style="padding: 0.35rem 0.65rem; font-size: 0.75rem;" onclick="editCustomer({{ json_encode($c) }})">
                                    <i class="fa-solid fa-pen"></i> Edit
                                </button>
                                <form action="{{ route('admin.customers.destroy', $c->id) }}" method="POST" onsubmit="return confirm('Delete customer record?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger" style="padding: 0.35rem 0.65rem; font-size: 0.75rem;">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 3rem; color: var(--text-muted);">
                            <i class="fa-solid fa-users" style="font-size: 2.5rem; margin-bottom: 1rem; display: block; opacity: 0.5;"></i>
                            No customer records found. Customers will automatically accumulate as orders are placed.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div style="margin-top: 1.5rem;">
    {{ $customers->appends(request()->query())->links() }}
</div>

<!-- Add Customer Modal -->
<div id="addCustomerModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); z-index: 100; align-items: center; justify-content: center;">
    <div class="card" style="width: 100%; max-width: 480px; padding: 1.5rem; position: relative;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
            <h3 style="font-family: 'Outfit'; font-size: 1.2rem; color: var(--text-main);"><i class="fa-solid fa-user-plus" style="color: var(--primary);"></i> Add New Customer</h3>
            <button onclick="id('addCustomerModal').style.display='none'" style="background: none; border: none; color: var(--text-main); font-size: 1.25rem; cursor: pointer;">✕</button>
        </div>

        <form action="{{ route('admin.customers.store') }}" method="POST">
            @csrf
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Full Name</label>
                <input type="text" name="name" required placeholder="Armen Sargsyan" style="width: 100%; padding: 0.65rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 8px; color: var(--text-main); outline: none;">
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Phone Number</label>
                <input type="tel" name="phone" placeholder="094112233" style="width: 100%; padding: 0.65rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 8px; color: var(--text-main); outline: none;">
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Email Address</label>
                <input type="email" name="email" placeholder="armen@example.com" style="width: 100%; padding: 0.65rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 8px; color: var(--text-main); outline: none;">
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Customer Notes</label>
                <textarea name="notes" rows="2" placeholder="VIP Client, prefers window seat..." style="width: 100%; padding: 0.65rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 8px; color: var(--text-main); outline: none;"></textarea>
            </div>

            <div style="margin-bottom: 1.25rem;">
                <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; color: var(--text-main); cursor: pointer;">
                    <input type="checkbox" name="marketing_opt_in" value="1" checked>
                    <span>Consented to marketing communications</span>
                </label>
            </div>

            <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="id('addCustomerModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Customer</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Customer Modal -->
<div id="editCustomerModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); z-index: 100; align-items: center; justify-content: center;">
    <div class="card" style="width: 100%; max-width: 480px; padding: 1.5rem; position: relative;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
            <h3 style="font-family: 'Outfit'; font-size: 1.2rem; color: var(--text-main);"><i class="fa-solid fa-pen-to-square" style="color: var(--primary);"></i> Edit Customer</h3>
            <button onclick="id('editCustomerModal').style.display='none'" style="background: none; border: none; color: var(--text-main); font-size: 1.25rem; cursor: pointer;">✕</button>
        </div>

        <form id="editCustomerForm" method="POST">
            @csrf
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Full Name</label>
                <input type="text" id="edit_name" name="name" required style="width: 100%; padding: 0.65rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 8px; color: var(--text-main); outline: none;">
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Phone Number</label>
                <input type="tel" id="edit_phone" name="phone" style="width: 100%; padding: 0.65rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 8px; color: var(--text-main); outline: none;">
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Email Address</label>
                <input type="email" id="edit_email" name="email" style="width: 100%; padding: 0.65rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 8px; color: var(--text-main); outline: none;">
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Customer Notes</label>
                <textarea id="edit_notes" name="notes" rows="2" style="width: 100%; padding: 0.65rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 8px; color: var(--text-main); outline: none;"></textarea>
            </div>

            <div style="margin-bottom: 1.25rem;">
                <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; color: var(--text-main); cursor: pointer;">
                    <input type="checkbox" id="edit_marketing_opt_in" name="marketing_opt_in" value="1">
                    <span>Consented to marketing communications</span>
                </label>
            </div>

            <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="id('editCustomerModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Details</button>
            </div>
        </form>
    </div>
</div>

<script>
    function id(name) { return document.getElementById(name); }

    function editCustomer(c) {
        id('editCustomerForm').action = "/admin/customers/" + c.id;
        id('edit_name').value = c.name || '';
        id('edit_phone').value = c.phone || '';
        id('edit_email').value = c.email || '';
        id('edit_notes').value = c.notes || '';
        id('edit_marketing_opt_in').checked = !!c.marketing_opt_in;
        id('editCustomerModal').style.display = 'flex';
    }
</script>
@endsection
