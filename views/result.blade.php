@isset($css)
    @foreach ($css as $file)
        <link rel="stylesheet" href="{{ $file }}?{{ time() }}">
    @endforeach
@endisset

@isset($js)
    @foreach ($js as $file)
        <script src="{{ $file }}?{{ time() }}"></script>
    @endforeach
@endisset
