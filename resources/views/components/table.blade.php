<div class="table-responsive">
    <table class="table table-striped table-borderless border text-center">
        @isset($header)
            <thead>
                {{ $header }}
            </thead>
        @endisset
        <tbody>
            {{ $slot }}
        </tbody>
        @isset($footer)
            <tfoot>
                {{ $footer }}
            </tfoot>
        @endisset
    </table>
</div>
