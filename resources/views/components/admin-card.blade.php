@props([
    'titleModel' => 'seo_title',
    'descriptionModel' => 'seo_description',
    'canonicalModel' => 'seo_canonical_url',
    'robotsModel' => 'seo_robots',
])

<section {{ $attributes->merge(['class' => 'overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-zinc-950/5 dark:bg-zinc-950 dark:ring-white/10']) }}>
    <header class="border-b border-zinc-100/70 bg-zinc-50 px-5 py-4 dark:border-zinc-800/70 dark:bg-zinc-900/50">
        <flux:heading size="lg" class="text-[15px] font-semibold leading-6 text-zinc-950 dark:text-white">{{ __('SEO') }}</flux:heading>
        <p class="mt-0.5 text-[13px] leading-5 text-zinc-500 dark:text-zinc-400">{{ __('Naslov, opis i indeksiranje za tražilice i dijeljenje.') }}</p>
    </header>

    <div class="grid gap-4 p-5">
        <flux:field>
            <flux:label>
                <span class="inline-flex items-center gap-1.5">
                    <span>{{ __('SEO naslov') }}</span>
                    <flux:dropdown position="top" align="start">
                        <flux:button type="button" variant="ghost" size="xs" icon="information-circle" class="-my-1 size-6 p-0 text-zinc-400 hover:text-zinc-700 dark:text-zinc-500 dark:hover:text-zinc-200" :aria-label="__('Što je SEO naslov?')" />
                        <flux:popover class="w-72">
                            <div class="space-y-1.5">
                                <flux:heading size="sm">{{ __('SEO naslov') }}</flux:heading>
                                <flux:text class="text-sm leading-5">{{ __('Naslov za Google i dijeljenje linka. Ako ga ne mijenjate, prati naziv zapisa.') }}</flux:text>
                            </div>
                        </flux:popover>
                    </flux:dropdown>
                </span>
            </flux:label>
            <flux:input wire:model.live.debounce.300ms="{{ $titleModel }}" />
            <flux:error name="{{ $titleModel }}" />
        </flux:field>

        <flux:field>
            <flux:label>
                <span class="inline-flex items-center gap-1.5">
                    <span>{{ __('SEO opis') }}</span>
                    <flux:dropdown position="top" align="start">
                        <flux:button type="button" variant="ghost" size="xs" icon="information-circle" class="-my-1 size-6 p-0 text-zinc-400 hover:text-zinc-700 dark:text-zinc-500 dark:hover:text-zinc-200" :aria-label="__('Što je SEO opis?')" />
                        <flux:popover class="w-72">
                            <div class="space-y-1.5">
                                <flux:heading size="sm">{{ __('SEO opis') }}</flux:heading>
                                <flux:text class="text-sm leading-5">{{ __('Kratak tekst ispod naslova u pretrazi. Ako ga ne mijenjate, prati opis zapisa.') }}</flux:text>
                            </div>
                        </flux:popover>
                    </flux:dropdown>
                </span>
            </flux:label>
            <flux:textarea wire:model.live.debounce.500ms="{{ $descriptionModel }}" rows="4" />
            <flux:error name="{{ $descriptionModel }}" />
        </flux:field>

        <flux:field>
            <flux:label>
                <span class="inline-flex items-center gap-1.5">
                    <span>{{ __('Canonical URL') }}</span>
                    <flux:tooltip :content="__('Automatski se popunjava')">
                        <flux:icon icon="information-circle" class="size-4 text-zinc-400 dark:text-zinc-500" />
                    </flux:tooltip>
                </span>
            </flux:label>
            <flux:input wire:model="{{ $canonicalModel }}" disabled />
            <flux:error name="{{ $canonicalModel }}" />
        </flux:field>

        <flux:field>
            <flux:label>
                <span class="inline-flex items-center gap-1.5">
                    <span>{{ __('Robots') }}</span>
                    <flux:tooltip :content="__('Kontrolira indeksiranje i praćenje tražilicama')">
                        <flux:icon icon="information-circle" class="size-4 text-zinc-400 dark:text-zinc-500" />
                    </flux:tooltip>
                </span>
            </flux:label>
            <flux:select wire:model="{{ $robotsModel }}" disabled>
                <flux:select.option value="">{{ __('Automatski') }}</flux:select.option>
                <flux:select.option value="index,follow">{{ __('Index, follow') }}</flux:select.option>
                <flux:select.option value="noindex,follow">{{ __('Noindex, follow') }}</flux:select.option>
                <flux:select.option value="noindex,nofollow">{{ __('Noindex, nofollow') }}</flux:select.option>
            </flux:select>
            <flux:error name="{{ $robotsModel }}" />
        </flux:field>
    </div>
</section>
