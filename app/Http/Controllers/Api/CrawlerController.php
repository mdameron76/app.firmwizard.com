<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Collector\Competitor;
use App\Models\Collector\CrawlerPage;
use App\Models\Collector\Collection;
use App\Models\Collector\FirmLocation;
use App\Models\Firm;
use App\Models\FirmProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CrawlerController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/crawler/scripts",
     *     summary="List available crawler scripts",
     *     tags={"Crawler"},
     *     @OA\Response(
     *         response=200,
     *         description="List of available scripts",
     *         @OA\JsonContent(
     *             @OA\Property(property="scripts", type="array", @OA\Items(type="string"))
     *         )
     *     )
     * )
     */
    public function listScripts()
    {
        $scriptsPath = app_path('Scripts/Python');
        $scripts = [];

        if (is_dir($scriptsPath)) {
            $files = scandir($scriptsPath);
            $scripts = array_filter($files, function($file) use ($scriptsPath) {
                return pathinfo($file, PATHINFO_EXTENSION) === 'py' 
                    && is_file($scriptsPath . '/' . $file);
            });
        }

        return response()->json([
            'scripts' => array_values($scripts),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/crawler/page",
     *     summary="Crawl and store a page for competitor analysis",
     *     tags={"Crawler"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"competitor_id", "url"},
     *             @OA\Property(property="competitor_id", type="integer", example=1),
     *             @OA\Property(property="url", type="string", example="https://example.com"),
     *             @OA\Property(property="save_to_db", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Page crawled successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="page_id", type="integer"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function crawlPage(Request $request)
    {
        $validated = $request->validate([
            'competitor_id' => 'required|integer',
            'url' => 'required|url',
            'save_to_db' => 'boolean',
        ]);

        $competitorId = $validated['competitor_id'];
        $url = $validated['url'];
        $saveToDb = $validated['save_to_db'] ?? true;

        // Verify competitor exists
        $competitor = Competitor::find($competitorId);
        if (!$competitor) {
            return response()->json([
                'success' => false,
                'error' => 'Competitor not found',
            ], 404);
        }

        // Execute Python page crawler
        $scriptPath = app_path('Scripts/Python/page_crawler.py');
        
        if (!file_exists($scriptPath)) {
            return response()->json([
                'success' => false,
                'error' => 'page_crawler.py not found',
            ], 404);
        }

        try {
            // Get collector DB credentials from env
            $dbHost = env('COLLECTOR_DB_HOST', '127.0.0.1');
            $dbPort = env('COLLECTOR_DB_PORT', '3306');
            $dbName = env('COLLECTOR_DB_DATABASE', 'lawsoftware_collector');
            $dbUser = env('COLLECTOR_DB_USERNAME', 'root');
            $dbPass = env('COLLECTOR_DB_PASSWORD', '');

            // Execute Python script with DB credentials
            $result = Process::run([
                'python',
                $scriptPath,
                $url,
                $competitorId,
                $dbHost,
                $dbPort,
                $dbName,
                $dbUser,
                $dbPass,
                $saveToDb ? '1' : '0',
            ]);

            if ($result->failed()) {
                Log::error("Page crawler failed: " . $result->errorOutput());
                return response()->json([
                    'success' => false,
                    'error' => 'Crawler execution failed',
                    'details' => $result->errorOutput(),
                ], 500);
            }

            // Parse JSON output
            $output = json_decode($result->output(), true);

            if (!$output || !isset($output['success'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid crawler output',
                    'raw_output' => $result->output(),
                ], 500);
            }

            return response()->json($output);

        } catch (\Exception $e) {
            Log::error("Crawler exception: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/crawler/pages/{competitorId}",
     *     summary="Get crawled pages for a competitor",
     *     tags={"Crawler"},
     *     @OA\Parameter(
     *         name="competitorId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of crawled pages"
     *     )
     * )
     */
    public function getPages($competitorId)
    {
        $competitor = Competitor::with(['pages' => function($query) {
            $query->orderBy('crawled_at', 'desc')->limit(100);
        }])->find($competitorId);

        if (!$competitor) {
            return response()->json([
                'success' => false,
                'error' => 'Competitor not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'competitor' => [
                'id' => $competitor->id,
                'name' => $competitor->name,
                'website' => $competitor->website,
            ],
            'pages' => $competitor->pages,
            'total_pages' => $competitor->pages()->count(),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/crawler/find-competitors",
     *     summary="Find competitors using Google Places API",
     *     tags={"Crawler"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"collection_id"},
     *             @OA\Property(property="collection_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Competitor search completed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="run_id", type="integer"),
     *             @OA\Property(property="competitors_found", type="integer")
     *         )
     *     )
     * )
     */
    public function findCompetitors(Request $request)
    {
        $validated = $request->validate([
            'collection_id' => 'required|integer',
        ]);

        $collectionId = $validated['collection_id'];

        // Verify collection exists
        $collection = Collection::find($collectionId);
        if (!$collection) {
            return response()->json([
                'success' => false,
                'error' => 'Collection not found',
            ], 404);
        }

        // Execute Python competitor finder script
        $scriptPath = app_path('Scripts/Python/competitor_finder.py');
        
        if (!file_exists($scriptPath)) {
            return response()->json([
                'success' => false,
                'error' => 'competitor_finder.py not found',
            ], 404);
        }

        try {
            // Get collector DB credentials from env
            $dbHost = env('COLLECTOR_DB_HOST', '127.0.0.1');
            $dbPort = env('COLLECTOR_DB_PORT', '3306');
            $dbName = env('COLLECTOR_DB_DATABASE', 'lawsoftware_collector');
            $dbUser = env('COLLECTOR_DB_USERNAME', 'root');
            $dbPass = env('COLLECTOR_DB_PASSWORD', '');

            // Set environment variables for the Python script
            $envVars = [
                'DB_HOST' => $dbHost,
                'DB_PORT' => $dbPort,
                'DB_DATABASE' => $dbName,
                'DB_USER' => $dbUser,
                'DB_PASSWORD' => $dbPass,
                'GOOGLE_MAPS_API_KEY' => env('GOOGLE_MAPS_API_KEY', ''),
            ];

            // Execute Python script
            $result = Process::env($envVars)->run([
                'python',
                $scriptPath,
                '--collection-id',
                $collectionId,
            ]);

            if ($result->failed()) {
                Log::error("Competitor finder failed: " . $result->errorOutput());
                return response()->json([
                    'success' => false,
                    'error' => 'Competitor finder execution failed',
                    'details' => $result->errorOutput(),
                ], 500);
            }

            // Parse output to get run_id
            $output = $result->output();
            Log::info("Competitor finder output: " . $output);

            // Try to extract run_id from output
            preg_match('/Collection run (\d+) completed/', $output, $matches);
            $runId = $matches[1] ?? null;

            // Get competitors count for this collection
            $competitorsCount = DB::connection('collector')
                ->table('competitor_collections')
                ->where('collection_id', $collectionId)
                ->count();

            return response()->json([
                'success' => true,
                'run_id' => $runId,
                'competitors_found' => $competitorsCount,
                'output' => $output,
            ]);

        } catch (\Exception $e) {
            Log::error("Competitor finder exception: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/crawler/collections",
     *     summary="Get all collections",
     *     tags={"Crawler"},
     *     @OA\Response(
     *         response=200,
     *         description="List of collections"
     *     )
     * )
     */
    public function getCollections()
    {
        $collections = Collection::with(['runs' => function($query) {
            $query->orderBy('started_at', 'desc')->limit(1);
        }])->get();

        return response()->json([
            'success' => true,
            'collections' => $collections,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/crawler/collections",
     *     summary="Create a new collection",
     *     tags={"Crawler"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"firm_id", "firm_location_id", "name", "query"},
     *             @OA\Property(property="firm_id", type="integer"),
     *             @OA\Property(property="firm_location_id", type="integer"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="query", type="string"),
     *             @OA\Property(property="enabled", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Collection created"
     *     )
     * )
     */
    public function createCollection(Request $request)
    {
        $validated = $request->validate([
            'firm_id' => 'required|integer',
            'firm_location_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'query' => 'required|string|max:255',
            'enabled' => 'boolean',
        ]);

        $collection = Collection::create($validated);

        return response()->json([
            'success' => true,
            'collection' => $collection,
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/crawler/firms",
     *     summary="Get list of firms",
     *     tags={"Crawler"},
     *     @OA\Response(
     *         response=200,
     *         description="List of firms"
     *     )
     * )
     */
    public function getFirms()
    {
        $firms = Firm::select('id', 'name', 'slug', 'website_url', 'primary_email', 'primary_phone')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'firms' => $firms,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/crawler/firm-locations",
     *     summary="Get list of firm locations",
     *     tags={"Crawler"},
     *     @OA\Parameter(
     *         name="firm_id",
     *         in="query",
     *         description="Filter by firm ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of firm locations"
     *     )
     * )
     */
    public function getFirmLocations(Request $request)
    {
        $query = FirmLocation::query();

        if ($request->has('firm_id')) {
            $query->where('firm_id', $request->firm_id);
        }

        $locations = $query->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'firm_locations' => $locations,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/crawler/firm-profiles",
     *     summary="Get list of firm profiles",
     *     tags={"Crawler"},
     *     @OA\Parameter(
     *         name="firm_id",
     *         in="query",
     *         description="Filter by firm ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of firm profiles"
     *     )
     * )
     */
    public function getFirmProfiles(Request $request)
    {
        $query = FirmProfile::with('firm:id,name,slug,website_url');

        if ($request->has('firm_id')) {
            $query->where('firm_id', $request->firm_id);
        }

        $profiles = $query->get();

        return response()->json([
            'success' => true,
            'firm_profiles' => $profiles,
        ]);
    }
}
