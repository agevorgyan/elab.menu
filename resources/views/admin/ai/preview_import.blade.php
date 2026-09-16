@extends('layouts.app')

@section('title', 'Review AI Extracted Menu - ' . $vendor->name)

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h1 style="font-family: 'Outfit', sans-serif; font-size: 1.75rem; font-weight: 700;">
            <i class="fa-solid fa-check-double text-amber-400"></i> Review Extracted Menu Draft
        </h1>
        <p style="color: var(--text-muted); font-size: 0.9rem;">Review and adjust extracted categories and dishes before publishing.</p>
    </div>
</div>

<form action="{{ route('admin.ai.import.confirm') }}" method="POST">
    @csrf
    @foreach($parsedData['categories'] as $cIdx => $cat)
        <div class="card" style="margin-bottom: 1.5rem;">
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.75rem; color: var(--text-muted); font-weight: 700; uppercase;">CATEGORY NAME</label>
                <input type="text" name="categories[{{ $cIdx }}][name]" value="{{ $cat['name'] }}" style="font-size: 1.25rem; font-weight: 700; width: 100%; background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 8px; padding: 0.5rem 0.75rem; color: #fff;">
            </div>

            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                @foreach($cat['products'] as $pIdx => $prod)
                    <div style="display: grid; grid-template-columns: 2fr 3fr 1fr; gap: 1rem; background: rgba(0,0,0,0.25); padding: 0.85rem; border-radius: 8px; border: 1px solid var(--border-color); align-items: center;">
                        <div>
                            <label style="display: block; font-size: 0.7rem; color: var(--text-muted);">Dish Name</label>
                            <input type="text" name="categories[{{ $cIdx }}][products][{{ $pIdx }}][name]" value="{{ $prod['name'] }}" style="width: 100%; background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 6px; padding: 0.4rem; color: #fff; font-weight: 600;">
                        </div>

                        <div>
                            <label style="display: block; font-size: 0.7rem; color: var(--text-muted);">Description</label>
                            <input type="text" name="categories[{{ $cIdx }}][products][{{ $pIdx }}][description]" value="{{ $prod['description'] ?? '' }}" style="width: 100%; background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 6px; padding: 0.4rem; color: #fff; font-size: 0.85rem;">
                        </div>

                        <div>
                            <label style="display: block; font-size: 0.7rem; color: var(--text-muted);">Price ({{ $vendor->currency }})</label>
                            <input type="number" name="categories[{{ $cIdx }}][products][{{ $pIdx }}][price]" value="{{ $prod['price'] }}" style="width: 100%; background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 6px; padding: 0.4rem; color: #f59e0b; font-weight: 700;">
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach

    <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 2rem;">
        <a href="{{ route('admin.ai.import') }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary" style="padding: 0.75rem 2rem; font-size: 1rem;">
            <i class="fa-solid fa-cloud-arrow-up"></i> Confirm & Publish Menu
        </button>
    </div>
</form>
@endsection
