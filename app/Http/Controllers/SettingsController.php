<?php

namespace App\Http\Controllers;

use App\Models\Collector\FirmLocation;
use App\Models\FwPixelApiKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class SettingsController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $firm = $user->firms()->first();

        $pixelApiKey = null;
        $pixelCode = null;
        $userRole = null;
        $firmLocations = collect();

        if ($firm) {
            // Get user's role in the firm
            $userRole = $user->firms()->where('firm_id', $firm->id)->first()->pivot->role ?? null;

            // Get firm locations
            $firmLocations = FirmLocation::where('firm_id', $firm->id)
                ->orderBy('name')
                ->get();

            $pixelApiKey = FwPixelApiKey::where('firm_id', $firm->id)
                ->where('is_active', true)
                ->first();

            if (!$pixelApiKey) {
                $pixelApiKey = FwPixelApiKey::createForFirm($firm->id);
            }

            $pixelCode = $this->generatePixelCode($firm->id, $pixelApiKey->api_key);
        }

        return view('settings.index', compact('pixelApiKey', 'pixelCode', 'firm', 'userRole', 'firmLocations'));
    }

    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . Auth::id()],
        ]);

        $user = Auth::user();
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->save();

        return back()->with('success', 'Profile updated successfully!');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'new_password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = Auth::user();
        $user->password = Hash::make($validated['new_password']);
        $user->save();

        return back()->with('success', 'Password updated successfully!');
    }

    public function updateProfilePicture(Request $request)
    {
        $validated = $request->validate([
            'profile_picture' => ['required', 'image', 'mimes:jpeg,png,jpg,gif', 'max:5120'], // 5MB max
        ]);

        $user = Auth::user();

        if ($request->hasFile('profile_picture')) {
            $file = $request->file('profile_picture');
            $filename = 'profile_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('profile_pictures', $filename, 'public');

            // Store the path in user's profile_picture field
            // You may need to add this column to users table via migration
            // $user->profile_picture = $path;
            // $user->save();
        }

        return back()->with('success', 'Profile picture updated successfully!');
    }

    public function updateFirm(Request $request)
    {
        $user = Auth::user();
        $firm = $user->firms()->first();

        if (!$firm) {
            return back()->with('error', 'No firm associated with your account.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'primary_email' => ['nullable', 'email', 'max:255'],
            'primary_phone' => ['nullable', 'string', 'max:20'],
            'timezone' => ['nullable', 'string', 'max:100'],
        ]);

        $firm->update($validated);

        return back()->with('success', 'Firm information updated successfully!');
    }

    public function regeneratePixelKey(Request $request)
    {
        $user = Auth::user();
        $firm = $user->firms()->first();

        if (!$firm) {
            return back()->with('error', 'No firm associated with your account.');
        }

        // Deactivate old keys
        FwPixelApiKey::where('firm_id', $firm->id)->update(['is_active' => false]);

        // Create new key
        FwPixelApiKey::createForFirm($firm->id);

        return back()->with('success', 'Pixel tracking key regenerated successfully!');
    }

    /**
     * Generate the pixel JavaScript code
     */
    private function generatePixelCode(int $firmId, string $apiKey): string
    {
        $apiUrl = 'https://app.firmwizard.com/api/pixel/collect';

        return <<<JS
<!-- FirmWizard Tracking Pixel -->
<script>
(function() {
  var FIRM_ID = {$firmId};
  var API_KEY = '{$apiKey}';
  var API_URL = '{$apiUrl}';

  // Get or create FirmWizard ID (fwid)
  function getFwid() {
    var fwid = localStorage.getItem('fwid');
    if (!fwid) {
      fwid = 'fw_' + Math.random().toString(36).substr(2, 9) + Date.now().toString(36);
      localStorage.setItem('fwid', fwid);
    }
    return fwid;
  }

  // Parse UTM parameters from URL
  function getUtmParams() {
    var params = new URLSearchParams(window.location.search);
    return {
      utm_source: params.get('utm_source'),
      utm_medium: params.get('utm_medium'),
      utm_campaign: params.get('utm_campaign')
    };
  }

  // Get click IDs
  function getClickIds() {
    var params = new URLSearchParams(window.location.search);
    return {
      gclid: params.get('gclid'),
      fbclid: params.get('fbclid')
    };
  }

  // Send pixel event
  function sendPixelEvent(eventType) {
    eventType = eventType || 'pageview';
    var utm = getUtmParams();
    var clickIds = getClickIds();

    var payload = {
      firm_id: FIRM_ID,
      fwid: getFwid(),
      url: window.location.href,
      referrer: document.referrer || null,
      utm: utm,
      gclid: clickIds.gclid,
      fbclid: clickIds.fbclid,
      event_type: eventType,
      api_key: API_KEY
    };

    fetch(API_URL, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(payload)
    }).catch(function(err) {
      console.error('FirmWizard pixel error:', err);
    });
  }

  // Auto-populate fwid in form fields
  function populateFwidFields() {
    var fwidInputs = document.querySelectorAll('input[name="fwid"], input[id="fwid"]');
    var fwid = getFwid();
    fwidInputs.forEach(function(input) {
      input.value = fwid;
    });
  }

  // Send pageview on load
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
      sendPixelEvent('pageview');
      populateFwidFields();
    });
  } else {
    sendPixelEvent('pageview');
    populateFwidFields();
  }

  // Watch for dynamically added forms
  if (window.MutationObserver) {
    var observer = new MutationObserver(function() {
      populateFwidFields();
    });
    observer.observe(document.body, { childList: true, subtree: true });
  }

  // Expose functions for custom use
  window.fwTrack = sendPixelEvent;
  window.getFwid = getFwid;
})();
</script>
<!-- End FirmWizard Tracking Pixel -->
JS;
    }

    public function storeLocation(Request $request)
    {
        $user = Auth::user();
        $firm = $user->firms()->first();

        if (!$firm) {
            return back()->with('error', 'No firm associated with your account.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:50'],
            'zipcode' => ['required', 'string', 'max:20'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'radius_miles' => ['nullable', 'numeric', 'min:0.6', 'max:62.1'],
        ]);

        // Convert miles to meters (1 mile = 1609.34 meters)
        $radiusMiles = $validated['radius_miles'] ?? 15.5;
        $validated['radius_meters'] = round($radiusMiles * 1609.34);
        unset($validated['radius_miles']);

        $validated['firm_id'] = $firm->id;

        FirmLocation::create($validated);

        return back()->with('success', 'Location added successfully!');
    }

    public function updateLocation(Request $request, $id)
    {
        $user = Auth::user();
        $firm = $user->firms()->first();

        if (!$firm) {
            return back()->with('error', 'No firm associated with your account.');
        }

        $location = FirmLocation::where('firm_id', $firm->id)->findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:50'],
            'zipcode' => ['required', 'string', 'max:20'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'radius_miles' => ['nullable', 'numeric', 'min:0.6', 'max:62.1'],
        ]);

        // Convert miles to meters (1 mile = 1609.34 meters)
        if (isset($validated['radius_miles'])) {
            $validated['radius_meters'] = round($validated['radius_miles'] * 1609.34);
            unset($validated['radius_miles']);
        }

        $location->update($validated);

        return back()->with('success', 'Location updated successfully!');
    }

    public function deleteLocation($id)
    {
        $user = Auth::user();
        $firm = $user->firms()->first();

        if (!$firm) {
            return back()->with('error', 'No firm associated with your account.');
        }

        $location = FirmLocation::where('firm_id', $firm->id)->findOrFail($id);
        $location->delete();

        return back()->with('success', 'Location deleted successfully!');
    }
}
