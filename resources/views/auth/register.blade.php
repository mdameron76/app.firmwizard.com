@extends('layouts.custom-master')

@php
// Passing the bodyClass variable from the view to the layout
$bodyClass = 'authentication-background';
@endphp

@section('styles')
@endsection

@section('content')
	
        <div class="authentication-basic-background">
            <img src="{{asset('build/assets/images/media/backgrounds/9.png')}}" alt="">
        </div>

        <div class="container">
            <div class="row justify-content-center align-items-center authentication authentication-basic h-100">
                <div class="col-xxl-4 col-xl-5 col-lg-6 col-md-6 col-sm-8 col-12">
                    <div class="card custom-card border-0 my-4">
                        <div class="card-body p-5">
                            <div class="mb-4"> 
                                <a href="{{url('/')}}"> 
                                    <img src="{{asset('build/assets/images/brand-logos/toggle-logo.png')}}" alt="logo" class="desktop-dark"> 
                                </a> 
                            </div>
                            <div>
                                <h4 class="mb-1 fw-semibold">Sign Up</h4>
                                <p class="mb-4 text-muted fw-normal">Join us by creating a free account!</p>
                            </div>

                            <form method="POST" action="{{ route('register') }}">
                                @csrf
                                
                                <div class="row gy-3">
                                    <!-- Name -->
                                    <div class="col-xl-12">
                                        <label for="name" class="form-label text-default">Full Name</label>
                                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                               id="name" name="name" 
                                               placeholder="Enter Full Name" 
                                               value="{{ old('name') }}" 
                                               required autofocus autocomplete="name">
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- Email -->
                                    <div class="col-xl-12">
                                        <label for="email" class="form-label text-default">Email</label>
                                        <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                               id="email" name="email" 
                                               placeholder="Enter Email" 
                                               value="{{ old('email') }}" 
                                               required autocomplete="username">
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- Password -->
                                    <div class="col-xl-12">
                                        <label for="password" class="form-label text-default d-block">Password</label>
                                        <div class="position-relative">
                                            <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                                   id="password" name="password" 
                                                   placeholder="Enter Password" 
                                                   required autocomplete="new-password">
                                            <a href="javascript:void(0);" class="show-password-button text-muted" 
                                               onclick="createpassword('password',this)" id="button-addon2">
                                                <i class="ri-eye-off-line align-middle"></i>
                                            </a>
                                            @error('password')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <!-- Confirm Password -->
                                    <div class="col-xl-12 mb-2">
                                        <label for="password_confirmation" class="form-label text-default d-block">Confirm Password</label>
                                        <div class="position-relative">
                                            <input type="password" class="form-control @error('password_confirmation') is-invalid @enderror" 
                                                   id="password_confirmation" name="password_confirmation" 
                                                   placeholder="Confirm Password" 
                                                   required autocomplete="new-password">
                                            <a href="javascript:void(0);" class="show-password-button text-muted" 
                                               onclick="createpassword('password_confirmation',this)" id="button-addon3">
                                                <i class="ri-eye-off-line align-middle"></i>
                                            </a>
                                            @error('password_confirmation')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Create Account Button -->
                                <div class="d-grid mt-3">
                                    <button type="submit" class="btn btn-primary">Create Account</button>
                                </div>
                            </form>

                            <!-- Sign In Link -->
                            <div class="text-center mt-3 fw-medium">
                                Already have an account? <a href="{{ route('login') }}" class="text-primary">Sign In</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

@endsection

@section('scripts')
        <!-- Show Password JS -->
        <script src="{{asset('build/assets/show-password.js')}}"></script>
@endsection
