@extends('layouts.app')

@section('title', 'Live Kitchen Orders - ' . $vendor->name)

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-family: 'Outfit', sans-serif; font-size: 1.75rem; font-weight: 700; display: flex; align-items: center; gap: 0.6rem;">
            <i class="fa-solid fa-bell-concierge" style="color: var(--primary);"></i> Խոհանոցի Պատվերների Վահանակ
        </h1>
        <p style="color: var(--text-muted); font-size: 0.9rem;">
            Իրական ժամանակի պատվերներ սեղաններից և օնլայն <strong>{{ $location?->name ?? 'Բոլոր մասնաճյուղեր' }}</strong>-ի համար։
        </p>
    </div>

    <!-- Live Controls: Connection Status & Sound Toggle -->
    <div style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
        <!-- Live Status Pill -->
        <div id="liveStatusPill" style="display: flex; gap: 0.5rem; align-items: center; background: rgba(16, 185, 129, 0.15); color: #10b981; padding: 0.5rem 1rem; border-radius: 9999px; font-size: 0.85rem; font-weight: 700; border: 1px solid rgba(16, 185, 129, 0.3);">
            <span class="pulse-dot" style="width: 8px; height: 8px; background: #10b981; border-radius: 50%; display: inline-block;"></span>
            <span id="liveStatusText">Ուղիղ կապ ակտիվ է</span>
        </div>

        <!-- Audio Toggle Button -->
        <button id="soundToggleBtn" onclick="toggleKitchenSound()" class="btn btn-secondary" style="padding: 0.5rem 0.9rem; font-size: 0.85rem; display: flex; align-items: center; gap: 0.45rem;">
            <i id="soundIcon" class="fa-solid fa-volume-high" style="color: var(--primary);"></i>
            <span id="soundText">Ձայնը միացված է</span>
        </button>

        <!-- Test Sound Button -->
        <button onclick="playKitchenChime(true)" class="btn btn-secondary" style="padding: 0.5rem 0.75rem; font-size: 0.85rem;" title="Ստուգել ծանուցման ձայնը">
            <i class="fa-solid fa-bell" style="color: #f59e0b;"></i>
        </button>
    </div>
</div>

<!-- New Order Alert Banner (Hidden by default, flashes when new order arrives) -->
<div id="newOrderBanner" style="display: none; background: linear-gradient(135deg, #ef4444, #f97316); color: #ffffff; padding: 0.9rem 1.25rem; border-radius: 12px; margin-bottom: 1.5rem; box-shadow: 0 10px 25px rgba(239, 68, 68, 0.4); align-items: center; justify-content: space-between; animation: slideDown 0.4s ease;">
    <div style="display: flex; align-items: center; gap: 0.75rem;">
        <span style="font-size: 1.5rem; animation: ringBell 0.8s infinite alternate;">🔔</span>
        <div>
            <strong style="font-size: 1.05rem; font-family: 'Outfit';">ՆՈՐ ՊԱՏՎԵՐ ՍՏԱՑՎԵՑ!</strong>
            <div style="font-size: 0.85rem; opacity: 0.95;" id="newOrderBannerText">Ստացվել է նոր պատվեր սեղանից։</div>
        </div>
    </div>
    <button onclick="dismissNewOrderBanner()" style="background: rgba(255,255,255,0.25); border: none; color: #fff; padding: 0.35rem 0.75rem; border-radius: 8px; font-weight: 700; cursor: pointer;">
        Լավ
    </button>
</div>

<!-- Filters Bar -->
<div class="card" style="padding: 0.75rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
        <a href="?status=all" class="btn {{ request('status', 'all') == 'all' ? 'btn-primary' : 'btn-secondary' }}" style="padding: 0.4rem 0.85rem; font-size: 0.8rem;">
            Բոլորը (All)
        </a>
        <a href="?status=pending" class="btn {{ request('status') == 'pending' ? 'btn-primary' : 'btn-secondary' }}" style="padding: 0.4rem 0.85rem; font-size: 0.8rem;">
            ⏳ Սպասող (Pending)
            @if(isset($pendingCount) && $pendingCount > 0)
                <span style="background: #ef4444; color: #fff; padding: 0.1rem 0.4rem; border-radius: 9999px; font-size: 0.7rem; margin-left: 0.2rem;">{{ $pendingCount }}</span>
            @endif
        </a>
        <a href="?status=preparing" class="btn {{ request('status') == 'preparing' ? 'btn-primary' : 'btn-secondary' }}" style="padding: 0.4rem 0.85rem; font-size: 0.8rem;">
            🔥 Պատրաստվում է (Preparing)
        </a>
        <a href="?status=ready" class="btn {{ request('status') == 'ready' ? 'btn-primary' : 'btn-secondary' }}" style="padding: 0.4rem 0.85rem; font-size: 0.8rem;">
            🔔 Պատրաստ է (Ready)
        </a>
        <a href="?status=completed" class="btn {{ request('status') == 'completed' ? 'btn-primary' : 'btn-secondary' }}" style="padding: 0.4rem 0.85rem; font-size: 0.8rem;">
            ✅ Ավարտված (Completed)
        </a>
    </div>

    <div style="font-size: 0.8rem; color: var(--text-muted); display: flex; align-items: center; gap: 0.4rem;">
        <i class="fa-solid fa-rotate text-xs"></i>
        <span>Ինքնաթարմացում՝ <strong id="pollCounter">4</strong>վ</span>
    </div>
</div>

<!-- Orders Grid Container -->
<div id="ordersContainer">
    @include('admin.orders.partials.order_cards', ['orders' => $orders, 'vendor' => $vendor])
</div>

<style>
@keyframes pulse {
    0% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.08); opacity: 0.85; }
    100% { transform: scale(1); opacity: 1; }
}
@keyframes ringBell {
    0% { transform: rotate(-15deg); }
    100% { transform: rotate(15deg); }
}
@keyframes slideDown {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}
.pulse-dot {
    animation: pulse 1.5s infinite;
}
</style>

<script>
// Sound Settings (Saved in LocalStorage)
let isSoundEnabled = localStorage.getItem('kitchen_sound_enabled') !== 'false';
let lastOrderId = {{ $orders->first()?->id ?? 0 }};
let pollInterval = 4000; // 4 seconds
let pollSecondsRemaining = 4;
let isPolling = false;
let audioCtx = null;

// Initialize Sound Button
function updateSoundUI() {
    const icon = document.getElementById('soundIcon');
    const text = document.getElementById('soundText');
    if (isSoundEnabled) {
        icon.className = 'fa-solid fa-volume-high';
        icon.style.color = '#10b981';
        text.innerText = 'Ձայնը միացված է';
    } else {
        icon.className = 'fa-solid fa-volume-xmark';
        icon.style.color = '#ef4444';
        text.innerText = 'Ձայնն անջատված է';
    }
}
updateSoundUI();

function toggleKitchenSound() {
    isSoundEnabled = !isSoundEnabled;
    localStorage.setItem('kitchen_sound_enabled', isSoundEnabled);
    updateSoundUI();
    if (isSoundEnabled) {
        playKitchenChime(true);
    }
}

// Crisp Restaurant Bell Chime using Web Audio API (No external file needed)
function playKitchenChime(force = false) {
    if (!isSoundEnabled && !force) return;

    try {
        const AudioContextClass = window.AudioContext || window.webkitAudioContext;
        if (!AudioContextClass) return;

        if (!audioCtx) {
            audioCtx = new AudioContextClass();
        }
        if (audioCtx.state === 'suspended') {
            audioCtx.resume();
        }

        const now = audioCtx.currentTime;

        // Tone 1: High crisp chime (E5 - 659.25Hz)
        const osc1 = audioCtx.createOscillator();
        const gain1 = audioCtx.createGain();
        osc1.type = 'sine';
        osc1.frequency.setValueAtTime(659.25, now);
        gain1.gain.setValueAtTime(0.4, now);
        gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.9);
        osc1.connect(gain1);
        gain1.connect(audioCtx.destination);
        osc1.start(now);
        osc1.stop(now + 0.9);

        // Tone 2: Harmonic Brass Ding (B5 - 987.77Hz)
        const osc2 = audioCtx.createOscillator();
        const gain2 = audioCtx.createGain();
        osc2.type = 'triangle';
        osc2.frequency.setValueAtTime(987.77, now + 0.12);
        gain2.gain.setValueAtTime(0.5, now + 0.12);
        gain2.gain.exponentialRampToValueAtTime(0.001, now + 1.4);
        osc2.connect(gain2);
        gain2.connect(audioCtx.destination);
        osc2.start(now + 0.12);
        osc2.stop(now + 1.4);

        // Tone 3: Rich Octave resonance (E6 - 1318.5Hz)
        const osc3 = audioCtx.createOscillator();
        const gain3 = audioCtx.createGain();
        osc3.type = 'sine';
        osc3.frequency.setValueAtTime(1318.5, now + 0.25);
        gain3.gain.setValueAtTime(0.35, now + 0.25);
        gain3.gain.exponentialRampToValueAtTime(0.001, now + 1.6);
        osc3.connect(gain3);
        gain3.connect(audioCtx.destination);
        osc3.start(now + 0.25);
        osc3.stop(now + 1.6);

    } catch (e) {
        console.warn('Audio Context notification failed:', e);
    }
}

// Unlock audio on first user click anywhere on page
document.addEventListener('click', function unlockAudio() {
    if (!audioCtx) {
        const AudioContextClass = window.AudioContext || window.webkitAudioContext;
        if (AudioContextClass) audioCtx = new AudioContextClass();
    }
    if (audioCtx && audioCtx.state === 'suspended') {
        audioCtx.resume();
    }
}, { once: true });

function dismissNewOrderBanner() {
    document.getElementById('newOrderBanner').style.display = 'none';
}

// Fetch Live Orders via AJAX Feed
async function fetchKitchenFeed() {
    if (isPolling) return;
    isPolling = true;

    try {
        const currentUrl = new URL(window.location.href);
        const status = currentUrl.searchParams.get('status') || 'all';
        const feedUrl = `{{ route('admin.orders.feed') }}?status=${encodeURIComponent(status)}&last_order_id=${lastOrderId}`;

        const res = await fetch(feedUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });

        if (res.ok) {
            const data = await res.json();
            if (data.success) {
                // If new order arrived
                if (data.has_new && data.latest_order_id > lastOrderId) {
                    playKitchenChime();

                    // Show Banner
                    const banner = document.getElementById('newOrderBanner');
                    const bannerText = document.getElementById('newOrderBannerText');
                    bannerText.innerText = `Ստացվել է նոր պատվեր (ընդհանուր սպասող՝ ${data.pending_count})։`;
                    banner.style.display = 'flex';

                    // Update Title with Alert
                    document.title = `(1) 🔔 ՆՈՐ ՊԱՏՎԵՐ! - {{ $vendor->name }}`;
                    setTimeout(() => {
                        document.title = 'Live Kitchen Orders - {{ $vendor->name }}';
                    }, 8000);
                }

                // Update container with rendered HTML
                document.getElementById('ordersContainer').innerHTML = data.html;
                lastOrderId = Math.max(lastOrderId, data.latest_order_id);

                // Update Connection Pill
                document.getElementById('liveStatusText').innerText = `Ուղիղ կապ (թարմացվել է ${new Date().toLocaleTimeString()})`;
            }
        }
    } catch (err) {
        console.error('Kitchen live feed error:', err);
        document.getElementById('liveStatusText').innerText = 'Կապի խնդիր (կրկին փորձ)';
    } finally {
        isPolling = false;
        pollSecondsRemaining = 4;
    }
}

// Status update with AJAX
async function changeOrderStatus(orderId, newStatus) {
    try {
        const res = await fetch(`/admin/orders/${orderId}/status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({ status: newStatus })
        });

        if (res.ok) {
            // Silently refresh the list
            fetchKitchenFeed();
        } else {
            alert('Չհաջողվեց թարմացնել պատվերի կարգավիճակը:');
        }
    } catch (e) {
        console.error('Order status update error:', e);
    }
}

// 4-second Polling Timer
setInterval(() => {
    pollSecondsRemaining--;
    if (pollSecondsRemaining <= 0) {
        document.getElementById('pollCounter').innerText = '...';
        fetchKitchenFeed();
    } else {
        document.getElementById('pollCounter').innerText = pollSecondsRemaining;
    }
}, 1000);
</script>
@endsection
