<?php

namespace App\Console\Commands;

use App\Services\ModuleManagerService;
use App\Models\ModuleManager;
use App\Models\Deployment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BoostMcpCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'boost:mcp';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs the Model Context Protocol (MCP) server for Laravel Boost';

    /**
     * @var ModuleManagerService
     */
    protected $moduleService;

    public function __construct(ModuleManagerService $moduleService)
    {
        parent::__construct();
        $this->moduleService = $moduleService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Disable output buffering to ensure real-time communication
        if (ob_get_level()) ob_end_clean();
        
        // Log startup
        Log::info('Starting Boost MCP Server...');
        // We can't write to stdout for logging, as it breaks JSON-RPC. Use stderr or log file.
        fwrite(STDERR, "Boost MCP Server Started\n");

        $stdin = fopen('php://stdin', 'r');

        while (true) {
            $line = fgets($stdin);
            if ($line === false) {
                break;
            }

            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            try {
                $request = json_decode($line, true);
                if (!$request || !isset($request['jsonrpc'])) {
                    continue; // Should send parse error, but keep simple
                }

                $this->handleRequest($request);

            } catch (\Exception $e) {
                Log::error('MCP Error', ['error' => $e->getMessage()]);
                $this->sendError($request['id'] ?? null, -32603, $e->getMessage());
            }
        }
    }

    private function handleRequest(array $request)
    {
        $id = $request['id'] ?? null;
        $method = $request['method'];
        $params = $request['params'] ?? [];

        switch ($method) {
            case 'initialize':
                $this->sendResponse($id, [
                    'protocolVersion' => '2024-11-05',
                    'capabilities' => [
                        'tools' => [
                            'listChanged' => false
                        ],
                        'resources' => [],
                        'prompts' => []
                    ],
                    'serverInfo' => [
                        'name' => 'laravel-boost-mcp',
                        'version' => '1.0.0'
                    ]
                ]);
                break;

            case 'notifications/initialized':
                // No response needed for notifications
                break;

            case 'tools/list':
                $this->sendResponse($id, [
                    'tools' => $this->getTools()
                ]);
                break;

            case 'tools/call':
                $this->handleToolCall($id, $params);
                break;
                
            case 'ping':
                 $this->sendResponse($id, "pong");
                 break;

            default:
                // Ignore unknown notifications, error on unknown requests
                if ($id !== null) {
                   $this->sendError($id, -32601, "Method not found: $method");
                }
                break;
        }
    }

    private function getTools(): array
    {
        return [
            [
                'name' => 'list_modules',
                'description' => 'List all modules in the system with their status',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [],
                ]
            ],
            [
                'name' => 'create_module',
                'description' => 'Create a new module with backend, frontend, and Git integration',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'moduleName' => [
                            'type' => 'string',
                            'description' => 'Name of the module (plural, lowercase, e.g., "products")'
                        ],
                        'displayName' => [
                            'type' => 'string',
                            'description' => 'Display name (e.g., "Products")'
                        ],
                        'displayNameSingular' => [
                            'type' => 'string',
                            'description' => 'Singular display name (e.g., "Product")'
                        ],
                        'fields' => [
                            'type' => 'array',
                            'description' => 'List of fields',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'name' => ['type' => 'string'],
                                    'type' => ['type' => 'string', 'enum' => ['string', 'number', 'boolean', 'date', 'text', 'email', 'file', 'image']],
                                    'required' => ['type' => 'boolean'],
                                    'label' => ['type' => 'string']
                                ],
                                'required' => ['name', 'type']
                            ]
                        ],
                        'gitConfig' => [
                            'type' => 'object',
                            'properties' => [
                                'createBranch' => ['type' => 'boolean'],
                                'repositorySlug' => ['type' => 'string', 'description' => 'Owner/Repo slug (e.g., "smt197/ressurex-backend-automation")']
                            ]
                        ]
                    ],
                    'required' => ['moduleName', 'displayName', 'displayNameSingular']
                ]
            ],
            [
                'name' => 'get_deployment_status',
                'description' => 'Get the status of recent deployments',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'limit' => ['type' => 'integer', 'default' => 5]
                    ]
                ]
            ]
        ];
    }

    private function handleToolCall($id, array $params)
    {
        $name = $params['name'];
        $args = $params['arguments'] ?? [];

        try {
            $result = null;

            switch ($name) {
                case 'list_modules':
                    $modules = ModuleManager::all(['module_name', 'display_name', 'enabled', 'created_at']);
                    $result = ['content' => [['type' => 'text', 'text' => json_encode($modules, JSON_PRETTY_PRINT)]]];
                    break;

                case 'create_module':
                    $output = $this->moduleService->createModule($args);
                    $result = ['content' => [['type' => 'text', 'text' => json_encode($output, JSON_PRETTY_PRINT)]]];
                    break;

                case 'get_deployment_status':
                    $limit = $args['limit'] ?? 5;
                    $deployments = Deployment::latest()->take($limit)->get();
                    $result = ['content' => [['type' => 'text', 'text' => json_encode($deployments, JSON_PRETTY_PRINT)]]];
                    break;

                default:
                    $this->sendError($id, -32601, "Tool not found: $name");
                    return;
            }

            $this->sendResponse($id, $result);

        } catch (\Exception $e) {
            $this->sendError($id, -32603, "Tool execution failed: " . $e->getMessage());
        }
    }

    private function sendResponse($id, $result)
    {
        $response = [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => $result
        ];
        echo json_encode($response) . "\n";
        flush();
    }

    private function sendError($id, $code, $message)
    {
        $response = [
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => $code,
                'message' => $message
            ]
        ];
        echo json_encode($response) . "\n";
        flush();
    }
}
