@props(['steps' => [], 'current' => 0])

<div {{ $attributes->merge(['class' => 'lumiere-wizard']) }}>
    @foreach($steps as $index => $label)
        <div class="wizard-step {{ $index == $current ? 'active' : ($index < $current ? 'completed' : '') }}">
            <div class="step-number">{{ $index + 1 }}</div>
            <div class="step-label">{{ $label }}</div>
        </div>
    @endforeach
</div>
