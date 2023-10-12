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
                <h2>Delete account</h2>

                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <strong>Name</strong>: {{ $user['name'] }}
                    </li>
                    <li class="list-group-item">
                        <strong>Email</strong>: {{ $user['email'] }}
                    </li>
                </ul>

                <p class="lead">We will immediately create a background task to delete your data, the task should
                    start within a minute, once it completes your data will be gone and your session
                    will be deleted.</p>

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

                <form action="{{ route('account.delete-account') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-danger">Confirm Delete (Cannot be undone)</button>
                    <a href="{{ route('account') }}" class="btn btn-sm btn-primary">Cancel</a>
                </form>

            </main>
            <x-footer />
        </div>
        <script src="{{ asset('node_modules/bootstrap/dist/js/bootstrap.js') }}" defer></script>
    </body>
</html>
