<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Reviews</title>
    <link rel="stylesheet" href="{{ asset('css/review.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>

<body>
    <div class="sidebar">
        <div class="sidebar-logo">
        <img src="{{ asset('images/kingsalon.png') }}" alt="Admin Logo" style="width: 200px; height: auto;">
        </div>
        <ul>
            <li><a href="{{ route('admin.dashboard') }}"><i class="fas fa-home"></i> Dashboard</a></li>
            <!-- <li><a href="{{ route('appointments.index') }}"><i class="fas fa-calendar"></i> Appointments</a></li> -->
            <li><a href="{{ route('services.index') }}"><i class="fas fa-cut"></i> Services</a></li>
            <li><a href="{{ route('clients.index') }}"><i class="fas fa-user"></i> Clients</a></li>
            <li class="has-submenu">
                <a href="#" class="submenu-toggle"><i class="fas fa-users"></i> Members</a>
                <ul class="submenu">
                    <li><a href="{{ route('owners.index') }}"><i class="fas fa-user"></i> Owner</a></li>
                    <li><a href="{{ route('managers.index') }}"> <i class="fas fa-user"></i> Manager</a></li>
                    <li><a href="{{ route('staff.index') }}"> <i class="fas fa-user"></i> Staff</a></li>
                    <li><a href="{{ route('cashiers.index') }}"> <i class="fas fa-user"></i> Cashier</a></li>
                </ul>
            </li>
            <li><a href="{{ route('admin.products.index') }}"><i class="fas fa-box"></i>Products</a></li>
            <li><a href="{{ route('reviews.index') }}"> <i class="fas fa-star"></i> Reviews</a></li>
        </ul>
    </div>

    <div class="container">
        <h1 class="title">All Reviews</h1>

        @if (session('error'))
        <div class="alert error">
            {{ session('error') }}
            <button class="close-btn">&times;</button>
        </div>
        @endif

        @if ($reviews->isEmpty())
        <div class="alert info">No reviews available at the moment.</div>
        @else
        <div class="table-container">
            <table class="review-table">
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Profile Image</th>
                        <th>Rating</th>
                        <th>Review</th>
                        <th>Service Name</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($reviews as $review)
                    <tr>
                        <td>{{ $review->full_name }}</td>
                        <td class="text-center">
                            <img src="{{ $review->profile_image }}" alt="Profile Image" class="profile-img">
                        </td>
                        <td>{{ $review->rating }} <span class="star">&#9733;</span></td>
                        <td>{{ $review->review_message }}</td>
                        <td>{{ $review->service_name }}</td>
                        <td>{{ $review->created_at->format('d M Y') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</body>

</html>