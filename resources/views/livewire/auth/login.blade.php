<div class="grid min-h-screen place-items-center bg-slate-950 px-4">
    <div class="w-full max-w-sm">
        <div class="mb-8 text-center">
            <span class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-emerald-500 text-2xl font-black text-slate-950">B</span>
            <h1 class="mt-4 text-2xl font-black text-white">BizBite</h1>
            <p class="text-sm text-slate-400">Sign in to your billing console</p>
        </div>

        <form wire:submit="authenticate" class="space-y-4 rounded-2xl border border-slate-800 bg-slate-900 p-6">
            <div>
                <label for="email" class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-400">Email</label>
                <input id="email" type="email" wire:model="email" autofocus autocomplete="username"
                       class="w-full rounded-lg border border-slate-700 bg-slate-800 px-4 py-2.5 text-sm text-white outline-none focus:border-emerald-500" />
                @error('email') <p class="mt-1 text-xs font-semibold text-red-400">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-400">Password</label>
                <input id="password" type="password" wire:model="password" autocomplete="current-password"
                       class="w-full rounded-lg border border-slate-700 bg-slate-800 px-4 py-2.5 text-sm text-white outline-none focus:border-emerald-500" />
                @error('password') <p class="mt-1 text-xs font-semibold text-red-400">{{ $message }}</p> @enderror
            </div>

            <label class="flex items-center gap-2 text-xs text-slate-400">
                <input type="checkbox" wire:model="remember" class="rounded border-slate-600 bg-slate-800 text-emerald-500" />
                Keep me signed in on this device
            </label>

            <button type="submit" wire:loading.attr="disabled"
                    class="w-full rounded-lg bg-emerald-500 py-2.5 text-sm font-black uppercase tracking-widest text-slate-950 transition hover:bg-emerald-400 disabled:opacity-50">
                Sign In
            </button>
        </form>

        <p class="mt-6 text-center text-xs text-slate-600">BizBite Micro-Billing Engine · by BizaroHQ</p>
    </div>
</div>
