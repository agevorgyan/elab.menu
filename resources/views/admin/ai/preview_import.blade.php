@extends('layouts.app')

@section('title', __('Ստուգել Արտածված Մենյուն') . ' - ' . $vendor->name)

@section('content')
@php
    $totalDishes = 0;
    foreach ($parsedData['categories'] as $cat) {
        $totalDishes += count($cat['products'] ?? []);
    }
@endphp

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
    <div style="min-width: 0;">
        <h1 style="font-family: 'Outfit', sans-serif; font-size: clamp(1.4rem, 2.5vw, 1.85rem); font-weight: 800; color: var(--text-main); margin: 0; display: flex; align-items: center; gap: 0.75rem;">
            <span style="background: rgba(16, 185, 129, 0.15); color: #10b981; width: 44px; height: 44px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0;">
                <i class="fa-solid fa-list-check"></i>
            </span>
            <span>{{ __('Ստուգել & Խմբագրել Արտածված Մենյուն') }}</span>
        </h1>
        <p style="color: var(--text-muted); font-size: 0.9rem; margin: 0.35rem 0 0;">
            {{ __('Ստուգեք, փոփոխեք կամ ավելացրեք ուտեստները նախքան մենյուում հրապարակելը:') }}
        </p>
    </div>

    <div style="display: flex; gap: 0.6rem; align-items: center; flex-wrap: wrap;">
        <span class="badge badge-indigo" style="font-size: 0.8rem; padding: 0.35rem 0.75rem;">
            <i class="fa-solid fa-layer-group"></i> {{ count($parsedData['categories']) }} {{ __('Կատեգորիա') }}
        </span>
        <span class="badge badge-emerald" style="font-size: 0.8rem; padding: 0.35rem 0.75rem;">
            <i class="fa-solid fa-utensils"></i> {{ $totalDishes }} {{ __('Ուտեստ') }}
        </span>
    </div>
</div>

<form action="{{ route('admin.ai.import.confirm') }}" method="POST" x-data="{
    categories: @js(array_values($parsedData['categories'])),
    addCategory() {
        this.categories.push({
            name: 'Նոր Բաժին',
            products: [{ name: '', description: '', price: 0 }]
        });
    },
    removeCategory(cIdx) {
        if (this.categories.length > 1) {
            this.categories.splice(cIdx, 1);
        }
    },
    addProduct(cIdx) {
        if (!this.categories[cIdx].products) {
            this.categories[cIdx].products = [];
        }
        this.categories[cIdx].products.push({
            name: '',
            description: '',
            price: 0
        });
    },
    removeProduct(cIdx, pIdx) {
        this.categories[cIdx].products.splice(pIdx, 1);
    }
}">
    @csrf

    <template x-for="(cat, cIdx) in categories" :key="cIdx">
        <div class="card" style="margin-bottom: 1.5rem; position: relative;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin-bottom: 1.25rem; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 240px;">
                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); font-weight: 800; letter-spacing: 0.05em; text-transform: uppercase; margin-bottom: 0.35rem;">
                        <i class="fa-solid fa-folder-open" style="color: var(--primary); margin-right: 0.3rem;"></i>
                        {{ __('ԿԱՏԵԳՈՐԻԱՅԻ ԱՆՎԱՆՈՒՄ') }}
                    </label>
                    <input type="text" :name="'categories[' + cIdx + '][name]'" x-model="cat.name" required style="font-size: 1.15rem; font-weight: 800; width: 100%; box-sizing: border-box; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 12px; padding: 0.65rem 0.9rem; color: var(--text-main); outline: none;">
                </div>

                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <button type="button" @click="addProduct(cIdx)" class="btn btn-secondary" style="font-size: 0.78rem; padding: 0.45rem 0.85rem; border-radius: 10px;">
                        <i class="fa-solid fa-plus" style="color: #10b981;"></i> {{ __('Ավելացնել Ուտեստ') }}
                    </button>
                    <button type="button" @click="removeCategory(cIdx)" class="btn btn-secondary" style="font-size: 0.78rem; padding: 0.45rem 0.75rem; border-radius: 10px; color: #ef4444;" title="{{ __('Ջնջել Կատեգորիան') }}">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 0.85rem;">
                <template x-for="(prod, pIdx) in cat.products" :key="pIdx">
                    <div style="background: var(--input-bg); padding: 1rem; border-radius: 14px; border: 1px solid var(--border-color); display: flex; flex-direction: column; gap: 0.75rem; position: relative;">
                        <div style="display: grid; grid-template-columns: minmax(180px, 2fr) minmax(220px, 3fr) minmax(130px, 1.2fr) auto; gap: 0.85rem; align-items: flex-end;">
                            <div>
                                <label style="display: block; font-size: 0.72rem; color: var(--text-muted); font-weight: 700; margin-bottom: 0.3rem;">{{ __('Ուտեստի Անվանում') }}</label>
                                <input type="text" :name="'categories[' + cIdx + '][products][' + pIdx + '][name]'" x-model="prod.name" required placeholder="{{ __('Անվանում') }}" style="width: 100%; box-sizing: border-box; background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 10px; padding: 0.5rem 0.75rem; color: var(--text-main); font-weight: 600; outline: none; font-size: 0.88rem;">
                            </div>

                            <div>
                                <label style="display: block; font-size: 0.72rem; color: var(--text-muted); font-weight: 700; margin-bottom: 0.3rem;">{{ __('Նկարագրություն') }}</label>
                                <input type="text" :name="'categories[' + cIdx + '][products][' + pIdx + '][description]'" x-model="prod.description" placeholder="{{ __('Բաղադրություն, մանրամասներ') }}" style="width: 100%; box-sizing: border-box; background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 10px; padding: 0.5rem 0.75rem; color: var(--text-main); font-size: 0.85rem; outline: none;">
                            </div>

                            <div>
                                <label style="display: block; font-size: 0.72rem; color: var(--text-muted); font-weight: 700; margin-bottom: 0.3rem;">{{ __('Գին') }} ({{ $vendor->currency }})</label>
                                <input type="number" step="any" :name="'categories[' + cIdx + '][products][' + pIdx + '][price]'" x-model="prod.price" required placeholder="0" style="width: 100%; box-sizing: border-box; background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 10px; padding: 0.5rem 0.75rem; color: var(--primary); font-weight: 800; outline: none; font-size: 0.95rem;">
                            </div>

                            <div>
                                <button type="button" @click="removeProduct(cIdx, pIdx)" class="btn btn-secondary" style="padding: 0.5rem 0.65rem; border-radius: 10px; color: #ef4444;" title="{{ __('Ջնջել Ուտեստը') }}">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </template>

    <div style="margin-bottom: 2rem; display: flex; justify-content: flex-start;">
        <button type="button" @click="addCategory()" class="btn btn-secondary" style="border-radius: 12px; font-weight: 700; padding: 0.7rem 1.25rem;">
            <i class="fa-solid fa-folder-plus" style="color: var(--primary);"></i> {{ __('Ավելացնել Նոր Բաժին') }}
        </button>
    </div>

    <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 2rem; flex-wrap: wrap;">
        <a href="{{ route('admin.ai.import') }}" class="btn btn-secondary" style="padding: 0.75rem 1.5rem; border-radius: 12px;">
            {{ __('Չեղարկել') }}
        </a>
        <button type="submit" class="btn btn-primary" style="padding: 0.75rem 2rem; font-size: 1rem; border-radius: 12px; box-shadow: 0 4px 14px var(--primary-glow); gap: 0.5rem;">
            <i class="fa-solid fa-cloud-arrow-up"></i>
            <span>{{ __('Հաստատել & Հրապարակել Մենյուն') }}</span>
        </button>
    </div>
</form>
@endsection
