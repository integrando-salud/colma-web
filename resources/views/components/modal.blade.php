<div class="modal fade" id="{{ $id }}" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content bg-body-tertiary rounded-0 border-3 border-dark-subtle">
            <div class="modal-header border-0">
                @isset($title)
                    <h5 class="modal-title text-primary-emphasis">{{ $title }}</h5>
                @endisset
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                {{ $slot }}
            </div>
            @isset($footer)
                <div class="modal-footer border-0">
                    {{ $footer }}
                </div>
            @endisset
        </div>
    </div>
</div>
