import './bootstrap';
import 'preline';

// ------------------------------
// Livewire + Preline
// ------------------------------
document.addEventListener('livewire:navigated', () => {
    if (window.HSStaticMethods) {
        window.HSStaticMethods.autoInit();
    }
});

// ------------------------------
// Alpine Carousel
// ------------------------------
window.carousel = function () {
    return {
        container: null,
        prev: null,
        next: null,

        init() {
            this.container = this.$refs.container;

            if (!this.container) return;

            this.update();

            this.container.addEventListener(
                "scroll",
                this.update.bind(this),
                { passive: true }
            );
        },

        update() {

            if (!this.container) return;

            const rect = this.container.getBoundingClientRect();

            const visibleElements = Array.from(this.container.children).filter(
                (child) => {
                    const childRect = child.getBoundingClientRect();

                    return (
                        childRect.left >= rect.left &&
                        childRect.right <= rect.right
                    );
                }
            );

            if (visibleElements.length) {
                this.prev = this.getPrevElement(visibleElements);
                this.next = this.getNextElement(visibleElements);
            }
        },

        getPrevElement(list) {
            const sibling = list[0]?.previousElementSibling;

            return sibling instanceof HTMLElement ? sibling : null;
        },

        getNextElement(list) {
            const sibling = list[list.length - 1]?.nextElementSibling;

            return sibling instanceof HTMLElement ? sibling : null;
        },

        scrollTo(element) {

            if (!this.container || !element) return;

            this.container.scroll({
                left:
                    element.offsetLeft +
                    element.offsetWidth / 2 -
                    this.container.offsetWidth / 2,

                behavior: "smooth",
            });
        },
    };
};

// ------------------------------
// Read More
// ------------------------------
window.readMore = function () {

    return {

        open: false,

        isTruncated: false,

        lineNumber: 6,

        lineHeight: 20,

        truncateHeight: 0,

        init() {

            if (!this.$refs.content) return;

            this.truncateHeight = this.lineNumber * this.lineHeight;

            this.isTruncated =
                this.$refs.content.offsetHeight > this.truncateHeight;
        },
    };
};

// ------------------------------
// Card Read More
// ------------------------------
document.addEventListener("DOMContentLoaded", () => {

    const cardHeight = 200;

    const cards = document.querySelectorAll(".card");

    cards.forEach((card) => {

        const description = card.querySelector(".description");

        const readMore = card.querySelector(".button");

        if (!description || !readMore) return;

        description.style.maxHeight = `${cardHeight}px`;

        if (description.scrollHeight <= cardHeight) {

            readMore.style.display = "none";

            return;
        }

        const addSpan = () => {

            if (description.nextElementSibling?.classList.contains("dots"))
                return;

            const span = document.createElement("span");

            span.className = "dots";

            span.innerHTML = "&hellip;";

            description.after(span);
        };

        addSpan();

        readMore.addEventListener("click", () => {

            if (description.style.maxHeight === `${cardHeight}px`) {

                description.style.maxHeight =
                    `${description.scrollHeight}px`;

                readMore.innerHTML = "Read Less";

                description.nextElementSibling?.remove();

            } else {

                description.style.maxHeight = `${cardHeight}px`;

                readMore.innerHTML = "Read More";

                addSpan();
            }

        });

    });

});

// ------------------------------
// Booking Date Fix
// ------------------------------
document.addEventListener("DOMContentLoaded", () => {

    const bookingInput = document.querySelector('input[name="book"]');

    if (bookingInput) {

        bookingInput.min = new Date().toISOString().slice(0, 16);

    }

});
