@auth
    {{-- Pulsante di attivazione delle notifiche push (webpush.js espone window.registerWebPush) --}}
    <div id="webpush-toggle" style="display:none; position:fixed; bottom:1rem; right:1rem; z-index:60;">
        <button
            type="button"
            id="webpush-toggle-btn"
            style="display:inline-flex; align-items:center; gap:.4rem; padding:.55rem .9rem; border-radius:.6rem;
                   background:#1e293b; color:#fff; font-size:.85rem; font-weight:600; cursor:pointer;
                   box-shadow:0 6px 18px rgba(0,0,0,.25); border:0;"
        >
            🔔 Attiva notifiche
        </button>
    </div>

    <script>
        (function () {
            const container = document.getElementById('webpush-toggle');
            const button = document.getElementById('webpush-toggle-btn');

            if (!container || !button) {
                return;
            }

            const setState = (text, disabled) => {
                button.textContent = text;
                button.disabled = !!disabled;
                button.style.opacity = disabled ? '0.7' : '1';
                button.style.cursor = disabled ? 'default' : 'pointer';
            };

            const refresh = async () => {
                if (typeof window.checkWebPushStatus !== 'function') {
                    return false;
                }

                const status = await window.checkWebPushStatus();

                if (!status.supported) {
                    container.style.display = 'none';
                    return true;
                }

                if (status.subscribed) {
                    container.style.display = 'block';
                    setState('✅ Notifiche attive', true);
                } else {
                    container.style.display = 'block';
                    setState('🔔 Attiva notifiche', false);
                }

                return true;
            };

            button.addEventListener('click', async () => {
                if (typeof window.registerWebPush !== 'function') {
                    return;
                }

                setState('Attivazione…', true);
                const result = await window.registerWebPush();

                if (result.success) {
                    setState('✅ Notifiche attive', true);
                } else {
                    setState('🔔 Attiva notifiche', false);
                    alert(result.message);
                }
            });

            const boot = async () => {
                if (await refresh()) {
                    return;
                }

                window.addEventListener('webpush:ready', refresh);

                let attempts = 0;
                const timer = setInterval(async () => {
                    attempts++;
                    if ((await refresh()) || attempts > 20) {
                        clearInterval(timer);
                    }
                }, 500);
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', boot);
            } else {
                boot();
            }
        })();
    </script>
@endauth
