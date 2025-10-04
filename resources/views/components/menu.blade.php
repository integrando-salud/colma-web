<nav>
    <ul class="list-unstyled mb-0 list-inline">
        @foreach($items as $item)
            <li @class(['list-inline-item', 'me-4' => !$loop->last])>
                <a href="{{ $item['link'] }}" class="fs-4 fw-semibold text-primary-emphasis text-decoration-none">{{ $item['title'] }}</a>
            </li>
        @endforeach
    </ul>
</nav>
