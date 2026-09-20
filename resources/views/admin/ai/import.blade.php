@extends('layouts.app')

@section('title', __('AI Մենյուի Գործիքակազմ') . ' - ' . $vendor->name)

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
    <div style="min-width: 0; flex: 1;">
        <h1 style="font-family: 'Outfit', sans-serif; font-size: clamp(1.4rem, 2.5vw, 1.85rem); font-weight: 800; color: var(--text-main); margin: 0; display: flex; align-items: center; gap: 0.75rem;">
            <span style="background: rgba(139, 92, 246, 0.15); color: #8b5cf6; width: 44px; height: 44px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0;">
                <i class="fa-solid fa-wand-magic-sparkles"></i>
            </span>
            <span>{{ __('AI Մենյուի Գործիքակազմ') }}</span>
        </h1>
        <p style="color: var(--text-muted); font-size: 0.9rem; margin: 0.35rem 0 0; word-break: break-word;">
            {{ __('Արտածեք մենյուն PDF/Word ֆայլերից կամ ակնթարթորեն թարգմանեք ամբողջ մենյուն մեկ սեղմումով') }}
        </p>
    </div>
</div>

<div class="grid-2" style="gap: 1.75rem;">
    <!-- Tool A: AI Document / Text Menu Import -->
    <div class="card" style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 20px; padding: clamp(1.25rem, 3vw, 1.85rem); position: relative; overflow: hidden; box-shadow: var(--shadow-card); display: flex; flex-direction: column; justify-content: space-between;">
        <div style="position: absolute; top: 0; left: 0; right: 0; height: 4px; background: linear-gradient(90deg, var(--primary), #ea580c);"></div>

        <div>
            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
                <span style="width: 36px; height: 36px; border-radius: 10px; background: rgba(245, 158, 11, 0.15); color: var(--primary); display: inline-flex; align-items: center; justify-content: center; font-size: 1rem;">
                    <i class="fa-solid fa-file-import"></i>
                </span>
                <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.25rem; font-weight: 800; color: var(--text-main); margin: 0;">
                    1. {{ __('AI Մենյուի Արտածում (Extractor)') }}
                </h3>
            </div>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0 0 1.5rem 0; line-height: 1.5; word-break: break-word;">
                {{ __('Վերբեռնեք գոյություն ունեցող մենյուի ֆայլը կամ տեղադրեք տեքստը: AI-ն ինքնուրույն կխմբավորի ուտեստները ըստ բաժինների, կառանձնացնի գները, նկարագրությունները և դիետիկ նշումները:') }}
            </p>

            <form action="{{ route('admin.ai.import.process') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div style="margin-bottom: 1.25rem;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.4rem;">
                        <i class="fa-solid fa-cloud-arrow-up" style="color: var(--primary); margin-right: 0.35rem;"></i>
                        {{ __('Վերբեռնել Ֆայլ (PDF, DOCX, TXT)') }}
                    </label>
                    <input type="file" name="menu_file" accept=".pdf,.doc,.docx,.txt" style="width: 100%; box-sizing: border-box; padding: 0.75rem 1rem; background: var(--bg-body); border: 2px dashed var(--border-color); border-radius: 14px; color: var(--text-main); font-size: 0.88rem; outline: none; cursor: pointer;">
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.4rem;">
                        <i class="fa-solid fa-paste" style="color: var(--primary); margin-right: 0.35rem;"></i>
                        {{ __('ԿԱՄ Տեղադրեք Մենյուի Տեքստը') }}
                    </label>
                    <textarea name="menu_text" rows="6" placeholder="ՆԱԽՈՒՏԵՍՏՆԵՐ&#10;Հումուս տրյուֆելով 3500 AMD&#10;Տապակած կալամարի 4500 AMD&#10;&#10;ՏԱՔ ՈՒՏԵՍՏՆԵՐ&#10;Ռիբայ Սթեյք 12500 AMD" style="width: 100%; box-sizing: border-box; padding: 0.85rem 1rem; background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 14px; color: var(--text-main); font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 0.85rem; outline: none; resize: vertical; line-height: 1.5;"></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; box-sizing: border-box; justify-content: center; border-radius: 12px; font-weight: 700; padding: 0.8rem 1.5rem; font-size: 0.95rem; box-shadow: 0 4px 14px rgba(245, 158, 11, 0.35);">
                    <i class="fa-solid fa-brain"></i> {{ __('Արտածել & Ստուգել Մենյուն') }}
                </button>
            </form>
        </div>
    </div>

    <!-- Tool B: AI One-Click Translation -->
    <div class="card" style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 20px; padding: clamp(1.25rem, 3vw, 1.85rem); position: relative; overflow: hidden; box-shadow: var(--shadow-card); display: flex; flex-direction: column; justify-content: space-between;">
        <div style="position: absolute; top: 0; left: 0; right: 0; height: 4px; background: linear-gradient(90deg, #3b82f6, #8b5cf6);"></div>

        <div>
            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
                <span style="width: 36px; height: 36px; border-radius: 10px; background: rgba(59, 130, 246, 0.15); color: #3b82f6; display: inline-flex; align-items: center; justify-content: center; font-size: 1rem;">
                    <i class="fa-solid fa-language"></i>
                </span>
                <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.25rem; font-weight: 800; color: var(--text-main); margin: 0;">
                    2. {{ __('AI 1-Սեղմումով Թարգմանություն') }}
                </h3>
            </div>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0 0 1.5rem 0; line-height: 1.5; word-break: break-word;">
                {{ __('Ակնթարթորեն թարգմանեք Ձեր առկա ուտեստների անվանումները, նկարագրությունները և կատեգորիաները ընտրված լեզվով, որպեսզի օտարերկրյա հյուրերը վստահորեն պատվիրեն:') }}
            </p>

            <form action="{{ route('admin.ai.translate') }}" method="POST">
                @csrf
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.4rem;">
                        {{ __('Ընտրեք Թիրախային Լեզուն') }}
                    </label>
                    <select name="target_language" required style="width: 100%; box-sizing: border-box; padding: 0.8rem 1rem; background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 12px; color: var(--text-main); font-size: 0.95rem; font-weight: 600; outline: none;">
                        <option value="hy">🇦🇲 Հայերեն (Armenian)</option>
                        <option value="en">🇬🇧 English</option>
                        <option value="ru">🇷🇺 Русский (Russian)</option>
                        <option value="fr">🇫🇷 Français (French)</option>
                        <option value="de">🇩🇪 Deutsch (German)</option>
                        <option value="es">🇪🇸 Español (Spanish)</option>
                    </select>
                </div>

                <div style="background: rgba(59, 130, 246, 0.08); border: 1px dashed rgba(59, 130, 246, 0.3); border-radius: 14px; padding: 1rem; margin-bottom: 1.5rem; font-size: 0.82rem; color: var(--text-muted); line-height: 1.5;">
                    <i class="fa-solid fa-circle-info" style="color: #3b82f6; margin-right: 0.35rem;"></i>
                    {{ __('Թարգմանության ավարտից հետո բոլոր ուտեստները կստանան թարգմանված տարբերակները մենյուի համապատասխան լեզվի համար:') }}
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; box-sizing: border-box; justify-content: center; background: linear-gradient(135deg, #3b82f6, #2563eb); border-color: #2563eb; color: #fff; border-radius: 12px; font-weight: 700; padding: 0.8rem 1.5rem; font-size: 0.95rem; box-shadow: 0 4px 14px rgba(59, 130, 246, 0.35);">
                    <i class="fa-solid fa-language"></i> {{ __('Թարգմանել Ամբողջ Մենյուն Հիմա') }}
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
