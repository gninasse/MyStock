<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb bg-white p-3 rounded shadow-sm mb-0">
        <li class="breadcrumb-item">
            <a href="{{ route('cores.dashboard') }}" class="text-decoration-none">
                <i class="fas fa-home"></i>
            </a>
        </li>
        @if(isset($breadcrumbs) && count($breadcrumbs) > 0)
            @foreach($breadcrumbs as $breadcrumb)
                @if($loop->last)
                    <li class="breadcrumb-item active" aria-current="page">{{ $breadcrumb['title'] }}</li>
                @else
                    <li class="breadcrumb-item">
                        <a href="{{ $breadcrumb['url'] ?? '#' }}" class="text-decoration-none">
                            {{ $breadcrumb['title'] }}
                        </a>
                    </li>
                @endif
            @endforeach
        @else
            @yield('breadcrumb')
        @endif
    </ol>
</nav>
