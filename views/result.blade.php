@isset($css)
    @foreach ($css as $file)
        <link rel="stylesheet" href="{{ $file }}{{ $suffix }}">
    @endforeach
@endisset

@isset($js)
    @foreach ($js as $file)
        <script src="{{ $file }}{{ $suffix }}"></script>
    @endforeach
@endisset
