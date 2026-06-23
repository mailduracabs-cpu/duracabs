<div class="">
{{-- <div class="flex flex-col h-full min-h72 md:min-h-96 bg-white shadow-sm rounded-xl dark:bg-gray-900">
        <!-- Carosel -->
        <div x-data="{
            images: {{ Js::from($images) }},
            selected: 0,
            nextImage() {
                this.selected++
                if (this.selected == this.images.length) {
                    this.selected = 0
                }
            },
            previousImage() {
                this.selected--
                if (this.selected < 0) {
                    this.selected = this.images.length - 1
                }
            },
        }" class="flex felx-col flex-1 justify-center items-center relative">
            <div
                class=" h-full w-full bg-white rounded-t-xl border border-gray-200 dark:border-gray-50/10 dark:bg-gray-900">
                <div class="flex justify-center h-full bg-gray-100 rounded-t-xl">
                    <div class="bg-cover bg-center bg-no-repeat w-full h-full p-0 min-h-52 md:min-h-80 rounded-t-xl"
                        x-bind:style="`background-image: url({{ asset('img/home/${images[selected]}') }})`"></div>
                </div>
            </div>
            <button x-on:click="previousImage()"
                class="absolute inset-y-0 start-0 inline-flex justify-center items-center w-[46px] h-full text-gray-600 hover:bg-gray-900/[.1] rounded-tl-xl"
                type="button">
                <span class="text-2xl">
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke-width="1.5" stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                    </svg>

                </span>
                <span class="sr-only"> Previous</span>
            </button>
            <button x-on:click="nextImage()"
                class="absolute inset-y-0 end-0 inline-flex justify-center items-center w-[46px] h-full text-gray-900 hover:bg-gray-900/[.1] rounded-tr-xl"
                type="button">


                <span class="sr-only"> Next</span>
                <span class="text-2xl">
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke-width="1.5" stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </span>

            </button>

            <div class="flex justify-center absolute -bottom-5 start-0 end-0 space-x-2">
                <template x-for='(image,index) in images'>
                    <span x-on:click="selected = index"
                        x-bind:class="{ 'bg-blue-600 dark:bg-amber-600': index == selected }"
                        class="w-3 h-3 border border-blue-600 dark:border-amber-600 rounded-full cursor-pointer  "></span>

                </template>


            </div>

        </div>

        <!-- End Carosel -->
        <div class="p-4 md:p-6 border-x border-b border-gray-200 dark:border-gray-50/10 dark:bg-gray-900 rounded-b-xl">
            <span class="block md-1 text-lg font-semibold uppercase text-blue-600 dark:text-amber-600">
                Card Title
            </span>
            <h3 class='text-sm font-semibold text-gray-800 dark:text-gray-300'>
                loremLorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the
                industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and
                scrambled it to make a type specimen book.
            </h3>
        </div>
    </div> --}}


{{-- <div class="">

    <div x-data="{}" x-init="swiper = new Swiper($refs.container, {
        loop: true,
        slidesPerView: 4,
        spaceBetween: 0,
    
        breakpoints: {
            640: {
                slidesPerView: 1,
                spaceBetween: 0,
            },
            768: {
                slidesPerView: 2,
                spaceBetween: 0,
            },
            1024: {
                slidesPerView: 4,
                spaceBetween: 0,
            },
        },
    })" class="relative w-10/12 mx-auto flex flex-row mt-10">
        <div class="absolute inset-y-0 left-0 z-10 flex items-center">
            <button @click="swiper.slidePrev()"
                class="bg-white -ml-2 lg:-ml-4 flex justify-center items-center w-10 h-10 rounded-full shadow focus:outline-none">
                <svg viewBox="0 0 20 20" fill="currentColor" class="chevron-left w-6 h-6">
                    <path fill-rule="evenodd"
                        d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"
                        clip-rule="evenodd"></path>
                </svg>
            </button>
        </div>

        <div class="swiper-container" x-ref="container">
            {{ $bannerTab }}
            <button type="button" wire:click="changeBanner('return')"
                class="flex text-center items-center justify-center  w-full p-4 bg-white border-r border-gray-200 dark:border-gray-700 hover:text-gray-700 hover:bg-gray-50 focus:ring-4 focus:ring-blue-300 focus:outline-none dark:hover:text-white dark:bg-gray-800 dark:hover:bg-gray-700">

                <h2
                    class="font-bold text-xm uppercase {{ $bannerTab === 'return' ? 'main-color-text font-extrabold' : '' }} ">
                    Return</h2>
            </button>
            <div class="swiper-wrapper">
                <!-- Slides -->




                @foreach ($carousel as $item)
                    <div class="swiper-slide p-4">
                        <div class="flex flex-col rounded shadow overflow-hidden">
                            <div class="flex-shrink-0">
                                <img class="h-48 w-full object-cover"
                                    src="https://images.unsplash.com/photo-1496128858413-b36217c2ce36?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=1679&q=80"
                                    alt="">

                                {{ $item->name }}
                            </div>
                        </div>
                    </div>
                @endforeach







            </div>
        </div>

        <div class="absolute inset-y-0 right-0 z-10 flex items-center">
            <button @click="swiper.slideNext()"
                class="bg-white -mr-2 lg:-mr-4 flex justify-center items-center w-10 h-10 rounded-full shadow focus:outline-none">
                <svg viewBox="0 0 20 20" fill="currentColor" class="chevron-right w-6 h-6">
                    <path fill-rule="evenodd"
                        d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                        clip-rule="evenodd"></path>
                </svg>
            </button>
        </div>
    </div>

</div> --}}




