@props(['settings'])

<div x-show="activeTab === 'contacts'" x-cloak>
            <!-- Contacts Section -->
            <div class="settings-card">
                <div class="settings-section-title">
                    <i class="fa-solid fa-headset" style="color: #f59e0b;"></i>
                    <span>3. Պաշտոնական Կոնտակտներ (Լենդինգ և Աջակցություն)</span>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fa-solid fa-phone"></i> Հիմնական Հեռախոսահամար *
                        </label>
                        <input type="text" name="contact_phone" class="form-input" required value="{{ old('contact_phone', $settings['contact_phone'] ?? '+37455776066') }}" placeholder="+37455776066">
                        <span class="input-hint">Ցուցադրվում է լենդինգի գլխամասում և ստորոտում</span>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="fa-brands fa-whatsapp" style="color: #25d366;"></i> WhatsApp Համար
                        </label>
                        <input type="text" name="contact_whatsapp" class="form-input" value="{{ old('contact_whatsapp', $settings['contact_whatsapp'] ?? '+37455776066') }}" placeholder="+37455776066">
                        <span class="input-hint">Արագ կապի կոճակների համար (առանց բացատների)</span>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="fa-brands fa-telegram" style="color: #229ed9;"></i> Telegram Համար կամ Username
                        </label>
                        <input type="text" name="contact_telegram" class="form-input" value="{{ old('contact_telegram', $settings['contact_telegram'] ?? '+37455776066') }}" placeholder="+37455776066 կամ elab_menu">
                        <span class="input-hint">Telegram ալիքի կամ չատի համար</span>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="fa-solid fa-envelope"></i> Էլ. Փոստ (Official Email) *
                        </label>
                        <input type="email" name="contact_email" class="form-input" required value="{{ old('contact_email', $settings['contact_email'] ?? 'menu@elab.am') }}" placeholder="menu@elab.am">
                        <span class="input-hint">Հաճախորդների դիմումների և հարցումների համար</span>
                    </div>
                </div>
            </div>

            <!-- Social Media Links -->
            <div class="settings-card">
                <div class="settings-section-title">
                    <i class="fa-solid fa-share-nodes" style="color: #3b82f6;"></i>
                    <span>4. Սոցիալական Ցանցեր</span>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fa-brands fa-facebook" style="color: #1877f2;"></i> Facebook Էջ / Link
                        </label>
                        <input type="text" name="social_facebook" class="form-input" value="{{ old('social_facebook', $settings['social_facebook'] ?? 'https://facebook.com/elab.menu') }}" placeholder="https://facebook.com/elab.menu կամ @elab.menu">
                        <span class="input-hint">Ֆեյսբուքյան պաշտոնական էջի հղումը</span>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="fa-brands fa-instagram" style="color: #e1306c;"></i> Instagram Էջ / Link
                        </label>
                        <input type="text" name="social_instagram" class="form-input" value="{{ old('social_instagram', $settings['social_instagram'] ?? 'https://instagram.com/elab.menu') }}" placeholder="https://instagram.com/elab.menu կամ @elab.menu">
                        <span class="input-hint">Ինստագրամյան պաշտոնական էջի հղումը</span>
                    </div>
                </div>
            </div>
        </div>