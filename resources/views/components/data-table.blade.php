@props(['id', 'headers' => []])

<div class="overflow-x-auto">
    <table id="{{ $id }}" {{ $attributes->merge(['class' => 'table-striped min-w-full divide-y divide-gray-200 stripe hover [&>tbody>tr:nth-child(even)]:bg-gray-50']) }}>
        <thead class="bg-gray-50">
            <tr>
                @foreach($headers as $header)
                    <x-table-head :width="$header === 'No' ? '50px' : ($header === 'Action' ? '200px' : '')">{{ $header }}</x-table-head>
                @endforeach
            </tr>
        </thead>
        <tbody class="bg-white! divide-y divide-gray-200">
        </tbody>
    </table>
</div>