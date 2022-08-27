<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Yatzy Game Scorer by Costs to Expect">
        <meta name="author" content="Dean Blackborough">
        <title>Yatzy Game Scorer: Account</title>
        <link rel="icon" sizes="48x48" href="{{ asset('images/favicon.ico') }}">
        <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('images/favicon.png') }}">
        <link href="{{ asset('css/theme.css') }}" rel="stylesheet" />
    </head>
    <body>
        <x-offcanvas active="account" />

        <div class="col-lg-8 mx-auto p-3 py-md-5">
            <main>
                <h2>Your account</h2>

                <p class="load">Manage your account details below.</p>

                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <strong>Name</strong>: {{ $user['name'] }}
                    </li>
                    <li class="list-group-item">
                        <strong>Email</strong>: {{ $user['email'] }}
                    </li>
                </ul>

                @if ($job !== null)
                    @if ($job === 'delete-yatzy-account')
                        <div class="alert alert-dark mt-5" role="alert">
                            <h4 class="alert-heading">Delete started!</h4>
                            <p>A job has been added to delete your account, we should be done in a minute or two!</p>
                            <p>You have been logged out, if you refresh you will be back at the login screen.</p>
                            <p>You will get an email when your account has been deleted.</p>
                        </div>
                    @endif
                @endif

                <h3 class="mt-5">Delete Yatzy account</h3>

                <p class="lead">This will delete all your Yatzy games and any data
                    specific to the Yatzy App.</p>

                <p>Please review the tables below to see what will be deleted and what will remain.</p>

                <h4>Data that will be deleted</h4>

                <p>All the data listed in this table will be deleted.</p>

                <div class="table-responsive">
                    <table class="table table-dark">
                        <thead>
                        <tr>
                            <th scope="col">Content</th>
                            <th scope="col">Description</th>
                            <th scope="col">Location</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td>Yatzy Games</td>
                            <td>Your open and complete games</td>
                            <td>API</td>
                        </tr>
                        <tr>
                            <td>Share Tokens</td>
                            <td>Public share tokens for open games</td>
                            <td>Yatzy</td>
                        </tr>
                        <tr>
                            <td>Game Log</td>
                            <td>The logs containing all game actions</td>
                            <td>API</td>
                        </tr>
                        <tr>
                            <td>Sessions</td>
                            <td>Session information</td>
                            <td>Yatzy</td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <h4>Data that will be not deleted</h4>

                <p>All the data listed in this table will remain.</p>

                <div class="table-responsive">
                    <table class="table table-dark">
                        <thead>
                        <tr>
                            <th scope="col">Content</th>
                            <th scope="col">Description</th>
                            <th scope="col">Location</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td>Account</td>
                            <td>Your Costs to Expect account, one account for all our Apps</td>
                            <td>API</td>
                        </tr>
                        <tr>
                            <td>Players</td>
                            <td>Players you created, usable across all our game scoring Apps</td>
                            <td>API</td>
                        </tr>
                        <tr>
                            <td>Other App</td>
                            <td>Your will still have access to all the other Costs to Expect Apps and
                                none of your data will be touched</td>
                            <td>API & Relevant Apps</td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <a href="{{ route('account.confirm-delete-yatzy-account') }}" class="btn btn-sm btn-danger">Delete Yatzy Account</a>

                <h3 class="mt-5">Delete Costs to Expect account</h3>

                <p class="lead">This will delete your Costs to Expect account, your Yatzy account will be
                    deleted along with all your other Costs to Expect accounts, API, Budget, Expense etc.</p>

                <p>Please review the table below to see what will be deleted, nothing will remain.</p>

                <h4>Data that will be deleted</h4>

                <div class="table-responsive">
                    <table class="table table-dark">
                        <thead>
                        <tr>
                            <th scope="col">Content</th>
                            <th scope="col">Description</th>
                            <th scope="col">Location</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td>Account</td>
                            <td>Your Costs to Expect account</td>
                            <td>API</td>
                        </tr>
                        <tr>
                            <td>Data</td>
                            <td>All the data we have stored will be deleted</td>
                            <td>API & all our Apps</td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <a href="{{ route('account.confirm-delete-account') }}" class="btn btn-sm btn-danger">Delete Account (Coming Soon *)</a>

                <p>* We need to update the API to support this, expect an update soon.</p>

            </main>
            <x-footer />
        </div>
        <script src="{{ asset('node_modules/bootstrap/dist/js/bootstrap.js') }}" defer></script>
    </body>
</html>
