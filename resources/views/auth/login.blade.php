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
                                <h4 class="mb-1 fw-semibold">Hi, Welcome back!</h4>
                                <p class="mb-4 text-muted fw-normal">Please enter your credentials</p>
                            </div>

                            <!-- Session Status -->
                            @if (session('status'))
                                <div class="alert alert-success" role="alert">
                                    {{ session('status') }}
                                </div>
                            @endif

                            <form method="POST" action="{{ route('login') }}">
                                @csrf
                                
                                <div class="row gy-3">
                                    <!-- Email Address -->
                                    <div class="col-xl-12">
                                        <label for="email" class="form-label text-default">Email</label>
                                        <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                               id="email" name="email" 
                                               placeholder="Enter Email" 
                                               value="{{ old('email') }}" 
                                               required autofocus autocomplete="username">
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- Password -->
                                    <div class="col-xl-12 mb-2">
                                        <label for="password" class="form-label text-default d-block">Password</label>
                                        <div class="position-relative">
                                            <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                                   id="password" name="password" 
                                                   placeholder="Enter Password" 
                                                   required autocomplete="current-password">
                                            <a href="javascript:void(0);" class="show-password-button text-muted" 
                                               onclick="createpassword('password',this)" id="button-addon2">
                                                <i class="ri-eye-off-line align-middle"></i>
                                            </a>
                                            @error('password')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <!-- Remember Me & Forgot Password -->
                                        <div class="mt-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" 
                                                       name="remember" id="remember_me">
                                                <label class="form-check-label" for="remember_me">
                                                    Remember me
                                                </label>
                                                @if (Route::has('password.request'))
                                                    <a href="{{ route('password.request') }}" 
                                                       class="float-end link-danger fw-medium fs-12">
                                                        Forgot password?
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Sign In Button -->
                                <div class="d-grid mt-3">
                                    <button type="submit" class="btn btn-primary">Sign In</button>
                                </div>
                            </form>

                            <!-- Register Link -->
                            @if (Route::has('register'))
                                <div class="text-center mt-3 fw-medium">
                                    Don't have an account? <a href="{{ route('register') }}" class="text-primary">Register Here</a>
                                </div>
                            @endif
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
