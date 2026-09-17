@extends('layouts.app')

@section('title', 'Table QR Code Studio - ' . $vendor->name)

@section('content')
<style>
    @media print {
        body * { visibility: hidden; }
        #printableFlyer, #printableFlyer * { visibility: visible; }
        #printableFlyer { position: absolute; left: 0; top: 0; width: 100%; border: none !important; box-shadow: none !important; }
    }
</style>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;" class="no-print">
    <div>
        <h1 style="font-family: 'Outfit', sans-serif; font-size: 1.75rem; font-weight: 700;">
            <i class="fa-solid fa-qrcode" style="color: var(--primary);"></i> Table QR Code Studio
        </h1>
        <p style="color: var(--text-muted); font-size: 0.9rem;">Generate branded Dine-In table stand flyers for {{ $location?->name ?? 'All Locations' }}.</p>
    </div>
    <button onclick="window.print()" class="btn btn-primary">
        <i class="fa-solid fa-print"></i> Print Table Stand Flyer
    </button>
</div>

<div class="grid-2 no-print" x-data="{ tableNum: '4', qrType: 'dine_in' }">
    <!-- QR Customizer Controls -->
    <div class="card">
        <h3 style="font-family: 'Outfit'; font-size: 1.1rem; margin-bottom: 1rem; color: var(--text-main);">QR Stand Configuration</h3>

        <div style="margin-bottom: 1.25rem;">
            <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Table Number</label>
            <input type="text" x-model="tableNum" style="width: 100%; padding: 0.65rem 0.9rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-main); font-size: 1.1rem; font-weight: 700; outline: none;">
        </div>

        <div style="margin-bottom: 1.25rem;">
            <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">QR Code Purpose</label>
            <select x-model="qrType" style="width: 100%; padding: 0.65rem 0.9rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-main); font-size: 0.9rem; outline: none;">
                <option value="dine_in">Dine-In Table Menu (Scan to browse & order)</option>
                <option value="ordering">Dedicated WhatsApp / Takeaway Order QR</option>
            </select>
        </div>

        <div style="background: var(--badge-bg); border: 1px solid var(--border-color); padding: 1rem; border-radius: 12px; font-size: 0.85rem; color: var(--badge-text);">
            <i class="fa-solid fa-lightbulb"></i> <strong>Tip:</strong> Print a table stand flyer for each table (Table 1 to Table {{ $location?->table_count ?? 20 }}). When guests scan at the table, the table number automatically locks onto their order!
        </div>
    </div>

    <!-- Printable Table Stand Flyer Preview -->
    <div class="card" id="printableFlyer" style="background: var(--bg-card); border: 2px solid var(--primary); border-radius: 24px; padding: 2.5rem; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; position: relative;">
        <!-- Logo -->
        <img src="{{ $vendor->logo ?? 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=150&q=80' }}" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid var(--primary); margin-bottom: 1rem;">

        <h2 style="font-family: 'Outfit'; font-size: 1.75rem; font-weight: 800; color: var(--text-main); margin-bottom: 0.25rem;">{{ $vendor->name }}</h2>
        <div style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 1.5rem;">{{ $location?->name ?? 'Main Branch' }}</div>

        <!-- Table Badge -->
        <div style="background: var(--primary); color: #ffffff; font-weight: 900; font-size: 1.25rem; padding: 0.35rem 1.5rem; border-radius: 9999px; font-family: 'Outfit'; margin-bottom: 1.5rem;" x-text="'TABLE ' + tableNum">
            TABLE 4
        </div>

        <!-- QR Code Image Canvas -->
        <div style="background: #ffffff; padding: 1rem; border-radius: 16px; margin-bottom: 1.5rem; box-shadow: 0 10px 25px rgba(0,0,0,0.15);">
            <img :src="'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' + encodeURIComponent('{{ route("client.menu", ["vendor_slug" => $vendor->slug, "location_slug" => $location?->slug]) }}?table=' + tableNum + '&mode=' + qrType)" style="width: 180px; height: 180px; display: block;" alt="Table QR">
        </div>

        <div style="font-family: 'Outfit'; font-weight: 700; font-size: 1.1rem; color: var(--text-main); margin-bottom: 0.25rem;">
            📱 SCAN TO VIEW DIGITAL MENU
        </div>
        <div style="font-size: 0.8rem; color: var(--text-muted);">
            Point phone camera to view menu & place order
        </div>

        <div style="margin-top: 2rem; font-size: 0.7rem; color: var(--text-muted);">
            Powered by QRMenu SaaS Platform
        </div>
    </div>
</div>
@endsection
