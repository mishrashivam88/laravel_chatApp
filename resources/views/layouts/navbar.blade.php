<nav class="navbar navbar-expand-lg navbar-light bg-secondary shadow-sm">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="#">Chat App</a>

    <div class="dropdown ms-auto">
      <button class="btn btn-light dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
        {{ Auth::user()->name }}
      </button>
      <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
        <li><a class="dropdown-item" href="{{ route('profile' , Auth::id()) }}">Profile</a></li>

        <li>
          <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal">
            Logout
          </a>
        </li>

      </ul>
    </div>
  </div>
</nav>

<!-- Flash Messages -->
<div class="container mt-2">
    @if(session('success'))
        <div id="successAlert" class="alert alert-success text-center">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div id="errorAlert" class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</div>

<!-- Logout Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      
      <div class="modal-header">
        <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body text-center">
        <i class="fas fa-sign-out-alt fa-2x mb-3 text-danger"></i>
        <p>Are you sure you want to logout?</p>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>

        <!--  Logout Form -->
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-danger">Logout</button>
        </form>
      </div>

    </div>
  </div>
</div>

<script>
    setTimeout(() => {
        let success = document.getElementById('successAlert');
        let error = document.getElementById('errorAlert');

        if (success) {
            success.style.transition = "opacity 0.5s";
            success.style.opacity = "0";
            setTimeout(() => success.remove(), 500);
        }

        if (error) {
            error.style.transition = "opacity 0.5s";
            error.style.opacity = "0";
            setTimeout(() => error.remove(), 500);
        }
    }, 2000);
</script>