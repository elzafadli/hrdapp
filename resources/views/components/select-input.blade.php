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

        .select2-container--open {
            z-index: 99999 !important;
        }

        .select2-dropdown {
            background-color: #ffffff !important;
            border: 1px solid #d1d5db !important;
            border-radius: 0.5rem !important;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15), 0 10px 10px -5px rgba(0, 0, 0, 0.08) !important;
            z-index: 999999 !important;
        }

        .select2-container--default .select2-search--dropdown {
            padding: 0.5rem !important;
            background-color: #ffffff !important;
        }

        .select2-container--default .select2-search--dropdown .select2-search__field {
            border: 1px solid #d1d5db !important;
            border-radius: 0.375rem !important;
            padding: 0.375rem 0.5rem !important;
            font-size: 0.875rem !important;
            outline: none !important;
            width: 100% !important;
            background-color: #ffffff !important;
        }

        .select2-container--default .select2-search--dropdown .select2-search__field:focus {
            border-color: #6366f1 !important;
        }

        .select2-container--default .select2-results__options {
            max-height: 250px !important;
            overflow-y: auto !important;
            background-color: #ffffff !important;
        }

        .select2-container--default .select2-results__option {
            font-size: 0.875rem !important;
            padding: 0.5rem 0.75rem !important;
            color: #1f2937 !important;
            background-color: #ffffff !important;
        }

        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #2563eb !important;
            color: #ffffff !important;
        }

        .select2-container--default .select2-results__option[aria-selected="true"] {
            background-color: #eff6ff !important;
            color: #1d4ed8 !important;
            font-weight: 600 !important;
        }
    </style>
    <script>
        function select2MultiMatcher(params, data) {
            // Jika input pencarian kosong, tampilkan seluruh item
            if (!params.term || $.trim(params.term) === '') {
                return data;
            }

            // Jika item tidak memiliki properti teks (misal optgroup kosong), abaikan
            if (typeof data.text === 'undefined') {
                return null;
            }

            // Pecah kata kunci pencarian berdasarkan spasi (multi-word / multi-token search)
            // Contoh: "izin cuti" akan mencocokkan teks yang mengandung kata "izin" DAN kata "cuti"
            var terms = params.term.toLowerCase().split(/\s+/).filter(Boolean);
            var text = (data.text || '').toLowerCase();

            // Semua kata kunci harus ada di dalam teks item
            var isAllMatched = terms.every(function (t) {
                return text.indexOf(t) > -1;
            });

            if (isAllMatched) {
                return data;
            }

            return null;
        }
        window.select2MultiMatcher = select2MultiMatcher;

        $(document).ready(function () {
            $('select.select2').each(function () {
                if ($(this).is('select') && !$(this).data('select2')) {
                    var $modal = $(this).closest('.modal, dialog, [role="dialog"]');
                    var config = {
                        width: '100%',
                        matcher: select2MultiMatcher
                    };
                    if ($modal.length) {
                        config.dropdownParent = $modal;
                    }

                    $(this).select2(config).on('change', function () {
                        this.dispatchEvent(new Event('change', { bubbles: true }));
                        this.dispatchEvent(new Event('input', { bubbles: true }));
                    });
                }
            });
        });
    </script>
@endonce
@endpush
