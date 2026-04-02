<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container d-flex align-items-center justify-content-center flex-column" style="min-height: 100vh;">
    
    <!--  Success Message -->
    @if(session('success'))
        <div id="successAlert" class="alert alert-success w-50 text-center">
            {{ session('success') }}
        </div>
    @endif

    <!--  Error Message (Validation + Custom) -->
    @if ($errors->any())
        <div id="errorAlert" class="alert alert-danger w-50">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card p-4 shadow-sm mt-3" style="width: 100%; max-width: 400px;">
        
        <h4 class="text-center mb-3">Register</h4>

        <form method="POST" action="{{ route('register.now') }}" enctype="multipart/form-data">
            @csrf

            <div class="mb-3">
                <label>Name</label>
                <input type="text" name="name" class="form-control" required>
            </div>

            <div class="mb-3">
                <label>Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>

            <div class="mb-3">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <div class="mb-3">
                <label>Confirm Password</label>
                <input type="password" name="password_confirmation" class="form-control" required>
            </div>

            <div class="mb-3">
                <label>Profile Picture</label>
                <input type="file" name="profile_img" class="form-control">
            </div>

            <div class="d-grid mb-2">
                <button type="submit" class="btn btn-primary">Register</button>
            </div>

            <div class="text-center">
                <small>
                    Already have an account? 
                    <a href="{{ route('login') }}">Login</a>
                </small>
            </div>

        </form>

    </div>

</div>

<!--  Auto Hide Script -->
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

    }, 2000); // 2 sec
</script>

</body>
</html>