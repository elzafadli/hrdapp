@props(['status'])

@if($status === 'open')
    <span class="px-2 py-1 text-xs font-semibold text-green-700 bg-green-100 rounded-full">Open</span>
@elseif($status === 'closed')
    <span class="px-2 py-1 text-xs font-semibold text-gray-700 bg-gray-100 rounded-full">Closed</span>
@else
    <span class="px-2 py-1 text-xs font-semibold text-gray-700 bg-gray-100 rounded-full">{{ $status }}</span>
@endif
