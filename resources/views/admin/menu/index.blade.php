@extends('layouts.app')

@section('title', 'Menu Builder - ' . $vendor->name)

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
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
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem; flex-wrap: wrap; gap: 0.75rem;">
                <div>
                    <h2 style="font-family: 'Outfit'; font-size: 1.25rem; font-weight: 700; color: var(--text-main);">
                        {{ $category->name }}
                        <span style="font-size: 0.8rem; font-weight: 400; color: var(--text-muted); margin-left: 0.5rem;">
                            (HY: {{ $category->getTranslatedName('hy') }} | RU: {{ $category->getTranslatedName('ru') }})
                        </span>
                    </h2>
                    @if($category->description)
                        <p style="font-size: 0.85rem; color: var(--text-muted);">{{ $category->description }}</p>
                    @endif
                </div>

                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <button type="button" class="btn btn-secondary" style="padding: 0.35rem 0.65rem; font-size: 0.75rem;" onclick="editCategory({{ json_encode($category) }})">
                        <i class="fa-solid fa-pen"></i> Edit Category
                    </button>
                    <form action="{{ route('admin.menu.categories.destroy', $category->id) }}" method="POST" onsubmit="return confirm('Delete category {{ $category->name }} and all its dishes?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger" style="padding: 0.35rem 0.65rem; font-size: 0.75rem;" title="Delete Category">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </form>
                    <span style="background: var(--input-bg); border: 1px solid var(--border-color); padding: 0.25rem 0.65rem; border-radius: 9999px; font-size: 0.8rem; color: var(--text-muted);">
                        {{ $category->products->count() }} dishes
                    </span>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 1rem;">
                @foreach($category->products as $product)
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 1rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 14px; gap: 1.25rem; flex-wrap: wrap;">
                        <img src="{{ $product->image ?? 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=150&q=80' }}" style="width: 70px; height: 70px; object-fit: cover; border-radius: 10px;" alt="dish">
                        
                        <div style="flex: 1; min-width: 240px;">
                            <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                                <strong style="font-size: 1.05rem; color: var(--text-main);">{{ $product->name }}</strong>
                                @if($product->is_featured)
                                    <span style="background: var(--badge-bg); color: var(--badge-text); border: 1px solid var(--border-color); padding: 0.15rem 0.45rem; border-radius: 6px; font-size: 0.7rem; font-weight: 700;">★ FEATURED</span>
                                @endif

                                @if(!empty($product->dietary_tags))
                                    @foreach($product->dietary_tags as $tag)
                                        <span style="background: rgba(16, 185, 129, 0.15); color: #10b981; padding: 0.15rem 0.45rem; border-radius: 6px; font-size: 0.7rem; text-transform: uppercase; font-weight: 700;">{{ str_replace('_', ' ', $tag) }}</span>
                                    @endforeach
                                @endif
                            </div>

                            <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.25rem;">{{ $product->description }}</p>

                            <div style="display: flex; gap: 1rem; align-items: center; margin-top: 0.5rem; font-size: 0.75rem; color: var(--text-muted); flex-wrap: wrap;">
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

                        <div style="text-align: right; display: flex; flex-direction: column; align-items: flex-end; gap: 0.5rem;">
                            <div style="font-size: 1.25rem; font-weight: 800; color: var(--primary); font-family: 'Outfit';">
                                {{ number_format($product->price) }} {{ $vendor->currency }}
                            </div>

                            <div style="display: flex; gap: 0.5rem;">
                                <button type="button" class="btn btn-secondary" style="padding: 0.35rem 0.65rem; font-size: 0.75rem;" onclick="editProduct({{ json_encode($product->load('allergens')) }})">
                                    <i class="fa-solid fa-pen"></i> Edit
                                </button>

                                <form action="{{ route('admin.menu.products.toggle', $product->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-secondary" style="padding: 0.35rem 0.65rem; font-size: 0.75rem;">
                                        @if($product->is_available)
                                            <span style="color: #10b981;"><i class="fa-solid fa-toggle-on"></i> In Stock</span>
                                        @else
                                            <span style="color: #ef4444;"><i class="fa-solid fa-toggle-off"></i> Out of Stock</span>
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
<div id="newCategoryModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(4px); z-index: 100; align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; width: 100%; max-width: 480px; padding: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="font-family: 'Outfit'; font-size: 1.25rem; color: var(--text-main);">Create New Category</h3>
            <button onclick="document.getElementById('newCategoryModal').style.display='none'" style="background: none; border: none; color: var(--text-main); font-size: 1.25rem; cursor: pointer;"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form action="{{ route('admin.menu.categories.store') }}" method="POST">
            @csrf
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Category Name (English)</label>
                <input type="text" name="name" required placeholder="e.g. Signature Cocktails" style="width: 100%; padding: 0.65rem 0.9rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-main); font-size: 0.9rem; outline: none;">
            </div>
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Armenian Name (Հայերեն)</label>
                <input type="text" name="hy_name" placeholder="օր․ Կոկտեյլներ" style="width: 100%; padding: 0.65rem 0.9rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-main); font-size: 0.9rem; outline: none;">
            </div>
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Russian Name (Русский)</label>
                <input type="text" name="ru_name" placeholder="напр. Коктейли" style="width: 100%; padding: 0.65rem 0.9rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-main); font-size: 0.9rem; outline: none;">
            </div>
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Description</label>
                <textarea name="description" rows="2" style="width: 100%; padding: 0.65rem 0.9rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-main); font-size: 0.9rem; outline: none;"></textarea>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('newCategoryModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Category</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Category -->
<div id="editCategoryModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(4px); z-index: 100; align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; width: 100%; max-width: 480px; padding: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="font-family: 'Outfit'; font-size: 1.25rem; color: var(--text-main);">Edit Category</h3>
            <button onclick="document.getElementById('editCategoryModal').style.display='none'" style="background: none; border: none; color: var(--text-main); font-size: 1.25rem; cursor: pointer;"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form id="editCategoryForm" method="POST">
            @csrf
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Category Name (English)</label>
                <input type="text" id="edit_cat_name" name="name" required style="width: 100%; padding: 0.65rem 0.9rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-main); font-size: 0.9rem; outline: none;">
            </div>
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Armenian Name (Հայերեն)</label>
                <input type="text" id="edit_cat_hy_name" name="hy_name" style="width: 100%; padding: 0.65rem 0.9rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-main); font-size: 0.9rem; outline: none;">
            </div>
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Russian Name (Русский)</label>
                <input type="text" id="edit_cat_ru_name" name="ru_name" style="width: 100%; padding: 0.65rem 0.9rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-main); font-size: 0.9rem; outline: none;">
            </div>
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Description</label>
                <textarea id="edit_cat_description" name="description" rows="2" style="width: 100%; padding: 0.65rem 0.9rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-main); font-size: 0.9rem; outline: none;"></textarea>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('editCategoryModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Category</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Add Product -->
<div id="newProductModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(4px); z-index: 100; align-items: center; justify-content: center; padding: 1rem; overflow-y: auto;">
    <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; width: 100%; max-width: 600px; padding: 2rem; margin: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="font-family: 'Outfit'; font-size: 1.25rem; color: var(--text-main);">Add New Menu Dish</h3>
            <button onclick="document.getElementById('newProductModal').style.display='none'" style="background: none; border: none; color: var(--text-main); font-size: 1.25rem; cursor: pointer;"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form action="{{ route('admin.menu.products.store') }}" method="POST">
            @csrf
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Category</label>
                <select name="category_id" required style="width: 100%; padding: 0.65rem 0.9rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-main); font-size: 0.9rem; outline: none;">
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Dish Name (English)</label>
                    <input type="text" name="name" required style="width: 100%; padding: 0.65rem 0.9rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-main); font-size: 0.9rem; outline: none;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Price ({{ $vendor->currency }})</label>
                    <input type="number" name="price" step="100" required style="width: 100%; padding: 0.65rem 0.9rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-main); font-size: 0.9rem; outline: none;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Armenian Name (Հայերեն)</label>
                    <input type="text" name="hy_name" style="width: 100%; padding: 0.65rem 0.9rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-main); font-size: 0.9rem; outline: none;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Russian Name (Русский)</label>
                    <input type="text" name="ru_name" style="width: 100%; padding: 0.65rem 0.9rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-main); font-size: 0.9rem; outline: none;">
                </div>
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Image URL</label>
                <input type="url" name="image" placeholder="https://images.unsplash.com/..." style="width: 100%; padding: 0.65rem 0.9rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-main); font-size: 0.9rem; outline: none;">
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Description</label>
                <textarea name="description" rows="2" style="width: 100%; padding: 0.65rem 0.9rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-main); font-size: 0.9rem; outline: none;"></textarea>
            </div>

            <!-- Dietary & Allergens -->
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.5rem;">Dietary Tags</label>
                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                    @foreach(['vegan', 'vegetarian', 'gluten_free', 'halal', 'chef_special', 'spicy'] as $dtag)
                        <label style="display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.8rem; background: var(--input-bg); border: 1px solid var(--border-color); padding: 0.25rem 0.5rem; border-radius: 6px; cursor: pointer; color: var(--text-main);">
                            <input type="checkbox" name="dietary_tags[]" value="{{ $dtag }}"> {{ str_replace('_', ' ', strtoupper($dtag)) }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.5rem;">EU Allergens Tagging</label>
                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                    @foreach($allergens as $allg)
                        <label style="display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.8rem; background: var(--input-bg); border: 1px solid var(--border-color); padding: 0.25rem 0.5rem; border-radius: 6px; cursor: pointer; color: var(--text-main);">
                            <input type="checkbox" name="allergens[]" value="{{ $allg->id }}"> {{ $allg->icon }} {{ $allg->name }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0.75rem; margin-bottom: 1.5rem;">
                <div>
                    <label style="font-size: 0.75rem; color: var(--text-muted);">Calories (kcal)</label>
                    <input type="number" name="calories" placeholder="450" style="width: 100%; padding: 0.5rem 0.75rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 8px; color: var(--text-main); font-size: 0.85rem; outline: none;">
                </div>
                <div>
                    <label style="font-size: 0.75rem; color: var(--text-muted);">Prep Mins</label>
                    <input type="number" name="preparation_time_min" placeholder="15" style="width: 100%; padding: 0.5rem 0.75rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 8px; color: var(--text-main); font-size: 0.85rem; outline: none;">
                </div>
                <div style="display: flex; align-items: flex-end;">
                    <label style="font-size: 0.8rem; display: flex; align-items: center; gap: 0.5rem; cursor: pointer; color: var(--text-main);">
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

<!-- Modal Edit Product -->
<div id="editProductModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(4px); z-index: 100; align-items: center; justify-content: center; padding: 1rem; overflow-y: auto;">
    <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; width: 100%; max-width: 600px; padding: 2rem; margin: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="font-family: 'Outfit'; font-size: 1.25rem; color: var(--text-main);">Edit Dish</h3>
            <button onclick="document.getElementById('editProductModal').style.display='none'" style="background: none; border: none; color: var(--text-main); font-size: 1.25rem; cursor: pointer;"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form id="editProductForm" method="POST">
            @csrf
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Category</label>
                <select id="edit_prod_category_id" name="category_id" required style="width: 100%; padding: 0.65rem 0.9rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-main); font-size: 0.9rem; outline: none;">
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Dish Name (English)</label>
                    <input type="text" id="edit_prod_name" name="name" required style="width: 100%; padding: 0.65rem 0.9rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-main); font-size: 0.9rem; outline: none;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Price ({{ $vendor->currency }})</label>
                    <input type="number" id="edit_prod_price" name="price" step="100" required style="width: 100%; padding: 0.65rem 0.9rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-main); font-size: 0.9rem; outline: none;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Armenian Name (Հայերեն)</label>
                    <input type="text" id="edit_prod_hy_name" name="hy_name" style="width: 100%; padding: 0.65rem 0.9rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-main); font-size: 0.9rem; outline: none;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Russian Name (Русский)</label>
                    <input type="text" id="edit_prod_ru_name" name="ru_name" style="width: 100%; padding: 0.65rem 0.9rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-main); font-size: 0.9rem; outline: none;">
                </div>
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Image URL</label>
                <input type="url" id="edit_prod_image" name="image" placeholder="https://images.unsplash.com/..." style="width: 100%; padding: 0.65rem 0.9rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-main); font-size: 0.9rem; outline: none;">
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Description</label>
                <textarea id="edit_prod_description" name="description" rows="2" style="width: 100%; padding: 0.65rem 0.9rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-main); font-size: 0.9rem; outline: none;"></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Armenian Description (Հայերեն)</label>
                    <textarea id="edit_prod_hy_description" name="hy_description" rows="2" style="width: 100%; padding: 0.65rem 0.9rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-main); font-size: 0.9rem; outline: none;"></textarea>
                </div>
                <div>
                    <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Russian Description (Русский)</label>
                    <textarea id="edit_prod_ru_description" name="ru_description" rows="2" style="width: 100%; padding: 0.65rem 0.9rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-main); font-size: 0.9rem; outline: none;"></textarea>
                </div>
            </div>

            <!-- Dietary & Allergens -->
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.5rem;">Dietary Tags</label>
                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                    @foreach(['vegan', 'vegetarian', 'gluten_free', 'halal', 'chef_special', 'spicy'] as $dtag)
                        <label style="display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.8rem; background: var(--input-bg); border: 1px solid var(--border-color); padding: 0.25rem 0.5rem; border-radius: 6px; cursor: pointer; color: var(--text-main);">
                            <input type="checkbox" class="edit-dietary-checkbox" name="dietary_tags[]" value="{{ $dtag }}"> {{ str_replace('_', ' ', strtoupper($dtag)) }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.5rem;">EU Allergens Tagging</label>
                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                    @foreach($allergens as $allg)
                        <label style="display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.8rem; background: var(--input-bg); border: 1px solid var(--border-color); padding: 0.25rem 0.5rem; border-radius: 6px; cursor: pointer; color: var(--text-main);">
                            <input type="checkbox" class="edit-allergen-checkbox" name="allergens[]" value="{{ $allg->id }}"> {{ $allg->icon }} {{ $allg->name }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 0.75rem; margin-bottom: 1.5rem;">
                <div>
                    <label style="font-size: 0.75rem; color: var(--text-muted);">Calories (kcal)</label>
                    <input type="number" id="edit_prod_calories" name="calories" placeholder="450" style="width: 100%; padding: 0.5rem 0.75rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 8px; color: var(--text-main); font-size: 0.85rem; outline: none;">
                </div>
                <div>
                    <label style="font-size: 0.75rem; color: var(--text-muted);">Prep Mins</label>
                    <input type="number" id="edit_prod_preparation_time_min" name="preparation_time_min" placeholder="15" style="width: 100%; padding: 0.5rem 0.75rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 8px; color: var(--text-main); font-size: 0.85rem; outline: none;">
                </div>
                <div style="display: flex; align-items: flex-end;">
                    <label style="font-size: 0.8rem; display: flex; align-items: center; gap: 0.5rem; cursor: pointer; color: var(--text-main);">
                        <input type="checkbox" id="edit_prod_is_featured" name="is_featured" value="1"> ★ Featured
                    </label>
                </div>
                <div style="display: flex; align-items: flex-end;">
                    <label style="font-size: 0.8rem; display: flex; align-items: center; gap: 0.5rem; cursor: pointer; color: #10b981; font-weight: 700;">
                        <input type="checkbox" id="edit_prod_is_available" name="is_available" value="1"> In Stock
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
        
        document.getElementById('edit_prod_image').value = prod.image || '';
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
        document.getElementById('edit_prod_is_available').checked = !!prod.is_available;
        
        document.getElementById('editProductModal').style.display = 'flex';
    }
</script>
@endsection
