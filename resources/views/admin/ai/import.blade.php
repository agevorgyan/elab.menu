@extends('layouts.app')

@section('title', 'AI Menu Import & Translation - ' . $vendor->name)

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h1 style="font-family: 'Outfit', sans-serif; font-size: 1.75rem; font-weight: 700;">
            <i class="fa-solid fa-wand-magic-sparkles" style="color: var(--primary);"></i> AI Menu Suite
        </h1>
        <p style="color: var(--text-muted); font-size: 0.9rem;">Extract digital menus from PDF/Word documents or translate your whole menu in 1-click.</p>
    </div>
</div>

<div class="grid-2">
    <!-- Tool A: AI Document / Text Menu Import -->
    <div class="card" style="border-top: 4px solid var(--primary);">
        <h3 style="font-family: 'Outfit'; font-size: 1.25rem; margin-bottom: 0.5rem;">
            1. AI Menu Extractor (From PDF / Word / Text)
        </h3>
        <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.25rem;">
            Upload your existing menu file or paste menu text below. AI will automatically group items into categories, extract dish names, descriptions, prices, and dietary tags.
        </p>

        <form action="{{ route('admin.ai.import.process') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Upload Menu File (PDF, DOCX, TXT)</label>
                <input type="file" name="menu_file" accept=".pdf,.doc,.docx,.txt" style="width: 100%; padding: 0.65rem 0.9rem; background: var(--input-bg); border: 1px dashed var(--border-color); border-radius: 10px; color: var(--text-main); font-size: 0.9rem; outline: none;">
            </div>

            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">OR Paste Raw Menu Text</label>
                <textarea name="menu_text" rows="6" placeholder="STARTERS&#10;Truffle Hummus 3500 AMD&#10;Crispy Calamari 4500 AMD&#10;&#10;MAINS&#10;Ribeye Steak 12500 AMD" style="width: 100%; padding: 0.75rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-main); font-family: monospace; font-size: 0.85rem; outline: none;"></textarea>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">
                <i class="fa-solid fa-brain"></i> Extract & Review Menu
            </button>
        </form>
    </div>

    <!-- Tool B: AI One-Click Translation -->
    <div class="card" style="border-top: 4px solid #3b82f6;">
        <h3 style="font-family: 'Outfit'; font-size: 1.25rem; margin-bottom: 0.5rem;">
            2. AI One-Click Multilingual Translation
        </h3>
        <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.25rem;">
            Instantly translate your existing dishes, descriptions, and category titles into target languages so your international guests order with total confidence.
        </p>

        <form action="{{ route('admin.ai.translate') }}" method="POST">
            @csrf
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Select Target Language</label>
                <select name="target_language" required style="width: 100%; padding: 0.75rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-main); font-size: 0.95rem; outline: none;">
                    <option value="hy">🇦🇲 Armenian (Հայերեն)</option>
                    <option value="en">🇬🇧 English</option>
                    <option value="ru">🇷🇺 Russian (Русский)</option>
                    <option value="fr">🇫🇷 French (Français)</option>
                    <option value="de">🇩🇪 German (Deutsch)</option>
                    <option value="es">🇪🇸 Spanish (Español)</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; background: #3b82f6; color: #fff;">
                <i class="fa-solid fa-language"></i> Translate Entire Menu Now
            </button>
        </form>
    </div>
</div>
@endsection
