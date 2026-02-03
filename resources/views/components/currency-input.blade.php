@props(['disabled' => false])

<input type="text" {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'currency-mask border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm py-1.5 text-sm text-right']) !!}>

@once
    @push('scripts')
        <script type="module">
            $(document).ready(function () {
                $('.currency-mask').inputmask({
                    alias: 'numeric',
                    groupSeparator: ",",
                    digits: 2,
                    digitsOptional: false,
                    placeholder: "0.00",
                    rightAlign: true,
                    autoGroup: true,
                    prefix: "",
                    allowMinus: false,
                    removeMaskOnSubmit: true
                });
            });
        </script>
    @endpush
@endonce