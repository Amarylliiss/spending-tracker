<template>
    <div class="guest">
        <section class="guest-art">
            <div class="brand" style="padding: 0;">
                <span class="brand-mark" style="background: white; color: var(--color-forest);">zł</span>
                Spendings
            </div>
            <div>
                <h1>Wiesz, ile wydajesz, zanim skończy się miesiąc.</h1>
                <div class="guest-preview">
                    <div>
                        <div style="display: flex; justify-content: space-between;"><span>Jedzenie</span><b>742 zł / 900 zł</b></div>
                        <div class="bar"><i style="width: 82%; background: #f3c96b;" /></div>
                    </div>
                    <div>
                        <div style="display: flex; justify-content: space-between;"><span>Transport</span><b>186 zł / 400 zł</b></div>
                        <div class="bar"><i style="width: 46%; background: white;" /></div>
                    </div>
                    <div>
                        <div style="display: flex; justify-content: space-between;"><span>Rozrywka</span><b>171 zł / 150 zł</b></div>
                        <div class="bar"><i style="width: 100%; background: #f08a80;" /></div>
                    </div>
                </div>
            </div>
            <p style="margin: 0; opacity: 0.75;">Reguły same przypiszą kategorię, a alert przyjdzie, zanim przekroczysz limit.</p>
        </section>
        <section class="guest-form">
            <slot />
        </section>
    </div>
</template>
