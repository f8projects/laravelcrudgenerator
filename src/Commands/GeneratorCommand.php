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

        $this->line("Setting model name  ............................ " . (($this->parseModelName($this->ask('Input Model name (etc. Car)'))) ? 'Success' : 'Failed'));

        $this->line("\nInput model's fields. Etc. name,description separated by comma");
        $this->line("Example: name:db_type:html_type,name:html_type,name::html_type");
        $this->line("\nColumn types: boolean, string, text, longText. Default: string");
        $this->line("Input types: text, textarea. Default: text");
        $this->parseModelFields($this->ask('Input fields'));

        $this->info("[Creating models and migrations]");
        $this->line("Creating model of [{$this->modelName}] ....................... " . (($this->modelPut()) ? 'Success' : 'Failed'));
        $this->line("Creating migration for [{$this->modelName}] .................. " . (($this->modelMigrationPut()) ? 'Success' : 'Failed'));

        $this->info("\n[Creating controller, repository and requests]");
        $this->line("Creating controller for [{$this->modelName}Controller] ....... " . (($this->controllerPut()) ? 'Success' : 'Failed'));

        $this->info("\n[Creating Routes and views]");
        $this->line("Append routes for [{$this->modelName}] ....................... " . (($this->routesPut()) ? 'Success' : 'Failed'));
        $this->line("Creating index view for [{$this->modelName}] ................. " . (($this->indexViewPut()) ? 'Success' : 'Failed'));

        $this->info("\n[Creating Exception handlers]");
        $this->line("Creating Handler ................. " . (($this->handlerPut()) ? 'Success' : 'Failed'));

        $this->line("\nAll done!");
    }

    protected function parseModelName($input)
    {
        $this->modelName = ucfirst($input);

        return true;
    }

    protected function parseModelFields($input)
    {
        $inputFields = explode(",", $input);

        foreach ($inputFields as $field) {
            $fieldData = explode(":", $field);

            if(!isset($fieldData[1]) || $fieldData[1] == NULL)
                $fieldData[1] = 'string';

            if(!isset($fieldData[2]) || $fieldData[2] == NULL)
                $fieldData[2] = 'text';

            $array[] = [
                'name' => $fieldData[0],
                'db_type' => $fieldData[1],
                'html_type' => $fieldData[2],
            ];
        }

        return $this->modelFields = $array;
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

        foreach ($this->modelFields as $modelField) {
            $fillableFields .= "\n        '{$modelField['name']}',";
        }

        $output = str_replace(
            [
                '{{fillableFields}}',
            ],
            [
                $fillableFields,
            ],
            $this->replaceNouns('models/Model')
        );

        file_put_contents($path . "/{$this->modelName}.php", $output);

        return true;
    }

    protected function modelMigrationPut()
    {
        $migrationCommands = '';

        foreach ($this->modelFields as $modelField) {
            $migrationCommands .= "\n            \$table->{$modelField['db_type']}('{$modelField['name']}');";
        }

        $output = str_replace(
            [
                '{{migrationCommands}}',
            ],
            [
                $migrationCommands,
            ],
            $this->replaceNouns('migrations/Migration')
        );

        file_put_contents(database_path("/migrations/" . date('Y_m_d_His') . "_create_" . strtolower(str_plural($this->modelName)) . "_table.php"), $output);

        return true;
    }

    protected function controllerPut()
    {
        $modelRules = '';

        foreach ($this->modelFields as $modelField) {
            $modelRules .= "\n            '{$modelField['name']}' => 'required',";
        }

        $output = str_replace(
            [
                '{{modelRules}}',
            ],
            [
                $modelRules,
            ],
            $this->replaceNouns('controllers/ModelController')
        );

        file_put_contents(app_path("/Http/Controllers/{$this->modelName}Controller.php"), $output);

        return true;
    }

    protected function routesPut()
    {
        file_put_contents(app_path("Exceptions/Handler.php"), $this->getStub('exceptions/Handler'));

        return true;
    }

    protected function handlerPut()
    {
        File::append(base_path('routes/web.php'), "\n\nRoute::resource('" . str_plural(strtolower($this->modelName)) . "', '{$this->modelName}Controller', ['only' => ['index', 'show', 'store', 'update', 'destroy']]);");

        return true;
    }

    protected function indexViewPut()
    {
        if(!file_exists($path = resource_path('/views/' . strtolower($this->modelName))))
        mkdir($path, 0777, true);

        $thColumns = '';

        foreach ($this->modelFields as $modelField) {
            $thColumns .= "\n                <th>" . ucfirst($modelField['name']) . "</th>";
        }

        $tdColumns = '';

        foreach ($this->modelFields as $modelField) {
            $tdColumns .= "\n                <td>{{\$" . strtolower($this->modelName) . "->{$modelField['name']}}}</td>";
        }

        $ajaxFill = '';

        foreach ($this->modelFields as $modelField) {
            $ajaxFill .= "\n                $(\"#" . strtolower($this->modelName) . "-form input[name$='{$modelField['name']}']\" ).val(data.{$modelField['name']})";
        }

        $viewDataFill = '';

        foreach ($this->modelFields as $modelField) {
            $viewDataFill .= "\n                $('.view-{$modelField['name']}')
                    .html('')
                    .append(
                        $('<strong>')
                            .append('" . ucfirst($modelField['name']) . "'))
                            .append(
                                $('<span>')
                                    .text(' ' + data.{$modelField['name']})
                    )";
        }

        $viewDataRows = '';

        foreach ($this->modelFields as $modelField) {
            $viewDataRows .= "\n            <div class=\"view-{$modelField['name']}\"></div>";
        }

        $tableRowHtml = "$('<tr>')
                                        .append(
                                            $('<th>')
                                                .attr('scope', 'row')
                                                .append(response.id)
                                        )";

        foreach ($this->modelFields as $modelField) {
            $tableRowHtml .= "\n                                        .append(
                                            $('<td>')
                                                .text(response.{$modelField['name']})
                                        )";
        }

        $tableRowHtml .= "\n                                        .append(
                                            $('<td>')
                                                .append(
                                                    $('<a>')
                                                        .attr('href', 'javascript:void(0);')
                                                        .attr('data-id', response.id)
                                                        .attr('title', 'view')
                                                        .addClass('btn btn-outline-secondary btn-sm " . strtolower($this->modelName) . "-view')
                                                        .append(
                                                            $('<i>')
                                                                .addClass('fas fa-eye')
                                                        )
                                                )
                                                .append(' ')
                                                .append(
                                                    $('<a>')
                                                        .attr('href', 'javascript:void(0);')
                                                        .attr('data-id', response.id)
                                                        .attr('title', 'edit')
                                                        .addClass('btn btn-outline-secondary btn-sm " . strtolower($this->modelName) . "-edit')
                                                        .append(
                                                            $('<i>')
                                                                .addClass('fas fa-edit')
                                                        )
                                                )
                                                .append(' ')
                                                .append(
                                                    $('<a>')
                                                        .attr('href', 'javascript:void(0);')
                                                        .attr('data-id', response.id)
                                                        .attr('title', 'delete')
                                                        .addClass('btn btn-outline-secondary btn-sm " . strtolower($this->modelName) . "-delete')
                                                        .append(
                                                            $('<i>')
                                                                .addClass('fas fa-trash')
                                                        )
                                                )
                                        )";


        $formElements = '';

        foreach ($this->modelFields as $modelField) {
            $formElements .= str_replace(
                [
                    '{{fieldTitle}}',
                    '{{fieldName}}',
                ],
                [
                    ucfirst($modelField['name']),
                    $modelField['name'],
                ],
                $this->replaceNouns('shared/' . $modelField['html_type'])
            );
        }

        $output = str_replace(
            [
                '{{thColumns}}',
                '{{tdColumns}}',
                '{{formElements}}',
                '{{ajaxFill}}',
                '{{tableRowHtml}}',
                '{{viewDataRows}}',
                '{{viewDataFill}}',
            ],
            [
                $thColumns,
                $tdColumns,
                $formElements,
                $ajaxFill,
                $tableRowHtml,
                $viewDataRows,
                $viewDataFill,
            ],
            $this->replaceNouns('views/IndexView')
        );

        file_put_contents($path . '/index.blade.php', $output);

        return true;
    }

    protected function getStub($stub)
    {
        return file_get_contents(resource_path("LaravelCrudGeneratorStubs/$stub.stub"));
    }

    protected function replaceNouns($stub)
    {
        return str_replace(
            [
                '{{modelNameSingular}}',
                '{{modelNameSingularLowerCase}}',
                '{{modelNamePlural}}',
                '{{modelNamePluralLowerCase}}',
            ],
            [
                $this->modelName,                           // Test
                strtolower($this->modelName),               // test
                str_plural($this->modelName),               // Tests
                strtolower(str_plural($this->modelName)),   // tests
            ],
            $this->getStub($stub)
        );
    }
}
