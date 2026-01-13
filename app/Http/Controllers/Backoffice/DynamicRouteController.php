<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\DynamicRoute;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DynamicRouteController extends Controller
{
    /**
     * Check if the authenticated user is an admin.
     */
    private function checkAdminRole(): void
    {
        if (! Auth::check() || ! Auth::user()->getRoleNames()->contains('admin')) {
            abort(403, 'Unauthorized action.');
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $this->checkAdminRole();

        $routes = DynamicRoute::orderBy('guard')->orderBy('order')->paginate(20);

        return view('backoffice.routes.index', compact('routes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $this->checkAdminRole();

        $roles = Role::all();
        $permissions = Permission::all();

        return view('backoffice.routes.create', compact('roles', 'permissions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->checkAdminRole();

        $validated = $request->validate([
            'name' => ['required', 'string', 'unique:dynamic_routes,name', 'regex:/^[a-z0-9._-]+$/'],
            'uri' => ['required', 'string'],
            'method' => ['required', 'in:GET,POST,PUT,PATCH,DELETE,OPTIONS'],
            'controller' => ['required', 'string'],
            'action' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'requires_auth' => ['boolean'],
            'guard' => ['required', 'in:web,api'],
            'middleware' => ['nullable', 'string'],
            'permissions' => ['nullable', 'array'],
            'roles' => ['nullable', 'array'],
            'is_active' => ['boolean'],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);

        // Convert comma-separated middleware to array
        if (! empty($validated['middleware'])) {
            $validated['middleware'] = array_map('trim', explode(',', $validated['middleware']));
        } else {
            $validated['middleware'] = null;
        }

        // Extract model name from controller
        $controllerParts = explode('\\', $validated['controller']);
        $controllerName = end($controllerParts);
        $modelName = str_replace('Controller', '', $controllerName);
        $isApi = strpos($validated['controller'], 'Api') !== false;

        // Create the controller if it doesn't exist
        $controllerCreated = $this->createControllerIfNeeded($validated['controller'], $validated['action']);

        // Auto-generate related files
        $generatedFiles = [];

        // 1. Create Model if it doesn't exist
        if ($this->createModelIfNeeded($modelName)) {
            $generatedFiles[] = 'Model';
        }

        // 2. Create API Resource if it's an API controller
        if ($isApi && $this->createResourceIfNeeded($modelName)) {
            $generatedFiles[] = 'Resource';
        }

        // 3. Create Resource Collection if it's an API controller
        if ($isApi && $this->createCollectionIfNeeded($modelName)) {
            $generatedFiles[] = 'Collection';
        }

        // 4. Create Form Request
        if ($this->createRequestIfNeeded($modelName)) {
            $generatedFiles[] = 'Request';
        }

        DynamicRoute::create($validated);

        $message = 'Route created successfully!';
        if ($controllerCreated) {
            $message .= ' Controller generated automatically.';
        }
        if (! empty($generatedFiles)) {
            $message .= ' Generated: '.implode(', ', $generatedFiles).'.';
        }
        $message .= ' Clear cache to apply changes.';

        return redirect()->route('backoffice.routes.index')
            ->with('success', $message);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DynamicRoute $route): View
    {
        $this->checkAdminRole();

        $roles = Role::all();
        $permissions = Permission::all();

        return view('backoffice.routes.edit', compact('route', 'roles', 'permissions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DynamicRoute $route): RedirectResponse
    {
        $this->checkAdminRole();

        $validated = $request->validate([
            'name' => ['required', 'string', 'unique:dynamic_routes,name,'.$route->id, 'regex:/^[a-z0-9._-]+$/'],
            'uri' => ['required', 'string'],
            'method' => ['required', 'in:GET,POST,PUT,PATCH,DELETE,OPTIONS'],
            'controller' => ['required', 'string'],
            'action' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'requires_auth' => ['boolean'],
            'guard' => ['required', 'in:web,api'],
            'middleware' => ['nullable', 'string'],
            'permissions' => ['nullable', 'array'],
            'roles' => ['nullable', 'array'],
            'is_active' => ['boolean'],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);

        // Convert comma-separated middleware to array
        if (! empty($validated['middleware'])) {
            $validated['middleware'] = array_map('trim', explode(',', $validated['middleware']));
        } else {
            $validated['middleware'] = null;
        }

        $route->update($validated);

        return redirect()->route('backoffice.routes.index')
            ->with('success', 'Route updated successfully! Clear cache to apply changes.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DynamicRoute $route): RedirectResponse
    {
        $this->checkAdminRole();

        $route->delete();

        return redirect()->route('backoffice.routes.index')
            ->with('success', 'Route deleted successfully!');
    }

    /**
     * Toggle route active status.
     */
    public function toggle(DynamicRoute $route): RedirectResponse
    {
        $this->checkAdminRole();

        $route->update(['is_active' => ! $route->is_active]);

        $status = $route->is_active ? 'activated' : 'deactivated';

        return redirect()->route('backoffice.routes.index')
            ->with('success', "Route {$status} successfully!");
    }

    /**
     * Clear route cache.
     */
    public function clearCache(): RedirectResponse
    {
        $this->checkAdminRole();

        Artisan::call('route:clear');
        Artisan::call('cache:clear');

        return redirect()->route('backoffice.routes.index')
            ->with('success', 'Route cache cleared successfully!');
    }

    /**
     * Create controller if it doesn't exist with CRUD methods.
     */
    protected function createControllerIfNeeded(string $controllerClass, string $action): bool
    {
        // Convert namespace to file path
        // Remove 'App\' prefix and convert remaining namespace to path
        $relativePath = str_replace('App\\', '', $controllerClass);
        $path = app_path(str_replace('\\', '/', $relativePath).'.php');

        // Check if controller already exists
        if (file_exists($path)) {
            return false;
        }

        // Extract controller name and namespace
        $parts = explode('\\', $controllerClass);
        $controllerName = array_pop($parts);
        $namespace = implode('\\', $parts);

        // Create directory if it doesn't exist
        $directory = dirname($path);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Determine if it's an API controller
        $isApi = strpos($controllerClass, 'Api') !== false;

        // Extract model name from controller name
        $modelName = str_replace('Controller', '', $controllerName);

        // Generate controller content
        $content = $this->generateControllerContent($namespace, $controllerName, $modelName, $isApi);

        // Write the file
        file_put_contents($path, $content);

        return true;
    }

    /**
     * Generate the controller file content.
     */
    protected function generateControllerContent(string $namespace, string $controllerName, string $modelName, bool $isApi): string
    {
        $useStatements = "use App\Http\Controllers\Controller;\nuse Illuminate\Http\Request;";
        $returnType = $isApi ? 'JsonResponse' : 'View|RedirectResponse';

        if ($isApi) {
            $useStatements .= "\nuse Illuminate\Http\JsonResponse;";
        } else {
            $useStatements .= "\nuse Illuminate\View\View;\nuse Illuminate\Http\RedirectResponse;";
        }

        // Try to add model if it exists
        $modelClass = "App\\Models\\{$modelName}";
        if (class_exists($modelClass)) {
            $useStatements .= "\nuse {$modelClass};";
            $hasModel = true;
        } else {
            $hasModel = false;
        }

        $methods = $this->generateCrudMethods($modelName, $isApi, $hasModel);

        return <<<PHP
<?php

namespace {$namespace};

{$useStatements}

class {$controllerName} extends Controller
{
{$methods}
}

PHP;
    }

    /**
     * Generate CRUD methods for the controller.
     */
    protected function generateCrudMethods(string $modelName, bool $isApi, bool $hasModel): string
    {
        $modelVariable = lcfirst($modelName);
        $pluralModel = str($modelName)->plural()->lower();

        if ($isApi) {
            return $this->generateApiMethods($modelName, $modelVariable, $hasModel);
        }

        return $this->generateWebMethods($modelName, $modelVariable, $pluralModel, $hasModel);
    }

    /**
     * Generate API controller methods.
     */
    protected function generateApiMethods(string $modelName, string $modelVariable, bool $hasModel): string
    {
        if (! $hasModel) {
            return <<<'PHP'
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        // TODO: Implement index method
        return response()->json([
            'message' => 'List of resources',
            'data' => []
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        // TODO: Implement store method
        return response()->json([
            'message' => 'Resource created successfully',
            'data' => []
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        // TODO: Implement show method
        return response()->json([
            'message' => 'Resource details',
            'data' => []
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        // TODO: Implement update method
        return response()->json([
            'message' => 'Resource updated successfully',
            'data' => []
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        // TODO: Implement destroy method
        return response()->json([
            'message' => 'Resource deleted successfully'
        ]);
    }
PHP;
        }

        return <<<PHP
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        \${$modelVariable}s = {$modelName}::paginate(15);

        return response()->json(\${$modelVariable}s);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request \$request): JsonResponse
    {
        \$validated = \$request->validate([
            // TODO: Add validation rules
        ]);

        \${$modelVariable} = {$modelName}::create(\$validated);

        return response()->json([
            'message' => '{$modelName} created successfully',
            'data' => \${$modelVariable}
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show({$modelName} \${$modelVariable}): JsonResponse
    {
        return response()->json(\${$modelVariable});
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request \$request, {$modelName} \${$modelVariable}): JsonResponse
    {
        \$validated = \$request->validate([
            // TODO: Add validation rules
        ]);

        \${$modelVariable}->update(\$validated);

        return response()->json([
            'message' => '{$modelName} updated successfully',
            'data' => \${$modelVariable}
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy({$modelName} \${$modelVariable}): JsonResponse
    {
        \${$modelVariable}->delete();

        return response()->json([
            'message' => '{$modelName} deleted successfully'
        ]);
    }
PHP;
    }

    /**
     * Generate Web controller methods.
     */
    protected function generateWebMethods(string $modelName, string $modelVariable, string $pluralModel, bool $hasModel): string
    {
        if (! $hasModel) {
            return <<<'PHP'
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        // TODO: Implement index method
        return view('index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        // TODO: Implement create method
        return view('create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        // TODO: Implement store method
        return redirect()->back()->with('success', 'Created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): View
    {
        // TODO: Implement show method
        return view('show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id): View
    {
        // TODO: Implement edit method
        return view('edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): RedirectResponse
    {
        // TODO: Implement update method
        return redirect()->back()->with('success', 'Updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): RedirectResponse
    {
        // TODO: Implement destroy method
        return redirect()->back()->with('success', 'Deleted successfully');
    }
PHP;
        }

        return <<<PHP
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        \${$pluralModel} = {$modelName}::paginate(15);

        return view('{$pluralModel}.index', compact('{$pluralModel}'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('{$pluralModel}.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request \$request): RedirectResponse
    {
        \$validated = \$request->validate([
            // TODO: Add validation rules
        ]);

        {$modelName}::create(\$validated);

        return redirect()->route('{$pluralModel}.index')
            ->with('success', '{$modelName} created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show({$modelName} \${$modelVariable}): View
    {
        return view('{$pluralModel}.show', compact('{$modelVariable}'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit({$modelName} \${$modelVariable}): View
    {
        return view('{$pluralModel}.edit', compact('{$modelVariable}'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request \$request, {$modelName} \${$modelVariable}): RedirectResponse
    {
        \$validated = \$request->validate([
            // TODO: Add validation rules
        ]);

        \${$modelVariable}->update(\$validated);

        return redirect()->route('{$pluralModel}.index')
            ->with('success', '{$modelName} updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy({$modelName} \${$modelVariable}): RedirectResponse
    {
        \${$modelVariable}->delete();

        return redirect()->route('{$pluralModel}.index')
            ->with('success', '{$modelName} deleted successfully');
    }
PHP;
    }

    /**
     * Create model if it doesn't exist.
     */
    protected function createModelIfNeeded(string $modelName): bool
    {
        $modelClass = "App\\Models\\{$modelName}";

        // Check if model already exists
        if (class_exists($modelClass)) {
            return false;
        }

        $path = app_path("Models/{$modelName}.php");

        // Create directory if it doesn't exist
        $directory = dirname($path);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Generate model content
        $content = $this->generateModelContent($modelName);

        // Write the file
        file_put_contents($path, $content);

        return true;
    }

    /**
     * Generate model content.
     */
    protected function generateModelContent(string $modelName): string
    {
        $tableName = str($modelName)->plural()->snake()->lower();

        return <<<PHP
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class {$modelName} extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected \$table = '{$tableName}';

    /**
     * The attributes that are mass assignable.
     */
    protected \$fillable = [
        // TODO: Add fillable fields
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            // TODO: Add casts
        ];
    }
}

PHP;
    }

    /**
     * Create API Resource if it doesn't exist.
     */
    protected function createResourceIfNeeded(string $modelName): bool
    {
        $resourceName = "{$modelName}Resource";
        $path = app_path("Http/Resources/{$resourceName}.php");

        // Check if resource already exists
        if (file_exists($path)) {
            return false;
        }

        // Create directory if it doesn't exist
        $directory = dirname($path);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Generate resource content
        $content = $this->generateResourceContent($modelName, $resourceName);

        // Write the file
        file_put_contents($path, $content);

        return true;
    }

    /**
     * Generate resource content.
     */
    protected function generateResourceContent(string $modelName, string $resourceName): string
    {
        return <<<PHP
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class {$resourceName} extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request \$request): array
    {
        return [
            'id' => \$this->id,
            // TODO: Add resource fields
            'created_at' => \$this->created_at?->toISOString(),
            'updated_at' => \$this->updated_at?->toISOString(),
        ];
    }
}

PHP;
    }

    /**
     * Create Resource Collection if it doesn't exist.
     */
    protected function createCollectionIfNeeded(string $modelName): bool
    {
        $collectionName = "{$modelName}Collection";
        $path = app_path("Http/Resources/{$collectionName}.php");

        // Check if collection already exists
        if (file_exists($path)) {
            return false;
        }

        // Create directory if it doesn't exist
        $directory = dirname($path);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Generate collection content
        $content = $this->generateCollectionContent($modelName, $collectionName);

        // Write the file
        file_put_contents($path, $content);

        return true;
    }

    /**
     * Generate collection content.
     */
    protected function generateCollectionContent(string $modelName, string $collectionName): string
    {
        $resourceName = "{$modelName}Resource";

        return <<<PHP
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class {$collectionName} extends ResourceCollection
{
    /**
     * The resource that this collection collects.
     */
    public \$collects = {$resourceName}::class;

    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request \$request): array
    {
        return [
            'data' => \$this->collection,
            'meta' => [
                'total' => \$this->total(),
                'per_page' => \$this->perPage(),
                'current_page' => \$this->currentPage(),
                'last_page' => \$this->lastPage(),
            ],
        ];
    }
}

PHP;
    }

    /**
     * Create Form Request if it doesn't exist.
     */
    protected function createRequestIfNeeded(string $modelName): bool
    {
        $requestName = "{$modelName}Request";
        $path = app_path("Http/Requests/{$requestName}.php");

        // Check if request already exists
        if (file_exists($path)) {
            return false;
        }

        // Create directory if it doesn't exist
        $directory = dirname($path);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Generate request content
        $content = $this->generateRequestContent($modelName, $requestName);

        // Write the file
        file_put_contents($path, $content);

        return true;
    }

    /**
     * Generate request content.
     */
    protected function generateRequestContent(string $modelName, string $requestName): string
    {
        return <<<PHP
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class {$requestName} extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // TODO: Implement authorization logic
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // TODO: Add validation rules
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            // TODO: Add custom attribute names
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            // TODO: Add custom error messages
        ];
    }
}

PHP;
    }
}
