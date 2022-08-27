<div class="table-responsive">

    <hr />

    <h2 class="text-primary">Player Scores</h2>

    <table class="table table-striped table-sm">
        <caption>Player scores, delayed by ten seconds.</caption>
        <thead>
            <tr>
                <th scope="col">Name</th>
                <th scope="col">Turns</th>
                <th scope="col">Upper</th>
                <th scope="col">Lower</th>
                <th scope="col">Total</th>
            </tr>
        </thead>
        <tbody class="table-group-divider">
            @foreach($scores as $__score)
            <tr @if ($__score['turns'] === 13) class="table-success" @endif>
                <th scope="row">{{ $__score['name'] }}</th>
                <td>{{ $__score['turns'] }}</td>
                <td>{{ $__score['upper'] }}</td>
                <td>{{ $__score['lower'] }}</td>
                <th scope="row">{{ $__score['total'] }}</th>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>