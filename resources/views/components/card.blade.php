@props([
    'title' => '',
    'icon' => null,
    'tools' => false
])

<div {{ $attributes->merge(['class' => 'lumiere-card mb-4']) }}>
    @if($title)
        <div class="lumiere-card-header">
            @if($icon)
                <i class="fas fa-{{ $icon }}"></i>
            @endif
            {{ $title }}
            
            @if($tools)
                <div class="ms-auto">
                    {{ $tools }}
                </div>
            @endif
        </div>
    @endif
    
    <div class="lumiere-card-body">
        {{ $slot }}
    </div>
</div>
