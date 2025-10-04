<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
    @foreach ($images as $index => $screenshot)
        <div class="relative group">
            <a href="{{ asset($screenshot) }}" class="lightbox" data-lightbox="image-{{ $index }}">
                <img src="{{ asset($screenshot) }}" class="w-full h-auto rounded-lg shadow-lg group-hover:shadow-2xl transition-shadow duration-300">
            </a>
        </div>
    @endforeach
</div>

@push('styles')
    <style>
        .lightbox-modal {
            position: fixed;
            z-index: 50;
            inset: 0;
            background: rgba(0,0,0,0.85);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .lightbox-modal img {
            max-width: 90vw;
            max-height: 90vh;
            border-radius: 0.5rem;
            box-shadow: 0 0 40px #000a;
        }
        .lightbox-modal .close-btn {
            position: absolute;
            top: 2rem;
            right: 2rem;
            font-size: 2rem;
            color: #fff;
            cursor: pointer;
            background: none;
            border: none;
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('a.lightbox').forEach(function(link) {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    let modal = document.createElement('div');
                    modal.className = 'lightbox-modal';
                    modal.innerHTML = `
                        <button class="close-btn" aria-label="Close">&times;</button>
                        <img src="${this.href}" alt="">
                    `;
                    document.body.appendChild(modal);
                    modal.querySelector('.close-btn').onclick = function() {
                        modal.remove();
                    };
                    modal.onclick = function(ev) {
                        if (ev.target === modal) modal.remove();
                    };
                    document.addEventListener('keydown', function escListener(ev) {
                        if (ev.key === 'Escape') {
                            modal.remove();
                            document.removeEventListener('keydown', escListener);
                        }
                    });
                });
            });
        });
    </script>
@endpush