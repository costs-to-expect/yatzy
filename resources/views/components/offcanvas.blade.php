<nav class="navbar navbar-dark bg-dark" aria-label="Offcanvas navbar">
    <div class="container-fluid">
        @auth
        <a class="navbar-brand" href="{{ route('home') }}">
            Yatzy by <img src="{{ asset('images/logo.png') }}" width="30" height="30" class="d-inline-block align-middle" alt=""><span class="d-none">C</span>osts to Expect.com
        </a>
        @endauth

        @guest
            <a class="navbar-brand" href="/">
                Yatzy by <img src="{{ asset('images/logo.png') }}" width="30" height="30" class="d-inline-block align-middle" alt=""><span class="d-none">C</span>osts to Expect.com
            </a>
        @endguest
        <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbarDark" aria-controls="offcanvasNavbarDark">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="offcanvas offcanvas-end text-bg-dark" tabindex="-1" id="offcanvasNavbarDark" aria-labelledby="offcanvasNavbarDarkLabel">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title" id="offcanvasNavbarDarkLabel">Yatzy</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <ul class="navbar-nav justify-content-end flex-grow-1 pe-3">
                    <li class="nav-item">
                        <a class="nav-link @if($active === 'home') active @endif" @if($active === 'home') aria-current="page" @endif href="{{ route('home') }}">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link @if($active === 'games') active @endif" @if($active === 'games') aria-current="page" @endif href="{{ route('games') }}">Games</a>
                    </li>
                    <li class="nav-item ps-3">
                        <a class="nav-link" href="{{ route('game.create.view') }}">- New Game</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link @if($active === 'players') active @endif" @if($active === 'players') aria-current="page" @endif href="{{ route('players') }}">Players</a>
                    </li>
                    <li class="nav-item ps-3">
                        <a class="nav-link" href="{{ route('player.create.view') }}">- New Player</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="https://api.costs-to-expect.com">The API</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link @if($active === 'account') active @endif" @if($active === 'account') aria-current="page" @endif href="{{ route('account') }}">Account</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('sign-out') }}">Sign-out</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>