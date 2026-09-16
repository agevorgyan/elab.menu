@extends('layouts.app')

@section('title', 'Multi-Locations Management - ' . $vendor->name)

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h1 style="font-family: 'Outfit', sans-serif; font-size: 1.75rem; font-weight: 700;">
            <i class="fa-solid fa-location-dot text-amber-400"></i> Multi-Locations Management
        </h1>
        <p style="color: var(--text-muted); font-size: 0.9rem;">Run every location from a single account. Master menu with location overrides.</p>
    </div>
    <button class="btn btn-primary" onclick="document.getElementById('newLocationModal').style.display='flex'">
        <i class="fa-solid fa-plus"></i> Add New Location
    </button>
</div>

<div class="grid-2">
    @foreach($locations as $loc)
        <div class="card" style="border-left: 4px solid var(--primary);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                <div>
                    <h3 style="font-family: 'Outfit'; font-size: 1.25rem; font-weight: 700;">{{ $loc->name }}</h3>
                    <div style="font-size: 0.85rem; color: var(--text-muted);"><i class="fa-solid fa-map-pin text-amber-500"></i> {{ $loc->address ?? 'Address not specified' }}</div>
                </div>
                <span style="background: rgba(16, 185, 129, 0.15); color: #34d399; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 700;">
                    ACTIVE
                </span>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 1rem; font-size: 0.85rem; background: rgba(0,0,0,0.2); padding: 0.75rem; border-radius: 8px;">
                <div>
                    <span style="color: var(--text-muted); display: block; font-size: 0.75rem;">PHONE</span>
                    <strong>{{ $loc->phone ?? 'N/A' }}</strong>
                </div>
                <div>
                    <span style="color: var(--text-muted); display: block; font-size: 0.75rem;">WHATSAPP</span>
                    <strong>{{ $loc->whatsapp_number ?? 'N/A' }}</strong>
                </div>
                <div>
                    <span style="color: var(--text-muted); display: block; font-size: 0.75rem;">TABLE COUNT</span>
                    <strong>{{ $loc->table_count }} Tables</strong>
                </div>
                <div>
                    <span style="color: var(--text-muted); display: block; font-size: 0.75rem;">MIN ORDER</span>
                    <strong>{{ number_format($loc->minimum_order_amount) }} {{ $vendor->currency }}</strong>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                <a href="{{ route('client.menu', ['vendor_slug' => $vendor->slug, 'location_slug' => $loc->slug]) }}" target="_blank" class="btn btn-secondary" style="font-size: 0.8rem;">
                    <i class="fa-solid fa-eye"></i> View Location Storefront
                </a>
            </div>
        </div>
    @endforeach
</div>

<!-- Modal Create Location -->
<div id="newLocationModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 100; align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; width: 100%; max-width: 500px; padding: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="font-family: 'Outfit'; font-size: 1.25rem;">Add New Business Location</h3>
            <button onclick="document.getElementById('newLocationModal').style.display='none'" style="background: none; border: none; color: #fff; font-size: 1.25rem; cursor: pointer;"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form action="{{ route('admin.locations.store') }}" method="POST">
            @csrf
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Location Name</label>
                <input type="text" name="name" required placeholder="e.g. Dilijan Resort Branch" style="width: 100%; padding: 0.6rem; background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 6px; color: #fff;">
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Address</label>
                <input type="text" name="address" placeholder="Myasnikyan St 15" style="width: 100%; padding: 0.6rem; background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 6px; color: #fff;">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Phone Number</label>
                    <input type="text" name="phone" placeholder="+37410..." style="width: 100%; padding: 0.6rem; background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 6px; color: #fff;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">WhatsApp Order #</label>
                    <input type="text" name="whatsapp_number" placeholder="37491..." style="width: 100%; padding: 0.6rem; background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 6px; color: #fff;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                <div>
                    <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Table Count</label>
                    <input type="number" name="table_count" value="20" required style="width: 100%; padding: 0.6rem; background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 6px; color: #fff;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Min Order Amount</label>
                    <input type="number" name="minimum_order_amount" value="0" style="width: 100%; padding: 0.6rem; background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 6px; color: #fff;">
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('newLocationModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Location</button>
            </div>
        </form>
    </div>
</div>
@endsection
