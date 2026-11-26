@props(['active' => 'books'])

@can('books.manage')

    <nav class="catalog-nav" aria-label="Book catalog navigation">

        @php
            $items = [
                'books' => ['Books', 'bi-book'],
                'genres' => ['Genres', 'bi-tags'],
                'authors' => ['Authors', 'bi-person'],
                'publishers' => ['Publishers', 'bi-building'],
            ];
        @endphp

        @foreach($items as $key => $item)

            <a
                href="{{ $key === 'books'
                    ? route('books.index')
                    : route('catalog.index', $key) }}"
                class="catalog-nav-link {{ $active === $key ? 'is-active' : '' }}"
                @if($active === $key)
                    aria-current="page"
                @endif
            >
                <i class="bi {{ $item[1] }}"
                   aria-hidden="true"></i>

                <span>{{ $item[0] }}</span>
            </a>

        @endforeach

    </nav>

@endcan