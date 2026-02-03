<div class="flex items-center">
    @if(isset($editRoute))
        <a href="{{ $editRoute }}"
            class="inline-flex items-center px-2 py-1.5 bg-yellow-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-600 active:bg-yellow-700 focus:outline-none focus:border-yellow-700 focus:ring ring-yellow-300 disabled:opacity-25 transition ease-in-out duration-150 mr-2">
            <i class="fa-solid fa-pen"></i>
        </a>
    @endif

    @if(isset($deleteRoute))
        <form action="{{ $deleteRoute }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure?');">
            @csrf
            @method('DELETE')
            <button type="submit"
                class="inline-flex items-center px-2 py-1.5 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 active:bg-red-900 focus:outline-none focus:border-red-900 focus:ring ring-red-300 disabled:opacity-25 transition ease-in-out duration-150">
                <i class="fa-solid fa-trash"></i>
            </button>
        </form>
    @endif
</div>