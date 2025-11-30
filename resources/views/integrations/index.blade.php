@extends('layouts.master')

@section('styles')
@endsection

@section('content')

                    <!-- Start::page-header -->
                    <div class="page-header-breadcrumb mb-3">
                        <div class="d-flex align-center justify-content-between flex-wrap">
                            <h1 class="page-title fw-medium fs-18 mb-0">Integrations</h1>
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="javascript:void(0);">Settings</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Integrations</li>
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

                    @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="ri-error-warning-line me-1"></i> {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    @endif

                    <!-- Info Banner -->
                    <div class="alert alert-info d-flex align-items-start" role="alert">
                        <i class="ri-information-line fs-20 me-2"></i>
                        <div>
                            <strong>Connect Your Marketing Platforms</strong>
                            <p class="mb-0 mt-1">Link your marketing accounts to enable automated data syncing, AI-powered insights, and comprehensive analytics across all your channels.</p>
                        </div>
                    </div>

                    <!-- Integrations Grid -->
                    <div class="row">
                        @foreach($availableIntegrations as $platform => $integration)
                        <div class="col-xl-4 col-lg-6 col-md-6 col-sm-12">
                            <div class="card custom-card">
                                <div class="card-body">
                                    <div class="d-flex align-items-start mb-3">
                                        <div class="me-3">
                                            <span class="avatar avatar-lg bg-light">
                                                <span class="fs-24">{{ $integration['icon'] }}</span>
                                            </span>
                                        </div>
                                        <div class="flex-fill">
                                            <h6 class="fw-semibold mb-1">{{ $integration['title'] }}</h6>
                                            <p class="fs-12 text-muted mb-2">{{ $integration['description'] }}</p>

                                            @php
                                                $statusClass = 'secondary';
                                                $statusText = 'Not Connected';

                                                if ($integration['status'] === 'active') {
                                                    $statusClass = 'success';
                                                    $statusText = 'Connected';
                                                } elseif ($integration['status'] === 'expired') {
                                                    $statusClass = 'warning';
                                                    $statusText = 'Expired';
                                                } elseif ($integration['status'] === 'error') {
                                                    $statusClass = 'danger';
                                                    $statusText = 'Error';
                                                }
                                            @endphp

                                            <span class="badge bg-{{ $statusClass }}-transparent">
                                                <i class="ri-checkbox-circle-line me-1"></i>{{ $statusText }}
                                            </span>

                                            @if(isset($integration['last_synced_at']) && $integration['last_synced_at'])
                                            <div class="fs-11 text-muted mt-2">
                                                <i class="ri-time-line me-1"></i>Last synced: {{ $integration['last_synced_at']->diffForHumans() }}
                                            </div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="d-grid">
                                        @if($integration['status'] === 'not_connected')
                                            @if($integration['auth_type'] === 'oauth2')
                                                <a href="{{ route('integrations.oauth.google', ['platform' => $platform]) }}" class="btn btn-primary btn-wave">
                                                    <i class="ri-link me-1"></i>Connect
                                                </a>
                                            @else
                                                <button type="button" class="btn btn-primary btn-wave" data-bs-toggle="modal" data-bs-target="#configModal-{{ $platform }}">
                                                    <i class="ri-settings-3-line me-1"></i>Configure
                                                </button>
                                            @endif
                                        @else
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-light btn-wave sync-btn" data-integration-id="{{ $integration['integration']->id }}" data-platform="{{ $platform }}">
                                                    <i class="ri-refresh-line me-1"></i>Sync Now
                                                </button>
                                                <button type="button" class="btn btn-light btn-wave" data-bs-toggle="modal" data-bs-target="#manageModal-{{ $platform }}">
                                                    <i class="ri-settings-4-line"></i>
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Sync button handlers
        document.querySelectorAll('.sync-btn').forEach(button => {
            button.addEventListener('click', async function() {
                const integrationId = this.getAttribute('data-integration-id');
                const platform = this.getAttribute('data-platform');
                const icon = this.querySelector('i');
                const originalText = this.innerHTML;

                // Disable button and show loading state
                this.disabled = true;
                icon.className = 'ri-loader-4-line me-1 ri-spin';
                this.innerHTML = '<i class="ri-loader-4-line me-1 ri-spin"></i>Syncing...';

                try {
                    const response = await fetch(`/integrations/${integrationId}/sync`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });

                    const result = await response.json();

                    if (result.success) {
                        // Show success message
                        showToast('Success', result.message, 'success');

                        // Reload page after 1 second to show updated sync time
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        // Show error message
                        showToast('Error', result.message, 'danger');

                        // Re-enable button
                        this.disabled = false;
                        this.innerHTML = originalText;
                    }
                } catch (error) {
                    console.error('Sync error:', error);
                    showToast('Error', 'Failed to sync. Please try again.', 'danger');

                    // Re-enable button
                    this.disabled = false;
                    this.innerHTML = originalText;
                }
            });
        });

        function showToast(title, message, type) {
            // Create toast element
            const toastHtml = `
                <div class="toast align-items-center text-white bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body">
                            <strong>${title}:</strong> ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                </div>
            `;

            // Add to toast container (create if doesn't exist)
            let container = document.querySelector('.toast-container');
            if (!container) {
                container = document.createElement('div');
                container.className = 'toast-container position-fixed top-0 end-0 p-3';
                document.body.appendChild(container);
            }

            container.insertAdjacentHTML('beforeend', toastHtml);
            const toastEl = container.lastElementChild;
            const toast = new bootstrap.Toast(toastEl);
            toast.show();

            // Remove after hidden
            toastEl.addEventListener('hidden.bs.toast', () => {
                toastEl.remove();
            });
        }
    });
</script>
@endsection
