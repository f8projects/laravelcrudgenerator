<?php

namespace f8projects\laravelcrudgenerator\Commands;

use Illuminate\Support\Facades\File;
use Illuminate\Console\Command;

class GeneratorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
     protected $signature = 'crud:generator';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create CRUD operations';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if ($this->confirm('Do you wish to create app layout?'))
        $this->line("Creating layout ................................ " . (($this->layoutPut()) ? 'Success' : 'Failed'));

        $this->line("Setting model name  ............................ " . (($this->parseInputName($this->ask('Input Model name (etc. Car)'))) ? 'Success' : 'Failed'));

        $this->line("\nInput model's fields. Etc. name,description separated by comma");
        $this->line("Example: name:column_type:input_type,name:input_type,name::input_type");
        $this->line("\nColumn types: boolean, string, text, longText. Default: string");
        $this->line("Input types: text, textarea. Default: text");
        $this->parseInputFields($this->ask('Input fields'));

        $this->info("[Creating models and migrations]");
        $this->line("Creating model of [{$this->modelName}] ....................... " . (($this->modelPut()) ? 'Success' : 'Failed'));
        $this->line("Creating migration for [{$this->modelName}] .................. " . (($this->modelMigrationPut()) ? 'Success' : 'Failed'));

        $this->info("\n[Creating controller, repository and requests]");
        $this->line("Creating controller for [{$this->modelName}Controller] ....... " . (($this->controllerPut()) ? 'Success' : 'Failed'));
        $this->line("Creating Repository ............................ " . (($this->repositoryPut()) ? 'Success' : 'Failed'));
        $this->line("Creating Store and Update requests for [{$this->modelName}] .. " . (($this->requestPut()) ? 'Success' : 'Failed'));

        $this->info("\n[Creating Routes and views]");
        $this->line("Append routes for [{$this->modelName}] ....................... " . (($this->routesPut()) ? 'Success' : 'Failed'));
        $this->line("Creating index view for [{$this->modelName}] ................. " . (($this->indexViewPut()) ? 'Success' : 'Failed'));
        $this->line("Creating create/edit view for [{$this->modelName}] ........... " . (($this->createViewPut()) ? 'Success' : 'Failed'));

        $this->line("\nAll done!");
    }

    protected function parseInputName($input)
    {
        $this->modelName = $input;

        return true;
    }

    protected function parseInputFields($input)
    {
        $values = explode(",", $input);

        foreach ($values as $value) {
            $options = explode(":", $value);

            if(!isset($options[1]) || $options[1] == NULL)
                $options[1] = 'string';

            if(!isset($options[2]) || $options[2] == NULL)
                $options[2] = 'text';

            $fields[] = [
                'name' => $options[0],
                'column_type' => $options[1],
                'input_type' => $options[2],
            ];
        }

        return $this->inputData = $fields;
    }

    protected function layoutPut()
    {
        if(!file_exists($path = resource_path('/views/layouts')))
        mkdir($path, 0777, true);

        file_put_contents($path . '/app.blade.php', $this->getStub('layouts/appLayout'));

        return true;
    }

    protected function modelPut()
    {
        if(!file_exists($path = app_path('/Models')))
        mkdir($path, 0777, true);

        $fillableFields = '';

        foreach ($this->inputData as $value) {
            $fillableFields .= "\n        '{$value['name']}',";
        }

        $output = str_replace(
            [
                '{{fillableFields}}',
            ],
            [
                $fillableFields,
            ],
            $this->replaceNamesAndGet('models/Model')
        );

        file_put_contents($path . "/{$this->modelName}.php", $output);

        return true;
    }

    protected function modelMigrationPut()
    {
        $migrationFields = '';

        foreach ($this->inputData as $value) {
            $migrationFields .= "\n            \$table->{$value['column_type']}('{$value['name']}');";
        }

        $output = str_replace(
            [
                '{{migrationFields}}',
            ],
            [
                $migrationFields,
            ],
            $this->replaceNamesAndGet('migrations/Migration')
        );

        file_put_contents(database_path("/migrations/" . date('Y_m_d_His') . "_create_" . strtolower(str_plural($this->modelName)) . "_table.php"), $output);

        return true;
    }

    protected function controllerPut()
    {
        file_put_contents(app_path("/Http/Controllers/{$this->modelName}Controller.php"), $this->replaceNamesAndGet('controllers/ModelController'));

        return true;
    }

    protected function repositoryPut()
    {
        if(!file_exists($path = app_path('/Repositories')))
        mkdir($path, 0777, true);

        file_put_contents($path . '/Repository.php', $this->getStub('repositories/Repository'));
        file_put_contents($path . '/RepositoryInterface.php', $this->getStub('repositories/RepositoryInterface'));

        return true;
    }

    protected function requestPut()
    {
        if(!file_exists($path = app_path('/Http/Requests')))
        mkdir($path, 0777, true);

        $ruless = '';

        foreach ($this->inputData as $value) {
            $ruless .= "\n            '{$value['name']}' => 'required',";
        }

        $errors = '';

        foreach ($this->inputData as $value) {
            $errors .= "\n            '{$value['name']}.required' => 'Email is required!',";
        }

        $fillData = '';

        foreach ($this->inputData as $value) {
            $fillData .= "\n            '{$value['name']}' => \$this->{$value['name']},";
        }

        $outputStore = str_replace(
            [
                '{{rules}}',
                '{{errors}}',
                '{{fillData}}',
            ],
            [
                $ruless,
                $errors,
                $fillData,
            ],
            $this->replaceNamesAndGet('requests/ModelStoreRequest')
        );

        $outputUpdate = str_replace(
            [
                '{{rules}}',
                '{{errors}}',
                '{{fillData}}',
            ],
            [
                $ruless,
                $errors,
                $fillData,
            ],
            $this->replaceNamesAndGet('requests/ModelUpdateRequest')
        );

        file_put_contents($path . "/{$this->modelName}StoreRequest.php", $outputStore);
        file_put_contents($path . "/{$this->modelName}UpdateRequest.php", $outputUpdate);

        return true;
    }

    protected function routesPut()
    {
        File::append(base_path('routes/web.php'), "\n\nRoute::resource('" . str_plural(strtolower($this->modelName)) . "', '{$this->modelName}Controller');");

        return true;
    }

    protected function indexViewPut()
    {
        if(!file_exists($path = resource_path('/views/' . strtolower($this->modelName))))
        mkdir($path, 0777, true);

        $th = '';

        foreach ($this->inputData as $value) {
            $th .= "\n                <th>" . ucfirst($value['name']) . "</th>";
        }

        $td = '';

        foreach ($this->inputData as $value) {
            $td .= "\n                <td>{{\$" . strtolower($this->modelName) . "->{$value['name']}}}</td>";
        }

        $output = str_replace(
            [
                '{{th}}',
                '{{td}}',
            ],
            [
                $th,
                $td,
            ],
            $this->replaceNamesAndGet('views/IndexView')
        );

        file_put_contents($path . '/index.blade.php', $output);

        return true;
    }

    protected function createViewPut()
    {
        if(!file_exists($path = resource_path('/views/' . strtolower($this->modelName))))
        mkdir($path, 0777, true);

        $formInputs = '';
        $dataContent = '';

        foreach ($this->inputData as $value) {
            $formInputs .= str_replace(
                [
                    '{{fieldTitle}}',
                    '{{fieldName}}',
                ],
                [
                    ucfirst($value['name']),
                    $value['name'],
                ],
                $this->replaceNamesAndGet('shared/' . $value['input_type'])
            );
        }

        foreach ($this->inputData as $value) {
            $dataContent .= str_replace(
                [
                    '{{fieldTitle}}',
                    '{{fieldName}}',
                ],
                [
                    ucfirst($value['name']),
                    $value['name'],
                ],
                $this->replaceNamesAndGet('shared/show')
            );
        }

        $createOutput = str_replace(
            [
                '{{formInputs}}',
            ],
            [
                $formInputs,
            ],
            $this->replaceNamesAndGet('views/CreateView')
        );

        $editOutput = str_replace(
            [
                '{{formInputs}}',
            ],
            [
                $formInputs,
            ],
            $this->replaceNamesAndGet('views/EditView')
        );

        $showOutput = str_replace(
            [
                '{{dataContent}}',
            ],
            [
                $dataContent,
            ],
            $this->replaceNamesAndGet('views/ShowView')
        );

        file_put_contents($path . '/create.blade.php', $createOutput);
        file_put_contents($path . '/edit.blade.php', $editOutput);
        file_put_contents($path . '/show.blade.php', $showOutput);

        return true;
    }

    protected function getStub($type)
    {
        return file_get_contents(resource_path("LaravelCrudGeneratorStubs/$type.stub"));
    }

    protected function getFormField($type)
    {
        return "\n" . $this->getStub("shared/$type");
    }

    protected function replaceNamesAndGet($stub)
    {
        return str_replace(
            [
                '{{modelName}}',
                '{{modelNamePluralLowerCase}}',
                '{{modelNamePlural}}',
                '{{modelNameSingularLowerCase}}',
            ],
            [
                $this->modelName,
                strtolower(str_plural($this->modelName)),
                str_plural($this->modelName),
                strtolower($this->modelName),
            ],
            $this->getStub($stub)
        );
    }
}
