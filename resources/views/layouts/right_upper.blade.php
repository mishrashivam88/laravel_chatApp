<div id="chat-box">
    <div id="chat-header" class="d-flex align-items-center gap-2">
        <img id="chat-user-img" src="{{ asset('storage/profile_images/'.Auth::user()->profile_img) }}" width="40" class="rounded-circle">
        <h5 id="chat-user-name">Heyy {{ Auth::user()->name }}</h5>
    </div>
</div>