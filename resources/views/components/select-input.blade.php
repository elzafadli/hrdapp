@props(['disabled' => false, 'options' => [], 'selected' => null, 'placeholder' => null, 'noSelect2' => false])

<div wire:ignore class="{{ $attributes->get('class') }}">
    <select name="{{ $attributes->get('name') }}" id="{{ $attributes->get('id') }}" {{ $disabled ? 'disabled' : '' }}
        {{ $attributes->whereStartsWith('x-') }}
        {{ $attributes->whereStartsWith('on') }}
        {{ $attributes->whereStartsWith('wire:') }}
        {{ ($attributes->has('required') && $noSelect2) ? 'required' : '' }}
        class="{{ $noSelect2 ? '' : 'select2' }} border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md py-1.5 text-sm shadow-sm w-full bg-white">
        @if($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif
        @if(!empty($options))
            @foreach($options as $key => $value)
                <option value="{{ $key }}" {{ (string)$selected === (string)$key && $selected !== null && $selected !== '' ? 'selected' : '' }}>
                    {{ $value }}
                </option>
            @endforeach
        @endif
        {{ $slot }}
    </select>
</div>

@push('scripts')
@once
    <style>
        .select2-container {
            width: 100% !important;
            display: block !important;
        }

        .select2-container .select2-selection--single {
            min-height: 38px !important;
            height: 38px !important;
            padding: 0.375rem 0.75rem !important;
            border: 1px solid #d1d5db !important;
            border-radius: 0.375rem !important;
            display: flex !important;
            align-items: center !important;
            background-color: #fff !important;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
            position: relative !important;
        }

        .select2-container--default.select2-container--open .select2-selection--single,
        .select2-container--default.select2-container--focus .select2-selection--single {
            border-color: #6366f1 !important;
            outline: none !important;
            box-shadow: 0 0 0 1px #6366f1 !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            font-size: 0.875rem !important;
            line-height: normal !important;
            padding-left: 0 !important;
            padding-right: 1.5rem !important;
            color: #111827 !important;
            display: block !important;
            width: 100% !important;
            float: none !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__placeholder {
            color: #9ca3af !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 100% !important;
            top: 0 !important;
            right: 0.5rem !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            width: 20px !important;
            position: absolute !important;
        }

        .select2-dropdown {
            border: 1px solid #d1d5db !important;
            border-radius: 0.375rem !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
            z-index: 9999 !important;
        }

        .select2-container--default .select2-search--dropdown .select2-search__field {
            border: 1px solid #d1d5db !important;
            border-radius: 0.375rem !important;
            padding: 0.375rem 0.5rem !important;
            font-size: 0.875rem !important;
            outline: none !important;
        }

        .select2-container--default .select2-search--dropdown .select2-search__field:focus {
            border-color: #6366f1 !important;
        }

        .select2-container--default .select2-results__option {
            font-size: 0.875rem !important;
            padding: 0.375rem 0.75rem !important;
        }

        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #4f46e5 !important;
            color: #fff !important;
        }
    </style>
    <script>
        $(document).ready(function () {
            $('.select2').each(function () {
                if (!$(this).data('select2')) {
                    $(this).select2({
                        width: '100%'
                    }).on('change', function () {
                        this.dispatchEvent(new Event('change', { bubbles: true }));
                        this.dispatchEvent(new Event('input', { bubbles: true }));
                    });
                }
            });
        });
    </script>
@endonce
@endpush
