<div class="toast-container position-fixed top-50 start-50 translate-middle p-3">
    <div id="toast_yahtzee" class="toast bg-primary" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-body text-white">
            <h2>{{ $toast_yahtzee['heading'] }}</h2>
            <p class="mb-0">{{ $toast_yahtzee['message'] }}</p>
        </div>
    </div>
    <div id="toast_yahtzee_scratch" class="toast bg-primary" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-body text-white">
            <h2>{{ $toast_yahtzee_scratch['heading'] }}</h2>
            <p class="mb-0">{{ $toast_yahtzee_scratch['message'] }}</p>
        </div>
    </div>
    <div id="toast_yahtzee_bonus_one" class="toast bg-primary" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-body text-white">
            <h2>{{ $toast_yahtzee_bonus_one['heading'] }}</h2>
            <p class="mb-0">{{ $toast_yahtzee_bonus_one['message'] }}</p>
        </div>
    </div>
    <div id="toast_yahtzee_bonus_two" class="toast bg-primary" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-body text-white">
            <h2>{{ $toast_yahtzee_bonus_two['heading'] }}</h2>
            <p class="mb-0">{{ $toast_yahtzee_bonus_two['message'] }}</p>
        </div>
    </div>
    <div id="toast_yahtzee_bonus_three" class="toast bg-primary" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-body text-white">
            <h2>{{ $toast_yahtzee_bonus_three['heading'] }}</h2>
            <p class="mb-0">{{ $toast_yahtzee_bonus_three['message'] }}</p>
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