<div class="toast-container position-fixed top-50 start-50 translate-middle p-3">
    <div id="toast_yatzy" class="toast bg-primary" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-body text-white">
            <h2>{{ $toast_yatzy['heading'] }}</h2>
            <p class="mb-0">{{ $toast_yatzy['message'] }}</p>
        </div>
    </div>
    <div id="toast_yatzy_scratch" class="toast bg-primary" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-body text-white">
            <h2>{{ $toast_yatzy_scratch['heading'] }}</h2>
            <p class="mb-0">{{ $toast_yatzy_scratch['message'] }}</p>
        </div>
    </div>
    <div id="toast_done" class="toast bg-primary" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-body text-white">
            <h2>Done!</h2>
            <p class="mb-0">You scored <span id="final-score">0</span>, when everyone has finished
                their final turn we will see how you did</p>
        </div>
    </div>
</div>