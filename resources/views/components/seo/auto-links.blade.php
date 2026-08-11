@php
    $internalLinks = collect(data_get($seoAutoLinks ?? [], 'internal', []))->filter(fn ($link) => filled(data_get($link, 'url')) && filled(data_get($link, 'title')));
    $externalLinks = collect(data_get($seoAutoLinks ?? [], 'external', []))->filter(fn ($link) => filled(data_get($link, 'url')) && filled(data_get($link, 'title')));
@endphp

@if($internalLinks->isNotEmpty() || $externalLinks->isNotEmpty())
<section class="border-t border-slate-200 bg-white py-10" aria-labelledby="seo-related-links-heading">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        @if($internalLinks->isNotEmpty())
            <div>
                <p class="text-xs font-extrabold uppercase tracking-[0.18em] text-sky-600">Explore Dura Cabs</p>
                <h2 id="seo-related-links-heading" class="mt-2 text-2xl font-black text-slate-900">Related routes, cars & travel pages</h2>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Useful pages related to this service. These links update automatically as new routes and pages are published.</p>

                <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($internalLinks as $link)
                        <a href="{{ data_get($link, 'url') }}" class="group flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 transition hover:border-sky-200 hover:bg-sky-50">
                            <span>
                                <span class="block text-[11px] font-extrabold uppercase tracking-wide text-slate-400">{{ data_get($link, 'type', 'Related') }}</span>
                                <span class="mt-0.5 block text-sm font-bold text-slate-700 group-hover:text-sky-700">{{ data_get($link, 'title') }}</span>
                            </span>
                            <span class="ml-3 text-lg text-sky-600" aria-hidden="true">→</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        @if($externalLinks->isNotEmpty())
            <div class="mt-8 border-t border-slate-100 pt-6">
                <h3 class="text-sm font-black text-slate-900">Useful official resources</h3>
                <div class="mt-3 flex flex-wrap gap-x-5 gap-y-2">
                    @foreach($externalLinks as $link)
                        <a href="{{ data_get($link, 'url') }}" target="_blank" rel="noopener noreferrer" class="text-sm font-semibold text-sky-700 hover:underline">
                            {{ data_get($link, 'title') }} ↗
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</section>
@endif