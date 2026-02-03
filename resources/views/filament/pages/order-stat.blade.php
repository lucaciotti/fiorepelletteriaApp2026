<x-filament-panels::page>
    {{-- Page content --}}
    {{-- {{ $this->table }} --}}
    @livewire('order-stat.form-choice')
    <br>
    {{-- <x-filament::loading-indicator class="h-5 w-5" />  --}}
    @livewire('order-stat.stat-table')
</x-filament-panels::page>
