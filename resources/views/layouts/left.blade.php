    
    <style>
        .contact-info img,
#chat-user-img {
    width: 40px;
    height: 40px;
    object-fit: cover;   /* image crop ho jayegi nicely */
    border-radius: 50%;  /* perfect circle */
}
    </style>
    @php
    use App\Models\User ;
    $users = User::whereNot('id' , Auth::id())->get();
    @endphp
    <div id="contacts">
        <div id="contacts-header">
            <div id="contacts-search" class="mt-2">
                <input type="text" id="searchInput" class="form-control" placeholder="Search contacts">
            </div>
        </div>

        <div id="contacts-list">
            @foreach ($users as $user)
    <div class="contact-item"
         data-id="{{ $user->id }}"
         data-name="{{ $user->name }}"
         data-image="{{ asset('storage/profile_images/'.$user->profile_img) }}">
        
        <div class="contact-info">
            <img src="{{ asset('storage/profile_images/'.$user->profile_img) }}">
            <span class="badge bg-success badge-dot"></span>
            <div>
                <p class="mb-0 fw-bold">{{ $user->name }}</p>
                <small class="text-muted">Hello!</small>
            </div>
        </div>

        <small class="text-muted">Now</small>
    </div>
           @endforeach
                      
            <!-- <div class="contact-item">
                <div class="contact-info">
                    <img src="https://mdbcdn.b-cdn.net/img/Photos/new-templates/bootstrap-chat/ava2-bg.webp">
                    <span class="badge bg-warning badge-dot"></span>
                    <div>
                        <p class="mb-0 fw-bold">Alexa</p>
                        <small class="text-muted">Hi there!</small>
                    </div>
                </div>
                <small class="text-muted">5 min</small>
            </div> -->
            <!-- More contacts -->
        </div>
    </div>


    <script>

//search logic 
const searchInput = document.getElementById('searchInput');
const contactsList = document.getElementById('contacts-list');
const contacts = contactsList.getElementsByClassName('contact-item');

searchInput.addEventListener('input', function() {
    const filter = this.value.toLowerCase();

    Array.from(contacts).forEach(contact => {
        const name = contact.getAttribute('data-name').toLowerCase();
        if(name.includes(filter)) {
            contact.style.display = '';
        } else {
            contact.style.display = 'none';
        }
    });
});
</script>