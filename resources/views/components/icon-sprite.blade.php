{{--
    Every icon and game mark as an SVG symbol, drawn anywhere with <svg><use href="#i-name"/></svg>. The Blade <x-icon>
    and the JavaScript (public/js/ui.js) both use it, so there is one copy of the path data (App\View\Icons).
--}}
<svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false" style="position:absolute;width:0;height:0;overflow:hidden">
    @foreach (\App\View\Icons::OUTLINE as $name => $paths)
        <symbol id="i-{{ $name }}" viewBox="0 0 24 24">{!! $paths !!}</symbol>
    @endforeach
    @foreach (\App\View\Icons::SOLID as $name => $paths)
        <symbol id="i-{{ $name }}" viewBox="0 0 24 24">{!! $paths !!}</symbol>
    @endforeach
    @foreach (\App\View\Icons::MARKS as $name => $paths)
        <symbol id="m-{{ $name }}" viewBox="0 0 24 24">{!! $paths !!}</symbol>
    @endforeach
</svg>
