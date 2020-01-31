<?php 
namespace Megaads\Clara\Commands;

use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

class ModuleSubmitCommand extends AbtractCommand 
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'module:submit 
                            {--update : [boolean] Set update module mode} 
                            {--module= : [string] Module name} 
                            {--branch= : [string] Branch name. Default is master} 
                            {--updateDesc= : [string] Update description of module} 
                            {--updateNamesapce= : [string] Update module namespace. Default auto generate from module name}';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Submit a new module or Update exist module';
    /**
     * Default repository branch name
     *
     * @var string
     */
    protected $defaultBranch = 'master';
    /**
     * Execute the console command.
     */
    public function handle() {
        $isUpdate = $this->option('update');
        if ($isUpdate) {
            $branch = ($this->option('branch')) ? $this->option('branch') : $this->defaultBranch;
            $moduleName = ($this->option('module')) ? $this->option('module') : NULL;
            $moduleDesc = ($this->option('updateDesc')) ? $this->option('updateDesc') : NULL;
            $moduleNamespace = ($this->option('updateNamesapce')) ? $this->option('updateNamesapce') : NULL;
            $moduleImage = '';
            if (!$moduleName) {
                echo "Please enter the name of module that you want to update.\n Or run command php artisan module:submit --help to some information\n";
                exit();
            } else {
                $findPackage = DB::table('app')->where('name', $moduleName)->first();
                if ($findPackage) {
                    $repo = $findPackage->repository;
                    if (!$moduleDesc) {
                        $moduleDesc = $findPackage->description;
                    }
                    if (!$moduleNamespace) {
                        $moduleNamespace = $this->buildModuleNamespace($findPackage->name);
                    }
                }
            }
        } else {
            $repo = $this->ask('What repository to deploy?');
            $validate = $this->validateRepoUrl($repo);
            //Run question util repository url  validated
            while(!$validate['status']) {
                echo "Wrong " . $validate['type'] . " repository. Please try again! \n";
                $repo = $this->ask('What repository to deploy?');
                $validate = $this->validateRepoUrl($repo);
            }
            $branch = $this->ask('What branch do you like to pull?', $this->defaultBranch);
            $moduleName = $this->ask('What do you like package name?', ' ');
            $moduleImage = $this->ask('Enter image of this package.', ' ');
            $moduleDesc = $this->ask('Some description for this package.', ' ');
            $moduleNamespace = $this->ask('Name space of Module?', ' ');
            //Auto build module name if it null.
            if ($moduleName == ' ') {
                $moduleName = $this->getModuleName($repo, $validate['type']);
            }
            //Build module namespace if it null.
            if ($moduleNamespace == ' ') {
                $moduleNamespace = $this->buildModuleNamespace($moduleName);
            }
        }
        // Clone or pull from repository url
        $this->downloadPackage($repo, $branch, $moduleName);
        // Build insert params
        $insertParams = [
            'name' => $moduleName,
            'name_space' => $moduleNamespace,
            'package_url' => url('/modules/' . $moduleName . '.zip'),
            'description' => $moduleDesc, 
            'image' => $moduleImage,
            'repository' => $repo,
            'category_id' => 1, 
            'developer_id' => 1
        ];
        // Call function to insert params.
        $this->saveOrUpdateModule($insertParams);
    }
    /**
     * Validate repository url format
     *
     * @param [type] $repoUrl
     * @return void
     */
    private function validateRepoUrl($repoUrl) {
        $retval = [
            'type' => '',
            'status' => false
        ];
        // Check repo is bitbucket
        if (preg_match('/bitbucket.org/i', $repoUrl)) {
            $retval['type']='bitbucket';
        }
        // Or repo is github
        if (preg_match('/github.com/i', $repoUrl)) {
            $retval['type'] = 'github';
        }
        if (strpos($repoUrl, '.git') !== false) {
            $retval['status'] = true;
        }
        return $retval;
    }
    /**
     * Auto generate module name from repository url.
     *
     * @param [type] $repoUrl
     * @param [type] $type
     * @return void
     */
    private function getModuleName($repoUrl, $type) {
        $retval = '';
        $urls = explode('/', $repoUrl);
        $lastPath = end($urls);
        $lastPath = str_replace('.git', '', $lastPath);
        $arrLastPath = explode('-', $lastPath);
        if (count($arrLastPath) > 1) {
            foreach ($arrLastPath as $item) {
                $retval .= ucfirst($item);
            }
        } else {
            $retval = ucfirst($arrLastPath[0]);
        }
        return $retval;
    }
    /**
     * Process to download module from repository
     *
     * @param [type] $repo
     * @param [type] $branch
     * @param [type] $name
     * @return void
     */
    private function downloadPackage($repo, $branch, $name) {
        //Check module is exists on modules directory
        $modulePath = public_path('/modules/' . $name);
        $result = $this->checkModuleDirectory($modulePath);
        $findGitLib = new Process("which git");
        $findGitLib->run();
        $gitPath = $findGitLib->getOutput();
        $gitPath = str_replace("\n", "", $gitPath);
        if ($gitPath) {
           $processResult = $this->processWithRepository($result, $repo, $modulePath, $branch, $gitPath);
           if ($processResult) {
                $this->compressDirectory($name);
                $this->removeDirectory($name);
           }
        }
    }
    /**
     * Save or Update a module to database
     *
     * @param [type] $params
     * @return void
     */
    private function saveOrUpdateModule($params) {
        $packageImage = $params['image'];
        unset($params['image']);
        //Find package by download url.
        $findPackage = DB::table('app')->where('package_url', $params['package_url'])->first();
        if ($findPackage) {
            DB::table('app')->where('id', $findPackage->id)->update($params);
        } else {
            $getId = DB::table('app')->insertGetId($params);
            if ($getId) {
                DB::table('app_gallery')->insert([
                    'app_id' => $getId,
                    'image_url' => $packageImage
                ]);
            }
        }
    }
    /**
     * Compress module directory
     *
     * @param [type] $moduleName
     * @return void
     */
    private function compressDirectory($moduleName) {
        $modulePath = public_path('modules/' . $moduleName);
        $zipFileName = $moduleName . ".zip";
        $zipFile = new \ZipArchive();
        $zipFile->open(public_path('modules/' . $zipFileName), \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($modulePath));
        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = $moduleName . '/' . substr($filePath, strlen($modulePath) + 1);
                $zipFile->addFile($filePath, $relativePath);
            }
        }
        $zipFile->close();
    }
    /**
     * Delete module directory after compress as zip file
     *
     * @param [type] $moduleName
     * @return void
     */
    private function removeDirectory($moduleName) {
        $modulePath = public_path('modules/' . $moduleName);
        $deleteDir = new Process("rm -rf $modulePath");
        $deleteDir->run();
    }
    /**
     * Clone or pull update from repository url
     *
     * @param [type] $type
     * @param [type] $repo
     * @param [type] $path
     * @param [type] $branch
     * @param [type] $gitPath
     * @return void
     */
    private function processWithRepository($type, $repo, $path, $branch, $gitPath) {
        $retval = true;
        if ($type == 'created') {
            echo "Cloning....\n";
            $command = "$gitPath clone $repo $path";
            $clonePackage = new Process($command);
            $clonePackage->run();
            if (!$clonePackage->isSuccessful()) {
                $retval = false;
            }
            if ($branch != $this->defaultBranch) {
                $this->processWithRepository('existed', $repo, $path, $branch, $gitPath);
            }
        } else {
            echo "Checking out branch $branch....\n";
            $checkoutCommand = "cd $path && $gitPath fetch && $gitPath checkout $branch";
            $output = shell_exec($checkoutCommand);
        }
        return $retval;
    }
    /**
     * Check module is exists in public/modules directory
     *
     * @param [type] $modulePath
     * @return void
     */
    private function checkModuleDirectory($modulePath) {
        $retval = 'created';
        if (!file_exists($modulePath)) {
            mkdir($modulePath, 0777);
        } else {
            $retval = 'existed';
        }
        return $retval;
    }
    /**
     * Auto generate module namespace from module name
     *
     * @param [type] $moduleName
     * @return void
     */
    private function buildModuleNamespace($moduleName) {
        $retval = '';
        $pieces = preg_split('/(?=[A-Z])/', $moduleName);
        foreach($pieces as $piece) {
            if ($piece !== '') {
                $retval .= strtolower($piece) . '-';
            }
        }
        return rtrim($retval, '-');
    }
}