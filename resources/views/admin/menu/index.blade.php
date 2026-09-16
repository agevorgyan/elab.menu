@extends('layouts.app')

@section('title', 'Menu Builder - ' . $vendor->name)

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h1 style="font-family: 'Outfit', sans-serif; font-size: 1.75rem; font-weight: 700;">Menu Builder</h1>
        <p style="color: var(--text-muted); font-size: 0.9rem;">Manage categories, dishes, prices, allergens, and dietary tags.</p>
    </div>
    <div style="display: flex; gap: 0.75rem;">
        <button class="btn btn-secondary" onclick="document.getElementById('newCategoryModal').style.display='flex'">
            <i class="fa-solid fa-folder-plus"></i> New Category
        </button>
        <button class="btn btn-primary" onclick="document.getElementById('newProductModal').style.display='flex'">
            <i class="fa-solid fa-plus"></i> Add New Dish
        </button>
    </div>
</div>

@if($categories->count() == 0)
    <div class="card" style="text-align: center; padding: 4rem 2rem;">
        <i class="fa-solid fa-utensils" style="font-size: 3rem; color: var(--primary); margin-bottom: 1rem;"></i>
        <h2 style="font-family: 'Outfit'; font-size: 1.5rem; margin-bottom: 0.5rem;">Your menu is empty</h2>
        <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Start by adding categories and dishes, or use our AI Menu Import tool to import from PDF/Word.</p>
        <div style="display: flex; justify-content: center; gap: 1rem;">
            <a href="{{ route('admin.ai.import') }}" class="btn btn-primary"><i class="fa-solid fa-wand-magic-sparkles"></i> AI Menu Import</a>
            <button class="btn btn-secondary" onclick="document.getElementById('newCategoryModal').style.display='flex'"><i class="fa-solid fa-plus"></i> Create Category</button>
        </div>
    </div>
@else
    @foreach($categories as $category)
        <div class="card" style="margin-bottom: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem;">
                <div>
                    <h2 style="font-family: 'Outfit'; font-size: 1.25rem; font-weight: 700;">
                        {{ $category->name }}
                        <span style="font-size: 0.8rem; font-weight: 400; color: var(--text-muted); margin-left: 0.5rem;">
                            (HY: {{ $category->getTranslatedName('hy') }} | RU: {{ $category->getTranslatedName('ru') }})
                        </span>
                    </h2>
                    @if($category->description)
                        <p style="font-size: 0.85rem; color: var(--text-muted);">{{ $category->description }}</p>
                    @endif
                </div>
                <span style="background: rgba(255,255,255,0.05); padding: 0.25rem 0.65rem; border-radius: 9999px; font-size: 0.8rem; color: var(--text-muted);">
                    {{ $category->products->count() }} dishes
                </span>
            </div>

            <div style="display: flex; flex-direction: column; gap: 1rem;">
                @foreach($category->products as $product)
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 1rem; background: rgba(0,0,0,0.25); border: 1px solid var(--border-color); border-radius: 12px; gap: 1.25rem;">
                        <img src="{{ $product->image ?? 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=150&q=80' }}" style="width: 70px; height: 70px; object-fit: cover; border-radius: 10px;" alt="dish">
                        
                        <div style="flex: 1;">
                            <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                                <strong style="font-size: 1.05rem;">{{ $product->name }}</strong>
                                @if($product->is_featured)
                                    <span style="background: rgba(245, 158, 11, 0.2); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.4); padding: 0.15rem 0.45rem; border-radius: 4px; font-size: 0.7rem; font-weight: 700;">★ FEATURED</span>
                                @endif

                                @if(!empty($product->dietary_tags))
                                    @foreach($product->dietary_tags as $tag)
                                        <span style="background: rgba(16, 185, 129, 0.15); color: #34d399; padding: 0.15rem 0.45rem; border-radius: 4px; font-size: 0.7rem; text-transform: uppercase; font-weight: 700;">{{ str_replace('_', ' ', $tag) }}</span>
                                    @endforeach
                                @endif
                            </div>

                            <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.25rem;">{{ $product->description }}</p>

                            <div style="display: flex; gap: 1rem; align-items: center; margin-top: 0.5rem; font-size: 0.75rem; color: var(--text-muted);">
                                @if($product->calories)
                                    <span><i class="fa-solid fa-fire text-amber-500"></i> {{ $product->calories }} kcal</span>
                                @endif
                                @if($product->preparation_time_min)
                                    <span><i class="fa-solid fa-clock"></i> {{ $product->preparation_time_min }} mins</span>
                                @endif
                                @if($product->allergens->count() > 0)
                                    <span><i class="fa-solid fa-triangle-exclamation text-red-400"></i> Allergens: {{ $product->allergens->pluck('icon')->join(' ') }}</span>
                                @endif
                            </div>
                        </div>

                        <div style="text-align: right; display: flex; flex-direction: column; align-items: flex-end; gap: 0.5rem;">
                            <div style="font-size: 1.25rem; font-weight: 800; color: #f59e0b; font-family: 'Outfit';">
                                {{ number_format($product->price) }} {{ $vendor->currency }}
                            </div>

                            <div style="display: flex; gap: 0.5rem;">
                                <form action="{{ route('admin.menu.products.toggle', $product->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-secondary" style="padding: 0.35rem 0.65rem; font-size: 0.75rem;">
                                        @if($product->is_available)
                                            <span style="color: #34d399;"><i class="fa-solid fa-toggle-on"></i> In Stock</span>
                                        @else
                                            <span style="color: #f87171;"><i class="fa-solid fa-toggle-off"></i> Out of Stock</span>
                                        @endif
                                    </button>
                                </form>

                                <form action="{{ route('admin.menu.products.destroy', $product->id) }}" method="POST" onsubmit="return confirm('Delete dish {{ $product->name }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger" style="padding: 0.35rem 0.65rem; font-size: 0.75rem;">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
@endif

<!-- Modal Add Category -->
<div id="newCategoryModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 100; align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; width: 100%; max-width: 480px; padding: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="font-family: 'Outfit'; font-size: 1.25rem;">Create New Category</h3>
            <button onclick="document.getElementById('newCategoryModal').style.display='none'" style="background: none; border: none; color: #fff; font-size: 1.25rem; cursor: pointer;"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form action="{{ route('admin.menu.categories.store') }}" method="POST">
            @csrf
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Category Name (English)</label>
                <input type="text" name="name" required placeholder="e.g. Signature Cocktails" style="width: 100%; padding: 0.6rem; background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 6px; color: #fff;">
            </div>
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Armenian Name (Հայերեն)</label>
                <input type="text" name="hy_name" placeholder="օր․ Կոկտեյլներ" style="width: 100%; padding: 0.6rem; background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 6px; color: #fff;">
            </div>
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Russian Name (Русский)</label>
                <input type="text" name="ru_name" placeholder="напр. Коктейли" style="width: 100%; padding: 0.6rem; background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 6px; color: #fff;">
            </div>
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Description</label>
                <textarea name="description" rows="2" style="width: 100%; padding: 0.6rem; background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 6px; color: #fff;"></textarea>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('newCategoryModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Category</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Add Product -->
<div id="newProductModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 100; align-items: center; justify-content: center; padding: 1rem; overflow-y: auto;">
    <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; width: 100%; max-width: 600px; padding: 2rem; margin: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="font-family: 'Outfit'; font-size: 1.25rem;">Add New Menu Dish</h3>
            <button onclick="document.getElementById('newProductModal').style.display='none'" style="background: none; border: none; color: #fff; font-size: 1.25rem; cursor: pointer;"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form action="{{ route('admin.menu.products.store') }}" method="POST">
            @csrf
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Category</label>
                <select name="category_id" required style="width: 100%; padding: 0.6rem; background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 6px; color: #fff;">
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Dish Name (English)</label>
                    <input type="text" name="name" required style="width: 100%; padding: 0.6rem; background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 6px; color: #fff;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Price ({{ $vendor->currency }})</label>
                    <input type="number" name="price" step="100" required style="width: 100%; padding: 0.6rem; background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 6px; color: #fff;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Armenian Name (Հայերեն)</label>
                    <input type="text" name="hy_name" style="width: 100%; padding: 0.6rem; background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 6px; color: #fff;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Russian Name (Русский)</label>
                    <input type="text" name="ru_name" style="width: 100%; padding: 0.6rem; background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 6px; color: #fff;">
                </div>
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Image URL</label>
                <input type="url" name="image" placeholder="https://images.unsplash.com/..." style="width: 100%; padding: 0.6rem; background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 6px; color: #fff;">
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Description</label>
                <textarea name="description" rows="2" style="width: 100%; padding: 0.6rem; background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 6px; color: #fff;"></textarea>
            </div>

            <!-- Dietary & Allergens -->
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.5rem;">EU Allergens Tagging</label>
                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                    @foreach($allergens as $allg)
                        <label style="display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.8rem; background: rgba(255,255,255,0.05); padding: 0.25rem 0.5rem; border-radius: 4px; cursor: pointer;">
                            <input type="checkbox" name="allergens[]" value="{{ $allg->id }}"> {{ $allg->icon }} {{ $allg->name }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0.75rem; margin-bottom: 1.5rem;">
                <div>
                    <label style="font-size: 0.75rem; color: var(--text-muted);">Calories (kcal)</label>
                    <input type="number" name="calories" placeholder="450" style="width: 100%; padding: 0.5rem; background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 6px; color: #fff;">
                </div>
                <div>
                    <label style="font-size: 0.75rem; color: var(--text-muted);">Prep Mins</label>
                    <input type="number" name="preparation_time_min" placeholder="15" style="width: 100%; padding: 0.5rem; background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 6px; color: #fff;">
                </div>
                <div style="display: flex; align-items: flex-end;">
                    <label style="font-size: 0.8rem; display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" name="is_featured" value="1"> ★ Featured
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
@endsection
