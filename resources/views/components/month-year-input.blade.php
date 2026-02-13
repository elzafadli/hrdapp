@props([
    'monthId' => 'start_month',
    'monthName' => 'start_month',
    'yearId' => 'start_year',
    'yearName' => 'start_year',
    'monthValue' => null,
    'yearValue' => null,
    'currentDate' => null
])

@php
    // Default to current date if not provided
    if ($currentDate) {
        $defaultMonth = $currentDate->format('n');
        $defaultYear = $currentDate->format('Y');
    } else {
        $defaultMonth = (int) date('n');
        $defaultYear = (int) date('Y');
    }

    // Use provided value or default
    $selectedMonth = $monthValue ?? $defaultMonth;
    $selectedYear = $yearValue ?? $defaultYear;

    // Indonesian month names
    $months = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];
@endphp

<div {!! $attributes->merge(['class' => 'grid grid-cols-2 gap-2']) !!}>
    <div>
        <select id="{{ $monthId }}" name="{{ $monthName }}" class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
            <option value="">Pilih Bulan</option>
            @foreach($months as $key => $name)
                <option value="{{ $key }}" {{ old($monthName, $selectedMonth) == $key ? 'selected' : '' }}>
                    {{ $name }}
                </option>
            @endforeach
        </select>
    </div>
    <div>
        <select id="{{ $yearId }}" name="{{ $yearName }}" class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
            <option value="">Pilih Tahun</option>
            @for($y = $defaultYear - 2; $y <= $defaultYear + 10; $y++)
                <option value="{{ $y }}" {{ old($yearName, $selectedYear) == $y ? 'selected' : '' }}>
                    {{ $y }}
                </option>
            @endfor
        </select>
    </div>
</div>
