@php
    use App\Services\Seo\SeoService;

    $orphanPageLinks = app(SeoService::class)->links(
        currentUrl: url()->current(),
        limit: 16
    );
@endphp

@if(!empty($orphanPageLinks))
    <section
        class="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8"
        aria-label="Explore more pages"
    >
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-600 dark:bg-slate-800">
            <h2 class="text-lg font-bold text-slate-900 dark:text-white">
                Explore More
            </h2>

            <div class="mt-4 flex flex-wrap gap-2">
                @foreach($orphanPageLinks as $item)
                    <a
                        href="{{ $item['url'] }}"
                        class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-950 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 dark:hover:bg-slate-600"
                    >
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endif
