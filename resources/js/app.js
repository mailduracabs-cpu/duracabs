import './bootstrap';
import 'preline'

document.addEventListener('livewire:navigated', ()=> {
    window.HSStaticMethods.autoInit();
})

window.carousel = function () {
  return {
    container: null,
    prev: null,
    next: null,
    init() {
      this.container = this.$refs.container

      this.update();

      this.container.addEventListener('scroll', this.update.bind(this), {passive: true});
    },
    update() {
      const rect = this.container.getBoundingClientRect();

      const visibleElements = Array.from(this.container.children).filter((child) => {
        const childRect = child.getBoundingClientRect()

        return childRect.left >= rect.left && childRect.right <= rect.right;
      });

      if (visibleElements.length > 0) {
        this.prev = this.getPrevElement(visibleElements);
        this.next = this.getNextElement(visibleElements);
      }
    },
    getPrevElement(list) {
      const sibling = list[0].previousElementSibling;

      if (sibling instanceof HTMLElement) {
        return sibling;
      }

      return null;
    },
    getNextElement(list) {
      const sibling = list[list.length - 1].nextElementSibling;

      if (sibling instanceof HTMLElement) {
        return sibling;
      }

      return null;
    },
    scrollTo(element) {
      const current = this.container;

      if (!current || !element) return;

      const nextScrollPosition =
        element.offsetLeft +
        element.getBoundingClientRect().width / 2 -
        current.getBoundingClientRect().width / 2;

      current.scroll({
        left: nextScrollPosition,
        behavior: 'smooth',
      });
    }
  };
}

function readMore() {
  return {
    open: false,
    isTruncated: false,
    lineNumber: 6,
    lineHeight: 20,
    truncateHeight: 0,
    init() {
      this.truncateHeight = this.getTruncateHeight();
      this.isTruncated = this.$refs.content.offsetHeight > this.truncateHeight;
    },
    getTruncateHeight() {
      return this.lineNumber * this.lineHeight;
    }
  }
}


// define the card height when it is not expanded
const cardHeight = 200;

// get all cards
const cards = document.querySelectorAll(".card");

// loop through cards
for (let card of cards) {
  const description = card.querySelector(".description");
  const readMore = card.querySelector(".button");

  // limit description height to s200px
  description.style.maxHeight = `${cardHeight}px`;
  // hide read more button if description is less than card height
  if (description.scrollHeight <= cardHeight) {
    readMore.style.display = "none";
  }

  // function to add span with hellip
  const addSpan = () => {
    let span = document.createElement("span");
    span.innerHTML = "&hellip;";
    // append span after description
    description.parentNode.insertBefore(span, description.nextSibling);
  };

  // add a span with hellip at the end of the container if the card is not expanded
  if (description.scrollHeight > cardHeight) {
    addSpan();
  }

  // add event listener to read more button to toggle description height
  readMore.addEventListener("click", () => {
    if (description.style.maxHeight === `${cardHeight}px`) {
      description.style.maxHeight = `${description.scrollHeight}px`;
      readMore.innerHTML = "Read less";
      // remove span with hellip when description is expanded
      description.nextSibling.remove();
    } else {
      description.style.maxHeight = `${cardHeight}px`;
      readMore.innerHTML = "Read more";
      // add span with hellip when description is collapsed
      addSpan();
    }
  });
}

var today = new Date().toISOString().slice(0, 16);

document.getElementsByName("book")[0].min = today;


