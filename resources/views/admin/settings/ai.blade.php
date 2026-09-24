@extends('layouts.app')

@section('title', __('AI Կարգավորումներ & AI Մատուցող') . ' - ' . $vendor->name)

@section('content')
<div style="max-width: 1050px; margin: 0 auto; width: 100%; box-sizing: border-box;">
    <!-- Page Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <div style="min-width: 0; flex: 1;">
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; font-size: 0.85rem;">
                <a href="{{ route('admin.settings.index') }}" style="color: var(--text-muted); text-decoration: none; display: flex; align-items: center; gap: 0.35rem;">
                    <i class="fa-solid fa-arrow-left"></i> {{ __('Կարգավորումներ') }}
                </a>
                <span style="color: var(--text-muted);">/</span>
                <span style="color: var(--primary); font-weight: 700;">AI Control Center</span>
            </div>

            <h1 style="font-family: 'Outfit', sans-serif; font-size: clamp(1.4rem, 2.5vw, 1.85rem); font-weight: 800; margin: 0; color: var(--text-main); display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                <span style="background: linear-gradient(135deg, rgba(139, 92, 246, 0.2), rgba(236, 72, 153, 0.2)); color: #8b5cf6; width: 44px; height: 44px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0; box-shadow: 0 4px 12px rgba(139, 92, 246, 0.25);">
                    <i class="fa-solid fa-robot"></i>
                </span>
                <span>{{ __('AI Կարգավորումներ & Մոդելներ') }}</span>
                <span style="font-size: 0.72rem; font-weight: 700; background: linear-gradient(135deg, #8b5cf6, #ec4899); color: #fff; padding: 0.2rem 0.6rem; border-radius: 999px; letter-spacing: 0.04em;">MULTI-PROVIDER</span>
            </h1>
            <p style="color: var(--text-muted); font-size: 0.9rem; margin: 0.35rem 0 0 0; word-break: break-word;">
                {{ __('Ընտրեք AI պրովայդերին (Gemini, OpenAI, Claude, DeepSeek, Groq և այլն), նշեք Ձեր API բանալին և կարգավորեք AI Մատուցող խորհրդատուին') }}
            </p>
        </div>

        <div style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
            <a href="{{ route('admin.settings.index') }}" class="btn btn-secondary" style="display: flex; align-items: center; gap: 0.5rem; text-decoration: none; border-radius: 12px; font-weight: 600; font-size: 0.88rem;">
                <i class="fa-solid fa-sliders"></i> {{ __('Ընդհանուր կարգավորումներ') }}
            </a>
            <button type="submit" form="aiSettingsForm" class="btn btn-primary" style="display: flex; align-items: center; gap: 0.5rem; border-radius: 12px; font-weight: 700; padding: 0.65rem 1.4rem; font-size: 0.92rem; box-shadow: 0 4px 14px rgba(139, 92, 246, 0.35); background: linear-gradient(135deg, #8b5cf6, #7c3aed); border: none; color: #fff;">
                <i class="fa-solid fa-floppy-disk"></i> {{ __('Պահպանել Կարգավորումները') }}
            </button>
        </div>
    </div>

    @if (session('success'))
        <div style="background: rgba(16, 185, 129, 0.12); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 14px; padding: 1rem 1.25rem; margin-bottom: 1.5rem; color: #10b981; display: flex; align-items: center; gap: 0.65rem;">
            <i class="fa-solid fa-circle-check" style="font-size: 1.2rem;"></i>
            <span style="font-weight: 600; font-size: 0.92rem;">{{ session('success') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div style="background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 14px; padding: 1rem 1.25rem; margin-bottom: 1.5rem; color: #ef4444;">
            <div style="font-weight: 700; display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                <i class="fa-solid fa-circle-exclamation"></i> {{ __('Ուշադրություն. Լրացված տվյալներում առկա են սխալներ') }}
            </div>
            <ul style="margin: 0; padding-left: 1.25rem; font-size: 0.88rem;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $activeProviderKey = old('ai_provider', $vendor->getAiProvider());
        $activeModel = old('ai_model', $vendor->getAiModel());
        $savedApiKey = $vendor->getAiApiKey();
        $hasCustomKey = !empty($savedApiKey);
        $savedBaseUrl = $vendor->getAiBaseUrl();
    @endphp

    <!-- Top Status Banner -->
    <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 18px; padding: 1.2rem 1.5rem; margin-bottom: 1.75rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; box-shadow: var(--shadow-card);">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <div style="width: 48px; height: 48px; border-radius: 14px; background: rgba(139, 92, 246, 0.12); border: 1px solid rgba(139, 92, 246, 0.25); display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: #8b5cf6;">
                <i class="fa-solid fa-microchip"></i>
            </div>
            <div>
                <div style="font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">
                    {{ __('Ընթացիկ Ակտիվ AI Շարժիչ') }}
                </div>
                <div style="font-size: 1.1rem; font-weight: 800; color: var(--text-main); font-family: 'Outfit', sans-serif; display: flex; align-items: center; gap: 0.5rem; margin-top: 0.15rem;">
                    <span id="headerProviderName">{{ $providers[$activeProviderKey]['name'] ?? 'Google Gemini' }}</span>
                    <span style="font-size: 0.82rem; font-weight: 600; color: #8b5cf6; background: rgba(139, 92, 246, 0.1); padding: 0.15rem 0.5rem; border-radius: 6px;" id="headerModelName">
                        {{ $activeModel }}
                    </span>
                </div>
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
            @if($hasCustomKey)
                <span style="font-size: 0.8rem; font-weight: 700; background: rgba(16, 185, 129, 0.12); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 8px; padding: 0.35rem 0.75rem; display: inline-flex; align-items: center; gap: 0.4rem;">
                    <i class="fa-solid fa-key"></i> {{ __('Գործընկերոջ Սեփական API Key') }}
                </span>
            @else
                <span style="font-size: 0.8rem; font-weight: 700; background: rgba(59, 130, 246, 0.12); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 8px; padding: 0.35rem 0.75rem; display: inline-flex; align-items: center; gap: 0.4rem;">
                    <i class="fa-solid fa-cloud"></i> {{ __('Համակարգային Լռելյայն Ռեժիմ') }}
                </span>
            @endif

            @if($vendor->ai_waiter_enabled)
                <span style="font-size: 0.8rem; font-weight: 700; background: rgba(139, 92, 246, 0.12); color: #8b5cf6; border: 1px solid rgba(139, 92, 246, 0.3); border-radius: 8px; padding: 0.35rem 0.75rem; display: inline-flex; align-items: center; gap: 0.4rem;">
                    <i class="fa-solid fa-wand-magic-sparkles"></i> {{ __('AI Մատուցող՝ Ակտիվ') }}
                </span>
            @else
                <span style="font-size: 0.8rem; font-weight: 700; background: rgba(100, 116, 139, 0.12); color: #64748b; border: 1px solid rgba(100, 116, 139, 0.3); border-radius: 8px; padding: 0.35rem 0.75rem; display: inline-flex; align-items: center; gap: 0.4rem;">
                    <i class="fa-solid fa-pause"></i> {{ __('AI Մատուցող՝ Անջատված') }}
                </span>
            @endif
        </div>
    </div>

    <form action="{{ route('admin.settings.ai.update') }}" method="POST" id="aiSettingsForm">
        @csrf

        <div style="display: grid; grid-template-columns: 1fr; gap: 1.75rem;">

            <!-- SECTION 1: AI SERVICE PROVIDERS & INTEGRATION -->
            <div class="card settings-card" style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 20px; padding: clamp(1.2rem, 3vw, 1.85rem); box-shadow: var(--shadow-card); position: relative;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
                    <div style="display: flex; align-items: center; gap: 0.9rem;">
                        <span style="width: 44px; height: 44px; border-radius: 12px; background: rgba(139, 92, 246, 0.15); color: #8b5cf6; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; flex-shrink: 0;">
                            <i class="fa-solid fa-brain"></i>
                        </span>
                        <div>
                            <h3 style="margin: 0; font-size: 1.25rem; font-weight: 800; color: var(--text-main); font-family: 'Outfit', sans-serif;">
                                {{ __('1. AI Համակարգի Ընտրություն (AI Providers)') }}
                            </h3>
                            <p style="margin: 0.2rem 0 0; font-size: 0.85rem; color: var(--text-muted); word-break: break-word;">
                                {{ __('Ընտրեք այն AI ծառայությունը, որը պետք է աշխատեցնի AI Մատուցողը, Մենյուի Իմպորտը և Թարգմանությունը') }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Provider Selector Cards Grid -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 280px), 1fr)); gap: 1rem; margin-bottom: 1.75rem;">
                    @foreach($providers as $pKey => $pConfig)
                        @php
                            $isSelected = ($activeProviderKey === $pKey);
                            $brandColor = match($pKey) {
                                'gemini' => '#4285F4',
                                'openai' => '#10a37f',
                                'claude' => '#d97706',
                                'deepseek' => '#2563eb',
                                'groq' => '#ea580c',
                                'openrouter' => '#7c3aed',
                                'custom' => '#475569',
                                default => '#8b5cf6'
                            };
                            $iconClass = match($pKey) {
                                'gemini' => 'fa-brands fa-google',
                                'openai' => 'fa-solid fa-cube',
                                'claude' => 'fa-solid fa-shield-cat',
                                'deepseek' => 'fa-solid fa-compass',
                                'groq' => 'fa-solid fa-bolt',
                                'openrouter' => 'fa-solid fa-route',
                                'custom' => 'fa-solid fa-server',
                                default => 'fa-solid fa-microchip'
                            };
                        @endphp
                        <label class="provider-card" style="border: 2px solid {{ $isSelected ? $brandColor : 'var(--border-color)' }}; background: {{ $isSelected ? 'rgba('.hexdec(substr($brandColor, 1, 2)).', '.hexdec(substr($brandColor, 3, 2)).', '.hexdec(substr($brandColor, 5, 2)).', 0.06)' : 'var(--bg-body)' }}; border-radius: 16px; padding: 1.15rem; cursor: pointer; transition: all 0.2s ease; position: relative; display: flex; flex-direction: column; justify-content: space-between; gap: 0.75rem;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 0.5rem;">
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <span style="width: 38px; height: 38px; border-radius: 10px; background: {{ $brandColor }}1a; color: {{ $brandColor }}; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;">
                                        <i class="{{ $iconClass }}"></i>
                                    </span>
                                    <div>
                                        <div style="font-weight: 800; font-size: 1rem; color: var(--text-main);">
                                            {{ $pConfig['name'] }}
                                        </div>
                                        <span style="font-size: 0.72rem; font-weight: 700; color: {{ $brandColor }}; background: {{ $brandColor }}15; padding: 0.15rem 0.45rem; border-radius: 5px; display: inline-block; margin-top: 0.15rem;">
                                            {{ $pConfig['badge'] ?? strtoupper($pKey) }}
                                        </span>
                                    </div>
                                </div>
                                <input type="radio" name="ai_provider" value="{{ $pKey }}" {{ $isSelected ? 'checked' : '' }} class="provider-radio" style="accent-color: {{ $brandColor }}; width: 18px; height: 18px; cursor: pointer;">
                            </div>

                            <p style="margin: 0; font-size: 0.8rem; color: var(--text-muted); line-height: 1.4;">
                                {{ $pConfig['description'] }}
                            </p>

                            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.75rem; border-top: 1px dashed var(--border-color); padding-top: 0.5rem; margin-top: 0.25rem;">
                                <span style="color: var(--text-muted);">
                                    <i class="fa-solid fa-layer-group"></i> {{ count($pConfig['models']) }} {{ __('մոդելներ') }}
                                </span>
                                @if(!empty($pConfig['doc_url']))
                                    <a href="{{ $pConfig['doc_url'] }}" target="_blank" onclick="event.stopPropagation();" style="color: {{ $brandColor }}; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 0.25rem;">
                                        {{ __('Console') }} <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 0.7rem;"></i>
                                    </a>
                                @endif
                            </div>
                        </label>
                    @endforeach
                </div>

                <!-- Active Provider Detailed Fields Block -->
                <div style="background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 16px; padding: 1.4rem; margin-bottom: 1.25rem;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.75rem;">
                        <h4 style="margin: 0; font-size: 1rem; font-weight: 800; color: var(--text-main); display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fa-solid fa-sliders" style="color: #8b5cf6;"></i>
                            <span>{{ __('Մոդելի և API Ինտեգրման Պարամետրեր') }}</span>
                        </h4>

                        <!-- Live Connection Test Button -->
                        <button type="button" id="btnTestConnection" class="btn btn-secondary" style="border-radius: 10px; font-weight: 700; font-size: 0.85rem; padding: 0.5rem 1rem; display: inline-flex; align-items: center; gap: 0.4rem; border-color: rgba(139, 92, 246, 0.4); color: #8b5cf6;">
                            <i class="fa-solid fa-bolt" id="testIcon"></i>
                            <span id="testBtnText">{{ __('Ստուգել Կապը (Test)') }}</span>
                        </button>
                    </div>

                    <!-- Test Result Notification Area -->
                    <div id="testResultBox" style="display: none; border-radius: 12px; padding: 0.85rem 1rem; margin-bottom: 1.25rem; font-size: 0.88rem; font-weight: 600;"></div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 280px), 1fr)); gap: 1.25rem;">
                        <!-- Model Selection Dropdown -->
                        <div class="form-group">
                            <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem;">
                                {{ __('AI Մոդել (Model Name)') }} <span style="color: #ef4444;">*</span>
                            </label>
                            <div style="position: relative; display: flex; align-items: center;">
                                <select name="ai_model" id="aiModelSelect" class="form-control" style="width: 100%; background: var(--bg-card); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 12px; padding: 0.75rem 1rem 0.75rem 2.6rem; font-size: 0.95rem; font-weight: 600;">
                                    <!-- Populated dynamically by JavaScript -->
                                </select>
                                <span style="position: absolute; left: 1rem; color: #8b5cf6; font-size: 1rem;">
                                    <i class="fa-solid fa-microchip"></i>
                                </span>
                            </div>
                            <span style="font-size: 0.78rem; color: var(--text-muted); margin-top: 0.35rem; display: block;" id="modelHintText">
                                {{ __('Ընտրեք նախընտրած մոդելը ըստ արագության և գնի') }}
                            </span>
                        </div>

                        <!-- Custom Model Input (shown if Custom selected) -->
                        <div class="form-group" id="customModelGroup" style="display: none;">
                            <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem;">
                                {{ __('Մոդելի Ճշգրիտ Կոդը (Custom Model Identifier)') }}
                            </label>
                            <div style="position: relative; display: flex; align-items: center;">
                                <input type="text" name="custom_model" id="customModelInput" value="{{ old('custom_model', $activeModel) }}" placeholder="օրինակ՝ deepseek-chat, gpt-4o, llama3.3:70b" class="form-control" style="width: 100%; background: var(--bg-card); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 12px; padding: 0.75rem 1rem 0.75rem 2.6rem; font-size: 0.95rem; font-weight: 600;">
                                <span style="position: absolute; left: 1rem; color: #8b5cf6; font-size: 1rem;">
                                    <i class="fa-solid fa-terminal"></i>
                                </span>
                            </div>
                            <span style="font-size: 0.78rem; color: var(--text-muted); margin-top: 0.35rem; display: block;">
                                {{ __('Նշեք մոդելի ճշգրիտ անունը համաձայն տվյալ պրովայդերի փաստաթղթերի') }}
                            </span>
                        </div>

                        <!-- API Key Input -->
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                <label style="font-size: 0.85rem; font-weight: 700; color: var(--text-muted); margin: 0;">
                                    {{ __('API Բանալի (API Key / Secret Token)') }}
                                </label>
                                <span id="providerDocLinkWrapper">
                                    <a href="#" id="providerDocLink" target="_blank" style="font-size: 0.78rem; font-weight: 600; color: #8b5cf6; text-decoration: none;">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i> <span id="providerDocText">{{ __('Ստանալ API Key') }}</span>
                                    </a>
                                </span>
                            </div>

                            <div style="position: relative; display: flex; align-items: center;">
                                <input type="password" name="ai_api_key" id="aiApiKeyInput" value="{{ old('ai_api_key', $savedApiKey) }}" placeholder="{{ $hasCustomKey ? '••••••••••••••••••••••••••••••••' : 'Տեղադրեք Ձեր API բանալին (օր․՝ sk-... կամ AIzaSy...)' }}" class="form-control" style="width: 100%; background: var(--bg-card); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 12px; padding: 0.75rem 3.5rem 0.75rem 2.6rem; font-size: 0.95rem; font-weight: 600;">
                                <span style="position: absolute; left: 1rem; color: #8b5cf6; font-size: 1rem;">
                                    <i class="fa-solid fa-key"></i>
                                </span>

                                <button type="button" id="toggleApiKeyVisibility" style="position: absolute; right: 1rem; background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1rem; padding: 0.25rem;">
                                    <i class="fa-solid fa-eye" id="eyeIcon"></i>
                                </button>
                            </div>

                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 0.45rem; flex-wrap: wrap; gap: 0.5rem;">
                                <span style="font-size: 0.78rem; color: var(--text-muted);" id="apiKeyNote">
                                    @if($hasCustomKey)
                                        <span style="color: #10b981; font-weight: 700;">✓ API Key-ը պահպանված է:</span> Նոր բանալի մուտքագրելու դեպքում այն կթարմացվի:
                                    @else
                                        Եթե բանալի չնշեք Gemini-ի դեպքում, կօգտագործվի համակարգային լռելյայն բանալին:
                                    @endif
                                </span>

                                @if($hasCustomKey)
                                    <label style="display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.78rem; color: #ef4444; cursor: pointer;">
                                        <input type="checkbox" name="clear_api_key" value="1" style="accent-color: #ef4444;">
                                        <span>{{ __('Մաքրել պահպանված բանալին') }}</span>
                                    </label>
                                @endif
                            </div>
                        </div>

                        <!-- Base URL (Required for Custom/Ollama or optional for others) -->
                        <div class="form-group" id="baseUrlGroup" style="grid-column: 1 / -1; display: none;">
                            <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem;">
                                {{ __('API Base URL (Endpoint)') }}
                            </label>
                            <div style="position: relative; display: flex; align-items: center;">
                                <input type="text" name="ai_base_url" id="aiBaseUrlInput" value="{{ old('ai_base_url', $savedBaseUrl) }}" placeholder="օրինակ՝ http://localhost:11434/v1 կամ https://api.together.xyz/v1" class="form-control" style="width: 100%; background: var(--bg-card); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 12px; padding: 0.75rem 1rem 0.75rem 2.6rem; font-size: 0.95rem; font-weight: 600;">
                                <span style="position: absolute; left: 1rem; color: #8b5cf6; font-size: 1rem;">
                                    <i class="fa-solid fa-network-wired"></i>
                                </span>
                            </div>
                            <span style="font-size: 0.78rem; color: var(--text-muted); margin-top: 0.35rem; display: block;">
                                {{ __('Նշեք հատուկ սերվերի կամ տեղային Ollama/vLLM հասցեն (OpenAI-compatible /chat/completions):') }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Informational Architecture Card -->
                <div style="background: linear-gradient(135deg, rgba(139, 92, 246, 0.06), rgba(59, 130, 246, 0.05)); border: 1px dashed rgba(139, 92, 246, 0.25); border-radius: 14px; padding: 1rem 1.25rem;">
                    <div style="display: flex; align-items: center; gap: 0.65rem; font-weight: 700; font-size: 0.9rem; color: var(--text-main); margin-bottom: 0.35rem;">
                        <i class="fa-solid fa-shield-halved" style="color: #8b5cf6;"></i>
                        {{ __('Ինչպե՞ս է ապահովվում AI-ի անխափան աշխատանքը') }}
                    </div>
                    <p style="margin: 0; font-size: 0.82rem; color: var(--text-muted); line-height: 1.5;">
                        {{ __('• Եթե Ձեր ընտրած պրովայդերի քվոտան ավարտվի կամ ցանցային սխալ առաջանա, համակարգը չի խափանվի. AI Մատուցողն ու Մենյուի գեներատորն ավտոմատ կերպով կանցնեն համակարգային կրկնօրինակման (Fallback):') }}<br>
                        {{ __('• Ֆայլերի և նկարների ճանաչման (Vision OCR) ժամանակ տեքստային պրովայդերների դեպքում համակարգն ավտոմատ կերպով օգտագործում է բարձր ճշգրտության Gemini Multimodal շարժիչը:') }}
                    </p>
                </div>
            </div>

            <!-- SECTION 2: AI WAITER ADVISOR CONFIGURATION (MOVED HERE) -->
            <div class="card settings-card" style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 20px; padding: clamp(1.2rem, 3vw, 1.85rem); box-shadow: var(--shadow-card); position: relative; overflow: hidden;">
                <div style="position: absolute; top: 0; left: 0; right: 0; height: 4px; background: linear-gradient(90deg, #8b5cf6, #ec4899, #f59e0b);"></div>

                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
                    <div style="display: flex; align-items: center; gap: 0.9rem;">
                        <span style="width: 44px; height: 44px; border-radius: 12px; background: rgba(139, 92, 246, 0.15); color: #8b5cf6; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; flex-shrink: 0;">
                            <i class="fa-solid fa-wand-magic-sparkles"></i>
                        </span>
                        <div>
                            <h3 style="margin: 0; font-size: 1.25rem; font-weight: 800; color: var(--text-main); font-family: 'Outfit', sans-serif; display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                                <span>{{ __('2. AI Մատուցող Խորհրդատու (AI Waiter & Sommelier)') }}</span>
                                <span style="font-size: 0.72rem; font-weight: 700; background: linear-gradient(135deg, #8b5cf6, #d946ef); color: #fff; padding: 0.15rem 0.5rem; border-radius: 6px;">AI SOMMELIER</span>
                            </h3>
                            <p style="margin: 0.2rem 0 0; font-size: 0.85rem; color: var(--text-muted); word-break: break-word;">
                                {{ __('Անհատական խոհարարական առաջարկություններ, համահունչ խմիչքների զուգորդում և ինտերակտիվ ընտրություն հաճախորդի համար') }}
                            </p>
                        </div>
                    </div>

                    <label style="display: inline-flex; align-items: center; gap: 0.75rem; cursor: pointer; background: var(--bg-body); border: 1px solid var(--border-color); padding: 0.55rem 0.95rem; border-radius: 12px;">
                        <input type="checkbox" name="ai_waiter_enabled" value="1" id="aiWaiterToggle" {{ old('ai_waiter_enabled', $vendor->ai_waiter_enabled) ? 'checked' : '' }} style="width: 18px; height: 18px; accent-color: #8b5cf6; cursor: pointer;">
                        <span style="font-size: 0.9rem; font-weight: 700; color: var(--text-main);">
                            {{ __('Միացնել AI Մատուցողը') }}
                        </span>
                    </label>
                </div>

                <div id="aiWaiterOptionsBlock" style="display: {{ old('ai_waiter_enabled', $vendor->ai_waiter_enabled) ? 'block' : 'none' }};">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 280px), 1fr)); gap: 1.25rem; margin-bottom: 1.25rem;">
                        <!-- AI Մատուցողի անվանումը -->
                        <div class="form-group">
                            <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem;">
                                {{ __('AI Մատուցողի Անունը / Կերպարը') }}
                            </label>
                            <div style="position: relative; display: flex; align-items: center;">
                                <input type="text" name="ai_waiter_name" id="aiWaiterNameInput" value="{{ old('ai_waiter_name', $vendor->ai_waiter_name ?? 'AI Մատուցող') }}" placeholder="Օրինակ՝ Ալեքս կամ Շեֆ Խորհրդատու" class="form-control" style="width: 100%; background: var(--bg-body); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 12px; padding: 0.75rem 1rem 0.75rem 2.6rem; font-size: 0.95rem; font-weight: 600;">
                                <span style="position: absolute; left: 1rem; color: #8b5cf6; font-size: 1rem;">
                                    <i class="fa-solid fa-user-tie"></i>
                                </span>
                            </div>
                            <span style="font-size: 0.78rem; color: var(--text-muted); margin-top: 0.35rem; display: block;">
                                {{ __('Այս անունով AI-ն կներկայանա հաճախորդին ողջույնի և խորհրդատվության ժամանակ') }}
                            </span>
                        </div>

                        <!-- Ողջույնի տեքստ -->
                        <div class="form-group">
                            <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem;">
                                {{ __('Ողջույնի Հատուկ Ուղերձ (ոչ պարտադիր)') }}
                            </label>
                            <div style="position: relative; display: flex; align-items: center;">
                                <input type="text" name="ai_waiter_welcome_text" id="aiWaiterWelcomeInput" value="{{ old('ai_waiter_welcome_text', $vendor->ai_waiter_welcome_text) }}" placeholder="Օրինակ՝ Բարի գալուստ, ուրախ ենք Ձեզ տեսնել: Կօգնե՞մ ընտրել լավագույն ուտեստը:" class="form-control" style="width: 100%; background: var(--bg-body); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 12px; padding: 0.75rem 1rem 0.75rem 2.6rem; font-size: 0.95rem; font-weight: 600;">
                                <span style="position: absolute; left: 1rem; color: #8b5cf6; font-size: 1rem;">
                                    <i class="fa-solid fa-comment-dots"></i>
                                </span>
                            </div>
                            <span style="font-size: 0.78rem; color: var(--text-muted); margin-top: 0.35rem; display: block;">
                                {{ __('Եթե դատարկ է, կօգտագործվի ստանդարտ ջերմ ողջույնը') }}
                            </span>
                        </div>
                    </div>

                    <!-- Առաջնահերթ Ինգրիդիենտներ (Priority Ingredients) -->
                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label style="display: flex; justify-content: space-between; align-items: center; font-size: 0.88rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.5rem;">
                            <span>
                                <i class="fa-solid fa-pepper-hot" style="color: #ef4444; margin-right: 0.35rem;"></i>
                                {{ __('Առաջնահերթ Ինգրիդիենտներ (Բաղադրիչներ)') }}
                            </span>
                            <span style="font-size: 0.76rem; font-weight: 600; color: #8b5cf6; background: rgba(139, 92, 246, 0.1); padding: 0.2rem 0.5rem; border-radius: 6px;">
                                {{ __('Խթանում AI Առաջարկներում') }}
                            </span>
                        </label>
                        <textarea name="ai_waiter_priority_ingredients" id="aiWaiterPriorityInput" rows="3" class="form-control" placeholder="Օրինակ՝ Սաղմոն, Տրյուֆել, Black Angus, Ծովախեցգետին, Պիստակ, Ավոկադո" style="width: 100%; background: var(--bg-body); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 12px; padding: 0.85rem 1rem; font-size: 0.92rem; font-weight: 500; resize: vertical;">{{ old('ai_waiter_priority_ingredients', $vendor->ai_waiter_priority_ingredients) }}</textarea>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 0.4rem; flex-wrap: wrap; gap: 0.5rem;">
                            <span style="font-size: 0.8rem; color: var(--text-muted);">
                                <i class="fa-solid fa-circle-info" style="color: #8b5cf6;"></i>
                                {{ __('Մուտքագրեք ստորակետով անջատված: Այս բաղադրիչներով պատրաստված ուտեստներին AI-ն կտա բարձր առաջնահերթություն:') }}
                            </span>
                            @if(!empty($vendor->getAiWaiterPriorityIngredientsList()))
                                <div style="display: flex; gap: 0.35rem; flex-wrap: wrap;">
                                    @foreach($vendor->getAiWaiterPriorityIngredientsList() as $ing)
                                        <span style="font-size: 0.72rem; font-weight: 700; background: rgba(139, 92, 246, 0.15); color: #8b5cf6; border: 1px solid rgba(139, 92, 246, 0.3); border-radius: 6px; padding: 0.15rem 0.45rem;">
                                            #{{ $ing }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Live Waiter Preview Simulation Card -->
                    <div style="background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 16px; padding: 1.25rem;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                            <span style="font-weight: 700; font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; display: flex; align-items: center; gap: 0.4rem;">
                                <i class="fa-solid fa-eye" style="color: #8b5cf6;"></i> {{ __('Ինչպես է տեսնում հաճախորդը (Live Preview)') }}
                            </span>
                            <span style="font-size: 0.72rem; background: rgba(16, 185, 129, 0.15); color: #10b981; font-weight: 700; padding: 0.15rem 0.45rem; border-radius: 6px;">
                                {{ __('Մենյուի Վիջեթ') }}
                            </span>
                        </div>

                        <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 1.15rem; max-width: 500px; margin: 0 auto; box-shadow: 0 4px 16px rgba(0,0,0,0.06);">
                            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
                                <div style="width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, #8b5cf6, #ec4899); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1rem; box-shadow: 0 2px 8px rgba(139, 92, 246, 0.4);">
                                    <i class="fa-solid fa-sparkles"></i>
                                </div>
                                <div>
                                    <div style="font-weight: 800; font-size: 0.95rem; color: var(--text-main);" id="previewWaiterName">
                                        {{ $vendor->ai_waiter_name ?? 'AI Մատուցող' }}
                                    </div>
                                    <div style="font-size: 0.75rem; color: #10b981; display: flex; align-items: center; gap: 0.3rem;">
                                        <span style="width: 6px; height: 6px; border-radius: 50%; background: #10b981;"></span>
                                        <span>{{ __('Օնլայն • Պատրաստ է առաջարկել') }}</span>
                                    </div>
                                </div>
                            </div>

                            <div style="background: var(--bg-body); border-radius: 12px; padding: 0.85rem 1rem; font-size: 0.88rem; color: var(--text-main); line-height: 1.45; border-left: 3px solid #8b5cf6;" id="previewWelcomeMessage">
                                {{ $vendor->ai_waiter_welcome_text ?: 'Բարի գալուստ: Ես Ձեր խելացի մատուցողն եմ: Ի՞նչ կցանկանայիք փորձել այսօր:' }}
                            </div>

                            <div style="display: flex; gap: 0.4rem; margin-top: 0.75rem; flex-wrap: wrap;">
                                <span style="font-size: 0.75rem; background: rgba(139, 92, 246, 0.1); color: #8b5cf6; padding: 0.25rem 0.6rem; border-radius: 999px; font-weight: 600;">
                                    🥩 Մսային ուտեստներ
                                </span>
                                <span style="font-size: 0.75rem; background: rgba(139, 92, 246, 0.1); color: #8b5cf6; padding: 0.25rem 0.6rem; border-radius: 999px; font-weight: 600;">
                                    🥗 Թեթև & Դիետիկ
                                </span>
                                <span style="font-size: 0.75rem; background: rgba(139, 92, 246, 0.1); color: #8b5cf6; padding: 0.25rem 0.6rem; border-radius: 999px; font-weight: 600;">
                                    🍷 Խմիչքների ընտրություն
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Action Bar -->
            <div style="display: flex; justify-content: flex-end; align-items: center; gap: 1rem; padding: 1rem 0; margin-bottom: 2rem;">
                <a href="{{ route('admin.settings.index') }}" class="btn btn-secondary" style="border-radius: 12px; padding: 0.75rem 1.4rem; font-weight: 600;">
                    {{ __('Չեղարկել') }}
                </a>
                <button type="submit" class="btn btn-primary" style="display: flex; align-items: center; gap: 0.5rem; border-radius: 12px; font-weight: 700; padding: 0.75rem 1.75rem; font-size: 0.95rem; box-shadow: 0 4px 14px rgba(139, 92, 246, 0.35); background: linear-gradient(135deg, #8b5cf6, #7c3aed); border: none; color: #fff;">
                    <i class="fa-solid fa-floppy-disk"></i> {{ __('Պահպանել AI Կարգավորումները') }}
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Dynamic Script for Provider Switching, Model Selection, and Live Connection Testing -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const providersData = @json($providers);
    let currentProvider = "{{ $activeProviderKey }}";
    let currentModel = "{{ $activeModel }}";

    const providerRadios = document.querySelectorAll('.provider-radio');
    const modelSelect = document.getElementById('aiModelSelect');
    const customModelGroup = document.getElementById('customModelGroup');
    const customModelInput = document.getElementById('customModelInput');
    const baseUrlGroup = document.getElementById('baseUrlGroup');
    const baseUrlInput = document.getElementById('aiBaseUrlInput');
    const apiKeyInput = document.getElementById('aiApiKeyInput');
    const providerDocLink = document.getElementById('providerDocLink');
    const providerDocText = document.getElementById('providerDocText');
    const providerDocLinkWrapper = document.getElementById('providerDocLinkWrapper');
    const headerProviderName = document.getElementById('headerProviderName');
    const headerModelName = document.getElementById('headerModelName');

    // AI Waiter Toggle and Live Preview
    const aiWaiterToggle = document.getElementById('aiWaiterToggle');
    const aiWaiterOptionsBlock = document.getElementById('aiWaiterOptionsBlock');
    const aiWaiterNameInput = document.getElementById('aiWaiterNameInput');
    const aiWaiterWelcomeInput = document.getElementById('aiWaiterWelcomeInput');
    const previewWaiterName = document.getElementById('previewWaiterName');
    const previewWelcomeMessage = document.getElementById('previewWelcomeMessage');

    if (aiWaiterToggle && aiWaiterOptionsBlock) {
        aiWaiterToggle.addEventListener('change', function() {
            aiWaiterOptionsBlock.style.display = this.checked ? 'block' : 'none';
        });
    }

    if (aiWaiterNameInput && previewWaiterName) {
        aiWaiterNameInput.addEventListener('input', function() {
            previewWaiterName.textContent = this.value.trim() || 'AI Մատուցող';
        });
    }

    if (aiWaiterWelcomeInput && previewWelcomeMessage) {
        aiWaiterWelcomeInput.addEventListener('input', function() {
            previewWelcomeMessage.textContent = this.value.trim() || 'Բարի գալուստ: Ես Ձեր խելացի մատուցողն եմ: Ի՞նչ կցանկանայիք փորձել այսօր:';
        });
    }

    // Toggle API Key visibility
    const toggleApiKeyVisibility = document.getElementById('toggleApiKeyVisibility');
    const eyeIcon = document.getElementById('eyeIcon');
    if (toggleApiKeyVisibility && apiKeyInput) {
        toggleApiKeyVisibility.addEventListener('click', function() {
            if (apiKeyInput.type === 'password') {
                apiKeyInput.type = 'text';
                eyeIcon.className = 'fa-solid fa-eye-slash';
            } else {
                apiKeyInput.type = 'password';
                eyeIcon.className = 'fa-solid fa-eye';
            }
        });
    }

    // Render models for selected provider
    function updateProviderUI(providerKey, keepExistingModel = false) {
        const config = providersData[providerKey];
        if (!config) return;

        currentProvider = providerKey;
        if (headerProviderName) headerProviderName.textContent = config.name;

        // Update provider cards styles
        document.querySelectorAll('.provider-card').forEach(card => {
            const radio = card.querySelector('.provider-radio');
            if (radio && radio.value === providerKey) {
                card.style.borderColor = '#8b5cf6';
                card.style.background = 'rgba(139, 92, 246, 0.08)';
            } else {
                card.style.borderColor = 'var(--border-color)';
                card.style.background = 'var(--bg-body)';
            }
        });

        // Populate models dropdown
        modelSelect.innerHTML = '';
        Object.entries(config.models).forEach(([mCode, mLabel]) => {
            const opt = document.createElement('option');
            opt.value = mCode;
            opt.textContent = mLabel;
            if (keepExistingModel && mCode === currentModel) {
                opt.selected = true;
                foundSelected = true;
            } else if (!keepExistingModel && mCode === config.default_model) {
                opt.selected = true;
                foundSelected = true;
            }
            modelSelect.appendChild(opt);
        });

        // Add Custom Model option
        const customOpt = document.createElement('option');
        customOpt.value = 'custom';
        customOpt.textContent = '✨ Այլ մոդել (Custom Model)...';
        if (keepExistingModel && !foundSelected && currentModel) {
            customOpt.selected = true;
            customModelInput.value = currentModel;
            foundSelected = true;
        }
        modelSelect.appendChild(customOpt);

        handleModelSelectChange();

        // Doc link
        if (config.doc_url) {
            providerDocLinkWrapper.style.display = 'inline-block';
            providerDocLink.href = config.doc_url;
            providerDocText.textContent = 'Ստանալ ' + config.name + ' API Key';
        } else {
            providerDocLinkWrapper.style.display = 'none';
        }

        // Show/hide Base URL input (Custom, or optional override)
        if (providerKey === 'custom') {
            baseUrlGroup.style.display = 'block';
            if (!baseUrlInput.value) {
                baseUrlInput.value = config.base_url || 'http://localhost:11434/v1';
            }
        } else {
            baseUrlGroup.style.display = 'block';
            baseUrlInput.placeholder = config.base_url ? `Լռելյայն՝ ${config.base_url}` : 'Լռելյայն API host';
        }
    }

    function handleModelSelectChange() {
        if (modelSelect.value === 'custom') {
            customModelGroup.style.display = 'block';
            if (headerModelName) headerModelName.textContent = customModelInput.value.trim() || 'Custom';
        } else {
            customModelGroup.style.display = 'none';
            if (headerModelName) headerModelName.textContent = modelSelect.value;
        }
    }

    modelSelect.addEventListener('change', handleModelSelectChange);
    customModelInput.addEventListener('input', function() {
        if (modelSelect.value === 'custom' && headerModelName) {
            headerModelName.textContent = this.value.trim() || 'Custom';
        }
    });

    providerRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.checked) {
                updateProviderUI(this.value, false);
            }
        });
    });

    // Initialize UI on load
    updateProviderUI(currentProvider, true);

    // Live Connection Test AJAX
    const btnTest = document.getElementById('btnTestConnection');
    const testIcon = document.getElementById('testIcon');
    const testBtnText = document.getElementById('testBtnText');
    const testResultBox = document.getElementById('testResultBox');

    btnTest.addEventListener('click', function() {
        const provider = document.querySelector('input[name="ai_provider"]:checked')?.value || 'gemini';
        let model = modelSelect.value;
        if (model === 'custom') {
            model = customModelInput.value.trim();
        }
        const apiKey = apiKeyInput.value.trim();
        const baseUrl = baseUrlInput.value.trim();

        // UI Loading state
        btnTest.disabled = true;
        testIcon.className = 'fa-solid fa-spinner fa-spin';
        testBtnText.textContent = 'Ստուգվում է...';
        testResultBox.style.display = 'none';

        fetch("{{ route('admin.settings.ai.test') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                ai_provider: provider,
                ai_model: model,
                ai_api_key: apiKey,
                ai_base_url: baseUrl
            })
        })
        .then(response => response.json())
        .then(data => {
            btnTest.disabled = false;
            testIcon.className = 'fa-solid fa-bolt';
            testBtnText.textContent = 'Ստուգել Կապը (Test)';

            testResultBox.style.display = 'block';
            if (data.success) {
                testResultBox.style.background = 'rgba(16, 185, 129, 0.12)';
                testResultBox.style.border = '1px solid rgba(16, 185, 129, 0.3)';
                testResultBox.style.color = '#10b981';
                testResultBox.innerHTML = `
                    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem;">
                        <span style="display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fa-solid fa-circle-check" style="font-size: 1.1rem;"></i>
                            <span>${data.message}</span>
                        </span>
                        <span style="background: rgba(16, 185, 129, 0.2); padding: 0.2rem 0.6rem; border-radius: 6px; font-size: 0.8rem; font-weight: 700;">
                            ⚡ ${data.latency_ms} ms
                        </span>
                    </div>
                `;
            } else {
                testResultBox.style.background = 'rgba(239, 68, 68, 0.12)';
                testResultBox.style.border = '1px solid rgba(239, 68, 68, 0.3)';
                testResultBox.style.color = '#ef4444';
                testResultBox.innerHTML = `
                    <div style="display: flex; align-items: flex-start; gap: 0.5rem;">
                        <i class="fa-solid fa-circle-xmark" style="font-size: 1.1rem; margin-top: 0.15rem;"></i>
                        <div>
                            <div>${data.message}</div>
                            <div style="font-size: 0.8rem; opacity: 0.85; margin-top: 0.25rem;">Խնդրում ենք ստուգել API Key-ը, մոդելի անվանումը կամ ինտերնետ կապը:</div>
                        </div>
                    </div>
                `;
            }
        })
        .catch(err => {
            btnTest.disabled = false;
            testIcon.className = 'fa-solid fa-bolt';
            testBtnText.textContent = 'Ստուգել Կապը (Test)';

            testResultBox.style.display = 'block';
            testResultBox.style.background = 'rgba(239, 68, 68, 0.12)';
            testResultBox.style.border = '1px solid rgba(239, 68, 68, 0.3)';
            testResultBox.style.color = '#ef4444';
            testResultBox.innerHTML = `
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fa-solid fa-circle-xmark" style="font-size: 1.1rem;"></i>
                    <span>Ցանցային սխալ հարցման ընթացքում: Խնդրում ենք փորձել կրկին:</span>
                </div>
            `;
        });
    });
});
</script>
@endsection
