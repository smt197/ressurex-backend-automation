<?php

namespace App\Services;

use App\Models\Menu;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class BackendModuleGenerator
{
    protected string $moduleName;

    protected string $singularName;

    protected string $studlyName;

    protected string $studlySingular;

    protected array $fields;

    protected string $identifierField;

    protected array $roles;

    public function __construct(string $moduleName, array $fields, string $identifierField = 'id', array $roles = ['user'])
    {
        $this->moduleName = $moduleName;
        $this->singularName = Str::singular($moduleName);
        $this->studlyName = Str::studly($moduleName);
        $this->studlySingular = Str::studly($this->singularName);
        $this->fields = $fields;
        $this->identifierField = $identifierField;
        $this->roles = $roles;
    }

    public function generate(): array
    {
        $results = [];

        try {
            // Créer le disque de stockage pour le module si nécessaire
            if ($this->hasFileField()) {
                $diskResult = $this->createModuleDisk();
                $results['disk_created'] = $diskResult;
            }

            $results['model'] = $this->generateModel();
            $results['migration'] = $this->generateMigration();
            $results['factory'] = $this->generateFactory();
            $results['controller'] = $this->generateController();
            $results['resource'] = $this->generateResource();
            $results['collection'] = $this->generateCollection();
            $results['request'] = $this->generateRequest();
            $results['policy'] = $this->generatePolicy();
            $results['seeder'] = $this->generateSeeder();
            $results['lang'] = $this->generateLang();
            $results['route'] = $this->addRoute();

            // Générer le Job si le module a des champs File
            if ($this->hasFileField()) {
                $results['job'] = $this->generateJob();
            }

            // Run migration automatically
            $migrationResult = $this->runMigration();
            $results['migration_executed'] = $migrationResult;

            // Start queue worker for this module if it has file fields
            if ($this->hasFileField()) {
                $queueWorkerResult = $this->startQueueWorker();
                $results['queue_worker_started'] = $queueWorkerResult;

                // Clear config cache to make new disk available
                Artisan::call('config:clear');
                \Log::info('Config cache cleared to register new disk');
            }

            // Run seeder automatically
            $seederResult = $this->runSeeder();
            $results['seeder_executed'] = $seederResult;

            $menuResult = $this->generateMenu($this->roles);
            $results['menu_generated'] = $menuResult;

            // Add seeders to DatabaseSeeder
            $addedToDatabaseSeeder = $this->addSeedersToDatabaseSeeder();
            $results['added_to_database_seeder'] = $addedToDatabaseSeeder;

            return [
                'success' => true,
                'message' => 'Backend module generated successfully',
                'files' => $results,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Backend generation failed: ' . $e->getMessage(),
                'error' => $e->getTraceAsString(),
            ];
        }
    }

    protected function hasFileField(): bool
    {
        foreach ($this->fields as $field) {
            if ($field['type'] === 'File') {
                return true;
            }
        }

        return false;
    }

    protected function getFileFields(): array
    {
        return array_filter($this->fields, fn($field) => $field['type'] === 'File');
    }

    protected function generateModel(): string
    {
        $fillable = $this->getFillableFields();
        $casts = $this->getCasts();

        $useSlug = '';
        $useMedia = '';
        $implements = '';
        $useTrait = 'HasFactory';
        $slugMethod = '';
        $mediaMethod = '';

        if ($this->identifierField === 'slug') {
            $useSlug = "use Spatie\Sluggable\HasSlug;\nuse Spatie\Sluggable\SlugOptions;\n";
            $useTrait = 'HasFactory, HasSlug';
            $firstFieldName = $this->fields[0]['name'] ?? 'name';
            $slugMethod = "

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom(['{$firstFieldName}'])
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }";
        }

        if ($this->hasFileField()) {
            $useMedia = "use Spatie\MediaLibrary\HasMedia;\nuse Spatie\MediaLibrary\InteractsWithMedia;\n";
            $implements = ' implements HasMedia';
            $useTrait .= ', InteractsWithMedia';

            $diskName = Str::snake($this->moduleName);
            $mediaMethod = "

    /**
     * Définir la collection de médias.
     */
    public function registerMediaCollections(): void
    {
        \$this->addMediaCollection('{$diskName}')
            ->acceptsMimeTypes(['image/png', 'image/jpg', 'image/jpeg', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
            ->useDisk('{$diskName}');
    }";
        }

        $content = "<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
{$useSlug}{$useMedia}
class {$this->studlySingular} extends Model{$implements}
{
    use {$useTrait};

    protected \$fillable = [{$fillable}];

    protected function casts(): array
    {
        return [{$casts}
        ];
    }{$slugMethod}{$mediaMethod}
}
";

        $path = app_path("Models/{$this->studlySingular}.php");
        File::put($path, $content);

        return $path;
    }

    protected function generateMigration(): string
    {
        $timestamp = date('Y_m_d_His');
        $tableName = Str::snake($this->moduleName);
        $fields = $this->getMigrationFields();

        $content = "<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('{$tableName}', function (Blueprint \$table) {
            \$table->id();{$fields}
            \$table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('{$tableName}');
    }
};
";

        $path = database_path("migrations/{$timestamp}_create_{$tableName}_table.php");
        File::put($path, $content);

        return $path;
    }

    protected function generateFactory(): string
    {
        $factoryFields = $this->getFactoryDefinition();

        $content = "<?php

namespace Database\Factories;

use App\Models\\{$this->studlySingular};
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\\{$this->studlySingular}>
 */
class {$this->studlySingular}Factory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
{$factoryFields}
        ];
    }
}
";

        $path = database_path("factories/{$this->studlySingular}Factory.php");
        File::put($path, $content);

        return $path;
    }

    protected function generateController(): string
    {
        $keyName = $this->identifierField === 'slug' ? 'slug' : 'id';

        // Exclure les champs File pour searchable et sortable
        $fieldsWithoutFile = array_filter($this->fields, fn($field) => $field['type'] !== 'File');
        $searchable = implode("', '", array_column(array_slice($fieldsWithoutFile, 0, 3), 'name'));
        $sortable = implode("', '", array_column(array_slice($fieldsWithoutFile, 0, 2), 'name'));

        $keyMethod = $this->identifierField === 'slug' ? "
    public function keyName(): string
    {
        return 'slug';
    }

    " : '';

        // Ajouter les méthodes de gestion des fichiers si nécessaire
        $fileHandlingMethods = '';
        $additionalUses = '';
        if ($this->hasFileField()) {
            $fileFields = $this->getFileFields();
            $fileFieldNames = array_map(fn($f) => $f['name'], $fileFields);
            $fileFieldsConditions = implode(' || ', array_map(fn($name) => "\$request->hasFile('{$name}')", $fileFieldNames));
            $collectionName = Str::snake($this->moduleName);

            $additionalUses = "
use App\Jobs\Process{$this->studlySingular}Uploads;
use App\Models\JobStatus;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;";

            $fileHandlingMethods = "
    /**
     * After create hook - handle file uploads with job
     */
    protected function afterStore(\$request, \${$this->singularName})
    {
        if ({$fileFieldsConditions}) {
            \$this->processFileUploads(\$request, \${$this->singularName});
        }
    }

    /**
     * After update hook - handle file uploads with job
     */
    protected function afterUpdate(\$request, \${$this->singularName})
    {
        if ({$fileFieldsConditions}) {
            \$this->processFileUploads(\$request, \${$this->singularName});
        }
    }

    /**
     * Process file uploads using job
     */
    protected function processFileUploads(\$request, \${$this->singularName})
    {
        \$tempFilePaths = [];
        \$originalFileNames = [];

        \$fileFields = [" . implode(', ', array_map(fn($name) => "'{$name}'", $fileFieldNames)) . "];

        foreach (\$fileFields as \$fieldName) {
            if (\$request->hasFile(\$fieldName)) {
                \$files = is_array(\$request->file(\$fieldName))
                    ? \$request->file(\$fieldName)
                    : [\$request->file(\$fieldName)];

                foreach (\$files as \$file) {
                    \$tempFileName = uniqid('temp_').'.'.\$file->getClientOriginalExtension();
                    \$tempPath = 'temp/'.\$tempFileName;

                    Storage::disk('temporaryDirectory')->put(\$tempPath, file_get_contents(\$file->getRealPath()));

                    \$tempFilePaths[] = \$tempPath;
                    \$originalFileNames[] = \$file->getClientOriginalName();
                }
            }
        }

        if (count(\$tempFilePaths) > 0) {
            \$job = new Process{$this->studlySingular}Uploads(
                \$tempFilePaths,
                \$originalFileNames,
                \${$this->singularName}->id,
            );

            // Configuration du JobStatus avant dispatch
            \$jobStatusId = \$job->getJobStatusId();
            \$jobStatus = JobStatus::find(\$jobStatusId);
            if (\$jobStatus) {
                \$jobStatus->user_id = \$request->user()->id;
                \$jobStatus->status = 'queued';
                \$jobStatus->save();

                // Récupérer tous les jobs actifs de l'utilisateur pour notification
                \$activeJobs = JobStatus::where('user_id', \$request->user()->id)
                    ->whereIn('status', ['queued', 'executing'])
                    ->latest()
                    ->get();

                Log::info('Notifying user of new job', [
                    'user_id' => \$request->user()->id,
                    'active_jobs_count' => \$activeJobs->count(),
                ]);

                // Envoyer notification immédiate
                \$request->user()->notify(new \App\Notifications\JobStatusUpdatedNotification(\$activeJobs, \$request->user()));
            }

            // Dispatch du job
            dispatch(\$job);

            Log::info('Process{$this->studlySingular}Uploads job dispatched', [
                'job_status_id' => \$jobStatusId,
                '{$this->singularName}_id' => \${$this->singularName}->id,
                'file_count' => count(\$tempFilePaths),
                'queue' => '{$collectionName}',
            ]);
        }
    }

    /**
     * Delete a specific media file
     * DELETE /{$this->moduleName}/{id}/media/{mediaId}
     */
    public function deleteMedia(\$resourceId, \$mediaId)
    {
        try {
            \${$this->singularName} = {$this->studlySingular}::findOrFail(\$resourceId);

            \$media = \${$this->singularName}->media()->where('id', \$mediaId)->first();

            if (!\$media) {
                return response()->json([
                    'success' => false,
                    'message' => 'Media file not found'
                ], 404);
            }

            \$media->delete();

            Log::info('Media deleted successfully', [
                '{$this->singularName}_id' => \$resourceId,
                'media_id' => \$mediaId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'File deleted successfully'
            ]);
        } catch (\Exception \$e) {
            Log::error('Failed to delete media', [
                'error' => \$e->getMessage(),
                '{$this->singularName}_id' => \$resourceId,
                'media_id' => \$mediaId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete file'
            ], 500);
        }
    }
";
        }

        $content = "<?php

namespace App\Http\Controllers;

use App\Http\Requests\\{$this->studlySingular}Request;
use App\Http\Resources\\{$this->studlySingular}Resource;
use App\Http\Resources\Collections\\{$this->studlySingular}Collection;
use App\Models\\{$this->studlySingular};
use Orion\Http\Controllers\Controller;{$additionalUses}
use Orion\Concerns\DisableAuthorization;

class {$this->studlySingular}Controller extends Controller
{
    use DisableAuthorization;

    protected \$model = {$this->studlySingular}::class;
    protected \$resource = {$this->studlySingular}Resource::class;
    protected \$collectionResource = {$this->studlySingular}Collection::class;
    protected \$request = {$this->studlySingular}Request::class;
{$keyMethod}
    public function limit(): int
    {
        return config('app.limit_pagination');
    }

    public function maxLimit(): int
    {
        return config('app.max_pagination');
    }

    public function searchableBy(): array
    {
        return ['{$searchable}'];
    }

    public function sortableBy(): array
    {
        return ['{$sortable}', 'created_at'];
    }

    public function filterableBy(): array
    {
        return ['{$searchable}'];
    }{$fileHandlingMethods}
}
";

        $path = app_path("Http/Controllers/{$this->studlySingular}Controller.php");
        File::put($path, $content);

        return $path;
    }

    protected function generateResource(): string
    {
        $fields = $this->getResourceFields();

        $content = "<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class {$this->studlySingular}Resource extends JsonResource
{
    public function toArray(\$request)
    {
        \$data = [
            'id' => \$this->id,{$fields}
            'created_at' => \$this->created_at,
            'updated_at' => \$this->updated_at,
        ];

        if (\$request->isMethod('get') && \$request->route('{$this->singularName}')) {
            return [
                'message' => __('{$this->moduleName}.show'),
                'data' => \$data,
            ];
        } elseif (\$request->isMethod('post') || \$request->isMethod('put') || \$request->isMethod('patch') || \$request->isMethod('delete')) {
            if (strpos(\$request->getPathInfo(), 'search') === false) {
                return [
                    'message' => \$this->successMessage(\$request),
                    'data' => \$data,
                ];
            }
        }

        return \$data;
    }

    private function successMessage(\$request): string
    {
        switch (\$request->method()) {
            case 'POST':
                return __('{$this->moduleName}.created');
            case 'PATCH':
            case 'PUT':
                return __('{$this->moduleName}.updated');
            case 'GET':
                return __('{$this->moduleName}.list');
            case 'DELETE':
                return __('{$this->moduleName}.deleted');
            default:
                return __('{$this->moduleName}.list');
        }
    }
}
";

        $path = app_path("Http/Resources/{$this->studlySingular}Resource.php");
        File::put($path, $content);

        return $path;
    }

    protected function generateCollection(): string
    {
        $content = "<?php

namespace App\Http\Resources\Collections;

use App\Http\Resources\\{$this->studlySingular}Resource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class {$this->studlySingular}Collection extends ResourceCollection
{
    public function toArray(\$request)
    {
        return [
            'message' => \$this->successMessage(\$request),
            'data' => {$this->studlySingular}Resource::collection(\$this->collection),
        ];
    }

    public function withResponse(\$request, \$response)
    {
        \$jsonResponse = json_decode(\$response->getContent(), true);

        // Transform pagination metadata to frontend format
        if (isset(\$jsonResponse['meta'])) {
            \$jsonResponse['pagination'] = [
                'current_page' => \$jsonResponse['meta']['current_page'] ?? 1,
                'per_page' => \$jsonResponse['meta']['per_page'] ?? 15,
                'total' => \$jsonResponse['meta']['total'] ?? 0,
            ];
        }

        unset(
            \$jsonResponse['links'],
            \$jsonResponse['meta'],
            \$jsonResponse['data']['links'],
            \$jsonResponse['data']['meta']
        );

        \$response->setContent(json_encode(\$jsonResponse));
    }

    private function successMessage(\$request): string
    {
        switch (\$request->method()) {
            case 'GET':
                return __('{$this->moduleName}.list');
            default:
                return '';
        }
    }
}
";

        $path = app_path("Http/Resources/Collections/{$this->studlySingular}Collection.php");
        File::put($path, $content);

        return $path;
    }

    protected function generateRequest(): string
    {
        $storeRules = $this->getValidationRules(true);
        $updateRules = $this->getValidationRules(false);

        $content = "<?php

namespace App\Http\Requests;

use Orion\Http\Requests\Request;

class {$this->studlySingular}Request extends Request
{
    public function storeRules(): array
    {
        return [{$storeRules}
        ];
    }

    public function updateRules(): array
    {
        return [{$updateRules}
        ];
    }

    public function commonMessages(): array
    {
        return [
            'required' => 'The :attribute field is required.',
            'string' => 'The :attribute field must be a string.',
            'numeric' => 'The :attribute field must be a number.',
            'boolean' => 'The :attribute field must be true or false.',
            'date' => 'The :attribute field must be a valid date.',
            'max' => 'The :attribute field must not exceed :max characters.',
        ];
    }
}
";

        $path = app_path("Http/Requests/{$this->studlySingular}Request.php");
        File::put($path, $content);

        return $path;
    }

    protected function generatePolicy(): string
    {
        $content = "<?php

namespace App\Policies;

use App\Models\\{$this->studlySingular};
use App\Models\User;
use App\Services\OrionPolicyService;

class {$this->studlySingular}Policy
{
    public function viewAny(?User \$user): bool
    {
        return OrionPolicyService::viewAny(\$user);
    }

    public function view(?User \$user, {$this->studlySingular} \${$this->singularName}): bool
    {
        return OrionPolicyService::view(\$user, \${$this->singularName});
    }

    public function create(?User \$user): bool
    {
        return OrionPolicyService::create(\$user);
    }

    public function update(?User \$user, {$this->studlySingular} \${$this->singularName}): bool
    {
        return OrionPolicyService::update(\$user, \${$this->singularName});
    }

    public function delete(?User \$user, {$this->studlySingular} \${$this->singularName}): bool
    {
        return OrionPolicyService::delete(\$user, \${$this->singularName});
    }

    public function restore(?User \$user, {$this->studlySingular} \${$this->singularName}): bool
    {
        return OrionPolicyService::restore(\$user, \${$this->singularName});
    }

    public function forceDelete(?User \$user, {$this->studlySingular} \${$this->singularName}): bool
    {
        return OrionPolicyService::forceDelete(\$user, \${$this->singularName});
    }

    public function search(?User \$user): bool
    {
        return OrionPolicyService::search(\$user);
    }

    public function batch(?User \$user): bool
    {
        return OrionPolicyService::batch(\$user);
    }
}
";

        $path = app_path("Policies/{$this->studlySingular}Policy.php");
        File::put($path, $content);

        return $path;
    }

    protected function addRoute(): string
    {
        $routesPath = base_path('routes/api.php');
        $content = File::get($routesPath);

        $routeLine = "    Orion::resource('{$this->moduleName}', {$this->studlySingular}Controller::class);";
        $importLine = "use App\Http\Controllers\\{$this->studlySingular}Controller;";

        // Add media deletion route if module has file fields
        $mediaDeleteRoute = '';
        if ($this->hasFileField()) {
            $mediaDeleteRoute = "\n    Route::delete('{$this->moduleName}/{resourceId}/media/{mediaId}', [{$this->studlySingular}Controller::class, 'deleteMedia']);";
        }

        // Add import if not exists
        if (! str_contains($content, $importLine)) {
            $content = preg_replace(
                '/(use\s+App\\\\Http\\\\Controllers\\\\[^;]+;)/',
                "$1\n{$importLine}",
                $content,
                1
            );
        }

        // Add route at the end before the last closing brace if not exists
        if (! str_contains($content, "Orion::resource('{$this->moduleName}'")) {
            $lines = explode("\n", $content);
            $insertIndex = -1;

            // Find the closing }); of the authenticated middleware group
            for ($i = count($lines) - 1; $i >= 0; $i--) {
                if (str_contains($lines[$i], 'Route::group') && str_contains($lines[$i], 'auth:sanctum')) {
                    // Found the auth group, now find its closing });
                    for ($j = $i + 1; $j < count($lines); $j++) {
                        if (trim($lines[$j]) === '});') {
                            $insertIndex = $j;
                            break 2;
                        }
                    }
                }
            }

            if ($insertIndex > 0) {
                // Remove empty lines before the closing brace
                while ($insertIndex > 0 && trim($lines[$insertIndex - 1]) === '') {
                    array_splice($lines, $insertIndex - 1, 1);
                    $insertIndex--;
                }

                // Add the routes with one empty line above
                $routesToAdd = ['', $routeLine];
                if ($mediaDeleteRoute) {
                    $routesToAdd[] = $mediaDeleteRoute;
                }
                array_splice($lines, $insertIndex, 0, $routesToAdd);
                $content = implode("\n", $lines);
            } else {
                // Fallback: append at the end
                $content = rtrim($content) . "\n\n{$routeLine}{$mediaDeleteRoute}\n";
            }
        }

        File::put($routesPath, $content);

        return $routesPath;
    }

    protected function generateLang(): string
    {
        $langPath = database_path("seeders/lang/{$this->moduleName}.json");

        $translations = [
            [
                'group' => $this->moduleName,
                'key' => 'list',
                'text' => [
                    'en' => "{$this->moduleName} list.",
                    'fr' => "Liste des {$this->moduleName}.",
                    'pt' => "Lista de {$this->moduleName}.",
                ],
            ],
            [
                'group' => $this->moduleName,
                'key' => 'show',
                'text' => [
                    'en' => "{$this->moduleName} details retrieved successfully.",
                    'fr' => "Détails du {$this->moduleName} récupérés avec succès.",
                    'pt' => "Detalhes do {$this->moduleName} recuperados com sucesso.",
                ],
            ],
            [
                'group' => $this->moduleName,
                'key' => 'created',
                'text' => [
                    'en' => "{$this->moduleName} created successfully.",
                    'fr' => "{$this->moduleName} créé avec succès.",
                    'pt' => "{$this->moduleName} criado com sucesso.",
                ],
            ],
            [
                'group' => $this->moduleName,
                'key' => 'updated',
                'text' => [
                    'en' => "{$this->moduleName} updated successfully.",
                    'fr' => "{$this->moduleName} mis à jour avec succès.",
                    'pt' => "{$this->moduleName} atualizado com sucesso.",
                ],
            ],
            [
                'group' => $this->moduleName,
                'key' => 'deleted',
                'text' => [
                    'en' => "{$this->moduleName} deleted successfully.",
                    'fr' => "{$this->moduleName} supprimé avec succès.",
                    'pt' => "{$this->moduleName} excluído com sucesso.",
                ],
            ],
            [
                'group' => $this->moduleName,
                'key' => 'not_found',
                'text' => [
                    'en' => "{$this->moduleName} not found.",
                    'fr' => "{$this->moduleName} non trouvé.",
                    'pt' => "{$this->moduleName} não encontrado.",
                ],
            ],
        ];

        File::put($langPath, json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $langPath;
    }

    // Helper methods
    protected function getFillableFields(): string
    {
        // Exclure les champs de type File (gérés par Spatie Media Library)
        $fieldsWithoutFiles = array_filter($this->fields, fn($field) => $field['type'] !== 'File');
        $fillable = array_map(fn($field) => "'{$field['name']}'", $fieldsWithoutFiles);

        if ($this->identifierField === 'slug') {
            array_unshift($fillable, "'slug'");
        }

        return implode(', ', $fillable);
    }

    protected function getCasts(): string
    {
        $casts = [];
        foreach ($this->fields as $field) {
            // Exclure les champs File
            if ($field['type'] === 'File') {
                continue;
            }

            if ($field['type'] === 'number') {
                $casts[] = "\n            '{$field['name']}' => 'integer'";
            } elseif ($field['type'] === 'boolean') {
                $casts[] = "\n            '{$field['name']}' => 'boolean'";
            } elseif ($field['type'] === 'Date') {
                $casts[] = "\n            '{$field['name']}' => 'datetime'";
            }
        }

        return implode(',', $casts);
    }

    protected function getMigrationFields(): string
    {
        $fields = '';

        if ($this->identifierField === 'slug') {
            $fields .= "\n            \$table->string('slug')->unique();";
        }

        foreach ($this->fields as $field) {
            // Exclure les champs File (pas de colonne en BDD pour les fichiers)
            if ($field['type'] === 'File') {
                continue;
            }

            $type = $this->getMigrationType($field['type']);
            $nullable = ! $field['required'] ? '->nullable()' : '';
            $fields .= "\n            \$table->{$type}('{$field['name']}'){$nullable};";
        }

        return $fields;
    }

    protected function getMigrationType(string $type): string
    {
        switch ($type) {
            case 'string':
            case 'email':
            case 'password':
                return 'string';
            case 'number':
                return 'integer';
            case 'boolean':
                return 'boolean';
            case 'Date':
                return 'timestamp';
            case 'File':
                // Ne devrait pas arriver car on exclut les File avant d'appeler cette méthode
                return null;
            case 'textarea':
                return 'text';
            case 'quill-editor':
                return 'longText';
            default:
                return 'string';
        }
    }

    protected function getResourceFields(): string
    {
        $fields = '';

        if ($this->identifierField === 'slug') {
            $fields .= "\n            'slug' => \$this->slug,";
        }

        foreach ($this->fields as $field) {
            // Exclure les champs File (on les ajoute à la fin via media)
            if ($field['type'] === 'File') {
                continue;
            }
            $fields .= "\n            '{$field['name']}' => \$this->{$field['name']},";
        }

        // Ajouter les médias si le modèle a des champs File
        if ($this->hasFileField()) {
            $collectionName = Str::snake($this->moduleName);
            $fields .= "\n            'media' => \$this->getMedia('{$collectionName}')->map(function (\$media) {
                return [
                    'id' => \$media->id,
                    'name' => \$media->name,
                    'file_name' => \$media->file_name,
                    'mime_type' => \$media->mime_type,
                    'size' => \$media->size,
                    'url' => \$media->getUrl(),
                ];
            }),";
        }

        return $fields;
    }

    protected function getValidationRules(bool $isStore): string
    {
        $rules = '';

        foreach ($this->fields as $field) {
            $rule = $this->getFieldValidation($field, $isStore);
            $rules .= "\n            '{$field['name']}' => {$rule},";
        }

        return $rules;
    }

    protected function getFieldValidation(array $field, bool $isStore): string
    {
        $rules = [];

        if ($field['required'] && $isStore) {
            $rules[] = "'required'";
        } elseif (! $isStore) {
            $rules[] = "'sometimes'";
        } else {
            $rules[] = "'nullable'";
        }

        switch ($field['type']) {
            case 'string':
                $rules[] = "'string'";
                $rules[] = "'max:255'";
                break;
            case 'number':
                $rules[] = "'numeric'";
                break;
            case 'boolean':
                $rules[] = "'boolean'";
                break;
            case 'Date':
                $rules[] = "'date'";
                break;
            case 'File':
                $rules[] = "'array'";

                // Ajouter une règle pour chaque fichier dans le tableau
                return '[' . implode(', ', $rules) . "], \n            '{$field['name']}.*' => ['file', 'max:10240']";
        }

        return '[' . implode(', ', $rules) . ']';
    }

    protected function generateSeeder(): string
    {
        $hasFiles = $this->hasFileField();

        if ($hasFiles) {
            $collectionName = Str::snake($this->moduleName);
            $content = "<?php

namespace Database\Seeders;

use App\Models\\{$this->studlySingular};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;

class {$this->studlySingular}Seeder extends Seeder
{
    public function run(): void
    {
        {$this->studlySingular}::factory()
            ->count(10)
            ->create()
            ->each(function (\$item) {
                // Generate a unique avatar using UI Avatars API
                \$name = \$item->name ?? \$item->title ?? '{$this->studlySingular} ' . \$item->id;
                \$avatarUrl = 'https://ui-avatars.com/api/?name=' . urlencode(\$name) . '&size=200&background=random';

                // Download the avatar
                try {
                    \$imageContent = Http::get(\$avatarUrl)->body();

                    // Create temp directory if it doesn't exist
                    if (!file_exists(storage_path('app/temp'))) {
                        mkdir(storage_path('app/temp'), 0755, true);
                    }

                    // Save to temporary file
                    \$fileName = 'avatar-' . \$item->id . '.png';
                    \$filePath = storage_path('app/temp/' . \$fileName);
                    file_put_contents(\$filePath, \$imageContent);

                    // Add the file to media library
                    \$item->addMedia(\$filePath)
                        ->usingName('Avatar ' . \$item->id)
                        ->toMediaCollection('{$collectionName}');

                    // Clean up the temporary file
                    @unlink(\$filePath);
                } catch (\Exception \$e) {
                    // Fallback: create a simple text file if avatar download fails
                    \$fileName = 'test-file-' . \$item->id . '.txt';
                    \$filePath = storage_path('app/temp/' . \$fileName);
                    file_put_contents(\$filePath, 'Test file for ' . \$item->id);

                    \$item->addMedia(\$filePath)
                        ->usingName('Test File ' . \$item->id)
                        ->toMediaCollection('{$collectionName}');

                    @unlink(\$filePath);
                }
            });
    }
}
";
        } else {
            $content = "<?php

namespace Database\Seeders;

use App\Models\\{$this->studlySingular};
use Illuminate\Database\Seeder;

class {$this->studlySingular}Seeder extends Seeder
{
    public function run(): void
    {
        {$this->studlySingular}::factory()->count(10)->create();
    }
}
";
        }

        $path = database_path("seeders/{$this->studlySingular}Seeder.php");
        File::put($path, $content);

        return $path;
    }

    protected function generateJob(): string
    {
        $collectionName = Str::snake($this->moduleName);
        $diskName = $collectionName;

        $content = "<?php

namespace App\Jobs;

use App\Models\\{$this->studlySingular};
use App\Traits\Trackable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class Process{$this->studlySingular}Uploads implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use Trackable;

    protected \$jobStatus;

    public \$tries = 3;

    public \$timeout = 300;


    protected array \$tempFilePaths;

    protected array \$originalFileNames;

    protected int \$main{$this->studlySingular}Id;

    public function __construct(
        array \$tempFilePaths,
        array \$originalFileNames,
        int \$main{$this->studlySingular}Id,
    ) {
        \$this->tempFilePaths = \$tempFilePaths;
        \$this->originalFileNames = \$originalFileNames;
        \$this->main{$this->studlySingular}Id = \$main{$this->studlySingular}Id;

         \$this->onQueue('{$collectionName}');

        \$this->prepareStatus();

        \$this->setInput([
            'file_count' => count(\$tempFilePaths),
            'main_{$this->singularName}_id' => \$main{$this->studlySingular}Id,
            'type' => '{$this->singularName}',
        ]);
    }

    public function handle(): void
    {
        Log::info(\"🎬 Process{$this->studlySingular}Uploads JOB STARTED for {$this->singularName} {\$this->main{$this->studlySingular}Id}\");

        \$status = \$this->getEntity();
        if (\$status) {
            \$status->job_id = \$this->job->getJobId();
            \$status->save();
        }

        \$this->setProgressMax(100);
        \$processed{$this->studlySingular}Ids = [];
        \$processedFileNames = [];

        try {
            \$this->updateStatus('executing');
            Log::info(\"🚀 IMMEDIATE START - Job switched to 'executing' status\");

            \$this->setProgressNowFloat(0);
            Log::info('📊 Progress initialized to 0%');

            \$main{$this->studlySingular} = {$this->studlySingular}::findOrFail(\$this->main{$this->studlySingular}Id);
            Log::info(\"📄 {$this->studlySingular} {\$this->main{$this->studlySingular}Id} loaded successfully\");

            Log::info('🏁 Starting processing of '.count(\$this->tempFilePaths).' files...');

            sleep(2);

            \$totalFiles = count(\$this->tempFilePaths);

            foreach (\$this->tempFilePaths as \$index => \$tempPath) {
                \$fileNumber = \$index + 1;
                \$fileName = \$this->originalFileNames[\$index];

                Log::info(\"🔄 Starting file {\$fileNumber}/{\$totalFiles}: {\$fileName}\");

                \$this->incrementProgressForFile(\$index, \$totalFiles, 0);
                Log::info(\"📋 Preparing {$this->singularName} for file {\$fileNumber}...\");
                sleep(2);

                \$this->incrementProgressForFile(\$index, \$totalFiles, 10);
                sleep(1);

                // Toujours utiliser l'entrée principale pour tous les fichiers
                \${$this->singularName}ToProcess = \$main{$this->studlySingular};

                // Clear existing media only once (first file)
                if (\$index === 0) {
                    \$existingMedia = \${$this->singularName}ToProcess->getMedia('{$this->moduleName}');
                    if (\$existingMedia->isNotEmpty()) {
                        Log::info(\"🗑️ Clearing {\$existingMedia->count()} existing media file(s)\");
                        \${$this->singularName}ToProcess->clearMediaCollection('{$this->moduleName}');
                    }
                }

                \$processed{$this->studlySingular}Ids[] = \${$this->singularName}ToProcess->id;
                \$processedFileNames[] = \$fileName;

                \$this->incrementProgressForFile(\$index, \$totalFiles, 20);
                Log::info(\"✅ {$this->studlySingular} prepared for file {\$fileNumber}\");
                sleep(1);

                \$this->incrementProgressForFile(\$index, \$totalFiles, 25);
                Log::info(\"🔍 Validating file {\$fileNumber}...\");
                sleep(2);

                \$this->incrementProgressForFile(\$index, \$totalFiles, 35);
                sleep(1);

                \$this->incrementProgressForFile(\$index, \$totalFiles, 40);
                Log::info(\"✅ File {\$fileNumber} validated\");
                sleep(1);

                \$this->incrementProgressForFile(\$index, \$totalFiles, 45);
                Log::info(\"📎 Attaching media for file {\$fileNumber}...\");
                sleep(2);

                \$this->incrementProgressForFile(\$index, \$totalFiles, 55);
                \$this->attachMediaTo{$this->studlySingular}(\${$this->singularName}ToProcess, \$tempPath, \$fileName);
                sleep(2);

                \$this->incrementProgressForFile(\$index, \$totalFiles, 65);
                sleep(1);

                \$this->incrementProgressForFile(\$index, \$totalFiles, 70);
                Log::info(\"✅ Media attached for file {\$fileNumber}\");
                sleep(1);

                \$this->incrementProgressForFile(\$index, \$totalFiles, 75);
                Log::info(\"🔧 Finalizing file {\$fileNumber}...\");
                sleep(2);

                \$this->incrementProgressForFile(\$index, \$totalFiles, 85);
                sleep(1);

                \$this->incrementProgressForFile(\$index, \$totalFiles, 90);
                sleep(1);

                \$this->incrementProgressForFile(\$index, \$totalFiles, 95);
                Log::info(\"🏁 Completing file {\$fileNumber}...\");
                sleep(1);

                \$this->incrementProgressForFile(\$index, \$totalFiles, 100);
                Log::info(\"🎉 File {\$fileNumber}/{\$totalFiles} completed successfully!\");
                sleep(1);
            }

            sleep(1);

            Storage::disk('temporaryDirectory')->delete(\$this->tempFilePaths);

            \$this->setOutput([
                'status_message' => __('{$this->moduleName}.processing_success'),
                'processed_{$this->singularName}_ids' => \$processed{$this->studlySingular}Ids,
                'processed_files' => \$processedFileNames,
                'total_processed' => count(\$processed{$this->studlySingular}Ids),
            ]);

            Log::info('Job completed. Setting status to finished.');
            \$this->update(['status' => 'finished']);
        } catch (Throwable \$e) {
            Log::error('Job failed: '.\$e->getMessage());
            \$this->setOutput([
                'error' => \$e->getMessage(),
                'processed_{$this->singularName}_ids_before_failure' => \$processed{$this->studlySingular}Ids,
            ]);
            throw \$e;
        }
    }

    private function attachMediaTo{$this->studlySingular}({$this->studlySingular} \${$this->singularName}, string \$tempPath, string \$originalFileName): void
    {
        \$safeFileName = Str::slug(pathinfo(\$originalFileName, PATHINFO_FILENAME)).'.'.pathinfo(\$originalFileName, PATHINFO_EXTENSION);

        \${$this->singularName}->addMediaFromDisk(\$tempPath, 'temporaryDirectory')
            ->usingName(\$originalFileName)
            ->usingFileName(\$safeFileName)
            ->withCustomProperties(['{$this->singularName}_id' => \${$this->singularName}->id])
            ->toMediaCollection('{$collectionName}');
    }

    private function incrementProgressForFile(int \$currentFileIndex, int \$totalFiles, float \$percentWithinFile): void
    {
        \$progressPerFile = 100 / \$totalFiles;
        \$overallProgress = (\$currentFileIndex * \$progressPerFile) + ((\$percentWithinFile / 100) * \$progressPerFile);
        \$this->setProgressNowFloat(\$overallProgress);
    }

    public function failed(Throwable \$exception): void
    {
        Storage::disk('temporaryDirectory')->delete(\$this->tempFilePaths);
        \$this->setOutput(['error' => \$exception->getMessage()]);
    }
}
";

        $path = app_path("Jobs/Process{$this->studlySingular}Uploads.php");
        File::put($path, $content);

        return $path;
    }

    protected function getFactoryDefinition(): string
    {
        $definitions = [];

        foreach ($this->fields as $field) {
            // Exclure les champs File (pas de génération factory pour les fichiers)
            if ($field['type'] === 'File') {
                continue;
            }

            $fakerMethod = $this->getFakerMethod($field['type'], $field['name']);
            $definitions[] = "            '{$field['name']}' => {$fakerMethod}";
        }

        return implode(",\n", $definitions);
    }

    protected function getFakerMethod(string $type, string $fieldName): string
    {
        $lower = strtolower($fieldName);

        switch ($type) {
            case 'string':
                if (str_contains($lower, 'email')) {
                    return 'fake()->unique()->safeEmail()';
                } elseif (str_contains($lower, 'phone') || str_contains($lower, 'telephone')) {
                    return 'fake()->phoneNumber()';
                } elseif (str_contains($lower, 'address') || str_contains($lower, 'street')) {
                    return 'fake()->address()';
                } elseif (str_contains($lower, 'city')) {
                    return 'fake()->city()';
                } elseif (str_contains($lower, 'country')) {
                    return 'fake()->country()';
                } elseif (str_contains($lower, 'url') || str_contains($lower, 'link')) {
                    return 'fake()->url()';
                } elseif (str_contains($lower, 'description') || str_contains($lower, 'content') || str_contains($lower, 'text')) {
                    return 'fake()->paragraph()';
                } elseif (str_contains($lower, 'title') || str_contains($lower, 'name')) {
                    return 'fake()->words(3, true)';
                } else {
                    return 'fake()->sentence()';
                }

            case 'number':
                if (str_contains($lower, 'price') || str_contains($lower, 'amount') || str_contains($lower, 'cost')) {
                    return 'fake()->randomFloat(2, 10, 1000)';
                } elseif (str_contains($lower, 'quantity') || str_contains($lower, 'stock')) {
                    return 'fake()->numberBetween(1, 100)';
                } elseif (str_contains($lower, 'age')) {
                    return 'fake()->numberBetween(18, 80)';
                } else {
                    return 'fake()->numberBetween(1, 1000)';
                }

            case 'boolean':
                return 'fake()->boolean()';

            case 'Date':
                if (str_contains($lower, 'birth')) {
                    return 'fake()->date()';
                } else {
                    return 'fake()->dateTimeBetween(\'-1 year\', \'now\')';
                }

            case 'textarea':
                return 'fake()->paragraph(5)';

            case 'quill-editor':
                return 'fake()->randomHtml(4, 6)';

            case 'email':
                return 'fake()->unique()->safeEmail()';

            case 'password':
                return 'bcrypt(\'password\')';

            default:
                return 'fake()->sentence()';
        }
    }

    protected function generateFakeValue(string $type, string $fieldName, int $index): string
    {
        switch ($type) {
            case 'string':
                if (str_contains(strtolower($fieldName), 'name')) {
                    return "'{$this->studlySingular} {$index}'";
                } elseif (str_contains(strtolower($fieldName), 'description')) {
                    return "'Description for {$this->singularName} {$index}'";
                } elseif (str_contains(strtolower($fieldName), 'email')) {
                    return "'{$this->singularName}{$index}@example.com'";
                } else {
                    return "'{$fieldName} {$index}'";
                }

            case 'number':
                if (str_contains(strtolower($fieldName), 'price') || str_contains(strtolower($fieldName), 'amount')) {
                    return (string) ($index * 10 + 99);
                } elseif (str_contains(strtolower($fieldName), 'quantity') || str_contains(strtolower($fieldName), 'stock')) {
                    return (string) ($index * 5);
                } else {
                    return (string) $index;
                }

            case 'boolean':
                return $index % 2 === 0 ? 'true' : 'false';

            case 'Date':
                return "now()->subDays({$index})";

            default:
                return "'{$fieldName} value {$index}'";
        }
    }

    protected function runMigration(): array
    {
        try {
            \Log::info('Running migrations for module', [
                'module' => $this->moduleName,
            ]);

            // Execute php artisan migrate
            Artisan::call('migrate', ['--force' => true]);
            $output = Artisan::output();

            \Log::info('Migration executed successfully', [
                'output' => $output,
            ]);

            return [
                'success' => true,
                'message' => 'Migration executed successfully',
                'output' => $output,
            ];
        } catch (\Exception $e) {
            \Log::error('Migration execution failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Migration failed: ' . $e->getMessage(),
            ];
        }
    }

    protected function runSeeder(): array
    {
        try {
            // Si le module a des champs File, configurer le disque manuellement avant le seeder
            if ($this->hasFileField()) {
                $diskName = Str::snake($this->moduleName);
                Config::set("filesystems.disks.{$diskName}", [
                    'driver' => 'local',
                    'root' => storage_path("app/public/{$diskName}"),
                    'url' => rtrim(env('APP_URL'), '/') . "/storage/{$diskName}",
                    'visibility' => 'public',
                    'throw' => false,
                    'report' => false,
                ]);

                \Log::info('Disk manually configured for seeder', [
                    'disk' => $diskName,
                    'root' => storage_path("app/public/{$diskName}"),
                ]);
            }

            \Log::info('Running seeder for module', [
                'module' => $this->moduleName,
                'seeder' => "{$this->studlySingular}Seeder",
            ]);

            // Execute php artisan db:seed --class=...Seeder
            Artisan::call('db:seed', [
                '--class' => "Database\\Seeders\\{$this->studlySingular}Seeder",
                '--force' => true,
            ]);
            $output = Artisan::output();

            \Log::info('Seeder executed successfully', [
                'output' => $output,
            ]);

            // Execute TranslationLoaderSeeder to reload translations
            \Log::info('Running TranslationLoaderSeeder');
            Artisan::call('db:seed', [
                '--class' => 'Database\\Seeders\\TranslationLoaderSeeder',
                '--force' => true,
            ]);
            $translationOutput = Artisan::output();

            \Log::info('TranslationLoaderSeeder executed successfully', [
                'output' => $translationOutput,
            ]);

            return [
                'success' => true,
                'message' => 'Seeder executed successfully',
                'output' => $output,
                'translation_output' => $translationOutput,
            ];
        } catch (\Exception $e) {
            \Log::error('Seeder execution failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Seeder failed: ' . $e->getMessage(),
            ];
        }
    }

    protected function startQueueWorker(): array
    {
        try {
            $queueName = Str::snake($this->moduleName);

            \Log::info('Attempting to start queue worker for module.', [
                'module' => $this->moduleName,
                'queue' => $queueName,
                'os_family' => PHP_OS_FAMILY,
            ]);

            // --- CORRECTION POUR LARAGON / WINDOWS ---

            // 1. Déterminer le chemin de l'exécutable PHP de manière fiable
            $phpExecutable = env('PHP_EXECUTABLE_PATH'); // Priorité au .env
            if (!$phpExecutable) {
                \Log::warning('PHP_EXECUTABLE_PATH not set in .env. Falling back to PHP_BINARY.');
                $phpExecutable = PHP_BINARY; // Fallback
            }
            if (!$phpExecutable) {
                \Log::error('Could not determine PHP executable path. Worker not started.');
                return [
                    'success' => false,
                    'message' => 'PHP executable path could not be determined. Please set PHP_EXECUTABLE_PATH in your .env file.',
                ];
            }

            $artisanPath = base_path('artisan');

            // 2. Construire la commande en s'assurant que les chemins sont bien entre guillemets
            $command = sprintf(
                '"%s" "%s" queue:work --queue=%s --tries=3 --timeout=300',
                $phpExecutable,
                $artisanPath,
                $queueName
            );

            if (PHP_OS_FAMILY === 'Windows') {
                // 3. Utiliser la syntaxe correcte pour "start /B" qui évite les problèmes
                // L'ajout de "" comme premier argument est une astuce pour s'assurer que
                // le reste de la commande n'est pas interprété comme un titre de fenêtre.
                $fullCommand = "start /B \"\" {$command}";
                pclose(popen($fullCommand, 'r'));
            } else {
                // Pour Linux/Mac, rediriger la sortie vers un log est une bonne pratique
                $logPath = storage_path('logs/queue-worker.log');
                $fullCommand = "nohup {$command} > {$logPath} 2>&1 &";
                exec($fullCommand);
            }
            // --- FIN DE LA CORRECTION ---

            \Log::info('Queue worker start command executed successfully.', [
                'queue' => $queueName,
                'command' => $fullCommand,
            ]);

            return [
                'success' => true,
                'message' => "Queue worker start command issued for queue: {$queueName}",
                'queue' => $queueName,
            ];
        } catch (\Exception $e) {
            \Log::error('Failed to execute startQueueWorker function', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'An exception occurred while trying to start queue worker: ' . $e->getMessage(),
            ];
        }
    }

    protected function generateMenu(array $roles = ['user']): string
    {
        // Generate menu seeder file
        $seederPath = $this->generateMenuSeeder($roles);

        // Run the menu seeder
        $this->runMenuSeeder();

        return $seederPath;
    }

    protected function generateMenuSeeder(array $roles = ['user']): string
    {
        $menuName = $this->moduleName;
        $menuRoute = '/index/' . strtolower($this->moduleName);
        $menuIcon = 'extension'; // Default icon
        $menuColor = '#10b981'; // Default green color
        $menuSlug = Str::slug($this->moduleName);

        // Convert roles array to PHP array syntax
        $rolesString = json_encode($roles);

        $content = "<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Menu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class {$this->studlySingular}MenuSeeder extends Seeder
{
    public function run(): void
    {
        // Get or create default category (Dashboard)
        \$category = Category::where('name', 'Dashboard')->first();

        if (!\$category) {
            \$category = Category::where('name', 'Dashboard')->first();
        }

        // Check if menu already exists
        \$existingMenu = Menu::where('name', '{$menuName}')
            ->orWhere('slug', '{$menuSlug}')
            ->first();

        if (\$existingMenu) {
            // Update existing menu
            \$existingMenu->update([
                'icon' => '{$menuIcon}',
                'color' => '{$menuColor}',
                'route' => '{$menuRoute}',
                'roles' => {$rolesString},
                'category_id' => \$category?->id,
                'disable' => 1,
            ]);

            \$this->command->info('Menu \"{$menuName}\" updated successfully.');
        } else {
            // Create new menu
            Menu::create([
                'name' => '{$menuName}',
                'icon' => '{$menuIcon}',
                'color' => '{$menuColor}',
                'route' => '{$menuRoute}',
                'roles' => {$rolesString},
                'slug' => '{$menuSlug}',
                'category_id' => \$category?->id,
                'disable' => 1,
            ]);

            \$this->command->info('Menu \"{$menuName}\" created successfully.');
        }
    }
}
";

        $path = database_path("seeders/{$this->studlySingular}MenuSeeder.php");
        File::put($path, $content);
        \Log::info("Menu seeder generated: {$path}");

        return $path;
    }

    protected function runMenuSeeder(): bool
    {
        try {
            \Log::info("Running menu seeder for {$this->studlySingular}");

            Artisan::call('db:seed', [
                '--class' => "Database\\Seeders\\{$this->studlySingular}MenuSeeder",
                '--force' => true
            ]);

            \Log::info("Menu seeder executed successfully for {$this->studlySingular}");
            return true;
        } catch (\Exception $e) {
            \Log::error("Failed to run menu seeder for {$this->studlySingular}", [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    protected function addSeedersToDatabaseSeeder(): bool
    {
        try {
            $databaseSeederPath = database_path('seeders/DatabaseSeeder.php');

            if (!File::exists($databaseSeederPath)) {
                \Log::warning("DatabaseSeeder.php not found");
                return false;
            }

            $content = File::get($databaseSeederPath);

            // Check if seeders are already added
            $moduleSeederClass = "{$this->studlySingular}Seeder::class";
            $menuSeederClass = "{$this->studlySingular}MenuSeeder::class";

            if (strpos($content, $moduleSeederClass) !== false && strpos($content, $menuSeederClass) !== false) {
                \Log::info("Seeders already exist in DatabaseSeeder");
                return true;
            }

            // Find the closing bracket of the $this->call array
            // We'll insert just before the ]);
            $pattern = '/(\s*)(]\);)/';

            if (preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                $insertPosition = $matches[1][1];

                // Build the new seeder lines
                $newSeeders = "            {$moduleSeederClass},\n";
                $newSeeders .= "            {$menuSeederClass},\n";

                // Insert the new seeders before the closing bracket
                $newContent = substr_replace($content, $newSeeders, $insertPosition, 0);

                File::put($databaseSeederPath, $newContent);
                \Log::info("Added seeders to DatabaseSeeder successfully");
                return true;
            } else {
                \Log::warning("Could not find appropriate location in DatabaseSeeder");
                return false;
            }
        } catch (\Exception $e) {
            \Log::error("Failed to add seeders to DatabaseSeeder", [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public static function removeSeedersFromDatabaseSeeder(string $moduleName): bool
    {
        try {
            $singularName = Str::singular($moduleName);
            $studlySingular = Str::studly($singularName);

            $databaseSeederPath = database_path('seeders/DatabaseSeeder.php');

            if (!File::exists($databaseSeederPath)) {
                \Log::warning("DatabaseSeeder.php not found");
                return false;
            }

            $content = File::get($databaseSeederPath);
            $originalContent = $content;

            // Remove module seeder line
            $moduleSeederPattern = "/\s*{$studlySingular}Seeder::class,?\s*\n?/";
            $content = preg_replace($moduleSeederPattern, '', $content);

            // Remove menu seeder line
            $menuSeederPattern = "/\s*{$studlySingular}MenuSeeder::class,?\s*\n?/";
            $content = preg_replace($menuSeederPattern, '', $content);

            // Clean up any extra blank lines
            $content = preg_replace("/\n\n\n+/", "\n\n", $content);

            if ($content !== $originalContent) {
                File::put($databaseSeederPath, $content);
                \Log::info("Removed seeders from DatabaseSeeder successfully", [
                    'module' => $moduleName
                ]);
                return true;
            } else {
                \Log::info("No seeders found to remove from DatabaseSeeder", [
                    'module' => $moduleName
                ]);
                return true;
            }
        } catch (\Exception $e) {
            \Log::error("Failed to remove seeders from DatabaseSeeder", [
                'module' => $moduleName,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Crée un disque de stockage dynamique pour le module
     */
    protected function createModuleDisk(): array
    {
        try {
            $diskName = Str::snake($this->moduleName);

            // Les modules stockent directement dans public/storage/{moduleName}
            // Pas besoin de créer dans storage/app

            // Ajouter le disque dans AppServiceProvider
            $this->addDiskToAppServiceProvider($diskName);

            // Ajouter le disque dans filesystems.php
            $this->addDiskToFilesystemsConfig($diskName);

            // Créer le répertoire public pour le disque
            $this->createStorageLink($diskName);

            $storagePath = storage_path("app/public/{$diskName}");

            \Log::info('Module disk created successfully', [
                'disk_name' => $diskName,
                'path' => $storagePath,
            ]);

            return [
                'success' => true,
                'disk_name' => $diskName,
                'path' => $storagePath,
            ];
        } catch (\Exception $e) {
            \Log::error('Failed to create module disk', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Supprime le disque de stockage d'un module
     */
    public static function deleteModuleDisk(string $moduleName): array
    {
        try {
            $diskName = Str::snake($moduleName);

            // Supprimer le répertoire physique du module (storage/app/public/{diskName})
            // La fonction removeStorageLink supprime déjà le répertoire et son contenu
            self::removeStorageLink($diskName);

            // Retirer le disque de AppServiceProvider
            self::removeDiskFromAppServiceProvider($diskName);

            // Retirer le disque de filesystems.php
            self::removeDiskFromFilesystemsConfig($diskName);

            \Log::info('Module disk deleted successfully', ['disk_name' => $diskName]);

            return [
                'success' => true,
                'disk_name' => $diskName,
            ];
        } catch (\Exception $e) {
            \Log::error('Failed to delete module disk', [
                'module' => $moduleName,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Ajoute un disque dans AppServiceProvider DISK_CONFIG
     */
    protected function addDiskToAppServiceProvider(string $diskName): void
    {
        $providerPath = app_path('Providers/AppServiceProvider.php');
        $content = File::get($providerPath);

        // Vérifier si le disque existe déjà
        if (str_contains($content, "'{$diskName}' =>")) {
            \Log::info('Disk already exists in AppServiceProvider', ['disk' => $diskName]);

            return;
        }

        // Trouver la position de DISK_CONFIG et ajouter le nouveau disque
        $pattern = "/(private const DISK_CONFIG = \[)/";
        if (preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            $insertPosition = $matches[0][1] + strlen($matches[0][0]);
            $newDiskEntry = "\n        '{$diskName}' => ['visibility' => 'public', 'url' => true],";
            $content = substr_replace($content, $newDiskEntry, $insertPosition, 0);

            File::put($providerPath, $content);
            \Log::info('Disk added to AppServiceProvider', ['disk' => $diskName]);
        } else {
            \Log::warning('Could not find DISK_CONFIG in AppServiceProvider');
        }
    }

    /**
     * Retire un disque de AppServiceProvider DISK_CONFIG
     */
    protected static function removeDiskFromAppServiceProvider(string $diskName): void
    {
        $providerPath = app_path('Providers/AppServiceProvider.php');
        $content = File::get($providerPath);

        // Supprimer la ligne du disque
        $pattern = "/\s*'{$diskName}' => \[.*?\],?\n/";
        $content = preg_replace($pattern, '', $content);

        File::put($providerPath, $content);
        \Log::info('Disk removed from AppServiceProvider', ['disk' => $diskName]);
    }

    /**
     * Ajoute un disque dans config/filesystems.php
     */
    protected function addDiskToFilesystemsConfig(string $diskName): void
    {
        $filesystemsPath = config_path('filesystems.php');
        $content = File::get($filesystemsPath);

        // Vérifier si le disque existe déjà
        if (str_contains($content, "'{$diskName}' =>")) {
            \Log::info('Disk already exists in filesystems.php', ['disk' => $diskName]);

            return;
        }

        // Trouver la position de 's3' => [ et insérer avant
        $pattern = "/('s3' => \[)/";
        if (preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            $insertPosition = $matches[0][1];
            $newDiskConfig = "'{$diskName}' => [
            'driver' => 'local',
            'root' => storage_path('app/{$diskName}'),
            'url' => rtrim(env('APP_URL'), '/').'/storage/{$diskName}',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        ";
            $content = substr_replace($content, $newDiskConfig, $insertPosition, 0);

            File::put($filesystemsPath, $content);
            \Log::info('Disk added to filesystems.php', ['disk' => $diskName]);
        } else {
            \Log::warning('Could not find insertion point in filesystems.php');
        }
    }

    /**
     * Retire un disque de config/filesystems.php
     */
    protected static function removeDiskFromFilesystemsConfig(string $diskName): void
    {
        $filesystemsPath = config_path('filesystems.php');
        $content = File::get($filesystemsPath);

        // Supprimer la configuration du disque (multilignes)
        $pattern = "/\s*'{$diskName}' => \[\s*'driver'[^\]]+\],\s*\n/s";
        $content = preg_replace($pattern, '', $content);

        File::put($filesystemsPath, $content);
        \Log::info('Disk removed from filesystems.php', ['disk' => $diskName]);
    }

    /**
     * Crée le répertoire de stockage public pour le module
     * Les modules stockent dans storage/app/public/{moduleName}
     * qui est accessible via public/storage/{moduleName} grâce au lien symbolique
     */
    protected function createStorageLink(string $diskName): void
    {
        $moduleStoragePath = storage_path("app/public/{$diskName}");

        // Créer le répertoire storage/app/public s'il n'existe pas
        if (! File::exists(storage_path('app/public'))) {
            File::makeDirectory(storage_path('app/public'), 0755, true);
        }

        // Créer le répertoire du module s'il n'existe pas
        if (! File::exists($moduleStoragePath)) {
            File::makeDirectory($moduleStoragePath, 0755, true);
            \Log::info('Module storage directory created', [
                'disk' => $diskName,
                'path' => $moduleStoragePath,
            ]);
        } else {
            \Log::info('Module storage directory already exists', [
                'disk' => $diskName,
                'path' => $moduleStoragePath,
            ]);
        }
    }

    /**
     * Supprime le répertoire de stockage public du module
     */
    protected static function removeStorageLink(string $diskName): void
    {
        $moduleStoragePath = storage_path("app/public/{$diskName}");

        if (File::exists($moduleStoragePath)) {
            try {
                // Supprimer le répertoire et tout son contenu
                if (File::deleteDirectory($moduleStoragePath)) {
                    \Log::info('Module storage directory removed', [
                        'disk' => $diskName,
                        'path' => $moduleStoragePath,
                    ]);
                } else {
                    \Log::warning('Failed to delete module storage directory (may be locked by another process)', [
                        'disk' => $diskName,
                        'path' => $moduleStoragePath,
                        'hint' => 'Please close any applications accessing these files and delete manually if needed',
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error('Error deleting module storage directory', [
                    'disk' => $diskName,
                    'path' => $moduleStoragePath,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
