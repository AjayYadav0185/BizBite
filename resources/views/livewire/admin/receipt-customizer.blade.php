<div class="max-w-3xl space-y-6">
    <div>
        <h1 class="text-2xl font-black text-slate-900">Receipt Customizer</h1>
        <p class="text-sm text-slate-500">These lines print on every thermal bill. The preview updates as you type.</p>
    </div>

    <div class="grid gap-6 md:grid-cols-2">
        {{-- -------------------------------------------- FORM --}}
        <section class="rounded-2xl border border-card-border bg-white p-4 shadow-card sm:p-5">
            <h2 class="mb-4 text-sm font-black uppercase tracking-widest text-slate-500">Templates</h2>

            <label class="mb-1 block text-xs font-bold text-slate-600">Receipt Header (top line)</label>
            <input
                type="text"
                wire:model.live="printHeader"
                placeholder="e.g. WELCOME TO BIZBITE"
                class="w-full rounded-xl border border-card-border px-4 py-2.5 text-sm outline-none transition focus:border-brand-500 @error('printHeader') border-red-400 @enderror"
            />
            @error('printHeader')
                <p class="mt-1 text-xs font-semibold text-red-500">{{ $message }}</p>
            @enderror

            <label class="mb-1 mt-4 block text-xs font-bold text-slate-600">Receipt Footer (bottom line)</label>
            <input
                type="text"
                wire:model.live="printFooter"
                placeholder="e.g. Thank you — visit again!"
                class="w-full rounded-xl border border-card-border px-4 py-2.5 text-sm outline-none transition focus:border-brand-500 @error('printFooter') border-red-400 @enderror"
            />
            @error('printFooter')
                <p class="mt-1 text-xs font-semibold text-red-500">{{ $message }}</p>
            @enderror

            <div class="mt-5 flex flex-wrap items-center gap-3">
                <button wire:click="save" wire:loading.attr="disabled"
                        class="rounded-xl bg-brand-500 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-brand-400 disabled:opacity-50 shrink-0">
                    Save Templates
                </button>
                @if ($saved)
                    <span class="text-sm font-bold text-brand-600" role="status">✓ Saved — all future bills use these lines.</span>
                @endif
            </div>
        </section>

        {{-- -------------------------------------------- LIVE PREVIEW --}}
        <section class="rounded-2xl border border-card-border bg-surface-muted p-4 sm:p-5">
            <h2 class="mb-3 text-sm font-black uppercase tracking-widest text-slate-500">Live 80mm Preview</h2>
            <div class="w-full overflow-x-auto rounded-xl border border-dashed border-border-muted bg-white p-4 font-mono text-[11px] leading-relaxed text-slate-900">
                <div class="mx-auto w-64 max-w-full">
                    @if (filled($printHeader))
                        <p class="text-center font-bold uppercase">{{ $printHeader }}</p>
                    @endif
                    <p class="text-center text-[13px] font-black uppercase">{{ $store->name }}</p>
                    <p class="text-center">{{ $store->address }}</p>
                    <p class="text-center">Ph: {{ $store->phone }}</p>
                    <p>-------------------------------</p>
                    <p>Bill: 001-20260908-0001</p>
                    <p>-------------------------------</p>
                    <p class="flex justify-between"><span>2 x Vada Pav</span><span>24.00</span></p>
                    <p class="flex justify-between"><span>1 x Chai</span><span>10.00</span></p>
                    <p>-------------------------------</p>
                    <p class="flex justify-between font-bold"><span>TOTAL</span><span>Rs. 34.00</span></p>
                    <p>-------------------------------</p>
                    <p class="text-center">{{ filled($printFooter) ? $printFooter : 'Thank you!' }}</p>
                </div>
            </div>
        </section>
    </div>
</div>
