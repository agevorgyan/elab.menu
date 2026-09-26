@props(['settings'])

<div x-show="activeTab === 'telegram'" x-cloak>
            <div class="settings-card">
                <div class="settings-section-title">
                    <i class="fa-brands fa-telegram" style="color: #229ed9;"></i>
                    <span>6. Telegram Ծանուցումների Կարգավորումներ (Platform Default & Admin Alerts)</span>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fa-solid fa-robot" style="color: #229ed9;"></i> Հարթակի Լռելյայն Telegram Bot Token
                        </label>
                        <input type="password" name="telegram_bot_token" id="saTelegramBotToken" class="form-input" value="{{ old('telegram_bot_token', $settings['telegram_bot_token'] ?? '') }}" placeholder="123456789:ABCdefGHIjklMNOpqr... (@BotFather)">
                        <span class="input-hint">Եթե ռեստորանը չունի սեփական բոտ, ծանուցումները կուղարկվեն այս բոտի միջոցով</span>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="fa-solid fa-hashtag" style="color: #229ed9;"></i> SuperAdmin Alert Chat ID / Group ID
                        </label>
                        <input type="text" name="telegram_admin_chat_id" id="saTelegramChatId" class="form-input" value="{{ old('telegram_admin_chat_id', $settings['telegram_admin_chat_id'] ?? '') }}" placeholder="-100123456789 կամ անձնական Chat ID">
                        <span class="input-hint">Այս չատում կստանաք համակարգային թեստեր և ադմինիստրատիվ ազդանշաններ</span>
                    </div>
                </div>

                <!-- Test Connection Box -->
                <div class="preview-box" style="margin-top: 1.25rem; background: rgba(34, 158, 217, 0.08); border: 1px dashed rgba(34, 158, 217, 0.35);">
                    <div>
                        <strong style="color: var(--text-main); font-size: 0.95rem; display: block; margin-bottom: 0.25rem;">
                            <i class="fa-solid fa-paper-plane" style="color: #229ed9;"></i> Ստուգել Telegram Կապը & Ուղարկել Թեստ
                        </strong>
                        <span style="font-size: 0.82rem; color: var(--text-muted);">
                            Ստուգեք, որ Bot Token-ը և SuperAdmin Chat ID-ն ճիշտ են կարգավորված և հաղորդագրությունը հաջողությամբ հասնում է։
                        </span>
                    </div>
                    <button type="button" id="btnTestSaTelegram" onclick="testSuperAdminTelegram()" class="btn" style="background: #229ed9; color: #fff; border-radius: 10px; padding: 0.55rem 1.2rem; font-size: 0.85rem; font-weight: 700; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 0.5rem;">
                        <i class="fa-solid fa-paper-plane"></i> Ուղարկել Թեստ
                    </button>
                </div>

                <div id="saTelegramTestResult" style="display: none; margin-top: 1rem; border-radius: 12px; padding: 0.85rem 1.15rem; font-size: 0.88rem; font-weight: 600;"></div>
            </div>
        </div>