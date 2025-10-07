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
                            <img src="{{ asset($feature['image'][0] ?? '') }}" class="retro-screen rounded-lg shadow-lg w-full h-auto object-cover">
                        @elseif ($feature['video'][0] ?? null)
                            <video autoplay loop muted playsinline src="{{ asset($feature['video'][0] ?? '') }}" class="retro-screen rounded-lg shadow-lg w-full h-auto object-cover"></video>
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

    @if ($levels = $content->children()->where('type_slug', 'level')->get())
        @if ($levels->count())
            <div class="max-w-6xl mx-auto py-12">
                <h2 class="text-3xl font-bold mb-6 text-center">Levels</h2>
                <p class="text-center text-lg mb-8">
                    Explore a variety of unique Kabonk! breakout levels, each offering its own distinct challenge and style.
                </p>
                <div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8">
                        @foreach ($levels as $level)
                            <div class="rounded-xl p-6 flex flex-col items-center bg-black/30">
                                @if ($level->field('screenshot')[0] ?? null)
                                    <img src="{{ asset($level->field('screenshot')[0]) }}" alt="{{ $level->field('name') }} image" class="retro-screen rounded-lg shadow-lg w-full h-auto object-cover mb-4">
                                @endif
                                <h3 class="text-xl font-semibold mb-2 text-center">{{ $level->field('name') }}</h3>
                                <div class="prose prose-invert text-center">{!! $level->field('description') !!}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
    @push('scripts-head')
    <script src="https://cdn.jsdelivr.net/npm/matter-js@0.20.0/build/matter.min.js"></script>
    @endpush
    @push('styles')
        <style>
            #footer canvas {
                width: 100%;
                margin: 0 auto;
            }
            .retro-screen {
                filter: grayscale(.8) contrast(1.2) brightness(1.1);
                -webkit-box-sizing: content-box;
                -moz-box-sizing: content-box;
                box-sizing: content-box;
                position: relative;
                margin: 0;
                border: none;
                -webkit-border-radius: 50% / 10%;
                border-radius: 50% / 10%;
                font: normal 100%/normal Arial, Helvetica, sans-serif;
                color: white;
                text-align: center;
                text-indent: 0.1em;
                -o-text-overflow: clip;
                text-overflow: clip;
                background: #fff;
                border-top: 3px solid #CDCDCD;
                border-bottom: 3px solid #CDCDCD;
                transform: scale(0.9);
                transition: scale 0.5s, filter 0.5s, box-shadow 0.5s, transform 0.5s, border-radius 0.5s;
            }

            .retro-screen::before {
                -webkit-box-sizing: content-box;
                -moz-box-sizing: content-box;
                box-sizing: content-box;
                position: absolute;
                content: "";
                top: 10%;
                right: -5%;
                bottom: 10%;
                left: -5%;
                border-left: 3px solid #CDCDCD;
                border-right: 3px solid #CDCDCD;
                -webkit-border-radius: 5% / 50%;
                border-radius: 5% / 50%;
                font: normal 100%/normal Arial, Helvetica, sans-serif;
                color: rgba(0,0,0,1);
                -o-text-overflow: clip;
                text-overflow: clip;
                background: #fff;
                text-shadow: none;

            }
            .retro-screen:hover {
                filter: none;
                border-radius: 0;
                border-color: #f0f;
                box-shadow: 0 4px 24px 0 rgba(255,0,255,0.15);
                transition: filter 0.5s, box-shadow 0.5s, transform 0.5s, border-radius 0.5s;
                transform: scale(1);
            }
        </style>
    @endpush
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
            // module aliases
            var Engine = Matter.Engine,
                Render = Matter.Render,
                Runner = Matter.Runner,
                Bodies = Matter.Bodies,
                Composite = Matter.Composite,
                Events = Matter.Events;

            // create an engine
            var engine = Engine.create({
                gravity: { y: 0, x: 0 }
            });

            var defaultCategory = 0x0001,
                blueCategory = 0x0002,
                pinkCategory = 0x0004;
            var colorA = 'rgba(255,0,255,.8)';
            var colorB = 'rgba(234,251,254,.8)';

            var boxSize = 20;
            var gameWidth = 800;
            var gameHeight = 80;
            // Store all rectangles for easy access
            var rectangles = [];

            for (var x = 0; x < gameWidth; x += boxSize) {
                for (var y = 0; y < gameHeight; y += boxSize) {
                    var color = (x > gameWidth / 2) ? colorA : colorB;
                    var category = (color === colorA) ? pinkCategory : blueCategory;
                    var box = Bodies.rectangle(x + boxSize / 2, y + boxSize / 2, boxSize, boxSize, {
                        isStatic: true,
                        render: {
                            fillStyle: color
                        },
                        collisionFilter: {
                            category: category
                        }
                    });
                    rectangles.push(box);
                    Composite.add(engine.world, box);
                }
            }

            // create a renderer
            var render = Render.create({
                element: document.getElementById('footer'),
                engine: engine,
                options: {
                    transparent: true,
                    background: 'transparent',
                    width: gameWidth,
                    height: gameHeight,
                    wireframes: false
                }
            });

            // create two boxes and a ground
            var ballA = Bodies.circle(gameWidth / 1.3, gameHeight / 2, boxSize/3, {
                frictionAir: 0,
                friction: 0,
                frictionStatic: 0,
                inertia: Infinity,
                restitution: 1,
                render: {
                    fillStyle: '#a3e0fc'
                },
                collisionFilter: {
                    mask: defaultCategory | blueCategory
                },
            });
            var ballB = Bodies.circle(gameWidth / 3, gameHeight / 2, boxSize/3, {
                frictionAir: 0,
                friction: 0,
                frictionStatic: 0,
                inertia: Infinity,
                restitution: 1,
                render: {
                    fillStyle: '#ff00ff'
                },
                
                collisionFilter: {
                    mask: defaultCategory | pinkCategory
                },
            });

            Events.on(engine, 'collisionStart', function(event) {
                event.pairs.forEach(function(pair) {
                    var bodies = [pair.bodyA, pair.bodyB];

                    // Ensure minimum velocity after collision between ballA and ballB
                    if (
                        (bodies.includes(ballA))
                    ) {
                        [ballA].forEach(function(ball) {
                            let vx = ball.velocity.x;
                            let vy = ball.velocity.y;
                            if (Math.abs(vx) < 5) {
                                vx = 5 * Math.sign(vx || (Math.random() - 5));
                            }
                            if (Math.abs(vy) < 5) {
                                vy = 5 * Math.sign(vy || (Math.random() - 5));
                            }
                            Matter.Body.setVelocity(ball, { x: vx, y: vy });
                        });
                    }
                    if (
                        (bodies.includes(ballB))
                    ) {
                        [ballB].forEach(function(ball) {
                            let vx = ball.velocity.x;
                            let vy = ball.velocity.y;
                            if (Math.abs(vx) < 5) {
                                vx = 5 * Math.sign(vx || (Math.random() - 5));
                            }
                            if (Math.abs(vy) < 5) {
                                vy = 5 * Math.sign(vy || (Math.random() - 5));
                            }
                            Matter.Body.setVelocity(ball, { x: vx, y: vy });
                        });
                    }

                    var otherBody = null;
                    if (bodies.includes(ballA)) {
                        otherBody = pair.bodyA === ballA ? pair.bodyB : pair.bodyA;
                    } else if (bodies.includes(ballB)) {
                        otherBody = pair.bodyA === ballB ? pair.bodyB : pair.bodyA;
                    }
                    if (otherBody && otherBody.isStatic) {
                        // Check if it's a blue or pink rectangle
                        if (otherBody.collisionFilter.category === blueCategory) {
                            otherBody.collisionFilter.category = pinkCategory;
                            otherBody.render.fillStyle = colorA;
                        } else if (otherBody.collisionFilter.category === pinkCategory) {
                            otherBody.collisionFilter.category = blueCategory;
                            otherBody.render.fillStyle = colorB;
                        }
                    }
                });
            });

            // add all of the bodies to the world
            Composite.add(engine.world, [ballA, ballB]);

            // Add walls
            var walls = [
                Bodies.rectangle(gameWidth / 2, -25, gameWidth, 50, { isStatic: true, restitution: 1 }),
                Bodies.rectangle(gameWidth / 2, gameHeight + 25, gameWidth, 50, { isStatic: true, restitution: 1 }),
                Bodies.rectangle(-25, gameHeight / 2, 50, gameHeight, { isStatic: true, restitution: 1 }),
                Bodies.rectangle(gameWidth + 25, gameHeight / 2, 50, gameHeight, { isStatic: true, restitution: 1 })
            ];
            Composite.add(engine.world, walls);

            // move ballA and ballB in a random direction
            let speed = 20;
            Matter.Body.setVelocity(ballA, { x: (Math.random() - 0.5) * speed, y: (Math.random() - 0.5) * speed });
            Matter.Body.setVelocity(ballB, { x: (Math.random() - 0.5) * speed, y: (Math.random() - 0.5) * speed });

            // Add mouse click event handling
            render.canvas.addEventListener('click', function(event) {
                var rect = render.canvas.getBoundingClientRect();
                var mouseX = event.clientX - rect.left;
                var mouseY = event.clientY - rect.top;
                
                // Convert screen coordinates to world coordinates
                // Account for canvas scaling (canvas world size vs displayed size)
                var scaleX = gameWidth / rect.width;
                var scaleY = gameHeight / rect.height;
                var worldX = mouseX * scaleX;
                var worldY = mouseY * scaleY;
                
                // Check which rectangle was clicked
                rectangles.forEach(function(rectangle) {
                    var bounds = rectangle.bounds;
                    if (worldX >= bounds.min.x && worldX <= bounds.max.x &&
                        worldY >= bounds.min.y && worldY <= bounds.max.y) {
                        
                        // Switch the category and color of the clicked rectangle
                        if (rectangle.collisionFilter.category === blueCategory) {
                            rectangle.collisionFilter.category = pinkCategory;
                            rectangle.render.fillStyle = colorA;
                        } else if (rectangle.collisionFilter.category === pinkCategory) {
                            rectangle.collisionFilter.category = blueCategory;
                            rectangle.render.fillStyle = colorB;
                        }
                    }
                });
            });

            // run the renderer
            Render.run(render);

            // create runner
            var runner = Runner.create();

            // run the engine
            Runner.run(runner, engine);
        });
    </script>
    @endpush
@endsection