<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class AiWaiterService
{
    /**
     * Generate dish recommendations based on preferences, priority ingredients, and optional user prompt.
     *
     * @param  array<string, mixed>  $preferences
     * @return array<string, mixed>
     */
    public function recommendDishes(
        Vendor $vendor,
        array $preferences = [],
        ?string $prompt = null,
        string $lang = 'hy',
        ?int $locationId = null
    ): array {
        // 1. Fetch available products with relations
        $products = Product::where('vendor_id', $vendor->id)
            ->where('is_available', true)
            ->whereHas('category', function ($q) {
                $q->where('is_active', true);
            })
            ->with(['category', 'variations', 'allergens', 'overrides'])
            ->get();

        if ($products->isEmpty()) {
            return [
                'commentary' => $this->getDefaultEmptyCommentary($lang),
                'recommendations' => [],
            ];
        }

        // 2. Extract vendor priority ingredients
        $priorityIngredients = $vendor->getAiWaiterPriorityIngredientsList();

        // 3. Score each product
        $scoredProducts = [];
        $craving = $preferences['craving'] ?? null;
        $occasion = $preferences['occasion'] ?? null;
        $drinkPref = $preferences['drink_preference'] ?? null;
        $dietary = $preferences['dietary'] ?? [];
        $normalizedPrompt = ! empty($prompt) ? mb_strtolower(trim($prompt)) : '';

        foreach ($products as $product) {
            $score = 0;
            $matchedPriorityIngredients = [];

            $nameText = mb_strtolower($product->getTranslatedName($lang).' '.$product->name);
            $descText = mb_strtolower($product->getTranslatedDescription($lang).' '.($product->description ?? ''));
            $catName = mb_strtolower($product->category?->getTranslatedName($lang) ?? ($product->category?->name ?? ''));
            $fullText = $nameText.' '.$descText.' '.$catName;

            // Priority Ingredients Match (+40 points per match)
            foreach ($priorityIngredients as $ingredient) {
                $ingLower = mb_strtolower(trim($ingredient));
                if ($ingLower !== '' && str_contains($fullText, $ingLower)) {
                    $score += 40;
                    $matchedPriorityIngredients[] = $ingredient;
                }
            }

            // Featured Dish Boost (+15 points)
            if ($product->is_featured || ($vendor->featured_product_id === $product->id)) {
                $score += 15;
            }

            // Craving Match
            if ($craving) {
                $score += $this->calculateCravingScore($craving, $fullText, $product);
            }

            // Occasion Match
            if ($occasion) {
                $score += $this->calculateOccasionScore($occasion, $fullText, $product);
            }

            // Dietary Filters
            if (! empty($dietary)) {
                $score += $this->calculateDietaryScore($dietary, $fullText, $product);
            }

            // Freeform prompt keyword matches
            if ($normalizedPrompt !== '') {
                $score += $this->calculatePromptScore($normalizedPrompt, $fullText);
            }

            // Give non-drinks higher priority for main recommendations unless user specifically asked for drinks/dessert
            $isDrinkCategory = $this->isBeverageCategory($catName);
            if ($isDrinkCategory && $craving !== 'drink') {
                $score -= 30; // Drinks are presented primarily through pairings
            }

            $scoredProducts[] = [
                'product' => $product,
                'score' => $score,
                'priority_matches' => array_unique($matchedPriorityIngredients),
            ];
        }

        // 4. Sort by score descending
        usort($scoredProducts, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        // 5. Select top 3 distinct products
        $topItems = array_slice($scoredProducts, 0, 3);
        if (empty($topItems)) {
            $topItems = array_slice($scoredProducts, 0, 1);
        }

        // 6. Build recommendation payloads with reasons & pairings
        $recommendations = [];
        foreach ($topItems as $item) {
            /** @var Product $prod */
            $prod = $item['product'];
            $priorityMatches = $item['priority_matches'];

            $reason = $this->buildRecommendationReason($prod, $priorityMatches, $craving, $occasion, $lang);
            $pairings = $this->findPairingsForProduct($prod, $products, $vendor, $lang, $locationId);

            $recommendations[] = [
                'id' => $prod->id,
                'name' => $prod->getTranslatedName($lang),
                'category_name' => $prod->category?->getTranslatedName($lang) ?? '',
                'description' => $prod->getTranslatedDescription($lang),
                'price' => (float) $prod->getEffectivePrice($locationId),
                'regular_price' => (float) $prod->getRegularPrice($locationId),
                'is_discount_active' => $prod->isDiscountActive(),
                'discount_percentage' => $prod->getDiscountPercentage(),
                'formatted_price' => number_format($prod->getEffectivePrice($locationId)).' '.$vendor->currency,
                'image' => $prod->image ?? 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=600&q=80',
                'dietary_tags' => $prod->dietary_tags ?? [],
                'calories' => $prod->calories,
                'preparation_time_min' => $prod->preparation_time_min,
                'reason' => $reason,
                'pairings' => $pairings,
                'payload' => [
                    'id' => $prod->id,
                    'name' => $prod->getTranslatedName($lang),
                    'image' => $prod->image ?? 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=400&q=80',
                    'description' => $prod->getTranslatedDescription($lang),
                    'base_price' => (float) $prod->getEffectivePrice($locationId),
                    'regular_price' => (float) $prod->getRegularPrice($locationId),
                    'is_discount_active' => $prod->isDiscountActive(),
                    'discount_percentage' => $prod->getDiscountPercentage(),
                    'variations' => $prod->variations->map(function ($v) use ($lang) {
                        return [
                            'id' => $v->id,
                            'name' => $v->getTranslatedName($lang),
                            'price' => (float) $v->getEffectivePrice(),
                            'regular_price' => (float) $v->price,
                            'is_default' => (bool) $v->is_default,
                        ];
                    })->values(),
                ],
            ];
        }

        // 7. Craft warm sommelier commentary
        $commentary = $this->generateSommelierCommentary($vendor, $recommendations, $preferences, $prompt, $lang);

        return [
            'commentary' => $commentary,
            'recommendations' => $recommendations,
        ];
    }

    /**
     * Find best complementary drinks and sides/salads for a given product.
     *
     * @param  Collection<int, Product>  $allProducts
     * @return array<string, mixed>
     */
    public function findPairingsForProduct(
        Product $product,
        $allProducts,
        Vendor $vendor,
        string $lang = 'hy',
        ?int $locationId = null
    ): array {
        $prodName = mb_strtolower($product->name.' '.$product->getTranslatedName($lang));
        $prodDesc = mb_strtolower(($product->description ?? '').' '.$product->getTranslatedDescription($lang));
        $catName = mb_strtolower($product->category?->name ?? '');
        $combined = $prodName.' '.$prodDesc.' '.$catName;

        $pairedDrink = null;
        $pairedSide = null;
        $pairingNote = '';

        // Determine profile
        $isRedMeat = str_contains($combined, 'steak') || str_contains($combined, 'ribeye') || str_contains($combined, 'beef') || str_contains($combined, 'տավար') || str_contains($combined, 'սթեյք') || str_contains($combined, 'говядина') || str_contains($combined, 'стейк');
        $isSeafood = str_contains($combined, 'salmon') || str_contains($combined, 'fish') || str_contains($combined, 'calamari') || str_contains($combined, 'shrimp') || str_contains($combined, 'սաղմոն') || str_contains($combined, 'ձուկ') || str_contains($combined, 'խեցգետին') || str_contains($combined, 'лосось') || str_contains($combined, 'рыба');
        $isDessert = str_contains($combined, 'cake') || str_contains($combined, 'chocolate') || str_contains($combined, 'fondant') || str_contains($combined, 'աղանդեր') || str_contains($combined, 'թխվածք') || str_contains($combined, 'պաղպաղակ') || str_contains($combined, 'десерт');
        $isAppetizer = str_contains($combined, 'hummus') || str_contains($combined, 'pita') || str_contains($combined, 'starter') || str_contains($combined, 'նախուտեստ') || str_contains($combined, 'закуска');

        // Look for companion drinks
        $drinks = $allProducts->filter(function ($p) {
            $c = mb_strtolower($p->category?->name ?? '');

            return $this->isBeverageCategory($c);
        });

        if ($drinks->isNotEmpty()) {
            if ($isRedMeat) {
                // Look for cocktail (old fashioned, bourbon, whiskey) or red wine
                $pairedDrink = $drinks->first(function ($d) {
                    $text = mb_strtolower($d->name.' '.$d->description);

                    return str_contains($text, 'old fashioned') || str_contains($text, 'bourbon') || str_contains($text, 'red') || str_contains($text, 'wine') || str_contains($text, 'գինի');
                }) ?? $drinks->first();

                $pairingNote = match ($lang) {
                    'en' => 'The robust richness of the meat pairs flawlessly with an aged Bourbon cocktail or full-bodied red wine.',
                    'ru' => 'Глубокий мясной вкус идеально раскрывается с выдержанным коктейлем Old Fashioned или бокалом красного вина.',
                    default => 'Մսի հարուստ համը կատարելապես ընդգծվում է հնեցված բուրբոնով կոկտեյլով կամ հարուստ կարմիր գինով:',
                };
            } elseif ($isSeafood) {
                // Look for white wine, spritz, gin tonic, or lemonade
                $pairedDrink = $drinks->first(function ($d) {
                    $text = mb_strtolower($d->name.' '.$d->description);

                    return str_contains($text, 'white') || str_contains($text, 'spritz') || str_contains($text, 'tonic') || str_contains($text, 'lemonade') || str_contains($text, 'գինի');
                }) ?? $drinks->first();

                $pairingNote = match ($lang) {
                    'en' => 'Crisp citrus notes and light beverages beautifully balance the tender seafood textures.',
                    'ru' => 'Освежающие цитрусовые нотки напитка подчеркивают нежную текстуру рыбы и морепродуктов.',
                    default => 'Թարմեցնող ցիտրուսային և նուրբ նոտաները հիանալիորեն համադրվում են նուրբ ծովամթերքի հետ:',
                };
            } elseif ($isDessert) {
                // Look for coffee, espresso or sweet cocktail
                $pairedDrink = $drinks->first(function ($d) {
                    $text = mb_strtolower($d->name.' '.$d->description);

                    return str_contains($text, 'coffee') || str_contains($text, 'espresso') || str_contains($text, 'tea') || str_contains($text, 'սուրճ');
                }) ?? $drinks->first();

                $pairingNote = match ($lang) {
                    'en' => 'A warm aromatic espresso or fine liqueur provides the ultimate sweet finale.',
                    'ru' => 'Ароматный эспрессо или изысканный дижестив создают идеальный сладкий финал.',
                    default => 'Անուշաբույր տաք էսպրեսոն կամ նուրբ ըմպելիքը ստեղծում են կատարյալ քաղցր ավարտ:',
                };
            } else {
                $pairedDrink = $drinks->first();
                $pairingNote = match ($lang) {
                    'en' => 'Specially selected by our sommelier to elevate your dining experience.',
                    'ru' => 'Особый выбор нашего сомелье для идеального гастрономического баланса.',
                    default => 'Մեր մատուցողի հատուկ ընտրությունը՝ ճաշատեսակի համային երանգներն ամբողջացնելու համար:',
                };
            }
        }

        // Look for companion starter / side
        $sides = $allProducts->filter(function ($p) use ($product) {
            return $p->id !== $product->id && ! $this->isBeverageCategory(mb_strtolower($p->category?->name ?? ''));
        });

        if ($isRedMeat || $isSeafood) {
            // Pair with an appetizer or salad
            $pairedSide = $sides->first(function ($s) {
                $c = mb_strtolower($s->category?->name ?? '');

                return str_contains($c, 'starter') || str_contains($c, 'appetizer') || str_contains($c, 'salad') || str_contains($c, 'նախուտեստ');
            });
        } elseif ($isAppetizer) {
            // Pair with a main steak or dish
            $pairedSide = $sides->first(function ($s) {
                $c = mb_strtolower($s->category?->name ?? '');

                return str_contains($c, 'main') || str_contains($c, 'steak');
            });
        }

        return [
            'pairing_note' => $pairingNote,
            'drink' => $pairedDrink ? [
                'id' => $pairedDrink->id,
                'name' => $pairedDrink->getTranslatedName($lang),
                'price' => (float) $pairedDrink->getEffectivePrice($locationId),
                'regular_price' => (float) $pairedDrink->getRegularPrice($locationId),
                'is_discount_active' => $pairedDrink->isDiscountActive(),
                'discount_percentage' => $pairedDrink->getDiscountPercentage(),
                'formatted_price' => number_format($pairedDrink->getEffectivePrice($locationId)).' '.$vendor->currency,
                'image' => $pairedDrink->image ?? 'https://images.unsplash.com/photo-1551024709-8f23befc6f87?w=300&q=80',
                'payload' => [
                    'id' => $pairedDrink->id,
                    'name' => $pairedDrink->getTranslatedName($lang),
                    'image' => $pairedDrink->image,
                    'description' => $pairedDrink->getTranslatedDescription($lang),
                    'base_price' => (float) $pairedDrink->getEffectivePrice($locationId),
                    'regular_price' => (float) $pairedDrink->getRegularPrice($locationId),
                    'is_discount_active' => $pairedDrink->isDiscountActive(),
                    'discount_percentage' => $pairedDrink->getDiscountPercentage(),
                    'variations' => $pairedDrink->variations->map(function ($v) use ($lang) {
                        return [
                            'id' => $v->id,
                            'name' => $v->getTranslatedName($lang),
                            'price' => (float) $v->getEffectivePrice(),
                            'regular_price' => (float) $v->price,
                            'is_default' => (bool) $v->is_default,
                        ];
                    })->values(),
                ],
            ] : null,
            'side' => $pairedSide ? [
                'id' => $pairedSide->id,
                'name' => $pairedSide->getTranslatedName($lang),
                'price' => (float) $pairedSide->getEffectivePrice($locationId),
                'regular_price' => (float) $pairedSide->getRegularPrice($locationId),
                'is_discount_active' => $pairedSide->isDiscountActive(),
                'discount_percentage' => $pairedSide->getDiscountPercentage(),
                'formatted_price' => number_format($pairedSide->getEffectivePrice($locationId)).' '.$vendor->currency,
                'image' => $pairedSide->image ?? 'https://images.unsplash.com/photo-1540420773420-3366772f4999?w=300&q=80',
                'payload' => [
                    'id' => $pairedSide->id,
                    'name' => $pairedSide->getTranslatedName($lang),
                    'image' => $pairedSide->image,
                    'description' => $pairedSide->getTranslatedDescription($lang),
                    'base_price' => (float) $pairedSide->getEffectivePrice($locationId),
                    'regular_price' => (float) $pairedSide->getRegularPrice($locationId),
                    'is_discount_active' => $pairedSide->isDiscountActive(),
                    'discount_percentage' => $pairedSide->getDiscountPercentage(),
                    'variations' => $pairedSide->variations->map(function ($v) use ($lang) {
                        return [
                            'id' => $v->id,
                            'name' => $v->getTranslatedName($lang),
                            'price' => (float) $v->getEffectivePrice(),
                            'regular_price' => (float) $v->price,
                            'is_default' => (bool) $v->is_default,
                        ];
                    })->values(),
                ],
            ] : null,
        ];
    }

    /**
     * Determine if a category is for beverages.
     */
    protected function isBeverageCategory(string $categoryName): bool
    {
        $cat = trim(mb_strtolower($categoryName));
        if ($cat === '') {
            return false;
        }

        $pattern = '/\b(drinks?|beverages?|cocktails?|wines?|beers?|bar|coffee|teas?|խմիչք[ա-ֆ]*|կոկտեյլ[ա-ֆ]*|գինի[ա-ֆ]*|գարեջուր|սուրճ|թեյ|напитк[а-я]*|коктейл[а-я]*|вин[ао][а-я]*|пив[оа]|кофе|чай)\b/ui';

        return (bool) preg_match($pattern, $cat);
    }

    /**
     * Score craving match.
     */
    protected function calculateCravingScore(string $craving, string $text, Product $product): int
    {
        $keywords = match ($craving) {
            'meat' => ['steak', 'ribeye', 'angus', 'beef', 'chicken', 'pork', 'lamb', 'տավար', 'միս', 'սթեյք', 'գառ', 'հավ', 'говядина', 'стейк', 'курица', 'мясо'],
            'seafood' => ['salmon', 'fish', 'calamari', 'shrimp', 'crab', 'seafood', 'ձուկ', 'սաղմոն', 'ծովամթերք', 'խեցգետին', 'рыба', 'лосось', 'морепродукты', 'креветки'],
            'vegetarian' => ['hummus', 'salad', 'vegan', 'vegetarian', 'pasta', 'cheese', 'բուսական', 'վեգան', 'աղցան', 'բանջարեղեն', 'веган', 'вегетарианский', 'салат'],
            'dessert' => ['cake', 'dessert', 'lava', 'fondant', 'sweet', 'chocolate', 'pistachio', 'աղանդեր', 'թխվածք', 'պաղպաղակ', 'քաղցր', 'десерт', 'торт', 'шоколад'],
            default => [],
        };

        $score = 0;
        foreach ($keywords as $kw) {
            if (str_contains($text, $kw)) {
                $score += 35;
                break;
            }
        }

        return $score;
    }

    /**
     * Score occasion match.
     */
    protected function calculateOccasionScore(string $occasion, string $text, Product $product): int
    {
        return match ($occasion) {
            'romantic' => (str_contains($text, 'steak') || str_contains($text, 'salmon') || str_contains($text, 'wine') || str_contains($text, 'cake') || $product->is_featured) ? 25 : 5,
            'quick_lunch' => (str_contains($text, 'hummus') || str_contains($text, 'calamari') || ($product->preparation_time_min && $product->preparation_time_min <= 20)) ? 25 : 5,
            'family' => (str_contains($text, 'hummus') || str_contains($text, 'pita') || str_contains($text, 'steak')) ? 20 : 5,
            'celebration' => ($product->is_featured || $product->price > 4000) ? 25 : 5,
            default => 10,
        };
    }

    /**
     * Score dietary match.
     *
     * @param  array<int, string>  $dietary
     */
    protected function calculateDietaryScore(array $dietary, string $text, Product $product): int
    {
        $score = 0;
        foreach ($dietary as $tag) {
            if ($tag === 'vegan' && (! empty($product->dietary_tags) && in_array('vegan', $product->dietary_tags))) {
                $score += 30;
            }
            if ($tag === 'gluten_free' && (! empty($product->dietary_tags) && in_array('gluten_free', $product->dietary_tags))) {
                $score += 30;
            }
            if ($tag === 'low_calorie' && $product->calories && $product->calories < 500) {
                $score += 25;
            }
        }

        return $score;
    }

    /**
     * Score prompt match based on words.
     */
    protected function calculatePromptScore(string $prompt, string $text): int
    {
        $words = preg_split('/[\s,\.\!\?]+/', $prompt);
        $matched = 0;
        foreach ($words ?: [] as $w) {
            $w = trim($w);
            if (mb_strlen($w) >= 3 && str_contains($text, $w)) {
                $matched += 20;
            }
        }

        return $matched;
    }

    /**
     * Build recommendation explanation reason.
     *
     * @param  array<int, string>  $priorityMatches
     */
    protected function buildRecommendationReason(
        Product $product,
        array $priorityMatches,
        ?string $craving,
        ?string $occasion,
        string $lang
    ): string {
        if (! empty($priorityMatches)) {
            $ingText = implode(', ', $priorityMatches);

            return match ($lang) {
                'en' => "Features our chef's signature ingredient: {$ingText}, crafted to perfection.",
                'ru' => "Приготовлено с использованием нашего фирменного ингредиента: {$ingText}.",
                default => "Պարունակում է մեր ֆիրմային առաջնահերթ բաղադրիչը՝ «{$ingText}», որը պատրաստված է շեֆ-խոհարարի հատուկ բաղադրատոմսով:",
            };
        }

        return match ($lang) {
            'en' => 'One of our most celebrated dishes, balancing vibrant flavors and premium ingredients.',
            'ru' => 'Одно из самых изысканных блюд нашего ресторана с неповторимой гармонией вкусов.',
            default => 'Մեր մենյուի ամենասիրված և բարձր գնահատված ուտեստներից մեկը՝ կատարյալ համերի համադրությամբ:',
        };
    }

    /**
     * Generate narrative commentary from AI Sommelier.
     *
     * @param  array<int, mixed>  $recommendations
     * @param  array<string, mixed>  $preferences
     */
    protected function generateSommelierCommentary(
        Vendor $vendor,
        array $recommendations,
        array $preferences,
        ?string $prompt,
        string $lang
    ): string {
        $waiterName = $vendor->getAiWaiterName();

        // Check if AI generation is enabled via vendor configured provider
        if (! empty($recommendations)) {
            try {
                $dishNames = implode(', ', array_column($recommendations, 'name'));
                $promptText = "You are {$waiterName}, a friendly and ultra-sophisticated AI waiter & sommelier at '{$vendor->name}'. Write a short (2-3 sentences), warm, appetizing and enthusiastic recommendation directly to the guest in the requested language ({$lang}). Explain why the selected dishes ({$dishNames}) are the perfect choice for their taste and occasion. Keep it elegant, concise, and without any markdown bullet points.";

                $gateway = app(AiGatewayService::class);
                $aiText = $gateway->generateText($vendor, $promptText, ['timeout' => 5]);
                if (! empty($aiText)) {
                    return $aiText;
                }
            } catch (\Throwable $e) {
                Log::info('AI waiter commentary skipped, using expert template: '.$e->getMessage());
            }
        }

        // Sophisticated Rule-Based Narrative Commentary Fallback
        $dishNames = ! empty($recommendations) ? implode(' և ', array_column(array_slice($recommendations, 0, 2), 'name')) : '';

        return match ($lang) {
            'en' => "Hello! I am {$waiterName}, your personal dining advisor. Based on your preferences, I have hand-picked our standout specialties: {$dishNames}. Each dish is paired with the finest drinks to create an unforgettable gastronomic journey for you!",
            'ru' => "Здравствуйте! Я {$waiterName}, ваш персональный гастрономический консультант. Основываясь на ваших пожеланиях, я подобрал лучшие блюда: {$dishNames}. Каждое из них дополнено идеально гармонирующими напитками для великолепного вечера!",
            default => "Ողջույն! Ես {$waiterName}-ն եմ՝ Ձեր անձնական խոհարարական խորհրդատուն: Ելնելով Ձեր նախասիրություններից՝ ընտրել եմ մեր մենյուի լավագույն ճաշատեսակները՝ «{$dishNames}»: Յուրաքանչյուր ուտեստի հետ պատրաստել եմ նաև համահունչ ըմպելիքների զուգորդումներ, որոնք կդարձնեն Ձեր այցը անմոռանալի:",
        };
    }

    /**
     * Default empty commentary.
     */
    protected function getDefaultEmptyCommentary(string $lang): string
    {
        return match ($lang) {
            'en' => 'Welcome! Please browse our curated menu or adjust your search to discover our specialties.',
            'ru' => 'Добро пожаловать! Ознакомьтесь с нашим меню или измените фильтры, чтобы найти подходящие блюда.',
            default => 'Բարի գալուստ! Խնդրում ենք ծանոթանալ մեր մենյուին կամ փոփոխել նախասիրությունները՝ լավագույն առաջարկները տեսնելու համար:',
        };
    }
}
