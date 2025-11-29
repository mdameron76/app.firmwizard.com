@extends('layouts.master')

@section('styles')

@endsection

@section('content')
	
                    <!-- Start::page-header -->
                    <div class="page-header-breadcrumb mb-3">
                        <div class="d-flex align-center justify-content-between flex-wrap">
                            <h1 class="page-title fw-medium fs-18 mb-0">Settings</h1>
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="javascript:void(0);">Account</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Settings</li>
                            </ol>
                        </div>
                    </div>
                    <!-- End::page-header -->

                    @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="ri-check-line me-1"></i> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    @endif

                    @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Please fix the following errors:</strong>
                        <ul class="mb-0 mt-2">
                            @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    @endif

                    <!-- Start::row-1 -->
                    <div class="row">
                        <div class="col-xl-12">
                            <div class="card custom-card">
                                <div class="card-header">
                                    <div class="card-title">
                                        Account
                                    </div>
                                </div>
                                <form action="{{ route('settings.profile.update') }}" method="POST">
                                    @csrf
                                    <div class="card-body p-4">
                                        <div class="row gy-3">
                                            <div class="col-xl-12">
                                                <div class="d-flex align-items-start flex-wrap gap-3">
                                                    <div>
                                                        <span class="avatar avatar-xxl">
                                                            <img src="{{asset('build/assets/images/faces/9.jpg')}}" alt="">
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <span class="fw-medium d-block mb-2">Profile Picture</span>
                                                        <div class="btn-list mb-1">
                                                            <button type="button" class="btn btn-sm btn-primary btn-wave"><i class="ri-upload-2-line me-1"></i>Change Image</button>
                                                            <button type="button" class="btn btn-sm btn-light btn-wave"><i class="ri-delete-bin-line me-1"></i>Remove</button>
                                                        </div>
                                                        <span class="d-block fs-12 text-muted">Use JPEG, PNG, or GIF. Best size: 200x200 pixels. Keep it under 5MB</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-xl-6">
                                                <label for="profile-user-name" class="form-label">User Name :</label>
                                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" id="profile-user-name" value="{{ old('name', Auth::user()->name) }}" placeholder="Enter Name" required>
                                                @error('name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-xl-6">
                                                <label for="profile-email" class="form-label">Email :</label>
                                                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" id="profile-email" value="{{ old('email', Auth::user()->email) }}" placeholder="Enter Email" required>
                                                @error('email')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            @if($userRole)
                                            <div class="col-xl-6">
                                                <label for="profile-role" class="form-label">Role :</label>
                                                <input type="text" class="form-control" id="profile-role" value="{{ ucfirst($userRole) }}" readonly>
                                                <small class="text-muted">Your role in the firm. Contact an admin to change this.</small>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="card-footer">
                                        <button type="submit" class="btn btn-primary float-end mb-2">Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        @if($firm)
                        <div class="col-xl-12">
                            <div class="card custom-card">
                                <div class="card-header">
                                    <div class="card-title">
                                        Firm Information
                                    </div>
                                </div>
                                <form action="{{ route('settings.firm.update') }}" method="POST">
                                    @csrf
                                    <div class="card-body p-4">
                                        <div class="row gy-3">
                                            <div class="col-xl-6">
                                                <label for="firm-name" class="form-label">Firm Name :</label>
                                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" id="firm-name" value="{{ old('name', $firm->name) }}" placeholder="Enter Firm Name" required>
                                                @error('name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-xl-6">
                                                <label for="firm-website" class="form-label">Website URL :</label>
                                                <input type="url" name="website_url" class="form-control @error('website_url') is-invalid @enderror" id="firm-website" value="{{ old('website_url', $firm->website_url) }}" placeholder="https://example.com">
                                                @error('website_url')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-xl-6">
                                                <label for="firm-email" class="form-label">Primary Email :</label>
                                                <input type="email" name="primary_email" class="form-control @error('primary_email') is-invalid @enderror" id="firm-email" value="{{ old('primary_email', $firm->primary_email) }}" placeholder="contact@example.com">
                                                @error('primary_email')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-xl-6">
                                                <label for="firm-phone" class="form-label">Primary Phone :</label>
                                                <input type="text" name="primary_phone" class="form-control @error('primary_phone') is-invalid @enderror" id="firm-phone" value="{{ old('primary_phone', $firm->primary_phone) }}" placeholder="(555) 123-4567">
                                                @error('primary_phone')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-xl-6">
                                                <label for="firm-timezone" class="form-label">Timezone :</label>
                                                <select name="timezone" class="form-control @error('timezone') is-invalid @enderror" data-trigger id="firm-timezone">
                                                    <option value="America/New_York" {{ old('timezone', $firm->timezone) == 'America/New_York' ? 'selected' : '' }}> (GMT-05:00) Eastern Time (US & Canada)</option>
                                                    <option value="America/Chicago" {{ old('timezone', $firm->timezone) == 'America/Chicago' ? 'selected' : '' }}> (GMT-06:00) Central Time (US & Canada)</option>
                                                    <option value="America/Denver" {{ old('timezone', $firm->timezone) == 'America/Denver' ? 'selected' : '' }}> (GMT-07:00) Mountain Time (US & Canada)</option>
                                                    <option value="America/Los_Angeles" {{ old('timezone', $firm->timezone) == 'America/Los_Angeles' ? 'selected' : '' }}>(GMT-08:00) Pacific Time (US & Canada)</option>
                                                    <option value="America/Anchorage" {{ old('timezone', $firm->timezone) == 'America/Anchorage' ? 'selected' : '' }}> (GMT-09:00) Alaska</option>
                                                    <option value="Pacific/Honolulu" {{ old('timezone', $firm->timezone) == 'Pacific/Honolulu' ? 'selected' : '' }}> (GMT-10:00) Hawaii</option>
                                                </select>
                                                @error('timezone')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-footer">
                                        <button type="submit" class="btn btn-primary float-end mb-2">Save Changes</button>
                                    </div>
                                </form>
                                <div class="card-body border-top">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="mb-0 fw-medium">Firm Locations</h6>
                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addLocationModal">
                                            <i class="ri-add-line me-1"></i>Add Location
                                        </button>
                                    </div>
                                    @if($firmLocations->count() > 0)
                                    <div class="table-responsive">
                                        <table class="table table-hover text-nowrap">
                                            <thead>
                                                <tr>
                                                    <th>Location Name</th>
                                                    <th>Address</th>
                                                    <th>City</th>
                                                    <th>State</th>
                                                    <th>Zipcode</th>
                                                    <th class="text-end">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($firmLocations as $location)
                                                <tr>
                                                    <td>{{ $location->name }}</td>
                                                    <td>{{ $location->address_line1 ?? '-' }}</td>
                                                    <td>{{ $location->city }}</td>
                                                    <td>{{ $location->state }}</td>
                                                    <td>{{ $location->zipcode }}</td>
                                                    <td class="text-end">
                                                        <button type="button" class="btn btn-sm btn-icon btn-light" onclick="editLocation({{ $location->id }})" title="Edit">
                                                            <i class="ri-edit-line"></i>
                                                        </button>
                                                        <form action="{{ route('settings.locations.delete', $location->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this location?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-icon btn-danger" title="Delete">
                                                                <i class="ri-delete-bin-line"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    @else
                                    <div class="alert alert-info">
                                        No locations added yet. Click "Add Location" to get started.
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif
                        <div class="col-xl-12">
                            <div class="card custom-card">
                                <div class="card-header">
                                    <div class="card-title">
                                        Change Password
                                    </div>
                                </div>
                                <form action="{{ route('settings.password.update') }}" method="POST">
                                    @csrf
                                    <div class="card-body">
                                        <div class="row gy-3">
                                            <div class="col-xl-6">
                                                <label for="Password" class="form-label">New Password</label>
                                                <input type="password" name="new_password" class="form-control @error('new_password') is-invalid @enderror" id="Password" placeholder="Enter Password" required>
                                                @error('new_password')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-xl-6">
                                                <label for="confirm-password" class="form-label">Confirm Password</label>
                                                <input type="password" name="new_password_confirmation" class="form-control @error('new_password_confirmation') is-invalid @enderror" id="confirm-password" placeholder="Confirm Password" required>
                                                @error('new_password_confirmation')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-footer">
                                        <button type="submit" class="btn btn-primary float-end">Update Password</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="col-xl-12">
                            <div class="card custom-card">
                                <div class="card-header">
                                    <div class="card-title">
                                        Security Settings
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="d-sm-flex d-block align-items-top justify-content-between">
                                        <div class="w-50">
                                            <p class="fs-14 mb-1 fw-medium">Two-Factor Authentication</p>
                                            <p class="fs-12 mb-0 text-muted">This helps protect accounts from unauthorized access, even if a password is compromised.</p>
                                        </div>
                                        <a href="javascript:void(0);" class="link-primary text-decoration-underline">Set Up 2FA</a>
                                    </div>
                                    <div class="d-sm-flex d-block align-items-top justify-content-between mt-3">
                                        <div class="w-50">
                                            <p class="fs-14 mb-1 fw-medium">Password Verification</p>
                                            <p class="fs-12 mb-0 text-muted">This additional step helps ensure that the person attempting to modify account details is the legitimate account owner.</p>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="" id="personal-details">
                                            <label class="form-check-label" for="personal-details">
                                                Require Personal Details
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <div class="btn-list float-end">
                                        <button class="btn btn-danger btn-wave">Deactivate Account</button>
                                        <button class="btn btn-light btn-wave">Restore Defaults</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-12">
                            <div class="card custom-card">
                                <div class="card-header">
                                    <div class="card-title">
                                        Notifications
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row gx-5 gy-3">
                                        <div class="col-xl-12">
                                            <p class="fs-14 mb-1 fw-medium">Configure Notifications</p>
                                            <p class="fs-12 mb-0 text-muted">By configuring notifications, users can tailor their experience to receive alerts for the types of events that matter to them.</p>
                                        </div>
                                        <div class="col-xl-12">
                                            <div class="d-flex align-items-top justify-content-between mt-sm-0 mt-3">
                                                <div class="mail-notification-settings">
                                                    <p class="fs-14 mb-1 fw-medium">In-App Notifications</p>
                                                    <p class="fs-12 mb-0 text-muted">Alerts that appear within the application interface.</p>
                                                </div>
                                                <div class="toggle toggle-success on mb-0 float-sm-end" id="in-app-notifications">
                                                    <span></span>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-top justify-content-between mt-3">
                                                <div class="mail-notification-settings">
                                                    <p class="fs-14 mb-1 fw-medium">Email Notifications</p>
                                                    <p class="fs-12 mb-0 text-muted">Messages sent to the user's email address.</p>
                                                </div>
                                                <div class="toggle toggle-success on mb-0 float-sm-end" id="email-notifications">
                                                    <span></span>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-top justify-content-between mt-3">
                                                <div class="mail-notification-settings">
                                                    <p class="fs-14 mb-1 fw-medium">Push Notifications</p>
                                                    <p class="fs-12 mb-0 text-muted">Alerts sent to the user's mobile device or desktop.</p>
                                                </div>
                                                <div class="toggle toggle-success mb-0 float-sm-end" id="push-notifications">
                                                    <span></span>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-top justify-content-between mt-3">
                                                <div class="mail-notification-settings">
                                                    <p class="fs-14 mb-1 fw-medium">SMS Notifications</p>
                                                    <p class="fs-12 mb-0 text-muted">Text messages sent to the user's mobile phone.</p>
                                                </div>
                                                <div class="toggle toggle-success on mb-0 float-sm-end" id="sms-notifications">
                                                    <span></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @if($firm && $pixelCode)
                        <div class="col-xl-12">
                            <div class="card custom-card">
                                <div class="card-header">
                                    <div class="card-title">
                                        Tracking Pixel
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row gy-3">
                                        <div class="col-xl-12">
                                            <p class="fs-14 mb-3">
                                                Install the FirmWizard tracking pixel on your website to track visitor behavior, attribution, and conversions.
                                                This pixel will automatically track page views, UTM parameters, and click IDs (gclid, fbclid) for marketing attribution.
                                            </p>
                                            <label for="pixel-code" class="form-label fw-medium">Pixel Code:</label>
                                            <div class="position-relative">
                                                <textarea
                                                    id="pixel-code"
                                                    class="form-control font-monospace"
                                                    rows="15"
                                                    readonly
                                                    style="font-size: 12px; resize: vertical;"
                                                >{{ $pixelCode }}</textarea>
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-primary position-absolute"
                                                    style="top: 10px; right: 10px;"
                                                    onclick="copyPixelCode()"
                                                >
                                                    <i class="ri-file-copy-line me-1"></i>Copy Code
                                                </button>
                                            </div>
                                            <div class="alert alert-info mt-3 mb-0" role="alert">
                                                <strong>Installation Instructions:</strong>
                                                <ol class="mb-0 mt-2 ps-3">
                                                    <li>Copy the pixel code above</li>
                                                    <li>Paste it into your website's HTML, just before the closing <code>&lt;/head&gt;</code> tag</li>
                                                    <li>The pixel will automatically track page views and visitor information</li>
                                                    <li><strong>Important:</strong> Add this hidden field to all contact forms to capture the visitor ID:
                                                        <br><code class="d-block mt-1 bg-light p-1 rounded">&lt;input type="hidden" name="fwid" id="fwid"&gt;</code>
                                                        <small class="text-muted">The pixel automatically populates this field via <code>localStorage.getItem('fwid')</code></small>
                                                    </li>
                                                    <li>For custom event tracking, use <code>window.fwTrack('event_name')</code> in your JavaScript</li>
                                                    <li>For GBP redirect tracking, create a <code>/gbp</code> page that redirects to your homepage with UTM parameters</li>
                                                </ol>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <form action="{{ route('settings.pixel.regenerate') }}" method="POST" onsubmit="return confirm('Are you sure you want to regenerate your pixel key? This will invalidate your current tracking code.');">
                                        @csrf
                                        <button type="submit" class="btn btn-warning btn-wave">
                                            <i class="ri-refresh-line me-1"></i>Regenerate Pixel Key
                                        </button>
                                        <small class="text-muted ms-2">Current Key: {{ substr($pixelApiKey->api_key, 0, 20) }}...</small>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                    <!--End::row-1 -->

                    <!-- Add Location Modal -->
                    <div class="modal fade" id="addLocationModal" tabindex="-1" aria-labelledby="addLocationModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form action="{{ route('settings.locations.store') }}" method="POST">
                                    @csrf
                                    <div class="modal-header">
                                        <h6 class="modal-title" id="addLocationModalLabel">Add Firm Location</h6>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row gy-3">
                                            <div class="col-12">
                                                <label for="add-name" class="form-label">Location Name <span class="text-danger">*</span></label>
                                                <input type="text" name="name" class="form-control" id="add-name" required>
                                            </div>
                                            <div class="col-12">
                                                <label for="add-address" class="form-label">Address</label>
                                                <input type="text" name="address_line1" class="form-control" id="add-address">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="add-city" class="form-label">City <span class="text-danger">*</span></label>
                                                <input type="text" name="city" class="form-control" id="add-city" required>
                                            </div>
                                            <div class="col-md-3">
                                                <label for="add-state" class="form-label">State <span class="text-danger">*</span></label>
                                                <input type="text" name="state" class="form-control" id="add-state" maxlength="2" placeholder="CA" required>
                                            </div>
                                            <div class="col-md-3">
                                                <label for="add-zipcode" class="form-label">Zipcode <span class="text-danger">*</span></label>
                                                <input type="text" name="zipcode" class="form-control" id="add-zipcode" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="add-latitude" class="form-label">Latitude</label>
                                                <input type="number" step="0.0000001" name="latitude" class="form-control" id="add-latitude" placeholder="37.7749">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="add-longitude" class="form-label">Longitude</label>
                                                <input type="number" step="0.0000001" name="longitude" class="form-control" id="add-longitude" placeholder="-122.4194">
                                            </div>
                                            <div class="col-12">
                                                <label for="add-radius" class="form-label">Radius (miles)</label>
                                                <input type="number" step="0.1" name="radius_miles" class="form-control" id="add-radius" value="15.5" min="0.6" max="62.1">
                                                <small class="text-muted">Default: 15.5 miles (~25 km). Range: 0.6 - 62.1 miles</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">Add Location</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Edit Location Modal -->
                    <div class="modal fade" id="editLocationModal" tabindex="-1" aria-labelledby="editLocationModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form id="editLocationForm" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-header">
                                        <h6 class="modal-title" id="editLocationModalLabel">Edit Firm Location</h6>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row gy-3">
                                            <div class="col-12">
                                                <label for="edit-name" class="form-label">Location Name <span class="text-danger">*</span></label>
                                                <input type="text" name="name" class="form-control" id="edit-name" required>
                                            </div>
                                            <div class="col-12">
                                                <label for="edit-address" class="form-label">Address</label>
                                                <input type="text" name="address_line1" class="form-control" id="edit-address">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="edit-city" class="form-label">City <span class="text-danger">*</span></label>
                                                <input type="text" name="city" class="form-control" id="edit-city" required>
                                            </div>
                                            <div class="col-md-3">
                                                <label for="edit-state" class="form-label">State <span class="text-danger">*</span></label>
                                                <input type="text" name="state" class="form-control" id="edit-state" maxlength="2" required>
                                            </div>
                                            <div class="col-md-3">
                                                <label for="edit-zipcode" class="form-label">Zipcode <span class="text-danger">*</span></label>
                                                <input type="text" name="zipcode" class="form-control" id="edit-zipcode" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="edit-latitude" class="form-label">Latitude</label>
                                                <input type="number" step="0.0000001" name="latitude" class="form-control" id="edit-latitude">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="edit-longitude" class="form-label">Longitude</label>
                                                <input type="number" step="0.0000001" name="longitude" class="form-control" id="edit-longitude">
                                            </div>
                                            <div class="col-12">
                                                <label for="edit-radius" class="form-label">Radius (miles)</label>
                                                <input type="number" step="0.1" name="radius_miles" class="form-control" id="edit-radius" min="0.6" max="62.1">
                                                <small class="text-muted">Range: 0.6 - 62.1 miles</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">Update Location</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

@endsection

@section('scripts')
<script>
// Location data for editing
var locations = @json($firmLocations);

function copyPixelCode() {
    var pixelCode = document.getElementById('pixel-code');
    pixelCode.select();
    pixelCode.setSelectionRange(0, 99999); // For mobile devices

    navigator.clipboard.writeText(pixelCode.value).then(function() {
        // Show success message
        var btn = event.target.closest('button');
        var originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="ri-check-line me-1"></i>Copied!';
        btn.classList.remove('btn-primary');
        btn.classList.add('btn-success');

        setTimeout(function() {
            btn.innerHTML = originalHTML;
            btn.classList.remove('btn-success');
            btn.classList.add('btn-primary');
        }, 2000);
    }, function(err) {
        console.error('Could not copy text: ', err);
        alert('Failed to copy. Please copy manually.');
    });
}

function editLocation(locationId) {
    // Find location data
    var location = locations.find(function(loc) {
        return loc.id === locationId;
    });

    if (!location) {
        alert('Location not found');
        return;
    }

    // Populate edit form
    document.getElementById('edit-name').value = location.name || '';
    document.getElementById('edit-address').value = location.address_line1 || '';
    document.getElementById('edit-city').value = location.city || '';
    document.getElementById('edit-state').value = location.state || '';
    document.getElementById('edit-zipcode').value = location.zipcode || '';
    document.getElementById('edit-latitude').value = location.latitude || '';
    document.getElementById('edit-longitude').value = location.longitude || '';

    // Convert meters to miles (1 mile = 1609.34 meters)
    var radiusMeters = location.radius_meters || 25000;
    var radiusMiles = (radiusMeters / 1609.34).toFixed(1);
    document.getElementById('edit-radius').value = radiusMiles;

    // Update form action
    var form = document.getElementById('editLocationForm');
    form.action = '/settings/locations/' + locationId;

    // Show modal
    var editModal = new bootstrap.Modal(document.getElementById('editLocationModal'));
    editModal.show();
}
</script>
@endsection
