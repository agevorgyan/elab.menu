@extends('layouts.app')

@section('title', __('AI Մենյուի Գործիքակազմ') . ' - ' . $vendor->name)

@section('styles')
<style>
    .ai-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 20px;
        padding: clamp(1.25rem, 3vw, 1.85rem);
        position: relative;
        overflow: hidden;
        box-shadow: var(--shadow-card);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 560px;
        box-sizing: border-box;
    }

    .ai-card-accent-orange {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #f59e0b, #ec4899);
    }

    .ai-card-accent-blue {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #3b82f6, #8b5cf6);
    }

    /* Segmented Tabs Strip */
    .ai-tabs-strip {
        display: flex;
        align-items: center;
        background: var(--input-bg);
        border: 1px solid var(--border-color);
        border-radius: 14px;
        padding: 5px;
        gap: 6px;
        margin-bottom: 1.5rem;
        width: 100%;
        box-sizing: border-box;
    }

    .ai-tab-btn {
        flex: 1 1 0%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.65rem 0.5rem;
        border-radius: 10px;
        border: 1px solid transparent;
        background: transparent;
        color: var(--text-muted);
        font-family: 'Inter', -apple-system, sans-serif;
        font-size: 0.85rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        white-space: nowrap;
        outline: none;
        box-sizing: border-box;
    }

    .ai-tab-btn:hover {
        color: var(--text-main);
        background: rgba(148, 163, 184, 0.08);
    }

    .ai-tab-btn.active {
        background: var(--bg-card);
        color: var(--text-main);
        border-color: var(--border-color);
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.08), 0 1px 3px rgba(0, 0, 0, 0.04);
    }

    /* Modern Dropzone */
    .ai-dropzone {
        border: 2px dashed var(--border-color);
        border-radius: 16px;
        padding: 1.75rem 1.25rem;
        text-align: center;
        background: var(--bg-body);
        transition: all 0.25s ease;
        position: relative;
        cursor: pointer;
    }

    .ai-dropzone:hover {
        border-color: var(--primary);
        background: rgba(245, 158, 11, 0.03);
    }

    .ai-dropzone input[type="file"] {
        position: absolute;
        inset: 0;
        opacity: 0;
        width: 100%;
        height: 100%;
        cursor: pointer;
        z-index: 10;
    }

    /* Format Badge Chips */
    .format-chips-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
        margin-top: 0.85rem;
    }

    .format-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.25rem 0.55rem;
        border-radius: 8px;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        line-height: 1.2;
    }

    /* Input Field Styling */
    .ai-input-wrap {
        position: relative;
        width: 100%;
    }

    .ai-input-wrap input {
        width: 100%;
        box-sizing: border-box;
        padding: 0.85rem 1rem 0.85rem 2.85rem;
        background: var(--bg-body);
        border: 1px solid var(--border-color);
        border-radius: 14px;
        color: var(--text-main);
        font-size: 0.9rem;
        font-family: 'Inter', sans-serif;
        outline: none;
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    .ai-input-wrap input:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px var(--primary-glow);
    }

    .ai-input-wrap .input-prefix-icon {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-muted);
        font-size: 1rem;
        pointer-events: none;
    }

    .ai-textarea {
        width: 100%;
        box-sizing: border-box;
        padding: 0.95rem 1.15rem;
        background: var(--bg-body);
        border: 1px solid var(--border-color);
        border-radius: 14px;
        color: var(--text-main);
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 0.85rem;
        outline: none;
        resize: vertical;
        line-height: 1.55;
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    .ai-textarea:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px var(--primary-glow);
    }

    .ai-info-banner {
        padding: 0.85rem 1rem;
        border-radius: 14px;
        font-size: 0.8rem;
        line-height: 1.5;
        display: flex;
        align-items: flex-start;
        gap: 0.6rem;
    }
</style>
@endsection

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

<div class="grid-2" style="gap: 1.75rem;" x-data="{ tab: 'file', isProcessing: false, fileName: '' }">
    <!-- Tool A: AI Multi-Format & URL Menu Extractor -->
    <div class="ai-card">
        <div class="ai-card-accent-orange"></div>

        <!-- Processing Loading Overlay -->
        <div x-show="isProcessing" x-transition style="position: absolute; inset: 0; background: var(--modal-overlay); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); z-index: 50; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 2rem;">
            <div style="width: 60px; height: 60px; border-radius: 18px; background: var(--primary-gradient); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1.8rem; box-shadow: 0 10px 25px var(--primary-glow); margin-bottom: 1.25rem;">
                <i class="fa-solid fa-wand-magic-sparkles fa-spin"></i>
            </div>
            <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.25rem; font-weight: 800; color: var(--text-main); margin-bottom: 0.4rem;">
                {{ __('AI-ն արտածում է մենյուն...') }}
            </h3>
            <p style="font-size: 0.85rem; color: var(--text-muted); max-width: 320px; margin: 0; line-height: 1.5;">
                {{ __('Կառուցվածքավորվում են ուտեստները, գները, նկարագրություններն ու բաժինները: Խնդրում ենք սպասել:') }}
            </p>
        </div>

        <div>
            <div style="display: flex; align-items: center; gap: 0.85rem; margin-bottom: 0.75rem;">
                <span style="width: 44px; height: 44px; border-radius: 14px; background: rgba(245, 158, 11, 0.15); color: var(--primary); display: inline-flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0;">
                    <i class="fa-solid fa-brain"></i>
                </span>
                <div>
                    <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.25rem; font-weight: 800; color: var(--text-main); margin: 0;">
                        1. {{ __('AI Խելացի Ներմուծում (Omni-Extractor)') }}
                    </h3>
                    <div style="display: flex; align-items: center; gap: 0.4rem; margin-top: 0.2rem;">
                        <span class="badge badge-amber" style="font-size: 0.68rem;">Excel, Word, CSV, Images, PDF & Web URL</span>
                    </div>
                </div>
            </div>

            <p style="font-size: 0.86rem; color: var(--text-muted); margin: 0 0 1.35rem 0; line-height: 1.5; word-break: break-word;">
                {{ __('Ներբեռնեք ցանկացած ֆորմատի ֆայլ, կցեք ռեստորանի կայքի հղումը կամ տեղադրեք տեքստը: AI-ն ավտոմատ կստեղծի կատեգորիաները, ուտեստները, գներն ու նկարագրությունները:') }}
            </p>

            <!-- Segmented Control Tabs -->
            <div class="ai-tabs-strip">
                <button type="button" @click="tab = 'file'" class="ai-tab-btn" :class="{ 'active': tab === 'file' }">
                    <i class="fa-solid fa-file-arrow-up" style="color: #10b981;"></i>
                    <span>{{ __('Ֆայլեր') }}</span>
                </button>
                <button type="button" @click="tab = 'url'" class="ai-tab-btn" :class="{ 'active': tab === 'url' }">
                    <i class="fa-solid fa-globe" style="color: #3b82f6;"></i>
                    <span>{{ __('Կայքի Հղում') }}</span>
                </button>
                <button type="button" @click="tab = 'text'" class="ai-tab-btn" :class="{ 'active': tab === 'text' }">
                    <i class="fa-solid fa-align-left" style="color: #f59e0b;"></i>
                    <span>{{ __('Տեքստ') }}</span>
                </button>
            </div>

            <form action="{{ route('admin.ai.import.process') }}" method="POST" enctype="multipart/form-data" @submit="isProcessing = true">
                @csrf
                <input type="hidden" name="import_source" :value="tab">

                <!-- Tab 1: Multi-format Files -->
                <div x-show="tab === 'file'" style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem;">
                        <i class="fa-solid fa-cloud-arrow-up" style="color: var(--primary); margin-right: 0.35rem;"></i>
                        {{ __('Վերբեռնեք մենյուի ֆայլը') }}
                    </label>

                    <div class="ai-dropzone">
                        <input type="file" name="menu_file" id="menuFileInput" accept=".xlsx,.xls,.csv,.docx,.doc,.pdf,.jpg,.jpeg,.png,.webp,.txt,.json" @change="fileName = $event.target.files[0]?.name || ''">
                        <div style="pointer-events: none;">
                            <div style="width: 50px; height: 50px; border-radius: 50%; background: var(--input-bg); border: 1px solid var(--border-color); display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: var(--primary); margin: 0 auto 0.75rem;">
                                <i class="fa-solid fa-file-circle-plus"></i>
                            </div>
                            <div style="font-size: 0.92rem; font-weight: 800; color: var(--text-main);" x-text="fileName ? 'Ընտրված ֆայլ՝ ' + fileName : '{{ __('Սեղմեք կամ քաշեք ֆայլն այստեղ') }}'"></div>
                            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.35rem;">
                                {{ __('Առավելագույն չափսը՝ 20 MB: Բոլոր ֆորմատներն ապահովվում են:') }}
                            </div>
                        </div>
                    </div>

                    <!-- Supported Format Badges -->
                    <div class="format-chips-grid">
                        <span class="format-chip badge-emerald">
                            <i class="fa-solid fa-file-excel"></i> Excel (.xlsx, .xls)
                        </span>
                        <span class="format-chip badge-cyan">
                            <i class="fa-solid fa-file-csv"></i> CSV (.csv)
                        </span>
                        <span class="format-chip badge-indigo">
                            <i class="fa-solid fa-file-word"></i> Word (.docx, .doc)
                        </span>
                        <span class="format-chip badge-purple">
                            <i class="fa-solid fa-image"></i> Images (.jpg, .png, .webp)
                        </span>
                        <span class="format-chip badge-rose">
                            <i class="fa-solid fa-file-pdf"></i> PDF (.pdf)
                        </span>
                        <span class="format-chip badge-amber">
                            <i class="fa-solid fa-file-lines"></i> TXT / JSON
                        </span>
                    </div>
                </div>

                <!-- Tab 2: Website URL Scraping -->
                <div x-show="tab === 'url'" style="margin-bottom: 1.5rem;" x-cloak>
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem;">
                        <i class="fa-solid fa-link" style="color: #3b82f6; margin-right: 0.35rem;"></i>
                        {{ __('Ռեստորանի կամ Մենյուի Կայքի Հղում (Website URL)') }}
                    </label>

                    <div class="ai-input-wrap">
                        <i class="fa-solid fa-globe input-prefix-icon" style="color: #3b82f6;"></i>
                        <input type="url" name="website_url" placeholder="https://example-restaurant.com/menu">
                    </div>

                    <div class="ai-info-banner" style="background: rgba(59, 130, 246, 0.08); border: 1px dashed rgba(59, 130, 246, 0.25); color: var(--text-muted); margin-top: 0.85rem;">
                        <i class="fa-solid fa-wand-magic-sparkles" style="color: #3b82f6; margin-top: 0.15rem; flex-shrink: 0;"></i>
                        <span>{{ __('Տեղադրեք ռեստորանի պաշտոնական կայքի, առցանց մենյուի կամ առաքման էջի հղումը: AI-ն կվերլուծի կայքի կառուցվածքը և կառանձնացնի ուտեստները:') }}</span>
                    </div>
                </div>

                <!-- Tab 3: Direct Text -->
                <div x-show="tab === 'text'" style="margin-bottom: 1.5rem;" x-cloak>
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem;">
                        <i class="fa-solid fa-paste" style="color: var(--primary); margin-right: 0.35rem;"></i>
                        {{ __('Տեղադրեք Մենյուի Տեքստը կամ Գնացուցակը') }}
                    </label>
                    <textarea name="menu_text" rows="7" class="ai-textarea" placeholder="ՆԱԽՈՒՏԵՍՏՆԵՐ&#10;Հումուս տրյուֆելով 3500 AMD&#10;Տապակած կալամարի 4500 AMD&#10;&#10;ՏԱՔ ՈՒՏԵՍՏՆԵՐ&#10;Ռիբայ Սթեյք 12500 AMD"></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; box-sizing: border-box; justify-content: center; border-radius: 12px; font-weight: 800; padding: 0.85rem 1.5rem; font-size: 0.95rem; box-shadow: 0 4px 14px var(--primary-glow); gap: 0.5rem;">
                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                    <span>{{ __('Արտածել & Ստուգել Մենյուն') }}</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Tool B: AI One-Click Translation -->
    <div class="ai-card">
        <div class="ai-card-accent-blue"></div>

        <div>
            <div style="display: flex; align-items: center; gap: 0.85rem; margin-bottom: 0.75rem;">
                <span style="width: 44px; height: 44px; border-radius: 14px; background: rgba(59, 130, 246, 0.15); color: #3b82f6; display: inline-flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0;">
                    <i class="fa-solid fa-language"></i>
                </span>
                <div>
                    <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.25rem; font-weight: 800; color: var(--text-main); margin: 0;">
                        2. {{ __('AI 1-Սեղմումով Թարգմանություն') }}
                    </h3>
                    <div style="display: flex; align-items: center; gap: 0.4rem; margin-top: 0.2rem;">
                        <span class="badge badge-indigo" style="font-size: 0.68rem;">Multilingual Deep Translation</span>
                    </div>
                </div>
            </div>

            <p style="font-size: 0.86rem; color: var(--text-muted); margin: 0 0 1.35rem 0; line-height: 1.5; word-break: break-word;">
                {{ __('Ակնթարթորեն թարգմանեք Ձեր առկա ուտեստների անվանումները, նկարագրությունները և կատեգորիաները ընտրված լեզվով, որպեսզի օտարերկրյա հյուրերը վստահորեն պատվիրեն:') }}
            </p>

            <form action="{{ route('admin.ai.translate') }}" method="POST">
                @csrf
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem;">
                        {{ __('Ընտրեք Թիրախային Լեզուն') }}
                    </label>
                    <select name="target_language" required style="width: 100%; box-sizing: border-box; padding: 0.85rem 1rem; background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 14px; color: var(--text-main); font-size: 0.92rem; font-weight: 700; outline: none; cursor: pointer;">
                        <option value="hy">🇦🇲 Հայերեն (Armenian)</option>
                        <option value="en">🇬🇧 English</option>
                        <option value="ru">🇷🇺 Русский (Russian)</option>
                        <option value="fr">🇫🇷 Français (French)</option>
                        <option value="de">🇩🇪 Deutsch (German)</option>
                        <option value="es">🇪🇸 Español (Spanish)</option>
                    </select>
                </div>

                <div class="ai-info-banner" style="background: rgba(59, 130, 246, 0.08); border: 1px dashed rgba(59, 130, 246, 0.25); color: var(--text-muted); margin-bottom: 1.5rem;">
                    <i class="fa-solid fa-circle-info" style="color: #3b82f6; margin-top: 0.15rem; flex-shrink: 0;"></i>
                    <span>{{ __('Թարգմանության ավարտից հետո բոլոր ուտեստները կստանան թարգմանված տարբերակները մենյուի համապատասխան լեզվի համար:') }}</span>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; box-sizing: border-box; justify-content: center; background: linear-gradient(135deg, #3b82f6, #2563eb); border-color: #2563eb; color: #fff; border-radius: 12px; font-weight: 800; padding: 0.85rem 1.5rem; font-size: 0.95rem; box-shadow: 0 4px 14px rgba(59, 130, 246, 0.35); gap: 0.5rem;">
                    <i class="fa-solid fa-language"></i>
                    <span>{{ __('Թարգմանել Ամբողջ Մենյուն Հիմա') }}</span>
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
