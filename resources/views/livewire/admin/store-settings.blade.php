@php
    $field = 'w-full rounded-lg border border-line bg-surface px-3 py-2 text-sm text-ink placeholder:text-ink-3 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20';
    $label = 'mb-1 block text-xs font-semibold text-ink-2';
    $err = fn ($k) => $errors->has($k) ? 'border-red-400' : '';
@endphp
<div class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="font-display text-2xl font-semibold">Store &amp; billing settings</h1>
            <p class="text-sm text-ink-3">Details printed on every bill. GSTIN and FSSAI are legally required on restaurant bills in India.</p>
        </div>
        <div class="flex items-center gap-3">
            @if ($saved)
                <span class="flex items-center gap-1 text-sm font-semibold text-emerald-600 dark:text-emerald-400" role="status"><x-icon name="check" class="h-4 w-4" />Saved</span>
            @endif
            <button wire:click="save" wire:loading.attr="disabled" class="rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 disabled:opacity-50">Save settings</button>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[1fr_340px]">
        <div class="space-y-6">
            <section class="rounded-2xl border border-line bg-surface p-5 shadow-card">
                <h2 class="mb-4 flex items-center gap-2 font-display text-base font-semibold"><x-icon name="store" class="h-5 w-5 text-brand-ink" />Business details</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="sm:col-span-2"><span class="{{ $label }}">Trade name (as on bill) *</span>
                        <input type="text" wire:model.live.debounce.500ms="name" class="{{ $field }} {{ $err('name') }}" />
                        @error('name')<span class="mt-1 block text-xs text-red-500">{{ $message }}</span>@enderror</label>
                    <label class="sm:col-span-2"><span class="{{ $label }}">Address</span>
                        <input type="text" wire:model.live.debounce.500ms="address" placeholder="Shop no., market, locality" class="{{ $field }}" /></label>
                    <label><span class="{{ $label }}">City</span>
                        <input type="text" wire:model.live.debounce.500ms="city" placeholder="New Delhi / Gurugram / Noida" class="{{ $field }}" /></label>
                    <label><span class="{{ $label }}">State</span>
                        <select wire:model.live="state" class="{{ $field }}">
                            <option value="">Select state</option>
                            @foreach ($states as $code => $stateName)
                                <option value="{{ $stateName }}">{{ $stateName }}</option>
                            @endforeach
                        </select></label>
                    <label><span class="{{ $label }}">PIN code</span>
                        <input type="text" inputmode="numeric" maxlength="6" wire:model.live.debounce.500ms="pincode" class="{{ $field }} {{ $err('pincode') }}" />
                        @error('pincode')<span class="mt-1 block text-xs text-red-500">{{ $message }}</span>@enderror</label>
                    <label><span class="{{ $label }}">Phone</span>
                        <input type="tel" wire:model.live.debounce.500ms="phone" placeholder="+91 98xxx xxxxx" class="{{ $field }}" /></label>
                </div>
            </section>

            <section class="rounded-2xl border border-line bg-surface p-5 shadow-card">
                <h2 class="mb-4 flex items-center gap-2 font-display text-base font-semibold"><x-icon name="shield" class="h-5 w-5 text-brand-ink" />Tax &amp; licences</h2>
                <label class="mb-4 flex cursor-pointer items-start gap-3 rounded-xl border border-line bg-surface-2 p-3">
                    <input type="checkbox" wire:model.live="gstEnabled" class="mt-0.5 h-4 w-4 rounded border-line-strong text-brand-600 focus:ring-brand-500" />
                    <span>
                        <span class="block text-sm font-semibold">GST registered — print TAX INVOICE</span>
                        <span class="block text-xs text-ink-3">Shows GSTIN and CGST/SGST per slab (SAC 996331). Turn off if you are not GST registered: bills print as "Bill of Supply" without tax lines.</span>
                    </span>
                </label>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label><span class="{{ $label }}">GSTIN {{ $gstEnabled ? '*' : '' }}</span>
                        <input type="text" maxlength="15" wire:model.live.debounce.600ms="gstin" placeholder="07ABCDE1234F1Z2" class="{{ $field }} {{ $err('gstin') }} font-mono uppercase" />
                        @error('gstin')<span class="mt-1 block text-xs text-red-500">{{ $message }}</span>@enderror
                        @if (! $errors->has('gstin') && filled($gstin) && \App\Support\India::isValidGstin($gstin))
                            <span class="mt-1 block text-xs text-emerald-600 dark:text-emerald-400">✓ Valid · {{ \App\Support\India::stateFromGstin($gstin) }}</span>
                        @endif
                    </label>
                    <label><span class="{{ $label }}">FSSAI licence no.</span>
                        <input type="text" inputmode="numeric" maxlength="14" wire:model.live.debounce.600ms="fssai" placeholder="14 digits" class="{{ $field }} {{ $err('fssai') }} font-mono" />
                        @error('fssai')<span class="mt-1 block text-xs text-red-500">{{ $message }}</span>@enderror</label>
                </div>
            </section>

            <section class="rounded-2xl border border-line bg-surface p-5 shadow-card">
                <h2 class="mb-4 flex items-center gap-2 font-display text-base font-semibold"><x-icon name="qr" class="h-5 w-5 text-brand-ink" />UPI payments</h2>
                <label class="block"><span class="{{ $label }}">UPI ID (VPA) for the counter QR</span>
                    <input type="text" wire:model.live.debounce.600ms="upiVpa" placeholder="yourshop@okaxis" class="{{ $field }} {{ $err('upiVpa') }}" />
                    @error('upiVpa')<span class="mt-1 block text-xs text-red-500">{{ $message }}</span>@enderror
                    <span class="mt-1 block text-xs text-ink-3">When the cashier picks UPI, the POS shows a QR with the exact bill amount — works with PhonePe, Google Pay, Paytm and BHIM.</span>
                </label>
            </section>

            <section class="rounded-2xl border border-line bg-surface p-5 shadow-card">
                <h2 class="mb-4 flex items-center gap-2 font-display text-base font-semibold"><x-icon name="receipt" class="h-5 w-5 text-brand-ink" />Receipt lines</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label><span class="{{ $label }}">Header (top line)</span>
                        <input type="text" maxlength="120" wire:model.live.debounce.400ms="printHeader" placeholder="e.g. Pure Veg · Since 1998" class="{{ $field }}" /></label>
                    <label><span class="{{ $label }}">Footer (bottom line)</span>
                        <input type="text" maxlength="120" wire:model.live.debounce.400ms="printFooter" placeholder="Thank you! Visit again." class="{{ $field }}" /></label>
                </div>
            </section>
        </div>

        <aside class="xl:sticky xl:top-6 xl:self-start">
            <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-ink-3">Live 80mm preview</p>
            <div class="rounded-2xl border border-line bg-surface-3 p-4">
                <div class="rounded-md p-3 shadow-card" style="background:#fff">
                    @include('partials.receipt', ['r' => $this->previewReceipt()])
                </div>
            </div>
        </aside>
    </div>
</div>
