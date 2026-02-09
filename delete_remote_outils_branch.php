<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\GithubApiService;

$api = new GithubApiService();
$owner = 'smt197';
$repo = 'ressurex-backend-automation';
$branch = 'module/outils';

echo "Deleting branch $branch on $owner/$repo...\n";
$result = $api->deleteBranchOnGithub($owner, $repo, $branch);

if ($result) {
    echo "SUCCESS: Branch deleted.\n";
} else {
    echo "FAILURE: Could not delete branch (might not exist or other error).\n";
}
