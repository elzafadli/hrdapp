@props(['disabled' => false, 'options' => [], 'selected' => null, 'placeholder' => 'Select an option'])

<div wire:ignore {!! $attributes->merge(['class' => '']) !!}>
    <select name="{{ $attributes->get('name') }}" id="{{ $attributes->get('id') }}" {{ $disabled ? 'disabled' : '' }}
        class="select2 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md py-1 text-sm shadow-sm w-full">
        <option value="">{{ $placeholder }}</option>
        @foreach($options as $key => $value)
            <option value="{{ $key }}" {{ $selected == $key ? 'selected' : '' }}>
                {{ $value }}
            </option>
        @endforeach
    </select>
</div>

@push('scripts')
    <style>
        .select2-container .select2-selection--single {
            height: auto !important;
            padding: 0.375rem 0.75rem !important;
            /* py-1.5 px-3 match */
            border: 1px solid #d1d5db !important;
            /* border-gray-300 */
            border-radius: 0.375rem !important;
            /* rounded-md */
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            font-size: 0.875rem !important;
            /* text-sm */
            line-height: 1.25rem !important;
            /* leading-5 */
            padding-left: 0 !important;
            color: #111827 !important;
            /* text-gray-900 */
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 100% !important;
            top: 0 !important;
            right: 0.5rem !important;
        }

        /* Access dropdown results to ensure they are also small text */
        .select2-container--default .select2-results__option {
            font-size: 0.875rem !important;
            /* text-sm */
            padding: 0.375rem 0.75rem !important;
        }
    </style>
    <script>
        $(document).ready(function () {
            $('.select2').select2({
                width: '100%' // Ensure it takes full width
            });
        });
    </script>
@endpush