@if($vendor->ai_waiter_enabled)
<!-- Fullscreen AI Waiter Advisor Modal -->
<div x-show="showAiWaiter" 
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fullscreen-ai-waiter-backdrop"
     style="position: fixed; inset: 0; z-index: 99995; background: var(--bg-main); display: flex; flex-direction: column; overflow: hidden;"
     x-cloak>

    <!-- Top Navigation Bar -->
    <header style="background: var(--bg-card); border-bottom: 1px solid var(--border-color); padding: 0.85rem 1.25rem; display: flex; justify-content: space-between; align-items: center; z-index: 30; flex-shrink: 0; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);">
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <!-- Sommelier Avatar -->
            <div style="position: relative; width: 44px; height: 44px; border-radius: 50%; background: linear-gradient(135deg, #8b5cf6, #ec4899); padding: 2px; flex-shrink: 0;">
                <div style="width: 100%; height: 100%; border-radius: 50%; background: var(--bg-card); display: flex; align-items: center; justify-content: center; color: #8b5cf6; font-size: 1.25rem;">
                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                </div>
                <span style="position: absolute; bottom: 0; right: 0; width: 12px; height: 12px; border-radius: 50%; background: #10b981; border: 2px solid var(--bg-card);"></span>
            </div>

            <div>
                <div style="display: flex; align-items: center; gap: 0.4rem;">
                    <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.1rem; font-weight: 800; color: var(--text-main); margin: 0;">
                        {{ $vendor->getAiWaiterName() }}
                    </h3>
                    <span style="font-size: 0.65rem; font-weight: 700; background: rgba(139, 92, 246, 0.15); color: #8b5cf6; padding: 0.1rem 0.45rem; border-radius: 6px;">AI ADVISOR</span>
                </div>
                <div style="font-size: 0.75rem; color: #10b981; font-weight: 600; display: flex; align-items: center; gap: 0.35rem; margin-top: 0.1rem;">
                    <span style="width: 6px; height: 6px; border-radius: 50%; background: #10b981;"></span>
                    <span x-show="selectedLang === 'hy'">Առցանց խորհրդատու</span>
                    <span x-show="selectedLang === 'en'">Online Sommelier</span>
                    <span x-show="selectedLang === 'ru'">Онлайн-сомелье</span>
                </div>
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 0.5rem;">
            <!-- Reset Button -->
            <button type="button" 
                    @click="resetAiQuiz()"
                    title="Սկսել նորից"
                    style="width: 38px; height: 38px; border-radius: 12px; background: var(--bg-body); border: 1px solid var(--border-color); color: var(--text-muted); display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s;">
                <i class="fa-solid fa-rotate-left"></i>
            </button>

            <!-- Close Button -->
            <button type="button" 
                    @click="closeAiWaiter()" 
                    style="width: 38px; height: 38px; border-radius: 12px; background: var(--bg-body); border: 1px solid var(--border-color); color: var(--text-main); display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s;">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    </header>

    <!-- Main Scrollable Content Container -->
    <div style="flex: 1; overflow-y: auto; padding: 1.25rem 1rem 6rem 1rem; max-width: 680px; margin: 0 auto; width: 100%; -webkit-overflow-scrolling: touch;">

        <!-- 1. GUIDED INTERACTIVE QUIZ SECTION -->
        <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 20px; padding: 1.25rem; margin-bottom: 1.25rem; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);">
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-main); display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fa-solid fa-list-check" style="color: #8b5cf6;"></i>
                    <span x-show="selectedLang === 'hy'">Արագ ընտրություն ըստ Ձեր ճաշակի</span>
                    <span x-show="selectedLang === 'en'">Quick Interactive Preference Quiz</span>
                    <span x-show="selectedLang === 'ru'">Быстрый подбор по вашему вкусу</span>
                </div>
            </div>

            <!-- Question 1: Craving / Main Preference -->
            <div style="margin-bottom: 1rem;">
                <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.04em;">
                    <span x-show="selectedLang === 'hy'">1. Ի՞նչ համեր եք նախընտրում այսօր</span>
                    <span x-show="selectedLang === 'en'">1. What are you craving today?</span>
                    <span x-show="selectedLang === 'ru'">1. Что вы предпочитаете сегодня?</span>
                </div>
                <div style="display: flex; gap: 0.45rem; flex-wrap: wrap;">
                    <button type="button" 
                            @click="selectQuizOption('craving', 'meat')"
                            :class="{ 'quiz-active': aiPreferences.craving === 'meat' }"
                            class="quiz-chip-btn">
                        <span>🥩</span>
                        <span x-show="selectedLang === 'hy'">Միս և Սթեյք</span>
                        <span x-show="selectedLang === 'en'">Meat & Steaks</span>
                        <span x-show="selectedLang === 'ru'">Мясо и стейки</span>
                    </button>

                    <button type="button" 
                            @click="selectQuizOption('craving', 'seafood')"
                            :class="{ 'quiz-active': aiPreferences.craving === 'seafood' }"
                            class="quiz-chip-btn">
                        <span>🐟</span>
                        <span x-show="selectedLang === 'hy'">Ձուկ և Ծովամթերք</span>
                        <span x-show="selectedLang === 'en'">Fish & Seafood</span>
                        <span x-show="selectedLang === 'ru'">Рыба и морепродукты</span>
                    </button>

                    <button type="button" 
                            @click="selectQuizOption('craving', 'vegetarian')"
                            :class="{ 'quiz-active': aiPreferences.craving === 'vegetarian' }"
                            class="quiz-chip-btn">
                        <span>🥗</span>
                        <span x-show="selectedLang === 'hy'">Բուսական / Թեթև</span>
                        <span x-show="selectedLang === 'en'">Vegetarian / Light</span>
                        <span x-show="selectedLang === 'ru'">Легкое / Веган</span>
                    </button>

                    <button type="button" 
                            @click="selectQuizOption('craving', 'dessert')"
                            :class="{ 'quiz-active': aiPreferences.craving === 'dessert' }"
                            class="quiz-chip-btn">
                        <span>🍰</span>
                        <span x-show="selectedLang === 'hy'">Աղանդեր և Քաղցր</span>
                        <span x-show="selectedLang === 'en'">Dessert & Sweets</span>
                        <span x-show="selectedLang === 'ru'">Десерты и сладкое</span>
                    </button>
                </div>
            </div>

            <!-- Question 2: Occasion / Mood -->
            <div style="margin-bottom: 1rem;">
                <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.04em;">
                    <span x-show="selectedLang === 'hy'">2. Ո՞րն է Ձեր առիթը կամ տրամադրությունը</span>
                    <span x-show="selectedLang === 'en'">2. What is the occasion or mood?</span>
                    <span x-show="selectedLang === 'ru'">2. Какой повод или настроение?</span>
                </div>
                <div style="display: flex; gap: 0.45rem; flex-wrap: wrap;">
                    <button type="button" 
                            @click="selectQuizOption('occasion', 'romantic')"
                            :class="{ 'quiz-active': aiPreferences.occasion === 'romantic' }"
                            class="quiz-chip-btn">
                        <span>🍷</span>
                        <span x-show="selectedLang === 'hy'">Ռոմանտիկ ընթրիք</span>
                        <span x-show="selectedLang === 'en'">Romantic Dinner</span>
                        <span x-show="selectedLang === 'ru'">Романтический ужин</span>
                    </button>

                    <button type="button" 
                            @click="selectQuizOption('occasion', 'quick_lunch')"
                            :class="{ 'quiz-active': aiPreferences.occasion === 'quick_lunch' }"
                            class="quiz-chip-btn">
                        <span>⚡</span>
                        <span x-show="selectedLang === 'hy'">Արագ լանչ</span>
                        <span x-show="selectedLang === 'en'">Quick Lunch</span>
                        <span x-show="selectedLang === 'ru'">Быстрый ланч</span>
                    </button>

                    <button type="button" 
                            @click="selectQuizOption('occasion', 'family')"
                            :class="{ 'quiz-active': aiPreferences.occasion === 'family' }"
                            class="quiz-chip-btn">
                        <span>👨‍👩‍👧</span>
                        <span x-show="selectedLang === 'hy'">Ընկերական / Ընտանեկան</span>
                        <span x-show="selectedLang === 'en'">Family & Friends</span>
                        <span x-show="selectedLang === 'ru'">Семья и друзья</span>
                    </button>

                    <button type="button" 
                            @click="selectQuizOption('occasion', 'celebration')"
                            :class="{ 'quiz-active': aiPreferences.occasion === 'celebration' }"
                            class="quiz-chip-btn">
                        <span>🎉</span>
                        <span x-show="selectedLang === 'hy'">Տոնական</span>
                        <span x-show="selectedLang === 'en'">Celebration</span>
                        <span x-show="selectedLang === 'ru'">Праздничный повод</span>
                    </button>
                </div>
            </div>

            <!-- Question 3: Drink Preference -->
            <div>
                <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.04em;">
                    <span x-show="selectedLang === 'hy'">3. Ինչպիսի՞ խմիչք կնախընտրեք զուգորդել</span>
                    <span x-show="selectedLang === 'en'">3. What drink would you like to pair?</span>
                    <span x-show="selectedLang === 'ru'">3. Какой напиток предпочитаете к блюду?</span>
                </div>
                <div style="display: flex; gap: 0.45rem; flex-wrap: wrap;">
                    <button type="button" 
                            @click="selectQuizOption('drink_preference', 'wine')"
                            :class="{ 'quiz-active': aiPreferences.drink_preference === 'wine' }"
                            class="quiz-chip-btn">
                        <span>🍷</span>
                        <span x-show="selectedLang === 'hy'">Գինի</span>
                        <span x-show="selectedLang === 'en'">Wine</span>
                        <span x-show="selectedLang === 'ru'">Вино</span>
                    </button>

                    <button type="button" 
                            @click="selectQuizOption('drink_preference', 'cocktail')"
                            :class="{ 'quiz-active': aiPreferences.drink_preference === 'cocktail' }"
                            class="quiz-chip-btn">
                        <span>🍸</span>
                        <span x-show="selectedLang === 'hy'">Կոկտեյլ</span>
                        <span x-show="selectedLang === 'en'">Cocktail</span>
                        <span x-show="selectedLang === 'ru'">Коктейль</span>
                    </button>

                    <button type="button" 
                            @click="selectQuizOption('drink_preference', 'beer')"
                            :class="{ 'quiz-active': aiPreferences.drink_preference === 'beer' }"
                            class="quiz-chip-btn">
                        <span>🍺</span>
                        <span x-show="selectedLang === 'hy'">Գարեջուր</span>
                        <span x-show="selectedLang === 'en'">Beer</span>
                        <span x-show="selectedLang === 'ru'">Пиво</span>
                    </button>

                    <button type="button" 
                            @click="selectQuizOption('drink_preference', 'non_alcoholic')"
                            :class="{ 'quiz-active': aiPreferences.drink_preference === 'non_alcoholic' }"
                            class="quiz-chip-btn">
                        <span>🍋</span>
                        <span x-show="selectedLang === 'hy'">Թարմ / Անալկոհոլ</span>
                        <span x-show="selectedLang === 'en'">Fresh / Non-Alcoholic</span>
                        <span x-show="selectedLang === 'ru'">Безалкогольное</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- 2. FREEFORM PROMPT INPUT & QUICK SUGGESTIONS -->
        <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 20px; padding: 1.25rem; margin-bottom: 1.5rem; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);">
            <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-main); margin-bottom: 0.65rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fa-solid fa-comment-dots" style="color: #8b5cf6;"></i>
                <span x-show="selectedLang === 'hy'">Կամ նկարագրեք Ձեր ցանկությունը</span>
                <span x-show="selectedLang === 'en'">Or describe your special craving</span>
                <span x-show="selectedLang === 'ru'">Или опишите ваши пожелания</span>
            </div>

            <form @submit.prevent="submitAiPrompt()">
                <div style="position: relative; display: flex; align-items: center;">
                    <input type="text" 
                           x-model="aiPromptInput"
                           :placeholder="selectedLang === 'en' ? 'e.g. Tender steak with rich red wine or grilled salmon...' : (selectedLang === 'ru' ? 'например: Нежный стейк с вином или лосось на гриле...' : 'Օրինակ՝ Հյութալի սթեյք գինու հետ կամ գրիլ սաղմոն...')"
                           class="form-control" 
                           style="width: 100%; background: var(--bg-body); border: 1.5px solid var(--border-color); color: var(--text-main); border-radius: 14px; padding: 0.85rem 3.2rem 0.85rem 1rem; font-size: 0.92rem; outline: none; transition: border-color 0.2s;">
                    
                    <button type="submit" 
                            :disabled="aiLoading"
                            style="position: absolute; right: 0.4rem; width: 38px; height: 38px; border-radius: 10px; border: none; background: linear-gradient(135deg, #8b5cf6, #d946ef); color: #ffffff; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: transform 0.2s;">
                        <i class="fa-solid fa-paper-plane" x-show="!aiLoading"></i>
                        <i class="fa-solid fa-spinner fa-spin" x-show="aiLoading" x-cloak></i>
                    </button>
                </div>
            </form>

            <!-- Quick preset prompt pills -->
            <div style="display: flex; gap: 0.4rem; margin-top: 0.75rem; overflow-x: auto; scrollbar-width: none; padding-bottom: 0.25rem;">
                <button type="button" @click="setPromptPreset('Հյութալի սթեյք կարմիր գինով')" class="prompt-preset-chip">
                    🥩 Սթեյք գինով
                </button>
                <button type="button" @click="setPromptPreset('Թարմ գրիլ սաղմոն և թեթև ըմպելիք')" class="prompt-preset-chip">
                    🐟 Գրիլ Սաղմոն
                </button>
                <button type="button" @click="setPromptPreset('Թեթև բուսական նախուտեստ')" class="prompt-preset-chip">
                    🥗 Թեթև նախուտեստ
                </button>
                <button type="button" @click="setPromptPreset('Շոկոլադե աղանդեր տաք սուրճի հետ')" class="prompt-preset-chip">
                    🍰 Շոկոլադե աղանդեր
                </button>
            </div>
        </div>

        <!-- 3. AI WAITER COMMENTARY & STATUS -->
        <div style="margin-bottom: 1.5rem;">
            <!-- Loading State -->
            <div x-show="aiLoading" 
                 x-transition
                 style="background: var(--bg-card); border: 1px solid rgba(139, 92, 246, 0.3); border-radius: 20px; padding: 1.5rem; text-align: center; box-shadow: 0 4px 20px rgba(139, 92, 246, 0.1);">
                <div style="width: 50px; height: 50px; margin: 0 auto 0.75rem auto; border-radius: 50%; background: rgba(139, 92, 246, 0.15); color: #8b5cf6; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                    <i class="fa-solid fa-spinner fa-spin"></i>
                </div>
                <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-main); margin-bottom: 0.25rem;">
                    <span x-show="selectedLang === 'hy'">AI Մատուցողը վերլուծում է մենյուն...</span>
                    <span x-show="selectedLang === 'en'">AI Sommelier is selecting pairings...</span>
                    <span x-show="selectedLang === 'ru'">AI-сомелье подбирает идеальные сочетания...</span>
                </div>
                <div style="font-size: 0.8rem; color: var(--text-muted);">
                    <span x-show="selectedLang === 'hy'">Հաշվի են առնվում առաջնահերթ ինգրիդիենտներն ու համահունչ ըմպելիքները:</span>
                    <span x-show="selectedLang === 'en'">Considering chef signature ingredients and flavor harmony.</span>
                    <span x-show="selectedLang === 'ru'">Учитываются фирменные ингредиенты и гармония вкусов.</span>
                </div>
            </div>

            <!-- Sommelier Message Bubble -->
            <div x-show="!aiLoading && aiCommentary" 
                 x-transition
                 style="background: linear-gradient(135deg, rgba(139, 92, 246, 0.1), rgba(236, 72, 153, 0.06)); border: 1.5px solid rgba(139, 92, 246, 0.3); border-radius: 20px; padding: 1.25rem; display: flex; gap: 0.9rem; align-items: flex-start; box-shadow: 0 4px 15px rgba(139, 92, 246, 0.08);">
                <div style="width: 38px; height: 38px; border-radius: 50%; background: linear-gradient(135deg, #8b5cf6, #d946ef); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0; box-shadow: 0 4px 10px rgba(139, 92, 246, 0.3);">
                    <i class="fa-solid fa-quote-left"></i>
                </div>
                <div style="flex: 1;">
                    <div style="font-weight: 800; font-size: 0.85rem; color: #8b5cf6; margin-bottom: 0.25rem; text-transform: uppercase; letter-spacing: 0.04em;">
                        {{ $vendor->getAiWaiterName() }} &bull; SOMMELIER NOTE
                    </div>
                    <div style="font-size: 0.92rem; line-height: 1.55; color: var(--text-main);" x-text="aiCommentary"></div>
                </div>
            </div>
        </div>

        <!-- 4. RECOMMENDED DISHES & PAIRINGS STREAM -->
        <div x-show="!aiLoading && aiRecommendations.length > 0">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.25rem; font-weight: 800; color: var(--text-main); margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fa-solid fa-sparkles" style="color: #f59e0b;"></i>
                    <span x-show="selectedLang === 'hy'">Առաջարկվող Ճաշատեսակներ</span>
                    <span x-show="selectedLang === 'en'">Recommended Highlights</span>
                    <span x-show="selectedLang === 'ru'">Рекомендуемые блюда</span>
                </h3>
                <span style="font-size: 0.78rem; font-weight: 700; color: #8b5cf6;" x-text="aiRecommendations.length + ' ընտրանի'"></span>
            </div>

            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                <template x-for="(rec, idx) in aiRecommendations" :key="rec.id">
                    <div class="ai-dish-card" style="background: var(--bg-card); border: 1.5px solid var(--border-color); border-radius: 22px; padding: 1.25rem; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.04); position: relative; overflow: hidden; transition: transform 0.2s, border-color 0.2s;">
                        
                        <!-- Top Dish Info -->
                        <div style="display: flex; gap: 1rem; align-items: flex-start;">
                            <img :src="rec.image" :alt="rec.name" style="width: 95px; height: 95px; border-radius: 16px; object-fit: cover; flex-shrink: 0; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">

                            <div style="flex: 1; min-width: 0;">
                                <div style="display: flex; align-items: center; gap: 0.4rem; margin-bottom: 0.25rem; flex-wrap: wrap;">
                                    <span style="font-size: 0.72rem; font-weight: 700; color: #8b5cf6; background: rgba(139, 92, 246, 0.12); padding: 0.15rem 0.45rem; border-radius: 6px;" x-text="rec.category_name"></span>
                                    <template x-if="rec.calories">
                                        <span style="font-size: 0.72rem; color: var(--text-muted);">
                                            <i class="fa-solid fa-fire" style="color: #f97316;"></i> <span x-text="rec.calories + ' kcal'"></span>
                                        </span>
                                    </template>
                                </div>

                                <h4 style="font-family: 'Outfit', sans-serif; font-size: 1.15rem; font-weight: 800; color: var(--text-main); margin: 0 0 0.35rem 0; line-height: 1.25;" x-text="rec.name"></h4>

                                <div style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; margin-bottom: 0.5rem;" x-text="rec.description"></div>

                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div style="font-family: 'Outfit', sans-serif; font-size: 1.2rem; font-weight: 800; color: var(--accent);" x-text="rec.formatted_price"></div>

                                    <button type="button" 
                                            @click="selectDish(rec.payload)"
                                            class="ai-add-cart-btn"
                                            style="background: var(--primary); color: #fff; border: none; border-radius: 12px; padding: 0.55rem 0.95rem; font-size: 0.85rem; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 0.45rem; box-shadow: 0 4px 12px rgba(0,0,0,0.15); transition: transform 0.15s;">
                                        <i class="fa-solid fa-cart-plus"></i>
                                        <span x-show="selectedLang === 'hy'">Ավելացնել</span>
                                        <span x-show="selectedLang === 'en'">Add to Cart</span>
                                        <span x-show="selectedLang === 'ru'">В корзину</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Why Recommended Badge / Reason -->
                        <template x-if="rec.reason">
                            <div style="margin-top: 0.85rem; padding: 0.65rem 0.85rem; border-radius: 12px; background: var(--bg-body); border: 1px dashed var(--border-color); font-size: 0.82rem; color: var(--text-muted); display: flex; align-items: flex-start; gap: 0.5rem;">
                                <i class="fa-solid fa-sparkles" style="color: #8b5cf6; margin-top: 0.15rem; flex-shrink: 0;"></i>
                                <span x-text="rec.reason"></span>
                            </div>
                        </template>

                        <!-- FOOD & DRINK PAIRING SUB-CARD -->
                        <template x-if="rec.pairings && (rec.pairings.drink || rec.pairings.side)">
                            <div style="margin-top: 1rem; border-top: 1px solid var(--border-color); padding-top: 0.9rem;">
                                
                                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.65rem;">
                                    <div style="font-size: 0.78rem; font-weight: 800; color: #8b5cf6; display: flex; align-items: center; gap: 0.4rem; text-transform: uppercase; letter-spacing: 0.04em;">
                                        <i class="fa-solid fa-wine-glass"></i>
                                        <span x-show="selectedLang === 'hy'">Համահունչ Զուգորդում</span>
                                        <span x-show="selectedLang === 'en'">Sommelier Pairing</span>
                                        <span x-show="selectedLang === 'ru'">Идеальное сочетание</span>
                                    </div>
                                    <span style="font-size: 0.72rem; color: var(--text-muted);" x-show="selectedLang === 'hy'">1-Click ավելացում</span>
                                </div>

                                <template x-if="rec.pairings.pairing_note">
                                    <div style="font-size: 0.8rem; color: var(--text-muted); font-style: italic; margin-bottom: 0.65rem;" x-text="rec.pairings.pairing_note"></div>
                                </template>

                                <!-- Paired Items Grid -->
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 0.65rem;">
                                    <!-- Paired Drink -->
                                    <template x-if="rec.pairings.drink">
                                        <div style="background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 14px; padding: 0.65rem; display: flex; align-items: center; justify-content: space-between; gap: 0.6rem;">
                                            <div style="display: flex; align-items: center; gap: 0.55rem; min-width: 0;">
                                                <img :src="rec.pairings.drink.image" style="width: 44px; height: 44px; border-radius: 10px; object-fit: cover; flex-shrink: 0;">
                                                <div style="min-width: 0;">
                                                    <div style="font-size: 0.84rem; font-weight: 700; color: var(--text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" x-text="rec.pairings.drink.name"></div>
                                                    <div style="font-size: 0.78rem; font-weight: 800; color: var(--accent);" x-text="rec.pairings.drink.formatted_price"></div>
                                                </div>
                                            </div>

                                            <button type="button" 
                                                    @click="selectDish(rec.pairings.drink.payload)"
                                                    title="Ավելացնել զամբյուղ"
                                                    style="width: 32px; height: 32px; border-radius: 10px; border: 1px solid var(--border-color); background: var(--bg-card); color: #8b5cf6; display: flex; align-items: center; justify-content: center; cursor: pointer; flex-shrink: 0; transition: transform 0.15s;">
                                                <i class="fa-solid fa-plus"></i>
                                            </button>
                                        </div>
                                    </template>

                                    <!-- Paired Side / Appetizer -->
                                    <template x-if="rec.pairings.side">
                                        <div style="background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 14px; padding: 0.65rem; display: flex; align-items: center; justify-content: space-between; gap: 0.6rem;">
                                            <div style="display: flex; align-items: center; gap: 0.55rem; min-width: 0;">
                                                <img :src="rec.pairings.side.image" style="width: 44px; height: 44px; border-radius: 10px; object-fit: cover; flex-shrink: 0;">
                                                <div style="min-width: 0;">
                                                    <div style="font-size: 0.84rem; font-weight: 700; color: var(--text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" x-text="rec.pairings.side.name"></div>
                                                    <div style="font-size: 0.78rem; font-weight: 800; color: var(--accent);" x-text="rec.pairings.side.formatted_price"></div>
                                                </div>
                                            </div>

                                            <button type="button" 
                                                    @click="selectDish(rec.pairings.side.payload)"
                                                    title="Ավելացնել զամբյուղ"
                                                    style="width: 32px; height: 32px; border-radius: 10px; border: 1px solid var(--border-color); background: var(--bg-card); color: #8b5cf6; display: flex; align-items: center; justify-content: center; cursor: pointer; flex-shrink: 0; transition: transform 0.15s;">
                                                <i class="fa-solid fa-plus"></i>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                    </div>
                </template>
            </div>
        </div>

    </div>

    <!-- Fixed Bottom Sticky Bar -->
    <footer style="background: var(--bg-card); border-top: 1px solid var(--border-color); padding: 0.85rem 1.25rem max(0.85rem, env(safe-area-inset-bottom)) 1.25rem; display: flex; justify-content: space-between; align-items: center; z-index: 30; flex-shrink: 0; box-shadow: 0 -4px 15px rgba(0, 0, 0, 0.05); max-width: 680px; margin: 0 auto; width: 100%;">
        <button type="button" 
                @click="closeAiWaiter()" 
                style="background: transparent; border: none; color: var(--text-muted); font-size: 0.88rem; font-weight: 600; display: flex; align-items: center; gap: 0.4rem; cursor: pointer;">
            <i class="fa-solid fa-arrow-left"></i>
            <span x-show="selectedLang === 'hy'">Մենյու</span>
            <span x-show="selectedLang === 'en'">Menu</span>
            <span x-show="selectedLang === 'ru'">В меню</span>
        </button>

        <button type="button" 
                @click="closeAiWaiter(); showCartModal = true"
                style="background: var(--primary); color: #ffffff; border: none; border-radius: 14px; padding: 0.65rem 1.25rem; font-size: 0.92rem; font-weight: 700; display: flex; align-items: center; gap: 0.65rem; cursor: pointer; box-shadow: 0 4px 14px rgba(0,0,0,0.15);">
            <i class="fa-solid fa-basket-shopping"></i>
            <span x-show="selectedLang === 'hy'">Զամբյուղ</span>
            <span x-show="selectedLang === 'en'">View Cart</span>
            <span x-show="selectedLang === 'ru'">Корзина</span>
            <span x-show="cartTotalCount > 0" 
                  x-text="cartTotalCount" 
                  style="background: #ffffff; color: var(--primary); font-size: 0.75rem; font-weight: 800; padding: 0.1rem 0.45rem; border-radius: 9999px;"></span>
        </button>
    </footer>

</div>

<style>
.quiz-chip-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.45rem 0.8rem;
    border-radius: 12px;
    border: 1px solid var(--border-color);
    background: var(--bg-body);
    color: var(--text-muted);
    font-size: 0.82rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    white-space: nowrap;
}
.quiz-chip-btn:hover {
    border-color: #8b5cf6;
    color: var(--text-main);
}
.quiz-chip-btn.quiz-active {
    background: linear-gradient(135deg, rgba(139, 92, 246, 0.2), rgba(217, 70, 239, 0.2));
    border-color: #8b5cf6;
    color: #8b5cf6;
    font-weight: 700;
    box-shadow: 0 0 12px rgba(139, 92, 246, 0.2);
}
.prompt-preset-chip {
    padding: 0.35rem 0.65rem;
    border-radius: 9999px;
    border: 1px solid var(--border-color);
    background: var(--bg-body);
    color: var(--text-muted);
    font-size: 0.76rem;
    font-weight: 600;
    white-space: nowrap;
    cursor: pointer;
    transition: all 0.15s ease;
}
.prompt-preset-chip:hover {
    border-color: #8b5cf6;
    color: #8b5cf6;
}
.ai-dish-card:hover {
    border-color: rgba(139, 92, 246, 0.4);
}
.ai-add-cart-btn:active {
    transform: scale(0.95);
}
</style>
@endif
