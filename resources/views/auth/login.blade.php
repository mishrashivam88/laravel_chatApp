<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Login</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container d-flex flex-column align-items-center justify-content-center" style="min-height: 100vh;">

    <!--  Success Message -->
    @if(session('success'))
        <div id="successAlert" class="alert alert-success w-50 text-center">
            {{ session('success') }}
        </div>
    @endif

    <!--  Error Message -->
    @if ($errors->any())
        <div id="errorAlert" class="alert alert-danger w-50">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card p-4 shadow-sm mt-3" style="width: 100%; max-width: 350px;">

        <h4 class="text-center mb-3">Login</h4>

        <form method="POST" action="{{ route('login.now') }}">
            @csrf

            <div class="mb-3">
                <label>Email</label>
                <input type="email" name="email" class="form-control"  required>
            </div>

            <div class="mb-3">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <div class="d-grid mb-2">
                <button type="submit" class="btn btn-primary">Login</button>
            </div>

            <div class="text-center">
                <div class="mb-2 text-end">
                    <a href="#">Forgot Password?</a>
                </div>
                <small>
                    Don't have an account?
                    <a href="{{ route('register') }}">Register</a>
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

    }, 2000);
</script>

</body>

</html>