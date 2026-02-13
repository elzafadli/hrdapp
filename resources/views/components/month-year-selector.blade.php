@props([
    'label' => null,
    'namePrefix' => '',
    'defaultMonth' => null,
    'defaultYear' => null,
    'yearFrom' => 2020,
    'yearTo' => 2030,
])

<div>
    @if($label)
        <x-input-label :for="$namePrefix . '_month'" :value="$label" />
    @endif
    <div class="mt-1 grid grid-cols-2 gap-2">
        <select id="{{ $namePrefix }}_month" name="{{ $namePrefix }}_month" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
            <option value="">Bulan</option>
            <option value="1" {{ old($namePrefix . '_month', $defaultMonth) == '1' ? 'selected' : '' }}>Januari</option>
            <option value="2" {{ old($namePrefix . '_month', $defaultMonth) == '2' ? 'selected' : '' }}>Februari</option>
            <option value="3" {{ old($namePrefix . '_month', $defaultMonth) == '3' ? 'selected' : '' }}>Maret</option>
            <option value="4" {{ old($namePrefix . '_month', $defaultMonth) == '4' ? 'selected' : '' }}>April</option>
            <option value="5" {{ old($namePrefix . '_month', $defaultMonth) == '5' ? 'selected' : '' }}>Mei</option>
            <option value="6" {{ old($namePrefix . '_month', $defaultMonth) == '6' ? 'selected' : '' }}>Juni</option>
            <option value="7" {{ old($namePrefix . '_month', $defaultMonth) == '7' ? 'selected' : '' }}>Juli</option>
            <option value="8" {{ old($namePrefix . '_month', $defaultMonth) == '8' ? 'selected' : '' }}>Agustus</option>
            <option value="9" {{ old($namePrefix . '_month', $defaultMonth) == '9' ? 'selected' : '' }}>September</option>
            <option value="10" {{ old($namePrefix . '_month', $defaultMonth) == '10' ? 'selected' : '' }}>Oktober</option>
            <option value="11" {{ old($namePrefix . '_month', $defaultMonth) == '11' ? 'selected' : '' }}>November</option>
            <option value="12" {{ old($namePrefix . '_month', $defaultMonth) == '12' ? 'selected' : '' }}>Desember</option>
        </select>
        <select id="{{ $namePrefix }}_year" name="{{ $namePrefix }}_year" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
            <option value="">Tahun</option>
            @for($y = $yearFrom; $y <= $yearTo; $y++)
                <option value="{{ $y }}" {{ old($namePrefix . '_year', $defaultYear) == $y ? 'selected' : '' }}>{{ $y }}</option>
            @endfor
        </select>
    </div>
    <input type="hidden" id="{{ $namePrefix }}_date" name="{{ $namePrefix }}_date">
</div>
