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
                                <h4 class="mb-1 fw-semibold">Reset Password</h4>
                                <p class="mb-4 text-muted fw-normal">Set your new password here.</p>
                            </div>

                            <form method="POST" action="{{ route('password.store') }}">
                                @csrf

                                <!-- Password Reset Token -->
                                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                                <div class="row gy-3">
                                    <!-- Email Address -->
                                    <div class="col-xl-12">
                                        <label for="email" class="form-label text-default">Email</label>
                                        <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                               id="email" name="email" 
                                               placeholder="Enter Email" 
                                               value="{{ old('email', $request->email) }}" 
                                               required autofocus autocomplete="username">
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- New Password -->
                                    <div class="col-xl-12">
                                        <label for="password" class="form-label text-default">New Password</label>
                                        <div class="position-relative">
                                            <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                                   id="password" name="password" 
                                                   placeholder="New Password" 
                                                   required autocomplete="new-password">
                                            <a href="javascript:void(0);" class="show-password-button text-muted" 
                                               onclick="createpassword('password',this)" id="button-addon21">
                                                <i class="ri-eye-off-line align-middle"></i>
                                            </a>
                                            @error('password')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <!-- Confirm Password -->
                                    <div class="col-xl-12">
                                        <label for="password_confirmation" class="form-label text-default">Confirm Password</label>
                                        <div class="position-relative">
                                            <input type="password" class="form-control @error('password_confirmation') is-invalid @enderror" 
                                                   id="password_confirmation" name="password_confirmation" 
                                                   placeholder="Confirm Password" 
                                                   required autocomplete="new-password">
                                            <a href="javascript:void(0);" class="show-password-button text-muted" 
                                               onclick="createpassword('password_confirmation',this)" id="button-addon22">
                                                <i class="ri-eye-off-line align-middle"></i>
                                            </a>
                                            @error('password_confirmation')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Reset Password Button -->
                                <div class="d-grid mt-3">
                                    <button type="submit" class="btn btn-primary">Reset Password</button>
                                </div>
                            </form>

                            <!-- Back to Login Link -->
                            <div class="text-center mt-3 fw-medium">
                                Don't want to reset? <a href="{{ route('login') }}" class="text-primary">Sign In</a>
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
