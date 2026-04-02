<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">

    <style>
        body {
            background: #f2f2f2;
            font-family: Arial, sans-serif;
        }
        .profile-card {
            max-width: 500px;
            margin: 50px auto;
            padding: 2rem;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .profile-img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>

    @include('layouts.navbar')

    <div class="container">
        <!-- Flash Messages -->
        <div class="mt-3">

        </div>
<div style="height: 88.5vh;">
        <div class="profile-card text-center">
            <h3 class="mb-4">Your Profile</h3>

            <form action="{{ route('profile.update.now' , Auth::id()) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PATCH')

                <!-- Profile Image -->
                <div class="mb-3">
                    <img src="{{ Auth::user()->profile_img ? asset('storage/profile_images/' . Auth::user()->profile_img) : 'https://via.placeholder.com/120' }}" alt="Profile Image" class="profile-img">
                </div>
                <div class="mb-3">
                    <input type="file" name="profile_img" class="form-control" accept="image/*">
                </div>

                <!-- Name -->
                <div class="mb-3 text-start">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" value="{{ Auth::user()->name }}" class="form-control" required>
                </div>

                <!-- Email (readonly) -->
                <div class="mb-3 text-start">
                    <label class="form-label">Email</label>
                    <input type="email" value="{{ Auth::user()->email }}" class="form-control" readonly>
                </div>

                <button type="submit" class="btn btn-primary w-100">Update Profile</button>
            </form>
        </div>
    </div>
</div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>