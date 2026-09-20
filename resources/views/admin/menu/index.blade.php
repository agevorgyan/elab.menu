@extends('layouts.app')

@section('title', 'Menu Builder - ' . $vendor->name)

@section('styles')
<style>
    .menu-dish-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1.15rem 1.25rem;
        background: var(--input-bg);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        gap: 1.25rem;
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        min-width: 0;
        box-sizing: border-box;
    }
    .menu-dish-row:hover {
        border-color: rgba(245, 158, 11, 0.35);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        transform: translateY(-1px);
    }
    .dish-thumb {
        width: 76px;
        height: 76px;
        object-fit: cover;
        border-radius: 14px;
        flex-shrink: 0;
        border: 1px solid var(--border-color);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .dish-content {
        flex: 1;
        min-width: 0;
    }
    .dish-pricing-col {
        text-align: right;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 0.45rem;
        flex-shrink: 0;
    }
    .modal-box-responsive {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 20px;
        width: 100%;
        max-width: min(660px, 95vw);
        max-height: 90vh;
        overflow-y: auto;
        padding: 1.75rem 2rem;
        margin: auto;
        box-sizing: border-box;
        box-shadow: 0 25px 60px rgba(0,0,0,0.45);
    }
    @media (max-width: 768px) {
        .menu-dish-row {
            flex-direction: column;
            align-items: stretch;
            gap: 1rem;
            padding: 1rem;
        }
        .dish-main-mobile {
            display: flex;
            align-items: flex-start;
            gap: 0.85rem;
            width: 100%;
            min-width: 0;
        }
        .dish-pricing-col {
            align-items: stretch;
            text-align: left;
            border-top: 1px solid var(--border-color);
            padding-top: 0.85rem;
            width: 100%;
            gap: 0.75rem;
        }
        .dish-pricing-row-mobile {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .dish-actions-mobile {
            display: flex;
            gap: 0.5rem;
            width: 100%;
            justify-content: flex-end;
            flex-wrap: wrap;
        }
        .modal-box-responsive {
            padding: 1.25rem;
        }
    }
</style>
@endsection

@section('content')
<!-- Page Header -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <div style="display: flex; align-items: center; gap: 0.65rem;">
            <span style="width: 40px; height: 40px; border-radius: 12px; background: rgba(16, 185, 129, 0.15); color: #10b981; display: inline-flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                <i class="fa-solid fa-utensils"></i>
            </span>
            <h1 style="font-size: 1.75rem; font-weight: 800; margin: 0; color: var(--text-main);">
                Menu Builder
            </h1>
        </div>
        <p style="color: var(--text-muted); font-size: 0.88rem; margin-top: 0.35rem;">
            Manage categories, dish offerings, pricing portions, allergens, and happy hour discount schedules.
        </p>
    </div>
    <div style="display: flex; gap: 0.65rem; flex-wrap: wrap;">
        <button class="btn btn-secondary" onclick="document.getElementById('newCategoryModal').style.display='flex'">
            <i class="fa-solid fa-folder-plus" style="color: var(--primary);"></i> New Category
        </button>
        <button class="btn btn-primary" onclick="openNewProductModal()">
            <i class="fa-solid fa-plus"></i> Add New Dish
        </button>
    </div>
</div>

@if($categories->count() == 0)
    <div class="card" style="text-align: center; padding: 4rem 2rem;">
        <div style="width: 64px; height: 64px; border-radius: 20px; background: rgba(245, 158, 11, 0.15); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 1.25rem;">
            <i class="fa-solid fa-utensils"></i>
        </div>
        <h2 style="font-size: 1.5rem; font-weight: 800; margin-bottom: 0.5rem; color: var(--text-main);">Your menu is empty</h2>
        <p style="color: var(--text-muted); margin-bottom: 1.75rem; max-width: 440px; margin-left: auto; margin-right: auto; line-height: 1.5;">
            Start creating categories and dishes, or use our AI Menu Import tool to digitize existing paper/PDF menus in seconds.
        </p>
        <div style="display: flex; justify-content: center; gap: 0.75rem; flex-wrap: wrap;">
            <a href="{{ route('admin.ai.import') }}" class="btn btn-primary">
                <i class="fa-solid fa-wand-magic-sparkles"></i> AI Menu Import
            </a>
            <button class="btn btn-secondary" onclick="document.getElementById('newCategoryModal').style.display='flex'">
                <i class="fa-solid fa-plus"></i> Create Category
            </button>
        </div>
    </div>
@else
    @foreach($categories as $category)
        <div class="card" style="margin-bottom: 2rem;">
            <!-- Category Header -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.85rem; flex-wrap: wrap; gap: 0.75rem;">
                <div style="min-width: 0; flex: 1;">
                    <div style="display: flex; align-items: center; gap: 0.65rem; flex-wrap: wrap;">
                        <h2 style="font-size: 1.25rem; font-weight: 800; color: var(--text-main); margin: 0;">
                            {{ $category->name }}
                        </h2>
                        @if($category->getTranslatedName('hy') || $category->getTranslatedName('ru'))
                            <span style="font-size: 0.78rem; font-weight: 500; color: var(--text-muted); background: var(--input-bg); border: 1px solid var(--border-color); padding: 0.15rem 0.5rem; border-radius: 6px;">
                                @if($category->getTranslatedName('hy')) HY: {{ $category->getTranslatedName('hy') }} @endif
                                @if($category->getTranslatedName('hy') && $category->getTranslatedName('ru')) | @endif
                                @if($category->getTranslatedName('ru')) RU: {{ $category->getTranslatedName('ru') }} @endif
                            </span>
                        @endif
                        <span class="badge badge-indigo" style="font-size: 0.72rem; padding: 0.2rem 0.55rem;">
                            {{ $category->products->count() }} dishes
                        </span>
                    </div>
                    @if($category->description)
                        <p style="font-size: 0.82rem; color: var(--text-muted); margin-top: 0.35rem;" class="break-word">{{ $category->description }}</p>
                    @endif
                </div>

                <div style="display: flex; gap: 0.45rem; align-items: center; flex-shrink: 0;">
                    <button type="button" class="btn btn-secondary" style="padding: 0.35rem 0.75rem; font-size: 0.78rem;" onclick="editCategory({{ json_encode($category) }})">
                        <i class="fa-solid fa-pen"></i> Edit Category
                    </button>
                    <form action="{{ route('admin.menu.categories.destroy', $category->id) }}" method="POST" onsubmit="return confirm('Delete category {{ $category->name }} and all its dishes?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger" style="padding: 0.35rem 0.65rem; font-size: 0.78rem;" title="Delete Category">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Dishes List -->
            <div style="display: flex; flex-direction: column; gap: 0.85rem;">
                @forelse($category->products as $product)
                    <div class="menu-dish-row">
                        <!-- Left/Desktop Content: Thumbnail + Info -->
                        <div class="dish-main-mobile" style="flex: 1; min-width: 0; display: flex; align-items: flex-start; gap: 1rem;">
                            <img src="{{ $product->image }}" onerror="this.onerror=null;this.src='{{ asset('images/default-dish.png') }}';" class="dish-thumb" alt="{{ $product->name }}">
                            
                            <div class="dish-content">
                                <div style="display: flex; align-items: center; gap: 0.45rem; flex-wrap: wrap;">
                                    <strong style="font-size: 1.05rem; color: var(--text-main); font-family: 'Outfit'; word-break: break-word;">{{ $product->name }}</strong>
                                    
                                    @if($vendor->featured_dish_enabled && $vendor->featured_product_id == $product->id)
                                        <span class="badge" style="background: linear-gradient(135deg, #f59e0b, #ef4444); color: #fff; font-size: 0.7rem; font-weight: 800;">
                                            <i class="fa-solid fa-fire-flame-curved"></i> {{ $vendor->featured_dish_badge ?: 'ՕՐՎԱ ՈՒՏԵՍՏ' }}
                                        </span>
                                    @endif
                                    
                                    @if($product->is_featured)
                                        <span class="badge badge-amber" style="font-size: 0.7rem; font-weight: 700;">★ FEATURED</span>
                                    @endif

                                    @if(!empty($product->dietary_tags))
                                        @foreach($product->dietary_tags as $tag)
                                            <span class="badge badge-emerald" style="font-size: 0.68rem; text-transform: uppercase;">{{ str_replace('_', ' ', $tag) }}</span>
                                        @endforeach
                                    @endif
                                </div>

                                @if($product->description)
                                    <p style="font-size: 0.82rem; color: var(--text-muted); margin-top: 0.35rem; line-height: 1.4; word-break: break-word;">{{ $product->description }}</p>
                                @endif

                                <!-- Variations & Portions Chips -->
                                @if($product->variations && $product->variations->count() > 0)
                                    @php
                                        $hasCustomVariations = $product->variations->count() > 1 || 
                                            ($product->variations->count() === 1 && !in_array($product->variations->first()->name, ['Standard', 'Standard Portion']));
                                    @endphp
                                    @if($hasCustomVariations)
                                        <div style="margin-top: 0.65rem; padding: 0.5rem 0.75rem; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; width: 100%; box-sizing: border-box;">
                                            <div style="font-size: 0.7rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.35rem; display: flex; align-items: center; gap: 0.35rem;">
                                                <i class="fa-solid fa-sliders" style="color: var(--primary);"></i> Portions & Variations ({{ $product->variations->count() }}):
                                            </div>
                                            <div style="display: flex; flex-wrap: wrap; gap: 0.35rem;">
                                                @foreach($product->variations as $var)
                                                    <div style="display: inline-flex; align-items: center; gap: 0.35rem; background: var(--input-bg); border: 1px solid {{ $var->is_default ? 'var(--primary)' : 'var(--border-color)' }}; padding: 0.2rem 0.55rem; border-radius: 8px; font-size: 0.75rem; white-space: nowrap;">
                                                        @if($var->is_default)
                                                            <i class="fa-solid fa-circle-check" style="color: var(--primary); font-size: 0.75rem;" title="Default Portion"></i>
                                                        @else
                                                            <i class="fa-regular fa-circle" style="color: var(--text-muted); font-size: 0.7rem;"></i>
                                                        @endif
                                                        <span style="font-weight: 700; color: var(--text-main);">{{ $var->name }}</span>
                                                        @php
                                                            $extraTrans = array_filter([
                                                                $var->name_translations['hy'] ?? null,
                                                                $var->name_translations['ru'] ?? null
                                                            ], fn($t) => !empty($t) && $t !== $var->name);
                                                        @endphp
                                                        @if(!empty($extraTrans))
                                                            <span style="color: var(--text-muted); font-size: 0.7rem;">({{ implode(' • ', $extraTrans) }})</span>
                                                        @endif
                                                        <span style="font-family: 'Outfit'; font-weight: 800; color: var(--primary);">
                                                            {{ number_format($var->price) }} {{ $vendor->currency }}
                                                        </span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                @endif

                                <div style="display: flex; gap: 0.85rem; align-items: center; margin-top: 0.5rem; font-size: 0.75rem; color: var(--text-muted); flex-wrap: wrap;">
                                    @if($product->calories)
                                        <span><i class="fa-solid fa-fire" style="color: var(--primary);"></i> {{ $product->calories }} kcal</span>
                                    @endif
                                    @if($product->preparation_time_min)
                                        <span><i class="fa-solid fa-clock"></i> {{ $product->preparation_time_min }} mins</span>
                                    @endif
                                    @if($product->allergens->count() > 0)
                                        <span><i class="fa-solid fa-triangle-exclamation" style="color: #ef4444;"></i> Allergens: {{ $product->allergens->pluck('icon')->join(' ') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Right / Bottom Pricing & Actions -->
                        <div class="dish-pricing-col">
                            <div class="dish-pricing-row-mobile">
                                <div style="font-size: 1.25rem; font-weight: 800; color: var(--primary); font-family: 'Outfit'; line-height: 1.2;">
                                    @if($product->isDiscountActive())
                                        <div style="display: flex; align-items: baseline; gap: 0.45rem;">
                                            <span style="color: #ef4444;">{{ number_format($product->discount_price) }} {{ $vendor->currency }}</span>
                                            <del style="color: var(--text-muted); font-size: 0.85rem; font-weight: 500;">{{ number_format($product->price) }}</del>
                                        </div>
                                    @elseif($product->discount_price)
                                        <div>
                                            <span>{{ number_format($product->price) }} {{ $vendor->currency }}</span>
                                            <div style="font-size: 0.72rem; color: var(--text-muted); font-weight: 600;">
                                                (Զեղչ՝ {{ number_format($product->discount_price) }} {{ $vendor->currency }})
                                            </div>
                                        </div>
                                    @elseif($product->variations->count() > 1)
                                        {{ number_format($product->variations->min('price')) }} - {{ number_format($product->variations->max('price')) }} {{ $vendor->currency }}
                                    @else
                                        {{ number_format($product->price) }} {{ $vendor->currency }}
                                    @endif
                                </div>

                                @if($product->isDiscountActive())
                                    <div style="display: flex; align-items: center; gap: 0.35rem; flex-wrap: wrap; justify-content: flex-end;">
                                        <span class="badge badge-rose" style="font-size: 0.7rem;">
                                            <i class="fa-solid fa-tag"></i> -{{ $product->getDiscountPercentage() }}% Զեղչ
                                        </span>
                                        <span class="badge badge-emerald" style="font-size: 0.7rem;">
                                            <i class="fa-regular fa-clock"></i> {{ $product->getDiscountScheduleSummary() }}
                                        </span>
                                    </div>
                                @elseif($product->discount_price)
                                    <div style="font-size: 0.7rem; color: var(--text-muted);">
                                        <i class="fa-regular fa-clock"></i> {{ $product->getDiscountScheduleSummary() }} <span style="opacity: 0.7;">(ժամից դուրս)</span>
                                    </div>
                                @endif
                            </div>

                            <div class="dish-actions-mobile">
                                <button type="button" class="btn btn-secondary" style="padding: 0.35rem 0.7rem; font-size: 0.78rem;" onclick="editProduct({{ json_encode($product->load(['allergens', 'variations'])) }})">
                                    <i class="fa-solid fa-pen"></i> Edit
                                </button>

                                <form action="{{ route('admin.menu.products.toggle', $product->id) }}" method="POST" style="margin: 0;">
                                    @csrf
                                    <button type="submit" class="btn btn-secondary" style="padding: 0.35rem 0.7rem; font-size: 0.78rem;">
                                        @if($product->is_available)
                                            <span style="color: #10b981;"><i class="fa-solid fa-toggle-on"></i> In Stock</span>
                                        @else
                                            <span style="color: #ef4444;"><i class="fa-solid fa-toggle-off"></i> Out of Stock</span>
                                        @endif
                                    </button>
                                </form>

                                <form action="{{ route('admin.menu.products.destroy', $product->id) }}" method="POST" style="margin: 0;" onsubmit="return confirm('Delete dish {{ $product->name }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger" style="padding: 0.35rem 0.65rem; font-size: 0.78rem;" title="Delete dish">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div style="text-align: center; padding: 2rem; color: var(--text-muted); font-size: 0.9rem;">
                        No dishes added to this category yet.
                    </div>
                @endforelse
            </div>
        </div>
    @endforeach
@endif

<!-- Modal Add Category -->
<div id="newCategoryModal" class="modern-modal-overlay" style="display: none;">
    <div class="modal-box-responsive" style="max-width: 500px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="font-size: 1.25rem; font-weight: 800; color: var(--text-main); margin: 0;">Create New Category</h3>
            <button onclick="document.getElementById('newCategoryModal').style.display='none'" style="background: none; border: none; color: var(--text-muted); font-size: 1.25rem; cursor: pointer;"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form action="{{ route('admin.menu.categories.store') }}" method="POST">
            @csrf
            <div style="margin-bottom: 1rem;">
                <label class="form-label">Category Name (English) *</label>
                <input type="text" name="name" required placeholder="e.g. Signature Cocktails" class="form-input">
            </div>
            <div style="margin-bottom: 1rem;">
                <label class="form-label">Armenian Name (Հայերեն)</label>
                <input type="text" name="hy_name" placeholder="օր․ Կոկտեյլներ" class="form-input">
            </div>
            <div style="margin-bottom: 1rem;">
                <label class="form-label">Russian Name (Русский)</label>
                <input type="text" name="ru_name" placeholder="напр. Коктейли" class="form-input">
            </div>
            <div style="margin-bottom: 1.5rem;">
                <label class="form-label">Description (Optional)</label>
                <textarea name="description" rows="2" placeholder="Brief category introduction..." class="form-textarea"></textarea>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('newCategoryModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Category</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Category -->
<div id="editCategoryModal" class="modern-modal-overlay" style="display: none;">
    <div class="modal-box-responsive" style="max-width: 500px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="font-size: 1.25rem; font-weight: 800; color: var(--text-main); margin: 0;">Edit Category</h3>
            <button onclick="document.getElementById('editCategoryModal').style.display='none'" style="background: none; border: none; color: var(--text-muted); font-size: 1.25rem; cursor: pointer;"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form id="editCategoryForm" method="POST">
            @csrf
            <div style="margin-bottom: 1rem;">
                <label class="form-label">Category Name (English) *</label>
                <input type="text" id="edit_cat_name" name="name" required class="form-input">
            </div>
            <div style="margin-bottom: 1rem;">
                <label class="form-label">Armenian Name (Հայերեն)</label>
                <input type="text" id="edit_cat_hy_name" name="hy_name" class="form-input">
            </div>
            <div style="margin-bottom: 1rem;">
                <label class="form-label">Russian Name (Русский)</label>
                <input type="text" id="edit_cat_ru_name" name="ru_name" class="form-input">
            </div>
            <div style="margin-bottom: 1.5rem;">
                <label class="form-label">Description</label>
                <textarea id="edit_cat_description" name="description" rows="2" class="form-textarea"></textarea>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('editCategoryModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Category</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Add Product -->
<div id="newProductModal" class="modern-modal-overlay" style="display: none;">
    <div class="modal-box-responsive">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="font-size: 1.25rem; font-weight: 800; color: var(--text-main); margin: 0;">Add New Menu Dish</h3>
            <button onclick="document.getElementById('newProductModal').style.display='none'" style="background: none; border: none; color: var(--text-muted); font-size: 1.25rem; cursor: pointer;"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form action="{{ route('admin.menu.products.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div style="margin-bottom: 1rem;">
                <label class="form-label">Category *</label>
                <select name="category_id" required class="form-select">
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid-3" style="margin-bottom: 1rem;">
                <div style="grid-column: span 1;">
                    <label class="form-label">Dish Name (English) *</label>
                    <input type="text" name="name" required class="form-input">
                </div>
                <div>
                    <label class="form-label">Price ({{ $vendor->currency }}) *</label>
                    <input type="number" name="price" step="100" required class="form-input">
                </div>
                <div>
                    <label class="form-label" style="color: #ef4444;">
                        <i class="fa-solid fa-tag"></i> Զեղչված գին
                    </label>
                    <input type="number" name="discount_price" step="100" placeholder="Օրինակ՝ 2500" class="form-input" style="border-color: rgba(239, 68, 68, 0.4);">
                </div>
            </div>

            <!-- Happy Hour & Discount Schedule Section -->
            <div style="margin-bottom: 1.25rem; background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 14px; padding: 1rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span style="width: 28px; height: 28px; border-radius: 8px; background: rgba(239, 68, 68, 0.15); color: #ef4444; display: inline-flex; align-items: center; justify-content: center; font-size: 0.85rem;">
                            <i class="fa-solid fa-clock"></i>
                        </span>
                        <div>
                            <strong style="font-size: 0.85rem; color: var(--text-main);">Զեղչի Ժամանակացույց (Happy Hour)</strong>
                            <small style="display: block; color: var(--text-muted); font-size: 0.72rem;">Սահմանեք օրերը և ժամերը, երբ կգործի զեղչը</small>
                        </div>
                    </div>
                    <label style="font-size: 0.78rem; display: flex; align-items: center; gap: 0.4rem; cursor: pointer; color: var(--text-main); font-weight: 600;">
                        <input type="checkbox" name="is_discount_active" value="1" checked> Ակտիվացնել զեղչը
                    </label>
                </div>

                <!-- Day Selector Chips -->
                <div style="margin-bottom: 0.75rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem; flex-wrap: wrap; gap: 0.35rem;">
                        <label style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600;">Շաբաթվա օրեր</label>
                        <div style="display: flex; gap: 0.35rem;">
                            <button type="button" class="btn btn-secondary" style="padding: 0.15rem 0.45rem; font-size: 0.68rem;" onclick="setDiscountDaysPreset('new', 'all')">Բոլորը</button>
                            <button type="button" class="btn btn-secondary" style="padding: 0.15rem 0.45rem; font-size: 0.68rem;" onclick="setDiscountDaysPreset('new', 'weekdays')">Երկ-Ուրբ</button>
                            <button type="button" class="btn btn-secondary" style="padding: 0.15rem 0.45rem; font-size: 0.68rem;" onclick="setDiscountDaysPreset('new', 'weekends')">Հանգստյան</button>
                        </div>
                    </div>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.4rem;">
                        @php
                            $weekDays = [
                                'mon' => 'Երկ',
                                'tue' => 'Երք',
                                'wed' => 'Չոր',
                                'thu' => 'Հնգ',
                                'fri' => 'Ուրբ',
                                'sat' => 'Շաբ',
                                'sun' => 'Կիր'
                            ];
                        @endphp
                        @foreach($weekDays as $key => $lbl)
                            <label style="display: inline-flex; align-items: center; gap: 0.3rem; font-size: 0.75rem; background: var(--input-bg); border: 1px solid var(--border-color); padding: 0.25rem 0.55rem; border-radius: 8px; cursor: pointer; color: var(--text-main);">
                                <input type="checkbox" class="new-discount-day-checkbox" name="discount_days[]" value="{{ $key }}" checked> {{ $lbl }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Time Window -->
                <div class="grid-2" style="margin-bottom: 0.35rem;">
                    <div>
                        <label class="form-label" style="font-size: 0.75rem;">Սկիզբ (Start Time)</label>
                        <input type="time" name="discount_start_time" class="form-input" style="padding: 0.45rem 0.75rem;">
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 0.75rem;">Ավարտ (End Time)</label>
                        <input type="time" name="discount_end_time" class="form-input" style="padding: 0.45rem 0.75rem;">
                    </div>
                </div>
            </div>

            <div class="grid-2" style="margin-bottom: 1rem;">
                <div>
                    <label class="form-label">Armenian Name (Հայերեն)</label>
                    <input type="text" name="hy_name" class="form-input">
                </div>
                <div>
                    <label class="form-label">Russian Name (Русский)</label>
                    <input type="text" name="ru_name" class="form-input">
                </div>
            </div>

            <div style="margin-bottom: 1rem;">
                <label class="form-label">
                    <i class="fa-solid fa-image" style="color: var(--primary);"></i> Dish Image (Upload File or Enter URL)
                </label>
                <div class="grid-2" style="align-items: center;">
                    <div>
                        <input type="file" name="image_file" accept="image/*" class="form-input" style="padding: 0.45rem;">
                        <small style="color: var(--text-muted); font-size: 0.7rem; display: block; margin-top: 0.25rem;">📁 Upload file from device</small>
                    </div>
                    <div>
                        <input type="url" name="image" placeholder="https://..." class="form-input">
                        <small style="color: var(--text-muted); font-size: 0.7rem; display: block; margin-top: 0.25rem;">🔗 Or paste external image link</small>
                    </div>
                </div>
            </div>

            <div style="margin-bottom: 1rem;">
                <label class="form-label">Description</label>
                <textarea name="description" rows="2" class="form-textarea"></textarea>
            </div>

            <!-- Portions & Variations Section -->
            <div style="margin-bottom: 1.25rem; background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 14px; padding: 1rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem;">
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-main);">
                            <i class="fa-solid fa-sliders" style="color: var(--primary);"></i> Portions & Variations (Optional)
                        </label>
                        <small style="color: var(--text-muted); font-size: 0.72rem;">Add sizes/options with translations (EN, Հայերեն, Русский)</small>
                    </div>
                    <button type="button" class="btn btn-secondary" style="padding: 0.3rem 0.65rem; font-size: 0.75rem;" onclick="addVariationRow('new')">
                        <i class="fa-solid fa-plus"></i> Add Portion
                    </button>
                </div>
                <div id="new_variations_container" style="display: flex; flex-direction: column; gap: 0.5rem;"></div>
            </div>

            <!-- Dietary & Allergens -->
            <div style="margin-bottom: 1rem;">
                <label class="form-label">Dietary Tags</label>
                <div style="display: flex; flex-wrap: wrap; gap: 0.4rem;">
                    @foreach(['vegan', 'vegetarian', 'gluten_free', 'halal', 'chef_special', 'spicy'] as $dtag)
                        <label style="display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.78rem; background: var(--input-bg); border: 1px solid var(--border-color); padding: 0.25rem 0.55rem; border-radius: 8px; cursor: pointer; color: var(--text-main);">
                            <input type="checkbox" name="dietary_tags[]" value="{{ $dtag }}"> {{ str_replace('_', ' ', strtoupper($dtag)) }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div style="margin-bottom: 1rem;">
                <label class="form-label">EU Allergens Tagging</label>
                <div style="display: flex; flex-wrap: wrap; gap: 0.4rem;">
                    @foreach($allergens as $allg)
                        <label style="display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.78rem; background: var(--input-bg); border: 1px solid var(--border-color); padding: 0.25rem 0.55rem; border-radius: 8px; cursor: pointer; color: var(--text-main);">
                            <input type="checkbox" name="allergens[]" value="{{ $allg->id }}"> {{ $allg->icon }} {{ $allg->name }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="grid-3" style="margin-bottom: 1.5rem;">
                <div>
                    <label class="form-label" style="font-size: 0.75rem;">Calories (kcal)</label>
                    <input type="number" name="calories" placeholder="450" class="form-input">
                </div>
                <div>
                    <label class="form-label" style="font-size: 0.75rem;">Prep Mins</label>
                    <input type="number" name="preparation_time_min" placeholder="15" class="form-input">
                </div>
                <div style="display: flex; align-items: flex-end; padding-bottom: 0.35rem;">
                    <label style="font-size: 0.82rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem; cursor: pointer; color: var(--text-main);">
                        <input type="checkbox" name="is_featured" value="1"> ★ Featured Dish
                    </label>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('newProductModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Dish</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Product -->
<div id="editProductModal" class="modern-modal-overlay" style="display: none;">
    <div class="modal-box-responsive">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="font-size: 1.25rem; font-weight: 800; color: var(--text-main); margin: 0;">Edit Dish</h3>
            <button onclick="document.getElementById('editProductModal').style.display='none'" style="background: none; border: none; color: var(--text-muted); font-size: 1.25rem; cursor: pointer;"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form id="editProductForm" method="POST" enctype="multipart/form-data">
            @csrf
            <div style="margin-bottom: 1rem;">
                <label class="form-label">Category *</label>
                <select id="edit_prod_category_id" name="category_id" required class="form-select">
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid-3" style="margin-bottom: 1rem;">
                <div>
                    <label class="form-label">Dish Name (English) *</label>
                    <input type="text" id="edit_prod_name" name="name" required class="form-input">
                </div>
                <div>
                    <label class="form-label">Price ({{ $vendor->currency }}) *</label>
                    <input type="number" id="edit_prod_price" name="price" step="100" required class="form-input">
                </div>
                <div>
                    <label class="form-label" style="color: #ef4444;">
                        <i class="fa-solid fa-tag"></i> Զեղչված գին
                    </label>
                    <input type="number" id="edit_prod_discount_price" name="discount_price" step="100" placeholder="Օրինակ՝ 2500" class="form-input" style="border-color: rgba(239, 68, 68, 0.4);">
                </div>
            </div>

            <!-- Happy Hour & Discount Schedule Section (Edit) -->
            <div style="margin-bottom: 1.25rem; background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 14px; padding: 1rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span style="width: 28px; height: 28px; border-radius: 8px; background: rgba(239, 68, 68, 0.15); color: #ef4444; display: inline-flex; align-items: center; justify-content: center; font-size: 0.85rem;">
                            <i class="fa-solid fa-clock"></i>
                        </span>
                        <div>
                            <strong style="font-size: 0.85rem; color: var(--text-main);">Զեղչի Ժամանակացույց (Happy Hour)</strong>
                            <small style="display: block; color: var(--text-muted); font-size: 0.72rem;">Սահմանեք օրերը և ժամերը, երբ կգործի զեղչը</small>
                        </div>
                    </div>
                    <label style="font-size: 0.78rem; display: flex; align-items: center; gap: 0.4rem; cursor: pointer; color: var(--text-main); font-weight: 600;">
                        <input type="checkbox" id="edit_prod_is_discount_active" name="is_discount_active" value="1"> Ակտիվացնել զեղչը
                    </label>
                </div>

                <!-- Day Selector Chips -->
                <div style="margin-bottom: 0.75rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem; flex-wrap: wrap; gap: 0.35rem;">
                        <label style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600;">Շաբաթվա օրեր</label>
                        <div style="display: flex; gap: 0.35rem;">
                            <button type="button" class="btn btn-secondary" style="padding: 0.15rem 0.45rem; font-size: 0.68rem;" onclick="setDiscountDaysPreset('edit', 'all')">Բոլորը</button>
                            <button type="button" class="btn btn-secondary" style="padding: 0.15rem 0.45rem; font-size: 0.68rem;" onclick="setDiscountDaysPreset('edit', 'weekdays')">Երկ-Ուրբ</button>
                            <button type="button" class="btn btn-secondary" style="padding: 0.15rem 0.45rem; font-size: 0.68rem;" onclick="setDiscountDaysPreset('edit', 'weekends')">Հանգստյան</button>
                        </div>
                    </div>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.4rem;">
                        @foreach($weekDays as $key => $lbl)
                            <label style="display: inline-flex; align-items: center; gap: 0.3rem; font-size: 0.75rem; background: var(--input-bg); border: 1px solid var(--border-color); padding: 0.25rem 0.55rem; border-radius: 8px; cursor: pointer; color: var(--text-main);">
                                <input type="checkbox" class="edit-discount-day-checkbox" name="discount_days[]" value="{{ $key }}"> {{ $lbl }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Time Window -->
                <div class="grid-2" style="margin-bottom: 0.35rem;">
                    <div>
                        <label class="form-label" style="font-size: 0.75rem;">Սկիզբ (Start Time)</label>
                        <input type="time" id="edit_prod_discount_start_time" name="discount_start_time" class="form-input" style="padding: 0.45rem 0.75rem;">
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 0.75rem;">Ավարտ (End Time)</label>
                        <input type="time" id="edit_prod_discount_end_time" name="discount_end_time" class="form-input" style="padding: 0.45rem 0.75rem;">
                    </div>
                </div>
            </div>

            <div class="grid-2" style="margin-bottom: 1rem;">
                <div>
                    <label class="form-label">Armenian Name (Հայերեն)</label>
                    <input type="text" id="edit_prod_hy_name" name="hy_name" class="form-input">
                </div>
                <div>
                    <label class="form-label">Russian Name (Русский)</label>
                    <input type="text" id="edit_prod_ru_name" name="ru_name" class="form-input">
                </div>
            </div>

            <div style="margin-bottom: 1rem;">
                <label class="form-label">
                    <i class="fa-solid fa-image" style="color: var(--primary);"></i> Dish Image (Upload New File or Change URL)
                </label>
                <div class="grid-2" style="align-items: center;">
                    <div>
                        <input type="file" name="image_file" accept="image/*" class="form-input" style="padding: 0.45rem;">
                        <small style="color: var(--text-muted); font-size: 0.7rem; display: block; margin-top: 0.25rem;">📁 Upload new image file</small>
                    </div>
                    <div>
                        <input type="url" id="edit_prod_image" name="image" placeholder="https://..." class="form-input">
                        <small style="color: var(--text-muted); font-size: 0.7rem; display: block; margin-top: 0.25rem;">🔗 Or edit external image link</small>
                    </div>
                </div>
            </div>

            <div style="margin-bottom: 1rem;">
                <label class="form-label">Description</label>
                <textarea id="edit_prod_description" name="description" rows="2" class="form-textarea"></textarea>
            </div>

            <div class="grid-2" style="margin-bottom: 1rem;">
                <div>
                    <label class="form-label">Armenian Description (Հայերեն)</label>
                    <textarea id="edit_prod_hy_description" name="hy_description" rows="2" class="form-textarea"></textarea>
                </div>
                <div>
                    <label class="form-label">Russian Description (Русский)</label>
                    <textarea id="edit_prod_ru_description" name="ru_description" rows="2" class="form-textarea"></textarea>
                </div>
            </div>

            <!-- Portions & Variations Section -->
            <div style="margin-bottom: 1.25rem; background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 14px; padding: 1rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem;">
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-main);">
                            <i class="fa-solid fa-sliders" style="color: var(--primary);"></i> Portions & Variations (Optional)
                        </label>
                        <small style="color: var(--text-muted); font-size: 0.72rem;">Add or edit portions with translations (EN, Հայերեն, Русский)</small>
                    </div>
                    <button type="button" class="btn btn-secondary" style="padding: 0.3rem 0.65rem; font-size: 0.75rem;" onclick="addVariationRow('edit')">
                        <i class="fa-solid fa-plus"></i> Add Portion
                    </button>
                </div>
                <div id="edit_variations_container" style="display: flex; flex-direction: column; gap: 0.5rem;"></div>
            </div>

            <!-- Dietary & Allergens -->
            <div style="margin-bottom: 1rem;">
                <label class="form-label">Dietary Tags</label>
                <div style="display: flex; flex-wrap: wrap; gap: 0.4rem;">
                    @foreach(['vegan', 'vegetarian', 'gluten_free', 'halal', 'chef_special', 'spicy'] as $dtag)
                        <label style="display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.78rem; background: var(--input-bg); border: 1px solid var(--border-color); padding: 0.25rem 0.55rem; border-radius: 8px; cursor: pointer; color: var(--text-main);">
                            <input type="checkbox" class="edit-dietary-checkbox" name="dietary_tags[]" value="{{ $dtag }}"> {{ str_replace('_', ' ', strtoupper($dtag)) }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div style="margin-bottom: 1rem;">
                <label class="form-label">EU Allergens Tagging</label>
                <div style="display: flex; flex-wrap: wrap; gap: 0.4rem;">
                    @foreach($allergens as $allg)
                        <label style="display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.78rem; background: var(--input-bg); border: 1px solid var(--border-color); padding: 0.25rem 0.55rem; border-radius: 8px; cursor: pointer; color: var(--text-main);">
                            <input type="checkbox" class="edit-allergen-checkbox" name="allergens[]" value="{{ $allg->id }}"> {{ $allg->icon }} {{ $allg->name }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="grid-3" style="margin-bottom: 1.5rem;">
                <div>
                    <label class="form-label" style="font-size: 0.75rem;">Calories (kcal)</label>
                    <input type="number" id="edit_prod_calories" name="calories" placeholder="450" class="form-input">
                </div>
                <div>
                    <label class="form-label" style="font-size: 0.75rem;">Prep Mins</label>
                    <input type="number" id="edit_prod_preparation_time_min" name="preparation_time_min" placeholder="15" class="form-input">
                </div>
                <div style="display: flex; align-items: flex-end; padding-bottom: 0.35rem;">
                    <label style="font-size: 0.82rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem; cursor: pointer; color: var(--text-main);">
                        <input type="checkbox" id="edit_prod_is_featured" name="is_featured" value="1"> ★ Featured Dish
                    </label>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('editProductModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Dish</button>
            </div>
        </form>
    </div>
</div>

<script>
    function setDiscountDaysPreset(prefix, type) {
        const checkboxes = document.querySelectorAll(`.${prefix}-discount-day-checkbox`);
        checkboxes.forEach(cb => {
            if (type === 'all') {
                cb.checked = true;
            } else if (type === 'weekdays') {
                cb.checked = ['mon', 'tue', 'wed', 'thu', 'fri'].includes(cb.value);
            } else if (type === 'weekends') {
                cb.checked = ['sat', 'sun'].includes(cb.value);
            }
        });
    }

    function editCategory(cat) {
        document.getElementById('editCategoryForm').action = "/admin/menu/categories/" + cat.id;
        document.getElementById('edit_cat_name').value = cat.name || '';
        document.getElementById('edit_cat_hy_name').value = (cat.name_translations && cat.name_translations.hy) ? cat.name_translations.hy : '';
        document.getElementById('edit_cat_ru_name').value = (cat.name_translations && cat.name_translations.ru) ? cat.name_translations.ru : '';
        document.getElementById('edit_cat_description').value = cat.description || '';
        document.getElementById('editCategoryModal').style.display = 'flex';
    }

    function editProduct(prod) {
        document.getElementById('editProductForm').action = "/admin/menu/products/" + prod.id;
        document.getElementById('edit_prod_category_id').value = prod.category_id;
        document.getElementById('edit_prod_name').value = prod.name || '';
        document.getElementById('edit_prod_price').value = prod.price || 0;
        
        document.getElementById('edit_prod_hy_name').value = (prod.name_translations && prod.name_translations.hy) ? prod.name_translations.hy : '';
        document.getElementById('edit_prod_ru_name').value = (prod.name_translations && prod.name_translations.ru) ? prod.name_translations.ru : '';
        
        document.getElementById('edit_prod_image').value = (prod.image && !prod.image.includes('default-dish')) ? prod.image : '';
        document.getElementById('edit_prod_description').value = prod.description || '';
        
        document.getElementById('edit_prod_hy_description').value = (prod.description_translations && prod.description_translations.hy) ? prod.description_translations.hy : '';
        document.getElementById('edit_prod_ru_description').value = (prod.description_translations && prod.description_translations.ru) ? prod.description_translations.ru : '';
        
        // Dietary tags
        const tags = prod.dietary_tags || [];
        document.querySelectorAll('.edit-dietary-checkbox').forEach(cb => {
            cb.checked = tags.includes(cb.value);
        });
        
        // Allergens
        const allergenIds = prod.allergens ? prod.allergens.map(a => a.id) : [];
        document.querySelectorAll('.edit-allergen-checkbox').forEach(cb => {
            cb.checked = allergenIds.includes(parseInt(cb.value));
        });
        
        document.getElementById('edit_prod_calories').value = prod.calories || '';
        document.getElementById('edit_prod_preparation_time_min').value = prod.preparation_time_min || '';
        document.getElementById('edit_prod_is_featured').checked = !!prod.is_featured;

        // Discount & Happy Hour fields
        document.getElementById('edit_prod_discount_price').value = prod.discount_price || '';
        document.getElementById('edit_prod_discount_start_time').value = prod.discount_start_time ? prod.discount_start_time.substring(0, 5) : '';
        document.getElementById('edit_prod_discount_end_time').value = prod.discount_end_time ? prod.discount_end_time.substring(0, 5) : '';
        document.getElementById('edit_prod_is_discount_active').checked = prod.is_discount_active !== false && prod.is_discount_active !== 0;

        const discountDays = prod.discount_days || [];
        document.querySelectorAll('.edit-discount-day-checkbox').forEach(cb => {
            cb.checked = discountDays.length === 0 || discountDays.includes(cb.value);
        });
        
        // Populate Variations
        const editContainer = document.getElementById('edit_variations_container');
        if (editContainer) {
            editContainer.innerHTML = '';
            if (prod.variations && prod.variations.length > 0) {
                prod.variations.forEach(v => {
                    addVariationRow('edit', v);
                });
            }
        }

        document.getElementById('editProductModal').style.display = 'flex';
    }

    let variationCounter = 0;

    function addVariationRow(prefix, varData = null) {
        const container = document.getElementById(prefix + '_variations_container');
        if (!container) return;

        const idx = variationCounter++;
        const id = varData ? (varData.id || '') : '';
        const name = varData ? (varData.name || '') : '';
        const hyName = (varData && varData.name_translations && varData.name_translations.hy) ? varData.name_translations.hy : ((varData && varData.hy_name) ? varData.hy_name : '');
        const ruName = (varData && varData.name_translations && varData.name_translations.ru) ? varData.name_translations.ru : ((varData && varData.ru_name) ? varData.ru_name : '');
        const price = varData ? (varData.price || '') : '';
        const isDefault = varData ? !!varData.is_default : (container.children.length === 0);

        const row = document.createElement('div');
        row.className = 'variation-row';
        row.style = 'display: flex; flex-direction: column; gap: 0.5rem; padding: 0.75rem; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; margin-bottom: 0.35rem;';
        row.innerHTML = `
            <input type="hidden" name="variations[${idx}][id]" value="${id}">
            <input type="hidden" name="variations[${idx}][is_default]" class="var-is-default-input" value="${isDefault ? '1' : '0'}">
            
            <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                <input type="text" name="variations[${idx}][name]" value="${name}" placeholder="English (e.g. Regular)" required class="form-input" style="flex: 2; min-width: 140px; padding: 0.45rem 0.65rem; font-size: 0.85rem;">
                <input type="number" name="variations[${idx}][price]" value="${price}" placeholder="Price" step="100" required class="form-input" style="flex: 1; min-width: 90px; padding: 0.45rem 0.65rem; font-size: 0.85rem;">
                <label style="display: flex; align-items: center; gap: 0.35rem; font-size: 0.75rem; color: var(--text-muted); cursor: pointer; white-space: nowrap; flex-shrink: 0;" title="Default Portion">
                    <input type="radio" name="${prefix}_default_radio" ${isDefault ? 'checked' : ''} onchange="setDefaultVariation(this)">
                    <span>Default</span>
                </label>
                <button type="button" class="btn btn-danger" style="padding: 0.4rem 0.6rem; font-size: 0.75rem;" onclick="this.closest('.variation-row').remove()" title="Remove portion">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>

            <div class="grid-2" style="gap: 0.5rem;">
                <input type="text" name="variations[${idx}][hy_name]" value="${hyName}" placeholder="Հայերեն (օր. Սովորական)" class="form-input" style="padding: 0.4rem 0.65rem; font-size: 0.8rem;">
                <input type="text" name="variations[${idx}][ru_name]" value="${ruName}" placeholder="Русский (напр. Стандарт)" class="form-input" style="padding: 0.4rem 0.65rem; font-size: 0.8rem;">
            </div>
        `;
        container.appendChild(row);
    }

    function setDefaultVariation(radioEl) {
        const container = radioEl.closest('#new_variations_container, #edit_variations_container');
        if (!container) return;
        container.querySelectorAll('.variation-row').forEach(row => {
            const rowRadio = row.querySelector('input[type="radio"]');
            const defaultInput = row.querySelector('.var-is-default-input');
            if (rowRadio && defaultInput) {
                defaultInput.value = rowRadio.checked ? '1' : '0';
            }
        });
    }

    function openNewProductModal() {
        const container = document.getElementById('new_variations_container');
        if (container) container.innerHTML = '';
        document.getElementById('newProductModal').style.display = 'flex';
    }
</script>
@endsection
