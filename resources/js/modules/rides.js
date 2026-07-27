const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/* =========================================
   RIDES RESULTS — FARE MODAL
========================================= */
const MODAL_ID = 'fareSummaryModal';
const modalListeners = new WeakSet();

function getElement(id) {
    return document.getElementById(id);
}

function toNumber(value, fallback = 0) {
    const number = Number.parseFloat(value);
    return Number.isFinite(number) ? number : fallback;
}

function formatMoney(value) {
    return `₹ ${Math.round(toNumber(value))}`;
}

function formatRate(value) {
    return toNumber(value).toFixed(2);
}

function setText(id, value) {
    const element = getElement(id);
    if (element) element.textContent = String(value);
}

function setHtml(id, value) {
    const element = getElement(id);
    if (element) element.innerHTML = value;
}

function setVisible(id, visible) {
    const element = getElement(id);
    if (element) element.style.display = visible ? 'block' : 'none';
}

function getFareModal() {
    return getElement(MODAL_ID);
}

function renderFareModal({
    categoryName,
    baseFare,
    gstAmount,
    totalPrice,
    extraKmLimit = 0,
    extraKmRate = 0,
    showDriverAllowance = false,
    driverAllowance = '',
    showTollTax = true,
    tollTaxStatus = 'Excluded',
    notes = '',
}) {
    const modal = getFareModal();
    if (!modal) return false;

    setText('carCategory', `${categoryName || 'Vehicle'} Or Equivalent`);
    setText('baseFare', formatMoney(baseFare));
    setText('gstAmount', formatMoney(gstAmount));
    setText('totalPrice', formatMoney(totalPrice));
    setText('extraKmLimit', extraKmLimit);
    setText('extraKmRate', formatRate(extraKmRate));

    setVisible('driverAllowanceSection', showDriverAllowance);
    if (showDriverAllowance) setText('driverAllowance', driverAllowance);

    setVisible('tollTaxSection', showTollTax);
    if (showTollTax) setText('tollTaxStatus', tollTaxStatus);

    setHtml('fareNotes', notes);

    modal.style.display = '';
    modal.classList.remove('hidden');
    modal.setAttribute('aria-hidden', 'false');

    return true;
}

function showFareSummary(categoryId, categoryName, totalPrice, kmCharge, driverCharge, range, totalKm, days) {
    const fare = toNumber(totalPrice);
    const tripDays = Math.max(1, toNumber(days, 1));
    const driverChargePerDay = toNumber(driverCharge);
    const totalDriverCharge = driverChargePerDay * tripDays;
    const baseFare = fare - totalDriverCharge;
    const gstAmount = fare * 0.05;

    renderFareModal({
        categoryName,
        baseFare,
        gstAmount,
        totalPrice: fare + gstAmount,
        extraKmLimit: totalKm,
        extraKmRate: kmCharge,
        showDriverAllowance: true,
        driverAllowance: `${formatMoney(totalDriverCharge)} (${tripDays} day${tripDays > 1 ? 's' : ''})`,
        tollTaxStatus: 'Excluded',
        notes: `
            Extra Charge After: ${totalKm} KMS. will be ₹${formatRate(kmCharge)}/KM.<br>
            There will be a night allowance of ₹0 for the driver after 8 PM.<br>
            <strong>Toll-Tax:</strong> Excluded |
            <strong>Parking:</strong> Extra (if applicable)
        `,
    });
}

function showFareSummaryOneWay(
    rideName,
    categoryName,
    price,
    maxPrice,
    tollTax,
    kmLimit,
    hrLimit,
    extraKmCharge,
    extraHrCharge,
) {
    const fare = toNumber(price);
    const toll = toNumber(tollTax);
    const gstAmount = fare * 0.05;
    const tollIncluded = toll === 0;

    renderFareModal({
        categoryName,
        baseFare: fare,
        gstAmount,
        totalPrice: fare + gstAmount + (tollIncluded ? 0 : toll),
        extraKmLimit: kmLimit,
        extraKmRate: extraKmCharge,
        showDriverAllowance: false,
        tollTaxStatus: tollIncluded ? 'Included' : formatMoney(toll),
        notes: `
            One way trip — one pickup and one drop. Extra pickup or drop is chargeable.<br>
            <strong>Toll-Tax:</strong> ${tollIncluded ? 'Included' : formatMoney(toll)} |
            <strong>Parking:</strong> Extra (if applicable)<br>
            Extra charge after ${kmLimit} KMS: ₹${formatRate(extraKmCharge)}/KM.<br>
            Extra charge after ${hrLimit} HRS: ₹${formatRate(extraHrCharge)}/HR.
        `,
    });
}

function showFareSummarySelfDrive(rideName, categoryName, price, maxPrice, days, security) {
    const fare = toNumber(price);
    const gstAmount = (fare * 5) / 105;
    const duration = toNumber(days);
    const deposit = toNumber(security);

    renderFareModal({
        categoryName,
        baseFare: fare,
        gstAmount,
        totalPrice: fare + gstAmount,
        extraKmLimit: 0,
        extraKmRate: 10,
        showDriverAllowance: false,
        tollTaxStatus: 'Excluded',
        notes: `
            Self Drive for ${duration} hour(s).<br>
            No driver included — you drive yourself.<br>
            <strong>Toll-Tax:</strong> Excluded |
            <strong>Parking:</strong> Extra (if applicable)<br>
            <strong>Security:</strong> ${formatMoney(deposit)} refundable deposit
        `,
    });
}

function showFareSummaryLocal(
    rideName,
    categoryName,
    price,
    maxPrice,
    cars,
    plan,
    extraKmCharge,
    extraHrCharge,
    driverAllowances,
) {
    const fare = toNumber(price);
    const gstAmount = (fare * 5) / 105;
    const carCount = Math.max(1, toNumber(cars, 1));
    const allowance = toNumber(driverAllowances, 300);

    renderFareModal({
        categoryName,
        baseFare: fare,
        gstAmount,
        totalPrice: fare + gstAmount,
        extraKmLimit: 0,
        extraKmRate: 10,
        showDriverAllowance: false,
        tollTaxStatus: 'Excluded',
        notes: `
            Local trip with ${plan} plan for ${carCount} car(s).<br>
            <strong>Toll-Tax:</strong> Excluded |
            <strong>Parking:</strong> Extra (if applicable)<br>
            Extra KM charge: ₹${formatRate(extraKmCharge)}/KM.<br>
            Extra hour charge: ₹${formatRate(extraHrCharge)}/HR.<br>
            Driver allowance after 8 PM until 4 AM: ₹${formatRate(allowance)}
        `,
    });
}

function closeFareSummary() {
    const modal = getFareModal();
    if (!modal) return;

    modal.classList.add('hidden');
    modal.style.display = '';
    modal.setAttribute('aria-hidden', 'true');
}

function initializeModalEventListeners(root = document) {
    const modal = root.querySelector?.(`#${MODAL_ID}`) || getFareModal();
    if (!(modal instanceof HTMLElement) || modalListeners.has(modal)) return;

    modal.addEventListener('click', (event) => {
        if (event.target === modal) closeFareSummary();
    });

    modalListeners.add(modal);
}

function handleModalKeydown(event) {
    if (event.key !== 'Escape') return;

    const modal = getFareModal();
    if (modal && !modal.classList.contains('hidden')) closeFareSummary();
}

/* =========================================
   RIDES RESULTS — PREMIUM MOTION
========================================= */
function initRidesPremiumPage(root = document) {
    const page = root.querySelector?.('#rides-page') || document.querySelector('#rides-page');
    if (!page) return;

    initializeModalEventListeners(page);

    const targets = page.querySelectorAll([
        '.rides-side-card',
        '.rides-toolbar',
        '.rides-results > div',
        '.sd-card',
        '#fareSummaryModal > div',
    ].join(','));

    const observer = !prefersReducedMotion && 'IntersectionObserver' in window
        ? new IntersectionObserver((entries, currentObserver) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                entry.target.classList.add('is-visible');
                currentObserver.unobserve(entry.target);
            });
        }, { threshold: 0.08, rootMargin: '0px 0px -24px 0px' })
        : null;

    targets.forEach((element, index) => {
        if (!(element instanceof HTMLElement)) return;
        if (element.dataset.ridesMotionReady === 'true') return;

        element.dataset.ridesMotionReady = 'true';
        element.classList.add('rides-motion-item');
        element.style.animationDelay = `${Math.min(index % 6, 5) * 70}ms`;

        if (!observer) {
            element.classList.add('is-visible');
            return;
        }

        observer.observe(element);
    });
}

function bootRidesPage(root = document) {
    window.requestAnimationFrame(() => initRidesPremiumPage(root));
}

document.addEventListener('DOMContentLoaded', () => bootRidesPage(document), { once: true });
document.addEventListener('livewire:navigated', () => bootRidesPage(document));
document.addEventListener('keydown', handleModalKeydown);

document.addEventListener('livewire:init', () => {
    if (!window.Livewire?.hook) return;

    window.Livewire.hook('morph.updated', ({ el }) => {
        bootRidesPage(el instanceof Element ? el : document);
    });
});

document.addEventListener('livewire:initialized', () => bootRidesPage(document));

// Vite files are ES modules. Inline Blade onclick handlers require explicit window exports.
window.showFareSummary = showFareSummary;
window.showFareSummaryOneWay = showFareSummaryOneWay;
window.showFareSummarySelfDrive = showFareSummarySelfDrive;
window.showFareSummaryLocal = showFareSummaryLocal;
window.closeFareSummary = closeFareSummary;

export {
    closeFareSummary,
    initRidesPremiumPage,
    showFareSummary,
    showFareSummaryLocal,
    showFareSummaryOneWay,
    showFareSummarySelfDrive,
};
