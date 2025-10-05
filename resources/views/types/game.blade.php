@extends('layouts.default')

@section('title', $content->name)

@section('content')
<div class="min-h-screen {{ $content->slug }} bg-black text-white">
    <div @if ($content->field('background')) style="background-image: url({{ asset($content->field('background')[0]) }})"@endif class="bg-pink-900 bg-blend-soft-light bg-cover bg-opacity-10 backdrop-blur-sm min-h-screen flex items-center justify-center">
        <div class="w-full max-w-6xl mx-auto flex justify-center">
            <img src="{{ asset($content->field('logo')[0] ?? null) }}" alt="{{ $content->name }} logo" class="w-1/2 max-h-80">
        </div>
    </div>
    <div class="flex justify-center py-8">
        <a href="{{ $content->field('steam') ?? '#' }}" target="_blank"
           class="inline-block bg-[#ff00ff] hover:bg-pink-500 text-black font-bold py-3 px-6 rounded shadow-lg transition duration-200 hover:shadow-pink-400/70">
            Available on Steam
        </a>
    </div>

    @if ($features = $content->field('features'))
        <div class="max-w-5xl mx-auto space-y-16 py-8">
            @foreach ($features as $index => $feature)
                <div class="flex flex-col md:flex-row {{ $index % 2 == 1 ? 'md:flex-row-reverse' : '' }} items-center gap-8">
                    <div class="md:w-1/2 w-full">
                        @if ($feature['image'][0] ?? null)
                            <img src="{{ asset($feature['image'][0] ?? '') }}" class="rounded-lg shadow-lg w-full h-auto object-cover">
                        @elseif ($feature['video'][0] ?? null)
                            <video autoplay loop muted playsinline src="{{ asset($feature['video'][0] ?? '') }}" class="rounded-lg shadow-lg w-full h-auto object-cover"></video>
                        @endif
                                            
                    </div>
                    <div class="md:w-1/2 w-full prose prose-invert">
                        {!! $feature['description'] !!}
                    </div>
                </div>
                @if ($index < count($features) - 1)
                    <hr class="border-t border-pink-500 my-8">
                @endif
            @endforeach
        </div>

        <div class="flex justify-center py-8">
            <a href="{{ $content->field('steam') ?? '#' }}" target="_blank"
            class="inline-block bg-[#ff00ff] hover:bg-pink-500 text-black font-bold py-3 px-6 rounded shadow-lg transition duration-200 hover:shadow-pink-400/70">
                Available on Steam
            </a>
        </div>
        
    @endif
</div>
@endsection