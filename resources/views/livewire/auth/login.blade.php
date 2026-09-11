<div class="grid min-h-screen place-items-center bg-gradient-to-br from-grad-start via-canvas to-grad-end px-4">
    <!-- Soft emerald page gradient (theme AppGradients.page) -->
    <div class="pointer-events-none absolute inset-0 bg-gradient-to-br from-brand-500/60 via-transparent to-teal-600/40 opacity-[0.07]" aria-hidden="true"></div>

    <div class="w-full max-w-sm">
        <div class="mb-8 text-center">
            {{-- Brand circle — theme AuthBrandHeader (brandMain gradient) --}}
            <span class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-gradient-to-br from-brand-500 to-brand-600 text-2xl font-black text-white shadow-card">B</span>
            <h1 class="mt-4 text-2xl font-black text-slate-900">BizBite</h1>
            <p class="text-sm text-slate-500">Sign in to your billing console</p>
        </div>

        <form wire:submit="authenticate" class="space-y-4 rounded-2xl border border-card-border bg-white p-6 shadow-card-hover">
            <div>
                <label for="email" class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Email</label>
                <input id="email" type="email" wire:model="email" autofocus autocomplete="username"
                       class="w-full rounded-xl border border-card-border bg-canvas-soft px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-brand-500 @error('email') border-red-400 @enderror" />
                @error('email') <p class="mt-1 text-xs font-semibold text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Password</label>
                <input id="password" type="password" wire:model="password" autocomplete="current-password"
                       class="w-full rounded-xl border border-card-border bg-canvas-soft px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-brand-500 @error('password') border-red-400 @enderror" />
                @error('password') <p class="mt-1 text-xs font-semibold text-red-500">{{ $message }}</p> @enderror
            </div>

            <label class="flex items-center gap-2 text-xs text-slate-600">
                <input type="checkbox" wire:model="remember" class="rounded border-border-muted bg-white text-brand-600" />
                Keep me signed in on this device
            </label>

            <button type="submit" wire:loading.attr="disabled"
                    class="w-full rounded-xl bg-brand-500 py-2.5 text-sm font-black uppercase tracking-widest text-slate-950 transition hover:bg-brand-400 disabled:opacity-50">
                Sign In
            </button>
        </form>

        <p class="mt-6 text-center text-xs text-slate-500">BizBite Micro-Billing Engine · by BizaroHQ</p>
    </div>
</div>
