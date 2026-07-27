<?php

declare(strict_types=1);

namespace App\Livewire;

use Illuminate\Contracts\View\View;


class ServiceSearchPanel extends Homepage
{
    private const DEFAULT_TAB = 'one_way';

    /**
     * Tabs supported by the shared search panel.
     *
     * @var array<int, string>
     */
    private const ALLOWED_TABS = [
        'one_way',
        'return',
        'local',
        'self_drive',
    ];

    public string $defaultTab = self::DEFAULT_TAB;

    public ?string $defaultFromCity = null;

    public ?string $defaultToCity = null;

    public function mount(
        string $defaultTab = self::DEFAULT_TAB,
        ?string $defaultFromCity = null,
        ?string $defaultToCity = null,
    ): void {
        $this->defaultTab = $this->normaliseTab($defaultTab);
        $this->defaultFromCity = $this->normaliseCity($defaultFromCity);
        $this->defaultToCity = $this->normaliseCity($defaultToCity);

        $this->selected_tab = $this->defaultTab;

        $this->applyDefaultFromCity($this->defaultFromCity);
        $this->applyDefaultToCity($this->defaultToCity);
        $this->clearAutocompleteResults();
    }

    /**
     * Change the active booking tab.
     *
     * Invalid tabs and repeated clicks are ignored.
     */
    public function changeTab($value): void
    {
        $tab = is_string($value)
            ? $this->normaliseTab($value)
            : self::DEFAULT_TAB;

        if ($this->selected_tab === $tab) {
            return;
        }

        parent::changeTab($tab);

        $this->clearAutocompleteResults();
        $this->resetValidation();
    }

    public function render(): View
    {
        return view('livewire.service-search-panel');
    }

    private function normaliseTab(?string $tab): string
    {
        $tab = trim((string) $tab);

        return in_array($tab, self::ALLOWED_TABS, true)
            ? $tab
            : self::DEFAULT_TAB;
    }

    private function normaliseCity(?string $city): ?string
    {
        if ($city === null) {
            return null;
        }

        $city = trim($city);

        return $city !== '' ? $city : null;
    }

    private function applyDefaultFromCity(?string $city): void
    {
        if ($city === null) {
            return;
        }

        /*
         * Existing Homepage properties are intentionally populated here
         * so every current booking type continues to work without changing
         * the tested Blade bindings.
         */
        $this->query = $city;
        $this->query_search = $city;

        $this->queryFrom = $city;
        $this->queryFrom_search = $city;

        $this->queryLocal = $city;
        $this->querySelfDrive = $city;
    }

    private function applyDefaultToCity(?string $city): void
    {
        if ($city === null) {
            return;
        }

        $this->query2 = $city;
        $this->query2_search = $city;

        $this->queryTo = $city;
        $this->queryTo_search = $city;
    }

    private function clearAutocompleteResults(): void
    {
        $this->cities_from = null;
        $this->cities_to = null;
        $this->dataFrom = null;
        $this->dataTo = null;
    }
}